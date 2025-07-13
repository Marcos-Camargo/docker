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
                        <h3 class="box-title"><?= $this->lang->line('messages_active_confirm_mensage_company'); ?></h3>
                        <br><br>
                        <h4><strong><?= $this->lang->line('application_total') ?> <?= $this->lang->line('application_stores') ?></strong>: <?=$count_stores?></h4>
                        <h4><strong><?= $this->lang->line('application_total') ?> <?= $this->lang->line('application_users') ?></strong>: <?=$count_users?></h4>
                    </div>
                    <form role="form" action="<?= (base_url('company/update')) ?>" method="post" enctype="multipart/form-data">
                        <div class="box-body">
                        </div>
                        <div class="box-footer">
                            <a type="submit" href="<?php echo base_url('company/activeConfirmed/') . $store_id ?>" class="btn btn-success"><?= $this->lang->line('application_activate'); ?> </a>
                            <a href="<?php echo base_url('company/') ?>" class="btn btn-warning"><?= $this->lang->line('application_back'); ?></a>
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