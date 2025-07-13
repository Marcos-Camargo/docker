<?php

/**
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_category $model_category
 * @property Model_products $model_products
 * @property Model_groups $model_groups
 * @property Model_users $model_users
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 * @property CSV_Validation $csv_validation
 *
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_Router $router
 */
class ChangeProductCategory extends BatchBackground_Controller
{
    /**
     * @var object 
     */
    private $fields_csv = array(
        'old_code'  => 'Código Antigo',
        'new_code'  => 'Código Novo'
    );

    /**
     * @var array $data_users
     */
    private $data_users;

    /**
     * @var array $data_groups_by_user
     */
    private $data_groups_by_user;

    /**
     * @var array $data_categories
     */
    private $data_categories;

    /**
     * @var array $errors_import
     */
    private $errors_import;

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_csv_to_verifications');
        $this->load->model('model_category');
        $this->load->model('model_products');
        $this->load->model('model_groups');
        $this->load->model('model_users');
        $this->load->model('model_queue_products_marketplace');
        $this->load->library('CSV_Validation');
    }
    
    // php index.php BatchC/CSVFileProcessing/ChangeProductCategory run
    public function run($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = __CLASS__ . '/' . __FUNCTION__;

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . __CLASS__;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params));

        $csvs_to_import = $this->model_csv_to_verifications->getDontChecked(false, 'ChangeProductCategory');
        foreach ($csvs_to_import as $csv_import) {
            $this->importCsv($csv_import);
        }

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }

    /**
     * Faz a validação inicial antes de ler as linhas
     *
     * @param   array   $fileCsv
     * @throws  Exception
     */
    private function initValidation(array $fileCsv)
    {
        $user_data  = $this->data_users[$fileCsv['user_id']] ?? null;
        $user_group = $this->data_groups_by_user[$fileCsv['user_id']] ?? null;

        if (is_null($user_data)) {
            $user_data = $this->data_users[$fileCsv['user_id']] = $this->model_users->getUserData($fileCsv['user_id']);
            $this->data_users = $user_data;
        }

        if (is_null($user_group)) {
            $user_group = $this->data_groups_by_user[$fileCsv['user_id']] = $this->model_groups->getUserGroupByUserId($fileCsv['user_id']);
        }

        // Não encontrou o grupo de usuário.
        if (empty($user_group)) {
            throw new Exception("Grupo do usuário não encontrado.");
        }

        // Usuário precisa ser admin.
        if (!$user_group['only_admin']) {
            throw new Exception("Somente administradores podem fazer o envio de arquivos.");
        }
    }

    /**
     * Importa o arquivo csv
     *
     * @param array $csv
     */
    public function importCsv(array $csv)
    {
        $this->errors_import = array();

        echo "Inciando para o csv: {$csv["upload_file"]}\n";

        try {
            $products = $this->csv_validation->convertCsvToArray($csv["upload_file"]);
        } catch (Exception $e) {
            $message = "O arquivo deve estar no formato UTF8, caso contrário alguns caracteres podem ficar desconfigurados.";
            $this->errors_import[] = array(
                "line"      => 0,
                "message"   => $message
            );

            echo "$message\n";
            $this->saveFinalImportFile($csv['id']);
            return;
        }

        try {
            $this->initValidation($csv);
        } catch (Exception $exception) {
            $message = $exception->getMessage();
            $this->errors_import[] = array(
                "line"      => 0,
                "message"   => $message
            );

            echo "$message\n";
            $this->saveFinalImportFile($csv['id']);
            return;
        }

        $headers = array_map(function($header) {
            return detectUTF8($header);
        }, $products->getHeader());

        // O código da categoria não pode ser em branco.
        if (!in_array($this->fields_csv['old_code'], $headers) || !in_array($this->fields_csv['new_code'], $headers)) {
            $message = 'Deve ser informado todos os campos para a atualização.';
            $this->errors_import[] = array(
                "line"      => 0,
                "message"   => $message
            );

            echo "$message\n";
            $this->saveFinalImportFile($csv['id']);
            return;
        }

        foreach ($products as $line => $value) {
            $line = $line + 1;

            // Vê se precisa transformar para utf8 a chave da linha.
            foreach ($value as $key_line => $v_) {
                $value[detectUTF8($key_line)] = $v_;

                if ($key_line !== detectUTF8($key_line)) {
                    unset($value[$key_line]);
                }
            }

            // Linha em branco.
            if ($this->csv_validation->lineEmptyCheck($value)) {
                echo "Linha $line em branco\n";
                continue;
            }

            // O código da categoria não pode ser em branco.
            if (empty($value[detectUTF8($this->fields_csv['old_code'])]) || empty($value[detectUTF8($this->fields_csv['new_code'])])) {
                $message = "Código está em branco. Novo({$value[detectUTF8($this->fields_csv['new_code'])]}) e Antigo({$value[detectUTF8($this->fields_csv['old_code'])]})";
                $this->errors_import[] = array(
                    "line"      => $line,
                    "message"   => $message
                );
                echo "[LINE: $line] $message\n";
                continue;
            }

            $old_code = $value[detectUTF8($this->fields_csv['old_code'])];
            $new_code = $value[detectUTF8($this->fields_csv['new_code'])];

            echo "Alterando da categoria $old_code para $new_code\n";

            // Não pode trocar para a mesma categoria.
            if (trim($old_code) == trim($new_code)) {
                $message = "Códigos são iguais. ($new_code)";
                $this->errors_import[] = array(
                    "line"      => $line,
                    "message"   => $message
                );
                echo "[LINE: $line] $message\n";
                continue;
            }

            $old_category_data = $this->data_categories[trim($old_code)] ?? null;
            $new_category_data = $this->data_categories[trim($new_code)] ?? null;
            if (is_null($old_category_data)) {
                $old_category_data = $this->data_categories[trim($old_code)] = $this->model_category->getDataByNameorId(trim($old_code));
            }
            if (is_null($new_category_data)) {
                $new_category_data = $this->data_categories[trim($new_code)] = $this->model_category->getDataByNameorId(trim($new_code));
            }

            // As duas categorias devem existir.
            if (empty($old_category_data) || empty($new_category_data)) {
                $status_old_categorya = empty($old_category_data) ? 'não encontrada' : 'encontrada';
                $status_new_categorya = empty($new_category_data) ? 'não encontrada' : 'encontrada';
                $message = "Categoria não foi encontrada. Novo($status_new_categorya) e Antigo($status_old_categorya)";
                $this->errors_import[] = array(
                    "line"      => $line,
                    "message"   => $message
                );
                echo "[LINE: $line] $message\n";
                continue;
            }

            $old_category_id = $old_category_data['id']; // 0000 = Categoria antiga do CSV.
            $new_category_id = json_encode(array((string)$new_category_data['id'])); // 1111 Categoria nova do CSV. Codifica para ficar no padrão do banco ["1111"].

            while (true) {
                // Consulta nos produtos com essa categoria com limite de 500 registros. Fazer a consulta na model.
                $rowsToSendQueue = $this->model_products->getByCategoryWithLimit($old_category_id, 500, 'id, category_id');

                // Acabou os registros;
                if (empty($rowsToSendQueue)) {
                    break;
                }

                // Verifica se a fila está cheia.
                while ($this->model_queue_products_marketplace->countQueue()['qtd'] > 500) {
                    echo "Fila com muitos produtos, aguardar 60 segundos para checar novamente\n";
                    sleep(60);
                }

                echo "Adicionado ".count($rowsToSendQueue)." registros na fila.\n";

                // Atualiza a categoria. Fazer a consulta na model.
                $this->model_products->updateByProductIds(
                    array(
                        'category_id' => $new_category_id
                    ), array_map(function ($item) {
                        return $item['id'];
                    }, $rowsToSendQueue)
                );
            }
        }

        // Se existem erros, criará um arquivo com as linhas com erros.
        /*if (!empty($this->errors_import)) {
            try {
                $this->csv_validation->createNewFileCsv(str_replace('.csv', '_with_error.csv', $csv["upload_file"]), $this->new_file_with_error, $csv["upload_file"]);
            } catch (Exception $exception) {
                $this->errors_import[] = array(
                    "line"      => 0,
                    "message"   => $exception->getMessage()
                );
            }
        }*/

        $this->saveFinalImportFile($csv['id']);
    }

    /**
     * Salva o processamento.
     *
     * @param   int $csv_id
     */
    private function saveFinalImportFile(int $csv_id)
    {
        $processing_response = null;
        $situation = 'success';

        if (!empty($this->errors_import)) {
            // Ordena os erros de forma crescente.
            $errors_import_to_save = array();
            foreach (array_msort($this->errors_import, array('line' => SORT_ASC)) as $data) {
                $errors_import_to_save[] = $data;
            }

            $processing_response = json_encode($errors_import_to_save, JSON_UNESCAPED_UNICODE);
            $situation = 'err';
        }

        $this->model_csv_to_verifications->setChecked($csv_id, $situation, $processing_response);
    }
}
