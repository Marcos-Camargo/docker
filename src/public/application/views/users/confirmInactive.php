<?php include_once(APPPATH . '/third_party/zipcode.php'); ?>
<!--
SW Serviços de Informática 2019

Editar Empresa
 
-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_edit";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><?= $this->lang->line('messages_inactive_confirm_mensage_users'); ?></h3>
                    </div>
                    <form role="form" action="<?= (base_url('users/update')) ?>" method="post" enctype="multipart/form-data">
                    	<input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
                        <div class="box-body">
                        </div>
                        <div class="box-footer">
                            <a type="submit" href="<?php echo base_url('users/inactiveConfirmed/') . $store_id ?>" class="btn btn-danger"><?= $this->lang->line('application_inactivate'); ?> </a>
                            <a href="<?php echo base_url('stores/') ?>" class="btn btn-warning"><?= $this->lang->line('application_back'); ?></a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">

</script>