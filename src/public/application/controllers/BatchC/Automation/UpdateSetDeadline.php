<?php

class UpdateSetDeadline extends BatchBackground_Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_products');
        $this->load->model('model_category');
        $this->load->model('model_settings');
    }
    // php index.php BatchC/Automation/UserAutomation run null 150
    function run($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob('Automation/'.$this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data(
                'batch',
                $log_name,
                'Já tem um job rodando ou que foi cancelado',
                "E"
            );
            return;
        }

       date_default_timezone_set('America/Sao_Paulo');
       echo $date = date("Y-m-d H:");
       echo "\n";
        $seller =  $this->model_settings->getSettingDatabyName('sellercenter');
        if ($seller['value'] == 'conectala') {

            $limit = 200;
            $offset = 0;
            while (true) {

                $list = $this->model_products->listProduct($offset, $limit); //listo todos os produtos
                if (count($list) == 0) {
                    break;
                }
                $offset += $limit;
                $i = 0;
                $soma = "";
                foreach ($list as $v) {
                    $id = $v['id'];
                    $idCategory_id = trim($v['category_id'], '[" "]'); //limpo ela removendo esses [" "] caracteres
                    $block = $this->model_category->getcategoryBlockForJob($idCategory_id,$date); //consulto se a categoria está com Prazo fixo e se foi atualizado na ultimas hora
                    
                    if ($block == 1) { // se produto estiver com a categoria com prazo fixado e se atualizado na ultima hora retorno o numero de dias days_cross_docking
                        echo ' Prazo operacional atual de ' . $v['prazo_operacional_extra'] . ' Fixado para';
                        echo ' ' .  $days_cross_docking = $this->model_category->getcategoryDays_cross_docking($idCategory_id);
                        if ($v['prazo_fixo'] <> 1) { //bloqueio de alteração de prazo
                                   echo ' ' . $updatePrazoOperacionalExtra = $this->model_products->updatePrazoOperacionalExtra($days_cross_docking, $id); //Update de Dias Fixados
                            $i++;
                        }
                    }
                    echo "\n";


                }
            }
        }
        echo "\n";
        echo ' Total Afetados ' . $soma .= $i;
        echo "\n";
        /* encerra o job */
        get_instance()->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }
}
