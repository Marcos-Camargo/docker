<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div id="messages"></div>

                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <?php if (in_array('createPaymentGatewayConfig', $user_permission)): ?>
                    <button class="btn btn-primary" data-toggle="modal"
                            data-target="#addSettingModal"><?= $this->lang->line('application_add_setting'); ?></button>
                    <br/> <br/>
                <?php endif; ?>

                <div class="box">
                    <div class="box-body">
                        <table id="manageTable" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th style="width:20%;"><?= $this->lang->line('application_name'); ?></th>
                                <th style="width:70%;"><?= $this->lang->line('application_value'); ?></th>
                                <th style="width:70%;"><?= $this->lang->line('application_payment_gateway'); ?></th>
                                <?php if (in_array('updateSetting', $user_permission) || in_array('deleteSetting', $user_permission)): ?>
                                    <th style="width:5%;"><?= $this->lang->line('application_action'); ?></th>
                                <?php endif; ?>
                            </tr>
                            </thead>

                        </table>
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
<!-- /.content-wrapper -->

<?php if (in_array('createPaymentGatewayConfig', $user_permission)): ?>
    <!-- create Setting modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="addSettingModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?= $this->lang->line('application_add_setting'); ?></h4>
                </div>

                <form role="form" action="<?php echo base_url('paymentGatewaySettings/create') ?>" method="post"
                      id="createSettingForm">

                    <div class="modal-body">

                        <div class="form-group">
                            <label for="setting_name"><?= $this->lang->line('application_name'); ?></label>
                            <input type="text" class="form-control" id="setting_name" name="setting_name"
                                   placeholder="<?= $this->lang->line('application_enter_setting_name'); ?>"
                                   autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="setting_value"><?= $this->lang->line('application_value'); ?></label>
                            <input type="text" class="form-control" id="setting_value" name="setting_value"
                                   placeholder="<?= $this->lang->line('application_enter_setting_value'); ?>"
                                   autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="setting_gateway_id"><?= $this->lang->line('application_payment_gateway'); ?></label>
                            <input type="text" class="form-control" id="setting_gateway_id" name="setting_gateway_id"
                                   placeholder="<?= $this->lang->line('application_payment_gateway'); ?>"
                                   autocomplete="off">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default"
                                data-dismiss="modal"><?= $this->lang->line('application_close'); ?></button>
                        <button type="submit"
                                class="btn btn-primary"><?= $this->lang->line('application_save'); ?></button>
                    </div>

                </form>


            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
<?php endif; ?>

<?php if (in_array('updatePaymentGatewayConfig', $user_permission)): ?>
    <!-- edit Setting modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="editSettingModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?= $this->lang->line('application_edit_setting'); ?></h4>
                </div>

                <form role="form" action="<?php echo base_url('paymentGatewaySettings/update') ?>" method="post"
                      id="updateSettingForm">

                    <div class="modal-body">
                        <div id="messages"></div>

                        <div class="form-group">
                            <label for="edit_setting_name"><?= $this->lang->line('application_name'); ?></label>
                            <input type="text" class="form-control" id="edit_setting_name" name="edit_setting_name"
                                   placeholder="<?= $this->lang->line('application_enter_setting_name'); ?>"
                                   autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="edit_setting_value"><?= $this->lang->line('application_value'); ?></label>
                            <input type="text" class="form-control" id="edit_setting_value" name="edit_setting_value"
                                   placeholder="<?= $this->lang->line('application_enter_setting_value'); ?>"
                                   autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="edit_setting_gateway_id"><?= $this->lang->line('application_gateway_id'); ?></label>
                            <input type="text" class="form-control" id="edit_setting_gateway_id"
                                   name="edit_setting_gateway_id"
                                   placeholder="<?= $this->lang->line('application_payment_gateway'); ?>"
                                   autocomplete="off">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default"
                                data-dismiss="modal"><?= $this->lang->line('application_close'); ?></button>
                        <button type="submit"
                                class="btn btn-primary"><?= $this->lang->line('application_update_changes'); ?></button>
                    </div>

                </form>


            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
<?php endif; ?>

<script type="text/javascript">
    var manageTable;

    $(document).ready(function () {

        $("#paymentgatewaysettingsNav").addClass('active');

        // initialize the datatable
        manageTable = $('#manageTable').DataTable({
            "language": {"url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"},
            "scrollX": true,
            'ajax': '<?php echo base_url('paymentGatewaySettings/fetchSettingData');?>',
            'order': []
        });

        // submit the create from
        $("#createSettingForm").unbind('submit').on('submit', function () {
            var form = $(this);

            // remove the text-danger
            $(".text-danger").remove();

            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: form.serialize(), // /converting the form data into array and sending it to server
                dataType: 'json',
                success: function (response) {

                    manageTable.ajax.reload(null, false);

                    if (response.success === true) {
                        $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">' +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + response.messages +
                            '</div>');


                        // hide the modal
                        $("#addSettingModal").modal('hide');

                        // reset the form
                        $("#createSettingForm")[0].reset();
                        $("#createSettingForm .form-group").removeClass('has-error').removeClass('has-success');

                    } else {

                        if (response.messages instanceof Object) {
                            $.each(response.messages, function (index, value) {
                                var id = $("#" + index);

                                id.closest('.form-group')
                                    .removeClass('has-error')
                                    .removeClass('has-success')
                                    .addClass(value.length > 0 ? 'has-error' : 'has-success');

                                id.after(value);

                            });
                        } else {
                            $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">' +
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>' + response.messages +
                                '</div>');
                        }
                    }
                }, error: e => console.log(e)
            });

            return false;
        });


    });

    function editSetting(id) {
        $.ajax({
            url: '<?php echo base_url('paymentGatewaySettings/fetchSettingDataById');?>/' + id,
            type: 'post',
            dataType: 'json',
            success: function (response) {

                $("#edit_setting_name").val(response.name);
                $("#edit_setting_value").val(response.value);
                $("#edit_setting_gateway_id").val(response.gateway_id);
                $("#edit_active").val(response.status);

                // submit the edit from
                $("#updateSettingForm").unbind('submit').bind('submit', function () {
                    var form = $(this);

                    // remove the text-danger
                    $(".text-danger").remove();

                    $.ajax({
                        url: form.attr('action') + '/' + id,
                        type: form.attr('method'),
                        data: form.serialize(), // /converting the form data into array and sending it to server
                        dataType: 'json',
                        success: function (response) {

                            manageTable.ajax.reload(null, false);

                            if (response.success === true) {
                                $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">' +
                                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                    '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + response.messages +
                                    '</div>');


                                // hide the modal
                                $("#editSettingModal").modal('hide');
                                // reset the form
                                $("#updateSettingForm .form-group").removeClass('has-error').removeClass('has-success');

                            } else {

                                if (response.messages instanceof Object) {
                                    $.each(response.messages, function (index, value) {
                                        var id = $("#" + index);

                                        id.closest('.form-group')
                                            .removeClass('has-error')
                                            .removeClass('has-success')
                                            .addClass(value.length > 0 ? 'has-error' : 'has-success');

                                        id.after(value);

                                    });
                                } else {
                                    $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">' +
                                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                        '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>' + response.messages +
                                        '</div>');
                                }
                            }
                        }
                    });

                    return false;
                });

            }
        });
    }

</script>
