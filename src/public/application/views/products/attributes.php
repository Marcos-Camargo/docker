

<div class="content-wrapper">
	  
    <?php 
      $data['pageinfo'] = $attributes != '' ? 'application_edit' : 'application_add';
      $data['page_now'] = 'attributes';
      $this->load->view('templates/content_header', $data); 
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
            <form  action="<?php base_url("products/attributes/$product/$category") ?>" method="post" enctype="multipart/form-data">
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
                    <label><?=$this->lang->line('application_sku');?>: </label>
                    <span ><?php echo $product_data['sku']; ?></span>
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
                <h4><span><?='Categoria: '. $category_ml;?></span></h4>
                <?php if ($enriched_ml) { ?>
                <h6><span>Enriquecido</span></h6>
                <?php } ?>
                </div>
                
                <?php $i=0;
                  foreach($camposML as $key => $campoML) { 
                    if ($i == 4) {
                      echo '<div class="row"></div>';
                      $i=0;
                    }
                    $i++;
             
                    if ($attributes != '') {  // pego os attributos 
                      foreach ($attributes as $attribute) {
                        if (($attribute['id_atributo'] == $campoML['id_atributo']) && ($attribute['int_to'] == 'ML')) {
                          $valueML[$key] = $attribute['valor'];
                        }
                      }
                     }
                  ?>
                  
                  <div class="form-group col-md-3 col-xs-12 <?php echo (form_error("valorML[$key]")) ? "has-error" : ""; ?>">
                    <label for=""><?=$campoML['nome']?> <?= $campoML['obrigatorio'] == 1 ? '(*)' : '' ?> 
                      <?php echo (($campoML['tooltip']== '') || (is_null($campoML['tooltip']))) ? '' : '<i style="color:red;" class="fa fa-question-circle-o" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="'.str_replace('"',"",$campoML['tooltip']).'"></i>'; ?>
                    </label>
                    <input type="hidden" name="id_atributoML[]" value="<?= $campoML['id_atributo'] ?>" />
                    <input type="hidden" name="nomeML[]" value="<?= $campoML['nome'] ?>" />
                    <input type="hidden" name="obrigML[]" value="<?= $campoML['obrigatorio'] == 1 ? '1' : '0' ?>" />
                    <?php if ($campoML['tipo'] == 'list' || $campoML['tipo'] == 'boolean' ) {
                      $options = json_decode($campoML['valor']); ?>
                      <select name="valorML[]" class="form-control" ?>
                        <option value=""><?=$this->lang->line('application_select')?></option>
                        <?php foreach ($options as $option) {
                          if (isset($valueML[$key]) && $valueML[$key] == $option->name) {
                            $default = true;
                          } else {
                            $default = false;
                          } ?>
                          <option value="<?=$option->name?>" <?=set_select("valorML[$key]", $option->name, $default) ?>><?=$option->name?></option>
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
                            <input type="text" autocomplete="off" class="form-control" name="valorML[]" value="<?php echo set_value("valorML[$key]", $text) ?>" placeholder="Insira <?=$campoML['nome']?>" list="datalist_<?= $campoML['id_atributo'] ?>" />
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
                            <input type="text" autocomplete="off" class="form-control" name="valorML[]" value="<?php echo set_value("valorML[$key]", $text) ?>" placeholder="Insira <?=$campoML['nome']?>" />
                         <?php 
                        }
                         ?>
                   
                    <?php } ?>
                    <?php echo "<i style='color:red'>".form_error("valorML[$key]")."</i>"; ?>
                  </div>
                  
                  <?php
                  }
                } ?>
  
  
                <?php if (count($camposVIA)) {
                ?>
                <div class="form-group col-md-12 col-xs-12"  <?php echo (count($camposVIA) == 0) ? 'style="display:none"' : '' ; ?>>
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
                        <option value=""><?=$this->lang->line('application_select')?></option>
                        <?php foreach ($options as $option) {
                          if (isset($valueVia[$key]) && $valueVia[$key] == $option->udaValue) {
                            $default = true;
                          } else {
                            $default = false;
                          } ?>
                          <option value="<?=$option->udaValue?>" <?=set_select("valorVia[$key]", $option->udaValue, $default) ?>><?=$option->udaValue?></option>
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
                            <input type="text" autocomplete="off" class="form-control" name="valorVia[]" value="<?php echo set_value("valorVia[$key]", $text) ?>" placeholder="Insira <?=$campoVIA['nome']?>" list="datalist_<?= $campoVIA['id_atributo'] ?>" />
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
                            <input type="text" autocomplete="off" class="form-control" name="valorVia[]" value="<?php echo set_value("valorVia[$key]", $text) ?>" placeholder="Insira <?=$campoVIA['nome']?>" />
                         <?php 
                        }
                         ?>
                   
                    <?php } ?>
                    <?php echo "<i style='color:red'>".form_error("valorVia[$key]")."</i>"; ?>
                  </div>
                <?php } 
                } ?>
                
  
  
  
  
  
                <?php if (isset($camposNM) && count($camposNM)) {
                ?>
                <div class="form-group col-md-12 col-xs-12"  <?php echo (count($camposNM) == 0) ? 'style="display:none"' : '' ; ?>>
                  <hr>
                <h3><span><?=$this->lang->line('application_attributes_nm_sc');?></span></h3>
                </div>
                
                <?php $i=0;
         
                  foreach($camposNM as $key => $campoNM) { 
                      
            if ($i == 4) {
              echo '<div class="row"></div>';
              $i=0;
            }
            $i++;
            if ($attributes != '') {  // pego os attributos 
                      foreach ($attributes as $attribute) {
                if (($attribute['id_atributo'] == $campoNM['id_atributo']) && ($attribute['int_to'] == 'NM')) {
                              $valueNM[$key] = $attribute['valor'];
                          }
                        }
                     }
                  ?>
                  
                  <div class="form-group col-md-3 col-xs-12 <?php echo (form_error("valorNM[$key]")) ? "has-error" : ""; ?>">
                    <label for=""><?=$campoNM['nome']?> <?= $campoNM['obrigatorio'] == 1 ? '(*)' : '' ?>
                      <?php echo (($campoNM['tooltip']== '') || (is_null($campoNM['tooltip']))) ? '' : '<i style="color:red;" class="fa fa-question-circle-o" 
                        aria-hidden="true" data-toggle="tooltip" data-placement="top" title="'.$campoNM['tooltip'].'"></i>'; ?>
                    </label>
                    <input type="hidden" name="id_atributoNM[]" value="<?= $campoNM['id_atributo'] ?>" />
                    <input type="hidden" name="nomeNM[]" value="<?= $campoNM['nome'] ?>" />
                    <input type="hidden" name="obrigNM[]" value="<?= $campoNM['obrigatorio'] == 1 ? '1' : '0' ?>" />
                    <?php if ($campoNM['tipo'] == 'list' || $campoNM['tipo'] == 'boolean' ) {
                      $options = json_decode($campoNM['valor']); ?>
                      <select name="valorNM[]" class="form-control" ?>
                        <option value=""><?=$this->lang->line('application_select')?></option>
                        <?php foreach ($options as $option) {
                          if (isset($valueNM[$key]) && $valueNM[$key] == $option->value) {
                            $default = true;
                          } else {
                            $default = false;
                          } ?>
                          <option value="<?=$option->value?>" <?=set_select("valorNM[$key]", $option->value, $default) ?>><?=$option->value?></option>
                        <?php } ?>
                      </select>
                      <?php } else {
                          if (isset($valueNM[$key])) {
                              $text = $valueNM[$key];
                          } else {
                              $text = '';
                          } 
                        if ($campoNM['tipo'] == 'string' && $campoNM['valor'] != '') {
                          ?>
                            <input type="text" autocomplete="off" class="form-control" name="valorNM[]" value="<?php echo set_value("valorNM[$key]", $text) ?>" 
                                      placeholder="Insira <?=$campoNM['nome']?>" list="datalist_<?= $campoNM['id_atributo'] ?>" />
                         <?php 
                          $options = json_decode($campoNM['valor']); 
                          ?>
                  <datalist id="datalist_<?= $campoNM['id_atributo'] ?>">
                    <?php 
                    foreach ($options as $option) {
                      ?>
                                  <option value="<?=$option->value?>"</option>
                                 <?php	
                    }
                    ?>
                  </datalist>
                <?php
                        } else {
                          ?>
                            <input type="text" autocomplete="off" class="form-control" name="valorNM[]" value="<?php echo set_value("valorNM[$key]", $text) ?>" 
                                      placeholder="Insira <?=$campoNM['nome']?>" />
                         <?php 
                        }
                         ?>
                   
                    <?php } ?>
                    <?php echo "<i style='color:red'>".form_error("valorNM[$key]")."</i>"; ?>
                  </div>
                <?php } 
                } ?>
  
  
  
  
  
              <?php if (isset($camposORT) && count($camposORT)) {
                ?>
                <div class="form-group col-md-12 col-xs-12"  <?php echo (count($camposORT) == 0) ? 'style="display:none"' : '' ; ?>>
                  <hr>
                <h3><span><?=$this->lang->line('application_attributes_ort_sc');?></span></h3>
                </div>
                
                <?php $i=0;
         
                  foreach($camposORT as $key => $campoORT) { 
                      
            if ($i == 4) {
              echo '<div class="row"></div>';
              $i=0;
            }
            $i++;
            if ($attributes != '') {  // pego os attributos 
                      foreach ($attributes as $attribute) {
                if (($attribute['id_atributo'] == $campoORT['id_atributo']) && ($attribute['int_to'] == 'ORT')) {
                              $valueORT[$key] = $attribute['valor'];
                          }
                        }
                     }
                  ?>
                  
                  <div class="form-group col-md-3 col-xs-12 <?php echo (form_error("valorORT[$key]")) ? "has-error" : ""; ?>">
                    <label for=""><?=$campoORT['nome']?> <?= $campoORT['obrigatorio'] == 1 ? '(*)' : '' ?>
                      <?php echo (($campoORT['tooltip']== '') || (is_null($campoORT['tooltip']))) ? '' : '<i style="color:red;" class="fa fa-question-circle-o" 
                        aria-hidden="true" data-toggle="tooltip" data-placement="top" title="'.$campoORT['tooltip'].'"></i>'; ?>
                    </label>
                    <input type="hidden" name="id_atributoORT[]" value="<?= $campoORT['id_atributo'] ?>" />
                    <input type="hidden" name="nomeORT[]" value="<?= $campoORT['nome'] ?>" />
                    <input type="hidden" name="obrigORT[]" value="<?= $campoORT['obrigatorio'] == 1 ? '1' : '0' ?>" />
                    <?php if ($campoORT['tipo'] == 'list' || $campoORT['tipo'] == 'boolean' ) {
                      $options = json_decode($campoORT['valor']); ?>
                      <select name="valorORT[]" class="form-control" ?>
                        <option value=""><?=$this->lang->line('application_select')?></option>
                        <?php foreach ($options as $option) {
                          if (isset($valueORT[$key]) && $valueORT[$key] == $option->value) {
                            $default = true;
                          } else {
                            $default = false;
                          } ?>
                          <option value="<?=$option->value?>" <?=set_select("valorORT[$key]", $option->value, $default) ?>><?=$option->value?></option>
                        <?php } ?>
                      </select>
                      <?php } else {
                          if (isset($valueORT[$key])) {
                              $text = $valueORT[$key];
                          } else {
                              $text = '';
                          } 
                        if ($campoORT['tipo'] == 'string' && $campoORT['valor'] != '') {
                          ?>
                            <input type="text" autocomplete="off" class="form-control" name="valorORT[]" value="<?php echo set_value("valorORT[$key]", $text) ?>" 
                                      placeholder="Insira <?=$campoORT['nome']?>" list="datalist_<?= $campoORT['id_atributo'] ?>" />
                         <?php 
                          $options = json_decode($campoORT['valor']); 
                          ?>
                  <datalist id="datalist_<?= $campoORT['id_atributo'] ?>">
                    <?php 
                    foreach ($options as $option) {
                      ?>
                                  <option value="<?=$option->value?>"</option>
                                 <?php	
                    }
                    ?>
                  </datalist>
                <?php
                        } else {
                          ?>
                            <input type="text" autocomplete="off" class="form-control" name="valorORT[]" value="<?php echo set_value("valorORT[$key]", $text) ?>" 
                                      placeholder="Insira <?=$campoORT['nome']?>" />
                         <?php 
                        }
                         ?>
                   
                    <?php } ?>
                    <?php echo "<i style='color:red'>".form_error("valorORT[$key]")."</i>"; ?>
                  </div>
                <?php } 
                } ?>
  
  
  
  
                
                <?php
                foreach ($sellercenters as $sellercenter) {
                  $titSellercenter = $sellercentersnames[$sellercenter]; 
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

                    if ($only_admin != 1 && in_array($campoSellerCenter['nome'], $show_marketplace_attributes_only_to_admin)) {
                        continue;
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
                              // Valor de atributo inativo, não deve ser listado.
                              if (isset($option->IsActive) && !$option->IsActive) {
                                continue;
                              }

                              if (isset($valueSellerCenter[$key]) && $valueSellerCenter[$key] == $option->FieldValueId) {
                                $default = true;
                              } else {
                                $default = false;
                                if ($campoSellerCenter['nome'] == 'Cor') {
                                  if ($cor_default !== false) {
                                    if ($option->Value == $cor_default) {
                                      $default = true;
                                    }
                                  }
                                }
                                if ($campoSellerCenter['nome'] == 'Tamanho') {
                                  if ($tamanho_default !== false) {
                                    if ($option->Value == $tamanho_default) {
                                      $default = true;
                                    }
                                  }
                                }
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
                    <?php $options = json_decode($campoSellerCenter['valor']); ?>
                    <datalist id="datalist_<?= $campoSellerCenter['id_atributo'] ?>">
                        <?php foreach ($options as $option) { ?>
                            <option value="<?=$option->FieldValueId?>"</option>
                        <?php } ?>
                    </datalist>
                    <?php
                        } elseif($campoSellerCenter['tipo'] == 'date') { ?>
                        <input type="date" autocomplete="off" class="form-control" name="valor<?=$sellercenter;?>[]" value="<?php echo set_value("valorsellercenter[$key]", $text) ?>" placeholder="Insira <?=$campoSellerCenter['nome']?>" />
                    <?php
                        } else { ?>
                        <input type="text" autocomplete="off" class="form-control" name="valor<?=$sellercenter;?>[]" value="<?php echo set_value("valorsellercenter[$key]", $text) ?>" placeholder="Insira <?=$campoSellerCenter['nome']?>" />
                     <?php
                        }
                    } ?>
                    <?php echo "<i style='color:red'>".form_error("valor".$sellercenter."[$key]")."</i>"; ?>
                </div>
                  
                <?php
                  }
                 }
                }
                ?>
                <div class="form-group col-md-12 col-xs-12">
                  <hr>
                  <h3><span><?=$this->lang->line('application_attributes_custom')?></span></h3>
                </div>
                <div class="form-group col-md-12 col-xs-12 d-flex justify-content-center">
                  <div class="col-md-6 col-xs-12">
                    <div class="input-group">
                      <input type="text" autocomplete="off" class="form-control" maxlength="128" placeholder="<?=$this->lang->line('application_enter_attribute_custom')?>" list="attrCustom" />
                      <span class="input-group-btn">
                        <button type="button" class="btn btn-primary btn-flat" id="addAttributeCustom"><i class="fa fa-plus"></i> <?=$this->lang->line('application_add')?></button>
                      </span>
                    </div>
                    <datalist id="attrCustom">
                      <?php
                      foreach ($allAttributesCustom as $attrCustom)
                        echo "<option value='{$attrCustom['name_attr']}'></option>";
                      ?>
                    </datalist>
                  </div>
                </div>
                <div class="form-group col-md-12 col-xs-12 d-flex justify-content-center">
                  <div class="col-md-8 col-xs-12">
                    <table class="table table-hover" id="tableAttrbuteCustom" style="display: <?=empty(set_value("attributeCustom_name[]")) ? (count($camposCustom) ? 'table' : 'none') : (count(set_value("attributeCustom_name[]")) ? 'table' : 'none')?>">
                        <thead>
                          <tr>
                              <td><b><?=$this->lang->line('application_attribute')?></b></td>
                              <td><b><?=$this->lang->line('application_value')?></b></td>
                              <td><b><?=$this->lang->line('application_action')?></b></td>
                          </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (empty(set_value("attributeCustom_name[]"))) {
                                foreach ($camposCustom as $campoCustom) {
                                    echo "
                                  <tr data-db='true'>
                                      <td><input type='text' name='attributeCustom_name[]' class='form-control' maxlength='255' value='{$campoCustom['name_attr']}' readonly required></td>
                                      <td><input type='text' name='attributeCustom_value[]' class='form-control' maxlength='255' value='{$campoCustom['value_attr']}' required></td>
                                      <td>
                                          <button type='button' class='btn btn-danger btn-sm btn-flat removeAttrCustom'>
                                              <i class='fa fa-trash'></i>
                                          </button>
                                      </td>
                                  </tr>
                                ";
                                }
                            } else { // é roolback da pagina, pega o que usuario digitou
                                if (is_array(set_value("attributeCustom_name[]"))) {
  
                                    $attrCustonsName  = set_value("attributeCustom_name[]");
                                    $attrCustonsValue = set_value("attributeCustom_value[]");
  
                                    foreach ($attrCustonsName as $keyAttrCustom => $_) {
                                        echo "
                                          <tr data-db='true'>
                                              <td><input type='text' name='attributeCustom_name[]' class='form-control' maxlength='255' value='{$attrCustonsName[$keyAttrCustom]}' readonly></td>
                                              <td><input type='text' name='attributeCustom_value[]' class='form-control' maxlength='255' value='{$attrCustonsValue[$keyAttrCustom]}'></td>
                                              <td>
                                                  <button type='button' class='btn btn-danger btn-sm btn-flat removeAttrCustom'>
                                                      <i class='fa fa-trash'></i>
                                                  </button>
                                              </td>
                                          </tr>
                                        ";
                                    }
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                  </div>
                </div>
                <?php if (isset($collections)) {?>
                <div class="form-group col-md-12 col-xs-12">
                  <hr>
                  <h3><span>Navegação</span></h3><!--- tradução -->
                </div>
                <div class="form-group col-md-12 col-xs-12 d-flex justify-content-center">
  
                    <div class="input-group col-md-12 col-xs-12">
                    <select class="form-control selectpicker show-tick" id="product_collections" name="product_collections[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 5" title="Selecione para escolher a navegação">
                      <?php foreach ($collections as $collection) { ?>
                        <option value="<?= $collection['id'] ?>" <?php echo set_select('collections', $collection['id'], in_array($collection['id'], $productCollections)); ?>><?= $collection['path'] ?></option>
                      <?php } ?>
                    </select>
                    <?php echo '<i style="color:red">' . form_error('collections[]') . '</i>'; ?>
                </div>
              </div>
              <?php }?>
            </div>
            <span id="msg_error" style="display: none;">A seleção é obrigatória</span>
              <div class="box-footer">
                <button type="submit" id="btn_save_attributes" class="btn btn-primary"
                    <?= $product_data['status'] == Model_products::DELETED_PRODUCT ? 'style="display:none;"' : '' ?>
                ><?=$this->lang->line('application_update_changes')?></button>
                <a href="<?php echo base_url("products") ?>" class="btn btn-warning"><?=$this->lang->line('application_back')?></a>&nbsp;
                <a href="<?php echo base_url("products/update/$product") ?>" class="btn btn-info"><?=$this->lang->line('application_product')?></a>
                <?php if (in_array('enrichProduct', $user_permission)) : ?>
                <a href="javascript:return void();" class="btn btn-primary" id="sendQueueOmnilogic">Enriquecer Categorias</a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </div>
  
  <script src="<?php echo base_url('assets/dist/js/components/products/product.disable.form.js') ?>"></script>
  <script type="text/javascript">
      var productDeleted = '<?= $product_data['status'] == Model_products::DELETED_PRODUCT ?>';
      $(document).ready(function () {
          if(productDeleted) {
              (new ProductDisableForm({
                  form: $('#formUpdateProduct')
              })).disableForm();
          }
          removeAttrDataList();
  
      });
  
  const removeAttrDataList = () => {
  
      let arrAttrExisting = [];
      $('#tableAttrbuteCustom tbody tr').each(function (){
          arrAttrExisting.push(removeAcento($('td:eq(0) input',this).val()).toUpperCase());
      });
  
      $('#attrCustom option').each(function (){
         if (arrAttrExisting.includes(removeAcento($(this).attr('value')).toUpperCase())) $(this).remove();
      });
  }
  
  $('#addAttributeCustom').click(function (){
      const input = $(this).closest('.input-group').find('input');
      let stopApp = false;
      let dataDb = false;
  
      if (input.val().trim() == '') {
          AlertSweet.fire({
              icon: 'warning',
              title: 'Não é permitido adicionar atributo em branco!'
          });
          return false;
      }
  
      $('#attrCustom option').each(function (){
          if (removeAcento($(this).attr('value')).toUpperCase() === removeAcento(input.val()).toUpperCase()) dataDb = true;
      });
  
      $('#tableAttrbuteCustom tbody tr').each(function (){
          if (removeAcento($('td:eq(0) input',this).val()).toUpperCase() === removeAcento(input.val()).toUpperCase()) {
              AlertSweet.fire({
                  icon: 'warning',
                  title: 'Não é permitido o mesmo atributo mais que um vez!'
              });
              stopApp = true;
          }
      });
      if (stopApp) return false;
  
      $('#tableAttrbuteCustom tbody')
          .append(`
          <tr data-db="${dataDb}">
              <td><input type='text' name='attributeCustom_name[]' maxlength='255' class='form-control' value='${input.val()}' readonly required></td>
              <td><input type='text' name='attributeCustom_value[]' maxlength='255' class='form-control' value='' required></td>
              <td>
                  <button type='button' class='btn btn-danger btn-sm btn-flat removeAttrCustom'>
                      <i class='fa fa-trash'></i>
                  </button>
              </td>
          </tr>`).find('tr:last td:eq(1) input').focus();
  
      $(`#attrCustom option[value="${input.val()}"]`).remove();
      input.val('');
      if ($('#tableAttrbuteCustom tbody tr').length === 1) $('#tableAttrbuteCustom').show();
  });
  
  $(document).on('click', '.removeAttrCustom', function (){
      $(this).attr('disabled', true);
      const input = $(this).closest('tr').find('td:eq(0) input').val();
      $(this).closest('tr').remove();
  
      if ($(this).closest('tr').data('db')) $(`#attrCustom`).append(`<option value="${input}"></option>`);
  
      if (!$('#tableAttrbuteCustom tbody tr').length) $('#tableAttrbuteCustom').hide();
  })
  
  const removeAcento = (text) => {
      text = text.toLowerCase();
      text = text.replace(new RegExp('[ÁÀÂÃ]','gi'), 'a');
      text = text.replace(new RegExp('[ÉÈÊ]','gi'), 'e');
      text = text.replace(new RegExp('[ÍÌÎ]','gi'), 'i');
      text = text.replace(new RegExp('[ÓÒÔÕ]','gi'), 'o');
      text = text.replace(new RegExp('[ÚÙÛ]','gi'), 'u');
      text = text.replace(new RegExp('[Ç]','gi'), 'c');
      return text;
  }
  
  $('#btn_save_attributes').click(function(event){
    var valueInArray = $('#product_collections').val();
    var combo = $('.input-group .bootstrap-select.form-control .dropdown-toggle');
    var msg = $('#msg_error');
      if(!valueInArray.length){
        event.preventDefault();
        combo.css("border-color", "red");
        msg.css({"display": "block", "color": "red","margin-left": "25px","margin-top" : "-25px", "position" : "absolute"});
      }else{
        combo.css("border-color", "#f0f0f0");
        msg.css("display", "none");
      }
  });
  
  $(function(){
      $('#sendQueueOmnilogic').click(function(){
          $.ajax({
              method: "POST",
              url: "/products/sendOmnilogicEnrichCategories",
              data: {product: <?=$product?>},
              success: function() {
                  $(location).attr("href", "/products/attributes/edit/<?=$product?>/<?=$category?>");
              },
              error: function(data) {
                  console.log(data);
              }
          });
      });
  });
    
  </script>
  <style>
  .filter-option { background-color: white; }
  </style>