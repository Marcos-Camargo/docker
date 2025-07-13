<!--
SW Serviços de Informática 2019

Listar Pedidos

Obs:
cada usuario so pode ver pedidos da sua empresa.
Agencias podem ver todos os pedidos das suas empresas
Admin pode ver todas as empresas e agencias

-->
<div class="content-wrapper">
  <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>
  <section class="content">
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
        <?php if(!$haveStoreForInvoice){?>
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><?=$this->lang->line('application_request_biller_module');?></h3>
                </div>
                <div class="box-body">
                    <div class="row ">
                        <div class="col-md- 12 text-center">
                            <h4><?=$this->lang->line('messages_no_permission_invoice')?></h4>
                            <a href="<?=base_url('users/request_biller')?>" class="btn btn-primary mt-4"><?=$this->lang->line('application_request_biller_module');?></a>
                        </div>
                    </div>
                </div>
            </div>
        <?php } else { ?>
        <div class="box box-primary">
          <div class="box-header">
            <h3 class="box-title"><?=$this->lang->line('application_filter') . " " . $this->lang->line('application_status');?></h3>
          </div>
          <div class="box-body">
              <div class="row">
                  <div class="form-group">
                      <label class="col-md-3 col-xs-12">
                          <input type="checkbox" class="minimal" name="orders_for_invoice" id="orders_for_invoice" value="true" <?=set_checkbox('orders_for_invoice', 'true', true)?>>
                          <?=$this->lang->line('application_order_3')?>
                      </label>
                      <label class="col-md-3 col-xs-12">
                          <input type="checkbox" class="minimal" name="orders_invoiced" id="orders_invoiced" value="true" <?=set_checkbox('orders_invoiced', 'true')?>>
                          <?=$this->lang->line('application_Invoiced')?>
                      </label>
                      <label class="col-md-3 col-xs-12">
                          <input type="checkbox" class="minimal" name="orders_processing" id="orders_processing" value="true" <?=set_checkbox('orders_processing', 'true')?>>
                          <?=$this->lang->line('application_order_56')?>
                      </label>
                      <label class="col-md-3 col-xs-12">
                          <button type="button" class="btn btn-primary col-md-12" id="filterTable"><?=$this->lang->line('application_filter');?></button>
                      </label>
                  </div>
              </div>
          </div>
        </div>
        <div class="box box-primary">
          <div class="box-body">
              <div class="col-md-12 no-padding">
                  <div class="form-group d-flex justify-content-between flex-wrap-wrap">
                      <div class="col-md-3">
                        <button class="btn btn-primary col-md-12" id="btnSelectAll"><i class="fa fa-check-square-o"></i> <?=$this->lang->line('application_check_all')?></button>
                      </div>
                      <div class="col-md-3">
                        <button class="btn btn-warning col-md-12" id="btnDeselectAll" disabled><i class="fa fa-square-o"></i> <?=$this->lang->line('application_deselect_all')?></button>
                      </div>
                      <div class="col-md-3 divBtnSend">
                          <button class="btn btn-success col-md-12" id="btnSendInvoices" disabled><i class="fa fa-check"></i> <?=$this->lang->line('application_send_selecteds')?></button>
                          <small class="text-center col-md-12"></small>
                      </div>
                      <div class="col-md-3">
                          <button class="btn btn-info col-md-12" data-toggle="modal" data-target="#downloadXmlMonth"><i class="fa fa-download"></i> <?=$this->lang->line('application_download') . ' XML / ' . $this->lang->line('application_Month')?></button>
                          <small class="text-center col-md-12"></small>
                      </div>
                  </div>
              </div>
              <div class="col-md-12 no-padding">
                <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                  <thead>
                  <tr>
                    <th><?=$this->lang->line('application_mark');?></th>
                    <th><?=$this->lang->line('application_order');?></th>
                    <th><?=$this->lang->line('application_company');?></th>
                    <th><?=$this->lang->line('application_name');?></th>
                    <th><?=$this->lang->line('application_date');?></th>
                    <th><?=$this->lang->line('application_total_amount');?></th>
                    <th><?=$this->lang->line('application_status');?></th>
                    <?php if(in_array('updateOrder', $user_permission) || in_array('viewOrder', $user_permission) || in_array('deleteOrder', $user_permission)): ?>
                      <th><?=$this->lang->line('application_action');?></th>
                    <?php endif; ?>
                  </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>
          </div>
        </div>

        <?php } ?>
      </div>
    </div>
  </section>
</div>

<?php if($haveStoreForInvoice){?>
<div class="modal fade" tabindex="-1" role="dialog" id="viewErrorInvoice">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_order_57');?></span></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <h3 class="text-center"><?=$this->lang->line('messages_problem_to_order_invoice')?></h3>
                    <div class="col-md-12">
                        <div class="bd-callout bd-callout-danger">
                            <h4 id="jquery-incompatibility"><?=$this->lang->line('messages_problems_found')?></h4>
                            <div class="errors mt-4">
                                <ul></ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <h4><?=$this->lang->line('messages_after_correcting_back_here')?></h4>
                        <label><?=$this->lang->line('messages_all_problems_fixed')?></label> <br><button class="btn btn-sm btn-success btnTryToIssue"><?=$this->lang->line('application_yes_send_again')?></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="viewErrorOrder">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_problem_order');?></span></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <h3 class="text-center"><?=$this->lang->line('messages_problem_to_order_invoice')?></h3>
                    <div class="col-md-12">
                        <div class="bd-callout bd-callout-danger">
                            <h4><?=$this->lang->line('messages_problems_found')?></h4>
                            <div class="errors mt-4">
                                <ul></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="viewInvoice">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_invoice');?></span></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Chave</label>
                            <input type="text" class="form-control" name="chave_nfe" value="" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Número</label>
                            <input type="text" class="form-control" name="numero_nfe" value="" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Série</label>
                            <input type="text" class="form-control" name="serie_nfe" value="" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>data Emissão</label>
                            <input type="text" class="form-control" name="data_nfe" value="" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>DANFE</label><br>
                            <a target="_blank" class="link" href="">Visualizar Fatura</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>XML</label><br>
                            <a download class="xml" href=""><i class="fa fa-download"></i> Baixar</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="requestCancel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_request_cancel');?></span></h4>
            </div>
            <div class="modal-body">
                <div class="row dataInvoice">
                    <div class="col-md-12">
                        <h4 class="text-center">Você tem certeza que deseja realizar a solicitação para cancelamento?</h4>
                        <h3 class="mt-5 text-center font-weight-bold">Pedido: <span class="numberOrderInvoiceCancel"></span></h3>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-danger col-md-3" id="btnSendRequestCancel"><?=$this->lang->line('application_yes');?></button>
                <button type="button" class="btn btn-primary col-md-3" data-dismiss="modal"><?=$this->lang->line('application_no');?></button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="downloadXmlMonth">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_download') . ' XML';?></span></h4>
            </div>
            <div class="modal-body">
                <div class="row d-flex justify-content-center">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><?=$this->lang->line('application_Month')?></label>
                            <input type="number" class="form-control" name="month" min="1" max="12" value="<?=date('m')?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><?=$this->lang->line('application_Year')?></label>
                            <input type="number" class="form-control" name="year" value="<?=date('Y')?>">
                        </div>
                    </div>
                    <div class="col-md-6" style="display: <?=count($storesView) > 0 ? 'block' : 'none'?>">
                        <div class="form-group">
                            <label><?=$this->lang->line('application_stores')?></label>
                            <select class="form-control" name="stores">
                                <?php
                                foreach ($storesView as $store)
                                    echo "<option value='{$store['id']}'>{$store['name']}</option>";
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <button id="btnDownloadXmlMonth" class="btn btn-success col-md-12" style="margin-top: 23px"><?=$this->lang->line('application_download')?></button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 mt-3 divLinkXml" style="display: none">
                        <label><?=$this->lang->line('application_download') . ' XML: '?></label>
                        <a href="" class="downloadXmlLote btn btn-primary" download=""></a>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="dataAdditional">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_add_additional_data');?></span></h4>
            </div>
            <div class="modal-body">
                <div class="row dataInvoice">
                    <div class="col-md-12">
                        <label><?=$this->lang->line('application_message')?></label>
                        <input type="text" name="message" class="form-control" maxlength="100">
                        <small><?=$this->lang->line('messages_limit_100_characters')?></small>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-primary col-md-3" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                <button type="button" class="btn btn-success col-md-3" id="btnSendAdditionalData"><?=$this->lang->line('application_add');?></button>
            </div>
            <input type="hidden" name="order_id" />
        </div>
    </div>
</div>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/datatables.net/js/processing.js"></script>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/sweetalert/dist/sweetalert2.all.min.js"></script>
<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

    manageTable = $('#manageTable').DataTable( {
        "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
        "processing": true,
        "scrollX": true,
        "sortable": true,
        "columnDefs": [
            {
                targets: [0,6],
                className: 'text-center'
            },
            {
                targets: [7],
                className: 'd-flex justify-content-center flex-nowrap'
            }
        ]
    });
    $('input[type="checkbox"].minimal').iCheck({
        checkboxClass: 'icheckbox_minimal-blue'
    });

    $("#filterTable").trigger('click');

});

$(document).on('click', '.btnRequestCancel', function () {
    const order_id = $(this).attr('order-id');

    $('#requestCancel').modal();
    $('#requestCancel button#btnSendRequestCancel').attr('order_id', order_id);
    $('#requestCancel h3 span.numberOrderInvoiceCancel').text(order_id);

    return false;
})

$('#btnSendRequestCancel').on('click', function () {
    const url = "<?=base_url('orders/RequestCancelInvoice')?>";
    const order_id = $(this).attr('order_id');

    $.ajax({
        url,
        type: "POST",
        data: { order_id },
        dataType: 'json',
        success: response => {
            if(response == true) {
                $('#requestCancel').modal('hide');
                Toast.fire({
                    icon: 'success',
                    title: 'Solicitação enviada!'
                });
            } else {
                Toast.fire({
                    icon: 'error',
                    title: 'Não foi possível realizar a solicitação!'
                });
            }
        }
    });
})

$('#btnDownloadXmlMonth').on('click', function () {
    const url = "<?=base_url('orders/downloadXmlLote')?>";
    const element = $(this).closest('#downloadXmlMonth');
    const month = element.find('[name="month"]').val();
    const year = element.find('[name="year"]').val();
    const store = element.find('[name="stores"]').val();
    console.log(month, year, store, url);
    $.ajax({
        url,
        type: "POST",
        data: { month, year, store },
        dataType: 'json',
        success: response => {
            if(response == false){
                Toast.fire({
                    icon: 'error',
                    title: "Não foi encontrado arquivos com esse filtro!"
                });
                $('.divLinkXml').slideUp('slow');
                return false;
            }
            $('.divLinkXml').slideDown('slow');
            $('a.downloadXmlLote').attr('href', response[0]);
            $('a.downloadXmlLote').text(response[1]);
        },
        error: error => {
            console.log(error);
        }
    });
})

$(document).on('click', '.btnInvoiceError', function () {
    const url = "<?=base_url('orders/getErrorOrderInvoice')?>";
    const order_id = $(this).attr('order-id');
    $.ajax({
        url,
        type: "POST",
        data: { order_id },
        dataType: 'json',
        success: response => {
            $('#viewErrorInvoice .errors ul').empty();

            for(let i = 0; i < response.length; i++) {
                $('#viewErrorInvoice .errors ul').append(`<li>${response[i]}</li>`);
            }
            $('#viewErrorInvoice').modal();
            $('#viewErrorInvoice').attr('order_id', order_id);
        }
    });
});

$(document).on('click', '.btnErrorOrder', function () {
    const url = "<?=base_url('orders/errorOrderAjax')?>";
    const order_id = $(this).attr('order-id');
    $.ajax({
        url,
        type: "POST",
        data: { order_id },
        dataType: 'json',
        success: response => {
            console.log(response);
            $('#viewErrorOrder .errors ul').empty();

            for(let i = 0; i < response.length; i++) {
                $('#viewErrorOrder .errors ul').append(`<li>${response[i].message} - <a target="_blank" href='${response[i].product_url}'>Ver Produto</a></li>`);
            }
            $('#viewErrorOrder').modal();
            $('#viewErrorOrder').attr('order_id', order_id);
        }
    });
});

$('.btnTryToIssue').click(function () {
    const url = "<?=base_url('orders/sendInvoiceWithError')?>";
    const order_id = $(this).closest('#viewErrorInvoice').attr('order_id');
    $.ajax({
        url,
        type: "POST",
        data: { order_id },
        dataType: 'json',
        success: response => {
            console.log(response);
            if(response == true){
                Toast.fire({
                    icon: 'success',
                    title: 'Pedidos enviado para ser faturado'
                })
            }
            else{
                Toast.fire({
                    icon: 'error',
                    title: 'Erro para enviar para ser faturado'
                })
            }

            $('#viewErrorInvoice').modal('hide');

            $("#filterTable").trigger('click');
        }
    });
});

$(document).on('click', '.viewInvoice', function () {
    const url = "<?=base_url('orders/getDataInvoice')?>";
    const order_id = $(this).attr('order-id');
    $.ajax({
        url,
        type: "POST",
        data: { order_id },
        dataType: 'json',
        success: response => {
            console.log(response);
            if(response.success == true){
                $('#viewInvoice input[name="chave_nfe"]').val(response.data.chave);
                $('#viewInvoice input[name="numero_nfe"]').val(response.data.numero);
                $('#viewInvoice input[name="serie_nfe"]').val(response.data.serie);
                $('#viewInvoice input[name="data_nfe"]').val(response.data.date_emission);
                $('#viewInvoice a.link').attr('href', response.data.link);
                $('#viewInvoice a.xml').attr('href', response.data.linkXml);
                $('#viewInvoice').modal();
            }
        }
    });
    return false;
});

// Filtragem
$('#filterTable').click(function (event) {
    const url               = "<?=base_url('orders/fetchOrdersInvoiceData')?>";
    const orders_for_invoice= $('#orders_for_invoice').is(':checked');
    const orders_invoiced   = $('#orders_invoiced').is(':checked');
    const orders_processing = $('#orders_processing').is(':checked');
    const btnLoader         = $(this);
    let rowsTable           = new Array();

    if(!orders_for_invoice && !orders_invoiced && !orders_processing){
        Toast.fire({
            icon: 'error',
            title: "<?=$this->lang->line('messages_select_at_least_one_filter')?>"
        })
        return false;
    }

    manageTable.clear().draw().processing(true);

    $.ajax({
        url,
        type: "GET",
        data: { orders_for_invoice, orders_invoiced, orders_processing },
        dataType: 'json',
        success: response => {
            $.each(response.data, function( index, value ) {
                rowsTable.push(value);
            });

            manageTable.clear().draw().rows.add(rowsTable).columns.adjust().draw().processing(false);

            // Adicionar plugins na nova renderização
            $('[data-toggle="tooltip"], [data-togg="tooltip"]').tooltip();
            $('input[type="checkbox"].minimal').iCheck({
                checkboxClass: 'icheckbox_minimal-blue'
            });

            // Adicionar attr de order_encode na linha
            $('#manageTable tbody tr').each(function(){
                order_id = $('td:eq(1)', this).text();
                $(this).attr("order_id", order_id);
            });

            updateButtonsPainel();
        }
    });
});

$('#manageTable').on( 'page.dt', function () {
    // Adicionar attr de order_encode na linha
    setTimeout(() => {
        $('#manageTable tbody tr').each(function(){
            order_id = $('td:eq(1)', this).text();
            $(this).attr("order_id", order_id);
        });
    }, 1000);
} );


$(document).on('ifChanged', ".sendInvoice", function(){
    updateButtonsPainel();
});

$(document).on('click', ".btnSendInvoice, #btnSendInvoices", async function () {

    let orders_id = new Array(); // coleção com as order_id
    let resultSendInvoice;

    // Envio unitária
    if($(this).hasClass('btnSendInvoice')){
        orders_id.push($(this).attr('order-id'));
    }
    // Envio em lote
    else if($(this).attr('id') === "btnSendInvoices"){
        $('#manageTable tbody tr').each(function() {
            if($('td:eq(0) input', this).is(':checked'))
                orders_id.push($(this).attr('order_id'));
        })
    }

    // Requisição para envio
    await sendOrderForInvoice(orders_id).then(response => {
        resultSendInvoice = response;
    });

    if(!resultSendInvoice.success){
        Toast.fire({
            icon: 'error',
            title: resultSendInvoice.message
        })
        return false
    }

    Toast.fire({
        icon: 'success',
        title: 'Pedidos enviado para ser faturadoo'
    })

    $("#filterTable").trigger('click');
})


const sendOrderForInvoice = order_id => {
    const url = "<?=base_url('orders/sendOrdersForInvoice')?>";
    let retorno;

    retorno = $.ajax({
        url,
        type: "POST",
        data: { order_id },
        dataType: 'json',
        success: response => {
            return response;
        }
    });

    return retorno;
}

$("#btnSelectAll").click(function () {
    $('#manageTable tbody tr').each(function() {
        $('td:eq(0) input', this).iCheck('check');
    })
});

$("#btnDeselectAll").click(function () {
    $('#manageTable tbody tr').each(function() {
        $('td:eq(0) input', this).iCheck('uncheck');
    })
});

const updateButtonsPainel = () => {
    if($(".sendInvoice:checked").length > 0) {
        $("#btnSendInvoices").attr("disabled", false);
        $("#btnDeselectAll").attr("disabled", false);
        $(".sendInvoice:checked").length === 1 ?
            $(".divBtnSend small").text($(".sendInvoice:checked").length + " <?=$this->lang->line('application_selected_order')?>") : $(".divBtnSend small").text($(".sendInvoice:checked").length + " <?=$this->lang->line('application_selected_orders')?>");
    }
    else{
        $("#btnSendInvoices").attr("disabled", true);
        $("#btnDeselectAll").attr("disabled", true);
        $(".divBtnSend small").text("<?=$this->lang->line('application_no_orders_selected')?>");
    }

    if($(".sendInvoice:checked").length == $(".sendInvoice").length)
        $("#btnSelectAll").attr("disabled", true);
    else
        $("#btnSelectAll").attr("disabled", false);
}


// DISABLED ENABLED BUTTON
const loadingBtn = (thisBtn, onOff) => {
    if(onOff == 0) setTimeout(() => { $(thisBtn).removeAttr("disabled") }, 750);
    if(onOff == 1) $(thisBtn).prop('disabled', true);

    if(thisBtn.is("button")){
        if(onOff == 0) setTimeout(() => { thisBtn.html(`${thisBtn.text().replace('<i class="fa fa-spinner fa-spin"></i>', '')}`) }, 750);
        if(onOff == 1) thisBtn.html(`${thisBtn.text()} <i class="fa fa-spinner fa-spin"></i>`);
    }
}

$(document).on('click', '.btnAddAdditionalData', function () {
    const url = "<?=base_url('orders/getAdditionData')?>";
    const order_id = $(this).attr('order-id');
    $.ajax({
        url,
        type: "POST",
        data: { order_id },
        dataType: 'json',
        success: response => {
            if (!response.success) {
                Toast.fire({
                    icon: 'error',
                    title: response.data
                });
                return false;
            }

            $('#dataAdditional input[name="message"]').val(response.data ?? '');
            $('#dataAdditional input[name="order_id"]').val(order_id);
            $('#dataAdditional').modal();

        }, error: e => {
            console.log(e);
        }
    });
});

$('#btnSendAdditionalData').on('click', function () {
    const url       = "<?=base_url('orders/updateAdditionData')?>";
    const order_id  = $(this).closest('#dataAdditional').find('input[name="order_id"]').val();
    const message   = $(this).closest('#dataAdditional').find('input[name="message"]').val();

    $.ajax({
        url,
        type: "POST",
        data: { order_id, message },
        dataType: 'json',
        success: response => {
            Toast.fire({
                icon: !response.success ? 'error' : 'success',
                title: response.data
            });

            if (response.success) {
                $('#dataAdditional').modal('hide');
            }

        }, error: e => {
            console.log(e);
        }
    });
});


</script>
<?php } ?>
<script>
$(document).ready(function() {
    $("#mainOrdersNav").addClass('active');
    $("#manageOrdersInvoiceNav").addClass('active');
});
</script>
