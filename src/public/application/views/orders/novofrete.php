<!--
SW Serviços de Informática 2019

Editar Pedidos

-->

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data); ?>

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
       
       <!--- Não tem mais cotação com a frete Rápido
        <?php 
        // mostro botão de para solicitar Cotação de Frete
        if(in_array('updateOrder', $user_permission)) : ?>
			<button type="button" class="btn btn-primary" id="btcotacaofrete"><i class="fa fa-money">&nbsp<?=$this->lang->line('application_request_freight_quote');?></i></button>
		 <?php endif; ?>
        ---> 
        
        <?php 
        // mostro botão para tentar novamento com os correios
        if(in_array('deleteOrder', $user_permission) && ($order_data['order']['paid_status']==101)) {   ?> 
       	    <button type="button" class="btn btn-primary" onclick="returnCorreios()" data-toggle="modal" data-target="#returnCorreiosModal"><i class="fa fa-mail-bulk">&nbsp<?=$this->lang->line('application_try_again_correios');?></i></button>
		 <?php } ?>

			 <button type="button" class="btn btn-primary" onclick="registerFreight()" data-toggle="modal" data-target="#registerFreightModal"><i class="fa fa-truck">&nbsp<?=$this->lang->line('application_register_external_freight');?></i></button>

        <?php 
        // mostro botão para cancelar pedido 
        if(in_array('deleteOrder', $user_permission)) : ?>
       	    <button type="button" class="btn btn-danger" onclick="cancelOrder()" data-toggle="modal" data-target="#cancelarOrdemModal"><i class="fa fa-window-close">&nbsp<?=$this->lang->line('application_cancel_order');?></i></button>
		 <?php endif; ?>
         
        <div class="box">
          <form role="form" action="<?php base_url('orders/novoFrete') ?>" method="post">
              <div class="box-body">
              	<input type="hidden" id="orderid" name="orderid" value="<?php echo $order_data['order']['id'] ?>" > 
              	
            <?php 
            if (validation_errors()) { 
	          foreach (explode("</p>",validation_errors()) as $erro) { 
		        $erro = trim($erro);
		        if ($erro!="") { ?>
				<div class="alert alert-error alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	                <?php echo $erro."</p>"; ?>
				</div>
		<?php	}
			  }
	       	} ?>
	       	
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
				<?php if(in_array('createOrder', $user_permission)) { $more = "readonly"; } else { $more = "readonly"; } ?>

                  <h3>Dados do Pedido</h3>
                  <div class="form-group col-md-2">
                    <label for="id"><?=$this->lang->line('application_order');?></label>
                    <div>
					  <span name="id" id="id" class="form-control"><?php echo $order_data['order']['id'] ?></span>              
                    </div>
                  </div>
				  
                  <div class="form-group col-md-4">
                    <label for="numero_marketplace"><?=$this->lang->line('application_order_marketplace_full');?></label>
                    <div>
                    	<span name="numero_marketplace" id="numero_marketplace" class="form-control"><?php echo $order_data['order']['numero_marketplace'] ?></span>                     
                    </div>
                  </div>

                  <div class="form-group col-md-2">
                    <label><?=$this->lang->line('application_date');?></label>
                    <div>
                    	<span class="form-control"><?php echo date('d/m/Y', strtotime($order_data['order']['date_time'])); ?></span>
                    </div>
                  </div>

                  <div class="form-group col-md-4">
                    <label><?=$this->lang->line('application_status');?></label>                  
                    <div>
                    	<span class="form-control" ><?php  echo $status_str;?> </span>
                    </div>
                  </div>
                  
                  <div class="form-group col-md-2">
                    <label for="origin"><?=$this->lang->line('application_origin');?></label>
                    <div>
                    	<span class="form-control"><?php echo $order_data['order']['origin'] ?></span> 
                    </div>
                  </div>

                  <div class="form-group col-md-6">
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
			      <div class="row"></div>
                  <div class="form-group col-md-3">
                    <label for="company_id"><?=$this->lang->line('application_company');?></label>
                    <div>
                    	<!----
                    	<span class="form-control"><?php echo $order_data['order']['company_id'] ?></span> 
                    	-->
                    	<a id="store_id" name="store_id" href="<?php echo base_url().'stores/update/'.$order_data['order']['company_id'];?>"  target="_blank"><span><?php echo $order_data['order']['loja']['name']  ?> &nbsp </span><i class="fa fa-eye"></i></a>
                   
                    </div>
                  </div>
                  
                  <div class="form-group col-md-3">
                    <label for="store_id"><?=$this->lang->line('application_store');?></label>
                    <div>
                    	<!----
                    	<span class="form-control"><?php echo $order_data['order']['store_id'] ?></span> 
                    	-->
                    	<a id="store_id" name="store_id" href="<?php echo base_url().'stores/update/'.$order_data['order']['store_id'];?>"  target="_blank"><span "><?php echo $order_data['order']['loja']['name']  ?> &nbsp </span><i class="fa fa-eye"></i></a>
                   
                    </div>
                  </div>

                <div class="col-md-12">
                <h3><?=$this->lang->line('application_items')?></h3>
                </div>
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
	                      <td><?=$x; ?></td>                

                          <td><a href="<?php echo base_url().'products/update/'.$val['product_id'];?>"  target="_blank"><span><?php echo $val['sku'] ?> &nbsp </span><i class="fa fa-eye"></i></a> </td>
                          <td><span class="form-control"><?php echo $val['name'] ?></span> </td>
                          <td><span class="form-control"><?php echo $val['qty'] ?></span> </td>
                          <td><span class="form-control"><?php echo get_instance()->formatprice($val['rate']) ?></span> </td>
                          <td><span class="form-control"><?php echo get_instance()->formatprice($val['discount']) ?></span> </td>
                          <td><span class="form-control"><?php echo get_instance()->formatprice($val['amount']) ?></span> </td>
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
              </div>
               <div class="col-md-12 col-xs-12 pull pull-left">

                <br /><h3><?=$this->lang->line('application_payments')?></h3>
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
                       	  <td><span class="form-control"><?php echo $val['id'] ?></span> </td>
                       	  <td><span class="form-control"><?php echo $val['order_id'] ?></span> </td>
                       	  <td><span class="form-control"><?php echo $val['parcela'] ?></span> </td>
                       	  <td><span class="form-control"><?php echo $val['data_vencto'] ?></span> </td>
                       	  <td><span class="form-control"><?php echo get_instance()->formatprice($val['valor']) ?></span> </td>
                       	  <td><span class="form-control"><?php echo $val['forma_desc'] ?></span> </td>
                       
                       </tr>
                       <?php $x++; ?>
                     <?php endforeach; ?>
                   <?php else: echo "<tr><td colspan='6'>" . $this->lang->line('messages_no_informations') . "</td></tr>";  ?>
                   <?php endif; ?>
                   </tbody>
                </table>
                </div>
                
                <div class="col-md-12 col-xs-12 pull pull-left">

                <br /><h3>NFe</h3>
                <div class="form-group col-md-3">
                    <label><?=$this->lang->line('application_crossdocking_limit_date');?></label>
                    <div>
                    	<span class="form-control"><?php $date = new DateTime($order_data['order']['data_limite_cross_docking']); echo date_format($date, 'd/m/Y'); ?></span> 
                    </div>
                 </div>
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
                       <tr id="row_<?php echo $x; ?>">
                       	<td><span class="form-control"><?php echo $val['id'] ?></span> </td>
                       	<td><span class="form-control"><?php echo $val['date_emission'] ?></span> </td>
                       	<td><span class="form-control"><?php echo $val['nfe_serie'] ?></span> </td>
                       	<td><span class="form-control"><?php echo $val['nfe_num'] ?></span> </td>
                       	<td><span class="form-control"><?php echo $val['chave'] ?></span> </td>
                       </tr>
                       <?php $x++; ?>
                     <?php endforeach; ?>
                   <?php else: echo "<tr><td colspan='6'>" . $this->lang->line('messages_no_informations') . "</td></tr>";  ?>
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
                  <div class="form-group col-md-4">
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
               </div>  
               
               <div class="col-md-12 col-xs-12 pull pull-left">
                <h3><?=$this->lang->line('application_freight')?></h3>
                <table class="table table-bordered" id="product_info_table">
                  <thead>
                    <tr>
                      <th style="width:5%"><?=$this->lang->line('application_item');?></th>
                      <th style="width:30%"><?=$this->lang->line('application_ship_company');?></th>
                      <th style="width:8%"><?=$this->lang->line('application_ship_date');?></th>
                      <th style="width:10%"><?=$this->lang->line('application_value');?></th>
                      <th style="width:10%"><?=$this->lang->line('application_tracking code');?></th>
                      <th style="width:10%"><?=$this->lang->line('application_gather_date');?></th>

                    </tr>
                  </thead>

                   <tbody>

                    <?php if(isset($order_data['freights'])): ?>
                      <?php $x = 1; ?>
                      <?php foreach ($order_data['freights'] as $key => $val): ?>
                        <?php //print_r($v); ?>
                       <tr id="row_<?php echo $x; ?>">
                       	
                       	<td><span class="form-control"><?php echo $val['id'] ?></span> </td>
                       	<td><span class="form-control"><?php echo $val['ship_company'] ?></span> </td>
                       	<td><span class="form-control"><?php echo $val['date_delivered'] ?></span> </td>
                       	<td><span class="form-control"><?php echo get_instance()->formatprice($val['ship_value']) ?></span> </td>
                       	<td><span class="form-control"><?php echo $val['codigo_rastreio'] ?></span> </td>
                       	<td><span class="form-control"><?php echo $order_data['order']['data_coleta'] ?></span> </td>
                       	
                       </tr>
                       <?php $x++; ?>
                     <?php endforeach; ?>                     
                   <?php else: echo "<tr><td colspan='6'>" . $this->lang->line('messages_no_informations') . "</td></tr>";  ?>
                   <?php endif; ?>
                   </tbody>
                </table>
                    
                    
               </div>
               
              </div>
              <!-- /.box-body -->

              <div class="box-footer">

                <input type="hidden" name="service_charge_rate" value="<?php echo $company_data['service_charge_value'] ?>" autocomplete="off">
                <input type="hidden" name="vat_charge_rate" value="<?php echo $company_data['vat_charge_value'] ?>" autocomplete="off">


                <a href="<?php echo base_url('orders/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
              </div>
            </form>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->
      </div>
      <!-- col-md-12 -->
    </div>
    <!-- /.row -->
    

  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php if(in_array('updateOrder', $user_permission)): ?>
<div class="modal fade" tabindex="-1" role="dialog" id="novoFreteModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php 
		    // var_dump($order_data['nfes']);
			$titulo = $this->lang->line('application_novofrete');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php echo $this->lang->line('application_novofrete');?></span></h4>
		</div>
	    <form role="form" action="<?php echo base_url('orders/mudastatusfrete') ?>" method="post" id="nfeForm">
	    <div class="modal-body">
		    
		    <div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_freight_quote_delivery_price');?></label></div>
				<div class="form-group col-md-3">
					<input type="text" readonly  name="novoprecofrete" id="novoprecofrete" class="form-control" value="" >
				</div>
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_freight_old_delivery_price');?></label></div>
				<div class="form-group col-md-3">
					<input type="text" readonly  name="precoantigofrete" id="precoantigofrete" class="form-control" value="<?php echo get_instance()->formatprice($order_data['order']['total_ship']) ?>" >
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_freight_quote_delivery_company');?></label></div>
				<div class="form-group col-md-6">
					<input type="text" readonly  name="novotransportadora" id="novotransportadora" class="form-control" value="" >
				</div>
			</div>
			
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_freight_quote_delivery_days');?></label></div>
				<div class="form-group col-md-2">
					<input type="text" readonly  name="novoprazoentrega" id="novoprazoentrega" class="form-control" value="" >
				</div>
			</div>
			
			<input type="hidden" id="newpriceFR" name="newpriceFR" value="" autocomplete="off">
			<input type="hidden" id="oldpriceFR" name="oldpriceFR" value="<?php echo $order_data['order']['total_ship'] ?>" autocomplete="off">
     		<input type="hidden" id="id_pedido" name="id_pedido" value="<?php echo $order_data['order']['id'] ?>" autocomplete="off">

		</div> <!-- modal-body -->
	    <div class="modal-footer">
	   		<button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
	      	<button type="submit" class="btn btn-primary" id="confirmafrete" name="confirmafrete"><?=$this->lang->line('application_confirm');?></button>
	    
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<?php if(in_array('deleteOrder', $user_permission)): ?>

<div class="modal fade" tabindex="-1" role="dialog" id="cancelarOrdemModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php 
			$titulo = $this->lang->line('application_cancel_order');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:red;"><?php echo $this->lang->line('application_cancel_order');?></span></h4>
			<br><?php echo $this->lang->line('application_cancel_warning');?>
		</div>
	    <form role="form" action="<?php echo base_url('orders/cancelarPedido') ?>" method="post" id="cancelarPedidoForm">
	    <div class="modal-body">
		    
		    <div class="row">
				<div class="form-group col-md-2"><label><?=$this->lang->line('application_cancel_reason');?></label></div>
				<div class="form-group col-md-9">
					<input type="text" required name="motivo_cancelamento" id="motivo_cancelamento" class="form-control" value="" autocomplete="off" list="datalist_reasons">
					<datalist id="datalist_reasons">
					<?php foreach ($cancel_reasons as $option) {?>
						
		               <option value="<?=$option['value']?>"</option>
		             <?php } ?>
					</datalist>
				</div>
			</div>
			
			<div class="row">
				<div class="form-group col-md-2"><label><?=$this->lang->line('application_penalty_to');?></label></div>
				<div class="form-group col-md-9">
                  <select class="form-control" id="penalty_to" name="penalty_to">
                  	<?php foreach ($cancel_penalty_to as $option) { ?>
                    <option value="<?=$option['value'];?>" ><?=$option['value'];?></option>
                 	<?php } ?>
                  </select>
                </div>
			</div>
			
			<input type="hidden" id="id_cancelamento" name="id_cancelamento" value="<?php echo $order_data['order']['id'] ?>" autocomplete="off">

		</div> <!-- modal-body -->
	    <div class="modal-footer">
	   		<button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
	      	<button type="submit" class="btn btn-primary" id="confirmacancelamento" name="confirmacancelamento"><?=$this->lang->line('application_confirm');?></button>
	    
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<div class="modal fade" tabindex="-1" role="dialog" id="registerFreightModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php 
			$titulo = $this->lang->line('application_register_external_freight');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:red;"><?php echo $this->lang->line('application_register_external_freight');?></span></h4>
		</div>
	    <form role="form" action="<?php echo base_url('orders/registerFreight') ?>" method="post" id="registerFreightForm">
	    <div class="modal-body">

			<div class="row">
				<div class="form-group col-md-12 d-flex">
				<label class="col-md-4"><?=$this->lang->line('application_ship_company');?>(*)</label>
					<select required class="col-md-8 form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="ship_company" name="ship_company" title="<?=$this->lang->line('application_select');?>" >
                    <?php foreach ($ship_companies as $k => $v): ?>
                      <option value="<?php echo $v['id'] ?>" <?php echo set_select('ship_company', $v['id']); ?> ><?php echo $v['name'] ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
			</div>
			
			<div class="row">
				<div class="form-group col-md-12 d-flex">
				<label class="col-md-4"><?=$this->lang->line('application_price');?>(*)</label>
					<input type="text" required name="price_ship" id="price_ship" class="col-md-8 form-control maskdecimal2" autocomplete="off"  value="<?php echo set_value('price_ship'); ?>" >
				</div>
			</div>
			
			<div class="row">
				<div class="form-group col-md-12 d-flex">
				<label class="col-md-4"><?=$this->lang->line('application_tracking code');?>(*)</label>
					<input type="text" required name="tracking_code" id="tracking_code" class="col-md-8 form-control" value="" autocomplete="off"  >
				</div>
			</div>
			
			<div class="row">
      <div class="form-group col-md-12 d-flex">
				<label class="col-md-4"><?=$this->lang->line('application_service');?>(*)</label>
					<input type="text" required name="method" id="method" class="col-md-8 form-control" value="" autocomplete="off"  >
				</div>
			</div>
			
			<div class="row">
        <div class="form-group col-md-12 d-flex">
          <label class="col-md-4"><?=$this->lang->line('application_expected_date');?>(*)</label>
          <div class='input-group date col-md-8 no-padding' id='expected_date_pick' name="expected_date_pick">
            <input type='text' required class="col-md-12 form-control" id='expected_date' name="expected_date" autocomplete="off" value="<?php echo set_value('expected_date', $data_prometida);?>" />
            <span class="input-group-addon">
                <span class="glyphicon glyphicon-calendar"></span>
            </span>
          </div>
        </div>
      </div>

			<div class="row">
      <div class="form-group col-md-12 d-flex">
				<label class="col-md-4"><?=$this->lang->line('application_url_tracking');?></label>
					<input type="text" name="url_tracking" id="url_tracking" class="col-md-8 form-control" value="" autocomplete="off"  >
				</div>
			</div>

			<input type="hidden" id="id_register_freight" name="id_register_freight" value="<?php echo $order_data['order']['id'] ?>" autocomplete="off">

		</div> <!-- modal-body -->
	    <div class="modal-footer">
	   		<button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
	      	<button type="submit" class="btn btn-primary" id="confirmfreight" name="confirmfreight"><?=$this->lang->line('application_confirm');?></button>
	    
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<?php if(in_array('deleteOrder', $user_permission) && ($order_data['order']['paid_status']==101)) : ?>

<div class="modal fade" tabindex="-1" role="dialog" id="returnCorreiosModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php 
			$titulo = $this->lang->line('application_try_again_correios');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:red;"><?php echo $this->lang->line('application_try_again_correios');?></span></h4>
		</div>
	    <form role="form" action="<?php echo base_url('orders/returnCorreios') ?>" method="post" id="returnCorreiosForm">
	    <div class="modal-body">
		    
			
			<input type="hidden" id="id_returnCorreios" name="id_returnCorreios" value="<?php echo $order_data['order']['id'] ?>" autocomplete="off">

		</div> <!-- modal-body -->
	    <div class="modal-footer">
	   		<button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
	      	<button type="submit" class="btn btn-primary" id="confirmaReturn" name="confirmaReturn"><?=$this->lang->line('application_confirm');?></button>
	    
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>


<script type="text/javascript">
  var base_url = "<?php echo base_url(); ?>";

	var formatter = new Intl.NumberFormat('pt-BR', {
	  style: 'currency',
	  currency: 'BRL',
	});

  $(document).ready(function() {
    $(".select_group").select2();
    // $("#description").wysihtml5();

    $("#mainProcessesNav").addClass('active');
    $("#semFreteNav").addClass('active');
    
    $('.maskdecimal2').inputmask({
	  alias: 'numeric', 
	  allowMinus: false,  
	  digits: 2, 
	  max: 999999999.99
	});
    
    $('#expected_date_pick').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR", 
		startDate: new Date(),
		todayBtn: true, 
		todayHighlight: true
	});
    
    $('#btcotacaofrete').on('click',function (){   
    	var orderid = $("#orderid").val();     
	    $.ajax({
	   		type:'GET',
	   		dataType: "json",
	   		url :base_url + 'orders/consultafrete',
	   		data : "id="+orderid,
	   		success: function(data) {
	   			if (data.erro) {
	   				// alert (data.erro); 
	   				location.reload();
	   			} else {
	   				$("#newpriceFR").val(data.preco_frete);
		   			$("#novoprecofrete").val(formatter.format(data.preco_frete));
		   			$("#novotransportadora").val(data.transportadora);
		   			$("#novoprazoentrega").val(data.prazo_entrega);
					$("#novoFreteModal").modal('show');
	   			}
				
	   		}
		}); 
		//e.preventDefault();
	});
    

  }); // /document
  
  function cancelOrder()
  { 
      // submit the edit from 
      $("#cancelarPedidoForm").unbind('submit').bind('submit', function() {
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
              $("#cancelarOrdemModal").modal('hide');
              // reset the form 
              $("#cancelarPedidoForm.form-group").removeClass('has-error').removeClass('has-success');
              //alert(response.messages);
              window.location = base_url + 'orders/semfrete'
              // window.location.reload(false); 

            } else {
              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);

                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  id.after(value);

                });
              } else {
              	$("#cancelarOrdemModal").modal('hide');
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

function registerFreight()
  { 
      // submit the edit from 
      $("#registerFreightForm").unbind('submit').bind('submit', function() {
        var form = $(this);
        form.find('button[type="submit"]').attr('disabled', true);
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
              $("#registerFreightModal").modal('hide');
              // reset the form 
              $("#registerFreightForm.form-group").removeClass('has-error').removeClass('has-success');
              //alert(response.messages);
              window.location = base_url + 'orders/semfrete'
              // window.location.reload(false); 

            } else {
              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);

                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  id.after(value);

                });
              } else {
              	$("#registerFreightModal").modal('hide');
                $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                  '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
                '</div>');
              }
            }
          },complete: e => {
            form.find('button[type="submit"]').attr('disabled', false);
          }
        }); 

        return false;
      });
	}
  
  
   function returnCorreios()
  { 
      // submit the edit from 
      $("#returnCorreiosForm").unbind('submit').bind('submit', function() {
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
              $("#returnCorreiosModal").modal('hide');
              // reset the form 
              $("#returnCorreiosForm.form-group").removeClass('has-error').removeClass('has-success');
              //alert(response.messages);
              // window.location = base_url + 'orders/semfrete'
              window.location.reload(false); 

            } else {
              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);

                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  id.after(value);

                });
              } else {
              	$("#returnCorreiosModal").modal('hide');
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
</script>