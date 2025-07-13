<!--

Criar Catálogos de Produtos

-->
<style>.div-top{float: left;width: -webkit-fill-available;}</style>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-md-12 col-xs-12">
          
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
            <div class="box-header">
              <h3 class="box-title"><?=$this->lang->line('application_edit_products_catalog');?></h3>
            </div>
            <form role="form" action="<?php base_url('catalogs/update') ?>" method="post">
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
                    <?php
                    }
                  }
                } ?>

                <div class="row">
                    <div class="form-group col-md-5 <?php echo (form_error('name')) ? 'has-error' : '';  ?>">
                        <label for="username"><?=$this->lang->line('application_name');?></label>
                        <input type="text" class="form-control" id="name" required minlenght="3"  maxlenght="255" name="name" placeholder="<?=$this->lang->line('application_enter_catalog_name');?>" value="<?php echo set_value('name',$catalog['name']) ?>"  autocomplete="off">
                        <?php echo '<i style="color:red">'.form_error('name').'</i>'; ?>

                    </div>
                    <div class="form-group col-md-2  <?php echo (form_error('status')) ? 'has-error' : '';  ?>">
                        <label for="status"><?=$this->lang->line('application_status');?>(*)</label>
                        <select class="form-control" id="status" name="status">
                            <option value="1" <?php echo set_select('status', 1, ($catalog['status'] == 1)) ?>><?=$this->lang->line('application_active');?></option>
                            <option value="2" <?php echo set_select('status', 2, ($catalog['status'] == 2)) ?>><?=$this->lang->line('application_inactive');?></option>
                        </select>
                        <?php echo '<i style="color:red">'.form_error('status').'</i>'; ?>
                    </div>
                    <?php
                    $exist = array();
                    foreach ($catalogs_stores as $cat_str) {
                        $exist[] = $cat_str['store_id'];
                    }
                    ?>
                    <div class="form-group col-md-5 <?php echo (form_error('stores')) ? 'has-error' : '';  ?>">
                        <label for="stores" class="normal"><?=$this->lang->line('application_authorized_stores');?></label>
                        <select class="form-control selectpicker show-tick" id="stores" name ="stores[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                            <?php foreach ($stores as $store) { ?>
                            <option value="<?= $store['id'];?>" <?=set_select('stores', $store['id'],  (in_array($store['id'],$exist)));?>><?= $store['name'] ?></option>
                            <?php } ?>
                         </select>
                    </div>
                </div>

                <div class="row">
                  <div class="form-group col-md-6 <?php echo (form_error('identifying_technical_specification')) ? 'has-error' : '';  ?>">
                      <label for="identifying_technical_specification" class="normal"><?=$this->lang->line('application_identifying_technical_specification');?></label>
                      <select class="form-control show-tick" id="identifying_technical_specification" name ="identifying_technical_specification" title="<?=$this->lang->line('application_select');?>">
                          <?php foreach ($attributesSelect as $attribute) { ?>
                              <option value="<?= $attribute['fieldValueId'].':'.$attribute['value'];?>" <?=set_select('identifying_technical_specification', $attribute['fieldValueId'].':'.$attribute['value'],  ($catalog['attribute_id'] == $attribute['fieldValueId'] && $catalog['attribute_value'] == $attribute['value']));?>><?= $attribute['fieldValueId'] . ' ' . $attribute['value'] ?></option>
                          <?php } ?>
                      </select>
                  </div>
                  <div class="form-group col-md-3 <?php echo $this->session->flashdata('valid_price_min') ? ' has-error' : '';  ?>">
                      <label for="price_min"><?=$this->lang->line('application_price_min');?></label>
                      <input type="text" class="form-control" id="price_min" onKeyPress="maskCurrency(this,currency)"  maxlenght="9" name="price_min" placeholder="Digite o Preço Mínimo" value="<?php echo set_value('price_min',$catalog['price_min']) ?>"  autocomplete="off">
                      <?php echo '<i style="color:red">'. $this->session->flashdata('valid_price_min').'</i>'; ?>
                  </div>

                  <div class="form-group col-md-3 <?php echo $this->session->flashdata('valid_price_max') ? ' has-error' : '';  ?>">
                      <label for="price_max"><?=$this->lang->line('application_price_max');?></label>
                      <input type="text" class="form-control" id="price_max" onKeyPress="maskCurrency(this,currency)"  maxlenght="9" name="price_max" placeholder="Digite o Preço máximo" value="<?php echo set_value('price_max',$catalog['price_max']) ?>" autocomplete="off">
                      <?php echo '<i style="color:red">'. $this->session->flashdata('valid_price_max').'</i>'; ?>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-2 col-xs-12">
                      <label><small><?=$this->lang->line('application_created_on');?></small></label>
                      <span ><small><?php echo date("d/m/Y H:i:s",strtotime($catalog['date_create'])); ?></small></span>
                  </div>
                  <div class="form-group col-md-2 col-xs-12 text-right">
                      <label><small><?=$this->lang->line('application_updated_on');?></small></label>
                      <span ><small><?php echo date("d/m/Y H:i:s",strtotime($catalog['date_update'])); ?></small></span>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-3 <?php echo $this->session->flashdata('valid_price_max') ? ' has-error' : '';  ?>">
                      <label for="inactive_products_with_inactive_brands" class="d-flex align-items-center mt-3"> <input type="checkbox" id="inactive_products_with_inactive_brands" name="inactive_products_with_inactive_brands" class="minimal" <?=set_checkbox('sign', $catalog['inactive_products_with_inactive_brands'], $catalog['inactive_products_with_inactive_brands'] == 1) ?>>&nbsp;<?=$this->lang->line('application_inactive_products_with_inactive_brands');?></label>
                      <?php echo '<i style="color:red">'. $this->session->flashdata('inactive_products_with_inactive_brands').'</i>'; ?>
                  </div>

                  <div class="form-group col-md-3 <?php echo $this->session->flashdata('integrate_products_that_exist_in_other_catalogs') ? ' has-error' : '';  ?>">
                      <label for="integrate_products_that_exist_in_other_catalogs" class="d-flex align-items-center mt-3"> <input type="checkbox" id="integrate_products_that_exist_in_other_catalogs" name="integrate_products_that_exist_in_other_catalogs" class="minimal" <?=set_checkbox('sign', $catalog['integrate_products_that_exist_in_other_catalogs'], $catalog['integrate_products_that_exist_in_other_catalogs'] == 1) ?>>&nbsp;<?=$this->lang->line('application_integrate_products_that_exist_in_other_catalogs');?></label>
                      <?php echo '<i style="color:red">'. $this->session->flashdata('integrate_products_that_exist_in_other_catalogs').'</i>'; ?>
                  </div>
              </div>

              <div class="row">
                <div class="form-group col-md-12  <?php echo (form_error('description')) ? 'has-error' : '';  ?>">
                  	<label for="description"><?=$this->lang->line('application_description');?></label>
                  	<textarea type="text" class="form-control" id="description" name="description" placeholder="<?=$this->lang->line('application_enter_description');?>"><?php echo set_value('description', $catalog['description']); ?></textarea>
                	<?php echo '<i style="color:red">'.form_error('description').'</i>'; ?>  
                </div>
              </div>

              <div class="row">
                  <div class="form-group col-md-2 <?php echo (form_error('marketplaces')) ? 'has-error' : '';  ?>">
                      <label for="marketplaces"><?=$this->lang->line('application_marketplaces');?></label>
                      <select class="form-control selectpicker show-tick" id="marketplaces" name="marketplaces" title="<?=$this->lang->line('application_select');?>">
                          <option value=""><?=$this->lang->line('application_select')?></option>
                          <?php foreach ($marketplaces as $marketplace): ?>
                                <option value="<?=$marketplace['int_to']?>" <?=set_select('marketplaces', $marketplace['int_to'],$marketplace['int_to'] == $catalog['int_to']) ?>><?=$marketplace['name']?></option>
                          <?php endforeach; ?>
                      </select>
                      <small>Associe o marketplace ao catálogo</small>
                  </div>

                  <div class="form-group col-md-2 <?php echo (form_error('associate_skus_between_catalogs')) ? 'has-error' : '';  ?>">
                      <label for="associate_skus_between_catalogs"><?=$this->lang->line('application_associate_skus_between_catalogs');?></label>
                      <select class="form-control selectpicker show-tick" id="associate_skus_between_catalogs" name="associate_skus_between_catalogs[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                      <?php foreach ($catalogs as $catalog_to_select): ?>
                          <?php if ($catalog_to_select['id'] != $catalog['id']): ?>
                            <option value="<?=$catalog_to_select['id']?>" <?=set_select('associate_skus_between_catalogs', $catalog_to_select['id'], in_array($catalog_to_select['id'], $catalogs_associated)) ?>><?=$catalog_to_select['name']?></option>
                          <?php endif; ?>
                      <?php endforeach; ?>
                      </select>
                  </div>

                    <div class="form-group col-md-2 <?php echo (form_error('fields_to_link_catalogs')) ? 'has-error' : '';  ?>">
                        <label for="fields_to_link_catalogs"><?=$this->lang->line('application_fields_for_linking_catalogs');?></label>
                        <select class="form-control selectpicker show-tick" id="fields_to_link_catalogs" name="fields_to_link_catalogs[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                            <option value="brand" <?=set_select('fields_to_link_catalogs', 'brand', in_array('brand', explode(',', $catalog['fields_to_link_catalogs']))) ?>>Marca</option>
                            <option value="ean" <?=set_select('fields_to_link_catalogs', 'ean', in_array('ean', explode(',', $catalog['fields_to_link_catalogs']))) ?>>EAN</option>
                        </select>
                    </div>
                </div>
              </div>
              <!-- /.box-body -->

              <div class="box-footer">
                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                <a href="<?php echo base_url('catalogs/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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

<script type="text/javascript">

$(document).ready(function() {
    
    $("#mainCatalogNav").addClass('active');
    $("#addCatalogNav").addClass('active');
  
    $("#description").summernote({
        toolbar: [
            // [groupName, [list of button]]
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['view', ['fullscreen', 'codeview']]
        ],
        height: 150,
        disableDragAndDrop : true,
        lang: 'pt-BR',
        shortcuts: false
    });

    $('input[type="checkbox"].minimal').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass   : 'iradio_minimal-blue'
    });
});

function maskCurrency(o,f){
  v_obj=o
  v_fun=f
  setTimeout("execCurrency()",1)
}

function execCurrency(){
  v_obj.value=v_fun(v_obj.value)
}

function currency(v){
  v=v.replace(/\D/g,"")
  v=v.replace(/[0-9]{9}/,"inválido")
  v=v.replace(/(\d{1})(\d{8})$/,"$1.$2") 
  v=v.replace(/(\d{1})(\d{5})$/,"$1.$2")
  v=v.replace(/(\d{1})(\d{1,2})$/,"$1,$2")
  return v;
}   
  

</script>
