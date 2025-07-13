<!--
Listar Pedidos Devolvidos

Observações:
- Cada usuário só pode ver pedidos da sua empresa;
- Agências podem ver todos os pedidos das suas empresas;
- Administradores podem ver todas as empresas e agências.
-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <style>
        .filters {
            position: relative;
            top: 30px;
            display: flex;
            justify-content: center;
            width: 70%;
            margin: auto;
        }

        .normal {
            font-weight: normal;
        }
        
        .bootstrap-select .dropdown-toggle .filter-option {
            background-color: white !important;
        }

        .bootstrap-select .dropdown-menu li a {
            border: 1px solid gray;
        }
    </style>
        
    <?php 
    $data['pageinfo'] = "application_return_order_short";
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
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <div class="box box-primary mt-2">
                    <div class="box-body">
                        <?php
                        if (in_array('createSettingChargebackRule', $user_permission) || in_array('updateSettingChargebackRule', $user_permission) || in_array('viewSettingChargebackRule', $user_permission) || in_array('deleteSettingChargebackRule', $user_permission)){ ?>
                            <a class="pull-left btn btn-primary" href="<?php echo base_url('settingsReturnChargeBack/index') ?>"><?=lang('application_configure_chargeback_rule')?></a>
                        <?php
                        }
                        ?>
                        <a class="pull-right btn btn-primary" href="<?php echo base_url('export/returnproductxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_data_export')?></a>
                    </div>
                </div>

                <div class="box box-primary">
                    <div class="box-body">
                        <div class="row">

                            <!-- Número do pedido Conecta -->
                            <div class="col-md-3">
                                <label for="buscapedidoconecta" class="normal"><?= $this->lang->line('application_order_number') ?></label>
                                <div class="input-group">
                                    <input type="search" id="buscapedidoconecta" onchange="buscapedido()" class="form-control" placeholder="<?= $this->lang->line('application_purchase_id') ?>" aria-label="Search" aria-describedby="basic-addon1">
                                    <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                                </div>
                            </div>

                            <!-- Número do pedido Marketplace -->
                            <div class="col-md-3">
                                <label for="buscapedidomkt" class="normal"><?= $this->lang->line('application_order_marketplace') ?></label>
                                <div class="input-group">
                                    <input type="search" id="buscapedidomkt" onchange="buscapedido()" class="form-control" placeholder="<?= $this->lang->line('application_order_marketplace') ?>" aria-label="Search" aria-describedby="basic-addon1">
                                    <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                                </div>
                            </div>

                            <!-- Loja -->
                            <div class="col-md-3">
                                <div class="">
                                    <label for="buscalojas" class="normal"><?= $this->lang->line('application_search_for_store') ?></label>
                                    <select class="form-control selectpicker show-tick" id="buscalojas" name ="loja[]" onchange="buscapedido()" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">

                                        <?php foreach ($stores_filter as $store_filter) { ?>
                                            <option value="<?= $store_filter['id'] ?>"><?= $store_filter['name'] ?></option>
                                        <?php } ?>

                                    </select>
                                </div>
                            </div>

                            <!-- Transportadora -->
                            <div class="col-md-3">
                                <div class="input-group col-md-12">
                                    <label for="buscaentrega" class="normal"><?= $this->lang->line('application_shipping_company') ?></label>
                                    <select class="form-control" id="buscaentrega" onchange="buscapedido()">
                                        <option value=""><?=$this->lang->line('application_all');?></option>
                                        <option value="CORREIOS"><?= $this->lang->line('application_post_offices') ?></option>
                                        <option value="TRANSPORTADORA"><?= $this->lang->line('application_ship_company') ?></option>
                                    </select>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-3">
                                <label for="buscastatus" class="normal"><?= $this->lang->line('application_order_status') ?></label>
                                <select class="form-control select2" id="buscastatus" onchange="buscapedido()">
                                    <option value=""><?=$this->lang->line('application_all');?></option>
                                    <option value="ACONTRATAR">A contratar</option>
                                    <option value="COLETADO">Coletado</option>
                                    <option value="DEVOLVIDO">Devolvido</option>
                                    <option value="CANCELADO">Cancelado</option>
                                </select>
                            </div>

                            <!-- Data de envio para contratação -->
                            <div class="col-md-3">
                                <label for="buscadataenviocontrata" class="normal"><?= $this->lang->line('application_hire_date_product_return') ?></label>
                                <input type="text" class="form-control pull-right datepicker" id="buscadataenviocontrata" name="buscadataenviocontrata" placeholder="<?= $this->lang->line('application_hire_date_product_return') ?>" onchange="buscapedido()" autocomplete="off">
                            </div>

                            <!-- Data de contratação -->
                            <div class="col-md-3">
                                <label for="buscadatacontratada" class="normal"><?=$this->lang->line('application_hire_date_return');?></label>
                                <input type="text" class="form-control pull-right datepicker" id="buscadatacontratada" name="buscadatacontratada" placeholder="<?= $this->lang->line('application_hire_date_return') ?>" onchange="buscapedido()" autocomplete="off">
                            </div>

                            <!-- Data de devolução -->
                            <div class="col-md-3">
                                <label for="buscadataenvio" class="normal"><?=$this->lang->line('application_date_product_return');?></label>
                                <input type="text" class="form-control pull-right datepicker" id="buscadataenvio" name="buscadataenvio" placeholder="<?= $this->lang->line('application_date_product_return') ?>" onchange="buscapedido()" autocomplete="off">
                            </div>

                            <!-- Limpa os filtros -->
                            <div class="col-md-3 pull-right text-right">
                                <label  class="normal" style="display: block;">&nbsp; </label>
                                <button type="button" onclick="clearFilters()" class=" btn btn-primary"> <i class="fa fa-eraser"></i> <?= $this->lang->line('application_clear') ?> </button>
                            </div>
                        </div>
                    </div>
                </div>
              
                <div class="row"></div>
                
                <div class="box box-primary">
                    <div class="box-body">
                        <!-- table id="manageTable" class="table table-bordered table-striped" -->
                        <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                            <thead>
                                <tr>
                                    <!-- Id -->
                                    <th data-toggle="tooltip" data-placement="top" title="Devolução" data-container="body">Devolução</th>

                                    <!-- Pedido Conecta -->
                                    <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_conecta_orders') ?>" data-container="body"><?=$this->lang->line('application_order');?></th>

                                    <!-- Pedido Marketplace -->
                                    <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_order_marketplace') ?>" data-container="body"><?=$this->lang->line('application_order_marketplace');?></th>

                                    <!-- Número da nota -->
                                    <!-- th data-toggle="tooltip" data-placement="top" title="<?=$this->lang->line('application_nfe_num') ?>" data-container="body"><?=$this->lang->line('application_nfe_num');?></th -->

                                    <!-- Nota fiscal em PDF -->
                                    <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_nfe_file') ?>" data-container="body"><?=$this->lang->line('application_nfe_file');?></th>

                                    <!-- Transportadora -->
                                    <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_shipping_company') ?>" data-container="body"><?=$this->lang->line('application_shipping_company');?></th>

                                    <!-- Valor total -->
                                    <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_return_product_total_value') ?>" data-container="body"><?=$this->lang->line('application_return_product_total_value');?></th>

                                    <!-- Loja -->
                                    <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_store') ?>" data-container="body"><?=$this->lang->line('application_store');?></th>

                                    <!-- Item devolvido -->
                                    <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_returned_item') ?>" data-container="body"><?=$this->lang->line('application_returned_item_short');?></th>

                                    <!-- Status do pedido -->
                                    <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_order_status') ?>" data-container="body"><?=$this->lang->line('application_status');?></th>
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

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

// Deixa o submenu "Devolução de Produtos" em negrito, marcando o submenu como selecionado.
$(document).ready(function() {
    $("#mainOrdersNav").addClass('active');
    $("#ReturnOrderNav").addClass('active');
    $(".select2").select2();

    buscapedido();
});

// Recupera as informações preenchidas nos filtros e busca os pedidos correspondentes.
function buscapedido()
{
    // Pedido Conecta Lá
    let pedidoconecta = $('#buscapedidoconecta').val();

    // Pedido Marketplace
    let pedidomkt = $('#buscapedidomkt').val();

    // Lojas
    let stores = [];
    $('#buscalojas option:selected').each(function() {
        stores.push($(this).val());
    });

    if (stores == '') {
        stores = '';
    }

    // Transportadora
    let shippingco = [];
    $('#buscaentrega option:selected').each(function() {
        shippingco.push($(this).val());
    });

    if (shippingco == '') {
        shippingco = '';
    }

    // Status
    let status = [];
    $('#buscastatus option:selected').each(function() {
        status.push($(this).val());
    });

    if (status == '') {
        status = '';
    }

    // Data de envio para contratação
    let hiredatereturn = $('#buscadataenviocontrata').datepicker({ dateFormat: 'dd-mm-yy' }).val();

    // Data contratada
    let hiredate = $('#buscadatacontratada').datepicker({ dateFormat: 'dd-mm-yy' }).val();

    // Data de devolução
    let datereturn = $('#buscadataenvio').datepicker({ dateFormat: 'dd-mm-yy' }).val();

    if ((typeof manageTable === 'object') && (manageTable !== null)) {
        manageTable.destroy();
    }

    manageTable = $('#manageTable').DataTable({
        "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
        "processing": true,
        "serverSide": true,
        "scrollX": true,
        "sortable": true,
        "searching": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline({
            url: base_url + 'ProductsReturn/fetchReturnOrders',
            data: {
                pedidoconecta: pedidoconecta, 
                pedidomkt: pedidomkt, 
                stores: stores, 
                shippingco: shippingco, 
                status: status, 
                hiredatereturn: hiredatereturn, 
                hiredate: hiredate, 
                datereturn: datereturn
            },
            pages: 2, // number of pages to cache
            success: setTimeout(function(){$("input[name='loja[]']").each(function(){$(this).prop('disabled', false)})}, 1500)
        })
    });
}

// Atualiza o status do pedido no banco de dados.
function atualizastatus(id, index, order_id)
{
    let index_val = document.getElementById('buscastatus' + id).selectedIndex;
    let index_changed = false;
    let status_value = $('#buscastatus' + id).val();
    if (status_value === 'ACONTRATAR') {
        status_value = 'a_contratar';
        index_changed = true;
    } else if (status_value === 'COLETADO') {
        index_changed = true;
    } else if (status_value === 'DEVOLVIDO' || status_value === 'CANCELADO') {
        const status_value_lower = status_value.toLowerCase();
        Swal.fire({
            title: `Marcar o produto como ${status_value_lower}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#f39c12',
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            html: '<b>ATENÇÃO!</b> Não será possível mudar o status da devolução do pedido no futuro.'
        }).then((result) => {
            if (result.value) {

                $.ajax({
                    url: base_url + 'ProductsReturn/updateStatus',
                    type: 'post',
                    dataType: 'json',
                    data: {
                        return_id: id,
                        status: status_value_lower,
                        order_id
                    },
                    success: function(response) {
                        if (!response.success) {
                            Swal.fire({
                                icon: 'error',
                                title: response.message
                            });
                            document.getElementById('buscastatus' + id).selectedIndex = $('#current_index' + id).val();
                            return false;
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Operação confirmada',
                            confirmButtonText: "Ok",
                        });
                        document.getElementById('buscastatus' + id).outerHTML = status_value === 'DEVOLVIDO' ? '<span class="label label-success">Devolvido</span>' : '<span class="label label-danger">Cancelado</span>';
                        document.getElementById('current_index' + id).value = index_val;
                        console.log('success');
                    },
                    fail: function(response) {
                        console.log('fail');
                    }
                });
            } else if (result.dismiss === 'cancel' || result.dismiss === 'backdrop') {
                Swal.fire({
                    icon: 'error',
                    title: 'Operação cancelada',
                    confirmButtonText: "Ok",
                });

                document.getElementById('buscastatus' + id).selectedIndex = $('#current_index' + id).val();
            }
        });
    }

    debugger;
    if (index_changed) {

        const payload = {
            return_id: id, 
            status: status_value.toLowerCase()
        };

        $.ajax({
            url: base_url + 'ProductsReturn/updateStatus',
            type: 'post', 
            dataType: 'json', 
            data: payload, 
            success: function(response) {
                if (!response.success) {
                    Swal.fire({
                        icon: 'error',
                        title: response.message
                    });
                    document.getElementById('buscastatus' + id).selectedIndex = $('#current_index' + id).val();
                    return false;
                }

                if (status_value === 'a_contratar') {
                    document.getElementById('buscastatus' + id).style.backgroundColor = '#ffc107';
                } else if (status_value === 'COLETADO') {
                    document.getElementById('buscastatus' + id).style.backgroundColor = '#007bff';
                }

                document.getElementById('current_index' + id).value = index_val;
                console.log('success');
            }, 
            fail: function(response) {
                console.log('fail');
            }
        });
    }
}

// Limpa todos os filtros e mostra, paginados, todos os resultados.
function clearFilters()
{
    $('#buscapedidoconecta').val('');
    $('#buscapedidomkt').val('');
    $('#buscalojas').val('');
    $('#buscaentrega').val('');
    $('#buscastatus').val('');
    $('#buscadataenviocontrata').val('');
    $('#buscadatacontratada').val('');
    $('#buscadataenvio').val('');

    buscapedido();
}

$(document).ready(function() {
    $('#buscadataenviocontrata').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR", 
		startDate: new Date(+new Date() + 86400000),
		todayBtn: true, 
		todayHighlight: true
	});

    $('#buscadatacontratada').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR", 
		startDate: new Date(+new Date() + 86400000),
		todayBtn: true, 
		todayHighlight: true
	});

    $('#buscadataenvio').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR", 
		startDate: new Date(+new Date() + 86400000),
		todayBtn: true, 
		todayHighlight: true
	});
});
</script>
