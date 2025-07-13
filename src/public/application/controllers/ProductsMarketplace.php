<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ProductsMarketplace extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        
        $this->not_logged_in();
        
        $this->data['page_title'] = $this->lang->line('application_productsmarketplace');
        
        $this->load->model('model_products');   
		$this->load->model('model_products_marketplace'); 
		$this->load->model('model_stores'); 
		$this->load->model('model_integrations'); 
		$this->load->model('model_errors_transformation'); 
		$this->load->model('model_settings'); 
    }

	public function index()
    {
        if(!in_array('updateProductsMarketplace', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		$Preco_Quantidade_Por_Marketplace = $this->model_settings->getStatusbyName('price_qty_by_marketplace');
		if ($Preco_Quantidade_Por_Marketplace != 1) {
			 redirect('dashboard', 'refresh');
        }
		$this->data['names_marketplaces'] = $this->model_integrations->getNamesIntegrationsbyCompanyStore($this->data['usercomp'],$this->data['userstore']); 
        $this->data['stores_filter'] = $this->model_stores->getStoresData();
        $this->render_template('productsmarketplace/index', $this->data);
    }

    public function fetchPricesData()
	{
		if(!in_array('updateProductsMarketplace', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		$Preco_Quantidade_Por_Marketplace = $this->model_settings->getStatusbyName('price_qty_by_marketplace');
		if ($Preco_Quantidade_Por_Marketplace != 1) {
			 redirect('dashboard', 'refresh');
        }
		
		$result = array('data' => array());
		
		$postdata = $this->postClean(NULL,TRUE);
		$ini = $postdata['start'];
		$draw = $postdata['draw'];
		$busca = $postdata['search']; 
		$length = $postdata['length'];
		//$postdata['int_to'] = array('MLC');
		
		// busco o percentual de estoque de cada marketplace 
		$sql = "select id, value ,concat(lower(value),'_perc_estoque') as name from attribute_value av where attribute_parent_id = 5";
		$query = $this->db->query($sql);
		$mkts = $query->result_array();
		foreach ($mkts as $ind => $val) {
			$sql = "select value from settings where name = '".$val['name']."'";
			$query = $this->db->query($sql);
			$parm = $query->row_array();
			$key_param = $val['value'].'PERC'; 
			$parms[$key_param] = $parm['value'];
		}
		
		$this->data['pricesfilter'] = '';
        if ($busca['value']) {
            if (strlen($busca['value'])>=2) {  // Garantir no minimo 3 letras
                $this->data['pricesfilter'] = " AND ( p.sku like '%".$busca['value']."%' 
                OR p.name like '%".$busca['value']."%' OR pm.prd_id like '%".$busca['value']."%' OR i.name like '%".$busca['value']."%'
                ) ";
            } else {
                //return true;
            }
        } else {
            if (trim($postdata['sku'])) {
                $this->data['pricesfilter'] .= " AND p.sku like '%".$postdata['sku']."%' ";
            }
			 if (trim($postdata['nome'])) {
                $this->data['pricesfilter'] .= " AND p.name like '%".$postdata['nome']."%' ";
            }
            if (is_array($postdata['lojas'])) {
                $lojas = $postdata['lojas'];
                $this->data['pricesfilter'] .= " AND (";
                foreach($lojas as $loja) {
                    $this->data['pricesfilter'] .= "s.id = ".(int)$loja." OR ";
                }
                $this->data['pricesfilter'] = substr($this->data['pricesfilter'], 0, (strlen($this->data['pricesfilter'])-3));
                $this->data['pricesfilter'] .= ") ";
            }
            if (is_array($postdata['int_to'])) {
                $int_tos = $postdata['int_to'];
                $this->data['pricesfilter'] .= " AND (";
                foreach($int_tos as $int_to) {
                    $this->data['pricesfilter'] .= "pm.int_to = '".$int_to."' OR ";
                }
                $this->data['pricesfilter'] = substr($this->data['pricesfilter'], 0, (strlen($this->data['pricesfilter'])-3));
                $this->data['pricesfilter'] .= ") ";
            }

            $deletedStatus = Model_products::DELETED_PRODUCT;
            if ($postdata['status']) {
                $this->data['pricesfilter'] .= " AND (p.status = {$postdata['status']}
                    AND p.status NOT IN ({$deletedStatus}))";
            } else {
                $this->data['pricesfilter'] .=" AND p.status NOT IN ({$deletedStatus})";
            }

            if (trim($postdata['completo'])) {
            	$this->data['pricesfilter'] .= " AND p.situacao = ".$postdata['completo'];
            }
			if (trim($postdata['bsame_price'])) {
				$val = $postdata['bsame_price']-1;
            	$this->data['pricesfilter'] .= " AND pm.same_price = ".$val;
            }
			if (trim($postdata['bsame_qty'])) {
				$val = $postdata['bsame_qty']-1; 
            	$this->data['pricesfilter'] .= " AND pm.same_qty = ".$val;
            }
			if (trim($postdata['estoque'])) {
            	switch ((int)$postdata['estoque']) {
                    case 1: 
                        $this->data['pricesfilter'] .= " AND p.qty > 0 ";
                        break;
                    case 2:
                        $this->data['pricesfilter'] .= " AND p.qty <= 0 ";
                        break;
                }
            }
			
        }
				
		//$sOrder = ' ORDER BY pm.prd_id ASC ';
		if (isset($postdata['order'])) {
			if ($postdata['order'][0]['dir'] == "asc") {
				$direcao = "ASC";
			} else { 
				$direcao = "DESC";
		    }
			$campos = array('pm.prd_id','i.name','p.sku','p.name','CAST(pm.price AS DECIMAL(12,2))','CAST(p.price AS DECIMAL(12,2))','CAST(p.qty AS UNSIGNED)','s.name','');
			$campo =  $campos[$postdata['order'][0]['column']];
			if ($campo != "") {
				if ($campo == 'id') {
					if ($direcao =="ASC") {$direcao ="DESC";}
					else {$direcao ="ASC";}
				}
				$sOrder = " ORDER BY ".$campo." ".$direcao;
		    }
		}

		$data = $this->model_products_marketplace->getProductsDataView($ini, $this->data['pricesfilter'], $sOrder, $length );
		$filtered = $this->model_products_marketplace->getProductsDataCount($this->data['pricesfilter']);
		if ($this->data['pricesfilter'] == '') {
			$total_rec = $filtered;
		}
		else {
			$total_rec = $this->model_products_marketplace->getProductsDataCount();
		}

		$result = array();
		foreach ($data as $key => $value) {
			// button
			$status  = '';
			switch ($value['prdstatus']) {
                case 1: 
                    $status =  '<span class="label label-success">'.$this->lang->line('application_active').'</span>';
                    break;
                default:
                    $status = '<span class="label label-danger">'.$this->lang->line('application_inactive').'</span>';
                    break;
			}
			switch ($value['prdsituacao']) {
				case 1: 
                     $status .=  '<br><span class="label label-danger">'.$this->lang->line('application_incomplete').'</span><br>';
					 break;
                case 2: 
                     $status .=  '<br><span class="label label-success">'.$this->lang->line('application_complete').'</span><br>';
                     break;
			}
			
			$integration = $this->model_integrations->getPrdIntegrationByIntTo($value['int_to'],$value['prd_id'],$value['store_id']);

            if ($integration) {
            	$error_transformation = $this->model_errors_transformation->countErrorsByProductId($integration['id'],$integration['int_to']);
				if ($error_transformation >0) {
					$status .= '<span class="label label-danger" >'.mb_strtoupper($this->lang->line('application_errors_tranformation'),'UTF-8').'</span>';
				} elseif ($integration['status_int']==0) {
                    $status .= '<span class="label label-warning">'.mb_strtoupper($this->lang->line('application_product_in_analysis'),'UTF-8').'</span>';
                } elseif ($integration['status_int']==1) {
                    $status .= '<span class="label label-success">'.mb_strtoupper($this->lang->line('application_product_waiting_to_be_sent'),'UTF-8').'</span>';
                } elseif ($integration['status_int']==2) {
                    $status .= '<span class="label label-primary">'.mb_strtoupper($this->lang->line('application_product_sent'),'UTF-8').'</span>';
                } elseif ($integration['status_int']==11) {
                    $over = $this->model_integrations->getPrdBestPrice($value['EAN']);
                    $status .= '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_product_higher_price'),'UTF-8').'('.$over.')</span>';
                } elseif ($integration['status_int']==12) {
                    $status .= '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_product_higher_price'),'UTF-8').'</span>';
                } elseif ($integration['status_int']==13) {
                    $status .= '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_product_higher_price'),'UTF-8').'</span>';
                } elseif ($integration['status_int']==14) {
                    $status .= '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_product_release'),'UTF-8').'</span>';
                } elseif ($integration['status_int']==20) {
                    $status .= '<span class="label label-success">'.mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8').'</span>';
                } elseif ($integration['status_int']==21) {
                    $status .= '<span class="label label-success">'.mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8').'</span>';
                } elseif ($integration['status_int']==22) {
                    $status .= '<span class="label label-success">'.mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8').'</span>';
                } elseif ($integration['status_int']==23) {
                    $status .= '<span class="label label-success">'.mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8').'</span>';
				} elseif ($integration['status_int']==24) {
                    $status .= '<span class="label label-success">'.mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8').'</span>';
                } elseif ($integration['status_int']==90) {
                    $status .= '<span class="label label-default">'.mb_strtoupper($this->lang->line('application_product_inactive'),'UTF-8').'</span>';
                } elseif ($integration['status_int']==99) {
                    $status .= '<span class="label label-warning">'.mb_strtoupper($this->lang->line('application_product_in_analysis'),'UTF-8').'</span>';
                } else {
                    $status .= '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_product_out_of_stock'),'UTF-8').'</span>';
                }
            }
			else {
				$status .= '<span class="label label-default">'.mb_strtoupper($this->lang->line('application_not_integrated'),'UTF-8').'</span>';
			}
			
			$skuvariant ='';
			if ($value['variant'] != ""  && $value['hub']) { // mostra as variações somente se for HUB
				$variant = $this->model_products->getVariants($value['prd_id'],$value['variant'] );
				$skuvariant='/'.$variant['sku'];
				$value['prdqty'] = $variant['qty'];
				$variants_names = explode(';', $value['has_variants']);
				$variants_value = explode(';', $variant['name']);
				$i = 0;
				$value['name'] .= '<br>Variação:';
				foreach ($variants_names as $variants_name) {
					$value['name'] .= $variants_name.":".$variants_value[$i++]."; ";
				}
			}
			
			$disablePrice = '';
			$checkPrice = '';
			if ($value['same_price']) {$disablePrice = 'disabled'; $checkPrice = 'checked';}
			$is_kit='';
			$price = $this->formatprice($value['price']);
			$prdprice = $this->formatprice($value['prdprice']);
			if($value['is_kit'] == 1) {
				$is_kit = '<br><span class="label label-warning">Kit</span>';
			}
			elseif (($value['variant'] == "") || ($value['variant'] == "0"))  {
				$price = "<input type='text' $disablePrice id='price_$value[id]' class='form-control'  onfocus='this.value=formatPriceVirgula(this.value)' onKeyUp='this.value=formatPrice(this.value)' onfocusout='this.value=changePrice($value[id], $value[price], this.value, 0)'   value='$price' size='7' />
				<br><input type='checkbox' id='samePrice_$value[id]'  onchange='samePrice($value[id],$value[prdprice])' $checkPrice >
				<span style=' border:1px solid black;' class='label label-default'>$prdprice</span>"; 
			}else {
				$price = '-';
			}
			
			if  ($value['hub'] !== false) { // Se é loja Conecta lá aplica o percentual e não muda a quantidade
				$key_param = $value['int_to'].'PERC'; 
				$qty_atual = (int) $value['prdqty'] * $parms[$key_param] / 100; 
				$qty_atual = ceil($qty_atual); // arrendoda para cima  
				if ((int) $value['prdqty'] < 5)  {
					$qty_atual = 0;
					if ($value['int_to'] == 'B2W') {
						$qty_atual = (int) $value['prdqty'];
					}		    
				}
				$value['qty'] = $qty_atual;
			}
			$checkQty = '';
			$disableQty = '';
			if ($value['same_qty']) {$disableQty = 'disabled'; $checkQty = 'checked';}
			
			if (($value['is_kit'] == 1) || ($value['hub'] !== false)) {
				$qty = $value['qty'].'/'.$value['prdqty']. '<br>';
			}
			else{
			    $qty = "<input type='text' $disableQty id='qty_$value[id]' class='form-control'  onchange='this.value=changeQty($value[id], $value[qty], this.value, $value[prdqty],0)' onKeyPress='return digitos(event, this)' value='$value[qty]' size='3' />
			    <br><input type='checkbox' id='sameQty_$value[id]'  onchange='sameQty($value[id],$value[prdqty])' $checkQty >
			    <span style='border:1px solid black;' class='label label-default'>$value[prdqty]</span>"; 
			}
			
			$link_id = "<a href='".base_url('products/update/'.$value['prd_id'])."'>".$value['sku'].$skuvariant.' '.$is_kit."</a>";
			$result[$key] = array(
				$value['prd_id'],
				$value['mktname'],
				$link_id,
				$value['name'],
				$price,
				$qty,
				$value['loja'],
				$status,
			);
	
		} // /foreach
		if ($filtered==0) {$filtered = $i;}
		$output = array(
			"draw" => $draw,
		    "recordsTotal" => $total_rec,
		    "recordsFiltered" => $filtered,
		    "data" => $result
		);
		ob_clean();
		echo json_encode($output);
		
	}

	public function updateQty()
    {
    	if(!in_array('updateProductsMarketplace', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		$Preco_Quantidade_Por_Marketplace = $this->model_settings->getStatusbyName('price_qty_by_marketplace');
		if ($Preco_Quantidade_Por_Marketplace != 1) {
			 redirect('dashboard', 'refresh');
        }
        $id = $this->postClean('id');
        $old_qty = $this->postClean('old_qty');
        $new_qty = $this->postClean('new_qty');
		$same_qty = $this->postClean('same_qty');

        if (!is_numeric($new_qty)) return;

        $data = [
            'qty' => $new_qty,
            'same_qty'=> $same_qty,
        ];

        $saved = $this->model_products_marketplace->updateGet($data, $id);
        $log = [
            'id' 	 	 => $id,
            'int_to' 	 => $saved['int_to'],
            'product_id' => $saved['prd_id'],
            'same_qty'   => $same_qty,
            'old_qty'    => $old_qty,
            'new_qty'    => $new_qty
        ];
        $this->log_data(__CLASS__, __FUNCTION__, json_encode($log), 'I');
	}	
	
	public function updatePrice()
    {
    	if(!in_array('updateProductsMarketplace', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		$Preco_Quantidade_Por_Marketplace = $this->model_settings->getStatusbyName('price_qty_by_marketplace');
		if ($Preco_Quantidade_Por_Marketplace != 1) {
			 redirect('dashboard', 'refresh');
        }
		
        $id   = $this->postClean('id');
        $old_price = $this->postClean('old_price');
        $new_price = $this->postClean('new_price');
		$same_price = $this->postClean('same_price');

        if (substr_count($new_price, '.') > 1) {
            $new_price = substr_replace(str_replace('.', '', $new_price), '.', -2, 0);
        }
		$old_price = preg_replace('/[^0-9,.]/', '', $old_price);
		if (substr_count($old_price, ',') > 0) {
			$old_price = floatval(str_replace(',', '.', str_replace('.', '', $old_price)));
		}

        $data = [
            'price' => $new_price, 
            'same_price' => $same_price
        ];
		
		
		$atual = $this->model_products_marketplace->getData($id);
		
		if (($atual['price'] == $new_price) && ($atual['same_price'] == $same_price)) {
			// nada mudou não preciso alterar nem gravar log 
			return ;
		}
		$saved = $this->model_products_marketplace->updateGet($data, $id);

        $log = [
            'id' 	 	 => $id,
            'int_to' 	 => $atual['int_to'],
            'product_id' => $atual['prd_id'],
            'old_same_price' => $atual['same_price'],
            'same_price' => $same_price,
            'old_price'  => $atual['price'],
            'new_price'  => $new_price
        ];
        $this->log_data(__CLASS__, __FUNCTION__, json_encode($log), 'I');
		
		if ($saved['variant'] != '') { // remover isso quando tivermos preço por variação. 
			$this->model_products_marketplace->updateAllVariants($data, $atual['int_to'], $atual['prd_id']);
		}
		
		$this->model_products->update(array('date_update' => date('Y-m-d H:i:s')),$atual['prd_id']); // faço um update na data do arquivo para que o mesmo seja atualizado no marketplace
    }

}