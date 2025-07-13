<!--

Criar produto de um produto de catálogo

--> 
<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

<!-- necssário para o carousel ----> 

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_edit";  
	$data['page_now'] ='catalog_based_product'; 
	$this->load->view('templates/content_header',$data); ?>


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
        
        <?php if (($product_catalog['status'] != 1)) { // se o product_catolog ficou inativo, o produto baseado nele não pode ficar ativo 
		?>
			<div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->lang->line('application_product_catalog_inactive'); ?>&nbsp;
            <?php echo $this->lang->line('application_reason').': '.$product_catalog['reason']; ?>
          </div>
	    <?php } ?>
        
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

        <div class="box">
        	<?php  $readonly = ' readonly '; 
        		 $optionRO = ' disabled '; ?>
              <form action="<?php base_url('catalogProducts/updateFromCatalog/'.$product_catalog['id']) ?>" method="post" enctype="multipart/form-data" id="formUpdateProduct">
        	
              <div class="box-body" >
              	<?php
					$numft = 0;
					if (!$product_catalog['is_on_bucket']) {
						$fotos = scandir(FCPATH . 'assets/images/catalog_product_image/' . $product_catalog['image']);
						foreach ($fotos as $foto) {
							if (($foto != ".") && ($foto != "..") && ($foto != "")) {
								$numft++;
								$ln1[$numft] = base_url('assets/images/catalog_product_image/' . $product_catalog['image'] . '/' . $foto);
								$ln2[$numft] = '{width: "120px", key: "' . $product_catalog['image'] . '/' . $foto . '"}';
							}
						}
					} else {
						// Prefixo de url para buscar a imagem.
						$asset_prefix = "assets/images/catalog_product_image/" . $product_catalog['image'] . "/";
						// Busca as imagens do produto já formatadas.
						$product_images = $this->bucket->getFinalObject($asset_prefix);
						// Caso tenha dado certo, busca o conteudo.
						if ($product_images['success']) {
							// Percorre cada elemento e verifica se não é imagem de variação.
							foreach ($product_images['contents'] as $key => $image_data) {
								// Monta a chave da imagem completa.
								$full_key = $product_catalog['image'] . '/' . $image_data['key'];
								$numft++;
								$ln1[$numft] = $image_data['url'];
								$ln2[$numft] = '{width: "120px", key: "' . $full_key . '"}';
							}
						}
					}

					?>	
				<?php if ($numft > 6) { $numft=6; } ?>
               
                <div class="form-group col-md-12 col-xs-12">
	               	<?php for ($i=1; $i<=$numft; $i++) {
						$size = getimagesize($ln1[$i]);
						$ratio = $size[0]/$size[1]; // width/height
					    $width = 200*$ratio;
					    $height = 200;
						if ($width> 200) {$width=200;}
						 ?> 
						<a href="<?= $ln1[$i];?>"><img width="<?=$width;?>px" height="<?=$height;?>px" src="<?= $ln1[$i];?>" ></a>
					<?php } ?>
				</div>
				
				<div class="form-group col-md-12 col-xs-12">
					
					<h2><strong class="nameProduct"><?php echo $product_catalog['name']; ?></strong></h2>
					<p><strong><?=$label_ean;?> : <?php echo $product_catalog['EAN']; ?></strong></p>
					
					<?php if (isset($prd_original)){ ?>
						<div class="alert alert-warning alert-dismissible">						
						<p><strong><?=$this->lang->line('application_multi_channel_fulfillment_store');?></strong></p>
					    </div>
					<?php  } ?>
				</div>

            <div class="row">
				<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('sku')) ? "has-error" : ""; ?> ">
                  	<label for="sku"><?=$this->lang->line('application_sku');?>(*)</label>
                  	<input type="text" class="form-control" id="sku" name="sku" required placeholder="<?=$this->lang->line('application_enter_sku');?>" value="<?php echo set_value('sku',$product_data['sku']); ?>" autocomplete="off" onKeyUp="checkSpecialSku(event, this);" onblur="checkSpecialSku(event, this);" />
               		<?php echo '<i style="color:red">'.form_error('sku').'</i>'; ?>
                </div>
                
                <div class="form-group col-md-3 col-xs-12">
                  <label for="store"><?=$this->lang->line('application_store');?>(*)</label>
					<?php  
					$nome_loja = '';
					foreach($stores as $store) {
						if ($store['id'] == $product_data['store_id']) {
							$nome_loja = $store['name'];
							break;
						}
					}
					?>	
	            	<input type="hidden" class="form-control" id="store" name="store" value="<?php echo $product_data['store_id']; ?>"  />
                	<span class="form-control"><?php echo $nome_loja ?></span>
                </div>
                
                
                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('prazo_operacional_extra')) ? "has-error" : ""; ?> ">
                  	<label for="prazo_operacional_extra"><?=$this->lang->line('application_extra_operating_time');?></label>
                  	<input type="text" class="form-control" maxlength="2" id="prazo_operacional_extra" name="prazo_operacional_extra" placeholder="<?=$this->lang->line('application_extra_operating_time');?>" value="<?php echo set_value('prazo_operacional_extra', $product_data['prazo_operacional_extra']) ?>" autocomplete="off"  onKeyPress="return digitos(event, this);" <?php echo ($changeCrossdocking ? 'readonly' : '');?> />
               		 <?php echo '<i style="color:red">'.form_error('prazo_operacional_extra').'</i>'; ?>
                </div>
                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('qty')) ? "has-error" : ""; ?> ">
                    <label for="qty"><?=$this->lang->line('application_qty');?>(*)</label>
                    <input type="text" class="form-control" id="qty" name="qty" <?php echo ($product_data['has_variants'] != '') ? 'readonly' :'required';?> placeholder="<?=$this->lang->line('application_enter_qty');?>" value="<?php echo set_value('qty',$product_data['qty']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
                    <div class="input-group" id="qty_marketplace" >
                        <?php
                        $temHub = false;
                        foreach ($integrations as $integration) {
                            if ($integration['int_type'] == 'DIRECT') {
                                $temHub = true;
                                break;
                            }
                        }
                        if ($temHub) {
                            ?>
                            <table class="table table-striped table-hover responsive display table-condensed" >
                                <thead>
                                <tr>
                                    <th width="40%" ><?=$this->lang->line('application_marketplace');?></th>
                                    <th><?=$this->lang->line('application_price_marketplace');?></th>
                                </tr>

                                <?php
                                foreach($integrations as $integration) {

                                    ?>
                                    <tr>
                                        <td width="40%"><?php echo $integration['int_to']; ?>
                                            <br>
                                            <input type="checkbox" class="sameqtychk" name="sameQty_<?=$integration['id']?>" <?= set_checkbox('sameQty_'.$integration['id'], 'on' , true ) ?> id="sameQty_<?=$integration['id']?>"  onchange="sameQty(<?=$integration['id']?>)">
                                            <small><?=$this->lang->line('application_same_qty');?></small>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control sameqtyval" id="qty_<?=$integration['id']?>" onKeyPress="return digitos(event, this)" onchange="changeQtyMkt(<?=$integration['id']?>)"  name="qty_<?=$integration['id']?>" required placeholder="<?=$this->lang->line('application_enter_qty');?>" value="<?php echo set_value('qty_'.$integration['id']); ?>" autocomplete="off"  />
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                </thead>
                            </table>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
                 
            <div class="row">
                <small>
                    <i>
                        <div class="form-group col-md-3 col-xs-12">
                            <label><?=$this->lang->line('application_created_on');?></label>
                            <span ><?php echo date("d/m/Y H:i:s",strtotime($product_data['date_create'])); ?></span>
                        </div>
                        <div class="form-group col-md-3 col-xs-12">
                            <label><?=$this->lang->line('application_updated_on');?></label>
                            <span ><?php echo date("d/m/Y H:i:s",strtotime($product_data['date_update'])); ?></span>
                        </div>
                    </i>
                </small>
            </div>
            <div class="row">
					<!-- Variants DIV -->
		  		<?php 
		  		if ($product_data['has_variants'] != '') {
		  			
		  			$variations = explode(';',$product_catalog['has_variants']);
		  			?>
				<div  class="col-md-12 col-xs-12" >
				    <h4 class="mb-3"><?=$this->lang->line('application_variations');?></h4>  
					<div class="row">
						 <div  class="form-group col-md-1">
	                        <label></label>
	                    </div>
						<div id="Lnvar" class="form-group col-md-1">
					    	<label><?=$this->lang->line('application_number');?></label>
						</div>
						<?php if (in_array('TAMANHO', $variations)) { ?> 
						<div id="Ltvar" class="form-group col-md-2">
					    	<label><?=$this->lang->line('application_size');?></label>
						</div>
						<?php }
						if (in_array('Cor', $variations)) { ?>
						<div id="Lcvar" class="form-group col-md-2">
					    	<label><?=$this->lang->line('application_color');?></label>
						</div>
						<?php }
						if (in_array('VOLTAGEM', $variations)) { ?>
						<div id="Lvvar" class="form-group col-md-2">
					    	<label><?=$this->lang->line('application_voltage');?></label>
						</div>
						<?php } ?>
						<div id="Leanvar" class="form-group col-md-2">
	                        <label><?=$label_ean;?></label>
	                    </div>
	                    <div  class="form-group col-md-1">
	                        <label><?=$this->lang->line('application_qty');?></label>
	                    </div>
	                    <div  class="form-group col-md-2">
	                        <label><?=$this->lang->line('application_sku');?></label>
	                    </div>
					</div>
						
					<div>
						<?php 
						for($i=0; $i<count($product_cat_variants); $i++) {
       						$variant_prod  = array_combine( explode(';', $product_catalog['has_variants']),  explode(';', $product_cat_variants[$i]['name']));
						?>
						<div class="row" >
							<div  class="form-group col-md-1">
		                    	<?php $imagem = ($product_cat_variants[$i]['principal_image'] == '') ? base_url('assets/images/system/sem_foto.png') :  $product_cat_variants[$i]['principal_image']; ?>
	                    		<a href="<?= $imagem;?>"><img src="<?= $imagem;?>" class="img-rounded" width="50" height="50" /></a>
		                    </div>
							<div id="Invar<?php echo $i;?>" class="form-group col-md-1">
							   <span class="form-control label label-success" ><?php echo $i;?></span>
							</div>
							<?php if (in_array('TAMANHO', $variations)) { ?> 
							<div id="Itvar" class="form-group col-md-2">
								<span><?php echo (key_exists('TAMANHO',$variant_prod)) ? $variant_prod['TAMANHO'] : "" ?></span>
							</div>
							<?php }
							if (in_array('Cor', $variations)) { ?>
							<div id="Icvar" class="form-group col-md-2">
								<span ><?php echo (key_exists('Cor',$variant_prod)) ? $variant_prod['Cor'] : "" ?></span>
							</div>
							<?php }
							if (in_array('VOLTAGEM', $variations)) { ?>
							<div id="Ivvar" class="form-group col-md-2">
								<span ><?php echo (key_exists('VOLTAGEM',$variant_prod)) ? $variant_prod['VOLTAGEM'] : "" ?></span>
							</div>
							<?php } ?>
							<div  class="form-group col-md-2">
								<span ><?php echo $product_variants[$i]['EAN'] ?></span>
		                    </div>
		                    <div class="Iqvar form-group col-md-1 <?php echo (form_error('Q['.$i.']')) ? "has-error" : ""; ?>">
                       		 	<input type="text" class="form-control" id="Q[]" name="Q[]" autocomplete="off"  placeholder="Estoque" onKeyPress="return digitos(event, this);" value="<?php echo set_value('Q['.$i.']', (isset($product_variants[$i]['qty'])) ? $product_variants[$i]['qty'] : "") ?>" />
                    			<?php echo '<i style="color:red">'.form_error('Q['.$i.']').'</i>'; ?>
                    		</div>
                     		<div class="Iskuvar form-group col-md-2 <?php echo (form_error('SKU_V['.$i.']')) ? "has-error" : ""; ?>">
                        		<input type="text" class="form-control" id="SKU_V[]" name="SKU_V[]" autocomplete="off" placeholder="SKU Variação"  value="<?php echo set_value('SKU_V['.$i.']', (isset($product_variants[$i]['sku'])) ? $product_variants[$i]['sku'] : "") ?>" />
                				<?php echo '<i style="color:red">'.form_error('SKU_V['.$i.']').'</i>'; ?>
                			</div>			                    
						</div>
						<?php } ?>
					</div>
				</div>
				<?php } ?>

            </div>

                  <?php
                  $readonly = '';
                  $sufix_field_name = '';
                  if (($product_catalog['status'] != 1) && ($product_data['status'] == 1)) { // se o product_catolog ficou inativo, o produto baseado nele não pode ficar ativo
                      $product_data['status'] =2;
                  } ?>
                  <?php
                  foreach ($catalogs_associated as $key => $catalog_associated):
                      $product_associated = getArrayByValueIn($products_catalogs_associated, $catalog_associated['id'], 'catalog_id');
                  ?>
                      <hr class="mb-0">
                      <div class="row content-catalog-product">
                          <div class="col-md-12 d-flex justify-content-start align-items-end">
                              <h3 class="font-weight-bold"><?=$catalog_associated['name']?><?=$key == 0 ? '<small class="ml-5 text-primary">(Base do produto)</small></h3>' : ''?>
                              <?php if ($key > 0): ?>
                                  <?php $readonly = empty($product_associated) ? 'disabled' : ''; $sufix_field_name = '_associated[]' ?>
                                  <h4 class="ml-5 font-weight-bold" style="display:<?=!empty($product_associated) ? 'none' : 'block'?>"><input type="checkbox" class="enable_catalog" name="enable_catalog<?=$sufix_field_name?>" <?=!empty($product_associated) ? 'checked' : ''?>> Deseja vende nesse catálogo?</h4>
                              <?php endif ?>
                          </div>
                          <input type="hidden" name="catalog_id<?=$sufix_field_name?>" value="<?=$catalog_associated['id']?>" <?=$readonly?>>
                          <?php  $reqprice = "required";
                          if ($priceRO) {$reqprice = "readonly";} ?>
                          <?php if (!isset($promotion) && count($campaigns)==0) { ?>
                              <div class="form-group col-md-2 col-xs-12">
                                  <label for="price_<?=$key?>" class="d-flex justify-content-between">
                                      <?=$this->lang->line('application_price');?>(*)
                                      <?php if ((($product_associated['status'] ?? 0) == 1) && ($product_data['situacao']==2) &&(!$priceRO)) { ?>
                                          <a href="<?php echo base_url('promotions/createOne/'.$product_data['id']) ?>" ><i class="fa fa-plus-circle"> <?=$this->lang->line('application_add_promotion') ?></i></a>
                                      <?php } ?>
                                  </label>
                                  <div class="input-group">
                                      <span class="input-group-addon"><strong>R$</strong></span>
                                      <input type="text" class="form-control maskdecimal2" id="price_<?=$key?>" name="price<?=$sufix_field_name?>"  <?=$reqprice?> placeholder="<?=$this->lang->line('application_enter_price');?>" value="<?php echo set_value('price',$products_catalogs_associated_catalog[$catalog_associated['id']]['price'] ?? ''); ?>" autocomplete="off"  />
                                  </div>
                                  <?php if (!empty($products_catalogs_associated_catalog[$catalog_associated['id']]['competitiveness'])) { ?>
                                      <span class="label label-danger" data-toggle="tooltip" title="<?=$this->lang->line('application_reduce_product_value')?>">Produto <?= number_format($products_catalogs_associated_catalog[$catalog_associated['id']]['competitiveness'], 2, ',', '.') ?>% acima do valor de mercado</span>
                                  <?php } ?>

                              </div>
                          <?php } else {
                              if (isset($promotion)) {
                                  $buttons = '';
                                  if ((in_array('updatePromotions', $this->permission)) && (($promotion['active'] == 3) || ($promotion['active'] == 4))) { // posso editar se está agendada ou em aprovação
                                      $buttons.= '<a href="'.base_url('promotions/update/'.$promotion['id']).'" class="btn btn-default" data-toggle="tooltip" title="'.$this->lang->line('application_edit').'"><i class="fa fa-pencil-square-o"></i></a>';
                                  }
                                  if ((in_array('deletePromotions', $this->permission))  && ($promotion['active'] == 1)) { // Posso inativar se estiver ativo
                                      $buttons.= '<button class="btn btn-danger" onclick="inactivePromotion(event,'.$promotion['id'].')" data-toggle="tooltip" title="'.$this->lang->line('application_inactivate').'"><i class="fa fa-minus-square"></i></button>';
                                  }
                                  if ((in_array('updatePromotions', $this->permission)) && ($promotion['active'] == 3)) {  // Posso aprovar se está em aprovação
                                      $buttons.= '<button class="btn btn-success" onclick="approvePromotion(event,'.$promotion['id'].')" data-toggle="tooltip" title="'.$this->lang->line('application_approve').'"><i class="fa fa-check"></i></button>';
                                  }
                                  if ((in_array('deletePromotions', $this->permission)) && (($promotion['active'] == 3) || ($promotion['active'] == 4)))  { // posso deletar se está em aprovação ou agendado
                                      $buttons.= '<button class="btn btn-warning" onclick="deletePromotion(event,'.$promotion['id'].')" data-toggle="tooltip" title="'.$this->lang->line('application_delete').'"><i class="fa fa-trash"></i></button>';
                                  }

                                  if ($promotion['type'] == 2) {
                                      $msg = $this->lang->line('application_msg_product_promotion_1');
                                      $msg = sprintf($msg,get_instance()->formatprice($product_data['price']),get_instance()->formatprice($promotion['price']),date('d/m/Y',strtotime($promotion['start_date'])),date('d/m/Y',strtotime($promotion['end_date'])));
                                  }
                                  else {
                                      $msg = $this->lang->line('application_msg_product_promotion_2');
                                      $msg = sprintf($msg,get_instance()->formatprice($product_data['price']),get_instance()->formatprice($promotion['price']),date('d/m/Y',strtotime($promotion['start_date'])),date('d/m/Y',strtotime($promotion['end_date'])),$promotion['qty'],$promotion['qty_used']);
                                  }
                                  ?>
                                  <div style="background-color: green; color:white; " class="form-group col-md-12 col-xs-12">
                                      <h5><span style="word-break: break-word; ">&nbsp;<?php echo $msg; ?></span>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $buttons; ?></h5>
                                  </div>
                                  <input type="hidden" id="price" name="price" value="<?php echo $product_data['price']; ?>" />

                              <?php }
                              elseif (count($campaigns)>0) {
                                  foreach ($campaigns as $campaign) {
                                      $msg = $this->lang->line('application_msg_product_campaign');
                                      $msg = sprintf($msg,$campaign['name'],$campaign['marketplace'],date('d/m/Y',strtotime($campaign['start_date'])),date('d/m/Y',strtotime($campaign['end_date'])),get_instance()->formatprice($product_data['price']),get_instance()->formatprice($campaign['sale']));
                                      ?>
                                      <div style="background-color: green; color:white; " class="form-group col-md-12 col-xs-12">
                                          <h5><span style="word-break: break-word; ">&nbsp;<?php echo $msg; ?></h5>
                                      </div>
                                  <?php } ?>
                                  <input type="hidden" id="price" name="price" value="<?php echo $product_data['price']; ?>" />
                              <?php } ?>

                          <?php }
                          ?>

                          <?php if ($priceRO) { // não pode alterar o preço então pergunto se quer bloquear a venda de acordo com o descont ?>
                              <div class="form-group col-md-2 col-xs-12 <?php echo (form_error('maximum_discount')) ? "has-error" : ""; ?> ">
                                  <div class="price_catalog" >
                                      <input type="checkbox" name="checkdiscount<?=$sufix_field_name?>" <?= set_checkbox("checkdiscount$sufix_field_name", 'on' , !is_null($key == 0 ? $product_data['maximum_discount_catalog'] : ($product_associated['maximum_discount_catalog'] ?? null))) ?>>
                                      &nbsp;
                                      <small>
                                          <?=$sellercenter_name=='somaplace'?$this->lang->line('application_inactive_if_this_discount_gs'):$this->lang->line('application_inactive_if_this_discount');?>
                                          <?php if($sellercenter_name=='somaplace'): ?>
                                              <i style="color:red;" class="fa fa-question-circle-o" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="" data-original-title="<?= $this->lang->line('application_inactive_if_this_discount_gs_tooltip')?>" aria-describedby="tooltip232541"></i>
                                          <?php endif ;?>
                                      </small>
                                      <div class="input-group">
                                          <input type="text" class="form-control maskdecimal2" name="maximum_discount<?=$sufix_field_name?>" placeholder="<?=$this->lang->line('application_maximum_discount');?>" value="<?php echo set_value("maximum_discount$sufix_field_name", $key == 0 ? $product_data['maximum_discount_catalog'] : ($product_associated['maximum_discount_catalog'] ?? '')) ?>" autocomplete="off" />
                                          <span class="input-group-addon"><strong>%</strong></span>
                                      </div>
                                      <?php if (!is_null($product_catalog['original_price']) && ($product_catalog['original_price'])!==0) { ?>
                                          <small style="color:black;"><b><?=$this->lang->line('application_current_discount');?>: <?php echo round((1-$product_catalog['price'] / $product_catalog['original_price'])*100,2)." %" ?></b></small>
                                      <?php } ?>
                                  </div>
                                  <?php echo '<i style="color:red">'.form_error("maximum_discount$sufix_field_name").'</i>'; ?>
                              </div>
                          <?php } ?>
                          <div class="form-group col-md-2 col-xs-12  <?php echo (form_error('status')) ? "has-error" : ""; ?>">
                              <label for="status"><?=$this->lang->line('application_active');?>(*)</label>
                              <select class="form-control" id="status" name="status<?=$sufix_field_name?>" <?=$readonly?>>
                                  <option value="1" <?php echo set_select('status', 1, ($key == 0 ? $product_data['status'] : ($product_associated['status'] ?? 1)) == 1) ?>><?=$this->lang->line('application_yes');?></option>
                                  <option value="2" <?php echo set_select('status', 2, ($key == 0 ? $product_data['status'] : ($product_associated['status'] ?? 1)) == 2) ?>><?=$this->lang->line('application_no');?></option>
                              </select>
                              <?php echo '<i style="color:red">'.form_error('status').'</i>'; ?>
                          </div>
                      </div>
                  <?php endforeach ?>

                  <?php
                  include_once APPPATH . 'views/products/components/popup.update.status.product.php';
                  ?>
                  <script src="<?php echo base_url('assets/dist/js/components/products/product.status.component.js') ?>"></script>


                
				<div class="row"> </div>
				<hr>
			    <h4 class="mb-3">Detalhes do Produto</h4>
				<?php if($product_catalog['status'] != 1) {  ?>
					<div class="form-group col-md-12 col-xs-12">
						 <label><span style="color:red"><?=$this->lang->line('application_product_catalog_inactive');?></span></label>
						 <br><label><span style="color:red"><?=$this->lang->line('application_reason');?>: <?php echo $product_catalog['reason']; ?></span></label>
					</div>
                 	<div class="row"></div>
                <?php } ?>
                	 
				<div class="form-group col-md-12 col-xs-12">
                  <label><?=$this->lang->line('application_description');?></label>
                  <span class="descriptionName"><?php echo $product_catalog['description']; ?></span>
                </div>
                
				<div class="form-group col-md-12 col-xs-12">
                 <table class="table table-striped table-hover responsive display table-condensed"> 
                	<tr>
                		<th><?=$this->lang->line('application_suggested_price');?></th>
                		<td><?php echo get_instance()->formatprice($product_catalog['price']); ?></td>
                	</tr>
                	<?php if (!is_null($product_catalog['original_price']) && ($product_catalog['original_price'])!==0) { ?> 
	                	<tr>
	                		<th><?=$this->lang->line('application_original_price');?></th>
	                		<td><?php echo get_instance()->formatprice($product_catalog['original_price']); ?></td>
	                	</tr>
	                	<tr>
	                		<th><?=$this->lang->line('application_discount');?></th>
	                		<td><?php echo round((1-$product_catalog['price'] / $product_catalog['original_price'])*100,2)." %" ?></td>
	                	</tr>
                	<?php } ?> 
                	<?php if (!is_null($product_catalog['ref_id'])) { ?> 
	                	<tr>
	                		<th><?=$this->lang->line('application_vtex_ref_id');?></th>
	                		<td><?php echo $product_catalog['ref_id']; ?></td>
	                	</tr>
                	<?php } ?> 
                	<?php if (!is_null($product_catalog['mkt_sku_id'])) { ?> 
	                	<tr>
	                		<th><?=$this->lang->line('application_vtex_sku_id');?></th>
	                		<td><?php echo $product_catalog['mkt_sku_id']; ?></td>
	                	</tr>
                	<?php } ?> 
                	<tr>
                		<th><?=$this->lang->line('application_brandcode');?></th>
                		<td><?php echo $product_catalog['brand_code']; ?></td>
                	</tr>
                	<tr>
                		<th><?=$this->lang->line('application_net_weight');?></th>
                		<td><?php echo $product_catalog['net_weight']; ?></td>
                	</tr>
                	<tr>
                		<th><?=$this->lang->line('application_weight');?></th>
                		<td><?php echo $product_catalog['gross_weight']; ?></td>
                	</tr>
                	<tr>
                		<th><?=$this->lang->line('application_width');?></th>
                		<td><?php echo $product_catalog['width']; ?></td>
                	</tr>
                	<tr>
                		<th><?=$this->lang->line('application_height');?></th>
                		<td><?php echo $product_catalog['height']; ?></td>
                	</tr>
                	<tr>
                		<th><?=$this->lang->line('application_depth');?></th>
                		<td><?php echo $product_catalog['length']; ?></td>
                	</tr>
                    <tr>
                        <th><?=$this->lang->line('application_products_by_packaging');?></th>
                        <td><?php echo $product_catalog['products_package']; ?></td>
                    </tr>
                	<tr>
                		<th><?=$this->lang->line('application_garanty');?>&nbsp;<?=$this->lang->line('application_in_months');?></th>
                		<td><?php echo $product_catalog['warranty']; ?></td>
                	</tr>
                	<tr>
                		<th><?=$this->lang->line('application_NCM');?></th>
                		<td><?php echo $product_catalog['NCM']; ?></td>
                	</tr>
                	<tr>
                		<th><?=$this->lang->line('application_origin_product');?></th>
                		<td><?php foreach ($origins as $k => $v){ 
	                  		if	( $k == $product_catalog['origin']) {
	                  		 	echo $v;
	                      	 } 
	                      } ?>
	                    </td>
                	</tr>
                	<tr>
                		<th><?=$this->lang->line('application_brands');?></th>
                		<td class="brandName" brand-id="<?=$brands[0]['id'] ?? ''?>"><?php foreach ($brands as $k => $v){
	                  		if	( $v['id'] == $product_catalog['brand_id']) {
	                  		 	echo $v['name'];
	                      	 } 
	                      } ?>
	                    </td>
                	</tr>
                	<tr>
                		<th><?=$this->lang->line('application_categories');?></th>
                		<td class="categoryName" category-id="<?=$category[0]['id'] ?? ''?>"><?php foreach ($category as $k => $v){
	                  		if	( $v['id'] == $product_catalog['category_id']) {
	                  		 	echo $v['name'];
	                      	 } 
	                      } ?>
	                    </td>
                	</tr>
                	<?php if(in_array('admDashboard', $user_permission)) {  ?>
                	<tr>
                		<th><?=$this->lang->line('application_catalog_product');?></th>
                		<td>
	                  		<a href="<?php echo base_url().'catalogProducts/view/'.$product_catalog['id'];?>"  target="_blank"><span "><?php echo $product_catalog['id'] ?> &nbsp </a>
	                    </td>
                	</tr>
                	 <?php } ?>
                     <?php if ($identifying_technical_specification && $identifying_technical_specification['status'] == 1) { ?>
                         <tr>
                             <th><?=$this->lang->line('application_collection');?></th>
                             <td><?= $catalog['attribute_value'] ?? '' ?></td>
                         </tr>
                     <?php } ?>
                </table>
                </div>
                
                <!---
  
                <div class="form-group col-md-3 col-xs-12 ">
                 	<label for="EAN"><?=$label_ean;?></label>
                  	<span class="form-control"><?php echo set_value('EAN',$product_catalog['EAN']) ?></span>
                </div>

                <div class="form-group col-md-6 col-xs-12">
                  	<label for="name"><?=$this->lang->line('application_name');?></label>
                  	<span class="form-control"><?php echo set_value('EAN',$product_catalog['name']) ?></span>
                </div>   
                       
                <div class="row"></div>
                <?php
                	$mycatalogs = array(); 
	                $allcatalogs = array();
					foreach($linkcatalogs as $lc) {
					 	$allcatalogs[] = $lc['catalog_id'];
					}
                	foreach ($catalogs as $catalog) {
                		if (in_array($catalog['id'], $allcatalogs)) {
                			$mycatalogs[] = $catalog['name'];
                		}
                	}
                ?>
                <div class="form-group col-md-12 col-xs-12">
                  	<label for="name"><?=$this->lang->line('application_catalogs');?></label>
                  	<span class="form-control"><?php echo implode(", ",$mycatalogs) ?></span>
                </div>
                      
                <div class="col-md-12 col-xs-12"><hr></div>

                <div class="form-group col-md-12 col-xs-12">
                  <label for="description"><?=$this->lang->line('application_description');?>(*)</label>
                  <span><?php echo $product_catalog['description']; ?></span>
                </div>
                
                <div class="row"></div>
                
                
                
				<div class="form-group col-md-3 col-xs-12">
                  	<label for="price"><?=$this->lang->line('application_price');?></label>
                  	<div class="input-group">
                    	<span class="input-group-addon"><strong>R$</strong></span>
                    	<span class="form-control"><?php echo $product_catalog['price']; ?></span>
                	</div>
                </div>
				
                <div class="form-group col-md-3 col-xs-12">
                  	<label for="brand_code"><?=$this->lang->line('application_brandcode');?></label>
                  	<span class="form-control"><?php echo $product_catalog['brand_code']; ?></span>             
                </div>
                
                <div class="row"></div>
                
               	<div class="form-group col-md-3 col-xs-12">
                  	<label for="net_weight"><?=$this->lang->line('application_net_weight');?></label>
                  	<div class="input-group">
                    	<span class="form-control"><?php echo $product_catalog['net_weight']; ?></span>
                    	<span class="input-group-addon"><strong>Kg</strong></span>
                  	</div>
                </div>
                
                <div class="form-group col-md-3 col-xs-12">
                  	<label for="gross_weight"><?=$this->lang->line('application_weight');?></label>
                  	<div class="input-group">
                  		<span class="form-control"><?php echo $product_catalog['gross_weight']; ?></span>
                    	<span class="input-group-addon"><strong>Kg</strong></span>
                  	</div>
                </div>
                
                <div class="form-group col-md-3 col-xs-12">
                  	<label for="width"><?=$this->lang->line('application_width');?></label>
                 	<div class="input-group">
                 		<span class="form-control"><?php echo $product_catalog['width']; ?></span>
                    	<span class="input-group-addon"><strong>cm</strong></span>
                  	</div>
                </div>
                
                <div class="form-group col-md-3 col-xs-12">
                  	<label for="height"><?=$this->lang->line('application_height');?></label>
                  	<div class="input-group">
                  		<span class="form-control"><?php echo $product_catalog['height']; ?></span>
                    	<span class="input-group-addon"><strong>cm</strong></span>
                 	 </div>
                </div>
                
                <div class="row"></div>
                
                <div class="form-group col-md-3 col-xs-12">
                  	<label for="length"><?=$this->lang->line('application_depth');?></label>
                  	<div class="input-group">
                  		<span class="form-control"><?php echo $product_catalog['length']; ?></span>
                    	<span class="input-group-addon"><strong>cm</strong></span>
                  	</div>
                </div>
                
                <div class="form-group col-md-3 col-xs-12">
                  	<label for="warranty"><?=$this->lang->line('application_garanty');?>(em meses)</label>
                  	<span class="form-control"><?php echo $product_catalog['warranty']; ?></span>
                </div>
                
                <div class="form-group col-md-3 col-xs-12">
                  	<label for="NCM"><?=$this->lang->line('application_NCM');?></label>
                  	<span class="form-control"><?php echo $product_catalog['NCM']; ?></span>
                </div>

                  <?php $attribute_id = json_decode($product_catalog['attribute_value_id']);
                  if (is_null($attribute_id)) {
                      $attribute_id = Array ("[]");
                  }
                  ?>
                  <?php if($attributes): ?>
                      <?php foreach ($attributes as $k => $v): ?>
                          <div class="form-group col-md-3 col-xs-12">
                              <label for="groups"><?php echo $v['attribute_data']['name'] ?>(*)</label>
                              <select class="form-control select_group" style="width:80%" id="attributes_value_id" name="attributes_value_id[]" >
                                  <?php foreach ($v['attribute_value'] as $k2 => $v2): ?>
                                      <option value="<?php echo $v2['id'] ?>" disabled <?php echo set_select('attributes_value_id', $v2['id'], in_array($v2['id'], $attribute_id)) ?>><?php echo $v2['value'] ?></option>
                                  <?php endforeach ?>
                              </select>
                          </div>
                      <?php endforeach ?>
                  <?php endif; ?>
                  
                <div class="form-group col-md-12 col-xs-12">
                  	<label for="origin"><?=$this->lang->line('application_origin_product');?>(*)</label>
                  		<?php foreach ($origins as $k => $v): 
                  		if	( $k == $product_catalog['origin']) {
                  		?>
                  			<span class="form-control"><?php echo $v ?></span>
                      	<?php } ?>
                     <?php endforeach ?>
                </div>
                
                <div class="form-group col-md-5 col-xs-12">
                  	<label for="brands"><?=$this->lang->line('application_brands');?></label>
                  	<?php 
                  	foreach ($brands as $k => $v): 
                  		if	( $v['id'] == $product_catalog['brand_id']) {
                  		?>
                  			<span class="form-control"><?php echo $v['name'] ?></span>
                      	<?php } ?>
                    <?php endforeach ?>
                </div>
                
                <div class="form-group col-md-12 col-xs-12">
                  	<label for="brands"><?=$this->lang->line('application_categories');?></label>
                  	<?php foreach ($category as $k => $v): 
                  		if	( $v['id'] == $product_catalog['category_id']) {
                  		?>
                  			<span class="form-control"><?php echo $v['name'] ?></span>
                      	<?php } ?>
                     <?php endforeach ?>
                </div>
                
                <div id='linkcategory'></div>
                
                <div class="row"></div>
                --->
		  	
		  		<?php if (count($mykits)) : ?>
				<div class="form-group col-md-12 col-xs-12">
				<br>
                 
				<button onClick="toggleKits(event)" class="btn btn-default"><i class="fa fa-puzzle-piece"></i> <?=count($mykits).' '.$this->lang->line('application_products_kit');?></button>
				   <div id="kits_products" style="display: none;">
	                  <table  class="table table-striped table-bordered"> 
	                  	<tr> 
	                  	  <th><?=$this->lang->line('application_id');?></th>
	                  	  <th><?=$this->lang->line('application_seller_sku');?></th>
	                  	  <th><?=$this->lang->line('application_name');?></th>
	                  	  <th><?=$this->lang->line('application_price');?></th>
	                  	  <th><?=$this->lang->line('application_qty');?></th>
	                  	  <th><?=$this->lang->line('application_action');?></th>
	                  	</tr>
	                  	<?php foreach($mykits as $kit) :   ?>
	                  		<tr>
	                  			<th scope="row"><a target="__blank" href="<?php echo base_url('productsKit/update/'.$kit['id']); ?>" ><?php echo $kit['id'];?></a></th>
	                  			<td><?php echo $kit['sku']; ?></td>
	                  			<td><?php echo $kit['name']; ?></td>
	                  			<td><?php echo get_instance()->formatprice($kit['price']); ?></td>
	                  			<td><?php echo $kit['qty']; ?></td>
	                  			<td><a target="__blank" href="<?php echo base_url('productsKit/update/'.$kit['id']); ?>" class="btn btn-default"><i class="fa fa-eye"></i></a></td>
							
	                  		</tr>
	                  	<?php endforeach ?>
	                  </table>
                  </div>
                </div>
			<?php endif ?>
			
			<?php if (count($myorders)) : ?>
				<div class="form-group col-md-12 col-xs-12">
				<br>
				  <button onClick="toggleOrders(event)" class="btn btn-default"><i class="fa fa-dollar-sign"></i> <?= count($myorders).' '.$this->lang->line('application_orders');?></button>	
				  <div id="orders_product" style="display: none;">

	                  <table class="table table-striped table-bordered"> 
	                  	<tr> 
	                  	  <th><?=$this->lang->line('application_id');?></th>
	                  	  <th><?=$this->lang->line('application_marketplace');?></th>
	                  	  <th><?=$this->lang->line('application_order_marketplace_full');?></th>
	                  	  <th><?=$this->lang->line('application_name');?></th>
	                  	  <th><?=$this->lang->line('application_total_amount');?></th>
	                  	  <th><?=$this->lang->line('application_order_date');?></th>
	                  	  <th><?=$this->lang->line('application_status');?></th>
	                  	  <th><?=$this->lang->line('application_action');?></th>
	                  	</tr>
	                  	<?php foreach($myorders as $order) :   ?>
	                  		<tr>
	                  			<th scope="row"><a target="__blank" href="<?php echo base_url('orders/update/'.$order['id']); ?>" ><?php echo $order['id'];?></a></th>
	                  			<td><?php echo $order['origin']; ?></td>
	                  			<td><?php echo $order['numero_marketplace']; ?></td>
	                  			<td><?php echo $order['customer_name']; ?></td>
	                  			<td><?php echo get_instance()->formatprice($order['total_order']); ?></td>
	                  			<td><?php echo date('d/m/Y', strtotime($order['date_time'])); ?></td>
	                  			<td><?php echo $this->lang->line('application_order_'.$order['paid_status']); ?></td>
	                  			<td><a target="__blank" href="<?php echo base_url('orders/update/'.$order['id']); ?>" class="btn btn-default"><i class="fa fa-eye"></i></a></td>
							
	                  		</tr>
	                  	<?php endforeach ?>
	                  </table>
                  				  	
				  </div>
                </div>
			<?php endif ?>

            <div class="form-group col-md-12">
              <div class="callout callout-warning mb-0" style="display: none" id="listBlockView">
                  <h4 class="mt-0"><?= $this->lang->line('application_blocked_product_future') ?></h4>
                  <ul></ul>
              </div>
            </div>
			
			<!--- adicionado tabela de itegrações -->
               	<?php if (isset($integracoes)) : ?>
                <div class="form-group col-md-12 col-xs-12">
                  <label><?=$this->lang->line('application_integrations');?></label>
                  <table style="width:100%;" class="table table-striped table-bordered"> 
                  	<tr style="background-color: #f1f1c1; border: 1px solid black; border-collapse: collapse; font-size: smaller" > 
                  	  <th style="">Marketplace</th>
                  	  <th style="">SKU Local</th>
                  	  <th style="">SKU Marketplace</th>
                  	  <th style=""><?=$this->lang->line('application_status');?></th>
                  	  <th style=""><?=$this->lang->line('application_date');?></th>
                  	  <th style=""><?=$this->lang->line('application_advertisement_link');?></th>
                  	  <th style=""><?=$this->lang->line('application_quality');?></th>
                  	  <?php if (in_array('doProductsApproval', $this->permission)) : ?>
                  	  	<th style=""><?=$this->lang->line('application_products_approval');?></th>
                  	  <?php endif ?>
                  	</tr>
                  	<?php foreach($integracoes as $integracao) :  
						$ad_link ='';
						if (!is_null($integracao['ad_link'])) {
							$ad_links = json_decode($integracao['ad_link'],true);
							if (json_last_error() === 0) {
								foreach($ad_links as $link) {
									$ad_link .= '<a target="__blank" href="'.$link['href'].'" class="btn btn-default"><i class="fa fa-money"></i><small> '.$link['name'].'</small></a><br>';
								}
							}else {
								if (strpos($integracao['ad_link'],'http')!==false) {
									$ad_link .= '<a target="__blank" href="'.$integracao['ad_link'].'" class="btn btn-default"><i class="fa fa-money"></i><small> '.$this->lang->line('application_goto_ad').'</small></a>';
								}
							}
						}
						$quality = '';
						if (!is_null($integracao['quality'])) {
							$perc = (float)$integracao['quality'] * 100;
							if ($perc == 100) {
								$desc = $this->lang->line('application_professional');	
								$pd = "progress-bar-success";
							}elseif ($perc >=80) {
								$desc = $this->lang->line('application_satisfactory');	
								$pd = "progress-bar-info";
							}else {
								$desc = $this->lang->line('application_basic');
								$pd = "progress-bar-danger";
							}
							$quality = '<div class="progress-bar '.$pd.'" role="progressbar" aria-valuenow="'.$perc.'" aria-valuemin="0" aria-valuemax="100" style="width:'.$perc.'%">'.$perc.'% '.$desc.'</div>';
						}			
							
						 ?>
                  		<tr style="background-color: white; border: 1px solid black; border-collapse: collapse; font-size: smaller">
                  			<td style=""><?php echo $integracao['name']; ?>
                  				<?php if (($integracao['status'] == 1) && (trim($integracao['skubling']) != '') && (trim($integracao['approved']) == 1) ): ?>
									<button onclick="sendToMarketplace(event,'<?= $integracao['int_to'] ?>','<?= $product_data['id'] ?>')" class="pull-right btn btn-success btn-sm"><?= $this->lang->line('application_send'); ?></button>
								<?php endif ?>
                  			</td>
                  			<td style=""><?php echo $integracao['skubling']; ?></td>
                  			<td style=""><?php echo $integracao['skumkt']; ?></td>
                  			<?php if ($integracao['date_last_int'] != '') {
                  				$data_int = date("d/m/Y H:i:s",strtotime($integracao['date_last_int']));
                  			}else{
                  				$data_int = '--';
                  			}
							?>
							<td style="max-width: 250px;overflow-x: auto"><?php echo $integracao['status_int']; ?>
								<?php if (($data_int != '--') && (!$notAdmin)): ?> 
									<br><a href="<?php echo base_url("products/log_integration_marketplace/".$integracao['int_to']."/".$product_data['id']) ?>" ><?= $this->lang->line('application_integration_log_with').' '.$integracao['int_to']; ?></a>
								<?php endif ?>
							</td>
                  			<td style=""><?php echo $data_int; ?></td>
                  			<td style=""><?php echo $ad_link; ?></td>
                  			<td style=""><?php echo $quality; ?></td>
                  			
                  			 <?php if (in_array('doProductsApproval', $this->permission)) : ?>
                  			 	<?php if (!$integracao['auto_approve']) : ?>
                  			 	 <td style="font-size: smaller">
                  			 	 	<?php if ($integracao['approved'] != 4) : ?>
	                  			 	 	<?php if ($integracao['approved'] != 1) : ?>
	                  			 	 	 	<button onclick="changeIntegrationApproval(event,'<?=$integracao['id']?>','<?=$product_data['id']?>','1','<?=$integracao['approved']?>','<?=$integracao['int_to']?>')" class="btn btn-success"><small><?=$this->lang->line('application_approve');?></small></button>
	                   			 	 	<?php endif ?>
	                  			 	 	<?php if ($integracao['approved'] != 2) : ?>	
	                  			 	 		<button onclick="changeIntegrationApproval(event,'<?=$integracao['id']?>','<?=$product_data['id']?>','2','<?=$integracao['approved']?>','<?=$integracao['int_to']?>')" class="btn btn-danger"><small><?=$this->lang->line('application_disapprove');?></small></button>
	                  			 	 	<?php endif ?>
	                  			 	 	<?php if ($integracao['approved'] != 3) : ?>	
	                  			 	 		<button onclick="changeIntegrationApproval(event,'<?=$integracao['id']?>','<?=$product_data['id']?>','3','<?=$integracao['approved']?>','<?=$integracao['int_to']?>')" class="btn btn-primary"><small><?=$this->lang->line('application_mark_as_in_approval');?></small></button>
	                  			 	 	<?php endif ?>
                  			 	 	<?php endif ?>
                  			 	 </td>
                  			 	 
                  			 	 <?php else: ?>
                  			 	<td style=""></td>
                  			 	<?php endif ?>
                  			 <?php endif ?>
                  		</tr>
                  	<?php endforeach ?>
                  </table>
                </div>
                <?php endif ?>
                
                <?php if (count($errors_transformation)>0) : ?>
                <div class="form-group col-md-12 col-xs-12">
                  <h3><span id="errors-transformation" class="label label-danger"><?=$this->lang->line('application_errors_tranformation');?></span></h3>
                  <table  class="table table-bordered table-striped table-dark"> 
                  	<thead class="thead-light">
	                   	<tr style="background-color: #f44336; border: 1px solid black; border-collapse: collapse; color:white"> 
	                  	  <th width="10%"><?=$this->lang->line('application_marketplace');?></th>
	                  	  <th width="10%"><?=$this->lang->line('application_step');?></th>
	                  	  <th width="10%"><?=$this->lang->line('application_date');?></th>
	                  	  <th width="70%"><?=$this->lang->line('application_error');?></th>
	                  	</tr>
                  	</thead>
                  	 <tbody>
                  	<?php foreach($errors_transformation as $error_transformation) :   ?>
                  		<tr style="background-color: lightgray; border: 1px solid black; border-collapse: collapse;">
                  			<td width="10%"><?php echo $error_transformation['int_to']; ?></td>
                  			<td width="10%"><?php echo $error_transformation['step']; ?></td>
                  			<td width="10%"><?php echo  date('d/m/Y H:i:s', strtotime($error_transformation['date_update'])); ?></td>
                  			<td width="70%"><?php echo $error_transformation['message']; ?></td>
                  		</tr>
                  	<?php endforeach ?>
                  	</tbody>
                  </table>
                </div>
                <?php endif ?>
		  	
              </div> 
              <!-- /.box-body -->

              <div class="box-footer">
                  <button
                          type="submit"
                          class="btn btn-primary"
                      <?= $product_data['status'] == Model_products::DELETED_PRODUCT ? 'style="display:none;"' : '' ?>
                  >
                      <?=$this->lang->line('application_update_changes');?>
                  </button>
               	  <?php if ($backsite == 'backupdate') { ?>
               	  	<a href="<?php echo base_url('catalogProducts/update/'.$product_catalog['id']) ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
               	  <?php } elseif ($backsite == 'backview') { ?>
               	  	<a href="<?php echo base_url('catalogProducts/view/'.$product_catalog['id']) ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
               	  <?php } else { ?>
               	  	 <a href="<?php echo base_url('products') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
               	  <?php }  ?>
				  <a href="<?php echo base_url("products/log_products_view/".$product_data['id']) ?>" class="pull-right btn btn-warning"><?= $this->lang->line('application_latest_changes'); ?></a>
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
<!-- /.content-wrapper -->
<div class="modal fade" tabindex="-1" role="dialog" id="approvePromotion">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?=$this->lang->line('application_approve_promotion');?></h4>
		</div>
	    <form role="form" action="<?php echo base_url('promotions/approvePromotion') ?>" method="post" id="approvePromotionForm">
	    <div class="modal-body">
			<p><?=$this->lang->line('application_confirm_approve_promotion');?></p>
			<input type="hidden" name="id_approve"  id="id_approve" value="" autocomplete="off">
			<input type="hidden" name="id_product"  id="id_product" value="<?=$product_data['id'];?>" autocomplete="off">
		</div> <!-- modal-body -->
	    <div class="modal-footer">
	      <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
	      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="inactivePromotion">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?=$this->lang->line('application_inactivate_promotion');?></h4>
		</div>
	    <form role="form" action="<?php echo base_url('promotions/inactivePromotion') ?>" method="post" id="inactivePromotionForm">
	    <div class="modal-body">
			<p><?=$this->lang->line('application_confirm_inactivate_promotion');?></p>
			<input type="hidden" name="id_inactive"  id="id_inactive" value="" autocomplete="off">
			<input type="hidden" name="id_product"  id="id_product" value="<?=$product_data['id'];?>" autocomplete="off">
		</div> <!-- modal-body -->
	    <div class="modal-footer">
	      <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
	      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removePromotion">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?=$this->lang->line('application_remove_promotion');?></h4>
		</div>
	    <form role="form" action="<?php echo base_url('promotions/removePromotion') ?>" method="post" id="removePromotionForm">
	    <div class="modal-body">
			<p><?=$this->lang->line('application_confirm_remove_promotion');?></p>
			<input type="hidden" name="id_remove"  id="id_remove" value="" autocomplete="off">
			<input type="hidden" name="id_product"  id="id_product" value="<?=$product_data['id'];?>" autocomplete="off">
		</div> <!-- modal-body -->
	    <div class="modal-footer">
	      <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
	      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script src="<?php echo base_url('assets/dist/js/components/products/product.disable.form.js') ?>"></script>
<script type="text/javascript">
var base_url = "<?php echo base_url(); ?>";

var maximum_discount_save = "<?php echo $product_data['maximum_discount_catalog']; ?>";

var productDeleted = '<?= $product_data['status'] == Model_products::DELETED_PRODUCT ?>';

$(document).ready(function() {
    if(productDeleted) {
        (new ProductDisableForm({
            form: $('#approvePromotionForm')
        })).disableForm();
    }
    $(".select_group").select2();
	
	$("input[type='checkbox']:checked").trigger('change');
	
    $('.maskdecimal3').inputmask({
		  alias: 'numeric', 
		  allowMinus: false,  
		  digits: 3, 
		  max: 999999999.999
		});
    $('.maskdecimal2').inputmask({
		  alias: 'numeric', 
		  allowMinus: false,  
		  digits: 2, 
		  max: 999999999.99
		});

    verifyWords();
    $('#store').trigger('change');
    $('[name^="checkdiscount"]').trigger('change');
    $('[name^="enable_catalog"]').trigger('change');
});
  
$('#formUpdateProduct').submit(function () {
    let variations = [];
    let exitApp = false;
    $('input[name="SKU_V[]"]').each(function () {
        if(variations.includes($(this).val()) && $(this).val() != "") exitApp = true;
        variations.push($(this).val());
    });

    if (exitApp) {
        AlertSweet.fire({
            icon: 'warning',
            title: 'Não é permitido o mesmo SKU para mais que uma variação. <br><br>Faça o ajuste e tente novamente!'
            });
            return false;
        }
});

function restrict(tis) { // so aceita numero com 2 digitos 
	var prev = tis.getAttribute("data-prev");
  	prev = (prev != '') ? prev : '';
  	if (Math.round(tis.value*100)/100!=tis.value)
  	tis.value=prev;
  	tis.setAttribute("data-prev",tis.value)
}

function checkEAN(ean, field, product_id) {
	
	$.ajax({
		type: "POST",
		enctype: 'multipart/form-data',
		data: {
			ean: ean,
			product_id: product_id
		},
		url: base_url+"catalogProducts/checkEANpost",
		dataType: "json",
		async: true,
		success: function(response) { 
			//console.log(response)
			if (response.success) {
				var id = $("#"+field);
				id.removeClass('has-error');
				var id = $("#"+field+'erro');
				$("#"+field+'erro').hide();
			}
			else {
			    var id = $("#"+field);
				id.addClass('has-error');
				$("#"+field+'erro').html('<i style="color:red">'+response.message+'</i>');
				$("#"+field+'erro').show();
			} 
			
		},
		error: function(jqXHR, textStatus, errorThrown) {
           console.log(textStatus, errorThrown);
		}
	}); 
}

function toggleOrders(e) {
	  	e.preventDefault();
	  	$("#orders_product").toggle();
  	}
	
function toggleKits(e) {
  	e.preventDefault();
  	$("#kits_products").toggle();
}

function approvePromotion(e, promotion_id) {
  	e.preventDefault();
    document.getElementById('id_approve').value=promotion_id; 
	$("#approvePromotion").modal('show');	
}

function inactivePromotion(e, promotion_id) {
  	e.preventDefault();
    document.getElementById('id_inactive').value=promotion_id; 
	$("#inactivePromotion").modal('show');	
}

function deletePromotion(e, promotion_id) {
  	e.preventDefault();
    document.getElementById('id_remove').value=promotion_id; 
	$("#removePromotion").modal('show');	
}

function samePrice(id) {
	const samePrice = document.getElementById('samePrice_'+id).checked

	const fields = [
		{original: 'price', copy: 'price_'+id},
	]
	
	if (samePrice) {
		fields.forEach((item) => {
		$('#'+item.copy)[0].value = $('#'+item.original).val()
		$('#'+item.copy).attr('disabled', 'disabled')
		})
		$('#samePrice_'+id).attr('checked', 'checked')
	} else {
		fields.forEach((item) => {
		$('#'+item.copy)[0].value = $('#'+item.original).val()
		$('#'+item.copy).removeAttr('disabled')
		})
		$('#samePrice_'+id).removeAttr('checked')
	}
}

function sameQty(id) {
	const sameQty = document.getElementById('sameQty_'+id).checked

	const fields = [
		{original: 'qty', copy: 'qty_'+id},
	]
	
	if (sameQty) {
		fields.forEach((item) => {
		$('#'+item.copy)[0].value = $('#'+item.original).val()
		$('#'+item.copy).attr('disabled', 'disabled')
		})
		$('#sameQty_'+id).attr('checked', 'checked')
	} else {
		fields.forEach((item) => {
		$('#'+item.copy)[0].value = $('#'+item.original).val()
		$('#'+item.copy).removeAttr('disabled')
		})
		$('#sameQty_'+id).removeAttr('checked')
	}
}

function changeQtyMkt(id) {

  if ($('#qty_'+id).val()> $('#qty').val()) {
  	Swal.fire({
				  icon: 'error',
				  title: "A quantidade para um marketplace não pode ser maior que o estoque do produto."
				}).then((result) => {
				});
	$('#qty_'+id)[0].value = $('#qty').val()
  }
 
}

function changeIntegrationApproval(e, id, prd_id, approve, old_approve, int_to) {
	e.preventDefault();
	$.ajax({
		url: base_url+"products/changeIntegrationApproval",
    	type: "POST",
        data: {
            id: id, prd_id : prd_id, approve : approve, old_approve: old_approve, int_to: int_to
        },
        async: true,
        success: function(data) {
        	location.reload();     
        }, 
        error: function(data) {
             AlertSweet.fire({
                icon: 'Error',
                title: 'Houve um erro ao atualizar o produto!'
            });
        }
	}); 

}

function toggleCatMktPlace(e) {
  	e.preventDefault();
  	$("#catlinkdiv").toggle();
}

$(document).on('change', '[name^="checkdiscount"]', function(){
    const checkdiscount = $(this);
    const maximumdiscount = $(this).closest('.price_catalog').find('[name^="maximum_discount"]');
    if (checkdiscount.is(':checked')) {
        maximumdiscount.prop('readonly', false);
        if (maximumdiscount.val() === '') {
            maximumdiscount.val('0.00');
        }
    }
    else {
        maximumdiscount.val('');
        maximumdiscount.prop('readonly', true);
    }
});

$(document).on('blur', '[name^="maximum_discount"]', function(){
    const maximumdiscount = $(this).val();

    if (maximumdiscount > 100)  {
        $(this).val(100);
    }
});

	function sendToMarketplace(e, int_to, prd_id) {
		e.preventDefault();
		
		AlertSweet.fire({
            title: '<?=$this->lang->line("do_you_want_to_send_the_product_back_to_the_marketplace")?>&nbsp'+int_to+' ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<?=$this->lang->line("application_send")?>',
            cancelButtonText: '<?=$this->lang->line("application_cancel")?>'
        }).then((result) => {
			//console.log(result);
            if (result.value) {
				$.ajax({
					url: base_url + "products/sendToMarketplace",
					type: "POST",
					dataType: "json",
					data: {
						prd_id: prd_id,
						int_to: int_to
					},
					async: true,
					success: function(data) {
						//console.log(data);
						if (data.status == 'success') {
							AlertSweet.fire({
								icon: 'success',
								title: '<?=$this->lang->line("the_product_has_been_queued_for_transmission_at")?> '+int_to,
								text: '<?=$this->lang->line("within_a_few_moments_refresh_the_screen")?> '
							});
						} else {
							AlertSweet.fire({
								icon: 'error',
								title: '<?=$this->lang->line("there_was_an_error_placing_the_product_in_the_queue")?>'
							});
						}
						
					},
					error: function(data) {
						AlertSweet.fire({
							icon: 'error',
							title: '<?=$this->lang->line("there_was_an_error_placing_the_product_in_the_queue")?>'
						});
					}
				});
			}
		});
	}

    const verifyWords = () => {
        const brand = $('.brandName').attr('brand-id');
        const category = $('.categoryName').attr('category-id');
        const store = $('#store').val();
        const sku = $('#sku').val();
        const product = window.location.pathname.indexOf('copy') >= 0 ? 0
            : parseInt(window.location.pathname.split('/').pop());
        const name = $('.nameProduct').text();
        const description = $('.descriptionName').html();

        $.ajax({
            type: "POST",
            data: {
                name,
                description,
                brand,
                category,
                store,
                sku,
                product
            },
            url: base_url + "index.php/products/verifyWords",
            dataType: "json",
            async: true,
            success: function(response) {

                console.log(response);

                if (response.blocked) {
                    let messageBlock = '';

                    $(response.data).each(function(index, value) {
                        messageBlock += `<li>${value}</li>`;
                    });

                    $('#listBlockView ul').empty().html(messageBlock);
                    $('#listBlockView').show();
                } else {
                    $('#listBlockView ul').empty().html('');
                    $('#listBlockView').hide();
                }

            },
            error: function(error) {
                console.log(error)
            }
        });
    }

$(document).on('change', '.enable_catalog', function () {
    $(this).closest('.content-catalog-product').find('input:not(.enable_catalog), select').prop('disabled', !$(this).is(':checked'));
    $(this).closest('.content-catalog-product').find('[name^="checkdiscount"]').trigger('change');

    if ($(this).is(':checked') && !$(this).closest('.content-catalog-product').find('[name^="checkdiscount"]').is(':checked')) {
        $(this).closest('.content-catalog-product').find('[name^="maximum_discount"]').prop('readonly', true);
    }
})
</script>