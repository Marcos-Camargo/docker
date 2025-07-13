<!--
SW Serviços de Informática 2019

Editar Pedidos

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_view";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="messages"></div>

        <?php if($this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('success'); ?>
          </div>
        <?php elseif($this->session->flashdata('error')): ?>
          <div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('error'); ?>
          </div>
        <?php endif; ?>
         
        <?php // se tem incidente no marketplace aparece só para o ADMIN
        if (( $order_data['order']['has_incident']) && (in_array('admDashboard', $user_permission)))  : ?>
			<div class="alert alert-error alert-dismissible" role="alert">
	            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	            <?php echo $this->lang->line('application_has_incident'); ?>
	            
	     	</div>
		<?php endif ?>
		
        <div class="box">
              <div class="box-body">
              	
              	<div class="row">
	       			<ol id="barra-simples-2" class="mid step-progress-bar">
	       			<?php if ($order_data['order']['paid_status'] == 1) { ?>
						<li class="<?= $order_data['order']['date_time'] ? 'step-past' : 'step-future' ?>" style="width: 50%; z-index: 2;">
							<span class="content-wpp"><?=$this->lang->line('application_order_date');?></span>
							<span class="content-bullet">1</span>
							<span class="content-wpp"><?= $order_data['order']['date_time']? date('d/m/Y', strtotime($order_data['order']['date_time'])) : '' ?></span>
						</li>
						<li class='step-future' style="width: 50%; z-index: 1;">
							<span class="content-wpp"><?=$this->lang->line('application_pay_date');?></span>
							<span class="content-bullet">2</span>
							<span class="content-wpp"></span>
							<span class="content-stick step-future"></span>
						</li>
					<?php } else { ?>
						<li class="<?= $order_data['order']['date_time'] ? 'step-past' : 'step-future' ?>" style="width: 20%; z-index: 5;">
							<span class="content-wpp"><?=$this->lang->line('application_order_date');?></span>
							<span class="content-bullet">1</span>
							<span class="content-wpp"><?= $order_data['order']['date_time']? date('d/m/Y', strtotime($order_data['order']['date_time'])) : '' ?></span>
						</li>
						<li class="<?= $order_data['order']['data_pago'] ? 'step-past' : 'step-future' ?>" style="width: 20%; z-index: 4;">
							<span class="content-wpp"><?=$this->lang->line('application_pay_date');?></span>
							<span class="content-bullet">2</span>
							<span class="content-wpp"><?= $order_data['order']['data_pago'] ? date('d/m/Y', strtotime($order_data['order']['data_pago'])) : '' ?></span>
							<span class="content-stick <?= $order_data['order']['data_pago'] ? 'step-past' : 'step-future' ?>"></span>
						</li>
						<?php if (is_null($order_data['order']['data_envio'])) { ?>
							<li class="<?= ($order_data['order']['data_limite_cross_docking'] < date('Y-m-d H:i:s')) ? 'step-delay' : 'step-present' ?>" style="width: 20%; z-index: 3;">
								<span class="content-wpp"><?=$this->lang->line('application_dispatch');?></span>
								<span class="content-bullet">3</span>
								<span class="content-wpp"><?= $order_data['order']['data_limite_cross_docking'] ? date('d/m/Y', strtotime($order_data['order']['data_limite_cross_docking'])) : '' ?></span>
								<span class="content-stick <?= ($order_data['order']['data_limite_cross_docking'] <  date('Y-m-d H:i:s'))? 'step-present' : 'step-future' ?>"></span>
							</li>
						<?php } else { ?>	
							<li class="<?= $order_data['order']['data_limite_cross_docking'] ? 'step-past' : 'step-future' ?>" style="width: 20%; z-index: 2;">
								<span class="content-wpp"><?=$this->lang->line('application_dispatch');?></span>
								<span class="content-bullet">3</span>
								<span class="content-wpp"><?= $order_data['order']['data_limite_cross_docking'] ? date('d/m/Y', strtotime($order_data['order']['data_limite_cross_docking'])) : '' ?></span>
								<span class="content-stick <?= $order_data['order']['data_limite_cross_docking'] ? 'step-past' : 'step-future' ?>"></span>
							</li>
						<?php } ?>	
						
						<li class="<?= $order_data['order']['data_envio'] ? 'step-past' : 'step-future' ?>" style="width: 20%; z-index: 2;">
							<span class="content-wpp"><?=$this->lang->line('application_ship_date');?></span>
							<span class="content-bullet">4</span>
							<span class="content-wpp"><?= $order_data['order']['data_envio'] ? date('d/m/Y', strtotime($order_data['order']['data_envio'])) : '' ?></span>
							<span class="content-stick <?= $order_data['order']['data_envio'] ? 'step-past' : 'step-future' ?>"></span>
						</li>

						<li class="<?= $order_data['order']['data_entrega'] ? 'step-past' : 'step-future' ?>" style="width: 20%; z-index: 1;">
							<span class="content-wpp"><?=$this->lang->line('application_delivered_date');?></span>
							<span class="content-bullet">5</span>
							<span class="content-wpp"><?= $order_data['order']['data_entrega'] ? date('d/m/Y', strtotime($order_data['order']['data_entrega'])) : '' ?></span>
							<span class="content-stick <?= $order_data['order']['data_entrega'] ? 'step-past' : 'step-future' ?>"></span>
						</li>
					
					<?php }  ?>
					</ol> 
				</div>

                <div class="col-md-12 col-xs-12 pull pull-left">

                  <h3><?=$this->lang->line('application_order_data')?></h3>
                  <div class="form-group col-md-2">
                    <label for="id"><?=$this->lang->line('application_order');?></label>
                    <div>
                      <span class="form-control"><?php echo $order_data['order']['id'] ?></span>
                    </div>
                  </div>
                  
                  <div class="form-group col-md-4">
                    <label for="numero_marketplace"><?=$this->lang->line('application_order_marketplace_full');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['numero_marketplace'] ?></span>
                    </div>
                  </div>


                  <div class="form-group col-md-2">
                    <label for="date_time"><?=$this->lang->line('application_date');?></label>
                    <div>
                    	<span class="form-control"><?php echo date('d/m/Y', strtotime($order_data['order']['date_time'])); ?></span>
                    </div>
                  </div>

                  <div class="form-group col-md-4">
                    <label for="paid_status"><?=$this->lang->line('application_status');?></label>
                     <?php if ($pedido_cancelado) : ?>
                    <button onClick="toggleCancelReason(event)" data-toggle="tooltip"  data-placement="top" title="<?=$this->lang->line('application_cancel_reason');?>" ><i class="fa fa-question-circle"></i></button>
                    <?php endif; ?>
                    <div>
                    	<span class="form-control" data-toggle="tooltip"  data-placement="top" title="<?php echo $order_data['order']['paid_status']?>" ><?php  echo $status_str;?>  </span>
                    </div>
                  </div>
				  <?php if ($pedido_cancelado) : ?>
				  	<div id="cancel_reason" style="display: none;">
						  <table border="2" bordercolor="red" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
						  	<TR>
		   					 	<TD>
								  <div class="form-group col-md-5">
				                    <label><?=$this->lang->line('application_cancel_reason');?></label>
				                    <div>
				                    	<span ><?php  echo $pedido_cancelado['reason'];?>  </span>
				                    </div>
				                  </div>
				                  
				                   <div class="form-group col-md-2">
				                    <label><?=$this->lang->line('application_penalty_to');?></label>
				                    <div>
				                    	<span ><?php  echo $pedido_cancelado['penalty_to'];?>  </span>
				                    </div>
				                  </div>
				                  
				                  <div class="form-group col-md-3">
				                    <label><?=$this->lang->line('application_registered_by');?></label>
				                    <div>
				                    	<span ><?php  echo $pedido_cancelado['username'];?>  </span>
				                    </div>
				                  </div>
				                  
				                  <div class="form-group col-md-2">
				                    <label><?=$this->lang->line('application_date');?></label>
				                    <div>
				                    	<span ><?php  echo date("d/m/Y H:i:s",strtotime($pedido_cancelado['date_update']));?>  </span>
				                    </div>
				                  </div>
		                  		</TD>
							</TR>
						  </TABLE>
				  </div>
				  <?php endif; ?>	

                  <div class="form-group col-md-2">
                    <label for="origin"><?=$this->lang->line('application_origin');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['origin'] ?></span>
                    </div>
                  </div>

                  <div class="form-group col-md-5">
                    <label for="customer_name"><?=$this->lang->line('application_name');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['customer_name'] ?></span>
                    </div>
                  </div>
                  
                  <div class="form-group col-md-3">
                    <label for="customer_name"><?php echo $this->lang->line('application_cpf').'/'.$this->lang->line('application_cnpj');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['cliente']['cpf_cnpj'] ?></span>
                    </div>
                  </div>

                  <div class="form-group col-md-2">
                    <label for="phone_customer"><?php echo $this->lang->line('application_phone');?></label>
                    <div>
                        <span class="form-control"><?php echo $order_data['order']['customer_phone'] ?></span>

                    </div>
                  </div>
				  <div class="row"></div>
                  <div class="form-group col-md-6">
                    <label for="company_id"><?=$this->lang->line('application_company');?></label>
                    <div>
                   	    <a id="company_id" name="company_id" href="<?php echo base_url().'company/update/'.$order_data['order']['company_id'];?>"  target="_blank"><span "><?php echo $order_data['order']['empresa']['name']  ?> &nbsp </span><i class="fa fa-eye"></i></a>
                    </div>
                  </div>
                  
                  <div class="form-group col-md-6">
                    <label for="store_id"><?=$this->lang->line('application_store');?></label>
                    <div>
                     	<a id="store_id" name="store_id" href="<?php echo base_url().'stores/update/'.$order_data['order']['store_id'];?>"  target="_blank"><span "><?php echo $order_data['order']['loja']['name']  ?> &nbsp </span><i class="fa fa-eye"></i></a>
                    </div>
                  </div>

                <div class="col-md-12">
                	<h3><?=$this->lang->line('application_items')?></h3>
                </div>
                <div class="col-md-12" style="overflow-x:auto;">
	                <table class="table table-bordered" id="product_info_table">
	                  <thead>
	                    <tr>
	                      <th style="width:5%"><?=$this->lang->line('application_item');?></th>
	                      <th style="width:8%"><?=$this->lang->line('application_sku');?></th>
	                      <th style="width:30%"><?=$this->lang->line('application_product');?></th>
	                      <th style="width:8%"><?=$this->lang->line('application_qty');?></th>
	                      <th style="width:10%"><?=$this->lang->line('application_value');?></th>
	                      <th style="width:10%"><?=$this->lang->line('application_discount');?></th>
	                      <th style="width:10%"><?=$this->lang->line('application_net_value');?></th>
	                      <th style="width:10%"><?=$this->lang->line('application_kit');?></th>
	                    </tr>
	                  </thead>
	
	                   <tbody>
	
	                    <?php if(isset($order_data['order_item'])): ?>
	                      <?php $x = 1; ?>
	                      <?php foreach ($order_data['order_item'] as $key => $val): ?>
	                        <?php //print_r($v); ?>
	                       <tr id="row_<?php echo $x; ?>">
                               <td><?= $x; ?></td>
                               <?php if ($val['status'] == Model_products::DELETED_PRODUCT) { ?>
                                   <td class="deleted-order-item">
                                       <a href="#" data-toggle="tooltip" data-placement="right"
                                          title="<?= $this->lang->line('application_excluded_products') ?>">
                                           <span><?php echo $val['sku'] ?></span>
                                           <i class="fa fa-trash-o"></i>
                                       </a>
                                   </td>
                               <?php } else { ?>
                                   <td class="link-order-item">
                                       <a href="<?php echo base_url() . 'products/update/' . $val['product_id']; ?>"
                                          target="_blank">
                                           <span><?php echo $val['sku'] ?></span>
                                           <i class="fa fa-eye"></i>
                                       </a>
                                   </td>
                               <?php } ?>
	                          <td><span class="form-control"><?php echo $val['name'] ?></span></td>
	                          <td><span class="form-control"><?php echo $val['qty'] ?></td>
	                          <td><span class="form-control"><?php echo get_instance()->formatprice($val['rate']) ?></td>
	                          <td><span class="form-control"><?php echo get_instance()->formatprice($val['discount']) ?></td>	
	                          <td><span class="form-control"><?php echo get_instance()->formatprice($val['amount']) ?></td>
	                          <td>
	                          	<?php if ($val['kit_id']) : ?>
	                            <a id="kit_id" name="kit_id" href="<?php echo base_url().'productsKit/update/'.$val['kit_id'];?>"  target="_blank"><span "><?php echo $val['kit_id']; ?> &nbsp </span><i class="fa fa-eye"></i></a>
	                          	<?php endif; ?>
	                          </td>
	                       </tr>
	                       <?php $x++; ?>
	                     <?php endforeach; ?>
	                   <?php endif; ?>
	                   </tbody>
	                </table>
                </div>
                <br /> <br/>


                  <div class="form-group col-md-3">
                    <label for="num_itens"><?=$this->lang->line('application_items');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['num_items'] ?></span>
                    </div>
                  </div>

                  <div class="form-group col-md-3">
                    <label for="sum_qty"><?=$this->lang->line('application_total_qty');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['sum_qty'] ?></span>
                    </div>
                  </div>

                  <div class="form-group col-md-3">
                    <label for="total_order"><?=$this->lang->line('application_total_products');?></label>
                    <div>
                    	<span class="form-control"><?php echo get_instance()->formatprice($order_data['order']['total_order']) ?></span>
                    </div>
                  </div>

                  <div class="form-group col-md-3">
                    <label for="discount"><?=$this->lang->line('application_discount');?></label>
                    <div>
                    	<span class="form-control"><?php echo get_instance()->formatprice($order_data['order']['discount']) ?></span>
                    </div>
                  </div>
                  <div class="form-group col-md-3">
                    <label for="freight"><?=$this->lang->line('application_ship_value');?></label>
                    <div>
                    	<span class="form-control"><?php echo get_instance()->formatprice($order_data['order']['total_ship']) ?></span>
                    </div>
                  </div>
                  <div class="form-group col-md-3">
                    <label for="gross_amount" data-toggle="tooltip" title="<?=$this->lang->line('application_value_products')?> + <?=$this->lang->line('application_freight')?> - <?=$this->lang->line('application_discount')?>"><?=$this->lang->line('application_gross_amount');?></label>
                    <div>
                    	<span class="form-control"><?php echo get_instance()->formatprice($order_data['order']['gross_amount']) ?></span>
                    </div>
                  </div>
                  <div class="form-group col-md-3">
                    <label for="service_charge"><?=$this->lang->line('application_taxes');?></label>
					<?php $taxas = $order_data['order']['service_charge'] + $order_data['order']['vat_charge']; ?>
                    <div>
                    	<span class="form-control"><?php echo get_instance()->formatprice($taxas) ?></span>
                     </div>
                  </div>
                  <div class="form-group col-md-3">
                    <label for="net_order" data-toggle="tooltip" title="<?=$this->lang->line('application_gross_amount')?> - <?=$this->lang->line('application_freight')?> - <?=$this->lang->line('application_taxes')?>"><?=$this->lang->line('application_net_value');?></label>
                    <div>
                    	<span class="form-control"><?php echo get_instance()->formatprice($order_data['order']['net_amount']) ?></span>
                    </div>
                  </div>
              
               <div class="col-md-12">
                <br /><h3><?=$this->lang->line('application_payments')?></h3>
               </div>
               <div class="col-md-12" style="overflow-x:auto;">
                <table class="table table-bordered" id="product_info_table">
                  <thead>
                    <tr>
                      <th style="width:5%"><?=$this->lang->line('application_item');?></th>
                      <th style="width:5%"><?=$this->lang->line('application_order');?></th>
                      <th style="width:8%"><?=$this->lang->line('application_parcel');?></th>
                      <th style="width:8%"><?=$this->lang->line('application_date');?></th>
                      <th style="width:10%"><?=$this->lang->line('application_value');?></th>
                      <th style="width:8%"><?=$this->lang->line('application_description');?></th>
                    </tr>
                  </thead>

                   <tbody>

                    <?php if(isset($order_data['pagtos'])): ?>
                      <?php $x = 1; ?>
                      <?php foreach ($order_data['pagtos'] as $key => $val): ?>
                        <?php //print_r($v); ?>
                       <tr id="row_<?php echo $x; ?>">
                       		<td><span class="form-control"><?php echo $val['id'] ?></span></td>
                       		<td><span class="form-control"><?php echo $val['order_id'] ?></span></td>
                       		<td><span class="form-control"><?php echo $val['parcela'] ?></span></td>
                       		<td><span class="form-control"><?php echo $val['data_vencto'] ?></span></td>
                       		<td><span class="form-control"><?php echo get_instance()->formatprice($val['valor']) ?></span></td>
                       		<td><span class="form-control"><?php echo $val['forma_desc'] ?></span></td>
                       </tr>
                       <?php $x++; ?>
                     <?php endforeach; ?>
                   <?php else: echo "<tr><td colspan=6>" . $this->lang->line('messages_no_informations') . "</td></tr>";  ?>
                   <?php endif; ?>
                   </tbody>
                </table>
                </div>
                
                <div class="col-md-12">
                 <br /><h3>NFe</h3>
                 </div>
                 <div class="form-group col-md-3">
                    <label><?=$this->lang->line('application_crossdocking_limit_date');?></label>
                    <div>
                    	<span class="form-control"><?php $date = new DateTime($order_data['order']['data_limite_cross_docking']); echo date_format($date, 'd/m/Y'); ?></span> 
                    </div>
                 </div>
				<div class="col-md-12" style="overflow-x:auto;">
                 <table class="table table-bordered" id="product_info_table">
                  <thead>
                    <tr>
                      <th style="width:5%"><?=$this->lang->line('application_item');?></th>
                      <th style="width:8%"><?=$this->lang->line('application_ship_date');?></th>
                      <th style="width:8%"><?=$this->lang->line('application_serie');?></th>
                      <th style="width:8%"><?=$this->lang->line('application_number');?></th>
                      <th style="width:20%"><?=$this->lang->line('application_key');?></th>
                    </tr>
                  </thead>

                   <tbody>

                    <?php if(isset($order_data['nfes'])): ?>
                      <?php $x = 1; ?>
                      <?php foreach ($order_data['nfes'] as $key => $val): ?>
                        <?php //print_r($v); ?>
                       <tr id="row_<?php echo $x; ?>">
                       		<td><span class="form-control"><?php echo $val['id'] ?></span></td>
                       		<td><span class="form-control"><?php echo $val['date_emission'] ?></span></td>
                       		<td><span class="form-control"><?php echo $val['nfe_serie'] ?></span></td>
                       		<td><span class="form-control"><?php echo $val['nfe_num'] ?></span></td>
                       		<td><span class="form-control"><?php echo $val['chave'] ?></span></td>
                       </tr>
                       <?php $x++; ?>
                     <?php endforeach; ?>
                   <?php else: echo "<tr><td colspan=6>" . $this->lang->line('messages_no_informations') . "</td></tr>";  ?>
                   <?php endif; ?>
                   </tbody>
                </table>
                </div>
                
               <div class="col-md-12 col-xs-12 pull pull-left">
                <h3><?=$this->lang->line('application_delivery_address')?></h3> 
            	  <div class="form-group col-md-5">
                    <label for="customer_address"><?=$this->lang->line('application_address');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['customer_address'] ?></span>
                    </div>
                  </div>
                  <div class="form-group col-md-1">
                    <label for="customer_address_num"><?=$this->lang->line('application_number');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['customer_address_num'] ?></span>
                    </div>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="customer_address_compl"><?=$this->lang->line('application_complement');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['customer_address_compl'] ?></span>
					</div>
                  </div>
                  <div class="form-group col-md-4">
                    <label for="customer_address_neigh"><?=$this->lang->line('application_neighb');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['customer_address_neigh'] ?></span>
                    </div>
                  </div>
                  <div class="form-group col-md-4">
                    <label for="customer_address_city"><?=$this->lang->line('application_city');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['customer_address_city'] ?></span>
                    </div>
                  </div>
                   <div class="form-group col-md-1">
                    <label for="customer_address_uf"><?=$this->lang->line('application_uf');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['customer_address_uf'] ?></span>
                    </div>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="customer_address_zip"><?=$this->lang->line('application_zip_code');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['customer_address_zip'] ?></span>
                     </div>
                  </div>
                  <div class="form-group col-md-5">
                    <label for="customer_reference"><?=$this->lang->line('application_reference');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['customer_reference'] ?></span>
                    </div>
                  </div>
               </div> 
               
               <div class="col-md-12 col-xs-12 pull pull-left">
                <h3><?=$this->lang->line('application_invoice_data')?></h3> 
                
                	<div class="form-group col-md-5">
                    <label for="client_name"><?=$this->lang->line('application_client_name');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['cliente']['customer_name'] ?></span>
                     </div>
                  </div>
                  <div class="form-group col-md-3">
                    <label for="client_cpf_cnpj"><?php echo $this->lang->line('application_cpf').'/'.$this->lang->line('application_cnpj');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['cliente']['cpf_cnpj'] ?></span>
                    </div>
                  </div>
                  <div class="form-group col-md-3">
                    <label for="client_email"><?php echo $this->lang->line('application_email');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['cliente']['email'] ?></span>
                    </div>
                  </div>
                  <div class="form-group col-md-5">
                    <label for="client_address"><?=$this->lang->line('application_address');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['cliente']['customer_address'] ?></span>
                    </div>
                  </div>
                  <div class="form-group col-md-1">
                    <label for="client_address_num"><?=$this->lang->line('application_number');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['cliente']['addr_num'] ?></span>
                    </div>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="client_address_compl"><?=$this->lang->line('application_complement');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['cliente']['addr_compl'] ?></span>
					</div>
                  </div>
                  <div class="form-group col-md-4">
                    <label for="client_address_neigh"><?=$this->lang->line('application_neighb');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['cliente']['addr_neigh'] ?></span>
                    </div>
                  </div>
                  <div class="form-group col-md-5">
                    <label for="client_address_city"><?=$this->lang->line('application_city');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['cliente']['addr_city'] ?></span>
                    </div>
                  </div>
                   <div class="form-group col-md-1">
                    <label for="client_address_uf"><?=$this->lang->line('application_uf');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['cliente']['addr_uf'] ?></span>
                    </div>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="client_address_zip"><?=$this->lang->line('application_zip_code');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['cliente']['zipcode'] ?></span>
                     </div>
                  </div>
                  <div class="form-group col-md-2">
                       <label for="phone_1"><?=$this->lang->line('application_phone');?> 1</label>
                       <div>
                           <span class="form-control"><?php echo $order_data['order']['cliente']['phone_1'] ?></span>
                       </div>
                  </div>
                  <div class="form-group col-md-2">
                       <label for="phone_2"><?=$this->lang->line('application_phone');?> 2</label>
                       <div>
                           <span class="form-control"><?php echo $order_data['order']['cliente']['phone_2'] ?></span>
                       </div>
                  </div>
               </div> 
               
               <div class="col-md-12">
                <h3><?=$this->lang->line('application_freight')?></h3>
               </div>
               <div class="col-md-12" style="overflow-x:auto;">
                <table class="table table-bordered" id="product_info_table">
                  <thead>
                    <tr>
                      <th style="width:5%"><?=$this->lang->line('application_item');?></th>
                      <th style="width:20%"><?=$this->lang->line('application_ship_company');?></th>
                      <th style="width:10%"><?=$this->lang->line('application_service');?></th>
                      <th style="width:8%"><?=$this->lang->line('application_ship_date');?></th>
                        <?=(in_array('admDashboard', $user_permission)) ? '<th style="width:10%">'.$this->lang->line('application_value').'</th>' : ''?>
                      <th style="width:10%"><?=$this->lang->line('application_tracking code');?></th>
                      <th style="width:10%"><?=$this->lang->line('application_gather_date');?></th>
                       <!--
                      <th style="width:8%"><?=$this->lang->line('application_status');?></th>
                      <th style="width:18%"><?=$this->lang->line('application_label');?></th>
                       -->
                    </tr>
                  </thead>

                   <tbody>

                    <?php if(isset($order_data['freights'])): ?>
                      <?php $x = 1; ?>
                      <?php foreach ($order_data['freights'] as $key => $val): ?>
                        <?php //print_r($v); ?>
                       <tr id="row_<?php echo $x; ?>">
                          <td><span class="form-control"><?php echo $val['id'] ?></span></td>
                          <td><span class="form-control"><?php echo $val['ship_company'] ?></span></td> 
                          <td><span class="form-control"><?php echo $val['method'] ?></span></td>                   
                          <td><span class="form-control"><?php echo $val['date_delivered'] ?></span></td>
                           <?=(in_array('admDashboard', $user_permission)) ? '<td><span class="form-control">'.get_instance()->formatprice($val['ship_value']).'</span></td>' : ''?>
                          <td><span class="form-control"><?php echo $val['codigo_rastreio'] ?></span></td>
                          <td><span class="form-control"><?php echo $order_data['order']['data_coleta'] ?></span></td>
                         
                          <!--
                          <td>
                            <input type="text" name="status_ship[]" id="status_ship_<?php echo $x; ?>" class="form-control" disabled value="<?php echo $val['status_ship'] ?>" autocomplete="off">
                          </td>
                          <td>
                          	 <?php if (!(is_null($val['link_plp'])) && ($val['link_plp']!='') ): ?>
                          	 	<a href="<?php echo $val['link_plp'] ?>" target="_blank"  class="btn btn-primary active">
                          	 		<i class="glyphicon glyphicon-print" aria-hidden="true"></i>&nbsp PLP
                          	 		</a>               
                             <?php endif; ?>
                          	 <?php if (!(is_null($val['link_etiqueta_a4'])) && ($val['link_etiqueta_a4']!='') ): ?>
                          	 	<a href="<?php echo $val['link_etiqueta_a4'] ?>" target="_blank"  class="btn btn-primary active">
                          	 		<i class="glyphicon glyphicon-print" aria-hidden="true"></i>&nbsp A4
                          	 		</a>               
                            <?php endif; ?>
                            <?php if (!(is_null($val['link_etiqueta_termica']))  && ($val['link_etiqueta_termica']!='') ): ?>
                          	 	<a href="<?php echo $val['link_etiqueta_termica'] ?>" target="_blank" class="btn btn-primary active">
                          	 		<i class="glyphicon glyphicon-fire" aria-hidden="true"></i>&nbsp <?=$this->lang->line('application_thermal')?></a>
                            <?php endif; ?>
                          </td>
                          -->
                       </tr>
                       <?php $x++; ?>
                     <?php endforeach; ?>                     
                   <?php else: echo "<tr><td colspan=6>" . $this->lang->line('messages_no_informations') . "</td></tr>";  ?>
                   <?php endif; ?>
                   </tbody>
                </table>
                    
                    
                    <div class="list-row">
                      <?php if(isset($order_data['frete_ocorrencias'])): ?>
                      	<div class="row">
                     	<?php foreach ($order_data['frete_ocorrencias'] as $key => $val): ?>
                     		<?php 
                     		 $cores[0] = '#8fc9ff';
							 $cores[1] = '#6eb9ff';
							 $cores[15] = '#5cb0ff';
							 $cores[16] = '#40a2ff';
							 $cores[17] = '#309aff';
							 $cores[200] = '#2192fc';
							 $cores[201] = '#0083ff';
							 $cores[3] = '#0083ff';
							 $cor = 'red';
							 if (array_key_exists($val['codigo'], $cores)) {
							 	$cor = $cores[$val['codigo']];
							 }
							 $tooltip = date('d/m/Y H:i:s', strtotime($val['data_atualizacao']));
							 if (!is_null($val['mensagem'])) {
							 	$tooltip = $tooltip.": ".trim($val['mensagem']); 
							 }
                     		?>
                     		<div class="col-sm-2 " style="background-color:<?php echo $cor; ?>">
                     			<span data-toggle="tooltip" title= "<?php echo $tooltip ?>"><strong><?php echo $val['nome'] ?></strong>
                     				<span class="glyphicon glyphicon-ok push-right"></span>
                     			</span>
                     		</div>
                     	<?php endforeach; ?>
                     	</div>
                     <?php endif; ?>
                    </div>
                </div>
              
              </div>
              <!-- /.box-body -->

              <div class="box-footer">

                <input type="hidden" name="service_charge_rate" value="<?php echo $company_data['service_charge_value'] ?>" autocomplete="off">
                <input type="hidden" name="vat_charge_rate" value="<?php echo $company_data['vat_charge_value'] ?>" autocomplete="off">

                <a target="__blank" href="<?php echo base_url() . 'orders/printDiv/'.$order_data['order']['id'] ?>" class="btn btn-default" ><?=$this->lang->line('application_print');?></a>
                <?php if(in_array('updateOrder', $user_permission)): ?>
                <!--- <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button> --->
                <?php endif; ?>
                <a href="<?php echo base_url('orders/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
              </div>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->
      </div>
      <!-- col-md-12 -->
    

  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php if(in_array('updateOrder', $user_permission)): ?>
<div class="modal fade" tabindex="-1" role="dialog" id="nfeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php 
		    // var_dump($order_data['nfes']);
			if(isset($order_data['nfes'])) { 
				$nfe = $order_data['nfes'][0];
				$titulo = $this->lang->line('application_change_invoice');
			}
			
			else {
				$nfe = array(
				'id' => '', 
				'order_id' => $order_data['order']['id'],
				'nfe_num' => '',
				'nfe_serie' => '',
				'date_emission' => '',
				'chave' => '',
				'company_id' => $order_data['order']['company_id']
				);
				$titulo = $this->lang->line('application_insert_invoice');
			}	
			$nfe['valor_nfe'] = get_instance()->formatprice($order_data['order']['gross_amount']);
			$nfe['valor_itens'] = get_instance()->formatprice($order_data['order']['total_order']);
			
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php echo $titulo;?></span></h4>
		</div>
	    <form role="form" action="<?php echo base_url('orders/updatenfe') ?>" method="post" id="nfeForm">
	    <div class="modal-body">
		    
		    <div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_number');?></label></div>
				<div class="form-group col-md-3">
					<input type="text" maxlength="9" minlength="1" name="num_nfe" id="num_nfe" class="form-control" required onKeyPress="return digitos(event, this);" value="<?php echo $nfe['nfe_num']; ?>" autocomplete="off">
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_serie');?></label></div>
				<div class="form-group col-md-3">
					<input type="text" maxlength="3" minlength="1" name="serie_nfe" id="serie_nfe" class="form-control" required onKeyPress="return digitos(event, this);" value="<?php echo $nfe['nfe_serie']; ?>" autocomplete="off">                	
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_issuance_date');?></label></div>
				<div class="form-group col-md-4">
                    <input type="text" maxlength="19" minlength="19" name="date_emission_nfe" id="date_emission_nfe" class="form-control" required  onKeyPress="return digitos(event, this);" onKeyUp="Mascara('DATAHORA',this,event);" value="<?php echo $nfe['date_emission']; ?>" autocomplete="off">
					<span STYLE="color: blue; font-size: 10pt">&nbsp;&nbsp;dd/mm/aaaa hh:mm:ss</span>
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_access_key');?></label></div>
				<div class="form-group col-md-8">
                    <input type="text" maxlength="44" minlength="44" name="chave_nfe" id="chave_nfe" class="form-control" required  onKeyPress="return digitos(event, this);" value="<?php echo $nfe['chave']; ?>" autocomplete="off">
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_gather_date');?></label></div>
				<div class="form-group col-md-4">
                    <input type="text" maxlength="10" minlength="10" name="data_coleta_nfe" id="data_coleta_nfe" class="form-control" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('DATA',this,event);" value="<?php echo $order_data['order']['data_coleta']; ?>" autocomplete="off">
					<span STYLE="color: blue; font-size: 10pt">&nbsp;&nbsp;dd/mm/aaaa</span>
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_valuenfe');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="valor_nfe" id="valor_nfe" class="form-control" required readonly onKeyPress="return digitos(event, this);" onKeyUp="Mascara('MOEDA',this,event);" value="<?php echo $nfe['valor_nfe']; ?>" autocomplete="off">
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_valueitems');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="valor_itens" id="valor_itens" class="form-control" required readonly onKeyPress="return digitos(event, this);" onKeyUp="Mascara('MOEDA',this,event);" value="<?php echo $nfe['valor_itens']; ?>" autocomplete="off"></td>
				</div>
			</div>
			<input type="hidden" name="id_nfe" value="<?php echo $nfe['id'] ?>" autocomplete="off">
     		<input type="hidden" name="id_pedido" value="<?php echo $nfe['order_id'] ?>" autocomplete="off">
     		<input type="hidden" name="company_id" value="<?php echo $nfe['company_id'] ?>" autocomplete="off">

		</div> <!-- modal-body -->
	    <div class="modal-footer">
	      <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
	      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>
	      
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>


<script type="text/javascript">
  var base_url = "<?php echo base_url(); ?>";

  // function printOrder(id)
  // {
  //   if(id) {
  //     $.ajax({
  //       url: base_url + 'orders/printDiv/' + id,
  //       type: 'post',
  //       success:function(response) {
  //         var mywindow = window.open('', 'new div', 'height=400,width=600');
  //         // mywindow.document.write('<html><head><title></title>');
  //         // mywindow.document.write('<link rel="stylesheet" href="<?php //echo base_url('assets/bower_components/bootstrap/dist/css/bootstrap.min.css') ?>" type="text/css" />');
  //         // mywindow.document.write('</head><body >');
  //         mywindow.document.write(response);
  //         // mywindow.document.write('</body></html>');

  //         mywindow.print();
  //         mywindow.close();

  //         return true;
  //       }
  //     });
  //   }
  // }

  $(document).ready(function() {
    $(".select_group").select2();
    // $("#description").wysihtml5();

    $("#mainOrdersNav").addClass('active');
    $("#manageOrdersNav").addClass('active');
    
    
    // Add new row in the table 
    $("#add_row").unbind('click').bind('click', function() {
      var table = $("#product_info_table");
      var count_table_tbody_tr = $("#product_info_table tbody tr").length;
      var row_id = count_table_tbody_tr + 1;

      $.ajax({
          url: base_url + '/orders/getTableProductRow/',
          type: 'post',
          dataType: 'json',
          success:function(response) {
            

              // console.log(reponse.x);
               var html = '<tr id="row_'+row_id+'">'+
                   '<td>'+ 
                    '<select class="form-control select_group product" data-row-id="'+row_id+'" id="product_'+row_id+'" name="product[]" style="width:100%;" onchange="getProductData('+row_id+')">'+
                        '<option value=""></option>';
                        $.each(response, function(index, value) {
                          html += '<option value="'+value.id+'">'+value.name+'</option>';             
                        });
                        
                      html += '</select>'+
                    '</td>'+ 
                    '<td><input type="number" name="qty[]" id="qty_'+row_id+'" class="form-control" onkeyup="getTotal('+row_id+')"></td>'+
                    '<td><input type="text" name="rate[]" id="rate_'+row_id+'" class="form-control" disabled><input type="hidden" name="rate_value[]" id="rate_value_'+row_id+'" class="form-control"></td>'+
                    '<td><input type="text" name="amount[]" id="amount_'+row_id+'" class="form-control" disabled><input type="hidden" name="amount_value[]" id="amount_value_'+row_id+'" class="form-control"></td>'+
                    '<td><button type="button" class="btn btn-default" onclick="removeRow(\''+row_id+'\')"><i class="fa fa-close"></i></button></td>'+
                    '</tr>';

                if(count_table_tbody_tr >= 1) {
                $("#product_info_table tbody tr:last").after(html);  
              }
              else {
                $("#product_info_table tbody").html(html);
              }

              $(".product").select2();

          }
        });

      return false;
    });

  }); // /document

  function getTotal(row = null) {
    if(row) {
      var total = Number($("#rate_value_"+row).val()) * Number($("#qty_"+row).val());
      total = total.toFixed(2);
      $("#amount_"+row).val(total);
      $("#amount_value_"+row).val(total);
      
      subAmount();

    } else {
      alert('no row !! please refresh the page');
    }
  }

  // get the product information from the server
  function getProductData(row_id)
  {
    var product_id = $("#product_"+row_id).val();    
    if(product_id == "") {
      $("#rate_"+row_id).val("");
      $("#rate_value_"+row_id).val("");

      $("#qty_"+row_id).val("");           

      $("#amount_"+row_id).val("");
      $("#amount_value_"+row_id).val("");

    } else {
      $.ajax({
        url: base_url + 'orders/getProductValueById',
        type: 'post',
        data: {product_id : product_id},
        dataType: 'json',
        success:function(response) {
          // setting the rate value into the rate input field
          
          $("#rate_"+row_id).val(response.price);
          $("#rate_value_"+row_id).val(response.price);

          $("#qty_"+row_id).val(1);
          $("#qty_value_"+row_id).val(1);

          var total = Number(response.price) * 1;
          total = total.toFixed(2);
          $("#amount_"+row_id).val(total);
          $("#amount_value_"+row_id).val(total);
          
          subAmount();
        } // /success
      }); // /ajax function to fetch the product data 
    }
  }

  // calculate the total amount of the order
  function subAmount() {
    var service_charge = <?php echo ($company_data['service_charge_value'] > 0) ? $company_data['service_charge_value']:0; ?>;
    var vat_charge = <?php echo ($company_data['vat_charge_value'] > 0) ? $company_data['vat_charge_value']:0; ?>;

    var tableProductLength = $("#product_info_table tbody tr").length;
    var totalSubAmount = 0;
    for(x = 0; x < tableProductLength; x++) {
      var tr = $("#product_info_table tbody tr")[x];
      var count = $(tr).attr('id');
      count = count.substring(4);

      totalSubAmount = Number(totalSubAmount) + Number($("#amount_"+count).val());
    } // /for

    totalSubAmount = totalSubAmount.toFixed(2);

    // sub total
    $("#gross_amount").val(totalSubAmount);
    $("#gross_amount_value").val(totalSubAmount);

    // vat
    var vat = (Number($("#gross_amount").val())/100) * vat_charge;
    vat = vat.toFixed(2);
    $("#vat_charge").val(vat);
    $("#vat_charge_value").val(vat);

    // service
    var service = (Number($("#gross_amount").val())/100) * service_charge;
    service = service.toFixed(2);
    $("#service_charge").val(service);
    $("#service_charge_value").val(service);
    
    // total amount
    var totalAmount = (Number(totalSubAmount) + Number(vat) + Number(service));
    totalAmount = totalAmount.toFixed(2);
    // $("#net_amount").val(totalAmount);
    // $("#totalAmountValue").val(totalAmount);

    var discount = $("#discount").val();
    if(discount) {
      var grandTotal = Number(totalAmount) - Number(discount);
      grandTotal = grandTotal.toFixed(2);
      $("#net_amount").val(grandTotal);
      $("#net_amount_value").val(grandTotal);
    } else {
      $("#net_amount").val(totalAmount);
      $("#net_amount_value").val(totalAmount);
      
    } // /else discount 

    var paid_amount = Number($("#paid_amount").val());
    if(paid_amount) {
      var net_amount_value = Number($("#net_amount_value").val());
      var remaning = net_amount_value - paid_amount;
      $("#remaining").val(remaning.toFixed(2));
      $("#remaining_value").val(remaning.toFixed(2));
    }

  } // /sub total amount

  function paidAmount() {
    var grandTotal = $("#net_amount_value").val();

    if(grandTotal) {
      var dueAmount = Number($("#net_amount_value").val()) - Number($("#paid_amount").val());
      dueAmount = dueAmount.toFixed(2);
      $("#remaining").val(dueAmount);
      $("#remaining_value").val(dueAmount);
    } // /if
  } // /paid amoutn function

  function removeRow(tr_id)
  {
    $("#product_info_table tbody tr#row_"+tr_id).remove();
    subAmount();
  }
  
  function editNfe()
  { 

      // submit the edit from 
      $("#nfeForm").unbind('submit').bind('submit', function() {
        var form = $(this);

        // remove the text-danger
        $(".text-danger").remove();
        $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize(), // /converting the form data into array and sending it to server
          dataType: 'json',  
          success:function(response) {
            if(response.success === true) {
              $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
              '</div>');

			
              // hide the modal
              $("#nfeModal").modal('hide');
              // reset the form 
              $("#nfeForm.form-group").removeClass('has-error').removeClass('has-success');
              alert(response.messages);
              window.location.reload(false); 

            } else {
              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);
                 // alert(index+":"+value); 
                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  id.after(value);

                });
              } else {
              	$("#nfeModal").modal('hide');
                $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                  '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
                '</div>');
              }
            }
          }
        }); 

        return false;
      });
	}
	
	function toggleCancelReason(e) {
	  	e.preventDefault();
	  	$("#cancel_reason").toggle();
  	}
</script>