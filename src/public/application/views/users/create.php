<!--
SW Serviços de Informática 2019

Criar usuarios

-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data); ?>

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
              <h3 class="box-title"><?=$this->lang->line('application_add_user');?></h3>
            </div>
            <form id="formCreateUser" name="form-create-user" role="form" action="<?php base_url('users/create') ?>" method="post" autocomplete="off">
                <input type="text" style="display:none">
                <input type="password" style="display:none">
                <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
              
              <div class="box-body">
                <div class="row">

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
                </div>

                <div class="row">
                    <div class="form-group col-md-5 <?php echo (form_error('username')) ? 'has-error' : '';  ?>">
                        <label for="username"><?=$this->lang->line('application_username');?>(*)</label>
                        <input type="text" class="form-control" id="username" required minlenght="5" name="username" placeholder="<?=$this->lang->line('application_enter_user_username');?>" value="<?php echo set_value('username','') ?>"  autocomplete="off">
                        <p id="usernameDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_username')?></p>
                        <?php echo '<i style="color:red">'.form_error('username').'</i>'; ?>
                    </div>

                    <div class="form-group col-md-5 <?php echo (form_error('email')) ? 'has-error' : '';  ?>">
                        <label for="email"><?=$this->lang->line('application_email');?>(*)</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="<?=$this->lang->line('application_enter_user_email');?>" value="<?php echo set_value('email', '') ?>"  autocomplete="off">
                        <p id="emailDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_email')?></p>
                        <?php echo '<i style="color:red">'.form_error('email').'</i>'; ?>  
                    </div>
                
                    <div class="form-group col-md-2  <?php echo (form_error('active')) ? 'has-error' : '';  ?>">
                        <label for="active"><?=$this->lang->line('application_active');?>(*)</label>
                        <select class="form-control" id="active" name="active">
                          <option value="1" <?php echo set_select('active', 1) ?>><?=$this->lang->line('application_yes');?></option>
                          <option value="2" <?php echo set_select('active', 2) ?>><?=$this->lang->line('application_no');?></option>
                        </select>
                        <?php echo '<i style="color:red">'.form_error('active').'</i>'; ?>  
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-5 <?php echo (form_error('fname')) ? 'has-error' : '';  ?>">
                        <label for="fname"><?=$this->lang->line('application_firstname');?>(*)</label>
                        <input type="text" class="form-control" id="fname" name="fname" required placeholder="<?=$this->lang->line('application_enter_user_fname');?>" value="<?php echo set_value('fname') ?>"  autocomplete="off">
                        <p id="fnameDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_fname')?></p>
                        <?php echo '<i style="color:red">'.form_error('fname').'</i>'; ?>  
                    </div>

                    <div class="form-group col-md-5 <?php echo (form_error('lname')) ? 'has-error' : '';  ?>">
                        <label for="lname"><?=$this->lang->line('application_lastname');?>(*)</label>
                        <input type="text" class="form-control" id="lname" name="lname" required placeholder="<?=$this->lang->line('application_enter_user_lname');?>" value="<?php echo set_value('lname') ?>"  autocomplete="off">
                        <p id="lnameDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_lname')?></p>
                        <?php echo '<i style="color:red">'.form_error('lname').'</i>'; ?>  
                    </div>

                    <div class="form-group col-md-2  <?php echo (form_error('phone')) ? 'has-error' : '';  ?>">
                        <label for="phone"><?=$this->lang->line('application_phone');?>(*)</label>
                        <input type="text" class="form-control" id="phone" name="phone" required placeholder="<?=$this->lang->line('application_enter_user_phone');?>" value="<?php echo set_value('phone') ?>"  autocomplete="off">
                        <p id="phoneDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_phone')?></p>
                        <?php echo '<i style="color:red">'.form_error('phone').'</i>'; ?>  
                    </div>
                </div>
                <div class="row">
				
				        <?php $defenable = ($this->session->flashdata('company_id')) ? " disabled " : ""; ?>

                </div>
                <div class="row">
                <div class="form-group col-md-3 <?php echo (form_error('company')) ? 'has-error' : '';  ?>">
                  	<label for="company"><?=$this->lang->line('application_company');?>(*)</label>
                  	<select class="form-control" id="company" name="company" required>
                    	  <option disabled selected value=""><?=$this->lang->line('application_select');?></option>
                    	<?php foreach ($company_data as $k => $v)  {
                            	$enable = $defenable;
                            	if ($v['id']==$this->session->flashdata('company_id') ) {
                            		$enable = "";
                            	} ?>
                      	<option <?php echo $enable; ?> value="<?php echo $v['id'] ?>" <?php echo set_select('company', $v['id'], $v['id'] == $this->session->flashdata('company_id')); ?> ><?php echo $v['name'] ?></option>
                      <?php } ?>
                  	</select>
                    <p id="companyDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_company')?></p>
                	  <?php echo '<i style="color:red">'.form_error('company').'</i>'; ?>  
                </div>
                
                <div class="form-group col-md-3 <?php echo (form_error('store_id')) ? 'has-error' : '';  ?>">
                  	<label for="store_id"><?=$this->lang->line('application_store');?></label>
                  	<select class="form-control" id="store_id" name="store_id" required>
                        <option value="0"><?=$this->lang->line('application_all_stores');?></option>
                  	</select>
                	  <?php echo '<i style="color:red">'.form_error('store_id').'</i>'; ?>  
                </div>

                <div class="form-group col-md-3 <?php echo (form_error('groups')) ? 'has-error' : '';  ?>">
                  	<label for="groups"><?=$this->lang->line('application_groups');?>(*)</label>
                  	<select class="form-control" id="groups" name="groups" required>
                    	  <option disabled selected value=""><?=$this->lang->line('application_select');?></option>
                    	  <?php foreach ($group_data as $k => $v): ?>
                      	    <option value="<?php echo $v['id'] ?>" <?php echo set_select('groups', $v['id'], false); ?>  ><?php echo $v['group_name'] ?></option>
                    	  <?php endforeach ?>
                 	  </select>
                    <p id="groupsDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_groups')?></p>
                	  <?php echo '<i style="color:red">'.form_error('groups').'</i>'; ?>
                </div>
                <div class="form-group col-md-3 <?php echo (form_error('associate_type_pj')) ? 'has-error' : '';  ?>">
                    <label for="associate_type_pj"><?=$this->lang->line('application_associate_type')?>(*)</label>
                    <?php ?>
                    <select class="form-control" id="associate_type_pj" name="associate_type_pj">
                        <option value="0" <?= set_select('associate_type_pj', 0) ?>><?=$this->lang->line('application_parent_company')?></option>
                        <option value="1" <?= set_select('associate_type_pj', 1) ?>><?=$this->lang->line('application_agency')?></option>
                        <option value="2" <?= set_select('associate_type_pj', 2) ?>><?=$this->lang->line('application_partner')?></option>
                        <option value="3" <?= set_select('associate_type_pj', 3) ?>><?=$this->lang->line('application_affiliate')?></option>
                        <option value="4" <?= set_select('associate_type_pj', 4) ?>><?=$this->lang->line('application_autonomous')?></option>
                        <option value="5" <?= set_select('associate_type_pj', 5) ?>><?=$this->lang->line('application_indicator')?></option>
                    </select>
                    <?php echo '<i style="color:red">'.form_error('associate_type_pj').'</i>'; ?>
                </div>
                </div>
                <?php if (in_array('doIntegration', $user_permission)) : ?>
                <div class="row" id="viewDataIndicate" style="display: none">
                    <div class="form-group col-md-3 <?php echo (form_error('bank')) ? "has-error" : "";?>">
                        <label for="bank"><?=$this->lang->line('application_bank');?></label>
                        <select class="form-control" id="bank" name="bank">
                            <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                            <?php foreach ($banks as $k => $v):?>
                                <option value="<?=trim($v['name'])?>" <?=set_select('bank', $v['name']) ?>><?=$v['name'] ?></option>
                            <?php endforeach ?>
                        </select>
                        <p id="bankDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_bank')?></p>
                        <?php echo '<i style="color:red">'.form_error('bank').'</i>'; ?>
                    </div>
                    <div class="form-group col-md-3 <?php echo (form_error('agency')) ? "has-error" : "";?>">
                        <label for="agency"><?=$this->lang->line('application_agency');?></label>
                        <input type="text" class="form-control" id="agency" name="agency" placeholder="<?=$this->lang->line('application_enter_agency')?>" autocomplete="off" value="<?=set_value('agency')?>">
                        <p id="agencyDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_agency')?></p>                        
                        <?php echo '<i style="color:red">'.form_error('agency').'</i>'; ?>
                    </div>
                    <div class="form-group col-md-3 <?php echo (form_error('account_type')) ? "has-error" : "";?>">
                        <label for="currency"><?=$this->lang->line('application_type_account');?></label>
                        <select class="form-control" id="account_type" name="account_type">
                            <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                            <?php foreach ($type_accounts as $k => $v): ?>
                                <option value="<?=trim($v)?>" <?= set_select('account_type', $v) ?>><?=$v ?></option>
                            <?php endforeach ?>
                        </select>
                        <?php echo '<i style="color:red">'.form_error('account_type').'</i>'; ?>
                    </div>
                    <div class="form-group col-md-3 <?php echo (form_error('account')) ? "has-error" : "";?>">
                        <label for="account"><?=$this->lang->line('application_account');?></label>
                        <input type="text" class="form-control" id="account" name="account" placeholder="<?=$this->lang->line('application_enter_bank_account')?>" autocomplete="off" value="<?=set_value('account','')?>">
                        <p id="accountDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_bank_account')?></p>
                        <?php echo '<i style="color:red">'.form_error('account').'</i>'; ?>
                        <td class="error"><?php echo form_error(); ?></td>
                    </div>
                </div>
                <?php endif ?>
                <div class="row">
                    <label class="col-md-3">
                        <input type="checkbox" onclick="checkLegalAdministrator()" class="minimal" name="legal_administrator" id="legal_administrator" value="1" <?php echo set_checkbox('legal_administrator', '1') ?>>
                        <?=$this->lang->line('application_legal_administrator');?>
                    </label>
                    <div class="form-group col-md-3  <?php echo (form_error('cpf')) ? 'has-error' : '';  ?>" id="cpfdiv"> 
                        <label for="cpf"><?=$this->lang->line('application_cpf');?></label>                        
                        <input type="text" class="form-control"  id="cpf" name="cpf" required placeholder="<?=$this->lang->line('application_cpf');?>" value="<?php echo set_value('cpf','') ?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" maxlength="14">
                        <p id="cpfDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_enter_CPF')?></p>
                        <?php echo '<i style="color:red">'.form_error('cpf').'</i>'; ?>  
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-5">
                        <label for="external_authentication_id">Tipo de Authenticação</label>
                        <select class="form-control" id="external_authentication_id" name="external_authentication_id">
                            <option selected value="0">Interna (senha gravada localmente)</option>
                            <?php foreach ($externals_authentication as $extauth): ?>
                                <option value="<?=$extauth['id']?>" <?=set_select('external_authentication_id', $extauth['id'] ); ?>><?=$extauth['name'].' ('.$extauth['type'].')';?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>

                <div class="row" id="viewInternalAuthentication">
                    <div class="form-group col-md-5 <?php echo (form_error('password')) ? 'has-error' : '';  ?>">
                        <label for="password"><?=$this->lang->line('application_password');?>(*)</label>
                        <input type="password" class="form-control" id="password" name="password" required value="<?php echo set_value('password','');?>" placeholder="<?=$this->lang->line('application_enter_user_password');?>" autocomplete="off">
                        <span onclick="hideShowPass(event, 'password')" ><small><i class="far fa-eye"></i><?=$this->lang->line('application_view_password');?></small></span>
                        <p id="passwordDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_password')?></p>
                        <?php echo '<i style="color:red">'.form_error('password').'</i>'; ?>
                    </div>

                    <div class="form-group col-md-5 <?php echo (form_error('cpassword')) ? 'has-error' : '';  ?>">
                        <label for="cpassword"><?=$this->lang->line('application_confirm_password');?>(*)</label>
                        <input type="password" class="form-control" id="cpassword" name="cpassword" required value="<?php echo set_value('cpassword','') ?>" placeholder="<?=$this->lang->line('application_enter_user_cpassword');?>" autocomplete="off">
                        <span onclick="hideShowPass(event, 'cpassword')" ><small><i class="far fa-eye"></i><?=$this->lang->line('application_view_password');?></small></span>
                        <p id="cpasswordDanger" class="text-danger d-none" style="display: none;"><?=$this->lang->line('application_user_required_cpassword')?></p>
                        <?php echo '<i style="color:red">'.form_error('cpassword').'</i>'; ?>
                    </div>
                </div>

              </div>
              <!-- /.box-body -->

              <div class="box-footer">
                <button type="submit" id="btnUserCreate" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                <a href="<?php echo base_url('users/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
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
var lojas = <?php echo json_encode($lojas); ?>;
var store_create = <?php echo $this->session->flashdata('store_id')??0 ; ?>;
var banks = <?php echo json_encode($banks); ?>;
var usar_mascara_banco = "<?php echo $usar_mascara_banco ?>";

var lojaoriginal = undefined; 
let fistLoad=true;
  $(document).ready(function() {
    var agency = $('#agency').val();
    var account = $('#account').val();
    var bank_name = $('#bank option:selected').val();

    if(store_create!=0){
      setCreatedStore(store_create,lojas);
    }
    // $("#groups").select2();

    $("#mainUserNav").addClass('active');
    $("#createUserNav").addClass('active');

    var val = $("#cpf").val();
		$("#cpf").val(val.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/g,"\$1.\$2.\$3\-\$4"));

    $("#cpf").on('focusout',function(){
				var val = $(this).val();
			  $(this).val(val.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/g,"\$1.\$2.\$3\-\$4"));
		});

    if ($('#external_authentication_id').val() == 0) {
        $('#password').prop('required', true);
        $('#cpassword').prop('required', true);
        $('#viewInternalAuthentication').show();
    }else {
        $('#password').prop('required', false);
        $('#cpassword').prop('required', false);
        $('#viewInternalAuthentication').hide();
    }
    if ($('#associate_type_pj').val() != 0) {
        $('#viewDataIndicate').show();
        $('#viewDataIndicate .form-control').each(function () {
            $(this).prop('required', true);
        })
    }
    if(usar_mascara_banco == true){
        applyBankMask(bank_name);
    }
    $("#bank").change(function () {
        $('#agency').val('');
        $('#account').val('');
        bank_name = $('#bank option:selected').val();
        if(usar_mascara_banco == true){
            applyBankMask(bank_name);
        }
    });

    $('#external_authentication_id').on('change', function (){
      if ($(this).val() == 0) {
          $('#viewInternalAuthentication').slideDown('slow');
          $('#password').prop('required', true);
          $('#cpassword').prop('required', true);
      }
      else {
          $('#viewInternalAuthentication').slideUp('slow');
          $('#password').prop('required', false);
          $('#cpassword').prop('required', false);
      }
    });

    var id_company=$('#company option:selected').val();
    selectAndSetStore(id_company,lojas);
    $("#company").change(function () {
      var id_company=$('#company option:selected').val();
      selectAndSetStore(id_company,lojas);
		
	  });
    checkLegalAdministrator();
  });
  
  
  
  $("#bttestaragidesk").click(function(e) {
    e.preventDefault();
    if (($("#email").val()=='') || ($("#password_agidesk").val()=='')) {
    	Swal.fire({
				  icon: 'success',
				  title: "Digite, pelo menos, o email e a senha do Agidesk"
				});
    	return ;
    }
	var params = { 
		"username": $("#email").val(),
		"password": $("#password_agidesk").val(),
		"grant_type": "password"};
    $.ajax({
    	contentType: 'application/x-www-form-urlencoded',
    	crossDomain: true,
        type: "POST",
        url: "https://agidesk.com/api/v1/auth/token",
        data: params,
        dataType: 'json',
        success: function(result) {
            Swal.fire({
				  icon: 'success',
				  title: "Login OK"
				});
          //  alert(result.access_token); 
        },
        error: function(result) {
        	var responseJson = result.responseJSON;
        	//alert(JSON.stringify(responseJson));
            Swal.fire({
				  icon: 'error',
				  title: "Erro: "+responseJson.error_description
				});
            //alert(JSON.stringify(result));
        }
    });
  });

  $('#associate_type_pj').on('change', function (){
    if ($(this).val() != 0) {
        $('#viewDataIndicate').slideDown('slow');
        $('#viewDataIndicate .form-control').each(function () {
            $(this).prop('required', true);
        })
    } else {
        $('#viewDataIndicate').slideUp('slow');
        $('[name="bank"]').val('');
        $('[name="agency"]').val('');
        $('[name="account_type"]').val('');
        $('[name="account"]').val('');
        $('#viewDataIndicate .form-control').each(function () {
            $(this).prop('required', false);
        })
    }
  });
  function applyBankMask(bank_name){
      $.each(banks, function(i,bank){
          if(banks[i].name == bank_name) {
              var pattern = /[a-zA-Z0-9]/ig;
              mask_account = banks[i].mask_account.replaceAll(pattern, "#")
              mask_agency = banks[i].mask_agency.replace(pattern, "#")
              $('#agency').mask(mask_agency);
              $('#agency').attr("placeholder", banks[i].mask_agency);
              $('#agency').attr("maxlength", mask_agency.length);
              $('#agency').attr("minlength", mask_agency.length);
              $('#account').mask(mask_account);
              $('#account').attr("placeholder", banks[i].mask_account);
              $('#account').attr("maxlength", mask_account.length);
              $('#account').attr("minlength", mask_account.length);
          }
      });
  }

  function hideShowPass(e, fieldid) {
  	  e.preventDefault();
	    var x = document.getElementById(fieldid);
	    if (x.type === "password") {
	        x.type = "text";
	    } else {
	        x.type = "password";
	    }
  }

  function setCreatedStore(store_id,lojas){
      let filtered_store=lojas.filter((element)=>{
          return element.id==store_id;
      })
      $('#company').val(filtered_store[0].company_id);
      $('#store_id').val(filtered_store[0].id);
  }
  async function selectAndSetStore(company_id,lojas){
      $('#store_id option:not(:first), #store_id_api option:not(:first)').remove();
      let filtered_store=await lojas.filter((element)=>{
          return element.company_id==company_id;
      })
      await filtered_store.forEach((element)=>{
          $('#store_id').append('<option value="'+element.id+'">'+element.name+'</option>');
          $('#store_id_api').append('<option value="'+element.id+'">'+element.name+'</option>');
          if(lojaoriginal!=undefined){
              if (lojaoriginal == element.id) {
                  $('#store_id').val(lojaoriginal);
              }
          }
      })
    
      if(filtered_store.length==1){
          $('#store_id').val(filtered_store[0].id);
          console.log( $('#store_id').val());
          // $('#store_id').prop('disabled', 'disabled');
      }else{
      }
      if(store_create!=0 && fistLoad){
          setCreatedStore(store_create,lojas);
      }
      fistLoad=false;
  }

  function checkLegalAdministrator(){
      if($('#legal_administrator').is(":checked")){
          $('#cpfdiv').show()
          $('#cpf').attr('required', true)
      }else{
          $('#cpfdiv').hide()
          $('#cpf').attr('required', false)
      }
  }

  var behavior = function (val) {
      return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
  },
  options = {
      onKeyPress: function (val, e, field, options) {
          field.mask(behavior.apply({}, arguments), options);
      }
  };

  $('#phone').mask(behavior, options);

  $('#btnUserCreate').click(function() {
      var formError = 0;
        
      $('#formCreateUser input, #formCreateUser select').each(function() {
          if ($(this).attr('id') !== 'bank' && $(this).attr('id') !== 'account_type') {
              if ($(this).attr('required') === 'required' && $(this).val() === '' || $(this).val() === null) {
                  $($(this)).css('border-color', '#dd4b39');
                  $('#' + $(this).attr('id') + 'Danger').show();
                  formError++;
              }
          }
      });

      if (formError !== 0) {
          return false;
      }
   });

    // Esse trecho de código impede que o formulário de cadastro de usuário exiba o email e senha do usuário logado
    $(document).ready(function() {
       // setTimeout(function() {
       //     $('#username, #password').val('');
       // }, 1000);
    });
</script>
