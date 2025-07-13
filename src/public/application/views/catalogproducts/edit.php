<!--

Editar Produtos de Catálogo

--> 
<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

<!-- necssário para o carousel ----> 

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

	<?php $data['pageinfo'] = ($readonlytag) ? "application_view" : "application_edit" ;  $this->load->view('templates/content_header',$data); ?>

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
        
        <?php if(in_array('createProduct', $user_permission)): ?>
	        <a href="<?php echo base_url('catalogProducts/createFromCatalog/'.$product_data['id']) ?>" class="btn btn-primary"><?=$this->lang->line('application_add_product');?></a>
		<?php endif; ?>

        <div class="box">
        	<?php if ($readonlytag) {
        		 $readonly = ' readonly '; 
        		 $optionRO = ' disabled '; ?>
        		<form action="<?php base_url('catalogProducts/view') ?>" method="post" enctype="multipart/form-data" id="formUpdateProduct">
        	<?php } else {
        		 $readonly = ''; 
        		 $optionRO = '';?>
        		<form action="<?php base_url('catalogProducts/update') ?>" method="post" enctype="multipart/form-data" id="formUpdateProduct">
        	<?php } ?>
          
              <div class="box-body" >

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
					if(!$product_data['is_on_bucket']){
					$fotos = scandir(FCPATH . 'assets/images/catalog_product_image/' . $product_data['image']);	
					foreach($fotos as $foto) {
						if (($foto!=".") && ($foto!="..") && ($foto!="")) {
							$numft++;
							$ln1[$numft] = base_url('assets/images/catalog_product_image/' . $product_data['image'].'/'. $foto);
							$ln2[$numft] = '{width: "120px", key: "'. $product_data['image'].'/'.$foto .'"}';
						}
					}
				}else{
							// Prefixo de url para buscar a imagem.
						$asset_prefix = "assets/images/catalog_product_image/" . $product_data['image'] . "/";
						// Busca as imagens do produto já formatadas.
						$product_images = $this->bucket->getFinalObject($asset_prefix);
						// Caso tenha dado certo, busca o conteudo.
						if ($product_images['success']) {
							// Percorre cada elemento e verifica se não é imagem de variação.
							foreach ($product_images['contents'] as $key => $image_data) {
								// Monta a chave da imagem completa.
								$full_key = $product_data['image'] . '/' . $image_data['key'];
								$numft++;
								$ln1[$numft] = $image_data['url'];
								$ln2[$numft] = '{width: "120px", key: "' . $full_key . '"}';
							}
						}
					}
					 
				?>	
				
				<?php if ($readonly == '') { ?>
                <div class="form-group col-md-12 col-xs-12 <?php echo (form_error('product_image')) ? "has-error" : ""; ?>">
                  <label for="prd_image"><?=$this->lang->line('application_uploadimages');?>(*):</label>
                  <div class="kv-avatar">
                      <div class="file-loading">
						  <input type="file" id="prd_image" name="prd_image[]" accept="image/png, image/jpeg" multiple>
                      </div>
                  </div>
                  <input type="hidden" name="product_image" id="product_image" value="<?= $product_data['image']; ?>"/>         
                  <?php echo '<i style="color:red">'.form_error('product_image').'</i>'; ?>
                </div>
                <?php } else {
                	 if ($numft > 6) { $numft=6; } ?>
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
                <?php } ?>
                
                <div id="EANDIV" class="form-group col-md-3 col-xs-12 <?php echo (form_error('EAN')) ? "has-error" : ""; ?>">
                  <label for="EAN"><?=$label_ean;?></label>
                  <input type="text" class="form-control" id="EAN" name="EAN" <?= $readonly;?> onchange="checkEAN(this.value,'EANDIV','<?=$product_data['id']; ?>')" <?=$require_ean?'required':''?> placeholder="<?=$label_ean;?>" value="<?php echo set_value('EAN',$product_data['EAN']) ?>" autocomplete="off" />
                  <?php echo '<i style="color:red">'.form_error('EAN').'</i>'; ?>
                  <div id="EANDIVerro" style="display: none;"><i style="color:red"><?=$invalid_ean;?></i></div>
                </div>

                <div class="form-group col-md-6 col-xs-12 <?php echo (form_error('name')) ? "has-error" : ""; ?>">
                  <label for="name"><?=$this->lang->line('application_name');?>(*)</label>
                  <input type="text" class="form-control" id="name" name="name" <?= $readonly;?> required placeholder="<?=$this->lang->line('application_enter_product_name');?>" maxlength="<?= $product_length_name?>" value="<?php echo set_value('name',$product_data['name']); ?>"  autocomplete="off"/>
                  <?php echo '<i style="color:red">'.form_error('name').'</i>'; ?>
                </div>

                <div class="form-group col-md-3 col-xs-12  <?php echo (form_error('status')) ? "has-error" : ""; ?>">
                  <label for="status"><?=$this->lang->line('application_active');?>(*)</label>
                  <select class="form-control" id="status" name="status">
                    <option value="1" <?=$optionRO;?> <?php echo set_select('status', 1, $product_data['status'] == 1) ?>><?=$this->lang->line('application_yes');?></option>
                    <option value="2" <?=$optionRO;?> <?php echo set_select('status', 2, $product_data['status'] == 2) ?>><?=$this->lang->line('application_no');?></option>
                    </select>
                  <?php echo '<i style="color:red">'.form_error('status').'</i>'; ?>
                </div>     
                
                <small><i>
	                <div class="form-group col-md-3 col-xs-12">
	                  <label><?=$this->lang->line('application_created_on');?></label>
	                  <span ><?php echo date("d/m/Y H:i:s",strtotime($product_data['date_create'])); ?></span>
	                </div>
	                 <div class="form-group col-md-3 col-xs-12">
	                  <label><?=$this->lang->line('application_updated_on');?></label>
	                  <span ><?php echo date("d/m/Y H:i:s",strtotime($product_data['date_update'])); ?></span>
	                </div>
	                <?php if (!is_null($product_data['reason'])) { ?>
	                <div class="form-group col-md-3 col-xs-12">
	                  <label><?=$this->lang->line('application_last_inactive_date');?></label>
	                  <span ><?php echo date("d/m/Y H:i:s",strtotime($product_data['last_inactive_date'])); ?></span>
	                </div> 
	                <div class="form-group col-md-3 col-xs-12">
	                  <label><?=$this->lang->line('application_reason');?></label>
	                  <span class="label label-danger"><?php echo $product_data['reason']; ?></span>
	                </div> 
	                <?php } ?>
                 </i></small>  
                 
                 <div class="row"></div>
                 <?php
                 $catalog_attribute = '';
                 $allcatalogs = array();
				 foreach($linkcatalogs as $lc) {
				 	$allcatalogs[] = $lc['catalog_id'];
				 }
                 ?>
                 <div class="form-group col-md-6 col-xs-6 <?php echo (form_error('catalogs[]')) ? "has-error" : ""; ?>">
              		<label for="catalogs" class="normal"><?=$this->lang->line('application_catalogs');?>(*)</label>
             		<select class="form-control selectpicker show-tick" id="catalogs" name ="catalogs[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 2" title="<?=$this->lang->line('application_select');?>">
		                <?php
                        foreach ($catalogs as $catalog) {
                            if(in_array($catalog['id'], $allcatalogs) && !empty($catalog['attribute_value'])){
                                $catalog_attribute = $catalog['attribute_value'];
                            }
                        ?>
		                <option value="<?= $catalog['id'] ?>" <?=$optionRO;?> <?php echo set_select('catalogs', $catalog['id'], in_array($catalog['id'], $allcatalogs)); ?> ><?= $catalog['name'] ?></option>
		           		<?php } ?>
              		</select>
              		<?php echo '<i style="color:red">'.form_error('catalogs[]').'</i>'; ?>
         		 </div>
         		 
         		<?php if (!is_null($product_data['ref_id'])) { ?>
         		 	<div class="form-group col-md-3 col-xs-12">
	                  <label><?=$this->lang->line('application_vtex_ref_id');?></label>
	                  <span class="form-control"><?php echo $product_data['ref_id']; ?></span>
	                </div> 
         		<?php } ?>
         		
         		<?php if (!is_null($product_data['mkt_sku_id'])) { ?>
         		 	<div class="form-group col-md-3 col-xs-12">
	                  <label><?=$this->lang->line('application_vtex_sku_id');?></label>
	                  <span class="form-control"><?php echo $product_data['mkt_sku_id']; ?></span>
	                </div> 
         		<?php } ?>
                       
                <div class="col-md-12 col-xs-12"><hr></div>
                <?php 
                $changevariantion = count($productssellers) == 0;  // posso mudar variação se ainda não tem nenhum produto de seller
                if ($readonly !== '') { $changevariantion = false; } // tb não altero se estiver readonly 
                ?> 
                
                <?php if ($changevariantion) : ?>
                <div class="col-md-12 col-xs-6">
                    <div class="callout callout-danger">
                        <p><?=$this->lang->line('messages_warning_create_catalog_product_variant')?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-group col-md-12 col-xs-12">
                  <label for="semvar"><?=$this->lang->line('application_variations');?>(*)</label><br>
				  <div class="form-check col-md-3 col-xs-12">				  	
				  	<?php if (!$changevariantion) { ?>
				        <input type="checkbox" disabled class="form-check-input" <?= set_checkbox('semvar', 'on' ,$product_data['has_variants'] == "") ?> >
				        <input type="hidden" id="semvar" name="semvar" value="<?= $product_data['has_variants'] == '' ? 'on': 'off'; ?>" <?= ($product_data['has_variants'] == '') ? 'checked': ''; ?> >
				    <?php } else { ?>
				    	<input type="checkbox" class="form-check-input" id="semvar" name="semvar" <?= set_checkbox('semvar', 'on' ,$product_data['has_variants'] == "") ?> >
				    <?php } ?>	
				    <span><?=$this->lang->line('application_without_variations');?></span>
				  </div>
				  <div class="form-check col-md-3 col-xs-12">
				  	<?php if (!$changevariantion) { ?>
				    	<input type="checkbox" disabled class="form-check-input" <?= set_checkbox('sizevar', 'on', (strpos($product_data['has_variants'], "TAMANHO") !== false)) ?> >
				    	<input type="hidden" id="sizevar" name="sizevar" value="<?= (strpos($product_data['has_variants'], 'TAMANHO') !== false) ? 'on': 'off'; ?>" <?= (strpos($product_data['has_variants'], 'TAMANHO') !== false) ? 'checked': ''; ?> >
				    <?php } else { ?>
				     	<input type="checkbox" class="form-check-input" id="sizevar" name="sizevar" <?= set_checkbox('sizevar', 'on', (strpos($product_data['has_variants'], "TAMANHO") !== false)) ?> >
				    <?php } ?>	
				    <span><?=$this->lang->line('application_size');?></span>
				  </div>
				  <div class="form-check col-md-3 col-xs-12">
				  	<?php if (!$changevariantion) { ?>
				    	<input type="checkbox" disabled class="form-check-input" <?= set_checkbox('colorvar', 'on', (strpos($product_data['has_variants'], "Cor") !== false)) ? 'checked' : ''; ?> >
				    	<input type="hidden" id="colorvar" name="colorvar" value="<?= (strpos($product_data['has_variants'], 'Cor') !== false) ? 'on' : 'off'; ?>"  <?= (strpos($product_data['has_variants'], 'Cor') !== false) ? 'checked' : ''; ?>>
				    <?php } else { ?>
				    	<input type="checkbox" class="form-check-input" id="colorvar" name="colorvar" <?= set_checkbox('colorvar', 'on', (strpos($product_data['has_variants'], "Cor") !== false)) ? 'checked' : ''; ?> >
				    <?php } ?>	
				    <span><?=$this->lang->line('application_color');?></span>
				  </div>
				  <div class="form-check col-md-3 col-xs-12">
				  	<?php if (!$changevariantion) { ?>
				    	<input type="checkbox" disabled class="form-check-input" <?= set_checkbox('voltvar', 'on', (strpos($product_data['has_variants'], "VOLTAGEM") !== false)) ? 'checked' : ''; ?> >
				    	<input type="hidden" id="voltvar" name="voltvar" value="<?= (strpos($product_data['has_variants'], "VOLTAGEM") !== false) ? 'on' : 'off'; ?>" <?= (strpos($product_data['has_variants'], "VOLTAGEM") !== false) ? 'checked' : ''; ?> >
				    <?php } else { ?>
				    	<input type="checkbox" class="form-check-input" id="voltvar" name="voltvar" <?= set_checkbox('voltvar', 'on', (strpos($product_data['has_variants'], "VOLTAGEM") !== false)) ? 'checked' : ''; ?> >
				    <?php } ?>	
				    <span><?=$this->lang->line('application_voltage');?></span>
				  </div>
				  <div class="form-check col-md-3 col-xs-12">
				  	<?php if (!$changevariantion) { ?>
				    	<input type="checkbox" disabled class="form-check-input" <?= set_checkbox('saborvar', 'on', (strpos($product_data['has_variants'], "SABOR") !== false)) ? 'checked' : ''; ?> >
				    	<input type="hidden" id="saborvar" name="saborvar" value="<?= (strpos($product_data['has_variants'], "SABOR") !== false) ? 'on' : 'off'; ?>" <?= (strpos($product_data['has_variants'], "SABOR") !== false) ? 'checked' : ''; ?> >
				    <?php } else { ?>
				    	<input type="checkbox" class="form-check-input" id="saborvar" name="saborvar" <?= set_checkbox('saborvar', 'on', (strpos($product_data['has_variants'], "SABOR") !== false)) ? 'checked' : ''; ?> >
				    <?php } ?>	
				    <span><?=$this->lang->line('application_flavor');?></span>
				  </div>
				  <div class="form-check col-md-3 col-xs-12">
				  	<?php if (!$changevariantion) { ?>
				    	<input type="checkbox" disabled class="form-check-input" <?= set_checkbox('grauvar', 'on', (strpos($product_data['has_variants'], "GRAU") !== false)) ? 'checked' : ''; ?> >
				    	<input type="hidden" id="grauvar" name="grauvar" value="<?= (strpos($product_data['has_variants'], "GRAU") !== false) ? 'on' : 'off'; ?>" <?= (strpos($product_data['has_variants'], "GRAU") !== false) ? 'checked' : ''; ?> >
				    <?php } else { ?>
				    	<input type="checkbox" class="form-check-input" id="grauvar" name="grauvar" <?= set_checkbox('grauvar', 'on', (strpos($product_data['has_variants'], "GRAU") !== false)) ? 'checked' : ''; ?> >
				    <?php } ?>	
				    <span><?=$this->lang->line('application_degree');?></span>
				  </div>
				  <div class="form-check col-md-3 col-xs-12">
				  	<?php if (!$changevariantion) { ?>
				    	<input type="checkbox" disabled class="form-check-input" <?= set_checkbox('ladovar', 'on', (strpos($product_data['has_variants'], "LADO") !== false)) ? 'checked' : ''; ?> >
				    	<input type="hidden" id="ladovar" name="ladovar" value="<?= (strpos($product_data['has_variants'], "LADO") !== false) ? 'on' : 'off'; ?>" <?= (strpos($product_data['has_variants'], "LADO") !== false) ? 'checked' : ''; ?> >
				    <?php } else { ?>
				    	<input type="checkbox" class="form-check-input" id="ladovar" name="ladovar" <?= set_checkbox('ladovar', 'on', (strpos($product_data['has_variants'], "LADO") !== false)) ? 'checked' : ''; ?> >
				    <?php } ?>	
				    <span><?=$this->lang->line('application_side');?></span>
				  </div>
				</div>
				
                <div class="col-md-12 col-xs-12"><hr></div>

                <div class="form-group col-md-12 col-xs-12 <?php echo (form_error('description')) ? 'has-error' : '';  ?>">
					<label for="description"><?=$this->lang->line('application_description');?>(*)</label>
					<textarea type="text" <?= $readonly;?> class="form-control" id="description" name="description" placeholder="<?=$this->lang->line('application_enter_description');?>" maxlength="<?=  $product_length_description ?>"><?php echo $product_data['description']; ?></textarea>
					<span id="char_description"></span><br />
					<span class="label label-warning" id="words_description" data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_explanation_of_forbidden_words'); ?>"></span>
                  	<?php echo '<i style="color:red">'.form_error('description').'</i>'; ?>
                </div>
                
                <div class="row"></div>
				<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('original_price')) ? 'has-error' : '';  ?>">
                  <label for="original_price"><?=$this->lang->line('application_original_price');?></label>
                  <div class="input-group">
                    <span class="input-group-addon"><strong>R$</strong></span>
                    <input type="text" class="form-control maskdecimal2" id="original_price" <?= $readonly ?> name="original_price" placeholder="<?=$this->lang->line('application_enter_price');?>" value="<?php echo set_value('original_price',$product_data['original_price']) ?>" autocomplete="off"  onblur="calcDiscount()" />
                  </div>
                  <?php echo '<i style="color:red">'.form_error('original_price').'</i>'; ?>
                </div>
                
                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('price')) ? 'has-error' : '';  ?>">
                  <label for="price"><?=$this->lang->line('application_price');?>(*)</label>
                  <div class="input-group">
                    <span class="input-group-addon"><strong>R$</strong></span>
                    <input type="text" class="form-control maskdecimal2" id="price" <?= $readonly ?> name="price" required placeholder="<?=$this->lang->line('application_enter_price');?>" value="<?php echo set_value('price',$product_data['price']) ?>" autocomplete="off"  onblur="calcDiscount()" />
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
                  <input type="text" class="form-control" id="brand_code" <?= $readonly ?> name="brand_code" placeholder="<?=$this->lang->line('application_enter_manufacturer_code');?>" value="<?php echo set_value('brand_code',$product_data['brand_code']); ?>"  autocomplete="off" />
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
		                    <input type="text" class="form-control maskdecimal3" id="gross_weight" <?= $readonly ?> name="gross_weight" required  placeholder="<?=$this->lang->line('application_enter_gross_weight');?>" value="<?php echo set_value('gross_weight',$product_data['gross_weight']) ?>" autocomplete="off"/>
		                    <span class="input-group-addon"><strong>Kg</strong></span>
		                  </div>
		                  <?php echo '<i style="color:red">'.form_error('gross_weight').'</i>'; ?>
		                </div>
		                
		                <div class="form-group col-md-2 col-xs-12 <?php echo (form_error('width')) ? 'has-error' : '';  ?>">
		                  <label for="width"><?=$this->lang->line('application_width');?> (*)</label>
		                  <div class="input-group">
		                    <input type="text" class="form-control maskdecimal2" id="width" <?= $readonly ?> name="width" required placeholder="<?=$this->lang->line('application_enter_width');?>" value="<?php echo set_value('width',$product_data['width']) ?>" autocomplete="off"  onKeyPress="return digitos(event, this);" />
		                    <span class="input-group-addon"><strong>cm</strong></span>
		                  </div>
		                  <?php echo '<i style="color:red">'.form_error('width').'</i>'; ?>
		                </div>
		                
		                <div class="form-group col-md-2 col-xs-12 <?php echo (form_error('height')) ? 'has-error' : '';  ?>">
		                  <label for="height"><?=$this->lang->line('application_height');?> (*)</label>
		                  <div class="input-group">
		                    <input type="text" class="form-control maskdecimal2" id="height" <?= $readonly ?> name="height" required  placeholder="<?=$this->lang->line('application_enter_height');?>" value="<?php echo set_value('height',$product_data['height']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
		                    <span class="input-group-addon"><strong>cm</strong></span>
		                  </div>
		                  <?php echo '<i style="color:red">'.form_error('height').'</i>'; ?>
		                </div>
		                
		                <div class="form-group col-md-2 col-xs-12 <?php echo (form_error('length')) ? 'has-error' : '';  ?>">
		                  <label for="length"><?=$this->lang->line('application_depth');?> (*)</label>
		                  <div class="input-group">
		                    <input type="text" class="form-control maskdecimal2" id="length" <?= $readonly ?> name="length" required placeholder="<?=$this->lang->line('application_enter_depth');?>" value="<?php echo set_value('length',$product_data['length']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
		                    <span class="input-group-addon"><strong>cm</strong></span>
		                  </div>
		                	<?php echo '<i style="color:red">'.form_error('length').'</i>'; ?>
		                </div>

                        <div class="form-group col-md-3 col-xs-12">
                            <label for="products_package" data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_how_many_units'); ?>"><?= $this->lang->line('application_products_by_packaging'); ?>(*)</label>
                            <div class="input-group">
                                <input type="text" class="form-control maskdecimal3" id="products_package" name="products_package" required placeholder="<?= $this->lang->line('application_enter_quantity_products'); ?>" value="<?=set_value('products_package',$product_data['products_package']) ?>" autocomplete="off" />
                                <span class="input-group-addon"><strong>Qtd</strong></span>
                            </div>
                            <?php echo '<i style="color:red">'.form_error('products_package').'</i>'; ?>
                        </div>
		        	</div>
		        </div>
                <!-- dimensões do produto fora da embalagem -->
				<div class="panel panel-primary">
					<div class="panel-heading"><?=$this->lang->line('application_product_dimensions');?> &nbsp 
						<span class="h6"> <?=$this->lang->line('application_out_of_the_package');?></span>
					</div>
					<div class="panel-body">
						<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('net_weight')) ? 'has-error' : '';  ?>">
		                  <label for="net_weight"><?=$this->lang->line('application_net_weight');?>(*)</label>
		                  <div class="input-group">
		                    <input type="text" class="form-control maskdecimal3" id="net_weight" <?= $readonly ?>  required name="net_weight" placeholder="<?=$this->lang->line('application_enter_net_weight');?>" value="<?php echo set_value('net_weight',$product_data['net_weight']) ?>" autocomplete="off"/>
		                    <span class="input-group-addon"><strong>Kg</strong></span>
		                  </div>
		                  <?php echo '<i style="color:red">'.form_error('net_weight').'</i>'; ?>
		                </div>
						<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('actual_width')) ? 'has-error' : '';  ?>">
							<label for="actual_width"><?=$this->lang->line('application_width');?></label>
							<div class="input-group">
								<input type="text" class="form-control maskdecimal2" id="actual_width" name="actual_width" placeholder="<?=$this->lang->line('application_enter_actual_width');?>" value="<?php print_r($product_data['actual_width']); ?>" autocomplete="off"  onKeyPress="return digitos(event, this);" />
								<span class="input-group-addon"><strong>cm</strong></span>
								
							</div>
								<?php echo '<i style="color:red">'.form_error('actual_width').'</i>'; ?>
						</div>
						<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('actual_height')) ? 'has-error' : '';  ?>">
							<label for="actual_height"><?=$this->lang->line('application_height');?></label>
							<div class="input-group">
								<input type="text" class="form-control maskdecimal2" id="actual_height" name="actual_height" placeholder="<?=$this->lang->line('application_enter_actual_height');?>" value="<?php print_r($product_data['actual_height']); ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
								<span class="input-group-addon"><strong>cm</strong></span>
							</div>
								<?php echo '<i style="color:red">'.form_error('actual_height').'</i>'; ?>
						</div>
						<div class="form-group col-md-3 col-xs-12 <?php echo (form_error('actual_depth')) ? 'has-error' : '';  ?>">
							<label for="actual_depth"><?=$this->lang->line('application_depth');?></label>
							<div class="input-group">
								<input type="text" class="form-control maskdecimal2" id="actual_depth" name="actual_depth" placeholder="<?=$this->lang->line('application_enter_actual_depth');?>" value="<?php print_r($product_data['actual_depth']); ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
								<span class="input-group-addon"><strong>cm</strong></span>
							</div>
							<?php echo '<i style="color:red">'.form_error('actual_depth').'</i>'; ?>
						</div>
					</div>
				</div>
				<!-- fim das dimensões do produto fora da embalagem -->
                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('warranty')) ? 'has-error' : '';  ?>">
                  <label for="garantia"><?=$this->lang->line('application_garanty');?>(* <?=$this->lang->line('application_in_months');?>)</label>
                  <input type="text" class="form-control" id="warranty" name="warranty" <?= $readonly ?>  required placeholder="<?=$this->lang->line('application_enter_warranty');?>" value="<?php echo set_value('warranty',$product_data['warranty']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" />
                  <?php echo '<i style="color:red">'.form_error('warranty').'</i>'; ?>
                </div>
                
                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error('NCM')) ? 'has-error' : '';  ?>">
                  <label for="NCM"><?=$this->lang->line('application_NCM');?></label>
                  <input type="text" class="form-control" id="NCM" name="NCM" <?= $readonly ?> placeholder="<?=$this->lang->line('application_enter_NCM');?>" value="<?php echo set_value('NCM',$product_data['NCM']); ?>" maxlength="10" size="10" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('NCM',this,event);"  autocomplete="off" />
                  <?php echo '<i style="color:red">'.form_error('NCM').'</i>'; ?>
                </div>

                  <?php $attribute_id = json_decode($product_data['attribute_value_id']);
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
                                      <option value="<?php echo $v2['id'] ?>" <?=$optionRO;?> <?php echo set_select('attributes_value_id', $v2['id'], in_array($v2['id'], $attribute_id)) ?>><?php echo $v2['value'] ?></option>
                                  <?php endforeach ?>
                              </select>
                          </div>
                      <?php endforeach ?>
                  <?php endif; ?>
                  
                <div class="form-group col-md-12 col-xs-12">
                  <label for="origin"><?=$this->lang->line('application_origin_product');?>(*)</label>
                  <select class="form-control" name="origin" id="origin" required>
                      <?php foreach ($origins as $key => $origin)
                          echo "<option $optionRO value='{$key}' ".set_select('origin', $key, $product_data['origin'] == $key).">{$origin}</option>";
                      ?>
                  </select>
                </div>
                
                <div class="form-group col-md-5 col-xs-12 <?php echo (form_error('brands')) ? 'has-error' : '';  ?>">
                  <label for="brands" class="d-flex justify-content-between">
                      <?=$this->lang->line('application_brands');?>(*)
                      <?php if ($readonly == '') { ?> 
                      	<a href="#" <?=$optionRO;?> onclick="AddBrand(event)" ><i class="fa fa-plus-circle"></i> <?=$this->lang->line('application_add_brand');?></a>
                      <?php } ?> 
                  </label>
                  <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="brands" name="brands" title="<?=$this->lang->line('application_select');?>" >
                    <?php foreach ($brands as $k => $v): ?>
                      <option value="<?php echo $v['id'] ?>" <?=$optionRO;?> <?php echo set_select('brands', $v['id'], $v['id'] == $product_data['brand_id']); ?> ><?php echo $v['name'] ?></option>
                    <?php endforeach ?>
                  </select>
                  <?php echo '<i style="color:red">'.form_error('brands').'</i>'; ?>
				</div>
                
                <div class="form-group col-md-12 col-xs-12 <?php echo (form_error('category')) ? 'has-error' : '';  ?>">
                  <label for="category"><?=$this->lang->line('application_categories');?>(*)</label> 
                  <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="category" name="category" title="<?=$this->lang->line('application_select');?>" >
                    <?php foreach ($category as $k => $v): ?>
                      <option value="<?php echo $v['id'] ?>" <?=$optionRO;?> <?php echo set_select('category', $v['id'], $v['id'] == $product_data['category_id']); ?> ><?php echo $v['name'] ?></option>
                    <?php endforeach ?>
                  </select>
                  <?php echo '<i style="color:red">'.form_error('category').'</i>'; ?>
                </div>
                
                <div id='linkcategory'></div>

                  <?php if($identifying_technical_specification && $identifying_technical_specification['status']  == 1){ ?>
                      <div class="form-group col-md-4 col-xs-12">
                          <label for="brands" class="d-flex justify-content-between">
                              <?= $this->lang->line('application_collection'); ?>
                          </label>
                          <input type="text" class="form-control" value="<?= $catalog_attribute ?? '' ?>" disabled>
                      </div>
                  <?php } ?>

                <div class="row"></div>

                
		  		<!-- Variants DIV -->
				<div id="variantModal" class="col-md-12 col-xs-12" style="display:none;">
                    <hr class="row">
				    <input type="hidden" name="numvar" id="numvar" value="<?php echo set_value('numvar',count($product_variants) ==0 ? 1 : count($product_variants)); ?>" />
                    <h4 class="mb-3"><?=$this->lang->line('application_variantproperties');?></span></h4>
					<input type="hidden" name="from" value="allocate" />    
					<div class="row">
						<div id="Lnvar" class="form-group col-md-1">
					    	<label><?=$this->lang->line('application_number');?></label>
						</div>
						<div id="Ltvar" class="form-group col-md-2">
					    	<label><?=$this->lang->line('application_size');?></label>
						</div>
						<div id="Lcvar" class="form-group col-md-2">
					    	<label><?=$this->lang->line('application_color');?></label>
						</div>
						<div id="Lvvar" class="form-group col-md-2">
					    	<label><?=$this->lang->line('application_voltage');?></label>
						</div>
						<div id="Lsvar" class="form-group col-md-2">
					    	<label><?=$this->lang->line('application_flavor');?></label>
						</div>
						<div id="Lgvar" class="form-group col-md-2">
					    	<label><?=$this->lang->line('application_degree');?></label>
						</div>
						<div id="Llvar" class="form-group col-md-2">
					    	<label><?=$this->lang->line('application_side');?></label>
						</div>
						<div id="Leanvar" class="form-group col-md-2">
	                        <label><?=$label_ean;?></label>
	                    </div>
					</div>
						
					<?php
					 
					if (count($product_variants) > 0) {
						$variant_prod = array_combine( explode(';', $product_data['has_variants']),  explode(';', $product_variants[0]['name'])); 
					}
					else {
						$variant_prod= array();
						$imagevariant0 = '';
					    $keys = array_merge(range('A', 'Z'), range('a', 'z'));
					
					    for ($i = 0; $i < 15; $i++) {
					        $imagevariant0 .= $keys[array_rand($keys)];
					    }
					} 
					?>
					<div class="row" id="variant0">
						<div id="Invar0" class="form-group col-md-1">
						   <span class="form-control label label-success" >0</span>
						</div>
						<div id="Itvar" class="form-group col-md-2">
							<input type="text" class="form-control" id="T[]" name="T[]" autocomplete="off" placeholder="<?=$this->lang->line('application_size')?>" value="<?php echo set_value('T[0]', (key_exists("TAMANHO", $variant_prod)) ? $variant_prod['TAMANHO'] : ""); ?>" <?= !$changevariantion?'readonly':''?>/>
						</div>
						<div id="Icvar" class="form-group col-md-2">
						    <input type="text" class="form-control" id="C[]" name="C[]" autocomplete="off" placeholder="<?=$this->lang->line('application_color')?>" value="<?php echo set_value('C[0]', (key_exists("Cor", $variant_prod)) ? $variant_prod['Cor'] : ""); ?>" <?= !$changevariantion?'readonly':''?>/>
						</div>
						<div id="Ivvar" class="form-group col-md-2">
							<?php if (!$changevariantion) { ?>
								<?php 
									$voltagem110= '110V';
									if (key_exists("VOLTAGEM", $variant_prod)) {
										$voltagem110 = $variant_prod['VOLTAGEM'];
									}
									?>
								<input type="text" class="form-control" id="V[]" name="V[]" readonly value="<?= $voltagem110;?>" />
						    <?php } else { ?>
						 		<select class="form-control" id="V[]" name="V[]">
									<?php 
									$voltagem110= true;
									if (key_exists("VOLTAGEM", $variant_prod)) {
										$voltagem110 = ($variant_prod['VOLTAGEM'] == '110V');
									}
									?>
				                    <option value="110V" <?php echo set_select('V[0]', '110V', $voltagem110) ?>>110V</option>
				                    <option value="220V" <?php echo set_select('V[0]', '220V', !$voltagem110) ?>>220V</option>
				                </select>
			                <?php }?>
						</div>

						<div id="Isvar" class="form-group col-md-2">
						    <input type="text" class="form-control" id="sb[]" name="sb[]" autocomplete="off" placeholder="<?=$this->lang->line('application_flavor')?>" value="<?php echo set_value('sb[0]', (key_exists("Sabor", $variant_prod)) ? $variant_prod['Sabor'] : ""); ?>" <?= !$changevariantion?'readonly':''?>/>
						</div>
						<div id="Igvar" class="form-group col-md-2">
						    <input type="text" class="form-control" id="gr[]" name="gr[]" autocomplete="off" placeholder="<?=$this->lang->line('application_degree')?>" value="<?php echo set_value('gr[0]', (key_exists("Grau", $variant_prod)) ? $variant_prod['Grau'] : ""); ?>" <?= !$changevariantion?'readonly':''?>/>
						</div>
						<div id="Ilvar" class="form-group col-md-2">
						    <input type="text" class="form-control" id="ld[]" name="ld[]" autocomplete="off" placeholder="<?=$this->lang->line('application_side')?>" value="<?php echo set_value('ld[0]', (key_exists("Lado", $variant_prod)) ? $variant_prod['Lado'] : ""); ?>" <?= !$changevariantion?'readonly':''?>/>
						</div>
						<div id= "EANV0" class="Ieanvar form-group col-md-2 <?php echo (form_error('EAN_V[0]')) ? 'has-error' : '';  ?>">
	                        <input type="text" class="form-control" id="EAN_V[]" name="EAN_V[]" <?= $readonly;?> autocomplete="off" onchange="checkEAN(this.value,'EANV0','<?= isset($product_variants[0]['id']) ? $product_variants[0]['id'] : 0 ; ?>')" placeholder="<?=$label_ean;?>"  value="<?php echo set_value('EAN_V[0]', isset($product_variants[0]['EAN']) ? $product_variants[0]['EAN'] : '' ) ?>" />
	                    	<span id="EANV0erro" style="display: none;"><i style="color:red"><?=$this->lang->line('application_invalid_ean');?></i></span>
	                    	<?php echo '<i style="color:red">'.form_error('EAN_V[0]').'</i>'; ?>
	                    </div>
	                    <div  class="form-group col-md-2">
	                    	<input type="hidden" id="IMAGEM0" name="IMAGEM[]" value="<?php echo set_value('IMAGEM[0]',isset($product_variants[0]['image']) ? $product_variants[0]['image'] : $imagevariant0 ) ?>" />
	                     	<?php 
	                     	$imagem = base_url('assets/images/system/sem_foto.png');
	                     	if (isset($product_variants[0]['principal_image'])) {
	                     		$imagem = ($product_variants[0]['principal_image'] == '') ? base_url('assets/images/system/sem_foto.png') :  $product_variants[0]['principal_image'];
	                     	}
	                      	?>
	                      	<?php if ($readonly == '') { ?> 
	                     		<a href="#" onclick="AddImage(event,'0')"><img id="foto_variant0" src="<?= $imagem;?>"  class="img-rounded" width="50" height="50" /><i class="fa fa-plus-circle"></i></a>
	                    	<?php  } else { ?> 
	                    		<a href="<?= $imagem;?>"><img id="foto_variant0" src="<?= $imagem;?>"  class="img-rounded" width="50" height="50" /></a>
	                    	<?php  }  ?> 
	                    	<input type="hidden"  name="VARIANT_ID[]" value="<?php echo set_value('VARIANT_ID[0]',isset($product_variants[0]['id']) ? $product_variants[0]['id']  : '') ?>" />
	                    </div>
					</div>
					<div class="input_fields_wrap">
						<?php 
						for($i=1; $i<count($product_variants); $i++) {
       						$variant_prod  = array_combine( explode(';', $product_data['has_variants']),  explode(';', $product_variants[$i]['name']));
						?>
						<div class="row" id="variant<?php echo $i;?>">
							<div id="Invar<?php echo $i;?>" class="form-group col-md-1">
							   <span class="form-control label label-success" ><?php echo $i;?></span>
							</div>
							<div id="Itvar" class="form-group col-md-2">
							    <input type="text" class="form-control" id="T[]" autocomplete="off" placeholder="<?=$this->lang->line('application_size')?>" name="T[]" value="<?php echo set_value('T['.$i.']',(key_exists('TAMANHO',$variant_prod)) ? $variant_prod['TAMANHO'] : ""); ?>" <?=!$changevariantion?'readonly':''?> />
							</div>
							<div id="Icvar" class="form-group col-md-2">
							    <input type="text" class="form-control" id="C[]" autocomplete="off" placeholder="<?=$this->lang->line('application_color')?>" name="C[]" value="<?php echo set_value('C['.$i.']', (key_exists('Cor',$variant_prod)) ? $variant_prod['Cor'] : ""); ?>"  <?=!$changevariantion?'readonly':''?> />
							</div>
							<div id="Ivvar" class="form-group col-md-2">
							    <?php if (!$changevariantion) { ?>
									<?php 
										$voltagem110= '110V';
										if (key_exists("VOLTAGEM", $variant_prod)) {
											$voltagem110 = $variant_prod['VOLTAGEM'];
										}
										?>
									<input type="text" class="form-control" id="V[]" name="V[]" readonly value="<?= $voltagem110;?>" />
						   		<?php } else { ?>
									<select class="form-control" id="V[]" name="V[]">
										<?php 
										$voltagem110= true;
										if (key_exists("VOLTAGEM",$variant_prod)) {
											$voltagem110 = ($variant_prod['VOLTAGEM'] == '110V');
										}
										?>
					                    <option value="110V" <?php echo set_select('V['.$i.']', '110V', $voltagem110) ?>>110V</option>
					                    <option value="220V" <?php echo set_select('V['.$i.']', '220V', !$voltagem110) ?>>220V</option>
					                </select>
				                <?php }?>
							</div>
							<div id="Isvar" class="form-group col-md-2">
							    <input type="text" class="form-control" id="sb[]" autocomplete="off" placeholder="<?=$this->lang->line('application_flavor')?>" name="sb[]" value="<?php echo set_value('sb['.$i.']', (key_exists('Sabor',$variant_prod)) ? $variant_prod['Sabor'] : ""); ?>"  <?=!$changevariantion?'readonly':''?> />
							</div>
							<div id="Igvar" class="form-group col-md-2">
							    <input type="text" class="form-control" id="gr[]" autocomplete="off" placeholder="<?=$this->lang->line('application_degree')?>" name="gr[]" value="<?php echo set_value('gr['.$i.']', (key_exists('Grau',$variant_prod)) ? $variant_prod['Grau'] : ""); ?>"  <?=!$changevariantion?'readonly':''?> />
							</div>
							<div id="Ilvar" class="form-group col-md-2">
							    <input type="text" class="form-control" id="ld[]" autocomplete="off" placeholder="<?=$this->lang->line('application_side')?>" name="ld[]" value="<?php echo set_value('ld['.$i.']', (key_exists('Lado',$variant_prod)) ? $variant_prod['Lado'] : ""); ?>"  <?=!$changevariantion?'readonly':''?> />
							</div>
							<div id= "EANV<?php echo $i;?>" class="Ieanvar form-group col-md-2 <?php echo (form_error('EAN_V['.$i.']')) ? 'has-error' : '';  ?>">
		                        <input type="text" class="form-control" id="EAN_V[]" autocomplete="off" <?= $readonly;?> value="<?php echo set_value('EAN_V['.$i.']', $product_variants[$i]['EAN']) ?>" name="EAN_V[]" onchange="checkEAN(this.value,'EANV<?php echo $i+1;?>','<?=$product_variants[$i]['id']; ?>')" placeholder="<?=$label_ean;?>"  />
		                    	<span id="EANV<?php echo $i;?>erro" style="display: none;"><i style="color:red"><?=$invalid_ean;?></i></span>
		                    	<?php echo '<i style="color:red">'.form_error('EAN_V['.$i.']').'</i>'; ?>
		                    </div>
		                    <div  class="form-group col-md-2">
		                    	<input type="hidden" id="IMAGEM<?php echo $i;?>" name="IMAGEM[]" value="<?php echo set_value('IMAGEM[<?php echo $i;?>]',$product_variants[$i]['image']) ?>" />
		                     	<?php $imagem = ($product_variants[$i]['principal_image'] == '') ? base_url('assets/images/system/sem_foto.png') :  $product_variants[$i]['principal_image']; ?>
		                     	
		                     	<?php if ($readonly == '') { ?> 
	                     			<a href="#" onclick="AddImage(event,'<?php echo $i;?>')"><img id="foto_variant<?php echo $i;?>" src="<?= $imagem;?>"  class="img-rounded" width="50" height="50" /><i class="fa fa-plus-circle"></i></a>
		                    	<?php  } else { ?> 
	                    			<a href="<?= $imagem;?>"><img src="<?= $imagem;?>" class="img-rounded" width="50" height="50" /></a>
                    			<?php  }  ?> 
		                     	
		                     	 <input type="hidden" name="VARIANT_ID[]" value="<?php echo set_value('VARIANT_ID[$id]',$product_variants[$i]['id'] ) ?>" />
		                    </div>
						</div>
						<?php } ?>
					</div>
                    
                    <?php if($changevariantion): ?>
				    <div class="row">
					  <div class="col-md-12 text-center">
					  	<button type="button" class="btn btn-primary add_line"><i class="fa fa-plus-square-o"></i> <?=$this->lang->line('application_variation_add');?></button>
				        <button type="button" class="btn btn-warning" id="reset_variant" name="reset_variant"><i class="fa fa-trash"></i> <?=$this->lang->line('application_clear');?></button>
					  </div>
				    </div>
                    <?php endif; ?>
				</div>
              </div> 
              <!-- /.box-body -->

              <div class="box-footer">
              	<?php if ($readonly == '') { ?> 
                	<button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
					<a href="<?php echo base_url('catalogProducts/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
					<?php if (!$show_attributes_button) {
					$attribute = preg_replace("/[^0-9]/", "", $product_data['category_id']); ?>
					<a href="<?php echo base_url("catalogProducts/attributes/edit/$product_data[id]/$attribute") ?>" class="btn btn-info">Atributos</a>
					<?php } ?>
				<?php } else { ?> 
					<a href="<?php echo base_url('catalogProducts/showcase') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
					<?php if (!$show_attributes_button) {
					$attribute = preg_replace("/[^0-9]/", "", $product_data['category_id']); ?>
					<a href="<?php echo base_url("catalogProducts/attributes/view/$product_data[id]/$attribute") ?>" class="btn btn-info">Atributos</a>
					<?php } ?>
				<?php } ?>
				
              </div>
            </form>
          <!-- /.box-body -->
        </div>
        
        
        
        <?php  
        if ($productssellers) { ?>
        <div class="">
        <hr> 
        <h3><?=$this->lang->line('application_associated_products');?></h3>
         <div class="col-md-3">
            <label for="buscasku" class="normal"><?=$this->lang->line('application_sku');?></label>
            <div class="input-group">
              <input type="search" id="buscasku" onchange="buscaProduto()" class="form-control" placeholder="<?=$this->lang->line('application_sku');?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>
        	
          <div class="col-md-2">
            <div class="input-group" >
              <label for="buscastatus" class="normal"><?=$this->lang->line('application_status');?></label>
              <select class="form-control" id="buscastatus" onchange="buscaProduto()">
                <option value=""><?=$this->lang->line('application_select');?></option>
                <option value="1" selected><?=$this->lang->line('application_active');?></option>
                <option value="2"><?=$this->lang->line('application_inactive');?></option>
              </select>
            </div>
          </div>
          
		  <div class="col-md-2">
            <div class="input-group" >
              <label for="buscaestoque" class="normal"><?=$this->lang->line('application_stock');?></label>
              <select class="form-control" id="buscaestoque" onchange="buscaProduto()">
                <option value=""><?=$this->lang->line('application_select');?></option>
                <option value="1" selected><?=$this->lang->line('application_with_stock');?></option>
                <option value="2"><?=$this->lang->line('application_no_stock');?></option>
              </select>
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="">
              <label for="buscalojas" class="normal">Buscar por Lojas</label>
              <select class="form-control selectpicker show-tick" id="buscalojas" name ="loja[]" onchange="buscaProduto()" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                <?php foreach ($stores_filter as $store_filter) { ?>
                <option value="<?= $store_filter['id'] ?>"><?= $store_filter['name'] ?></option>
           		<?php } ?>
                
              </select>
            </div>
          </div>

          <div class="col-md-1"></div>
          <div class="col-md-1"  >
			  <label  class="normal" style="display: block;">&nbsp; </label>
       		  <button type="button" onclick="clearFilters()" class="btn btn-primary"> <i class="fa fa-eraser"></i> Limpar </button>
        	</div>
        	
        <div class="row"></div>
        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_store');?></th>  
                <th><?=$this->lang->line('application_sku');?></th> 
                <th><?=$this->lang->line('application_qty');?></th> 
                <th><?=$this->lang->line('application_price');?></th> 
                <th><?=$this->lang->line('application_id');?></th>      
                <th><?=$this->lang->line('application_status');?></th>
              </tr>
              </thead>
            </table>
          </div>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->
      </div>
      <?php } ?>  
        
        
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
					<?php 
					$tot = count($product_variants); 
					if ($tot ==0) {$tot=1;}
					for($i=0; $i<$tot; $i++) { ?>
					<div id="showimage<?= $i;?>" style="display:none">
	                  <div class="file-loading">
						  	<input type="file" id="prd_image_variant<?= $i;?>" name="prd_image_variant<?= $i;?>[]" accept="image/*" multiple>						  
	                  </div>
					</div>
					<?php } ?>
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

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
var base_url = "<?php echo base_url(); ?>";
var varn_salvo = "<?php echo count($product_variants)+1;?>";	
var varnoriginal = "<?php echo count($product_variants)+1;?>";
var varnsomado = varnoriginal;
var update_category = "<?php echo in_array('updateCategory', $this->permission) ?>"
var upload_url ="<?= base_url('/Products/saveImageProduct'); ?>";
var delete_url = "<?= base_url($product_data['is_on_bucket']?'CatalogProducts/removeImageProduct':'/assets/plugins/fileinput/catalog_products/delete.php'); ?>";
var verify_ean = "<?= $verify_ean; ?>";
var label_ean = "<?= $label_ean; ?>";
var manageTable;
var base_url = "<?php echo base_url(); ?>";
var table;
var product_catalog_id = "<?php echo $product_data['id']; ?>";
var disabledescription = "<?php echo $readonlytag; ?>"; 
var onBucket = <?php echo $product_data['is_on_bucket']?>

$(document).ready(function() {
	calcDiscount();
    $(".select_group").select2();
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
	
	let sku  = $('#buscasku').val();
	let status  = $('#buscastatus').val();
  	let estoque  = $('#buscaestoque').val();
	var lojas = [];
    $('#buscalojas  option:selected').each(function() {
        lojas.push($(this).val());
    });
	if (lojas == ''){lojas = ''}
	  
	table = $('#manageTable').DataTable({
	    "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
	    "processing": true,
	    "serverSide": true,
	    "scrollX": true,
	    "sortable": true,
	    "searching": true,
	    "serverMethod": "post",
	    "ajax": $.fn.dataTable.pipeline({
	      url: base_url + 'catalogProducts/fetchMyProductCatalogData',
	      data: {product_catalog_id: product_catalog_id, sku:sku, status: status, estoque: estoque, lojas: lojas, view: disabledescription},
	      pages: 2 // number of pages to cache
	    })
	 });
    
    if (disabledescription) {
    	$('#description').summernote('disable');
    }
 	else {
 		$("#mainCatalogNav").addClass('active');
    	$("#addProductCatalogNav").addClass('active');
 	}
    
	
	var wrapper   		= $(".input_fields_wrap"); //Fields wrapper

	initImage(0);
    for(i=1; i<varnoriginal-1; i++) {
    	initImage(i);
    } 

	changeCategory($('#category option:selected').val());
	
    var varn = $("#numvar").val();
	if ((varn >= varn_salvo) && ($("#semvar").prop("checked") == false)) {
     	if($("#sizevar").prop("checked") === true) {
     		var tamanhos = <?php echo json_encode($variacaotamanho); ?>;
     	}
     	if($("#colorvar").prop("checked") === true) {
     		var cores = <?php echo json_encode($variacaocor); ?>;
     	}
     	if($("#voltvar").prop("checked") === true){
     		var voltagem = <?php echo json_encode($variacaovoltagem); ?>;
     	}
     	var eans = <?php echo json_encode($variacaoean); ?>;
		var imagens = <?php echo json_encode($variacaoimagem); ?>;
     	var i;     	
     	varnsomado = Number(varn)+1;
     	var ini = Number(varn_salvo)-1;
     	if (ini === 0) {ini=1;}
     	for (i=ini; i<varn; i++) {
	     	//var linha = '<div class="row" id="variant'+varn+'">';

	     	var linha = '<div class="row" id="variant'+i+'">';
	        linha = linha +'<div id="Invar'+i+'" class="form-group col-md-1"><span class="form-control label label-success">'+i+'</span></div>';
		
			if($("#sizevar").prop("checked") == true){	
				linha = linha + '<div id="Itvar" class="form-group col-md-2"><input type="text" required class="form-control" id="T[]" name="T[]" autocomplete="off" placeholder="<?=$this->lang->line('application_size')?>" value="'+tamanhos[i] +'" /></div>';
			}
			else {
				linha = linha + '<div id="Itvar" class="form-group col-md-2" style="display: none;"><input type="text" class="form-control" id="T[]" name="T[]" autocomplete="off" placeholder="<?=$this->lang->line('application_size')?>" /></div>';
			}
			if($("#colorvar").prop("checked") == true){
				linha = linha + '<div id="Icvar" class="form-group col-md-2"><input type="text" required class="form-control" id="C[]" name="C[]" autocomplete="off" placeholder="<?=$this->lang->line('application_color')?>" value="'+cores[i] +'" /></div>';
			}
			else {
				linha = linha + '<div id="Icvar" class="form-group col-md-2" style="display: none;"><input type="text" class="form-control" id="C[]" name="C[]" autocomplete="off" placeholder="<?=$this->lang->line('application_color')?>" /></div>';
			}
			if($("#voltvar").prop("checked") == true){
				 var sel110 = '';
				 var sel220 = '';
				 if (voltagem[i] == '110V') {
				 	var sel110 = ' selected ';
				 } 
				 else {
				 	var sel220 = ' selected ';
				 }
				 linha = linha + '<div id="Ivvar" class="form-group col-md-2"><select class="form-control" id="V[]" name="V[]"><option value="110V" '+sel110+'>110V</option><option value="220V" '+sel220+'>220V</option></select></div>';
			}
			else {
				linha = linha + '<div id="Ivvar" class="form-group col-md-2" style="display: none;"><select class="form-control" id="V[]" name="V[]"><option value="110V">110V</option><option value="220V">220V</option></select></div>';			
			}

			if($("#saborvar").prop("checked") == true){
				linha = linha + '<div id="Isvar" class="form-group col-md-2"><input type="text" required class="form-control" id="sb[]" name="sb[]" autocomplete="off" placeholder="<?=$this->lang->line('application_flavor')?>" value="'+sabor[i] +'" /></div>';
			}
			else {
				linha = linha + '<div id="Isvar" class="form-group col-md-2" style="display: none;"><input type="text" class="form-control" id="sb[]" name="sb[]" autocomplete="off" placeholder="<?=$this->lang->line('application_flavor')?>" /></div>';
			}

			if($("#grauvar").prop("checked") == true){
				linha = linha + '<div id="Igvar" class="form-group col-md-2"><input type="text" required class="form-control" id="gr[]" name="gr[]" autocomplete="off" placeholder="<?=$this->lang->line('application_degree')?>" value="'+grau[i] +'" /></div>';
			}
			else {
				linha = linha + '<div id="Igvar" class="form-group col-md-2" style="display: none;"><input type="text" class="form-control" id="gr[]" name="gr[]" autocomplete="off" placeholder="<?=$this->lang->line('application_degree')?>" /></div>';
			}

			if($("#ladovar").prop("checked") == true){
				linha = linha + '<div id="Ilvar" class="form-group col-md-2"><input type="text" required class="form-control" id="ld[]" name="ld[]" autocomplete="off" placeholder="<?=$this->lang->line('application_side')?>" value="'+lado[i] +'" /></div>';
			}
			else {
				linha = linha + '<div id="Ilvar" class="form-group col-md-2" style="display: none;"><input type="text" class="form-control" id="ld[]" name="ld[]" autocomplete="off" placeholder="<?=$this->lang->line('application_side')?>" /></div>';
			}
			
			linha = linha + '<div id="EANV'+i+'" class="Ieanvar form-group col-md-2"><input type="text" class="form-control" onchange="checkEAN(this.value,\'EANV'+i+'\',\'0\')" id="EAN_V[]" name="EAN_V[]" autocomplete="off" placeholder="<?=$label_ean?>" value="'+eans[i] +'" /><span id="EANV'+i+'erro" style="display: none;"><i style="color:red"><?=$invalid_ean;?></i></span></div>';
			checkEAN(eans[i],'EANV'+i,'0');
			
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

	
	var token = '<?= $product_data['image']; ?>'; // My Token
	$("#prd_image").fileinput({
        uploadUrl: "<?= base_url('/Products/saveImageProduct'); ?>",
        language: 'pt-BR',
        allowedFileExtensions: ["jpg", "png"],
        minImageWidth: <?=$dimenssion_min_product_image ?? 'null'?>,
        minImageHeight: <?=$dimenssion_min_product_image ?? 'null'?>,
        maxImageWidth: <?=$dimenssion_max_product_image ?? 'null'?>,
        maxImageHeight: <?=$dimenssion_max_product_image ?? 'null'?>,
        enableResumableUpload: true,
        autoOrientImage: false,
        resumableUploadOptions: {
           // uncomment below if you wish to test the file for previous partial uploaded chunks
           // to the server and resume uploads from that point afterwards
           // testUrl: "http://localhost/test-upload.php"
        },
        uploadExtraData: {
	        'uploadToken': token, // for access control / security 
			onBucket: onBucket
        },
        maxFileCount: 5,
        allowedFileTypes: ['image'],    // allow only images
        showCancel: true,
        initialPreviewAsData: true,
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
        overwriteInitial: false,
        theme: 'fas',
        deleteUrl: "<?= base_url($product_data['is_on_bucket']?'CatalogProducts/removeImageProduct':'/assets/plugins/fileinput/catalog_products/delete.php'); ?>"
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
	function changeTheOrderOfImages(params) {
		
		$.ajax({
			type: "POST",
			enctype: 'multipart/form-data',
			data: {
				params: params,
				onBucket:onBucket
			},
			url: base_url+"catalogProducts/orderimages",
			dataType: "json",
			async: false,
		   /// complete: function(response){
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
	
	if($("#semvar").prop("checked") == false){	
		$('[id="T[]"').attr("required",$("#sizevar").prop("checked"));
		$('[id="C[]"').attr("required",$("#colorvar").prop("checked"));
		if($("#sizevar").prop("checked") == false){
		    $('#Ltvar').hide();
		    $('[id=Itvar]').hide();
		}		
		if($("#colorvar").prop("checked") == false){
		    $('#Lcvar').hide();
		    $('[id=Icvar]').hide();
		}		
		if($("#voltvar").prop("checked") == false){
		    $('#Lvvar').hide();
		    $('[id=Ivvar]').hide();
		}	

		if($("#saborvar").prop("checked") == false){
		    $('#Lsvar').hide();
		    $('[id=Isvar]').hide();
		}	

		if($("#grauvar").prop("checked") == false){
		    $('#Lgvar').hide();
		    $('[id=Igvar]').hide();
		}	

		if($("#ladovar").prop("checked") == false){
		    $('#Llvar').hide();
		    $('[id=Ilvar]').hide();
		}	
	
		if ($("#numvar").val() === 0) {
			$('#numvar').val(1);
		}
		$("#variantModal").show();
	}	
	
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
	$.fn.variants = function(){
		$("#variantModal").show();
		var num = 0;
		$('[id="T[]"').attr("required",$("#sizevar").prop("checked"));
		$('[id="C[]"').attr("required",$("#colorvar").prop("checked"));
		if($("#sizevar").prop("checked") == false){	
		    $('#Ltvar').hide();
		    $('[id=Itvar]').hide();
		    num++;
		}    
		if($("#sizevar").prop("checked") == true){	
    		$('#Ltvar').show();
		    $('[id=Itvar]').show();
		}    
		if($("#colorvar").prop("checked") == false){	
		    $('#Lcvar').hide();
		    $('[id=Icvar]').hide();
		    num++;
		}    
		if($("#colorvar").prop("checked") == true){	
		    $('#Lcvar').show();
		    $('[id=Icvar]').show();
		}    
		if($("#voltvar").prop("checked") == false){	
		    $('#Lvvar').hide();
		    $('[id=Ivvar]').hide();
		    num++;
		}    
		if($("#voltvar").prop("checked") == true){	
		    $('#Lvvar').show();
		    $('[id=Ivvar]').show();
		}  
		if($("#saborvar").prop("checked") == true){	
		    $('#Lsvar').show();
		    $('[id=Isvar]').show();
		}    
		if($("#saborvar").prop("checked") == false){	
		    $('#Lsvar').hide();
		    $('[id=Isvar]').hide();
		    num++;
		}    
		if($("#grauvar").prop("checked") == true){	
		    $('#Lgvar').show();
		    $('[id=Igvar]').show();
		}   

		if($("#grauvar").prop("checked") == false){	
		    $('#Lgvar').hide();
		    $('[id=Igvar]').hide();
		    num++;
		}    
		if($("#ladovar").prop("checked") == true){	
		    $('#Llvar').show();
		    $('[id=Ilvar]').show();
		}   

		if($("#ladovar").prop("checked") == false){	
		    $('#Llvar').hide();
		    $('[id=Ilvar]').hide();
		    num++;
		}    
	
		if (num==6) {
			$('#semvar').prop('checked', true);
			$("#variantModal").hide();
			$.fn.variantsclear();
		//	$('[id="Q[]"').attr("required",false);
			
		}
		else {
			//$('#qty').attr("disabled",true);
		//	$('#qty').attr("required",false);
	//		$('[id="Q[]"').attr("required",true);
		}
	}
	
	$('#category').change(function() {
		var idcat = $('#category option:selected').val();
		changeCategory(idcat);
	});
	
	var wrapper   		= $(".input_fields_wrap"); //Fields wrapper
	var add_line        = $(".add_line"); //Add button ID

	$(add_line).click(function(e){ //on add input button click
		e.preventDefault();		
		var varn = $("#numvar").val();

		linnum = varn ;
		somado = varnsomado ;
		var noshow = ' style="display: none;"';
		var linha = '<div class="row" id="variant'+somado+'">';
        var tag = '';
        var required = 'required';
		linha = linha +'<div id="Invar'+somado+'" class="form-group col-md-1"><span class="form-control label label-success">'+linnum+'</span></div>';
		if($("#sizevar").prop("checked") == false){	
			tag = noshow;
            required = '';
		}	
        linha = linha + '<div id="Itvar" class="form-group col-md-2"'+ tag + '><input type="text" ' + required + ' class="form-control" id="T[]" name="T[]" autocomplete="off" placeholder="<?=$this->lang->line('application_size')?>"  /></div>';
		tag = '';
        required = 'required';
		if($("#colorvar").prop("checked") == false){
			tag = noshow;
            required = '';
		}	
			linha = linha + '<div id="Icvar" class="form-group col-md-2"'+ tag + '><input type="text" ' + required + ' class="form-control" id="C[]" name="C[]" autocomplete="off" placeholder="<?=$this->lang->line('application_color')?>"  /></div>';
		tag = '';
        required = 'required';
		if($("#voltvar").prop("checked") == false){
			tag = noshow;
            required = '';
		}
        linha = linha + '<div id="Ivvar" class="form-group col-md-2"'+ tag + '><select class="form-control" ' + required + ' id="V[]" name="V[]"><option value="110V">110V</option><option value="220V">220V</option></select></div>';
		
		linha = linha + '<div id="EANV'+somado+'" class="Ieanvar form-group col-md-2"><input type="text" class="form-control" onchange="checkEAN(this.value,\'EANV'+somado+'\',\'0\')" id="EAN_V[]" name="EAN_V[]" autocomplete="off" placeholder="<?=$label_ean?>" value="" /><span id="EANV'+somado+'erro" style="display: none;"><i style="color:red"><?=$invalid_ean;?></i></span></div>';
		
		linha = linha + '<div  class="form-group col-md-2">'; 
		var charSet =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	    var randomString = '';
	    for (var i = 0; i < 15; i++) {
	        var randomPoz = Math.floor(Math.random() * charSet.length);
	        randomString += charSet.substring(randomPoz,randomPoz+1);
	    }
		linha = linha + '<input type="hidden" id="IMAGEM'+somado+'" name="IMAGEM[]" value="'+randomString+'" />';
		linha = linha + '<a href="#" onclick="AddImage(event,\''+somado+'\')" >';
		linha = linha + '<img id="foto_variant'+somado+'" src="'+base_url+'assets/images/system/sem_foto.png'+'"  class="img-rounded" width="50" height="50" />';
		linha = linha + '<i class="fa fa-plus-circle"></i></a>';
        linha = linha + '</div>';
        
        linha = linha + '<div class="form-group col-md-1"><button type="button" onclick="RemoveVariant(event,'+somado+')" class="btn btn-default"><i class="fa fa-trash"></i></button></div>';
		
        linha = linha + '</div>';
	        
	    $(wrapper).append(linha);
		
		var wrapperimagem = $(".imagem_variant_wrap"); 
		var lin_image ='<div id="showimage'+somado+'" style="display:none"><div class="file-loading"><input type="file" id="prd_image_variant'+somado+'" name="prd_image_variant'+somado+'[]" accept="image/*" multiple></div></div>'
		wrapperimagem.append(lin_image);
		//console.log(lin_image);
		initImage(somado);
		
		varn++;
		varnsomado++;
		
		$('#numvar').val(varn);
		    
	});

	$(reset_variant).click(function(e){ //on clear button click
		e.preventDefault(); 
		$.fn.variantsclear();
	});	

	$.fn.variantsclear = function(){
		for (i = Number(varnoriginal); i <= Number(varnsomado); i++) { 
			div = 'div #variant'+i;
			$(div).remove();
		}		
		varn = varnoriginal - 1;
		if (varn < 1) {varn =1;};
		varnsomado = varnoriginal;
		$('#numvar').val(varn);
	}

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
  
  
function AddBrand(e) {
	$("#addBrandModal").modal('show');
}

function restrict(tis) { // so aceita numero com 2 digitos 
	var prev = tis.getAttribute("data-prev");
  	prev = (prev != '') ? prev : '';
  	if (Math.round(tis.value*100)/100!=tis.value)
  	tis.value=prev;
  	tis.setAttribute("data-prev",tis.value)
}

  	$('#formUpdateProduct').submit(function () {
  	    let variations = [];
  	    let exitApp = false;
        $('input[name="EAN_V[]"]').each(function () {
            if(variations.includes($(this).val()) && $(this).val() != "") exitApp = true;
            variations.push($(this).val());
        });

        if (exitApp) {
            AlertSweet.fire({
                icon: 'warning',
                title: 'Não é permitido o mesmo '+label_ean+' para mais que uma variação. <br><br>Faça o ajuste e tente novamente!'
            });
            return false;
        }
   });


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
function AddImage(e, num) {
	e.preventDefault();
	
	$("#addImagemModal").modal('show');
	
	for  (i=0;i<=varnsomado+1;i++)	{
	    $("#showimage"+i).hide();
	} 
	$('#variant_num').val(num);
	$("#showimage"+num).show();
}

function RemoveVariant(e, num) {
	e.preventDefault(); 

	var varn = $("#numvar").val();
	varn--;
	if (varn==0) {varn=1;}
	$('#numvar').val(varn);
	$('#showimage'+num).remove();
	$('#variant'+num).remove();
		
	j=Number(varnoriginal);
	for(i=j;i<=Number(varnsomado); i++) {
		if($('#Invar'+i).length != 0 ) {
			linnum = j-1;
			$('#Invar'+i).html('<span class="form-control label label-success">'+linnum+'</span>');
			j++;
		}
	}
	
	
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
			tokenimagem: tokenimagem,
			onBucket:onBucket
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
	var onBucket = <?php echo $product_data['is_on_bucket']?>
//console.log('num = '+num);
//console.log("#IMAGEM"+num);
	$.ajax({
		type: "POST",
		enctype: 'multipart/form-data',
		data: {
			tokenimagem: tokenimagem,
			onBucket:onBucket
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
						'onBucket':onBucket
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
				onBucket: onBucket
			},
			url: base_url+"catalogProducts/orderimages",
			dataType: "json",
			async: false,
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

function buscaProduto(){
	let sku  = $('#buscasku').val();
	let status  = $('#buscastatus').val();
  	let estoque  = $('#buscaestoque').val();
	var lojas = [];
    $('#buscalojas  option:selected').each(function() {
        lojas.push($(this).val());
    });
	if (lojas == ''){lojas = ''}
  
  	table.destroy();
  	table = $('#manageTable').DataTable({
	    "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
	    "processing": true,
	    "serverSide": true,
	    "scrollX": true,
	    "sortable": true,
	    "searching": true,
	    "serverMethod": "post",
	    "ajax": $.fn.dataTable.pipeline({
	      url: base_url + 'catalogProducts/fetchMyProductCatalogData',
	      data: {product_catalog_id: product_catalog_id, sku: sku, status: status, estoque: estoque, lojas: lojas, view: disabledescription},
	      pages: 2 // number of pages to cache
	    })
	 });
}

function clearFilters(){
  $('#buscasku').val('');
  $('#buscastatus').val('');
  $('#buscaestoque').val('');
  $('#buscalojas').selectpicker('val', '');
  buscaProduto();
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
	


	function buscaProduto() {
		let sku = $('#buscasku').val();
		let status = $('#buscastatus').val();
		let estoque = $('#buscaestoque').val();
		var lojas = [];
		$('#buscalojas  option:selected').each(function() {
			lojas.push($(this).val());
		});
		if (lojas == '') {
			lojas = ''
		}

		table.destroy();
		table = $('#manageTable').DataTable({
			"language": {
				"url": base_url + 'assets/bower_components/datatables.net/i18n/<?= ucfirst($this->input->cookie('swlanguage')) ?>.lang'
			},
			"processing": true,
			"serverSide": true,
			"scrollX": true,
			"sortable": true,
			"searching": true,
			"serverMethod": "post",
			"ajax": $.fn.dataTable.pipeline({
				url: base_url + 'catalogProducts/fetchMyProductCatalogData',
				data: {
					product_catalog_id: product_catalog_id,
					sku: sku,
					status: status,
					estoque: estoque,
					lojas: lojas,
					view: disabledescription
				},
				pages: 2 // number of pages to cache
			})
		});
	}

	function clearFilters() {
		$('#buscasku').val('');
		$('#buscastatus').val('');
		$('#buscaestoque').val('');
		$('#buscalojas').selectpicker('val', '');
		buscaProduto();
	}

	function calcDiscount() {
		var price = document.getElementById("price");
		var original_price = document.getElementById("original_price");
		var discount = document.getElementById("discount");

		if ((original_price.value != 0) && (price.value != '')) {
			discount.value = parseFloat((1 - (price.value / original_price.value)) * 100).toFixed(2);
		} else {
			discount.value = '';
		}
	}
</script>