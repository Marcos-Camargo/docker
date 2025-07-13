<?php
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Fornecedores

*/

/**
 * @property $model_settings
 * @property CI_Loader $load
 */
class Model_shipping_company extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_settings');
    }

    /**
     * get the providers dat
     *
     * @param null $id
     * @return mixed
     */
    public function getProviderData()
    {   
        $sql = "SELECT 
                 p.*,co.raz_social as raz_social
             FROM providers_to_seller as pts 
             INNER JOIN shipping_company as p on pts.provider_id = p.id
             INNER JOIN users as u on pts.user_id = u.id
             INNER JOIN company as co on u.company_id = co.id
             WHERE p.deleted = 0;";

        $query = $this->db->query($sql); 
        // dd($query->result_array());           
        return $query->result_array();
    }
    
    public function getProviderUsersSeller()
    {   
        $sql = "SELECT 
                 u.id
                 , CONCAT (u.firstname, ' ', u.lastname ) as fullname
                 , u.company_id
             FROM providers_to_seller as pts 
             INNER JOIN shipping_company as p on pts.provider_id = p.id
             INNER JOIN users as u on pts.user_seller_id = u.id
             INNER JOIN company as co on u.company_id = co.id
             WHERE p.deleted = 0 GROUP BY u.id;";

        $query = $this->db->query($sql); 
        // dd($query->result_array());           
        return $query->result_array();
    }

    /**
     * Recupera as integrações da loja.
     *
     * @param   int         $store  Código da loja.
     * @return  array|null
     */
    public function getIntegrationLogistic(int $store, int $status = null): ?array
    {
        $sql = "select
                    il.id,
                    ils.description,
                    il.active,
                    il.integration,
                    il.credentials,
                    il.id_integration,
                    ils.id as id_ils,
                    ils.use_seller,
                    il.store_id,
                    s.name as store_name,
                    il.external_integration_id
                FROM integration_logistic as il
                INNER JOIN integrations_logistic as ils ON (il.id_integration = ils.id)
                LEFT JOIN stores as s ON (s.id = il.store_id)
                WHERE il.store_id = ?";

        if ($status !== null) {
            $sql .= " AND il.active = $status";
        }

        $query = $this->db->query($sql, array($store));
        return $query->result_array();
    }
    
    public function updateKeyIntegration($data = array(), $store = null) {
        $backup = $this->db->where('store_id', $store)->get('integration_logistic')->row_array();
        $this->db->where('store_id', $store);
        if ($this->db->update('integration_logistic', $data)) {
            return $backup;
        }

        return false;
    }

    public function getIntegrationByStore(?int $store_id)
    {
        return $this->db->select('id, credentials')->where('store_id', $store_id)->get('integration_logistic')->row_array();
    }

    public function getProviderDataCompanyId($id = null)
    {   
        $sql = "SELECT 
                    p.* FROM providers_to_seller as pts 
                join shipping_company as p on pts.provider_id = p.id
                where p.id = '$id' and p.deleted = 0;";

        $query = $this->db->query($sql);            
        return $query->row_array();

    }
    
    public function getProviderDataUserSellerId($store_id)
    {   
        $sql = "SELECT 
                   p.*
                FROM providers_to_seller as pts 
                INNER JOIN shipping_company as p on pts.provider_id = p.id                
                WHERE pts.store_id = '$store_id' and p.deleted = 0;";

        $query = $this->db->query($sql, array($store_id));       
        return $query->result_array();
    }

    /**
     * Cria uma nova transportadora.
     *
     * @param   array       $data   Dados da transportadora para cadastro.
     * @param   array|null  $stores Lojas para fazer a associação com a transportadora.
     * @return  false|int           Código da transportadora criada ou 'false' caso falhe.
     */
    public function create(array $data, array $stores = null)
    {
        if(count($data) > 0) {

            if (!$this->db->insert('shipping_company', $data)) {
                return false;
            }

            $provider_id = $this->db->insert_id();

            $dataStores = $stores;
            if ($stores === null && $data['store_id']) {
                $dataStores[] = $data['store_id'];
            }

            $providerToSeller = array();
            foreach ($dataStores as $store) {
                $company = $this->db->get_where('stores', array('id' => $store))->row_array();
                $providerToSeller[] = array(
                    'provider_id'   => $provider_id,
                    'store_id'      => $store,
                    'company_id'    => $company['company_id'],
                    'user_id'       => $this->session->userdata['id']
                );
            }
            $this->bondShippingCompanyToSeller($providerToSeller);

            // SW - Log Create
            get_instance()->log_data('shipping_company','Create',json_encode([$data, $stores]));

            return $provider_id;
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
                INNER JOIN shipping_company P ON P.id = PI.providers_id
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
        get_instance()->log_data('ShippingCompany FileTable','Insert',json_encode($data),"I");
        return $provider_id;
    }
    
    public function insertFileTableShipping($data){
        $create = $this->db->insert('file_table_shipping', $data);
        $fileId = $this->db->insert_id();
        get_instance()->log_data('ShippingCompany FileTable','Insert',json_encode($data),"I");
        return $fileId;
    }
    
    public function insertTableShipping($rows)
    {
        //return (bool)$this->db->insert_batch('table_shipping', $rows);
        //$fileIds= array();
        foreach($rows as $key => $value) {
            $this->db->insert('table_shipping', $value);
            //$fileIds[$this->db->insert_id()] = '';
        }
//        get_instance()->log_data('ShippingCompany Table Config','Insert Table',json_encode($rows),"I");
        return true;
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
        return $this->db->update('providers_indicacao', $data);
        
    }

	public function deleteTableConfigShipping($tableFileId,$shippingCompanyId,$sellerId) {

        $sqlTableShipping = "DELETE FROM `table_shipping` WHERE (`id_file` = '$tableFileId' AND `idproviders_to_seller` = '$shippingCompanyId' );";        
        $queryTableShipping = $this->db->query($sqlTableShipping);
       
        $sql = "DELETE FROM `file_table_shipping` WHERE (`idfile_table_shipping` = '$tableFileId');";
        $query = $this->db->query($sql);        
        get_instance()->log_data('deleteTableConfigShipping','Delte',json_encode(array($tableFileId,$shippingCompanyId,$sellerId)),"D");
        return $query;
    }

    public function getProviderByTypeData($type)
    {
        $sql = "SELECT * FROM shipping_company WHERE tipo_fornecedor=? and deleted = 0";
        $query = $this->db->query($sql,array($type));
        return $query->result_array();
    }

    /**
     * Atualiza o status da transportadora, obdecendo a regra.
     *
     * @warning Só poderá existir até cinco transportadoras/integrações ativas e pelo menos uma ativa.
     *
     * @param   int     $storeId    Código da loja.
     * @param   int     $idProvider Código da transportadora.
     * @param   bool    $status     Status de ativo.
     * @return  int
     */
	public function updateStatusShippingCompany(int $storeId, int $idProvider, bool $status): int
    {
        $shippingCompanies      = $this->getShippingCompanyActiveByStore($storeId);
        $integrationsLogistic   = $this->getIntegrationLogistic($storeId, 1);
        $totalValidate          = count($shippingCompanies) + count($integrationsLogistic);

        // só tem uma transportadora/integração ativa, não ficar com todas as transportadoras inativas.
        if (!$status && $totalValidate <= 1) {
            return 2;
        }

        // Já tem cinco transportadoras/integrações ativas, não pode ativar mais.
        if ($status && $totalValidate >= 5) {
            return 3;
        }

        $dataUpdate = array(
            'active' => $status
        );

        $this->update($dataUpdate, $idProvider);

        return 1;
    }

    public function updateStatusShippingCompanyIntegration(int $store_id, $id_integration, bool $status): int
    {
        if (empty($id_integration)) {
            $storeIntegration = current($this->getIntegrationLogistic($store_id)) ?: [];
            $id_integration = $storeIntegration['id'] ?? 0;
        }
        $shippingCompanies      = $this->getShippingCompanyActiveByStore($store_id);
        $integrationsLogistic   = $this->getIntegrationLogistic($store_id, 1);
        $totalValidate          = count($shippingCompanies) + count($integrationsLogistic);

        // só tem uma transportadora/integração ativa, não ficar com todas as transportadoras inativas.
        if (!$status && $totalValidate <= 1) {
            return 2;
        }

        // Já tem cinco transportadoras/integrações ativas, não pode ativar mais.
        if ($status && $totalValidate >= 5) {
            return 3;
        }

        $this->db
            ->group_start()
                ->where('id', $id_integration)
                ->group_start()
                ->where('store_id', 0)
                ->or_where('store_id', $store_id)
                ->group_end()
            ->group_end()
            ->update('integration_logistic', array('active' => $status));

        return 1;
    }
    
	public function updateStatusFileTableShippigCompany($fileId,$status)
    {
        $sql = "UPDATE file_table_shipping SET `status` = '$status' WHERE `idfile_table_shipping` = '$fileId';";
        $query = $this->db->query($sql);

        $updateRow = $this->updateStatusTableShippigCompany($fileId,$status);
        get_instance()->log_data('ShippingCompany FileTable Config','Update',json_encode(array($fileId,$status)),"I");
        return $this->db->query($sql);
    }
    
	public function updateStatusTableShippigCompany($fileId,$status)
    {
        $sql = "UPDATE table_shipping SET `status` = '$status' WHERE `id_file` = '$fileId';";        
        $query = $this->db->query($sql);
        get_instance()->log_data('ShippingCompany Table Config','Update',json_encode(array($fileId,$status)),"I");
        return $this->db->query($sql);
    }

    public function updateStatusInactiveFileRow(int $shippingCompany, int $lastFileId)
    {
        get_instance()->log_data('ShippingCompany Table Config','Update',json_encode(array($shippingCompany, $lastFileId)));
        return $this->db->where(array('idproviders_to_seller' => $shippingCompany, 'id_file !=' => $lastFileId))->update('table_shipping', array('status' => 0));
    }

    public function deleteInactiveFileRow(int $shippingCompany, int $lastFileId)
    {
        /*
        $this->db->where(
            array(
                'idproviders_to_seller' => $shippingCompany, 
                'id_file !=' => $lastFileId
            )
        )->delete();

        $this->db->delete('mytable', array('id' => $id));
        */

        get_instance()->log_data('ShippingCompany Table Config','Update',json_encode(array($shippingCompany, $lastFileId)));
        return $this->db->where(array('idproviders_to_seller' => $shippingCompany, 'id_file !=' => $lastFileId))->delete('table_shipping');
    }

    public function updateStatusInactiveFile($FileIds)
    {
        get_instance()->log_data('ShippingCompany Table Config','Update',json_encode(array($FileIds)));
        return $this->db->where_in('idfile_table_shipping', $FileIds)->update('file_table_shipping', array('status' => 0));
    }

    public function getTableConfigShipping($shippingCompany, $sellerId)
    {
                return $this->db
                    ->distinct()
                    ->select("idfile_table_shipping as id_file,
                            file_table_shippingcol as filename,
                            CONCAT(directory,file_table_shippingcol) as dirFile,
                            DATE_FORMAT(dt_start_v, '%d/%m/%Y') as dt_start_v,
                            DATE_FORMAT(dt_end_v, '%d/%m/%Y') as dt_end_v,
                            DATE_FORMAT(dt_create_file, '%d/%m/%Y %H:%i:%s') as dt_create_file,
                            status as status,
                            deleted as deleted
                    ")
                    ->where('shipping_company_id', $shippingCompany)
                    ->get('file_table_shipping')
                    ->result_array();
    }
    
    public function getTableConfigShippingIds($shippingCompany, $lastFileId)
    {
        return $this->db->select('idfile_table_shipping AS id_file')
                        ->where(array('shipping_company_id' => $shippingCompany, 'idfile_table_shipping !=' => $lastFileId))
                        ->get('file_table_shipping')
                        ->result_array();
    }
    
    public function getTableConfigShippingId($fileId, $sellerId)
    {
        $sql = "SELECT 
                    CONCAT(fts.directory,'/',fts.file_table_shippingcol) as dirFile,
                    fts.file_table_shippingcol as filename                   
        FROM file_table_shipping fts 
        WHERE fts.idfile_table_shipping = {$fileId}";
        $query = $this->db->query($sql);
        return $query->row_array();
    }

    public function getTypeTableShipping($idTransportadora){

        $sql = "SELECT tts.id_type FROM type_table_shipping as tts WHERE tts.id_provider  = $idTransportadora;";
        $query = $this->db->query($sql);            
        return $query->result_array();
    }

    public function setTypeTableShipping($idTransportadora, $type){
        $select = $this->getTypeTableShipping($idTransportadora);
        
        if (count($select) > 0) {
            $sql = "UPDATE type_table_shipping SET id_type = '$type' WHERE id_provider = '$idTransportadora';";        
            return $this->db->query($sql);
        } else {
            $sql = "INSERT INTO type_table_shipping (idtype_table_shipping, id_provider, id_type) VALUES ('', '".$idTransportadora."', '".$type."');";
            return $this->db->query($sql);
        }
    }

    public function getPriceRegionShipping($idTransportadora){
        // $sql = "SELECT * FROM frete_regiao_provider as frp WHERE frp.id_provider  = $idTransportadora;";
        $sql = "SELECT * FROM frete_regiao_provider as frp  join regions as r on frp.id_regiao = r.idRegiao
                    left join states as e on frp.id_estado = e.idEstado 
                        WHERE frp.id_provider  = $idTransportadora;";
    
        $query = $this->db->query($sql);    
        // dd($query->result_array());
        return $query->result_array();
    }

    public function setPriceRegionShipping($data, $idProvider){
        // dd($data);
        $this->db->trans_start();
        $delete = $this->db->delete('frete_regiao_provider', array ('id_provider'=>$idProvider)); 
        foreach ($data as $key => $value) {
            // dd($data[$key]);
            $create = $this->db->insert('frete_regiao_provider', $data[$key]);
            $fileId = $this->db->insert_id();
            // dd($this->db->last_query());
            // if(!$fileId){
            //         var_dump($fileId);
            // }
        }
        if($delete && $create){
            $this->db->trans_complete();
            return true;
        
        }
       
        // $update = $this->db->update_batch('frete_regiao_provider',$data, 'id_regiao');
        // $update = $this->db->replace('frete_regiao_provider', $data);
    }

    /**
     * @param   int     $provider_id    Código da transportadora
     * @return  mixed                   Código da loja
     */
    public function getStoreByProvider(int $provider_id)
    {
        return $this->db->from('shipping_company')
                        ->where(array(
                            'id' => $provider_id,
                            'deleted' => false
                        ))
                        ->get()
                        ->row_array();
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
            $data['tipo_fornecedor'] = 'Transportadora';
            $create = $this->db->insert('shipping_company', $data);
            $provider_id = $this->db->insert_id();
            $providerToSeller = array(
                 'provider_id'  => $provider_id,
                 'store_id'     => $data['store_id'],
                 'company_id'   => $data['company_id'],
                 'user_id'      => $this->session->userdata['id']
            );
            $this->bondShippingCompanyToSeller(array($providerToSeller));
            // SW - Log Create
            get_instance()->log_data('shipping_company','Create',json_encode($data),"I");
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
    public function update_token_simplified($data = array(), $id = null)
    {
        $this->db->where('id', $id);
        $update = $this->db->update('shipping_company', $data);

        // SW - Log Update
        get_instance()->log_data('shipping_company','edit token after',json_encode($data));
        return $update == true;
    }

    /**
     * Insert row database
     *
     * @param   array   $datas
     * @return  bool
     */
    public function bondShippingCompanyToSeller(array $datas): bool
    {
        if (count($datas)) {
            $this->removeProviderToSeller($datas[0]['provider_id']);
            foreach ($datas as $data) {
                $data['dt_cadastro'] = date("Y-m-d H:i:s");
                $this->insertProviderToSeller($data);
            }
            get_instance()->log_data('providers_to_seller', 'edit after', json_encode($datas));
        }

        return true;
    }

    /**
     * Recupera dados da transportadora pelo código da loja e código da transportadora.
     *
     * @param   int|array   $store      Código da loja.
     * @param   int         $provider   Código da transportadora.
     * @return  null|array
     */
    public function getShippingCompanyToSeller($store, int $provider): ?array
    {
        // Se não for um vetor, transformo em vetor para manter o padrão e consulta com WHERE IN.
        if(!is_array($store)) {
            $store = array($store);
        }

        return $this->db->from('providers_to_seller as ps')
            ->join('shipping_company as sc', 'sc.id = ps.provider_id')
            ->where(array('sc.id' => $provider, 'sc.deleted' => false))
            ->where_in('ps.store_id', $store)
            ->get()
            ->row_array();
    }

    /**
     * Atualizar transportadora.
     *
     * @param   array   $data   Dados para atualizar transportadora.
     * @param   int     $id     Código da transportadora.
     * @return  bool
     */
    public function updateShippingCompany(array $data, int $id): bool
    {
        get_instance()->log_data('shipping_company','edit after',json_encode($data));

        return $this->db->where('id', $id)->update('shipping_company', $data) == true;
    }

    public function getShppingCompanyData()
    {   
        $sql = "SELECT 
                 p.*,co.raz_social as raz_social
             FROM providers_to_seller as pts 
             INNER JOIN shipping_company as p on pts.provider_id = p.id
             INNER JOIN users as u on pts.user_id = u.id
             INNER JOIN company as co on u.company_id = co.id
             WHERE p.deleted = 0;";

        $query = $this->db->query($sql); 
        // dd($query->result_array());           
        return $query->result_array();
    }

    /**
     * Atualiza dados da transportadora.
     *
     * @param   array       $data   Dados da transportadora para atualizar.
     * @param   array|null  $stores Lojas para fazer a associação com a transportadora.
     * @param   int         $id     Código da transportadora.
     * @return  bool
     */
    public function update(array $data, int $id, array $stores = null): bool
    {
        $this->db->where('id', $id);
        $update = $this->db->update('shipping_company', $data);

        if (isset($data['store_id']) || $stores) {
            $dataStores = $stores;
            if ($stores === null) {
                $dataStores[] = $data['store_id'];
            }

            $providerToSeller = array();
            foreach ($dataStores as $store) {
                $company = $this->db->get_where('stores', array('id' => $store))->row_array();
                $providerToSeller[] = array(
                    'provider_id' => $id,
                    'store_id' => $store,
                    'company_id' => $company['company_id'],
                    'user_id' => $this->session->userdata['id']
                );
            }
            $this->bondShippingCompanyToSeller($providerToSeller);
        }

        // SW - Log Update
        get_instance()->log_data('shipping_company','edit after',json_encode([$id, $data, $stores]));
        return $update == true;
    }

    /**
     * Recupera dados das lojas que utilizam a transportadora.
     *
     * @param   int|null    $provider_id    Código da transportadora.
     * @return  array|null                  Dados da conexão entre loja e transportadora.
     */
    public function getShippingCompanyToSellerData(int $provider_id = null): ?array
    {
        $this->db->from('providers_to_seller');

        if($provider_id) {
            return $this->db->where('provider_id', $provider_id)->get()->result_array();
        }

        return $this->db->get()->result_array();
    }

    /**
     * Recupera as transportadoras de uma loja.
     *
     * @param   int     $storeId    Código da loja (stores.id).
     * @return  array               Dados da transportadora.
     */
    public function getShippingCompanyByStore(int $storeId): array
    {
        if (!$storeId) {
            return [];
        }

        return $this->db
            ->select('
                shipping_company.id,
                shipping_company.razao_social,
                shipping_company.name as provider_name,
                shipping_company.active,
                shipping_company.store_id as store_shipping_company,
                shipping_company.freight_seller,
                providers_to_seller.store_id,
                company.name as company_name,
                providers_to_seller.company_id,
                stores.name as store_name'
            )
            ->from('shipping_company')
            ->join('providers_to_seller', 'providers_to_seller.provider_id = shipping_company.id')
            ->join('company', 'company.id = providers_to_seller.company_id')
            ->join('stores', 'stores.id = providers_to_seller.store_id')
            ->where(array(
                'providers_to_seller.store_id' => $storeId,
                'shipping_company.deleted' => false
            ))
            ->get()
            ->result_array();
    }

    /**
     * Recupera dados da transportadora.
     *
     * @param   int         $id Código da transportadora.
     * @return  null|array      Dados da transportadora, caso encontre.
     */
    public function getShippingCompany(int $id): ?array
    {
        return $this->db
            ->from('shipping_company')
            ->where(array(
                'id' => $id,
                'deleted' => false
            ))
            ->get()
            ->row_array();
    }

    /**
     * Recupera transportadora pelo CNPJ.
     *
     * @param   string      $cnpj   CNPJ da transportadora.
     * @return  null|array          Dados da transportadora, caso encontre.
     */
    public function getShippingCompanySellerCenterByCnpj(string $cnpj): ?array
    {
        return $this->db
            ->from('shipping_company')
            ->where("replace(replace(replace(cnpj, '.', ''), '-', ''), '/', '') = " . onlyNumbers($cnpj))
            ->where('store_id is NULL', NULL, FALSE)
            ->where('deleted', false)
            ->get()
            ->row_array();
    }

    /**
     * Recupera Transportadora pelo CNPJ e código da loja.
     *
     * @param   string      $cnpj   CNPJ da transportadora.
     * @param   int         $store  Código da loja.
     * @return  null|array          Dados da transportadora.
     */
    public function getShippingCompanyByCnpjAndStore(string $cnpj, int $store): ?array
    {
        $store = $store === 0 ? null : $store;
        return $this->db
            ->from('shipping_company')
            ->where("replace(replace(replace(cnpj, '.', ''), '-', ''), '/', '') = " . onlyNumbers($cnpj))
            ->where('store_id', $store)
            ->where('deleted', false)
            ->get()
            ->row_array();
    }

    /**
     * Remove o relacionamento entre a transportadora e a loja.
     *
     * @param   int         $shippingCompany    Código da transportadora.
     * @param   int|null    $store              Código da loja.
     * @return  bool                            Estado a exclusão.
     */
    public function removeProviderToSeller(int $shippingCompany, int $store = null): bool
    {
        $this->db->where('provider_id', $shippingCompany);

        if ($store) {
            $this->db->where('store_id', $store);
        }

        return (bool)$this->db->delete('providers_to_seller');
    }

    /**
     * Cria o relacionamento entre a transportadora e a loja.
     *
     * @param   array       $data   Dados da transportadora.
     * @return  false|int           Código do registro criado, falso caso falhe.
     */
    public function insertProviderToSeller(array $data)
    {
        if ($this->db->insert('providers_to_seller', $data)) {
            return $this->db->insert_id();
        }

        return false;
    }

    /**
     * Recupera Transportadora pelo CNPJ e código da loja.
     *
     * @param   int         $store  Código da loja.
     * @return  null|array          Dados da transportadora.
     */
    public function getShippingCompanyActiveByStore(int $store): ?array
    {
        return $this->db
            ->from('providers_to_seller as pts')
            ->join('shipping_company as sc', 'pts.provider_id = sc.id')
            ->where(['pts.store_id' => $store, 'sc.active' => 1])
            ->get()
            ->result_array();
    }

    public function getShippingCompanyActiveByStoreAndSellerCenter(int $store)
    {
        $this->db->where(['tipo_fornecedor' => 'Transportadora', 'active' => true]);

        if ($this->data['usercomp'] == 1) {
            $this->db->where("(store_id IS NULL OR store_id = $store)", NULL, FALSE);
        } else {
            $this->db->where('store_id', $store);
        }

        return $this->db->group_by('id')->get('shipping_company')->result_array();
    }

    public function getRegions()
    {
        return $this->db->get('regions')->result_array();
    }

    public function getRegionById(int $region_id)
    {
        return $this->db->get_where('regions', array('idRegiao' => $region_id))->row_array();
    }

    public function getStatesByRegion(int $region_id)
    {
        return $this->db->get_where('states', array('Regiao' => $region_id))->result_array();
    }

    public function getStateById(int $state_id)
    {
        return $this->db->get_where('states', array('idEstado' => $state_id))->row_array();
    }

    /**
     * Recupera Transportadora pelo CNPJ e código da loja.
     *
     * @return  array          Dados da transportadora.
     */
    public function getShippingCompaniesActive(): ?array
    {
        $this->db->select('sc.*')
            ->join('shipping_company as sc', 'pts.provider_id = sc.id')
            ->where('sc.active', 1);

        if ($this->data['usercomp'] != 1) {
            if ($this->data['userstore'] == 0){
                $this->db->where("pts.company_id", $this->data['usercomp']);
            } else {
                $this->db->where("pts.store_id", $this->data['userstore']);
            }
        }

        return $this->db->get('providers_to_seller as pts')->result_array();
    }

    public function createFreteRegiaoProviderBatch(array $data): bool
    {
        return $data && $this->db->insert_batch('frete_regiao_provider', $data);
    }

    public function createProvidersToSellerBatch(array $data): bool
    {
        return $data && $this->db->insert_batch('providers_to_seller', $data);
    }

    public function createShippingCompanyBatch(array $data): bool
    {
        return $data && $this->db->insert_batch('shipping_company', $data);
    }

    public function createTypeTableShippingBatch(array $data): bool
    {
        return $data && $this->db->insert_batch('type_table_shipping', $data);
    }
}