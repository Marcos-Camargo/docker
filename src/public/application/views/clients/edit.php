
  <?php include_once(APPPATH . '/third_party/zipcode.php'); ?>
<!--
SW Serviços de Informática 2019

Editar Clientes
-->
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
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
              <?php echo validation_errors(); ?>
            </div>
          <?php endif; ?>

          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><?=$this->lang->line('application_update_information');?></h3>
            </div>
            <form role="form" action="<?php base_url('clients/update') ?>" method="post"  enctype="multipart/form-data">
              <input type="hidden" id="crcli" name="crcli" value="S">
              <div class="card-body">
				<div class="row">
	                <div class="form-group col-md-9">
	                  <label for="nome"><?=$this->lang->line('application_name');?></label>
	                  <input type="text" class="form-control" id="nome" name="nome" placeholder="Enter name" autocomplete="off" value="<?= $fields['nome']; ?>">
	                </div>
	                <div class="form-group col-md-3">
	                  <label for="isPJ"><?=$this->lang->line('application_is_company');?>?</label>
	                  <select class="form-control" id="isPJ" name="isPJ">
	                    <option value="0" <?= ($fields['isPJ']==0) ? "selected":""; ?>><?=$this->lang->line('application_no')?></option>
	                    <option value="1" <?= ($fields['isPJ']==1) ? "selected":""; ?>><?=$this->lang->line('application_yes')?></option>
	                  </select>
	                </div>
				</div>
				<?php if($fields['isPJ']==1): ?>
				<div class="row">
	                <div class="form-group col-md-8" id="PJ" style="display:none;">
	                  <label for="raz_soc"><?=$this->lang->line('application_raz_soc');?></label>
	                  <input type="text" class="form-control" id="raz_soc" name="raz_soc" placeholder="<?=$this->lang->line('application_enter_razao_social')?>" autocomplete="off" value="<?= $fields['raz_soc']; ?>">
	                </div>
	                <div class="form-group col-md-4" id="PJ" style="display:none;">
	                  <label for="CNPJ"><?=$this->lang->line('application_cnpj');?></label>
	                  <input type="text" class="form-control" id="CNPJ" name="CNPJ" placeholder="<?=$this->lang->line('application_enter_CNPJ')?>" autocomplete="off" maxlength="18" size="18" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('CNPJ',this,event);" value="<?= $fields['CNPJ']; ?>">
	                </div>
				</div>
				<?php endif; ?>
				<div class="row">
	                <div class="form-group col-md-4" id="PF">
	                  <label for="CPF"><?=$this->lang->line('application_cpf');?></label>
	                  <input type="text" class="form-control" id="CPF" name="CPF" placeholder="<?=$this->lang->line('application_enter_CPF')?>" autocomplete="off" maxlength="14" size="14" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('CPF',this,event);" value="<?= $fields['CPF']; ?>">
	                </div>
	                 <div class="form-group col-md-4" id="PJ" style="display:none;">
	                  <label for="gestor"><?=$this->lang->line('application_gestor');?></label>
	                  <input type="text" class="form-control" id="gestor" name="gestor" placeholder="<?=$this->lang->line('application_enter_manager')?>" autocomplete="off" value="<?= $fields['gestor']; ?>"
>
	                </div>
	                <div class="form-group col-md-4">
	                  <label for="email"><?=$this->lang->line('application_email');?></label>
	                  <input type="text" class="form-control" id="email" name="email" placeholder="<?=$this->lang->line('application_enter_user_email')?>" autocomplete="off" value="<?= $fields['email']; ?>">
	                </div>
	                <div class="form-group col-md-2">
	                  <label for="phone_1"><?=$this->lang->line('application_phone');?>1</label>
	                  <input type="text" class="form-control" id="phone_1" name="phone_1" placeholder="<?=$this->lang->line('application_enter_phone')?>" autocomplete="off" maxlength="15" size="15" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('TEL',this,event);" value="<?= $fields['phone_1']; ?>">
	                </div>
	                <div class="form-group col-md-2">
	                  <label for="phone_2"><?=$this->lang->line('application_phone');?>2</label>
	                  <input type="text" class="form-control" id="phone_2" name="phone_2" placeholder="<?=$this->lang->line('application_enter_phone')?>" autocomplete="off" maxlength="15" size="15" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('TEL',this,event);" value="<?= $fields['phone_2']; ?>">
	                </div>
				</div>
                <div class="row">
                    <div class="form-group col-md-2">
                      <label for="zipcode"><?=$this->lang->line('application_zip_code');?></label>
                      <input type="text" class="form-control" id="zipcode" name="zipcode" placeholder="<?=$this->lang->line('application_enter_zipcode')?>" autocomplete="off" maxlength="9" size="8" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('CEP',this,event);" value="<?= $fields['zipcode']; ?>">
                    </div>
					<div class="form-group col-md-8">
	                  <label for="address"><?=$this->lang->line('application_address');?></label>
	                  <input type="text" class="form-control" id="address" name="address" placeholder="<?=$this->lang->line('application_enter_address')?>" autocomplete="off" value="<?= $fields['address']; ?>">
	                </div>
	                <div class="form-group col-md-2">
	                  <label for="addr_num"><?=$this->lang->line('application_number');?></label>
	                  <input type="text" class="form-control" id="addr_num" name="addr_num" placeholder="<?=$this->lang->line('application_enter_number')?>" autocomplete="off" value="<?= $fields['addr_num']; ?>">
	                </div>
					</div>
					<div class="row">
	                <div class="form-group col-md-2">
	                  <label for="addr_compl"><?=$this->lang->line('application_complement');?></label>
	                  <input type="text" class="form-control" id="addr_compl" name="addr_compl" placeholder="<?=$this->lang->line('application_enter_complement')?>" autocomplete="off" value="<?= $fields['addr_compl']; ?>">
	                </div>
	                <div class="form-group col-md-3">
	                  <label for="addr_neigh"><?=$this->lang->line('application_neighb');?></label>
	                  <input type="text" class="form-control" id="addr_neigh" name="addr_neigh" placeholder="<?=$this->lang->line('application_enter_neighborhood')?>" autocomplete="off" value="<?= $fields['addr_neigh']; ?>">
	                </div>
	                <div class="form-group col-md-3">
	                  <label for="addr_city"><?=$this->lang->line('application_city');?></label>
	                  <input type="text" class="form-control" id="addr_city" name="addr_city" placeholder="<?=$this->lang->line('application_enter_city')?>" autocomplete="off" value="<?= $fields['addr_city']; ?>">
	                </div>
	                <div class="form-group col-md-2">
	                  <label for="addr_uf"><?=$this->lang->line('application_uf');?></label>
	                  <select class="form-control" id="addr_UF" name="addr_uf">
	                    <option value=""><?=$this->lang->line('application_select');?></option>
	                    <?php foreach ($ufs as $k => $v): ?>
	                      <option value="<?php echo trim($k); ?>" <?= ($fields['addr_uf']==$k) ? "selected":""; ?>><?php echo $v ?></option>
	                    <?php endforeach ?>
	                  </select>
	                </div>
	                <div class="form-group col-md-2">
	                  <label for="country"><?=$this->lang->line('application_country');?></label>
	                  <select class="form-control" id="country" name="country">
	                    <option value=""><?=$this->lang->line('application_select');?></option>
	                    <?php foreach ($paises as $k => $v): ?>
	                      <option value="<?php echo trim($k); ?>" <?= ($fields['country']==$k) ? "selected":""; ?>><?php echo $v ?></option>
	                    <?php endforeach ?>
	                  </select>
	                </div>
				</div>
              </div>
              <!-- /.box-body -->

              <div class="card-footer">
			  	<?php if(in_array('updateClients', $this->permission)): ?>
                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
				<?php endif; ?>
                <a href="<?php echo base_url('clients/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
              </div>
            </form>
          </div>
          <!-- /.box -->
        </div>
        <!-- col-md-12 -->
      </div>
      <!-- /.row -->
      </div>      
	  <!-- FLUID -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

<script type="text/javascript">
  $(document).ready(function() {
    $("#mainClientsNav").addClass('active');
    $("#addClientsNav").addClass('active');

	$("select[name*='country']").val('BR').attr('selected', true);
	$("select[name*='country']").trigger('change');	
	$("select[name*='currency']").val('BRL').attr('selected', true);
	$("select[name*='currency']").trigger('change');	

	$('#isPJ').change(function() {
		if ($(this).val() === "1") {
		    $("div[id*='PF']").hide();
		    $("div[id*='PJ']").show();
		} else {
		    $("div[id*='PJ']").hide();
		    $("div[id*='PF']").show();
		}	
	})

    var btnCust = '<button type="button" class="btn btn-secondary" title="Add picture tags" ' + 
        'onclick="alert(\'Call your custom code here.\')">' +
        '<i class="glyphicon glyphicon-tag"></i>' +
        '</button>'; 
    $("#company_image").fileinput({
        overwriteInitial: true,
        maxFileSize: 1500,
        showClose: false,
        showCaption: false,
        browseLabel: '',
        removeLabel: '',
        browseIcon: '<i class="glyphicon glyphicon-folder-open"></i>',
        removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
        removeTitle: 'Cancel or reset changes',
        elErrorContainer: '#kv-avatar-errors-1',
        msgErrorClass: 'alert alert-block alert-danger',
        // defaultPreviewContent: '<img src="/uploads/default_avatar_male.jpg" alt="Your Avatar">',
        layoutTemplates: {main2: '{preview} {remove} {browse}'},
        allowedFileExtensions: ["jpg", "png", "gif"]
    });
	if(<?=!in_array('updateClients', $this->permission)? 'true' : 'false' ?>){
		$('form input[type=checkbox]')
			.attr("onclick", "return false;");
		$('form input')
		.attr("readonly",<?=!in_array('updateClients', $this->permission)?'true':'false' ?>);
		$('form select')
			.attr("disabled", <?=!in_array('updateClients', $this->permission)?'true':'false' ?>);
		$('form textarea')
			.attr("disabled", <?=!in_array('updateClients', $this->permission)?'true':'false' ?>);
	}

  });
</script>

