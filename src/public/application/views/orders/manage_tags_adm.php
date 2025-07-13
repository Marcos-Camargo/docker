<!--
SW Serviços de Informática 2019

Listar Pedidos

Obs:
cada usuario so pode ver pedidos da sua empresa.
Agencias podem ver todos os pedidos das suas empresas
Admin pode ver todas as empresas e agencias

-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">

            <div class="col-md-12 col-xs-12">

                <div id="messages"></div>

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
                    <div class="box-body">
                        <!-- table id="manageTable" class="table table-bordered table-striped" -->
                        <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                            <thead>
                            <tr>
                                <th>PLP</th>
                                <?=$plpAutomatic ? '' : '<th>Status</th>'?>
                                <th><?=$this->lang->line('application_store');?></th>
                                <th><?=$this->lang->line('application_company');?></th>
                                <th><?=$this->lang->line('application_date');?></th>
                                <th><?=$this->lang->line('application_action');?></th>
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

<div class="modal fade" tabindex="-1" role="dialog" id="viewPlp">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="<?=base_url('orders/manage_tags_adm')?>" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Solicitação de PLP</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h4 class="text-center store"></h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <h4 class="text-center company"></h4>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-bordered" id="table-view-plp" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                                <thead>
                                <th>Pedido</th>
                                <th>Código de Rastreio</th>
                                <th>Ação</th>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label>Número da PLP</label>
                            <input type="number" name="number_plp_edit" class="form-control" autocomplete="off" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-primary col-md-3" data-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-success col-md-3">Salvar</button>
                </div>
                <input type="hidden" name="number_plp" required>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="delFreight">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Excluir Etiqueta Pedido</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <h4 class="text-center text-order-id">Tem certeza que deseja excluir a etiqueta do pedido <strong></strong>?</h4>
                        <h5 class="text-center text-danger">Ao excluir, todas as etiquetas vinculadas a esse pedido serão excluídas e o status voltará para Aguardando Seller Emitir Etiqueta.</h5>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-primary col-md-3" data-dismiss="modal">Fechar</button>
                <button type="submit" class="btn btn-danger col-md-3 btnDelFreight" order-id="">Excluir</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
    var manageTable;
    var tagsTable;
    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function() {

        $("#mainProcessesNav").addClass('active');
        $("#manageOrdersTagsAdmNav").addClass('active');

        getTags();

        $('input[type="checkbox"].minimal').iCheck({
            checkboxClass: 'icheckbox_minimal-blue',
            radioClass   : 'iradio_minimal-blue'
        });

    });

    $(document).on('click', '.view-request', function () {
        const number_plp = $(this).attr('number-plp');
        const store = $(this).closest('tr').find('td:eq(1)').text();
        const company = $(this).closest('tr').find('td:eq(2)').text();

        getListPlp(number_plp, true, store, company);
    });

    $(document).on('click', '.del_freight', function () {
        const order_id = $(this).attr('order-id');
        const number_plp = $(this).attr('number-plp');

        $('#delFreight h4.text-order-id strong').html(order_id);
        $('#delFreight .btnDelFreight').attr('order-id', order_id);
        $('#delFreight .btnDelFreight').attr('number-plp', number_plp);
        $('#delFreight').modal();
    });

    $('#delFreight').on('hidden.bs.modal', function(e){
        $("body").addClass("modal-open");
    });

    $(document).on('click', '.btnDelFreight', function () {
        const url = "<?=base_url('orders/removeFreightOrder')?>";
        const order_id = $(this).attr('order-id');
        const number_plp = $(this).attr('number-plp');

        $.ajax({
            url,
            type: "POST",
            data: { order_id },
            dataType: 'json',
            success: response => {

                if(!response.success) {
                    AlertSweet.fire({
                        icon: 'error',
                        title: response.message
                    });
                    return false;
                }

                AlertSweet.fire({
                    icon: 'success',
                    title: response.message
                });

                getListPlp(number_plp);

                setTimeout(() => {
                    if ($('#table-view-plp tbody td.dataTables_empty').length) $('#viewPlp').modal('hide');
                    getTags();
                }, 1250);

                $('#delFreight').modal('hide');
            }
        });
    });

    const getListPlp = (number_plp, openModal = false, store = null, company = null) => {
        const url = "<?=base_url('orders/viewDataPLP')?>";
        $.ajax({
            url,
            type: "POST",
            data: { number_plp },
            dataType: 'json',
            success: response => {

                if (openModal) $('#viewPlp').modal();

                if(manageTable !== undefined)
                    manageTable.destroy();

                $('#viewPlp #table-view-plp tbody').empty();

                for (let i = 0; i < response.length; i++) {
                    $('#viewPlp #table-view-plp tbody').append(`<tr><td>${response[i].order_id}</td><td>${response[i].codigo_rastreio}</td><td><button type="button" class="btn btn-sm btn-danger del_freight" number-plp='${number_plp}' order-id='${response[i].order_id}'><i class="fa fa-trash"></i></button></td></tr>`);
                }

                setTimeout(() => {
                    manageTable = $('#table-view-plp').DataTable( {
                        "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
                        "processing": true,
                        "scrollX": true,
                        "sortable": true
                    });
                }, 500);

                if (store !== null) $('#viewPlp h4.store').html('<strong>Loja:</strong> ' + store);
                if (company !== null) $('#viewPlp h4.company').html('<strong>Empresa:</strong> ' + company);
                $('#viewPlp input[name="number_plp"]').val(number_plp);
            }
        });
    }

    const getTags = () => {

        if(tagsTable !== undefined)
            tagsTable.destroy();

        setTimeout(() => {
            tagsTable = $('#manageTable').DataTable({
                "language": {"url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
                "processing": true,
                "serverSide": true,
                "scrollX": true,
                "sortable": true,
                "serverMethod": "post",
                "ajax": $.fn.dataTable.pipeline({
                    url: base_url + 'orders/fetchPLPRequestData',
                    pages: 2 // number of pages to cache
                }),
                'createdRow': function (row, data, dataIndex) {
                    // $(row).find('td:eq(1)').attr('data-order', $(row).find('td:eq(1)').text() == "Pendente" ? 0 : 1);
                },
                "order": [[1, 'asc']]
            });
        }, 500);
    }

</script>
