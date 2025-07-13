<?php

// Esta API foi criada para receber os dados de uma empresa 
// da plataforma Shopify para criação de novas lojas.
// O email é enviado para o time de operações

require APPPATH . "controllers/Api/V1/API.php";

class ShopifyCreateNewStore extends API
{
  	public function __construct()
	{
		parent::__construct();
		
		$this->load->model('model_settings');
		$this->load->model('Model_shopify_new_stores');	
        $this->load->model('Model_company');	
				
	}

    public function index_get()
    {
        
        return $this->response("Método Get não implementado", REST_Controller::HTTP_UNAUTHORIZED);
        
    }

    public function index_post()
    {
        $json_data               = json_decode(file_get_contents('php://input'), true);
        $store_data              = $json_data['store'];
        $collection_address_data = $json_data['store']['collection_address'];
        $business_address_data   = $json_data['store']['business_address'];
        $responsible_data        = $json_data['store']['responsible'];

        //Verifica se o CNPJ está no database e em caso positivo retorna os dados da empresa        
        //$company_data = $this->Model_company->getCompanyByCNPJ($store_cnpj);
        $company_data = $this->Model_company->getCompanyData($store_data['company_id']);

        if ($company_data['id'] == []) {
            return $this->response( 
            array(
                "success" => false,
                "message" => "Company ID não cadastrado no banco de dados, solicitação de criação de loja recusada"
            ), 
            REST_Controller::HTTP_BAD_REQUEST);
        }
        
        $data = array( 
            "store_name"              => $store_data['store_name'], 
            "company_name"            => $company_data['name'],     
            "company_id"              => $store_data['company_id'],    
            "company_CNPJ"            => $company_data['CNPJ'],
            "company_raz_social"      => $store_data['corporate_name'],
            "tel1"                    => $store_data['tel1'],          
            "tel2"                    => $store_data['tel2'],          
            "collection_address"      => $collection_address_data['address'],
            "collection_number"       => $collection_address_data['number'],        
            "collection_complement"   => $collection_address_data['complement'],       
            "collection_neighborhood" => $collection_address_data['neighborhood'],       
            "collection_city"         => $collection_address_data['city'],   
            "collection_state"        => $collection_address_data['state'],       
            "collection_country"      => $collection_address_data['country'],       
            "collection_zipcode"      => $collection_address_data['zipcode'],      
            "business_address"        => $business_address_data['address'],     
            "business_number"         => $business_address_data['number'],             
            "business_complement"     => $business_address_data['complement'],         
            "business_neighborhood"   => $business_address_data['neighborhood'],       
            "business_city"           => $business_address_data['city'],      
            "business_state"          => $business_address_data['state'],          
            "business_country"        => $business_address_data['country'],            
            "business_zipcode"        => $business_address_data['zipcode'],            
            "responsible_name"        => $responsible_data['name'],        
            "responsible_email"       => $responsible_data['email'],            
            "shopify_id"              => $store_data['shopify_id'],
            "creation_link"           => 'stores/create/'.$store_data['company_id']
        );
                     
        //Se o company Id existir, registra o histórico do email no banco de dados
        //bloquear o mesmo shopify_id
        
        $create_data = $this->Model_shopify_new_stores->create($data);  
        if ($create_data == false) {
            return $this->response(array(
                "success" => false,
                "message" => "Não foi possível cadastrar os dados da nova loja no banco de dados, favor tentar novamente"
            ), 
            REST_Controller::HTTP_BAD_REQUEST);
        }
        else{ $this->response(
            array(
                "success" => true,
                "message" => "Solicitação de criação de loja recebida com sucesso! Em breve o time comercial entrará em contato via email ou telefone, obrigado!"
                )
            );
        }

        $company = $this->Model_company->getCompanyData(1);

        $data = array_merge ($data, array(
            "gestor"        => $company_data['gestor'],
            "url"           => 'stores/create/'.$data['company_id'],
            "logo"          => base_url().$company['logo']
        )
        );

        $from = $this->model_settings->getValueIfAtiveByName('email_marketing');
        if (!$from) {
            $from = 'marketing@conectala.com.br';
        }
        $to[] = "atendimento@conectala.com.br";
        
        $subject = 'Cadastramento de nova loja Shopify';

		$sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
        if (!$sellercenter) {
            $sellercenter = 'conectala';
        }
        $sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
        if (!$sellercenter_name) {
            $sellercenter_name = 'Conecta Lá';
        }
        $data['sellercentername'] = $sellercenter_name;
        if (is_file(APPPATH.'views/mailtemplate/'.$sellercenter . '/create_new_store_email.php')) {
            $body= $this->load->view('mailtemplate/'.$sellercenter.'/create_new_store_email',$data,TRUE);
        }
        else {
            $body= $this->load->view('mailtemplate/default/create_new_store_email',$data,TRUE);
        }
                
        //Envia o email com o CNPJ e email da empresa para o time de operações
        $this->sendEmailMarketing($to,$subject,$body,$from);
 
    }

    
}