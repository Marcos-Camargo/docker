<?php 
/*

Model de Acesso ao BD para tabela de fretes de pedidos  

*/

require_once APPPATH . "libraries/Microservices/v1/Integration/Price.php";
require_once APPPATH . "libraries/Microservices/v1/Integration/Stock.php";

use Microservices\v1\Integration\Stock;
use Microservices\v1\Integration\Price;

/**
 * @property CI_DB_query_builder $db
 * @property CI_Loader $load
 *
 * @property Model_campaigns_v2 $model_campaigns_v2
 *
 * @property Stock $ms_stock
 * @property Price $ms_price
 */
class Model_products_marketplace extends CI_Model
{
	public function __construct()
	{
		parent::__construct();

        $this->load->library("Microservices\\v1\\Integration\\Stock", array(), 'ms_stock');
        $this->load->library("Microservices\\v1\\Integration\\Price", array(), 'ms_price');
	}

    /**
     * Atualizar o preço e/ou estoque do produto no microsserviço.
     *
     * @param   string|null     $marketplace    Apelico do marketplace.
     * @param   int|null        $product_id     Código do produto.
     * @param   int|string|null $variant        Ordem da variação.
     * @param   array           $data           Dados de atualização.
     * @param   int|null        $mkt_prd_id     Código do produto no marketpalce.
     * @return  void
     */
    protected function updatePriceAndStockMicroservice(?string $marketplace, ?int $product_id, $variant, array $data, int $mkt_prd_id = null)
    {
        if (empty($data['price']) && empty($data['list_price']) && empty($data['qty'])) {
            return;
        }

        $variant = $variant === '' ? null : $variant;

        if (($marketplace === null || $product_id === null) && $mkt_prd_id !== null) {
            $data_marketplace_product = $this->getData($mkt_prd_id);

            if (empty($data_marketplace_product)) {
                return;
            }

            $marketplace = $data_marketplace_product['int_to'];
            $product_id  = $data_marketplace_product['prd_id'];
            $variant     = $data_marketplace_product['variant'];
            $variant     = $variant === '' ? null : $variant;
        }

        try {
            if ($this->ms_price->use_ms_price && (!empty($data['price']) || !empty($data['list_price']))) {
                $this->ms_price->updateMarketplacePrice($product_id, $variant, $marketplace, $data['price'] ?? null, $data['list_price'] ?? null);
            }

            if ($this->ms_stock->use_ms_stock && !empty($data['qty'])) {
                $this->ms_stock->updateMarketplaceStock($product_id, $variant, $marketplace, $data['qty']);
            }
        } catch (Exception $exception) {
            // Se der erro, por enquanto, não faz nada.
        }
    }

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('products_marketplace', $data);
			return ($insert == true) ? true : false;
		}
	}

	public function update($data, $id)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('products_marketplace', $data);

            $this->updatePriceAndStockMicroservice(null, null, null, $data, $id);
		}
		return false;
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('products_marketplace');
			return ($delete == true) ? true : false;
		}
		return false;
	}
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('products_marketplace', $data);
			return ($insert == true) ? true : false;
		}
		return false;
	}
	
	public function getData($id)
	{
		if($id) {
			$sql = 'SELECT * FROM products_marketplace WHERE id=?';
			$query = $this->db->query($sql,array($id));
       		return $query->row_array();
		}
		return false;
	}
	
	public function updateGet($data, $id)
	{
		if($data && $id) {
			$this->update($data, $id);
			return $this->getData($id);
		}
		return false;
	}
	
	public function getDataByUniqueKey($int_to, $prd_id, $variant = '') 
	{
		if (($int_to) && ($prd_id)) {
			$sql = 'SELECT * FROM products_marketplace WHERE int_to = ? AND prd_id = ? AND variant = ?';
			$query = $this->db->query($sql,array($int_to, $prd_id, $variant));
			return $query->row_array();
		}
		else {
			return false;
		}
	}
	
	public function newProduct($prd_id) {
		$this->load->model('model_products');
		$this->load->model('model_integrations');
		$prd = $this->model_products->getProductData(0,$prd_id);
		$integrations = $this->model_integrations->getIntegrationsbyStoreId($prd['store_id']); 
		foreach($integrations as $integration) {
			$this->createIfNotExist($integration['int_to'],$prd_id, $integration['int_type']=='DIRECT' || $integration['int_from']=='HUB');
		}	
	}
	
	public function deleteVariants($int_to,$prd_id)
	{
		$sql = 'DELETE FROM products_marketplace WHERE int_to = ? AND prd_id = ? AND variant != ""';
		$query = $this->db->query($sql,array($int_to, $prd_id));
	}
	
	
	public function createIfNotExist($int_to,$prd_id, $hub)
	{
		$this->load->model('model_products');
		$prd = $this->model_products->getProductData(0,$prd_id);
		if ($prd['is_kit'] == 1) { // kit não tem valor por marketplace por enquanto. 
			return true;  
		}
		if ($prd['has_variants'] == '') {
			$prd_mkt_test = $this->getDataByUniqueKey($int_to,$prd_id, '0');
			if ($prd_mkt_test) { // tinha Variação mas removeu. tenho que acertar o registro e apagar os demais. 
				$prd_mkt_test['variant'] = '';
				$this->update($prd_mkt_test, $prd_mkt_test['id']);
				
				$this->deleteVariants($int_to,$prd_id);
			}
			else  {
				$prd_mkt = $this->getDataByUniqueKey($int_to,$prd_id);
				if ($prd_mkt) {
					return true;
				}
				else {
					$data = array (
						'prd_id' => $prd_id,
						'variant' => '',
					  	'int_to' => $int_to,
					  	'status' => 1,
					  	'same_price' => true,
					  	'price' => $prd['price'],
					  	'hub' => $hub,
					  	'same_qty' => true,
					  	'qty' => $prd['qty'],
					);
					$this->create($data);
					return true;
				}
			}
		}
		else {
			$prd_mkt_test = $this->getDataByUniqueKey($int_to,$prd_id);
			if ($prd_mkt_test) { // não tinha tinha Variação mas colocou. tenho que acertar o registro e deixar gerar os demais.. 
				$prd_mkt_variant = $this->getDataByUniqueKey($int_to,$prd_id, '0');
				if ($prd_mkt_variant) { // ja tem o registro de variant 
					$data = $prd_mkt_variant;
					$this->remove($prd_mkt_test['id']);
				}else { // troco o registro antigo para ser vairação 
					$prd_mkt_test['variant'] = '0';
					$this->update($prd_mkt_test, $prd_mkt_test['id']);
					$data = $prd_mkt_test;
				}
			}
			else {
				$data = array (
						'prd_id' => $prd_id,
					  	'int_to' => $int_to,
					  	'status' => 1,
					  	'price' => $prd['price'],
					  	'same_price' => true,
					  	'hub' => $hub,
					  	'same_qty' => true,
					);
			}
			$variants = $this->model_products->getVariants($prd_id);
			foreach ($variants as $variant) {
				$prd_mkt = $this->getDataByUniqueKey($int_to,$prd_id,$variant['variant']);
				if (!$prd_mkt) {
					$data['id'] = 0;
					$data['variant'] = $variant['variant'];
					$data['qty'] = $variant['qty'];
					$this->create($data);
				}
			}
			return true;
		}		
	}
	
	function getProductsDataView($offset = 0, $procura = '', $sOrder = '', $limit = 200 ) {
		$more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);
       
	   	if ($offset=='') {$offset=0;}
		if ($limit=='') {$limit=200;}
	   
		$sql = 'SELECT pm.*, p.name, p.sku, p.price as prdprice, p.qty as prdqty, p.status as prdstatus, p.situacao as prdsituacao, s.name as loja, p.has_variants, p.is_kit, p.store_id, i.name AS mktname';
		$sql.= ' FROM products_marketplace pm, products p, integrations i';
		$sql.= ' LEFT JOIN stores s ON i.store_id=s.id ';
		$sql.= ' WHERE p.id = pm.prd_id AND (pm.hub = true OR (pm.variant="" OR pm.variant="0")) AND pm.int_to=i.int_to AND p.store_id=i.store_id';
		$sql.= $procura.$more.$sOrder.' LIMIT '.$limit.' OFFSET '.$offset;
	    //$this->session->set_flashdata('success', $sql);
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	function getProductsDataCount($procura = '') {
		$more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND p.company_id = " . $this->data['usercomp'] : " AND p.store_id = " . $this->data['userstore']);
       
		$sql = 'SELECT count(*) AS qtd ';
		$sql.= ' FROM products_marketplace pm, products p, integrations i';
		$sql.= ' LEFT JOIN stores s ON i.store_id=s.id ';
		$sql.= ' WHERE p.id = pm.prd_id AND (pm.hub = true OR (pm.variant="" OR pm.variant="0")) AND pm.int_to=i.int_to AND p.store_id=i.store_id';
		$sql.= $procura.$more;
		// $this->session->set_flashdata('success', $sql);
		$query = $this->db->query($sql);
        $row = $query->row_array();
        return $row['qtd'];
	}

	public function getAllDataByProduct($prd_id) 
	{
		if  ($prd_id) {
			$sql = 'SELECT * FROM products_marketplace WHERE prd_id = ? ORDER BY int_to, variant';
			$query = $this->db->query($sql,array($prd_id));
			return $query->result_array();
		}
		else {
			return false;
		}
	}

	public function getAllDataByIntToProduct($int_to, $prd_id) 
	{
		if (($int_to) && ($prd_id)) {
			$sql = 'SELECT * FROM products_marketplace WHERE int_to = ? AND prd_id = ?';
			$query = $this->db->query($sql,array($int_to, $prd_id));
			return $query->result_array();
		}
		else {
			return false;
		}
	}

	function updateAllVariants($data, $int_to, $prd_id) {
		if (($int_to) && ($prd_id)) {
			$recs = $this->getAllDataByIntToProduct( $int_to, $prd_id);
			foreach($recs as $rec) {
				$this->update($data, $rec['id']);
                $variant = $rec['variant'] === '' ? null : $rec['variant'];
                $this->updatePriceAndStockMicroservice($rec['int_to'], $rec['prd_id'], $variant, $data);
			}
		}
		else {
			return false;
		}
	}
	
	function getPriceProduct($prd_id, $price, $int_to, $has_variant='')
	{

        $this->load->model('model_campaigns_v2');

		$variant = '';
		if ($has_variant != '') {
			$variant = '0';  // por enquanto não temos o preço por variação. 
		}

		$prd_mkt = $this->getDataByUniqueKey($int_to, $prd_id,$variant);
		if (!$prd_mkt) {  // não achou !! não deveria acontecer mas aconteceu
			return $price;
		}
		if ($prd_mkt['same_price']) { // é para usar o preço do produto normal
			return $price;
		}
		if ($prd_mkt['price'] == '') {
			return $price;
		}
		return $prd_mkt['price'];   // retorna o preço deste marketplace 
	}
	
	function getQtyProduct($prd_id, $qty, $int_to, $variant = '')   // ainda não é chamado, será chamado quando virarmos hub
	{
		$prd_mkt = $this->getDataByUniqueKey($int_to, $prd_id,$variant); 
		if (!$prd_mkt) {  // não achou !! não deveria acontecer mas aconteceu
			return $qty;
		}
		if ($prd_mkt['same_qty']) { // é para usar a qty do produto normal
			return $qty;
		}
		return $prd_mkt['qty']; // retorna a quantidade deste marketplace 
	}
	
}