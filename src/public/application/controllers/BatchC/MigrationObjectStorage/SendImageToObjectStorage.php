<?php

use Aws\Exception\AwsException;

/**
 * Envia todas imagens de produtos para o bucket.
 * @property Bucket $bucket
 * @property Model_settings $model_settings
 * @property array $sellercenter
 */
class SendImageToObjectStorage extends BatchBackground_Controller
{

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        $this->load->library("bucket");
        $this->load->model("model_settings");
        $this->sellercenter = $this->model_settings->getSettingDatabyName('sellercenter');
    }

    /**
     * php index.php BatchC/MigrationObjectStorage/SendImageToObjectStorage run 
     */
    function run($id = null)
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

        $success = $this->startTransfer();
        if (!$success) {
            $this->log_data('batch', $log_name, 'Não foi possível realizar a transferência.', "E");
            return;
        }
        echo "Transferência realizada com sucesso.";

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Inicia a transferência dos arquivos para a respectiva pasta no bucket.
     * @return bool Retorna true caso tenha dado sucesso, falso caso tenha ocorrido algum erro.
     */
    function startTransfer()
    {
        try {
            // Busca o diretório de imagens.
            $root = FCPATH . 'assets/images/product_image';
            if (!is_dir($root)) {
                echo "Pasta assets/images/product_image não existe." . PHP_EOL;
                return false;
            }
            // Cria a instância de transferência.
            $dest = 'assets/images/product_image';
            $manager = $this->bucket->createTransfer($root, $dest);
            // Inicia a transferência.
            $manager->transfer();
            echo "Transferência concluida." . PHP_EOL;
            return true;
        } catch (AwsException $e) {
            echo "Não foi possível concluir a transferência." . PHP_EOL;
            echo "AWS Error:" . $e->getAwsErrorMessage();
            return false;
        } catch (Exception $e) {
            echo "Não foi possível concluir a transferência." . PHP_EOL;
            echo $e->getMessage();
            return false;
        }
        return false;
    }
}
