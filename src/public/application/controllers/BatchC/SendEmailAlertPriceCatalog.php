<?php

class SendEmailAlertPriceCatalog extends BatchBackground_Controller {
    public function __construct()
    {
        parent::__construct();
        // log_message('debug', 'Class BATCH ini.');

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_products_catalog');

    }

    function run($id=null,$params=null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        /* faz o que o job precisa fazer */
        echo "Pegando produtos para notificar por email \n";
        $this->sendEmailProductsCatalog();

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    public function sendEmailProductsCatalog()
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        $productsAlert = $this->model_products_catalog->getProductsWithChangedPrice(false, true, true);

        echo "[ ALL ]".json_encode($productsAlert)."\n";
        foreach ($productsAlert as $log_prd) {
            echo "[ LOG ]".json_encode($log_prd)."\n";
            $updateAlert = false;
            foreach ($this->model_products_catalog->getProductsByProductCatalogId($log_prd['product_catalog_id']) as $prd) {
                $emailStore = $prd['responsible_email'];
                $priceOld   = number_format($log_prd['old_price'], 2, ',', '.');
                $priceNew   = number_format($log_prd['new_price'], 2, ',', '.');
                $date       = date('d/m/Y H:i', strtotime($log_prd['date_create']));

                $subject    = "Alteração de Preço Catálogo - {$prd['name']}";
                $body       = "
                <!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
                <html xmlns='http://www.w3.org/1999/xhtml'>
                    <head>
                        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>
                        <title>Alteração de Preço</title>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'/>
                    </head>
                    <body style='text-align: center;'>
                        <img src='https://somaplace.somalabs.com.br/app/assets/images/company_image/5ff4a64e1668f.png' width='160px'>
                        <h3>Preço de Produto do Catálogo Alterado</h3>
                        <h5><b>Produto: </b>{$prd['name']}</h5>
                        <h5><b>Preço anterior: </b>R$ {$priceOld}</h5>
                        <h5><b>Novo preço: </b>R$ {$priceNew}</h5>
                        <h5><b>Data Alteração: </b>{$date}</h5>
                    </body>
                </html>
                ";

                echo "[ PRD ]".json_encode([$emailStore, $subject, $body])."\n";

//                $statusSendEmail = 'enviado';
                $statusSendEmail = $this->sendEmailMarketing($emailStore, $subject, $body) ? 'enviado' : 'nao_enviado';
                if ($statusSendEmail == 'enviado') $updateAlert = true;
                $this->log_data('batch', $log_name, 'Enviou e-mail de preço de catálogo alterado. STATUS_ENVIO='.$statusSendEmail."\n\n". json_encode(array($emailStore, $subject, $body)) . "\n\nLOG=" . json_encode($log_prd) . "\n\nProduct=" . json_encode($prd), "I");
            }
            // atualiza o log para alert=1 para não notificar novamente
            if ($updateAlert) {
                $this->model_products_catalog->updateAlertLogProductsCatalog($log_prd['id'], 1);
                $this->log_data('batch', $log_name, "Atualizou log_products_catalog_price para alert=1. \n\nLOG=" . json_encode($log_prd), "I");
            }
        }

    }
}