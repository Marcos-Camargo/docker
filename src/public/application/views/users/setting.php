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
        <a type="button" href="<?= base_url('users/notification_config') ?>" class="btn btn-primary">
            <?= $this->lang->line('application_notification'); ?>
        </a>
        <a type="button" href="<?= base_url('users/changepassword') ?>" class="btn btn-primary">
            <?= $this->lang->line('application_change_password'); ?>
        </a>
        <!-- <?php if (in_array('updateStore', $user_permission)) : ?>
          <a type="button" href="<?= base_url('users/notification_config') ?>" class="btn btn-primary">
            <?= $this->lang->line('application_notification'); ?>
          </a>
        <?php endif; ?> -->
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"><?= $this->lang->line('application_update_information'); ?></h3>
          </div>
          <!-- /.box-header -->
          <form role="form" action="<?php base_url('users/setting') ?>" method="post">
              <?php if (isset($csrf)) { ?>
                  <input type="hidden" name="<?= $csrf['name']; ?>" value="<?= $csrf['hash']; ?>"/>
              <?php } ?>
            <div class="box-body">

              <?php echo validation_errors(); ?>
			  <div class="row">
	              <div class="form-group col-md-6">
	                <label for="username"><?= $this->lang->line('application_username'); ?></label>
	                <input type="text" class="form-control" id="username" name="username" placeholder="<?= $this->lang->line('application_enter_user_username'); ?>" value="<?php echo $user_data['username'] ?>" autocomplete="off">
	              </div>
	
	              <div class="form-group col-md-6">
	                <label for="email"><?= $this->lang->line('application_email'); ?></label>
	                <input type="email" class="form-control" id="email" name="email" placeholder="<?= $this->lang->line('application_enter_user_email'); ?>" value="<?php echo $user_data['email'] ?>" autocomplete="off">
	              </div>
	
	              <div class="form-group col-md-6">
	                <label for="fname"><?= $this->lang->line('application_firstname'); ?></label>
	                <input type="text" class="form-control" id="fname" name="fname" placeholder="<?= $this->lang->line('application_enter_user_fname'); ?>" value="<?php echo $user_data['firstname'] ?>" autocomplete="off">
	              </div>
	
	              <div class="form-group col-md-6">
	                <label for="lname"><?= $this->lang->line('application_lastname'); ?></label>
	                <input type="text" class="form-control" id="lname" name="lname" placeholder="<?= $this->lang->line('application_enter_user_lname'); ?>" value="<?php echo $user_data['lastname'] ?>" autocomplete="off">
	              </div>
	
	              <div class="form-group col-md-3">
	                <label for="phone"><?= $this->lang->line('application_phone'); ?></label>
	                <input type="text" class="form-control" id="phone" name="phone" placeholder="<?= $this->lang->line('application_enter_user_phone'); ?>" value="<?php echo $user_data['phone'] ?>" autocomplete="off">
	              </div>
              </div>

            </div>
            <!-- /.box-body -->

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
    $("#MainsettingNav").addClass('active menu-open');
    $("#UsersettingNav").addClass('active');
  });


</script>