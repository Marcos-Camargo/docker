<?php


require APPPATH . "controllers/BatchC/SellerCenter/Vtex/Category.php";

class ShophubCategory extends Category
{

	var $auth_data;
	var $int_to;

	public function __construct()
	{
		parent::__construct();

	}

	// php index.php BatchC/SellerCenter/Vertem/ShophubCategory run 
	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        $this->sync();

		echo PHP_EOL . PHP_EOL . 'Fim da rotina' . PHP_EOL . PHP_EOL;
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
    }
	
	function sync() {
		$this->int_to = 'SH';
        $integrationName = $this->model_integrations->getIntegrationsbyCompIntType(1,$this->int_to,"CONECTALA","DIRECT",0);

        if ($integrationName) {
            echo 'Sync: '. $integrationName['int_to']."\n";
            $this->syncIntTo($integrationName['id'], $integrationName['int_to'], $integrationName['auth_data']);
            echo PHP_EOL;
        }

        echo PHP_EOL . "FIM SYNC CATEGORIAS" . PHP_EOL;
    }

}
