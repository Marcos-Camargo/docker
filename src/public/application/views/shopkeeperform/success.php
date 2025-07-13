

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

</head>


 
<?php include_once(APPPATH . '/third_party/zipcode.php'); ?>

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
                <?if(isset($shopkeeperform['success_description']) && $shopkeeperform['success_description'] != ""){?>
                    <p><h2><?php echo $shopkeeperform['success_description'] ?></h2></p>
                <?}?>
            </div>
    </body>


<footer class="main-footer ml-0">
    <div class="pull-right hidden-xs">
      <b>Version</b> 1.4.0
    </div>
	<img src="<?php echo base_url() . $this->session->userdata('logo'); ?>"  width="100">
    <strong>Copyright &copy; 2018-<?php echo date('Y') ?>. </strong> All rights reserved. Powered by Full Nine Digital Consultoria LTDA.
  </footer>

  <!-- Add the sidebar's background. This div must be placed
       immediately after the control sidebar -->
  <div class="control-sidebar-bg"></div>
</div>


</body>
</html>

