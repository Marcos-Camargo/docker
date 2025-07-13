<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php
    $data['pageinfo'] = "application_setting_up_return_chargeback_rules";
    $data['page_now'] = 'application_setting_up_return_chargeback_rules';
    $this->load->view('templates/content_header', $data);
    ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div id="messages"></div>

                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span>
                        </button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span>
                        </button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <?php if (in_array('createSettingChargebackRule', $user_permission)): ?>
                    <button class="btn btn-primary" data-toggle="modal"
                            data-target="#addModal"><?= lang('application_create_rule'); ?></button>
                    <br/> <br/>
                <?php endif; ?>

                <div class="box">
                    <div class="box-body">
                        <table id="manageTable" class="table table-bordered table-striped" style="width: 100%;">
                            <thead>
                            <tr>
                                <?php
                                foreach ($list_index_order as $key => $label) {
                                    ?>
                                    <th><?= lang($label); ?></th>
                                    <?php
                                }
                                ?>
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

<?php if (in_array('createSettingChargebackRule', $user_permission)): ?>
    <!-- create Setting modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="addModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><?= lang('application_create_rule'); ?></h4>
                </div>

                <form role="form" action="<?php echo $route_save; ?>" method="post" id="createForm">

                    <?php
                    $inputsDisabled = false;
                    include ('_form.php');
                    ?>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?= $this->lang->line('application_close'); ?>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <?= $this->lang->line('application_save'); ?>
                        </button>
                    </div>

                </form>

            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
<?php endif; ?>

<?php if(in_array('updateSettingChargebackRule', $user_permission)): ?>
    <!-- edit Setting modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="editModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_edit_rule');?></h4>
                </div>

                <form role="form" action="<?php echo $route_update; ?>" method="post" id="updateForm">

                    <?php
                    $inputsDisabled = false;
                    include ('_form.php');
                    ?>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?= $this->lang->line('application_close'); ?>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <?= $this->lang->line('application_save'); ?>
                        </button>
                    </div>

                </form>

            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
<?php endif; ?>

<?php if(in_array('viewSettingChargebackRule', $user_permission)): ?>
    <!-- edit Setting modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="viewModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_view_rule');?></h4>
                </div>

                <div class="view_content">
                    <?php
                    $inputsDisabled = true;
                    include ('_form.php');
                    ?>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <?= $this->lang->line('application_close'); ?>
                    </button>
                </div>

            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
<?php endif; ?>

<?php if (in_array('deleteSettingChargebackRule', $user_permission)): ?>
    <!-- remove brand modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">
                        <?= $this->lang->line('application_delete_rule'); ?>
                        <span id="deleteSetting"></span>
                    </h4>
                </div>

                <form role="form" action="<?php echo $route_delete_item; ?>" method="post" id="removeForm">
                    <div class="modal-body">
                        <p><?= $this->lang->line('messages_delete_message_confirm'); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default"
                                data-dismiss="modal"><?= $this->lang->line('application_close'); ?></button>
                        <button type="submit"
                                class="btn btn-primary"><?= $this->lang->line('application_confirm'); ?></button>
                    </div>
                </form>


            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
<?php endif; ?>
<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
    var manageTable;
    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function () {

        $("#paraMktPlaceNav").addClass('active');
        $("#settingChargebackRule").addClass('active');

        $('#marketplace_int_to').select2();
        $('#rule_full_refund_inside_cicle').select2();
        $('#rule_full_refund_outside_cicle').select2();
        $('#rule_partial_refund_inside_cicle').select2();
        $('#rule_partial_refund_outside_cicle').select2();

        manageTable = $('#manageTable').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang',
                "searchPlaceholder": "Marketplace"
            },
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            'ajax': '<?php echo $route_ajax_data; ?>',
            "order": [[0, "desc"]],
            "columns": [
                <?php
                foreach ($list_index_order as $key => $label){
                ?>
                {"data": "<?=$key;?>"},
                <?php
                }
                ?>
            ],
            fnServerParams: function (data) {
                data['order'].forEach(function (items, index) {
                    data['order'][index]['column'] = data['columns'][items.column]['data'];
                });
            },
        });

        $("#createForm").unbind('submit').on('submit', function () {
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
                        $("#addModal").modal('hide');

                        // reset the form
                        $("#createForm")[0].reset();
                        $("#createForm .form-group").removeClass('has-error').removeClass('has-success');

                        $("#createForm").find('#marketplace_int_to').select2("destroy").select2();
                        $("#createForm").find('#rule_full_refund_inside_cicle').select2("destroy").select2();
                        $("#createForm").find('#rule_full_refund_outside_cicle').select2("destroy").select2();
                        $("#createForm").find('#rule_partial_refund_inside_cicle').select2("destroy").select2();
                        $("#createForm").find('#rule_partial_refund_outside_cicle').select2("destroy").select2();

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

    function edit(id) {

        $("#editModal").find('#updateForm').hide();
        $("#editModal").find('.marketplace_int_to').val('');
        $("#editModal").find('.rule_full_refund_inside_cicle').val('');
        $("#editModal").find('.rule_full_refund_outside_cicle').val('');
        $("#editModal").find('.rule_partial_refund_inside_cicle').val('');
        $("#editModal").find('.rule_partial_refund_outside_cicle').val('');

        $.ajax({
            url: '<?php echo $route_get_item; ?>/'+id,
            type: 'post',
            dataType: 'json',
            success:function(response) {

                $("#editModal").find('#updateForm').slideDown();
                $("#editModal").find('.marketplace_int_to').val(response.marketplace_int_to).select2();
                $("#editModal").find('.rule_full_refund_inside_cicle').val(response.rule_full_refund_inside_cicle).select2();
                $("#editModal").find('.rule_full_refund_outside_cicle').val(response.rule_full_refund_outside_cicle).select2();
                $("#editModal").find('.rule_partial_refund_inside_cicle').val(response.rule_partial_refund_inside_cicle).select2();
                $("#editModal").find('.rule_partial_refund_outside_cicle').val(response.rule_partial_refund_outside_cicle).select2();

                // submit the edit from
                $("#updateForm").unbind('submit').bind('submit', function() {
                    var form = $(this);

                    // remove the text-danger
                    $(".text-danger").remove();

                    $.ajax({
                        url: form.attr('action') + '/' + id,
                        type: form.attr('method'),
                        data: form.serialize(), // /converting the form data into array and sending it to server
                        dataType: 'json',
                        success:function(response) {

                            manageTable.ajax.reload(null, false);

                            if(response.success === true) {
                                $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                                    '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
                                    '</div>');

                                // hide the modal
                                $("#editModal").modal('hide');

                                // reset the form
                                $("#updateForm .form-group").removeClass('has-error').removeClass('has-success');

                            } else {

                                if(response.messages instanceof Object) {
                                    $.each(response.messages, function(index, value) {

                                        var id = $("#updateForm").find("."+index);

                                        id.closest('.form-group')
                                            .removeClass('has-error')
                                            .removeClass('has-success')
                                            .addClass(value.length > 0 ? 'has-error' : 'has-success');

                                        id.after(value);

                                    });
                                } else {
                                    $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                                        '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
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

    function view(id) {

        $("#viewModal").find('.view_content').hide();
        $("#viewModal").find('.marketplace_int_to').val('');
        $("#viewModal").find('.rule_full_refund_inside_cicle').val('');
        $("#viewModal").find('.rule_full_refund_outside_cicle').val('');
        $("#viewModal").find('.rule_partial_refund_inside_cicle').val('');
        $("#viewModal").find('.rule_partial_refund_outside_cicle').val('');

        $.ajax({
            url: '<?php echo $route_get_item; ?>/'+id,
            type: 'post',
            dataType: 'json',
            success:function(response) {

                $("#viewModal").find('.view_content').slideDown();
                $("#viewModal").find('.marketplace_int_to').val(response.marketplace_int_to).select2();
                $("#viewModal").find('.rule_full_refund_inside_cicle').val(response.rule_full_refund_inside_cicle).select2();
                $("#viewModal").find('.rule_full_refund_outside_cicle').val(response.rule_full_refund_outside_cicle).select2();
                $("#viewModal").find('.rule_partial_refund_inside_cicle').val(response.rule_partial_refund_inside_cicle).select2();
                $("#viewModal").find('.rule_partial_refund_outside_cicle').val(response.rule_partial_refund_outside_cicle).select2();

            }

        });
    }

    function removeFunc(id) {

        if (id) {

            $("#removeForm").on('submit', function () {

                var form = $(this);

                // remove the text-danger
                $(".text-danger").remove();

                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: {id: id},
                    dataType: 'json',
                    success: function (response) {

                        manageTable.ajax.reload(null, false);

                        if (response.success === true) {
                            $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">' +
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + response.messages +
                                '</div>');

                            // hide the modal
                            $("#removeModal").modal('hide');

                        } else {

                            $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">' +
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>' + response.messages +
                                '</div>');
                        }
                    }
                });

                return false;
            });
        }
    }


</script>
