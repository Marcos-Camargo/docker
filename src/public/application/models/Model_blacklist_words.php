<?php 

class Model_blacklist_words extends CI_Model
{
	const ACTIVE 					= 1;
	public const INACTIVE 			= 2;
	public const NEW_OR_UPDATE_RULE = 1;
	public const OLD_RULE 			= 0;

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

		$sql = "SELECT * FROM blacklist_words ";

		if (isset($this->data['wordsfilter'])) {
			$sql .= "WHERE " . substr($this->data['wordsfilter'], 4);
		}
        $sql .= $order_by.$limit;

		$query = $this->db->query($sql);

		return $query->result_array();
	}

	public function getWordsDataViewCount($filter="")
	{
		$sql = "SELECT COUNT(*) AS qtd FROM blacklist_words ";

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
		$ruleAlreadyExists = $this->db->where($searchOnlyTheFields)->get('blacklist_words')->result_array();
		if ($ruleAlreadyExists) {
			$this->db->where('id', $ruleAlreadyExists[0]['id'])->update('blacklist_words', $data);
			return $ruleAlreadyExists[0]['id'];
		}
		$create = $this->db->insert('blacklist_words', $data);
		$id = $this->db->insert_id();

		return ($create) ? $id : false;
	}

	public function getWordById($id)
	{
		$query = $this->db->where('id', $id)->get('blacklist_words')->row_array();

		return $query;
	}

	public function update($id, $data)
	{
		$update = $this->db->where('id', $id)->update('blacklist_words', $data);

		return $update;
	}
    public function updateByPhase($phase_id,$data){
        $update = $this->db->where('phase_id', $phase_id)->update('blacklist_words', $data);
		return $update;
    }

    public function getNewOrUpdatedBlockingRulesConst(){
        return self::NEW_OR_UPDATE_RULE;
    }

	public function createProductWithLock($data)
	{
		$this->db->delete('products_with_lock', ['product_id' => $data[0]['product_id']]);
		foreach ($data as $dat) {
			$this->db->insert('products_with_lock', $dat);
		}
	}

	public function deleteProductWithLock($productId)
	{
		$this->db->delete('products_with_lock', ['product_id' => $productId]);
	}

	public function getAllProductLocks($productId)
	{
		return $this->db->where('product_id', $productId)->get('products_with_lock')->result_array();
	}

	public function getNewOrUpdatedBlockingRules()
	{
		return $this->db->where('new_or_update', self::NEW_OR_UPDATE_RULE)->get('blacklist_words')->result_array();
	}

    public function getDataBlackListActive($product, $product_integrated = false)
    {
        $ignoreField = array(
            'name',
            'description',
            'commission',
            'seller_index',
            'marketplace'
        );

        $query = $this->db->where('status', 1);

        foreach ($product as $key => $field) {

            if (in_array($key, $ignoreField)) continue;

            $query->group_start()
                ->where($key, $field)
                ->or_where($key, NULL)
            ->group_end();
        }

        if (array_key_exists('marketplace', $product) && $product['marketplace'])
            $query->where('marketplace', $product['marketplace']);
        else
            $query->where('marketplace', NULL);
		
		if ($product_integrated) {
			$query->where('apply_to', 1); // se o produto já integrado, somente regras que aplicam a todos os produtos serão buscadas 
		}
		
		$result = $query->get('blacklist_words')->result_array();
        return $result;
    }

    public function createProductWithLockByProduct($data, $prd_id)
    {
        $this->db->delete('products_with_lock', ['product_id' => $prd_id]);
        foreach ($data as $dat) {
            $this->db->insert('products_with_lock', $dat);
        }
    }

    public function getDataBlackListActiveAndIds($product, $rulesId, $product_integrated = false)
    {
        $ignoreField = array(
            'name',
            'description',
            'commission',
            'seller_index',
            'marketplace'
        );

        $query = $this->db->where('status', 1);

        foreach ($product as $key => $field) {

            if (in_array($key, $ignoreField)) continue;

            $query->group_start()
                ->where($key, $field)
                ->or_where($key, NULL)
            ->group_end();
        }

        if (array_key_exists('marketplace', $product) && $product['marketplace'])
            $query->where('marketplace', $product['marketplace']);
        else
            $query->where('marketplace', NULL);
		
		if ($product_integrated) {
			$query->where('apply_to', 1); // se o produto já integrado, somente regras que aplicam a todos os produtos serão buscadas 
		}
		
		$result = $query->where_in('id', $rulesId)->get('blacklist_words')->result_array();
		
        return $result;
    }

    public function createRuleWithLock(array $datas)
    {
        $this->deleteProductWithLock($datas[0]['product_id']);

        $blacklistUsed = array();
        foreach ($datas as $data) {
            if (in_array($data['blacklist_id'], $blacklistUsed)) continue;
            array_push($blacklistUsed, $data['blacklist_id']);

            $this->db->insert('products_with_lock', $data);
        }
    }

    public function getProductLockByPrdId($prd_id)
    {
        $rules = array();
        foreach ($this->db->where('product_id', $prd_id)->get('products_with_lock')->result_array() as $rule) {
            array_push($rules, $this->getWordById($rule['blacklist_id']));
        }
        return $rules;
    }

    public function getAllBlockingRules()
    {
        return $this->db->where('status', 1)->get('blacklist_words')->result_array();
    }
}