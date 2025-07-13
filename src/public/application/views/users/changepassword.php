<!--

Mostra a tela de trocar de senha 

-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_change_password";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <?php if ($this->session->flashdata('success')) : ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')) : ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><?= $this->lang->line('application_change_password') ?></h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <?php
                        if (validation_errors()) {
                            foreach (explode("</p>", validation_errors()) as $erro) {
                                $erro = trim($erro);
                                if ($erro != "") { ?>
                                    <div class="alert alert-error alert-dismissible" role="alert">
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span></button>
                                        <?php echo $erro . "</p>"; ?>
                                    </div>
                        <?php }
                            }
                        } ?>
                        
                            <div class="row">
                                <form role="form" action="<?php base_url('users/changepassword') ?>" method="post">
                                	<input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
                                    <div class="form-group col-md-3">
                                        <label for="current_password"><?= $this->lang->line('application_current_password') ?></label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" placeholder="<?= $this->lang->line('application_enter_current_password') ?>" value="<?= set_value('current_password') ?>" required />
                                    	<span onclick="hideShowPass(event, 'current_password')"><small><i class="far fa-eye"></i><?= $this->lang->line('application_view_password'); ?></small></span>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="new_password"><?= $this->lang->line('application_new_password') ?>
                                            <i class="fa fa-info-circle" data-toggle="tooltip" data-placement="bottom" title="<?= $this->lang->line('messages_password_strenght_profile') ?>"></i></label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="<?= $this->lang->line('application_enter_new_password') ?>" value="<?= set_value('new_password') ?>" minlength="8" maxlength="16" required />
                                    	<span onclick="hideShowPass(event, 'new_password')"><small><i class="far fa-eye"></i><?= $this->lang->line('application_view_password'); ?></small></span>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="confirm_password"><?= $this->lang->line('application_confirm_password') ?></label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="<?= $this->lang->line('application_enter_confirm_password') ?>" value="<?= set_value('confirm_password') ?>" minlength="8" maxlength="16" required />
                                    	<span onclick="hideShowPass(event, 'confirm_password')"><small><i class="far fa-eye"></i><?= $this->lang->line('application_view_password'); ?></small></span>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <br>
                                        <button type="submit" class="btn btn-primary col-md-12"><?= $this->lang->line('application_change_password'); ?></button>
                                    </div>
                                </form>
                            </div>
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>

<script type="text/javascript">
  $(document).ready(function() {
    $("#settingNav").addClass('active');
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
</script>
<style>
    label[for=new_password] {
        display: flex;
        justify-content: space-between;
    }

    label[for=new_password] i {
        cursor: pointer;
    }
</style>