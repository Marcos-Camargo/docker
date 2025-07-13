<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "libraries/CalculoFrete.php";

class TrackingStatusReceived extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_settings');
        $this->load->model('model_shipping_company');
        $this->load->model('model_shipping_tracking_occurrence');
        $this->calculoFrete = new CalculoFrete();
    }

    /**
     * Ocorrências devem ser recebidas via POST
     */
    public function index_put()
    {
        $status = json_decode(file_get_contents('php://input'));
        $this->log_data('api', 'WebHookStatusReceipt', 'Chegou PUT, não deveria - GET='.json_encode($_GET).' - PAYLOAD='.json_encode($status), "E");
        return $this->response(NULL,REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Ocorrências devem ser recebidas via POST
     */
    public function index_get()
    {
        $this->log_data('api', 'WebHookStatusReceipt', 'Chegou GET, não deveria - GET='.json_encode($_GET), "E");
        return $this->response(NULL,REST_Controller::HTTP_UNAUTHORIZED);
    }

    /**
     * Recebimento de ocorrências
     */
    public function index_post()
    {   
        //date_default_timezone_set('America/Sao_Paulo');
        
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        $trackingStatus = json_decode(file_get_contents('php://input'),true);

        $this->log_data('api', $log_name, "Chegou request Panex.\n\n body=".json_encode($trackingStatus)."\n\n header=".json_encode(getallheaders()), "I");
        $authentication_token = $this->model_settings->getSettingDatabyName('panex_authentication_token')['value'];
        
        // GET header authentication_token
        $received_authentication_token = getallheaders()['authentication_token'] ?? null;
        if (!$received_authentication_token) {
            $this->log_data('api', $log_name, "Authentication_token não encontrado.\n\n body=".json_encode($trackingStatus)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response(array(
                'requisition_ID' => $trackingStatus['requisition_ID'], 
                'status' => 'Erro na autenticação, o campo authentication_token no header está vazio', 
                'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_UNAUTHORIZED);
        }

        // valida authentication_token
        if ($received_authentication_token != $authentication_token ) {
            $this->log_data('api', $log_name, "Authentication_token não está relacionado no banco de dados.\n\n body=".json_encode($trackingStatus)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response(array(
                'requisition_ID' => $trackingStatus['requisition_ID'], 
                'status' => 'Erro na autenticação, authentication_token é inválido ', 
                'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_UNAUTHORIZED);
        }

        // valida o body do json
        if (!$trackingStatus) {
            $this->log_data('api', $log_name, "Não existem dados válidos no body do JSON.\n\n body=".file_get_contents('php://input')."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response(array(
                'requisition_ID' => $trackingStatus['requisition_ID'], 
                'status' => 'Dados inválidos no body, JSON vazio ou com formatação incorreta', 
                'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_UNAUTHORIZED);
        }
  
        if (empty($trackingStatus['requisition_ID'])) { 
            $this->log_data('api', $log_name, "Requisition ID vazio. Não foi possível criar o registro na tabela.\n\n body=".json_encode($trackingStatus)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response(array(
                'requisition_ID' => $trackingStatus['requisition_ID'], 
                'status' => 'Erro no recebimento, requisition_ID vazio', 
                'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_UNAUTHORIZED);
        }

        if (empty($trackingStatus['docDestinatario'])) { 
            $this->log_data('api', $log_name, "docDestinatario vazio. Não foi possível criar o registro na tabela.\n\n body=".json_encode($trackingStatus)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response(array(
                'requisition_ID' => $trackingStatus['requisition_ID'], 
                'status' => 'Erro no recebimento, docDestinatario vazio', 
                'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_UNAUTHORIZED);
        }
        if (empty($trackingStatus['ocorrencia']['codigo'])) { 
            $this->log_data('api', $log_name, "codigo vazio. Não foi possível criar o registro na tabela.\n\n body=".json_encode($trackingStatus)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response(array(
                'requisition_ID' => $trackingStatus['requisition_ID'], 
                'status' => 'Erro no recebimento, codigo vazio', 
                'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_UNAUTHORIZED);
        }
               
        //valida se o requisition id já foi enviado, para não receber registros repetidos
        $verify_ID = $this->model_shipping_tracking_occurrence->selectByRequisitionID($trackingStatus['requisition_ID']);
        if($verify_ID) {
            $this->log_data('api', $log_name, "Já existe um registro com esse requisition ID.\n\n body=".json_encode($trackingStatus)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response(array(
                'requisition_ID' => $trackingStatus['requisition_ID'], 
                'status' => 'Já existe um registro com esse requisition Id em nosso banco de dados ', 
                'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_UNAUTHORIZED);
        }

        $ship_company_name = $this->model_shipping_company->getShippingCompanySellerCenterByCnpj($trackingStatus['cnpjTransportadora'])['name'];
        
        //$trackingStatus['ocorrencia']['linkComprovante'] = urldecode($trackingStatus['ocorrencia']['linkComprovante']);

        $tracking_data = array(
            'requisition_ID'        => $trackingStatus['requisition_ID'],
            'register_date'         => date('Y-m-d H:i:s'),
            'tracking_code'         => $trackingStatus['nf']['numeroNFe'],
            'ship_company'          => $ship_company_name,
            'ship_company_CNPJ'     => preg_replace('/\D/', '', $trackingStatus['cnpjTransportadora']),            
            'payer_CNPJ'            => preg_replace('/\D/', '', $trackingStatus['cnpjPagador']),  
            'recipient_doc'         => preg_replace('/\D/', '', $trackingStatus['docDestinatario']), 
            'nfe_serie'             => $trackingStatus['nf']['serieNFe'],      
            'nfe_num'               => $trackingStatus['nf']['numeroNFe'],      
            'nfe_chave'             => $trackingStatus['nf']['chaveNFe'],  
            'pedido'                => $trackingStatus['nf']['pedido'],
            'cte_serie'             => $trackingStatus['cte']['serieCTe'],         
            'cte_num'               => $trackingStatus['cte']['numeroCTe'],              
            'cte_chave'             => $trackingStatus['cte']['chaveCTe'], 
            'occurrence_date'       => $trackingStatus['ocorrencia']['dataHoraEvento'],                  
            'codigo'                => $trackingStatus['ocorrencia']['codigo'],
            'description_code'      => $trackingStatus['ocorrencia']['descricao'],  
            'complete_description'  => $trackingStatus['ocorrencia']['complemento'],      
            'receiver_name'         => $trackingStatus['ocorrencia']['nomeRecebedor'],          
            'receiver_doc'          => $trackingStatus['ocorrencia']['docRecebedor'],     
            'method'                => 'Panex',     
            'delivery_receipt_link' => $trackingStatus['ocorrencia']['linkComprovante']          
        );

        $create_data = $this->model_shipping_tracking_occurrence->create($tracking_data);      
        if($create_data == False) {
            $this->log_data('batch', $log_name, "Não foi possível criar o registro na tabela.\n\n body=".json_encode($trackingStatus)."\n\n header=".json_encode(getallheaders()), "E");
            return $this->response(array(
                'requisition_ID' => $trackingStatus['requisition_ID'], 
                'status' => 'Não foi possível criar o registro na tabela do banco de dados', 
                'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_UNAUTHORIZED);
        }
        return $this->response(array(
            'requisition_ID' => $trackingStatus['requisition_ID'], 
            'status' => 'Ocorrência recebida com sucesso', 
            'requisition_date' => date('Y-m-d H:i:s')),REST_Controller::HTTP_OK);
    }
}