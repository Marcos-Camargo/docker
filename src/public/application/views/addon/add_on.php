<div class="content-wrapper">
    <?php
      $data['pageinfo'] = "application_manage";
      $data['page_now'] = 'add_on';
      $this->load->view('templates/content_header', $data);
    ?>
    <div class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-md-12 col-xs-12">
                                <h4>Inclua produtos complementares como uma seleção opcional para o comprador quando ele estiver comprando o produto principal. Eles aparecerão na página de detalhes do produto com o preço associado e com funções de adicionar ao carrinho.</h4>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <button type="button" class="btn btn-primary add_line" data-toggle="modal" data-target="#createModal">
                                    <i class="fa fa-plus"></i> <?=$this->lang->line('application_product_addon');?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12 col-xs-12">
                                <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?=lang('application_id');?></th>
                                            <th><?=lang('application_name');?></th>
                                            <th><?=lang('application_sku');?></th>
                                            <th><?=lang('application_status');?></th>
                                            <th><?=lang('application_action');?></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CREATE ADDON MODAL -->
        <div class="modal fade" tabindex="-1" role="dialog" id="createModal">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Incluir Produtos Complementares<span id="createAddon"></span></h4>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 col-xs-12">
                                <table id="manageTableCreate" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?=lang('application_image');?></th>
                                            <th><?=lang('application_name');?></th>
                                            <th><?=lang('application_sku');?></th>
                                            <th><?=lang('application_status');?></th>
                                            <th><?=lang('application_id');?></th>
                                            <th><?=lang('application_action');?></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">

var manageTable;
var manageTableCreate;
var base_url = "<?php echo base_url(); ?>";
window.prd_id = <?php echo $this->data['prd_id']; ?>;
var store_id = <?php echo $this->data['store_id']; ?>;

// initialize the datatable manageTableCreate
$(document).ready(function() {
    // initialize the datatable
    loadTable();
});

const loadCreateTable = () => {
    if (typeof manageTableCreate !== 'undefined') {
        manageTableCreate.destroy();
    }

    manageTableCreate = $('#manageTableCreate').DataTable({
        "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
        "processing": true,
        "serverSide": true,
        "scrollX": true,
        "sortable": true,
        "searching": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline({
            url: base_url +'AddOn/fetchProductStore/' + store_id,
            pages: 2,
            data: { prd_id_ignore: window.prd_id }
        })
    });
}

$('#createModal').on('shown.bs.modal', function (e) {
    loadCreateTable();
});

const loadTable = () => {
    if (typeof manageTable !== 'undefined') {
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
            url: base_url +'AddOn/fetchAddOnData',
            data: { prd_id },
            pages: 2
        })
    });
}

// remove functions 
const removeFunc = (prd_id) => {
    const row_data = $(`[onclick="removeFunc(${prd_id})"]`).closest('tr');

    Swal.fire({
        title: 'Deseja deletar o produto adicional?',
        html: `<h4>Essa ação não poderá ser desfeita!</h4>
                <p class="mb-0"><b>Nome: </b>${row_data.find('td:eq(1)').text()}</p>
                <p><b>SKU: </b>${row_data.find('td:eq(2)').text()}</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim',
        cancelButtonText: 'Não',
    }).then((result) => {
        if (result.value && prd_id) {
            $.ajax({
                url: base_url +'AddOn/removeAdditionalProduct',
                type: 'POST',
                data: { product_id_addon: prd_id, prd_id: window.prd_id },
                dataType: 'json',
                success:function(response) {
                    if (response.success === true) {
                        loadTable();
                    }

                    AlertSweet.fire({
                        icon: response.success ? 'success' : 'warning',
                        title: response.message
                    });
                }
            });
        }
    });
}

// create function
$(document).on('click', '.btn-add-product', function () {
    const row_data = $(this).closest('tr');
    const product_id_addon = $(this).data('product');
    Swal.fire({
        title: 'Deseja inserir o produto adicional?',
        html: `<p class="mb-0"><b>Nome: </b>${row_data.find('td:eq(1)').text()}</p>
                <p><b>SKU: </b>${row_data.find('td:eq(2)').text()}</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim',
        cancelButtonText: 'Não',
    }).then((result) => {
        if (result.value && product_id_addon) {
            $.ajax({
                url: base_url + "AddOn/create",
                type: 'POST',
                data: { prd_id: window.prd_id, product_id_addon },
                async: true,
                dataType: 'json',
                success:function(response) {
                    if (response.success === true) {
                        loadTable();
                        loadCreateTable()
                    }

                    AlertSweet.fire({
                        icon: response.success ? 'success' : 'warning',
                        title: response.message
                    });
                }
            });
        }
    });
});



</script>