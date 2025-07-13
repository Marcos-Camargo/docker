<?php /** @noinspection ALL */
/** @noinspection PhpExpressionResultUnusedInspection */
/*
SW Serviços de Informática 2019
Controller de Recebimentos
*/  
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property Model_billet $model_billet
 */
class Billet extends Admin_Controller
{
    public $conciliation_connector;
    public $conciliation_folder = '/assets/docs/conciliacao/';
    public $connector;
    public $subgrids_translated = array();
    public $conciliation_type;
    public $negociacao_marketplace_campanha;
    public $canceled_orders_data_conciliation;
    public $setting_api_comission;
    public $orders_precancelled_to_zero;
    public $payment_gateway_id;

    public $subgrids;

	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		set_time_limit(0);

		$this->data['page_title'] = 'Parâmetros de Boletos';

		$this->load->model('model_billet');
		$this->load->model('model_parametrosmktplace');
		$this->load->model('model_iugu');
        $this->load->model('model_settings');
        $this->load->model('model_gateway_settings');
        $this->load->model('model_orders');
        $this->load->model('model_users');
		$this->load->model('model_campaigns_v2');

        $this->negociacao_marketplace_campanha = $this->model_settings->getValueIfAtiveByName('negociacao_marketplace_campanha');
        $this->canceled_orders_data_conciliation = $this->model_settings->getStatusbyName('canceled_orders_data_conciliation');
        $this->setting_api_comission = $this->model_settings->getSettingDatabyName('api_comission');
        $this->setting_api_comission = $this->setting_api_comission['status'];
    		$this->fin_192_novos_calculos = $this->model_settings->getStatusbyName('fin_192_novos_calculos');


        $this->orders_precancelled_to_zero = ($this->model_settings->getStatusbyName('orders_precancelled_to_zero') == 1) ? true : false;
        $this->payment_gateway_id          = $this->model_settings->getValueIfAtiveByName('payment_gateway_id');

		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$more = " company_id = ".$usercomp;

        $this->load->helper("file");
        
        $this->subgrids = array
        (
            'ok' => "Ok",
            'div' => "Divergente",
            'nfound' => "Não encontrado",
            'outros' => "Outros",
            'estorno' => "Estorno",
        );

        $this->subgrids_translated = array(
            $this->lang->line('application_conciliacao_grids_ok'),
            $this->lang->line('application_conciliacao_grids_divergente'),
            $this->lang->line('application_conciliacao_grids_naoencontrado'),
            $this->lang->line('application_conciliacao_grids_outros'),
            $this->lang->line('application_conciliacao_grids_estorno')
        );
	}


	/* 
	* It only redirects to the manage order page
	*/
	public function index()
	{
		if(!in_array('viewBillet', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$this->data['page_title'] = 'Administrar os boletos';
		$this->render_template('billet/list', $this->data);		
	}
	
	/*
	* Fetches the orders data from the orders table 
	* this function is called from the datatable ajax function
	*/
	public function fetchBilletListsGerData()
	{
	    
		$result = array('data' => array());

		$data = $this->model_billet->getBilletsData();
		
		setlocale(LC_MONETARY,"pt_BR", "ptb");
		
		foreach ($data as $key => $value) {

			// button
			$buttons = '';

			if(in_array('viewBillet', $this->permission)) {
				$buttons .= ' <a href="'.base_url('billet/view/'.$value['id']).'" class="btn btn-default"><i class="fa fa-eye"></i></a>';
			}

			if(in_array('createBillet', $this->permission)) {
			    if($value['id_boleto_iugu'] == ""){
    				$buttons .= ' <button type="button" class="btn btn-default" onclick="gerarboleto('.$value['id'].')"><i class="fa fa-plus"></i></button>';
			    }else{
			        $buttons .= ' <button type="button" class="btn btn-default" onclick="#" disabled ><i class="fa fa-plus"></i></button>';
			    }
			}
			
			$result['data'][$key] = array(
				$value['id'],
				$value['marketplace'],
				$value['id_boleto_iugu'],
			    $value['data_geracao'],
			    "R$ ".str_replace ( ".", ",", $value['valor_total'] ),
			    $value['status_billet'],
			    $value['status_iugu'],
				$buttons
			);
		} // /foreach

		echo json_encode($result);
	}
	
	public function view($id = null){

	    $group_data1 = $this->model_billet->getBilletsData($id);
	    $group_data2 = $this->model_billet->getBilletsDataId($id);
	    $this->data['billets'] = $group_data1[0];
	    $this->data['billetsData'] = $group_data2;
	    $this->render_template('billet/view', $this->data);	
	    
	}
	
	public function createbillet(){
	    
	    $group_data1 = $this->model_billet->getMktPlacesData();
	    
	    $this->data['mktplaces'] = $group_data1;
	    
	    $this->render_template('billet/create', $this->data);	
	}
	
	public function fetchOrdersListData($idMktPlace = null, $dataInicio = null, $dataFim = null, $retirados = null){
	    
	    $data['mktPlace'] = $idMktPlace;
	    $data['dtInicio'] = $dataInicio;
	    $data['dtFim'] = $dataFim;
	    $data['retirados'] = str_replace("-",",",$retirados);
	    
	    if($data['mktPlace'] <> ""){
    	    $data = $this->model_billet->getOrdersData($data);
    	    if($data){
    	    foreach ($data as $key => $value) {
    	        // button
    	        $buttons = '';
    	        
    	        $buttons .= ' <button type="button" class="btn btn-default" onclick="addbillet('.$value['id'].',\'Add\')"><i class="fa fa-plus"></i></button>';
    	        $buttons .= ' <button type="button" class="btn btn-default" onclick="addbillet('.$value['id'].',\'Remove\')"><i class="fa fa-minus"></i></button>';
    	        
    	        $result['data'][$key] = array(
    	            $value['id'],
    	            $value['mktplace'],
    	            $value['num_pedido'],
    	            $value['data_pedido'],
    	            $value['status'],
    	            "R$ ".str_replace ( ".", ",", $value['valor'] ),
    	            $buttons
    	        );
    	    } // /foreach
    	    }else{
    	        $result['data'][0] = array("","","","","","","");
    	    }
	    }else{
	        $result['data'][0] = array("","","","","","","");
	    }
	    echo json_encode($result);
	}
	
	public function fetchOrdersListAddedData($idOrders = null, $idFuncoes = null){
	    
	    $data['idOrders'] = str_replace("-",",",$idOrders);
	    
	    if($data['idOrders'] <> ""){
	        
	        // Array com funções e Ids
	        $arrayId = explode("-",$idOrders);
	        $arrayFunction = explode("-",$idFuncoes);
	         
	        $arrayFinal = array();
	         
	        $i = 0;
	        foreach($arrayId as $id){
	            $arrayFinal[$id] = $arrayFunction[$i];
	            $i++;
	        }
	        
	        $data = $this->model_billet->getOrdersAddedData($data);
	        if($data){
	            foreach ($data as $key => $value) {
	                
	                $função = "";
	                if( $arrayFinal[$value['id']] == "Add"){
	                    $função = "Somar ao Boleto";
	                }else{
	                    $função = "Não somar ao Boleto o pedido";
	                }
	                
	                $result['data'][$key] = array(
	                    $value['id'],
	                    $value['mktplace'],
	                    $value['num_pedido'],
	                    "R$ ".str_replace ( ".", ",", $value['valor'] ),
	                    $função
	                );
	            } // /foreach
	        }else{
	            $result['data'][0] = array("","","","","","","");
	        }
	    }else{
	        $result['data'][0] = array("","","","","","","");
	    }
	    echo json_encode($result);
	}
	
	public function totalBillet(){
	    
	    $idOrders = $this->postClean('id_orders');
	    $idFuncoes = $this->postClean('id_function');
	    
	    // Array com funções e Ids
	    $arrayId = explode("-",$idOrders);
	    $arrayFunction = explode("-",$idFuncoes);
	    
	    $idSomatorio = "";
	    $i = 0;
	    foreach($arrayId as $id){
	        if( $arrayFunction[$i] == "Add"){
	            if($idSomatorio == ""){
	                $idSomatorio = $id;
	            }else{
	                $idSomatorio .= ",".$id;
	            }
	        }
	        $i++;
	    }

	    if($idSomatorio <> ""){
	       $total = $this->model_billet->getSumOrdersAdded($idSomatorio);
	    }else{
	       $total[0]['valor'] = "0"; 
	    }
	    
	    echo "R$ ".$total[0]['valor'];
	}
	
	public function create(){
	    try{
    	    $inputs = $this->postClean(NULL,TRUE);
    	    
    	    $idOrders  = $inputs['hdn_id_orders'];
    	    $idFuncoes = $inputs['hdn_id_function'];
    	    
    	    // Array com funções e Ids
    	    $arrayId = explode("-",$idOrders);
    	    $arrayFunction = explode("-",$idFuncoes);
    	    
    	    //Insere o valor em billet e retorna o ID
    	    $idBillet = $this->model_billet->insertBillet($inputs);
    	    if($idBillet == false){
        	    echo "1;Erro ao cadastrar o boleto";
                die;    	        
    	    }
    	    
    	    //Prepara o array de pedidos a serem associados ao boleto
    	    $arraySaida = array();
    	    $i = 0;
    	    foreach($arrayId as $id){
    	        if( $arrayFunction[$i] == "Add"){
    	            $arraySaida[$i]['billet_id'] = $idBillet;
    	            $arraySaida[$i]['order_id'] = $id;
    	            $arraySaida[$i]['ativo'] = 1;
    	        }else{
    	            $arraySaida[$i]['billet_id'] = $idBillet;
    	            $arraySaida[$i]['order_id'] = $id;
    	            $arraySaida[$i]['ativo'] = 0;
    	        }
    	        $i++;
    	    }
    	    
    	    //Insere os pedidos associados ao boleto
    	    foreach($arraySaida as $ArrayBilletOrder){
    	        $salvar = $this->model_billet->insertBilletOrder($ArrayBilletOrder);
    	        if($idBillet == false){
    	            echo "1;Erro ao cadastrar o boleto";
    	            die;
    	        }
    	    }
    	    
    	    echo "0;Boleto cadastrado com sucesso!";
    	    
	    }catch(Exception $e){
	        echo "1;Erro ao cadastrar o boleto";
	    }
	    
	    
	}
	
	public function gerarboletoiugu(){
	    
	    $id =  $inputs = $this->postClean('id');

	    //Gera o boleto no webservice IUGU
	    $retorno = $this->model_billet->gerarBoletoIUGU($id);
	    
	    if($retorno['ret']){
	        
	        //Atualiza os status de geração
	        $retorno2 = $this->model_billet->atualizaStatus($id,3,6, $retorno['num_billet'],$retorno['url']);
	        
	        if($retorno2){
	            echo "0;Boleto IUGU gerado com sucesso";
	        }else{
	            echo "1;Erro ao gerar Boleto IUGU";
	        }
	        
	    }else{
	        echo "1;Erro ao gerar Boleto IUGU";
	    }
	    
	}
	
	/**********************************************************************/
	
	public function list()
	{
	    if(!in_array('viewBilletConcil', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }

		$valorNM = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
		if($valorNM['status'] == "1"){
			$this->data['page_title'] = $this->lang->line('application_conciliacao_consulta_novomundo');
		}else{
			$this->data['page_title'] = $this->lang->line('application_conciliacao_consulta');
		}
	    
	    
	    
	    if(in_array('createBilletConcil', $this->permission)) {
	        $this->data['categs'] = "";
	        $this->data['mktPlaces'] = "";
	    }
	    
	    $this->render_template('billet/list', $this->data);
	}

	public function payconciliation($lote = null){

		$flagrepasse = $this->model_settings->getSettingDatabyName('flag_liberacao_repasse_conciliacao');
		if(!$flagrepasse){
				$this->session->set_flashdata('error', 'Você não possui permissão para essa ação!');
				redirect('billet/list', 'refresh');
		}

		//Busca dados da conciliacao
		$dadosConciliacao = $this->model_billet->getConciliacaoGridData($lote);
		if(!$dadosConciliacao) {
				$this->session->set_flashdata('error', 'Conciliação não encontrada!');
				redirect('billet/list', 'refresh');
		}

		$dadosConciliacao = $dadosConciliacao[0];

		//Verifica se já foi pago
		$checkIugu = $this->model_billet->iugurepassecheck($dadosConciliacao['id_con']);

		if($checkIugu == 0){

                $this->model_billet->updateStatusRepasseConciliation($lote);

				//busca conciliação para pagar
				$pedidos = $this->model_billet->getConciliacaoConecta($lote);

				if($pedidos){

						foreach($pedidos as $pedido){
								
								$dataPhp = date("Y-m-d h:m:s", time());
			 
								$data = [];
								//insere na IUGU repasse
								$data['order_id'] = $pedido['order_id'];
								$data['numero_marketplace'] = $pedido['numero_marketplace'];
								$data['data_split'] = $pedido['data_split'];
								$data['data_transferencia'] = $dataPhp;
								$data['data_repasse_conta_corrente'] = $dataPhp;
								$data['conciliacao_id'] = $dadosConciliacao['id_con'];
								$data['valor_parceiro'] = $pedido['valor_parceiro'];
								$data['valor_afiliado'] = '0.00';
								$data['valor_produto_conectala'] = '0.00';
								$data['valor_frete_conectala'] = '0.00';
								$data['valor_repasse_conectala'] = '0.00';

								$this->model_billet->insertIuguRepasse($data);

						}

						$this->log_data(
								'general',
								__FUNCTION__,
								"Setou os pedidos da conciliação de lote número ".$lote." como pagos!",
								"I"
						);
						$this->session->set_flashdata('success', 'Conciliação realizada com sucesso!');
						redirect('billet/list', 'refresh');
				}

				$this->session->set_flashdata('error', 'Pedidos não encontrados!');
				redirect('billet/list', 'refresh');

		}else{
				$this->session->set_flashdata('error', 'Conciliacao já realizada!');
				redirect('billet/list', 'refresh');
		}

}

	public function fetchConciliacaoGridData($tipo = "conectala")
	{
		$gateways_with_payment_report = [];
		$setting_gateways_with_payment_report = $this->model_settings->getSettingDatabyName('payment_gateways_with_payment_report');

		if (isset($setting_gateways_with_payment_report['value']))
		{
			$gateways_with_payment_report = explode(';', $setting_gateways_with_payment_report['value']);
		}

	    $result = array('data' => array());
	    
	    $data = $this->model_billet->getConciliacaoGridData();

	    foreach ($data as $key => $value) {

			$title_adjustment = '';
	        // button
	        $buttons = '';

            if(in_array('viewBilletConcil', $this->permission)) {
                if($tipo == "conectala"){
                    $buttons .= ' <a href="'.base_url('billet/edit/'.$value['lote']).'" class="btn btn-default" title="'.$this->lang->line('application_view').'"><i class="fa fa-eye"></i></a>';
                    // $buttons .= ' <button type="button" class="btn btn-default" onclick="exportaArquivoConciliacao(\''.$value['lote'].'\')"><i class="fa fa-file-excel-o"></i></button>';
                    $buttons .= ' <button type="button" class="btn btn-default" onclick="exportaArquivoConciliacao(\''.$value['lote'].'\', \''.$value['integ_id'].'\')"><i class="fa fa-file-excel-o"></i></button>';
                    // $buttons .= ' <a href="'.base_url('billet/paymentreconciliation/'.$value['lote']).'" class="btn btn-default"><i class="fa fa-money"></i></a>'

                    $flagrepasse = $this->model_settings->getSettingDatabyName('flag_liberacao_repasse_conciliacao');
                    if($flagrepasse){
                        $checkIugu = $this->model_billet->iugurepassecheck($value['id_con']);
                        if($flagrepasse['status'] == "1" && $checkIugu == 0){
                            $lote = $value["lote"];
                            $buttons .= ' <button onclick="confirmarConciliacao(\''.$lote.'\')" class="btn btn-default"><i class="fa fa-money"></i></button>';
                        }
                    }
                }
            }

            if(in_array('createPaymentRelease', $this->permission)) {
                if($tipo != "conectala"){

                    $disabled = "";
                    $buttons .= ' <a href="'.base_url('billet/editsellercenter/'.$value['lote']).'" class="btn btn-default" title="'.$this->lang->line('application_view').'"><i class="fa fa-eye"></i></a>';

                    $flagrepasse = $this->model_settings->getSettingDatabyName('flag_liberacao_repasse_conciliacao');
                    if($flagrepasse){

                        $checkIugu = $this->model_billet->iugurepassecheck($value['id_con']);

                        if($flagrepasse['status'] == "1")
                        {
                            $lote = $value["lote"];
                            $buttons .= '<button ';

                            if (@$value['pagamento_conciliacao'] != 'Conciliação Paga')
                            {
                                $buttons .= ' onclick="confirmarLiberacaoPagamento(\''.$lote.'\')" ';
                            }
                            else
                            {
                                $buttons .= '  onclick="alert(\''.$this->lang->line('application_notification_payment_processed_subject').'\');" ';
                                $disabled = ' disabled';
                            }

                            $buttons .= ' class="btn btn-default'.$disabled.'"><i class="fa fa-money"></i></button>';
                        }
                    }
                }
            }

			if (in_array($this->payment_gateway_id, $gateways_with_payment_report))
			{
				$buttons .= ' <a href="'.base_url('payment/paymentReports/'.$value['ano_mes'].'/'.$value['lote']).'" class="btn btn-default" ';

				$allow_transfer_between_accounts = $this->model_gateway_settings->getGatewaySettingByName($this->payment_gateway_id, 'allow_transfer_between_accounts');

				if ($value['status_repasse'] == '27' && $allow_transfer_between_accounts == 1)
				{
					$buttons .= ' style="color: red;" ';
					$title_adjustment = ' - '.$this->lang->line('payment_report_list_btn_adjustment');
				}

				$buttons .= ' title="'.$this->lang->line('application_payment_report').$title_adjustment.'"><i class="fa fa-list-ul"></i></a>';
			}

            $ciclo = 'Do dia '.$value['data_inicio'].' - até '.$value['data_fim'];

	        if($tipo == "sellercenter" ){

                if($value['conciliacao_id'] == 0) {

                    $value['pagamento_conciliacao'] = $this->lang->line('application_payment_release_unpaid');
                }else{

                    $value['pagamento_conciliacao'] = $this->lang->line('application_payment_release_paid');
                }

                if($value['status'] == "Conciliação com sucesso") {

                    $value['status'] = $this->lang->line('application_payment_release_success');
                }else{

                    $value['status'] = $this->lang->line('application_payment_release_pending');
                }

            }

	        $result['data'][$key] = array(
	            $value['id_con'],
	            $value['data_criacao'],
	            $value['ano_mes'],
	            $value['apelido'],
	            $ciclo,
	            $value['status'],
				$value['pagamento_conciliacao'],
	            $buttons
	        );
	    } // /foreach
	    
	    echo json_encode($result);
	}

	public function payconciliationsellercenter($lote = null){

		$flagrepasse = $this->model_settings->getSettingDatabyName('flag_liberacao_repasse_conciliacao');
		if(!$flagrepasse){
				$this->session->set_flashdata('error', 'Você não possui permissão para essa ação!');
				redirect('billet/listsellercenter', 'refresh');
		}

		//Busca dados da conciliacao
		$dadosConciliacao = $this->model_billet->getConciliacaoGridData($lote);
		$dadosConciliacao = $dadosConciliacao[0];

		//Verifica se já foi pago
		$checkIugu = $this->model_billet->iugurepassecheck($dadosConciliacao['id_con']);

		if($checkIugu == 0){

            $this->model_billet->updateStatusRepasseConciliation($lote);

			//busca conciliação para pagar
			$pedidos = $this->model_billet->getConciliacaoSellerCenter($lote);

            //dá baixa em painel juridico
            $this->load->model('model_repasse');

            $legal_panel_transfers = $this->model_repasse->getLegalPanelTransfersByLot($lote);

            if ($legal_panel_transfers)
            {
                foreach ($legal_panel_transfers as $transfer)
                {
                    $this->model_repasse->updateTransferLegalCloseLegalPanel($transfer['legal_panel_id']);
                }
            }

			if($pedidos){

				foreach($pedidos as $pedido){
					
					$dataPhp = date("Y-m-d h:m:s", time());
					
					$repasse = "";
					if($pedido['valor_repasse_ajustado'] == "0.00"){
						$repasse = $pedido['valor_repasse'];
					}else{
						$repasse = $pedido['valor_repasse_ajustado'];
					}
					//insere na IUGU repasse
					$data['order_id'] = $pedido['order_id'];
					$data['numero_marketplace'] = $pedido['numero_marketplace'];
					$data['data_split'] = $dataPhp;
					$data['data_transferencia'] = $dataPhp;
					$data['data_repasse_conta_corrente'] = $dataPhp;
					$data['conciliacao_id'] = $dadosConciliacao['id_con'];
					$data['valor_parceiro'] = $repasse;
					$data['valor_afiliado'] = '0.00';
					$data['valor_produto_conectala'] = '0.00';
					$data['valor_frete_conectala'] = '0.00';
					$data['valor_repasse_conectala'] = '0.00';
                    $data['current_installment'] = $pedido['current_installment'] ?? 1;
                    $data['total_installments'] = $pedido['total_installments'] ?? 1;
                    $data['total_paid'] = $pedido['total_paid'] ?? 0;

					$this->model_billet->insertIuguRepasse($data);

					$this->log_data(
						'general',
						__FUNCTION__,
						"Setou os pedidos da conciliação de lote número ".$lote." como pagos!",
						"I"
					);
					
					$this->log_data(
						'general',
						__FUNCTION__,
						"Setou os pedidos da conciliação de lote número ".$lote." como pagos!",
						"I"
					);
					
				}
				$this->session->set_flashdata('success', 'Conciliação atualizada com sucesso!');
				redirect('billet/listsellercenter', 'refresh');
			}else{
				$this->session->set_flashdata('error', 'Não foram encontrados pedidos para essa conciliação!');
				redirect('billet/listsellercenter', 'refresh');
			}
			
		}else{
			$this->session->set_flashdata('error', 'Essa conciliação já foi paga!');
			redirect('billet/listsellercenter', 'refresh');
		}
		

	}

	public function paymentreconciliation($lote = null){

		if($lote <> null){
			$carregaTemp = $this->model_billet->carregaTempRepasse($lote);
			if($carregaTemp){
				$group_data1 = $this->model_billet->getMktPlacesData();
				$group_data2 = $this->model_parametrosmktplace->getReceivablesDataCiclo();
				$group_data3 = $data = $this->model_billet->getConciliacaoGridData($lote);
				$group_data3[0]['carregado'] = 1;
				
				$this->data['mktplaces'] = $group_data1;
				$this->data['ciclo'] = $group_data2;
				$this->data['hdnLote'] = $lote;
				$this->data['dadosBanco'] = $group_data3[0];
				
				$this->render_template('billet/paymentreconciliation', $this->data);
			}else{
				redirect('dashboard', 'refresh');
			}
		}else{
			redirect('dashboard', 'refresh');
		}

	}

	public function fetchConciliacaoAPagarGridData($lote = null){
	    
		if($lote == null){
			$result['data'][0] = array();
			echo json_encode($result);
			die;
		}

	    $result = array('data' => array());
	    
	    $data = $this->model_billet->getTempRepasse($lote);
	    
	    foreach ($data as $key => $value) {
	        
	        // button
	        $buttons = '';
	        
	        if($value['status_repasse'] == "21") {
	            $buttons .= ' <button type="button" class="btn btn-default" onclick="ajustarepassetemp(\''.$value['id'].'\')"><i class="fa fa-minus"></i></button>';
	        }

			if($value['status_repasse'] == "24") {
	            $buttons .= ' <button type="button" class="btn btn-default" onclick="ajustarepassetemp(\''.$value['id'].'\')"><i class="fa fa-plus"></i></button>';
	        }
	        
	        $result['data'][$key] = array(
	            $value['id'],
	            $value['name'],
				"R$ ".str_replace ( ".", ",", $value['valor_conectala'] ),
	            "R$ ".str_replace ( ".", ",", $value['valor_seller'] ),
				$value['responsavel'],
				$value['status_billet'],
	            $buttons
	        );
	    } // /foreach
	    
	    echo json_encode($result);
	}

	public function mudastatusrepassetemp(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    if( $inputs['id'] <> "" and $inputs['lote'] <> ""){
	        
	        $data = $this->model_billet->alterastatusrepassetemp($inputs);
	        if($data){
	            echo "0;Repasse tratado com sucesso";
	        }else{
	            echo "1;Erro ao tratar repasse $data";
	        }
	    }else{
	        echo "1;Erro ao atualizar repasse<br>Os campos não estavam todos preenchidos";
	    }

	}

	public function cadastrarrepasse(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    if( $inputs['hdnLote'] <> "" ){
	        
			//Limpa informação anterior na tabela principal
			$data = $this->model_billet->limpaRepasse($inputs['hdnLote']);

	        if($data){

				//Cadastra a informação na tabela final
				$data2 = $this->model_billet->cadastraRepasseFinal($inputs['hdnLote']);
				if($data2){
					echo "0;Repasse cadastrado com sucesso";
				}else{
					echo "1;Erro ao cadastrar conciliação".$data2;
				}
	            
	        }else{
	            echo "1;Erro ao cadastrar conciliação".$data;
	        }
	    }else{
	        echo "1;Erro ao cadastrar repasse<br>Os campos não estavam todos preenchidos";
	    }
	    
	}

	public function exportarepasse($lote = null){

		header("Pragma: public");
	    header("Cache-Control: no-store, no-cache, must-revalidate");
	    header("Cache-Control: pre-check=0, post-check=0, max-age=0");
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    header("Content-Transfer-Encoding: none");
	    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
	    header("Content-type: application/x-msexcel; charset=utf-8");
	    header("Content-Disposition: attachment; filename=Relatório Repasse.xls");
	    
		if($lote == null){
			$result['data'][0] = array("","","","","","");
		}else{

			$result = array('data' => array());
			
			$data = $this->model_billet->getTempRepasse($lote);
			
			if($data){
				foreach ($data as $key => $value) {
					
					$result['data'][$key] = array(
						$value['id'],
						$value['name'],
						"R$ ".str_replace ( ".", ",", $value['valor_conectala'] ),
						"R$ ".str_replace ( ".", ",", $value['valor_seller'] ),
						$value['responsavel'],
						$value['status_billet']
					);
				} 
			}else{
				$result['data'][0] = array("","","","","","");
			}
		}
		
	    echo utf8_decode("<table border=\"1\">
                    <tr>
                    <th>".$this->lang->line('application_id')."</th>
					<th>".$this->lang->line('application_store')."</th>
                    <th>Valor a receber - ConectaLá</th>
					<th>Valor a receber - Selle</th>
					<th>Responsável Conciliação</th>
					<th>".$this->lang->line('application_rec_5')."</th>
                  </tr>");
	    
	    foreach($result['data'] as $dadosTela){

	        echo utf8_decode("<tr>");
	        echo utf8_decode("<td>".$dadosTela[0]."</td>");
			echo utf8_decode("<td>".$dadosTela[1]."</td>");
			echo utf8_decode("<td>".$dadosTela[2]."</td>");
			echo utf8_decode("<td>".$dadosTela[3]."</td>");
			echo utf8_decode("<td>".$dadosTela[4]."</td>");
			echo utf8_decode("<td>".$dadosTela[5]."</td>");
	        echo utf8_decode("</tr>");
	        
	    }
	    
	    echo utf8_decode("</table>");
		

	}
	
	public function excelnfconciliacaorepasse($lote){
	    
	    header("Pragma: public");
	    header("Cache-Control: no-store, no-cache, must-revalidate");
	    header("Cache-Control: pre-check=0, post-check=0, max-age=0");
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    header("Content-Transfer-Encoding: none");
	    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
	    header("Content-type: application/x-msexcel; charset=utf-8");
	    header("Content-Disposition: attachment; filename=Relatório Conciliação - ".$lote.".xls");
	    
	    $group_data3 = $this->model_billet->getConciliacaoGridData($lote);
	    $dados = $group_data3[0];
	    
	    $input['lote'] = $lote;
	    $input['tipo'] = "Ok";
	    
	    if($dados['integ_id'] == "10"){
	        $data = $this->model_billet->getOrdersGridsB2W($input);
	    }elseif($dados['integ_id'] == "11"){
	        $data = $this->model_billet->getOrdersGridsML($input);
	    }elseif($dados['integ_id'] == "15"){
	        $data = $this->model_billet->getOrdersGridsViaVarejo($input);
	    }elseif($dados['integ_id'] == "16"){
	        $data = $this->model_billet->getOrdersGridsCarrefour($input);
	        if(empty($data)){
	            $data = $this->model_billet->getOrdersGridsCarrefourXls($input);
	        }
	    }elseif($dados['integ_id'] == "17"){
	        $data = $this->model_billet->getOrdersGridsMadeira($input);
	    }
        elseif ($dados['integ_id'] == "30")
        {
	        $data = $this->model_billet->getOrdersGridsNM($input);
	    }

	    //monta o array de saída
	    $arraySaida = array();
	    
	    
	    foreach($data as $dadosLinhaConciliacao){
	        
	        if($dadosLinhaConciliacao['store_id'] == "" || $dadosLinhaConciliacao['seller_name'] == ""){
	           $retornoLoja = $this->model_billet->buscalojapelopedido($dadosLinhaConciliacao, $dados['integ_id']);
	            if($retornoLoja){
	                $dadosLinhaConciliacao['seller_name'] = $retornoLoja['name'];
	                $dadosLinhaConciliacao['store_id'] = $retornoLoja['id'];
	            }
	        }
	        
	        if($dadosLinhaConciliacao['store_id'] <> "" and $dadosLinhaConciliacao['tratado'] == "1"){
	            
	            if(array_key_exists($dadosLinhaConciliacao['store_id'],$arraySaida )){
	                
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_pedido']                = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_pedido'] + $dadosLinhaConciliacao['valor_pedido']; // valor pedido mktplace
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto']               = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto'] + $dadosLinhaConciliacao['valor_produto'];  // valor pedido mktplace - produto
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete']                 = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete'] + $dadosLinhaConciliacao['valor_frete']; // valor pedido mktplace - frete CONTRATADO
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_real']            = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_real'] + $dadosLinhaConciliacao['valor_frete']; // valor pedido mktplace - frete REAL 
	                
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_receita_calculado']     = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_receita_calculado'] + $dadosLinhaConciliacao['valor_receita_calculado'];    // valor receita mktplace -Total
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto_calculado']     = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto_calculado'] + $dadosLinhaConciliacao['valor_produto_calculado'];    // valor receita mktplace - Produto
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_calculado']       = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_calculado'] + $dadosLinhaConciliacao['valor_frete_calculado'];    // valor receita mktplace - frete
	                
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_da_transacao']          = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_da_transacao'] + $dadosLinhaConciliacao['valor_da_transacao'];    // valor recebido mktplace - total
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto_recebido']      = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto_recebido'] + $dadosLinhaConciliacao['valor_produto_recebido'];    // valor recebido mktplace - produto
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_recebido']        = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_recebido'] + $dadosLinhaConciliacao['valor_frete_recebido'];    // valor recebido mktplace - frete
	                
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_conectala']             = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_conectala'] + $dadosLinhaConciliacao['valor_conectala'];    // valor comissão conectala - total
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto_conecta']       = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto_conecta'] + $dadosLinhaConciliacao['valor_produto_conecta'];    // valor comissão conectala - produto
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_conecta']         = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_conecta'] + $dadosLinhaConciliacao['valor_frete_conecta'];    // valor comissão conectala - frete
	                
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_conecta_novo']    = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_conecta_novo'] + ( $dadosLinhaConciliacao['valor_frete'] * ( $dadosLinhaConciliacao['valor_percentual_parceiro']/100 ) ) - ( $dadosLinhaConciliacao['valor_frete'] * ( $dadosLinhaConciliacao['valor_percentual_mktplace'] /100) );    // valor comissão conectala - frete NOVO
	                
	                
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto_conecta_ajustado']         = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto_conecta_ajustado'] + $dadosLinhaConciliacao['valor_produto_conecta_ajustado'];
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_conecta_ajustado']         = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_conecta_ajustado'] + $dadosLinhaConciliacao['valor_frete_conecta_ajustado'];
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_conectala_ajustado']         = $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_conectala_ajustado'] + $dadosLinhaConciliacao['valor_conectala_ajustado'];
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_parceiro_ajustado']         = $arraySaida[$dadosLinhaConciliacao['store_id']][''] + $dadosLinhaConciliacao['valor_parceiro_ajustado'];
	                
	                
	            }else{
	                
	                //Busca dados loja
	                $params['store_id']                                                                = $dadosLinhaConciliacao['store_id'];
	                $params['providers_id']                                                            = "";
	                $resultado                                                                         = $this->model_iugu->buscaDadosCadastroSubconta($params);
	                
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['data']                            = $dados['data_criacao'];
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['ano_mes']                         = $dados['ano_mes'];
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['descloja']                        = $dados['descloja'];
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['ciclo']                           = $dados['data_inicio']." - ".$dados['data_fim'];
	                
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['store_id']                        = $dadosLinhaConciliacao['store_id'];
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['seller_name']                     = $dadosLinhaConciliacao['seller_name'];
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['raz_social']                      = $resultado['raz_social'];
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['cnpj']                            = $resultado['CNPJ'];
	                
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_pedido']                    = $dadosLinhaConciliacao['valor_pedido'];               // valor pedido mktplace - total
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto']                   = $dadosLinhaConciliacao['valor_produto'];               // valor pedido mktplace - produto
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete']                     = $dadosLinhaConciliacao['valor_frete'];               // valor pedido mktplace - frete CONTRATADO
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_real']                = $dadosLinhaConciliacao['valor_frete_real'];           // valor pedido mktplace - frete REAL
	                
	                
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_receita_calculado']         = $dadosLinhaConciliacao['valor_receita_calculado'];    // valor receita mktplace -Total
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto_calculado']         = $dadosLinhaConciliacao['valor_produto_calculado'];    // valor receita mktplace - Produto
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_calculado']           = $dadosLinhaConciliacao['valor_frete_calculado'];    // valor receita mktplace - frete
	                
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_da_transacao']              = $dadosLinhaConciliacao['valor_da_transacao'];    // valor recebido mktplace - total
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto_recebido']          = $dadosLinhaConciliacao['valor_produto_recebido'];    // valor recebido mktplace - produto
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_recebido']            = $dadosLinhaConciliacao['valor_frete_recebido'];    // valor recebido mktplace - frete
	                
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_conectala']                 = $dadosLinhaConciliacao['valor_conectala'];    // valor comissão conectala - total
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto_conecta']           = $dadosLinhaConciliacao['valor_produto_conecta'];    // valor comissão conectala - produto
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_conecta']             = $dadosLinhaConciliacao['valor_frete_conecta'];    // valor comissão conectala - frete
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_conecta_novo']        = ( $dadosLinhaConciliacao['valor_frete'] * ( $dadosLinhaConciliacao['valor_percentual_parceiro']/100 ) ) - ( $dadosLinhaConciliacao['valor_frete'] * ( $dadosLinhaConciliacao['valor_percentual_mktplace'] /100) );    // valor comissão conectala - frete NOVO
	                
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_produto_conecta_ajustado']  = $dadosLinhaConciliacao['valor_produto_conecta_ajustado'];
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_frete_conecta_ajustado']    = $dadosLinhaConciliacao['valor_frete_conecta_ajustado'];
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_conectala_ajustado']        = $dadosLinhaConciliacao['valor_conectala_ajustado'];
	                $arraySaida[$dadosLinhaConciliacao['store_id']]['valor_parceiro_ajustado']         = $dadosLinhaConciliacao['valor_parceiro_ajustado'];
	                
	            } 
	        }
	    }
	    
	    echo utf8_decode("<table>
                    <tr>
                    <th>".$this->lang->line('application_date')."</th>
                    <th>".$this->lang->line('application_conciliacao_month_year')."</th>
                    <th>".$this->lang->line('application_runmarketplaces')."</th>
                    <th>".$this->lang->line('application_parameter_mktplace_value_ciclo')."</th>
                    <th>".$this->lang->line('application_id')." - ".$this->lang->line('application_store')."</th>
                    <th>".$this->lang->line('application_store')."</th>
                    <th>".$this->lang->line('application_raz_soc')."</th>
                    <th>".$this->lang->line('application_cnpj')."</th>
                    <th>".$this->lang->line('application_conciliacao_mktplace_value')."</th>
                    <th>".$this->lang->line('application_value_products')."</th>
                    <th>".$this->lang->line('application_ship_value')."</th>
                    <th>".$this->lang->line('application_ship_value')." - Real</th>
                    <th>Receita - Marketplace</th>
                    <th>".$this->lang->line('application_value_products')." - Marketplace</th>
                    <th>".$this->lang->line('application_ship_value')." - Marketplace</th>
                    <th>".$this->lang->line('application_marketplace')." - Valor recebido Total</th>
                    <th>".$this->lang->line('application_marketplace')." - Valor recebido produto</th>
                    <th>".$this->lang->line('application_marketplace')." - Valor recebido frete</th>
                    <th>Valor a receber - ConectaLá - Real</th>
                    <th>Valor a receber - ConectaLá - Contratado</th>
                    <th>Valor Comissão Produto - ConectaLá</th>
                    <th>Valor Comissão Frete - ConectaLá - Real</th>
                    <th>Valor Comissão Frete - ConectaLá - Contratado</th>
                    <th>Valor Comissão Produto - ConectaLá - Ajustado</th>
                    <th>Valor Comissão Frete - ConectaLá - Ajustado</th>
                    <th>Valor Comissão Total - ConectaLá - Ajustado</th>
                    <th>Valor Parceiro - Ajustado</th>
                  </tr>");
	    
	    foreach($arraySaida as $dadosTela){
	           
	        echo utf8_decode("<tr>");
	        echo utf8_decode("<td>".$dadosTela['data']."</td>");
	        echo utf8_decode("<td>".$dadosTela['ano_mes']."</td>");
	        echo utf8_decode("<td>".$dadosTela['descloja']."</td>");
	        echo utf8_decode("<td>".$dadosTela['ciclo']."</td>");
	        echo utf8_decode("<td>".$dadosTela['store_id']."</td>");
	        echo utf8_decode("<td>".$dadosTela['seller_name']."</td>");
	        echo utf8_decode("<td>".$dadosTela['raz_social']."</td>");
	        echo utf8_decode("<td>".$dadosTela['cnpj']."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_pedido'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_produto'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_frete'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_frete_real'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_receita_calculado'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_produto_calculado'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_frete_calculado'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_da_transacao'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_produto_recebido'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_frete_recebido'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_conectala'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_produto_conecta'] + $dadosTela['valor_frete_conecta_novo'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_produto_conecta'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_frete_conecta'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_frete_conecta_novo'] )."</td>");
	        
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_produto_conecta_ajustado'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_frete_conecta_ajustado'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_conectala_ajustado'] )."</td>");
	        echo utf8_decode("<td>".str_replace ( ".", ",", $dadosTela['valor_parceiro_ajustado'] )."</td>");
	        
	        echo utf8_decode("</tr>");
	        
	    }
	    
	    echo utf8_decode("</table>");
	    
	}
	
	public function createconcilia(){

        if(!in_array('createBilletConcil', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

	    $group_data1 = $this->model_billet->getMktPlacesData();
	    $group_data2 = $this->model_parametrosmktplace->getReceivablesDataCiclo();
	    $obsFixo = $this->model_billet->getDataObservacaoFixaPedido();
	    
	    $group_data3 = array();
	    $group_data3['id_mkt'] = "";
	    $group_data3['id_ciclo'] = "";
	    $group_data3['ano_mes'] = "";
	    $group_data3['carregado'] = "0";
	    
	    $this->data['mktplaces'] = $group_data1;
	    $this->data['ciclo'] = $group_data2;
	    $this->data['hdnLote'] = date('YmdHis').rand(1,1000000);
	    $this->data['dadosBanco'] = $group_data3;
	    $this->data['obsFixo'] = $obsFixo;
	    $this->data['negociacao_marketplace_campanha'] = ($this->negociacao_marketplace_campanha);
        $this->data['canceled_orders_data_conciliation'] = ($this->canceled_orders_data_conciliation);
		$this->data['fin_192_novos_calculos'] = $this->fin_192_novos_calculos;

	    $this->render_template('billet/create', $this->data);
	}
	
	public function edit($lote){
	    
	    
	    $group_data1 = $this->model_billet->getMktPlacesData();
	    $group_data2 = $this->model_parametrosmktplace->getReceivablesDataCiclo();
	    $group_data3 = $data = $this->model_billet->getConciliacaoGridData($lote);
	    $group_data3[0]['carregado'] = 1;
	    $obsFixo = $this->model_billet->getDataObservacaoFixaPedido();
	    
	    //Limpa a tabela conciliacao
	    //$this->model_billet->limpatabelaconciliacaotempedido($lote);
	    
	    
	    $this->data['mktplaces'] = $group_data1;
	    $this->data['ciclo'] = $group_data2;
	    $this->data['hdnLote'] = $lote;
	    $this->data['dadosBanco'] = $group_data3[0];
	    $this->data['obsFixo'] = $obsFixo;
        $this->data['negociacao_marketplace_campanha'] = ($this->negociacao_marketplace_campanha);
        $this->data['canceled_orders_data_conciliation'] = ($this->canceled_orders_data_conciliation);

		$this->data['fin_192_novos_calculos'] = $this->fin_192_novos_calculos;
		
	    $this->render_template('billet/create', $this->data);
	}
	
	public function uploadArquivo()
    {
	    

	    if (!empty($_FILES))
        {

			if($this->postClean('id') <> "999"){
				$group_data1 = $this->model_billet->getMktPlacesDataID($this->postClean('id'));
				$apelido    = $group_data1[0]['apelido'];
			}else{
				$apelido = "MANUAL";
			}

	        $exp_extens = explode( ".", $_FILES['product_upload']['name']) ;
	        $extensao   = $exp_extens[count($exp_extens)-1];

	        $lote       = $this->postClean('lote');
	        $tempFile   = $_FILES['product_upload']['tmp_name'];
	        
            $caminhoMapeado = str_replace('\\', '/', getcwd().$this->conciliation_folder);

            if (!is_dir($caminhoMapeado))
                mkdir($caminhoMapeado, 0755, true);

	        $targetPath = $caminhoMapeado;
	        $targetFile =  str_replace('//','/',$targetPath) . $apelido .'-'. $lote . '.' . $extensao;
	        
	        move_uploaded_file($tempFile,$targetFile);
	        
	        $retorno['ret'] = "sucesso";
	        $retorno['extensao'] = $extensao;
	        
            ob_clean();
	        echo json_encode($retorno);
	    }
	    
	    
	}
	
	public function learquivoconciliacao()
    {

	    $inputs = $this->postClean(NULL,TRUE);
		
		if($inputs['slc_mktplace'] <> "999"){
			$group_data1 = $this->model_billet->getMktPlacesDataID($inputs['slc_mktplace']);
			$apelido = $group_data1[0]['apelido'];
		}else{
			$apelido = "MANUAL";
		}

        $caminhoMapeado = str_replace('\\', '/', getcwd().$this->conciliation_folder);

		$tipoArquivoVV = "Cartão";
	    
	    $arquivo = $apelido .'-'. $inputs['hdnLote'] . '.' . $inputs['hdnExtensao'];
	    $inputs['arquivo'] = $arquivo;

		$checkArquivo = false;

		//$this->db->trans_begin();

	    if (file_exists($caminhoMapeado.$arquivo)){

			//Check se o arquivo de input é o ajuste de conciliação
			if($inputs['hdnExtensao'] == "xlsx"){

				$checkArquivo = true;

				error_reporting(1);
	            //import lib excel
	            require_once (APPPATH . '/third_party/PHPExcel/IOFactory.php');
	            $objPHPExcel = PHPExcel_IOFactory::load($caminhoMapeado.$arquivo);
	                
				$testeLinha = 1;
				
				foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
					
					$worksheetTitle     = $worksheet->getTitle();
					$highestRow         = $worksheet->getHighestRow(); // e.g. 10
					$highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
					$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
					$nrColumns = ord($highestColumn) - 64;
					
					//lê a linha
					for ($row = 1; $row <= $highestRow; ++ $row) {
						
						$valorColuna = 1;
						$arrayValores = array();
						
						//lê a coluna
						for ($col = 0; $col < $highestColumnIndex; ++ $col) {
							$cell = $worksheet->getCellByColumnAndRow($col, $row);
							$val = $cell->getValue();
							$arrayValores[$valorColuna] = $val;
							$valorColuna++;
						}
						
						
						if($testeLinha == "1"){
							
							$testeLinha ++;
							
							$cabecalho[1] = "Número Pedido";
							$cabecalho[2] = "Valor Produto Conecta Lá";
							$cabecalho[3] = "Valor Frete Conecta Lá";
							$cabecalho[4] = "Valor Parceiro";
							
							$count = 1;
							foreach ($arrayValores as $colunas){
								if( $colunas <> $cabecalho[$count]){
									$checkArquivo = false;
								}
								$count++;
							}
							
						}else{
							
							$testeLinha ++;

							if($checkArquivo){

								$save = $this->model_billet->atualizaValorConciliacaoArquivoMovelo($inputs,$arrayValores);
								if ($save == false){
									echo "Erro ao atualizar a tabela";die;
								}

							}
						}
					}
				}
			}

	        
	        if($inputs['hdnExtensao'] == "csv"){
	            
	            $arquivo = fopen($caminhoMapeado.$arquivo, "r");
	            $row = 1;  
	            while (($data = fgetcsv($arquivo, 10000, ";", "'", "\n")) !== FALSE) {
	                
	                if($row == "1"){
	                    $count = 1;
	                    if($inputs['slc_mktplace'] == "10"){
	                        
	                        $cabecalho[1] = "Marca";
	                        $cabecalho[2] = "Nome Fantasia";
	                        $cabecalho[3] = "Data pedido";
	                        $cabecalho[4] = "Data Pagamento";
	                        $cabecalho[5] = "Data Estorno";
	                        $cabecalho[6] = "Data Liberação";
	                        $cabecalho[7] = "Data Prevista Pgto";
	                        $cabecalho[8] = "Lançamento";
	                        $cabecalho[9] = "Ref. Pedido";
	                        $cabecalho[10] = "Entrega";
	                        $cabecalho[11] = "Tipo";
	                        $cabecalho[12] = "Status";
	                        $cabecalho[13] = "Valor";
	                        $cabecalho[14] = "Parcela";
	                        $cabecalho[15] = "Meio Pgto";
	                        $cabecalho[16] = "Modelo Financeiro";
	                        
	                        foreach ($data as $colunas){
	                            
	                            if(str_replace( "\"", "", utf8_encode($colunas) ) <> $cabecalho[$count]){
	                                echo "Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".utf8_encode($colunas)." esperado ".$cabecalho[$count];die;
	                            }
	                            $count++;
	                        }
	                        
	                    }elseif($inputs['slc_mktplace'] == "15"){

							$cabecalho[1] = "Número Pedido";
	                        $cabecalho[2] = "Id Entrega";
	                        $cabecalho[3] = "Tipo da Transação";
	                        $cabecalho[4] = "Marca";
	                        $cabecalho[5] = "Data do Pedido Incluído";
	                        $cabecalho[6] = "Data do Pedido Entregue";
	                        $cabecalho[7] = "Data Liberação";
	                        $cabecalho[8] = "Data Prevista do Repasse";
	                        $cabecalho[9] = "Data do Repasse";
	                        $cabecalho[10] = "Data Antecipação";
	                        $cabecalho[11] = "Número de Liquidação";
	                        $cabecalho[12] = "SKU Marketplace";
	                        $cabecalho[13] = "SKU Lojista";
	                        $cabecalho[14] = "Valor do Produto sem desconto";
	                        $cabecalho[15] = "Desconto Ônus Via";
	                        $cabecalho[16] = "Desconto Ônus Lojista";
	                        $cabecalho[17] = "Valor do Frete";
	                        $cabecalho[18] = "Tipo do Frete";
	                        $cabecalho[19] = "Frete Promocional Ônus Via";
	                        $cabecalho[20] = "Frete Promocional Ônus Lojista";
	                        $cabecalho[21] = "Valor da Transação";
	                        $cabecalho[22] = "Comissão Contratual %";
	                        $cabecalho[23] = "Comissão Aplicada %";
	                        $cabecalho[24] = "Comissão Aplicada R$";
	                        $cabecalho[25] = "Número de Parcelas";
	                        $cabecalho[26] = "Parcela Atual";
	                        $cabecalho[27] = "Valor Parcela";
	                        $cabecalho[28] = "Valor Bruto de Repasse";
							$cabecalho[29] = "Valor da Antecipação";
							$cabecalho[30] = "Taxa de Antecipação";
							$cabecalho[31] = "Valor Líquido de Repasse";
							$cabecalho[32] = "Motivo do Ajuste";
							$cabecalho[33] = "Observações";
							$cabecalho[34] = "Origem Repasse";
							$cabecalho[35] = "Meio de Pagamento";
							$cabecalho[36] = "Tipo de Campanha";
							$cabecalho[37] = "Ajuste realizado outro ciclo";
							$cabecalho[38] = "Valor ajuste realizado ciclos anteriores";
							$cabecalho[39] = "Data do ajuste";
							$cabecalho[40] = "NF Repasse";
							$cabecalho[41] = "NF Cliente";
							$cabecalho[42] = "Descrição do produto";
							$cabecalho[43] = "Departamento";
							$cabecalho[44] = "Categoria";
							$saida = "";

	                        $encontradoErroCartao = false;
							$encontradoErroBoleto = false;

	                        foreach ($data as $colunas){
	                            if(str_replace("?","",str_replace( "\"", "", utf8_encode(utf8_decode($colunas)) )) <> $cabecalho[$count]){
	                                $saida .= "<br>Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".str_replace("?","",str_replace( "\"", "", utf8_encode(utf8_decode($colunas)) ))." esperado ".$cabecalho[$count];
									$encontradoErroCartao = true;
	                            }
	                            $count++;
	                        }
							
							$cabecalhoBoleto[1] = "Ciclo da transação";
							$cabecalhoBoleto[2] = "Ciclo do vencimento";
							$cabecalhoBoleto[3] = "Data de vencimento";
							$cabecalhoBoleto[4] = "Data do pedido";
							$cabecalhoBoleto[5] = "Data do envio do pedido";
							$cabecalhoBoleto[6] = "Tipo da transação";
							$cabecalhoBoleto[7] = "Número do pedido";
							$cabecalhoBoleto[8] = "Sequencial do item";
							$cabecalhoBoleto[9] = "Descrição do produto";
							$cabecalhoBoleto[10] = "NSU";
							$cabecalhoBoleto[11] = "Forma de pagamento";
							$cabecalhoBoleto[12] = "Total de parcelas";
							$cabecalhoBoleto[13] = "Número da parcela";
							$cabecalhoBoleto[14] = "Total do pedido";
							$cabecalhoBoleto[15] = "Valor da transação";
							$cabecalhoBoleto[16] = "Valor da comissão";
							$cabecalhoBoleto[17] = "Valor do repasse";
							$cabecalhoBoleto[18] = "Valor do Item";
							$cabecalhoBoleto[19] = "Valor do Frete";
							$cabecalhoBoleto[20] = "Tipo do Frete";
							$cabecalhoBoleto[21] = "Usuário Responsável";
							$cabecalhoBoleto[22] = "Motivo";
							$cabecalhoBoleto[23] = "Data de Notificação de envio";
							$cabecalhoBoleto[24] = "Origem";

							if($encontradoErroCartao){
								$count = 1;
								foreach ($data as $colunas){
									if(str_replace( "\"", "", utf8_encode(utf8_decode($colunas)) ) <> $cabecalhoBoleto[$count]){
										$saida .= "<br>Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".utf8_encode(utf8_decode($colunas))." esperado ".$cabecalhoBoleto[$count];
										$encontradoErroBoleto = true;
									}
									$count++;
								}

								if($encontradoErroBoleto == false){
									$tipoArquivoVV = "Boleto";
								}
							}

							if($encontradoErroBoleto == true){
								if($encontradoErroCartao == true ){
									echo $saida;die;
								}
							}

	                    }elseif($inputs['slc_mktplace'] == "16"){
	                        	                        
							$cabecalho[1] = "Data de criação";
							$cabecalho[2] = "Data recebida";
							$cabecalho[3] = "Data da transação";
							$cabecalho[4] = "Loja";
							$cabecalho[5] = "N° do pedido";
							$cabecalho[6] = "Número da fatura";
							$cabecalho[7] = "Número da transação";
							$cabecalho[8] = "Quantidade";
							$cabecalho[9] = "Rótulo da categoria";
							$cabecalho[10] = "SKU da oferta";
							$cabecalho[11] = "Descrição";
							$cabecalho[12] = "Tipo";
							$cabecalho[13] = "Status do pagamento";
							$cabecalho[14] = "Quantidade";
							$cabecalho[15] = "Débito";
							$cabecalho[16] = "Crédito";
							$cabecalho[17] = "Saldo";
							$cabecalho[18] = "Moeda";
							$cabecalho[19] = "Referência do pedido do cliente";
							$cabecalho[20] = "Referência do pedido loja";
							$cabecalho[21] = "Data do ciclo de faturamento";
	                        
	                        foreach ($data as $colunas){
	                            if( trim(utf8_encode( utf8_decode($colunas)) ) <> $cabecalho[$count]){
	                                
	                                echo "Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".utf8_encode(utf8_decode($colunas))." esperado ".$cabecalho[$count];die;
	                            }
	                            $count++;
	                        }

	                    }elseif($inputs['slc_mktplace'] == "11"){
	                        
	                        $cabecalho[1] = "DATE";
	                        $cabecalho[2] = "SOURCE_ID";
	                        $cabecalho[3] = "EXTERNAL_REFERENCE";
	                        $cabecalho[4] = "RECORD_TYPE";
	                        $cabecalho[5] = "DESCRIPTION";
	                        $cabecalho[6] = "NET_CREDIT_AMOUNT";
	                        $cabecalho[7] = "NET_DEBIT_AMOUNT";
	                        $cabecalho[8] = "GROSS_AMOUNT";
	                        $cabecalho[9] = "SELLER_AMOUNT";
	                        $cabecalho[10] = "MP_FEE_AMOUNT";
	                        $cabecalho[11] = "FINANCING_FEE_AMOUNT";
	                        $cabecalho[12] = "SHIPPING_FEE_AMOUNT";
	                        $cabecalho[13] = "TAXES_AMOUNT";
	                        $cabecalho[14] = "COUPON_AMOUNT";
	                        $cabecalho[15] = "INSTALLMENTS";
	                        $cabecalho[16] = "PAYMENT_METHOD";
	                        $cabecalho[17] = "TAX_DETAIL";
	                        $cabecalho[18] = "TAX_AMOUNT_TELCO";
	                        $cabecalho[19] = "TRANSACTION_APPROVAL_DATE";
	                        $cabecalho[20] = "POS_ID";
	                        $cabecalho[21] = "POS_NAME";
	                        $cabecalho[22] = "EXTERNAL_POS_ID";
	                        $cabecalho[23] = "STORE_ID";
	                        $cabecalho[24] = "STORE_NAME";
	                        $cabecalho[25] = "EXTERNAL_STORE_ID";
	                        $cabecalho[26] = "CURRENCY";
	                        $cabecalho[27] = "TAXES_DISAGGREGATED";
	                        $cabecalho[28] = "SHIPPING_ID";
	                        $cabecalho[29] = "SHIPMENT_MODE";
	                        $cabecalho[30] = "ORDER_ID";
	                        $cabecalho[31] = "PACK_ID";
	                        $cabecalho[32] = "METADATA";
	                        $cabecalho[33] = "REFUND_ID";
	                        
	                        foreach ($data as $colunas){
	                            if( trim(utf8_encode( utf8_decode($colunas)) ) <> $cabecalho[$count]){
	                                
	                                echo "Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".utf8_encode(utf8_decode($colunas))." esperado ".$cabecalho[$count];die;
	                            }
	                            $count++;
	                        }
	                    }elseif($inputs['slc_mktplace'] == "17"){
	                        
							$cabecalho[1] = "Data";
	                        $cabecalho[2] = "Valor";
	                        $cabecalho[3] = "Descrição";
	                        $cabecalho[4] = "pedido";
	                        $cabecalho[5] = "Pedido MM";
	                        $cabecalho[6] = "Data liberação";
	                        $cabecalho[7] = "Detalhamento";
	                        
	                        foreach ($data as $colunas){
	                            //if( trim( iconv(mb_detect_encoding($colunas, mb_detect_order(), true), "UTF-8", $colunas) ) <> $cabecalho[$count] ){
								if( trim( iconv('ASCII', 'UTF-8//IGNORE', $colunas) ) <> $cabecalho[$count] ){
	                                echo "Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".iconv('ASCII', 'UTF-8//IGNORE', $colunas)." esperado ".$cabecalho[$count];die;
	                            }
	                            $count++;
	                        }
	                    }
                        elseif ($inputs['slc_mktplace'] == "30")
                        {

	                        $cabecalho[1] = "id_transf";
	                        $cabecalho[2] = "operacao";
	                        $cabecalho[3] = "cod_transf";
	                        $cabecalho[4] = "id_pedido_nm";
	                        $cabecalho[5] = "id_parc";
	                        $cabecalho[6] = "id_fornecedor";
	                        $cabecalho[7] = "seller";
	                        $cabecalho[8] = "sequence";
	                        $cabecalho[9] = "data_emissao";
	                        $cabecalho[10] = "data_entrega";
	                        $cabecalho[11] = "orderid";
	                        $cabecalho[12] = "cpf_cnpj";
	                        $cabecalho[13] = "nome";
	                        $cabecalho[14] = "situacao";
	                        $cabecalho[15] = "total_pedido";
	                        $cabecalho[16] = "valor_comissao";
	                        $cabecalho[17] = "valor_repasse";
	                        $cabecalho[18] = "valor_ir";
	                        $cabecalho[19] = "total";

	                        foreach ($data as $colunas)
                            {
	                            if (str_replace( "\"", "", utf8_encode($colunas) ) <> $cabecalho[$count])
                                {
	                                echo "Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".utf8_encode($colunas)." esperado ".$cabecalho[$count];
									die;
	                            }

	                            $count++;
	                        }
                        }
	                }

	                if ( $row > 1 ){
    	                //Salva no banco o a linha
    	                if($inputs['slc_mktplace'] == "10"){
    	                    $save = $this->model_billet->salvarArquivoB2WTable($inputs,$data);
    	                    if ($save == false){
    	                        echo "Erro ao subir na tabela";die;
    	                    }
    	                }elseif($inputs['slc_mktplace'] == "15"){
							if($tipoArquivoVV == 'Cartão'){
								$save = $this->model_billet->salvarArquivoViaVarejoTable($inputs,$data);
							}else{
								$save = $this->model_billet->salvarArquivoViaVarejoTableBoleto($inputs,$data);
							}
    	                    if ($save == false){
    	                        echo "Erro ao subir na tabela";die;
    	                    }
    	                }elseif($inputs['slc_mktplace'] == "16"){
    	                    $save = $this->model_billet->salvarArquivoCarrefourTable($inputs,$data);
    	                    if ($save == false){
    	                        echo "Erro ao subir na tabela";die;
    	                    }
    	                }elseif($inputs['slc_mktplace'] == "11"){
    	                    $save = $this->model_billet->salvarArquivoMLTableCSV($inputs,$arrayValores);
    	                    if ($save == false){
    	                        echo "Erro ao subir na tabela";die;
    	                    }
    	                }
                        else if ($inputs['slc_mktplace'] == "30")
                        {
    	                    $save = $this->model_billet->salvarArquivoNMTable($inputs, $data);

    	                    if ($save == false)
                            {
                                echo "Erro ao subir na tabela";
								die;
                            }

                        }
	               }
	                $row++;
	            }
	            fclose($arquivo);

				if ($save == false){
					//$this->db->trans_commit();
					die;
				}

	               
	            if($inputs['slc_mktplace'] == "10"){
	                //Trata a conciliação
	                $save2 = $this->model_billet->consiliaarquivoB2W($inputs);
	                if ($save2){
	                    //Atualiza os valores divididos nas tabelas 
	                    $save3 = $this->model_billet->atualizavaloresconciliadivisaoB2W($inputs);
	                    if($save3){
	                       echo "Feito com sucesso!";die;
	                    }else{
	                        echo "Erro ao conciliar arquivo carregado";die;
	                    }
	                }else{
	                    echo "Erro ao conciliar arquivo carregado";die;
	                }
	            }elseif($inputs['slc_mktplace'] == "15"){ 
	                //Trata a conciliação
	                $save2 = $this->model_billet->consiliaarquivoViaVarejo($inputs);
	                if ($save2){
	                    //Atualiza os valores divididos nas tabelas
	                    $save3 = $this->model_billet->atualizavaloresconciliadivisaoViaVArejo($inputs);
	                    if($save3){
	                        echo "Feito com sucesso!";die;
	                    }else{
	                        echo "Erro ao conciliar arquivo carregado";die;
	                    }
	                }else{
	                    echo "Erro ao conciliar arquivo carregado";die;
	                }
	            }elseif($inputs['slc_mktplace'] == "16"){ 
	                //Trata a conciliação
	                $save2 = $this->model_billet->consiliaarquivoCarrefour($inputs);
	                if ($save2){
	                    //Atualiza os valores divididos nas tabelas
	                    $save3 = $this->model_billet->atualizavaloresconciliadivisaoCarrefour($inputs);
	                    if($save3){
	                        echo "Feito com sucesso!";die;
	                    }else{
	                        echo "Erro ao conciliar arquivo carregado";die;
	                    }
	                }else{
	                    echo "Erro ao conciliar arquivo carregado";die;
	                }
	            }elseif($inputs['slc_mktplace'] == "11"){
	                //Trata a conciliação
	                $save2 = $this->model_billet->consiliaarquivoML($inputs);
	                if ($save2){
	                    //Atualiza os valores divididos nas tabelas
	                    $save3 = $this->model_billet->atualizavaloresconciliadivisaoML($inputs);
	                    if($save3){
	                        echo "Feito com sucesso!";die;
	                    }else{
	                        echo "Erro ao conciliar arquivo carregado";die;
	                    }
	                }else{
	                    echo "Erro ao conciliar arquivo carregado";die;
	                }
	            }
                else if ($inputs['slc_mktplace'] == "30")
                {
	                //Trata a conciliação
	                $save2 = $this->model_billet->consiliaarquivoNM($inputs); // alterar e duplicar la no model_billet]

	                if ($save2)
                    {
	                    //Atualiza os valores divididos nas tabelas
	                    $save3 = $this->model_billet->atualizavaloresconciliadivisaoNM($inputs);

	                    if ($save3)
                        {
	                       echo "Feito com sucesso!";die;
	                    }
                        else
                        {
	                        echo "Erro ao conciliar arquivo carregado";die;
	                    }
	                }
                    else
                    {
	                    echo "Erro ao conciliar arquivo carregado";die;
	                }
                }

	        }elseif($checkArquivo == false){
	            
	            error_reporting(1);
	            //import lib excel
	            require_once (APPPATH . '/third_party/PHPExcel/IOFactory.php');
	            $objPHPExcel = PHPExcel_IOFactory::load($caminhoMapeado.$arquivo);
	            if($inputs['slc_mktplace'] == "11"){
	                
	                $testeLinha = 1;
	                
	                foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
	                    
	                    $worksheetTitle     = $worksheet->getTitle();
	                    $highestRow         = $worksheet->getHighestRow(); // e.g. 10
	                    $highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
	                    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
	                    $nrColumns = ord($highestColumn) - 64;
	                    
	                    //lê a linha
	                    for ($row = 1; $row <= $highestRow; ++ $row) {
	                        
	                        $valorColuna = 1;
	                        $arrayValores = array();
	                        
	                        //lê a coluna
	                        for ($col = 0; $col < $highestColumnIndex; ++ $col) {
	                            $cell = $worksheet->getCellByColumnAndRow($col, $row);
	                            $val = $cell->getValue();
	                            //$dataType = PHPExcel_Cell_DataType::dataTypeForValue($val);
	                            $arrayValores[$valorColuna] = $val;
	                            $valorColuna++;
	                        }
	                        
	                        
	                        if($testeLinha == "1"){
	                            
	                            $testeLinha ++;
	                            
	                            $cabecalho[1] = "DATE";
	                            $cabecalho[2] = "SOURCE_ID";
	                            $cabecalho[3] = "EXTERNAL_REFERENCE";
	                            $cabecalho[4] = "RECORD_TYPE";
	                            $cabecalho[5] = "DESCRIPTION";
	                            $cabecalho[6] = "NET_CREDIT_AMOUNT";
	                            $cabecalho[7] = "NET_DEBIT_AMOUNT";
	                            $cabecalho[8] = "GROSS_AMOUNT";
	                            $cabecalho[9] = "SELLER_AMOUNT";
	                            $cabecalho[10] = "MP_FEE_AMOUNT";
	                            $cabecalho[11] = "FINANCING_FEE_AMOUNT";
	                            $cabecalho[12] = "SHIPPING_FEE_AMOUNT";
	                            $cabecalho[13] = "TAXES_AMOUNT";
	                            $cabecalho[14] = "COUPON_AMOUNT";
	                            $cabecalho[15] = "INSTALLMENTS";
	                            $cabecalho[16] = "PAYMENT_METHOD";
	                            $cabecalho[17] = "TAX_DETAIL";
	                            $cabecalho[18] = "TAX_AMOUNT_TELCO";
	                            $cabecalho[19] = "TRANSACTION_APPROVAL_DATE";
	                            $cabecalho[20] = "POS_ID";
	                            $cabecalho[21] = "POS_NAME";
	                            $cabecalho[22] = "EXTERNAL_POS_ID";
	                            $cabecalho[23] = "STORE_ID";
	                            $cabecalho[24] = "STORE_NAME";
	                            $cabecalho[25] = "EXTERNAL_STORE_ID";
	                            $cabecalho[26] = "CURRENCY";
	                            $cabecalho[27] = "TAXES_DISAGGREGATED";
	                            $cabecalho[28] = "SHIPPING_ID";
	                            $cabecalho[29] = "SHIPMENT_MODE";
	                            $cabecalho[30] = "ORDER_ID";
	                            $cabecalho[31] = "PACK_ID";
	                            $cabecalho[32] = "METADATA";
	                            $cabecalho[33] = "REFUND_ID";
	                            
	                            $count = 1;
	                            foreach ($arrayValores as $colunas){
	                                if( $colunas <> $cabecalho[$count]){
	                                    echo "Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".$colunas." esperado ".$cabecalho[$count];die;
	                                }
	                                $count++;
	                            }
	                            
	                        }else{
	                            
	                            $testeLinha ++;
	                            
                                $save = $this->model_billet->salvarArquivoMLTable($inputs,$arrayValores);
                                if ($save == false){
                                    echo "Erro ao subir na tabela";die;
                                }
	                        }
	                    }
	                    
	                }
	                
	            }elseif($inputs['slc_mktplace'] == "16"){
	                
	                $testeLinha = 1;
	                
	                foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
	                    
	                    $worksheetTitle     = $worksheet->getTitle();
	                    $highestRow         = $worksheet->getHighestRow(); // e.g. 10
	                    $highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
	                    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
	                    $nrColumns = ord($highestColumn) - 64;
	                    
	                    //lê a linha
	                    for ($row = 1; $row <= $highestRow; ++ $row) {
	                        
	                        $valorColuna = 1;
	                        $arrayValores = array();
	                        
	                        //lê a coluna
	                        for ($col = 0; $col < $highestColumnIndex; ++ $col) {
	                            $cell = $worksheet->getCellByColumnAndRow($col, $row);
	                            $val = $cell->getValue();
	                            $arrayValores[$valorColuna] = $val;
	                            $valorColuna++;
	                        }
	                        
	                        
	                        if($testeLinha == "1"){
	                            
	                            $testeLinha ++;

								$cabecalho[1] = "Data de criação";
								$cabecalho[2] = "Data recebida";
								$cabecalho[3] = "Data da transação";
								$cabecalho[4] = "Loja";
								$cabecalho[5] = "N° do pedido";
								$cabecalho[6] = "Número da fatura";
								$cabecalho[7] = "Número da transação";
								$cabecalho[8] = "Quantidade";
								$cabecalho[9] = "Rótulo da categoria";
								$cabecalho[10] = "SKU da oferta";
								$cabecalho[11] = "Descrição";
								$cabecalho[12] = "Tipo";
								$cabecalho[13] = "Status do pagamento";
								$cabecalho[14] = "Quantidade";
								$cabecalho[15] = "Débito";
								$cabecalho[16] = "Crédito";
								$cabecalho[17] = "Saldo";
								$cabecalho[18] = "Moeda";
								$cabecalho[19] = "Referência do pedido do cliente";
								$cabecalho[20] = "Referência do pedido loja";
								$cabecalho[21] = "Data do ciclo de faturamento";
	                            
	                            $count = 1;
	                            foreach ($arrayValores as $colunas){
	                                if( $colunas <> $cabecalho[$count]){
	                                    echo "Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".$colunas." esperado ".$cabecalho[$count];die;
	                                }
	                                $count++;
	                            }
	                            
	                        }else{
	                            
	                            $testeLinha ++;
	                            
	                            if( ($arrayValores[1] <> "" or $arrayValores[1] <> null ) and 
	                                ($arrayValores[9] <> "" or $arrayValores[9] <> null ) and 
	                                ($arrayValores[18] <> "" or $arrayValores[18] <> null ) ){
	                                $save = $this->model_billet->salvarArquivoCarrefourTableXls($inputs,$arrayValores);
	                                if ($save == false){
	                                    echo "Erro ao subir na tabela";die;
	                                }
	                            }
	                        }
	                    }
	                    
	                }
	                
	            }elseif($inputs['slc_mktplace'] == "17"){
	                
	                $testeLinha = 1;
	                
	                foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
	                    
	                    $worksheetTitle     = $worksheet->getTitle();
	                    $highestRow         = $worksheet->getHighestRow(); // e.g. 10
	                    $highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
	                    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
	                    $nrColumns = ord($highestColumn) - 64;
	                    
	                    //lê a linha
	                    for ($row = 1; $row <= $highestRow; ++ $row) {
	                        
	                        $valorColuna = 1;
	                        $arrayValores = array();
	                        
	                        //lê a coluna
	                        for ($col = 0; $col < $highestColumnIndex; ++ $col) {
	                            $cell = $worksheet->getCellByColumnAndRow($col, $row);
	                            $val = $cell->getValue();
	                            //$dataType = PHPExcel_Cell_DataType::dataTypeForValue($val);
	                            $arrayValores[$valorColuna] = $val;
	                            $valorColuna++;
	                        }
	                        
	                        
	                        if($testeLinha == "1"){
	                            
	                            $testeLinha ++;
	                            
								$cabecalho[1] = "Data";
								$cabecalho[2] = "Valor";
								$cabecalho[3] = "Descrição";
								$cabecalho[4] = "Pedido";
								$cabecalho[5] = "Pedido MM";
								$cabecalho[6] = "Data liberação";
								$cabecalho[7] = "Detalhamento";
	                            
	                            $count = 1;

	                            foreach ($arrayValores as $colunas){
	                                if( $colunas <> $cabecalho[$count]){
	                                    echo "Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".$colunas." esperado ".$cabecalho[$count];die;
	                                }
	                                $count++;
	                            }
	                            
	                        }else{
	                            
	                            $testeLinha ++;
	                            
                                $save = $this->model_billet->salvarArquivoMadeiraTable($inputs,$arrayValores);
                                if ($save == false){
                                    echo "Erro ao subir na tabela";die;
                                }
	                        }
	                    }
	                    
	                }
	                
	            }elseif($inputs['slc_mktplace'] == "15"){

					$testeLinha = 1;

	                foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {

	                    $worksheetTitle     = $worksheet->getTitle();
	                    $highestRow         = $worksheet->getHighestRow(); // e.g. 10
	                    $highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
	                    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
	                    $nrColumns = ord($highestColumn) - 64;

	                    //lê a linha
	                    for ($row = 1; $row <= $highestRow; ++ $row) {

	                        $valorColuna = 1;
	                        $arrayValores = array();

	                        //lê a coluna
	                        for ($col = 0; $col < $highestColumnIndex; ++ $col) {
	                            $cell = $worksheet->getCellByColumnAndRow($col, $row);
	                            $val = $cell->getValue();
	                            //$dataType = PHPExcel_Cell_DataType::dataTypeForValue($val);
	                            $arrayValores[$valorColuna] = $val;
	                            $valorColuna++;
	                        }

	                        if($testeLinha == "1"){

	                            $testeLinha ++;

	                            $cabecalho[1] = "Número Pedido";
								$cabecalho[2] = "Id Entrega";
								$cabecalho[3] = "Tipo da Transação";
								$cabecalho[4] = "Marca";
								$cabecalho[5] = "Data do Pedido Incluído";
								$cabecalho[6] = "Data do Pedido Entregue";
								$cabecalho[7] = "Data Liberação";
								$cabecalho[8] = "Data Prevista do Repasse";
								$cabecalho[9] = "Data do Repasse";
								$cabecalho[10] = "Data Antecipação";
								$cabecalho[11] = "Número de Liquidação";
								$cabecalho[12] = "SKU Marketplace";
								$cabecalho[13] = "SKU Lojista";
								$cabecalho[14] = "Valor do Produto sem desconto";
								$cabecalho[15] = "Desconto Ônus Via";
								$cabecalho[16] = "Desconto Ônus Lojista";
								$cabecalho[17] = "Valor do Frete";
								$cabecalho[18] = "Tipo do Frete";
								$cabecalho[19] = "Frete Promocional Ônus Via";
								$cabecalho[20] = "Frete Promocional Ônus Lojista";
								$cabecalho[21] = "Valor da Transação";
								$cabecalho[22] = "Comissão Contratual %";
								$cabecalho[23] = "Comissão Aplicada %";
								$cabecalho[24] = "Comissão Aplicada R$";
								$cabecalho[25] = "Número de Parcelas";
								$cabecalho[26] = "Parcela Atual";
								$cabecalho[27] = "Valor Parcela";
								$cabecalho[28] = "Valor Bruto de Repasse";
								$cabecalho[29] = "Valor da Antecipação";
								$cabecalho[30] = "Taxa de Antecipação";
								$cabecalho[31] = "Valor Líquido de Repasse";
								$cabecalho[32] = "Motivo do Ajuste";
								$cabecalho[33] = "Observações";
								$cabecalho[34] = "Origem Repasse";
								$cabecalho[35] = "Meio de Pagamento";
								$cabecalho[36] = "Tipo de Campanha";
								$cabecalho[37] = "Ajuste realizado outro ciclo";
								$cabecalho[38] = "Valor ajuste realizado ciclos anteriores";
								$cabecalho[39] = "Data do ajuste";
								$cabecalho[40] = "NF Repasse";
								$cabecalho[41] = "NF Cliente";
								$cabecalho[42] = "Descrição do produto";
								$cabecalho[43] = "Departamento";
								$cabecalho[44] = "Categoria";

	                            $count = 1;
	                            foreach ($arrayValores as $colunas){
	                                if( $colunas <> $cabecalho[$count]){
	                                    echo "Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".$colunas." esperado ".$cabecalho[$count];die;
	                                }
	                                $count++;
	                            }

	                        }else{

	                            $testeLinha ++;

                                $save = $this->model_billet->salvarArquivoViaVarejoTable($inputs,$arrayValores);
                                if ($save == false){
                                    echo "Erro ao subir na tabela";die;
                                }

	                        }
	                    }

	                }

				}
                elseif($inputs['slc_mktplace'] == "30"){
	                $testeLinha = 1;

	                foreach ($objPHPExcel->getWorksheetIterator() as $worksheet)
                    {
	                    $worksheetTitle     = $worksheet->getTitle();
	                    $highestRow         = $worksheet->getHighestRow(); // e.g. 10
	                    $highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
	                    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
	                    $nrColumns = ord($highestColumn) - 64;

	                    //lê a linha
	                    for ($row = 1; $row <= $highestRow; ++ $row)
                        {
	                        $valorColuna = 1;
	                        $arrayValores = array();

	                        //lê a coluna
	                        for ($col = 0; $col < $highestColumnIndex; ++ $col)
                            {
	                            $cell = $worksheet->getCellByColumnAndRow($col, $row);
	                            $val = $cell->getValue();
	                            //$dataType = PHPExcel_Cell_DataType::dataTypeForValue($val);
	                            $arrayValores[$valorColuna] = $val;
	                            $valorColuna++;
	                        }

	                        if ($testeLinha == "1")
                            {
	                            $testeLinha ++;

	                            $cabecalho[1] = "Seller";
		                        $cabecalho[2] = "Pedido";
		                        $cabecalho[3] = "Pedido MM";
		                        $cabecalho[4] = "Data Lançamento";
		                        $cabecalho[5] = "Data Pagamento";
		                        $cabecalho[6] = "Data liberação";
		                        $cabecalho[7] = "Data Prevista Pagamento";
		                        $cabecalho[8] = "Status";
		                        $cabecalho[9] = "Tipo";
		                        $cabecalho[10] = "Valor";

	                            $count = 1;
	                            foreach ($arrayValores as $colunas)
                                {
	                                if ( $colunas <> $cabecalho[$count])
	                                    echo "Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".$colunas." esperado ".$cabecalho[$count];die;

	                                $count++;
	                            }
	                        }
                            else
                            {
	                            $testeLinha ++;
                                $save = $this->model_billet->salvarArquivoNMTable($inputs,$arrayValores);

                                if ($save == false)
                                    echo "Erro ao subir na tabela";die;
	                        }
	                    }
	                }
	            }elseif($inputs['slc_mktplace'] == "999"){

					$testeLinha = 1;

	                foreach ($objPHPExcel->getWorksheetIterator() as $worksheet)
                    {
	                    $worksheetTitle     = $worksheet->getTitle();
	                    $highestRow         = $worksheet->getHighestRow(); // e.g. 10
	                    $highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
	                    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
	                    $nrColumns = ord($highestColumn) - 64;

	                    //lê a linha
	                    for ($row = 1; $row <= $highestRow; ++ $row)
                        {
	                        $valorColuna = 1;
	                        $arrayValores = array();

	                        //lê a coluna
	                        for ($col = 0; $col < $highestColumnIndex; ++ $col)
                            {
	                            $cell = $worksheet->getCellByColumnAndRow($col, $row);
	                            $val = $cell->getValue();
	                            $arrayValores[$valorColuna] = $val;
	                            $valorColuna++;
	                        }

	                        if ($testeLinha == "1"){

	                            $testeLinha ++;
	                            $cabecalho[1] = "Numero do Pedido (entrega)";
								$cabecalho[2] = "ref_pedido (b2w)";
								$cabecalho[3] = "Marketplace";
	                            $count = 1;

	                            foreach ($arrayValores as $colunas){

	                                if ( $colunas <> $cabecalho[$count] ){
	                                    echo "Erro ao ler o arquivo, formato de colunas inválido: Encontrado - ".$colunas." esperado ".$cabecalho[$count];die;
									}

	                                $count++;
	                            }

	                        }else{

	                            $testeLinha ++;
                                $save = $this->model_billet->salvarArquivoConciliacaoManual($inputs,$arrayValores);

                                if ($save == false){
                                    echo "Erro ao subir na tabela";die;
								}
	                        }
	                    }
	                }
				}

				if ($save == false){
					//$this->db->trans_commit();
					die;
				}

                if($inputs['slc_mktplace'] == "11"){
                    //Trata a conciliação
                    $save2 = $this->model_billet->consiliaarquivoML($inputs);
                    if ($save2){
                        //Atualiza os valores divididos nas tabelas
                        $save3 = $this->model_billet->atualizavaloresconciliadivisaoML($inputs);
                        if($save3){
                            echo "Feito com sucesso!";die;
                        }else{
                            echo "Erro ao conciliar arquivo carregado";die;
                        }
                    }else{
                        echo "Erro ao conciliar arquivo carregado";die;
                    }
                }elseif($inputs['slc_mktplace'] == "16"){
                    //Trata a conciliação
                    $save2 = $this->model_billet->consiliaarquivoCarrefourXls($inputs);
                    if ($save2){
                        //Atualiza os valores divididos nas tabelas
                        $save3 = $this->model_billet->atualizavaloresconciliadivisaoCarrefourXls($inputs);
                        if($save3){
                            echo "Feito com sucesso!";die;
                        }else{
                            echo "Erro ao conciliar arquivo carregado";die;
                        }
                    }else{
                        echo "Erro ao conciliar arquivo carregado";die;
                    }
                }elseif($inputs['slc_mktplace'] == "17"){ // MAdeira Madeira 
	                //Trata a conciliação
	                $save2 = $this->model_billet->consiliaarquivoMadeira($inputs);
	                if ($save2){
	                    //Atualiza os valores divididos nas tabelas 
	                    $save3 = $this->model_billet->atualizavaloresconciliadivisaoMadeira($inputs);
	                    if($save3){
	                       echo "Feito com sucesso!";die;
	                    }else{
	                        echo "Erro ao conciliar arquivo carregado";die;
	                    }
	                }else{
	                    echo "Erro ao conciliar arquivo carregado";die;
	                }
	            }elseif($inputs['slc_mktplace'] == "15"){
					//Trata a conciliação
					$save2 = $this->model_billet->consiliaarquivoViaVarejo($inputs);
					if ($save2){
						//Atualiza os valores divididos nas tabelas
						$save3 = $this->model_billet->atualizavaloresconciliadivisaoViaVArejo($inputs);
						if($save3){
							echo "Feito com sucesso!";die;
						}else{
							echo "Erro ao conciliar arquivo carregado";die;
						}
					}else{
						echo "Erro ao conciliar arquivo carregado";die;
					}
                }elseif($inputs['slc_mktplace'] == "30"){ // NovoMundo
	                //Trata a conciliação
	                $save2 = $this->model_billet->consiliaarquivoNM($inputs);
	                if ($save2){
	                    //Atualiza os valores divididos nas tabelas
	                    $save3 = $this->model_billet->atualizavaloresconciliadivisaoNM($inputs);
	                    if($save3){
	                       echo "Feito com sucesso!";die;
	                    }else{
	                        echo "Erro ao conciliar arquivo carregado";die;
	                    }
	                }else{
	                    echo "Erro ao conciliar arquivo carregado";die;
	                }
				}elseif($inputs['slc_mktplace'] == "999"){
	                //Trata a conciliação
	                $save2 = $this->model_billet->consiliaarquivoManual($inputs);
	                if ($save2){
	                    //Atualiza os valores divididos nas tabelas
	                    $save3 = $this->model_billet->atualizavaloresconciliadivisaoManual($inputs);
	                    if($save3){
	                       echo "Feito com sucesso!";die;
	                    }else{
	                        echo "Erro ao conciliar arquivo carregado";die;
	                    }
	                }else{
	                    echo "Erro ao conciliar arquivo carregado";die;
	                }
	            }
	        }
	        
	    }else{
	        echo "Arquivo não encontrado";
	    }

		//$this->db->trans_commit();
	}
	
	public function fetchOrdersListOrdersGrid($tipo = null, $hdnLote = null, $mktplace = null)
    {
	    if ($hdnLote == "" or $tipo == "" or $mktplace == "")
        {
	        // $result['data'][0] = array("","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","");
            $result['data'][0]  = array("","","","","","","","","","",
            "","","","","","","","","","",
            "","","","","","","","","","",
            "","","","","","","","","","",
            "","","","","","","","");

			if($this->fin_192_novos_calculos == "1"){
				array_push($result['data'][0], ...array("","","","","","","","","",""));
			}
		
				array_push($result['data'][0], ...array(""));
			

            if ($this->negociacao_marketplace_campanha == "1")
                array_push($result['data'][0], ...array("",""));

            if ($this->canceled_orders_data_conciliation == "1")
                array_push($result['data'][0], ...array("","",""));

            ob_clean();
    	    echo json_encode($result);
    	    die;
	    }
	    
	    if($tipo == "ok")
	        $input['tipo'] = "Ok";
	    else if ($tipo == "div")
	        $input['tipo'] = "Divergente";
	    else if ($tipo == "nfound")
	        $input['tipo'] = "Não encontrado";
	    else if($tipo == "outros")
	        $input['tipo'] = "Outros";
	    else
	        $input['tipo'] = "Estorno";
	    
	    $input['lote'] = $hdnLote;
	        
	    if ($mktplace == "10")
        {
    	    $data = $this->model_billet->getOrdersGridsB2W($input);
	    }
        else if ($mktplace == "15")
        {
	        $data = $this->model_billet->getOrdersGridsViaVarejo($input);
	    }
        else if ($mktplace == "11")
        {
	        $data = $this->model_billet->getOrdersGridsML($input);
	    }
        else if ($mktplace == "16")
        {
	        $data = $this->model_billet->getOrdersGridsCarrefour($input);

	        if(empty($data))
	            $data = $this->model_billet->getOrdersGridsCarrefourXls($input);
	    }
        else if ($mktplace == "17")
        {
	        $data = $this->model_billet->getOrdersGridsMadeira($input);
		}
        else if ($mktplace == "30")
        {
	        $data = $this->model_billet->getOrdersGridsNM($input);
		}
		else if ($mktplace == "999")
        {
	        $data = $this->model_billet->getOrdersGridsManual($input);
		}


        if ($data)
        {
            $this->load->model('model_campaigns_v2');

            foreach ($data as $key => $value)
            {
                // button
                $buttons    = '';
                $frete      = '';
                $frete_real = '';

                $campaigns_pricetags            = 0;
                $campaigns_campaigns            = 0;
                $campaigns_mktplace             = 0;
                $campaigns_seller               = 0;
                $campaigns_promotions           = 0;
                $campaigns_rebate               = 0;
                $campaigns_comission_reduction  = 0;
                $campaigns_refund               = 0;

                $campaigns_channel_redux        = 0;
                $campaigns_channel_rebate       = 0;
                $campaigns_channel_refund       = 0;

                $campaign_data = $this->model_campaigns_v2->getCampaignsTotalsByRefId($value['ref_pedido']);      

                if ($campaign_data)
                {
                    $campaigns_pricetags                    = $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'pricetags', true);
                    $campaigns_campaigns                    = $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'campaigns', true);
                    $campaigns_mktplace                     = $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'channel', true);
                    $campaigns_seller                       = $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'seller', true);
                    $campaigns_promotions                   = $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'promotions', true);
                    $campaigns_rebate                       = $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'rebate', true);
                    $campaigns_comission_reduction          = $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'comission_reduction', true);
                    $campaigns_comission_reduction_products  = $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'comission_reduction_products', true);

                    $valor_parceiro          = (is_numeric($value['valor_parceiro'])) ? $value['valor_parceiro'] : 0;
                    $valor_parceiro_ajustado = (is_numeric($value['valor_parceiro_ajustado'])) ? $value['valor_parceiro_ajustado'] : 0;

                    //braun refund
                    if ($this->setting_api_comission != "1")
                        $campaigns_refund = (abs($valor_parceiro_ajustado - $valor_parceiro) > 0) ? abs($valor_parceiro_ajustado - $valor_parceiro) : 0;
                    else
                        $campaigns_refund = (abs($valor_parceiro_ajustado - $valor_parceiro - $campaigns_comission_reduction_products) > 0) ? abs($valor_parceiro_ajustado - $valor_parceiro - $campaigns_comission_reduction_products) : 0;                    
                    
                    $campaigns_channel_redux        = $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'comission_reduction_marketplace', true);
                    $campaigns_channel_rebate       = $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'rebate_marketplace', true);
                    $campaigns_channel_refund       = 0;
                }

                if(in_array('updateBilletConcil', $this->permission)) {


                $buttons .= ' <button type="button" class="btn btn-default" onclick="incluirObservacao(\''.$value['ref_pedido'].'\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-plus-square"></i></button>';
                
                if ($tipo <> "ok")
                    $buttons .= ' <button type="button" class="btn btn-default" onclick="marcapedidook(\''.$value['ref_pedido'].'\')"><i class="fa fa-level-up"></i></button>';
                else
                    $buttons .= ' <button type="button" class="btn btn-default" onclick="mudacomissaoparceiro(\''.$value['ref_pedido'].'\')" data-toggle="modal" data-target="#comissaoModal"><i class="fa fa-pencil"></i></button>';
                
                if ($value['tratado'] == "0")
                    $buttons .= ' <button type="button" class="btn btn-default" onclick="marcapedidotratado(\''.$value['ref_pedido'].'\')"><i class="fa fa-check"></i></button>';

                }
                
                if ($value['alertaFrete'] == "Maior")
                    $frete = '<span class="label label-danger">'."R$ ".str_replace ( ".", ",", $value['valor_frete'] ).'</span>';
                else
                    $frete = "R$ ".str_replace ( ".", ",", $value['valor_frete'] );
                
                if ($value['alertaFreteReal'] == "Maior")
                    $frete_real = '<span class="label label-danger">'."R$ ".str_replace ( ".", ",", $value['valor_frete_real'] ).'</span>';
                else
                    $frete_real = "R$ ".str_replace ( ".", ",", $value['valor_frete_real'] );
                
                $observacao = "";
                
                if ($value['observacao'] <> "")
                    $observacao = ' <button type="button" class="btn btn-default" onclick="listarObservacao(\''.$value['ref_pedido'].'\')" data-toggle="modal" data-target="#listaObs"><i class="fa fa-eye"></i></button>';

                if (!empty($value))
                {
                    foreach ($value as $k => $v)
                    {
                        if ($v == '-0')
                            $value[$k] = '0';
                    }
                }
				
				$numero_pedido = $value['ref_pedido'];
				if($this->fin_192_novos_calculos == "1"){				
					
					$comissao_marketplace = ($value['valor_pedido'] * $value['valor_percentual_mktplace']) - $campaigns_channel_redux + ($campaigns_pricetags * $value['valor_percentual_mktplace']);
					$valor_repasse_marketplace = ($value['valor_pedido'] - $comissao_marketplace) + ($campaigns_channel_rebate + $campaigns_pricetags);
					$comissao_negociada_seller = ($value['valor_pedido'] + $campaigns_pricetags + $campaigns_mktplace) * $value['valor_percentual_parceiro'] - $campaigns_refund;
					$comissao_conectala = $comissao_negociada_seller - $comissao_marketplace;
					$frete_conectala = $value['tipo_frete'] == 0 ? $value['valor_frete'] : 0;
					$retencao_conecta = $comissao_conectala + $frete_conectala + $campaigns_channel_rebate;
					$recebimento_seller = $valor_repasse_marketplace - $retencao_conecta;
										
					
					$valor_recebido_marketplace = 0;
					// carrefour
					if ($mktplace == "16"){
						$valor_recebido_marketplace = $value['valor_extrato'];						
						if(strpos($value['tipo'], 'manual') !== false){
							$numero_pedido = substr($value['descricao'], -13);									
						}
					}
					// via varejo
					if ($mktplace == "15"){
						$valor_recebido_marketplace = $value['valor_liquido_repasse'];
					}
					// B2W
					if ($mktplace == "10"){
						$valor_recebido_marketplace = $value['valor'];
						$numero_pedido = $value['ref_pedido'];	
					}
					// Madeira
					if ($mktplace == "17"){
						$valor_recebido_marketplace = 0;
					}
					
					$check_valor = $valor_repasse_marketplace - $valor_recebido_marketplace;

					$status = "";
					if($check_valor > 0){
						$status = "Divergente";
					}else{
						$status = "Aprovado";
					}
					if($valor_recebido_marketplace < 0){
						$status = "Estorno";
					}
						
				}
                
                //braun
                //fin-302
                if ($this->canceled_orders_data_conciliation == "1")
                {
                    $canceled_orders = ['responsible' => '', 'reason' => '', 'penalty' => ''];

                    $canceled_data = $this->model_orders->getPedidosCanceladosByOrderId($campaign_data['order_id']);

                    if (!empty($canceled_data))
                    {
                        $user_data = $this->model_users->getUserData($canceled_data['user_id']);
                        $user_name = $user_data['firstname'].' '.$user_data['lastname'];

                        $canceled_orders['responsible'] = $user_name;
                        $canceled_orders['reason']      = $canceled_data['reason'];
                        $canceled_orders['penalty']     = $canceled_data['penalty_to'];
                    }
                }

								// checa se o pedido tem valor antecipado
								$valor_antecipado = 0;
					
									$valor_antecipado = $this->model_billet->getValueAnticipationTransfer($value['ref_pedido']);										
					
								
								if($valor_antecipado > 0){
									$valor_pago_marketplace = $valor_antecipado;
								}

                $result['data'][$key] = array
                (
                    $numero_pedido,
                    $value['seller_name'],
                    $value['data_pedido'],
					$value['pedido_enviado'],
					$value['tipo_frete'],
                    $value['status_conciliacao'],										
                    "R$ ".str_replace ( ".", ",", $value['valor_pedido'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_produto'] ),
                    $frete,
                    $value['valor_percentual_mktplace']."%",
                    $value['valor_percentual_parceiro']."%", 
                    "R$ ".str_replace ( ".", ",", $valor_pago_marketplace ),
                    "R$ ".str_replace ( ".", ",", $value['valor_produto_calculado'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_frete_calculado'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_receita_calculado'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_produto_conecta'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_frete_real'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_frete_real_contratado'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_frete_conecta'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_conectala'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_produto_parceiro'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_frete_parceiro'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_parceiro'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_parceiro_novo'] ),
					"R$ ".str_replace ( ".", ",", $value['valor_desconto_camp_promo'] ),					

                    "R$ ".str_replace ( ".", ",", $value['valor_produto_recebido'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_frete_recebido'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_da_transacao'] ), 
                    "R$ ".str_replace ( ".", ",", $value['dif_valor_recebido_produto'] ),
                    "R$ ".str_replace ( ".", ",", $value['dif_valor_recebido_frete'] ),
                    "R$ ".str_replace ( ".", ",", $value['dif_valor_recebido'] ),
                    
                    "R$ ".str_replace ( ".", ",", $value['valor_produto_conecta_ajustado'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_frete_conecta_ajustado'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_conectala_ajustado'] ),
                    "R$ ".str_replace ( ".", ",", $value['valor_parceiro_ajustado'] ),

                    //braun
                    "R$ ".str_replace ( ".", ",", $campaigns_pricetags),
                    "R$ ".str_replace ( ".", ",", $campaigns_campaigns),
                    "R$ ".str_replace ( ".", ",", $campaigns_mktplace),
                    "R$ ".str_replace ( ".", ",", $campaigns_seller),
                    "R$ ".str_replace ( ".", ",", $campaigns_promotions),
                    "R$ ".str_replace ( ".", ",", $campaigns_comission_reduction),
                    "R$ ".str_replace ( ".", ",", $campaigns_rebate),
                    "R$ ".str_replace ( ".", ",", $campaigns_refund),
					
                );

									array_splice( $result['data'][$key], 7, 0, "R$ ".str_replace ( ".", ",", $valor_antecipado ) );
				
				
                if($this->negociacao_marketplace_campanha == "1")
                {
                    array_push($result['data'][$key], ...array
                    (
                        "R$ ".str_replace ( ".", ",", $campaigns_channel_redux),
                        "R$ ".str_replace ( ".", ",", $campaigns_channel_rebate)
                        // "R$ ".str_replace ( ".", ",", $campaigns_channel_refund),
                    ));
                }

				if($this->fin_192_novos_calculos == "1"){
					array_push(
						$result['data'][$key], ...array(
							money($comissao_marketplace),
							money($valor_repasse_marketplace),
							money($comissao_negociada_seller),
							money($comissao_conectala),
							money($frete_conectala),
							money($retencao_conecta),
							money($recebimento_seller),
							money($valor_recebido_marketplace),
							money($check_valor),
							$status
						)
					);
				}

                array_push($result['data'][$key], ...array
                (
                    $observacao,
                    $value['chamado_mktplace'],
                    $value['chamado_agidesk'],
                    $value['usuario'],
                ));

                if ($this->canceled_orders_data_conciliation == "1")
                {
                    array_push($result['data'][$key], ...array
                    (
                        $canceled_orders['responsible'],
                        $canceled_orders['reason'],
                        $canceled_orders['penalty'],
                    ));
                }

                array_push($result['data'][$key], ...array
                (
                    $buttons                        
                ));
            } // /foreach
        }
        else
        {
            $result['data'][0]  = array("","","","","","","","","","",
            "","","","","","","","","","",
            "","","","","","","","","","",
            "","","","","","","","","","",
            "","","","","","","","");

			if($this->fin_192_novos_calculos == "1"){
				array_push($result['data'][0], ...array("","","","","","","","","",""));
			}

				array_push($result['data'][0], ...array(""));


            if ($this->negociacao_marketplace_campanha == "1")
                array_push($result['data'][0], ...array("",""));

            if ($this->canceled_orders_data_conciliation == "1")
                array_push($result['data'][0], ...array("","",""));
        }
        
        ob_clean();
	    echo json_encode($result);
	}
	
	public function exportaconciliacao($tipo = null, $hdnLote = null, $mktplace = null){
	    
		$data = [];

	    header("Pragma: public");
	    header("Cache-Control: no-store, no-cache, must-revalidate");
	    header("Cache-Control: pre-check=0, post-check=0, max-age=0");
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    header("Content-Transfer-Encoding: none");
	    header("Content-Type: application/vnd.ms-excel;");
	    header("Content-type: application/x-msexcel;");
	    header("Content-Disposition: attachment; filename=Conciliacao $hdnLote"."_"."$tipo.xls");
	    
	    if($tipo == "ok"){
	        $input['tipo'] = "Ok";
	    }elseif($tipo == "div"){
	        $input['tipo'] = "Divergente";
	    }elseif($tipo == "nfound"){
	        $input['tipo'] = "Não encontrado";
	    }elseif($tipo == "outros"){
	        $input['tipo'] = "Outros";
	    }else{
	        $input['tipo'] = "Estorno";
	    }
	    
	    $input['lote'] = $hdnLote;
	    
	    if($mktplace == "10"){
	        $data = $this->model_billet->getOrdersGridsB2W($input);
	    }elseif($mktplace == "15"){
	        $data = $this->model_billet->getOrdersGridsViaVarejo($input);
	    }elseif($mktplace == "11"){
	        $data = $this->model_billet->getOrdersGridsML($input);
	    }elseif($mktplace == "16"){
	        $data = $this->model_billet->getOrdersGridsCarrefour($input);
	        if(empty($data)){
	            $data = $this->model_billet->getOrdersGridsCarrefourXls($input);
	        }
	    }elseif($mktplace == "17"){
	        $data = $this->model_billet->getOrdersGridsMadeira($input);
	    }
		elseif($mktplace == "30")
        {
	        $data = $this->model_billet->getOrdersGridsNM($input);
	    }elseif($mktplace == "999")
        {
	        $data = $this->model_billet->getOrdersGridsManual($input);
	    }

		$valor_pedido_marketplace 	= 0;
		$valor_produtos_marketplace = 0;
		$valor_frete_marketplace 	= 0;
		$percentual_marketplace	 	= 0;
		$percentual_seller 			= 0;
		$descontos 					= 0;
		$reducao_comissao_mkt		= 0;
		$rebate_marketplace 		= 0;
		$marketplace_pagando 		= 0;
		$reembolso					= 0;
		$val_recebido_mkt_extrato 	= 0;
	    
	    if($data){

            $this->load->model('model_campaigns_v2');

	        foreach ($data as $key => $value) {
	            // button
	            $buttons    = '';
	            $frete      = '';
	            $frete_real = '';

				$campaigns_pricetags            = (0);
                $campaigns_campaigns            = (0);
                $campaigns_mktplace             = (0);
                $campaigns_seller               = (0);
                $campaigns_promotions           = (0);
                $campaigns_rebate               = (0);
                $campaigns_comission_reduction  = (0);
                $campaigns_refund               = (0);

                $campaigns_channel_redux        = 0;
                $campaigns_channel_rebate       = 0;
                $campaigns_channel_refund       = 0;

                $campaign_data = $this->model_campaigns_v2->getCampaignsTotalsByRefId($value['ref_pedido']);      

                if ($campaign_data)
                {
                    $campaigns_pricetags            = ($this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'pricetags', true));
                    $campaigns_campaigns            = ($this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'campaigns', true));
                    $campaigns_mktplace             = ($this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'channel', true));
                    $campaigns_seller               = ($this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'seller', true));
                    $campaigns_promotions           = ($this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'promotions', true));
                    $campaigns_rebate               = ($this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'rebate', true));
                    $campaigns_comission_reduction  = ($this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'comission_reduction', true));
                    // $campaigns_refund               = (($value['valor_parceiro_ajustado'] - $value['valor_parceiro']) > 0) ? ($value['valor_parceiro_ajustado'] - $value['valor_parceiro']) : 0;    
                    
                    $campaigns_comission_reduction_products  = $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'comission_reduction_products', true);

                    $valor_parceiro          = (is_numeric($value['valor_parceiro'])) ? $value['valor_parceiro'] : 0;
                    $valor_parceiro_ajustado = (is_numeric($value['valor_parceiro_ajustado'])) ? $value['valor_parceiro_ajustado'] : 0;

                    //braun refund
                    if ($this->setting_api_comission != "1"){
                        $campaigns_refund = (abs($valor_parceiro_ajustado - $valor_parceiro) > 0) ? money(abs($valor_parceiro_ajustado - $valor_parceiro)) : money(0);
						$reembolso  	  = (abs($valor_parceiro_ajustado - $valor_parceiro) > 0) ? abs($valor_parceiro_ajustado - $valor_parceiro) : 0;
					}else{
                        $campaigns_refund = (abs($valor_parceiro_ajustado - $valor_parceiro - $campaigns_comission_reduction_products) > 0) ? money(abs($valor_parceiro_ajustado - $valor_parceiro - $campaigns_comission_reduction_products)) : money(0);
						$reembolso  	  = (abs($valor_parceiro_ajustado - $valor_parceiro - $campaigns_comission_reduction_products) > 0) ? abs($valor_parceiro_ajustado - $valor_parceiro - $campaigns_comission_reduction_products) : 0;
					}
                    
                    $campaigns_channel_redux        = money($this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'comission_reduction_marketplace', true));
                    $campaigns_channel_rebate       = money($this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'rebate_marketplace', true));
                    $campaigns_channel_refund       = money(0);

					$descontos 						= $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'pricetags', true);
					$reducao_comissao_mkt			= $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'comission_reduction_marketplace', true);
					$rebate_marketplace 			= $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'rebate_marketplace', true);
					$marketplace_pagando			= $this->model_campaigns_v2->getConciliationTotals($campaign_data['order_id'], 'channel', true);

                }

	            $buttons .= ' <button type="button" class="btn btn-default" onclick="incluirObservacao(\''.$value['ref_pedido'].'\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-plus-square"></i></button>';
	            
	            if($tipo <> "ok") {
	                $buttons .= ' <button type="button" class="btn btn-default" onclick="marcapedidook(\''.$value['ref_pedido'].'\')"><i class="fa fa-level-up"></i></button>';
	            }else{
	                $buttons .= ' <button type="button" class="btn btn-default" onclick="mudacomissaoparceiro(\''.$value['ref_pedido'].'\')" data-toggle="modal" data-target="#comissaoModal"><i class="fa fa-pencil"></i></button>';
	            }
	            
	            if($value['tratado'] == "0"){
	                $buttons .= ' <button type="button" class="btn btn-default" onclick="marcapedidotratado(\''.$value['ref_pedido'].'\')"><i class="fa fa-check"></i></button>';
	            }
	            
	            
	            
	            if($value['alertaFrete'] == "Maior"){
	                $frete = '<span class="label label-danger">'."R$ ".str_replace ( ".", ",", $value['valor_frete'] ).'</span>';
	            }else{
	                $frete = "R$ ".str_replace ( ".", ",", $value['valor_frete'] );
	            }
	            
	            if($value['alertaFreteReal'] == "Maior"){
	                $frete_real = '<span class="label label-danger">'."R$ ".str_replace ( ".", ",", $value['valor_frete_real'] ).'</span>';
	            }else{
	                $frete_real = "R$ ".str_replace ( ".", ",", $value['valor_frete_real'] );
	            }
	            
	            $observacao = "";
	            
	            if ( $value['observacao'] <> "" ){
	                $retornoObservacao = $this->model_billet->buscaobservacaopedido($hdnLote, $value['ref_pedido'], 1);
	                foreach($retornoObservacao as $obs){
	                    if($observacao == ""){
	                        $observacao = "[".$obs['data_criacao']."] - ".$obs['observacao'];
	                    }else{
	                        $observacao .= "<br>[".$obs['data_criacao']."] - ".$obs['observacao'];
	                    }
	                }
	            }

				$numero_pedido = $value['ref_pedido'];
				if($this->fin_192_novos_calculos == "1"){				
					
					$comissao_marketplace = ($value['valor_pedido'] * $value['valor_percentual_mktplace']) - $campaigns_channel_redux + ($campaigns_pricetags * $value['valor_percentual_mktplace']);
					$valor_repasse_marketplace = ($value['valor_pedido'] - $comissao_marketplace) + ($campaigns_channel_rebate + $campaigns_pricetags);
					$comissao_negociada_seller = ($value['valor_pedido'] + $campaigns_pricetags + $campaigns_mktplace) * $value['valor_percentual_parceiro'] - $campaigns_refund;
					$comissao_conectala = $comissao_negociada_seller - $comissao_marketplace;
					$frete_conectala = $value['tipo_frete'] == 0 ? $value['valor_frete'] : 0;
					$retencao_conecta = $comissao_conectala + $frete_conectala + $campaigns_channel_rebate;
					$recebimento_seller = $valor_repasse_marketplace - $retencao_conecta;
										
					
					$valor_recebido_marketplace = 0;
					// carrefour
					if ($mktplace == "16"){
						$valor_recebido_marketplace = $value['valor_extrato'];						
						if(strpos($value['tipo'], 'manual') !== false){
							$numero_pedido = substr($value['descricao'], -13);									
						}
					}
					// via varejo
					if ($mktplace == "15"){
						$valor_recebido_marketplace = $value['valor_liquido_repasse'];
					}
					// B2W
					if ($mktplace == "10"){
						$valor_recebido_marketplace = $value['valor'];
						$numero_pedido = $value['ref_pedido'];	
					}
					// Madeira
					if ($mktplace == "17"){
						$valor_recebido_marketplace = 0;
					}
					
					$check_valor = $valor_repasse_marketplace - $valor_recebido_marketplace;

					$status = "";
					if($check_valor > 0){
						$status = "Divergente";
					}else{
						$status = "Aprovado";
					}
					if($valor_recebido_marketplace < 0){
						$status = "Estorno";
					}
						
				}

                //braun
                //fin-302
                if ($this->canceled_orders_data_conciliation == "1")
                {
                    $canceled_orders = ['responsible' => '', 'reason' => '', 'penalty' => ''];

                    $canceled_data = $this->model_orders->getPedidosCanceladosByOrderId($campaign_data['order_id']);

                    if (!empty($canceled_data))
                    {
                        $user_data = $this->model_users->getUserData($canceled_data['user_id']);
                        $user_name = $user_data['firstname'].' '.$user_data['lastname'];

                        $canceled_orders['responsible'] = $user_name;
                        $canceled_orders['reason']      = $canceled_data['reason'];
                        $canceled_orders['penalty']     = $canceled_data['penalty_to'];
                    }
                }

	            $result[$key] = array(
	                $numero_pedido,
	                $value['seller_name'],
	                $value['data_pedido'],
	                $value['status_conciliacao'],
	                "R$ ".str_replace ( ".", ",", $value['valor_pedido'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_produto'] ),
	                $frete,
	                $value['valor_percentual_mktplace']."%",
	                $value['valor_percentual_parceiro']."%",
	                "R$ ".str_replace ( ".", ",", $value['valor_marketplace'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_produto_calculado'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_frete_calculado'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_receita_calculado'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_produto_conecta'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_frete_real'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_frete_real_contratado'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_frete_conecta'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_conectala'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_produto_parceiro'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_frete_parceiro'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_parceiro'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_parceiro_novo'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_produto_recebido'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_frete_recebido'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_da_transacao'] ),
	                "R$ ".str_replace ( ".", ",", $value['dif_valor_recebido_produto'] ),
	                "R$ ".str_replace ( ".", ",", $value['dif_valor_recebido_frete'] ),
	                "R$ ".str_replace ( ".", ",", $value['dif_valor_recebido'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_produto_conecta_ajustado'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_frete_conecta_ajustado'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_conectala_ajustado'] ),
	                "R$ ".str_replace ( ".", ",", $value['valor_parceiro_ajustado'] ),
	                $observacao,
	                $value['chamado_mktplace'],
	                $value['chamado_agidesk'],
	                $value['usuario'],
					$value['pedido_enviado'],
					$value['tipo_frete'],
					"R$ ".str_replace ( ".", ",", $value['valor_desconto_camp_promo'] ), //38

                    //braun
                    str_replace ( ".", ",", money($campaigns_pricetags)),
                    str_replace ( ".", ",", money($campaigns_campaigns)),
                    str_replace ( ".", ",", money($campaigns_mktplace)),
                    str_replace ( ".", ",", money($campaigns_seller)),
                    str_replace ( ".", ",", money($campaigns_promotions)),
                    str_replace ( ".", ",", money($campaigns_comission_reduction)),
                    str_replace ( ".", ",", money($campaigns_rebate)),
                    str_replace ( ".", ",", money($campaigns_refund)),

					$value['data_gatilho']
	            );

				if($this->fin_192_novos_calculos == "1"){		
					array_push($result[$key], ...array(
							($comissao_marketplace),
							($valor_repasse_marketplace),
							($comissao_negociada_seller),
							($comissao_conectala),
							($frete_conectala),
							($retencao_conecta),
							($recebimento_seller),
							($valor_recebido_marketplace)							
						)
					);
				}

				array_push($result[$key], ...array(
						$descontos,
						$reducao_comissao_mkt,
						$rebate_marketplace,
						$marketplace_pagando,
						$reembolso,								
					)
				);

                if ($this->negociacao_marketplace_campanha == "1")
                {
                    array_push($result[$key], ...array
                    (
                        str_replace ( ".", ",", $campaigns_channel_redux), //47
                        str_replace ( ".", ",", $campaigns_channel_rebate),
                        // "R$ ".str_replace ( ".", ",", $campaigns_channel_refund),
                    ));
                }

                if ($this->canceled_orders_data_conciliation == "1")
                {
                    array_push($result[$key], ...array
                    (
                        $canceled_orders['responsible'],
                        $canceled_orders['reason'],
                        $canceled_orders['penalty'],
                    ));
                }
	        }
	    
        
        }else{
	        $result[0] =  array("","","","","","","","","","",
                                "","","","","","","","","","",
                                "","","","","","","","","","",
                                "","","","","","","","","","",
                                "","","","","","","","","","");
                                
			if($this->fin_192_novos_calculos == "1"){	
				array_push($result[0], ...array("","","","","","","","","",""));
			}
            if ($this->negociacao_marketplace_campanha == "1")
                array_push($result[0], ...array("",""));

            if ($this->canceled_orders_data_conciliation == "1")
                array_push($result[0], ...array("","",""));
	    }
        
        $colspan1 = 12;
        $colspan2 = 8;
        $colspan3 = 4;

        if ($this->negociacao_marketplace_campanha == "1" && $this->canceled_orders_data_conciliation != "1")
        {
            $colspan1 = 14;
            $colspan2 = 10;
            $colspan3 = 4;
        }
        else if ($this->negociacao_marketplace_campanha == "1" && $this->canceled_orders_data_conciliation == "1")
        {
            $colspan1 = 17;
            $colspan2 = 10;
            $colspan3 = 7;
        }
        else if ($this->negociacao_marketplace_campanha != "1" && $this->canceled_orders_data_conciliation == "1")
        {
            $colspan1 = 15;
            $colspan2 = 8;
            $colspan2 = 4;
        }               
		
		$news_columns_fin_192 = "";
		if($this->fin_192_novos_calculos == "1"){		
			$colspan1 = $colspan1 + 10; 
			$news_columns_fin_192 = '<th colspan="10" bgcolor="#708090" class="text-center"><font color="#FFFFFF">Outros Valores</font></th>';
		}

	    echo '<table id="manageTableOrdersOk" border="1" class="table table-bordered table-striped">';
	    echo utf8_decode('<thead>
                            <tr>
                            	<th colspan="26" bgcolor="black" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>
                            	<th colspan="10" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores Recebidos</font></th>
                            	<th colspan="'.$colspan1.'" bgcolor="#36D2D6" class="text-center"><font color="#FFFFFF">Informações</font></th>
                            </tr>  
                            <tr>
                            	<th colspan="12" bgcolor="black" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>
                            	<th colspan="4" bgcolor="#2626C9" class="text-center"><font color="#FFFFFF">Marketplace</font></th>
                            	<th colspan="5" bgcolor="#16F616" class="text-center"><font color="#FFFFFF">Conecta Lá</font></th>
                            	<th colspan="5" bgcolor="#E1AA98" class="text-center"><font color="#FFFFFF">Seller</font></th>
                            	<th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores Recebidos</font></th>
                            	<th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Expectativa x Recebido</font></th>
                            	<th colspan="4" bgcolor="red" class="text-center"><font color="#FFFFFF">Valores a pagar</font></th>
                                <th colspan="'.$colspan2.'" bgcolor="purple" class="text-center"><font color="#FFFFFF">Campanhas / Ofertas / Promoções</font></th>								
								'.$news_columns_fin_192.'
                            	<th colspan="'.$colspan3.'" bgcolor="#36D2D6" class="text-center"><font color="#FFFFFF">Observação/Chamado/Responsável/Ação</font></th>
                            </tr>
                            <tr>
                                <th>'.$this->lang->line('application_purchase_id').'</th>
                                <th>'.$this->lang->line('application_store').'</th>
                                <th>'.$this->lang->line('application_date').' Pedido</th>
								<th>'.$this->lang->line('application_date').' Gatilho</th>
								<th>Pedido enviado</th>
                        		<th>Tipo de Frete</th>
								<th>'.$this->lang->line('application_status_conciliacao').'</th>
                                <th>'.$this->lang->line('application_conciliacao_mktplace_value').'</th> 
                                <th>'.$this->lang->line('application_value_products').' - Marketplace</th>
                                <th>'.$this->lang->line('application_ship_value').' - Marketplace</th>
                                <th>'.$this->lang->line('application_rate').' - Marketplace</th>
                                <th>'.$this->lang->line('application_rate').' - Seller</th>
                                <th>'.$this->lang->line('application_value').' - Pago Marketplace</th>
                                <th>'.$this->lang->line('application_value_products').' - Marketplace</th>
                                <th>'.$this->lang->line('application_ship_value').' - Marketplace</th>
                                <th>Receita - Marketplace</th>
                                <th>Valor Comissão Produto - ConectaLá</th>
                                <th>Frete Real - ConectaLá</th>
                                <th>Valor Comissão Extra Frete - ConectaLá</th>
                                <th>Valor Comissão Frete - ConectaLá</th>
                                <th>Valor a receber - ConectaLá</th>
                                <th>Valor Desconto Produto - Seller</th>
                                <th>Valor Descontro Frete - Seller</th>
                                <th>Valor a receber - Seller</th>
                                <th>Valor a receber Ajustado - Seller</th>
								<th>Valor de desconto a ser acrescido</th>
                                <th>'.$this->lang->line('application_marketplace').' - Valor recebido produto</th>
                                <th>'.$this->lang->line('application_marketplace').' - Valor recebido frete</th>
                                <th>'.$this->lang->line('application_marketplace').' - Valor recebido Total</th>
                                <th>Dif. Valor Recebido de produto</th>
                                <th>Dif. Valor Recebido de frete</th>
                                <th>Dif. Valor Recebido Total</th>
                                <th>Valor Comissão Produto - ConectaLá</th>
                                <th>Valor Comissão Frete - ConectaLá</th>
                                <th>Valor a receber - ConectaLá</th>
                                <th>Valor a receber - Seller</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_pricetags').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_campaigns').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_totalmktplace').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_totalseller').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_promotions').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_comissionredux').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_rebate').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_refund').'</th>');

        if ($this->negociacao_marketplace_campanha == "1")
        {
            echo utf8_decode('
                <th>'.$this->lang->line('conciliation_sc_gridok_comissionreduxchannel').'</th>
                <th>'.$this->lang->line('conciliation_sc_gridok_rebatechannel').'</th>
                ');
        }
        // <th>'.$this->lang->line('conciliation_sc_gridok_pricetags').'</th>

		if($this->fin_192_novos_calculos == "1"){
			echo utf8_encode('
				<th>Comissão MarketPlace</th>
				<th>Valor Repasse MarketPlace</th>
				<th>Comissão Negociada Seller</th>
				<th>Comissão Conecta Lá</th>
				<th>Frete Conecta Lá</th>
				<th>Retenção conecta</th>
				<th>Recebimento Seller</th>
				<th>Valor Recebido MarketPlace | Extrato</th>
				<th>Check Valor</th>
				<th>Status</th>
			');
		}

        echo utf8_decode('
                                <th>'.$this->lang->line('application_extract_obs').'</th>
                                <th>Chamado Marketplace</th>
                                <th>Chamado Agidesk</th>
                                <th>Responsável Conciliação</th>
                        ');

        if ($this->canceled_orders_data_conciliation == "1")
        {
            echo utf8_decode('
                <th>'.$this->lang->line('conciliation_grid_cancel_responsible').'</th>
                <th>'.$this->lang->line('conciliation_grid_cancel_reason').'</th>
                <th>'.$this->lang->line('conciliation_grid_cancel_penalty').'</th>
                ');
        }

        echo utf8_decode('
                            </tr>
                           </thead>');

	    foreach($result as $value){

			if($this->fin_192_novos_calculos == "1"){
				$comissao_marketplace 		= $value[48] == "" ? 0 : $value[48];
				$valor_repasse_marketplace 	= $value[49] == "" ? 0 : $value[49];
				$comissao_negociada_seller 	= $value[50] == "" ? 0 : $value[50];
				$comissao_conectala 		= $value[51] == "" ? 0 : $value[51];
				$frete_conectala 			= $value[52] == "" ? 0 : $value[52];
				$retencao_conectala 		= $value[53] == "" ? 0 : $value[53];
				$recebimento_seller 		= $value[54] == "" ? 0 : $value[54];
				$val_recebido_mkt_extrato 	= $value[55] == "" ? 0 : $value[55];
				$descontos 					= $value[56];
				$reducao_comissao_mkt		= $value[57];
				$rebate_marketplace 		= $value[58];
				$marketplace_pagando 		= $value[59];
				$reembolso					= $value[60];
			}else{
				$descontos 					= $value[48];
				$reducao_comissao_mkt		= $value[49];
				$rebate_marketplace 		= $value[50];
				$marketplace_pagando 		= $value[51];
				$reembolso					= $value[52];			
			}	


	        echo "<tr>";
	        echo utf8_decode("<td>".$value[0]."</td>");
	        echo utf8_decode("<td>".$value[1]."</td>");
	        echo utf8_decode("<td>".$value[2]."</td>");
	        echo utf8_decode("<td>".$value[47]."</td>");
			echo utf8_decode("<td>".$value[36]."</td>");
			echo utf8_decode("<td>".$value[37]."</td>");
	        echo utf8_decode("<td>".$value[3]."</td>");
	        echo utf8_decode("<td>".$value[4]."</td>");
	        echo utf8_decode("<td>".$value[5]."</td>");
	        echo utf8_decode("<td>".$value[6]."</td>");
	        echo utf8_decode("<td>".$value[7]."</td>");
	        echo utf8_decode("<td>".$value[8]."</td>");
	        echo utf8_decode("<td>".$value[9]."</td>");
	        echo utf8_decode("<td>".$value[10]."</td>");
	        echo utf8_decode("<td>".$value[11]."</td>");
	        echo utf8_decode("<td>".$value[12]."</td>");
	        echo utf8_decode("<td>".$value[13]."</td>");
	        echo utf8_decode("<td>".$value[14]."</td>");
	        echo utf8_decode("<td>".$value[15]."</td>");
	        echo utf8_decode("<td>".$value[16]."</td>");
	        echo utf8_decode("<td>".$value[17]."</td>");
	        echo utf8_decode("<td>".$value[18]."</td>");
	        echo utf8_decode("<td>".$value[19]."</td>");
	        echo utf8_decode("<td>".$value[20]."</td>");
	        echo utf8_decode("<td>".$value[21]."</td>");
			echo utf8_decode("<td>".$value[38]."</td>");
	        echo utf8_decode("<td>".$value[22]."</td>");
	        echo utf8_decode("<td>".$value[23]."</td>");
	        echo utf8_decode("<td>".$value[24]."</td>");
	        echo utf8_decode("<td>".$value[25]."</td>");
	        echo utf8_decode("<td>".$value[26]."</td>");
	        echo utf8_decode("<td>".$value[27]."</td>");
	        echo utf8_decode("<td>".$value[28]."</td>");
	        echo utf8_decode("<td>".$value[29]."</td>");
	        echo utf8_decode("<td>".$value[30]."</td>");
	        echo utf8_decode("<td>".$value[31]."</td>");
	        
                        
            echo utf8_decode("<td>".$value[39]."</td>");
            echo utf8_decode("<td>".$value[40]."</td>");
            echo utf8_decode("<td>".$value[41]."</td>");
            echo utf8_decode("<td>".$value[42]."</td>");
            echo utf8_decode("<td>".$value[43]."</td>");
            echo utf8_decode("<td>".$value[44]."</td>");
            echo utf8_decode("<td>".$value[45]."</td>");
            echo utf8_decode("<td>".$value[46]."</td>");


            if ($this->negociacao_marketplace_campanha == "1")
            {
                echo utf8_decode("<td>".$value[47]."</td>");
                echo utf8_decode("<td>".$value[48]."</td>");
            }

			if($this->fin_192_novos_calculos == "1"){
				echo utf8_decode("<td>".money($comissao_marketplace)."</td>");
				echo utf8_decode("<td>".money($valor_repasse_marketplace)."</td>");
				echo utf8_decode("<td>".money($comissao_negociada_seller)."</td>");
				echo utf8_decode("<td>".money($comissao_conectala)."</td>");
				echo utf8_decode("<td>".money($frete_conectala)."</td>");
				echo utf8_decode("<td>".money($retencao_conectala)."</td>");
				echo utf8_decode("<td>".money($recebimento_seller)."</td>");
				echo utf8_decode("<td>".money($val_recebido_mkt_extrato)."</td>");
				echo utf8_decode("<td>".(isset($check_valor) && !empty($check_valor) ? money($check_valor) : money(0))."</td>");
				echo utf8_decode("<td>".(isset($status) ? $status : "-")."</td>");
			}

	        echo utf8_decode("<td>".$value[32]."</td>");
	        echo utf8_decode("<td>".$value[33]."</td>");
	        echo utf8_decode("<td>".$value[34]."</td>");
	        echo utf8_decode("<td>".$value[35]."</td>");

            if ($this->canceled_orders_data_conciliation == "1")
            {
                echo utf8_decode("<td>".$value[49]."</td>");
                echo utf8_decode("<td>".$value[50]."</td>");
                echo utf8_decode("<td>".$value[51]."</td>");
            }

	        echo "</tr>";
	        
	    }
	    
	    echo '</table>';
	    
	}
	
	
	public function contatotallinhasgridconciliacao($hdnLote = null, $mktplace = null){
	    
	    $arraySaida = array(); 
	    
	    if($hdnLote == "" or $mktplace == ""){
	        
	        $arraySaida['ok'] = "0";
	        $arraySaida['div'] = "0";
	        $arraySaida['nfound'] = "0";
	        $arraySaida['outros'] = "0";
	        $arraySaida['est'] = "0";
	        echo json_encode($arraySaida);
	        
	        die;
	    }
	    $input['lote'] = $hdnLote;
	    
	    
	    $data = array();
        $input['tipo'] = "Ok";
        
        if($mktplace == "10"){
            $data = $this->model_billet->getOrdersGridsB2W($input);
        }elseif($mktplace == "15"){
            $data = $this->model_billet->getOrdersGridsViaVarejo($input);
        }elseif($mktplace == "11"){
            $data = $this->model_billet->getOrdersGridsML($input);
        }elseif($mktplace == "16"){
            $data = $this->model_billet->getOrdersGridsCarrefour($input);
            if(empty($data)){
                $data = $this->model_billet->getOrdersGridsCarrefourXls($input);
            }
        }elseif($mktplace == "17"){
            $data = $this->model_billet->getOrdersGridsMadeira($input);
        }
        else if ($mktplace == "30")
        {
            $data = $this->model_billet->getOrdersGridsNM($input);
        }else if ($mktplace == "999")
        {
	        $data = $this->model_billet->getOrdersGridsManual($input);
		}

        $arraySaida['ok'] = (is_array($data)) ? count($data) : 0;
        
        
        $data = array();
        $input['tipo'] = "Divergente";
        
        if($mktplace == "10"){
            $data = $this->model_billet->getOrdersGridsB2W($input);
        }elseif($mktplace == "15"){
            $data = $this->model_billet->getOrdersGridsViaVarejo($input);
        }elseif($mktplace == "11"){
            $data = $this->model_billet->getOrdersGridsML($input);
        }elseif($mktplace == "16"){
            $data = $this->model_billet->getOrdersGridsCarrefour($input);
            if(empty($data)){
                $data = $this->model_billet->getOrdersGridsCarrefourXls($input);
            }
        }elseif($mktplace == "17"){
            $data = $this->model_billet->getOrdersGridsMadeira($input);
        }
        else if ($mktplace == "30")
        {
            $data = $this->model_billet->getOrdersGridsNM($input);
        }else if ($mktplace == "999")
        {
	        $data = $this->model_billet->getOrdersGridsManual($input);
		}

        $arraySaida['div'] = (is_array($data)) ? count($data) : 0;
        
        
        $data = array();
        $input['tipo'] = "Não encontrado";
        
        if($mktplace == "10"){
            $data = $this->model_billet->getOrdersGridsB2W($input);
        }elseif($mktplace == "15"){
            $data = $this->model_billet->getOrdersGridsViaVarejo($input);
        }elseif($mktplace == "11"){
            $data = $this->model_billet->getOrdersGridsML($input);
        }elseif($mktplace == "16"){
            $data = $this->model_billet->getOrdersGridsCarrefour($input);
            if(empty($data)){
                $data = $this->model_billet->getOrdersGridsCarrefourXls($input);
            }
        }elseif($mktplace == "17"){
            $data = $this->model_billet->getOrdersGridsMadeira($input);
        }
        else if ($mktplace == "30")
        {
            $data = $this->model_billet->getOrdersGridsNM($input);
        }else if ($mktplace == "999")
        {
	        $data = $this->model_billet->getOrdersGridsManual($input);
		}

        $arraySaida['nfound'] = (is_array($data)) ? count($data) : 0;
        
        
        $data = array();
        $input['tipo'] = "Outros";
        
        if($mktplace == "10"){
            $data = $this->model_billet->getOrdersGridsB2W($input);
        }elseif($mktplace == "15"){
            $data = $this->model_billet->getOrdersGridsViaVarejo($input);
        }elseif($mktplace == "11"){
            $data = $this->model_billet->getOrdersGridsML($input);
        }elseif($mktplace == "16"){
            $data = $this->model_billet->getOrdersGridsCarrefour($input);
            if(empty($data)){
                $data = $this->model_billet->getOrdersGridsCarrefourXls($input);
            }
        }elseif($mktplace == "17"){
            $data = $this->model_billet->getOrdersGridsMadeira($input);
        }
        else if ($mktplace == "30")
        {
            $data = $this->model_billet->getOrdersGridsNM($input);
        }else if ($mktplace == "999")
        {
	        $data = $this->model_billet->getOrdersGridsManual($input);
		}

        $arraySaida['outros'] = (is_array($data)) ? count($data) : 0;
        
        
        $data = array();
        $input['tipo'] = "Estorno";
        
        if($mktplace == "10"){
            $data = $this->model_billet->getOrdersGridsB2W($input);
        }elseif($mktplace == "15"){
            $data = $this->model_billet->getOrdersGridsViaVarejo($input);
        }elseif($mktplace == "11"){
            $data = $this->model_billet->getOrdersGridsML($input);
        }elseif($mktplace == "16"){
            $data = $this->model_billet->getOrdersGridsCarrefour($input);
            if(empty($data)){
                $data = $this->model_billet->getOrdersGridsCarrefourXls($input);
            }
        }elseif($mktplace == "17"){
            $data = $this->model_billet->getOrdersGridsMadeira($input);
        }
        else if ($mktplace == "30")
        {
            $data = $this->model_billet->getOrdersGridsNM($input);
        }else if ($mktplace == "999")
        {
	        $data = $this->model_billet->getOrdersGridsManual($input);
		}

        $arraySaida['est'] = (is_array($data)) ? count($data) : 0;
        
        echo json_encode($arraySaida);
        die;
	    
	}
	
	public function fetchOrdersListTotaisGridsConciliacao($hdnLote = null, $mktplace = null)
    {
	    error_reporting(1);
    	$arraySaida = array();
    	$totalGeral = 0;

        $array_index = 0;
        $list_index  = 1;
    	
    	if($hdnLote == "" or $mktplace == ""){
    	    
    	    
    	    $arraySaida['data'][0] = array(1,"Valor total dos pedidos Ok","0");
    	    $arraySaida['data'][1] = array(2,"Valor total dos pedidos Divergentes","0");
    	    $arraySaida['data'][2] = array(3,"Valor Estimado dos pedidos Não encontrados","0");
    	    $arraySaida['data'][3] = array(4,"Valor total dos pedidos Outros","0");
    	    $arraySaida['data'][4] = array(5,"Valor total dos pedidos Estorno","0");
    	    $arraySaida['data'][5] = array(6,"Valor total Geral","0");
    	    
    	    echo json_encode($arraySaida);
    	    
    	    die;
    	}
    	$input['lote'] = $hdnLote;
    	
    	$data = array();
    	$input['tipo'] = "Ok";
    	
    	if($mktplace == "10"){
    	    $data = $this->model_billet->getOrdersGridsB2W($input);
    	}elseif($mktplace == "15"){
    	    $data = $this->model_billet->getOrdersGridsViaVarejo($input);
    	}elseif($mktplace == "11"){
    	    $data = $this->model_billet->getOrdersGridsML($input);
    	}elseif($mktplace == "16"){
    	    $data = $this->model_billet->getOrdersGridsCarrefour($input);
    	    if(empty($data)){
    	        $data = $this->model_billet->getOrdersGridsCarrefourXls($input);
    	    }
    	}elseif($mktplace == "17"){
    	    $data = $this->model_billet->getOrdersGridsMadeira($input);
    	}
        else if ($mktplace == "30")
        {
    	    $data = $this->model_billet->getOrdersGridsNM($input);
    	}else if ($mktplace == "999")
        {
	        $data = $this->model_billet->getOrdersGridsManual($input);
		}

    	$total = 0;

    	foreach ($data as $result)
        {
    	    $total = $total + $result['valor_da_transacao'];
    	}

    	$totalGeral = $totalGeral + $total;
        $total = str_replace (",",".",$total);

    	$arraySaida['data'][$array_index++] = array($list_index++,"Valor total dos pedidos Ok","R$ ".number_format($total, 2, ",", "."));

    	$data = array();
    	$input['tipo'] = "Divergente";
    	
    	if($mktplace == "10"){
    	    $data = $this->model_billet->getOrdersGridsB2W($input);
    	}elseif($mktplace == "15"){
    	    $data = $this->model_billet->getOrdersGridsViaVarejo($input);
    	}elseif($mktplace == "11"){
    	    $data = $this->model_billet->getOrdersGridsML($input);
    	}elseif($mktplace == "16"){
    	    $data = $this->model_billet->getOrdersGridsCarrefour($input);
    	    if(empty($data)){
    	        $data = $this->model_billet->getOrdersGridsCarrefourXls($input);
    	    }
    	}elseif($mktplace == "17"){
    	    $data = $this->model_billet->getOrdersGridsMadeira($input);
    	}
        else if ($mktplace == "30")
        {
    	    $data = $this->model_billet->getOrdersGridsNM($input);
    	}else if ($mktplace == "999")
        {
	        $data = $this->model_billet->getOrdersGridsManual($input);
		}

    	$total = 0;

        foreach($data as $result)
        {
    	    $total = $total + $result['valor_da_transacao'];
    	}

    	$totalGeral = $totalGeral + $total;
        $total = str_replace (",",".",$total);

    	$arraySaida['data'][$array_index++] = array($list_index++,"Valor total dos pedidos Divergentes","R$ ".number_format($total, 2, ",", "."));

    	$data = array();
    	$input['tipo'] = "Não encontrado";
    	
    	if($mktplace == "10"){
    	    $data = $this->model_billet->getOrdersGridsB2W($input);
    	}elseif($mktplace == "15"){
    	    $data = $this->model_billet->getOrdersGridsViaVarejo($input);
    	}elseif($mktplace == "11"){
    	    $data = $this->model_billet->getOrdersGridsML($input);
    	}elseif($mktplace == "16"){
    	    $data = $this->model_billet->getOrdersGridsCarrefour($input);
    	    if(empty($data)){
    	        $data = $this->model_billet->getOrdersGridsCarrefourXls($input);
    	    }
    	}elseif($mktplace == "17"){
    	    $data = $this->model_billet->getOrdersGridsMadeira($input);
    	}
        else if ($mktplace == "30")
        {
    	    $data = $this->model_billet->getOrdersGridsNM($input);
    	}else if ($mktplace == "999")
        {
	        $data = $this->model_billet->getOrdersGridsManual($input);
		}

    	$total = 0;

        $percentual = $this->model_billet->getPercentual($mktplace);

    	foreach($data as $result)
        {
    	    $total = $total + round($result['valor_pedido_mktplace'] - ($result['valor_pedido_mktplace'] * ($percentual / 100)), 2);
    	}

    	$totalGeral = $totalGeral + $total;
        $total = str_replace (",",".",$total);

    	$arraySaida['data'][$array_index++] = array($list_index++,"Valor Estimado dos pedidos Não encontrados","R$ ".number_format($total, 2, ",", "."));
    	
    	$data = array();
    	$input['tipo'] = "Outros";
    	
    	if($mktplace == "10"){
    	    $data = $this->model_billet->getOrdersGridsB2W($input);
    	}elseif($mktplace == "15"){
    	    $data = $this->model_billet->getOrdersGridsViaVarejo($input);
    	}elseif($mktplace == "11"){
    	    $data = $this->model_billet->getOrdersGridsML($input);
    	}elseif($mktplace == "16"){
    	    $data = $this->model_billet->getOrdersGridsCarrefour($input);
    	    if(empty($data)){
    	        $data = $this->model_billet->getOrdersGridsCarrefourXls($input);
    	    }
    	}elseif($mktplace == "17"){
    	    $data = $this->model_billet->getOrdersGridsMadeira($input);
    	}
        else if ($mktplace == "30")
        {
    	    $data = $this->model_billet->getOrdersGridsNM($input);
    	}else if ($mktplace == "999")
        {
	        $data = $this->model_billet->getOrdersGridsManual($input);
		}

    	$total = 0;

    	foreach($data as $result)
        {
    	    $total = $total + $result['valor_da_transacao'];
    	}

    	$totalGeral = $totalGeral + $total;
        $total = str_replace (",",".",$total);

    	$arraySaida['data'][$array_index++] = array($list_index++,"Valor total dos pedidos Outros","R$ ".number_format($total, 2, ",", "."));
    	
    	$data = array();
    	$input['tipo'] = "Estorno";
    	
        $conciliation_table_orderid = 'ref_pedido';

    	if ($mktplace == "10")
        {
    	    $data = $this->model_billet->getOrdersGridsB2W($input,1);          
            $conciliation_table = 'conciliacao_b2w_tratado';
    	} 
        else if ($mktplace == "15")
        {
    	    $data = $this->model_billet->getOrdersGridsViaVarejo($input,1);
            $conciliation_table = 'conciliacao_viavarejo';
            $conciliation_table_orderid = 'numero_do_pedido';
    	}
        else if ($mktplace == "11")
        {
    	    $data = $this->model_billet->getOrdersGridsML($input,1);
            $conciliation_table = 'conciliacao_mercadolivre';
            $conciliation_table_orderid = 'external_reference';
    	}
        else if ($mktplace == "16")
        {
    	    $data = $this->model_billet->getOrdersGridsCarrefour($input,1);            
            $conciliation_table = 'conciliacao_carrefour';
            $conciliation_table_orderid = 'n_do_pedido';

    	    if (empty($data))
            {
                $data = $this->model_billet->getOrdersGridsCarrefourXls($input,1);
                $conciliation_table = 'conciliacao_carrefour_xls';
            }
    	}
        else if ($mktplace == "17")
        {
    	    $data = $this->model_billet->getOrdersGridsMadeira($input,1);
            $conciliation_table = 'conciliacao_madeira_tratado';
    	}
        else if ($mktplace == "30")
        {
    	    $data = $this->model_billet->getOrdersGridsNM($input, 1);
            $conciliation_table = 'conciliacao_nm';
            $conciliation_table_orderid = 'orderid';
    	}
		else if ($mktplace == "999")
        {
    	    $data = $this->model_billet->getOrdersGridsManual($input, 1);
            $conciliation_table = 'conciliacao_manual';
    	}

    	$total = 0;

    	foreach ($data as $result)
        {
    	    $total = $total + $result['valor_da_transacao'];
    	}
        
        //braun -> se somar o valor do estorno vai confundir o total do resumo
        $total = str_replace (",",".",$total);

    	$arraySaida['data'][$array_index++] = array($list_index++,"Valor total dos pedidos Estorno","R$ ".number_format($total, 2, ",", "."));

        //braun -> modulo campanhas
         $this->load->model('model_campaigns_v2');

        $order_ids = [];
        $data_orders = $this->model_billet->getConciliacaoOrdersIds($hdnLote, $conciliation_table, $conciliation_table_orderid);
        if (!empty($data_orders))
        {
            foreach($data_orders as $order)
            {
                $order_ids[] = $order['order_id'];
            }
        }

        $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "pricetags");
        $arraySaida['data'][$array_index] = array($list_index++, "Total Descontos");
        $arraySaida['data'][$array_index++][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : $this->session->userdata('currency')." 0,00";

        
        $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "campaigns");
        $arraySaida['data'][$array_index] = array($list_index++, "Total de Descontos em Campanhas/Ofertas");
        $arraySaida['data'][$array_index++][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : $this->session->userdata('currency')." 0,00";

        $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "channel");
        $arraySaida['data'][$array_index] = array($list_index++, "Total de Redução para o Market Place");
        $arraySaida['data'][$array_index++][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : $this->session->userdata('currency')." 0,00";

        $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "seller");
        $arraySaida['data'][$array_index] = array($list_index++, "Total de Redução para o Seller");
        $arraySaida['data'][$array_index++][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : $this->session->userdata('currency')." 0,00";

        $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "promotions");
        $arraySaida['data'][$array_index] = array($list_index++, "Total Descontado em Promoções");
        $arraySaida['data'][$array_index++][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : $this->session->userdata('currency')." 0,00";

        $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "comission_reduction");
        $arraySaida['data'][$array_index] = array($list_index++, "Redução na Comissão do Market Place");
        $arraySaida['data'][$array_index++][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : $this->session->userdata('currency')." 0,00";

        $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "rebate");
        $arraySaida['data'][$array_index] = array($list_index++, "Total em Rebate");
        $arraySaida['data'][$array_index++][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : $this->session->userdata('currency')." 0,00";

        // $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "refund");
        $data = $this->model_billet->getConciliacaoSellerCenter($lote,"refund");
        $arraySaida['data'][$array_index] = array($list_index++, "Total Reembolso");
        $arraySaida['data'][$array_index++][] = ($data) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data[0]['total_refund'])) : $this->session->userdata('currency')." 0,00"; 
 

        if ($this->negociacao_marketplace_campanha == "1")
        {
            $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "comission_reduction_marketplace");
            $arraySaida['data'][$array_index] = array($list_index++, "Redução Comissão Marketplace");
            $arraySaida['data'][$array_index++][] = ($data) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data[0]['total_refund'])) : $this->session->userdata('currency')." 0,00"; 
    
            $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "rebate_marketplace");
            $arraySaida['data'][$array_index] = array($list_index++, "Rebate MArketplace");
            $arraySaida['data'][$array_index++][] = ($data) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data[0]['total_refund'])) : $this->session->userdata('currency')." 0,00";
        }
    	
    	$totalGeral = str_replace (",",".",$totalGeral);
    	$arraySaida['data'][$array_index++] = array($list_index++,"Valor total Geral", $this->session->userdata('currency')." ".number_format($totalGeral, 2, ",", "."));
    	
    	echo json_encode($arraySaida);
    	
    }
	
	public function salvarobs(){
	    
	    $inputs = $this->postClean(NULL,TRUE);

	    if($inputs['txt_hdn_pedido'] <> "" && $inputs['txt_observacao'] <> "" && $inputs['hdnLote'] <> "" && $inputs['slc_obs_fixo'] <> "" ){
	        $data = $this->model_billet->salvaObservacao($inputs);
	        if($data){
    	        echo "0;Observação cadastrada com sucesso";
	        }else{
	            echo "1;Erro ao cadastrar observação.";
	        }
	    }
	    
	}
	
	public function cadastrarconciliacao(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    if( $inputs['slc_mktplace'] <> "" and $inputs['txt_ano_mes'] <> "" and $inputs['slc_ciclo'] <> "" and $inputs['txt_carregado'] == "1" )
        {	        
            $inputs['slc_ano_mes'] = $inputs['txt_ano_mes'];
            
	        //Verifica se já existe essa conciliação
	        $check = $this->model_billet->verificaconsiliacao($inputs);
	        
	       
	        if($check['qtd'] == 0) {
	            //cadastra uma nova
    	        $data = $this->model_billet->criaconsiliacao($inputs);
	        }else{
	            
                //atualiza os dados da conciliação	            
	            $dataEdit = $this->model_billet->editaconsiliacao($inputs);
	            if($dataEdit){
	                //atualiza os pedidos da concialiação já criada
	                $data2 = $this->model_billet->mudastatuspedidolote($inputs);
	                
	                if($data2){
	                    //atualiza o status da conciliação já criada
	                    $data = $this->model_billet->mudastatusconciliacao($inputs);
	                }else{
	                    echo "1;Erro ao atualizar conciliação $data2";
	                }
	            }else{
	                echo "1;Erro ao atualizar conciliação";
	            }
	        }
	        
	        if($data){
	            echo "0;Conciliação cadastrada com sucesso";
	        }else{
	            echo "1;Erro ao cadastrar conciliação $data";
	        }
	    }else{
	        echo "1;Erro ao cadastrar conciliação<br>Os campos não estavam todos preenchidos";
	    }
	    
	           
	    
	    
	}
	
	public function mudastatuspedidogrid(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    if( $inputs['lote'] <> "" and $inputs['pedido'] <> "" and $inputs['mktplace'] <> ""){
	        
	        $data = $this->model_billet->insereconciliacaotemppedido($inputs);
	        if($data){
	            echo "0;Conciliação cadastrada com sucesso";
	        }else{
	            echo "1;Erro ao cadastrar conciliação $data";
	        }
	        
	    }else{
	        echo "1;Erro ao atualiza pedido<br>Os campos não estavam todos preenchidos";
	    }
	    
	    
	}
	
	public function mudastatuspedidogridlote(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    if( $inputs['lote'] <> "" and $inputs['status'] <> "" and $inputs['mktplace'] <> ""){
	        
	        $data = $this->model_billet->insereconciliacaotemppedidolote($inputs);
	        if($data){
	            echo "0;Conciliação tratada com sucesso";
	        }else{
	            echo "1;Erro ao tratar conciliação $data";
	        }
	    }else{
	        echo "1;Erro ao atualiza pedido<br>Os campos não estavam todos preenchidos";
	    }

	}
	
	public function salvarcomissao(){
	
	$inputs = $this->postClean(NULL,TRUE);
	
	if($inputs['txt_comissao'] <> "" && $inputs['txt_hdn_pedido_comissao'] <> "" && $inputs['txt_comissao_produto_conectala'] <> "" && $inputs['txt_comissao_frete_conectala'] <> "" ){
	    $data = $this->model_billet->salvaComissao($inputs);
	    if($data){
	        echo "0;Comissão cadastrada com sucesso";
	    }else{
	        echo "1;Erro ao cadastrar Comissão.";
	    }
	}
	
	}
	
	public function tratalinhapedidoconciliacao(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    if( $inputs['lote'] <> "" and $inputs['pedido'] <> "" and $inputs['mktplace'] <> ""){
	        
	        $data = $this->model_billet->salvaPedidoTratadoConeciliacao($inputs);
	        if($data){
	            echo "0;Conciliação tratada com sucesso";
	        }else{
	            echo "1;Erro ao tratar conciliação $data";
	        }
	        
	    }else{
	        echo "1;Erro ao atualiza conciliação";
	    }
	    
	}
	
	public function tratalinhapedidoconciliacaolote(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    if( $inputs['lote'] <> "" and $inputs['mktplace'] <> ""){
	        
	        $data = $this->model_billet->salvaPedidoTratadoConeciliacaoLote($inputs);
	        if($data){
	            echo "0;Conciliação tratada com sucesso";
	        }else{
	            echo "1;Erro ao tratar conciliação $data";
	        }
	        
	    }else{
	        echo "1;Erro ao atualiza conciliação";
	    }
	    
	}
	
	public function buscaobservacaopedido(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    if($inputs['pedido'] <> "" && $inputs['lote'] <> "" ){
	        $data = $this->model_billet->buscaobservacaopedido($inputs['lote'], $inputs['pedido'], 1);
	    }else{
	        $data[0] = array("","","");
	    }
	    
	    echo json_encode($data);
	    
	}
	
	
	/************************************************************************/
	
	public function listtransp()
	{
	    if(!in_array('viewBillet', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = 'Conulta de Conciliações';
	    
	    $this->data['transportadoras'] = $this->model_billet->getTransportadorasFreights();
	    $this->data['ciclos'] = $this->model_parametrosmktplace->getReceivablesDataCiclotransp();
	    $this->data['obsFixo'] = $this->model_billet->getDataObservacaoFixaPedido();
        $this->data['negociacao_marketplace_campanha'] = ($this->negociacao_marketplace_campanha);
        $this->data['canceled_orders_data_conciliation'] = ($this->canceled_orders_data_conciliation);

	    $this->render_template('billet/listtransp', $this->data);
	}
	
	public function fetchConciliacaoGridDataTransp($transportadora = null, $ciclo = null)
	{
	    
	    $result = array('data' => array());
	    
	    $data = $this->model_billet->getConciliacaoGridDataTransp($transportadora, $ciclo);
	    
	    foreach ($data as $key => $value) {
	        
	        $status = '';
	        
	        $status = $this->model_iugu->statuspedido($value['paid_status']);
	        
	        $buttons = '';
	        
	        if(in_array('deleteParamktplace', $this->permission)) {
	            $buttons .= ' <button type="button" class="btn btn-default" onclick="incluirObservacao(\''.$value['numero_marketplace'].'\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-plus-square"></i></button>';
	        }
	        
	        $result['data'][$key] = array(
	            $value['id'],
	            $value['empresa'],
	            $value['loja'],
	            $value['ship_company'],
	            $value['numero_marketplace'],
	            $status,
	            $value['data_entrega'],
	            $value['frete_real'],
	            $value['frete_real_recalculado'],
	            $value['observacao'],
	            $buttons
	        );
	    }
	    
	    echo json_encode($result);
	}
	
	public function salvarobstranps(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    if($inputs['txt_hdn_pedido'] <> "" && $inputs['txt_observacao'] <> "" && $inputs['slc_obs_fixo'] <> "" ){
	        $data = $this->model_billet->salvaObservacaoTransp($inputs);
	        if($data){
	            echo "0;Observação cadastrada com sucesso";
	        }else{
	            echo "1;Erro ao cadastrar observação.";
	        }
	    }
	    
	}

	
	/******************** RESUMO CONCILIACAO TRANSP ************************/
	
	public function listtranspresumo()
	{
	    if(!in_array('viewBillet', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = 'Conulta de Conciliações';
	    
	    $this->data['transportadoras'] = $this->model_billet->getTransportadorasFreights();
	    $this->data['ciclos'] = $this->model_parametrosmktplace->getReceivablesDataCiclotransp();
	    $this->data['obsFixo'] = $this->model_billet->getDataObservacaoFixaPedido();
        $this->data['negociacao_marketplace_campanha'] = ($this->negociacao_marketplace_campanha);
        $this->data['canceled_orders_data_conciliation'] = ($this->canceled_orders_data_conciliation);

	    $this->render_template('billet/listtranspresumo', $this->data);
	}
	
	public function fetchConciliacaoGridDataTranspresumo($transportadora = null, $ciclo = null)
	{
	    
	    $result = array('data' => array());
	    
	    $data = $this->model_billet->getGridContaGridDataTransp($transportadora, $ciclo);
	    
	    foreach ($data as $key => $value) {
	        
	        $buttons = '';
	        $observacao = '';
	        
	        if ($value['idCiclo'] <> ""){
	            $buttons .= ' <button type="button" class="btn btn-default" onclick="incluirObservacao(\''.$value['idCiclo'].'|'.$value['CNPJ'].'\')" data-toggle="modal" data-target="#observacaoModal"><i class="fa fa-plus-square"></i></button>';
	            $buttons .= ' <button type="button" class="btn btn-default" onclick="incluirNovoValor(\''.$value['idCiclo'].'|'.$value['CNPJ'].'\')" data-toggle="modal" data-target="#valorModal"><i class="fa fa-truck   "></i></button>';
	        }
	        
	        if ($value['observacao'] <> ""){
	            $observacao = ' <button type="button" class="btn btn-default" onclick="listarObservacao(\''.$value['idCiclo'].'|'.$value['CNPJ'].'\')" data-toggle="modal" data-target="#listaObs"><i class="fa fa-eye"></i></button>';
	        }
	        
	        $result['data'][$key] = array(
	            $value['CNPJ'],
	            $value['ship_company'],
	            "R$ ".str_replace ( ".", ",", $value['ship_value'] ),
	            "R$ ".str_replace ( ".", ",", $value['valor_pago_real'] ),
	            "R$ ".str_replace ( ".", ",", $value['diferenca_frete'] ),
	            $value['tipo_ciclo'],
	            $value['dia_semana'],
	            $value['data_inicio'],
	            $value['data_fim'],
	            $value['data_pagamento'],
	            $value['data_pagamento_conecta'],
	            $value['statusPedido'],
	            $observacao,
	            $buttons
	        );
	    }
	    echo '<Pre>';print_r($result);die;
	    echo json_encode($result);
	    
	}
	
	public function salvarobstranpsresumo(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    if($inputs['txt_hdn_pedido'] <> "" && $inputs['txt_observacao'] <> "" && $inputs['slc_obs_fixo'] <> "" ){
	        $data = $this->model_billet->salvaObservacaoTranspresumo($inputs);
	        if($data){
	            echo "0;Observação cadastrada com sucesso";
	        }else{
	            echo "1;Erro ao cadastrar observação.";
	        }
	    }
	    
	}
	
	public function salvarfretetranpsresumo(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    if($inputs['txt_hdn_pedido_valor'] <> "" && $inputs['txt_novo_frete'] <> ""  ){
	        $data = $this->model_billet->salvaFreteTranspresumo($inputs);
	        if($data){
	            echo "0;Novo valor frete cadastrado com sucesso";
	        }else{
	            echo "1;Erro ao cadastrar novo valor de frete.";
	        }
	    }
	    
	}
	
	public function buscaobservacaotranspresumo(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    if($inputs['chave'] <> "" ){
	        $data = $this->model_billet->buscaobservacaotranspresumo($inputs['chave'], 1);
	    }else{
	        $data[0] = array("","","");
	    }
	    
	    echo json_encode($data);
	    
	}
	
	public function fetchConciliacaoGridDataTranspresumoTotais($transportadora = null, $ciclo = null)
	{
	
	$result = array('data' => array());
	
	$data = $this->model_billet->getConciliacaoGridDataTranspresumo($transportadora, $ciclo);
	
	$arrayChave = array();
	$dataTratado = array();
	$posicao = 0;
	
	//array total
	$arrayTotal['ship_value']                 = "0";
	$arrayTotal['valor_pago_real']            = "0";
	$arrayTotal['diferenca_frete']            = "0";
	$arrayTotal['tipo_ciclo']                 = "";
	$arrayTotal['dia_semana']                 = "";
	$arrayTotal['data_inicio']                = "";
	$arrayTotal['data_fim']                   = "";
	$arrayTotal['data_pagamento']             = "";
	$arrayTotal['data_pagamento_conecta']     = "";
	$arrayTotal['statusPedido']               = "Total";

	foreach ($data as $key1 => $value1) {
	    
	    if (array_key_exists($value1['tipo_ciclo'].$value1['dia_semana'].$value1['data_inicio'].$value1['data_fim'].$value1['data_pagamento'].$value1['data_pagamento_conecta'].$value1['statusPedido'], $arrayChave)) {
	        
	        $chaveArray = $arrayChave[$value1['tipo_ciclo'].$value1['dia_semana'].$value1['data_inicio'].$value1['data_fim'].$value1['data_pagamento'].$value1['data_pagamento_conecta'].$value1['statusPedido']];
	        
	        $dataTratado[$chaveArray]['ship_value']              = $dataTratado[$chaveArray]['ship_value']  + $value1['ship_value'];
	        $dataTratado[$chaveArray]['valor_pago_real']         = $dataTratado[$chaveArray]['valor_pago_real']  + $value1['valor_pago_real'];
	        $dataTratado[$chaveArray]['diferenca_frete']         = $dataTratado[$chaveArray]['diferenca_frete']  + $value1['diferenca_frete'];
	        
	        $arrayTotal['ship_value']                            = $arrayTotal['ship_value'] + $value1['ship_value'];
	        $arrayTotal['valor_pago_real']                       = $arrayTotal['valor_pago_real'] + $value1['valor_pago_real'];
	        $arrayTotal['diferenca_frete']                       = $arrayTotal['diferenca_frete'] + $value1['diferenca_frete'];
	        
	        
	    }else{
	        
	        $arrayChave[$value1['tipo_ciclo'].$value1['dia_semana'].$value1['data_inicio'].$value1['data_fim'].$value1['data_pagamento'].$value1['data_pagamento_conecta'].$value1['statusPedido']] = $posicao;
	        
	        $dataTratado[$posicao]['ship_value']                 = $value1['ship_value'];
	        $dataTratado[$posicao]['valor_pago_real']            = $value1['valor_pago_real'];
	        $dataTratado[$posicao]['diferenca_frete']            = $value1['diferenca_frete'];
	        $dataTratado[$posicao]['tipo_ciclo']                 = $value1['tipo_ciclo'];
	        $dataTratado[$posicao]['dia_semana']                 = $value1['dia_semana'];
	        $dataTratado[$posicao]['data_inicio']                = $value1['data_inicio'];
	        $dataTratado[$posicao]['data_fim']                   = $value1['data_fim'];
	        $dataTratado[$posicao]['data_pagamento']             = $value1['data_pagamento'];
	        $dataTratado[$posicao]['data_pagamento_conecta']     = $value1['data_pagamento_conecta'];
	        $dataTratado[$posicao]['statusPedido']               = $value1['statusPedido'];
	        
	        $arrayTotal['ship_value']                            = $arrayTotal['ship_value'] + $value1['ship_value'];
	        $arrayTotal['valor_pago_real']                       = $arrayTotal['valor_pago_real'] + $value1['valor_pago_real'];
	        $arrayTotal['diferenca_frete']                       = $arrayTotal['diferenca_frete'] + $value1['diferenca_frete'];
	        
	        
	        $posicao++;
	        
	    }
	    
	}
	
	foreach ($dataTratado as $key => $value) {
	    
	    $result['data'][$key] = array(
	        $value['statusPedido'],
	        "R$ ".str_replace ( ".", ",", $value['ship_value'] ),
	        "R$ ".str_replace ( ".", ",", $value['valor_pago_real'] ),
	        "R$ ".str_replace ( ".", ",", $value['diferenca_frete'] ),
	        $value['tipo_ciclo'],
	        $value['dia_semana'],
	        $value['data_inicio'],
	        $value['data_fim'],
	        $value['data_pagamento'],
	        $value['data_pagamento_conecta']
	    );
	}
	
	
	$result['data'][$posicao] = array(
	    $arrayTotal['statusPedido'],
	    "R$ ".str_replace ( ".", ",", $arrayTotal['ship_value'] ),
	    "R$ ".str_replace ( ".", ",", $arrayTotal['valor_pago_real'] ),
	    "R$ ".str_replace ( ".", ",", $arrayTotal['diferenca_frete'] ),
	    $arrayTotal['tipo_ciclo'],
	    $arrayTotal['dia_semana'],
	    $arrayTotal['data_inicio'],
	    $arrayTotal['data_fim'],
	    $arrayTotal['data_pagamento'],
	    $arrayTotal['data_pagamento_conecta']
	);
	
	
	echo json_encode($result);
	}

	/******************** ENCONTRO DE CONTAS TRANPS ************************/
	
	public function listcontastransp()
	{
	    if(!in_array('viewBillet', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = 'Encontro de contas Transportadora';
	    
	    $this->data['transportadoras'] = $this->model_billet->getTransportadorasFreights();
	    $this->data['ciclos'] = $this->model_parametrosmktplace->getReceivablesDataCiclotransp();
	    $this->data['obsFixo'] = $this->model_billet->getDataObservacaoFixaPedido();
        $this->data['negociacao_marketplace_campanha'] = ($this->negociacao_marketplace_campanha);
        $this->data['canceled_orders_data_conciliation'] = ($this->canceled_orders_data_conciliation);

	    $this->render_template('billet/listcontastransp', $this->data);
	}
	
	public function fetchContaGridDataTransp($transportadora = null, $ciclo = null)
	{
	    
	    $result = array('data' => array());
	    
	    $data = $this->model_billet->getGridContaGridDataTranspPedido($transportadora, $ciclo);
	    
	    $data2 = $this->model_billet->getGridContaGridDataTransp($transportadora, $ciclo);
	    
	    $arraySaida        = array();
	    $arrayDia          = array();
	    $arrayDesconto     = array();
	    $arrayAcumulado    = array();
	    $mktplaceAux       = "";
	    $indice            = 0;
	    
	    $arrayDia['descloja'] = "<b>Ganhos do Dia</b>";
	    $arrayDesconto['descloja'] = "<b>Descontos do Dia</b>";
	    $arrayAcumulado['descloja'] = "<b>Acumulado do Dia</b>";
	    
	    for($i=1;$i<=31;$i++){
	        $arrayDia[$i] = 0;
	        $arrayDesconto[$i] = 0;
	        $arrayAcumulado[$i] = 0;
	    }
	    
	    foreach ($data as $key => $value) {
	        
	        if ( $mktplaceAux <>  $value['descloja']){
	            
	            $indice++;
	            $mktplaceAux = $value['descloja'];
	            
	            $arraySaida[$indice]['descloja'] = $value['descloja'];
	            
	            for($i=1;$i<=31;$i++){
	                $arraySaida[$indice][$i] = "0";
	            }
	            
	            $arraySaida[$indice][$value['data_pagamento']] = $value['valor'];
	            $arrayDia[$value['data_pagamento']] = $arrayDia[$value['data_pagamento']] + $value['valor'];
	            
	        }else{
	            $arraySaida[$indice][$value['data_pagamento']] = $value['valor'];
	            $arrayDia[$value['data_pagamento']] = $arrayDia[$value['data_pagamento']] + $value['valor'];
	            
	        }
	        
	    }
	    
	    $mktplaceAux       = "";
	    foreach ($data2 as $key2 => $value2) {
	        
	        if ( $mktplaceAux <>  $value2['descloja']){
	            
	            $indice++;
	            $mktplaceAux = $value2['descloja'];
	            
	            $arraySaida[$indice]['descloja'] = $value2['descloja'];
	            
	            for($i=1;$i<=31;$i++){
	                $arraySaida[$indice][$i] = "0";
	            }
	            
	            $arraySaida[$indice][$value2['data_pagamento']] = $value2['valor'];
	            $arrayDesconto[$value2['data_pagamento']] = $arrayDesconto[$value2['data_pagamento']] + $value2['valor'];
	            
	        }else{
	            $arraySaida[$indice][$value2['data_pagamento']] = $value2['valor'];
	            $arrayDesconto[$value2['data_pagamento']] = $arrayDesconto[$value2['data_pagamento']] + $value2['valor'];
	            
	        }
	        
	    }
	    
	    $valorAcumulado = 0;
	    for($i=1;$i<=31;$i++){
	        $valorAcumulado = $valorAcumulado + ($arrayDia[$i] + $arrayDesconto[$i]);
	        $arrayAcumulado[$i] = $valorAcumulado;
	        
	    }
	    
	    for($i=1;$i<=31;$i++){
	        $arrayDia[$i]          = $this->formataCorGrid($arrayDia[$i]);
	        $arrayDesconto[$i]     = $this->formataCorGrid($arrayDesconto[$i]);
	        $arrayAcumulado[$i]    = $this->formataCorGrid($arrayAcumulado[$i]);
	    }
	   
	    for($j=1;$j<=$indice;$j++){
	        
	        $result['data'][$j-1] = array(
	            $arraySaida[$j]['descloja'],
	            $this->formataCorGrid($arraySaida[$j][1]),
	            $this->formataCorGrid($arraySaida[$j][2]),
	            $this->formataCorGrid($arraySaida[$j][3]),
	            $this->formataCorGrid($arraySaida[$j][4]),
	            $this->formataCorGrid($arraySaida[$j][5]),
	            $this->formataCorGrid($arraySaida[$j][6]),
	            $this->formataCorGrid($arraySaida[$j][7]),
	            $this->formataCorGrid($arraySaida[$j][8]),
	            $this->formataCorGrid($arraySaida[$j][9]),
	            $this->formataCorGrid($arraySaida[$j][10]),
	            $this->formataCorGrid($arraySaida[$j][11]),
	            $this->formataCorGrid($arraySaida[$j][12]),
	            $this->formataCorGrid($arraySaida[$j][13]),
	            $this->formataCorGrid($arraySaida[$j][14]),
	            $this->formataCorGrid($arraySaida[$j][15]),
	            $this->formataCorGrid($arraySaida[$j][16]),
	            $this->formataCorGrid($arraySaida[$j][17]),
	            $this->formataCorGrid($arraySaida[$j][18]),
	            $this->formataCorGrid($arraySaida[$j][19]),
	            $this->formataCorGrid($arraySaida[$j][20]),
	            $this->formataCorGrid($arraySaida[$j][21]),
	            $this->formataCorGrid($arraySaida[$j][22]),
	            $this->formataCorGrid($arraySaida[$j][23]),
	            $this->formataCorGrid($arraySaida[$j][24]),
	            $this->formataCorGrid($arraySaida[$j][25]),
	            $this->formataCorGrid($arraySaida[$j][26]),
	            $this->formataCorGrid($arraySaida[$j][27]),
	            $this->formataCorGrid($arraySaida[$j][28]),
	            $this->formataCorGrid($arraySaida[$j][29]),
	            $this->formataCorGrid($arraySaida[$j][30]),
	            $this->formataCorGrid($arraySaida[$j][31])
	        );
	        
	    }
	    
	    $result['data'][$indice] = array(
	        $arrayDia['descloja'],
	        $arrayDia[1],
	        $arrayDia[2],
	        $arrayDia[3],
	        $arrayDia[4],
	        $arrayDia[5],
	        $arrayDia[6],
	        $arrayDia[7],
	        $arrayDia[8],
	        $arrayDia[9],
	        $arrayDia[10],
	        $arrayDia[11],
	        $arrayDia[12],
	        $arrayDia[13],
	        $arrayDia[14],
	        $arrayDia[15],
	        $arrayDia[16],
	        $arrayDia[17],
	        $arrayDia[18],
	        $arrayDia[19],
	        $arrayDia[20],
	        $arrayDia[21],
	        $arrayDia[22],
	        $arrayDia[23],
	        $arrayDia[24],
	        $arrayDia[25],
	        $arrayDia[26],
	        $arrayDia[27],
	        $arrayDia[28],
	        $arrayDia[29],
	        $arrayDia[30],
	        $arrayDia[31]);
	    
	    $result['data'][$indice+1] = array(
	        $arrayDesconto['descloja'],
	        $arrayDesconto[1],
	        $arrayDesconto[2],
	        $arrayDesconto[3],
	        $arrayDesconto[4],
	        $arrayDesconto[5],
	        $arrayDesconto[6],
	        $arrayDesconto[7],
	        $arrayDesconto[8],
	        $arrayDesconto[9],
	        $arrayDesconto[10],
	        $arrayDesconto[11],
	        $arrayDesconto[12],
	        $arrayDesconto[13],
	        $arrayDesconto[14],
	        $arrayDesconto[15],
	        $arrayDesconto[16],
	        $arrayDesconto[17],
	        $arrayDesconto[18],
	        $arrayDesconto[19],
	        $arrayDesconto[20],
	        $arrayDesconto[21],
	        $arrayDesconto[22],
	        $arrayDesconto[23],
	        $arrayDesconto[24],
	        $arrayDesconto[25],
	        $arrayDesconto[26],
	        $arrayDesconto[27],
	        $arrayDesconto[28],
	        $arrayDesconto[29],
	        $arrayDesconto[30],
	        $arrayDesconto[31]);
	        
	        
	    $result['data'][$indice+2] = array(
	        $arrayAcumulado['descloja'],
	        $arrayAcumulado[1],
	        $arrayAcumulado[2],
	        $arrayAcumulado[3],
	        $arrayAcumulado[4],
	        $arrayAcumulado[5],
	        $arrayAcumulado[6],
	        $arrayAcumulado[7],
	        $arrayAcumulado[8],
	        $arrayAcumulado[9],
	        $arrayAcumulado[10],
	        $arrayAcumulado[11],
	        $arrayAcumulado[12],
	        $arrayAcumulado[13],
	        $arrayAcumulado[14],
	        $arrayAcumulado[15],
	        $arrayAcumulado[16],
	        $arrayAcumulado[17],
	        $arrayAcumulado[18],
	        $arrayAcumulado[19],
	        $arrayAcumulado[20],
	        $arrayAcumulado[21],
	        $arrayAcumulado[22],
	        $arrayAcumulado[23],
	        $arrayAcumulado[24],
	        $arrayAcumulado[25],
	        $arrayAcumulado[26],
	        $arrayAcumulado[27],
	        $arrayAcumulado[28],
	        $arrayAcumulado[29],
	        $arrayAcumulado[30],
	        $arrayAcumulado[31]);
	    
	    echo json_encode($result);
	}
	
	function formataCorGrid($numero){
	    
	    if( $numero > 0){
	        return '<span class="label label-success">'." R$ " .number_format($numero, 2, ",", ".").'</span>';
	    }elseif($numero < 0){
	        return '<span class="label label-danger">'." R$ " .number_format($numero, 2, ",", ".").'</span>';
	    }else{
	        return " R$ " .number_format($numero, 2, ",", ".");
	    }
	    
	}

	/***************************************************************************/

	public function listdiscountworksheet(){
	    
	    if(!in_array('viewDiscountWorksheet', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $this->data['page_title'] = $this->lang->line('application_discount_worksheet');
	    $this->render_template('billet/listdiscountworksheet', $this->data);
	}

	public function fetchdiscountworksheetgroup(){
	    
	    if(!in_array('viewDiscountWorksheet', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $buttons    = '';
	    
	    $data = $this->model_billet->getdiscountworksheetgroup();
	    
	    $result = array('data' => array());
	    
	    if($data){
    	    foreach ($data as $key => $value) {
    	        
    	        $buttons    = '';
    	        
    	        // $buttons.= '<a href="'.base_url('payment/editnfs/'.$value['id']).'" class="btn btn-default" data-toggle="tooltip" title="'.$this->lang->line('application_edit').'"><i class="fa fa-pencil-square-o"></i></a>';
    	        if(!in_array('deleteDiscountWorksheet', $this->permission)) {
					$buttons .= ' <button type="button" class="btn btn-default" onclick="apagadiscountworksheetgroup(\''.$value['id'].'\')"><i class="fa fa-trash"></i></button>';
				}
				
    	        $result['data'][$key] = array(
    	            $value['id'],
    	            $value['data_ciclo'],
    	            $value['data_criacao_tratado'],
					$value['ativo_tratado'],
    	            $buttons
    	        );
    	        
    	    }
	    }else{
	        $result['data'][0] = array("","","","","");
	    }
	    
	    echo json_encode($result);
	}

	public function apagadiscountworksheetgroup(){

		$inputs = $this->postClean(NULL,TRUE);

        if ($inputs['id'] <> "") {
            $desconto = $this->model_billet->desativadiscountworksheetgroup($inputs['id']);
            if ($desconto) {
                echo true;
            }else{
				echo false;
			}
        } else {
            echo false;
        }


	}
	
	public function creatediscountworksheet(){
	    
	    if(!in_array('createDiscountWorksheet', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }

	    $dataStores = $this->model_stores->getStoresData();
	    
	    $result['store_id'] = "";
	    $result['data_ciclo'] = "";
	    
	    $this->data['page_title'] = "Cadastrar - ".$this->lang->line('application_discount_worksheet');
	    $this->data['hdnLote'] = date('YmdHis').rand(1,1000000);
	    $this->data['hdnId'] = 0;
	    $this->data['stores'] = $dataStores;
	    $this->data['dados'] = $result;
	    $this->render_template('billet/creatediscountworksheet', $this->data);
	    
	}

	public function salvardiscountworksheet(){

		if(!in_array('createDiscountWorksheet', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $inputs = $this->postClean(NULL,TRUE);
		
		//Atualiza discountworksheet groups
		$cadastroDWGroup = $this->model_billet->insertediscountworksheetgroup($inputs);
		if($cadastroDWGroup){
			//Insere os dados na tabela final
			$save = $this->model_billet->inseredadostabeladiscountworksheet($inputs['hdnLote'],null);
			if($save){
				// $this->model_billet->saveCampignV2OrderUsingCargaDesconto($inputs['hdnLote']);

				$this->model_billet->limpatabeladiscountworksheettemp($inputs['hdnLote'],"temp");
				echo "0;Planilha de desconto salva com sucesso";
			}else{
				echo "1;Erro ao salvar Planilha de desconto $save";
			}
		}else{
			echo "1;Erro ao salvar Nota fiscal $cadastroDWGroup";
		}
	    
	}

	public function uploadArquivoDiscountworksheet()
    {
	    if (!empty($_FILES))
        {
	        $lote = $this->postClean('lote');
	        $exp_extens = explode( ".", $_FILES['product_upload']['name']) ;
	        $extensao = $exp_extens[count($exp_extens)-1];
	        
	        $tempFile = $_FILES['product_upload']['tmp_name'];
	        
            $root = getcwd();
            $caminhoMapeado = $root.DIRECTORY_SEPARATOR.'discountworksheet';

            if(!is_dir($caminhoMapeado."/")){
                mkdir($caminhoMapeado, 0755, true);
            }

            $caminhoMapeado.= DIRECTORY_SEPARATOR.$lote."/";
            if(!is_dir($caminhoMapeado."/")){
                mkdir($caminhoMapeado, 0755);
            }

	        $targetPath = $caminhoMapeado;
	        $targetFile =  str_replace('//','/',$targetPath) . $lote . '.' . $extensao;
	        
	        move_uploaded_file($tempFile, $targetFile);
	        
	        $retorno['ret'] = "sucesso";
	        $retorno['extensao'] = $extensao;
	        
	        // $arquivo = $lote . '.' . $extensao;
	        
	        echo json_encode($retorno);
	    }
	    else
        {
            return false;
        }
	}

	public function learquivodiscountworksheet(){

        $inputs = $this->postClean(NULL,TRUE);

        $caminhoMapeado = getFolderPath('discountworksheet'.DIRECTORY_SEPARATOR.$inputs['hdnLote'], true);

	    $arquivo = $inputs['hdnLote'] . '.' . $inputs['hdnExtensao'];
	    $inputs['arquivo'] = $arquivo;

	    if( file_exists($caminhoMapeado.$arquivo) ){

            $colunasEsperadas = [];
            $colunasEsperadas[] = "departamento";
            $colunasEsperadas[] = "status_pagamento";
            $colunasEsperadas[] = "linha";
            $colunasEsperadas[] = "cod_pedido";
            $colunasEsperadas[] = "cod_entrega";
            $colunasEsperadas[] = "order_line_id";
            $colunasEsperadas[] = "cod_sku";
            $colunasEsperadas[] = "desc_sku";
            $colunasEsperadas[] = "parceiro";
            $colunasEsperadas[] = "cnpj";
            $colunasEsperadas[] = "marca";
            $colunasEsperadas[] = "midia";
            $colunasEsperadas[] = "dt_pedido";
            $colunasEsperadas[] = "cashback_expirado_b2w";
            $colunasEsperadas[] = "cashback_expirado_seller";
            $colunasEsperadas[] = "gmv";
            $colunasEsperadas[] = "vl_frete_cliente";
            $colunasEsperadas[] = "vl_frete_mais_item";
            $colunasEsperadas[] = "qtd_itens";
            $colunasEsperadas[] = "comissao_seller";
            $colunasEsperadas[] = "desconto_condicional_b2w";
            $colunasEsperadas[] = "desconto_condicional_seller";
            $colunasEsperadas[] = "cashback_b2w";
            $colunasEsperadas[] = "cashback_Seller";
            $colunasEsperadas[] = "finance_b2w";
            $colunasEsperadas[] = "finance_Seller";
            $colunasEsperadas[] = "finance_cetelem";
            $colunasEsperadas[] = "desconto_incondicional_b2w";
            $colunasEsperadas[] = "desconto_incondicional_seller";
            $colunasEsperadas[] = "frete_b2w";
            $colunasEsperadas[] = "frete_seller";
            $colunasEsperadas[] = "cupom_b2w";
            $colunasEsperadas[] = "cupom_seller";

            $rows = readTempXls($caminhoMapeado.$arquivo, $colunasEsperadas);

            foreach ($rows as $row){

                $save = $this->model_billet->salvarDiscountWorksheetLTable($inputs,$row);
                if ($save == false){
                    exit("Erro ao subir na tabela ".$save);
                }

            }

			exit("Feito com sucesso!");

	    }else{
	        exit("Arquivo não encontrado");
	    }

	}

	/******************************************************/
	
	public function listsellercenter()
	{
	    if(!in_array('createPaymentRelease', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }

		$valorNM = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
		if($valorNM['status'] == "1"){
			$this->data['page_title'] = $this->lang->line('application_conciliacao_consulta_novomundo');
		}else{
			$this->data['page_title'] = $this->lang->line('application_conciliacao_consulta');
		}

	    if(in_array('createBillet', $this->permission)) {
	        $this->data['categs'] = "";
	        $this->data['mktPlaces'] = "";
	    }

	    $this->render_template('billet/listsellercenter', $this->data);
	}

	public function createconciliasellercenterfile(){
	    
	    
	    $group_data1 = $this->model_billet->getMktPlacesData();
	    $group_data2 = $this->model_parametrosmktplace->getReceivablesDataCiclo();
	    $obsFixo = $this->model_billet->getDataObservacaoFixaPedido();
	    
	    $group_data3 = array();
	    $group_data3['id_mkt'] = "";
	    $group_data3['id_ciclo'] = "";
	    $group_data3['ano_mes'] = "";
	    $group_data3['carregado'] = "0";
	    
	    $this->data['mktplaces'] = $group_data1;
	    $this->data['ciclo'] = $group_data2;
	    $this->data['hdnLote'] = date('YmdHis').rand(1,1000000);
	    $this->data['dadosBanco'] = $group_data3;
	    $this->data['obsFixo'] = $obsFixo;
        $this->data['negociacao_marketplace_campanha'] = ($this->negociacao_marketplace_campanha);
        $this->data['canceled_orders_data_conciliation'] = ($this->canceled_orders_data_conciliation);

		$this->data['flag_pago'] = false;

		$this->data['page_title'] = 'Conciliação';
	    
	    $this->render_template('billet/createsellercenterdemo', $this->data);
	}

	public function createconciliasellercenter(){
	    
	    
	    $group_data1 = $this->model_billet->getMktPlacesData();
	    $group_data2 = $this->model_parametrosmktplace->getReceivablesDataCiclo();
	    $obsFixo = $this->model_billet->getDataObservacaoFixaPedido();
	    
	    $group_data3 = array();
	    $group_data3['id_mkt'] = "";
	    $group_data3['id_ciclo'] = "";
	    $group_data3['ano_mes'] = "";
	    $group_data3['carregado'] = "0";

	    $this->data['mktplaces'] = $group_data1;
	    $this->data['hdnLote'] = date('YmdHis').rand(1,1000000);
	    $this->data['dadosBanco'] = $group_data3;
	    $this->data['obsFixo'] = $obsFixo;
        $this->data['negociacao_marketplace_campanha'] = ($this->negociacao_marketplace_campanha);
        $this->data['canceled_orders_data_conciliation'] = ($this->canceled_orders_data_conciliation);				

		$this->data['flag_pago'] = false;

		$this->data['page_title'] = 'Conciliação';
	    
	    $this->render_template('billet/createsellercenter', $this->data);
	}

    public function buscaCiclosPorMarketplace()
    {
        $marketplaces = $this->input->post('marketplaces');
        if (!empty($marketplaces)) {
            $ciclos = $this->model_parametrosmktplace->getReceivablesDataCiclo(null, null, $marketplaces);
            echo json_encode($ciclos);
        } else {
            echo json_encode([]);
        }
    }


    public function uploadarquivoconciliasellercenter(){
	    
	    if (!empty($_FILES)) 
        {
	        $group_data1 = $this->model_billet->getMktPlacesDataID($this->postClean('id'));
	        
	        $apelido = $group_data1[0]['apelido'];
	        
	        $exp_extens = explode( ".", $_FILES['product_upload']['name']) ;
	        $extensao = $exp_extens[count($exp_extens)-1];
	        
	        $lote=$this->postClean('lote');
	        $tempFile = $_FILES['product_upload']['tmp_name'];
	        
	        if ($_SERVER['SERVER_NAME'] == "localhost")
			{
				$root = getcwd();
				$caminhoMapeado = str_replace('\\', '/', $root.'/assets/docs/conciliacao/');
				
				if (!is_dir($caminhoMapeado))
					mkdir($caminhoMapeado, 0777, true);
			}
			else
			{
				//$caminhoMapeado = "/var/www/html/sicoob/app/assets/docs/";

				$root = getcwd();
				$caminhoMapeado = str_replace('\\', '/', $root.'/assets/docs/conciliacao/');
				
				if (!is_dir($caminhoMapeado))
					mkdir($caminhoMapeado, 0777, true);
			}
	        
	        $targetPath = $caminhoMapeado;
	        $targetFile =  str_replace('//','/',$targetPath) . $apelido .'-'. $lote . '.' . $extensao;
	        
	        move_uploaded_file($tempFile,$targetFile);
	        
	        $retorno['ret'] = "sucesso";
	        $retorno['extensao'] = $extensao;
	        
	        echo json_encode($retorno);
	    }
	    
	    
	}

	public function learquivoconciliacaosellercenter(){
	    $inputs = $this->postClean(NULL,TRUE);
		
	    $group_data1 = $this->model_billet->getMktPlacesDataID($inputs['slc_mktplace']);
	    $apelido = $group_data1[0]['apelido'];
	    
	    if ($_SERVER['SERVER_NAME'] == "localhost")
		{
			$root = getcwd();
			$caminhoMapeado = str_replace('\\', '/', $root.'/assets/docs/conciliacao/');
			
			if (!is_dir($caminhoMapeado))
				mkdir($caminhoMapeado, 0777, true);
		}
		else
		{
			//$caminhoMapeado = "/var/www/html/sicoob/app/assets/docs/";

			$root = getcwd();
			$caminhoMapeado = str_replace('\\', '/', $root.'/assets/docs/conciliacao/');
			
			if (!is_dir($caminhoMapeado))
				mkdir($caminhoMapeado, 0777, true);
		}

		$tipoArquivoVV = "Cartão";
	    
	    $arquivo = $apelido .'-'. $inputs['hdnLote'] . '.' . $inputs['hdnExtensao'];
	    $inputs['arquivo'] = $arquivo;

		$checkArquivo = false;

	    if( file_exists($caminhoMapeado.$arquivo) ){

			//Check se o arquivo de input é o ajuste de conciliação
			if($inputs['hdnExtensao'] == "xlsx"){
				
				$checkArquivo = true;

				error_reporting(1);
	            //import lib excel
	            require_once (APPPATH . '/third_party/PHPExcel/IOFactory.php');
	            $objPHPExcel = PHPExcel_IOFactory::load($caminhoMapeado.$arquivo);
	                
				$testeLinha = 1;
				
				foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
					
					$worksheetTitle     = $worksheet->getTitle();
					$highestRow         = $worksheet->getHighestRow(); // e.g. 10
					$highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
					$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
					$nrColumns = ord($highestColumn) - 64;
					
					//lê a linha
					for ($row = 1; $row <= $highestRow; ++ $row) {
						
						$valorColuna = 1;
						$arrayValores = array();
						
						//lê a coluna
						for ($col = 0; $col < $highestColumnIndex; ++ $col) {
							$cell = $worksheet->getCellByColumnAndRow($col, $row);
							$val = $cell->getValue();
							$arrayValores[$valorColuna] = $val;
							$valorColuna++;
						}
						
						
						if($testeLinha == "1"){
							
							$testeLinha ++;
							
							$cabecalho[1] = "Numero Pedido";
							$cabecalho[2] = "Regra Conciliação";
							$cabecalho[3] = "Valor Ajustado";
							$cabecalho[4] = "Justificativa";
							
							$count = 1;
							foreach ($arrayValores as $colunas){
								if( $colunas <> $cabecalho[$count]){
									$checkArquivo = false;
								}
								$count++;
							}
							
						}else{
							
							$testeLinha ++;

							if($checkArquivo){

								$save = $this->model_billet->atualizaValorConciliacaoSellerCenterArquivoMovelo($inputs,$arrayValores);
								if ($save == false){
									echo "Erro ao atualizar a tabela";die;
								}

							}
						}
					}
				}
			}

	    }else{
	        echo "Arquivo não encontrado";
	    }
	}

	public function editsellercenter($lote){
	    
	    
	    $group_data1 = $this->model_billet->getMktPlacesData();
	    $group_data2 = $this->model_parametrosmktplace->getReceivablesDataCiclo();
	    $obsFixo = $this->model_billet->getDataObservacaoFixaPedido();
		$group_data3 = $data = $this->model_billet->getConciliacaoGridData($lote);
		$group_data3[0]['carregado'] = 1;
        $integPrincipal = $group_data3[0]['id_mkt'];
        $integAdicionais = json_decode($group_data3[0]['integ_ids_adicionais'], true) ?? [];

        $cicloPrincipal = $group_data3[0]['id_ciclo'];
        $cicloAdicionais = json_decode($group_data3[0]['param_mkt_ciclo_ids_adicionais'], true) ?? [];

        $this->data['mktplaces_selected'] = array_merge([$integPrincipal], $integAdicionais);
        $this->data['ciclos_selected'] = array_merge([$cicloPrincipal], $cicloAdicionais);

	    $this->data['mktplaces'] = $group_data1;
	    $this->data['ciclo'] = $group_data2;
	    $this->data['hdnLote'] = $lote;
	    $this->data['dadosBanco'] = $group_data3[0];
	    $this->data['obsFixo'] = $obsFixo;
        $this->data['negociacao_marketplace_campanha'] = ($this->negociacao_marketplace_campanha);
        $this->data['canceled_orders_data_conciliation'] = ($this->canceled_orders_data_conciliation);

		$flagPago = false;
		$flagPago = $this->model_billet->getConciliacaoPagaFlag($lote);

		$this->data['flag_pago'] = $flagPago;

		$this->data['page_title'] = 'Conciliação';
	    
	    $this->render_template('billet/createsellercenter', $this->data);
	}

	public function geraconciliacaosellercenter()
    {
        set_time_limit(0);

		$inputs = $this->postClean(NULL,TRUE);

        if (!empty($inputs['slc_mktplace']) && !empty($inputs['txt_ano_mes']) && !empty($inputs['slc_ciclo']) && is_array($inputs['slc_ciclo'])) {

            $valorSellercenter = $this->model_settings->getSettingDatabyName('sellercenter');

            $datas_ciclo = [];

            list($mes, $ano) = explode("-", $inputs['txt_ano_mes']);

            foreach ($inputs['slc_ciclo'] as $ciclo_id) {
                // Busca os dados do ciclo individualmente
                $group_data = $this->model_parametrosmktplace->getReceivablesDataCiclo($ciclo_id);

                if (!$group_data || empty($group_data['data_pagamento'])) {
                    continue;
                }

                // Monta o dia com 0 à esquerda se necessário
                $dia = str_pad((int)$group_data['data_pagamento'], 2, "0", STR_PAD_LEFT);

                // Constrói a data inicialmente
                $data_br = "$dia/$mes/$ano";

                // Ajuste de dia inválido, apenas para fastshop
                if ($valorSellercenter['value'] === "fastshop") {
                    list($d, $m, $y) = explode("/", $data_br);
                    $d = (int)$d;
                    $m = (int)$m;
                    $y = (int)$y;

                    for ($i = 0; $i <= 3; $i++) {
                        if (checkdate($m, $d - $i, $y)) {
                            $dia_ajustado = str_pad($d - $i, 2, "0", STR_PAD_LEFT);
                            $data_br = "$dia_ajustado/" . str_pad($m, 2, "0", STR_PAD_LEFT) . "/$y";
                            break;
                        }
                    }
                }

                $datas_ciclo[] = $data_br;
            }

            $inputs['data_ciclo'] = $datas_ciclo;
            $apelidos = $this->model_orders_conciliation_installments->getApelidosMarketplace($inputs['slc_mktplace']);
            $apelidosFiltrados = array_column($apelidos, 'apelido');

			$this->db->trans_begin();
            //Removendo os registros de parcelamento inválido ao iniciar
            $this->model_orders_conciliation_installments->clearInvalidBatchesInstallments();

            if (!in_array($valorSellercenter['value'], ['fastshop', 'epoca', 'sicredi', 'privalia','somaplace'])){
				list($geraConciliacao, $conciliation_array) = $this->model_billet->generateInstallmentsByInputs(
					$inputs,
					$this->setting_api_comission
				); 
			}

            $datasFormatadas = array_map(function($data) {
                return dateBrazilToDateInternational($data);
            }, $inputs['data_ciclo']);

            $currentConciliationInstallments = $this->model_orders_conciliation_installments->findAllToBeReconciled($datasFormatadas, $apelidosFiltrados);
            if ($currentConciliationInstallments){

                foreach ($currentConciliationInstallments as $conciliationInstallment){
                    //Salvando o lote da conciliação a ser gerada agora na parcela em questão
                    $conciliationInstallment['lote'] = $inputs['hdnLote'];

                    $paid_status = $this->model_orders->getOrdersData(null, $conciliationInstallment['order_id'])['paid_status'];
					
                    if ($paid_status == '96' && $this->orders_precancelled_to_zero && $conciliationInstallment['status_conciliacao'] == 'Conciliação Cancelamento')
                    {
                        $conciliationInstallment['valor_comissao_produto'] = 0;
                        $conciliationInstallment['valor_comissao_frete'] = 0;
                        $conciliationInstallment['valor_comissao'] = 0;
                        $conciliationInstallment['valor_repasse'] = 0;
                        $conciliationInstallment['valor_repasse_ajustado'] = 0;
                    }

                    $this->model_orders_conciliation_installments->update($conciliationInstallment['id'], $conciliationInstallment);

                    //Salvando a conciliação
                    $conciliation_array = $this->model_orders_conciliation_installments->convertConciliationInstallmentToConciliationSellerCenter($conciliationInstallment);

                    //recuperando o formato de data que era utilizado anteriormente.
                    $conciliation_array['data_ciclo'] = $conciliationInstallment['data_ciclo'];

                    $geraConciliacao[] = $this->model_billet->createConciliacaoSellerCenter($inputs, $_SESSION['username'], $conciliation_array);
                }
            }

            $conciliacaoPainelLegal = $this->model_billet->createConciliacaoSellerCenterPainelLegal($inputs, $_SESSION['username']);

            $this->db->trans_commit();

			if (!empty($geraConciliacao) || $conciliacaoPainelLegal) {
                echo $this->lang->line('application_payment_release_payment_created');
            } else {
                echo $this->lang->line('application_payment_release_payment_created_error');
                //echo "1;Erro ao gerar a conciliação!";
            }
		}else{
			echo $this->lang->line('application_payment_release_payment_created_error');
		}
	}
	
	public function getconciliacaosellercentergrid($lote = null){

		$inputs = $this->input;
		$post = $inputs->post();
		$inputsGet = $inputs->get();

		if($lote <> null){

			// registros
            $data = $this->generateArrayDataConciliation($lote, $inputsGet, $post['length'], $post['start']);

			//Contador de registros
			$countData = $this->model_billet->getConciliacaoSellerCenter($lote,'pedidos',null,null,null,$inputsGet);
			$count = $countData[0]["qtd"];

			if($data) {

				foreach ($data as $key => $value) {

					$buttons = '';
					$observacao = '';
					
					$buttons .= ' <button type="button" class="btn btn-default" onclick="editcomissao(\''.$value['id'].'\')" data-toggle="modal" data-target="#comissaoModal"><i class="fa fa-pencil"></i></button>';
					$buttons .= ' <button type="button" class="btn btn-default" onclick="editobservacao(\''.$value['id'].'\')" data-toggle="modal" data-target="#observacaoModal"><i class="fa fa-sticky-note-o"></i></button>';

					if ( $value['observacao'] <> "" ){
						$observacao = ' <button type="button" class="btn btn-default" onclick="listarObservacao(\''.$value['id'].'\')" data-toggle="modal" data-target="#listaObs"><i class="fa fa-eye"></i></button>';
					}

					if ($value['tratado'] == 1) {
						
						$buttons .= ' <button type="button" class="btn btn-default" onclick="incluiremovepedidoconciliacao(\''.$value['id'].'\')"><i class="fa fa-minus"></i></button>';
						$status = $value['6'];

                    } else {

                        $buttons .= ' <button type="button" class="btn btn-default" onclick="incluiremovepedidoconciliacao(\''.$value['id'].'\')"><i class="fa fa-plus"></i></button>';
                        $status = $this->lang->line("application_payment_release_removed");

                    }

					$result['data'][$key][] = $value['1']; // numero pedido vtex
					$result['data'][$key][] = $value['26']; // numero pedido vtex
					$result['data'][$key][] = $value['6'];
                    $result['data'][$key][] = $value['2'];
                    $result['data'][$key][] = '';
                    $result['data'][$key][] = $value['4'];
                    $result['data'][$key][] = $value['18']; //tipo pagamento
                    $result['data'][$key][] = $value['7']; //valor_pedido
                    $result['data'][$key][] = $value['23']; //valor_antecipado
                    $result['data'][$key][] = $value['8']; //valor_produto
                    $result['data'][$key][] = $value['9']; //valor frete
                    $result['data'][$key][] = $value['10']; //percentual comissao
                    $result['data'][$key][] = $value['15']; //valor repasse
                    if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                        $result['data'][$key][] = $value['25']; //parcelas
                    }
                    $result['data'][$key][] = $value['user'];
                    $result['data'][$key][] = $value['campaigns_pricetags'];
                    $result['data'][$key][] = $value['campaigns_campaigns'];
                    $result['data'][$key][] = $value['campaigns_mktplace'];
                    $result['data'][$key][] = $value['campaigns_seller'];
                    $result['data'][$key][] = $value['campaigns_promotions'];
                    $result['data'][$key][] = $value['campaigns_comission_reduction'];
                    $result['data'][$key][] = $value['campaigns_rebate'];
                    $result['data'][$key][] = $value['campaigns_refund'];
                    $result['data'][$key][] = $observacao;
                    $result['data'][$key][] = $buttons;

				} // /foreach

			} else {
				$result['data'][0] = array("","","","","","","","","","","","","","","","","","","","","","","");
				
                array_push($result['data'][0], ...array(""));
				
			}
		} else {

			$result['data'][0] = array("","","","","","","","","","","","","","","","","","","","","","","");
			
            array_push($result['data'][0], ...array(""));
			
		}

		$result = [
			'draw' => $post['draw'] ?? null,
			'recordsTotal' => $count,
			'recordsFiltered' => $count,
			'data' => $result['data']
		];



		echo json_encode($result);
	}

	public function getconciliacaosellercentergridresumo($lote = null){

		if($lote <> null){

			$this->load->model('model_campaigns_v2');
			$this->load->model('model_repasse');

			$data        = $this->model_billet->getConciliacaoSellerCenter($lote,"pedidos");
			
			if($data){
				$result['data'][0] = array(
					"1",
					"Total de Pedidos",
					$data[0]['qtd']);
			}else{
				$result['data'][0] = array(
					"1",
					"Total de Pedidos",
					"0");
			}

			$data = $this->model_billet->getConciliacaoSellerCenter($lote,"valores");
			
			if($data){
				$result['data'][1] = array(
					"2",
					"Total de Pedidos em reais",
					"R$ ".number_format($data[0]['qtd'], 2, ",", "."));
					// "R$ ".str_replace ( ".", ",", $data[0]['qtd'] ));
			}else{
				$result['data'][1] = array(
					"2",
					"Total de Pedidos em reais",
					"0");
			}
			
			$data = $this->model_billet->getConciliacaoSellerCenter($lote,"cancelados");
			
			if($data){
				$result['data'][2] = array(
					"3",
					"Total de Pedidos Cancelados",
					$data[0]['qtd']);
			}else{
				$result['data'][2] = array(
					"3",
					"Total de Pedidos Cancelados",
					"0");
			}

			$data = $this->model_billet->getConciliacaoSellerCenter($lote,"cancelados_valores");
			
			if($data){
				$result['data'][3] = array(
					"4",
					"Total de Pedidos Cancelados em reais",
					"R$ ".number_format($data[0]['qtd'], 2, ",", "."));
					// "R$ ".str_replace ( ".", ",", $data[0]['qtd'] ));
			}else{
				$result['data'][3] = array(
					"4",
					"Total de Pedidos Cancelados em reais",
					"0");
			}

			$data = $this->model_billet->getConciliacaoSellerCenter($lote,"sellers");
			
			if($data){
				$result['data'][4] = array(
					"5",
					"Total de Sellers no ciclo",
					$data[0]['qtd']);
			}else{
				$result['data'][4] = array(
					"5",
					"Total de Sellers no ciclo",
					"0");
			}

            $order_ids = [];
            $data_orders = $this->model_billet->getConciliacaoSellerCenter($lote);
            if (!empty($data_orders))
            {
                foreach($data_orders as $order)
                {
                    $order_ids[] = $order['order_id'];
                }
            }

            $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "pricetags");
            $result['data'][5] = array("6", "Total em PriceTags");
            $result['data'][5][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : "0";

            $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "campaigns");
            $result['data'][6] = array("7", "Total de Descontos em Campanhas/Ofertas");
            $result['data'][6][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : "0";

            $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "channel");
            $result['data'][7] = array("8", "Total de Redução para o Market Place");
            $result['data'][7][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : "0";

            $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "seller");
            $result['data'][8] = array("9", "Total de Redução para o Seller");
            $result['data'][8][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : "0";

            $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "promotions");
            $result['data'][9] = array("10", "Total Descontado em Promoções");
            $result['data'][9][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : "0";

            $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "comission_reduction");
            $result['data'][10] = array("11", "Redução na Comissão do Market Place");
            $result['data'][10][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : "0";

            $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "rebate");
            $result['data'][11] = array("12", "Total em Rebate");
            $result['data'][11][] = ($data['total']) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data['total'])) : "0";

            // $data = $this->model_campaigns_v2->getConciliationTotals($order_ids, "refund");
            $data = $this->model_billet->getConciliacaoSellerCenter($lote,"refund");
            $result['data'][12] = array("13", "Total Reembolso");
            $result['data'][12][] = ($data) ? str_replace($this->session->userdata('currency'), $this->session->userdata('currency').' ', $this->formatprice($data[0]['total_refund'])) : "0";

			$data = $this->model_repasse->getPaidStores($lote);
			$result['data'][13] = array("14", "Total de Seller");
			$result['data'][13][] = count($data);

			$paid_stores = ['positive' => 0, 'negative' => 0];

			foreach ($data as $seller)
			{
				if ($seller['total'] >= 0)
				{
					$paid_stores['positive']++;
				}
				else
				{
					$paid_stores['negative']++;
				}
			}

			$result['data'][14] = array("15", "Total de Seller Positivo");
			$result['data'][14][] = $paid_stores['positive'];

			$result['data'][15] = array("16", "Total de Seller Negativo");
			$result['data'][15][] = $paid_stores['negative'];
	    
		}else{
			$result['data'][0] = array("","","");
		}

		echo json_encode($result);
	}

	public function getstoresfromconciliacaosellercenter(){

		if(!in_array('createNFS', $this->permission)) {
    	    redirect('dashboard', 'refresh');
    	}
	    
	    $lote = $this->postClean('lote',TRUE);
	    
	    $result = $this->model_billet->getstoresfromconciliacaosellercenter($lote);
	    echo json_encode($result);

	} 

	public function getstatusfromconciliacaosellercenter(){

		if(!in_array('createNFS', $this->permission)) {
    	    redirect('dashboard', 'refresh');
    	}
	    
	    $lote = $this->postClean('lote',TRUE);
	    
	    $result = $this->model_billet->getstatusfromconciliacaosellercenter($lote);
	    echo json_encode($result);

	} 

	public function salvarobssellercenter(){
	    
	    $inputs = $this->postClean(NULL,TRUE);

	    if($inputs['txt_hdn_pedido_obs'] <> "" && $inputs['txt_observacao'] <> "" && $inputs['hdnLote'] <> "" ){
	        $data = $this->model_billet->salvaObservacaosellercenter($inputs);
	        if($data){
    	        echo "0;Observação cadastrada com sucesso";
	        }else{
	            echo "1;Erro ao cadastrar observação.";
	        }
	    }
	    
	}
	
	public function incluiremovepedidoconciliacao(){
	    
	    $inputs = $this->postClean(NULL,TRUE);

	    if($inputs['id'] <> "" && $inputs['hdnLote'] <> ""){
	        $data = $this->model_billet->incluiremovepedidoconciliacaosellercenter($inputs);
	        if($data){
    	        echo "0;Pedido tratado com sucesso";
	        }else{
	            echo "1;Erro ao tratar pedido.";
	        }
	    }
	    
	}

	public function alteracomissaosellercenter(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
		 
	    if($inputs['txt_comissao'] <> "" && $inputs['txt_hdn_pedido_comissao'] <> "" && $inputs['hdnLote'] <> "" ){
	        $data = $this->model_billet->alteracomissaosellercenter($inputs);
	        if($data){
    	        echo "0;Observação cadastrada com sucesso";
	        }else{
	            echo "1;Erro ao cadastrar observação.";
	        }
	    }
	    
	}

	public function buscaobservacaopedidosellercenter(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    if($inputs['pedido'] <> "" && $inputs['lote'] <> "" ){
	        $data = $this->model_billet->buscaobservacaopedidosellercenter($inputs['lote'], $inputs['pedido'], 1);
	    }else{
	        $data[0] = array("","","");
	    }
	    
	    echo json_encode($data);
	    
	}

	public function cadastrarconciliacaosellercenter()
    {    
	    $inputs = $this->postClean(NULL,TRUE);

	    if ($inputs['slc_mktplace'] <> "" and $inputs['txt_ano_mes'] <> "" and $inputs['slc_ciclo'] <> "")
        {
			$inputs['slc_ano_mes'] = $inputs['txt_ano_mes'];
	        
	        //Verifica se já existe essa conciliação
	        $check = $this->model_billet->verificaconsiliacao($inputs);
	        
            $this->load->model('model_campaigns_v2');
            $this->load->model('model_gateway');
            $payment_gateway_code = $this->model_gateway->getGatewayCodeById($this->payment_gateway_id);

            $orders = $this->model_billet->getOrdersFromConciliacaoSellercenter($inputs['hdnLote']);
	       
	        if ($check['qtd'] == 0) 
            {
	            //cadastra uma nova
    	        $data = $this->model_billet->criaconsiliacao($inputs);
				if($data)
                {
                    $carregaTemp = $this->model_billet->carregaTempRepasseSellercenter($inputs['hdnLote'], null, $payment_gateway_code);
					$data2 = $this->model_billet->cadastraRepasseFinal($inputs['hdnLote']);

                    $this->model_orders_conciliation_installments->setPaidByBatch($inputs['hdnLote']);

					if($data2)
						echo "0;Conciliação cadastrada com sucesso";
					else
						echo "1;Erro ao cadastrar conciliação $data";					
				}else
                    echo "1;Erro ao cadastrar conciliação $data";

	        }else{

				$inputs['slc_ano_mes'] = $inputs['txt_ano_mes'];

                //atualiza os dados da conciliação	            
	            $dataEdit = $this->model_billet->editaconsiliacao($inputs);
	            if($dataEdit){

                    $this->model_orders_conciliation_installments->setPaidByBatch($inputs['hdnLote']);


                    $repasse_pago = $this->model_billet->getConciliacaoGridData($inputs['hdnLote']);

                    if (is_array($repasse_pago) && $repasse_pago[0]['pagamento_conciliacao'] == 'Conciliação não paga') { // repasse ainda nao foi pago
                        $carregaTemp = $this->model_billet->carregaTempRepasseSellercenter($inputs['hdnLote'], null, $payment_gateway_code);
                        $data2 = $this->model_billet->cadastraRepasseFinal($inputs['hdnLote']);
                    }
                    echo "0;Conciliação cadastrada com sucesso";

	            }else{
	                echo "1;Erro ao atualizar conciliação $dataEdit";
	            }
	        }

	    }else{
	        echo "1;Erro ao cadastrar conciliação<br>Os campos não estavam todos preenchidos";
	    }
	    
	}

    private function generateArrayDataConciliation($hdnLote, $inputsGet, $length = null, $start = null): array
    {

        $result = [];

        $data = $this->model_billet->getConciliacaoSellerCenter($hdnLote,null,null,$length,$start,$inputsGet);

        if ($data) {

            foreach ($data as $key => $value) {

                $valor_repasse = 0.00;
                if($value['valor_repasse_ajustado'] <> "0.00"){
                    $valor_repasse = $value['valor_repasse_ajustado'];
                }else{
                    $valor_repasse = $value['valor_repasse'];
                }

                switch ($value['status_conciliacao']) {
                    case "Conciliação Cancelamento" :
                        $status = $this->lang->line("application_payment_release_cancel");
                        break;
                    case "Conciliação Ciclo" :
                        $status = $this->lang->line("application_payment_release_cycle");
                        break;
                    case "Conciliação Estorno de Comissão" :
                        $status = $this->lang->line("application_payment_release_legal_panel_cycle");
                        break;
                    default:
                        $status = $value['status_conciliacao'];
                }

                // checa se o pedido tem valor antecipado
                $valor_antecipado = 0;

                // $n_mkt = $this->model_billet->getMarketplaceNumberFromOrderId($value['order_id']);
                // if(!is_null($n_mkt)){
                    $valor_antecipado = $this->model_billet->getValueAnticipationTransfer($value['numero_marketplace']);
                // }

                // $campaign_data = $this->model_campaigns_v2->getCampaignsTotalsByOrderId($data[$key]['order_id']);

                $valor_percentual_produto = $value['valor_percentual_produto']."%";
                // $valor_percentual_produto .= (isset($campaign_data['comission_reduction']) && floatVal($campaign_data['comission_reduction']) > 0) ? '*' : '';
				$valor_percentual_produto .= (isset($value['comission_reduction']) && floatVal($value['comission_reduction']) > 0) ? '*' : '';

               /* $campaigns_pricetags                    = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'pricetags', true));
                $campaigns_campaigns                    = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'campaigns', true));
                $campaigns_mktplace                     = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'channel', true));
                $campaigns_seller                       = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'seller', true));
                $campaigns_promotions                   = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'promotions', true));
                $campaigns_rebate                       = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'rebate', true));
                $campaigns_comission_reduction          = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'comission_reduction', true));
                $campaigns_comission_reduction_products = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'comission_reduction_products', true));

                $campaigns_pricetags                    = (is_array($campaigns_pricetags)) ? $campaigns_pricetags['total'] : $campaigns_pricetags;
                $campaigns_campaigns                    = (is_array($campaigns_campaigns)) ? $campaigns_campaigns['total'] : $campaigns_campaigns;
                $campaigns_mktplace                     = (is_array($campaigns_mktplace)) ? $campaigns_mktplace['total'] : $campaigns_mktplace;
                $campaigns_seller                       = (is_array($campaigns_seller)) ? $campaigns_seller['total'] : $campaigns_seller;
                $campaigns_promotions                   = (is_array($campaigns_promotions)) ? $campaigns_promotions['total'] : $campaigns_promotions;
                $campaigns_rebate                       = (is_array($campaigns_rebate)) ? $campaigns_rebate['total'] : $campaigns_rebate;
                $campaigns_comission_reduction          = (is_array($campaigns_comission_reduction)) ? $campaigns_comission_reduction['total'] : $campaigns_comission_reduction;
                $campaigns_comission_reduction_products = (is_array($campaigns_comission_reduction_products)) ? $campaigns_comission_reduction_products['total'] : $campaigns_comission_reduction_products; */

				
				$campaigns_pricetags                    = 	$value['total_pricetags'];
				$campaigns_campaigns                    =	$value['total_campaigns'];
				$campaigns_mktplace                     =	$value['total_channel'];
				$campaigns_seller                       =	$value['total_seller'];
				$campaigns_promotions                   =	$value['total_promotions'];
				$campaigns_rebate                       =	$value['total_rebate'];
				$campaigns_comission_reduction          =	$value['comission_reduction'];
				$campaigns_comission_reduction_products =	$value['comission_reduction_products'];

                $valor_repasse_calc_refund          = (is_numeric($data[$key]['valor_repasse'])) ? $data[$key]['valor_repasse'] : 0;
                $valor_repasse_calc_refund_ajustado = (is_numeric($data[$key]['valor_repasse_ajustado'])) ? $data[$key]['valor_repasse_ajustado'] : 0;

                if ($this->setting_api_comission != "1"){
                    $campaigns_refund = (abs($valor_repasse - $valor_repasse_calc_refund) > 0) ? round((abs($valor_repasse_calc_refund_ajustado - $valor_repasse_calc_refund)),2) : (0);
                }else {
                    $campaigns_refund = (abs($valor_repasse_calc_refund_ajustado - $valor_repasse_calc_refund - $campaigns_comission_reduction_products) > 0) ? round((abs($valor_repasse_calc_refund_ajustado - $valor_repasse_calc_refund - $campaigns_comission_reduction_products)),2) : (0);
                }

                $result[$key] = array(
                    0 => $value['order_id'],
					1 => $value['numero_marketplace'],
                    2 => $value['seller_name'],
                    3 => $value['data_pedido'],
                    4 => $value['data_entrega'],
                    5 => $value['data_ciclo'],
                    6 => $status,
                    7 => "R$ ".str_replace ( ".", ",", $value['valor_pedido'] ),
                    8 => "R$ ".str_replace ( ".", ",", $value['valor_produto'] ),
                    9 => "R$ ".str_replace ( ".", ",", $value['valor_frete'] ),
                    10 => $value['valor_percentual_produto']."%",
                    11 => $value['valor_percentual_frete']."%",
                    12 => "R$ ".str_replace ( ".", ",", $value['valor_comissao'] ),
                    13 => "R$ ".str_replace ( ".", ",", $value['valor_comissao_produto'] ),
                    14 => "R$ ".str_replace ( ".", ",", $value['valor_comissao_frete'] ),
                    15 => "R$ ".str_replace ( ".", ",", $valor_repasse ),
                    16 => "R$ ".str_replace ( ".", ",", $value['valor_repasse_produto'] ),
                    17 => "R$ ".str_replace ( ".", ",", $value['valor_repasse_frete'] ),
                    18 => $value['tipo_pagamento'],
                    19 => $value['taxa_cartao_credito'],
                    20 => $value['digitos_cartao'],
                    21 => $value['cnpj'],
                    22 => $value['data_report'],
                    23 => "R$ ".str_replace ( ".", ",", $valor_antecipado ),
                    24 => $value['store_id'],
                    25 => $value['current_installment']."/".$value['total_installments'],
					26 => $value['descloja'],
                    'campaigns_pricetags' => "R$ ".str_replace ( ".", ",",  $campaigns_pricetags),
                    'campaigns_campaigns' => "R$ ".str_replace ( ".", ",",  $campaigns_campaigns),
                    'campaigns_mktplace' => "R$ ".str_replace ( ".", ",",  $campaigns_mktplace),
                    'campaigns_seller' => "R$ ".str_replace ( ".", ",",  $campaigns_seller),
                    'campaigns_promotions' => "R$ ".str_replace ( ".", ",",  $campaigns_promotions),
                    'campaigns_comission_reduction' => "R$ ".str_replace ( ".", ",",  $campaigns_comission_reduction),
                    'campaigns_rebate' => "R$ ".str_replace ( ".", ",",  $campaigns_rebate),
                    'campaigns_refund' => "R$ ".str_replace ( ".", ",", $campaigns_refund ),
                    'user' => $value['usuario'],
                    'observacao' => $value['observacao'],
                    'id' => $value['id'],
                    'order_id' => $value['order_id'],
                    'tratado' => $value['tratado'],
                );

            }
        }else{
            $result[0] = array(
                0 => '',
                1 => '',
                2 => '',
                3 => '',
                4 => '',
                5 => '',
                6 => '',
                7 => '',
                8 => '',
                9 => '',
                10 => '',
                11 => '',
                12 => '',
                13 => '',
                14 => '',
                15 => '',
                16 => '',
                17 => '',
                18 => '',
                19 => '',
                20 => '',
                21 => '',
                22 => '',
                23 => '',
                24 => '',
                25 => '',
                26 => '',
                'campaigns_pricetags' => '',
                'campaigns_campaigns' => '',
                'campaigns_mktplace' => '',
                'campaigns_seller' => '',
                'campaigns_promotions' => '',
                'campaigns_comission_reduction' => '',
                'campaigns_rebate' => '',
                'campaigns_refund' => '',
                'user' => '',
                'observacao' => '',
                'id' => '',
                'order_id' => '',
                'tratado' => '',
            );

        }
        return $result;

    }

	public function exportaconciliacaosellercenter($hdnLote = null)
    {
        $this->load->model('model_campaigns_v2');
        $this->load->helper('money');

	    header("Pragma: public");
	    header("Cache-Control: no-store, no-cache, must-revalidate");
	    header("Cache-Control: pre-check=0, post-check=0, max-age=0");
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    header("Content-Transfer-Encoding: none");
	    header("Content-Type: application/vnd.ms-excel;");
	    header("Content-type: application/x-msexcel;");
	    header("Content-Disposition: attachment; filename=liberacao_pagamento_$hdnLote.xls"); 

		$valorCartao = $this->model_settings->getSettingDatabyName('digitos_cartao_relatorio_fin');
		
		if(!$valorCartao){
			$valorCartao['status'] = "0";
		}
		
	    $input['lote'] = $hdnLote;


		$inputs = $this->input;
		$inputsGet = $inputs->get();

        $result = $this->generateArrayDataConciliation($hdnLote, $inputsGet);

        ob_clean();
	    echo '<table id="manageTableOrdersOk" class="table table-bordered table-striped" border="1" cellpadding="4">';
	    echo utf8_decode('<thead>
                            <tr>');
		if($valorCartao['status'] == "1"){
			echo utf8_decode('	<th colspan="20" bgcolor="blue" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>');
		}else{
			echo utf8_decode('	<th colspan="19" bgcolor="blue" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>');
		}
		echo utf8_decode('		<th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Seller Center</font></th>
                            	<th colspan="4" bgcolor="#16F616" class="text-center"><font color="#FFFFFF">Seller</font></th>
                            	<th colspan="8" bgcolor="blue" class="text-center"><font color="#FFFFFF">Campanhas</font></th>
                            </tr>
                            <tr>
                                <th>Id Pedido</th>
								<th>Marketplace</th>
                                <th>Número pedido VTEX</th>
								<th>Id Seller</th>
								<th>Seller</th>
								<th>CNPJ</th>
								<th>Data Pedido</th>
								<th>Data de Entrega</th>
								<th>Data Gatilho Liberação de Pagamento</th>
								<th>Data do pagamento</th>
								<th>'.$this->lang->line('application_payment_release').'</th>
								<th>Total Pedido</th>');								
								echo utf8_decode('<th>Valor Pago Antecipação</th>');
								echo utf8_decode('<th>Valor Produtos</th>
								<th>Valor Frete</th>
								<th>% Comissão sobre produto</th>
								<th>% Comissão sobre frete</th>
								<th>Tipo Pagamento</th>
								<th>MDR</th>');

		if($valorCartao['status'] == "1"){
			echo utf8_decode('	<th>'.$this->lang->line('application_card_number').'</th>');
		}

		echo utf8_decode('		<th>Total Comissão</th>
								<th>Comissão Produto</th>
								<th>Comissão Frete</th>
								<th>Total Repasse</th>
								<th>Repasse Produto</th>
								<th>Repasse Frete</th>
								<th>'.$this->lang->line('application_parcel').'</th>
								
                                <th>'.$this->lang->line('conciliation_sc_gridok_pricetags').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_campaigns').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_totalmktplace').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_totalseller').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_promotions').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_comissionredux').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_rebate').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_refund').'</th>
                            </tr>
                           </thead>');
	    
	    foreach($result as $k => $value) {

	        echo "<tr>";
	        echo utf8_decode("<td>".$value[0]."</td>");
	        echo utf8_decode("<td>".$value[26]."</td>");
			echo utf8_decode("<td>".$value[1]."</td>");
			echo utf8_decode("<td>".$value[24]."</td>");
	        echo utf8_decode("<td>".$value[2]."</td>");
			echo utf8_decode("<td>&nbsp;".$value[21]."</td>");
			echo utf8_decode("<td>".$value[3]."</td>");
			echo utf8_decode("<td>".$value[4]."</td>");
			echo utf8_decode("<td>".$value[22]."</td>");
			echo utf8_decode("<td>".$value[5]."</td>");
			echo utf8_decode("<td>".$value[6]."</td>");
			echo utf8_decode("<td>".$value[7]."</td>");
			echo utf8_decode("<td>".$value[23]."</td>");
			echo utf8_decode("<td>".$value[8]."</td>");
			echo utf8_decode("<td>".$value[9]."</td>");
			echo utf8_decode("<td>".$value[10]."</td>");
			echo utf8_decode("<td>".$value[11]."</td>");
			echo utf8_decode("<td>".$value[18]."</td>");
			echo utf8_decode("<td>".$value[19]."</td>");
			if($valorCartao['status'] == "1"){
				echo utf8_decode("<td>".$value[20]."</td>");
			}
			echo utf8_decode("<td>".$value[12]."</td>");
			echo utf8_decode("<td>".$value[13]."</td>");
			echo utf8_decode("<td>".$value[14]."</td>");
			echo utf8_decode("<td>".$value[15]."</td>");
			echo utf8_decode("<td>".$value[16]."</td>");
			echo utf8_decode("<td>".$value[17]."</td>");
			echo utf8_decode("<td>&nbsp;".$value[25]."</td>");
            echo utf8_decode("<td>".$value['campaigns_pricetags']."</td>");
            echo utf8_decode("<td>".$value['campaigns_campaigns']."</td>");
            echo utf8_decode("<td>".$value['campaigns_mktplace']."</td>");
            echo utf8_decode("<td>".$value['campaigns_seller']."</td>");
            echo utf8_decode("<td>".$value['campaigns_promotions']."</td>");
            echo utf8_decode("<td>".$value['campaigns_comission_reduction']."</td>");
            echo utf8_decode("<td>".$value['campaigns_rebate']."</td>");
            echo utf8_decode("<td>".$value['campaigns_refund']."</td>");

	        echo "</tr>";

	    }
	    
	    echo '</table>';
	    
	}

	public function testeretornoprototipo2($lote = null){

		if($lote == 1){
			$result['data'][0] = array("1","Total de Pedidos","5");
			$result['data'][1] = array("2","Total de Pedidos em reais","R$ 3.100,00");
			$result['data'][2] = array("3","Total de Pedidos Cancelados","1");
			$result['data'][3] = array("4","Total de Pedidos Cancelados em reais","R$ 300,00");
			$result['data'][4] = array("5","Total de Sellers no ciclo","3");
		}else{
			$result['data'][0] = array("1","Total de Pedidos","5");
			$result['data'][1] = array("2","Total de Pedidos em reais","R$ 3.100,00");
			$result['data'][2] = array("3","Total de Pedidos Cancelados","1");
			$result['data'][3] = array("4","Total de Pedidos Cancelados em reais","R$ 300,00");
			$result['data'][4] = array("5","Total de Sellers no ciclo","3");
		}
    	    echo json_encode($result);
    	    die;

	}

	/******************************************************/
	
	public function listlegalpanelsellercenter()
	{
	    if(!in_array('viewBillet', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }

		$valorNM = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
		if($valorNM['status'] == "1"){
			$this->data['page_title'] = $this->lang->line('application_legal_panel');
		}else{
			$this->data['page_title'] = $this->lang->line('application_legal_panel');
		}
	    
	    
	    
	    if(in_array('createBillet', $this->permission)) {
	        $this->data['categs'] = "";
	        $this->data['mktPlaces'] = "";
	    }
	    
	    $this->render_template('billet/listlegalpanel', $this->data);
	}

	public function fetchlegalpanelsellercenter(){
		$result['data'] = array();
		echo json_encode($result);
    	    die;
	}
	
	public function fetchlegalpanelsellercenterconciliacao($legalPanelId){
        $result = [];
		$result['data'] = $this->model_billet->getConciliationsAlreadyDebited((int)$legalPanelId);
        if ($result['data']){
            foreach($result['data'] as &$data){
                $data['valor_repasse'] = money($data['valor_repasse']);
            }
        }
		echo json_encode($result);
    	    die;
	}

	/*
	 * Todo: alterar quando subir a liberação de pagamento fiscal 
	 */
	public function fetchlegalpanelfiscalsellercenterconciliacao($legalPanelId){
        $result = [];
		$result['data'] = $this->model_billet->getConciliationsAlreadyDebited((int)0);
        if ($result['data']){
            foreach($result['data'] as &$data){
                $data['valor_repasse'] = money($data['valor_repasse']);
            }
        }
		echo json_encode($result);
    	    die;
	}

	public function createlegalpanelsellercenter(){
	    
	    
	    $group_data1 = $this->model_billet->getMktPlacesData();
	    $group_data2 = $this->model_parametrosmktplace->getReceivablesDataCiclo();
	    $obsFixo = $this->model_billet->getDataObservacaoFixaPedido();

	    $group_data3 = array();
	    $group_data3['id_mkt'] = "";
	    $group_data3['id_ciclo'] = "";
	    $group_data3['ano_mes'] = "";
	    $group_data3['carregado'] = "0";
	    
	    $this->data['mktplaces'] = $group_data1;
	    $this->data['ciclo'] = $group_data2;
	    $this->data['hdnLote'] = date('YmdHis').rand(1,1000000);
	    $this->data['dadosBanco'] = $group_data3;
	    $this->data['obsFixo'] = $obsFixo;
        $this->data['negociacao_marketplace_campanha'] = ($this->negociacao_marketplace_campanha);
        $this->data['canceled_orders_data_conciliation'] = ($this->canceled_orders_data_conciliation);

		$split_data = $this->model_iugu->getBilletStatusData("Chamado Marketplace");
		$this->data['status_billets'] = $split_data;

		$this->data['page_title'] = 'Painel Jurídico';

	    $this->render_template('billet/createlegalpanel', $this->data);
	}

	public function testeretornoegalpanelsellercenter($entrada = null){

		if($entrada == 1){
			$result['data'][0] = array("001","Conciliação Ciclo","Loja Teste 1","5","17/04/2021","R$ 500,00","R$ 430,00","R$ 70,00","20%","R$ 330,00","André Risi",'','<button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-pencil"></i></button><br><button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-minus"></i></button>');
			$result['data'][1] = array("002","Conciliação Ciclo","Loja Teste 1","5","15/04/2021","R$ 600,00","R$ 540,00","R$ 60,00","20%","R$ 420,00","André Risi",'','<button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-pencil"></i></button><br><button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-minus"></i></button>');
			$result['data'][2] = array("003","Conciliação Ciclo","Loja Teste 2","3","20/04/2021","R$ 700,00","R$ 650,00","R$ 50,00","20%","R$ 510,00","André Risi",'','<button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-pencil"></i></button><br><button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-minus"></i></button>');
			$result['data'][3] = array("004","Cancelamento"     ,"Loja Teste 3","2","03/04/2021","R$ 300,00","R$ 280,00","R$ 20,00","20%","-R$ 220,00","André Risi",'','<button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-pencil"></i></button><br><button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-minus"></i></button>');
			$result['data'][4] = array("005","Conciliação Ciclo","Loja Teste 3","2","25/04/2021","R$ 1000,00","R$ 910,00","R$ 50,00","20%","R$ 710,00","André Risi",'','<button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-pencil"></i></button><br><button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-minus"></i></button>');
		}else{
			$result['data'][0] = array("001","Conciliação Ciclo","Loja Teste 1","5","17/04/2021","R$ 500,00","R$ 430,00","R$ 70,00","20%","R$ 330,00","André Risi",'<button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-eye"></i></button>','<button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-pencil"></i></button><br><button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-minus"></i></button>');
			$result['data'][1] = array("002","Conciliação Ciclo","Loja Teste 1","5","15/04/2021","R$ 600,00","R$ 540,00","R$ 60,00","20%","R$ 420,00","André Risi",'','<button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-pencil"></i></button><br><button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-minus"></i></button>');
			$result['data'][2] = array("003","Conciliação Ciclo","Loja Teste 2","3","20/04/2021","R$ 700,00","R$ 650,00","R$ 50,00","20%","R$ 510,00","André Risi",'','<button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-pencil"></i></button><br><button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-minus"></i></button>');
			$result['data'][3] = array("004","Cancelamento"     ,"Loja Teste 3","2","03/04/2021","R$ 300,00","R$ 280,00","R$ 20,00","20%","-R$ 220,00","André Risi",'','<button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-pencil"></i></button><br><button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-minus"></i></button>');
			$result['data'][4] = array("005","Conciliação Ciclo","Loja Teste 3","2","25/04/2021","R$ 1000,00","R$ 910,00","R$ 50,00","20%","R$ 710,00","André Risi",'','<button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-pencil"></i></button><br><button type="button" class="btn btn-default" onclick="listarObservacao()" data-toggle="modal" data-target="#listaObs"><i class="fa fa-minus"></i></button>');
		}
    	    echo json_encode($result);
    	    die;
	}

	public function testeretornoegalpanelsellercenter2($entrada = null){

		if($entrada == 1){
			$result['data'][0] = array("1","Total de Pedidos","5");
			$result['data'][1] = array("2","Total de Pedidos em reais","R$ 3.100,00");
			$result['data'][2] = array("3","Total de Pedidos Cancelados","1");
			$result['data'][3] = array("4","Total de Pedidos Cancelados em reais","R$ 300,00");
			$result['data'][4] = array("5","Total de Sellers no ciclo","3");
		}else{
			$result['data'][0] = array("1","Total de Pedidos","5");
			$result['data'][1] = array("2","Total de Pedidos em reais","R$ 3.100,00");
			$result['data'][2] = array("3","Total de Pedidos Cancelados","1");
			$result['data'][3] = array("4","Total de Pedidos Cancelados em reais","R$ 300,00");
			$result['data'][4] = array("5","Total de Sellers no ciclo","3");
		}
    	    echo json_encode($result);
    	    die;

	}
	
	public function testeapi(){

		try{


			$this->db->trans_begin();
			$orderId = 4535;

			$this->load->library('ordersMarketplace');

			$AllIdCampaigns = $this->model_campaigns_v2->getAllCampaignsByOrderId($orderId);

			if($AllIdCampaigns){
				foreach($AllIdCampaigns as $camp){
					$ArrayIdCampaigns[$camp['campaign_id']] = $camp['campaign_id'];
				}
		
				//Chamar a função de clean
				if(!$this->model_campaigns_v2->clearCampaignsTablesFromChangeSeller($orderId)){
					$this->db->trans_rollback();
				}
		
				//Select nos itens do pedido para recalcular as campanhas
				$items = $this->model_orders->getOrdersItemData($orderId);
				foreach($items as $item){
					if(!$this->ordersmarketplace->saveTotalDiscounts($item, $item['id'], $ArrayIdCampaigns)){
						$this->db->trans_rollback();
					}
				}
			}

			$this->db->trans_commit();

			$IdsTratados = implode(",", $ArrayIdCampaigns);
			dd($AllIdCampaigns,$ArrayIdCampaigns,$IdsTratados);

			

			$data['order_id'] = '0';
			$data['numero_marketplace'] = 'teste';
			$data['data_split'] = '2022-05-13 15:49:23';
			$data['data_transferencia'] = '2022-05-13 15:49:23';
			$data['data_repasse_conta_corrente'] = '2022-05-13 15:49:23';
			$data['conciliacao_id'] = 18;
			$data['valor_parceiro'] = 48.90;
			$data['valor_afiliado'] = null;
			$data['valor_produto_conectala'] = null;
			$data['valor_frete_conectala'] = null;
			$data['valor_repasse_conectala'] = null;
	
			$teste = $this->model_billet->insertIuguRepasse($data);

			
			if($teste){
				$this->db->trans_commit();
				//$this->db->trans_rollback();
			}else{
				$this->db->trans_rollback();
			}

		}catch(Exception $e){
			echo $e;
			$this->db->trans_rollback();
		}

	}


	/*********INÍCIO DAS FUNÇÕES DA LIBERAÇÃO DE PAGAMENTO FISCAL ******* */
	public function listsellercenterfiscal()
	{
	    if(!in_array('createPaymentReleaseFiscal', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }

		$this->data['page_title'] = $this->lang->line('application_payment_release_fiscal_consulta');

	    if(in_array('createBillet', $this->permission)) {
	        $this->data['categs'] = "";
	        $this->data['mktPlaces'] = "";
	    }

	    $this->render_template('billet/listsellercenterfiscal', $this->data);
	}

	public function fetchConciliacaoGridDataFiscal($tipo = "conectala")
	{
		$gateways_with_payment_report = [];
		$setting_gateways_with_payment_report = $this->model_settings->getSettingDatabyName('payment_gateways_with_payment_report');

		if (isset($setting_gateways_with_payment_report['value']))
		{
			$gateways_with_payment_report = explode(';', $setting_gateways_with_payment_report['value']);
		}

	    $result = array('data' => array());
	    
	    $data = $this->model_billet->getConciliacaoGridDataFiscal();

	    foreach ($data as $key => $value) {

			$title_adjustment = '';
	        // button
	        $buttons = '';

            if(in_array('viewBilletConcil', $this->permission)) {
                if($tipo == "conectala"){
                    $buttons .= ' <a href="'.base_url('billet/editfiscal/'.$value['lote']).'" class="btn btn-default" title="'.$this->lang->line('application_view').'"><i class="fa fa-eye"></i></a>';
                    // $buttons .= ' <button type="button" class="btn btn-default" onclick="exportaArquivoConciliacao(\''.$value['lote'].'\')"><i class="fa fa-file-excel-o"></i></button>';
                    $buttons .= ' <button type="button" class="btn btn-default" onclick="exportaArquivoConciliacao(\''.$value['lote'].'\', \''.$value['integ_id'].'\')"><i class="fa fa-file-excel-o"></i></button>';
                    // $buttons .= ' <a href="'.base_url('billet/paymentreconciliation/'.$value['lote']).'" class="btn btn-default"><i class="fa fa-money"></i></a>'

                    $flagrepasse = $this->model_settings->getSettingDatabyName('flag_liberacao_repasse_conciliacao');
                    if($flagrepasse){
                        $checkIugu = $this->model_billet->iugurepassecheck($value['id_con']);
                        if($flagrepasse['status'] == "1" && $checkIugu == 0){
                            $lote = $value["lote"];
                            // $buttons .= ' <button onclick="confirmarConciliacao(\''.$lote.'\')" class="btn btn-default"><i class="fa fa-money"></i></button>';
                        }
                    }
                }
            }

            if(in_array('createPaymentReleaseFiscal', $this->permission)) {
                if($tipo != "conectala"){

                    $disabled = "";
                    $buttons .= ' <a href="'.base_url('billet/editsellercenterfiscal/'.$value['lote']).'" class="btn btn-default" title="'.$this->lang->line('application_view').'"><i class="fa fa-eye"></i></a>';

                    $flagrepasse = $this->model_settings->getSettingDatabyName('flag_liberacao_repasse_conciliacao');
                    if($flagrepasse){

                        $checkIugu = $this->model_billet->iugurepassecheck($value['id_con']);

                        if($flagrepasse['status'] == "1")
                        {
                            $lote = $value["lote"];
                            $buttons .= '<button ';

                            if (@$value['pagamento_conciliacao'] != 'Conciliação Paga')
                            {
                                $buttons .= ' onclick="confirmarLiberacaoPagamento(\''.$lote.'\')" ';
                            }
                            else
                            {
                                $buttons .= '  onclick="alert(\''.$this->lang->line('application_notification_payment_processed_subject').'\');" ';
                                $disabled = ' disabled';
                            }

                            $buttons .= ' class="btn btn-default'.$disabled.'"><i class="fa fa-money"></i></button>';
                        }
                    }
                }
            }

			if (in_array($this->payment_gateway_id, $gateways_with_payment_report))
			{
				$buttons .= ' <a href="'.base_url('payment/paymentReports/'.$value['ano_mes'].'/'.$value['lote']).'" class="btn btn-default" ';

				$allow_transfer_between_accounts = $this->model_gateway_settings->getGatewaySettingByName($this->payment_gateway_id, 'allow_transfer_between_accounts');

				if ($value['status_repasse'] == '27' && $allow_transfer_between_accounts == 1)
				{
					$buttons .= ' style="color: red;" ';
					$title_adjustment = ' - '.$this->lang->line('payment_report_list_btn_adjustment');
				}

				$buttons .= ' title="'.$this->lang->line('application_payment_report').$title_adjustment.'"><i class="fa fa-list-ul"></i></a>';
			}

            $ciclo = 'Do dia '.$value['data_inicio'].' - até '.$value['data_fim'];

	        if($tipo == "sellercenter" ){

                if($value['conciliacao_id'] == 0) {

                    $value['pagamento_conciliacao'] = $this->lang->line('application_payment_release_unpaid');
                }else{

                    $value['pagamento_conciliacao'] = $this->lang->line('application_payment_release_paid');
                }

                if($value['status'] == "Conciliação com sucesso") {

                    $value['status'] = $this->lang->line('application_payment_release_success');
                }else{

                    $value['status'] = $this->lang->line('application_payment_release_pending');
                }

            }

					$result['data'][$key] = array(
	            $value['id_con'],
	            $value['data_criacao'],
	            $value['ano_mes'],
	            $value['apelido'],
	            $ciclo,
	            $value['status'],
				$value['pagamento_conciliacao'],
	            $buttons
	        );
	    } // /foreach
	    
	    echo json_encode($result);
	}

	public function createconciliasellercenterfiscal(){
	    
	    
	    $group_data1 = $this->model_billet->getMktPlacesData();
	    $group_data2 = $this->model_parametrosmktplace->getReceivablesDataCicloFiscal();
	    $obsFixo = $this->model_billet->getDataObservacaoFixaPedido();
	    
	    $group_data3 = array();
	    $group_data3['id_mkt'] = "";
	    $group_data3['id_ciclo'] = "";
	    $group_data3['ano_mes'] = "";
	    $group_data3['carregado'] = "0";
	    
	    $this->data['mktplaces'] = $group_data1;
	    $this->data['ciclo'] = $group_data2;
	    $this->data['hdnLote'] = date('YmdHis').rand(1,1000000);
	    $this->data['dadosBanco'] = $group_data3;
	    $this->data['obsFixo'] = $obsFixo;
        $this->data['negociacao_marketplace_campanha'] = ($this->negociacao_marketplace_campanha);
        $this->data['canceled_orders_data_conciliation'] = ($this->canceled_orders_data_conciliation);				

		$this->data['flag_pago'] = false;

		$this->data['page_title'] = 'Conciliação';
	    
	    $this->render_template('billet/createsellercenterfiscal', $this->data);
	}

	public function geraconciliacaosellercenterfiscal()
    {
		$inputs = $this->postClean(NULL,TRUE);

		if (/* isset($inputs['slc_ciclo']) && */ $inputs['slc_mktplace'] <> "" && $inputs['txt_ano_mes'] <> "" && $inputs['slc_ciclo'] <> "")
        {
			
			//Monta ciclo a ser buscado
			$group_data2 = $this->model_parametrosmktplace->getReceivablesDataCicloFiscal($inputs['slc_ciclo']);

			
			$valorSellercenter = $this->model_settings->getSettingDatabyName('sellercenter');

			$data = str_replace("-","/",$inputs['txt_ano_mes']);
			if($group_data2['data_ciclo_fiscal'] < 10){
				$inputs['data_ciclo'] = "0".intVal($group_data2['data_ciclo_fiscal'])."/".$data ;
		}else{
				$inputs['data_ciclo'] = $group_data2['data_ciclo_fiscal']."/".$data ;
			}

            //braun hack fin-685
			$this->db->trans_begin();

            //Removendo os registros de parcelamento inválido ao iniciar
            $this->model_orders_conciliation_installments->clearInvalidBatchesInstallmentsFiscal();

			if ($valorSellercenter['value'] != "fastshop" && $valorSellercenter['value'] != "epoca" && $valorSellercenter['value'] != "sicredi"){
				list($geraConciliacao, $conciliation_array) = $this->model_billet->generateInstallmentsByInputsFiscal(
					$inputs,
					$this->setting_api_comission
				);
			}


            $currentConciliationInstallments = $this->model_orders_conciliation_installments->findAllToBeReconciledFiscal(dateBrazilToDateInternational($inputs['data_ciclo']));
			
            if ($currentConciliationInstallments){

                foreach ($currentConciliationInstallments as $conciliationInstallment){

                    //Salvando o lote da conciliação a ser gerada agora na parcela em questão
                    $conciliationInstallment['lote'] = $inputs['hdnLote'];

                    $paid_status = $this->model_orders->getOrdersData(null, $conciliationInstallment['order_id'])['paid_status'];
					
                    if ($paid_status == '96' && $this->orders_precancelled_to_zero && $conciliationInstallment['status_conciliacao'] == 'Conciliação Cancelamento')
                    {
                        $conciliationInstallment['valor_comissao_produto'] = 0;
                        $conciliationInstallment['valor_comissao_frete'] = 0;
                        $conciliationInstallment['valor_comissao'] = 0;
                        $conciliationInstallment['valor_repasse'] = 0;
                        $conciliationInstallment['valor_repasse_ajustado'] = 0;
                    }

                    $this->model_orders_conciliation_installments->updateFiscal($conciliationInstallment['id'], $conciliationInstallment);

                    //Salvando a conciliação
                    $conciliation_array = $this->model_orders_conciliation_installments->convertConciliationInstallmentToConciliationSellerCenter($conciliationInstallment);

                    //braun -> recuperando o formato de data que era utilizado anteriormente.
                    $conciliation_array['data_ciclo'] = $inputs['data_ciclo'];

                    $geraConciliacao[] = $this->model_billet->createConciliacaoSellerCenterFiscal($inputs, $_SESSION['username'], $conciliation_array);
                }
            }

            $conciliacaoPainelLegal = $this->model_billet->createConciliacaoSellerCenterPainelLegalFiscal($inputs, $_SESSION['username']);

            $this->db->trans_commit();

			if (!empty($geraConciliacao) || $conciliacaoPainelLegal) {
                echo $this->lang->line('application_payment_release_payment_created');
            } else {
                echo $this->lang->line('application_payment_release_payment_created_error');
                //echo "1;Erro ao gerar a conciliação!";
            }
		}else{
			echo $this->lang->line('application_payment_release_payment_created_error');
		}
	}

	public function getstoresfromconciliacaosellercenterfiscal(){

		if(!in_array('createNFS', $this->permission)) {
    	    redirect('dashboard', 'refresh');
    	}
	    
	    $lote = $this->postClean('lote',TRUE);
	    
	    $result = $this->model_billet->getstoresfromconciliacaosellercenterFiscal($lote);
	    echo json_encode($result);

	} 

	public function getstatusfromconciliacaosellercenterfiscal(){

		if(!in_array('createNFS', $this->permission)) {
    	    redirect('dashboard', 'refresh');
    	}
	    
	    $lote = $this->postClean('lote',TRUE);
	    
	    $result = $this->model_billet->getstatusfromconciliacaosellercenterFiscal($lote);
	    echo json_encode($result);

	} 

	public function getconciliacaosellercentergridfiscal($lote = null){

		$inputs = $this->input;
		$post = $inputs->post();
		$inputsGet = $inputs->get();

		if($lote <> null){

			// registros
            $data = $this->generateArrayDataConciliationFiscal($lote, $inputsGet, ($post['length']) ?? null, ($post['start']) ?? null);

			//Contador de registros
			$countData = $this->model_billet->getConciliacaoSellerCenterFiscal($lote,'pedidos',null,null,null,$inputsGet);
			$count = $countData[0]["qtd"];

			if($data) {

				foreach ($data as $key => $value) {

					$buttons = '';
					$observacao = '';
					
					$buttons .= ' <button type="button" class="btn btn-default" onclick="editcomissao(\''.$value['id'].'\')" data-toggle="modal" data-target="#comissaoModal"><i class="fa fa-pencil"></i></button>';
					$buttons .= ' <button type="button" class="btn btn-default" onclick="editobservacao(\''.$value['id'].'\')" data-toggle="modal" data-target="#observacaoModal"><i class="fa fa-sticky-note-o"></i></button>';

					if ( $value['observacao'] <> "" ){
						$observacao = ' <button type="button" class="btn btn-default" onclick="listarObservacao(\''.$value['id'].'\')" data-toggle="modal" data-target="#listaObs"><i class="fa fa-eye"></i></button>';
					}

					if ($value['tratado'] == 1) {
						
						$buttons .= ' <button type="button" class="btn btn-default" onclick="incluiremovepedidoconciliacao(\''.$value['id'].'\')"><i class="fa fa-minus"></i></button>';
						$status = $value['6'];

                    } else {

                        $buttons .= ' <button type="button" class="btn btn-default" onclick="incluiremovepedidoconciliacao(\''.$value['id'].'\')"><i class="fa fa-plus"></i></button>';
                        $status = $this->lang->line("application_payment_release_removed");

                    }

					$result['data'][$key][] = $value['1'];
                    $result['data'][$key][] = $value[6];
                    $result['data'][$key][] = $value['2'];
                    $result['data'][$key][] = '';
                    $result['data'][$key][] = $value['4'];
                    $result['data'][$key][] = $value['18']; //tipo pagamento
                    $result['data'][$key][] = $value['7']; //valor_pedido
                    $result['data'][$key][] = $value['23']; //valor_antecipado
                    $result['data'][$key][] = $value['8']; //valor_produto
                    $result['data'][$key][] = $value['9']; //valor frete
                    $result['data'][$key][] = $value['10']; //percentual comissao
                    $result['data'][$key][] = $value['15']; //valor repasse
                    if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                        $result['data'][$key][] = $value['25']; //parcelas
                    }
                    $result['data'][$key][] = $value['user'];
                    $result['data'][$key][] = $value['campaigns_pricetags'];
                    $result['data'][$key][] = $value['campaigns_campaigns'];
                    $result['data'][$key][] = $value['campaigns_mktplace'];
                    $result['data'][$key][] = $value['campaigns_seller'];
                    $result['data'][$key][] = $value['campaigns_promotions'];
                    $result['data'][$key][] = $value['campaigns_comission_reduction'];
                    $result['data'][$key][] = $value['campaigns_rebate'];
                    $result['data'][$key][] = $value['campaigns_refund'];
                    $result['data'][$key][] = $observacao;
                    $result['data'][$key][] = $buttons;

				} // /foreach

			} else {
				$result['data'][0] = array("","","","","","","","","","","","","","","","","","","","","","","");
				
                array_push($result['data'][0], ...array(""));
				
			}
		} else {

			$result['data'][0] = array("","","","","","","","","","","","","","","","","","","","","","","");
			
            array_push($result['data'][0], ...array(""));
			
		}

		$result = [
			'draw' => $post['draw'] ?? null,
			'recordsTotal' => $count,
			'recordsFiltered' => $count,
			'data' => $result['data']
		];



		echo json_encode($result);
	}

	private function generateArrayDataConciliationFiscal($hdnLote, $inputsGet, $length = null, $start = null): array
    {

        $result = [];

        $data = $this->model_billet->getConciliacaoSellerCenterFiscal($hdnLote,null,null,$length,$start,$inputsGet);

        if ($data) {

            foreach ($data as $key => $value) {

                $valor_repasse = 0.00;
                if($value['valor_repasse_ajustado'] <> "0.00"){
                    $valor_repasse = $value['valor_repasse_ajustado'];
                }else{
                    $valor_repasse = $value['valor_repasse'];
                }

                switch ($value['status_conciliacao']) {
                    case "Conciliação Cancelamento" :
                        $status = $this->lang->line("application_payment_release_cancel");
                        break;
                    case "Conciliação Ciclo" :
                        $status = $this->lang->line("application_payment_release_cycle");
                        break;
                    case "Conciliação Estorno de Comissão" :
                        $status = $this->lang->line("application_payment_release_legal_panel_cycle");
                        break;
					case "Conciliação Estorno de Comissão Cancelamento" :
						$status = $this->lang->line("application_payment_release_legal_panel_cycle_cancel");
						break;
					case "Conciliação Estorno de Comissão Devolução" :
						$status = $this->lang->line("application_payment_release_legal_panel_cycle_devolution");
						break;
					case "Conciliação Débito do Seller com o Marketplace" :
						$status = $this->lang->line("application_payment_release_legal_panel_cycle_debit_marketplace");
						break;
                    default:
                        $status = $value['status_conciliacao'];
                }

                // checa se o pedido tem valor antecipado
				$valor_antecipado = 0;

				// $n_mkt = $this->model_billet->getMarketplaceNumberFromOrderId($value['order_id']);
				// if(!is_null($n_mkt)){
				$valor_antecipado = $this->model_billet->getValueAnticipationTransfer($value['numero_marketplace']);
				// }

				// $campaign_data = $this->model_campaigns_v2->getCampaignsTotalsByOrderId($data[$key]['order_id']);

				$valor_percentual_produto = $value['valor_percentual_produto']."%";
				// $valor_percentual_produto .= (isset($campaign_data['comission_reduction']) && floatVal($campaign_data['comission_reduction']) > 0) ? '*' : '';
				$valor_percentual_produto .= (isset($value['comission_reduction']) && floatVal($value['comission_reduction']) > 0) ? '*' : '';

				/* $campaigns_pricetags                    = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'pricetags', true));
				$campaigns_campaigns                    = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'campaigns', true));
				$campaigns_mktplace                     = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'channel', true));
				$campaigns_seller                       = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'seller', true));
				$campaigns_promotions                   = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'promotions', true));
				$campaigns_rebate                       = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'rebate', true));
				$campaigns_comission_reduction          = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'comission_reduction', true));
				$campaigns_comission_reduction_products = ($this->model_campaigns_v2->getConciliationTotals($data[$key]['order_id'], 'comission_reduction_products', true));

				$campaigns_pricetags                    = (is_array($campaigns_pricetags)) ? $campaigns_pricetags['total'] : $campaigns_pricetags;
				$campaigns_campaigns                    = (is_array($campaigns_campaigns)) ? $campaigns_campaigns['total'] : $campaigns_campaigns;
				$campaigns_mktplace                     = (is_array($campaigns_mktplace)) ? $campaigns_mktplace['total'] : $campaigns_mktplace;
				$campaigns_seller                       = (is_array($campaigns_seller)) ? $campaigns_seller['total'] : $campaigns_seller;
				$campaigns_promotions                   = (is_array($campaigns_promotions)) ? $campaigns_promotions['total'] : $campaigns_promotions;
				$campaigns_rebate                       = (is_array($campaigns_rebate)) ? $campaigns_rebate['total'] : $campaigns_rebate;
				$campaigns_comission_reduction          = (is_array($campaigns_comission_reduction)) ? $campaigns_comission_reduction['total'] : $campaigns_comission_reduction;
				$campaigns_comission_reduction_products = (is_array($campaigns_comission_reduction_products)) ? $campaigns_comission_reduction_products['total'] : $campaigns_comission_reduction_products; */


				$campaigns_pricetags                    = 	$value['total_pricetags'];
				$campaigns_campaigns                    =	$value['total_campaigns'];
				$campaigns_mktplace                     =	$value['total_channel'];
				$campaigns_seller                       =	$value['total_seller'];
				$campaigns_promotions                   =	$value['total_promotions'];
				$campaigns_rebate                       =	$value['total_rebate'];
				$campaigns_comission_reduction          =	$value['comission_reduction'];
				$campaigns_comission_reduction_products =	$value['comission_reduction_products'];

                $valor_repasse_calc_refund          = (is_numeric($data[$key]['valor_repasse'])) ? $data[$key]['valor_repasse'] : 0;
                $valor_repasse_calc_refund_ajustado = (is_numeric($data[$key]['valor_repasse_ajustado'])) ? $data[$key]['valor_repasse_ajustado'] : 0;

                if ($this->setting_api_comission != "1"){
                    $campaigns_refund = (abs($valor_repasse_ajustado - $valor_repasse_calc_refund) > 0) ? round((abs($valor_repasse_calc_refund_ajustado - $valor_repasse_calc_refund)),2) : (0);
                }else {
                    $campaigns_refund = (abs($valor_repasse_calc_refund_ajustado - $valor_repasse_calc_refund - $campaigns_comission_reduction_products) > 0) ? round((abs($valor_repasse_calc_refund_ajustado - $valor_repasse_calc_refund - $campaigns_comission_reduction_products)),2) : (0);
                }

                $result[$key] = array(
                    0 => $value['order_id'],
                    1 => $value['numero_marketplace'],
                    2 => $value['seller_name'],
                    3 => $value['data_pedido'],
                    4 => $value['data_pago'],
                    5 => $value['data_ciclo'],
                    6 => $status,
                    7 => "R$ ".str_replace ( ".", ",", $value['valor_pedido'] ),
                    8 => "R$ ".str_replace ( ".", ",", $value['valor_produto'] ),
                    9 => "R$ ".str_replace ( ".", ",", $value['valor_frete'] ),
                    10 => $value['valor_percentual_produto']."%",
                    11 => $value['valor_percentual_frete']."%",
                    12 => "R$ ".str_replace ( ".", ",", $value['valor_comissao'] ),
                    13 => "R$ ".str_replace ( ".", ",", $value['valor_comissao_produto'] ),
                    14 => "R$ ".str_replace ( ".", ",", $value['valor_comissao_frete'] ),
                    15 => "R$ ".str_replace ( ".", ",", $valor_repasse ),
                    16 => "R$ ".str_replace ( ".", ",", $value['valor_repasse_produto'] ),
                    17 => "R$ ".str_replace ( ".", ",", $value['valor_repasse_frete'] ),
                    18 => $value['tipo_pagamento'],
                    19 => $value['taxa_cartao_credito'],
                    20 => $value['digitos_cartao'],
                    21 => $value['cnpj'],
                    22 => $value['data_report'],
                    23 => "R$ ".str_replace ( ".", ",", $valor_antecipado ),
                    24 => $value['store_id'],
                    25 => $value['current_installment']."/".$value['total_installments'],
                    'campaigns_pricetags' => "R$ ".str_replace ( ".", ",",  $campaigns_pricetags),
                    'campaigns_campaigns' => "R$ ".str_replace ( ".", ",",  $campaigns_campaigns),
                    'campaigns_mktplace' => "R$ ".str_replace ( ".", ",",  $campaigns_mktplace),
                    'campaigns_seller' => "R$ ".str_replace ( ".", ",",  $campaigns_seller),
                    'campaigns_promotions' => "R$ ".str_replace ( ".", ",",  $campaigns_promotions),
                    'campaigns_comission_reduction' => "R$ ".str_replace ( ".", ",",  $campaigns_comission_reduction),
                    'campaigns_rebate' => "R$ ".str_replace ( ".", ",",  $campaigns_rebate),
                    'campaigns_refund' => "R$ ".str_replace ( ".", ",", $campaigns_refund ),
                    'user' => $value['usuario'],
                    'observacao' => $value['observacao'],
                    'id' => $value['id'],
                    'order_id' => $value['order_id'],
                    'tratado' => $value['tratado'],
					'data_entrega' => $value['data_entrega']
                );

            }
        }else{
            $result[0] = array(
                0 => '',
                1 => '',
                2 => '',
                3 => '',
                4 => '',
                5 => '',
                6 => '',
                7 => '',
                8 => '',
                9 => '',
                10 => '',
                11 => '',
                12 => '',
                13 => '',
                14 => '',
                15 => '',
                16 => '',
                17 => '',
                18 => '',
                19 => '',
                20 => '',
                21 => '',
                22 => '',
                23 => '',
                24 => '',
                25 => '',
                'campaigns_pricetags' => '',
                'campaigns_campaigns' => '',
                'campaigns_mktplace' => '',
                'campaigns_seller' => '',
                'campaigns_promotions' => '',
                'campaigns_comission_reduction' => '',
                'campaigns_rebate' => '',
                'campaigns_refund' => '',
                'user' => '',
                'observacao' => '',
                'id' => '',
                'order_id' => '',
                'tratado' => '',
            );

        }
        return $result;

    }

	public function getconciliacaosellercentergridresumofiscal($lote = null){

		if($lote <> null){

			$this->load->model('model_campaigns_v2');
			$this->load->model('model_repasse');

			$data        = $this->model_billet->getConciliacaoSellerCenterFiscal($lote,"fiscalresumo");
			$i = 0;
			if($data){
				foreach($data as $liberacao){
					$result['data'][$i] = array(
						$liberacao['store_id'],
						$liberacao['seller_name'],
						"R$ ".$liberacao['valor_fiscal']);
					$i++;
				}
			}else{
				$result['data'][0] = array("","","");	
			}
			
		}else{
			$result['data'][0] = array("","","");
		}

		echo json_encode($result);
	}

	public function uploadarquivoconciliasellercenterfiscal(){
	    
	    if (!empty($_FILES)) 
        {
	        $group_data1 = $this->model_billet->getMktPlacesDataID($this->postClean('id'));
	        
	        $apelido = $group_data1[0]['apelido'];
	        
	        $exp_extens = explode( ".", $_FILES['product_upload']['name']) ;
	        $extensao = $exp_extens[count($exp_extens)-1];
	        
	        $lote=$this->postClean('lote');
	        $tempFile = $_FILES['product_upload']['tmp_name'];
	        
	        if ($_SERVER['SERVER_NAME'] == "localhost")
			{
				$root = getcwd();
				$caminhoMapeado = str_replace('\\', '/', $root.'/assets/docs/conciliacaofiscal/');
				
				if (!is_dir($caminhoMapeado))
					mkdir($caminhoMapeado, 0777, true);
			}
			else
			{
				//$caminhoMapeado = "/var/www/html/sicoob/app/assets/docs/";

				$root = getcwd();
				$caminhoMapeado = str_replace('\\', '/', $root.'/assets/docs/conciliacaofiscal/');
				
				if (!is_dir($caminhoMapeado))
					mkdir($caminhoMapeado, 0777, true);
			}
	        
	        $targetPath = $caminhoMapeado;
	        $targetFile =  str_replace('//','/',$targetPath) . $apelido .'-'. $lote . '.' . $extensao;
	        
	        move_uploaded_file($tempFile,$targetFile);
	        
	        $retorno['ret'] = "sucesso";
	        $retorno['extensao'] = $extensao;
	        
	        echo json_encode($retorno);
	    }
	    
	    
	}

	public function learquivoconciliacaosellercenterfiscal(){
	    $inputs = $this->postClean(NULL,TRUE);
		
	    $group_data1 = $this->model_billet->getMktPlacesDataID($inputs['slc_mktplace']);
	    $apelido = $group_data1[0]['apelido'];
	    
	    if ($_SERVER['SERVER_NAME'] == "localhost")
		{
			$root = getcwd();
			$caminhoMapeado = str_replace('\\', '/', $root.'/assets/docs/conciliacaofiscal/');
			
			if (!is_dir($caminhoMapeado))
				mkdir($caminhoMapeado, 0777, true);
		}
		else
		{
			//$caminhoMapeado = "/var/www/html/sicoob/app/assets/docs/";

			$root = getcwd();
			$caminhoMapeado = str_replace('\\', '/', $root.'/assets/docs/conciliacaofiscal/');
			
			if (!is_dir($caminhoMapeado))
				mkdir($caminhoMapeado, 0777, true);
		}

		$tipoArquivoVV = "Cartão";
	    
	    $arquivo = $apelido .'-'. $inputs['hdnLote'] . '.' . $inputs['hdnExtensao'];
	    $inputs['arquivo'] = $arquivo;

		$checkArquivo = false;

	    if( file_exists($caminhoMapeado.$arquivo) ){

			//Check se o arquivo de input é o ajuste de conciliação
			if($inputs['hdnExtensao'] == "xlsx"){
				
				$checkArquivo = true;

				error_reporting(1);
	            //import lib excel
	            require_once (APPPATH . '/third_party/PHPExcel/IOFactory.php');
	            $objPHPExcel = PHPExcel_IOFactory::load($caminhoMapeado.$arquivo);
	                
				$testeLinha = 1;
				
				foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
					
					$worksheetTitle     = $worksheet->getTitle();
					$highestRow         = $worksheet->getHighestRow(); // e.g. 10
					$highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
					$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
					$nrColumns = ord($highestColumn) - 64;
					
					//lê a linha
					for ($row = 1; $row <= $highestRow; ++ $row) {
						
						$valorColuna = 1;
						$arrayValores = array();
						
						//lê a coluna
						for ($col = 0; $col < $highestColumnIndex; ++ $col) {
							$cell = $worksheet->getCellByColumnAndRow($col, $row);
							$val = $cell->getValue();
							$arrayValores[$valorColuna] = $val;
							$valorColuna++;
						}
						
						
						if($testeLinha == "1"){
							
							$testeLinha ++;
							
							$cabecalho[1] = "Numero Pedido";
							$cabecalho[2] = "Regra Conciliação";
							$cabecalho[3] = "Valor Ajustado";
							$cabecalho[4] = "Justificativa";
							
							$count = 1;
							foreach ($arrayValores as $colunas){
								if( $colunas <> $cabecalho[$count]){
									$checkArquivo = false;
								}
								$count++;
							}
							
						}else{
							
							$testeLinha ++;

							if($checkArquivo){

								$save = $this->model_billet->atualizaValorConciliacaoSellerCenterArquivoMoveloFiscal($inputs,$arrayValores);
								if ($save == false){
									echo "Erro ao atualizar a tabela";die;
								}

							}
						}
					}
				}
			}

	    }else{
	        echo "Arquivo não encontrado";
	    }
	}

	public function cadastrarconciliacaosellercenterfiscal()
    {    
	    $inputs = $this->postClean(NULL,TRUE);

	    if ($inputs['slc_mktplace'] <> "" and $inputs['txt_ano_mes'] <> "" and $inputs['slc_ciclo'] <> "")
        {
			$inputs['slc_ano_mes'] = $inputs['txt_ano_mes'];
	        
	        //Verifica se já existe essa conciliação
	        $check = $this->model_billet->verificaconsiliacaoFiscal($inputs);
	        
            $this->load->model('model_campaigns_v2');
            $this->load->model('model_gateway');
            $payment_gateway_code = $this->model_gateway->getGatewayCodeById($this->payment_gateway_id);

            $orders = $this->model_billet->getOrdersFromConciliacaoSellercenterFiscal($inputs['hdnLote']);
	       
	        if ($check['qtd'] == 0) 
            {
	            //cadastra uma nova
    	        $data = $this->model_billet->criaconsiliacaofiscal($inputs);
				if($data)
                {
                    $carregaTemp = $this->model_billet->carregaTempRepasseSellercenterFiscal($inputs['hdnLote'], null, $payment_gateway_code);
					$data2 = $this->model_billet->cadastraRepasseFinalFiscal($inputs['hdnLote']);

                    $this->model_orders_conciliation_installments->setPaidByBatchFiscal($inputs['hdnLote']);

					if($data2)
						echo "0;Conciliação cadastrada com sucesso";
					else
						echo "1;Erro ao cadastrar conciliação $data";					
				}else
                    echo "1;Erro ao cadastrar conciliação $data";

	        }else{

				$inputs['slc_ano_mes'] = $inputs['txt_ano_mes'];

                //atualiza os dados da conciliação	            
	            $dataEdit = $this->model_billet->editaconsiliacaoFiscal($inputs);
	            if($dataEdit){

                    $this->model_orders_conciliation_installments->setPaidByBatchFiscal($inputs['hdnLote']);


                    $repasse_pago = $this->model_billet->getConciliacaoGridDataFiscal($inputs['hdnLote']);

                    if (is_array($repasse_pago) && $repasse_pago[0]['pagamento_conciliacao'] == 'Conciliação não paga') { // repasse ainda nao foi pago
                        $carregaTemp = $this->model_billet->carregaTempRepasseSellercenterFiscal($inputs['hdnLote'], null, $payment_gateway_code);
                        $data2 = $this->model_billet->cadastraRepasseFinalFiscal($inputs['hdnLote']);
                    }
                    echo "0;Conciliação cadastrada com sucesso";

	            }else{
	                echo "1;Erro ao atualizar conciliação $dataEdit";
	            }
	        }

	    }else{
	        echo "1;Erro ao cadastrar conciliação<br>Os campos não estavam todos preenchidos";
	    }
	    
	}

	public function exportaconciliacaosellercenterfiscal($hdnLote = null)
    {
        $this->load->model('model_campaigns_v2');
        $this->load->helper('money');

	    header("Pragma: public");
	    header("Cache-Control: no-store, no-cache, must-revalidate");
	    header("Cache-Control: pre-check=0, post-check=0, max-age=0");
	    header("Pragma: no-cache");
	    header("Expires: 0");
	    header("Content-Transfer-Encoding: none");
	    header("Content-Type: application/vnd.ms-excel;");
	    header("Content-type: application/x-msexcel;");
	    header("Content-Disposition: attachment; filename=liberacao_pagamento_$hdnLote.xls");

		$valorCartao = $this->model_settings->getSettingDatabyName('digitos_cartao_relatorio_fin');
		
		if(!$valorCartao){
			$valorCartao['status'] = "0";
		}
		
	    $input['lote'] = $hdnLote;


		$inputs = $this->input;
		$inputsGet = $inputs->get();

        $result = $this->generateArrayDataConciliationFiscal($hdnLote, $inputsGet);

        ob_clean();
	    echo '<table id="manageTableOrdersOk" class="table table-bordered table-striped" border="1" cellpadding="4">';
	    echo utf8_decode('<thead>
                            <tr>');
		if($valorCartao['status'] == "1"){
			echo utf8_decode('	<th colspan="19" bgcolor="blue" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>');
		}else{
			echo utf8_decode('	<th colspan="18" bgcolor="blue" class="text-center"><font color="#FFFFFF">Expectativa Calculada</font></th>');
		}
		echo utf8_decode('		<th colspan="3" bgcolor="red" class="text-center"><font color="#FFFFFF">Seller Center</font></th>
                            	<th colspan="4" bgcolor="#16F616" class="text-center"><font color="#FFFFFF">Seller</font></th>
                            	<th colspan="8" bgcolor="blue" class="text-center"><font color="#FFFFFF">Campanhas</font></th>
                            </tr>
                            <tr>
                                <th>Id Pedido</th>
                                <th>Número pedido VTEX</th>
								<th>Id Seller</th>
								<th>Seller</th>
								<th>CNPJ</th>
								<th>Data Pedido</th>
								<th>Data de Entrega</th>
								<th>Data Gatilho Liberação de Pagamento</th>
								<th>Data do pagamento</th>
								<th>'.$this->lang->line('application_payment_release').'</th>
								<th>Total Pedido</th>');								
								echo utf8_decode('<th>Valor Pago Antecipação</th>');
								echo utf8_decode('<th>Valor Produtos</th>
								<th>Valor Frete</th>
								<th>% Comissão sobre produto</th>
								<th>% Comissão sobre frete</th>
								<th>Tipo Pagamento</th>
								<th>MDR</th>');

		if($valorCartao['status'] == "1"){
			echo utf8_decode('	<th>'.$this->lang->line('application_card_number').'</th>');
		}

		echo utf8_decode('		<th>Total Comissão</th>
								<th>Comissão Produto</th>
								<th>Comissão Frete</th>
								<th>Total Repasse</th>
								<th>Repasse Produto</th>
								<th>Repasse Frete</th>
								<th>'.$this->lang->line('application_parcel').'</th>
								
                                <th>'.$this->lang->line('conciliation_sc_gridok_pricetags').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_campaigns').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_totalmktplace').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_totalseller').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_promotions').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_comissionredux').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_rebate').'</th>
                                <th>'.$this->lang->line('conciliation_sc_gridok_refund').'</th>
                            </tr>
                           </thead>');
	    
	    foreach($result as $k => $value) {

	        echo "<tr>";
	        echo utf8_decode("<td>".$value[0]."</td>");
	        echo utf8_decode("<td>".$value[1]."</td>");
			echo utf8_decode("<td>".$value[24]."</td>");
	        echo utf8_decode("<td>".$value[2]."</td>");
			echo utf8_decode("<td>&nbsp;".$value[21]."</td>");
			echo utf8_decode("<td>".$value[3]."</td>");
			echo utf8_decode("<td>".$value['data_entrega']."</td>");
			echo utf8_decode("<td>".$value[5]."</td>");
			echo utf8_decode("<td>".$value[22]."</td>");
			echo utf8_decode("<td>".$value[6]."</td>");
			echo utf8_decode("<td>".$value[7]."</td>");
			echo utf8_decode("<td>".$value[23]."</td>");
			echo utf8_decode("<td>".$value[8]."</td>");
			echo utf8_decode("<td>".$value[9]."</td>");
			echo utf8_decode("<td>".$value[10]."</td>");
			echo utf8_decode("<td>".$value[11]."</td>");
			echo utf8_decode("<td>".$value[18]."</td>");
			echo utf8_decode("<td>".$value[19]."</td>");
			if($valorCartao['status'] == "1"){
				echo utf8_decode("<td>".$value[20]."</td>");
			}
			echo utf8_decode("<td>".$value[12]."</td>");
			echo utf8_decode("<td>".$value[13]."</td>");
			echo utf8_decode("<td>".$value[14]."</td>");
			echo utf8_decode("<td>".$value[15]."</td>");
			echo utf8_decode("<td>".$value[16]."</td>");
			echo utf8_decode("<td>".$value[17]."</td>");
			echo utf8_decode("<td>&nbsp;".$value[25]."</td>");
            echo utf8_decode("<td>".$value['campaigns_pricetags']."</td>");
            echo utf8_decode("<td>".$value['campaigns_campaigns']."</td>");
            echo utf8_decode("<td>".$value['campaigns_mktplace']."</td>");
            echo utf8_decode("<td>".$value['campaigns_seller']."</td>");
            echo utf8_decode("<td>".$value['campaigns_promotions']."</td>");
            echo utf8_decode("<td>".$value['campaigns_comission_reduction']."</td>");
            echo utf8_decode("<td>".$value['campaigns_rebate']."</td>");
            echo utf8_decode("<td>".$value['campaigns_refund']."</td>");

	        echo "</tr>";

	    }
	    
	    echo '</table>';
	    
	}

	public function salvarobssellercenterfiscal(){
	    
	    $inputs = $this->postClean(NULL,TRUE);

	    if($inputs['txt_hdn_pedido_obs'] <> "" && $inputs['txt_observacao'] <> "" && $inputs['hdnLote'] <> "" ){
	        $data = $this->model_billet->salvaObservacaosellercenterfiscal($inputs);
	        if($data){
    	        echo "0;Observação cadastrada com sucesso";
	        }else{
	            echo "1;Erro ao cadastrar observação.";
	        }
	    }
	    
	}

	public function alteracomissaosellercenterfiscal(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
		 
	    if($inputs['txt_comissao'] <> "" && $inputs['txt_hdn_pedido_comissao'] <> "" && $inputs['hdnLote'] <> "" ){
	        $data = $this->model_billet->alteracomissaosellercenterfiscal($inputs);
	        if($data){
    	        echo "0;Observação cadastrada com sucesso";
	        }else{
	            echo "1;Erro ao cadastrar observação.";
	        }
	    }
	    
	}

	public function buscaobservacaopedidosellercenterfiscal(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    if($inputs['pedido'] <> "" && $inputs['lote'] <> "" ){
	        $data = $this->model_billet->buscaobservacaopedidosellercenterFiscal($inputs['lote'], $inputs['pedido'], 1);
	    }else{
	        $data[0] = array("","","");
	    }
	    
	    echo json_encode($data);
	    
	}

	public function incluiremovepedidoconciliacaofiscal(){
	    
	    $inputs = $this->postClean(NULL,TRUE);

	    if($inputs['id'] <> "" && $inputs['hdnLote'] <> ""){
	        $data = $this->model_billet->incluiremovepedidoconciliacaosellercenterFiscal($inputs);
	        if($data){
    	        echo "0;Pedido tratado com sucesso";
	        }else{
	            echo "1;Erro ao tratar pedido.";
	        }
	    }
	    
	}

	public function editsellercenterfiscal($lote){
	    
	    
	    $group_data1 = $this->model_billet->getMktPlacesData();
	    $group_data2 = $this->model_parametrosmktplace->getReceivablesDataCicloFiscal();
	    $obsFixo = $this->model_billet->getDataObservacaoFixaPedido();
		$group_data3 = $data = $this->model_billet->getConciliacaoGridDataFiscal($lote);
		$group_data3[0]['carregado'] = 1;
		
	    $this->data['mktplaces'] = $group_data1;
	    $this->data['ciclo'] = $group_data2;
	    $this->data['hdnLote'] = $lote;
	    $this->data['dadosBanco'] = $group_data3[0];
	    $this->data['obsFixo'] = $obsFixo;
        $this->data['negociacao_marketplace_campanha'] = ($this->negociacao_marketplace_campanha);
        $this->data['canceled_orders_data_conciliation'] = ($this->canceled_orders_data_conciliation);

		$flagPago = false;
		$flagPago = $this->model_billet->getConciliacaoPagaFlagFiscal($lote);

		$this->data['flag_pago'] = $flagPago;

		$this->data['page_title'] = 'Conciliação';
	    
	    $this->render_template('billet/createsellercenterfiscal', $this->data);
	}

	public function payconciliationsellercenterfiscal($lote = null){

		$flagrepasse = $this->model_settings->getSettingDatabyName('flag_liberacao_repasse_conciliacao');
		if(!$flagrepasse){
				$this->session->set_flashdata('error', 'Você não possui permissão para essa ação!');
				redirect('billet/listsellercenter', 'refresh');
		}

		//Busca dados da conciliacao
		$dadosConciliacao = $this->model_billet->getConciliacaoGridDataFiscal($lote);
		$dadosConciliacao = $dadosConciliacao[0];

		//Verifica se já foi pago
		$checkIugu = $this->model_billet->iugurepassecheckfiscal($dadosConciliacao['id_con']);

		if($checkIugu == 0){

            $this->model_billet->updateStatusRepasseConciliationFiscal($lote);

			//busca conciliação para pagar
			$pedidos = $this->model_billet->getConciliacaoSellerCenterFiscal($lote);

            //dá baixa em painel juridico
            $this->load->model('model_repasse');

            $legal_panel_transfers = $this->model_repasse->getLegalPanelTransfersByLotFiscal($lote);

            if ($legal_panel_transfers)
            {
                foreach ($legal_panel_transfers as $transfer)
                {
                    $this->model_repasse->updateTransferLegalCloseLegalPanelFiscal($transfer['legal_panel_id']);
                }
            }

			if($pedidos){

				foreach($pedidos as $pedido){
					
					$dataPhp = date("Y-m-d h:m:s", time());
					
					$repasse = "";
					if($pedido['valor_repasse_ajustado'] == "0.00"){
						$repasse = $pedido['valor_repasse'];
					}else{
						$repasse = $pedido['valor_repasse_ajustado'];
					}
					//insere na IUGU repasse
					$data['order_id'] = $pedido['order_id'];
					$data['numero_marketplace'] = $pedido['numero_marketplace'];
					$data['data_split'] = $dataPhp;
					$data['data_transferencia'] = $dataPhp;
					$data['data_repasse_conta_corrente'] = $dataPhp;
					$data['conciliacao_fiscal_id'] = $dadosConciliacao['id_con'];
					$data['valor_parceiro'] = $repasse;
					$data['valor_afiliado'] = '0.00';
					$data['valor_produto_conectala'] = '0.00';
					$data['valor_frete_conectala'] = '0.00';
					$data['valor_repasse_conectala'] = '0.00';
                    $data['current_installment'] = $pedido['current_installment'] ?? 1;
                    $data['total_installments'] = $pedido['total_installments'] ?? 1;
                    $data['total_paid'] = $pedido['total_paid'] ?? 0;

					$this->model_billet->insertIuguRepasseFiscal($data);

					$this->log_data(
						'general',
						__FUNCTION__,
						"Setou os pedidos da conciliação de lote número ".$lote." como pagos!",
						"I"
					);
					
					$this->log_data(
						'general',
						__FUNCTION__,
						"Setou os pedidos da conciliação de lote número ".$lote." como pagos!",
						"I"
					);
					
				}
				$this->session->set_flashdata('success', 'Conciliação atualizada com sucesso!');
				redirect('billet/listsellercenterfiscal', 'refresh');
			}else{
				$this->session->set_flashdata('error', 'Não foram encontrados pedidos para essa conciliação!');
				redirect('billet/listsellercenterfiscal', 'refresh');
			}
			
		}else{
			$this->session->set_flashdata('error', 'Essa conciliação já foi paga!');
			redirect('billet/listsellercenterfiscal', 'refresh');
		}
		

	}

}
