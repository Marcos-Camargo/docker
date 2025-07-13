<!--
SW Serviços de Informática 2019

Listar Produtos

Obs:
cada usuario so pode ver produtos da sua empresa.
Agencias podem ver todos os produtos das suas empresas
Admin pode ver produtos detodas as empresas e agencias

-->
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
                <div id="showActions">
                    <span class="pull-right">&nbsp</span>
                    <a class="pull-right btn btn-primary" id="buttonCollapseFilter" style="margin-right: 5px;" role="button" data-toggle="collapse" href="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter" onclick="changeFilter()">Ocultar Filtros</a>
                </div>
                <br />

                <!-- <div style="background-color: #d3d3d3;"> -->
                <div class="panel-collapse collapse in" id="collapseFilter">

                    <h4>Filtros Conecta Lá</h4>
                    <div class="input-group col-md-3" style="display: inline-table;">
                        <div class="input-group">
                            <input type="search" id="buscar_categoria" class="form-control" placeholder="Nome da categoria" aria-label="Search" aria-describedby="basic-addon1" onchange="personalizedSearch()">
                            <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                        </div>
                    </div>
                    <div class="input-group col-md-3" style="display: inline-table;">
                        <div class="input-group">
                            <input type="search" id="buscar_por_loja" class="form-control" placeholder="Nome da loja" aria-label="Search" aria-describedby="basic-addon1" onchange="personalizedSearch()">
                            <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                        </div>
                    </div>
                    <div class="input-group col-md-2" style="display: inline-table;">
                        <div class="input-group">
                            <input type="search" id="busca_sku" class="form-control" placeholder="Código SKU" aria-label="Search" aria-describedby="basic-addon1" onchange="personalizedSearch()">
                            <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                        </div>
                    </div>
                    <div class="input-group col-md-3" style="display: inline-table;">
                        <div class="input-group">
                            <input type="search" id="busca_produto" class="form-control" placeholder="Nome do Produto" aria-label="Search" aria-describedby="basic-addon1" onchange="personalizedSearch()">
                            <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                        </div>
                    </div>
                    
                    
                    <br />
                </div>

                <br />
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"></h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                            <!-- <table id="manageTable" class="table"> -->
                            <thead>
                                <tr>
                                    <th><?= $this->lang->line('application_category_name'); ?></th>
                                    <th><?= $this->lang->line('application_omnilogic_date_sent'); ?></th>
                                    <th><?= $this->lang->line('application_store_name'); ?></th>
                                    <th><?= $this->lang->line('application_seller_sku'); ?></th>
                                    <th><?= $this->lang->line('application_product_name'); ?></th>
                                    <th><?= $this->lang->line('application_product_id'); ?></th>
                                    <th><?= $this->lang->line('application_action'); ?></th>
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


<?php if (in_array('deleteProduct', $user_permission)) : ?>
    <!-- remove brand modal -->
    <div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?= $this->lang->line('application_delete_product'); ?><span id="deleteproductname"></span></h4>
                </div>

                <form role="form" action="<?php echo base_url('products/remove') ?>" method="post" id="removeForm">
                    <div class="modal-body">
                        <p><?= $this->lang->line('messages_delete_message_confirm'); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?= $this->lang->line('application_close'); ?></button>
                        <button type="submit" class="btn btn-primary"><?= $this->lang->line('application_confirm'); ?></button>
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

    $(document).ready(function() {

        $("#mainProcessesNav").addClass('active');
        $("#sentOmnilogic").addClass('active');
        $("#hide").click(function() {
            $("#filterModal").hide();
            $("#showActions").show();
        });
        $("#show").click(function() {
            $("#filterModal").show();
            $("#showActions").hide();
        });

        let sku = $('#busca_sku').val();
        let product = $('#busca_produto').val();
        let buscar_categoria = $('#buscar_categoria').val();
        let buscar_por_loja = $('#buscar_por_loja').val();
        let situation = $('#busca_situacao').val();
        let integration = $('#busca_integracao').val();
        let estoque = $('#busca_estoque').val();

        manageTable = $('#manageTable').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?= ucfirst($this->input->cookie('swlanguage')) ?>.lang'
            },
            "processing": true,
            "serverSide": true,
            "sortable": true,
            "scrollX": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline({
                url: base_url + 'products/fetchProductDatasentOmnilogic',
                data: {
                    sku: sku,
                    product: product,
                    buscar_categoria: buscar_categoria,
                    buscar_por_loja: buscar_por_loja
                },
                pages: 2, // number of pages to cache
            }),
            "createdRow": function(row, data, dataIndex) {
                $(row).find('td:eq(3)').addClass('d-flex align-items-center');
            },
            "initComplete": function(settings, json) {
                $('#manageTable [data-toggle="tootip"]').tooltip();
            }
        });

        $('#manageTable').on('draw.dt', function() {
            $('#manageTable [data-toggle="tootip"]').tooltip();
        });

        $('body').tooltip({
            selector: '[data-toggle="tooltip"]'
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            // alert('oi');
            $($.fn.dataTable.tables(true)).DataTable()
                .columns.adjust()
                .responsive.recalc();
        });
        $('input[type="checkbox"].minimal').iCheck({
            checkboxClass: 'icheckbox_minimal-blue',
            radioClass: 'iradio_minimal-blue'
        });
    });

    // remove functions 
    function removeFunc(id, name) {
        if (id) {
            document.getElementById("deleteproductname").innerHTML = ': ' + name;
            $("#removeForm").on('submit', function() {

                var form = $(this);

                // remove the text-danger
                $(".text-danger").remove();

                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),
                    data: {
                        product_id: id
                    },
                    dataType: 'json',
                    success: function(response) {

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

    function changeQty(id, old_qty, new_qty) {
        $.ajax({
            url: base_url + "products/updateQty",
            type: 'POST',
            data: {
                id: id,
                old_qty: old_qty,
                new_qty: new_qty
            },
            async: true,
            dataType: 'json'
        });
    }

    function changePrice(id, old_price, new_price) {
        $.ajax({
            url: base_url + "products/updatePrice",
            type: 'POST',
            data: {
                id: id,
                old_price: old_price,
                new_price: new_price
            },
            async: true,
            dataType: 'json'
        });
        var priceFloat = parseFloat(new_price);
        var priceFormated = priceFloat.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });

        return priceFormated;
    }

    function formatPrice(value) {
        return value.replace(/[^0-9.]/g, "");
    }

    function personalizedSearch() {
        let sku = $('#busca_sku').val();
        let product = $('#busca_produto').val();
        let buscar_categoria = $('#buscar_categoria').val();
        let buscar_por_loja = $('#buscar_por_loja').val();
        console.log({
                    sku: sku,
                    product: product,
                    buscar_categoria: buscar_categoria,
                    buscar_por_loja: buscar_por_loja,
                });
        manageTable.destroy();

        manageTable = $('#manageTable').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?= ucfirst($this->input->cookie('swlanguage')) ?>.lang'
            },
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "sortable": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline({
                url: base_url + 'products/fetchProductDatasentOmnilogic',
                data: {
                    sku: sku,
                    product: product,
                    buscar_categoria: buscar_categoria,
                    buscar_por_loja: buscar_por_loja,
                },
                pages: 2, // number of pages to cache
            })
        });
    }

    function clearFilters() {
        $('#busca_sku').val('');
        $('#busca_produto').val('');
        $('#buscar_categoria').val('');
        $('#buscar_por_loja').val('0');
        $('#busca_situacao').val('0');
        $('#busca_integracao').val('999');
        $('#busca_estoque').val('0');

        personalizedSearch();
    }

    function changeFilter() {
        let text = document.getElementById('buttonCollapseFilter').innerHTML;
        if (text == 'Ocultar Filtros') {
            document.getElementById('buttonCollapseFilter').innerHTML = 'Exibir Filtros';
        } else {
            document.getElementById('buttonCollapseFilter').innerHTML = 'Ocultar Filtros';
        }
    }
</script>