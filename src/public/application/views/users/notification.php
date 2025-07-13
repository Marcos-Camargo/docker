<!--
SW Serviços de Informática 2019

Editar Profile

-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_settings";
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
                        <h3 class="box-title"><?= $this->lang->line('application_update_information'); ?></h3>
                    </div>
                    <!-- /.box-header -->
                    <form action="<?= base_url('users/set_notification_config') ?>" method="post">
                    	<input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
                        <div class="box-body">
                            <?php echo validation_errors(); ?>
                            <div class="col-sm-10 hidden">
                                <input type="text" readonly class="form-control-plaintext" id="user_id" value="<?= $user_config_for_notification['id_user'] ?>">
                            </div>
                            <div class="form-group">
                                <label for="exampleFormControlSelect1"><?= $this->lang->line('application_orders'); ?></label>
                                <select class="custom-select" name="order_notification">
                                    <option value="dont_send" <?=($user_config_for_notification['order_notification']=='dont_send' || $user_config_for_notification['order_notification']=='')?'selected':'' ?>><?= $this->lang->line('application_order_email_report_dont_take'); ?></option>
                                    <option value="receive_instantly" <?=($user_config_for_notification['order_notification']=='receive_instantly')?'selected':'' ?>><?= $this->lang->line('application_order_email_report_receive_instantly'); ?></option>
                                    <option value="receive_daily" <?=($user_config_for_notification ['order_notification']=='receive_daily')?'selected':'' ?>><?= $this->lang->line('application_order_email_report_receive_daily'); ?></option>
                                </select>
                            </div>
                            <div class="box-footer">
                                <button type="submit" class="btn btn-primary"><?= $this->lang->line('application_save'); ?></button>
                                <a href="<?php echo base_url('users/') ?>" class="btn btn-warning"><?= $this->lang->line('application_back'); ?></a>
                            </div>
                    </form>
                </div>
                <!-- /.box -->
            </div>

        </div>
        <!-- /.row -->


    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

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