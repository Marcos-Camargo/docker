<?php
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Fornecedores

*/

class Model_providers extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * get the providers dat
     *
     * @param null $id
     * @return mixed
     */
    public function getProviderData($id = null)
    {

        
        if($id) {
            $sql = "SELECT * FROM providers WHERE id = ?";
            $query = $this->db->query($sql, array($id));
            return $query->row_array();
        }

        $sql = "SELECT * FROM providers";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
    
    public function getProviderToSellerData($provider_id = null)
    {        
        if($provider_id) {
            $sql = "SELECT * FROM providers_to_seller WHERE provider_id = ?";
            $query = $this->db->query($sql, array($provider_id));
            return $query->row_array();
        }

        $sql = "SELECT * FROM providers_to_seller";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * Create row database
     *
     * @param array $data
     * @return bool
     */
    public function create($data = array())
    {
        if(count($data) > 0) {

            $company_id = isset($data['company_id']) ? $data['company_id'] : $this->session->userdata['usercomp'];
            unset($data['company_id']);
            $create = $this->db->insert('providers', $data);
            $provider_id = $this->db->insert_id();

            /*$providerToSeller = array(
                'provider_id'  => $provider_id
                ,'store_id'    => $data['store_id']
                ,'company_id'  => $company_id
                ,'user_id'    => $this->session->userdata['id']
           );
           $this->bondProvidertoSeller($providerToSeller);*/
            // SW - Log Create
            get_instance()->log_data('Providers','Create',json_encode($data),"I");
            return ($create == true) ? $provider_id : false;
        }
        return false;
    }
    
    /**
     * Create row database
     *
     * @param array $data
     * @return bool
     */
    public function createsimplified($data = array())
    {
        if(count($data) > 0) {
            $create = $this->db->insert('providers', $data);
            $provider_id = $this->db->insert_id();
            $providerToSeller = array(
                 'provider_id'  => $provider_id
                ,'store_id'    => $data['store_id']
                ,'company_id'    => $this->session->userdata['usercomp']
                ,'user_id'    => $this->session->userdata['id']
            );
            $this->bondProvidertoSeller($providerToSeller);
            // SW - Log Create
            get_instance()->log_data('Providers','Create',json_encode($data),"I");
            return ($create == true) ? $provider_id : false;
        }

        return false;
    }

    /**
     * Update row database
     *
     * @param array $data
     * @param null $id
     * @return bool
     */
    public function updatesimplified($data = array(), $id = null)
    {
        $this->db->where('id', $id);
        $update = $this->db->update('providers', $data);        
        get_instance()->log_data('Providers','edit after',json_encode($data),"I");
        return ($update == true) ? true : false;
    }
    
    /**
     * Insert row database
     *
     * @param array $data    
     * @return bool
     */
    public function bondProvidertoSeller($data = array())
    {   
        // echo "<pre>";
        // var_dump($this->getProvidertoSeller($data));
        // exit;
        // dd($data);
        $data['dt_cadastro'] = date("Y-m-d H:i:s");
        $providerToSeller = $this->getProvidertoSeller($data);
        // dd($providerToSeller);
        if ($providerToSeller && count($providerToSeller) > 0) {
            $this->db->where('idproviders_to_seller', $providerToSeller['idproviders_to_seller']);
            $providerToSeller = $this->db->update('providers_to_seller', $data);
        } else {
            $providerToSeller = $this->db->insert('providers_to_seller', $data);
        }
        
        get_instance()->log_data('Providers','edit after',json_encode($data),"I");
        return ($providerToSeller == true) ? true : false;
    }

    /**
     * Get data
     *
     * @param array $data
     * @param null $id
     * @return bool
     */
    public function getProvidertoSeller($data = array())
    {   
        if(is_array($data['store_id'])) {
            $storesId = implode("','", $data['store_id']);
        } else {
            $storesId = $data['store_id'];
        }

        $sql = "SELECT * FROM providers_to_seller ps
                INNER JOIN providers p ON p.id = ps.provider_id
                WHERE p.id = ".$data['provider_id']." AND ps.store_id IN ( ".$storesId." );
                ";
        $query = $this->db->query($sql);
        return (count($query->result_array()) > 0 ? $query->row_array() : false);
        
    }

    /**
     * Update row database
     *
     * @param array $data
     * @param null $id
     * @return bool
     */
    public function update($data = array(), $id = null)
    {
        $company_id = isset($data['company_id']) ? $data['company_id'] : $this->session->userdata['usercomp'];
        unset($data['company_id']);

        $this->db->where('id', $id);
        $update = $this->db->update('providers', $data);

        /*$providerToSeller = array(
            'provider_id'  => $id
            ,'store_id'    => $data['store_id']
            ,'company_id'  => $company_id
            ,'user_id'    => $this->session->userdata['id']
        );
        $this->bondProvidertoSeller($providerToSeller);*/

        // SW - Log Update
        get_instance()->log_data('Providers','edit after',json_encode($data),"I");
        return ($update == true) ? true : false;
    }

    /**
     * get the providers for CNPJ
     *
     * @param null $id
     * @return mixed
     */
    public function getProviderDataForCnpj($cnpj)
    {
        if($cnpj) {

            $cnpj_format   = strlen($cnpj) == 14 ?
                preg_replace("/([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{4})([0-9]{2})/", "$1.$2.$3/$4-$5", $cnpj) :
                $cnpj;

            $sql = "SELECT * FROM providers WHERE cnpj = '{$cnpj_format}' OR cnpj = '{$cnpj}'";
            $query = $this->db->query($sql);
            return $query->row_array();
        }

        return false;
    }
    
    public function getProvidersIndifcacao($id = null, $transportadora = null, $loja = null){
        
        $sql = "SELECT  PI.id AS id_pi,
	                    PI.store_id AS id_loja,
                    	PI.*,
                    	P.name AS ship_company,
                    	P.*,
                    	S.name AS loja,
                    	S.* 
                FROM providers_indicacao PI
                INNER JOIN providers P ON P.id = PI.providers_id
                LEFT JOIN stores S ON S.id = PI.store_id
                where 1=1";
        
        if($id){
            $sql .= " and PI.id = $id";
        }
        
        if($transportadora){
            $sql .= " and PI.providers_id = $transportadora";
        }
            
        if($loja){
            $sql .= " and PI.store_id = $loja";
        }

        $sql .= " order by PI.id";
        
        $query = $this->db->query($sql);
        return $query->result_array();
        
    }
    
    public function salvaIndicacaoTransp($inputs){
        
        $data['providers_id']           = $inputs['slc_transportadora_new'];
        $data['store_id']               = $inputs['slc_loja_new'];
        $data['percentual_desconto']    = $inputs['txt_desconto'];
        
        $create = $this->db->insert('providers_indicacao', $data);
        $provider_id = $this->db->insert_id();
        return $provider_id;
    }
    
    public function removerIndicacaoTransp($inputs){
        
        $sql = "delete from providers_indicacao where id = ".$inputs['txt_hdn_id_remove'];
        return $this->db->query($sql);
        
    }
    
    public function editarIndicacaoTransp($inputs){
        
        $data['providers_id']           = $inputs['slc_transportadora'];
        $data['store_id']               = $inputs['slc_loja'];
        $data['percentual_desconto']    = $inputs['txt_desconto'];
        $data['ativo']                  = $inputs['txt_ativo'];
        
        $this->db->where('id', $inputs['txt_hdn_id']);
        return $this->db->update('providers_indicacao    ', $data);
        
    }

	public function getProviderByTypeData($type)
    {

        $sql = "SELECT * FROM providers WHERE tipo_fornecedor=?";
        $query = $this->db->query($sql,array($type));
        return $query->result_array();
    }

    //Busca os dados pelo CNPJ quando ele vem com ou sem caracteres especiais
    public function getProviderDataByCNPJ($cnpj)
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        $sql = "SELECT * FROM providers where replace(replace(replace(cnpj, '/', ''),'.','' ),'-','' ) = ?";
        $query = $this->db->query($sql, array($cnpj));
        $row = $query->row_array();
        return $row;
    }
  
	public function getProviderByStoreId($storeId)
    {
        // $sql = "SELECT * FROM providers WHERE store_id = '$storeId';";        
        $sql = "SELECT
                     p.id
                    ,p.razao_social
                    ,p.name as provider_name
                    ,p.active
                    ,p.store_id
                    ,ps.store_id
                    ,c.name as company_name
                    ,ps.company_id
                    ,s.name as store_name
                FROM providers as p
                INNER JOIN providers_to_seller as ps ON ps.provider_id = p.id
                INNER JOIN company as c ON c.id = ps.company_id
                INNER JOIN stores as s ON s.id = p.store_id
                WHERE p.store_id = '$storeId';";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProvidersByRegister()
    {
        $more = ($this->data['usercomp'] == 1) ? "p.store_id IS NULL" : (
            ($this->data['userstore'] == 0) ? " ps.company_id = " . $this->data['usercomp'] : " ps.store_id = " . $this->data['userstore']
        );
        $sql = "SELECT p.*, ps.company_id 
                FROM providers p
                LEFT JOIN providers_to_seller ps ON ps.provider_id = p.id
                WHERE {$more} AND
                    p.tipo_fornecedor = 'Transportadora'";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getProvidersByStore($store)
    {
        $more = ($this->data['usercomp'] == 1) ? "(p.store_id IS NULL OR p.store_id = {$store})" : "ps.store_id = {$store}";
        $sql = "SELECT p.*, ps.company_id 
                FROM providers p
                LEFT JOIN providers_to_seller ps ON ps.provider_id = p.id
                WHERE {$more} AND
                    p.tipo_fornecedor = 'Transportadora'";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * get the providers active data
     * @param string $select Campos para serem selecionados.
     *
     * @return array
     */
    public function getDataProviderActive(string $select): array
    {
        return $this->db->select($select)->where('active', true)->get('providers')->result_array();
    }
}