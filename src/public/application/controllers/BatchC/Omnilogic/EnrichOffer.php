<?php
/*
 
Realiza o enriquecimento de produtos recebido da Omnilogic

*/   

abstract class EnrichOffer extends BatchBackground_Controller {
		
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
        $this->load->model('model_omnilogic');
        $this->load->model('model_omnilogic_channel_mkt');

    }

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
		
        $this->enrich();
        
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
        
    protected function getChannels() {
        $channels = array();

        $records = $this->model_omnilogic_channel_mkt->getList();

        foreach ($records as $channel) {
            array_push($channels, $channel['channel']);
        }

        return $channels;
    }

    abstract protected function enrichCategory($offer, $enrichment);

    abstract protected function enrichAttributes($offer, $enrichment);

    private function enrich()
    {
        foreach ($this->getChannels() as $channel) {
            $offers = $this->model_omnilogic->getList($channel);
            
            $count = 0;
            foreach ($offers as $offer) {
                echo ++$count . "/" . count($offers) . " - offer: ". $offer['seller_offer_id'] . PHP_EOL;
                $this->enrichOffer($offer);
            }
        }
	} 
    
    private function enrichOffer($offer) {
        try {
            $enrichment = json_decode($offer['body'], true);
            $categories = array();
            if (array_key_exists('subcategory_ids', $enrichment)) {
                $categories = $enrichment['subcategory_ids'];
            } 
            if (count($categories) == 1) {
                if ($this->enrichCategory($offer, $enrichment)) {
                    $this->enrichAttributes($offer, $enrichment);
                }
                else {
                    $data = array(
                        'prd_id' => $enrichment['seller_offer_id'],
                        'int_to' => $enrichment['channel'],
                        'amout' => count($categories),
                        'entity' => $enrichment['entity'],
                        'category' => json_encode($categories)
                    );
                    $this->model_omnilogic->sendOfferCategoryProblem($data);
                }
            }
            else {
                $data = array(
                    'prd_id' => $enrichment['seller_offer_id'],
                    'int_to' => $enrichment['channel'],
                    'amout' => count($categories),
                    'entity' => $enrichment['entity'],
                    'category' => json_encode($categories)
                );
                $this->model_omnilogic->sendOfferCategoryProblem($data);
                // $this->model_omnilogic->unenriched($offer['seller_offer_id']);
            }
        }
        finally {
            $this->model_omnilogic->setChecked($offer['id']);
        }
    }

    protected function getCategoryEnrich($enrichment) {
        if (count($enrichment['subcategory_ids']) > 0) {
            return $enrichment['subcategory_ids'][0];
        }
        return null;
    }
}
?>
