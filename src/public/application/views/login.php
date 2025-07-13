<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Log in</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/bootstrap/dist/css/bootstrap.min.css') ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/font-awesome/css/font-awesome.min.css') ?>">
    <!-- Ionicons -->
    <link rel="stylesheet" href="<?php echo base_url('assets/bower_components/Ionicons/css/ionicons.min.css') ?>">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo base_url('assets/dist/css/AdminLTE.min.css') ?>">
    <!-- iCheck -->
    <link rel="stylesheet" href="<?php echo base_url('assets/plugins/iCheck/square/blue.css') ?>">
    <!--  styles  -->
    <link rel="stylesheet" href="<?php echo base_url('assets/dist/css/styles.css') ?>">
    <!-- Animate-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- Google Font -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

    <!-- <link rel="icon" href="<?= base_url() ?>/favicon.ico" type="image/gif"> -->
    <link rel="icon" href="<?=base_url('assets/skins/'.$sellerCenter.'/favicon.ico')?>" type="image/gif">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

</head>
<body class="hold-transition login-page" style="background-color: #222">
<div class="login-box">
    <div class="login-box-body animate__animated animate__fadeInLeft">
        <div class="login-logo animate__animated animate__fadeInTopLeft">
            <a href="<?php echo base_url(); ?>">
                <img src="<?php echo base_url() . $this->session->userdata('logo'); ?>" width="150" height="50">
            </a>
        </div>
        <!-- /.login-logo -->


        <p class="login-box-msg"><?= $this->lang->line('application_login_header'); ?></p>

        <?php echo validation_errors(); ?>

        <?php if (!empty($errors)) {
			echo '<spam style="color:red;">'.$errors.'</spam>';
        } ?>

        <form action="<?php echo base_url('auth/login') ?>" method="post">
        	<input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
            <div class="form-group has-feedback animate__animated animate__fadeInDown">
                <input type="email" class="form-control" name="email" id="email"
                       placeholder="<?= $this->lang->line('application_email'); ?>"
                       value="<?php echo set_value('email', '') ?>" autofocus autocomplete="off">
                <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
            </div>
            <div class="form-group animate__animated animate__fadeInDown">
            	<div class="form-group has-feedback">
                	<input type="password" class="form-control" name="password" id="password" placeholder="<?= $this->lang->line('application_password'); ?>" autocomplete="off">
                	<span class="glyphicon glyphicon-eye-open form-control-feedback" onclick="hideShowPass(event, 'password')" style="cursor: pointer;pointer-events: fill;"></span>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-7">
                </div>

                <!--  
                <div class="col-xs-6">
                    <div class="checkbox icheck">
                        <label>
                          <input type="checkbox"> <?= $this->lang->line('application_login_remember'); ?>
                        </label>
                    </div>
                </div>
                -->
                <!-- /.col -->
                <div class="col-xs-4 animate__animated animate__fadeInUp" style="float: right">
                    <button type="submit" class="btn btn-primary btn-block btn-flat"
                            style="background-color: #222; border: 1px solid #000"><?= $this->lang->line('application_login'); ?></button>
                </div>
                <!-- /.col -->
            </div>
        </form>
        <div class="row" style="margin-top: 20px;margin-right: -37px;">
            <div class="col-xs-1 pull-right">
            </div>
            <div class="col-xs pull-right">
            	<button id="passwordReset"
                        class="btn-link"><b><i><?= $this->lang->line('application_password_reset') ?></i></b></button>
            </div>
        </div>

        <?php 
        if ($messages_external_login) { ?>
        <div class="row"></div>
            <?php foreach($messages_external_login as $extlogin) { ?>
                <div class="row" style="margin-top: 40px;margin-left: 0px;">
                    <?php 
                    $icon = '';
                    foreach($messages_external_login_icon as $openidicon) {
                        if (($openidicon['external_authentication_id'] == $extlogin['external_authentication_id']) && (is_file(FCPATH. $openidicon['value']))) {                                      
                            $icon = '<img src="'.base_url($openidicon['value']).'" width="25" hight="25" />';
                        } 
                    }?>
                    <a class="btn btn-warning" href=<?= base_url('externalLogin/index/'.$extlogin['external_authentication_id']);?>> <?= $icon?><b>&nbsp;<?= $extlogin['value']?></b></a>
                </div>   
                <?php if($this->session->flashdata('error')): ?>
                    <div class="row" style="margin-top: 20px;margin-left: 0px;"></div>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>                     
            <?php }
        } ?>

        <?php if (isset($policy)) {?>
        <div class="row" style="margin-top: 10px;">
            <div class="col-xs-12">
            	<p style="text-align: justify;"><small><?= $this->lang->line('application_privacy_policy_disclamer') ?><a href="<?= $policy; ?>" download> <?= $this->lang->line('application_privacy_policy') ?> </a></small> </p>
            </div>
        </div>
        <?php } ?>

    </div>

    <!-- /.login-box-body -->
</div>


<div class="modal fade" id="modalPassword" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"
                    id="myModalLabel"><?= $this->lang->line('application_pw_reset') ?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="form-group">
                        <label for="emailReset"
                               class="col-md-4 label-heading"><?= $this->lang->line('application_email') ?></label>
                        <div class="col-md-8 ui-front">
                            <input type="email" required class="form-control" name="emailReset" id="emailReset" required
                                   placeholder="<?= $this->lang->line('application_email') ?>" value=""
                                   autocomplete="true">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning"
                            data-dismiss="modal"><?= $this->lang->line('application_close') ?></button>
                    <button id="sendResetMail" class="btn btn-primary" style="width: 250px">
                        <?= $this->lang->line('application_send_reset_password_email') ?>
                    </button>
                    <button class="btn btn-primary hide" id="bt_spinner_reset" style="width: 250px" type="button" disabled>
                        <span class="fa fa-spinner fa-spin" role="status" aria-hidden="true"></span>
                        <span class="sr-only">Loading...</span>
                    </button>
                    <?php echo form_close() ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php

if (isset($validated))
  if ($validated) {
    ?>
    <div class="modal fade" id="modalNewPassword" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"
                        id="myModalLabel"><?= $this->lang->line('application_pw_reset') ?></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group  col-md-6">
                        	<div class="input-group">
                            	<input type="password" class="form-control" name="password" id="newPassword"
                                   placeholder="<?= $this->lang->line('application_enter_new_password'); ?>"
                                   autocomplete="off" minlength="8" maxlength="16">
                           		<span class="glyphicon glyphicon-eye-open input-group-addon" onclick="hideShowPass(event, 'newPassword')"></span>
                        	</div>
                        </div>
                        <div class="form-group  col-md-6">
                        	<div class="input-group">
                            	<input type="password" class="form-control" name="newPasswordConfirmation" id="newpasswordConfirmation"
                                   placeholder="<?= $this->lang->line('application_enter_confirm_password'); ?>"
                                   autocomplete="off" minlength="8" maxlength="16">
                           		<span class="glyphicon glyphicon-eye-open input-group-addon" onclick="hideShowPass(event, 'newpasswordConfirmation')"></span>
                        	</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-warning"
                                data-dismiss="modal"><?= $this->lang->line('application_close') ?></button>
                        <input type="button" id="confirmNewPassword" class="btn btn-primary"
                               value="<?= $this->lang->line('application_confirm_password') ?>">
                        <?php echo form_close() ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>

<div class="navbar n_psn animate__animated animate__bounce" id="box-cookies">
    <div class="col-sm-10 bg-white">
        <span class="text-black t-a12">Utilizamos cookies para oferecer melhor experiência, melhorar o desempenho, analisar como você interage em nosso site utilizando o idioma do navegador e informações da sessão durante o uso do site.</span>
    </div>
    <div class="col-sm-1">
        <a class="btn btn-lg bg-black b_34f" onclick="SetCookies()">Entendi</a>
    </div>
</div>

<!-- jQuery 3 -->

<script src="<?php echo base_url('assets/bower_components/jquery/dist/jquery.min.js') ?>"></script>
<!-- Bootstrap 3.3.7 -->
<script src="<?php echo base_url('assets/bower_components/bootstrap/dist/js/bootstrap.min.js') ?>"></script>
<!-- iCheck -->
<script src="<?php echo base_url('assets/plugins/iCheck/icheck.min.js') ?>"></script>
<script type="text/javascript" src="<?php echo base_url('/assets/bower_components/sweetalert/dist/sweetalert2.all.min.js') ?>"></script>
<script>
const AlertSweet = Swal.mixin({
    target: 'body'
})

var base_url = "<?php echo base_url(); ?>";

$(function () {
	$('input').iCheck({
		checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
        increaseArea: '20%' // optional
    });
});

$(document).ready(() => {
	$('#passwordReset').on('click', () => {
		$('#modalPassword').modal();
	});

    $('#sendResetMail').on('click', () => {
    	if  ($('#emailReset').val() == '') {
    		AlertSweet.fire({
						icon: 'error',
						title: '<?=$this->lang->line("application_enter_user_email")?>',
					});
    	}
    	else {
    		$('#bt_spinner_reset').removeClass('hide');
	    	$('#sendResetMail').hide();
	    		$.post(base_url+"auth/passwordReset",
		        {
		            email: $('#emailReset').val()
		        },
	            function (data, status) {
	            	erro = false;
	                //console.log(data);
	                if (data.toString().search('wrongmail') >= 0) {
						AlertSweet.fire({
							icon: 'success',
							title: '<?=$this->lang->line("messages_password_reset_email_error")?>',
						});
	                    erro = true;
					}
					if (data.toString().search('wait') >= 0) {
						AlertSweet.fire({
							icon: 'error',
							title: '<?=$this->lang->line("application_token_wait")?>',
						});
	        			erro = true;
					}
					if (data.toString().search('erroremail') >= 0) {
						AlertSweet.fire({
							icon: 'error',
							title: '<?=$this->lang->line("application_email_error")?>',
						});
	                    erro = true;
					}
                    if (data.toString().search('notfoundemail') >= 0) {
						AlertSweet.fire({
							icon: 'error',
							title: '<?=$this->lang->line("application_email_not_found")?>',
						});
	                    erro = true;
					}
                    if (data.toString().search('toomanyrequests') >= 0) {
						AlertSweet.fire({
							icon: 'error',
							title: '<?=$this->lang->line("application_too_many_requests_send_email")?>',
						});
	                    erro = true;
					}
					if (!erro){
						AlertSweet.fire({
							icon: 'success',
							title: '<?=$this->lang->line("application_email_sent")?>',
						});
					}
					$('#bt_spinner_reset').addClass('hide');
					$('#modalPassword').modal('hide');
					$('#sendResetMail').show();
	            });
    	}
        $('#emailReset').val('');
    });

	<?php
    if (isset($validated)) if ($validated) {
    ?>
		$('#modalNewPassword').modal();
		$('#confirmNewPassword').on('click', () => {
			if ($('#newPassword').val() === $('#newpasswordConfirmation').val()) {

				$.post(base_url+"auth/newPassword",

                    {
						newPassword: $('#newPassword').val(),
                        token: '<?php echo $token ?>'
                    },
                    function (data, status) {
                    	//console.log(data);
                        if (data == 1) {

                        	AlertSweet.fire({
								icon: 'success',
								title: '<?=$this->lang->line("messages_password_changed")?>',
							}).then((result) => {
								 window.location.replace(base_url+'auth/login');
							});

                        }
                        else {
                        	AlertSweet.fire({
								icon: 'error',
								title: data.toString(),
							});
                        }
                    });
            }
            else {
            	AlertSweet.fire({
					icon: 'error',
					title: '<?=$this->lang->line("application_error_match_passwords")?>',
				});
            }
        });
    <?php
	}
    ?>
});

function hideShowPass(e, fieldid) {
    e.preventDefault();
    var x = document.getElementById(fieldid);
    if (x.type === "password") {
    	x.type = "text";
    } else {
    	x.type = "password";
    }
}

var redirectUrl = '<?=
array_key_exists('redirect_url', $_GET) ? 
( xssClean($_GET['redirect_url']) ? xssClean($_GET['redirect_url']) :
($redirect_url ?? '')) : ($redirect_url ?? '')  ?>';

if (redirectUrl.length > 0) {
    handleWithRedirectURL(redirectUrl);
}

function handleWithRedirectURL(redirectUrl) {
    if (inIFrame()) {
        var url = new URL(redirectUrl);
        url.searchParams.set('_in_iframe', 'true');
        redirectUrl = url.toString();
    }
    insertFormParams({
        'redirect_url': redirectUrl
    });
}

function inIFrame() {
    try {
        return window.self !== window.top;
    } catch (e) {
        return true;
    }
}

function insertFormParams(params) {
    $.each(params, function (k, i) {
            if (i.length > 0) {
                $('form').append($('<input>', {type: 'hidden', name: k, value: i}));
            }
        }
    );
}

// Cookies
    $.ajax({
        url: base_url+"cookies/get",
        type: 'GET',
        dataType: 'json',
        success:function(response) {
            if (response !== "accept") {
                $('#box-cookies').show();
            }
        }
    });
    function SetCookies() {
        $('#box-cookies').addClass('animate__fadeOutDown');
        const value = 'accept';
        $.ajax({
            url: base_url+"cookies/set",
            type: 'POST',
            data: { value: value},
            dataType: 'json',
        });
    }
// Cookies

</script>
</body>
</html>
