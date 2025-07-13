<?php 
/*

Model de Acesso ao BD para tabela de fretes de pedidos  

*/  

class Model_atributos_categorias_marketplaces extends CI_Model
{

    private const TABLE = 'atributos_categorias_marketplaces';

	public function __construct()
	{
		parent::__construct();
		$this->load->model('model_categorias_marketplaces');
	}

	
	public function getData($id_integration= null, $id_atributo =null ,$id_categoria = null)
	{
		if (($id_integration) && ($id_atributo) && ($id_categoria)) {
			$sql = "SELECT * FROM atributos_categorias_marketplaces WHERE id_integration = ? AND id_atributo = ? AND id_categoria =?";
			$query = $this->db->query($sql, array((int)$id_integration, (string)$id_atributo, (string)$id_categoria));
			return $query->row_array();
		}

		$sql = "SELECT * FROM atributos_categorias_marketplaces";
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getDataSemItegracao($offset = 0,$orderby = 'ORDER BY usados DESC, a.id_atributo ASC, c.nome ASC', $procura = '')
	{
	/*	$sql = "SELECT s.apelido AS marketplace, a.id_atributo AS atributo, am.nome AS nome_atributo, c.nome AS categoria, 
					c.id as categoria_id, a.id_integration as id_integration
				FROM atributos_categorias_marketplaces a 
				LEFT JOIN stores_mkts_linked s ON s.id_mkt= a.id_integration 
				LEFT JOIN categorias_todos_marketplaces c ON c.id = a.id_categoria 
				LEFT JOIN atributos_marketplaces am ON am.id = a.id_atributo 
				WHERE a.integrado is null ".$procura." ".$orderby." LIMIT 5000 OFFSET ".$offset;
	*/
		$sql = " SELECT s.apelido AS marketplace, a.id_atributo AS atributo, am.nome AS nome_atributo, c.nome AS categoria, ";
		$sql .=	     "c.id as categoria_id, a.id_integration as id_integration, IF ( cml.id_loja IS NULL, FALSE, TRUE) as usado "; 
		$sql .=	" FROM atributos_categorias_marketplaces a ";
		$sql .=	" LEFT JOIN stores_mkts_linked s ON s.id_mkt= a.id_integration "; 
		$sql .=	" LEFT JOIN categorias_todos_marketplaces c ON c.id = a.id_categoria "; 
		$sql .=	" LEFT JOIN atributos_marketplaces am ON am.id = a.id_atributo ";
		$sql .=	" LEFT JOIN categories_mkts_linked cml ON cml.id_loja = a.id_categoria "; 
		$sql .=	" WHERE a.integrado is null ".$procura." ".$orderby." LIMIT 200 OFFSET ".$offset;

///get_instance()->log_data('Products','fetchsearch',print_r($sql,true));
		$query = $this->db->query($sql);
		return $query->result_array();

	}
	
	public function getCategoriaLocal($id_loja) {
			
		$sql =	'SELECT * FROM categories WHERE id IN ';
		$sql.=  '(SELECT id_cat FROM categories_mkts WHERE id_integration =13 AND id_mkt IN ';
		$sql.=  '(SELECT id_mkt FROM categories_mkts_linked WHERE id_loja= ? AND id_integration = 11 AND id_type=13))';
		$query = $this->db->query($sql, array($id_loja));
		return $query->result_array();
		
	}
	
	public function getCountSemItegracao($procura = '')
	{
		if ($procura == "") {
			$sql = " SELECT count(*) as qtd  "; 
			$sql .=	" FROM atributos_categorias_marketplaces a ";
			$sql .=	" WHERE a.integrado is null "; 		
		} else {
			$sql = " SELECT count(*) as qtd  "; 
			$sql .=	" FROM atributos_categorias_marketplaces a ";
			$sql .=	" LEFT JOIN stores_mkts_linked s ON s.id_mkt= a.id_integration "; 
			$sql .=	" LEFT JOIN categorias_todos_marketplaces c ON c.id = a.id_categoria "; 
			$sql .=	" LEFT JOIN atributos_marketplaces am ON am.id = a.id_atributo ";
			$sql .=	" WHERE a.integrado is null ".$procura;
		}
		 
		
		// get_instance()->log_data('Products','count',print_r($sql,true),"I");

		$query = $this->db->query($sql, array());
		$row = $query->row_array();
		return $row['qtd'];
		
	////	get_instance()->log_data('Products','fetchsearch',print_r($sql,true));
		
		$query = $this->db->query($sql);
		return count($query->result_array());

	}

	public function create($data)
	{
		if($data) {
			$insert = $this->db->insert('atributos_categorias_marketplaces', $data);
			return ($insert == true) ? true : false;
		}
	}

	public function update($data, $id_integration, $id_atributo,$id_categoria)
	{
		if($data && $id) {
			$this->db->where('id', $id);
			$update = $this->db->update('atributos_categorias_marketplaces', $data);
			
		}
	}

	public function remove($id_integration, $id_atributo,$id_categoria)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('atributos_categorias_marketplaces');
			return ($delete == true) ? true : false;
		}
	}
	
	public function replace($data)
	{
		if($data) {
			$insert = $this->db->replace('atributos_categorias_marketplaces', $data);
			return ($insert == true) ? true : false;
		}
	}
	
	
	public function setMarcaInt($id_integration, $id_atributo,$id_categoria, $valor){
		$sql = "UPDATE atributos_categorias_marketplaces SET integrado = ? WHERE id_integration = ? AND id_atributo = ? AND id_categoria = ?";
		
		$cmd = $this->db->query($sql, array($valor, $id_integration, (string)$id_atributo, (string)$id_categoria));
		
		return ;  
	}
	
	public function getCategoryMkt($id_cat, $id_integration = 13)
	{
		$sql = "SELECT id_mkt FROM categories_mkts WHERE id_cat = ? and id_integration = ?";
		$query = $this->db->query($sql, array($id_cat, $id_integration));
		return $query->row_array();
	}
	
	public function getAttributesByCategoryMkt($int_to, $category_id) {
		$sql = "SELECT * FROM atributos_categorias_marketplaces
				WHERE id_categoria = ? and int_to = ?
				order by obrigatorio, nome 
		";
		
		$query = $this->db->query($sql, array((string)$category_id, $int_to));
		return $query->result_array();
	}

	public function getAllAtributesByCategory($category_id) {
		$sql = "select ctm.category_id , acm.id_atributo, acm.nome, acm.obrigatorio, acm.variacao, acm.tipo, acm.valor 
		from categorias_marketplaces ctm 
			join atributos_categorias_marketplaces acm on acm.id_categoria = ctm.category_marketplace_id and acm.int_to = ctm.int_to 
		where ctm.category_id = ? ";
		
		$query = $this->db->query($sql, array($category_id));
		return $query->result_array();
	}

	public function getAllAtributesVariantByCategory($int_to, $category_id) {
		$sql = "select ctm.category_id , acm.id_atributo, acm.nome, acm.obrigatorio, acm.variacao, acm.tipo, acm.valor 
		from categorias_marketplaces ctm 
			join atributos_categorias_marketplaces acm on acm.id_categoria = ctm.category_marketplace_id and acm.int_to = ctm.int_to 
		where acm.variacao = 1 and acm.int_to = ? and ctm.category_marketplace_id = ? ";
		
		$query = $this->db->query($sql, array($int_to, $category_id));
		return $query->result_array();
	}

	public function getAttributesByNameWithVariants($int_to, $category_id, $name) {
		$sql = "select * from atributos_categorias_marketplaces 
		where int_to = ? and id_categoria = ? and nome = ?";

		$query = $this->db->query($sql, array($int_to, $category_id, $name));
		return $query->row_array();
	}

	public function getAttributesByName($int_to, $category_id, $name) {
		$sql = "select * from atributos_categorias_marketplaces 
		where variacao = 0 and int_to = ? and id_categoria = ? and nome = ?";
		$query = $this->db->query($sql, array($int_to, $category_id, $name));
		return $query->row_array();
	}

	public function getAttributesVariantByName($int_to, $category_id, $name) {
		$sql = "select * from atributos_categorias_marketplaces 
		where variacao = 1 and int_to = ? and id_categoria = ? and nome = ?";
		$query = $this->db->query($sql, array($int_to, $category_id, $name));
		return $query->row_array();
	}

	public function getCategoryMktML($id_mkt)
	{
		$sql = "SELECT id_loja FROM categories_mkts_linked WHERE id_mkt = ? and id_type = 13 and id_integration = 11";
		$query = $this->db->query($sql, array($id_mkt));
		return $query->row_array();
	}

	public function getAtributosCategoriaML($id_categoria, $id_integration=11)
	{
		/*
		$sql = "SELECT am.*, acm.obrigatorio FROM atributos_marketplaces am 
				LEFT JOIN atributos_categorias_marketplaces acm ON am.id = acm.id_atributo
				WHERE acm.id_integration = ? AND acm.id_categoria = ?
				order by acm.obrigatorio, nome 
		";
		*/
		$sql = "SELECT * FROM atributos_categorias_marketplaces
				WHERE id_integration = ? AND id_categoria = ?
				order by obrigatorio, nome 
		";
		
		$query = $this->db->query($sql, array($id_integration, (string)$id_categoria));
		return $query->result_array();
	}
	
	public function getAllAtributosMarketplaces()
	{

		$sql = "SELECT *  FROM atributos_marketplaces";
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getAllProdutosAtributos($id_product)
	{
		$sql = "SELECT *  FROM produtos_atributos_marketplaces WHERE id_product = ? ";
		$query = $this->db->query($sql,array($id_product));
		return $query->result_array();
	}
	
	public function getProductAttributeById($id_product, $id_atributo)
	{

		$sql = "SELECT *  FROM produtos_atributos_marketplaces WHERE id_product = ? AND id_atributo = ?";
		$query = $this->db->query($sql,array($id_product,$id_atributo));
		return $query->row_array();
	}

	public function saveBrandName($id_product, $id_brand)
	{
		$sql = "select name from brands where id = ?";
		$query = $this->db->query($sql, array($id_brand));

		if (!$query->result_array() == null) {
			$data = [
				'id_product'  => $id_product,
				'id_atributo' => 'BRAND',
				'valor'       => $query->result_array()[0]['name'],
			];
	
			$this->saveProdutosAtributos($data);
		}
	}
	
	public function saveProdutosAtributos($data)
	{
		if($data) {
			$insert = $this->db->replace('produtos_atributos_marketplaces', $data);
			return ($insert == true) ? true : false;
		}
	}

	public function deleteAtributos($id_product)
	{
		// $sql = "DELETE FROM produtos_atributos_marketplaces WHERE id_product = ? AND id_atributo <> BRAND AND id_atribuo,'EAN','GTIN','SELLER_SKU'";
		$sql = "DELETE FROM produtos_atributos_marketplaces WHERE id_product = ? AND id_atributo <> 'GTIN' AND id_atributo <> 'EAN' AND id_atributo <> 'BRAND' AND id_atributo <> 'SELLER_SKU'";
		$query = $this->db->query($sql, array($id_product));
		return;
	}
	
	public function apagaAtributosAntigos($id_product)
	{
		$sql = "DELETE FROM produtos_atributos_marketplaces WHERE id_product = ?";
		$query = $this->db->query($sql,array($id_product));
		return ;
	}
	
	public function getAtributo($id)
	{

		$sql = "SELECT * FROM atributos_marketplaces WHERE id = ?";
		$query = $this->db->query($sql,array($id));
		return $query->row_array();
	}
	
	public function getAtributoCategoriaML($id_categoria, $id_atributo, $id_integration=11)
	{
		/*
		$sql = "SELECT am.*, acm.obrigatorio, acm.variacao FROM atributos_marketplaces am 
				LEFT JOIN atributos_categorias_marketplaces acm ON am.id = acm.id_atributo
				WHERE acm.id_integration = ? AND acm.id_categoria = ? AND acm.id_atributo = ?
		";
		 */
		$sql = "SELECT * FROM atributos_categorias_marketplaces 
				WHERE id_integration = ? AND id_categoria = ? AND nome = ?
		";
		
		$query = $this->db->query($sql, array($id_integration, (string)$id_categoria, (string)$id_atributo));
		return $query->row_array();
	}
	
	
	public function getAtributosCategoriaMKT($id_categoria, $int_to)
	{

		$sql = "SELECT * FROM atributos_categorias_marketplaces
				WHERE int_to = ? AND id_categoria = ?
				order by obrigatorio, nome 
		";
		
		$query = $this->db->query($sql, array($int_to, (string)$id_categoria));
		return $query->result_array();
	}
	
	public function getProductAttributeByIdIntto($id_product, $id_atributo, $int_to)
	{

		$sql = "SELECT *  FROM produtos_atributos_marketplaces WHERE id_product = ? AND id_atributo = ? AND int_to=?";
		$query = $this->db->query($sql,array($id_product, (string)$id_atributo, $int_to));
		return $query->row_array();
	}

    public function getAtributoCategoriaMKT($id_categoria, $id_atributo, $int_to, bool $isVariant = false)
    {
        /*
        $sql = "SELECT am.*, acm.obrigatorio, acm.variacao FROM atributos_marketplaces am
                LEFT JOIN atributos_categorias_marketplaces acm ON am.id = acm.id_atributo
                WHERE acm.id_integration = ? AND acm.id_categoria = ? AND acm.id_atributo = ?
        ";
         */
        $where = '';
        if ($isVariant) $where = "AND variacao = 1";

        $sql = "SELECT * FROM atributos_categorias_marketplaces 
				WHERE int_to = ? AND id_categoria = ? AND nome = ? {$where}";

        $query = $this->db->query($sql, array($int_to, (string)$id_categoria,(string)$id_atributo));
        return $query->row_array();
    }

	public function getAtributoCategoria_MKT($id_categoria, $id_atributo, $int_to)
    {
        /*
        $sql = "SELECT am.*, acm.obrigatorio, acm.variacao FROM atributos_marketplaces am
                LEFT JOIN atributos_categorias_marketplaces acm ON am.id = acm.id_atributo
                WHERE acm.id_integration = ? AND acm.id_categoria = ? AND acm.id_atributo = ?
        ";
         */
        $sql = "SELECT * FROM atributos_categorias_marketplaces 
				WHERE int_to = ? AND id_categoria = ? AND id_atributo = ?
		";

        $query = $this->db->query($sql, array($int_to, (string)$id_categoria, (string)$id_atributo));
        return $query->row_array();
    }

	public function getAtributoByAttrIdMkt($id_atributo, $int_to, $category_id = null)
    {
		if (is_null($category_id)) {
			$sql = "SELECT * FROM atributos_categorias_marketplaces WHERE id_atributo = ? AND int_to = ?";
        	$query = $this->db->query($sql, array((string)$id_atributo, $int_to));
		}
		else {
			$cat = $this->model_categorias_marketplaces->getCategoryMktplace($int_to, $category_id); 
			$sql = "SELECT * FROM atributos_categorias_marketplaces WHERE id_atributo = ? AND int_to = ? AND id_categoria = ?";
        	$query = $this->db->query($sql, array((string)$id_atributo, $int_to, $cat['category_marketplace_id']));
		}
        
        return $query->row_array();
    }
	
	public function getAtributosCategoriaMKTVariant($id_categoria, $int_to, $names)
	{
		
		if (is_array($names)) {
			$tmp = '';
			foreach ($names as $nm) {
				$tmp.= ' nome = "'.strtoupper ($nm).'" OR '; 
			}
			$name = ' AND ('.substr($tmp,0, -4).')';
		}
		else {
			$name = ' AND nome = "'.strtoupper ($names).'"';
		}
		$sql = "SELECT * FROM atributos_categorias_marketplaces WHERE int_to=? AND id_categoria=? and variacao=1 ".$name;
		
		$query = $this->db->query($sql, array($int_to, (string)$id_categoria));
		return $query->row_array();
	}
	
	public function removeByCategory($category_mkt_id) 
	{
		$this->db->where('id_categoria', (string)$category_mkt_id);
        $this->db->delete('atributos_categorias_marketplaces');
	}

	public function removeByIntToCategory($int_to, $category_mkt_id)
	{
		$this->db->where(array(
            'id_categoria'  => (string)$category_mkt_id,
            'int_to'        => $int_to
        ))->delete('atributos_categorias_marketplaces');
	}

	public function removeByCategoryAndIntTo($category_mkt_id, $int_to) 
	{
		$where = array('id_categoria ' => (string)$category_mkt_id , 'int_to ' => $int_to);
		$this->db->where($where);
        $this->db->delete('atributos_categorias_marketplaces');
	}

    /**
     * @param   int             $product        Código do produto (products.id)
     * @param   string|array    $attributeName  Nome(s) do(s) atributo(s)
     * @return  mixed
     */
    public function getAttributeMarketplaceByProductAndAttribute(int $product, $attributeName)
    {
        // se não tem atributos não precisa fazer a consulta
        if (
            (is_array($attributeName) && empty($attributeName)) ||
            $attributeName === ''
        ) {
            return array();
        }

        $query = $this->db
            ->select('
                atributos_categorias_marketplaces.id_categoria, 
                atributos_categorias_marketplaces.id_atributo, 
                atributos_categorias_marketplaces.variacao, 
                atributos_categorias_marketplaces.nome, 
                atributos_categorias_marketplaces.tipo, 
                atributos_categorias_marketplaces.int_to, 
                atributos_categorias_marketplaces.valor as valor_obrigatorio, 
                produtos_atributos_marketplaces.valor as valor_atributo
            ')
            ->from('products')
            ->join('categorias_marketplaces', 'left(substr(products.category_id,3),length(products.category_id)-4) = categorias_marketplaces.category_id')
            ->join('atributos_categorias_marketplaces', 'atributos_categorias_marketplaces.id_categoria = categorias_marketplaces.category_marketplace_id')
            ->join('produtos_atributos_marketplaces', 'produtos_atributos_marketplaces.id_product = products.id and produtos_atributos_marketplaces.id_atributo= atributos_categorias_marketplaces.id_atributo', 'left')
            ->where([
                'products.id' => $product
            ]);

        if (is_array($attributeName)) {
            $query = $query->where_in('atributos_categorias_marketplaces.nome', $attributeName);
        } else {
            $query = $query->where('atributos_categorias_marketplaces.nome', $attributeName);
        }

        return $query->get()->result_array();
    }

    /**
     * @param   array   $data   Dados do atributo para criação
     * @return  bool            Status da criação do registro
     */
    public function createProductAttributeMarketplace(array $data): bool
    {
        if($data) {
            $insert = $this->db->insert('produtos_atributos_marketplaces', $data);
            return $insert == true;
        }
        return false;
    }

    /**
     * @param   string  $value  Valor do atributo para atualização
     * @param   array   $data   Dados para cláusula where
     * @return  bool            Status da atualização do registro
     */
    public function updateProductAttributeMarketplace(string $value, array $data): bool
    {
        if($data && $value) {
            $this->db->where($data);
            return (bool)$this->db->update('produtos_atributos_marketplaces', array('valor' => $value));

        }
        return false;
    }
	
	public function getAttributesByAttrIdMkt($id_atributo, $int_to)
    {
        $sql = "SELECT * FROM atributos_categorias_marketplaces WHERE id_atributo = ? AND int_to = ?";
        $query = $this->db->query($sql, array((string)$id_atributo, $int_to));
        return $query->result_array();
    }

	public function getAtributosCategoriaVariant($id_categoria, $int_to)
	{
		
		$sql = "SELECT * FROM atributos_categorias_marketplaces WHERE int_to=? AND id_categoria=? and variacao=1 and prd_sku=1";
		
		$query = $this->db->query($sql, array($int_to, (string)$id_categoria));
		return $query->result_array();
	}

    public function getAttributesCategoryMarketplace(int $category_id, string $int_to): array
    {
        return $this->db->select('acm.nome, acm.id_categoria, acm.id_atributo, acm.obrigatorio, acm.tipo, acm.int_to')
            ->join('categorias_marketplaces cm', 'cm.category_marketplace_id = acm.id_categoria')
            ->where(array(
                'acm.int_to' => $int_to,
                'cm.category_id' => $category_id,
                'acm.variacao' => false
            ))
            ->get('atributos_categorias_marketplaces acm')
            ->result_array();
    }

    public function getAttributesCategoryMarketplaceAttribute(int $category_id, string $int_to, string $attribute): ?array
    {
        return $this->db->select('acm.nome, acm.id_categoria, acm.id_atributo, acm.obrigatorio, acm.tipo, acm.int_to, acm.tipo, acm.multi_valor, acm.valor')
            ->join('categorias_marketplaces cm', 'cm.category_marketplace_id = acm.id_categoria')
            ->where(array(
                'acm.int_to'        => $int_to,
                'cm.category_id'    => $category_id,
                'acm.variacao'      => false,
                'acm.id_atributo'   => $attribute
            ))
            ->get('atributos_categorias_marketplaces acm')
            ->row_array();
    }

    public function getAllAttributesInUse(int $product_id)
    {
        return $this->db->query("
            SELECT DISTINCT acm.nome as name ,pam.valor as value 
            FROM produtos_atributos_marketplaces pam
            JOIN atributos_categorias_marketplaces acm ON acm.id_atributo = pam.id_atributo and pam.int_to = acm.int_to
            WHERE pam.id_product = ?
        ", array($product_id))->result_array();
    }

    /**
     * Consulta os IDs de atributos através do nome do atributos de um marketplace.
     *
     * @param   string  $attribute
     * @param   string  $int_to
     * @return  array|null
     */
    public function getAttributeIdByNameAndIntTo(string $attribute, string $int_to): ?array
    {
        return $this->db
            ->distinct('id_atributo, valor')
            ->select('id_atributo, valor, tipo')
            ->where(array(
                'int_to' => $int_to,
                'nome' => $attribute
            ))
            ->get('atributos_categorias_marketplaces')
            ->row_array();
    }

    /**
     * Consulta os produtos que contém um valor específico em algum atributo de algum marketplace.
     * Limitando a consulta uma quantidade informada
     * Sempre consultando a partir do último produto lido.
     *
     * @param   int         $attribute_id
     * @param   string      $int_to
     * @param   string      $value_id
     * @param   int         $limit
     * @param   int|null    $last_product
     * @return  array|null
     */
    public function getProductsByAttributeAndIntToAndValue(int $attribute_id, string $int_to, string $value_id, int $limit = 200, int $last_product = null): ?array
    {
        $this->db
            ->where(array(
                'id_atributo'   => $attribute_id,
                'valor'         => $value_id,
                'int_to'        => $int_to
            ));

        if (!is_null($last_product)) {
            $this->db->where('id_product >', $last_product);
        }

        return $this->db->order_by('id_product', 'ASC')
            ->limit($limit)
            ->get('produtos_atributos_marketplaces')
            ->result_array();
    }

    public function removeAttributeValueByAttributeAndIntToAndValueAndProduct(int $attribute_id, string $int_to, string $value_id, int $product_id): bool
    {
        return (bool)$this->db->where(array(
            'id_product'    => $product_id,
            'id_atributo'   => $attribute_id,
            'valor'         => $value_id,
            'int_to'        => $int_to,
        ))->delete('produtos_atributos_marketplaces');
    }

    public function getAttributesByIntToAndName($int_to, $identifyingTechnicalSpecification)
    {
        if(empty($int_to) || empty($identifyingTechnicalSpecification)){
            return false;
        }

        return $this->db->where('int_to', $int_to)
            ->where('nome', $identifyingTechnicalSpecification)
            ->get(self::TABLE)
            ->result();
    }

}