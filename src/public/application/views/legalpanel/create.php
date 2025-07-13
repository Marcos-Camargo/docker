<?php
use App\Libraries\Enum\LegalPanelNotificationType;
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <?php $data['page_now'] = 'legal_panel';
    $data['pageinfo'] = "application_add";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div id="messages2"></div>

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

                <div class="box">
                    <form role="form" id="frmCadastrar" name="frmCadastrar"
                          action="<?php base_url('legalpanel/create') ?>" method="post">
                        <div class="box-body">
                            <div class="row">
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
                            </div>
                            <input type="hidden" class="form-control" id="hdnChamado" name="hdnChamado" value="">
                            <input type="hidden" class="form-control" id="hdnPedido" name="hdnPedido" value="">

                            <div class="col-md-4 col-xs-4" >
                                <label for="group_isadmin"><?= $this->lang->line('application_legal_panel_notification_type'); ?></label>
                                <select class="form-control" id="notification_type"
                                        name="notification_type" <?php echo $read_only; ?>>
                                    <?php
                                    foreach (LegalPanelNotificationType::generateList() as $code => $name) {
                                        ?>
                                        <option value="<?= $code; ?>" <?php echo $legal_panel['notification_type'] == $code ? 'selected="selected"' : ''; ?>><?= $name; ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-4 col-xs-4 notification-type-others <?php echo (form_error('notification_title')) ? 'has-error' : '';  ?>" style="display: none;">
                                <label for="txt_notification_title"><?= $this->lang->line('application_legal_panel_notification_title'); ?> (*)</label>
                                <input type="text" class="form-control" id="txt_notification_title"
                                       name="notification_title" placeholder=""
                                       value="<?php echo set_value('notification_title', $legal_panel['notification_title']) ?>" <?php echo $read_only; ?>>
                                <?php echo '<i style="color:red">'.form_error('notification_title').'</i>';  ?>
                            </div>
                            <div class="col-md-4 col-xs-4 notification-type-others <?php echo (form_error('store_id')) ? 'has-error' : '';  ?> " style="display: none;">
                                <label for="store_id"><?= $this->lang->line('application_store_id'); ?>(*)</label>
                                <input type="text" class="form-control" id="store_id"
                                       name="store_id" placeholder="ID da Loja"
                                       value="<?php echo set_value('store_id', $legal_panel['store_id'] ?? null) ?>" <?php echo $read_only; ?>>
                                <?php echo '<i style="color:red">'.form_error('store_id').'</i>';  ?>
                            </div>
                            <div class="col-md-7 col-xs-7 notification-type-orders <?php echo (form_error('orders_id')) ? 'has-error' : '';  ?> ">
                                <label for="txt_numero_pedido"><?= $this->lang->line('application_purchase_id'); ?> (*)</label>
                                <input type="text" class="form-control" id="txt_numero_pedido" name="orders_id"
                                       placeholder=""
                                       value="<?php echo set_value('orders_id', $legal_panel['orders_id']) ?>" <?php echo $read_only; ?> required>
                                <?php echo '<i style="color:red">'.form_error('orders_id').'</i>';  ?>
                            </div>
                            <div class="col-md-6 col-xs-6 <?php echo (form_error('notification_id')) ? 'has-error' : '';  ?> ">
                                <label for="txt_numero_chamado"><?= $this->lang->line('application_notification_number'); ?></label>
                                <input type="text" class="form-control" id="txt_numero_chamado" name="notification_id"
                                       placeholder=""
                                       value="<?php echo set_value('notification_id', $legal_panel['notification_id']) ?>" <?php echo $read_only; ?>>
                                <?php echo '<i style="color:red">'.form_error('notification_id').'</i>';  ?>
                            </div>

                            <div class="col-md-2 col-xs-2 <?php echo (form_error('balance_debit')) ? 'has-error' : '';  ?>">
                                <label for="txt_saldo"><?= $this->lang->line('application_balance_debit'); ?> (*)</label>
                                <input type="text" class="form-control" id="txt_saldo" required name="balance_debit"
                                       placeholder=""
                                       value="<?php echo set_value('balance_debit', $legal_panel['balance_debit']) ?>" <?php echo $read_only; ?> >
                                <?php echo '<i style="color:red">'.form_error('balance_debit').'</i>';  ?>
                            </div>

                            <div class="col-md-2 col-xs-2 <?php echo (form_error('balance_paid')) ? 'has-error' : '';  ?>">
                                <label for="txt_saldo_quitar"><?= $this->lang->line('application_balance_paid'); ?> (*)</label>
                                <input type="text" class="form-control" id="txt_saldo_quitar" name="balance_paid"
                                       placeholder=""
                                       value="<?php echo set_value('balance_paid', $legal_panel['balance_paid']) ?>"
                                       readonly>
                                <?php echo '<i style="color:red">'.form_error('balance_paid').'</i>';  ?>
                            </div>

                            <div class="col-md-2 col-xs-2 <?php echo (form_error('status')) ? 'has-error' : '';  ?> ">
                                <label for="group_isadmin"><?= $this->lang->line('application_status'); ?></label>
                                <select class="form-control" id="status" name="status" <?php echo $read_only; ?>>
                                    <option value="">~~SELECT~~</option>
                                    <option value="Chamado Aberto">Chamado Aberto</option>
                                    <option value="Chamado Fechado">Chamado Fechado</option>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('status').'</i>';  ?>
                            </div>

                            <?php if (isset($legal_panel['accountable_opening']) && $legal_panel['accountable_opening']){ ?>
                                <div class="col-md-3 col-xs-3 <?php echo (form_error('accountable_opening')) ? 'has-error' : '';  ?> ">
                                    <label for="accountable_opening"><?= $this->lang->line('application_accountable_opening'); ?></label>
                                    <input type="text" class="form-control" id="accountable_opening" name="accountable_opening"
                                           placeholder=""
                                           value="<?php echo set_value('accountable_opening', $legal_panel['accountable_opening']) ?>"
                                           readonly disabled>
                                    <?php echo '<i style="color:red">'.form_error('accountable_opening').'</i>';  ?>
                                </div>
                            <?php
                            }

                            if (isset($legal_panel['accountable_update']) && $legal_panel['accountable_update']){
                            ?>

                                <div class="col-md-3 col-xs-3 <?php echo (form_error('accountable_update')) ? 'has-error' : '';  ?> ">
                                    <label for="accountable_update"><?= $this->lang->line('application_accountable_update'); ?></label>
                                    <input type="text" class="form-control" id="accountable_update" name="accountable_update"
                                           placeholder=""
                                           value="<?php echo set_value('accountable_update', $legal_panel['accountable_update']) ?>"
                                           readonly disabled>
                                </div>
                                <?php echo '<i style="color:red">'.form_error('accountable_update').'</i>';  ?>
                            <?php
                            }
                            ?>

                            <?php if (isset($legal_panel['conciliacao_id'])): ?>
                                <div class="col-md-3 col-xs-3  <?php echo (form_error('conciliacao_id')) ? 'has-error' : '';  ?> ">
                                    <label for="accountable_update">ID <?= $this->lang->line('payment_report_box_title'); ?></label>
                                    <input type="text" class="form-control" id="billet_id" name="billet_id"
                                           value="<?php echo set_value('conciliacao_id', $legal_panel['conciliacao_id']) ?>"
                                           style="display: inline-block"
                                           readonly disabled>
                                </div>
                                <div class="col-md-3 col-xs-3  <?php echo (form_error('conciliacao_id')) ? 'has-error' : '';  ?> ">
                                    <label for="open_billet" style="clear: both;">&nbsp;</label><br/>
                                    <a target="_blank" href="<?php echo base_url('billet/editsellercenter/'.$legal_panel['lote']) ?>"
                                       id="open_billet" class="btn btn-info" style="width: 100%;"><?= $this->lang->line('application_open_payment_report'); ?></a>
                                </div>
                            <?php endif; ?>

                            <div class="col-md-12 col-xs-12 mt-2 <?php echo (form_error('description')) ? 'has-error' : '';  ?> ">
                                <label for="txt_descricao"><?= $this->lang->line('application_description'); ?></label>
                                <textarea class="form-control" id="txt_descricao" name="description"
                                          placeholder="<?= $this->lang->line('application_description'); ?>" <?php echo $read_only; ?>> <?php echo set_value('description', $legal_panel['description']) ?> </textarea>
                                <?php echo '<i style="color:red">'.form_error('description').'</i>';  ?>
                            </div>


                        </div>

                        <div class="col-md-12 col-xs-12  <?php echo (form_error('document_upload[]')) ? 'has-error' : '';  ?> ">
                            <div class="box-body" id="divUpload" name="divUpload" style="display:block">
                                <!-- ?php echo validation_errors(); ?  -->
                                <div class="row">
                                    <div class="form-group col-md-12 col-xs-12">
                                        <label for="document_upload"><?= $this->lang->line('messages_upload_file'); ?></label>
                                        <div class="kv-avatar">
                                            <div class="file-loading">
                                                <input type="file" id="document_upload" name="document_upload[]"
                                                       value="" multiple <?php echo $read_only; ?>>
                                            </div>
                                        </div>
                                        <input type="hidden" name="attachment" id="attachment"
                                               value="<?php echo set_value('attachment', $legal_panel['attachment']) ?>"/>
                                        <?php echo '<i style="color:red">'.form_error('document_upload[]').'</i>';  ?>
                                    </div>
                                </div> <!-- row -->
                            </div> <!-- box body -->
                        </div>

                        <div class="box-footer">
                            <button type="submit" id="btnSave" name="btnSave" class="btn btn-primary col-md-2"><?= $this->lang->line('application_save'); ?></button>
                            <button type="button" id="btnVoltar" name="btnVoltar" class="btn btn-warning col-md-2"><?= $this->lang->line('application_back'); ?></button>
                        </div>


                    </form>

                    <?php
                    if (isset($legal_panel['id']) && $legal_panel['id']){
                    ?>
                        <div id="divTabelaPedidos" name="divTabelaPedidos" style="display:block">
                            <div class="box">
                                <div class="box-header">
                                    <h3 class="box-title">Conciliações já debitadas</h3>
                                </div>
                                <div class="box-body">

                                    <table id="tabelaPedidosAdd" name="tabelaPedidosAdd"
                                           class="table table-bordered table-striped">
                                        <thead>
                                        <tr>
                                            <th>Id Conciliação</th>
                                            <th>Data Conciliação</th>
                                            <th>Ciclo Conciliação</th>
                                            <th>Responsável Pela conciliação</th>
                                            <th>Valor Debitado</th>
                                        </tr>
                                        </thead>

                                    </table>

                                </div>
                            </div>

                        </div>
                    <?php
                    }
                    ?>
                    <!-- /.box -->
                </div>
                <!-- col-md-12 -->
            </div>
            <!-- /.row -->


    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>" type="text/javascript"></script>

<script type="text/javascript">
    var manageTable;
    var base_url = "<?php echo base_url(); ?>";
    var readonly = '<?php echo $read_only; ?>';

    function changeNotificationType() {
        if ($("#notification_type").val() == '<?=LegalPanelNotificationType::ORDER;?>') {
            $('.notification-type-others').hide();
            $('.notification-type-orders').show();
            $('.notification-type-orders').find('input').prop('required', true);
        } else {
            $('.notification-type-others').show();
            $('.notification-type-orders').hide();
            $('.notification-type-orders').find('input').prop('required', false);
        }
    }

    changeNotificationType();

    $(document).ready(function () {

        if (readonly) {
            $('#btnSave').hide();
        }

        $("#paraMktPlaceNav").addClass('active menu-open');
        $("#paineljuridicoNav").addClass('active');

        <?php
        if (isset($legal_panel['id']) && $legal_panel['id']){
            ?>

            // initialize the datatable
            manageTable = $('#tabelaPedidosAdd').DataTable({
                "language": {
                    "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang',
                },
                "processing": true,
                "serverSide": false,
                "serverMethod": "post",
                'ajax': {
                    url: base_url + 'billet/fetchlegalpanelsellercenterconciliacao/<?php echo $legal_panel['id'] ?: null; ?>',
                    'data': function(data){
                    }
                },
                "order": [[ 0, "desc" ]],
                "columns": [
                    {"data": "id"},
                    {"data": "data_criacao"},
                    {"data": "ciclo_conciliacao"},
                    {"data": "usuario"},
                    {"data": "valor_repasse"},
                ]
            });

        <?php
        }
        ?>

        $("#txt_saldo").change(function () {
            $("#txt_saldo_quitar").val($("#txt_saldo").val());
        });


        $("#notification_type").change(function () {
            changeNotificationType();
        });

        $("#btnVoltar").click(function () {
            window.location.assign(base_url.concat("legalpanel/"));
        });

        $("#status").val("<?php if ($legal_panel['status']) {
            echo $legal_panel['status'];
        }?>");

        $('#txt_saldo').inputmask('numeric', {
            autoGroup: true,
            groupSeparator: '.',
            digits: 2,
            radixPoint: ",",
            digitsOptiona: false,
            allowMinus: true,
            prefix: 'R$ ',
            placeholder: '',
            autoUnmask: true,
            rightAlign: false,
            removeMaskOnSubmit: true,
            unmaskAsNumber: true,
            require: true,
            alias: 'numeric',
            digitsOptional: false,

        });

        $('#txt_saldo_quitar').inputmask('numeric', {
            radixPoint: ',',
            autoUnmask: true,
            rightAlign: false,
            unmaskAsNumber: true,
            removeMaskOnSubmit: true,
            digits: 2,
            require: true,
            alias: 'numeric',
            groupSeparator: '.',
            autoGroup: true,
            digitsOptional: false,
            allowMinus: true,
            prefix: 'R$ ',
        });

        var btnCust = '<button type="button" class="btn btn-secondary" title="Add picture tags" ' +
            'onclick="alert(\'Call your custom code here.\')">' +
            '<i class="glyphicon glyphicon-tag"></i>' +
            '</button>';

        var uploadUrl = base_url.concat("legalpanel/fileUpload");
        var deleteUrl = base_url.concat("legalpanel/deleteFile");

        $.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            data: {
                token: $("#attachment").val(),
            },
            url: base_url + "legalpanel/getFiles",
            dataType: "json",
            async: true,
            success: function (response) {

                $("#document_upload").fileinput({
                    overwriteInitial: false,
                    language: 'pt-BR',
                    maxFileSize: 15000,
                    uploadUrl: uploadUrl,
                    showClose: false,
                    showCaption: false,
                    maxFileCount: 5,
                    uploadExtraData: {
                        uploadToken: $("#attachment").val(), // for access control / security
                    },
                    browseLabel: '',
                    removeLabel: '',
                    browseIcon: '<i class="glyphicon glyphicon-folder-open"></i>',
                    removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
                    removeTitle: 'Cancel or reset changes',
                    elErrorContainer: '#kv-avatar-errors-1',
                    msgErrorClass: 'alert alert-block alert-danger',
                    layoutTemplates: {main2: '{preview} {remove} {browse}'},
                    initialPreviewAsData: true,
                    initialPreview: response.ln1,
                    initialPreviewConfig: response.ln2,
                    allowedFileExtensions: ["xls", "pdf", "xml"],
                    deleteUrl: deleteUrl
                }).on('filesorted', function (event, params) {
                    console.log('File sorted params', params);
                }).on('fileuploaded', function (event, previewId, index, fileId) {
                    console.log('File Uploaded', 'ID: ' + fileId + ', Thumb ID: ' + previewId);
                }).on('fileuploaderror', function (event, data, msg) {
                    AlertSweet.fire({
                        icon: 'error',
                        title: 'Erro no upload do arquivo.<br>Garanta que seja um arquivo do tipo "xls", "pdf", "xml"!<br>Faça o ajuste e tente novamente!'
                    });
                    console.log('File Upload Error', 'ID: ' + data.fileId + ', Thumb ID: ' + data.previewId);
                })
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });


    });


</script>