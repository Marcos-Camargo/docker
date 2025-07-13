
<!--

Criar Produtos de Catálogo

-->

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data); ?>

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

        <div class="box">
          <form  action="<?php base_url('catalogProducts/create') ?>" method="post" enctype="multipart/form-data" id="formInsertProduct">
              <div class="box-body">
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
	       	
	       	 <?php 
	       		$numft = 0;
				$ln1=array();
				$ln2=array();
	       	 	if ($upload_image) { 
					$asset_prefix = "assets/images/catalog_product_image/" . $upload_image . "/";

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
	       	
                <div class="form-group col-md-12 col-xs-12 <?php echo (form_error('product_image')) ? "has-error" : ""; ?>">
                  <label for="product_image"><?=$this->lang->line('application_uploadimages');?>(*):</label>
                  <div class="kv-avatar">
                      <div class="file-loading">
						  <input type="file" id="prd_image" name="prd_image[]" accept="image/*" multiple>
                      </div>
                  </div>
                  <input type="hidden" name="product_image" id="product_image" value="<?php echo set_value('product_image') ?>"  />
                  <?php echo '<i style="color:red">'.form_error('product_image').'</i>'; ?>
                </div>
                
                <div id="EANDIV" class="form-group col-md-3 col-xs-12 <?php echo (form_error('EAN')) ? "has-error" : ""; ?>">
                  <label for="EAN"><?=$label_ean;?></label>
                  <input type="text" class="form-control" id="EAN" name="EAN" onchange="checkEAN(this.value,'EANDIV')" <?=$require_ean?'required':''?> placeholder="<?=$label_ean;?>" value="<?php echo set_value('EAN') ?>" autocomplete="off" />
                  <?php echo '<i style="color:red">'.form_error('EAN').'</i>'; ?>
                  <div id="EANDIVerro" style="display: none;"><i style="color:red"><?=$invalid_ean;?></i></div>
                </div>
                
                <div class="form-group col-md-6 col-xs-12 <?php echo (form_error('name')) ? "has-error" : ""; ?>">
                  <label for="name"><?=$this->lang->line('application_product_name');?>(*)</label>
                  <input type="text" class="form-control" id="name" name="name" required placeholder="<?=$this->lang->line('application_enter_product_name');?>" maxlength="<?= $product_length_name?>" value="<?php echo set_value('name') ?>" autocomplete="off"/>
                  <?php echo '<i style="color:red">'.form_error('name').'</i>'; ?>
                </div>

                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('status')) ? "has-error" : ""; ?>">
	              <label for="status"><?=$this->lang->line('application_availability');?>(*)</label>
	              <select class="form-control" id="status" name="status">
	                <option value="1" <?= ($this->input->post('status',true)==1) ? "selected":""; ?>><?=$this->lang->line('application_yes');?></option>
	                <option value="2" <?= ($this->input->post('status',true)==2) ? "selected":""; ?>><?=$this->lang->line('application_no');?></option>
	              </select>
	              <?php echo '<i style="color:red">'.form_error('status').'</i>'; ?>
                </div>
                
                <div class="form-group col-md-12 col-xs-12 <?php echo (form_error('catalogs[]')) ? "has-error" : ""; ?>">
              		<label for="catalogs" class="normal"><?=$this->lang->line('application_catalogs');?>(*)</label>
             		<select class="form-control selectpicker show-tick" id="catalogs" name ="catalogs[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 2" title="<?=$this->lang->line('application_select');?>">
		                <?php foreach ($catalogs as $catalog) { ?>
		                <option value="<?= $catalog['id'] ?>"  <?php echo set_select('catalogs', $catalog['id'], false); ?> ><?= $catalog['name'] ?></option>
		           		<?php } ?>
              		</select>
              		<?php echo '<i style="color:red">'.form_error('catalogs[]').'</i>'; ?>
         		 </div>
                
                <div class="col-md-12 col-xs-6"><hr></div>
                
                <div class="col-md-12 col-xs-6">
                    <div class="callout callout-danger">
                        <p><?=$this->lang->line('messages_warning_create_catalog_product_variant')?></p>
                    </div>
                </div>
                
                <div class="form-group col-md-12 col-xs-6">
                  <label for="semvar"><?=$this->lang->line('application_variations');?>(*)</label><br>
				  <div class="form-check col-md-2 col-xs-12">
				    <input type="checkbox" class="form-check-input" id="semvar" name="semvar" <?= set_checkbox('semvar', 'on',true)?> >
				    <span><?=$this->lang->line('application_without_variations');?> </span>
				  </div>
				  <div class="form-check col-md-2 col-xs-12">
				    <input type="checkbox" class="form-check-input" id="sizevar" name="sizevar" <?= set_checkbox('sizevar', 'on', false)?> >
				    <span><?=$this->lang->line('application_size');?></span>
				  </div>
				  <div class="form-check col-md-2 col-xs-12">
				    <input type="checkbox" class="form-check-input" id="colorvar" name="colorvar" <?= set_checkbox('colorvar', 'on', false)?> >
				    <span><?=$this->lang->line('application_color');?></span>
				  </div>
				  <div class="form-check col-md-2 col-xs-12">
				    <input type="checkbox" class="form-check-input" id="voltvar" name="voltvar" <?= set_checkbox('voltvar', 'on', false)?> >
				    <span><?=$this->lang->line('application_voltage');?></span>
				  </div>
				  <div class="form-check col-md-2 col-xs-12">
				    <input type="checkbox" class="form-check-input" id="saborvar" name="saborvar" <?= set_checkbox('saborvar', 'on', false)?> >
				    <span><?=$this->lang->line('application_flavor');?></span>
				  </div>
				  <div class="form-check col-md-2 col-xs-12">
				    <input type="checkbox" class="form-check-input" id="grauvar" name="grauvar" <?= set_checkbox('grauvar', 'on', false)?> >
				    <span><?=$this->lang->line('application_degree');?></span>
				  </div>
				  <div class="form-check col-md-2 col-xs-12">
				    <input type="checkbox" class="form-check-input" id="ladovar" name="ladovar" <?= set_checkbox('ladovar', 'on', false)?> >
				    <span><?=$this->lang->line('application_side');?></span>
				  </div>
				</div>
				
                <div class="col-md-12 col-xs-12"><hr></div>
                
                <div class="form-group col-md-12 col-xs-12  <?php echo (form_error('description')) ? 'has-error' : '';  ?>">
                  <label for="description"><?=$this->lang->line('application_description');?>(*)</label>
                  <textarea type="text" class="form-control" id="description" maxlength="<?=  $product_length_description ?>" name="description" placeholder="<?=$this->lang->line('application_enter_description');?>"><?php echo set_value('description') ?></textarea>
				  	<span id="char_description"></span><br />
					<span class="label label-warning" id="words_description" data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_explanation_of_forbidden_words'); ?>"></span>
                  	<?php echo '<i style="color:red">'.form_error('description').'</i>'; ?>
                </div>
                
				<div class="row"></div>
				
				<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('original_price')) ? 'has-error' : '';  ?>">
                  <label for="original_price"><?=$this->lang->line('application_original_price');?></label>
                  <div class="input-group">
                    <span class="input-group-addon"><strong>R$</strong></span>
                    <input type="text" class="form-control maskdecimal2" id="original_price" name="original_price" placeholder="<?=$this->lang->line('application_enter_price');?>" value="<?php echo set_value('original_price') ?>" autocomplete="off" onblur="calcDiscount()" />
                  </div>
                  <?php echo '<i style="color:red">'.form_error('original_price').'</i>'; ?>
                </div>
				
                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('price')) ? 'has-error' : '';  ?>">
                  <label for="price"><?=$this->lang->line('application_price');?>(*)</label>
                  <div class="input-group">
                    <span class="input-group-addon"><strong>R$</strong></span>
                    <input type="text" class="form-control maskdecimal2" id="price" name="price" required placeholder="<?=$this->lang->line('application_enter_price');?>" value="<?php echo set_value('price') ?>" autocomplete="off" onblur="calcDiscount()" />
                  </div>
                  <?php echo '<i style="color:red">'.form_error('price').'</i>'; ?>
                </div>
                
                <div class="form-group col-md-2 col-xs-12 ">
                  <label for="discount"><?=$this->lang->line('application_discount');?></label>
                  <div class="input-group">
                  	<input type="text" class="form-control" id="discount" name="discount" readonly value="" />
                  	<span class="input-group-addon"><strong>%</strong></span>
                  </div>
                </div>
                 
                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('brand_code')) ? 'has-error' : '';  ?>">
                  <label for="brand_code"><?=$this->lang->line('application_brandcode');?></label>
                  <input type="text" class="form-control" id="brand_code" name="brand_code" placeholder="<?=$this->lang->line('application_enter_manufacturer_code');?>" value="<?php echo set_value('brand_code') ?>" autocomplete="off" />
               	  <?php echo '<i style="color:red">'.form_error('brand_code').'</i>'; ?>
                </div>
                
                <div class="row"></div>
                <div class="panel panel-primary">
					<div class="panel-heading"><?=$this->lang->line('application_packaged_product_dimensions');?> &nbsp 
						<span class="h6"> <?=$this->lang->line('application_packaged_product_dimensions_explain');?>)</span>
					</div>
					<div class="panel-body">
		                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('gross_weight')) ? 'has-error' : '';  ?>">
		                  <label for="gross_weight"><?=$this->lang->line('application_weight');?>(*)</label>
		                  <div class="input-group">
		                    <input type="text" class="form-control maskdecimal3" id="gross_weight" name="gross_weight" required  placeholder="<?=$this->lang->line('application_enter_gross_weight');?>" value="<?php echo set_value('gross_weight') ?>" autocomplete="off"/>
		                    <span class="input-group-addon"><strong>Kg</strong></span>
		                  </div>
		                  <?php echo '<i style="color:red">'.form_error('gross_weight').'</i>'; ?>
		                </div>
		                
		                <div class="form-group col-md-2 col-xs-12 <?php echo (form_error('width')) ? 'has-error' : '';  ?>">
		                  <label for="width"><?=$this->lang->line('application_width');?> (*)</label>
		                  <div class="input-group">
		                    <input type="text" class="form-control maskdecimal2" id="width" name="width" required placeholder="<?=$this->lang->line('application_enter_width');?>" value="<?php echo set_value('width') ?>" autocomplete="off"  onKeyPress="return digitos(event, this);" />
		                    <span class="input-group-addon"><strong>cm</strong></span>
		                  </div>
		                  <?php echo '<i style="color:red">'.form_error('width').'</i>'; ?>
		                </div>
		                
		                <div class="form-group col-md-2 col-xs-12 <?php echo (form_error('height')) ? 'has-error' : '';  ?>">
		                  <label for="height"><?=$this->lang->line('application_height');?> (*)</label>
		                  <div class="input-group">
		                    <input type="text" class="form-control maskdecimal2" id="height" name="height" required  placeholder="<?=$this->lang->line('application_enter_height');?>" value="<?php echo set_value('height') ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
		                    <span class="input-group-addon"><strong>cm</strong></span>
		                  </div>
		                  <?php echo '<i style="color:red">'.form_error('height').'</i>'; ?>
		                </div>
              
		                <div class="form-group col-md-2 col-xs-12 <?php echo (form_error('length')) ? 'has-error' : '';  ?>">
		                  <label for="length"><?=$this->lang->line('application_depth');?> (*)</label>
		                  <div class="input-group">
		                    <input type="text" class="form-control maskdecimal2" id="length" name="length" required placeholder="<?=$this->lang->line('application_enter_depth');?>" value="<?php echo set_value('length') ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
		                    <span class="input-group-addon"><strong>cm</strong></span>
		                  </div>
		                	<?php echo '<i style="color:red">'.form_error('length').'</i>'; ?>
		                </div>

                        <div class="form-group col-md-3 col-xs-12">
                            <label for="products_package" data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_how_many_units'); ?>"><?= $this->lang->line('application_products_by_packaging'); ?>(*)</label>
                            <div class="input-group">
                                <input type="text" class="form-control maskdecimal3" id="products_package" name="products_package" required placeholder="<?= $this->lang->line('application_enter_quantity_products'); ?>" value="<?php echo set_value('products_package', '1') ?>" autocomplete="off" />
                                <span class="input-group-addon"><strong>Qtd</strong></span>
                            </div>
                        </div>
                	</div>
				</div>

				<!-- dimensões do produto fora da embalagem -->
				<div class="panel panel-primary">
					<div class="panel-heading"><?=$this->lang->line('application_product_dimensions');?> &nbsp 
						<span class="h6"> <?=$this->lang->line('application_out_of_the_package');?></span>
					</div>
					<div class="panel-body">
						<div class="form-group col-md-3 col-xs-12">
							<label for="peso_liquido"><?=$this->lang->line('application_net_weight');?>(*)</label>
							<div class="input-group">
								<input type="text" class="form-control maskdecimal3" id="peso_liquido" required name="peso_liquido" placeholder="<?=$this->lang->line('application_enter_net_weight');?>" value="<?php echo set_value('peso_liquido') ?>" autocomplete="off"/>
								<span class="input-group-addon"><strong>Kg</strong></span>
							</div>
						</div>
						<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('actual_width')) ? 'has-error' : '';  ?>">
							<label for="actual_width"><?=$this->lang->line('application_width');?></label>
							<div class="input-group">
								<input type="text" class="form-control maskdecimal2" id="actual_width" name="actual_width" placeholder="<?=$this->lang->line('application_enter_actual_width');?>" value="<?php echo set_value('actual_width') ?>" autocomplete="off"  onKeyPress="return digitos(event, this);" />
								<span class="input-group-addon"><strong>cm</strong></span>
							</div>
								<?php echo '<i style="color:red">'.form_error('actual_width').'</i>'; ?>
						</div>
						<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('actual_height')) ? 'has-error' : '';  ?>">
							<label for="actual_height"><?=$this->lang->line('application_height');?></label>
							<div class="input-group">
								<input type="text" class="form-control maskdecimal2" id="actual_height" name="actual_height" placeholder="<?=$this->lang->line('application_enter_actual_height');?>" value="<?php echo set_value('actual_height') ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
								<span class="input-group-addon"><strong>cm</strong></span>
							</div>
								<?php echo '<i style="color:red">'.form_error('actual_height').'</i>'; ?>
						</div>
						<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('actual_depth')) ? 'has-error' : '';  ?>">
							<label for="actual_depth"><?=$this->lang->line('application_depth');?></label>
							<div class="input-group">
								<input type="text" class="form-control maskdecimal2" id="actual_depth" name="actual_depth" placeholder="<?=$this->lang->line('application_enter_actual_depth');?>" value="<?php echo set_value('actual_depth') ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
								<span class="input-group-addon"><strong>cm</strong></span>
							</div>
							<?php echo '<i style="color:red">'.form_error('actual_depth').'</i>'; ?>
						</div>
					</div>
				</div>
				<!-- fim das dimensões do produto fora da embalagem -->
                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('warranty')) ? 'has-error' : '';  ?>">
                  <label for="garantia"><?=$this->lang->line('application_garanty');?>(* <?=$this->lang->line('application_in_months');?>)</label>
                  <input type="text" class="form-control" id="warranty" name="warranty" required placeholder="<?=$this->lang->line('application_enter_warranty');?>" value="<?php echo set_value('warranty') ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
                  <?php echo '<i style="color:red">'.form_error('warranty').'</i>'; ?>
                </div>
                
                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('NCM')) ? 'has-error' : '';  ?>">
                  <label for="NCM"><?=$this->lang->line('application_NCM');?></label>
                  <input type="text" class="form-control" id="NCM" name="NCM" placeholder="<?=$this->lang->line('application_enter_NCM');?>" value="<?php echo set_value('NCM'); ?>" maxlength="10" size="10" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('NCM',this,event);"  autocomplete="off" />
                  <?php echo '<i style="color:red">'.form_error('NCM').'</i>'; ?>
                </div>
                
                  <?php $att = $this->input->post('attributes_value_id',true);
                  $i = 0;
                  if($attributes): ?>
                      <?php foreach ($attributes as $k => $v): ?>
                          <div class="form-group col-md-3 col-xs-12">
                              <label for="groups"><?php echo $v['attribute_data']['name'] ?>(*)</label>
                              <select class="form-control select_group" id="attributes_value_id" name="attributes_value_id[]" >
                                  <?php foreach ($v['attribute_value'] as $k2 => $v2): ?>
                                      <option value="<?php echo $v2['id'] ?>" <?=set_select('attributes_value_id', $v2['id'])?>><?php echo $v2['value'] ?></option>
                                  <?php endforeach ?>
                              </select>
                          </div>
                          <?php
                          $i++;
                      endforeach ?>
                  <?php endif; ?>
                  
                <div class="form-group col-md-12 col-xs-12 <?php echo (form_error('origin')) ? 'has-error' : '';  ?>">
                  <label for="origin"><?=$this->lang->line('application_origin_product');?>(*)</label>
                  <select class="form-control" name="origin" id="origin" required>
                      <?php foreach ($origins as $key => $origin)
                          echo "<option value='{$key}' ".set_select('origin', $key).">{$origin}</option>";
                      ?>
                  </select>
                  <?php echo '<i style="color:red">'.form_error('origin').'</i>'; ?>
                </div>

                <div class="form-group col-md-5 col-xs-12 <?php echo (form_error('brands')) ? 'has-error' : '';  ?>">
                  <label for="brands" class="d-flex justify-content-between">
                      <?=$this->lang->line('application_brands');?>(*)
                      <a href="#" onclick="AddBrand(event)" ><i class="fa fa-plus-circle"></i> <?=$this->lang->line('application_add_brand');?></a>
                  </label>
                  <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="brands" name="brands" title="<?=$this->lang->line('application_select');?>" >
                    <?php foreach ($brands as $k => $v): ?>
                      <option value="<?php echo $v['id'] ?>" <?php echo set_select('brands', $v['id'], false); ?> ><?php echo $v['name'] ?></option>
                    <?php endforeach ?>
                  </select>
                  <?php echo '<i style="color:red">'.form_error('brands').'</i>'; ?>
				</div>
                
                <div class="form-group col-md-12 col-xs-12 <?php echo (form_error('category')) ? 'has-error' : '';  ?>">
                  <label for="category"><?=$this->lang->line('application_categories');?>(*)</label> 
                  <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="category" name="category" title="<?=$this->lang->line('application_select');?>" >
                    <?php foreach ($category as $k => $v): ?>
                      <option value="<?php echo $v['id'] ?>" <?php echo set_select('category', $v['id'], false); ?> ><?php echo $v['name'] ?></option>
                    <?php endforeach ?>
                  </select>
                  <?php echo '<i style="color:red">'.form_error('category').'</i>'; ?>
                </div>
                
                <div id='linkcategory'></div>

		  	<!-- Variants DIV -->
			<div id="variantModal" class="col-md-12 col-xs-12" style="display:none;">
                <input type="hidden" name="numvar" id="numvar" value="<?php echo set_value('numvar','1') ?>" />
                <h4 class="mb-3"><?=$this->lang->line('application_variantproperties');?></span></h4>
                <input type="hidden" name="from" value="allocate" />
                <div class="row">
                    <div id="Ltvar" class="col-md-2">
                        <label><?=$this->lang->line('application_size');?></label>
                    </div>
                    <div id="Lcvar" class="col-md-2">
                        <label><?=$this->lang->line('application_color');?></label>
                    </div>
                    <div id="Lvvar" class="col-md-2">
                        <label><?=$this->lang->line('application_voltage');?></label>
                    </div>
					<div id="Lsvar" class="col-md-2">
                        <label><?=$this->lang->line('application_flavor');?></label>
                    </div>
					<div id="Lgvar" class="col-md-2">
                        <label><?=$this->lang->line('application_degree');?></label>
                    </div>
					<div id="Llvar" class="col-md-2">
                        <label><?=$this->lang->line('application_side');?></label>
                    </div>
                    <div id="Leanvar" class="col-md-2">
                        <label><?=$label_ean;?></label>
                    </div>
                </div>
                <div class="row">
                    <div class="Itvar form-group col-md-2">
                        <input type="text" class="form-control" id="T[]" name="T[]" autocomplete="off" placeholder="Tamanho" autocomplete="off" value="<?php echo set_value('T[0]') ?>" />
                    </div>
                    <div class="Icvar form-group col-md-2">
                        <input type="text" class="form-control" id="C[]" name="C[]" autocomplete="off" placeholder="<?=$this->lang->line('application_color')?>"  value="<?php echo set_value('C[0]') ?>"/>
                    </div>
                    <div class="Ivvar form-group col-md-2">
                        <select class="form-control" id="V[]" name="V[]">
                            <option value="110V" <?php echo set_select('V[0]', '110V') ?>>110V</option>
                            <option value="220V" <?php echo set_select('V[0]', '220V') ?>>220V</option>
                        </select>
                    </div>
					<div class="Icvar form-group col-md-2">
                        <input type="text" class="form-control" id="sb[]" name="sb[]" autocomplete="off" placeholder="<?=$this->lang->line('application_flavor')?>"  value="<?php echo set_value('sb[0]') ?>"/>
                    </div>
					<div class="Igvar form-group col-md-2">
                        <input type="text" class="form-control" id="gr[]" name="gr[]" autocomplete="off" placeholder="<?=$this->lang->line('application_degree')?>"  value="<?php echo set_value('gr[0]') ?>"/>
                    </div>
					<div class="Ilvar form-group col-md-2">
                        <input type="text" class="form-control" id="ld[]" name="ld[]" autocomplete="off" placeholder="<?=$this->lang->line('application_side')?>"  value="<?php echo set_value('ld[0]') ?>"/>
                    </div>
                    <div id= "EANV0" class="Ieanvar form-group col-md-2">
                        <input type="text" class="form-control" id="EAN_V[]" autocomplete="off" value="<?php echo set_value('EAN_V[0]') ?>" name="EAN_V[]" onchange="checkEAN(this.value,'EANV0')" placeholder="<?=$label_ean;?>"  />
                    	<span id="EANV0erro" style="display: none;"><i style="color:red"><?=$invalid_ean;?></i></span>
                    </div>
                    <div  class="form-group col-md-2">
                    	<input type="hidden" id="IMAGEM0" name="IMAGEM[]" value="<?php echo set_value('IMAGEM[0]',$imagemvariant0 ) ?>" />
                     	<a href="#" onclick="AddImage(event,'0')"><img id="foto_variant0" src="<?php echo base_url('assets/images/system/sem_foto.png');?>"  class="img-rounded" width="50" height="50" /><i class="fa fa-plus-circle"></i></a>
                    </div>
                </div>
                <div class="input_fields_wrap">

                </div>
                <div class="form-group col-md-12 text-center">
                 <!---   <small class="text-danger"><strong><?=$this->lang->line('messages_warning_create_ean_variant')?></strong></small>   -->
                </div>
                <div class="row">
                  <div class="col-md-12 text-center">
                    <button type="button" class="btn btn-primary add_line"><i class="fa fa-plus-square-o"></i> <?=$this->lang->line('application_variation_add');?></button>
                    <button type="button" class="btn btn-warning" id="reset_variant" name="reset_variant"><i class="fa fa-trash"></i> <?=$this->lang->line('application_clear');?></button>
                  </div>
                </div>
			</div>

		  </div>
          <!-- /.box-body -->
          <div class="box-footer">
            <button type="submit" id="letsave" class="btn btn-primary col-md-2"><?=$this->lang->line('application_save');?></button>
            <a href="<?php echo base_url('productscatalog/') ?>" class="btn btn-warning col-md-2"><?=$this->lang->line('application_back');?></a>
          </div>
          </form>
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

<div class="modal fade" tabindex="-1" role="dialog" id="addBrandModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_add_brand');?></h4>
      </div>

      <form role="form" action="<?php echo base_url('brands/create') ?>" method="post" id="createBrandForm">

        <div class="modal-body">

          <div class="form-group">
            <label for="brand_name"><?=$this->lang->line('application_name');?></label>
            <input type="text" class="form-control" id="brand_name" name="brand_name" placeholder="<?=$this->lang->line('application_enter_brand_name')?>" autocomplete="off">
          </div>
          <input type="hidden" id="active" name="active" value="1" />
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
        </div>

      </form>

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->



<div class="modal fade" tabindex="-1" role="dialog" id="addImagemModal">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_uploadimages');?></h4>
      </div>
      <div class="modal-body">

          <div class="form-group col-md-12 col-xs-12">
              <label for="product_image_variant"><?=$this->lang->line('application_uploadimages');?>(*):</label>
              <div class="kv-avatar">
              	<div class="imagem_variant_wrap">
              		<div id="showimage0" style="display:none">
	                  <div class="file-loading">
						  <input type="file" id="prd_image_variant0" name="prd_image_variant0[]" accept="image/*" multiple>
	                  </div>
					</div>
                </div>
              </div>
              <input type="hidden" id="variant_num" name="variant_num" value="0" />
          </div>

      </div>

      <div class="modal-footer">
        	<a href="#" onclick="UpdateVariantImage(event)" class="btn btn-default" ><?=$this->lang->line('application_close');?></a>            
      </div>

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script type="text/javascript">
var base_url = "<?php echo base_url(); ?>";
var update_category = "<?php echo in_array('updateCategory', $this->permission) ?>";
var upload_url ="<?= base_url('/Products/saveImageProduct'); ?>";
var delete_url = "<?= base_url('Products/removeImageProduct'); ?>";
var fileinputvariant ;
var verify_ean = "<?= $verify_ean ?>";
var label_ean = "<?= $label_ean; ?>";
	function characterLimit(object) {
		var limit = object.getAttribute('maxlength');
		var attribute = object.getAttribute('id');

		if (attribute == 'description') {
			// var quantity = $(".note-editable").text().length;
			var quantity = $(".note-editable").html().length;
		} else {
			var quantity = object.value.length;
		}

		$('#char_' + attribute).text(`Caracteres digitados: ${quantity}/${limit}`);
	}
	const verifyWords = () => {
		const brand = $('#brands').val();
		const category = $('#category').val();
		const store = $('#store').val();
		const sku = $('#sku').val();
		const product = parseInt(window.location.pathname.split('/').pop());
		const name = $('#product_name').val();
		const description = $('.note-editable').html();

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
  $(document).ready(function() {

     var wrapper   		= $(".input_fields_wrap"); //Fields wrapper
    
     var varn = $("#numvar").val();
     if (varn > 1) {
     	if($("#sizevar").prop("checked") == true) {
     		var tamanhos = <?php echo json_encode($variacaotamanho); ?>;
     	}
     	if($("#colorvar").prop("checked") == true) {
     		var cores = <?php echo json_encode($variacaocor); ?>;
     	}
     	if($("#voltvar").prop("checked") == true){
     		var voltagem = <?php echo json_encode($variacaovoltagem); ?>;
     	}
		if($("#saborvar").prop("checked") == true){
     		var sabor = <?php echo json_encode($variacaosabor); ?>;
     	}
		 if($("#grauvar").prop("checked") == true){
     		var grau = <?php echo json_encode($variacaograu); ?>;
     	}
		 if($("#ladovar").prop("checked") == true){
     		var lado = <?php echo json_encode($variacaolado); ?>;
     	}
     	var eans = <?php echo json_encode($variacaoean); ?>;
     	var imagens = <?php echo json_encode($variacaoimagem); ?>;
     	var i;
     	checkEAN(eans[0],'EANV0')
     	for (i=1; i<varn; i++) {
     		
     		var linha = '<div class="row" id="variant'+varn+'">';
     		if($("#sizevar").prop("checked") == true){	
     			if (typeof tamanhos[i] === 'undefined') {
     			 	break;
     			}
				linha = linha + '<div class="Itvar form-group col-md-2"><input type="text" required class="form-control" id="T[]" name="T[]" autocomplete="off" placeholder="Tamanho" value="'+tamanhos[i] +'" /></div>';
			}
			else {
				linha = linha + '<div class="Itvar form-group col-md-2" style="display: none;"><input type="text" class="form-control" id="T[]" name="T[]" autocomplete="off" placeholder="Tamanho" /></div>';
			}
			if($("#colorvar").prop("checked") == true){
				if (typeof cores[i] === 'undefined') {
     			 	break;
     			}
				linha = linha + '<div class="Icvar form-group col-md-2"><input type="text" required class="form-control" id="C[]" name="C[]" autocomplete="off" placeholder="<?=$this->lang->line('application_color')?>" value="'+cores[i] +'" /></div>';
			} 
			else {
				linha = linha + '<div class="Icvar form-group col-md-2" style="display: none;"><input type="text" class="form-control" id="C[]" name="C[]" autocomplete="off" placeholder="<?=$this->lang->line('application_color')?>" /></div>';
			}
			if($("#voltvar").prop("checked") == true){
				if (typeof voltagem[i] === 'undefined') {
     			 	break;
     			}
				 var sel110 = '';
				 var sel220 = '';
				 if (voltagem[i] == '110V') {
				 	var sel110 = ' selected ';
				 } 
				 else {
				 	var sel220 = ' selected ';
				 }
				 linha = linha + '<div class="Ivvar form-group col-md-2"><select class="form-control" id="V[]" name="V[]"><option value="110V" '+sel110+'>110V</option><option value="220V" '+sel220+'>220V</option></select></div>';
			}
			else {
				linha = linha + '<div class="Ivvar form-group col-md-2" style="display: none;"><select class="form-control" id="V[]" name="V[]"><option value="110V">110V</option><option value="220V">220V</option></select></div>';
			}

			if($("#saborvar").prop("checked") == true){
				if (typeof sabor[i] === 'undefined') {
     			 	break;
     			}
				linha = linha + '<div class="Isvar form-group col-md-2"><input type="text" required class="form-control" id="sb[]" name="sb[]" autocomplete="off" placeholder="<?=$this->lang->line('application_flavor')?>" value="'+sabor[i] +'" /></div>';
			} 
			else {
				linha = linha + '<div class="Isvar form-group col-md-2" style="display: none;"><input type="text" class="form-control" id="sb[]" name="sb[]" autocomplete="off" placeholder="<?=$this->lang->line('application_flavor')?>" /></div>';
			}



			if($("#grauvar").prop("checked") == true){
				if (typeof grau[i] === 'undefined') {
     			 	break;
     			}
				linha = linha + '<div class="Igvar form-group col-md-2"><input type="text" required class="form-control" id="gr[]" name="gr[]" autocomplete="off" placeholder="<?=$this->lang->line('application_degree')?>" value="'+grau[i] +'" /></div>';
			} 
			else {
				linha = linha + '<div class="Igvar form-group col-md-2" style="display: none;"><input type="text" class="form-control" id="gr[]" name="gr[]" autocomplete="off" placeholder="<?=$this->lang->line('application_degree')?>" /></div>';
			}

			if($("#ladovar").prop("checked") == true){
				if (typeof lado[i] === 'undefined') {
     			 	break;
     			}
				linha = linha + '<div class="Ilvar form-group col-md-2"><input type="text" required class="form-control" id="ld[]" name="ld[]" autocomplete="off" placeholder="<?=$this->lang->line('application_side')?>" value="'+lado[i] +'" /></div>';
			} 
			else {
				linha = linha + '<div class="Ilvar form-group col-md-2" style="display: none;"><input type="text" class="form-control" id="ld[]" name="ld[]" autocomplete="off" placeholder="<?=$this->lang->line('application_side')?>" /></div>';
			}
            linha = linha + '<div id="EANV'+i+'" class="Ieanvar form-group col-md-2"><input type="text" class="form-control" onchange="checkEAN(this.value,\'EANV'+i+'\')" id="EAN_V[]" name="EAN_V[]" autocomplete="off" placeholder="<?=$label_ean?>" value="'+eans[i] +'" /><span id="EANV'+i+'erro" style="display: none;"><i style="color:red"><?=$invalid_ean;?></i></span></div>';
			checkEAN(eans[i],'EANV'+i);
			
			linha = linha + '<div class="form-group col-md-2">'; 
			linha = linha + '<input type="hidden" id="IMAGEM'+i+'" name="IMAGEM[]" value="'+imagens[i]+'" />';
			linha = linha + '<a href="#" onclick="AddImage(event,\''+i+'\')" >';
	        linha = linha + '<img id="foto_variant'+i+'" src="'+base_url+'assets/images/system/sem_foto.png'+'" class="img-rounded" width="50" height="50" />';
			linha = linha + '<i class="fa fa-plus-circle"></i></a>';
	        linha = linha + '</div>';
	        
	        linha = linha + '<div class="form-group col-md-1"><button type="button" onclick="RemoveVariant(event,'+i+')" class="btn btn-default"><i class="fa fa-trash"></i></button></div>';
			
	        linha = linha + '</div>';
			
			$(wrapper).append(linha);
			
			var wrapperimagem = $(".imagem_variant_wrap"); 
			var lin_image ='<div id="showimage'+i+'" style="display:none"><div class="file-loading"><input type="file" id="prd_image_variant'+i+'" name="prd_image_variant'+i+'[]" accept="image/*" multiple></div></div>'
			wrapperimagem.append(lin_image);
			//console.log(lin_image);
		    initImage(i);
		
		}
     	
     }
    
    $('.maskdecimal2').inputmask({
		  alias: 'numeric', 
		  allowMinus: false,  
		  digits: 2, 
		  max: 999999999999.99
		});
    
    $('.maskdecimal3').inputmask({
		  alias: 'numeric', 
		  allowMinus: false,  
		  digits: 3, 
		  max: 9999999999.999
		});

	$('#category').change(function() {
		var idcat = $('#category option:selected').val();
		changeCategory(idcat);
	});
      $("#description").summernote({
          toolbar: [
              // [groupName, [list of button]]
              ['style', ['bold', 'italic', 'underline', 'clear']],
              ['view', ['fullscreen', 'codeview']]
          ],
          height: 150,
          disableDragAndDrop : true,
          lang: 'pt-BR',
          shortcuts: false,
		  callbacks: {
				onBlur: function(e) {
					verifyWords();
				},
				onKeyup: function(e) {
					// var conteudo = $(".note-editable").text();
					var conteudo = $(".note-editable").html();
					var limit = $('#description').attr('maxlength');
					if (conteudo.length > limit) {
						// $(".note-editable").text(conteudo.slice(0,-(conteudo.length-limit)));
						$(".note-editable").html(conteudo.slice(0, -(conteudo.length - limit)));
					}
					characterLimit(this);
				}
			}
      });
	// $('#varprop').hide();
	// Controling Variants Options
	
	$('#semvar').change(function() {
	  	$('[id="T[]"').attr("required",false);
		$('[id="C[]"').attr("required",false);
	    $('#sizevar').prop('checked', false);
	    $('#colorvar').prop('checked', false);
	    $('#voltvar').prop('checked', false);
		$('#saborvar').prop('checked', false);
		$('#grauvar').prop('checked', false);
		$('#ladovar').prop('checked', false);
		$("#variantModal").hide();
		$.fn.variantsclear();
		$('#qty').attr("disabled",false);
		$('#qty').attr("required",true);
		$('#qty_marketplace').show();
	  });
	  $('#sizevar').change(function() {
	    $('#semvar').prop('checked', false);
	    $.fn.variants();
	  });
	  $('#colorvar').change(function() {
	    $('#semvar').prop('checked', false);
	    $.fn.variants();
	  });
	  $('#voltvar').change(function() {
	    $('#semvar').prop('checked', false);
	    $.fn.variants();
	  });
	  $('#saborvar').change(function() {
	    $('#semvar').prop('checked', false);
	    $.fn.variants();
	  });
	  $('#grauvar').change(function() {
	    $('#semvar').prop('checked', false);
	    $.fn.variants();
	  });
	  $('#ladovar').change(function() {
	    $('#semvar').prop('checked', false);
	    $.fn.variants();
	  });
	  
	$.fn.variants = function(){
		$("#variantModal").show();
		var num = 0;
		$('[id="T[]"').attr("required",$("#sizevar").prop("checked"));
		$('[id="C[]"').attr("required",$("#colorvar").prop("checked"));
		$('[id="sb[]"').attr("required",$("#saborvar").prop("checked"));
		$('[id="gr[]"').attr("required",$("#grauvar").prop("checked"));
		$('[id="ld[]"').attr("required",$("#ladovar").prop("checked"));
		if($("#sizevar").prop("checked") == false){	
		    $('#Ltvar').hide();
		    $('.Itvar').hide();
		    num++;
		}    
		if($("#sizevar").prop("checked") == true){	
		    $('#Ltvar').show();
		    $('.Itvar').show();
		}    
		if($("#colorvar").prop("checked") == false){	
		    $('#Lcvar').hide();
		    $('.Icvar').hide();
		    num++;
		}    
		if($("#colorvar").prop("checked") == true){	
		    $('#Lcvar').show();
		    $('.Icvar').show();
		}    
		if($("#voltvar").prop("checked") == false){	
		    $('#Lvvar').hide();
		    $('.Ivvar').hide();
		    num++;
		}    
		if($("#voltvar").prop("checked") == true){	
		    $('#Lvvar').show();
		    $('.Ivvar').show();
		}
		if($("#saborvar").prop("checked") == false){	
		    $('#Lsvar').hide();
		    $('.Isvar').hide();
		    num++;
		}    
		if($("#saborvar").prop("checked") == true){	
			console.log('aqui');
		    $('#Lsvar').show();
		    $('.Isvar').show();
		}
		if($("#grauvar").prop("checked") == false){	
		    $('#Lgvar').hide();
		    $('.Igvar').hide();
		    num++;
		}    
		if($("#grauvar").prop("checked") == true){	
			console.log('aqui');
		    $('#Lgvar').show();
		    $('.Igvar').show();
		}
		if($("#ladovar").prop("checked") == false){	
		    $('#Llvar').hide();
		    $('.Ilvar').hide();
		    num++;
		}    
		if($("#ladovar").prop("checked") == true){	
		    $('#Llvar').show();
		    $('.Ilvar').show();
		}    
		if (num==5) {
			$('#qty').attr("disabled",false);
			$('#qty').attr("required",true);
			$('#semvar').prop('checked', true);
			$("#variantModal").hide();
			$.fn.variantsclear();
			$('#qty_marketplace').show();
		}
		else {
			$('#qty').attr("disabled",true);
			$('#qty').attr("required",false);
			$('.sameqtychk').prop('checked', true);
			$('.sameqtyval').prop('disabled', true);
			$('.sameqtyval').prop('required', true);
			$('#qty').attr("disabled",true);
		  $('#qty_marketplace').hide();
		}
	}

	initImage(0);  // inicializa a primeira imagem com variação
	
	var add_line = $(".add_line"); //Add button ID

	$(add_line).click(function(e){ //on add input button click
		e.preventDefault();
		varn++;
		var linha = '<div class="row" id="variant'+varn+'">';
        let check_size = '';
        let check_color = '';
        let check_volt = '';
		let check_sabor = '';
		let check_grau = '';
		let check_lado = '';
        let show_size = '';
        let show_color = '';
        let show_volt = '';
		let show_sabor = '';
		let show_grau = '';
		let show_lado = '';

        $("#sizevar").prop("checked") == true  ? check_size  = 'checked' : show_size = 'style="display: none"';
        $("#colorvar").prop("checked") == true ? check_color = 'checked' : show_color = 'style="display: none"';
        $("#voltvar").prop("checked") == true  ? check_volt  = 'checked' : show_volt = 'style="display: none"';
		$("#saborvar").prop("checked") == true  ? check_sabor  = 'checked' : show_sabor = 'style="display: none"';
		$("#grauvar").prop("checked") == true  ? check_grau  = 'checked' : show_grau = 'style="display: none"';
		$("#ladovar").prop("checked") == true  ? check_lado  = 'checked' : show_lado = 'style="display: none"';

		$("#sizevar").prop("checked") == true  ? req_size  = 'required' : req_size  = '';
        $("#colorvar").prop("checked") == true ? req_color = 'required' : req_color = '';
        $("#voltvar").prop("checked") == true  ? req_volt  = 'required' : req_volt  = '';
		$("#saborvar").prop("checked") == true  ? req_sabor = 'required' : req_sabor  = '';
		$("#grauvar").prop("checked") == true  ? req_grau  = 'required' : req_grau  = '';
		$("#ladovar").prop("checked") == true  ? req_lado  = 'required' : req_lado  = '';

	    linha = linha + '<div class="Itvar form-group col-md-2" '+show_size+'><input type="text" '+check_size+' '+req_size+' class="form-control" id="T[]" name="T[]" autocomplete="off" placeholder="Tamanho"  /></div>';
        linha = linha + '<div class="Icvar form-group col-md-2" '+show_color+'><input type="text" '+check_color+' '+req_color+' class="form-control" id="C[]" name="C[]" autocomplete="off" placeholder="<?=$this->lang->line('application_color')?>"  /></div>';
        linha = linha + '<div class="Ivvar form-group col-md-2" '+show_volt+'><select '+check_volt+' '+req_volt+' class="form-control" id="V[]" name="V[]"><option value="110V">110V</option><option value="220V">220V</option></select></div>';

		linha = linha + '<div class="Isvar form-group col-md-2" '+show_sabor+'><input type="text" '+check_sabor+' '+req_sabor+' class="form-control" id="sb[]" name="sb[]" autocomplete="off" placeholder="<?=$this->lang->line('application_flavor')?>"  /></div>';
		linha = linha + '<div class="Igvar form-group col-md-2" '+show_grau+'><input type="text" '+check_grau+' '+req_grau+' class="form-control" id="gr[]" name="gr[]" autocomplete="off" placeholder="<?=$this->lang->line('application_degree')?>"  /></div>';
		linha = linha + '<div class="Ilvar form-group col-md-2" '+show_lado+'><input type="text" '+check_lado+' '+req_lado+' class="form-control" id="ld[]" name="ld[]" autocomplete="off" placeholder="<?=$this->lang->line('application_side')?>"  /></div>';

	    linha = linha + '<div id="EANV'+varn+'" class="Ieanvar form-group col-md-2"><input type="text" class="form-control" onchange="checkEAN(this.value,\'EANV'+varn+'\')" id="EAN_V[]" name="EAN_V[]" autocomplete="off" placeholder="<?=$label_ean?>" /><span id="EANV'+varn+'erro" style="display: none;"><i style="color:red"><?=$invalid_ean;?></i></span></div>';
			
		linha = linha + '<div  class="form-group col-md-2">'; 
		var charSet =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	    var randomString = '';
	    for (var i = 0; i < 15; i++) {
	        var randomPoz = Math.floor(Math.random() * charSet.length);
	        randomString += charSet.substring(randomPoz,randomPoz+1);
	    }
		linha = linha + '<input type="hidden" id="IMAGEM'+varn+'" name="IMAGEM[]" value="'+randomString+'" />';
		linha = linha + '<a href="#" onclick="AddImage(event,\''+varn+'\')" >';
		linha = linha + '<img id="foto_variant'+varn+'" src="'+base_url+'assets/images/system/sem_foto.png'+'"  class="img-rounded" width="50" height="50" />';
		linha = linha + '<i class="fa fa-plus-circle"></i></a>';
        linha = linha + '</div>';
        
        linha = linha + '<div class="form-group col-md-1"><button type="button" onclick="RemoveVariant(event,'+varn+')" class="btn btn-default"><i class="fa fa-trash"></i></button></div>';
		
        linha = linha + '</div>';
		$(wrapper).append(linha);
		
		var wrapperimagem = $(".imagem_variant_wrap"); 
		var lin_image ='<div id="showimage'+varn+'" style="display:none"><div class="file-loading"><input type="file" id="prd_image_variant'+varn+'" name="prd_image_variant'+varn+'[]" accept="image/*" multiple></div></div>'
		wrapperimagem.append(lin_image);
		//console.log(lin_image);
		initImage(varn);
		
		$('#numvar').val(varn);
	});
	
	$(reset_variant).click(function(e){ //on clear button click
		e.preventDefault(); 
		$.fn.variantsclear();
	});	

	$.fn.variantsclear = function(){
		for (i = 1; i <= varn; i++) { 
			div = 'div #variant'+i;
			$(div).remove();
			
			$('#showimage'+i).remove();
		}		
		varn = 1;
		$('#numvar').val(varn);
	}
    $("#mainCatalogNav").addClass('active');
    $("#addProductCatalogNav").addClass('active');
    
	var token = '<?= $prdtoken; ?>'; // My Token
	$("#prd_image").fileinput({
        uploadUrl: "<?= base_url('/Products/saveImageProduct'); ?>",
        language: 'pt-BR',
        autoOrientImage: false,
        allowedFileExtensions: ["jpg", "png"],
        minImageWidth: <?=$dimenssion_min_product_image ?? 'null'?>,
        minImageHeight: <?=$dimenssion_min_product_image ?? 'null'?>,
        maxImageWidth: <?=$dimenssion_max_product_image ?? 'null'?>,
        maxImageHeight: <?=$dimenssion_max_product_image ?? 'null'?>,
        enableResumableUpload: true,
        resumableUploadOptions: {
           // uncomment below if you wish to test the file for previous partial uploaded chunks
           // to the server and resume uploads from that point afterwards
           // testUrl: "http://localhost/test-upload.php"
        },
        uploadExtraData: {
	        'uploadToken': token, // for access control / security 
			'onBucket':1
        },
        maxFileCount: 5,
        allowedFileTypes: ['image'],    // allow only images
        showCancel: true,
        initialPreviewAsData: true,
        overwriteInitial: false,
        // initialPreview: [],          // if you have previously uploaded preview files
        // initialPreviewConfig: [],    // if you have previously uploaded preview files
        initialPreview: [
	    <?php
		    for ($i = 1; $i <= $numft; $i++) {
			   echo '"' . $ln1[$i]. '",';  
		    }    
		?>	
        ],
        initialPreviewConfig: [
	    <?php
		    for ($i = 1; $i <= $numft; $i++) {
			   echo $ln2[$i]. ',';  
		    }    
		?>	
        ],
        
        theme: 'fas',
        deleteUrl: "<?= base_url('/assets/plugins/fileinput/catalog_products/delete.php'); ?>"
    }).on('filesorted', function(event, params) {
		changeTheOrderOfImages(params)
		console.log('File sorted params', params);
	}).on('fileuploaded', function(event, previewId, index, fileId) {
        console.log('File Uploaded', 'ID: ' + fileId + ', Thumb ID: ' + previewId);
        $('#product_image').val(token);
    }).on('fileuploaderror', function(event, data, msg) {
    	AlertSweet.fire({
            icon: 'error',
            title: 'Erro no upload do arquivo de imagem.<br>Garanta que a imagem seja um jpg com tamanho entre 800x800 e 1200x1200!<br>Faça o ajuste e tente novamente!'
        });
        console.log('File Upload Error', 'ID: ' + data.fileId + ', Thumb ID: ' + data.previewId);
    }).on('filebatchuploadcomplete', function(event, preview, config, tags, extraData) {
        console.log('File Batch Uploaded', preview, config, tags, extraData);
	});

	$("#category, input[type='checkbox']:checked").trigger('change');
	
	// submit the create from 
  	$("#createBrandForm").unbind('submit').on('submit', function() {
   	var form = $(this);

    	// remove the text-danger
    	$(".text-danger").remove();

    	$.ajax({
	      url: form.attr('action'),
	      type: form.attr('method'),
	      data: form.serialize(), // /converting the form data into array and sending it to server
	      dataType: 'json',
	      success:function(response) {
	
	        // manageTable.ajax.reload(null, false); 
	
	        if(response.success === true) {
	            $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
	              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
	            '</div>');
	  
	            // hide the modal
	          	$("#addBrandModal").modal('hide');
				
				//adiciono a opção recem criada 
				$("#brands option[value='']").remove();
				$('#brands option:selected').before($('<option>', {
					    value: response.id,
					    text: response.brand_name,
					    selected: "selected"
					}));
				$("#brands").selectpicker('refresh');
				$("#brands").val(response.id).change();
				
		        // reset the form
		        $("#createBrandForm")[0].reset();
		        $("#createBrandForm .form-group").removeClass('has-error').removeClass('has-success');
	         
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
	          	alert(response.messages);
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
	
  });

  	function changeTheOrderOfImages(params) {
		
		$.ajax({
			type: "POST",
			enctype: 'multipart/form-data',
			data: {
				params: params,
				onBucket: 1
			},
			url: base_url+"catalogProducts/orderimages",
			dataType: "json",
			async: true,
			// complete: function(response){
			// 	console.log(response)
			// },
			success: function(success) {       
				console.log(success)
			},
			error: function(error){
				console.log(error)
			}
		}); 
	
	}
function AddBrand(e) {
  	e.preventDefault();
	$("#addBrandModal").modal('show');
}

$('#formInsertProduct').submit(function () {
    let variations = [];
    let exitApp = false;
    if ($('#semvar').is(':not(:checked)')) {
        $('input[name="EAN_V[]"]').each(function () {
            if (variations.includes($(this).val()) && $(this).val() != "") exitApp = true;
            variations.push($(this).val());
        });

        if (exitApp) {
            AlertSweet.fire({
                icon: 'warning',
                title: 'Não é permitido o mesmo '+label_ean+' para mais que uma variação. <br><br>Faça o ajuste e tente novamente!'
            });
            return false;
        }
    }
})

function toggleCatMktPlace(e) {
  	e.preventDefault();
  	$("#catlinkdiv").toggle();
}

function changeCategory(id) {
	var catlink = $("#linkcategory"); 
	var cattable = $("#catlinkbuttondiv");
	cattable.remove();
	$.ajax({
	      type:'GET', 
	      dataType: "json",
	      url:base_url + 'category/getLinkCategory/',
	      data:"idcat="+id,
	      success: function(data){
	      	  var cats = '<div id="catlinkbuttondiv" class="form-group col-md-12 col-xs-12"><button type="button" onClick="toggleCatMktPlace(event)" > Categorias por Marketplace</button>';
              cats =  cats + '<div id="catlinkdiv" style="display: none;" >'
              
	      	  if (data.length === 0) {
	      	     cats = cats + '<span style="color:red">Categoria não foi ainda associada a nenhum marketplace</span><div class="row"></div>';
	      	  }
	      	  else {
	      	      cats = cats+'<table  class="table table-striped table-hover responsive display table-condensed"><thead><tr><th>Marketplace</th><th>Id</th><th>Categoria do Marketplace</th></tr>';
		          for (var campo of data) {
		          	cats = cats + '<tr><td>'+campo.int_to+'</td><td>'+campo.category_marketplace_id+'</td><td>'+campo.nome+'</td></tr>';
	       		  }
	       		  cats = cats + '</thead></table>';
	      	  }
	      	  if ((update_category) && (id !== '')){
	       		  	cats = cats + '<a target="__blank" href="'+base_url+'category/link/'+id+'" class="btn btn-success"><i class="fa fa-pencil"></i> Alterar as Associações da Categoria</a>';
	       	  }
	       	  cats = cats + '</div></div>';
			  catlink.append(cats);
	      }
	});
}

function checkEAN(ean, field) {
	
		$.ajax({
			type: "POST",
			enctype: 'multipart/form-data',
			data: {
				ean: ean, 
				product_id : 0
			},
			url: base_url+"catalogProducts/checkEANpost",
			dataType: "json",
			async: true,
			success: function(response) { 
				console.log(response)
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

function AddImage(e, num) {
	e.preventDefault();
	
	$("#addImagemModal").modal('show');
	
	for  (i=0;i<=$("#numvar").val();i++)	{
	    $("#showimage"+i).hide();
	} 
	$('#variant_num').val(num);
	$("#showimage"+num).show();
}

function RemoveVariant(e, num) {
	e.preventDefault(); 
	$('#showimage'+num).remove();
	$('#variant'+num).remove();

	//var varn = $("#numvar").val();
 	//varn--;
	//if (varn==0) {varn=1;}
	//$('#numvar').val(varn);
}

function UpdateVariantImage(e) {
	e.preventDefault();
	
	var num = $("#variant_num").val();
	$("#addImagemModal").modal('hide');
	
	var tokenimagem = $("#IMAGEM"+num).val();

	$.ajax({
		type: "POST",
		enctype: 'multipart/form-data',
		data: {
			tokenimagem: tokenimagem
		},
		url: base_url+"catalogProducts/getImages",
		dataType: "json",
		async: true,
		success: function(response) { 
			console.log(response)
			if (response.success) {
				
				$("#foto_variant"+num).attr("src",base_url+'assets/images/system/sem_foto.png');
				if (response.ln1[0]) {
					$("#foto_variant"+num).attr("src",response.ln1[0]);
				}
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
           console.log(textStatus, errorThrown);
		}
	}); 
	
}

function initImage(num) 
{
	var tokenimagem = $("#IMAGEM"+num).val(); 
	// alert(tokenimagem);
	$.ajax({
		type: "POST",
		enctype: 'multipart/form-data',
		data: {
			tokenimagem: tokenimagem,
			onBucket:1
		},
		url: base_url+"catalogProducts/getImages",
		dataType: "json",
		async: true,
		success: function(response) { 
			console.log(response)
			if (response.success) {
				
				$("#foto_variant"+num).attr("src",base_url+'assets/images/system/sem_foto.png');
				if (response.ln1[0]) {
					$("#foto_variant"+num).attr("src",response.ln1[0]);
				}
				
				$("#prd_image_variant"+num).fileinput('destroy').fileinput({
			        uploadUrl: upload_url,
			        language: 'pt-BR',
			        autoOrientImage: false,
			        allowedFileExtensions: ["jpg", "png"],
                    minImageWidth: <?=$dimenssion_min_product_image ?? 'null'?>,
                    minImageHeight: <?=$dimenssion_min_product_image ?? 'null'?>,
                    maxImageWidth: <?=$dimenssion_max_product_image ?? 'null'?>,
                    maxImageHeight: <?=$dimenssion_max_product_image ?? 'null'?>,
			        enableResumableUpload: true,
			        resumableUploadOptions: {
			           // uncomment below if you wish to test the file for previous partial uploaded chunks
			           // to the server and resume uploads from that point afterwards
			           // testUrl: "http://localhost/test-upload.php"
			        },
			        uploadExtraData: {
				        'uploadToken': tokenimagem, // for access control / security 
						'onBucket':1
			        },
			        maxFileCount: 5,
			        allowedFileTypes: ['image'],    // allow only images
			        showCancel: true,
			        initialPreviewAsData: true,
			        overwriteInitial: false,
			        initialPreview: response.ln1,
			        initialPreviewConfig: response.ln2,
			        
			        theme: 'fas',
			        deleteUrl: delete_url
			    }).on('filesorted', function(event, params) {
					changeTheOrderOfImages(params)
					console.log('File sorted params', params);
				}).on('fileuploaded', function(event, previewId, index, fileId) {
			        console.log('File Uploaded', 'ID: ' + fileId + ', Thumb ID: ' + previewId);
			    }).on('fileuploaderror', function(event, data, msg) {
			    	AlertSweet.fire({
			            icon: 'error',
			            title: 'Erro no upload do arquivo de imagem.<br>Garanta que a imagem seja um jpg com tamanho entre 800x800 e 1200x1200!<br>Faça o ajuste e tente novamente!'
			        });
			        console.log('File Upload Error', 'ID: ' + data.fileId + ', Thumb ID: ' + data.previewId);
			    }).on('filebatchuploadcomplete', function(event, preview, config, tags, extraData) {
			        console.log('File Batch Uploaded', preview, config, tags, extraData);
				});
				
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
           console.log(textStatus, errorThrown);
		}
	}); 	
	
	function changeTheOrderOfImages(params) {
		
		$.ajax({
			type: "POST",
			enctype: 'multipart/form-data',
			data: {
				params: params,
				onBucket: 1
			},
			url: base_url+"catalogProducts/orderimages",
			dataType: "json",
			async: true,
			// complete: function(response){
			// 	console.log(response)
			// },
			success: function(success) {       
				console.log(success)
			},
			error: function(error){
				console.log(error)
			}
		}); 
	}
	
	
}
function calcDiscount() {
	var price = document.getElementById("price");
	var original_price = document.getElementById("original_price"); 
	var discount = document.getElementById("discount");
	
	if ((original_price.value !=0) && (price.value != ''))  {
		discount.value = parseFloat((1- (price.value / original_price.value))*100).toFixed(2);
	}
	else {
		discount.value = '';
	}
}
</script>