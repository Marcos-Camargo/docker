

<!DOCTYPE html>

<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $page_title; ?></title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <!-- Estilos necessários para a barra de progresso das lojas -->  
  <link rel="stylesheet" href="<?= base_url('assets/dist/css/progressBar.css') ?>">
  <!-- Bootstrap 3.3.7 -->  
  <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/bootstrap/dist/css/bootstrap.min.css') ?>">
  <!-- Font Awesome -->  
<!--  <link rel="stylesheet" href="--><?php //echo base_url('assets/bower_components/font-awesome/css/font-awesome.min.css') ?><!--">-->
  <!-- Ionicons -->
  <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/Ionicons/css/ionicons.min.css') ?>">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?php echo base_url('assets/dist/css/AdminLTE.min.css') ?>">
  <!-- AdminLTE Skins. Choose a skin from the css/skins
       folder instead of downloading all of them to reduce the load. -->
  <link rel="stylesheet" href="<?php echo base_url('assets/dist/css/skins/_all-skins.min.css') ?>">
  <!-- Date Picker -->
  <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.css') ?>">
  <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') ?>">
  <!-- Daterange picker -->  
  <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/bootstrap-daterangepicker/daterangepicker.css') ?>">
  <!-- bootstrap wysihtml5 - text editor -->
  <link rel="stylesheet" href="<?php echo base_url('assets/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css') ?>">
  <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css') ?>">
  <!-- Select2 -->
  <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/select2/dist/css/select2.min.css') ?>">
  <link rel="stylesheet" href="<?php echo base_url('assets/plugins/fileinput/css/fileinput.min.css') ?>">

  <link rel="stylesheet" href="<?php echo base_url('assets/fullcalendar/fullcalendar.min.css') ?>" />
  <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css') ?>" />
  <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/bootstrap-select-1.13.14/dist/css/bootstrap-select.css') ?>" />
  <link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.0/css/select.dataTables.min.css">

  <!-- icheck -->
  <!-- iCheck for checkboxes and radio inputs -->
  <link rel="stylesheet" href="<?php echo base_url('assets/plugins/iCheck/all.css') ?>">

  <!-- Styles CSS -->
  <link rel="stylesheet" href="<?=base_url('assets/dist/css/styles.css') ?>">

  <!-- Styles CSS parametro seller_center -->
  <?php if ($this->session->userdata('layout')) {
    $layout = $this->session->userdata('layout');  ?>
    <link rel="stylesheet" href="<?=base_url('assets/dist/css/styles_'.$layout['value'].'.css') ?>">
  <?php }?>

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  
  <!--- Bootstrap toggle --->
  <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/bootstrap-toggle-master/css/bootstrap-toggle.min.css') ?>" />
  

  <!-- jQuery 3 -->
  <script src="<?php echo base_url('assets/bower_components/jquery/dist/jquery.min.js') ?>"></script>
  <!-- jQuery UI 1.11.4 -->
  <script src="<?php echo base_url('assets/bower_components/jquery-ui/jquery-ui.min.js') ?>"></script>
  <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
  <script>
    $.widget.bridge('uibutton', $.ui.button);
  </script>
  <!-- Bootstrap 3.3.7 -->
  <script src="<?php echo base_url('assets/bower_components/bootstrap/dist/js/bootstrap.min.js') ?>"></script>
  <!-- Sparkline -->
  <script src="<?php echo base_url('assets/bower_components/jquery-sparkline/dist/jquery.sparkline.min.js') ?>"></script>
  <!-- jvectormap -->
  <script src="<?php echo base_url('assets/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js') ?>"></script>
  <script src="<?php echo base_url('assets/plugins/jvectormap/jquery-jvectormap-world-mill-en.js') ?>"></script>
  <!-- jQuery Knob Chart -->
  <script src="<?php echo base_url('assets/bower_components/jquery-knob/dist/jquery.knob.min.js') ?>"></script>
  <!-- daterangepicker -->
  <script src="<?php echo base_url('assets/bower_components/moment/min/moment.min.js') ?>"></script>
  <script src="<?php echo base_url('assets/bower_components/bootstrap-daterangepicker/daterangepicker.js') ?>"></script>
  <!-- datepicker -->
  <script src="<?php echo base_url('assets/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.js') ?>"></script>
  <script src="<?php echo base_url('assets/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') ?>"></script>
  <!-- Bootstrap WYSIHTML5 -->
  <script src="<?php echo base_url('assets/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js') ?>"></script>
  <!-- Slimscroll -->
  <script src="<?php echo base_url('assets/bower_components/jquery-slimscroll/jquery.slimscroll.min.js') ?>"></script>
  <!-- FastClick -->
  <script src="<?php echo base_url('assets/bower_components/fastclick/lib/fastclick.js') ?>"></script>
  <!-- Select2 -->
  <script src="<?php echo base_url('assets/bower_components/select2/dist/js/select2.full.min.js') ?>"></script>
  <!-- AdminLTE App -->  
  <script src="<?php echo base_url('assets/dist/js/adminlte.min.js') ?>"></script>
  <!-- AdminLTE for demo purposes -->
  <script src="<?php echo base_url('assets/dist/js/demo.js') ?>"></script>
  <script src="<?php echo base_url('assets/plugins/fileinput/js/fileinput.js') ?>"></script>
  <script src="<?php echo base_url('assets/plugins/fileinput/js/locales/pt-BR.js') ?>"></script>

  <!-- Plugin FileInput (necessário para mover as imagens) -->
  <!-- <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script> -->
  <script src="<?php echo base_url('assets/plugins/fileinput/js/plugins/piexif.js') ?>"></script>
  <script src="<?php echo base_url('assets/plugins/fileinput/js/plugins/sortable.js') ?>"></script>
  <script src="<?php echo base_url('assets/plugins/fileinput/js/locales/fr.js') ?>"></script>
  <script src="<?php echo base_url('assets/plugins/fileinput/js/locales/es.js') ?>"></script>

  <!-- icheck -->
  <script src="<?php echo base_url('assets/plugins/iCheck/icheck.min.js') ?>"></script>

  <!-- DataTables -->
    <script src="<?php echo base_url('assets/bower_components/datatables.net/js/jquery.dataTables.min.js') ?>"></script>
    <script src="<?php echo base_url('assets/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js') ?>"></script>
    <script src="https://cdn.datatables.net/responsive/1.0.7/js/dataTables.responsive.min.js"></script>
        <!-- SW FullCalendar -->
    <script src="<?php echo base_url('assets/fullcalendar/fullcalendar.min.js') ?>"></script>
    <script src="<?php echo base_url('assets/fullcalendar/gcal.js') ?>"></script>

    <script src="<?php echo base_url('assets/bower_components/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js') ?>"></script>
    <!-- SW -->
    <script src="<?php echo base_url('assets/plugins/SW/format_br.js'); ?>"></script>

    <!-- select bootstrap -->
    <script src="<?php echo base_url('assets/bower_components/bootstrap-select-1.13.14/dist/js/bootstrap-select.min.js') ?>"></script>
    <script src="<?php echo base_url('assets/bower_components/bootstrap-select-1.13.14/dist/js/i18n/defaults-pt_BR.min.js') ?>"></script>
	
	  <!--- Bootstrap toggle --->
    <script src="<?php echo base_url('assets/bower_components/bootstrap-toggle-master/js/bootstrap-toggle.min.js') ?>"></script>

    <!--- Bootstrap toggle --->
    <script src="<?php echo base_url('assets/plugins/Mask/jquery.mask.js') ?>"></script>
	
    <style>
    .loader {
      border: 16px solid #f3f3f3;
      border-radius: 50%;
      border-top: 16px solid #3498db;
      width: 80px;
      height: 80px;
      -webkit-animation: spin 2s linear infinite; /* Safari */
      animation: spin 2s linear infinite;
    } 

    /* Safari */
    @-webkit-keyframes spin {
      0% { -webkit-transform: rotate(0deg); }
      100% { -webkit-transform: rotate(360deg); }
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    </style>
    <script src="https://kit.fontawesome.com/ddad1b6b2a.js" crossorigin="anonymous"></script>

    <!-- include summernote css/js -->
    <link href="<?=base_url('assets/bower_components/summernote/summernote.min.css') ?>" rel="stylesheet">
    <script src="<?=base_url('assets/bower_components/summernote/summernote.min.js') ?>"></script>
    <script src="<?php echo base_url('assets/bower_components/summernote/lang/summernote-pt-BR.js') ?>"></script>
    <!-- Animate CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

	<link rel="icon" href="<?=base_url('assets/skins/'.$shopkeeperform['sellerCenter'].'/favicon.ico')?>" type="image/gif">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.15/jquery.mask.min.js"></script>
    <!--
    <script src="<?php echo base_url('assets/plugins/Mask/jquery.mask.js') ?>"></script>
     -->
     <script src="<?php echo base_url('assets/plugins/input-mask/valida.js') ?>"></script>
</head>

<?php include_once(APPPATH . '/third_party/zipcode.php'); ?>
<?php $bank_is_optional = 0; ?>

</body>
</html>

<!--
SW Serviços de Informática 2019

Listar Settings
Add , Edit & Delete

-->


<!-- Content Wrapper. Contains page content -->


  <!-- Main content --
  <section class="content">
    <!-- Small boxes (Stat box) -->
    
      <!-- Sidebar toggle button-->
      
    <body class="skin-conectala" style="height: auto; min-height: 100%;" >
        <div class="container">
            <br>
            <div class="py-5 text-center">
                
                    <img class="d-block mx-auto mb-4" style="max-width:272px; <?=$shopkeeperform['file_logotipo'] != ""? '' : 'display:none'?>" src="<?=base_url($shopkeeperform['file_logotipo'])?>" >
                <?if(isset($shopkeeperform['header_description']) && $shopkeeperform['header_description'] != ""){?>
                    <p><h2><?php echo $shopkeeperform['header_description'] ?></h2></p>
                <?}?>
            </div>
            <div class="row">
                <div class="col-md-12 col-xs-12">
                    <div class="box box-primary">
                        <form action="<?php base_url('ShopkeeperForm/createFormValue') ?>" method="post" enctype="multipart/form-data" id="form-shopkeeper" >
                            <div class="box-body" style="background-color:lightgray">
                                <div class="panel panel-primary">
                                
                                    <div class="panel-heading">&nbsp
                                        <span class="h6"> Formulario </span>
                                    </div>
                                    <div class="panel-body">
                                        <div class="row"> 
                                            <div class="form-group col-md-6 <?php echo (form_error('raz_soc')) ? "has-error" : "";?>">
                                                <label for="raz_soc"><?=$this->lang->line('application_raz_soc');?>(*)</label>
                                                <input type="text" class="form-control" required id="raz_soc" name="raz_soc" value="<?php echo set_value('raz_soc', $shopkeeperform["raz_social"]);?>" placeholder="<?=$this->lang->line('application_enter_razao_social');?>" autocomplete="off">
                                                <?php echo '<i style="color:red">'.form_error('raz_soc').'</i>';  ?>
                                            </div>
                                            <div class="form-group col-md-3 <?php echo (form_error('CNPJ')) ? "has-error" : "";?>">
                                                <label for="CNPJ"><?=$this->lang->line('application_cnpj');?>(*)</label>              
<input type="text" class="form-control" name="CNPJ" required id="CNPJ" onkeyup="FormataCnpj(this,event)"  placeholder="<?=$this->lang->line('application_enter_CNPJ');?>" autocomplete="off"
onblur="
if(!validarCNPJ(this.value)){
    $('.msg_erro_cnpj').show();
    $('.msg_ok_cnpj').hide();
}else{
    $('.msg_ok_cnpj').show();
}" maxlength="18"  class="form-control input-md" ng-model="cadastro.cnpj" onKeyPress="return digitos(event, this);" value="<?php echo set_value('CNPJ', $shopkeeperform["CNPJ"]); ?>"  required>
<!--
<input type="text" class="form-control" maxlength="18" minlenght="18" required id="CNPJ" name="CNPJ" value="<?php echo set_value('CNPJ',  $shopkeeperform["CNPJ"]);?>" placeholder="<?=$this->lang->line('application_enter_CNPJ');?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CNPJ',this,event);" value="<?=set_value('cnpj')?>">
-->
<div class="msg_erro_cnpj" style="display: none; color: red"><i> CNPJ Inválido</i></div>
<div class="msg_ok_cnpj" style="display: none; color: rgb(28, 168, 35)"><i> CNPJ Confirmado</i></div>
<?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
<div class="msg_erro_company_cpf" style="display: none; color: red"><i> CPF Inválido</i></div>
<div class="msg_ok_company_cpf" style="display: none; color: rgb(28, 168, 35)"><i> CPF Confirmado</i></div>
<?php endif; ?>
<?php echo '<i style="color:red">'.form_error('CNPJ').'</i>';  ?>
                                            </div> 
                                            <div class="form-group col-md-3">
                                                <label for="insc_estadual"><?=$this->lang->line('application_iest');?>(*)</label>
<input type="text" class="form-control" id="insc_estadual" required name="insc_estadual" placeholder="<?=$this->lang->line('application_iest')?>" autocomplete="off" value="<?=set_value('insc_estadual')?>">
<?php echo '<i style="color:red">'.form_error('insc_estadual').'</i>';  ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="1" name="exempted" <?php echo set_checkbox('exempted', '1',false); ?> onchange="exemptIE()" id="exempted">
                                                    <label class="form-check-label" for="exempted">
                                                    <?= $this->lang->line('application_exempted'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <div class="row">
                                        <div class="form-group col-md-5" <?php echo (form_error('name')) ? "has-error" : "";?>">
                                            <label for="name"><?=$this->lang->line('application_fantasy_name');?> (*)</label>
                                            <input type="text" class="form-control" required id="name" name="name" value="<?php echo set_value('name', $shopkeeperform["name"]);?>" placeholder="<?=$this->lang->line('application_enter_store_name');?>" autocomplete="off">
                                            <?php echo '<i style="color:red">'.form_error('name').'</i>';  ?>
                                        </div>
                                        <div class="form-group col-md-2 <?php echo (form_error('phone_1')) ? "has-error" : "";?>">
                                            <label for="phone"><?=$this->lang->line('application_phone');?> 1 (*)</label>
                                            <input type="text" class="form-control" required id="phone_1" name="phone_1" value="<?php echo set_value('phone_1', $shopkeeperform["phone_1"]);?>" placeholder="<?=$this->lang->line('application_enter_phone');?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" maxlength="15">
                                            <?php echo '<i style="color:red">'.form_error('phone_1').'</i>';  ?>
                                        </div>
                                        <div class="form-group col-md-2 <?php echo (form_error('phone_2')) ? "has-error" : "";?>">
                                            <label for="phone"><?=$this->lang->line('application_phone');?> 2 (*)</label>
                                            <input type="text" class="form-control" id="phone_2" name="phone_2" value="<?php echo set_value('phone_2', $shopkeeperform["phone_2"] );?>" placeholder="<?=$this->lang->line('application_enter_phone');?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" maxlength="15">
                                            <?php echo '<i style="color:red">'.form_error('phone_2').'</i>';  ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <hr>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4 <?php echo (form_error('responsible_name')) ? "has-error" : "";?>">
                                            <label for="responsible_name"><?=$this->lang->line('application_responsible_name');?> (*)</label>
                                            <input type="text" class="form-control" id="responsible_name" name="responsible_name" placeholder="<?=$this->lang->line('application_enter_responsible_name')?>" autocomplete="off" value="<?php echo set_value('responsible_name', $shopkeeperform["responsible_name"]);?>" required>
                                            <?php echo '<i style="color:red">'.form_error('responsible_name').'</i>';  ?>
                                        </div>
                                        <div class="form-group col-md-4 <?php echo (form_error('responsible_email')) ? "has-error" : ""; ?>">
                                            <label for="responsible_email"><?=$this->lang->line('application_responsible_email');?> (*)</label>
                                            <input type="email" class="form-control" id="responsible_email" name="responsible_email" required placeholder="<?=$this->lang->line('application_enter_responsible_email')?>" autocomplete="off" value="<?=set_value('responsible_email', $shopkeeperform["responsible_email"])?>">
                                            <?php  echo '<i style="color:red">'.form_error('responsible_email').'</i>'; ?>
                                        </div>
                                        <div class="form-group col-md-4 <?php echo (form_error('responsible_cpf')) ? "has-error" : "";?>">
                                            <label for="responsible_cpf"><?=$this->lang->line('application_responsible_cpf');?> (*)</label>
<input type="text" class="form-control" id="responsible_cpf" name="responsible_cpf" placeholder="<?=$this->lang->line('application_enter_responsible_cpf')?>" autocomplete="off" maxlength="15" size="15"
onblur="
if(!validarCPF(this.value)){
    $('.msg_erro_cpf').show();
    $('.msg_ok_cpf').hide();
   
}else{
    $('.msg_ok_cpf').show();
    $('.msg_erro_cpf').hide();
    $('.msg_erro_zera').hide();
}"
onkeypress="$(this).mask('000.000.000-00');" value="<?=set_value('responsible_cpf', $shopkeeperform["responsible_cpf"])?>">
<div class="msg_erro_cpf" style="display: none; color: red"><i> Verifique o CPF.</i></div>
<div class="msg_ok_cpf" style="display: none; color: rgb(28, 168, 35)"><i> CPF Confirmado</i></div>                      
<?php echo '<i class="msg_erro_zera" style="color:red">'.form_error('responsible_cpf').'</i>'; ?>
                                        </div>

                                        <div class="form-group col-md-4 <?php echo (form_error('responsible_mother_name')) ? "has-error" : "";?>">
                                            <label for="responsible_mother_name"><?=$this->lang->line('application_responsible_mother_name');?> </label>
                                            <input type="text" class="form-control" id="responsible_mother_name" name="responsible_mother_name" placeholder="<?=$this->lang->line('application_responsible_mother_name')?>" autocomplete="off" value="<?php echo set_value('responsible_mother_name', $shopkeeperform["responsible_mother_name"]);?>" >
                                            <?php echo '<i style="color:red">'.form_error('responsible_mother_name').'</i>';  ?>
                                        </div>
                                        <div class="form-group col-md-4 <?php echo (form_error('responsible_position')) ? "has-error" : "";?>">
                                            <label for="responsible_position"><?=$this->lang->line('application_responsible_position');?> </label>
                                            <input type="text" class="form-control" id="responsible_position" name="responsible_position" placeholder="<?=$this->lang->line('application_responsible_position')?>" autocomplete="off" value="<?php echo set_value('responsible_position', $shopkeeperform["responsible_position"]);?>" >
                                            <?php echo '<i style="color:red">'.form_error('responsible_position').'</i>';  ?>
                                        </div>


                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <label for="bank"><?=$this->lang->line('application_bank');?>(*)</label>
                                            <select class="form-control" id="bank" name="bank" <?php echo ($bank_is_optional == 1) ? '' : 'required' ?>>
                                                <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                                <?php foreach ($banks as $k => $v): ?>
                                                    <option value="<?=trim($v['name'])?>" <?=set_select('bank', $v['name'])?>><?=$v['name']?></option>
                                                <?php endforeach?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3 <?php echo (form_error('agency')) ? "has-error" : "";?>">
                                            <label for="agency"><?=$this->lang->line('application_agency');?> (*)</label>
                                            <input type="text" class="form-control" id="agency" name="agency" required placeholder="<?=$this->lang->line('application_enter_agency')?>" autocomplete="off" value="<?=set_value('agency', $shopkeeperform["agency"])?>">
                                            <?php echo '<i style="color:red">'.form_error('agency').'</i>'; ?>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="currency"><?=$this->lang->line('application_type_account');?> (*)</label>
                                            <select class="form-control" id="account_type" name="account_type" required >
                                                <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                                <?php foreach ($type_accounts as $k => $v): ?>
                                                    <option value="<?=trim($v)?>" <?=set_select('account_type', trim($v), $shopkeeperform['account_type'] == trim($v))?>><?=$v ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3 <?php echo (form_error('account')) ? "has-error" : "";?>">
                                            <label for="account"><?=$this->lang->line('application_account');?> (*)</label>
                                            <input type="text" class="form-control" id="account" name="account" required placeholder="<?=$this->lang->line('application_enter_bank_account')?>" autocomplete="off" value="<?=set_value('account', $shopkeeperform["account"])?>">
                                            <?php echo '<i style="color:red">'.form_error('account').'</i>'; ?>
                                        </div>
                                    </div>                        
                                        <fieldset>
                                            <legend><h4><?=$this->lang->line('application_collection_address');?></h4></legend>
                                            <div class="row">
                                                <div class="form-group col-md-2 <?php echo (form_error('zipcode')) ? "has-error" : "";?>">
                                                    <label for="zipcode"><?=$this->lang->line('application_zip_code');?> (*)</label>
                                                    <input type="text" class="form-control" required id="zipcode" name="zipcode" value="<?php echo set_value('zipcode', $shopkeeperform["zipcode"]);?>" placeholder="<?=$this->lang->line('application_enter_zipcode');?>" autocomplete="off" onkeyup="this.value=this.value.replace(/[^\d]/,'')">
                                                    <?php echo '<i style="color:red">'.form_error('zipcode').'</i>';  ?>
                                                </div>
                                                <div class="form-group col-md-8 <?php echo (form_error('address')) ? "has-error" : "";?>">
                                                    <label for="address"><?=$this->lang->line('application_address');?> (*)</label>
                                                    <input type="text" class="form-control" required id="address" name="address" value="<?php echo set_value('address', $shopkeeperform["address"]);?>" placeholder="<?=$this->lang->line('application_enter_address');?>" autocomplete="off">
                                                    <?php echo '<i style="color:red">'.form_error('address').'</i>';  ?>
                                                </div>
                                                <div class="form-group col-md-2 <?php echo (form_error('addr_num')) ? "has-error" : "";?>">
                                                    <label for="addr_num"><?=$this->lang->line('application_number');?> (*)</label>
                                                    <input type="text" class="form-control" required id="addr_num" name="addr_num" value="<?php echo set_value('addr_num', $shopkeeperform["addr_num"]);?>" placeholder="<?=$this->lang->line('application_enter_number');?>" autocomplete="off">
                                                    <?php echo '<i style="color:red">'.form_error('addr_num').'</i>';  ?>
                                                </div>
                                            </div> 
                                            <div class="row">
                                                <div class="form-group col-md-2 <?php echo (form_error('addr_compl')) ? "has-error" : "";?>">
                                                    <label for="addr_compl"><?=$this->lang->line('application_complement');?></label>
                                                    <input type="text" class="form-control" id="addr_compl" name="addr_compl" value="<?php echo set_value('addr_compl', $shopkeeperform["addr_compl"] );?>" placeholder="<?=$this->lang->line('application_enter_complement');?>" autocomplete="off">
                                                    <?php echo '<i style="color:red">'.form_error('addr_compl').'</i>';  ?>
                                                </div>
                                                <div class="form-group col-md-3 <?php echo (form_error('addr_neigh')) ? "has-error" : "";?>">
                                                    <label for="addr_neigh"><?=$this->lang->line('application_neighb');?> (*)</label>
                                                    <input type="text" class="form-control" required id="addr_neigh" name="addr_neigh" value="<?php echo set_value('addr_neigh', $shopkeeperform["addr_neigh"] );?>" placeholder="<?=$this->lang->line('application_enter_neighborhood');?>" autocomplete="off">
                                                    <?php echo '<i style="color:red">'.form_error('addr_neigh').'</i>';  ?>
                                                </div>
                                                <div class="form-group col-md-3 <?php echo (form_error('addr_city')) ? "has-error " : "";?>">
                                                    <label for="addr_city"><?=$this->lang->line('application_city');?> (*)</label>
                                                    <input type="text" class="form-control" required id="addr_city" name="addr_city" value="<?php echo set_value('addr_city', $shopkeeperform["addr_city"]);?>" placeholder="<?=$this->lang->line('application_enter_city');?>" autocomplete="off">
                                                    <?php echo '<i style="color:red">'.form_error('addr_city').'</i>';  ?>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="addr_uf"><?=$this->lang->line('application_uf');?> (*)</label>
                                                    <select class="form-control" id="addr_uf" name="addr_uf">
                                                        <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                                        <?php foreach ($ufs as $k => $v): ?>
                                                            <option value="<?php echo trim($k) ?>" <?php echo set_select('addr_uf', trim($k),trim($k) == $shopkeeperform['addr_uf']) ?> ><?php echo $v ?></option>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="country"><?=$this->lang->line('application_country');?> (*)</label>
                                                    <select class="form-control" id="country" name="country">
                                                        <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                                        <?php foreach ($paises as $k => $v): ?>
                                                            <option value="<?php echo trim($k); ?>" <?php echo set_select('country', trim($k),$k == $shopkeeperform['country'])?>><?php echo $v ?></option>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </fieldset>
                                        <!-- endereço comercial -->
                                        <fieldset>
                                            <legend><h4><?=$this->lang->line('application_business_address');?></h4></legend>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <input class="form-check-input" type="checkbox" value="1" name="same" <?php echo set_checkbox('same', '1',false); ?> onchange="sameAddress()" id="same">
                                                    <label for="same"><?=$this->lang->line('application_identical_to_collection_address');?></label>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-2 <?php echo (form_error('business_code')) ? "has-error" : "";?>">
                                                    <label for="business_code"><?=$this->lang->line('application_zip_code');?></label>
                                                    <input type="text" class="form-control" required id="business_code" name="business_code" value="<?php echo set_value('business_code', $shopkeeperform["business_code"]);?>" placeholder="<?=$this->lang->line('application_enter_zipcode');?>" autocomplete="off" onkeyup="this.value=this.value.replace(/[^\d]/,'')" onblur="consultZip(this.value)">
                                                    <?php echo '<i style="color:red">'.form_error('business_code').'</i>';  ?>
                                                </div>
                                                <div class="form-group col-md-8 <?php echo (form_error('business_street')) ? "has-error" : "";?>">
                                                    <label for="business_street"><?=$this->lang->line('application_address');?></label>
                                                    <input type="text" class="form-control" required id="business_street" name="business_street" value="<?php echo set_value('business_street', $shopkeeperform["business_street"]);?>" placeholder="<?=$this->lang->line('application_enter_address');?>" autocomplete="off">
                                                    <?php echo '<i style="color:red">'.form_error('business_street').'</i>';  ?>
                                                </div>
                                                <div class="form-group col-md-2 <?php echo (form_error('business_addr_num')) ? "has-error" : "";?>">
                                                    <label for="business_addr_num"><?=$this->lang->line('application_number');?></label>
                                                    <input type="text" class="form-control" required id="business_addr_num" name="business_addr_num" value="<?php echo set_value('business_addr_num', $shopkeeperform["business_addr_num"]);?>" placeholder="<?=$this->lang->line('application_enter_number');?>" autocomplete="off">
                                                    <?php echo '<i style="color:red">'.form_error('business_addr_num').'</i>';  ?>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-2 <?php echo (form_error('business_addr_compl')) ? "has-error" : "";?>">
                                                    <label for="business_addr_compl"><?=$this->lang->line('application_complement');?></label>
                                                    <input type="text" class="form-control" id="business_addr_compl" name="business_addr_compl" value="<?php echo set_value('business_addr_compl', $shopkeeperform["business_addr_compl"] );?>" placeholder="<?=$this->lang->line('application_enter_complement');?>" autocomplete="off">
                                                    <?php echo '<i style="color:red">'.form_error('business_addr_compl').'</i>';  ?>
                                                </div>
                                                <div class="form-group col-md-3 <?php echo (form_error('business_neighborhood')) ? "has-error" : "";?>">
                                                    <label for="business_neighborhood"><?=$this->lang->line('application_neighb');?></label>
                                                    <input type="text" class="form-control" required id="business_neighborhood" name="business_neighborhood" value="<?php echo set_value('business_neighborhood', $shopkeeperform["business_neighborhood"] );?>" placeholder="<?=$this->lang->line('application_enter_neighborhood');?>" autocomplete="off">
                                                    <?php echo '<i style="color:red">'.form_error('business_neighborhood').'</i>';  ?>
                                                </div>
                                                <div class="form-group col-md-3 <?php echo (form_error('business_town')) ? "has-error" : "";?>">
                                                    <label for="business_town"><?=$this->lang->line('application_city');?></label>
                                                    <input type="text" class="form-control" required id="business_town" name="business_town" value="<?php echo set_value('business_town', $shopkeeperform["business_town"]);?>" placeholder="<?=$this->lang->line('application_enter_city');?>" autocomplete="off">
                                                    <?php echo '<i style="color:red">'.form_error('business_town').'</i>';  ?>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="business_uf"><?=$this->lang->line('application_uf');?></label>
                                                    <select class="form-control" id="business_uf" name="business_uf">
                                                        <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                                        <?php foreach ($ufs as $k => $v): ?>
                                                            <option value="<?php echo trim($k) ?>" <?php echo set_select('business_uf', trim($k),trim($k) == $shopkeeperform['business_uf']) ?> ><?php echo $v ?></option>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="business_nation"><?=$this->lang->line('application_country');?></label>
                                                    <select class="form-control" id="business_nation" name="business_nation">
                                                        <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                                        <?php foreach ($paises as $k => $v): ?>
                                                            <option value="<?php echo trim($k); ?>" <?php echo set_select('business_nation', trim($k),$k == $shopkeeperform['business_nation'])?>><?php echo $v ?></option>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </fieldset>
                                        <div class="row">
                                            <hr>
                                        </div>
                                    
                                    <fieldset>
                                    <!-- até aqui -->
                                    <?php
                                        // $filters = $this->data['filters'];
                                        $fields2 = get_instance()->data['fields-2'] ?? [];
                                        $fields1 = get_instance()->data['fields-1'] ?? [];
                                        if((!empty($fields1)) || (!empty($fields2))){ ?>
                                            <legend><h4><?=$this->lang->line('application_add_information');?></h4></legend>
                                            
                                        <?php } foreach ($fields2 as $k => $v) {  ?>  
                                            
                                                <div class="form-group col-md-8">
                                                <label for="form-2_<?=$v['id']?>"><?= $v['label'] ?><?= $v['required'] == 1 ? "(*)" : "" ?></label>
                                                    <select class="form-control" id="<?=$v['id']?>"  <?= $v['required'] == 1 ? "required" : "" ?> name="form-2_<?=$v['id']?>">
                                                        <option value="1"><?=$this->lang->line('application_yes');?></option>
                                                        <option value="2"><?=$this->lang->line('application_no');?></option>
                                                    </select>
                                                </div>
                                            <?php }

                                        foreach ($fields1 as $k => $v) { ?>  
                                            <div class="form-group col-md-8">
                                                <label for="form-1_<?=$v['id']?>"><?= $v['label'] ?><?= $v['required'] == 1 ? "(*)" : "" ?></label>
                                                    <input type="text" class="form-control" name="form-1_<?=$v['id']?>" id="<?=$k?>" <?= $v['required'] == 1 ? "required" : "" ?>  />
                                            </div>
                                        <?php } ?>  
                                    
                            </fieldset>
                            <div class="row">
                                <hr>
                            </div>
                            <fieldset>

                            <?php
                                $filters = get_instance()->data['attachments'];
                                if($filters != null) {?>
                                    
                                    <legend><h4><?=$this->lang->line('application_attachment');?></h4></legend> 
                                    
                                <?php }?>
                                <div class="row">
                        <!-- até aqui -->
                                    <?php
                                        foreach ($filters as $k => $v) { ?>
                                        <div class="form-group col-md-8 ">
                                            <div class="custom-file">
                                                <?php if($v['type'] == 3 ) { ?>
                                                    <input type="file" class="custom-file-input" id="form-3_<?=$k?>" data-idfile="form-3_<?=$k?>" onchange="validateuploadFile(this)" <?= $v['required'] == 1 ? "required" : "" ?> name="form-3_<?=$k?>">

                                                    <label class="custom-file-label" for="form-3_<?=$k?>" id="label-form-3_<?=$k?>"><?= $v['label'] ?><?= $v['required'] == 1 ? "(*)" : "" ?> -  <?=$this->lang->line('application_fileType');?></label> 
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    </div>
                                </div>
                            </fieldset>
                                    <div class="box-footer">

                                        <?php if($shopkeeperform['status'] == '4'){?>
                                            <button type="submit" class="btn btn-primary" id='btn_save' onsubmit="save()" > <?=$this->lang->line('application_finish');?></button>
                                        <?php }?>
                                    </div>
                            </div>
                            <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
                            <input type="hidden" name="pj_pf" id="pj_pf" value="<?=$shopkeeperform['pj_pf']?>">
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
  </section>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="aprovedFormModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_setting');?><span id="aprovedFormName"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('ShopkeeperForm/aproved') ?>" method="post" id="aprovedForm">
        <div class="modal-body">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
        </div>
      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="reprovedFormModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_setting');?><span id="reprovedFormName"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('ShopkeeperForm/reproved') ?>" method="post" id="reprovedForm">
        <div class="modal-body">
            <div class="form-group col-md-6">
            <label for="reason"><?=$this->lang->line('application_reproved_reason');?></label>
                <select class="form-control" id="reason" name="reason" required >
                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                    <?php foreach ($reproved_reasons as $k => $v): ?>
                        <option value="<?=trim($v['id'])?>" <?= set_select('reason', $v['value']) ?>><?=$v['value']?></option>
                    <?php endforeach ?>
                </select>
                <input type="hidden" name="id" value="<?=$shopkeeperform['id']?>">
            </div>
        </div>
        <br>
        <br>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
        </div>
      </form>

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script type="text/javascript">

    var banks = <?php echo json_encode($banks); ?>;
    var usar_mascara_banco = "<?php echo $usar_mascara_banco ?>";
    var inputfile = '';

    $( document ).ready(function() {
        sameAddressReady();
        exemptIEReady();

        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
        const pj_pf = $('#pj_pf').val();
        const raz_soc = $('#raz_soc').closest('.form-group');
        const CNPJ = $('#CNPJ').closest('.form-group');
        const insc_estadual = $('#insc_estadual').closest('.form-group');
        const exempted = $('#exempted');
        const name = $('#name').closest('.form-group');

        raz_soc.find('label').text(pj_pf === 'pf' ? '<?=$this->lang->line('application_name');?>(*)' : '<?=$this->lang->line('application_raz_soc');?>(*)');
        raz_soc.find('input')
            .prop('placeholder', pj_pf === 'pf' ? "<?=$this->lang->line('application_enter_name');?>" : "<?=$this->lang->line('application_enter_razao_social');?>")
            .val(pj_pf === 'pf' ? name.find('input').val() : '');

        CNPJ.find('label').text(pj_pf === 'pf' ? '<?=$this->lang->line('application_cpf');?>(*)' : '<?=$this->lang->line('application_cnpj');?>(*)');
        CNPJ.find('input')
            .prop('placeholder', pj_pf === 'pf' ? "<?=$this->lang->line('application_enter_CPF');?>" : "<?=$this->lang->line('application_enter_CNPJ');?>")
            .prop('maxlength', pj_pf === 'pf' ? 14 : 18)
            .attr('onblur', pj_pf === 'pf' ? `if(!validarCPF(this.value)){$('.msg_erro_company_cpf').show();$('.msg_ok_company_cpf').hide();}else{$('.msg_ok_company_cpf').show();}` : `if(!validarCNPJ(this.value)){$('.msg_erro_cnpj').show();$('.msg_ok_cnpj').hide();}else{$('.msg_ok_cnpj').show();}`)
            .attr('onkeyup', pj_pf === 'pf' ? "FormataCpf(this,event)" : "FormataCnpj(this,event)");

        insc_estadual.find('label').text(pj_pf === 'pf' ? '<?=$this->lang->line('application_rg');?>' : '<?=$this->lang->line('application_iest');?>');
        insc_estadual.find('input').prop('placeholder', pj_pf === 'pf' ? "<?=$this->lang->line('application_rg');?>" : "<?=$this->lang->line('application_iest');?>").prop('required', pj_pf === 'pj');

        exempted.closest('.form-check').css('display', pj_pf === 'pf' ? 'none' : 'block');

        name.css('display', pj_pf === 'pf' ? 'none' : 'block');
        <?php endif; ?>
    });

    $("#bank").change(function () {
        $('#agency').val('');
        $('#account').val('');
        var bank_name = $('#bank option:selected').val();
        if(usar_mascara_banco == true){
            applyBankMask(bank_name);
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

    function exemptIE() {
        const ie = $('#insc_estadual')[0].hasAttribute('disabled')
        if (!ie) {
            $('#insc_estadual').attr({'disabled': true, 'required': false})
        } else {
            $('#insc_estadual').attr({'disabled': false, 'required': true})
        }
    }


  function mascaraMutuario(o,f){
        v_obj=o
        v_fun=f
        setTimeout('execmascara()',1)
    }

    function execmascara(){
        v_obj.value=v_fun(v_obj.value)
    }

    function cnpj(v){

        //Remove tudo o que não é dígito
        v=v.replace(/\D/g,"")
        //Coloca ponto entre o segundo e o terceiro dígitos
        v=v.replace(/^(\d{2})(\d)/,"$1.$2")
        //Coloca ponto entre o quinto e o sexto dígitos
        v=v.replace(/^(\d{2})\.(\d{3})(\d)/,"$1.$2.$3")
        //Coloca uma barra entre o oitavo e o nono dígitos
        v=v.replace(/\.(\d{3})(\d)/,".$1/$2")
        //Coloca um hífen depois do bloco de quatro dígitos
        v=v.replace(/(\d{4})(\d)/,"$1-$2")

        return v
    }

    <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
    function cpf(v){

        //Remove tudo o que não é dígito
        v=v.replace(/\D/g,"")
        //Coloca ponto entre o segundo e o terceiro dígitos
        v=v.replace(/^(\d{3})(\d)/,"$1.$2")
        //Coloca ponto entre o quinto e o sexto dígitos
        v=v.replace(/^(\d{3})\.(\d{3})(\d)/,"$1.$2.$3")
        //Coloca um hífen depois do bloco de dois dígitos
        v=v.replace(/\.(\d{2})(\d)/,".$1-$2")

        return v
    }
    <?php endif; ?>

    function exemptIEReady() {
        var iesame = $('#exempted')[0].hasAttribute('checked');
        console.log(iesame);
        if (iesame) {
            $('#insc_estadual').attr({'disabled': true, 'required': false})
        }
    }

    function sameAddress() {
        const sameAddress = $('#same')[0].hasAttribute('checked')

        const fields = [
            {original: 'zipcode', copy: 'business_code'},
            {original: 'address', copy: 'business_street'},
            {original: 'addr_num', copy: 'business_addr_num'},
            {original: 'addr_compl', copy: 'business_addr_compl'},
            {original: 'addr_neigh', copy: 'business_neighborhood'},
            {original: 'addr_city', copy: 'business_town'},
            {original: 'addr_uf', copy: 'business_uf'},
            {original: 'country', copy: 'business_nation'},
        ]

        if (!sameAddress) {
            fields.forEach((item) => {
                $('#'+item.copy)[0].value = $('#'+item.original).val()
                $('#'+item.copy).attr('disabled', 'disabled')
            })
            $('#same').attr('checked', 'checked')
        } else {
            fields.forEach((item) => {
                $('#'+item.copy)[0].value = ''
                $('#'+item.copy).removeAttr('disabled')
            })
            $('#same').removeAttr('checked')
        }
    }

    function sameAddressReady() {

        const sameAddress = $('#same')[0].hasAttribute('checked')

        const fields = [
            {original: 'zipcode', copy: 'business_code'},
            {original: 'address', copy: 'business_street'},
            {original: 'addr_num', copy: 'business_addr_num'},
            {original: 'addr_compl', copy: 'business_addr_compl'},
            {original: 'addr_neigh', copy: 'business_neighborhood'},
            {original: 'addr_city', copy: 'business_town'},
            {original: 'addr_uf', copy: 'business_uf'},
            {original: 'country', copy: 'business_nation'},
        ]

        if (sameAddress) {
            fields.forEach((item) => {
                $('#'+item.copy)[0].value = $('#'+item.original).val()
                $('#'+item.copy).attr('disabled', 'disabled')
            })
        }
    }

    function mascaraMutuario(o,f){
        v_obj=o
        v_fun=f
        setTimeout('execmascara()',1)
    }

    function execmascara(){
        v_obj.value=v_fun(v_obj.value)
    }

    function cnpj(v){

        //Remove tudo o que não é dígito
        v=v.replace(/\D/g,"")
        //Coloca ponto entre o segundo e o terceiro dígitos
        v=v.replace(/^(\d{2})(\d)/,"$1.$2")
        //Coloca ponto entre o quinto e o sexto dígitos
        v=v.replace(/^(\d{2})\.(\d{3})(\d)/,"$1.$2.$3")
        //Coloca uma barra entre o oitavo e o nono dígitos
        v=v.replace(/\.(\d{3})(\d)/,".$1/$2")
        //Coloca um hífen depois do bloco de quatro dígitos
        v=v.replace(/(\d{4})(\d)/,"$1-$2")

        return v
    }

    function consultZip(id) {
        $.ajax({
            url: 'https://viacep.com.br/ws/'+id+'/json/',
            type: 'get',
            dataType: 'json',
            success: function(response) {
                $('#business_street')[0].value = response.logradouro
                $('#business_neighborhood')[0].value = response.bairro
                $('#business_town')[0].value = response.localidade
                $('#business_uf')[0].value = response.uf
                $('#business_nation')[0].value = 'BR'
                return
            }
        });
    }
    function validateuploadFile(element) {
        var idfile = $(element).data("idfile");
        inputfile = "#label-" + idfile;
    }

    $("input[type='file']").on("change", function () {
        /* Tamanho máximo 2MB */
        if (this.files[0].size > 2000000) {
            clearMesasge();
            $("<b style='color:red'> O arquivo não pode ser maior que 2MB</b>").appendTo(inputfile);
            $(this).val('');
        } else {
            // Verifica a extensão do arquivo
            var allowedExtensions = /(\.gif|\.jpg|\.png|\.pdf)$/i;
            if (!allowedExtensions.exec(this.files[0].name)) {
                clearMesasge();
                $("<b style='color:red'> O arquivo deve ter extensão gif, jpg, png ou pdf</b>").appendTo(inputfile);
                $(this).val('');
            } else {
                clearMesasge();
            }
        }
    });

    function clearMesasge() {
        var msg = $(inputfile).text().replace(/O arquivo (não pode ser maior que 2MB|deve ter extensão gif, jpg, png ou pdf)/g, '');
        $(inputfile).text(msg);
    }

  
</script>
