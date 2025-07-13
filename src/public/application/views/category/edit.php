<script>
    $(function(){
        $('.checkbox-wrapper').tooltip();
        desabilitar();
    });
    function desabilitar() {
        if ($("#checkbox").is(":checked")) {
            $('#edit_cross_docking').attr('readonly', true).css('background-color', "#F08080");
        } else {
            $('#edit_cross_docking').attr('readonly', false).css('background-color', "");
        }
    }
</script>

<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_add";
    $this->load->view('templates/content_header', $data);
    ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

				<?php if ($this->session->flashdata('success')): ?>
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
                        <h3 class="box-title"><?= $this->lang->line('application_add_category'); ?></h3>
                    </div>
                    <form role="form" action="<?php base_url('category/edit') ?>" method="post">
                        <input name="id"  value="<?= $category->id ?>" type="hidden"/>
                        <div class="box-body">
                            <?php
                            if (validation_errors()) {
                                foreach (explode("</p>",validation_errors()) as $erro) {
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
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="category_name"><?= $this->lang->line('application_name'); ?></label>
                                    <input type="text" class="form-control" id="category_name" value="<?= $category->name ?>" name="edit_category_name" placeholder="<?= $this->lang->line('application_enter_category_name') ?>" autocomplete="off">
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="active"><?= $this->lang->line('application_status'); ?></label>
                                    <select class="form-control" id="active" name="edit_active">
                                        <option value="1" <?= ($category->active == 1 ? 'selected="selected"' : '') ?>><?= $this->lang->line('application_active'); ?></option>
                                        <option value="2" <?= ($category->active == 2 ? 'selected="selected"' : '') ?>><?= $this->lang->line('application_inactive'); ?></option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6  <?php echo (form_error('edit_cross_docking')) ? 'has-error' : '';  ?>">
                                    <label for="edit_cross_docking"><?= $this->lang->line('application_cross_docking_in_days'); ?></label>
                                    <input type="number" class="form-control" id="edit_cross_docking" value="<?php echo set_value('edit_cross_docking',$category->days_cross_docking); ?>" name="edit_cross_docking" autocomplete="off" placeholder="<?= $this->lang->line('application_enter_cross_docking_in_days'); ?>" >
                                    <small><?= $this->lang->line('messages_alert_limit_cross_docking'); ?></small>
                                    <div class="checkbox-wrapper" data-toggle="tooltip" title="<?=$this->lang->line('application_msg20')?>">
                                        <input data-toggle="popover" <?php echo ($category->blocked_cross_docking == 1) ? 'checked' : ''?> id="checkbox"  title=""  type="checkbox" onclick="desabilitar('sim')" name="blocked_cross_docking" value = "1" > Fixar Prazo
                                    </div>
                                    <?php echo '<i style="color:red">'.form_error('edit_cross_docking').'</i>'; ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6 <?php echo (form_error('edit_tipo_volume')) ? 'has-error' : '';  ?>">
                                    <label for="tipo_volume"><?= $this->lang->line('application_volume_type'); ?></label>
                                    <select class="form-control" id="tipo_volume" name="edit_tipo_volume" required>
                                        <option value=""><?= $this->lang->line('application_select'); ?></option>
                                        <?php foreach ($tipos_volumes as $tipo_volume): ?>
                                            <option value="<?php echo trim($tipo_volume['id']); ?>" <?= ($category->tipo_volume_id == trim($tipo_volume['id']) ? 'selected="selected"' : '') ?>><?php echo trim($tipo_volume['produto']) . '(' . trim($tipo_volume['codigo']) . ')'; ?></option>
                                        <?php endforeach ?>
                                    </select>
                                    <?php echo '<i style="color:red">'.form_error('edit_tipo_volume').'</i>'; ?>
                                </div>
                                <div class="form-group col-md-6 <?php echo (form_error('days_invoice_limit')) ? 'has-error' : '';  ?>">
                                    <label for="invoice-limits"><?= $this->lang->line('application_limit_invoice_days'); ?></label>
                                    <input type="number" class="form-control" id="days_invoice_limit" value="<?php echo set_value('days_invoice_limit',$category->days_invoice_limit); ?>" name="days_invoice_limit" autocomplete="off" placeholder="<?= $this->lang->line('application_enter_limit_invoice_days'); ?>">
                                    <small><?= $this->lang->line('messages_alert_limit_days_invoice'); ?></small>
                                    <?php echo '<i style="color:red">'.form_error('days_invoice_limit').'</i>'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary"><?= $this->lang->line('application_save'); ?></button>
                            <a href="<?php echo base_url('category/') ?>" class="btn btn-warning"><?= $this->lang->line('application_back'); ?></a>
                        </div>
                    </form>
                </div>
                <!-- /.box -->
            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>