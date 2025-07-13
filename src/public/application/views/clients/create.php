  <?php include_once(APPPATH . '/third_party/zipcode.php'); ?>

<!--
SW Serviços de Informática 2019

Criar Clientes

-->
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

	<?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data); ?>

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
              <h3 class="box-title"><?=$this->lang->line('application_add_new_client');?></h3>
            </div>
            <form role="form" action="<?php base_url('clients/create') ?>" method="post">
              <input type="hidden" id="crcli" name="crcli" value="S">
              <div class="card-body">
				<div class="row">
	                <div class="form-group col-md-9">
	                  <label for="nome"><?=$this->lang->line('application_name');?></label>
	                  <input type="text" class="form-control" id="nome" name="nome" required placeholder="<?=$this->lang->line('application_enter_user_fname')?>" autocomplete="off" value="<?=set_value('nome'); ?>">
	                </div>
	                <div class="form-group col-md-3">
	                  <label for="isPJ"><?=$this->lang->line('application_is_company');?>?</label>
	                  <select class="form-control" id="isPJ" name="isPJ" required>
	                    <option value="0" <?= set_select('isPJ', 0) ?>><?=$this->lang->line('application_no')?></option>
	                    <option value="1" <?= set_select('isPJ', 1) ?>><?=$this->lang->line('application_yes')?></option>
	                  </select>
	                </div>
				</div>
				<div class="row">
	                <div class="form-group col-md-8" id="PJ" style="display:none;">
	                  <label for="raz_soc"><?=$this->lang->line('application_raz_soc');?></label>
	                  <input type="text" class="form-control" id="raz_soc" name="raz_soc" placeholder="<?=$this->lang->line('application_enter_razao_social')?>" autocomplete="off" value="<?=set_value('raz_soc'); ?>">
	                </div>
	                <div class="form-group col-md-4" id="PJ" style="display:none;">
	                  <label for="CNPJ"><?=$this->lang->line('application_cnpj');?></label>
	                  <input type="text" class="form-control" id="CNPJ" name="CNPJ" placeholder="<?=$this->lang->line('application_enter_CNPJ')?>" autocomplete="off" maxlength="18" size="18" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('CNPJ',this,event);" value="<?=set_value('CNPJ'); ?>">
	                </div>
				</div>
				<div class="row">
	                <div class="form-group col-md-4" id="PF">
	                  <label for="CPF"><?=$this->lang->line('application_cpf');?></label>
	                  <input type="text" class="form-control" id="CPF" name="CPF" placeholder="<?=$this->lang->line('application_enter_CPF')?>" autocomplete="off" maxlength="14" size="14" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('CPF',this,event);" value="<?=set_value('CPF'); ?>" required>
	                </div>
	                 <div class="form-group col-md-4" id="PJ" style="display:none;">
	                  <label for="gestor"><?=$this->lang->line('application_gestor');?></label>
	                  <input type="text" class="form-control" id="gestor" name="gestor" placeholder="<?=$this->lang->line('application_enter_manager')?>" autocomplete="off" value="<?=set_value('gestor'); ?>">
	                </div>
	                <div class="form-group col-md-4">
	                  <label for="email"><?=$this->lang->line('application_email');?></label>
	                  <input type="text" class="form-control" id="email" name="email" required placeholder="<?=$this->lang->line('application_enter_email')?>" autocomplete="off" value="<?=set_value('email'); ?>">
	                </div>
	                <div class="form-group col-md-2">
	                  <label for="phone_1"><?=$this->lang->line('application_phone');?>1</label>
	                  <input type="text" class="form-control" id="phone_1" name="phone_1" required placeholder="<?=$this->lang->line('application_enter_user_phone')?>" autocomplete="off" maxlength="15" size="15" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('TEL',this,event);" value="<?=set_value('phone_1'); ?>">
	                </div>
	                <div class="form-group col-md-2">
	                  <label for="phone_2"><?=$this->lang->line('application_phone');?>2</label>
	                  <input type="text" class="form-control" id="phone_2" name="phone_2" placeholder="<?=$this->lang->line('application_enter_user_phone')?>" autocomplete="off" maxlength="15" size="15" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('TEL',this,event);" value="<?=set_value('phone_2'); ?>">
	                </div>
				</div>
                <div class="row">
                    <div class="form-group col-md-2">
                      <label for="zipcode"><?=$this->lang->line('application_zip_code');?></label>
                      <input type="text" class="form-control" id="zipcode" name="zipcode" required placeholder="<?=$this->lang->line('application_enter_zipcode')?>" autocomplete="off" maxlength="9" size="8" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('CEP',this,event);" value="<?=set_value('zipcode'); ?>">
                    </div>
					<div class="form-group col-md-8">
	                  <label for="address"><?=$this->lang->line('application_address');?></label>
	                  <input type="text" class="form-control" id="address" name="address" required placeholder="<?=$this->lang->line('application_enter_address')?>" autocomplete="off" value="<?=set_value('address'); ?>">
	                </div>
	                <div class="form-group col-md-2">
	                  <label for="addr_num"><?=$this->lang->line('application_number');?></label>
	                  <input type="text" class="form-control" id="addr_num" name="addr_num" required placeholder="<?=$this->lang->line('application_enter_number')?>r" autocomplete="off" value="<?=set_value('addr_num'); ?>">
	                </div>
                </div>
                <div class="row">
	                <div class="form-group col-md-2">
	                  <label for="addr_compl"><?=$this->lang->line('application_complement');?></label>
	                  <input type="text" class="form-control" id="addr_compl" name="addr_compl" placeholder="<?=$this->lang->line('application_enter_complement')?>" autocomplete="off" value="<?=set_value('addr_compl'); ?>">
	                </div>
	                <div class="form-group col-md-3">
	                  <label for="addr_neigh"><?=$this->lang->line('application_neighb');?></label>
	                  <input type="text" class="form-control" id="addr_neigh" name="addr_neigh" required placeholder="<?=$this->lang->line('application_enter_neighborhood')?>" autocomplete="off" value="<?=set_value('addr_neigh'); ?>">
	                </div>
	                <div class="form-group col-md-3">
	                  <label for="addr_city"><?=$this->lang->line('application_city');?></label>
	                  <input type="text" class="form-control" id="addr_city" name="addr_city" required placeholder="<?=$this->lang->line('application_enter_city')?>" autocomplete="off" value="<?=set_value('addr_city'); ?>">
	                </div>
	                <div class="form-group col-md-2">
	                  <label for="addr_uf"><?=$this->lang->line('application_uf');?></label>
	                  <select class="form-control" id="addr_UF" name="addr_uf" required>
	                    <option value=""><?=$this->lang->line('application_select');?></option>
	                    <?php foreach ($ufs as $k => $v): ?>
	                      <option value="<?php echo trim($k); ?>" <?= set_select('addr_uf', $k) ?>><?php echo $v ?></option>
	                    <?php endforeach ?>
	                  </select>
	                </div>
	                <div class="form-group col-md-2">
	                  <label for="country"><?=$this->lang->line('application_country');?></label>
	                  <select class="form-control" id="country" name="country">
	                    <option value=""><?=$this->lang->line('application_select');?></option>
	                    <?php foreach ($paises as $k => $v): ?>
	                      <option value="<?php echo trim($k); ?>" <?= set_select('country', $k) ?>><?php echo $v ?></option>
	                    <?php endforeach ?>
	                  </select>
	                </div>
				</div>
              </div>
              <!-- /.box-body -->

              <div class="card-footer">
                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                <a href="<?php echo base_url('company/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
              </div>
            </form>
          </div>
          <!-- /.box -->
        </div>
        <!-- col-md-12 -->
      </div>
      <!-- /.row -->
      </div>      
	  <!-- fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
<script type="text/javascript">
  $(document).ready(function() {
	$("#mainClientsNav").addClass('active');
    $("#addClientsNav").addClass('active');
    // $("#message").summernote();
	$("select[name*='country']").val('BR').attr('selected', true).trigger('change');
	// $("select[name*='country']").trigger('change');
	$("select[name*='currency']").val('BRL').attr('selected', true).trigger('change');
	// $("select[name*='currency']").trigger('change');
	$("#isPJ").trigger('change');

	$('#isPJ').change(function() {
		if ($(this).val() === "1") {
            $("div[id*='PF']").hide().find('input').attr('required', false);
            $("div[id*='PJ']").show().find('input').attr('required', true);
		}
		else {
		    $("div[id*='PJ']").hide().find('input').attr('required', false);
		    $("div[id*='PF']").show().find('input').attr('required', true);
		}	
	})
  });
</script>

