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
        <div class="box">
          <div class="box-header d-flex justify-content-between flex-wrap">
            <h3 class="box-title col-md-<?=count($storesView) > 1 ? '2' : '3'?>"><?=$this->lang->line('application_logs_view');?></h3>
            <div class="col-md-<?=count($storesView) > 1 ? '7' : '5'?> d-flex justify-content-between flex-wrap no-padding pull-right">
              <select class="form-control col-md-4" id="storeLog">
                <?php
                if (count($storesView) > 1) {
                    echo "<option value=''>{$this->lang->line('application_select_store')}...</option>";
                }
                foreach ($storesView as $store) {
                    echo "<option value='{$store['id']}'>{$store['name']}</option>";
                }
                ?>
              </select>
              <a href="<?=base_url('integrations/job_integration')?>" class="btn btn-primary col-md-3 btnManageIntegration"><i class="fas fa-cog"></i> <?=$this->lang->line('application_manage_integrations');?></a>
              <button type="button" class="btn btn-primary col-md-3 btnHistoryLog" data-toggle="modal" data-target="#historyLog"><i class="fas fa-history"></i> <?=$this->lang->line('application_log_history');?></button>
            </div>
              <div class="pull-right">
                  <div class="btn-group">
                      <button class="btn btn-success dropdown-toggle" type="button" id="expBtn"
                              href="<?php echo(base_url('export/integration_logs') . "") ?>"
                      >
                          <i class="fa fa-file-excel-o"></i> Exportar logs
                      </button>
                  </div>
              </div>
          </div>
          <div class="box-body">
            <table id="manageTable" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
                  <tr>
                    <th><?=$this->lang->line('application_type');?></th>
                    <th><?=$this->lang->line('application_title');?></th>
                    <th><?=$this->lang->line('application_store');?></th>
                    <th><?=$this->lang->line('application_date');?></th>
                    <th><?=$this->lang->line('application_action');?></th>
                  </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="viewLog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span class="icon"></span> <?=$this->lang->line('application_logs_view');?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <h3 class="icon text-center mb-1"></h3>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 form-group">
                        <h3 class="title text-center mt-1"></h3>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="description"></div>
                    </div>
                </div>
                <div class="row">
                    <hr class="mt-3 mb-">
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="unique_id"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="historyLog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span class="icon"></span> <?=$this->lang->line('application_integrate_history');?></h4>
            </div>
            <div class="modal-body search">
                <div class="row">
                    <div class="col-md-12 form-group">
                        <h4 class="text-center"><?=$this->lang->line('messages_test_log_history');?></h4>
                    </div>
                </div>
                <div class="row" style="display: <?=count($storesView) > 1 ? 'block' : 'none'?>">
                    <div class="col-md-12 form-group">
                        <label><?=$this->lang->line('application_store');?></label>
                        <select class="form-control" name="store" required>
                            <?php
                            foreach ($storesView as $store)
                                echo "<option value='{$store['id']}'>{$store['id']} - {$store['name']}</option>";
                            ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>Unique ID</label>
                        <input type="text" class="form-control" name="product_search">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 form-group d-flex justify-content-center">
                        <button type="button" class="btn btn-primary col-md-4" id="btnSearch"><?=$this->lang->line('application_search')?></button>
                    </div>
                </div>
            </div>
            <div class="modal-body result" style="display: none">
                <div class="row">
                    <div class="col-md-12 form-group">
                        <h3 class="text-center"><?=$this->lang->line('messages_test_product_result_search')?></h3>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 form-group result-search">

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 form-group text-center">
                        <button type="button" class="btn btn-primary" id="btnBackSearch" onclick="$('#historyLog .result').slideUp(1000);$('#historyLog .search').slideDown(1000)"><?=$this->lang->line('application_back_to_search')?></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" tabindex="-1" role="dialog" id="historyLogUniqueId">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title"><span class="icon"></span> <?=$this->lang->line('application_integrate_history');?> Unique ID <spam class="unique_id"></spam></h3>
            </div>
            <div class="modal-body result">                          
                <div class="row" style="color: initial !important;">
                    <div class="col-md-12 form-group result-search">

                    </div>
                </div>               
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>

<style>
    .box-header:before,
    .box-header:after{
        display: none;
    }
    .box-header .select2,
    #storeLog {
        flex:2;
        margin: 0px 2px;
        display: <?=count($storesView) > 1 ? 'block' : 'none'?>
    }
    .btnManageIntegration {
        flex: 1;
        margin: 0px 2px;
    }
    .btnHistoryLog{
        flex: 1;
        margin: 0px 0px 0px 2px;
    }
    #viewLog .description li {
        word-wrap: break-word;
    }
    #viewLog .description table ul {
        padding-left: 0;
    }
    #viewLog .description table li {
        list-style: none !important;
    }
    #tableLog {
        word-break: break-word;
    }
    #tableLog thead th:nth-child(1) {
        width: 20%;
    }
    #tableLog thead th:nth-child(2){
        width: 40%;
    }
    #tableLog thead th:nth-child(3){
        width: 40%;
    }
</style>

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.4/moment.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/plug-ins/1.10.21/sorting/datetime-moment.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
<!--<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.5/js/dataTables.responsive.min.js"></script>-->
<!--<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.5/css/responsive.dataTables.min.css"/>-->

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";
var userStore = "<?= $this->session->userdata('userstore') ?>";
var multiStores = "<?= count($storesView) > 1 ? 1 : 0 ?>";

var startDate = "<?= date('Y-m-d', strtotime("-90 days")) . " 00:00:00"?>";
var endDate = "<?= date('Y-m-d') . " 23:59:59"?>";

$(document).ready(function() {
    console.log(userStore);
    $("#mainIntegrationApiNav").addClass('active');
    $("#logIntegration").addClass('active');
    if ($('#storeLog option').length > 1) {
        $('#storeLog').val('');
    }
    $('#storeLog').select2();

    if(multiStores == 0) {
        manageTable = getTable();
    }

    $('#expBtn').on('click', function () {
        var addFilters = {store_id: $('#storeLog').val() != '' ? $('#storeLog').val() : 0};
        $.extend(addFilters, {startDate: startDate, endDate: endDate});
        var types = [];
        $('input[type="checkbox"][name="filter_error"]:checked').val() === undefined ? 0 : types.push('E');
        $('input[type="checkbox"][name="filter_alert"]:checked').val() === undefined ? 0 : types.push('W');
        $('input[type="checkbox"][name="filter_success"]:checked').val() === undefined ? 0 : types.push('S');
        $.extend(addFilters, {types: types});
        $.each(addFilters, function (filter, value) {
            return $.extend(addFilters, {
                [filter]: typeof value === 'string' ? encodeURIComponent(value) : value
            });
        });
        var expUrl = $(this).attr('href') + '?' + $.param(addFilters);
        window.open(expUrl, '_blank').focus();
    });
});
$(document).on('click', '.btn-view', function () {
    const action = base_url + 'integrations/viewLogIntegration';
    const log_id = $(this).attr('log-id');

    $('#viewLog .title').text('');
    $('#viewLog .description').html('');
    $('#viewLog .unique_id').html('<strong>Unique ID</strong>: 0');
    $('#viewLog .icon').removeClass('text-green text-red text-yellow').addClass('text-red').empty().append(`<i class="fas fa-bomb"></i>`);

    $.post( action, { log_id }, response => {

        if(!response['success'] || !response['data']) {
            AlertSweet.fire({
                icon: 'error',
                title: response['data'] ?? "<?=$this->lang->line('messages_no_found_data_this_log');?>"
            });

            return false;
        }
        let classe, icon;

        if (response['data'].type == "S") {
            classe = "text-green";
            icon = "fas fa-check-circle";
        }
        else if (response['data'].type == "E") {
            classe = "text-red";
            icon = "fas fa-bomb";
        }
        else if (response['data'].type == "W") {
            classe = "text-yellow";
            icon = "fas fa-exclamation-circle";
        }

        $('#viewLog .title').text(response['data'].title);
        $('#viewLog .description').html(response['data'].description);
        $('#viewLog .unique_id').html('<strong>Unique ID</strong>: '+response['data'].unique_id + '<br><strong>Increment ID</strong>: '+response['data'].id);
        $('#viewLog .icon').removeClass('text-green text-red text-yellow').addClass(classe).empty().append(`<i class="${icon}"></i>`);
        $('#viewLog').modal();

    }, "json")
    .fail(error => {
        console.log(error);
    });
})

$(document).on('click', '.btnhistoryLogUniqueId', function () {

    const search    =  $(this).attr('log-id');
    const store     =  $(this).attr('store');
    const url       = "<?=base_url('integrations/search_logs_integration')?>";

    $('#historyLogUniqueId .unique_id').text('');
    $('#historyLogUniqueId .result .result-search').html('');
    $('#viewVariation .variations').empty();

    if(search.length == 0) {
        $('#historyLogUniqueId').modal('hide');
        Swal.fire(
            '<?=$this->lang->line("messages_incorrect_data");?>',
            '<?=$this->lang->line("messages_enter_search_field");?>',
            'error'
        );
        return false;
    }

    $.ajax({
        url,
        type: "POST",
        data: { search, store },
        dataType: 'json',
        success: response => {
            if (!response['success']) {
                Swal.fire(
                    '<?=$this->lang->line("messages_incorrect_data");?>',
                    response['data'],
                    'error'
                );
                return false;
            }
            $('#historyLogUniqueId .unique_id').text(search);
            $('#historyLogUniqueId .result .result-search').html(response['data']);
        }, error: e => {
            console.log(e);
        }
    });
})

$(document).on('ifChanged', 'input[type="checkbox"][name^="filter_"]', function(){

    const filter_error   = $('input[type="checkbox"][name="filter_error"]:checked').val() === undefined ? '' : 'checked';
    const filter_alert   = $('input[type="checkbox"][name="filter_alert"]:checked').val() === undefined ? '' : 'checked';
    const filter_success = $('input[type="checkbox"][name="filter_success"]:checked').val() === undefined ? '' : 'checked';
    const filter_type = {
        'E': $('input[type="checkbox"][name="filter_error"]:checked').val() === undefined ? 0 : 1,
        'W': $('input[type="checkbox"][name="filter_alert"]:checked').val() === undefined ? 0 : 1,
        'S': $('input[type="checkbox"][name="filter_success"]:checked').val() === undefined ? 0 : 1
    }
    const filter_text = $('#manageTable_filter input').val();

    if(filter_error == '' && filter_alert == '' && filter_success == '') {
        /*AlertSweet.fire({
            icon: 'error',
            title: 'Não é possível deixar todos os filtros desmarcados'
        });return false;*/
        $('input[type="checkbox"][name="filter_error"], input[type="checkbox"][name="filter_alert"], input[type="checkbox"][name="filter_success"]').attr('checked', true);
    }

    const filter = {'error': filter_error, 'alert': filter_alert, 'success': filter_success};
    $('input[type="checkbox"][name="filter_error"], input[type="checkbox"][name="filter_alert"], input[type="checkbox"][name="filter_success"]').attr('disabled', true);

    manageTable.destroy();
    manageTable = getTable(filter_type, filter_text, filter);
})

const getTable = (filter_type = {'E': 1,'W': 1,'S': 1}, filter_text = '', filter = {'error': 'checked','alert': 'checked','success': 'checked'}) => {
    var checked_t = "";
    if (filter['error'] != 'checked' & filter['alert'] != 'checked' & filter['success'] != 'checked') {
        checked_t = "checked";
    }
    return $('#manageTable').DataTable( {
        "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
        "processing": true,
        "serverSide": true,
        "responsive": true,
        "sortable": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline({
            url: base_url + 'integrations/fetchLogIntegrationData',
            pages: 2, // number of pages to cache
            data: {
                filter_type,
                // 'search': {'value': filter_text }
                store_id: $('#storeLog').val()
            },
        }),
        "order": [[ 3, 'desc' ]],
        "initComplete": function( settings, json ) {
            let el = $('#manageTable_wrapper div:eq(0)');
            el.find('div:eq(0)').toggleClass('col-sm-6 col-sm-8').addClass('d-flex justify-content-between flex-wrap')
                .append(`
                <div class="dataTables_filter_check col-md-6 no-padding" id="manageTable_filter_check">
                    <label class="col-sm-4 text-red"><input type="checkbox" name="filter_error" value="error" ${checked_t} ${filter['error']}/> <?=$this->lang->line('application_error');?></label>
                    <label class="col-sm-4 text-orange"><input type="checkbox" name="filter_alert" value="alert" ${checked_t} ${filter['alert']}/> <?=$this->lang->line('application_alert');?></label>
                    <label class="col-sm-4 text-success"><input type="checkbox" name="filter_success" value="success" ${checked_t} ${filter['success']}/> <?=$this->lang->line('application_success');?></label>
                </div>
            `);
            el.find('div:eq(3)').toggleClass('col-sm-6 col-sm-4');

            $('input[type="checkbox"][name="filter_success"]').iCheck({
                checkboxClass: 'icheckbox_minimal-green'
            });
            $('input[type="checkbox"][name="filter_alert"]').iCheck({
                checkboxClass: 'icheckbox_minimal-orange'
            });
            $('input[type="checkbox"][name="filter_error"]').iCheck({
                checkboxClass: 'icheckbox_minimal-red'
            });
            $('#manageTable_filter input[type="search"]').val(filter_text + '-').trigger('keyup').val(filter_text).trigger('keyup');
        }
    });
}

$('#historyLog #btnSearch').click(() => {
    const search    = $('#historyLog input[name="product_search"]').val();
    const store     = $('#historyLog select[name="store"]').val();
    const url       = "<?=base_url('integrations/search_logs_integration')?>";

    if(search.length == 0) {
        Swal.fire(
            '<?=$this->lang->line("messages_incorrect_data");?>',
            '<?=$this->lang->line("messages_enter_search_field");?>',
            'error'
        );
        return false;
    }

    $.ajax({
        url,
        type: "POST",
        data: { search, store },
        dataType: 'json',
        success: response => {
            console.log(response);
            $('#viewVariation .variations').empty();
            if (!response['success']) {
                Swal.fire(
                    '<?=$this->lang->line("messages_incorrect_data");?>',
                    response['data'],
                    'error'
                );
                return false;
            }

            $('#historyLog .result .result-search').html(response['data']);
            $('#historyLog .result').slideDown(1000);
            $('#historyLog .search').slideUp(1000);
        }, error: e => {
            console.log(e);
        }
    });
});

$('#storeLog').on('change', function () {
    if ($(this).val() == 0) {
        return;
    }
    if (manageTable !== undefined) {
        manageTable.destroy();
    }

    const filter_error   = $('input[type="checkbox"][name="filter_error"]:checked').val() === undefined ? '' : 'checked';
    const filter_alert   = $('input[type="checkbox"][name="filter_alert"]:checked').val() === undefined ? '' : 'checked';
    const filter_success = $('input[type="checkbox"][name="filter_success"]:checked').val() === undefined ? '' : 'checked';
    const filter_type = {
        'E': $('input[type="checkbox"][name="filter_error"]:checked').val() === undefined ? 0 : 1,
        'W': $('input[type="checkbox"][name="filter_alert"]:checked').val() === undefined ? 0 : 1,
        'S': $('input[type="checkbox"][name="filter_success"]:checked').val() === undefined ? 0 : 1
    }
    const filter_text = $('#manageTable_filter input').val();

    const filter = {'error': filter_error, 'alert': filter_alert, 'success': filter_success};
    $('input[type="checkbox"][name="filter_error"], input[type="checkbox"][name="filter_alert"], input[type="checkbox"][name="filter_success"]').attr('disabled', true);

    manageTable = getTable(filter_type, filter_text, filter);
});

$(document).on('click', '.btnCollapseLogRequestProduct', function(){
    $(this).closest('.form-group').find('.collapseLogRequestProduct').slideToggle();
});

</script>
