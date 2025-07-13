<?php

class UpdateSetDeadlineNovo extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_products');
        $this->load->model('model_category');
        $this->load->model('model_settings');
    }

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

        $limit = 200;
        $offset = 0;
        while (true) {
            //$list = $this->model_products->listProduct($offset, $limit); //listo todos os produtos
            $list = $this->model_category->listCategory($offset, $limit); //listo todos as categorias
            if (count($list) == 0) {
                break;
            }
            $offset += $limit;

            foreach ($list as $v) {
                $id = $v['id'];

                $days_cross_docking = $v['days_cross_docking'];
                $list2 = $this->model_products->listProductJob($id,$days_cross_docking ); //listo todos os produtos
                foreach ($list2 as $v2) {
                    if (($v2['prazo_fixo'] <> 1)AND ($v2['prazo_operacional_extra'] <> $days_cross_docking) ) { //bloqueio de alteração de prazo
                        echo "Categoria=$id. Produto={$v2['id']}. Cross Docking=$days_cross_docking. Atualizado!\n";
                        $this->model_products->updatePrazoOperacionalExtra($days_cross_docking, $v2['id']); //Update de Dias Fixados
                    }
                }

                //Remove digito 1 do campo force_update em categories
                $this->model_category->update(array('force_update' => '0'), $id);
            }
        }
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }
} 