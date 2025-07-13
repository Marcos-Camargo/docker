<?php 

class Model_whitelist extends CI_Model
{
	const ACTIVE = 1;
	public const NEW_OR_UPDATE_RULE = 1;
	public const OLD_RULE = 0;

	public function __construct()
	{
		parent::__construct();
	}

	public function getWordsDataView($offset = 0, $limit = 200)
	{
        if ($limit == false) {
            $limit = "";
        } else {
            $limit = " LIMIT " . (int) $limit . "  OFFSET {$offset}";
        }

        if (isset($this->data['orderby'])) {
            $order_by = $this->data['orderby'];
        } else {
            $order_by = ' ORDER BY id desc ';
        }

		$sql = "SELECT * FROM whitelist ";

		if (isset($this->data['wordsfilter'])) {
			$sql .= "WHERE " . substr($this->data['wordsfilter'], 4);
		}
        $sql .= $order_by.$limit;

		$query = $this->db->query($sql);

		return $query->result_array();
	}

	public function getWordsDataViewCount($filter="")
	{
		$sql = "SELECT COUNT(*) AS qtd FROM whitelist ";

		if ($filter != '') {
			$sql .= ' WHERE ' . substr($filter, 4);
		}
		$query = $this->db->query($sql);
		$row = $query->row_array();

		return $row['qtd'];
	}

	public function create($data)
	{
		$searchOnlyTheFields = $data;
		foreach ($searchOnlyTheFields as $key => $field) {
			if ($key == 'sentence' || $key == 'created_by' || $key == 'status') {
				unset($searchOnlyTheFields[$key]);
			}
		}
		$ruleAlreadyExists = $this->db->where($searchOnlyTheFields)->get('whitelist')->result_array();
		if ($ruleAlreadyExists) {
			$this->db->where('id', $ruleAlreadyExists[0]['id'])->update('whitelist', $data);
			return $ruleAlreadyExists[0]['id'];
		}

		$create = $this->db->insert('whitelist', $data);
		$id = $this->db->insert_id();

		return ($create) ? $id : false;
	}

	public function getWordById($id)
	{
		$query = $this->db->where('id', $id)->get('whitelist')->row_array();

		return $query;
	}

	public function update($id, $data)
	{
		$update = $this->db->where('id', $id)->update('whitelist', $data);

		return $update;
	}

	public function updateByPhase($phase_id,$data){
        $update = $this->db->where('phase_id', $phase_id)->update('whitelist', $data);
		return $update;
    }

	public function getNewOrUpdatedBlockingRulesConst(){
        return self::NEW_OR_UPDATE_RULE;
    }

	public function updateStatusIfExists($data)
	{
		$searchOnlyTheFields = $data;
		foreach ($searchOnlyTheFields as $key => $field) {
			if ($key == 'sentence' || $key == 'created_by' || $key == 'status') {
				unset($searchOnlyTheFields[$key]);
			}
		}
		$ruleAlreadyExists = $this->db->where($searchOnlyTheFields)->get('whitelist')->result_array();
		if ($ruleAlreadyExists) {
			$json = json_decode($ruleAlreadyExists[0]['created_by']);
			$novojson = [
				'user_id' => $this->session->userdata('id'),
                'username' => $this->session->userdata('username'),
                'email' => $this->session->userdata('email'),
                'start' => $json->start,
                'end' => date('Y-m-d H:i:s')
			];
			$novo = json_encode($novojson);
			$this->db->where('id', $ruleAlreadyExists[0]['id'])->update('whitelist', ['status' => 2, 'created_by' => $novo]);
			return;
		}

		return;
	}

	public function getActiveWords()
	{
		$query = $this->db->where('status', 1)->get('whitelist')->result_array();

		return $query;
	}

	public function searchIfWordExistsToUpdate($word, $id)
	{
		$result = $this->db->where('words', $word)->where('id !=', $id)->get('whitelist')->result_array();

		return $result;
	}

	public function searchWhitelist($dataProduct, $product_integrated = false)
	{
		$removeFromSearch = [
			'name',
			'description',
			'commission',
			'seller_index',
			'marketplace'
		];

        $query = $this->db->where('status', 1);

		foreach ($dataProduct as $key => $data) {
			if (in_array($key, $removeFromSearch)) continue;

            $query->group_start()
                ->where($key, $data)
                ->or_where($key, NULL)
            ->group_end();
		}

        if (array_key_exists('marketplace', $dataProduct) && $dataProduct['marketplace'])
            $query->where('marketplace', $dataProduct['marketplace']);
        else
            $query->where('marketplace', NULL);

		if ($product_integrated) {
			$query->where('apply_to', 1); // se o produto já integrado, somente regras que aplicam a todos os produtos serão buscadas 
		}
		
		$result = $this->db->get('whitelist')->result_array();
		
		return $result;
	}

	public function getNewOrUpdatedPermissionRules()
	{
		return $this->db->where('new_or_update', self::NEW_OR_UPDATE_RULE)->get('whitelist')->result_array();
	}

    public function searchWhitelistAndIds($dataProduct, $rulesId, $product_integrated = false)
    {
        $removeFromSearch = [
            'name',
            'description',
            'commission',
            'seller_index',
            'marketplace'
        ];

        $query = $this->db->where('status', 1);

        foreach ($dataProduct as $key => $data) {
            if (in_array($key, $removeFromSearch)) continue;

            $query->group_start()
                ->where($key, $data)
                ->or_where($key, NULL)
            ->group_end();
        }

        if (array_key_exists('marketplace', $dataProduct) && $dataProduct['marketplace'])
            $query->where('marketplace', $dataProduct['marketplace']);
        else
            $query->where('marketplace', NULL);
		
		if ($product_integrated) {
			$query->where('apply_to', 1); // se o produto já integrado, somente regras que aplicam a todos os produtos serão buscadas 
		}
		$result = $this->db->where_in('id', $rulesId)->get('whitelist')->result_array();
        return $result;
    }
	
	public function getAllWhiteListRules()
    {
        return $this->db->where('status', 1)->get('whitelist')->result_array();
    }
}