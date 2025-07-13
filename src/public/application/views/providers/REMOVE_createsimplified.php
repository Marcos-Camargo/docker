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
                  <div class="box">
                      <div class="box-header">
                          <h3 class="box-title"><?=$this->lang->line('application_new_provider');?></h3>
                      </div>
                      <form role="form" action="<?php base_url('providers/createsimplified') ?>" method="post">
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
                                      <label for="cnpj"><?=$this->lang->line('application_cnpj');?>(*)</label>
                                      <input type="text" class="form-control" id="cnpj" name="cnpj" required placeholder="<?=$this->lang->line('application_enter_CNPJ')?>" autocomplete="off" maxlength="18" size="18" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CNPJ',this,event);" value="<?=set_value('cnpj')?>">
                                  </div>
                              </div>
                              <div class="row">
                                  <div class="form-group col-md-4">
                                      <label for="phone_1"><?=$this->lang->line('application_phone');?>(*)</label>
                                      <input type="text" class="form-control" id="phone" name="phone" required placeholder="<?=$this->lang->line('application_enter_user_phone')?>" autocomplete="off" maxlength="15" size="15" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" value="<?=set_value('phone')?>">
                                  </div>
                                  <div class="form-group col-md-4">
                                      <label for="responsible_name"><?=$this->lang->line('application_responsible_name');?>(*)</label>
                                      <input type="text" class="form-control" id="responsible_name" name="responsible_name" required placeholder="<?=$this->lang->line('application_enter_responsible_name')?>" autocomplete="off" value="<?=set_value('responsible_name')?>">
                                  </div>
                                  <div class="form-group col-md-4">
                                      <label for="responsible_email"><?=$this->lang->line('application_responsible_email');?>(*)</label>
                                      <input type="text" class="form-control" id="responsible_email" name="responsible_email" required placeholder="<?=$this->lang->line('application_enter_responsible_email')?>" autocomplete="off" value="<?=set_value('responsible_email')?>">
                                  </div>
                              </div>
                              <div class="row">
                                  <div class="form-group col-md-4">
                                      <label for="store"><?=$this->lang->line('application_store');?>(*)</label>
                                      <select class="form-control" name="store" id="store" required >
                                          <option value=""></option>
                                          <?php foreach($stores as $key => $value ) { ?>
                                              <option value="<?php echo $value['id'];?>"><?php echo $value['name'];?></option>
                                          <?php } ?>
                                      </select>
                                  </div>
                                  <div class="form-group col-md-4">
                                      <label for="ad_valorem"><?=$this->lang->line('application_ad_valorem');?></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="O Ad Valorem (%) é uma taxa adicional cobrada sobre o valor total do frete, em forma percentual. Representa o seguro da carga. O Ad Valorem será adicionado ao valor do frete: (Valor do frete x Ad Valorem) + Valor do Frete. Digite apenas números. Use ponto para separar os decimais. Ex: 10.25"></i>
                                      <input type="text" class="form-control" id="ad_valorem" name="ad_valorem" placeholder="<?=$this->lang->line('application_enter_ad_valorem')?>" autocomplete="off" value="<?=set_value('ad_valorem')?>">
                                  </div>
                                  <div class="form-group col-md-4">
                                      <label for="gris"><?=$this->lang->line('application_gris');?></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="O GRIS (%) é uma taxa adicional cobrada sobre o valor total do frete, em forma percentual. Funciona como um gerenciamento de risco contra roubos de cargas. O GRIS será adicionado ao valor do frete: (Valor do frete x GRIS) + Valor do Frete. Digite apenas números. Use ponto para separar os decimais. Ex: 10.25"></i>
                                      <input type="text" class="form-control" id="gris" name="gris" placeholder="<?=$this->lang->line('application_enter_gris')?>" autocomplete="off" value="<?=set_value('gris')?>">
                                  </div>
                              </div>
                              <div class="row">
                                  <div class="form-group col-md-4">
                                      <label for="toll"><?=$this->lang->line('application_toll');?></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="O pedágio (R$) será adicionado ao valor do frete a cada 100kg do produto. Se o produto tiver menos de 100Kg, será adicionado apenas um pedágio. Ex: Para um produto de 220Kg adicionaremos três vezes o valor do pedágio, ao valor final do frete. Digite apenas números. Use ponto para separar os decimais. Ex: 10.25"></i>
                                      <input type="text" class="form-control" id="toll" name="toll"  placeholder="<?=$this->lang->line('application_enter_toll')?>" autocomplete="off" value="<?=set_value('toll')?>">
                                  </div>
                                  <div class="form-group col-md-4">
                                      <label for="shipping_revenue"><?=$this->lang->line('application_shipping_revenue');?></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="A Receita de Frete (%) é uma receita adicional cobrada sobre o valor total do frete, em forma percentual. Possibilita uma receita extra ao vendedor. Será adicionado ao valor do frete: (Valor do Frete x Receita de frete) + Valor do Frete. Digite apenas números. Use ponto para separar os decimais. Ex: 10.25"></i>
                                      <input type="text" class="form-control" id="shipping_revenue" name="shipping_revenue" placeholder="<?=$this->lang->line('application_enter_shipping_revenue')?>" autocomplete="off" value="<?=set_value('shipping_revenue')?>">
                                  </div>
                                  <div class="form-group col-md-4">
                                      <label for="slc_tipo_cubage"><?=$this->lang->line('application_cubic_weight');?>? (*)</label></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="Selecione a opção 'Sim' para utilizar o cálculo de frete com o peso cubado. O peso cubado é calculado multiplicando as dimensões do produto (L x C x A) pelo Fator de Cubagem."></i>
                                      <select class="form-control" id="slc_tipo_cubage" name="slc_tipo_cubage" value="<?=set_value('slc_tipo_cubage')?>" required >
                                          <option value="FreteCubadoNao">Não</option>
                                          <option value="FreteCubadoSim">Sim</option>
                                      </select>
                                  </div>
                              </div>
                              <div id="divFretecubado">
                                  <div class="row">
                                      <div class="form-group col-md-4">
                                          <label for="cubage_factor"><?=$this->lang->line('application_cubage_factor');?> (*)</label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="O fator de cubagem é um número constante definido por cada transportadora para realizar o cálculo do peso cubado. A unidade de medida adotada para o fator de cubagem é em Kg/cm³. Digite apenas números inteiros, sem vírgulas. Ex: 3152"></i>
                                          <input type="number" min="1" max="999999" onkeyup="this.value=this.value.replace(/[^0-9]/g,'');" class="form-control" id="cubage_factor" name="cubage_factor" required placeholder="<?=$this->lang->line('application_enter_cubage_factor')?>" autocomplete="off" value="<?=set_value('cubage_factor')?>">
                                      </div>
                                  </div>
                              </div>
                          </div>
                          <div class="box-footer">
                              <button type="submit" class="btn btn-primary" id="salveProvider"><?=$this->lang->line('application_save');?></button>
                              <a href="<?=base_url('shippingcompany/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                          </div>
                      </form>
                  </div>
              </div>
          </div>
      </section>
  </div>
  <script type="text/javascript">
  $(document).ready(function() {
    
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
    
    $("#mainLogisticsNav").addClass('active');
    $("#addProvidersNav").addClass('active');
    $("#cnpj").blur(function(){
          $(".erro").remove();
      $("#salveProvider").prop("disabled",false);
        var cnpj = isCNPJValid($(this).val());
      if(cnpj === false) {
        Toast.fire({
          icon: 'error',
          title: 'Informe um CNPJ válido.'
        });
        $("#salveProvider").prop("disabled",true);
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
</script>
