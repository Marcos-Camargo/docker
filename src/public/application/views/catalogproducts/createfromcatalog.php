<!--

Criar produto de um produto de catálogo

--> 
<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

<!-- necssário para o carousel ----> 

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_add";  
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
              <form action="<?php base_url('catalogProducts/createFromCatalog/'.$product_catalog['id']) ?>" method="post" enctype="multipart/form-data" id="formUpdateProduct">
        	
              <div class="box-body" >
                <div class="row">
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
                        <?php for ($i=1; $i<=$numft; $i++) { ?>
                            <a href="<?= $ln1[$i];?>"><img width="150px" height="150px" src="<?= $ln1[$i];?>" ></a>
                        <?php } ?>
                    </div>

                    <div class="form-group col-md-12 col-xs-12">

                        <h2><strong><?php echo $product_catalog['name']; ?></strong></h2>
                        <p><strong><?=$label_ean;?> : <?php echo $product_catalog['EAN']; ?></strong></p>

                    </div>
                </div>
				<div class="row">
                    <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('sku')) ? "has-error" : ""; ?> ">
                        <label for="sku"><?=$this->lang->line('application_sku');?>(*)</label>
                        <input type="text" class="form-control" id="sku" name="sku" required placeholder="<?=$this->lang->line('application_enter_sku');?>" value="<?php echo set_value('sku'); ?>" autocomplete="off" onKeyUp="checkSpecialSku(event, this);" onblur="checkSpecialSku(event, this);" />
                        <?php echo '<i style="color:red">'.form_error('sku').'</i>'; ?>
                    </div>

                    <div class="form-group col-md-3 col-xs-12">
                      <label for="store"><?=$this->lang->line('application_store');?>(*)</label>
                      <select class="form-control select_group" id="store" name="store">
                        <?php foreach ($stores as $k => $v): ?>
                          <option value="<?php echo $v['id'] ?>" <?php echo set_select('store', $v['id'], false); ?> ><?php echo $v['name'] ?></option>
                        <?php endforeach ?>
                      </select>
                    </div>

                    <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('prazo_operacional_extra')) ? "has-error" : ""; ?> ">
                        <label for="prazo_operacional_extra"><?=$this->lang->line('application_extra_operating_time');?></label>
                        <input type="text" class="form-control" maxlength="2" id="prazo_operacional_extra" name="prazo_operacional_extra" placeholder="<?=$this->lang->line('application_extra_operating_time');?>" value="<?php echo set_value('prazo_operacional_extra',$crossdocking_catalog_default) ?>" autocomplete="off"  onKeyPress="return digitos(event, this);" <?php echo ($changeCrossdocking ? 'readonly' : '');?>  />
                         <?php echo '<i style="color:red">'.form_error('prazo_operacional_extra').'</i>'; ?>
                    </div>
                      <?php  $reqprice = "required";
                      $readonly = '';
                      $sufix_field_name = '';
                      if ($priceRO) {
                          $reqprice = "readonly";
                      } ?>
                      <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('qty')) ? "has-error" : ""; ?> ">
                          <label for="qty"><?=$this->lang->line('application_qty');?>(*)</label>
                          <input type="text" class="form-control" name="qty" <?php echo (count($product_variants) > 0) ? 'readonly' :'required';?> placeholder="<?=$this->lang->line('application_enter_qty');?>" value="<?php echo set_value('qty') ?>" autocomplete="off" onKeyPress="return digitos(event, this);"/>
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
                                                  <input type="text" class="form-control sameqtyval" id="qty_<?=$integration['id']?>" onKeyPress="return digitos(event, this)" onchange="changeQtyMkt(<?=$integration['id']?>)"  name="qty_<?=$integration['id']?>" required placeholder="<?=$this->lang->line('application_enter_qty');?>" value="<?php echo set_value('qty_'.$integration['id']); ?>" autocomplete="off"/>
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
				<div class="row"> </div>
				<div class="content_catalogs_associated"> </div>
				
					<!-- Variants DIV -->
		  		<?php 
		  		if (count($product_variants) > 0) {
		  			
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
						for($i=0; $i<count($product_variants); $i++) {
       						$variant_prod  = array_combine( explode(';', $product_catalog['has_variants']),  explode(';', $product_variants[$i]['name']));
						?>
						<div class="row" >
							<div  class="form-group col-md-1">
		                    	<?php $imagem = ($product_variants[$i]['principal_image'] == '') ? base_url('assets/images/system/sem_foto.png') :  $product_variants[$i]['principal_image']; ?>
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
                       		 	<input type="text" class="form-control" id="Q<?= $i;?>" name="Q[]" autocomplete="off"  placeholder="Estoque" onKeyPress="return digitos(event, this);" value="<?php echo set_value('Q['.$i.']') ?>" />
                    			<?php echo '<i style="color:red">'.form_error('Q['.$i.']').'</i>'; ?>
                    		</div>
                     		<div class="Iskuvar form-group col-md-2 <?php echo (form_error('SKU_V['.$i.']')) ? "has-error" : ""; ?>">
                        		<input type="text" class="form-control" id="SKU_V<?= $i;?>" name="SKU_V[]" autocomplete="off" placeholder="SKU Variação"  value="<?php echo set_value('SKU_V['.$i.']') ?>" />
                				<?php echo '<i style="color:red">'.form_error('SKU_V['.$i.']').'</i>'; ?>
                			</div>			                    
						</div>
						<?php } ?>
					</div>
				</div>
				<?php } ?>
				
                
				<div class="row"> </div>
				<hr>
			    <h4 class="mb-3">Produto Escolhido</h4>
				  
				<div class="form-group col-md-12 col-xs-12">
                  <label><?=$this->lang->line('application_description');?></label>
                  <span><?php echo $product_catalog['description']; ?></span>
                </div>

                  <?php
                  $catalog_attribute = '';
                  $mycatalogs = array();
                  $allcatalogs = array();
                  foreach ($linkcatalogs as $lc) {
                      $allcatalogs[] = $lc['catalog_id'];
                  }
                  foreach ($catalogs as $catalog) {
                      if (in_array($catalog['id'], $allcatalogs)) {
                          $mycatalogs[] = $catalog['name'];
                          $catalog_attribute = $catalog['attribute_value'];
                      }
                  }
                  ?>
                
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
						<td>
							<?=$this->lang->line('application_product_dimensions');?> &nbsp <span class="h6"> <?=$this->lang->line('application_out_of_the_package');?></span>
						</td>
					</tr>
					<tr>
                		<th><?=$this->lang->line('application_net_weight');?></th>
                		<td><?php echo $product_catalog['net_weight']; ?></td>
                	</tr>
					<tr>
                		<th><?=$this->lang->line('application_width');?></th>
                		<td><?php echo $product_catalog['actual_width']; ?></td>
                	</tr>
					<tr>
                		<th><?=$this->lang->line('application_height');?></th>
                		<td><?php echo $product_catalog['actual_height']; ?></td>
                	</tr>
					<tr>
                		<th><?=$this->lang->line('application_depth');?></th>
                		<td><?php echo $product_catalog['actual_depth']; ?></td>
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
                		<td><?php foreach ($brands as $k => $v){ 
	                  		if	( $v['id'] == $product_catalog['brand_id']) {
	                  		 	echo $v['name'];
	                      	 } 
	                      } ?>
	                    </td>
                	</tr>
                	<tr>
                		<th><?=$this->lang->line('application_categories');?></th>
                		<td><?php foreach ($category as $k => $v){ 
	                  		if	( $v['id'] == $product_catalog['category_id']) {
	                  		 	echo $v['name'];
	                      	 } 
	                      } ?>
	                    </td>
                	</tr>
                     <?php if ($identifying_technical_specification && $identifying_technical_specification['status'] == 1) { ?>
                         <tr>
                             <th><?=$this->lang->line('application_collection');?></th>
                             <td><?= $catalog_attribute ?? '' ?></td>
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
		  	
              </div> 
              <!-- /.box-body -->

              <div class="box-footer">
                  <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
				  <a href="<?php echo base_url('catalogProducts/view/'.$product_catalog['id']) ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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


<script type="text/javascript">
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {
    $(".select_group").select2();
 
    $("#mainCatalogNav").addClass('active');
    $("#addProductCatalogNav").addClass('active');
	
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
  	$('.percentual').inputmask({
		  alias: 'numeric', 
		  allowMinus: false,  
		  digits: 0, 
		  max: 999
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
    $('#store').trigger('change');
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

function toggleCatMktPlace(e) {
  	e.preventDefault();
  	$("#catlinkdiv").toggle();
}

$(document).on('change', '[name^="checkdiscount"]', function(){
    const checkdiscount = $(this);
    const maximumdiscount = $(this).closest('.content-catalog-product').find('[name^="maximum_discount"]');
    if (checkdiscount.is(':checked')) {
        maximumdiscount.prop('disabled', false);
        if (maximumdiscount.val() === '') {
            maximumdiscount.val('0.00');
        }
    }
    else {
        maximumdiscount.val('');
        maximumdiscount.prop('disabled', true);
    }
});

$(document).on('blur', '[name^="maximum_discount"]', function(){
    const maximumdiscount = $(this).val();

    if (maximumdiscount > 100)  {
        $(this).val(100);
    }
});

$(document).on('change', '.enable_catalog', function () {
    $(this).closest('.content-catalog-product').find('[name^="status"], [name^="checkdiscount"], [name^="catalog_id"]').prop('disabled', !$(this).is(':checked'));
    $(this).closest('.content-catalog-product').find('[name^="checkdiscount"]').trigger('change');

    if ($(this).is(':checked') && !$(this).closest('.content-catalog-product').find('[name^="checkdiscount"]').is(':checked')) {
        $(this).closest('.content-catalog-product').find('[name^="maximum_discount"]').prop('disabled', true);
    }
})

$('#store').on('change', function(){
    const store_id = $(this).val();
    $.get(base_url+"catalogProducts/getCatalogsAssociateByStore/<?=$product_catalog['id']?>/"+store_id, response => {
        let readonly = '';
        let sufix_field_name = '';
        let content = '';
        let price_disabled = '';
        let field_price = '';
        const price_ro = "<?=$priceRO?>";

        $('.content_catalogs_associated').empty();

        $(response.catalogs_associated).each(function(key, value) {
            field_price = key == 0 ? field_price = 'name="price"' : '';
            readonly = '';
            sufix_field_name = '';

            content += `<hr class="mb-0">
            <div class="row content-catalog-product">
                <div class="col-md-12 d-flex justify-content-start align-items-end">
                    <h3 class="font-weight-bold">${value.name}</h3>`;
                    if (key > 0) {
                        readonly = 'disabled';
                        sufix_field_name = '_associated[]';
                        content += `<h4 class="ml-5 font-weight-bold"><input type="checkbox" class="enable_catalog" name="enable_catalog${sufix_field_name}"> Deseja vende nesse catálogo?</h4>`;
                    }
            price_disabled = price_ro ? 'disabled' : '';
            content += `</div>
                <input type="hidden" name="catalog_id${sufix_field_name}" value="${value.id}" ${readonly}>

                <div class="form-group col-md-2 col-xs-12">
                      <label for="price"><?=$this->lang->line('application_price');?>(*)</label>
                      <div class="input-group">
                          <span class="input-group-addon"><strong>R$</strong></span>
                          <input type="text" ${field_price} class="form-control maskdecimal2" placeholder="<?=$this->lang->line('application_enter_price');?>" value="${value.price}" autocomplete="off" ${price_disabled}/>
                      </div>
                </div>`;

                if (price_ro) { // não pode alterar o preço então pergunto se quer bloquear a venda de acordo com o descont ?>
                    content += `<div class="form-group col-md-2 col-xs-12 <?php echo (form_error('maximum_discount')) ? "has-error" : ""; ?> ">
                          <div class="input-group col-md-12" id="qty_marketplace" >
                              <input type="checkbox" name="checkdiscount${sufix_field_name}" id="checkdiscount" ${readonly}>

                              &nbsp;<small>
                                  <?=$sellercenter_name=='somaplace'?$this->lang->line('application_inactive_if_this_discount_gs'):$this->lang->line('application_inactive_if_this_discount');?>
                                  <?php if($sellercenter_name=='somaplace'): ?>
                                      <i style="color:red;" class="fa fa-question-circle-o" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="" data-original-title="<?= $this->lang->line('application_inactive_if_this_discount_gs_tooltip')?>" aria-describedby="tooltip232541"></i>
                                  <?php endif ;?>
                              </small>
                              <div class="input-group">
                                  <input type="text" class="form-control maskdecimal2" name="maximum_discount${sufix_field_name}" id="maximum_discount" placeholder="<?=$this->lang->line('application_maximum_discount');?>" autocomplete="off" ${readonly}/>
                                  <span class="input-group-addon"><strong>%</strong></span>
                              </div>
                          </div>
                    </div>`;
                }
                content += `
                <div class="form-group col-md-2 col-xs-12">
                    <label for="status"><?=$this->lang->line('application_active');?>(*)</label>
                    <select class="form-control" name="status${sufix_field_name}" ${readonly}>
                        <option value="1"><?=$this->lang->line('application_yes');?></option>
                        <option value="2"><?=$this->lang->line('application_no');?></option>
                    </select>
                </div>
            </div>`
        });

        $('.content_catalogs_associated').append(content);
        $('[name^="checkdiscount"]').trigger('change');
    });
});

</script>