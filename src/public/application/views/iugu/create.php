<!--
SW Serviços de Informática 2019
<?php include_once(APPPATH . '/third_party/zipcode.php')?>

Criar Grupos de Acesso

-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <div class="col-md-12 col-xs-12">
        
        <div id="messages2"></div>
          
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
              <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3>
            </div>
            <form role="form" id="frmCadastrar" name="frmCadastrar" action="<?php base_url('billet/create') ?>" method="post">
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
				} 
			?>

                <div class="form-group col-md-4 col-xs-4">
                  <label for="group_isadmin"><?=$this->lang->line('application_billet_type_recipient');?>*</label>
                  <select class="form-control" id="slc_billet" name="slc_billet" <?php echo (form_error('slc_billet')) ? 'has-error' : '';  ?>>
                    <option value="">~~SELECT~~</option>
                    <option value="Adesao">Adesão</option>
                    <option value="Adesao agencia">Adesão Agencia</option>
                    <option value="Mensaliadde">Mensalidade</option>
                    <option value="Mensaliadde Agencia">Mensalidade Agencia</option>
                    <option value="Recebimento B2W">Recebimento B2W</option>
                    <option value="Recebimento Carrefour">Recebimento Carrefour</option>
                    <option value="Recebimento Mercado Livre">Recebimento Mercado Livre</option>
                    <option value="Recebimento Via Varejo">Recebimento Via Varejo</option>
                    <option value="Recebimento de Cliente Externo">Recebimento de Cliente Externo</option>
                    <option value="Outros">Outros</option>
                  </select>
                </div>
                
                <div class="form-group col-md-8 col-xs-8" id="divOutros" name="divOutros">
                  <label for="group_name"><?=$this->lang->line('application_billet_type_others');?></label>
                  <input type="text" class="form-control" id="txt_outros" name="txt_outros" placeholder="Outros">
                </div>

				<div class="form-group col-md-6 col-xs-6">
                <label for="group_isadmin"><?=$this->lang->line('application_store');?></label>
                  <select class="form-control" id="slc_store" name="slc_store">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($stores as $store) { ?>
                    	<option value="<?php echo $store['id']?>"><?php echo $store['name']?></option>
                    <?php }?>
                  </select>
                </div>
                
                 <div class="form-group col-md-6 col-xs-6">
                <label for="group_isadmin"><?=$this->lang->line('application_billet_type_payment');?>*</label>
                  <select class="form-control" id="txt_forma_pagamento" name="txt_forma_pagamento">
                    <option value="">~~SELECT~~</option>
                    <option value="boleto">Boleto</option>
                    <option value="cartao">Cartão de Crédito</option>
                    <option value="ambos">Ambos</option>
                  </select>
                </div>
                <div >
				<div class="form-group row">

				</div>
                    <div id="client_address" class="form-group">
						<div class="form-group col-md-4 col-md-3">
						<label for="group_name"><?=$this->lang->line('application_zip_code');?>*</label>
						<input type="text" class="form-control" id="txt_cep" name="txt_cep" placeholder="CEP" autocomplete="off" required>
						</div>
						<div class="form-group col-md-9">
    	                  <label for="txt_endereco"><?=$this->lang->line('application_address');?>*</label>
    	                  <input type="text" class="form-control" id="txt_endereco" name="txt_endereco"  placeholder="<?=$this->lang->line('application_enter_address')?>" autocomplete="off" value="<?=set_value('address')?>">
    	                </div>
						
						<div class="form-group col-md-1 col-xs-3">
							<label for="group_name"><?=$this->lang->line('application_number');?>*</label>
							<input type="text" class="form-control" id="txt_numero" name="txt_numero" placeholder="Número" required onkeyup="this.value=this.value.replace(/[!@#$%^&*¨)(+/{}~}º?°<>:;,./º\/\][]/,'')">
						</div>
						<div class="form-group col-md-2 col-xs-3">
    	                  <label for="txt_uf"><?=$this->lang->line('application_uf');?>(*)</label>
    	                  <select class="form-control" id="txt_uf" name="txt_uf" >
    	                    <option value=""><?=$this->lang->line('application_select');?></option>
    	                    <?php foreach ($ufs as $k => $v): ?>
    	                      <option value="<?=trim($k)?>" <?= set_select('txt_uf', $k) ?>><?=$v ?></option>
    	                    <?php endforeach ?>
    	                  </select>
    	                </div>
    	                <div class="form-group col-md-3 col-xs-3">
    	                  <label for="txt_complemento"><?=$this->lang->line('application_complement');?></label>
    	                  <input type="text" class="form-control" id="txt_complemento" name="txt_complemento" placeholder="<?=$this->lang->line('application_enter_complement')?>" autocomplete="off" value="<?=set_value('addr_compl')?>">
    	                </div>
    	                <div class="form-group col-md-3 col-xs-3">
    	                  <label for="txt_bairro"><?=$this->lang->line('application_neighb');?>*</label>
    	                  <input type="text" class="form-control" id="txt_bairro" name="txt_bairro"  placeholder="<?=$this->lang->line('application_enter_neighborhood')?>" autocomplete="off" value="<?=set_value('addr_neigh')?>">
    	                </div>
    	                <div class="form-group col-md-3 col-xs-3">
    	                  <label for="txt_cidade"><?=$this->lang->line('application_city');?>*</label>
    	                  <input type="text" class="form-control" id="txt_cidade" name="txt_cidade"  placeholder="<?=$this->lang->line('application_enter_city')?>" autocomplete="off" value="<?=set_value('addr_city')?>">
    	                </div>
						<input type="hidden" class="form-control" id="txt_pais" name="txt_pais">
    				</div>
				</div>     
                <hr>
                <div class="form-group col-md-6 col-xs-3">
                    <label for="group_name"><?=$this->lang->line('application_filename');?>/<?=$this->lang->line('application_raz_soc');?>*</label>
                    <input type="text" class="form-control" id="txt_nome" name="txt_nome" placeholder="Nome/Razão Social">
                </div>
                
                <div class="form-group col-md-6 col-xs-3">
                    <label for="group_name"><?=$this->lang->line('application_cnpj');?>/<?=$this->lang->line('application_cpf');?>*</label>
                    <input type="text" class="form-control" id="txt_cpf_cnpj" name="txt_cpf_cnpj" placeholder="CNPJ/CPF">
                </div>
                                
                <div class="form-group col-md-6 col-xs-6">
                    <label for="group_name"><?=$this->lang->line('application_email');?>*</label>
                    <input type="email" class="form-control" id="txt_email" name="txt_email" placeholder="Email">
                </div>
                <div class="form-group col-md-6 col-xs-6">
                    <label for="group_name"><?=$this->lang->line('application_due_date');?>*</label>
                    <input type="date" class="form-control" id="txt_dt_vencimento" name="txt_dt_vencimento" placeholder="Data de Vencimento" value="<?php echo date("Y-m-j");?>">
                </div>
                <div class="form-group col-md-6 col-xs-6" <?php echo (form_error('txt_desc_item')) ? 'has-error' : '';  ?>>
                    <label for="group_name"><?=$this->lang->line('application_billet_type');?>*</label>
                    <input type="text" class="form-control" id="txt_desc_item" name="txt_desc_item" placeholder="Descrição Cobrança">
					<?php echo '<i style="color:red">'.form_error('txt_desc_item').'</i>'; ?> 
				</div>
                <div class="form-group col-md-6 col-xs-6">
                    <label for="group_name"><?=$this->lang->line('application_value');?>*</label>
                    <input type="number" class="form-control" id="txt_valor" name="txt_valor" placeholder="Valor Cobrança" autocomplete="off" min="1" step='0.01' value='1.00'>
                </div>
                
              </div>
              
          
              <div class="box-footer">
                <button type="submit" id="btnSave" name="btnSave" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                <button type="button" id="btnVoltar" name="btnVoltar" class="btn btn-warning"><?=$this->lang->line('application_back');?></button>
              </div>
            </form>
            
            <div id="retornoTeste" name="retornoTest">
            
            
            
            </div>
            
            
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
var manageTableResult;
var manageTableBillet;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {
	$("#slc_store").select2();
	$("#btnVoltar").click( function(){
    	window.location.assign(base_url.concat("iugu/list"));
    });
	$("#txt_outros").prop('disabled', true);
	$("#txt_nome").prop('disabled', true);
	$("#txt_cpf_cnpj").prop('disabled', true); 
	$("#txt_cep").prop('disabled', true);
	$("#txt_numero").prop('disabled', true);
	$("#txt_email").prop('disabled', true);
	$("#txt_endereco").prop('disabled', true);
	$("#txt_complemento").prop('disabled', true);
	$("#txt_bairro").prop('disabled', true);  
	$("#txt_cidade").prop('disabled', true);
	$("#txt_uf").prop('disabled', true);
	$("#txt_pais").prop('disabled', true);
	//$( "#client_address" ).hide();
	$("#slc_billet").change(function() {
		if( $("#slc_billet").val() == "Recebimento B2W"
			|| $("#slc_billet").val() == "Recebimento Carrefour"
			|| $("#slc_billet").val() == "Adesao"
			|| $("#slc_billet").val() == "Adesao agencia"
			|| $("#slc_billet").val() == "Mensaliadde"
			|| $("#slc_billet").val() == "Mensaliadde Agencia"
			|| $("#slc_billet").val() == "Recebimento Mercado Livre"
			|| $("#slc_billet").val() == "Recebimento Via Varejo"){
			$("#txt_outros").prop('disabled', false);
			$("#slc_store").prop('disabled', false);
			desableClientForm();
		}else if($("#slc_billet").val() == "Recebimento de Cliente Externo"){
			clear_client_form();
			$("#slc_store").prop('disabled', true);
			$("#txt_outros").prop('disabled', true);
			$("#txt_outros").val('');
			enableFormClient();
		}else if($("#slc_billet").val() == "Outros"){
			clear_client_form();
			$("#slc_store").prop('disabled', false);
			$("#txt_outros").prop('disabled', false);
			$("#txt_outros").val('');
			enableFormClient();
		}else{
			$("#slc_store").prop('disabled', false);
			$("#txt_outros").prop('disabled', true);
			$("#txt_outros").val("");
		}
	});

	$("#slc_store").change(function() {

		if ( $("#slc_store").val() == "" ){
			$("#txt_nome").val("");
			$("#txt_cpf_cnpj").val("");
			$("#txt_cep").val("");
			$("#txt_numero").val("");
			$("#txt_complemento").val("");
			$("#txt_email").val("");
			$("#txt_endereco").val("");
			$("#txt_bairro").val("");
			$("#txt_cidade").val("");
			$("#txt_uf").val("");
			$("#txt_pais").val("");

		}else{
			
    		var pageURL2 = base_url.concat("iugu/buscaloja");
    		console.log(pageURL2);
    		$.post( pageURL2, $("#frmCadastrar").serialize(), function( data ) {
    			var obj = JSON.parse(data);
    			$("#txt_nome").val(obj[0].raz_social);
    			$("#txt_cpf_cnpj").val(obj[0].CNPJ);
    			$("#txt_cep").val(obj[0].zipcode);
    			$("#txt_numero").val(obj[0].addr_num);
    			$("#txt_numero").val(obj[0].addr_num);
    			$("#txt_email").val(obj[0].responsible_email);
    			$("#txt_endereco").val(obj[0].address);
    			$("#txt_complemento").val(obj[0].addr_compl);
    			$("#txt_bairro").val(obj[0].addr_neigh);   
    			$("#txt_cidade").val(obj[0].addr_city);
    			$("#txt_uf").val(obj[0].addr_uf);
    			$("#txt_pais").val(obj[0].country);
    		});
    		
		}
	});
	

	

	$("#btnSave").click( function(){
		let arrFormData = [];
			arrFormData.push({'txt_email': $("#txt_email").val()});
			arrFormData.push({'txt_dt_vencimento': $("#txt_dt_vencimento").val()});
			arrFormData.push({'txt_desc_item':$("#txt_desc_item").val()});
			arrFormData.push({'txt_valor': $("#txt_valor").val()});
			arrFormData.push({'txt_nome': $("#txt_nome").val()});
			arrFormData.push({'txt_cpf_cnpj': $("#txt_cpf_cnpj").val()});
			arrFormData.push({'txt_cep': $("#txt_cep").val()});
			arrFormData.push({'txt_endereco': $("#txt_endereco").val()});
			arrFormData.push({'txt_numero': $("#txt_numero").val()});
			arrFormData.push({'txt_bairro': $("#txt_bairro").val()});
			arrFormData.push({'txt_cidade': $("#txt_cidade").val()});
			arrFormData.push({'txt_uf': $("#txt_uf").val()});
			arrFormData.push({'txt_forma_pagamento': $("#txt_forma_pagamento").val()});

		if($("#txt_forma_pagamento").val() == ""){
    			alert ("Preencha o tipo de pagamento");
    			$("#btnSave").prop('disabled', false);
    			$("#btnVoltar").prop('disabled', false);
			return false;
		}
		if($("#txt_cep").val() == ""){
    			alert ("Informe o CEP..");
    			$("#btnSave").prop('disabled', false);
    			$("#btnVoltar").prop('disabled', false);
				$("#txt_cep").focus();
			return false;
		}
		if($("#txt_endereco").val() == ""){
    			alert ("Preencha o endereço");
    			$("#btnSave").prop('disabled', false);
    			$("#btnVoltar").prop('disabled', false);
				$("#txt_endereco").focus();
			return false;
		}	
		if($("#txt_nome").val() == ""){
    			alert ("Preencha o nome");
    			$("#btnSave").prop('disabled', false);
    			$("#btnVoltar").prop('disabled', false);
				$("#txt_nome").focus();
			return false;
		}		
		if($("#txt_cpf_cnpj").val() == ""){
    			alert ("Preencha o CPF ou CNPJ");
    			$("#btnSave").prop('disabled', false);
    			$("#btnVoltar").prop('disabled', false);
				$("#txt_nome").focus();
			return false;
		}
		if($("#txt_forma_pagamento").val() == ""){
    			alert ("Preencha o tipo de pagamento");
    			$("#btnSave").prop('disabled', false);
    			$("#btnVoltar").prop('disabled', false);
			return false;
		}
		if( !validateEmail($("#txt_email").val())) {
			alert ("Informe um e-mail válido");
    			$("#btnSave").prop('disabled', false);
    			$("#btnVoltar").prop('disabled', false);
			return false;
		}
		if($("#txt_email").val() == "") {
			alert ("Informe o endereço de e-mail");
    			$("#btnSave").prop('disabled', false);
    			$("#btnVoltar").prop('disabled', false);
			return false;
		}		
		if($("#txt_desc_item").val() == "") {
			alert ("Informe a Descrição do item");
    			$("#btnSave").prop('disabled', false);
    			$("#btnVoltar").prop('disabled', false);
			return false;
		}
		if($("#txt_numero").val() == ""){
    			alert ("Preencha o número no endereço");
				$("#txt_numero").focus();
    			$("#btnSave").prop('disabled', false);
    			$("#btnVoltar").prop('disabled', false);
			return false;
		}
		if(confirm("Deseja realmente criar esse boleto?")){
			var slc_store = "";
    		$("#btnVoltar").prop('disabled', true);
    		$("#btnSave").prop('disabled', true);
			if($("#slc_billet").val() == "Recebimento de Cliente Externo" ){
				slc_store = 'slc_store=10&';
			}else{
				slc_store = ''
			}
    		if($("#slc_billet").val() == ""){
    			alert ("Preencha o tipo do boleto a ser gerado");
    			$("#btnSave").prop('disabled', false);
    			$("#btnVoltar").prop('disabled', false);
    			return false;
    		}else{
    			if($("#slc_billet").val() == "Outros" && $("#txt_outros").val() == ""){
    				alert ("Preencha o tipo do boleto a ser gerado");
    				$("#btnSave").prop('disabled', false);
        			$("#btnVoltar").prop('disabled', false);
    				return false;
    			}
    		}

    		if($("#txt_email").val() 			== ""	||
    			$("#txt_dt_vencimento").val() 	== ""	||
    			$("#txt_desc_item").val() 		== ""	||
    			$("#txt_valor").val() 			== ""	||
    			$("#txt_nome").val() 			== ""	||
    			$("#txt_cpf_cnpj").val() 		== ""	||
    			$("#txt_cep").val() 			== ""	||
    			$("#txt_endereco").val() 		== ""	||
    			$("#txt_bairro").val()			== ""	||
    			$("#txt_cidade").val()			== ""	||
    			$("#txt_uf").val()				== ""	||
    			$("#txt_pais").val()			== ""	||
    			$("#txt_forma_pagamento").val()	== ""	||
    			$("#txt_numero").val() 			== ""	)
    		{
    			$("#btnSave").prop('disabled', false);
    			$("#btnVoltar").prop('disabled', false);
    			return false;
    		}
    
    		$("#txt_outros").prop('disabled', false);
    		$("#txt_nome").prop('disabled', false);
    		$("#txt_cpf_cnpj").prop('disabled', false);
    		$("#txt_cep").prop('disabled', false);
    		$("#txt_numero").prop('disabled', false);
    		$("#txt_email").prop('disabled', false);

    		var pageURL = base_url.concat("iugu/createbilletiugu");
    		$.post( pageURL, slc_store + $("#frmCadastrar").serialize(), function( data ) {
    			console.log(data);
    			if($("#slc_billet").val() == "Outros"){
    				$("#txt_outros").prop('disabled', false);
    			}else{
    				$("#txt_outros").prop('disabled', true);
    			}
    
    			if($("#slc_store").val() == "0"){
        			$("#txt_nome").prop('disabled', false);
        			$("#txt_cpf_cnpj").prop('disabled', false); 
        			$("#txt_cep").prop('disabled', false);
        			$("#txt_numero").prop('disabled', false);
        			$("#txt_email").prop('disabled', false);
    			}else{
    				$("#txt_nome").prop('disabled', true);
    				$("#txt_email").prop('disabled', true);
        			$("#txt_cpf_cnpj").prop('disabled', true); 
        			$("#txt_cep").prop('disabled', true);
        			$("#txt_numero").prop('disabled', true);
    			}
    			
    			var retorno = data.split(";");
    			if(retorno[0] == "0"){
    				$("#messages2").html('<div class="alert alert-success alert-dismissible" role="alert">'+
        		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
        		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+retorno[1]+
        		            '</div>');
    				$("#btnVoltar").prop('disabled', false);
    			}else{
    				$("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
    	              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
    	              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+retorno[1]+
    	            '</div>'); 
    				$("#btnSave").prop('disabled', false);
    				$("#btnVoltar").prop('disabled', false);
    			}
    			
    		});

		}

	});
	function validateEmail($email) {
		var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
		return emailReg.test( $email );
	}
	function validateNumberAddress($number){
		var numberAddress = /^[\d\-]+$/;
		return numberAddress.test( $number );
	}
	function clear_address_form() {
		// Limpa valores do formulário de cep.
		$("#txt_complemento").val("");
		$("#txt_endereco").val("");
		$("#txt_bairro").val("");
		$("#txt_cidade").val("");
		$("#txt_uf").val("");
		$("#txt_pais").val("");
	}
	function clear_client_form() {
		// Limpa valores do formulário.
		//$("#slc_store").empty();
		$('#slc_store').val("").trigger('change.select2');
		$("#txt_nome").val("");
		$("#txt_cpf_cnpj").val("");
		$("#txt_cep").val("");
		$("#txt_numero").val("");
		$("#txt_complemento").val("");
		$("#txt_email").val("");
		$("#txt_endereco").val("");
		$("#txt_bairro").val("");
		$("#txt_cidade").val("");
		$("#txt_uf").val("");
		$("#txt_pais").val("");
		$("#txt_complemento").val("");
		$("#txt_endereco").val("");
		$("#txt_bairro").val("");
		$("#txt_cidade").val("");
		$("#txt_uf").val("");
		$("#txt_pais").val("");

	}
	function desableClientForm() {
		$("#txt_nome").prop('disabled', true);
		$("#txt_cpf_cnpj").prop('disabled', true); 
		$("#txt_cep").prop('disabled', true);
		$("#txt_numero").prop('disabled', true);
		$("#txt_email").prop('disabled', true);
		$("#txt_endereco").prop('disabled', true);
		$("#txt_complemento").prop('disabled', true);
		$("#txt_bairro").prop('disabled', true);  
		$("#txt_cidade").prop('disabled', true);
		$("#txt_uf").prop('disabled', true);
		$("#txt_pais").prop('disabled', true);
	}
	function enableFormClient(){
		$("#txt_nome").prop('disabled', false);
		$("#txt_cpf_cnpj").prop('disabled', false); 
		$("#txt_cep").prop('disabled', false);
		$("#txt_numero").prop('disabled', false);
		$("#txt_email").prop('disabled', false);
		$("#txt_endereco").prop('disabled', false);
		$("#txt_complemento").prop('disabled', false);
		$("#txt_bairro").prop('disabled', false);  
		$("#txt_cidade").prop('disabled', false);
		$("#txt_uf").prop('disabled', false);
		$("#txt_pais").prop('disabled', false);
	}
	var options = {
    onKeyPress: function (cpf, ev, el, op) {
        var masks = ['000.000.000-000', '00.000.000/0000-00'];
			$('#txt_cpf_cnpj').mask((cpf.length > 14) ? masks[1] : masks[0], op);
		}
	}
	$("#txt_cep").mask('00000-000');
	$('#txt_cpf_cnpj').length > 11 ? $('#txt_cpf_cnpj').mask('00.000.000/0000-00', options) : $('#txt_cpf_cnpj').mask('000.000.000-00#', options);
	//Quando o campo cep perde o foco.
	$("#txt_cep").blur(function() {

		//Nova variável "cep" somente com dígitos.
		var cep = $(this).val().replace(/\D/g, '');

		//Verifica se campo cep possui valor informado.
		if (cep != "") {

			//Expressão regular para validar o CEP.
			var validacep = /^[0-9]{8}$/;

			//Valida o formato do CEP.
			if(validacep.test(cep)) {

				//Preenche os campos com "..." enquanto consulta webservice.
				$("#txt_complemento").val("...");
				$("#txt_endereco").val("...");
				$("#txt_bairro").val("...");
				$("#txt_cidade").val("...");
				$("#txt_uf").val("...");
				$("#txt_pais").val("...");
				//Consulta o webservice viacep.com.br/
				$.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {

					if (!("erro" in dados)) {
						//Atualiza os campos com os valores da consulta.
						$("#txt_endereco").val(dados.logradouro);;
						$("#txt_complemento").val(dados.complemento);
						$("#txt_bairro").val(dados.bairro);
						$("#txt_cidade").val(dados.localidade);
						$("#txt_uf").val(dados.uf);
					} //end if.
					else {
						//CEP pesquisado não foi encontrado.
						clear_address_form();
						alert("CEP não encontrado.");
					}
				});
			} //end if.
			else {
				//cep é inválido.
				clear_address_form();
				alert("Formato de CEP inválido.");
			}
		} //end if.
		else {
			//cep sem valor, limpa formulário.
			clear_address_form();
		}
	});
		

	
});


  
</script>