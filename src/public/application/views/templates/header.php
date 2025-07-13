<!DOCTYPE html>
<?php

    if(empty($_SESSION['language_code']))
        $_SESSION['language_code'] = 'pt_BR';
?>
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
  <link rel="stylesheet" href="<?=base_url('assets/dist/css/progressBar.css')?>">
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/css/fileinput.min.css">

  <link rel="stylesheet" href="<?php echo base_url('assets/fullcalendar/fullcalendar.min.css') ?>" />
  <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css') ?>" />
  <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/bootstrap-select-1.13.14/dist/css/bootstrap-select.css') ?>" />
  <link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.0/css/select.dataTables.min.css">

  <!-- icheck -->
  <!-- iCheck for checkboxes and radio inputs -->
  <link rel="stylesheet" href="<?php echo base_url('assets/plugins/iCheck/all.css') ?>">

  <!-- Styles CSS -->
  <link rel="stylesheet" href="<?=base_url('assets/dist/css/styles.css')?>">

  <?php if ($this->session->userdata('layout')) {
    // Styles CSS parametro seller_center
    $layout = $this->session->userdata('layout');
  ?>

  <?php if (file_exists('assets/dist/css/styles_'.$layout['value'].'.css')) { ?>
  <!-- CSS do Seller Center default -->
  <link rel="stylesheet" href="<?=base_url('assets/dist/css/styles_'.$layout['value'].'.css') ?>">
  <?php } ?>

  <!-- CSS Customizado -->
  <link rel="stylesheet" href="<?=base_url('assets/dist/css/CustomizationTheme/styles_custom.php?'.$layout['value']) ?>">
  <?php } ?>

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

  <!--- Bootstrap toggle --->
  <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/bootstrap-toggle-master/css/bootstrap-toggle.min.css') ?>" />

  <!-- bootstrap-switch -->

  <link rel="stylesheet" href="<?php echo base_url('assets/plugins/bootstrap-switch/css/bootstrap3/bootstrap-switch.min.css') ?>" />

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
  <script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/fileinput.min.js"></script>
  <script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/locales/pt-BR.js"></script>
  <script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/locales/fr.js"></script>
  <script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/locales/es.js"></script>

  <!-- Plugin FileInput (necessário para mover as imagens) -->
  <!-- <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script> -->
  <script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/plugins/piexif.min.js"></script>
  <script src="https://cdn.jsdelivr.net/gh/kartik-v/bootstrap-fileinput@5.5.0/js/plugins/sortable.min.js" type="text/javascript"></script>

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
    <script src="<?php echo base_url('assets/bower_components/bootstrap-select-1.13.14/dist/js/i18n/defaults-'.$_SESSION['language_code'].'.min.js') ?>"></script>
	
	  <!--- Bootstrap toggle --->
    <script src="<?php echo base_url('assets/bower_components/bootstrap-toggle-master/js/bootstrap-toggle.min.js') ?>"></script>

    <!--- Bootstrap toggle --->
    <script src="<?php echo base_url('assets/plugins/Mask/jquery.mask.js') ?>"></script>
<!-- bootstrap-switch  -->
<script src="<?php echo base_url('assets/plugins/bootstrap-switch/js/bootstrap-switch.min.js') ?>"></script>

    <script type="text/javascript" src="<?php echo base_url('assets/bower_components/bootstrap/dist/js/pipeline.js'); ?>"></script>
	
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

    div.dataTables_wrapper div.dataTables_processing {
      top: 20%;
    }

    </style>
    <script src="<?=base_url('assets/bower_components/font-awesome/ddad1b6b2a.js')?>" crossorigin="anonymous"></script>

    <!-- include summernote css/js -->
    <link href="<?=base_url('assets/bower_components/summernote/summernote.min.css')?>" rel="stylesheet">
    <script src="<?=base_url('assets/bower_components/summernote/summernote.min.js')?>"></script>
    <script src="<?php echo base_url('assets/bower_components/summernote/lang/summernote-pt-BR.js') ?>"></script>
    <!-- Animate CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

	<link rel="icon" href="<?=base_url('assets/skins/'.$sellerCenter.'/favicon.ico')?>" type="image/gif">

  <?php $settingJivochat = $this->model_settings->getSettingDatabyName('view_jivochat');
        if($settingJivochat && $settingJivochat['status'] == 1) { ?>
          <script src="//code.jivosite.com/widget/wgZ2gEnPph" async></script>
  <?php }?>
  <?php $settingGuidedTour = $this->model_settings->getSettingDatabyName('guided_tour');
        if($settingGuidedTour && $settingGuidedTour['status'] == 1) { ?>
          <script>window.userpilotSettings = {token: "NX-88cq82v8"}</script> 
          <script src = "https://js.userpilot.io/sdk/latest.js"></script>
          <script>
            userpilot.identify(

              "<?php echo $this->session->userdata('id'); ?>", // Used to identify users 
              {
                  name: "<?php echo $this->session->userdata('username'); ?>", // Full name
                  email: "<?php echo $this->session->userdata('email'); ?>", // Email address
                  created_at: "<?php echo (new DateTime())->getTimestamp(); ?>", // Signup date as a Unix timestamp
              }
            );
          </script>
  <?php } ?>
</head>
<body class="hold-transition skin-conectala sidebar-mini">

<style>
	body {color: #0066CC;}
	a:link {text-decoration: underline;}
	a.btn:link {text-decoration: none}
</style>

<?php $permissionPilot = $this->model_settings->getPermissionPilot('use_hotjar');
if($permissionPilot && $permissionPilot['status'] == 1) { ?>
    <!-- Hotjar Tracking Code for Log in  -->
    <script>
        (function(h,o,t,j,a,r){
            h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
            h._hjSettings={hjid:2897027,hjsv:6};
            a=o.getElementsByTagName('head')[0];
            r=o.createElement('script');r.async=1;
            r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
            a.appendChild(r);
        })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
    </script>
<?php } ?>

<?php if($hotjar_id && $hotjar_id['status'] == "1"){ ?>
<script> (function(h,o,t,j,a,r){ h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)}; h._hjSettings={hjid:<?= $hotjar_id['value'] ?>,hjsv:6}; a=o.getElementsByTagName('head')[0]; r=o.createElement('script');r.async=1; r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv; a.appendChild(r); })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv='); </script>
<?php } ?>

<?php $use_datadog_rum = $this->model_settings->getValueIfAtiveByName('use_datadog_rum');
if ($use_datadog_rum) { ?>

  <script
      src="https://www.datadoghq-browser-agent.com/us1/v4/datadog-rum.js"
      type="text/javascript">
  </script>
  <script>
      window.DD_RUM && window.DD_RUM.init({
        clientToken: '<?php echo $this->model_settings->getValueIfAtiveByName('datadog_rum_client_token'); ?>',
        applicationId: '<?php echo $this->model_settings->getValueIfAtiveByName('datadog_rum_application_id'); ?>',
        site: 'datadoghq.com',
        service: '<?php echo $this->model_settings->getValueIfAtiveByName('datadog_rum_service'); ?>',
        env: '<?php echo $this->model_settings->getValueIfAtiveByName('datadog_rum_env'); ?>',
        version: '<?=$version?>', 
        sessionSampleRate: <?php echo $this->model_settings->getValueIfAtiveByName('datadog_rum_session_sample_rate'); ?>,
        sessionReplaySampleRate: <?php echo $this->model_settings->getValueIfAtiveByName('datadog_rum_session_replay_sample_rate'); ?>,
        trackUserInteractions: true,
        trackResources: true,
        trackLongTasks: true,
        defaultPrivacyLevel: 'mask-user-input',
      });

      window.DD_RUM &&
      window.DD_RUM.startSessionReplayRecording();
  </script>

<?php } ?>

<div class="wrapper">

