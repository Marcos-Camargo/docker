<?php
// Redirecionamento temporário, relativo à LOG-457.
redirect('dashboard', 'refresh');
?>

<div class="content-wrapper">
    <?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data); ?>
    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
            <div id="messages"></div>
                <?php if($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?=$this->session->flashdata('success')?>
                    </div>
                <?php elseif($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?=$this->session->flashdata('error')?>
                    </div>
                <?php endif; ?>
                <?php if ($promotion_sellercenter == 0): ?>
                <div class="box box-primary">
                    <div class="box-header">
                        <h3 class="box-title">Adicionar Produtos Participantes</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-7 no-padding">
                                <div class="form-group col-md-12">
                                    <label for="select_products"><?=$this->lang->line('application_promotion_product');?></label>
                                    <div class="input-group">
                                        <select class="form-control select_group" id="select_products" name="product[]">
                                            <option value=""><?=$this->lang->line('application_promotion_product');?></option>
                                            <?php foreach ($products as $k => $v): ?>
                                                <option value="<?=$v['id']?>"><?=$v['sku']?> - <?=$v['name']?></option>
                                            <?php endforeach ?>
                                        </select>
                                        <div class="input-group-addon">
                                            <button type="button" class="btn btn-outline-secondary" style="line-height: 0.4; padding: 0;" onclick="addProduct();"><?=$this->lang->line('application_promotion_addproduct');?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5 no-padding">
                                <div class="form-group col-md-12">
                                    <div class="d-flex justify-content-between">
                                        <label for="import_products"><?=$this->lang->line('application_upload_products_csv_to_massive_import');?></label>
                                        <a href="<?=base_url('assets/files/promotion_logistic_sample_products.csv') ?>"><?=lang('application_download_sample');?></a>
                                    </div>
                                    <div class="input-group">
                                        <input type="file" name="fileProduct" id="import_products" class="form-control" />
                                        <div class="input-group-addon">
                                            <button type="button" class="btn btn-outline-secondary" id="btnImportProducts" style="line-height: 0.4; padding: 0;"><?=lang('application_send');?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="box box-primary">
                    <div class="box-header">
                        <h3 class="box-title">Produtos em promoção</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-12">
                            <table id="manageTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                    <th><?=$this->lang->line('application_sku');?></th>
                                    <th><?=$this->lang->line('application_name');?></th>
                                    <th><?=$this->lang->line('application_price');?></th>
                                    <th><?=$this->lang->line('application_inactive_date');?></th>
                                    <th><?=$this->lang->line('application_action');?></th>
                                    </tr>
                                </thead>
                            </table>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <a href="<?=base_url('PromotionLogistic/seller') ?>" class="btn btn-primary col-md-3"><?=$this->lang->line('application_back');?></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .swal2-container .swal2-html-container ol {
        text-align: left;
        list-style: disc;
        font-size: 1.1em;
    }
</style>

<script type="text/javascript">

const base_url = "<?=base_url()?>";
const idPromo = "<?=$idPromo?>";
let manageTable;

$(function() {
    $("#mainLogisticsNav").addClass('active');
    $("#logisticPromotionNav").addClass('active');

    $('#select_products').select2();
    onloadTable();
});

const onloadTable = () => {
    // initialize the datatable
    manageTable = $('#manageTable').DataTable({
        'paging': false,
        "destroy": true,
        "language": { "url": "<?=base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang');?>" },
        'ajax': {
            "url": base_url + 'PromotionLogistic/fetchPromoProductSeller',
			"data": { idPromo }
        },
		"type": "POST",
        "initComplete": function(settings,json) {
            $("input[data-bootstrap-switch]").each(function(result){
                $(this).bootstrapSwitch({size: "small"});
            });
        }
    });
}

$(document).on('switchChange.bootstrapSwitch', '[data-product-id]', function () {
    const id_product = $(this).data('product-id');
    $(`[data-product-id="${id_product}"]`).bootstrapSwitch('state', true);

    Swal.fire({
        icon: 'question',
        title: "Deseja inativar o produto da promoção?",
        html: "<label><input type='checkbox' name = 'checkInactiveProduct'> Estou ciente que não poderei mais ativar este produto nesta promoção.</label><br>",
        showCancelButton: true,
        confirmButtonText: "Sim",
        cancelButtonText:"Não",
        allowOutsideClick: false
    }).then((result) => {
        if (result.value === true && $('input[name = "checkInactiveProduct"]').is(':checked')) {
            $.ajax({
                url: `${base_url}PromotionLogistic/removeProduct`,
                type: "POST",
                data: { id_product, idPromo },
                async: true,
                success: response => {
                    Toast.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.message
                    });
                }, error: function(jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR,textStatus, errorThrown);
                }, complete: () => {
                    onloadTable();
                }
            });
        }
    });
});

<?php if ($promotion_sellercenter == 0): ?>
const addProduct= () => {
    const id_product = $("#select_products option:selected").val();

    $.ajax({
        url: `${base_url}PromotionLogistic/saveProduct`,
        type: "POST",
        data: { id_product, idPromo },
        async: true,
        success: function(response) {
            Toast.fire({
                icon: response.success ? 'success' : 'error',
                title: response.message
            });
        }, error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }, complete: () => {
            onloadTable();
        }
    });
}

$('#btnImportProducts').on('click', function(){
    const fileProducts = $('#import_products').prop('files')[0];

    if (typeof fileProducts === 'undefined') {
        return false;
    }

    let dataForm = new FormData();
    dataForm.append('file', fileProducts);
    dataForm.append('promotion', idPromo);

    $.ajax({
        url: `${base_url}PromotionLogistic/addProductByCSV`,
        type: 'POST',
        data: dataForm,
        dataType: 'json',
        enctype: 'multipart/form-data',
        processData:false,
        contentType:false,
        success: function(response) {
            if (response.success && response.additional.length === 0) {
                Swal.fire({
                    icon: 'success',
                    title: response.message,
                    showCancelButton: false,
                    confirmButtonText: "Ok",
                });
            } else {
                Swal.fire({
                    icon: response.success ? 'warning' : 'error',
                    title: response.message,
                    width: 600,
                    html: '<ol><li>' + response.additional.join('</li><li>') + '</li></ol>',
                    showCancelButton: false,
                    confirmButtonText: "Ok",
                });
            }
        }, error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }, complete: () => {
            onloadTable();
            $('#import_products').val('');
        }
    });
});

<?php endif; ?>

</script>

