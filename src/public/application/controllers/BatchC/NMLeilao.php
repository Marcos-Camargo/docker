<?php
/*
 
Realiza o Leilão de Produtos e atualiza Novo Mundo SC

*/   

require 'NovoMundo/NMIntegration.php';


 class NMLeilao extends BatchBackground_Controller
 {
	
    private $integration = null;
    private $integration_data = null;
    protected $int_to = 'NM';
    protected $api_keys = '';
    protected $api_url = 'https://novomundoteste.conectala.com.br/app/Api/V1/';
		
	public function __construct()
	{
		parent::__construct();
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_products');
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
		$this->load->model('model_category');
		$this->load->model('model_integrations');
		$this->load->model('model_stores');
		$this->load->model('model_orders');
		$this->load->model('model_integration_last_post');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_products_catalog');

        $this->integration = new NMIntegration();
    }

	
	function run($id = null, $params = null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__))
        {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}

		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		$this->getkeys(1, 0);
		$retorno = $this->toNMFase1();
		$retorno = $this->syncProducts();
	    $retorno = $this->inactiveProducts();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}


	function getkeys($company_id, $store_id) 
    {
		$this->integration_data = $this->model_integrations->getIntegrationsbyCompIntType($company_id, $this->int_to, "CONECTALA", "DIRECT", $store_id);
		$this->api_keys = json_decode($this->integration_data['auth_data'], true);
	}

		
	function promotions() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$this->log_data('batch',$log_name,"start","I");	
		$this->model_promotions->activateAndDeactivate();
	}

	
	function campaigns() {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$this->log_data('batch',$log_name,"start","I");	
		$this->model_campaigns->activateAndDeactivate();
	}


    function validateEan($ean = false, $log_name = null, $product = null)
    {
        if (!$ean)
            return false;

        if (!is_numeric($ean))
        {
            echo $msg = "*** PRODUTO SEM EAN: Prod. ".$product['id']." com os dados: ".print_r($product)."\n";
            $this->log_data('batch', $log_name, $msg, "E");
            return false;
        }
        
        $ean = strval(str_pad($ean, 13, '0', STR_PAD_LEFT));
        return $ean;
    }

	
 	function toNMFase1()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch', $log_name, "start", "I");	
		
		$sql = "UPDATE prd_to_integration SET status_int = 99 WHERE int_type = 13 AND int_to='".$this->int_to."'";
		$query = $this->db->query($sql);

		//Limpa tabela temporaria
		$sql = 'TRUNCATE int_processing_novomundo';
		$query = $this->db->query($sql);

		$parms = [];
		 
		//busco o percentual de estoque de cada marketplace 
		$sql = "select id, value ,concat(lower(value),'_perc_estoque') as name from attribute_value av where attribute_parent_id = 5";
		$query = $this->db->query($sql);
		$mkts = $query->result_array();

		foreach ($mkts as $ind => $val)
        {
			$sql                = "select value from settings where name = '".$val['name']."'";
			$query              = $this->db->query($sql);
			$parm               = $query->row_array();
			$key_param          = $val['value'].'PERC'; 
			$parms[$key_param]  = $parm['value'];
		}	
		
        if(empty($parms[$this->int_to.'PERC']))
            $parms[$this->int_to.'PERC'] = 100;
        
		// Calculo menos 30 dias de hoje 
		$date30 = new DateTime();
		$date30->sub(new DateInterval('P30D'));
		
		$sells      = [];
		$ufs        = [];
		$lojas      = $this->model_stores->getAllActiveStore();

		foreach ($lojas as $loja)
        {
			$sells[$loja['id']]     = $this->model_orders->getSellsOrdersCount($loja['id'], $date30->format('Y-m-d'));
			$ufs[$loja['id']]       = $loja['addr_uf'];
		}

        $sql = "SELECT p.id, p.int_id, p.prd_id, prod.has_variants, i.int_to FROM prd_to_integration p LEFT JOIN integrations i ON p.int_id=i.id 
                left join products prod on p.prd_id = prod.id  WHERE p.int_type = 13 AND p.status = 1 AND i.int_to='".$this->int_to."' ORDER BY p.prd_id";

		$query = $this->db->query($sql);
		$mktlkd = $query->result_array();

		foreach ($mktlkd as $ind => $val)
        {
            $sql = "SELECT * FROM products WHERE id = ".$val['prd_id'];
            $query = $this->db->query($sql);
            $prd = $query->row_array();
            $prd['qty'] = intval($prd['qty']);
            $ean = $prd['EAN'];
            
            $key_param = $val['int_to'].'PERC';         
			$qty_atual = ceil($prd['qty'] * intval($parms[$key_param]) / 100); 

            $ean = $this->validateEan($ean, $log_name, $prd);

            if ($prd['status'] > 1 || $prd['situacao'] == 1)
            {
                // está inativo ou incompleto 
                $sql = "UPDATE prd_to_integration SET status = 0 WHERE id = ".$val['id'];
                $query = $this->db->query($sql);
                continue;
            }

			if (!array_key_exists($prd['store_id'], $ufs))
             {
				$sql = "UPDATE prd_to_integration SET status = 0 WHERE id = ".$val['id'];
				$query = $this->db->query($sql);
				continue;
			}
			
			if ($qty_atual == 0)
            { 
				$st_int = 10;   // SEM ESTOQUE MIN
			}
            elseif($val['has_variants'] !== $prd['has_variants'])
            {
                $st_int = 15;
            } 
            else
            {                             
				$st_int = 0;
				$uf = 0;               

                if ($ufs[$prd['store_id']] == 'SP')
					$uf=1;	
				
				// pego o preços do marketplace ou a promação do produto
				$price = $this->model_products_marketplace->getPriceProduct($prd['id'], $prd['price'], $this->int_to, $prd['has_variants']);
				$price = $this->model_promotions->getPriceProduct($val['prd_id'], $price, $this->int_to);
				// $preco = $this->model_promotions->getPriceProduct($val['prd_id'],$preco);
				$price = round($price, 2);
				// inserir na tabela temporária
	        	$data = array(
	        		'int_to' => $val['int_to'],
	        		'prd_id' => $val['prd_id'],
	        		'EAN' => $ean,
	        		'price' => $price,
	        		'qty' => $prd['qty'],
	        		'qty_atual' => $qty_atual,
	        		'sku' => $prd['sku'],
	        		'reputacao' => '100',  //rick - pegar a reputação da empresa
	        		'NVL' => '0',
	        		'to_int_id' => $val['id'],
	        		'company_id' => $prd['company_id'],
	        		'store_id' => $prd['store_id'],
	        		'uf' => $uf,
	        		'sells' => $sells[$prd['store_id']],
	        		);
				
                if(!$insert = $this->db->insert('int_processing_novomundo', $data))
                {
                    echo $msg = '*** NÃO INSERIDO NA int_processing_novomundo . '.$prd['id'].' com os dados: '.serialize($prd). " 
                                e com os dados de inserção: ".serialize($data);
                    $this->log_data('batch', $log_name, $msg, "E");
                    continue;
                }
			}

			$sql = "UPDATE prd_to_integration SET status_int = ".$st_int. ", int_to ='".$val['int_to']."' WHERE id = ".$val['id'];
			$query = $this->db->query($sql);
		}

// Fase 2
		/* Regra nova 
		 * Menor Preço ganha.
		 * Empate:
		 *  Loja de SP Ganha
		 *  Empate 
		 *   Quem tem mais venda
		 *   Empate 
		 * 		Loja mais antiga
		 */
		// Seleciona melhores produtos

		$sql = "SELECT * FROM int_processing_novomundo WHERE int_to='".$this->int_to."' AND trim(coalesce(EAN, '')) <> '' 
                ORDER BY EAN ASC, CAST(price AS DECIMAL(12,2)) ASC, uf DESC, sells DESC, store_id ASC";
		$query = $this->db->query($sql);
		$mkt = $query->result_array();

		$int_ant = "";
		$ean_ant = "";
		$price = 0;

		foreach ($mkt as $val)
        {
            $ean = $this->validateEan($val['EAN'], $log_name, $val);

			if (($int_ant != $val['int_to']) || ($ean_ant != $ean))
            {
				echo "SELECIONADO...".$val['prd_id']."\n";
				$status_int = 1;
				$int_ant = $val['int_to'];
				$ean_ant = $ean;
				$price = $val['price'];
				$winner = $val['prd_id'];
			} 
            else
            {
				echo "PERDEU...".$val['prd_id']."\n";

				if ($ean_ant == $ean) 
                {
					if ($val['price'] > $price) 
						$status_int = 11;  // PREÇO ALTO
                    else
						$status_int = 14; // critério de desempate						
				} 
			}

			// Verifica se precisa sair
			$sql = "SELECT * FROM integration_last_post WHERE int_to = '".$val['int_to']."' AND EAN = '".$ean."'";			
			$cmd = $this->db->query($sql);

			if($cmd->num_rows() > 0) 
            {    
                // Existe um antigo
				$old = $cmd->row_array();

				// Se mesma empresa e mesmo valor , não precisa reenviar 
				if (($old['company_id'] == $val['company_id']) && ($old['price'] == $val['price']) && ($old['qty'] == $val['qty']))
                {
					$status_int = 1; // era 2 mas quero re-enviar todos novamente. 
				}

				if ($old['prd_id'] != $winner)
                {
					// precisa cair do bling	
					echo "TEM QUE CAIR...".$old['prd_id']." ".$old['skulocal']." ".$this->int_to."\n";

					if (!$this->zeraEstoque($old['skulocal'], $old['prd_id']))
                    {
                        echo $msg = "*** NÃO zerou estoque OLD_skulocal = ".$old['skulocal']." | OLD_PRD_ID: ".$old['prd_id']." \n";
                        $this->log_data('batch', $log_name, $msg, "E");
                        continue;
					}
					
					$sql = "UPDATE integration_last_post SET qty = 0, qty_atual = 0 WHERE int_to = '".$val['int_to']."' AND EAN = '".$ean."'";
					$cmd = $this->db->query($sql);
				}

				$skulocal = $old['skulocal'];
			} 
            else
            {
                $skulocal = $val['prd_id'].'_'.$this->int_to;

				$sql = "SELECT * FROM prd_to_integration WHERE int_to = '".$val['int_to']."' AND prd_id = '".$val['prd_id']."'";
				$cmd = $this->db->query($sql);
				$prd_to = $cmd->row_array();

				if ($prd_to)
                {
					if (!empty($prd_to['skubling']))
						$skulocal = $prd_to['skubling'];
				}
			}
			
			// Atualiza status_int
			$sql = "UPDATE prd_to_integration SET status_int = ".$status_int. ", skubling = '".$skulocal."' , skumkt = '".$skulocal."' WHERE id = ".$val['to_int_id'];
			$query = $this->db->query($sql);
		}
				
		// vejo os produtos que mudaram de EAN e derrubo eles do marketplace
		$sql = "SELECT * FROM integration_last_post b WHERE qty > 0 AND int_to = '".$this->int_to."'";
		$query = $this->db->query($sql);
		$prods_derr = $query->result_array();

		foreach ($prods_derr as $prd_derr)
        {
			$sql = "SELECT * FROM products WHERE id = ".$prd_derr['prd_id'];
			$query = $this->db->query($sql);
			$prd = $query->row_array();

			if ($prd['EAN'] != $prd_derr['EAN'])
            {
				if ($prd['EAN'] != '') 
                {
					echo "prd_id = ".$prd_derr['prd_id']." mudou de EAN e será derrubado\n";

					if (!$this->zeraEstoque($prd_derr['skulocal'], $prd_derr['prd_id']))
                    {
                        echo $msg = "*** NÃO zerou estoque - atributo1 = ".$prd_derr['skulocal']." | atributo2 = ".$prd_derr['prd_id']." \n";
                        $this->log_data('batch', $log_name, $msg, "E");
                        continue;
					}
					else 
                    {
						$sql = "UPDATE integration_last_post SET qty = 0, qty_atual = 0 WHERE id = ".$prd_derr['id'];
						$cmd = $this->db->query($sql);
					}
				}
			}
		}
		
		// marco todos para enviar novamente
		$sql = "select * from prd_to_integration WHERE status_int=2 AND int_to = '".$this->int_to."'";
		$query = $this->db->query($sql);
		$prds_to_int = $query->result_array();

		foreach ($prds_to_int as $prd_to_int)
        {
			$sql = "select * from products WHERE id =".$prd_to_int['prd_id'];
			$query = $this->db->query($sql);
			$prd = $query->row_array();
			echo "produto ".$prd['id'].' data do produto = '.$prd['date_update'].' Ultima integração em '.$prd_to_int['date_last_int']."\n";

			if ($prd_to_int['date_last_int'] < $prd['date_update'])
            {
				$sql = "UPDATE prd_to_integration SET status_int = 1, status = 1 WHERE id = ".$prd_to_int['id'];
				$query = $this->db->query($sql);
			}
		}

		return; 
	}	


	function zeraEstoque($sku, $prd_id)	
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
	
        $data = array('qty' => 0);

        $response = $this->integration->updateStock($this->api_keys, $sku, $data);

		if ($response['httpcode'] != "200")  
        {
			echo $msg = "Erro na respota do ".$this->int_to.". httpcode=".$response['httpcode']." RESPOSTA ".$this->int_to.": ".print_r($response['content'],true)."
                         - DADOS ENVIADOS:".print_r($data, true)." \n"; 
			$this->log_data('batch', $log_name, $msg, "E");
			return false;
		}
		
		$sql = "SELECT * FROM products WHERE id = ?";
		$query = $this->db->query($sql,array($prd_id));
		$prd = $query->row_array();

		 // zera os filhos
		if ($prd['has_variants'] != "") 
        {
			$variations = array();
	        $prd_vars = $this->model_products->getProductVariants($prd['id'], $prd['has_variants']);
	        $tipos = explode(";", $prd['has_variants']);
	        $variation_attributes = array();

			foreach($prd_vars as $value) 
            {
				if (isset($value['sku']))
                {
					$skumkt = $sku.'-'.$value['variant'];
					echo "Variação: Estoque id:".$prd['id']." ".$skumkt." estoque:".$prd['qty']." enviado:".$qty."\n";
					
                    $data = array('qty' => 0);
                    $response = $this->integration->updateStock($this->api_keys, $sku, $data);

					if ($response['httpcode'] != "200")
                    { 
						echo $msg = "Erro na respota do ".$this->int_to.". httpcode=".$response['httpcode']." RESPOSTA ".$this->int_to.": ".print_r($response['content'],true)." \n"; 
						$this->log_data('batch',$log_name, $msg, "E");
					}
				}
			}	
		}

		return true;
	}
	

    function syncProducts()
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
    	$this->log_data('batch', $log_name, "start", "I");	

		$sql            = "select id, value ,concat(lower(value),'_perc_estoque') as name from attribute_value av where attribute_parent_id = 5";
		$query          = $this->db->query($sql);
		$mkts           = $query->result_array();
		$estoqueIntTo   = array();

		foreach ($mkts as $ind => $val)
        {
			$sql                        = "select value from settings where name = '".$val['name']."'";
			$query                      = $this->db->query($sql);
			$parm                       = $query->row_array();
			$key_param                  = $val['value']; 
			$estoqueIntTo[$key_param]   = $parm['value'];
		}	
		
		$sql    = "SELECT * FROM prd_to_integration WHERE status_int = 1 AND status = 1 AND int_type = 13 AND int_to='".$this->int_to."'";	        
		$query  = $this->db->query($sql);
		$data   = $query->result_array();

        if(empty($estoqueIntTo[$this->int_to]))
            $estoqueIntTo[$this->int_to] = 100;

		foreach ($data as $key => $row) 
	    {
			$sql = "SELECT * FROM products WHERE id = ".$row['prd_id'];
			$cmd = $this->db->query($sql);
			$prd = $cmd->row_array();
			
			// pego os dados do catálogo do produto se houver 
			if (!is_null($prd['product_catalog_id']))
            {
				$prd_catalog            = $this->model_products_catalog->getProductProductData($prd['product_catalog_id']); 
				$prd['name']            = $prd_catalog['name'];
				$prd['description']     = $prd_catalog['description'];
				$prd['EAN']             = $prd_catalog['EAN'];
				$prd['largura']         = $prd_catalog['width'];
				$prd['altura']          = $prd_catalog['height'];
				$prd['profundidade']    = $prd_catalog['length'];
				$prd['peso_bruto']      = $prd_catalog['gross_weight'];
				$prd['ref_id']          = $prd_catalog['ref_id']; 
				$prd['brand_code']      = $prd_catalog['brand_code'];
				$prd['brand_id']        = '["'.$prd_catalog['brand_id'].'"]'; 
				$prd['category_id']     = '["'.$prd_catalog['category_id'].'"]';
				$prd['image']           = $prd_catalog['image'];
			}
		
			// pego o preço por Marketplace 
			$old_price      = $prd['price'];
			$prd['price']   = $this->model_products_marketplace->getPriceProduct($prd['id'], $prd['price'], $this->int_to, $prd['has_variants']);

			if ($old_price !== $prd['price'])
				echo " Produto ".$prd['id']." tem preço ".$prd['price']." para ".$this->int_to." e preço base ".$old_price."\n";

			// acerto o preço do produto com o preço da promoção se tiver 
			$prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'], $prd['price'], $this->int_to);
			// $prd['promotional_price'] = $this->model_promotions->getPriceProduct($prd['id'],$prd['price']);
			// e ai vejo se tem campanha 
		    // $prd['promotional_price'] = $this->model_campaigns->getPriceProduct($prd['id'],$prd['promotional_price'],$this->getInt_to());

			if ($prd['promotional_price'] > $prd['price'])
				$prd['price'] = $prd['promotional_price']; 
			
			$prd['price']               = round($prd['price'], 2);
			$prd['promotional_price']   = round($prd['promotional_price'], 2);
			
			if ($prd['is_kit'])
            {
				$prd['promotional_price']   = $prd['price']; 
				$productsKit                = $this->model_products->getProductsKit($prd['id']);
				$original_price             = 0; 

				foreach($productsKit as $productkit)
                {
					$original_price += $productkit['qty'] * $productkit['original_price'];
				}

				$prd['price']               = $original_price;

				echo " KIT ".$prd['id'].' preço de '.$prd['price'].' por '.$prd['promotional_price']."\n";  
			}
			
    		$sku = $row['skubling'];
			
			if ($prd['category_id'] == '[""]')
            {	
				$msg= 'Categoria não vinculada';
				$this->model_products->update(array('situacao'=>1), $prd['id']);
				$this->errorTransformation($prd['id'], $row['skubling'], $msg, $row['id']);
				continue;
			}

			// leio o int_processing 
			$sql            = "SELECT * FROM int_processing_novomundo WHERE to_int_id = ".$row['id'];
			$cmd            = $this->db->query($sql);
			$row_int_pro    = $cmd->row_array();
			
			if (!$row_int_pro)
            {
				echo $msg = "*** Não foi possivel encontrar o registro do produto no int_processing_novomundo para o produto ".$prd['id']." e to_int_id = ".$row['id']." \n"; 
				$this->log_data('batch', $log_name, $msg, "E");
				continue;
			}
			
			// troco a quantidade deste produto pela quantidade ajustada pelo percentual por cada produto
			$prd['qty'] = $row_int_pro['qty_atual'];
			
			$retorno    = $this->inserePrd($prd, $sku, $estoqueIntTo[$this->int_to]);    
			
			if (!$retorno)
            {
				continue; 
			}
            else
            { 		
				// TROUXE PRA DENTRO DO SUCESSO
				$int_date_time  = date('Y-m-d H:i:s');
				$sql            = "UPDATE prd_to_integration SET status_int = 2 , date_last_int = ? WHERE id = ".$row['id'];
				$cmd            = $this->db->query($sql, array($int_date_time));
				
				// Consultar o Tipo_volume do produto aqui para fazer update do mesmo no Bling_ult_envio
				$sql                    = "SELECT category_id FROM products WHERE id = ".$row['prd_id'];
				$cmd                    = $this->db->query($sql);
				$category_id_array      = $cmd->row_array();  //Category_id esta como caracter no products
				$cat_id                 = json_decode($category_id_array['category_id']);
				$sql                    = "SELECT codigo FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories 
						                    WHERE id =".intval($cat_id[0]).")";
				$cmd                    = $this->db->query($sql);
				$lido                   = $cmd->row_array();
				$tipo_volume_codigo     = ($lido['codigo']) ? $lido['codigo'] : '';
				
				$crossdocking           = (is_null($prd['prazo_operacional_extra'])) ? 0 : $prd['prazo_operacional_extra'];
				
				$sql                    = "SELECT * FROM integration_last_post WHERE int_to='".$this->int_to."' AND prd_id = ".$row['prd_id']." AND EAN = '".$row_int_pro['EAN']."'";
				$cmd                    = $this->db->query($sql);
				$integration_last_post           = $cmd->row_array();
				$marca_int_nm           = null;
				$categoria_nm           = null;
				$mkt_store_id           = ''; 

				if ($integration_last_post)
                {
					$marca_int_nm = $integration_last_post['marca_int_nm'];
					$categoria_nm = $integration_last_post['categoria_nm'];
					$mkt_store_id = $integration_last_post['mkt_store_id'];
				}

				$loja  = $this->model_stores->getStoresData($prd['store_id']);
				
	        	$data = array(
	        		'int_to' => $row_int_pro['int_to'],
	        		'company_id' => $row_int_pro['company_id'],
	        		'EAN' => $row_int_pro['EAN'],
	        		'prd_id' => $row_int_pro['prd_id'],
	        		'price' => $prd['promotional_price'],
	        		'qty' => $row_int_pro['qty'],
	        		'sku' => $row_int_pro['sku'],
	        		'reputacao' => $row_int_pro['reputacao'],
	        		'NVL' => $row_int_pro['NVL'],
	        		'mkt_store_id' => $mkt_store_id,         
	        		'data_ult_envio' => $int_date_time,
	        		'skulocal' => $sku,
	        		'skumkt' => $sku,
	        		'tipo_volume_codigo' => $tipo_volume_codigo, 
	        		'qty_atual' => $row_int_pro['qty_atual'],
	        		'largura' => $prd['largura'],
	        		'altura' => $prd['altura'],
	        		'profundidade' => $prd['profundidade'],
	        		'peso_bruto' => $prd['peso_bruto'],
	        		'store_id' => $prd['store_id'], 
	        		'marca_int_nm' => $marca_int_nm, 
					'categoria_nm'=> $categoria_nm,
	        		'crossdocking' => $crossdocking, 
	        		'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
	        		'zipcode' => preg_replace('/\D/', '', $loja['zipcode']), 
	        		'freight_seller' =>  $loja['freight_seller'],
					'freight_seller_end_point' => $loja['freight_seller_end_point'],
					'freight_seller_type' => $loja['freight_seller_type']
	        	);

				if ($integration_last_post)
					$insert = $this->model_integration_last_post->update($data, $integration_last_post['id']);
                else
					$insert = $this->db->replace('integration_last_post', $data);
			}			
	    }

        echo " ------- Processo de envio de produtos terminou\n";
        return "PRODUCTS Synced with ".$this->int_to;
    } 


	function inserePrd($prd, $skumkt, $estoqueIntTo) 
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		//pega os dados da integração. Por enquanto só a conectala faz a integração direta 
		
		$catP = json_decode($prd['category_id']);
		// pego a categoria 
		$categoria = $this->model_category->getCategoryData($catP);
		
		// Verifico se é catálogo para pegar a imagem do lugar certo
		if (!is_null($prd['product_catalog_id'])) 
			$pathImage = 'catalog_product_image';
		else 
			$pathImage = 'product_image';
		
		$brand_id = json_decode($prd['brand_id']);
		$sql = "SELECT * FROM brands WHERE id = ?";
		$query = $this->db->query($sql, $brand_id);
		$brand = $query->row_array();
		
		$description = substr(htmlspecialchars(strip_tags(str_replace("<br>"," \n",$prd['description'])), ENT_QUOTES, "utf-8"),0,3800);
		$description = str_replace("&amp;amp;"," ",$description);
		$description = str_replace("&amp;"," ",$description);
		$description = str_replace("&nbsp;"," ",$description);

		if (($description == '') || (trim(strip_tags($prd['description'])," \t\n\r\0\x0B\xC2\xA0")) == '')
			$description= substr(htmlspecialchars($prd['name'], ENT_QUOTES, "utf-8"),0,98);

        $prd['qty'] = intval($prd['qty']);

        $prd['NCM'] = str_pad($prd['NCM'], 8, '0', STR_PAD_LEFT);

        if(empty($estoqueIntTo))
            $estoqueIntTo = 100;

        if ($prd['qty'] >= 5) 
            $prd['qty'] = $prd['qty'] * intVal($estoqueIntTo) / 100; 
    
        $product_name = substr(strip_tags(htmlspecialchars($prd['name'], ENT_QUOTES, "utf-8"), " \t\n\r\0\x0B\xC2\xA0"), 0, 98);
        $product_name = $this->splitWords($product_name, '58', ''); //braun -> numero magico 58 corresponde ao limite de caracteres no nome por causa do ML
        $description  = $this->splitWords($description, '2998', '');

		$produto = array(
			"name"			        => $product_name,
            "sku" 			        => $skumkt,
			"description" 	        => $description,
			"active" 		        => "enabled",
			"price" 		        => (float)$prd['price'], 
            "qty"			        => $prd['qty'],
            "ean"			        => $prd['EAN'],
            "sku_manufacturer"      => $prd['sku'],
            "net_weight"            => (float)$prd['peso_liquido'],
            "gross_weight"          => (float)$prd['peso_bruto'],
            "width"			        => (float)($prd['largura'] < 11) ? 11 : $prd['largura'],
            "height"		        => (float)($prd['altura'] < 2) ? 2 : $prd['altura'],
			"depth"		            => (float)($prd['profundidade'] < 16) ? 16 : $prd['profundidade'],
            "guarantee"             => $prd['garantia'],
            "origin"                => $prd['origin'],
            "unity"                 => 'UN',
            "ncm"                   => $prd['NCM'],
            "manufacturer"          => $brand['name'],
            "extra_operating_time"  => intval($prd['prazo_operacional_extra']),
            "category"              => $categoria['name']
		);
		
		$imagens = array();

		if ($prd['image'] != "") 
        {
			$numft = 0;

			if (strpos("..".$prd['image'], "http") > 0 || strpos("..".$prd['image'], "https") > 0) 
            {
				$fotos = explode(",", $prd['image']);	

				foreach($fotos as $foto)
                {
					$imagens[$numft++] = $foto;
				}
			} 
            else
            {
				$fotos = scandir(FCPATH . 'assets/images/'.$pathImage.'/' . $prd['image']);	

				foreach($fotos as $foto) 
                {
					if (($foto != ".") && ($foto != "..")) 
                    {
						if(!is_dir(FCPATH . 'assets/images/'.$pathImage.'/' . $prd['image'].'/'.$foto)) 
                        {
							$imagens[$numft++] = str_replace('\\','/', base_url('assets/images/'.$pathImage.'/' . $prd['image'].'/'. $foto));
						}
					} 
				}
			}	
		}

		$produto['images'] = $imagens;

		// TRATAR VARIANTS	
        
        //braun -> isso precisa ser repensado:  o sistema utiliza nomes em portugues
        //enquanto a api espera nomes em ingles
        //msg de erro da API: Type of variation not found, valid variation: color, size, voltage
		if ($prd['has_variants'] != "")
        {
			$types_variations = $types_variations_translated = array();

            $prd_vars = $this->model_products->getProductVariants($prd['id'], $prd['has_variants']);
            $types_variations = @explode(";",$prd['has_variants']);

            if(is_array($types_variations) && !empty($types_variations))
            {
                foreach($types_variations as $k => $v)
                {   
                    $types_variations_translated[$k] = $this->translateTypeVariation($v);
                }
            }

			foreach($prd_vars as $k => $value)
            {
                if($k === 'numvars')
                    continue;

                $variation_images = [];

                $image_dir = @scandir(FCPATH . 'assets/images/'.$pathImage.'/'.$prd['image'].'/'. $value['image']);	

				foreach($image_dir as $image) 
                {
					if (($image != ".") && ($image != "..")) 
                    {
						if(!is_dir(FCPATH . 'assets/images/'.$pathImage.'/' . $prd['image'].'/'.$value['image'].'/'.$image)) 
							$variation_images[] = str_replace('\\','/', base_url('assets/images/'.$pathImage.'/' . $prd['image'].'/'. $image));
					} 
				}

                $variation[$k]['sku']       = $value['sku'];
                $variation[$k]['qty']       = $value['qty'];
                $variation[$k]['EAN']       = $value['EAN'];
                $variation[$k]['images']    = $variation_images;

                if(is_array($types_variations) && !empty($types_variations))
                {
                    foreach($types_variations as $type)
                    {                        
                        $variation[$k][$this->translateTypeVariation($type)] = $value[$type];
                    }
                }
			}

			$produto['types_variations'] = $types_variations_translated;
			$produto['product_variations'] = $variation;
		}

		$prod_data = array("product" => $produto);
		$json_data = json_encode($prod_data, JSON_UNESCAPED_UNICODE); 
			
		echo "Incluindo o produto ".$prd['id']." ".$prd['name']."\n";

		if ($json_data === false)
        {
			// a descrição está com algum problema . tento reduzir... 
			$produto['name'] = substr(strip_tags(htmlspecialchars($prd['name'], ENT_QUOTES, "utf-8"), " \t\n\r\0\x0B\xC2\xA0"), 0, 96);
			$produto['description'] = substr($description, 0, 3000);
			$prod_data = array("product" => $produto);
			$json_data = json_encode($prod_data);			

			if ($json_data === false) 
            {
				echo $msg = "Erro ao fazer o json do produto ".$prd['id']." ".print_r($produto,true)." \n";
				$this->log_data('batch', $log_name, $msg, "E");
				return false;
			}
		}

        $url = $this->api_url.'Products';
		$response = $this->postNovoMundo($url, $json_data, $this->api_keys);

		if ($response['httpcode'] != 200)
        {
			$msg         = "Erro URL: ". $this->api_url. " httpcode=".$response['httpcode']."\n"; 
			$msg        .= " RESPOSTA ".$this->int_to.": ".print_r($response['content'], true)." \n"; 
			echo $msg   .= " Dados enviados: ".print_r($prod_data, true)." \n"; 

			$this->log_data('batch', $log_name, $msg, "E");
			return false;
		}

		return true;	
	} 


    public function splitWords($string, $nb_caracs, $separator)
    {
        $string = strip_tags(html_entity_decode($string));

        if( strlen($string) <= $nb_caracs )
        {
            $final_string = $string;
        } 
        else
        {
            $final_string = "";
            $words = explode(" ", $string);

            foreach ($words as $value)
            {
                if (strlen($final_string." ".$value) < $nb_caracs)
                {
                    if (!empty($final_string)) $final_string .= " ";
                    $final_string .= $value;
                }
                else 
                {
                    break;
                }
            }

            $final_string .= $separator;
        }

        return $final_string;
    }



    private function translateTypeVariation($type = false)
    {
        if(!$type)
            return false;

        $type_english = strtolower($type);

        switch($type_english)
        {
            case 'tamanho':     $type_english = 'size';     break;
            case 'cor':         $type_english = 'color';    break;
            default:            $type_english = 'voltage';
        }

        return $type_english;
    }


    	function inactiveProducts()
	{
		// verifico os produtos que ficaram 99, provavelmente pois não tem mais transportadoras mas tem tabém os inatviso
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$int_date_time  = date('Y-m-d H:i:s');
		$sql            = "SELECT * FROM prd_to_integration WHERE status = 0 AND status_int = 99 AND int_to=?";
		$query          = $this->db->query($sql, array($this->int_to));
		$prds_int       = $query->result_array();

		foreach($prds_int as $prd_int) 
        {
			echo "Processando produto ".$prd_int['prd_id']."\n";

			$sql        = "SELECT * FROM products WHERE id = ?";
			$query      = $this->db->query($sql,array($prd_int['prd_id']));
			$prd        = $query->row_array();
			
			if (!is_null($prd_int['skubling'])) 
            {
				$sql        = "SELECT * FROM integration_last_post WHERE skulocal = ? AND int_to = ?";
				$query      = $this->db->query($sql, array($prd_int['skubling'], $this->int_to));
				$last_post  = $query->row_array();
			}
			
			if (is_null($prd_int['skubling']) || (!$last_post))
            {  
                // nunca integrou. 
				$status_int = false;

				if ($prd['status'] != 1)    // produto está inativo. 
                {
					$status_int = 90; 
                }
                elseif ($prd['qty'] <= 0)  // produto está sem estoque 
                {
					$status_int = 10;
				}
                elseif ($prd['situacao'] != 2)  // produto está incompleto
                {
					$status_int = 90;
                }
                elseif (!$this->hasShipCompany($prd))  // não tem transportadora.
                {
					$status_int = 91 ;
				}
                else
                {
					// não sei o motivo melhor apagar o registro. 
					echo " Razao para nao ter sido integrado é desconhecida - parte 1 - ".$prd_int['prd_id']."\n";
					// $sql = "DELETE FROM prd_to_integration WHERE id = ?";
					// $query = $this->db->query($sql, array($prd_int['id']));
				}

				if ($status_int) 
                {
					$sql    = "UPDATE prd_to_integration SET status_int = ?  WHERE id = ?";
					$query  = $this->db->query($sql, array($status_int, $prd_int['id']));
				}
			} 
			else 
            {
				
				if ($prd_int['prd_id'] !=  $last_post['prd_id']) 
                { 
                    // não é o ganhador do leilao, então posso marcar como desempate  
					$sql    = "UPDATE prd_to_integration SET status_int = 14  WHERE id = ?";
					$query  = $this->db->query($sql, array($prd_int['id']));
				}
				else 
                { 
                    // é o ganhador então tem que descobrir pq não foi enviado e zerar a quantidade
					$status_int = false;
                    
					if ($prd['status'] != 1)  // produto está inativo. 
						$status_int = 90; 
					elseif ($prd['qty'] <= 0)  // produto está sem estoque 
						$status_int = 10;
					elseif ($prd['situacao'] != 2)      // produto está incompleto
						$status_int = 90;
					elseif ($this->hasShipCompany($prd)) // não tem transportadora.                        
						echo "Razao para nao ter sido integrado é desconhecida - parte 2 - ".$prd_int['prd_id']."  ".$prd_int['skubling']."\n";
                    else 
						$status_int = 91;

					if ($status_int) 
                    {
						if ($this->zeraEstoque($prd_int['skubling'], $prd_int['prd_id'])) 
                        {  
                            // zera o estoque no marketplace
							$sql = "UPDATE integration_last_post SET qty = 0, data_ult_envio = ? WHERE id = ?";
							$cmd = $this->db->query($sql, array($int_date_time, $last_post['id']));

							$sql = "UPDATE prd_to_integration SET status_int = ?, date_last_int = ? WHERE id= ?";
							$cmd = $this->db->query($sql, array($status_int, $int_date_time, $prd_int['id']));
						}
					} 
				}
			}
		}
	}


    function hasShipCompany($prd)
    {
		$this->load->library('calculoFrete');
		
		$store = $this->model_stores->getStoresData($prd['store_id']);
		$cat_id = json_decode ( $prd['category_id']);
		$sql = "SELECT * FROM tipos_volumes WHERE id IN (SELECT tipo_volume_id FROM categories WHERE id =".intval($cat_id[0]).")";
		$cmd = $this->db->query($sql);
		$lido = $cmd->row_array();
		$tipo_volume_codigo= $lido['codigo'];		
					
		$prd_info = array (
			'peso_bruto' =>(float)$prd['peso_bruto'],
			'largura' =>(float)$prd['largura'],
			'altura' =>(float)$prd['altura'],
			'profundidade' =>(float)$prd['profundidade'],
			'tipo_volume_codigo' => $tipo_volume_codigo,
		);

		return ($this->calculofrete->verificaCorreios($prd_info) ||
				$this->calculofrete->verificaTipoVolume($prd_info,$store['addr_uf'],$store['addr_uf']) ||
				$this->calculofrete->verificaPorPeso($prd_info,$store['addr_uf'])) ; 
	}


	function getNovoMundo($url, $api_keys)
    {
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_HTTPHEADER     => $this->getHttpHeader($api_keys)
	    );
	    $ch       = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content  = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err      = curl_errno( $ch );
	    $errmsg   = curl_error( $ch );
	    $header   = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode'] = $httpcode;
	    $header['errno']    = $err;
	    $header['errmsg']   = $errmsg;
	    $header['content']  = $content;
	    return $header;
	}
    
    
	function postNovoMundo($url, $post_data, $api_keys)
    {	
        $ch = curl_init();

        curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_HTTPHEADER => $this->getHttpHeader($api_keys)
        ));

        $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}


	function putNovoMundo($url, $post_data, $api_keys)
    {		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_CUSTOMREQUEST  => "PUT",
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER =>  $this->getHttpHeader($api_keys)
	    );

	    $ch      = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}


	function deleteNovoMundo($url, $api_keys)
    {
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	        CURLOPT_CUSTOMREQUEST  => "DELETE",
			CURLOPT_HTTPHEADER     => $this->getHttpHeader($api_keys)
	    );

	    $ch      = curl_init( $url );
		curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}


    private function getHttpHeader($api_keys) 
    {
        if (empty($api_keys))
            return false;
            
        $keys = array();

        foreach ($api_keys as $k => $v)
        {
            if ($k != 'api_url' && $k != 'int_to')
                $keys[] = $k.':'.$v;
        }

        return $keys;        
    }


    function errorTransformation($prd_id, $sku, $msg, $prd_to_integration_id = null, $mkt_code = null)
	{
		$this->model_errors_transformation->setStatusResolvedByProductId($prd_id,$this->int_to);

		$trans_err = array(
			'prd_id' => $prd_id,
			'skumkt' => $sku,
			'int_to' => $this->int_to,
			'step' => "Preparação para envio",
			'message' => $msg,
			'status' => 0,
			'date_create' => date('Y-m-d H:i:s'), 
			'reset_jason' => '', 
			'mkt_code' => $mkt_code,
		);

		echo "Produto ".$prd_id." skubling ".$sku." int_to ".$this->int_to." ERRO: ".$msg."\n"; 
		$insert = $this->model_errors_transformation->create($trans_err);
		
		if (!is_null($prd_to_integration_id)) 
        {
			$sql = "UPDATE prd_to_integration SET date_last_int = now() WHERE id = ?";
			$cmd = $this->db->query($sql,array($prd_to_integration_id));
		}
	}

}
?>