

<div class="content-wrapper">
	  
  <?php 
    $data['pageinfo'] = $attributes != '' ? 'application_edit' : 'application_add';
    $data['page_now'] = 'attributes';
    $this->load->view('templates/content_header', $data); 
	
	if ($readonly) {
		$readonly = ' readonly '; 
		$optionRO = ' disabled ';
	}
	else {
		$readonly = ''; 
		$optionRO = '';
	}
  ?>

  <section class="content">
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
          <form  action="<?php base_url("catalogProducts/attributes/$product/$category") ?>" method="post" enctype="multipart/form-data">
            <div class="box-body">
            	<input type="hidden" name="id_product" value="<?= $product ?>">
            	  <?php
                if (validation_errors()) {
                  foreach (explode("</p>",validation_errors()) as $erro) {
                    $erro = trim($erro);
                    if ($erro!="") { 
              		?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      <?php echo $erro."</p>"; ?>
                    </div>
                  <?php	
                    }
                  }
                } ?>
            	<div class="form-group col-md-2 col-xs-12">
            		<?php 
            		if ((!is_null($product_data['principal_image'])) && ($product_data['principal_image'] != '')) {
		                $img = $product_data['principal_image'];
		            } else {
		                $img = base_url('assets/images/system/sem_foto.png');
		            }
					?>
            	     <img src="<?= $img?>" alt="<?=utf8_encode(substr($product_data['name'],0,20))?>" class="rounded" width="100" height="100" />'
            	</div>
            	<div class="form-group col-md-10 col-xs-12">
            	<h3>
                  <span><?php echo $product_data['name']; ?></span>
                <br>
                  <label><?=$label_ean;?>: </label>
                  <span ><?php echo $product_data['EAN']; ?></span>
                </h3>
               </div>
               <div class="form-group col-md-12 col-xs-12">
               	<?php $descricao = substr(htmlspecialchars(strip_tags(str_replace("&nbsp;",' ',str_replace("</p>","\n",str_replace("<br>","\n",$product_data['description'])))), ENT_QUOTES, "utf-8"),0,3800); ?>
                  <label for="description"><?=$this->lang->line('application_description');?></label>
                  <textarea type="text" class="form-control" id="description" name="description" rows="4" readonly ><?php echo $descricao; ?></textarea>
                </div>
                <div class="form-group col-md-12 col-xs-12">
            	  <?=$this->lang->line('application_attributes_warning')?>
                </div>
            	
            	<?php
              	if (count($camposML)) {
              	?>
            
                <div class="col-md-12 col-xs-12">
                 	<hr>
            	    <h3><span><?=$this->lang->line('application_attributes_mercado_livre');?></span></h3>
                </div>
              
              <?php $i=0;
                foreach($camposML as $key => $campoML) { 
					if ($i == 4) {
						echo '<div class="row"></div>';
						$i=0;
					}
					$i++;
					
					if ($attributes != '') {
                  		foreach ($attributes as $attribute) {
                    		if (($attribute['id_atributo'] == $campoML['id_atributo']) && ($attribute['int_to']=='ML')) {
                      			$valueML[$key] = $attribute['valor'];
                    		}
                  		}
					}
              	?>
                
                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error("valorML[$key]")) ? "has-error" : ""; ?>">
                  <label for=""><?=$campoML['nome']?> <?= $campoML['obrigatorio'] == 1 ? '(*)' : '' ?>
                  	<?php echo (($campoML['tooltip']== '') || (is_null($campoML['tooltip']))) ? '' : '<i style="color:red;" class="fa fa-question-circle-o" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="'.$campoML['tooltip'].'"></i>'; ?>
                  </label>
                  <input type="hidden" name="id_atributoML[]" value="<?= $campoML['id_atributo'] ?>" />
                  <input type="hidden" name="nomeML[]" value="<?= $campoML['nome'] ?>" />
                  <input type="hidden" name="obrigML[]" value="<?= $campoML['obrigatorio'] == 1 ? '1' : '0' ?>" />
                  <?php if ($campoML['tipo'] == 'list' || $campoML['tipo'] == 'boolean' ) {
                    $options = json_decode($campoML['valor']); ?>
                    <select name="valorML[]" class="form-control" ?>
                      <option <?=$optionRO;?> value=""><?=$this->lang->line('application_select')?></option>
                      <?php foreach ($options as $option) {
                        if (isset($valueML[$key]) && $valueML[$key] == $option->name) {
                          $default = true;
                        } else {
                          $default = false;
                        } ?>
                        <option <?=$optionRO;?> value="<?=$option->name?>" <?=set_select("valorML[$key]", $option->name, $default) ?>><?=$option->name?></option>
                      <?php } ?>
                    </select>
                    <?php } else {
		                    if (isset($valueML[$key])) {
		                      	$text = $valueML[$key];
		                    } else {
		                      	$text = '';
		                    } 
             			 	if ($campoML['tipo'] == 'string' && $campoML['valor'] != '') {
             			 		?>
                    			<input type="text" autocomplete="off" <?=$readonly;?> class="form-control" name="valorML[]" value="<?php echo set_value("valorML[$key]", $text) ?>" placeholder="Insira <?=$campoML['nome']?>" list="datalist_<?= $campoML['id_atributo'] ?>" />
             					<?php 
             			 		$options = json_decode($campoML['valor']); 
             			 		?>
								<datalist id="datalist_<?= $campoML['id_atributo'] ?>">
									<?php 
									foreach ($options as $option) {
										?>
				                        <option value="<?=$option->name?>"</option>
				                     	<?php	
									}
									?>
								</datalist>
							<?php
             			 	} else {
             			 		?>
                    			<input type="text" autocomplete="off" <?=$readonly;?> class="form-control" name="valorML[]" value="<?php echo set_value("valorML[$key]", $text) ?>" placeholder="Insira <?=$campoML['nome']?>" />
             					<?php 
             			 	}
                 			?>
                 
                  <?php } ?>
                  <?php echo "<i style='color:red'>".form_error("valorML[$key]")."</i>"; ?>
                </div>
              <?php } 
              }
              ?>
              
              <?php
              if (count($camposVIA)) {
              ?>
              <div class="form-group col-md-12 col-xs-12">
              	<hr>
            	<h3><span><?=$this->lang->line('application_attributes_via_varejo');?></span></h3>
              </div>
              
              <?php $i=0;
			 
                foreach($camposVIA as $key => $campoVIA) { 
              	    
					if ($i == 4) {
						echo '<div class="row"></div>';
						$i=0;
					}
					$i++;
					if ($attributes != '') {  // pego os attributos 
	                	foreach ($attributes as $attribute) {
							if (($attribute['id_atributo'] == $campoVIA['id_atributo']) && ($attribute['int_to']=='VIA')) {
	                      		$valueVia[$key] = $attribute['valor'];
	                    	}
	                  	}
               		}
              	?>
                
                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error("valorVia[$key]")) ? "has-error" : ""; ?>">
                  <label for=""><?=$campoVIA['nome']?> <?= $campoVIA['obrigatorio'] == 1 ? '(*)' : '' ?>
                  	<?php echo (($campoVIA['tooltip']== '') || (is_null($campoVIA['tooltip']))) ? '' : '<i style="color:red;" class="fa fa-question-circle-o" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="'.$campoVIA['tooltip'].'"></i>'; ?>
                  </label>
                  <input type="hidden" name="id_atributoVia[]" value="<?= $campoVIA['id_atributo'] ?>" />
                  <input type="hidden" name="nomeVia[]" value="<?= $campoVIA['nome'] ?>" />
                  <input type="hidden" name="obrigVia[]" value="<?= $campoVIA['obrigatorio'] == 1 ? '1' : '0' ?>" />
                  <?php if ($campoVIA['tipo'] == 'list' || $campoVIA['tipo'] == 'boolean' ) {
                    $options = json_decode($campoVIA['valor']); ?>
                    <select name="valorVia[]" class="form-control" ?>
                      <option <?=$optionRO;?> value=""><?=$this->lang->line('application_select')?></option>
                      <?php foreach ($options as $option) {
                        if (isset($valueVia[$key]) && $valueVia[$key] == $option->udaValue) {
                          $default = true;
                        } else {
                          $default = false;
                        } ?>
                        <option <?=$optionRO;?> value="<?=$option->udaValue?>" <?=set_select("valorVia[$key]", $option->udaValue, $default) ?>><?=$option->udaValue?></option>
                      <?php } ?>
                    </select>
                    <?php } else {
		                    if (isset($valueVia[$key])) {
		                      	$text = $valueVia[$key];
		                    } else {
		                      	$text = '';
		                    } 
             			 	if ($campoVIA['tipo'] == 'string' && $campoVIA['valor'] != '') {
             			 		?>
                    			<input type="text" autocomplete="off" <?=$readonly;?> class="form-control" name="valorVia[]" value="<?php echo set_value("valorVia[$key]", $text) ?>" placeholder="Insira <?=$campoVIA['nome']?>" list="datalist_<?= $campoVIA['id_atributo'] ?>" />
             					<?php 
             			 		$options = json_decode($campoVIA['valor']); 
             			 		?>
								<datalist id="datalist_<?= $campoVIA['id_atributo'] ?>">
									<?php 
									foreach ($options as $option) {
										?>
				                        <option value="<?=$option->udaValue?>"</option>
				                     	<?php	
									}
									?>
								</datalist>
							<?php
             			 	} else {
             			 		?>
                    			<input type="text" autocomplete="off" <?=$readonly;?> class="form-control" name="valorVia[]" value="<?php echo set_value("valorVia[$key]", $text) ?>" placeholder="Insira <?=$campoVIA['nome']?>" />
             					<?php 
             			 	}
                 			?>
                 
                  <?php } ?>
                  <?php echo "<i style='color:red'>".form_error("valorVia[$key]")."</i>"; ?>
                </div>
                
              <?php }
              } ?>
              
              
               <?php
              foreach ($sellercenters as $sellercenter) {
              	$titSellercenter = $sellercenter; 
              	$sellercenter = str_replace('&','',$sellercenter);
               if (count(${'campos'.$sellercenter})) {?>
              <div class="form-group col-md-12 col-xs-12" >
              	<hr>
            	<h3><span><?=$this->lang->line('application_attributes');?>&nbsp;</span><?=$titSellercenter;?></h3>
              </div>
              
              <?php $i=0;
			 	
                foreach(${'campos'.$sellercenter} as $key => $campoSellerCenter) { 
              	    
					if ($i == 4) {
						echo '<div class="row"></div>';
						$i=0;
					}
					$i++;
					if ($attributes != '') {  // pego os attributos 
	                	foreach ($attributes as $attribute) {
							if (($attribute['id_atributo'] == $campoSellerCenter['id_atributo']) && ($attribute['int_to']==$sellercenter)) {
	                      		$valueSellerCenter[$key] = $attribute['valor'];
	                    	}
	                  	}
               		}
              	?>
                
                <div class="form-group col-md-3 col-xs-12 <?php echo (form_error("valor".$sellercenter."[$key]")) ? "has-error" : ""; ?>">
                  <label for=""><?=$campoSellerCenter['nome']?> <?= $campoSellerCenter['obrigatorio'] == 1 ? '(*)' : '' ?>
                  	<?php echo (($campoSellerCenter['tooltip']== '') || (is_null($campoSellerCenter['tooltip']))) ? '' : '<i style="color:red;" class="fa fa-question-circle-o" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="'.$campoSellerCenter['tooltip'].'"></i>'; ?>
                  </label>
                  <input type="hidden" name="id_atributo<?=$sellercenter;?>[]" value="<?= $campoSellerCenter['id_atributo'] ?>" />
                  <input type="hidden" name="nome<?=$sellercenter;?>[]" value="<?= $campoSellerCenter['nome'] ?>" />
                  <input type="hidden" name="obrig<?=$sellercenter;?>[]" value="<?= $campoSellerCenter['obrigatorio'] == 1 ? '1' : '0' ?>" />
                  <?php if ($campoSellerCenter['tipo'] == 'list' || $campoSellerCenter['tipo'] == 'boolean' ) {
                    $options = json_decode($campoSellerCenter['valor']); ?>
                    <select name="valor<?=$sellercenter;?>[]" class="form-control" ?>
                      <option value=""><?=$this->lang->line('application_select')?></option>
                      <?php foreach ($options as $option) {
                        if (isset($valueSellerCenter[$key]) && $valueSellerCenter[$key] == $option->FieldValueId) {
                          $default = true;
                        } else {
                          $default = false;
                        } ?>
                        <option value="<?=$option->FieldValueId?>" <?=set_select("valor".$sellercenter."[$key]", $option->FieldValueId, $default) ?>><?=$option->Value?></option>
                      <?php } ?>
                    </select>
                    <?php } else {
		                    if (isset($valueSellerCenter[$key])) {
		                      	$text = $valueSellerCenter[$key];
		                    } else {
		                      	$text = '';
		                    } 
             			 	if ($campoSellerCenter['tipo'] == 'string' && $campoSellerCenter['valor'] != '' && $campoSellerCenter['valor'] != '[]' ) {
             			 		?>
                    			<input type="text" autocomplete="off" class="form-control" name="valor<?=$sellercenter;?>[]" value="<?php echo set_value("valor".$sellercenter."[$key]", $text) ?>" placeholder="Insira <?=$campoSellerCenter['nome']?>" list="datalist_<?= $campoSellerCenter['id_atributo'] ?>" />
             					<?php 
             			 		$options = json_decode($campoSellerCenter['valor']); 
             			 		?>
								<datalist id="datalist_<?= $campoSellerCenter['id_atributo'] ?>">
									<?php 
									foreach ($options as $option) {
										?>
				                        <option value="<?=$option->FieldValueId?>"</option>
				                     	<?php	
									}
									?>
								</datalist>
							<?php
             			 	} else {
             			 		?>
                    			<input type="text" autocomplete="off" class="form-control" name="valor<?=$sellercenter;?>[]" value="<?php echo set_value("valorsellercenter[$key]", $text) ?>" placeholder="Insira <?=$campoSellerCenter['nome']?>" />
             					<?php 
             			 	}
                 			?>
                 
                  <?php } ?>
                  <?php echo "<i style='color:red'>".form_error("valor".$sellercenter."[$key]")."</i>"; ?>
                </div>
                
              <?php
                }
               }
              }
              ?>
                
            </div>
            <div class="box-footer">
            	<?php if ($readonly == '') { ?>
	              <button type="submit" id="" class="btn btn-primary"><?=$this->lang->line('application_update_changes')?></button>
	              <a href="<?php echo base_url("catalogProducts") ?>" class="btn btn-warning"><?=$this->lang->line('application_back')?></a>&nbsp;
	              <a href="<?php echo base_url("catalogProducts/update/$product") ?>" class="btn btn-info"><?=$this->lang->line('application_product')?></a>
            	<?php } else { ?>
            	  <a href="<?php echo base_url("catalogProducts/showcase") ?>" class="btn btn-warning"><?=$this->lang->line('application_back')?></a>&nbsp;
             	  <a href="<?php echo base_url("catalogProducts/view/$product") ?>" class="btn btn-info"><?=$this->lang->line('application_product')?></a>
            	<?php } ?>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<script type="text/javascript">
$(document).ready(function() {
  $("#mainCatalogNav").addClass('active');
  $("#addProductCatalogNav").addClass('active');
	
});
	
</script>
