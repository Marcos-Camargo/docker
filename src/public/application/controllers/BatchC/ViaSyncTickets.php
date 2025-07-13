<?php
/*
 
Realiza a atualização de preço e estoque da VIA Varejo

*/   

require 'ViaVarejo/ViaOAuth2.php';
require 'ViaVarejo/ViaIntegration.php';
require 'ViaVarejo/ViaUtils.php';

 class ViaSyncTickets extends BatchBackground_Controller {
		
	private $oAuth2 = null;
	private $integration = null;
	private $count = 0;

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
		$this->load->model('model_integrations');

		$this->oAuth2 = new ViaOAuth2();
        $this->integration = new ViaIntegration();
    }

	function getInt_to() {
		return ViaUtils::getInt_to();
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
		
		$int_to = $this->getInt_to();
		$integration = $this->model_integrations->getIntegrationsbyCompIntType(1, 'VIA', "CONECTALA", "DIRECT", 0);
		$api_keys = json_decode($integration['auth_data'], true);
		
		$client_id = $api_keys['client_id'];
        $client_secret = $api_keys['client_secret']; 
        $grant_code = $api_keys['grant_code']; 
		
		$authorization = $this->oAuth2->authorize($client_id, $client_secret, $grant_code);
		
		/* faz o que o job precisa fazer */
		$retorno = $this->syncTickets($authorization, 0, 100);

		echo PHP_EOL . PHP_EOL . 'Fim da rotina' . PHP_EOL . PHP_EOL;
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
		 
    function syncTickets($authorization, $offset, $limit)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$int_to = $this->getInt_to();

		$response = $this->integration->getTickets($authorization, $offset, $limit);

		$tickets = json_decode($response['content'], true)['tickets'];


		foreach ($tickets as $ticket) {
			echo PHP_EOL . ++$this->count . ' - code: ' . $ticket['code'] . '... ';
			$ticket_db = $this->getTicket($ticket['code']);			
			echo is_null($ticket_db) ? 'Ticket novo... ' : 'Ticket já cadastrado... ';
			$this->replace($ticket);
		}

		if (count($tickets) == $limit) {
			$this->syncTickets($authorization, $offset + $limit, $limit);
		}

		return ;
	} 
	
	private function getTicket($code) {
		$sql = "SELECT * FROM ticket_via WHERE code = '".$code."'";
		$cmd = $this->db->query($sql);
		return $cmd->row_array();
	}

	private function replace($ticket)
	{
		$order = array(
			'id' => null,
			'href' => null
		);

		$skus = null;

		if (!key_exists('metadata', $ticket)) {
			return;
		}
		foreach ($ticket['metadata'] as $metadata) {
			if ($metadata['key'] == 'orders')
			{
				$order['id'] = $metadata['value']['id'];
				$order['href'] = $metadata['value']['href'];
			}
			else if ($metadata['key'] == 'skus') 
			{
				foreach($metadata['value'] as $sku)
				{
					$skus .= (!is_null($skus) ? ',' : '') . $sku['skuSellerId'];
				}
			}
		}

		$ticket_db = array(
			'code' => $ticket['code'],
			'status' => $ticket['status'],
			'description' => $ticket['description'],
			'createdAt' => $ticket['createdAt'],
			'updatedAt' => $ticket['updatedAt'],
			'closedAt' => $ticket['closedAt'],
			'priority' => $ticket['priority'],
			'assignee_user' => $ticket['assignee']['user'],
			'assignee_group' => $ticket['assignee']['group'],
			'ombudsman_active' => $ticket['ombudsman']['active'],
			'ombudsman_source' => $ticket['ombudsman']['source'],
			'ombudsman_createdAt' => $ticket['ombudsman']['createdAt'],
			'customer_name' => $ticket['customer']['name'],
			'customer_phoneNumber' => $ticket['customer']['phoneNumber'],
			'site' => $ticket['site'],
			'channel' => $ticket['channel'],
			'type_id' => $ticket['type']['id'],
			'type_name' => $ticket['type']['name'],
			'subject_id' => $ticket['subject']['id'],
			'subject_name' => $ticket['subject']['name'],
			'sla_expireAt' => $ticket['sla']['expireAt'],
			'sla_delayed' => $ticket['sla']['delayed'],
			'order_id' => $order['id'],
			'order_href' => $order['href'],
			'skus' => $skus
		);

		$this->db->replace('ticket_via', $ticket_db);
	}
}
?>
