<?php
use App\Libraries\Enum\LegalPanelNotificationType;

class Model_legal_panel_fiscal extends CI_Model
{
    private $tableName = 'legal_panel_fiscal';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_stores');
    }
    

    public function getDataById($id)
    {
        $sql = "SELECT * FROM `legal_panel_fiscal` WHERE id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }

    public function getDataByStoreAmountConciliation($storeId, $conciliationId, $amount)
    {
        $sql = "SELECT * FROM `legal_panel_fiscal` WHERE store_id = ? AND balance_debit = ? AND conciliacao_id = ?";
        $query = $this->db->query($sql, array($storeId, $amount, $conciliationId));
        return $query->row_array();
    }

	public function getAll($status)
	{

        if ($status == 'open'){
            $status = 'Chamado Aberto';
        }
        if ($status == 'closed'){
            $status = 'Chamado Fechado';
        }
        if ($status == 'all'){
            $status = '';
        }

		$sql = "SELECT * FROM `legal_panel_fiscal`";
        if ($status){
            $sql.= " WHERE status = '$status' ";
        }
        $sql.= " ORDER BY id ";
        $query = $this->db->query($sql);
        return $query->result_array();
		
	}

	public function getAllOthersBetweenDateByStore(int $storeId, string $dateStart = null, string $dateEnd = null)
	{

        $this->db->select("*");
        $this->db->from($this->tableName);
        $this->db->where('notification_type', LegalPanelNotificationType::OTHERS);
        $this->db->where('store_id', $storeId);
        if ($dateStart){
            $this->db->where('creation_date >=', $dateStart.' 00:00:00');
        }
        if ($dateEnd){
            $this->db->where('creation_date <=', $dateEnd.' 00:00:00');
        }

        return $this->db->get()->result_array();

	}

	public function getAllBetweenDate(string $dateStart = null, string $dateEnd = null)
	{

        $usersStores = $this->model_stores->getMyCompanyStoresArrayIds();
        $usersStoresString = implode(',', $usersStores);

        $selects = [];
        $selects[] = $this->tableName.'.*';
        $selects[] = 'DATE_FORMAT('.$this->tableName.'.creation_date,"%d/%m/%Y") AS notification_date';
        $selects[] = 'COALESCE(stores.name, store_legal_panel.name) AS store_name';
        $selects[] = 'orders.paid_status';
        $selects[] = 'DATE_FORMAT(orders.data_envio,"%d/%m/%Y") AS data_envio';
        $selects[] = 'DATE_FORMAT(orders.date_time,"%d/%m/%Y") AS order_datetime';
        $selects[] = 'DATE_FORMAT(orders.data_entrega,"%d/%m/%Y") AS order_date_delivery';
        $selects[] = 'data_pagamento_marketplace(orders.id) AS data_pagamento_mktplace ';
        $selects[] = 'data_pagamento_conecta(orders.id) AS data_pagamento_conectala';
        $selects[] = 'conciliacao_sellercenter.valor_repasse';
        $selects[] = 'conciliacao_sellercenter.current_installment';
        $selects[] = 'conciliacao_sellercenter.total_installments';
        $selects[] = 'iugu_repasse.data_transferencia';

        $this->db->select(implode(',', $selects));
        $this->db->from($this->tableName);
        $this->db->join('orders', 'orders.id = ' . $this->tableName . '.orders_id', 'left');
        $this->db->join('stores', 'stores.id = orders.store_id', 'left');
        $this->db->join('stores AS store_legal_panel', 'store_legal_panel.id = legal_panel_fiscal.store_id', 'left');
        $this->db->join('iugu_repasse', 'iugu_repasse.legal_panel_id = ' . $this->tableName . '.id', 'left');
        $this->db->join('conciliacao_sellercenter', 'stores.id = ' . $this->tableName . '.store_id AND conciliacao_sellercenter.legal_panel_id = '.$this->tableName.'.id', 'left');
        if ($dateStart){
            $this->db->where('creation_date >=', $dateStart.' 00:00:00');
        }
        if ($dateEnd){
            $this->db->where('creation_date <=', $dateEnd.' 23:59:59');
        }
        if ($usersStoresString){
            $this->db->where('stores.id IN ('.$usersStoresString.')');
        }
        $this->db->group_by($this->tableName.'.id');

        return $this->db->get()->result_array();

	}

    public function create($data)
    {
        if($data) {
            $insert = $this->db->insert('legal_panel_fiscal', $data);
            if (isset($this->session)){
                $this->session->set_flashdata('success', '<span class="glyphicon glyphicon-ok-sign"></span> Criado com sucesso!');
            }
            return ($insert == true) ? true : false;
        }
        return false;
    }
    
	public function update($data, $id)
    {
        if($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('legal_panel_fiscal', $data);
            if (isset($this->session)){
                $this->session->set_flashdata('success', '<span class="glyphicon glyphicon-ok-sign"></span> Editado com sucesso!');
            }
            return ($update == true) ? true : false;
        }
		return false;
    }
	
	public function remove($id)
    {
        if($id) {
            $this->db->where('id', $id);
            $delete = $this->db->delete('legal_panel_fiscal');
            return ($delete == true) ? true : false;
        }
		return false;
    }

    /**
     * @param int $orderId
     * @param int $notificationId
     * @param string $status
     * @param string $description
     * @param float $debitValue
     * @param string $username
     * @return bool
     */
    public function createDebit(int $orderId, string $notificationId, string $status, string $description, float $debitValue, string $username): bool
    {

        $data = [
            'notification_type' => LegalPanelNotificationType::ORDER,
            'orders_id' => $orderId,
            'notification_id' => $notificationId,
            'status' => $status,
            'description' => $description,
            'balance_paid' => $debitValue,
            'balance_debit' => $debitValue,
            'attachment' => null,
            'creation_date' => date_create()->format(DATE_INTERNATIONAL),
            'update_date' => date_create()->format(DATE_INTERNATIONAL),
            'accountable_opening' => $username,
            'accountable_update' => $username,
        ];

        return $this->create($data);

    }


    public function createDebitToStore(int $storeId, string $title, string $notificationId, string $status, string $description, float $debitValue, string $creationDate, string $username): bool
    {

        $data = [
            'notification_type' => LegalPanelNotificationType::OTHERS,
            'notification_title' => $title,
            'store_id' => $storeId,
            'notification_id' => $notificationId,
            'status' => $status,
            'description' => $description,
            'balance_paid' => $debitValue,
            'balance_debit' => $debitValue,
            'attachment' => null,
            'creation_date' => $creationDate,
            'update_date' => date_create()->format(DATE_INTERNATIONAL),
            'accountable_opening' => $username
        ];

        return $this->create($data);

    }

    public function isNotificationStoreAlreadyRegistered(string $notificationCode, int $storeId): bool
    {

        $row = $this->db->select('count(*) as count')
            ->from('legal_panel_fiscal')
            ->where('notification_id', $notificationCode)
            ->where('notification_type', LegalPanelNotificationType::OTHERS)
            ->where('store_id', $storeId)
            ->get()
            ->row_array();

        return $row['count'] > 0;

    }


    public function getDataByLotAndValue($lot, $value): ?array
    {
        $sql = "
                SELECT 
                    legal.*
                FROM 
                    legal_panel_fiscal legal
                    inner join conciliacao_sellercenter cs ON legal.id = cs.legal_panel_id
                WHERE 
                    cs.lote = '".$lot."'
                AND 
                    cs.legal_panel_id > 0 
                AND 
                    cs.valor_repasse = ".floatVal($value)."
                AND
                    legal.status = 'Chamado Aberto'
                ";

        $query = $this->db->query($sql);
        return ($query && $query->num_rows() > 0) ? $query->row_array() : null;
    }


    public function getAPiListItems($filters_array): array
    {
        $sql = "";
        
        foreach ($filters_array as $key => $val)
        {
            $$key = $val;
        }

        $sql_start = "
                SELECT
                    id
                    ,notification_type
                    ,notification_title
                    ,case when orders_id = 0 then '' else orders_id end as order_id
                    ,case when store_id = 0 then '' else store_id end as store_id
                    ,notification_id
                    ,case when status = 'Chamado Aberto' then 'open' else 'closed' end as status
                    ,description
                    ,balance_paid as amount
                    ,case when attachment is null then '' end as attachment
                    ,creation_date as date_time
                FROM
                    legal_panel_fiscal
                WHERE
                    1=1
                ";

        if ($start_date && $end_date)
        {
            $sql .= " AND DATE(creation_date) BETWEEN '" . $start_date . "' AND '" . $end_date . "' ";
        }
        else if ($start_date)
        {
            $sql .= " AND DATE(creation_date) >= '" . $start_date . "' ";
        }
        else if ($end_date)
        {
            $sql .= " AND DATE(creation_date) <= '" . $end_date . "' ";
        }

        if ($status)
        {
            $sql .= " AND status = ";
            $sql .= ($status == 'open') ? "'Chamado Aberto'" : "'Chamado Fechado'";
        }

        if ($notification_id)
        {
            $sql .= " AND notification_id LIKE '%" . $notification_id . "%' ";
        }

        if ($description)
        {
            $sql .= " AND description LIKE '%" . $description . "%' ";
        }

        if ($attachment)
        {
            $sql .= " AND attachment LIKE '%" . $attachment . "%' ";
        }

        if ($greater_amount)
        {
            $sql .= " AND balance_paid >= ".$greater_amount;
        }

        if ($less_amount)
        {
            $sql .= " AND balance_paid <= ".$less_amount;
        }

        if ($type)
        {
            $sql .= " AND notification_type = '".$type."' ";
        }

        if ($title)
        {
            $sql .= " AND notification_title LIKE '%".$title."%' ";
        }

        if ($order_id)
        {
            $sql .= " AND orders_id = ".$order_id;
        }

        if ($store_id)
        {
            $sql .= " AND store_id = ".$store_id;
        }

        $limit = " LIMIT ".(($page - 1) * $per_page).", ".$per_page;

        $query = $this->db->query($sql_start.$sql.$limit);

        $list_result = $query->result_array();
        $header_result = [];

        $sql_total_rows = "select count(id) as total_rows from legal_panel_fiscal where 1=1 ".$sql;
        $query_rows = $this->db->query($sql_total_rows);

        $header_result['total_registers'] = intVal($query_rows->row_array()['total_rows']);
        $header_result['registers_count'] = intVal($query->num_rows());
        $header_result['pages_count'] = ($header_result['total_registers'] > 0) ? intVal(ceil($header_result['total_registers'] / $per_page)) : 0;
        $header_result['current_page'] = $page;

        return ['header' => $header_result, 'result' => $list_result];

    }

    public function createLegalPanelLastId()
    {

        return $this->db->insert_id();
        
    }

    public function insertOrdersCommisionCharges($data) 
    {
        if($data) {
            $insert = $this->db->insert('orders_commision_charges', $data);
            return ($insert == true) ? true : false;
        }
        return false;
    }

    public function getDataOrdersCommisionChargesByOrderId($id)
    {
        $sql = "SELECT occ.*, u.email, u.firstname, u.lastname,DATE_FORMAT(occ.date_create , '%d/%m/%Y %H:%i:%S') as data_criacao_formatada FROM `orders_commision_charges` occ
        inner join users u on u.id = occ.users_id
         WHERE occ.order_id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }

    public function isNotificationOrderComissionRefundAlreadyRegistered(int $orderId): bool
    {

        $row = $this->db->select('count(*) as count')
            ->from('legal_panel_fiscal')
            ->where('orders_id', $orderId)
            ->where('notification_type', LegalPanelNotificationType::ORDER)
            ->where('notification_id', 'Estorno de comissão Cobrada')
            ->where('notification_title', 'Estorno de comissão Cobrada')
            ->where('description', 'Estorno de comissão Cobrada')
            ->get()
            ->row_array();

        return $row['count'] > 0;

    }

    public function encerraJuridicoFiscalPelaConciliacao($lote, $storeId){
        
        $sql = "UPDATE legal_panel_fiscal SET status = 'Chamado Fechado'
        WHERE id in (select legal_panel_id from conciliacao_sellercenter_fiscal where legal_panel_id is not null and lote = '$lote' and store_id = $storeId)";
        return $this->db->query($sql);

    }

}
