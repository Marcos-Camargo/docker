  <?php include_once(APPPATH . '/third_party/zipcode.php')?>

<!--
SW Serviços de Informática 2019

Atualizar transportadoras simplificado

-->
<div class="content-wrapper">
	<?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data) ?>
    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <?php if($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?=$this->session->flashdata('success')?>
                    </div>
                <?php elseif($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?=$this->session->flashdata('error')?>
                        <?=validation_errors()?>
                    </div>
                <?php endif?>
                <form role="form" action="<?php base_url('shipping_company/simplified/updatesimplified') ?>" method="post" id="updateShippingCompany">
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active"><a href="#data_shipping_company" data-toggle="tab"><?=$this->lang->line('application_datas_shipping_company')?></a></li>
                            <li><a href="#config_shipping_company" data-toggle="tab"><?=$this->lang->line('application_configs_shipping_company')?></a></li>
                        </ul>
                        <div class="tab-content col-md-12">
                            <div class="tab-pane active" id="data_shipping_company">
                                <input type="hidden" id="crcli" name="crcli" value="S">
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="name"><?=$this->lang->line('application_name');?></label>
                                        <input type="text" class="form-control" id="name" name="name" required placeholder="<?=$this->lang->line('application_enter_user_fname')?>" autocomplete="off" value="<?=set_value('name', $fields['name'])?>">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="raz_soc"><?=$this->lang->line('application_raz_soc');?></label>
                                        <input type="text" class="form-control" id="raz_soc" name="raz_soc" required placeholder="<?=$this->lang->line('application_enter_razao_social')?>" autocomplete="off" value="<?=set_value('razao_social', $fields['razao_social'])?>">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="cnpj"><?=$this->lang->line('application_cnpj');?></label>
                                        <input type="text" class="form-control" id="cnpj" name="cnpj" required placeholder="<?=$this->lang->line('application_enter_CNPJ')?>" autocomplete="off" maxlength="18" size="18" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CNPJ',this,event);" value="<?=set_value('cnpj', $fields['cnpj'])?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="phone_1"><?=$this->lang->line('application_phone');?></label>
                                        <input type="text" class="form-control" id="phone" name="phone" required placeholder="<?=$this->lang->line('application_enter_user_phone')?>" autocomplete="off" maxlength="15" size="15" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" value="<?=set_value('phone', $fields['phone'])?>">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="responsible_name"><?=$this->lang->line('application_responsible_name');?></label>
                                        <input type="text" class="form-control" id="responsible_name" name="responsible_name" required placeholder="<?=$this->lang->line('application_enter_responsible_name')?>" autocomplete="off" value="<?=set_value('responsible_name', $fields['responsible_name'])?>">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="responsible_email"><?=$this->lang->line('application_responsible_email');?></label>
                                        <input type="text" class="form-control" id="responsible_email" name="responsible_email" required placeholder="<?=$this->lang->line('application_enter_responsible_email')?>" autocomplete="off" value="<?=set_value('responsible_email', $fields['responsible_email'])?>">
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="config_shipping_company">
                                <div class="row">
                                    <div class="form-group col-md-4 <?=count($stores) === 1 ? 'd-none' : ''?>">
                                        <label for="store"><?=$this->lang->line('application_store');?></label>
                                        <select class="form-control" name="store" id="store" required >
                                            <?=(count($stores) !== 1) ? "<option value=''>{$this->lang->line('application_select')}</option>" : '' ?>
                                            <?php foreach($stores as $key => $value ) { ?>
                                                <?php if($value['id'] == $fields['store_id'] ) { ?>
                                                    <option value="<?php echo $value['id'];?>" selected ><?php echo $value['name'];?></option>
                                                <?php } else { ?>
                                                    <option value="<?php echo $value['id'];?>"><?php echo $value['name'];?></option>
                                                <?php } ?>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <h4 class="no-margin">Regras</h4>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="slc_tipo_cubage"><?=$this->lang->line('application_cubic_weight');?>? (*)</label></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="Selecione a opção 'Sim' para utilizar o cálculo de frete com o peso cubado. O peso cubado é calculado multiplicando as dimensões do produto (L x C x A) pelo Fator de Cubagem."></i>
                                        <select class="form-control" id="slc_tipo_cubage" name="slc_tipo_cubage" value="<?=set_value('slc_tipo_cubage', $fields['slc_tipo_cubage'])?>" required >
                                            <?php if( $fields['slc_tipo_cubage'] == "FreteCubadoSim") { ?>
                                                <option value="FreteCubadoSim">Sim</option>
                                                <option value="FreteCubadoNao">Não</option>
                                            <?php } elseif ($fields['slc_tipo_cubage'] == "FreteCubadoNao"){ ?>
                                                <option value="FreteCubadoNao">Não</option>
                                                <option value="FreteCubadoSim">Sim</option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div id="divFretecubado">
                                        <div class="row">
                                            <div class="form-group col-md-3">
                                                <label for="cubage_factor"><?=$this->lang->line('application_cubage_factor');?> (*)</label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="O fator de cubagem é um número constante definido por cada transportadora para realizar o cálculo do peso cubado. A unidade de medida adotada para o fator de cubagem é em Kg/cm³. Digite apenas números inteiros, sem vírgulas. Ex: 3152"></i>
                                                <input type="number" min="1" max="999999" onkeyup="this.value=this.value.replace(/[^0-9]/g,'');" class="form-control" id="cubage_factor" name="cubage_factor" required placeholder="<?=$this->lang->line('application_enter_cubage_factor')?>" autocomplete="off" value="<?=set_value('cubage_factor', $fields['cubage_factor'])?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="freight_calculation_standard">Modo de cálculo do frete</label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="A opção 'Por volume' permite calcular o frete com base na quantidade de itens do pedido que cabem em uma embalagem. A opção 'Por peso' permite calcular o frete com base no peso máxima de itens do pedido que cabem em uma embalagem."></i>
                                        <select class="form-control" id="freight_calculation_standard" name="freight_calculation_standard" required>
                                            <option value="PorVolume" <?=set_select('freight_calculation_standard', 'freight_calculation_standard', $fields['freight_calculation_standard'] == 0)?>>Por volume</option>
                                            <option value="PorPeso" <?=set_select('freight_calculation_standard', 'freight_calculation_standard', $fields['freight_calculation_standard'] == 1)?>>Por peso</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                              <h4 class="no-margin">Valores</h4>
                                    </div>
                                    <div class="form-group col-md-3">
                                          <label for="ad_valorem"><?=$this->lang->line('application_ad_valorem');?></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="O Ad Valorem (%) é uma taxa adicional cobrada sobre o valor total do frete, em forma percentual. Representa o seguro da carga. O Ad Valorem será adicionado ao valor do frete: (Valor do frete x Ad Valorem) + Valor do Frete. Digite apenas números. Use ponto para separar os decimais. Ex: 10.25"></i>
                                          <input type="text" class="form-control" id="ad_valorem" name="ad_valorem" placeholder="<?=$this->lang->line('application_enter_ad_valorem')?>" autocomplete="off" value="<?=set_value('ad_valorem', $fields['ad_valorem'])?>">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="gris"><?=$this->lang->line('application_gris');?></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="O GRIS (%) é uma taxa adicional cobrada sobre o valor total do frete, em forma percentual. Funciona como um gerenciamento de risco contra roubos de cargas. O GRIS será adicionado ao valor do frete: (Valor do frete x GRIS) + Valor do Frete. Digite apenas números. Use ponto para separar os decimais. Ex: 10.25"></i>
                                        <input type="text" class="form-control" id="gris" name="gris" placeholder="<?=$this->lang->line('application_enter_gris')?>" autocomplete="off" value="<?=set_value('gris', $fields['gris'])?>">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="toll"><?=$this->lang->line('application_toll');?></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="O pedágio (R$) será adicionado ao valor do frete a cada 100kg do produto. Se o produto tiver menos de 100Kg, o pedágio não será adicionado. Ex: Para um produto de 220Kg adicionaremos duas vezes o valor do pedágio, ao valor final do frete. Digite apenas números. Use ponto para separar os decimais. Ex: 10.25"></i>
                                        <input type="text" class="form-control" id="toll" name="toll"  placeholder="<?=$this->lang->line('application_enter_toll')?>" autocomplete="off" value="<?=set_value('toll', $fields['toll'])?>">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="shipping_revenue"><?=$this->lang->line('application_shipping_revenue');?></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="A Receita de Frete (%) é uma receita adicional cobrada sobre o valor total do frete, em forma percentual. Possibilita uma receita extra ao vendedor. Será adicionado ao valor do frete: (Valor do Frete x Receita de frete) + Valor do Frete. Digite apenas números. Use ponto para separar os decimais. Ex: 10.25"></i>
                                        <input type="text" class="form-control" id="shipping_revenue" name="shipping_revenue" placeholder="<?=$this->lang->line('application_enter_shipping_revenue')?>" autocomplete="off" value="<?=set_value('shipping_revenue', $fields['shipping_revenue'])?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="box box-primary col-md-12 mt-3">
                            <div class="box-footer">
                                <button type="submit" id="sendUpdate" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                                <a href="<?=base_url('shippingcompany/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    $('[name="store"]').select2();
  
  if ( $("#slc_tipo_cubage").val() == "FreteCubadoSim") {
    $("#divFretecubado").show();
    $("#cubage_factor").attr("required", "req");
  }else{
    $("#divFretecubado").hide();

    $("#cubage_factor").removeAttr("required", "req");
    $("#cubage_factor").val("");
  }

  $("#slc_tipo_cubage").change(function(){
    if ( $("#slc_tipo_cubage").val() == "FreteCubadoSim") {
      $("#divFretecubado").show();
      $("#cubage_factor").attr("required", "req");
    }else{
      $("#divFretecubado").hide();

      $("#cubage_factor").removeAttr("required", "req");
      $("#cubage_factor").val("");
    }
	});

  $("#ad_valorem").on("change",function(){
    $(this).val(parseFloat($(this).val()).toFixed(2));
  });

  $("#gris").on("change",function(){
    $(this).val(parseFloat($(this).val()).toFixed(2));
  });

  $("#toll").on("change",function(){
    $(this).val(parseFloat($(this).val()).toFixed(2));
  });

  $("#shipping_revenue").on("change",function(){
    $(this).val(parseFloat($(this).val()).toFixed(2));
  });

  $('[data-toggle="tooltip"]').tooltip()

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
      var value = $(this).val();
      if(value == "NaN" || value == "0.00" ){
        $('#ad_valorem').val("");
      }
  });
  
  $("#ad_valorem").blur(function(){
      var value = $(this).val();
      if(value == "NaN" || value == "0.00" ){
        $('#ad_valorem').val("");
      }
  });
  $("#gris").blur(function(){
      var value = $(this).val();
      if(value == "NaN" || value == "0.00"){
        $('#gris').val("");
      }
  });
  $("#toll").blur(function(){
      var value = $(this).val();
      if(value == "NaN" || value == "0.00"){
        $('#toll').val("");
      }
  });
  $("#shipping_revenue").blur(function(){
      var value = $(this).val();
      if(value == "NaN" || value == "0.00"){
        $('#shipping_revenue').val("");
      }
  });

});

function validaCpfCnpj(val) {

    val = val.trim();
    val = val.replace(/\./g, '');
    val = val.replace('-', '');
    val = val.replace('/', '');
    val = val.split('');

    if (val.length == 11) {
        var cpf = val;
        
        var v1 = 0;
        var v2 = 0;
        var aux = false;
        
        for (var i = 1; cpf.length > i; i++) {
            if (cpf[i - 1] != cpf[i]) {
                aux = true;   
            }
        } 
        
        if (aux == false) {
            return false; 
        } 
        
        for (var i = 0, p = 10; (cpf.length - 2) > i; i++, p--) {
            v1 += cpf[i] * p; 
        } 
        
        v1 = ((v1 * 10) % 11);
        
        if (v1 == 10) {
            v1 = 0; 
        }
        
        if (v1 != cpf[9]) {
            return false; 
        } 
        
        for (var i = 0, p = 11; (cpf.length - 1) > i; i++, p--) {
            v2 += cpf[i] * p; 
        } 
        
        v2 = ((v2 * 10) % 11);
        
        if (v2 == 10) {
            v2 = 0; 
        }
        
        if (v2 != cpf[10]) {
            return false; 
        } else {   
            return true; 
        }
    } else if (val.length == 14) {
        var cnpj = val;
        
        var v1 = 0;
        var v2 = 0;
        var aux = false;
        
        for (var i = 1; cnpj.length > i; i++) { 
            if (cnpj[i - 1] != cnpj[i]) {  
                aux = true;   
            } 
        } 
        
        if (aux == false) {  
            return false; 
        }
        
        for (var i = 0, p1 = 5, p2 = 13; (cnpj.length - 2) > i; i++, p1--, p2--) {
            if (p1 >= 2) {  
                v1 += cnpj[i] * p1;  
            } else {  
                v1 += cnpj[i] * p2;  
            } 
        } 
        
        v1 = (v1 % 11);
        
        if (v1 < 2) { 
            v1 = 0; 
        } else { 
            v1 = (11 - v1); 
        } 
        
        if (v1 != cnpj[12]) {  
            return false; 
        } 
        
        for (var i = 0, p1 = 6, p2 = 14; (cnpj.length - 1) > i; i++, p1--, p2--) { 
            if (p1 >= 2) {  
                v2 += cnpj[i] * p1;  
            } else {   
                v2 += cnpj[i] * p2; 
            } 
        }
        
        v2 = (v2 % 11); 
        
        if (v2 < 2) {  
            v2 = 0;
        } else { 
            v2 = (11 - v2); 
        } 
        
        if (v2 != cnpj[13]) {   
            return false; 
        } else {  
            return true; 
        }
    } else {
        return false;
    }
 }

$("#mainLogisticsNav").addClass('active');

$("#carrierRegistrationNav").addClass('active');

$("#cnpj").change(function(){
  var cnpj = $("#cnpj").val();
  $("#helpcnpj").remove();
  if(validaCpfCnpj(cnpj)) {
    $("#cnpj" ).parent( "div" ).removeClass('has-error');
    $('#sendUpdate').removeAttr('disabled');
    $("#cnpj" ).parent( "div" ).addClass('has-success');
  } else {
    $('#cnpj').addClass('has-error');
    $("#cnpj" ).parent( "div" ).addClass('has-error');
    $("#cnpj" ).after("<span id='helpcnpj' class='help-block'>Informe CNPJ válido</span>")
    $("#sendUpdate").attr('disabled','disabled');
  }
});

$('#updateShippingCompany button[type="submit"]').on('click', function () {

    const el = $(this).closest('form');

    let errors = [];
    if (el.find('[name="name"]').val() === '') {
        errors.push(el.find('[name="name"]').closest('.form-group').find('label').text().remove_asterisk());
    }
    if (el.find('[name="raz_soc"]').val() === '') {
        errors.push(el.find('[name="raz_soc"]').closest('.form-group').find('label').text().remove_asterisk());
    }
    if (el.find('[name="cnpj"]').val() === '') {
        errors.push(el.find('[name="cnpj"]').closest('.form-group').find('label').text().remove_asterisk());
    } else {
        if (!validaCpfCnpj(el.find('[name="cnpj"]').val())) {
            errors.push(el.find('[name="cnpj"]').closest('.form-group').find('label').text().remove_asterisk());
        }
    }
    if (el.find('[name="phone"]').val() === '') {
        errors.push(el.find('[name="phone"]').closest('.form-group').find('label').text().remove_asterisk());
    }
    if (el.find('[name="responsible_name"]').val() === '') {
        errors.push(el.find('[name="responsible_name"]').closest('.form-group').find('label').text().remove_asterisk());
    }
    if (el.find('[name="responsible_email"]').val() === '') {
        errors.push(el.find('[name="responsible_email"]').closest('.form-group').find('label').text().remove_asterisk());
    }
    if (el.find('[name="store"]').val() === '') {
        errors.push(el.find('[name="store"]').closest('.form-group').find('label').text().remove_asterisk());
    }
    if (el.find('[name="slc_tipo_cubage"]').val() === 'FreteCubadoSim' && (el.find('[name="cubage_factor"]').val() === '' || el.find('[name="cubage_factor"]').val() == 0)) {
        errors.push(el.find('[name="cubage_factor"]').closest('.form-group').find('label').text().remove_asterisk());
    }

    if (errors.length) {
        Swal.fire({
            icon: 'warning',
            title: 'Informe todos os campos obrigatório',
            html: '<ul class="text-left"><li style="list-style-type: none">' + errors.join('</li><li style="list-style-type: none">') + '</li></ul>'
        });
    }
    console.log(errors);
});

String.prototype.remove_asterisk = function() {
    return this.replace('(*)', '');
}
</script>
