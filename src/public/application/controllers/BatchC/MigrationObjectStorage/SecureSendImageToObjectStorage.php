<?php

/**
 * Envia todas imagens de produtos para o bucket.
 * Apresenta maior segurança por utilizar flags no banco para para a migração.
 * Utiliza paginação para realizar os envios com paralelismo.
 * Caso haja muita defasagem no tempo de inicio o mesmo produto pode ter as imagens enviadas duas vezes.
 * Contudo não há problema, visto que não há duplicação da chave, apenas sobrescrita.
 * 
 * @property     Bucket             $bucket
 * @property     Model_settings     $model_settings
 * @property     Model_job_schedule $model_job_schedule
 * @property     array              $sellercenter
 */
class SecureSendImageToObjectStorage extends BatchBackground_Controller
{

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = [
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        ];
        $this->session->set_userdata($logged_in_sess);
        $this->load->library("bucket");
        $this->load->model("model_settings");
        $this->sellercenter = $this->model_settings->getSettingDatabyName('sellercenter');
    }
    /**
     * php index.php BatchC/MigrationObjectStorage/SecureSendImageToObjectStorage run 
     */
    function run($id = null, $page = null, $limit = 10000)
    {
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob('MigrationObjectStorage/' . $this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data(
                'batch',
                $log_name,
                'Já tem um job rodando ou que foi cancelado',
                "E"
            );
            return;
        }

        // Cria os jobs  paginados.
        if ($page === 'null' || $page === null) {
            $this->load->model('model_job_schedule');
            $jobs_schedule = $this->model_job_schedule->getByModulePathAndStatus('MigrationObjectStorage/SecureSendImageToObjectStorage', [0, 1, 4, 6]);

            $jobs_check = array_filter($jobs_schedule, function ($job) {
                return count(explode(' ', $job['params'])) > 1;
            });

            if (!empty($jobs_check)) {
                echo "Ainda há jobs em execução.\n";
                $this->gravaFimJob();
                return true;
            }

            for ($x = 0; $x < 20; $x++) {
                $params = "$x";
                if ($limit != 10000) {
                    $params .= " $limit";
                }

                $this->db->insert('job_schedule', [
                    "module_path" => 'MigrationObjectStorage/SecureSendImageToObjectStorage',
                    "module_method" => "run",
                    'params' => $params,
                    'status' => '0',
                    'finished' => '0',
                    'error' => null,
                    'error_count' => '0',
                    'error_msg' => null,
                    'date_start' => date(DATETIME_INTERNATIONAL, strtotime('+1 minute', strtotime(dateNow()->format(DATETIME_INTERNATIONAL)))),
                    'date_end' => null,
                    'server_id' => '0'
                ]);

                echo "Criado migração de imagens para página $x\n";
            }
            $this->gravaFimJob();
            return true;
        }

        $success = $this->startSecureTransfer($page, $limit);
        if (!$success) {
            $this->log_data('batch', $log_name, 'Não foi possível realizar a transferência.', "E");
            return;
        }
        echo "Transferência realizada com sucesso.\n";

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Inicia a transferencia dos arquivos para a respectiva pasta no bucket.
     * @param    int    $page Página da transferência.
     * @param    int    $limit Limite pro página.
     * 
     * @return bool Retorna true caso tenha dado sucesso, falso caso tenha ocorrido algum erro.
     */
    function startSecureTransfer($page, $limit)
    {
        $offset = $page * $limit;
        $sql = "SELECT id, has_variants, image, principal_image FROM products WHERE is_on_bucket = 0 AND product_catalog_id IS NULL";
        $query = $this->db->query($sql . " LIMIT $limit OFFSET $offset");

        $anyLeft = $this->db->query($sql . " LIMIT 5");

        // Deleta a entrada no calendário após migrar todos produtos.
        if ($query->num_rows() == 0 && $anyLeft->num_rows() == 0) {
            $this->db->delete('calendar_events', ['module_path' => 'MigrationObjectStorage/SecureSendImageToObjectStorage']);
        }

        // Percorre cada produto.
        foreach ($query->result() as $key => $product) {

            // Trata produtos com image vazio.
            if (!$product->image || empty($product->image)) {
                echo "Produto com diretório de imagem inválido.\n";
                continue;
            }

            // Diretório da imagem do produto. Sem FCPATH para também ser o diretório no Bucket.
            $base_dir = 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'product_image' . DIRECTORY_SEPARATOR . $product->image;

            // Verifica se está válido, caso não exista, não há imagens nele, apenas seta como já enviado para não voltar no próximo job.
            if (!is_dir(FCPATH . $base_dir)) {
                echo "Diretório de imagem: $base_dir inválido.\n";
                $this->db->query("
                    UPDATE products p
                        SET p.is_on_bucket = 1,
                        p.date_update = p.date_update 
                        WHERE p.id = ?
                ", [$product->id]);
                continue;
            }

            // Realiza o envio do diretório deste produto.
            // TransferDir apenas retorna falso em caso de erro, portanto há beneficio de concorrência sem diminuição de confiabilidade.
            $transfer = $this->bucket->transferDirectory($base_dir);
            if ($transfer['success']) {

                // Busca a posição da pasta na imagem principal.
                $pos = strpos($product->principal_image, "assets/images/product_image");
                if ($pos === false) {
                    echo "Não há o url correto na imagem. Enviando o arquivo para o bucket sem alterar a URL\n";
                    $this->db->update("products", ["is_on_bucket" => 1], ['id' => $product->id]);
                    continue;
                }

                // Pega apenas a parte da imagem que é necessária, sem qualquer tipo de URL.
                $img = substr($product->principal_image, $pos);

                // Monta a nova URL.
                $new_principal_image = $this->bucket->getAssetUrl($img);
                $this->db->query("
                    UPDATE products p
                    SET p.is_on_bucket = 1,
                    p.principal_image = ?,
                    p.date_update = p.date_update 
                    WHERE p.id = ?
                ", [$new_principal_image, $product->id]);
            } else {
                echo $transfer['message'];
            }
        }
        return true;
    }
}
