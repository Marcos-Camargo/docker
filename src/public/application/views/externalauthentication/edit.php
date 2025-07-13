<!--
Criar/Ver/Editar External Authentication do tipo LDAP
-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

<?php $this->permission[]='createExternalAuthentication'; ?>

    <?php $data['pageinfo'] = "application_".$function;  
    $data['page_now'] = 'externalAuthentication';
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12 col-print-12">

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
                        <h3 class="box-title">
                        <?=$this->lang->line('application_'.$function);?> <?=$this->lang->line('application_externalAuthentication');?>
                        </h3>
                    </div>
                    
                    <form role="form" id="formedit" action="<?php base_url('externalAuthentication/'.$function) ?>" method="post"  enctype="multipart/form-data">
                        <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
                        <div class="box-body">
                            <div class="row">
                            <?php
                            if (validation_errors()) {
                                foreach (explode("</p>", validation_errors()) as $erro) {
                                    $erro = trim($erro);
                                    if ($erro!="") { ?>
                                        <div class="alert alert-error alert-dismissible" role="alert">
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            <?php echo $erro."</p>"; ?>
                                        </div>
                                        <?php
                                    }
                                }
                            } ?>
                            </div>
                            
                            <div class="row">

                                <div class="form-group col-md-6 col-print-6 <?php echo (form_error('name')) ? 'has-error' : ''; ?>">
                                    <label for="name"><?=$this->lang->line('application_name')?>*</label>
                                    <input type="text" required class="form-control" id="name" name="name" placeholder="<?=$this->lang->line('application_name')?>" autocomplete="off" value ="<?php echo set_value('name', $external_auth['name']) ?>" >
                                    <?php echo '<i style="color:red">'.form_error('name').'</i>'; ?>
                                </div>

                                <div class="form-group col-md-2 col-print-2">
                                    <label for="type"><?=$this->lang->line('application_type')?></label>
                                    <span class="form-control"><?=$external_auth['type']?>
                                    </span>
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="isActive"><?=$this->lang->line('application_status');?>*</label>
                                    <select class="form-control" required id="active" name="active" required>
                                        <option value="1" <?php echo $external_auth['active']=='1'?'selected="selected"':'';?>><?=$this->lang->line('application_active');?></option>
                                        <option value="0" <?php echo $external_auth['active']=='0'?'selected="selected"':'';?>><?=$this->lang->line('application_inactive');?></option>
                                    </select>
                                    <?php echo '<i style="color:red">'.form_error('isActive').'</i>'; ?>   
                                </div>

                            </div>

                            <?php if ($external_auth['type'] === 'OPENID') { ?>
                                <div class="row">
                                    <div class="form-group col-md-6 col-print-6 <?php echo (form_error('openid_client_id')) ? 'has-error' : ''; ?>">
                                        <label for="openid_client_id"><?=$this->lang->line('application_openid_client_id')?>*</label>
                                         <input type="text" required class="form-control" id="openid_client_id" name="openid_client_id" placeholder="<?=$this->lang->line('application_openid_client_id')?>" autocomplete="off" value ="<?php echo set_value('openid_client_id', $openid_configuration['openid_client_id']) ?>" >
                                         <?php echo '<i style="color:red">'.form_error('openid_client_id').'</i>'; ?>
                                    </div>
                                    <div class="form-group col-md-6 col-print-6 <?php echo (form_error('openid_client_secret')) ? 'has-error' : ''; ?>">
                                        <label for="openid_client_secret"><?=$this->lang->line('application_openid_client_secret')?>*</label>
                                         <input type="text" required class="form-control" id="openid_client_secret" name="openid_client_secret" placeholder="<?=$this->lang->line('application_openid_client_secret')?>" autocomplete="off" value ="<?php echo set_value('openid_client_secret', $openid_configuration['openid_client_secret']) ?>" >
                                         <?php echo '<i style="color:red">'.form_error('openid_client_secret').'</i>'; ?>
                                    </div>
                                </div>
                                <div class="row">    
                                    <div class="form-group col-md-6 col-print-6 <?php echo (form_error('openid_url_openid_configuration')) ? 'has-error' : ''; ?>">
                                        <label for="openid_url_openid_configuration"><?=$this->lang->line('application_openid_url_openid_configuration')?>*</label>
                                        <div class="input-group" >
                                        <span class="input-group-addon">https://</span>
                                            <input type="text" required class="form-control" id="openid_url_openid_configuration" name="openid_url_openid_configuration" placeholder="<?=$this->lang->line('application_openid_url_openid_configuration')?>" autocomplete="off" value ="<?php echo set_value('openid_url_openid_configuration', $openid_configuration['openid_url_openid_configuration']) ?>" >
                                            <?php echo '<i style="color:red">'.form_error('openid_url_openid_configuration').'</i>'; ?>
                                        </div>
                                    </div>                                
                                </div>
                                <div class="form-group col-md-3 col-print-3 <?php echo (form_error('openid_icon')) ? 'has-error' : ''; ?>">
                                    <label for="openid_icon"><?=$this->lang->line('application_openid_icon')?></label>
                                    <?php 
                                        $show_upload_openid_icon = false;
                                        if ($function=='update') {
                                            $show_upload_openid_icon = true;
                                            if (is_file(FCPATH. $openid_configuration['openid_icon'])) { 
                                                $show_upload_openid_icon = false;
                                        ?>
                                        
                                        <img src="<?=base_url($openid_configuration['openid_icon']);?>" alt="" width="30" height="30">
                                        
                                        <button id="button_alter_openid_icon" onclick="showDiv('button_alter_openid_icon','alter_openid_icon','openid_icon')"><?=$this->lang->line('application_to_change')?></button>
                                    <?php   }
                                        }
                                        if ($function=='create') {$show_upload_openid_icon = true;}
                                        if (($function=='view') && (is_file(FCPATH. $openid_configuration['openid_icon']))){?>
                                            
                                            <img src="<?=base_url($openid_configuration['openid_icon']);?>" alt="" width="30" height="30">
                                        <?php } ?>
                                    <div id="alter_openid_icon" style="display:<?=$show_upload_openid_icon ? 'block' : 'none';?>" >                                    
                                        <input type="file" accept="image/png, image/gif, image/jpeg, image/jpg" class="form-control"  id="openid_icon" name="openid_icon" autocomplete="off"  >
                                        <?php echo '<i style="color:red">'.form_error('openid_icon').'</i>'; ?>
                                    </div>
                                </div>
                                
                                <div class="row">    
                                    <div class="form-group col-md-6 col-print-6 <?php echo (form_error('openid_message_login')) ? 'has-error' : ''; ?>">
                                        <label for="openid_message_login"><?=$this->lang->line('application_openid_message_login')?>*</label>
                                        <input type="text" required class="form-control" id="openid_message_login" name="openid_message_login" placeholder="<?=$this->lang->line('application_openid_message_login')?>" autocomplete="off" value ="<?php echo set_value('openid_message_login', $openid_configuration['openid_message_login']) ?>" >
                                        <?php echo '<i style="color:red">'.form_error('openid_message_login').'</i>'; ?>
                                    </div>
                                </div>

                                <?php if ($function!='create') { ?>
                                <div class="row"> 
                                    <div class="col-md-5">
                                    <button type="button" onclick="OpenidTestSite(event)" id="testloginopenidbtn" class="btn btn-primary" >
                                    <?=$this->lang->line('application_openid_test_url');?>
                                    </button>
                                    </div>
                                </div>
                                <?php } ?>
                                <div class="row col-md-12 col-print-12">
                                    <h3 class="box-title"><?=$this->lang->line('application_instructions');?></h3>
                                    <h4><?=$this->lang->line('application_openid_instructions');?></h4>
                                </div> 
                                <div class="row">
                                    <div class="form-group col-md-9 col-print-9">
                                        <label><?=$this->lang->line('application_redirect_url')?></label>
                                    
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="<?= base_url('externalLogin/openIDLoginClose') ?>" readonly>
                                            <span class="input-group-btn">
                                                <button type="button" data-toggle="tooltip" title="<?=$this->lang->line('application_copy')?>" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                            <?php } ?>                            


                            <?php if ($external_auth['type'] === 'LDAP') { ?>
                                <div class="row">
                                    <div class="form-group col-md-6 col-print-6 <?php echo (form_error('ldap_host_name')) ? 'has-error' : ''; ?>">
                                        <label for="ldap_host_name"><?=$this->lang->line('application_ldap_host_name')?>*</label>
                                        <div class="input-group" >
                                        <span class="input-group-addon">ldaps://</span>
                                            <input type="text" required class="form-control" id="ldap_host_name" name="ldap_host_name" placeholder="<?=$this->lang->line('application_ldap_host_name')?>" autocomplete="off" value ="<?php echo set_value('ldap_host_name', $ldap_configuration['ldap_host_name']) ?>" >
                                            <?php echo '<i style="color:red">'.form_error('ldap_host_name').'</i>'; ?>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-2 col-print-2 <?php echo (form_error('ldap_port')) ? 'has-error' : ''; ?>">
                                        <label for="ldap_port"><?=$this->lang->line('application_ldap_port')?>*</label>
                                        <input type="text" required class="form-control" id="ldap_port" name="ldap_port" placeholder="<?=$this->lang->line('application_ldap_port')?>" autocomplete="off" value ="<?php echo set_value('ldap_port', $ldap_configuration['ldap_port']) ?>" >
                                        <?php echo '<i style="color:red">'.form_error('ldap_port').'</i>'; ?>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="ldap_version"><?=$this->lang->line('application_ldap_version')?>*</label>
                                        <select class="form-control" required id="ldap_version" name="ldap_version" required>
                                            <option value="2" <?php echo $ldap_configuration['ldap_version']=='2'?'selected="selected"':'';?>>2</option>
                                            <option value="3" <?php echo $ldap_configuration['ldap_version']=='3'?'selected="selected"':'';?>>3</option>
                                        </select>
                                        <?php echo '<i style="color:red">'.form_error('ldap_version').'</i>'; ?>   
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6 col-print-6 <?php echo (form_error('ldap_base_dn')) ? 'has-error' : ''; ?>">
                                        <label for="ldap_base_dn"><?=$this->lang->line('application_ldap_base_dn_dafault')?></label>
                                        <input type="text" class="form-control" id="ldap_base_dn" name="ldap_base_dn" placeholder="<?=$this->lang->line('application_ldap_base_dn_dafault')?>" autocomplete="off" value ="<?php echo set_value('ldap_base_dn', $ldap_configuration['ldap_base_dn']) ?>" onkeyup="LDAPMountName()">                                        
                                        <?php echo '<i style="color:red">'.form_error('ldap_base_dn').'</i>'; ?>
                                        <span id="longldapuser">ex.: ou=Users,dc=example,dc=com</span>
                                    </div>

                                    <div class="form-group col-md-2 col-print-2">
                                        <label for="ldap_user_type"><?=$this->lang->line('application_ldap_user_type')?>*</label>
                                        <select class="form-control" required id="ldap_user_type" name="ldap_user_type" required onchange="LDAPMountName()">
                                            <option value="username" <?php echo $ldap_configuration['ldap_user_type']=='Username'?'selected="selected"':'';?>><?=$this->lang->line('application_username')?></option>
                                            <option value="email" <?php echo $ldap_configuration['ldap_user_type']=='email'?'selected="selected"':'';?>><?=$this->lang->line('application_email')?></option>
                                        </select>
                                        <?php echo '<i style="color:red">'.form_error('ldap_user_type').'</i>'; ?>   
                                    </div>

                                </div>
                                <div class="row">
                                    <label class="col-md-3">
                                        <input type="checkbox" onclick="LDAPCheckRequireCertificate()" class="minimal" name="ldap_requires_certificate" id="ldap_requires_certificate" value="1" <?php echo set_checkbox('ldap_requires_certificate', '1', $ldap_configuration['ldap_requires_certificate']==1) ?>>
                                        <?=$this->lang->line('application_ldap_requires_certificate')?>
                                    </label>
                                </div>
                                <div class="row"id="ldap_certificate_files">
                                    <div class="form-group col-md-5 col-print-5 <?php echo (form_error('ldap_client_certificate')) ? 'has-error' : ''; ?>">
                                        <?php 
                                            $show_upload_ldap_client_certificate = false;
                                            if ($function=='update') {
                                                $show_upload_ldap_client_certificate = true;
                                                if (is_file(FCPATH. $ldap_configuration['ldap_client_certificate'])) { 
                                                    $show_upload_ldap_client_certificate = false;
                                            ?>
                                            <a href="<?php echo base_url($ldap_configuration['ldap_client_certificate']);?>"><?=$this->lang->line('application_ldap_client_certificate') ?></a>
                                            <button id="button_alter_ldap_client_certificate" onclick="showDiv('button_alter_ldap_client_certificate','alter_ldap_client_certificate','ldap_client_certificate')"><?=$this->lang->line('application_to_change')?></button>
                                        <?php   }
                                            }
                                            if ($function=='create') {$show_upload_ldap_client_certificate = true;}
                                            ?>
                                        <div id="alter_ldap_client_certificate" style="display:<?=$show_upload_ldap_client_certificate ? 'block' : 'none';?>" >
                                            <label for="ldap_client_certificate"><?=$this->lang->line('application_ldap_client_certificate')?> (.crt)</label>
                                            <input type="file" accept=".crt" class="form-control"  id="ldap_client_certificate" name="ldap_client_certificate" autocomplete="off"  >
                                            <?php echo '<i style="color:red">'.form_error('ldap_client_certificate').'</i>'; ?>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-5 col-print-5 <?php echo (form_error('ldap_certificate_key')) ? 'has-error' : ''; ?>">
                                        <?php 
                                            $show_upload_ldap_certificate_key = false;
                                            if ($function=='update') {
                                                $show_upload_ldap_certificate_key = true;
                                                if (is_file(FCPATH. $ldap_configuration['ldap_certificate_key'])) { 
                                                    $show_upload_ldap_certificate_key = false;
                                            ?>
                                            <a href="<?php echo base_url($ldap_configuration['ldap_certificate_key']);?>"><?=$this->lang->line('application_ldap_certificate_key') ?></a>
                                            <button id="button_alter_ldap_certificate_key" onclick="showDiv('button_alter_ldap_certificate_key','alter_ldap_certificate_key','ldap_certificate_key')"><?=$this->lang->line('application_to_change')?></button>
                                        <?php   }
                                            }
                                            if ($function=='create') {$show_upload_ldap_certificate_key = true;}
                                            ?>
                                        <div id="alter_ldap_certificate_key" style="display:<?=$show_upload_ldap_certificate_key ? 'block' : 'none';?>" >
                                            <label for="ldap_certificate_key"><?=$this->lang->line('application_ldap_certificate_key')?> (.key)</label>
                                            <input type="file" accept=".key" class="form-control"  id="ldap_certificate_key" name="ldap_certificate_key" autocomplete="off"  >
                                            <?php echo '<i style="color:red">'.form_error('ldap_certificate_key').'</i>'; ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($function!='create') { ?>
                                <div class="row"> 
                                    <div class="col-md-5">
                                    <button type="button" class="btn btn-primary" id="testloginbtn" data-toggle="modal" data-target="#modalLDAPTestLogin">
                                    <?=$this->lang->line('application_test_login_ldap');?>
                                    </button>
                                    </div>
                                </div>
                                <?php } ?>
                            <?php } ?>
                            
                            <br>
                            <div class="row">

                                <div class="col-md-5">
                                    <label class="normal"><?=$this->lang->line('application_created');?></label>
                                    <span ><?=$external_auth['email_created'].'/'.$external_auth['date_created'];?></span>
                                </div>
                                <div class="col-md-5">
                                    <label class="normal"><?=$this->lang->line('application_updated_on');?></label>
                                    <span ><?=$external_auth['email_updated'].'/'.$external_auth['date_updated'];?></span>
                                </div>
                            </div>


                        </div>
                        <!-- /.box-body -->
                        <div class="box-footer">
                            <?php if(in_array('createExternalAuthentication', $this->permission) && $function=='create'): ?>
                                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                            <?php endif;?>

                            <?php if(in_array('updateExternalAuthentication', $this->permission) && $function=='update'): ?>
                                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
                            <?php endif;?>
                            <a id="back_button" href="<?php echo base_url('externalAuthentication/index') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                        </div>
                    </form>
                    
                    <?php if($function!='create'): ?>
                        <div class="box">
                            <div class="box-header">
                                <h3 class="box-title"><?=$this->lang->line('application_users');?></h3>
                            </div>
                            <div class="box-body">
                                <table id="manageTable" aria-label="users table" class="table table-striped table-hover display table-condensed" style="border-spacing:0; border-collapse: collapse; width: 99%;">                                
                                    <thead>
                                    <tr>
                                        <th><?=$this->lang->line('application_id');?></th>
                                        <th><?=$this->lang->line('application_username');?></th>
                                        <th><?=$this->lang->line('application_firstname');?></th>
                                        <th><?=$this->lang->line('application_lastname');?></th>
                                        <th><?=$this->lang->line('application_email');?></th>
                                        <th><?=$this->lang->line('application_active');?></th>
                                    </tr>
                                    </thead>

                                </table>
                            </div>
                        </div>
                    <?php endif;?>
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

<?php if($function !=='create'): ?>
<div class="modal fade" tabindex="-1" role="dialog" id="modalLDAPTestLogin">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_test_login_ldap');?></h4>
      </div>

        <div class="modal-body">

            <div class="form-group">
                <?= $this->lang->line('application_format'); ?>
                <span id="longldapuserexemple">ex.: ou=Users,dc=example,dc=com</span>
            </div>
            <div class="form-group animate__animated animate__fadeInDown">
                <div class="form-group has-feedback">
                    <input type="texte" class="form-control" name="ldaplogin" id="ldaplogin"
                        placeholder="<?= $this->lang->line('application_enter_user_username'); ?>"
                        autocomplete="off">
                    <span class="glyphicon glyphicon-envelope form-control-feedback" ></span>
                </div>
            </div>

            <div class="form-group animate__animated animate__fadeInDown">
            	<div class="form-group has-feedback">
                	<input type="password" class="form-control" name="ldappassword" id="ldappassword" placeholder="<?= $this->lang->line('application_password'); ?>" autocomplete="off">
                	<span class="glyphicon glyphicon-eye-open form-control-feedback" onclick="LDAPhideShowPass(event, 'ldappassword')" style="cursor: pointer;pointer-events: fill;"></span>
                </div>
            </div>

        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-warning"
                    data-dismiss="modal"><?= $this->lang->line('application_close') ?></button>
            <button id="loginLdapbtn" onclick="LDAPTestLogin(event)" class="btn btn-primary" style="width: 250px"> Login
                <?= $this->lang->line('application_loggin') ?>
            </button>
            <?php echo form_close() ?>
        </div>

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">

    var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
        csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
    
    var manageTable;
    var base_url = "<?php echo base_url(); ?>";
    var edit_mode = "<?=$function?>";
    var conn_type = "<?=$external_auth['type']?>";

    var $form = $('#formedit'),
        origForm = $form.serialize();

    var formchanged = false; 

    $(document).ready(function() {
        $("#mainUserNav").addClass('active');
        $("#manageExternalAuthenticationNav").addClass('active');
        
        if (edit_mode != 'create') {
            if (conn_type=='LDAP') {
                LDAPMountName();
            }
        }

        if (conn_type=='LDAP') {
            LDAPCheckRequireCertificate();
        }
               
        manageTable = $('#manageTable').DataTable({
            "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "sortable": true,
            "searching": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline({
            url: base_url + 'externalAuthentication/fetchUsersExternalAuthentication',
            data: { 
                [csrfName]: csrfHash,
                externalauthenticationid: "<?=$external_auth['id']?>"
            },
            pages: 100
            })
        });

        if ((edit_mode != "update") && (edit_mode != "create")) {
            $('form input[type=checkbox]')
                .attr("onclick", "return false;");
            $('form input')
            .attr("readonly",true);
            $('form select')
                .attr("disabled", true);
            $('form textarea')
                .attr("disabled", true);
            $('form input[type=file]')
                .attr("disabled", true);
        }

        $('#print_button').on('click',function(event){
            event.preventDefault();
            window.print();
        })

        $('form :input').on('change input', function() {       
            formchanged = ($form.serialize() !== origForm);
            if(formchanged) {
                $('#testloginbtn').attr("disabled", true);
                $('#testloginopenidbtn').attr("disabled", true);
            }
            else {
                $('#testloginbtn').attr("disabled", false);
                $('#testloginopenidbtn').attr("disabled", false);
            }
        });

        $('.copy-input').click(function() {
            // Seleciona o conteúdo do input
            $(this).closest('.input-group').find('input').select();
            // Copia o conteudo selecionado
            const copy = document.execCommand('copy');
            if (copy) {
                Toast.fire({
                    icon: 'success',
                    title: "<?= $this->lang->line('application_content_successfully_copied') ?>"
                })
            } else {
                Toast.fire({
                    icon: 'error',
                    title: "<?= $this->lang->line('application_unable_to_copy_content') ?>"
                })
            }
        });
        
    });

function showDiv(button,divtoshow,uploadfield) {

    var x = document.getElementById(divtoshow);
    x.style.display = "block";
    var x = document.getElementById(button);
    x.style.display = "none";
    $('#'+uploadfield).attr('required', true);

}

function LDAPCheckRequireCertificate(){
    if($('#ldap_requires_certificate').is(":checked")){
        $('#ldap_certificate_files').show()
        if (edit_mode=='create') {
            $('#ldap_client_certificate').attr('required', true)
            $('#ldap_certificate_key').attr('required', true)
        }        
    }else{
        $('#ldap_certificate_files').hide()
        $('#ldap_client_certificate').attr('required', false)
        $('#ldap_certificate_key').attr('required', false)        
    }
}

function LDAPMountName() {

    var ldn = document.getElementById('ldap_base_dn').value; 
    var utype = document.getElementById('ldap_user_type').value; 
    if  (utype == 'username') {        
        $('#longldapuser').html('uid=username,'+ldn);
        $('#longldapuserexemple').html('uid=username,'+ldn);
    }
    else {
        $('#longldapuser').html('email@example.com');
        $('#longldapuserexemple').html('email@example.com');        
    }

}

function LDAPhideShowPass(e, fieldid) {
    e.preventDefault();
    var x = document.getElementById(fieldid);
    if (x.type === "password") {
    	x.type = "text";
    } else {
    	x.type = "password";
    }
}

function LDAPTestLogin(event) {
    event.preventDefault();
    if  ($('#ldaplogin').val() == '') {
        AlertSweet.fire({
            icon: 'error',
            title: '<?=$this->lang->line("application_enter_test_login")?>',
        });
    }
    else if  ($('#ldappassword').val() == '') {
        AlertSweet.fire({
            icon: 'error',
            title: '<?=$this->lang->line("application_enter_user_password")?>',
        });
    }
    else {
        $('#loginLdapbtn').attr("disabled", true);
        $.post(base_url+"externalAuthentication/LDAPTestLogin",
        {
            ldapid: <?=$external_auth['id']?>,
            ldaplogin: $('#ldaplogin').val(), 
            ldappassword: $('#ldappassword').val()
        },
        function (data, status) {
            returnedData = JSON.parse(data);
            if (returnedData.auth) {
                AlertSweet.fire({
                    icon: 'success',
                    title: returnedData.result,
                });
            }
            else {
                AlertSweet.fire({
                    icon: 'error',
                    title: returnedData.result,
                });
            }  
            $('#loginLdapbtn').attr("disabled", false);
            $('#ldaplogin').val('');
            $('#ldappassword').val('');
            $('#modalLDAPTestLogin').modal('hide'); 
        });
    }

}

function OpenidTestSite(event) {
    event.preventDefault();

    $('#testloginopenidbtn').attr("disabled", true);
    
    $.post(base_url+"externalAuthentication/OPENIDTestSite/",
    {
        openid: <?=$external_auth['id']?>
    },
    function (data, status) {
        returnedData = JSON.parse(data);
        if (returnedData.ok) {
            AlertSweet.fire({
                icon: 'success',
                width: 700,
                title: returnedData.result,
            });
        }
        else {
            AlertSweet.fire({
                icon: 'error',
                width: 700,
                title: returnedData.result,
            });
        }  
        $('#testloginopenidbtn').attr("disabled", false);

    });

}

</script>
<style>
    @media print{
        body {
            zoom: .8
        }
        .form-control{
            border: 1px solid #fff;
        }
        button,#back_button{
            visibility: hidden;
        }
        a[href]:after {
            content: none
        }
        .globalClass_ebef{
            visibility: hidden !important;
        }
        #manageTable_length,#manageTable_filter{
            visibility: hidden;
            display: none;
        }
        @page { size: landscape; }
        .logo-lg>img{
            width: 450px;
            height: 150px;
        }
        #logo_view{
            visibility: hidden;
        }
        select {
            appearance: none;
        }
        .col-md-1 {width:8%;  float:left;}
        .col-md-2 {width:16%; float:left;}
        .col-md-3 {width:25%; float:left;}
        .col-md-4 {width:33%; float:left;}
        .col-md-5 {width:42%; float:left;}
        .col-md-6 {width:50%; float:left;}
        .col-md-7 {width:58%; float:left;}
        .col-md-8 {width:66%; float:left;}
        .col-md-9 {width:75%; float:left;}
        .col-md-10{width:83%; float:left;}
        .col-md-11{width:92%; float:left;}
        .col-md-12{width:100%; float:left;}
        ::-webkit-input-placeholder { /* WebKit browsers */
            color: transparent;
        }
        :-moz-placeholder { /* Mozilla Firefox 4 to 18 */
            color: transparent;
        }
        ::-moz-placeholder { /* Mozilla Firefox 19+ */
            color: transparent;
        }
        :-ms-input-placeholder { /* Internet Explorer 10+ */
            color: transparent;
        } 
    }


</style>
