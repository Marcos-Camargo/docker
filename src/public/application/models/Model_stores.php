<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para LOjas/Depositos
 
 */

/**
 * Class Model_stores
 * @property CI_DB_query_builder $db
 */
class Model_stores extends CI_Model
{
	const COMPANY_ID_CONECTA = 1;
	const TABLE = 'stores s';

	public function __construct()
	{
		parent::__construct();
	}

	/* get the active store data */
	public function getActiveStore()
	{
		// $sql = "SELECT * FROM stores WHERE active = ?";
		// $query = $this->db->query($sql, array(1));

		$more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND id = " . $this->data['userstore']);

		$sql = "SELECT * FROM stores WHERE active = ? " . $more . ' ORDER BY name';
		$query = $this->db->query($sql, array(1));
		return $query->result_array();
	}

	/* Check the company and id data */
	public function CheckStores($cpy = null, $id = null)
	{
		if (($id) && ($cpy)) {
			if ($cpy == self::COMPANY_ID_CONECTA) {
				$sql = "SELECT * FROM stores where id = ?";
				$query = $this->db->query($sql, array($id));
			} else {
				$sql = "SELECT * FROM stores where company_id = ? AND id = ?";
				$query = $this->db->query($sql, array($cpy, $id));
			}
			return $query->row_array();
		} else {
			return false;
		}
	}

	/* get the brand data */
	public function getStoresData($id = null)
	{
		if ($id) {
			$sql = "SELECT * FROM stores where id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}
		/*
		$more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND company_id = ".$this->data['usercomp'] : " AND id = ".$this->data['userstore']);
		
		$sql = "SELECT * FROM stores WHERE active = ? ".$more;
		$query = $this->db->query($sql, array(1));
		*/

        if (empty($this->data['usercomp'])) {
            return [];
        }

		$more = (($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? "WHERE company_id = " . $this->data['usercomp'] : "WHERE id = " . $this->data['userstore']));

		$sql = "SELECT * FROM stores " . $more;
		// var_dump($sql);
		$query = $this->db->query($sql);

		return $query->result_array();
	}
	public function getAllStoresFromStage($where = [], $limit = 200, $orderby = 's.id', $direction = 'asc', $store = '', $phase = '', $responsable = "", $search = '')
	{
		if (($this->data['usercomp'] != 1)) {
			if (($this->data['userstore'] == 0)) {
				$where['s.company_id'] = $this->data['usercomp'];
			} else {
				$where['s.id'] = $this->data['userstore'];
			}
		}

		$query = $this->db
			->select([
				's.id',
				's.name as name',
				's.phase_id as phase_id',
				's.goal_month',
				'phases.name as stage',
				'phases.responsable_id',
				"CONCAT(users.firstname,' ',users.lastname) as user_name",
				'users.firstname',
				'users.lastname',
			])
			->from(self::TABLE)
			->join('phases', 'phases.id=s.phase_id')
			->join('users', 'users.id=phases.responsable_id')
			->where($where)->order_by($orderby, $direction);
		$query = $query->where_in("users.id", $responsable);
		$query = $query->where_in("phases.id", $phase);
		$query = $query->where_in("s.id", $store);
		if (!empty($search)) {
			$query = $query->group_start()->or_like([
				"CONCAT(users.firstname,' ',users.lastname)" => $search,
				's.name' => $search,
				'phases.name' => $search
			])->group_end();
		}
		$query = $query->limit($limit)
			->get();
		return $query->result_array();
	}
	public function getAllStoresFromStageExports($store = null, $phase = null, $responsable = null)
	{
		$where = [];
		if (($this->data['usercomp'] != 1)) {
			if (($this->data['userstore'] == 0)) {
				$where['s.company_id'] = $this->data['usercomp'];
			} else {
				$where['s.id'] = $this->data['userstore'];
			}
		}

		$query = $this->db
			->select([
				's.id as `ID da loja`',
				'phases.name as `Fase da loja`',
				"CONCAT(users.firstname,' ',users.lastname) as `Responsavel`",
				's.goal_month as `Meta`',
			])
			->from(self::TABLE);
		$query = $query->join('phases', 'phases.id=s.phase_id');
		$query = $query->join('users', 'users.id=phases.responsable_id');
		$query = $query->where($where);
		if ($responsable)
			$query = $query->where_in("users.id", $responsable);
		if ($phase)
			$query = $query->where_in("phases.id", $phase);
		if ($store)
			$query->like("s.name", $store);
		$query = $query->get();
		return $query->result_array();
	}
	public function countAllStoresFromStage($where = [], $limit = 200, $orderby = 's.id', $direction = 'asc', $store = '', $phase = '', $responsable = "", $search = '')
	{
		if (($this->data['usercomp'] != 1)) {
			if (($this->data['userstore'] == 0)) {
				$where['s.company_id'] = $this->data['usercomp'];
			} else {
				$where['s.id'] = $this->data['userstore'];
			}
		}

		$query = $this->db
			->select([
				's.id',
				's.name as name',
				's.phase_id as phase_id',
				's.goal_month',
				'phases.name as stage',
				'phases.responsable_id',
				"CONCAT(users.firstname,' ',users.lastname) as user_name",
				'users.firstname',
				'users.lastname',
			])
			->from(self::TABLE)
			->join('phases', 'phases.id=s.phase_id')
			->join('users', 'users.id=phases.responsable_id')
			->where($where);
		if (!empty($responsable)) {
			$query = $query->where_in("users.id", $responsable);
		}
		if (!empty($phase)) {
			$query = $query->where_in("phases.id", $phase);
		}
		if (!empty($store)) {
			$query = $query->where_in("s.id", $store);
		}
		if (!empty($search)) {
			$query = $query->group_start()->or_like([
				"CONCAT(users.firstname,' ',users.lastname)" => $search,
				's.name' => $search,
				'phases.name' => $search
			])->group_end();
		}
		return $query->count_all_results();
	}
	public function getStoresDataToImportCSV($id = null, $usercomp = null, $userstore = null)
	{
		// $this->data['userstore']=$userstore;
		// $this->data['usercomp']=$usercomp;
		if ($id) {
			$sql = "SELECT * FROM stores where id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}
		$more = ($usercomp == 1) ? "" : (($userstore == 0) ? "WHERE company_id = " . $usercomp : "WHERE id = " . $userstore);

		$sql = "SELECT * FROM stores " . $more;
		$query = $this->db->query($sql);

		return $query->result_array();
	}
	public function getStoresById($id = null)
	{
		if ($id) {
			$sql = "SELECT * FROM stores where id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}
		return false;
	}
	public function getStoresByName($name = null)
	{
		if ($name) {
			$sql = "SELECT * FROM stores where name = ?";
			$query = $this->db->query($sql, array($name));
			return $query->row_array();
		}
		return false;
	}

	/* get the active store data */
	public function getCompanyStores($id)
	{
		$sql = "SELECT * FROM stores WHERE company_id = ? and active = 1";
		$query = $this->db->query($sql, array($id));
		return $query->result_array();
	}
		/* get the active store migration data */
	public function getCompanyStoresMigration($id = null)
	{
		if($id){
			$sql = "SELECT * FROM stores s JOIN seller_migration_register sm ON sm.store_id = s.id
			WHERE s.company_id = ? and s.active = 1 and s.flag_store_migration = 1 AND sm.status = 0";
			$query = $this->db->query($sql, array($id));
		} else {
			$sql = "SELECT * FROM stores s WHERE s.company_id = ? and s.active = 1 and s.flag_store_migration = 1";
			$query = $this->db->query($sql, array($id));
		}
		return $query->result_array();
	}
	public function getCompanyStoresUnmigrated($company_id = null)
	{
		$sql = "SELECT * FROM stores s  WHERE s.company_id = ? and s.active = 1 and s.flag_store_migration = 1 AND NOT EXISTS (SELECT * FROM seller_migration_register sm WHERE sm.store_id = s.id);";
		$query = $this->db->query($sql, array($company_id));
		return $query->result_array();
	}
	public function getCompanyStoresOnMigration($company_id = null)
	{
		$sql = "SELECT * FROM stores s JOIN seller_migration_register sm ON sm.store_id = s.id WHERE s.company_id = ? AND s.active = 1 AND s.flag_store_migration = 1 AND EXISTS (SELECT * FROM seller_migration_register sm WHERE sm.store_id = s.id) AND sm.status = 0;";
		$query = $this->db->query($sql, array($company_id));
		return $query->result_array();
	}
	public function getStoreBySellerId($sellerId)
	{
		$sql = 'SELECT s.* FROM stores s JOIN company c ON c.id = s.company_id WHERE c.import_seller_id = ?';
		$query = $this->db->query($sql, array($sellerId));
		return $query->row_array();
	}

	public function getStoreToIntegrateVtex($status = 1)
	{
		$sql = 'select c.import_seller_id, s.name, s.id as store_id from stores s join company c on c.id = s.company_id  where s.integrate_status = ?';
		$query = $this->db->query($sql, array($status));
		return $query->result_array();
	}

	public function getStoresDataByImportSellerId($companyId, $int_to, $importSellerId)
	{
		$sql = "SELECT s.* FROM stores s WHERE s.company_id = ?  and s.import_seller_id = ?";
		$query = $this->db->query($sql, array($companyId, $int_to, $importSellerId));
		return $query->row_array();
	}

	public function create($data)
	{
		if ($data) {
			$insert = $this->db->insert('stores', $data);
			$id =  $this->db->insert_id();

			if ($insert) {
				get_instance()->log_data('Stores', 'create', json_encode(array_merge(array('id' => $id), $data)), "I");
			}
			return ($insert == true) ? $id : false;
		}
	}

	public function update($data, $id)
	{
		if ($data && $id) {
			$store_now = $this->getStoresData($id);
			get_instance()->log_data('Stores', 'edit_before', json_encode($store_now), "I");
			
			// Audit user consiste no usuário que realizou a alteração.
			$audit_user = get_instance()->session->userdata("id");
			if ($audit_user && is_numeric($audit_user)) {
				$data["audit_user"] = $audit_user;
			}

			$this->db->where('id', $id);

			$update = $this->db->update('stores', $data);
			get_instance()->log_data('Stores', 'edit_after', json_encode(array_merge(array('id' => $id), $data)), "I");
			return ($update == true) ? true : false;
		}
	}

	public function remove($id)
	{
		if ($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('stores');
			return ($delete == true) ? true : false;
		}
	}

	public function countTotalStores()
	{
		$more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND id = " . $this->data['userstore']);

		$sql = "SELECT * FROM stores WHERE 1=1 " . $more;
		$query = $this->db->query($sql, array(1));
		return $query->num_rows();
	}

	public function countTotalStoresActive()
	{
		$more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND id = " . $this->data['userstore']);

		$sql = "SELECT * FROM stores WHERE active = ?" . $more;
		$query = $this->db->query($sql, array(1));
		return $query->num_rows();
	}

	public function getStoresByCadastro($cadastro = null)
	{
		if ($cadastro) {
			if (is_array($cadastro)) {
				$where = '';
				foreach ($cadastro as $option) {
					if ($option == 'null') {
						$where .= 'fr_cadastro IS NULL OR ';
					} else {
						$where .= 'fr_cadastro = ' . $option . ' OR ';
					}
				}
				$where = substr($where, 0, -3);
				$sql = "SELECT * FROM stores WHERE " . $where;
				$query = $this->db->query($sql);
			} else {
				$sql = "SELECT * FROM stores WHERE fr_cadastro = ?";
				$query = $this->db->query($sql, array($cadastro));
			}
		} else {
			$sql = "SELECT * FROM stores WHERE fr_cadastro IS NULL";
			$query = $this->db->query($sql);
		}
		return $query->result_array();
	}

	public function getStoresByNewCategories($status)
	{

		$sql = "SELECT s.*, st.tipos_volumes_id, st.status as ststatus, tv.codigo as codigo FROM stores s ";
		$sql .= " LEFT JOIN stores_tiposvolumes st ON st.store_id=s.id ";
		$sql .= " LEFT JOIN tipos_volumes tv ON st.tipos_volumes_id=tv.id ";
		$sql .= " WHERE (s.fr_cadastro = 1 OR s.fr_cadastro = 4) AND st.status = ?";
		$query = $this->db->query($sql, array($status));
		return $query->result_array();
	}

	public function getStoresByNewCategoriesExpired($dia, $store_id = '')
	{

		$sql = "SELECT * FROM stores_tiposvolumes WHERE (status = 3 or status = 2) AND date_update < ?"; // pega as sem cadastro e as que já tem correio
		if ($store_id != "") {
			$sql .= " AND store_id = " . $store_id;
		}
		$query = $this->db->query($sql, array($dia));
		return $query->result_array();
	}


	public function activeStoreIfNoNewCategory($id)
	{

		$sql = "UPDATE stores SET fr_cadastro=4 WHERE id=?";
		$sql .= " AND id IN ";
		$sql .= " (SELECT store_id AS id FROM stores_tiposvolumes WHERE store_id = ? AND (status = 2 OR status = 4))"; //aceita o correios ou o cadastro completo
		$query = $this->db->query($sql, array($id, $id));
		return;
	}

	public function getStoresWithoutGatewaySubAccounts($gatewayId = null)
	{

		$leftjoin = "";

		if($gatewayId)
        {
			$leftjoin .= " AND gateway_id = $gatewayId ";
		}
		
        $sql = "
                select 
                    s.* 
                from 
                    stores s 
                    LEFT JOIN seller_migration_register smr ON s.id = smr.store_id
                    left join gateway_subaccounts gs on (gs.store_id = s.id ".$leftjoin.")
                where 
                    gs.id is null
                AND
                    (
                        (s.flag_store_migration <> 1 OR	s.flag_store_migration IS NULL)
                        OR 
                        (s.flag_store_migration = 1 AND smr.`status` = 1)
                    )
                AND 
                    active = 1
                ";		

		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getStoresWithoutGatewaySubAccountsV5($gatewayId = null)
	{

        // $leftjoin = " left join gateway_subaccounts gs on ( gs.store_id = s.id ";

		// if($gatewayId){
		// 	$leftjoin .= " AND gateway_id = $gatewayId ";
		// }
		// $leftjoin .= " )";

		// $sql = "select s.* from stores s 
        //     $leftjoin
        // where (gs.id is null OR gs.secondary_gateway_account_id is null) AND active = 1 ";		

        // if (ENVIRONMENT === 'development')
        // {
            $leftjoin = "";

            if($gatewayId)
            {
                $leftjoin .= " AND gateway_id = $gatewayId ";
            }
    
            $sql = "
                    select 
                        s.* 
                    from 
                        stores s 
                        LEFT JOIN seller_migration_register smr ON s.id = smr.store_id
                        left join gateway_subaccounts gs on (gs.store_id = s.id ".$leftjoin.")
                    where 
                        (gs.id is null OR gs.secondary_gateway_account_id is null) 
                    AND
                        (
                            (s.flag_store_migration <> 1 OR	s.flag_store_migration IS NULL)
                            OR 
                            (s.flag_store_migration = 1 AND smr.`status` = 1)
                        )
                    AND 
                        active = 1
                    ";		
        // }

        $query = $this->db->query($sql);
        return $query->result_array();
	}

	public function getStoresWithoutFinancialManagementSystem(int $systemId)
	{

		$sql = "select s.*, 
                company.name AS company_name, 
                company.email AS company_email, 
                company.IMUN AS state_registration, 
                company.phone_1 AS company_phone1, 
                company.phone_2 AS company_phone2 
                FROM stores s
                    JOIN company ON (company.id = s.company_id)
            left join financial_management_system_stores on (financial_management_system_stores.store_id = s.id AND financial_management_system_stores.financial_management_system_id = $systemId)
        where financial_management_system_stores.id is null OR financial_management_system_stores.financial_management_system_code = '' ";

		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getStoresWithFinancialManagementSystemChangedLastXMinutes(int $systemId, int $minutes)
	{

		$sql = "select s.*
                FROM stores s
                JOIN financial_management_system_stores on (financial_management_system_stores.store_id = s.id 
                                                                AND financial_management_system_stores.financial_management_system_id = $systemId 
                                                                AND financial_management_system_stores.financial_management_system_code <> '')
        where s.date_update >= DATE_SUB(now(), INTERVAL $minutes MINUTE) ";

        $query = $this->db->query($sql);

		return $query->result_array();
	}

	public function getStoresWithoutGatewaySubAccountsGetnet()
	{

		/*$sql = "SELECT DISTINCT s.* FROM stores s 
				LEFT JOIN gateway_subaccounts gs ON gs.store_id = s.id
				LEFT JOIN payment_gateway_store_logs pgsl ON pgsl.store_id = s.id
				WHERE gs.id IS NULL AND pgsl.store_id IS NULL";*/

		 $sql = "SELECT DISTINCT s.* FROM stores s 
				LEFT JOIN gateway_subaccounts gs ON gs.store_id = s.id
				LEFT JOIN ( select distinct store_id from payment_gateway_store_logs where description like '%rejeitado%' or description like '%reprovado%' or description like '%aguardando%' ) pgsl ON pgsl.store_id = s.id
				WHERE gs.id IS NULL AND pgsl.store_id IS NULL";

		$query = $this->db->query($sql);
		$result = $query->result_array();
		if($result){

			foreach($result as $loja){
				$update = "update payment_gateway_store_logs set status = 'W' where store_id = ".$loja['id']." and status = 'error'";
				$this->db->query($update);
			}

		}
	
		return $result;
		
	}
	
	public function getStoresCallbackSubAccountsGetnet()
	{

		$sql = "SELECT DISTINCT s.* FROM stores s 
				LEFT JOIN gateway_subaccounts gs ON gs.store_id = s.id
				LEFT JOIN payment_gateway_store_logs pgsl ON pgsl.store_id = s.id
				INNER JOIN `getnet_subaccount` getsub ON getsub.store_id = s.id
				WHERE gs.id IS NULL AND pgsl.store_id IS NOT NULL  ";

		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getStoresWithGatewaySubAccountsChangedLastXMinutes(int $minutes, $gatewayId = null): array
	{

		$sql = "SELECT s.* FROM stores s 
            LEFT JOIN gateway_subaccounts gs ON gs.store_id = s.id
        WHERE gs.id IS NOT null AND active = ? AND s.date_update >= ? ";

		if($gatewayId){
			$sql .= " AND gateway_id = ".$gatewayId;
		}

		$lastTime = subtractDateFromNow(0, $minutes)->format(DATETIME_INTERNATIONAL);

		$query = $this->db->query($sql, [1, $lastTime]);

		return $query ? $query->result_array() : [];
	}

	public function getStoresWithGatewaySubAccounts($gateway_id = null): array
	{
        $gateway = '';

        if (!empty($gateway_id))
            $gateway = " and gs.gateway_id = ".$gateway_id;

/*		$sql = "SELECT s.*, gs.secondary_gateway_account_id, gs.gateway_account_id FROM stores s
            LEFT JOIN gateway_subaccounts gs ON gs.store_id = s.id
        WHERE gs.id IS NOT null 
        #AND active = ? 
        ".$gateway;*/

		$sql = "SELECT 
					s.*, 
					gs.secondary_gateway_account_id, 
					gs.gateway_account_id 
				FROM 
					stores s 
					LEFT JOIN gateway_subaccounts gs ON gs.store_id = s.id
				WHERE 
					gs.id IS NOT null 
				AND 
					active = ? 
				".$gateway." order by gs.store_id desc";

		$query = $this->db->query($sql, [1]);

		return $query ? $query->result_array() : [];

	}
	public function getStoreWithGatewaySubAccount($gateway_id, $store_id): array
	{
        $gateway = '';

        if (!empty($gateway_id))
            $gateway = " and gs.gateway_id = ".$gateway_id;

		$sql = "SELECT 
					s.*, 
					gs.secondary_gateway_account_id, 
					gs.gateway_account_id 
				FROM 
					stores s 
					LEFT JOIN gateway_subaccounts gs ON gs.store_id = s.id
				WHERE 
					gs.id IS NOT null 
                AND s.id = ?
				AND 
					active = ? 
				".$gateway." order by gs.store_id desc";

		$query = $this->db->query($sql, [$store_id, 1]);
        $rows = $query->row_array();

		return $rows ?: [];
	}

	public function getAllActiveStore()
	{
		$sql = "SELECT * FROM stores WHERE active = ?";
		$query = $this->db->query($sql, array(1));

		return $query->result_array();
	}

	public function createStoresTiposVolumes($data)
	{
		if ($data) {
			foreach ($data as $key => $value) {
				$keys[] = $key;
				$values[] = $value;
			}
			$sql = "INSERT INTO stores_tiposvolumes (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ") ON DUPLICATE KEY UPDATE  status=status+0";
			$query = $this->db->query($sql);
			//$insert = $this->db->insert('stores_tiposvolumes', $data). ' ON DUPLICATE KEY UPDATE status=status+0';
			return;
		}
	}

	public function deleteStoresTiposVolumes($store_id, $tipos_volumes_id)
	{
		$sql = "DELETE FROM stores_tiposvolumes WHERE store_id = ? AND tipos_volumes_id = ?";
		$query = $this->db->query($sql, array($store_id, $tipos_volumes_id));
		return $query;
	}

	public function getStoresTiposVolumesByStore($store_id)
	{
		$sql = "SELECT * FROM stores_tiposvolumes WHERE store_id = ?";
		$query = $this->db->query($sql, array($store_id));

		return $query->result_array();
	}

	public function updateStoresTiposVolumesStatus($store_id, $tipos_volumes_id, $status)
	{
		$sql = "UPDATE stores_tiposvolumes SET status = ?  WHERE store_id = ? AND tipos_volumes_id =?";
		$query = $this->db->query($sql, array($status, $store_id, $tipos_volumes_id));

		return;
	}

	public function getStoresTiposVolumesData($store_id = null)
	{
		$sql = "SELECT stv.*, s.name AS loja, tv.produto AS tipo_volume FROM stores_tiposvolumes stv ";
		$sql .= " LEFT JOIN stores s ON s.id = stv.store_id ";
		$sql .= " LEFT JOIN tipos_volumes tv ON tv.id=stv.tipos_volumes_id ";
		if (!is_null($store_id)) {
			$sql .= " WHERE stv.store_id = " . $store_id;
		}
		$sql .= " ORDER BY stv.store_id, stv.status, tipo_volume";

		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getStoresTiposVolumesNovosData($offset = 0, $procura = '', $orderby = '', $export = false)
	{
		if ($offset == '') {
			$offset = 0;
		}
		$sql = "SELECT stv.*, s.name AS loja, tv.produto AS tipo_volume FROM stores_tiposvolumes stv ";
		$sql .= " LEFT JOIN stores s ON s.id = stv.store_id ";
		$sql .= " LEFT JOIN tipos_volumes tv ON tv.id=stv.tipos_volumes_id ";
		$sql .= " WHERE stv.status != 4 AND (s.fr_cadastro=1 OR s.fr_cadastro=4 OR s.fr_cadastro=5) " . $procura . $orderby;
		$sql .= $export ? '' : " LIMIT 200 OFFSET " . $offset;

		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getStoresTiposVolumesNovosCount($procura = '')
	{
		if ($procura == '') {
			$sql = "SELECT count(*) as qtd FROM stores_tiposvolumes stv ";
			$sql .= " LEFT JOIN stores s ON s.id = stv.store_id ";
			$sql .= " WHERE stv.status != 4 AND (s.fr_cadastro=1 OR s.fr_cadastro=4 OR s.fr_cadastro=5)";
		} else {
			$sql = "SELECT count(*) as qtd FROM stores_tiposvolumes stv ";
			$sql .= " LEFT JOIN stores s ON s.id = stv.store_id ";
			$sql .= " LEFT JOIN tipos_volumes tv ON tv.id=stv.tipos_volumes_id ";
			$sql .= " WHERE stv.status != 4 AND (s.fr_cadastro=1 OR s.fr_cadastro=4 OR s.fr_cadastro=5) " . $procura;
		}

		$query = $this->db->query($sql, array());
		$row = $query->row_array();
		return $row['qtd'];
	}
	public function getStoreForExport($loja_id, $company_id)
	{
		$more = ($company_id == 1) ? "" : (($loja_id == 0) ? " AND company_id = " . $company_id : " AND id = " . $loja_id);
		$sql = "SELECT id AS codigo, name AS nome, company_id FROM stores WHERE active = 1 and (id = ? or name = ?)" . $more;
		$query = $this->db->query($sql, array($loja_id, $loja_id));
		return $query->row();
	}
	public function getStoresForExport()
	{
		$more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND id = " . $this->data['userstore']);

		$sql = "SELECT id AS codigo, name AS nome, company_id FROM stores WHERE active = ? " . $more;
		$query = $this->db->query($sql, array(1));
		return $query->result_array();
	}

	public function getStoresId()
	{
		$arrIds = array();
		$more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND id = " . $this->data['userstore']);

		$sql = "SELECT id FROM stores WHERE active = ? " . $more;
		$query = $this->db->query($sql, array(1));
		foreach ($query->result_array() as $store) array_push($arrIds, $store['id']);

		return implode(",", $arrIds);
	}

	/* get the active store data */
	public function getStoreIdInvoicing()
	{
		// $sql = "SELECT * FROM stores WHERE active = ?";
		// $query = $this->db->query($sql, array(1));

		$arrReturn = array();

		$sql = "SELECT * FROM stores_invoicing ";
		$query = $this->db->query($sql);

		foreach ($query->result_array() as $store)
			array_push($arrReturn, $store['store_id']);

		return $arrReturn;
	}

	public function createStoresInvoicing($data)
	{
		if ($data) {
			$insert = $this->db->insert('stores_invoicing', $data);
			return ($insert == true) ?  $this->db->insert_id() : false;
		}
	}

	public function updateLoginFreteRapido($id, $senha)
	{
		$login = 'loja' . $id . '@conectala.com.br';
		$sql = "UPDATE stores SET fr_email_login=?, fr_senha =?  WHERE id=?";
		$query = $this->db->query($sql, array($login, $senha, $id));
		return;
	}

	public function getTokenInvoice($store_id)
	{
		$sql = "SELECT * FROM stores_invoicing WHERE store_id = ? AND active = ?";
		$query = $this->db->query($sql, array($store_id, 1));

		if ($query->num_rows() == 0) return false;

		$token = $query->row_array();

		if ($token['token_tiny'] == "") return false;

		return $token['token_tiny'];
	}

	public function getStoreActiveInvoicing($store_id)
	{
		$sql = "SELECT * FROM stores_invoicing WHERE store_id = ? AND active = 1";
		$query = $this->db->query($sql, array($store_id));
		return $query->num_rows();
	}

	public function getRequestStoreInvoice($store_id = null)
	{
		$where = $store_id ? "WHERE stores_invoicing.store_id = {$store_id}" : "";

		$sql = "SELECT 
                stores_invoicing.id,
                stores_invoicing.store_id,
                company.id as company_id,
                company.name as company_name,
                stores.name as store_name,
                stores_invoicing.certificado_path,
                stores_invoicing.certificado_pass,
                stores_invoicing.erp,
                stores_invoicing.token_tiny,
                stores_invoicing.active,
                stores_invoicing.created_at 
                FROM stores_invoicing 
                JOIN stores ON stores_invoicing.store_id = stores.id 
                JOIN company ON stores.company_id = company.id 
                {$where}
                ORDER BY stores_invoicing.active";
		$query = $this->db->query($sql);


		return $query->result_array();
	}

	public function updateRequestStoreInvoice($store_id, $token)
	{
		$sql = "UPDATE stores_invoicing SET token_tiny = ?, active = ? WHERE store_id = ?";
		return $this->db->query($sql, array($token, 1, $store_id));
	}

	public function getStoresForExportFormated()
	{
		// $sql = "SELECT * FROM stores WHERE active = ?";
		// $query = $this->db->query($sql, array(1));

		// $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND stores.company_id = ".$this->data['usercomp'] : " AND stores.id = ".$this->data['userstore']);
		$more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? "WHERE stores.company_id = " . $this->data['usercomp'] : " AND stores.id = " . $this->data['userstore']);

		$sql = "SELECT stores.id AS codigo, 
                stores.name AS nome,
                stores.raz_social,
                company.name as empresa,
				stores.onboarding,
                 DATE_FORMAT(stores.date_create,'%d/%m/%Y %H:%i') as date_create,
				case
                        when stores.active = 1 then 'Ativo'
                        when stores.active = 2 then 'Inativo'
                        when stores.active = 3 then 'Em Negociação'
                        when stores.active = 4 then 'Boleto'
                        when stores.active = 5 then 'Churn'
                        else ''
                end as store_status, 
                stores.addr_uf as addr_uf,
                stores.service_charge_value,
                stores.responsible_email,
                stores.freight_seller,
                stores.freight_seller_type,
                stores.CNPJ as cnpj,
								case
                  when flag_antecipacao_repasse = 'S' then 'SIM'                        
                else 'NÃO' end as flag_antecipacao_repasse,
                users.username as seller
                FROM stores
				LEFT JOIN users ON stores.seller = users.id
                JOIN company ON stores.company_id = company.id " . $more;
		return $this->db->query($sql);
	}

	public function replaceApiIntegration($data)
	{
		if ($data) {
			$insert = $this->db->replace('api_integrations', $data);
			return ($insert == true) ? true : false;
		}
	}

	public function getDataApiIntegration($store_id)
	{
		if ($store_id) {
			$sql = "SELECT api_integrations.*, stores.token_callback FROM api_integrations JOIN stores ON api_integrations.store_id = stores.id WHERE api_integrations.store_id = ?";
			$query = $this->db->query($sql, array($store_id));
			return $query->row_array();
		}
	}

	public function getDataApiIntegrationByStore($store_id)
	{
		if ($store_id) {
			$sql = "SELECT api_integrations.*, stores.token_callback, stores.company_id FROM stores LEFT JOIN api_integrations ON api_integrations.store_id = stores.id WHERE stores.id = ?";
			$query = $this->db->query($sql, array($store_id));
			return $query->row_array();
		}
	}

	public function getDataApiIntegrationForId($id)
	{
		if ($id) {
			$sql = "SELECT api_integrations.*, stores.name, stores.token_callback FROM api_integrations JOIN stores ON api_integrations.store_id = stores.id WHERE api_integrations.id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}
	}

	public function getStoresIntegration()
	{
		$sql = "SELECT api_integrations.*, stores.name, stores.company_id FROM api_integrations JOIN stores ON api_integrations.store_id = stores.id";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function updateStatusIntegration($id_integration, $update)
	{
		$sql = $this->db->update_string('api_integrations', $update, array('id' => $id_integration));
		return $this->db->query($sql);
	}

	public function setToSendToFreteRapido($store_id)
	{
		$sql = "UPDATE stores SET fr_cadastro=2 WHERE id = ?";
		return $this->db->query($sql, array($store_id));
	}

	public function getAllActiveStoreFR()
	{
		// $sql = "SELECT * FROM stores WHERE active = ?";
		// $query = $this->db->query($sql, array(1));

		// tem que estar ativa e ter realizado o cadastro no frete rápido. (fr_cadastro =4 ja fez, fr_cadastro = 5 mandando pequenas mudancas)
		$sql = "SELECT * FROM stores WHERE active = ? AND (fr_cadastro = 1 OR fr_cadastro = 4 OR fr_cadastro =5)";
		$query = $this->db->query($sql, array(1));

		return $query->result_array();
	}

	public function getDataForTheProgressBar($store_id)
	{
		// Pega a data de criação da loja
		$sql_store   = "SELECT date_create FROM stores WHERE id = ?";
		$query_store = $this->db->query($sql_store, array($store_id));
		$date_store  = $query_store->result_array()[0]['date_create'] ?? null;

		// Pega a data de criação do primeiro produto
		$sql_product   = "SELECT MIN(date_create) AS date_create FROM products WHERE store_id = ?";
		$query_product = $this->db->query($sql_product, array($store_id));
		$date_product  = $query_product->result_array()[0]['date_create'] ?? null;

		// Pega a data de criação do primeiro pedido
		$sql_order   = "SELECT MIN(CAST(date_time AS DATE)) AS date_create FROM orders WHERE store_id = ?";
		$query_order = $this->db->query($sql_order, array($store_id));
		$date_order  = $query_order->result_array()[0]['date_create'] ?? null;

		$data = [
			'date_store'   => $date_store,
			'date_product' => $date_product,
			'date_order'   => $date_order
		];

		return $data;
	}

	public function getStoreTokenCallback($token)
	{
		$sql = "SELECT * FROM stores WHERE token_callback = ?";
		$query = $this->db->query($sql, array($token));

		if ($query->num_rows() == 0) return false;

		return $query->row_array();
	}

	public function getMyCompanyStores($id, $active = null)
	{
		$more = isset($this->data) ? (($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND id = " . $this->data['userstore'])) : '';

		if ($active) $more .= " AND active = 1";

		$sql = "SELECT * FROM stores WHERE company_id = ?" . $more . ' ORDER BY id';
		$query = $this->db->query($sql, array($id));
		return $query->result_array();
	}

	public function getStoreById($id)
	{
		$sql = "SELECT * FROM stores WHERE id = ?";
		$query = $this->db->query($sql, array($id));
		return $query->row_array();
		// return $query->result_array();
	}

	public function getStoreIntegration()
	{
		$more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND stores.id = " . $this->data['userstore']);

		$sql = "SELECT stores.* FROM stores JOIN api_integrations ON stores.id = api_integrations.store_id WHERE api_integrations.status = 1 " . $more . " ORDER BY stores.name";

		$query = $this->db->query($sql);
		return $query->result_array();
	}

    public function getStoresWithLogIntegration()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND store.company_id = " . $this->data['usercomp'] : " AND store.id = " . $this->data['userstore']);

        $sql = "SELECT store.* FROM stores store 
                WHERE EXISTS( SELECT l.store_id FROM log_integration l WHERE l.store_id = store.id ORDER BY l.id DESC LIMIT 1 ) 
                {$more}
                ORDER BY store.name";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

	public function getCountStoresWithLogIntegration()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND store.company_id = " . $this->data['usercomp'] : " AND store.id = " . $this->data['userstore']);
		
        $sql = "SELECT COUNT(store.id) AS qtd FROM stores store 
                WHERE EXISTS( SELECT l.store_id FROM log_integration l WHERE l.store_id = store.id ORDER BY l.id DESC LIMIT 1 ) 
                {$more}
                ORDER BY store.name LIMIT 1";

        $query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
		
    }

	public function getStoresToIntegrateIntoTheVtexIn($integrationStatus)
	{
		$sql   = "SELECT s.*, i.int_to FROM integrations i
				  INNER JOIN stores s ON s.id = i.store_id 
				  WHERE i.auth_data = ?";
		$query = $this->db->query($sql, array($integrationStatus));
		return $query->result_array();
	}

	public function updateStoreIntegrationStatus($status, $storeId, $integration_to)
	{
		$sql   = "UPDATE integrations SET auth_data = ? 
				  WHERE store_id = ? AND int_to = ?";
		$query = $this->db->query($sql, array($status, $storeId, $integration_to));
		return $query;
	}

	public function updateStoreInvoice($data, $store_id)
	{
		if ($data) {
			$this->db->where('store_id', $store_id);
			$update = $this->db->update('stores_invoicing', $data);
			return $update == true;
		}
		return false;
	}

	public function updateIntegrateStatus($store_id, $status)
	{
		if ($status) {
			$data = array("integrate_status" => $status);

			$this->db->where('id', $store_id);
			$update = $this->db->update('stores', $data);

			return $update == true;
		}
		return false;
	}

	public function removeRequestStoreInvoice($storeId)
	{
		if ($storeId) {
			$this->db->where('store_id', $storeId);
			$delete = $this->db->delete('stores_invoicing');
			return ($delete == true) ? true : false;
		}
		return false;
	}

	public function getStoresIndicator($store_id = null)
	{
		$sql = "SELECT id, name FROM stores WHERE associate_type = 5";
		if ($store_id) $sql .= " AND id = {$store_id}";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getRegisteredSellers()
	{
		$sql = "SELECT stores.name AS store_name, 
				stores.id AS store_id, 
				stores.company_id, 
				catalogs.name AS integration_name FROM stores 
				INNER JOIN catalogs_stores ON catalogs_stores.store_id = stores.id 
				INNER JOIN catalogs ON catalogs.id = catalogs_stores.catalog_id";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getIntegratedSellers($sellerId, $integration)
	{
		$sql   = "SELECT * FROM integrations WHERE store_id = ? AND int_to = ?";
		$query = $this->db->query($sql, array($sellerId, $integration));
		return $query->result_array();
	}

	public function saveSellerToIntegrate($data)
	{
		$this->db->insert('integrations', $data);
	}

	public function updateSellerToIntegrate($id, $data)
	{
		$this->db->where('id', $id);
		$this->db->update('integrations', $data);
	}

	public function getStoresDataView($end = '', $filtrocnpj = '', $offset = 0, $procura = '', $orderby = '', $limit = 200)
	{
		if ($offset == '') {
			$offset = 0;
		}
		if ($limit == '') {
			$limit = 200;
		}
		$sql = "SELECT s.*, c.name as company, (SELECT MIN(date_create) FROM products WHERE store_id = s.id) AS date_product, ";
		$sql .= " (SELECT MIN(CAST(date_time AS DATE)) FROM orders WHERE store_id = s.id) AS date_order ";
		$sql .= "FROM stores s, company c ";
        $sql .= " WHERE s.company_id = c.id" . $end . $filtrocnpj;
		$sql .= $procura . $orderby . " LIMIT " . $limit . " OFFSET " . $offset;

		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getStoresDataCount($procura = '')
	{
		if ($procura == '') {
			$sql = "SELECT count(*) as qtd FROM stores ";
		} else {
			$sql = "SELECT count(*) as qtd FROM stores s, company c WHERE s.company_id = c.id ";
			$sql .= $procura;
		}

		$query = $this->db->query($sql, array());
		$row = $query->row_array();
		return $row['qtd'];
	}

	public function uniqueErpCustomerSupplierCode($id, $clifor)
	{
		$sql   = "SELECT count(*) AS qtd FROM stores WHERE id != ? AND erp_customer_supplier_code = ?";
		$query = $this->db->query($sql, array($id, $clifor));
		$row = $query->row_array();
		return ($row['qtd'] == 0);
	}

	public function getSellerIndex($where)
	{
		$result = $this->db->where($where)->order_by('id', 'DESC')->limit(1)->get('seller_index_history')->result_array();

		return $result;
	}

	public function deleteSellerIndex($id)
	{
		$result = $this->db->delete('seller_index_history', ['id' => $id]);

		return $result;
	}

	public function saveSellerIndex($data)
	{
		if ($this->db->replace('seller_index', $data)) {
			$result = $this->db->insert('seller_index_history', $data);
		}	
		return $result;
	}

	public function getStoreSellerIndex($storeId)
	{
		$sql   = "select
					min(indicador)
				from (
					-- PEDIDOS CANCELADOS
						select
							coalesce(
								case
									when (count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) <= 1 and count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) > 0.1) then 1
									when (count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) <= 0.1 and count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) > 0.08) then 2
									when (count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) <= 0.08 and count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) > 0.04) then 3
									when (count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) <= 0.04 and count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) > 0.03) then 4
								else 5 end
							,   5) indicador
							from orders
							join stores
								on stores.id = orders.store_id
							where
								cast(data_limite_cross_docking as date) between current_date - interval 30 day and current_date - interval 1 day
								and data_pago is not null
								and orders.store_id in (?)
					-- PEDIDOS ATRASADOS
				UNION ALL
						select
						coalesce(
								case
									when (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) < 1 and (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) >= 0.2 then 1
									when (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) < 0.2 and (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) >= 0.15 then 2
									when (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) < 0.15 and (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) >= 0.10 then 3
									when (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) < 0.10 and (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) >= 0.05 then 4
									when (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) < 0.05 and (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) >= 0 then 5
								end
							,   5) indicador
						from orders
							join stores
								on stores.id = orders.store_id
							where
								cast(data_limite_cross_docking as date) between current_date - interval 30 day and current_date - interval 1 day
								and data_pago is not null
								and orders.store_id in (?)
				UNION ALL
					-- MEDIACAO
						select
							coalesce(
								case
									when sum(ticket.num_chamados)/count(distinct orders.id) >= 0.1 and sum(ticket.num_chamados)/count(distinct orders.id) <= 1 then 1
									when sum(ticket.num_chamados)/count(distinct orders.id) >= 0.06 and sum(ticket.num_chamados)/count(distinct orders.id) < 0.1 then 2
									when sum(ticket.num_chamados)/count(distinct orders.id) >= 0.03 and sum(ticket.num_chamados)/count(distinct orders.id) < 0.06 then 3
									when sum(ticket.num_chamados)/count(distinct orders.id) >= 0.02 and sum(ticket.num_chamados)/count(distinct orders.id) < 0.03 then 4
									when sum(ticket.num_chamados)/count(distinct orders.id) >= 0 and sum(ticket.num_chamados)/count(distinct orders.id) < 0.02 then 5
								end, 5) indicador
						from orders
							join stores
								on orders.store_id = stores.id
							left join (
								select
									code
								,   count(*) num_chamados
								from ticket_b2w
									where mediation = true
								group by 1
									UNION ALL
								select
									replace(order_href, '/orders/', '') code
								,   count(*)
								from ticket_via
									where order_href is not null
								group by 1
									UNION ALL
								select
									order_id
								,   count(*)
								from ticket_ml
								group by 1
							) ticket
								on orders.numero_marketplace = ticket.code
						where
							cast(data_limite_cross_docking as date) between current_date - interval 30 day and current_date - interval 1 day
							and data_pago is not null
							and origin in ('B2W', 'VIA','ML')
							and orders.store_id in (?)
				) reputacao";
			$sql = 
				"select
					min(indicador)
				from (
					-- PEDIDOS CANCELADOS
						select
							coalesce(
								case
									when (count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) <= 1 and count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) > 0.1) then 1
									when (count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) <= 0.1 and count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) > 0.08) then 2
									when (count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) <= 0.08 and count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) > 0.04) then 3
									when (count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) <= 0.04 and count(distinct case when paid_status in (90,95,97,98,99) then orders.id end)/count(distinct orders.id) > 0.03) then 4
								else 5 end
							,   5) indicador
							from orders
							join stores
								on stores.id = orders.store_id
							where
								cast(data_limite_cross_docking as date) between current_date - interval 30 day and current_date - interval 1 day
								and data_pago is not null
								and orders.store_id in (?)
					-- PEDIDOS ATRASADOS
				UNION ALL
						select
						coalesce(
								case
									when (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) <= 1 and (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) >= 0.2 then 1
									when (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) < 0.2 and (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) >= 0.15 then 2
									when (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) < 0.15 and (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) >= 0.10 then 3
									when (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) < 0.10 and (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) >= 0.05 then 4
									when (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) < 0.05 and (count(distinct case WHEN cast(data_envio as date) > cast(data_limite_cross_docking as date) then orders.id WHEN data_envio is null and current_date > cast(data_limite_cross_docking as date) then orders.id end)/count(distinct orders.id)) >= 0 then 5
								end
							,   5) indicador
						from orders
							join stores
								on stores.id = orders.store_id
							where
								cast(data_limite_cross_docking as date) between current_date - interval 30 day and current_date - interval 1 day
								and data_pago is not null
								and orders.store_id in (?)
				UNION ALL
					-- MEDIACAO
						select
							coalesce(
								case
									when sum(ticket.num_chamados)/count(distinct orders.id) >= 0.1 and sum(ticket.num_chamados)/count(distinct orders.id) <= 1 then 1
									when sum(ticket.num_chamados)/count(distinct orders.id) >= 0.06 and sum(ticket.num_chamados)/count(distinct orders.id) < 0.1 then 2
									when sum(ticket.num_chamados)/count(distinct orders.id) >= 0.03 and sum(ticket.num_chamados)/count(distinct orders.id) < 0.06 then 3
									when sum(ticket.num_chamados)/count(distinct orders.id) >= 0.02 and sum(ticket.num_chamados)/count(distinct orders.id) < 0.03 then 4
									when sum(ticket.num_chamados)/count(distinct orders.id) >= 0 and sum(ticket.num_chamados)/count(distinct orders.id) < 0.02 then 5
								end, 5) indicador
						from orders
							join stores
								on orders.store_id = stores.id
							left join (
								select
									code
								,   count(*) num_chamados
								from ticket_b2w
									where mediation = true
								group by 1
									UNION ALL
								select
									replace(order_href, '/orders/', '') code
								,   count(*)
								from ticket_via
									where order_href is not null
								group by 1
									UNION ALL
								select
									order_id
								,   count(*)
								from ticket_ml
								group by 1
							) ticket
								on orders.numero_marketplace = ticket.code
						where
							cast(data_limite_cross_docking as date) between current_date - interval 30 day and current_date - interval 1 day
							and data_pago is not null
							and origin in ('B2W', 'VIA','ML')
							and orders.store_id in (?)
				) reputacao";
		$query = $this->db->query($sql, array($storeId, $storeId, $storeId));
		$row = $query->row_array();

		return $row['min(indicador)'];
	}

	public function inactive($id)
	{
		$activeValue = 1;
		$inactiveValue = 2;
		$store = $this->getStoresData($id);
		$sql = "SELECT s.* FROM stores s WHERE s.company_id = ? and s.active=?";
		$result = $this->db->query($sql, array($store['company_id'], $activeValue));
		$stores = $result->result_array();
		if (count($stores) == 1) {
			$sql = "update users u set active=" . $inactiveValue . " WHERE u.company_id = ? and u.active=" . $activeValue . ";";
			$this->db->query($sql, array($store['company_id']));
			$sql = "UPDATE company SET active = " . $inactiveValue . " WHERE id = ?";
			$result = $this->db->query($sql, array($store['company_id']));
		}
		$sql = "update users u set active=" . $inactiveValue . " WHERE u.store_id = ? and u.active=" . $activeValue . ";";
		$this->db->query($sql, array($id));
		$sql = "update stores s set active=" . $inactiveValue . " WHERE s.id = ? and s.active=" . $activeValue . ";";
		$this->db->query($sql, array($id));
	}
	public function active($id)
	{
		$activeValue = 1;
		$inactiveValue = 2;
		$store = $this->getStoresData($id);
		$sql = "SELECT s.* FROM stores s WHERE s.company_id = ? and active=?";
		$result = $this->db->query($sql, array($store['company_id'], $inactiveValue));
		if (count($result->result_array()) == 1) {
			$sql = "update users u set active=" . $activeValue . " WHERE u.company_id = ? and u.active=" . $inactiveValue . ";";
			$this->db->query($sql, array($store['company_id']));
			$sql = "UPDATE company SET active = " . $activeValue . " WHERE id = ?";
			$this->db->query($sql, array($store['company_id']));
		}
		$sql = "update users u set active=" . $activeValue . " WHERE u.store_id = ? and u.active=" . $inactiveValue . ";";
		$this->db->query($sql, array($id));
		$sql = "update stores s set active=" . $activeValue . " WHERE s.id = ? and s.active=" . $inactiveValue . ";";
		$this->db->query($sql, array($id));
	}
	public function vacationOn($id){
		$isVacation = 1;

    	$sql = "UPDATE stores SET is_vacation = ?, start_vacation = NOW() WHERE id = ?";
        $this->db->query($sql, array($isVacation, $id));

    	return true;
	}
	
	public function vacationOff($id){
		$isVacation = 0;

    	$sql = "UPDATE stores SET is_vacation = ?, end_vacation = NOW() WHERE id = ?";
        $this->db->query($sql, array($isVacation, $id));

    	return true;

	}

	public function getVacationLogs($id)
	{
		if (!$id) {
			echo json_encode(['error' => 'storeId não fornecido.']);
			return;
		}
		$limit = 6;
		$logs = $this->db
			->select('action AS status, users.email AS email_do_responsavel, value, date_log')
			->from('log_history')
			->join('users', 'users.id = log_history.user_id')
			->where('module', 'Stores')
			->where_in('action', ['ON Vacation', 'OFF Vacation'])
			->like('value', '{"id":"'.$id.'"', $side = 'after')
			->order_by('date_log', 'DESC')
			->limit($limit)
			->get()
			->result_array();

		echo json_encode(['logs' => $logs]);
	}
	public function startMigration($id)
	{
		$sql = "UPDATE `stores` SET `integrate_status` = '1' WHERE (`id` = ?);";
		$this->db->query($sql, array($id));
	}

	public function validApikeyIntelipost(string $apiKey, string $orderNumber)
    {
        // Módulo de fretes.
        $credential = $this->db->select('store_id')->like('credentials', "\"token\":\"$apiKey\"")->get('integration_logistic')->row_object();
        if ($credential) {
            // credencial do seller center, recuperar loja pelo order_number.
            if ($credential->store_id == 0) {
                $storeByFreight = $this->db->select('orders.store_id')->where('freights.shipping_order_id', $orderNumber)->join('orders', 'orders.id = freights.order_id')->get('freights')->row_object();
                // encontrou o pedido do rastreio.
                if ($storeByFreight) {
                    return $storeByFreight->store_id;
                }
                return false;
            }
            return $credential->store_id;
        }

		$sql = "SELECT id FROM stores WHERE freight_seller = 1 AND freight_seller_type in (3,4) AND freight_seller_end_point = ?";
		$query = $this->db->query($sql, array($apiKey));
		$resultStore = $query->row_array();

		if (!$resultStore) {
			$sql = "SELECT * FROM settings WHERE name = ? AND value = ?";
			$query = $this->db->query($sql, array('token_intelipost_sellercenter', $apiKey));
			$resultSetting = $query->row_array();

			if (!$resultSetting) return false;
		}

		return $this->getStoreByApikeyIntelipost($apiKey);
	}

	public function getStoreByApikeyIntelipost($apiKey)
	{
		$sql = "SELECT id FROM stores WHERE freight_seller = 1 AND freight_seller_type in (3,4) AND freight_seller_end_point = ?";
		$query = $this->db->query($sql, array($apiKey));
		$resultStore = $query->row_array();

		if (!$resultStore) {
			$sql = "SELECT * FROM settings WHERE name = ? AND value = ?";
			$query = $this->db->query($sql, array('token_intelipost_sellercenter', $apiKey));
			$resultSetting = $query->row_array();

			if (!$resultSetting) return false;
			return null;
		}

		return (int)$resultStore['id'];
	}

	public function getSellerIndexByStore(int $store_id)
	{
		$sql = "SELECT * FROM seller_index_history WHERE store_id = ? ORDER BY date DESC limit 1";
		$query = $this->db->query($sql, array($store_id));
		$result = $query->row_array();
		return $result['seller_index'] ?? 0;

		// deu erro em producao $result = $this->db->get_where('seller_index_history', array('store_id' => $store_id))->order_by('date', 'DESC')->limit(1)->row_array();
		// return $result['seller_index'] ?? 0;
	}


	public function uniqueCnpj($cnpj)
	{
        if (!is_array($cnpj)) $cnpj = [$cnpj];
        
		$sql = "select count(CNPJ) as used from stores where REPLACE(REPLACE(REPLACE(CNPJ, '.', '' ),'/',''),'-','') = '" . implode('', $cnpj) . "'";
		$query = $this->db->query($sql);
		$resultSetting = $query->row_array();
		if (intVal($resultSetting['used']) === 0) {
			return true;
		} else {
			return false;
		}
	}
	public function getWithoutTokens()
	{
		$whereCase = ['token_api =' => ''];
		return $this->db->select('*')->from('stores')->where($whereCase)->get()->result_array();
	}
	public function inCatalogByStoreIDAndProductID($store_id, $product_id, $product_catalog_id)
	{
		$catalogs_products_catalog = $this->db->select('*')->from('catalogs_products_catalog')->where('product_catalog_id', $product_catalog_id)->get()->row_array();
		$catalog_store = $this->db->select('*')->from('catalogs_stores')->where('store_id', $store_id)->where('catalog_id', $catalogs_products_catalog['catalog_id'])->get()->row_array();
		if ($catalog_store == null) {
			return false;
		} else {
			return true;
		}
		// dd($catalogs_products_catalog,$store_id,$catalog_store);
		// dd($store_id,$product_id,$product_catalog_id);
	}
	public function getStoreByTokenAndNameOrId($token, $store)
	{
		$or_store = [
			'id' => trim($store),
			'name' => trim($store)
		];
		$response = $this->db->select('*')->from('stores')->where('token_api', trim($token))->group_start()->or_where($or_store)->group_end()->get()->row_array();
		return $response;
	}

	/**
	 * Função utilizada para verificar se uma loja está cadastrada na tabela stores através do CNPJ;
	 * Exemplo de tratamento para remover caracteres especiais $cnpj = preg_replace('/\D/', '', $data["cnpj"]);
	 * $sql - Tratamento para desconsiderar caracteres especiais dos registros no banco, a busca acontece apenas entre os números.
	 *
	 * @param   string $cnpj Deve conter somente os números do cnpj (remover pontos, traço e barra)
	 * @return  mixed        Se o CNPJ existir, retorna todos os dados da loja.
	 */
	public function getStoreByCNPJ(string $cnpj)
	{
		$sql = "SELECT * FROM stores where replace(replace(replace(CNPJ, '/', ''),'.','' ),'-','' ) = ?";
		$query = $this->db->query($sql, array($cnpj));
		return $query->row_array();
	}

	public function getActiveStoreProductsPublish($filter = '')
	{
		$more = $this->data['usercomp'] == 1 ? "" : ($this->data['userstore'] == 0 ? " AND company_id = " . $this->data['usercomp'] : " AND id = " . $this->data['userstore']);

		$sql = "SELECT * FROM stores WHERE active = ? AND type_store !=2 {$more} {$filter} ORDER BY name";
		$query = $this->db->query($sql, array(1));
		return $query->result_array();
	}
	public function getStoreByIdOrName($id, $name)
	{
        if (is_numeric($id)) {
            $where_data = ['id' => $id];
        } else {
            $where_data = ['name' => $name];
        }
		return $this->db->select()->from(self::TABLE)->or_where($where_data)->get()->row_array();
	}
	public function existsStoreToThisPhaseId($phase_id)
	{
		$num = $this->db->select('count(id) as qtd')->from('stores')->where(['phase_id' => $phase_id])->get()->row_array();
		return $num['qtd'] > 0;
	}

    /**
     * Minhas lojas que tenho permissão de visualizar
     *
     * @return array Retorna todas as lojas que eu tenho permissão de visualizar
     */
    public function getMyStores(): array
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND company_id = " . $this->data['usercomp'] : " AND id = " . $this->data['userstore']);
        $sql = "SELECT id FROM stores WHERE active = 1 {$more}";
        $query = $this->db->query($sql);
        $stores = $query->result_array();

        $arrStores = array();
        foreach ($stores as $store) {
            array_push($arrStores, $store['id']);
        }
        return $arrStores;
    }

	/**
	 * Procura um endereço de e-mail e tem os seguintes retornos possíveis:
	 * - "0": E-mail não encontrado;
	 * - "1": E-mail encontrado.
	 */
	public function emailLookup(string $email) {
        if (empty($email)) {
            return false;
        }

        $sql = "SELECT COUNT(id) AS qtd 
				FROM stores 
				WHERE fr_email_contato = '$email' OR 
					fr_email_nfe = '$email' OR 
					fr_email_login = '$email'";

        $query = $this->db->query($sql);
        $row = $query->row_array();

        if ($row['qtd'] >= 1) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Verifica se o usuário pode acessar a loja.
     *
     * @param   int     $store  Código da loja (stores.id).
     * @return  bool            Pode acessar a loja.
     */
    public function checkIfTheStoreIsMine(int $store): bool
    {
        // É um administrador.
        if ($this->data['usercomp'] == 1) {
            return true;
        }

        // Usuário pode ver todas as lojas da empresa.
        if ($this->data['userstore'] == 0) {
            // A loja do parâmetro está dentro da empresa do usuário.
            if (
                $this->db
                    ->from('stores')
                    ->where(array('id' => $store, 'company_id' => $this->data['usercomp']))
                    ->get()
                    ->row_array()
            ) {
                return true;
            }

            return false;
        }

        // Usuário pode ver apenas uma loja da empresa.
        if ($this->data['userstore'] != 0) {
            // A loja é a mesma do parâmetro.
            if ($this->data['userstore'] == $store) {
                return true;
            }

            return false;
        }
    }

    public function getMyCompanyStoresArrayIds(): array
    {

        $companyId = $this->data['usercomp'];
        $userStore = $this->data['userstore'];

        if ($userStore){
            return [$userStore];
        }

		//Se for empresa 1, não tem lojas, tem que poder ver tudo
        if ($companyId == 1){
            return [];
        }

        $stores = $this->getCompanyStores($companyId);

        $ids = [];
        if ($stores){
            foreach ($stores as $store){
                $ids[] = $store['id'];
            }
        }

        return $ids;

    }

    public function storeAllowConciliationInstallment(int $storeId): bool
    {

        return $this->db->select('*')->from('stores')->where(['id' => $storeId, 'allow_payment_reconciliation_installments' => 1])->get()->result_array() ? true : false;

    }

    public function getStoresByCompany($id)
    {
        $sql = "SELECT * FROM stores WHERE company_id = ? ORDER BY id ASC";
        $query = $this->db->query($sql, array($id));
        return $query->result_array();
    }

    public function getStoreAndCompanyNameByPk(int $storeId)
    {
        $sql = 'SELECT s.name as store_name, s.id as store_id, 
                        c.name as company_name, c.id as company_id 
                FROM stores s JOIN company c ON c.id = s.company_id WHERE s.id = ?';
        $query = $this->db->query($sql, array($storeId));
        return $query->row_array();
    }

		public function getStoresAntecipacao($search)
		{
						
			if(!empty($search['name'])){
				$this->db->like('name', $search['name']);
			}

			if(!empty($search['store_id'])){
				$this->db->where('id', $search['store_id']);
			}

			if(!empty($search['cnpj'])){
				$this->db->where('CNPJ', $search['cnpj']);
			}

			if(!empty($search['agency'])){
				$this->db->where('agency', $search['agency']);
			}

			if(!empty($search['account'])){
				$this->db->where('account', $search['account']);
			}
			
			$this->db->select("stores.id AS id,
				name, raz_social, CNPJ, agency, account, IF(flag_antecipacao_repasse = 'S', 'true', 'false') as antecipacao_repasse
			");	
			$this->db->where('flag_antecipacao_repasse', 'S');
			$query = $this->db->get('stores');
			


			if($query){				
				return $query->num_rows() == 1 ? $query->row() : $query->result();				
			}
			
			return null;

		}

		public function inactiveContractFromStore($store_id = null){
			if($store_id == null){
				return null;
			}

			$sql = "SELECT * FROM stores WHERE id = ?";
			$store = $this->db->query($sql, array($store_id))->row();
			if(!$store){
				return null;
			}
			
			$sql = "SELECT * FROM contract_signatures WHERE store_id = ?";
			$contract_signature = $this->db->query($sql, array($store->id))->result();
			if(!$store){
				return null;
			}

			

			foreach($contract_signature as $cs){
				$sql = "SELECT * FROM contracts c         
        JOIN attribute_value av ON av.id = c.document_type
        WHERE c.id = ? AND av.value LIKE ?";
        $type = $this->db->query($sql, array($cs->contract_id, "Contrato de Antecipação"))->row();  
				if($type){
					$this->db->where('contract_signatures.id', $cs->id);
					$this->db->update('contract_signatures', ['active' => 0]);
				}
			}								
		}

    public function setDateUpdateNow($store_id): void
    {
        $this->update(['date_update' => dateNow()->format(DATETIME_INTERNATIONAL)], $store_id);
    }

    public function storeExists(int $id): bool
    {
        $sql = "SELECT count(*) total
                FROM stores 
                WHERE id = '$id'";
        $return = $this->db->query($sql)->row_array();
        return $return['total'] > 0;
    }

    public function checkCredentialApiStore(string $token, int $store_id, string $user_email): bool
    {
        $store = $this->getStoresData($store_id);
        // Loja não encontrada.
        if (empty($store)) {
            return false;
        }

        // Token não coincide com a da loja.
        if ($store['token_api'] !== $token) {
            return false;
        }
		
        $users_by_store = $this->db->where('store_id', $store_id)->or_group_start()->where(array('company_id' => $store['company_id'], 'store_id' => 0))->group_end()->get('users')->result_array();
        $users_by_store = array_map(function($user){
            return $user['email'];
        }, $users_by_store);

        // Usuário não pertence a loja.
        if (!in_array($user_email, $users_by_store)) {
            return false;
        }

        return true;
    }

	public function getStoresSubAccountsGetnet($storeId = null)
	{
		
		$where = "";
		if(is_numeric($storeId)){
			$where = "where s.id = $storeId";
		}

		$sql = "SELECT DISTINCT * FROM getnet_subaccount getsub
				INNER JOIN stores s  ON getsub.store_id = s.id
				$where";

		$query = $this->db->query($sql);
		return $query->result_array();
	}

    /* get the active store data */
    public function getStoresByProvider(int $provider_id): array
    {
        return $this->db
            ->where(array('provider_id' => $provider_id, 'active' => true))
            ->order_by('name')
            ->get('stores')
            ->result_array();
    }

    public function updateStoresByProvider(int $provider_id= null, int $store_id = null): bool
    {
        if (is_null($provider_id) && is_null($store_id)) {
            return false;
        }

        $this->db->where('active', true);

        if (!is_null($provider_id)) {
            $this->db->where('provider_id', $provider_id);
        }

        if ($this->data['usercomp'] != 1 && is_null($store_id)) {
            if ($this->data['userstore'] == 0) {
                $this->db->where('company_id', $this->data['usercomp']);
            } else {
                $this->db->where('id', $this->data['userstore']);
            }
        } elseif (!is_null($store_id)) {
            $this->db->where('id', $store_id);
        }

        return $this->db->update('stores', array('provider_id' => null));
    }

	public function getProviderInStores(int $store_id, int $provider_id): ?array
	{
		return $this->db
			->where(array('id' => $store_id, 'provider_id' => $provider_id, 'active' => true))
			->limit(1)
			->get('stores')
			->row_array();
	}

    public function updateStoresByStores(array $stores_id, array $data): bool
    {
        $this->db->where('active', true);
        $this->db->where_in('id', $stores_id);

        if ($this->data['usercomp'] != 1) {
            if ($this->data['userstore'] == 0) {
                $this->db->where('company_id', $this->data['usercomp']);
            } else {
                $this->db->where('id', $this->data['userstore']);
            }
        }

        return $this->db->update('stores', $data);
    }

    public function getStores($stores = []){

        if(is_array($stores)){
            if(count($stores) > 0){
                $stores_imploded = implode(",", $stores);
                $this->db->where('id IN ('.$stores_imploded.')');
            }
        }

        return $this->db->get('stores')->result();
    }

    public function getStoresMultiCdByCompany($id): array
    {
        return $this->db->where('company_id', $id)->where_in('type_store', array(1, 2))->get('stores')->result_array();
    }

    /* get the active store data */
    public function getActiveStoreToSellerMigrate()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND s.company_id = " . $this->data['usercomp'] : " AND s.id = " . $this->data['userstore']);

        $sql = "SELECT s.* FROM stores AS s JOIN seller_migration_register AS smr ON s.id = smr.store_id WHERE s.active = ? AND s.flag_store_migration = ? AND smr.status = 0" . $more . ' ORDER BY name';
        $query = $this->db->query($sql, array(1, 1));
        return $query->result_array();
    }

    public function getStoresUpdatedLastMinutes(int $minutes): array
    {
        $date = subtractMinutesToDatetimeV2(dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL), $minutes);

        return $this->db->where('date_update >', $date)->get('stores')->result_array();
    }

	public function getStoresWithoutGatewaySubAccountsMagalupay()
	{

		 $sql = "SELECT DISTINCT s.*
		 		FROM stores s
				LEFT JOIN magalupay_subaccount mp ON mp.store_id = s.id
				LEFT JOIN ( select distinct store_id from payment_gateway_store_logs where description like '%rejeitado%' or description like '%reprovado%' or description like '%aguardando%' ) pgsl ON pgsl.store_id = s.id
				WHERE mp.id IS NULL AND pgsl.store_id IS NULL ";

		$query = $this->db->query($sql);
		$result = $query->result_array();
		if($result){

			foreach($result as $loja){
				$update = "update payment_gateway_store_logs set status = 'W' where store_id = ".$loja['id']." and status = 'error'";
				$this->db->query($update);
			}

		}
	
		return $result;
		
	}

	public function getStoresCheckStatusSubAccountsMagalupay()
	{

		$sql = "SELECT DISTINCT mp.* FROM stores s 
				INNER JOIN `magalupay_subaccount` mp ON mp.store_id = s.id
				LEFT JOIN gateway_subaccounts gs ON gs.store_id = s.id
				WHERE gs.id IS NULL AND ifnull(reprove_reason,'vazio') <> 'Reprovado pela análise de risco' ";

		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getallinformationsmagalupay($store_id = null){

		$where = "1=1";
		  
		if($store_id){
			$where .= " AND mp.store_id = $store_id";
		}

		$sql = "SELECT DISTINCT mp.* FROM magalupay_subaccount mp
				WHERE $where ";

		$query = $this->db->query($sql);
		if($store_id){
			return $query->row_array();
		}else{
			return $query->result_array();
		}


	}

    public function getStoreIdByUser(int $user_id): array
    {
        $user = $this->db->get_where('users', array('id' => $user_id))->row_array();
        if (!$user) {
            return [];
        }

        // Usuário da empresa principal.
        if ($user['company_id'] == 1) {
            return array_map(
                function ($store) {
                    return $store['id'];
                },
                $this->db->get_where('stores', array('active' => 1))->result_array()
            );
        }

        // Usuário pode gerenciar todas as lojas da empresa.
        if ($user['store_id'] == 0) {
            return array_map(
                function ($store) {
                    return $store['id'];
                },
                $this->db->get_where('stores', array('company_id' => $user['company_id'], 'active' => 1))->result_array()
            );
        }

        // Gerencia somente uma loja.
        return array($user['store_id']);
    }


	public function getFieldsAddFromStoreId(int $store_id)
	{

		$sql = "SELECT tid, nsu, authorization_id, first_digits, last_digits FROM fields_orders_add WHERE store_id = ?";

		$query = $this->db->query($sql, [$store_id]);
		$row = $query->row_array();

		return $row;
	}

	public function getFieldsMandatoryFromStoreId(int $store_id)
	{

		$sql = "SELECT tid, nsu, authorization_id, first_digits, last_digits FROM fields_orders_mandatory WHERE store_id = ?";

		$query = $this->db->query($sql, [$store_id]);
		$row = $query->row_array();

		return $row;
	}

	public function saveFieldsOrdersAdd(array $data){
		$store_id = $data['store_id'];

		
		$exists = $this->db
			->where('store_id', $store_id)
			->get('fields_orders_add')
			->row_array();

		if ($exists) {
			
			$this->db->where('store_id', $store_id);
			$this->db->update('fields_orders_add', [
				'tid'             => $data['tid'] ?? 0,
				'nsu'             => $data['nsu'] ?? 0,
				'authorization_id'=> $data['authorization_id'] ?? 0,
				'first_digits'    => $data['first_digits'] ?? 0,
				'last_digits'     => $data['last_digits'] ?? 0
			]);
		} else {
			
			$this->db->insert('fields_orders_add', [
				'store_id'        => $store_id,
				'tid'             => $data['tid'] ?? 0,
				'nsu'             => $data['nsu'] ?? 0,
				'authorization_id'=> $data['authorization_id'] ?? 0,
				'first_digits'    => $data['first_digits'] ?? 0,
				'last_digits'     => $data['last_digits'] ?? 0
			]);
		}
	}

	public function getStoresWithFieldsConfigured(): array {
		// pega IDs das lojas com ao menos 1 campo obrigatório
		$mandatory = $this->db->select('store_id')
			->from('fields_orders_mandatory')
			->where('(tid = 1 OR nsu = 1 OR authorization_id = 1 OR first_digits = 1 OR last_digits = 1)', null, false)
			->get()->result_array();
	
		// pega IDs das lojas com ao menos 1 campo adicional
		$additional = $this->db->select('store_id')
			->from('fields_orders_add')
			->where('(tid = 1 OR nsu = 1 OR authorization_id = 1 OR first_digits = 1 OR last_digits = 1)', null, false)
			->get()->result_array();
	
		// junta tudo e remove duplicados
		$store_ids = array_unique(array_merge(
			array_column($mandatory, 'store_id'),
			array_column($additional, 'store_id')
		));
	
		return $store_ids;
	}

	public function saveFieldsOrdersMandatory(array $data)
	{
		$store_id = $data['store_id'];

		$exists = $this->db
			->where('store_id', $store_id)
			->get('fields_orders_mandatory')
			->row_array();

		if ($exists) {

			$this->db->where('store_id', $store_id);
			$this->db->update('fields_orders_mandatory', [
				'tid'             => $data['tid'] ?? 0,
				'nsu'             => $data['nsu'] ?? 0,
				'authorization_id'=> $data['authorization_id'] ?? 0,
				'first_digits'    => $data['first_digits'] ?? 0,
				'last_digits'     => $data['last_digits'] ?? 0
			]);
		} else {

			$this->db->insert('fields_orders_mandatory', [
				'store_id'        => $store_id,
				'tid'             => $data['tid'] ?? 0,
				'nsu'             => $data['nsu'] ?? 0,
				'authorization_id'=> $data['authorization_id'] ?? 0,
				'first_digits'    => $data['first_digits'] ?? 0,
				'last_digits'     => $data['last_digits'] ?? 0
			]);
		}
	}




}
