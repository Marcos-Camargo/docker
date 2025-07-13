  <?php include_once(APPPATH . '/third_party/zipcode.php')?>

<!--
SW Serviços de Informática 2019

Criar Fornecedores

-->
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

	<?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data) ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-md-12 col-xs-12" id="rowcol12">
          
          <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <?=$this->session->flashdata('success')?>
            </div>
          <?php elseif ($this->session->flashdata('error')): ?>
            <div class="alert alert-error alert-dismissible" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <?=$this->session->flashdata('error')?>
              <?=validation_errors()?>
            </div>
          <?php endif?>

          <div class="box">
            <div class="box-header">
              <h3 class="box-title"><?=$this->lang->line('application_new_provider');?></h3>
            </div>
            <form role="form" action="<?php base_url('shipping_company/create') ?>" method="post">
              <input type="hidden" id="crcli" name="crcli" value="S">
              <div class="box-body">
				<div class="row">
	                <div class="form-group col-md-4">
	                  <label for="name"><?=$this->lang->line('application_name');?>(*)</label>
	                  <input type="text" class="form-control" id="name" name="name" required placeholder="<?=$this->lang->line('application_enter_user_fname')?>" autocomplete="off" value="<?=set_value('name')?>">
	                </div>
	                <div class="form-group col-md-4">
	                  <label for="raz_soc"><?=$this->lang->line('application_raz_soc');?>(*)</label>
	                  <input type="text" class="form-control" id="raz_soc" name="raz_soc" required placeholder="<?=$this->lang->line('application_enter_razao_social')?>" autocomplete="off" value="<?=set_value('raz_soc')?>">
	                </div>
	                <div class="form-group col-md-4">
	                  <label for="txt_insc_estadual"><?=$this->lang->line('application_iest');?>(*)</label>
					  <input type="text" class="form-control" id="txt_insc_estadual" name="txt_insc_estadual" placeholder="<?=$this->lang->line('application_iest')?>" autocomplete="off" value="<?=set_value('insc_estadual')?>">
					  <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" name="exempted" onchange="exemptIE()" id="exempted">
                        <label class="form-check-label" for="exempted">
                          <?= $this->lang->line('application_exempted'); ?>
                        </label>
                      </div>
	                </div>
				</div>
				<div class="row">
					<div class="form-group col-md-2">
	                  	<label for="active_token_api"><?=$this->lang->line('application_token');?></label>
						<br />
						<input type="checkbox" id="active_token_api" name="active_token_api" data-toggle="toggle" data-on="Gerar" data-off="Não gerar" <?=set_checkbox('active_token_api', 'on', false) ?>>
					</div>
                    <div class="form-group col-md-5">
                        <label for="cnpj"><?=$this->lang->line('application_cnpj');?>(*)</label>
                        <input type="text" class="form-control" id="cnpj" name="cnpj" required placeholder="<?=$this->lang->line('application_enter_CNPJ')?>" autocomplete="off" maxlength="18" size="18" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CNPJ',this,event);" value="<?=set_value('cnpj')?>">
                    </div>
	                <div class="form-group col-md-4">
	                  <label for="phone_1"><?=$this->lang->line('application_phone');?>(*)</label>
	                  <input type="text" class="form-control" id="phone" name="phone" required placeholder="<?=$this->lang->line('application_enter_user_phone')?>" autocomplete="off" maxlength="15" size="15" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" value="<?=set_value('phone')?>">
	                </div>
	                <div class="form-group col-md-3 d-none">
	                  <label for="addr_uf"><?=$this->lang->line('application_providers_type');?>(*)</label>
	                  <select class="form-control" id="slc_tipo_provider" name="slc_tipo_provider" required> 
	                    <option value="Outros">Outros</option>
	                  </select>
	                </div>
	                <div class="form-group col-md-1">
	                  <label for="phone_2"><?=$this->lang->line('application_active');?></label>
                        <br />
	                  <input type="checkbox" class="minimal" id="active" name="active" <?=set_checkbox('active', 'on', true) ?>">
					</div>
				</div>
                  <div class="row">
                      <div class="form-group col-md-4">
                          <label for="responsible_name"><?=$this->lang->line('application_responsible_name');?>(*)</label>
                          <input type="text" class="form-control" id="responsible_name" name="responsible_name" required placeholder="<?=$this->lang->line('application_enter_responsible_name')?>" autocomplete="off" value="<?=set_value('responsible_name')?>">
                      </div>
                      <div class="form-group col-md-4">
                          <label for="responsible_email"><?=$this->lang->line('application_responsible_email');?>(*)</label>
                          <input type="text" class="form-control" id="responsible_email" name="responsible_email" required placeholder="<?=$this->lang->line('application_enter_responsible_email')?>" autocomplete="off" value="<?=set_value('responsible_email')?>">
                      </div>
                      <div class="form-group col-md-4">
                          <label for="responsible_cpf"><?=$this->lang->line('application_responsible_cpf');?>(*)</label>
                          <input type="text" class="form-control" id="responsible_cpf" name="responsible_cpf" placeholder="<?=$this->lang->line('application_enter_responsible_cpf')?>" autocomplete="off" maxlength="14" size="14" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" value="<?=set_value('responsible_cpf')?>">
                      </div>
                  </div>


                <div class="row">
                    <div class="form-group col-md-2">
                      <label for="zipcode"><?=$this->lang->line('application_zip_code');?>(*)</label>
                      <input type="text" class="form-control" id="zipcode" name="zipcode"  placeholder="<?=$this->lang->line('application_enter_zipcode')?>" autocomplete="off" maxlength="9" size="8" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CEP',this,event);" value="<?=set_value('zipcode')?>">
                    </div>
                    <div class="form-group col-md-8">
                      <label for="address"><?=$this->lang->line('application_address');?>(*)</label>
                      <input type="text" class="form-control" id="address" name="address"  placeholder="<?=$this->lang->line('application_enter_address')?>" autocomplete="off" value="<?=set_value('address')?>">
                    </div>
                    <div class="form-group col-md-2">
                      <label for="addr_num"><?=$this->lang->line('application_number');?>(*)</label>
                      <input type="text" class="form-control" id="addr_num" name="addr_num"  placeholder="<?=$this->lang->line('application_enter_number')?>r" autocomplete="off" value="<?=set_value('addr_num')?>">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-4">
                      <label for="addr_compl"><?=$this->lang->line('application_complement');?></label>
                      <input type="text" class="form-control" id="addr_compl" name="addr_compl" placeholder="<?=$this->lang->line('application_enter_complement')?>" autocomplete="off" value="<?=set_value('addr_compl')?>">
                    </div>
                    <div class="form-group col-md-3">
                      <label for="addr_neigh"><?=$this->lang->line('application_neighb');?>(*)</label>
                      <input type="text" class="form-control" id="addr_neigh" name="addr_neigh"  placeholder="<?=$this->lang->line('application_enter_neighborhood')?>" autocomplete="off" value="<?=set_value('addr_neigh')?>">
                    </div>
                    <div class="form-group col-md-3">
                      <label for="addr_city"><?=$this->lang->line('application_city');?>(*)</label>
                      <input type="text" class="form-control" id="addr_city" name="addr_city"  placeholder="<?=$this->lang->line('application_enter_city')?>" autocomplete="off" value="<?=set_value('addr_city')?>">
                    </div>
                    <div class="form-group col-md-2">
                      <label for="addr_uf"><?=$this->lang->line('application_uf');?>(*)</label>
                      <select class="form-control" id="addr_UF" name="addr_uf" >
                        <option value=""><?=$this->lang->line('application_select');?></option>
                        <?php foreach ($ufs as $k => $v): ?>
                          <option value="<?=trim($k)?>" <?= set_select('addr_uf', $k) ?>><?=$v ?></option>
                        <?php endforeach ?>
                      </select>
                    </div>
                </div>
				
				<div class="row">
	                <div class="form-group col-md-3">
	                	<label for="slc_tipo_pagamento"><?=$this->lang->line('application_billet_type_payment');?>(*)</label>
	                	<select class="form-control" id="slc_tipo_pagamento" name="slc_tipo_pagamento"> 
    	                    <option value=""><?=$this->lang->line('application_select');?></option>
    	                    <option value="Boleto">Boleto</option>
    	                    <option value="Transferencia">Transferência Bancária</option>
    	                </select>
	                </div>
	                <div class="form-group col-md-3 d-none">
						<label for="addr_uf"><?=$this->lang->line('application_company');?></label>
						<select class="form-control" onchange="getListCompanyStores(this);" name="slc_company" id="slc_company">
							<option value=""></option>
							<?php foreach($company_list as $key => $value) { ?>
								<option value="<?php echo $value['id'];?>"><?php echo $value['name'];?></option>
							<?php } ?>
						</select>
	                </div>

	                <div class="form-group col-md-3 d-none">
						<label for="store"><?=$this->lang->line('application_store');?>(*)</label>
						<select class="form-control" name="slc_store" id="slc_store" >
							<option value=""></option>
						</select>
	                </div>
	                
	           	</div>
				
				<div id="divTransferencia">
    				<div class="row">
    	                <div class="form-group col-md-3">
    	                  <label for="bank"><?=$this->lang->line('application_bank');?>(*)</label>
                            <select class="form-control" id="bank" name="bank" >
                                <option value=""><?=$this->lang->line('application_select');?></option>
                                <?php foreach ($banks as $k => $v): ?>
                                    <option value="<?=trim($v)?>" <?= set_select('bank', $v) ?>><?=$v ?></option>
                                <?php endforeach ?>
                            </select>
    	                </div>
    	                <div class="form-group col-md-3">
    	                  <label for="agency"><?=$this->lang->line('application_agency');?>(*)</label>
    	                  <input type="text" class="form-control" id="agency" name="agency"  placeholder="<?=$this->lang->line('application_enter_agency')?>" autocomplete="off" value="<?=set_value('agency')?>">
    	                </div>
                        <div class="form-group col-md-3">
                            <label for="currency"><?=$this->lang->line('application_type_account');?>(*)</label>
                            <select class="form-control" id="account_type" name="account_type" >
                                <option value=""><?=$this->lang->line('application_select');?></option>
                                <?php foreach ($type_accounts as $k => $v): ?>
                                    <option value="<?=trim($v)?>" <?= set_select('account_type', $v) ?>><?=$v ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
    	                <div class="form-group col-md-3">
    	                  <label for="account"><?=$this->lang->line('application_account');?>(*)</label>
    	                  <input type="text" class="form-control" id="account" name="account"  placeholder="<?=$this->lang->line('application_enter_bank_account')?>" autocomplete="off" value="<?=set_value('account')?>">
    	                </div>
    				</div>
    			</div>
    			
    			
				<div class="row">
	                <div class="form-group col-md-12">
	                  	<label for="group_name"><?=$this->lang->line('application_extract_obs');?></label>
          				<textarea class="form-control" id="txt_observacao" name="txt_observacao" placeholder="<?=$this->lang->line('application_extract_obs');?>"><?=set_value('observacao')?></textarea>
	                </div>
				</div>

                <div class="row" id="parentDataApi">
                    <div class="form-group col-md-5">
                        <label for="currency"><?=$this->lang->line('application_marketplace');?></label>
                        <select class="form-control" id="marketplace" name="marketplace" >
                            <option value="0"><?=$this->lang->line('application_select');?></option>
                            <?php foreach ($marketplaces as $k => $v): ?>
                                <option value="<?=$v['id']?>" <?=set_select('marketplace', $v['id'])?>><?=$v['name']?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="currency"><?=$this->lang->line('application_stores');?></label>
                        <select class="form-control selectpicker show-tick" id="stores" name ="stores[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                            <?php foreach ($stores as $store): ?>
                                <option value="<?=$store['id']?>" <?=set_select('marketplace', $store['id'], in_array($store['id'], $stores_by_provider))?>><?=$store['name']?></option>
                            <?php endforeach ?>
                        </select>
                        <small>Lojas que o fornecedor terá acesso</small>
                    </div>
                </div>

              </div>
              <!-- /.box-body -->

              <div class="box-footer">
                <button type="submit" class="btn btn-primary" id="salveProvider"><?=$this->lang->line('application_save');?></button>
                <a href="<?=base_url('providers/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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

var base_url = "<?=base_url();?>";

function getListCompanyStores(sel) {
    
    let dropdown = $('#slc_store').empty();
    var companyId = sel.value;
	//console.log(companyId);
    if (companyId != "") {
        $(".sucesso").remove();
        $(".erro").remove();
        //$("#manageTable").dataTable().fnDestroy();
        var storeList = getStoreForCompany(companyId);
    } else {
        Toast.fire({
            icon: 'error',
            title: 'Informe um Empresa.'
        });
        $("#manageTable").dataTable().fnDestroy();
    }
}

function getStoreForCompany(companyId) {
	console.log(companyId);
    $.ajax({
        url: base_url+"shippingcompany/getStoreForCompany",
    	type: "POST",
        data: {           
            companyId: companyId
        },
        //async: true,
        success: function(response) {
            var obj = JSON.parse(response);
			console.log(response);
            let dropdown = $('#slc_store');
                dropdown.empty();
                dropdown.append('<option selected="true" value="" ></option>');
                $.each(obj, function (key, entry) {
                    console.log(entry);
                    dropdown.append($('<option></option>').attr('value', entry.id).text(entry.name));
                }); 
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }
    });
}

$(document).ready(function() {
    $("#ad_valorem, #gris, #toll, #shipping_revenue,#slc_tipo_cubage").trigger('change');

	$("#slc_tipo_pagamento").trigger('change');

	$("#slc_val_credito").trigger('change');

	$("#slc_val_ship_min").trigger('change');

	$("#slc_qtd_min").trigger('change');

    $('input[type="checkbox"].minimal').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass   : 'iradio_minimal-blue'
    });

    $("#mainProvidersNav").addClass('active');
    $("#addProvidersNav").addClass('active');

    $('#active_token_api').trigger('change');
});

$("#slc_tipo_pagamento").change(function(){
    if ( $("#slc_tipo_pagamento").val() == "Transferencia") {
        $("#divTransferencia").show();
    }else{
        $("#divTransferencia").hide();
    }
});

$("#slc_val_credito").change(function(){
    if ( $("#slc_val_credito").val() == "Sim") {
        $("#div_txt_val_credito").show();
    }else{
        $("#div_txt_val_credito").hide();
    }
});

$("#slc_val_ship_min").change(function(){
    if ( $("#slc_val_ship_min").val() == "Sim") {
        $("#div_txt_val_ship_min").show();
        $("#txt_val_ship_min").attr("required", "req");
    }else{
        $("#div_txt_val_ship_min").hide();
        $("#txt_val_ship_min").removeAttr('required');
    }
});

$("#slc_qtd_min").change(function(){
    if ( $("#slc_qtd_min").val() == "Sim") {
        $("#div_txt_qtd_min").show();
        $("#txt_qtd_min").attr("required", "req");
    }else{
        $("#div_txt_qtd_min").hide();
        $("#txt_qtd_min").removeAttr('required');
    }
});

$("#cnpj").blur(function(){
    $(".erro").remove();
    $("#salveProvider").prop("disabled",false);
    const cnpj = isCNPJValid($(this).val());
    if (cnpj === false) {
        Toast.fire({
            icon: 'error',
            title: 'Informe um CNPJ válido.'
        });
        $("#salveProvider").prop("disabled",true);
    }
});

$("#responsible_cpf").blur(function(){
    $(".erro").remove();
    $("#salveProvider").prop("disabled",false);
    const cpf = isCpf($(this).val());
    if (cpf === false) {
        Toast.fire({
            icon: 'error',
            title: 'Informe um CPF válido.'
        });
        $("#salveProvider").prop("disabled",true);
    }
});

$("#slc_tipo_cubage").change(function(){
    if ($("#slc_tipo_cubage").val() == "FreteCubadoSim") {
        $("#divFretecubado").show();
        $("#cubage_factor").attr("required", "req");
    } else {
        $("#divFretecubado").hide();
        $("#cubage_factor").removeAttr("required", "req");
        $("#cubage_factor").val("");
    }
});

$("#ad_valorem").on("change",function(){
    if ($(this).val() !== '') {
        $(this).val(parseFloat($(this).val()).toFixed(2));
    }
});

$("#gris").on("change",function(){
    if ($(this).val() !== '') {
        $(this).val(parseFloat($(this).val()).toFixed(2));
    }
});

$("#toll").on("change",function(){
    if ($(this).val() !== '') {
        $(this).val(parseFloat($(this).val()).toFixed(2));
    }
});

$("#ad_valorem, #gris, #toll, #shipping_revenue").on('input', function() {
    var c = this.selectionStart,
        r = /[^0-9.]/gi,
        v = $(this).val();
    if(r.test(v)) {
        $(this).val(v.replace(r, ''));
        c--;
    }
    this.setSelectionRange(c, c);
});

$("#ad_valorem").blur(function(){
    let value = $(this).val();
    if(value == "NaN" || value == "0.00" ){
        $('#ad_valorem').val("");
    }
});

$("#ad_valorem").blur(function(){
    let value = $(this).val();
    if(value == "NaN" || value == "0.00" ){
        $('#ad_valorem').val("");
    }
});

$("#gris").blur(function(){
    let value = $(this).val();
    if(value == "NaN" || value == "0.00"){
        $('#gris').val("");
    }
});

$("#toll").blur(function(){
    let value = $(this).val();
    if(value == "NaN" || value == "0.00"){
        $('#toll').val("");
    }
});

$("#shipping_revenue").blur(function(){
    let value = $(this).val();
    if(value == "NaN" || value == "0.00"){
        $('#shipping_revenue').val("");
    }
});

function exemptIE() {
    const ie = $('#txt_insc_estadual')[0].hasAttribute('disabled')
    if (!ie) {
        $('#txt_insc_estadual').attr('disabled', 'disabled')
    } else {
        $('#txt_insc_estadual').removeAttr('disabled')
    }
}

function isCpf(cpf) {
    exp = /\.|-/g;
    cpf = cpf.toString().replace(exp, "");
    var digitoDigitado = eval(cpf.charAt(9) + cpf.charAt(10));
    var soma1 = 0,
            soma2 = 0;
    var vlr = 11;
    for (i = 0; i < 9; i++) {
        soma1 += eval(cpf.charAt(i) * (vlr - 1));
        soma2 += eval(cpf.charAt(i) * vlr);
        vlr--;
    }
    soma1 = (((soma1 * 10) % 11) === 10 ? 0 : ((soma1 * 10) % 11));
    soma2 = (((soma2 + (2 * soma1)) * 10) % 11);
    if (cpf === "11111111111" || cpf === "22222222222" || cpf === "33333333333" || cpf === "44444444444" || cpf === "55555555555" || cpf === "66666666666" || cpf === "77777777777" || cpf === "88888888888" || cpf === "99999999999" || cpf === "00000000000") {
        var digitoGerado = null;
    } else {
        var digitoGerado = (soma1 * 10) + soma2;
    }
    if (digitoGerado !== digitoDigitado) {
        return false;
    }
    return true;
}

function isCNPJValid(cnpj) {
    cnpj = cnpj.replace(/[^\d]+/g, '');
    if (cnpj == '') return false;
    if (cnpj.length != 14)
        return false;
    // Elimina CNPJs invalidos conhecidos
    if (cnpj == "00000000000000" ||
        cnpj == "11111111111111" ||
        cnpj == "22222222222222" ||
        cnpj == "33333333333333" ||
        cnpj == "44444444444444" ||
        cnpj == "55555555555555" ||
        cnpj == "66666666666666" ||
        cnpj == "77777777777777" ||
        cnpj == "88888888888888" ||
        cnpj == "99999999999999")
        return false;

    // Valida DVs
    tamanho = cnpj.length - 2
    numeros = cnpj.substring(0, tamanho);
    digitos = cnpj.substring(tamanho);
    soma = 0;
    pos = tamanho - 7;
    for (i = tamanho; i >= 1; i--) {
        soma += numeros.charAt(tamanho - i) * pos--;
        if (pos < 2)
            pos = 9;
    }
    resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
    if (resultado != digitos.charAt(0))
        return false;

    tamanho = tamanho + 1;
    numeros = cnpj.substring(0, tamanho);
    soma = 0;
    pos = tamanho - 7;
    for (i = tamanho; i >= 1; i--) {
        soma += numeros.charAt(tamanho - i) * pos--;
        if (pos < 2)
            pos = 9;
    }
    resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
    if (resultado != digitos.charAt(1))
        return false;

    return true;
}

$('#active_token_api').on('change', function(){
    if ($(this).is(':checked')) {
        $('#parentDataApi').show();
    } else {
        $('#parentDataApi').hide();
    }
});
</script>

