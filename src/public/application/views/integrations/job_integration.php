<!--

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
                <?php elseif($this->session->flashdata('warning')): ?>
                <div class="alert alert-warning alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?php echo $this->session->flashdata('warning'); ?>
                </div>
                <?php endif; ?>
                
                <?php
                if (validation_errors()) {
                    foreach (explode("</p>",validation_errors()) as $erro) {
                        $erro = trim($erro);
                        if ($erro!="") { ?>
                            <div class="alert alert-error alert-dismissible" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <?php echo $erro."</p>"; ?>
                            </div>
                        <?php	}
                    }
                } ?>
                <div class="box">
                    <div class="box-header d-flex justify-content-between flex-wrap">
                        <h3 class="box-title col-md-<?=count($storesView) > 1 ? '3' : '5'?> no-padding"><?=$this->lang->line('application_jobs_integration');?></h3>
                        <div class="col-md-<?=count($storesView) > 1 ? '9' : '7'?> d-flex justify-content-end flex-wrap no-padding">
                            <select class="form-control col-md-4" id="storeJob">
                                <?php
                                if (in_array('doIntegration', $user_permission))
                                    echo "<option value=''>### {$this->lang->line('application_all_stores')} ###</option>";
                                foreach ($storesView as $store)
                                    echo "<option value='{$store['id']}'>{$store['name']}</option>";
                                ?>
                            </select>
                            <a href="<?=base_url('integrations/log_integration')?>" class="btn btn-primary col-md-3 mr-2 btnHistoryIntegration"><i class="fas fa-history"></i> <?=$this->lang->line('application_history_integration');?></a>
                            <button type="button" class="btn btn-primary col-md-3 btnNewJob" data-toggle="modal" data-target="#newJob"><i class="fa fa-plus"></i> <?=$this->lang->line('application_job_new');?></button>
                            <?php if(in_array('doIntegration', $user_permission) && false){?>
                                <button type="button" class="btn btn-primary col-md-3 ml-2 btnTestProduct" data-toggle="modal" data-target="#testProduct"><i class="fas fa-tasks"></i> <?=$this->lang->line('application_job_test');?></button>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group col-md-12 col-xs-12">
                    	<?=$this->lang->line('messages_job_inactivation_doesnt_stop_webhook');?>
                    </div>
                    <div class="box-body">
                        <table id="manageTable" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                            <thead>
                                <tr>
                                    <th><?=$this->lang->line('application_integration');?></th>
                                    <th><?=$this->lang->line('application_name');?></th>
                                    <th><?=$this->lang->line('application_store');?></th>
                                    <th><?=$this->lang->line('application_last_run');?></th>
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


<div class="modal fade" tabindex="-1" role="dialog" id="newJob">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?=base_url('integrations/job_integration')?>" method="POST" enctype="multipart/form-data" id="formNewJob">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><span class="icon"></span> <?=$this->lang->line('application_job_register');?></h4>
                </div>
                <div class="modal-body">
                    <!--<div class="row">
                        <div class="col-md-12 form-group">
                            <label><?=$this->lang->line('application_integration');?></label>
                            <select class="form-control" name="integration" required>
                                <?php if (count($erp_integration) !== 1):?>
                                    <option value=""><?=$this->lang->line('application_select');?></option>
                                <?php endif; ?>
                                <?=in_array('bling', $erp_integration) ? '<option value="Bling">Bling</option>' : ''?>
                                <?=in_array('eccosys', $erp_integration) ? '<option value="Eccosys">Eccosys</option>' : ''?>
                                <?=in_array('tiny', $erp_integration) ? '<option value="Tiny">Tiny</option>' : ''?>
                                <?=in_array('bseller', $erp_integration) ? '<option value="BSeller">BSeller</option>' : ''?>
                                <?=in_array('pluggto', $erp_integration) ? '<option value="PluggTo">PluggTo</option>' : ''?>
                                <?=in_array('jn2', $erp_integration) ? '<option value="jn2">JN2</option>' : ''?>
                                <?=in_array('vtex', $erp_integration) ? '<option value="Vtex">VTEX</option>' : ''?>
                                <?=in_array('anymarket', $erp_integration) ? '<option value="AnyMarket">ANYMARKET</option>' : ''?>
                                <?=in_array('lojaintegrada', $erp_integration) ? '<option value="LojaIntegrada">Loja Integrada</option>' : ''?>
                            </select>
                        </div>
                    </div>-->
                    <div class="row" style="display: <?=count($storesView) > 1 ? 'block' : 'none'?>">
                        <div class="col-md-12 form-group">
                            <label><?=$this->lang->line('application_store');?></label>
                            <select class="form-control" name="store" required>
                                <?php if (count($storesView) !== 1):?>
                                    <option value=""><?=$this->lang->line('application_select');?></option>
                                <?php endif; ?>
                                <?php
                                foreach ($storesView as $store)
                                    echo "<option value='{$store['id']}'>{$store['id']} - {$store['name']}</option>";
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label>Job</label>
                            <select class="form-control" name="job" required disabled>
                                <option value=""><?=$this->lang->line('application_select_store');?></option>
                                <optgroup label="Produtos">
                                </optgroup>
                                <optgroup label="Pedidos">
                                </optgroup>
                                <optgroup label="Fila de Notificações" style="display: none;">
                                </optgroup>
                                <optgroup label="Todos">
                                </optgroup>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between flex-wrap">
                    <button type="button" class="btn btn-primary col-md-3" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-success col-md-3"><?=$this->lang->line('application_save');?></button>
                </div>
                <input type="hidden" name="integration">
            </form>
        </div>
    </div>
</div>
<?php
if(in_array('doIntegration', $user_permission) && false) {
?>
<div class="modal fade" tabindex="-1" role="dialog" id="testProduct">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span class="icon"></span> <?=$this->lang->line('application_job_test');?></h4>
            </div>
            <div class="modal-body search">
                <div class="row">
                    <div class="col-md-12 form-group">
                        <h4 class="text-center"><?=$this->lang->line('messages_test_product_integration');?></h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label><?=$this->lang->line('application_integrate_platform');?></label>
                        <select class="form-control" name="integration_search">
                            <?=in_array('bling', $erp_integration) ? '<option value="bling">Bling</option>' : ''?>
                            <?=in_array('eccosys', $erp_integration) ? '<option value="Eccosys">Eccosys</option>' : ''?>
                            <?=in_array('tiny', $erp_integration) ? '<option value="tiny">Tiny</option>' : ''?>
                            <?=in_array('bseller', $erp_integration) ? '<option value="BSeller">BSeller</option>' : ''?>
                        </select>
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
                        <label><?=$this->lang->line('application_enter_product_search');?></label>
                        <input type="text" class="form-control" name="product_search">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 form-group">
                        <ul>
                            <li><?=$this->lang->line('messages_test_product_integration_alert_1');?></li>
                            <li><?=$this->lang->line('messages_test_product_integration_alert_2');?></li>
                            <li><?=$this->lang->line('messages_test_product_integration_alert_3');?></li>
                        </ul>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 form-group d-flex justify-content-center">
                        <button type="button" class="btn btn-primary col-md-4" id="btnSearch"><?=$this->lang->line('application_search');?></button>
                    </div>
                </div>
            </div>
            <div class="modal-body result" style="display: none">
                <div class="row">
                    <div class="col-md-12 form-group">
                        <h3 class="text-center"><?=$this->lang->line('messages_test_product_result_search');?></h3>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 form-group result-search">

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 form-group text-center">
                        <button type="button" class="btn btn-primary" id="btnBackSearch" onclick="$('#testProduct .result').slideUp(1000);$('#testProduct .search').slideDown(1000)"><?=$this->lang->line('application_back_to_search');?></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-2" role="dialog" id="viewVariation">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span class="icon"></span> <?=$this->lang->line('application_variation');?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 form-group variations">

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>
<?php
}
?>
<style>
    .box-header:before,
    .modal-footer:before,
    .box-header:after,
    .modal-footer:after{
        display: none;
    }
    .wrapper {
        position: unset;
    }
    .box-header .select2,
    #storeJob {
        flex:2;
        margin: 0px 2px;
        display: <?=count($storesView) > 1 ? 'block' : 'none'?>
    }
    .btnHistoryIntegration,
    .btnNewJob,
    .btnTestProduct {
        flex: 1;
        margin: 0px 2px;
    }
</style>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.4/moment.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/plug-ins/1.10.21/sorting/datetime-moment.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
<!--<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.5/js/dataTables.responsive.min.js"></script>-->
<!--<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.5/css/responsive.dataTables.min.css"/>-->
<!--<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />-->


<script type="text/javascript">
    var manageTable;
    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function() {
        $("#mainIntegrationApiNav").addClass('active');
        $("#jobIntegration").addClass('active');
        $('#newJob select[name="store"]').select2({ dropdownParent: $("#newJob") });
        $('#testProduct select[name="store"]').select2({ dropdownParent: $("#testProduct") });
        $('#storeJob').select2();

        manageTable = getTable();
        $('#newJob [name="store"]').trigger('change');
    });

    $('#viewVariation').on('hidden.bs.modal', function(e){
        $("body").addClass("modal-open");
    });

    $(document).on('change', 'input[type="checkbox"][job-id][id^="job-"]', function(){

        const status    = $(this).is(':checked') ? 1 : 0;
        const job       = $(this).attr('job-id');
        const nameJob   = $(this).closest('tr').find('td:eq(1)').text();
        const situacao  = status == 1 ? '<?=$this->lang->line("application_activate")?>' : '<?=$this->lang->line("application_deactivate")?>';
        let title       = status == 1 ? '<?=$this->lang->line("messages_job_active")?>' : '<?=$this->lang->line("messages_job_deactivate")?>';
        title           +=  '<br>' + nameJob + '?';

        if (status) $(this).prop('checked', false);
        else $(this).prop('checked', true);

        AlertSweet.fire({
            title: title,
            text: status == 0 ? '<?=$this->lang->line("messages_alert_job_running")?>' : '',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#00a65a',
            cancelButtonColor: '#d33',
            confirmButtonText: situacao,
            cancelButtonText: '<?=$this->lang->line("application_cancel")?>'
        }).then((result) => {
            console.log(result);

            if (result.value) {
                const url = "<?=base_url('integrations/updateStatusJobIntegrations')?>";
                $('[type="checkbox"]').attr('disabled', true);
                $.ajax({
                    url,
                    type: "POST",
                    data: { job, status },
                    dataType: 'json',
                    success: response => {
                        console.log(response);
                        if (!response['success']) {
                            Swal.fire(
                                '<?=$this->lang->line("application_error_update")?>',
                                response['data'],
                                'error'
                            );
                        } else {
                            Swal.fire(
                                '<?=$this->lang->line("application_job_updated")?>',
                                'Job <strong>' + nameJob + '</strong> <?=$this->lang->line("messages_successfully_updated")?>',
                                'success'
                            );

                            if (status) $(this).prop('checked', true);
                            else $(this).prop('checked', false);
                        }
                        $('[type="checkbox"]').attr('disabled', false);
                    }, error: e => {
                        console.log(e);
                    }
                })
            }
        })
    });

    $(document).on('click', '.removeJob', function (){

        const job_id    = $(this).attr('job-id');
        const job_name  = $(this).closest('tr').find('td:eq(1)').text();
        const job_int   = $(this).closest('tr').find('td:eq(0)').text();
        const job_sts   = $(this).closest('tr').find('td:eq(4) input').is(':checked') ? 1 : 0;;

        AlertSweet.fire({
            title: '<?=$this->lang->line("messages_remove_job_permanently")?><br>'+job_name+' - '+job_int+' ?',
            text: job_sts == 0 ? '<?=$this->lang->line("messages_alert_job_running")?>' : '',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#337ab7',
            confirmButtonText: '<?=$this->lang->line("application_delete")?>',
            cancelButtonText: '<?=$this->lang->line("application_cancel")?>'
        }).then((result) => {
            console.log(result);
            if (result.value) {
                const url = "<?=base_url('integrations/remove_job_integration')?>";
                $.ajax({
                    url,
                    type: "POST",
                    data: { job_id },
                    dataType: 'json',
                    success: response => {
                        console.log(response);
                        if (!response['success']) {
                            Swal.fire(
                                '<?=$this->lang->line("application_error_delete")?>',
                                response['data'],
                                'error'
                            );
                        } else {
                            Swal.fire(
                                '<?=$this->lang->line("application_job_excluded")?>',
                                'Job <strong>' + job_name + '</strong> <?=$this->lang->line("messages_successfully_removed")?>!',
                                'success'
                            );

                            manageTable.destroy();
                            manageTable = getTable();
                        }
                    }, error: e => {
                        console.log(e);
                    }
                })
            }
        })
        return false;
    });

    $(document).on('click', '.emptyDateRun', function (){

        const job_id    = $(this).attr('job-id');
        const job_name  = $(this).closest('tr').find('td:eq(1)').text();
        const job_int   = $(this).closest('tr').find('td:eq(0)').text();
        const job_sts   = $(this).closest('tr').find('td:eq(4) input').is(':checked') ? 1 : 0;;

        AlertSweet.fire({
            title: '<?=$this->lang->line("messages_clean_date_job")?><br>'+job_name+' - '+job_int+' ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#337ab7',
            confirmButtonText: '<?=$this->lang->line("application_clear")?>',
            cancelButtonText: '<?=$this->lang->line("application_cancel")?>'
        }).then((result) => {
            console.log(result);
            if (result.value) {
                const url = "<?=base_url('integrations/clear_job_integration')?>";
                $.ajax({
                    url,
                    type: "POST",
                    data: { job_id },
                    dataType: 'json',
                    success: response => {
                        console.log(response);
                        if (!response['success']) {
                            Swal.fire(
                                '<?=$this->lang->line("application_error_update")?>',
                                response['data'],
                                'error'
                            );
                        } else {
                            Swal.fire(
                                '<?=$this->lang->line("application_job_updated")?>',
                                'Job <strong>' + job_name + '</strong> <?=$this->lang->line("messages_successfully_updated")?>!',
                                'success'
                            );

                            manageTable.destroy();
                            manageTable = getTable();
                        }
                    }, error: e => {
                        console.log(e);
                    }
                })
            }
        })
        return false;
    });

    $('#formNewJob').on('submit', function () {
        const integration   = $('input[name="integration"]').val();
        const store         = $('select[name="store"]').val();
        const job           = $('select[name="job"]').val();
        const url           = "<?=base_url('integrations/new_job_integration')?>";

        $.ajax({
            url,
            type: "POST",
            data: { integration, store, job },
            dataType: 'json',
            success: response => {
                console.log(response);

                if (response['warning']) {
                    response['data'] += '<br><br><h4>Para ativar sua integração, na coluna <b>Ação</b> ative os Jobs desejados.</h4>';
                    Swal.fire(
                        '<?=$this->lang->line("application_job_created_with_exception")?>',
                        response['data'],
                        'warning'
                    ).then(function () {
                        setTimeout(() => { $('body').css('padding-right', '0px') }, 500);
                    });
                }
                else if (!response['success']) {
                    Swal.fire(
                        '<?=$this->lang->line("application_error_create")?>',
                        response['data'],
                        'error'
                    ).then(function () {
                        setTimeout(() => { $('body').css('padding-right', '0px') }, 500);
                    });
                    return false;
                } else {

                    let namesJobs = '';
                    response['name'].forEach(item => {
                        namesJobs += 'Job <strong>' + item + '</strong> <?=$this->lang->line("messages_successfully_created")?><br>';
                    })
                    namesJobs += '<br><br><h4>Para ativar sua integração, na coluna <b>Ação</b> ative os Jobs desejados.</h4>';

                    Swal.fire(
                        '<?=$this->lang->line("application_job_created")?>',
                        namesJobs,
                        'success'
                    ).then(function () {
                        setTimeout(() => { $('body').css('padding-right', '0px') }, 500);
                    });
                }

                $('#newJob').modal('hide');
                //$('#newJob [name="integration"]').val($('#newJob [name="integration"] option:first').val());
                //$('#newJob [name="store"]').val($('#newJob [name="store"] option:first').val());
                $('#newJob [name="job"]').val($('#newJob [name="job"] option:first').val());

                manageTable.destroy();
                manageTable = getTable();

            }, error: e => {
                console.log(e);
            }
        });
        return false;
    });

    $('#testProduct #btnSearch').click(() => {
        const integration   = $('#testProduct select[name="integration_search"]').val();
        const search        = $('#testProduct input[name="product_search"]').val();
        const store         = $('#testProduct select[name="store"]').val();
        const url           = "<?=base_url('integrations/test_product_integration')?>";

        if(search.length == 0) {
            Swal.fire(
                '<?=$this->lang->line("messages_incorrect_data")?>',
                '<?=$this->lang->line("messages_enter_search_field")?>',
                'error'
            );
            return false;
        }

        $.ajax({
            url,
            type: "POST",
            data: { integration, search, store },
            dataType: 'json',
            success: response => {
                console.log(response);
                $('#viewVariation .variations').empty();
                if (!response['success']) {
                    Swal.fire(
                        '<?=$this->lang->line("application_error_has_ocurred")?>',
                        response['data'],
                        'error'
                    );
                    return false;
                }

                let strResult = "<table class='table col-md-12'><thead><tr><th><?=$this->lang->line('application_field')?></th><th><?=$this->lang->line('application_value')?></th></tr></thead><tbody>";
                let arrImage = [];
                let arrGrade = [];
                let strVariation = "";
                let viewCountVar = 0;

                for (var [k, v] of Object.entries(response['data'])) {
                    arrImage = [];
                    if (integration == 'tiny') {
                        if (k == 'anexos' || k == 'imagens_externas' || k == 'variacoes') {
                            if (v.length > 0 && k == 'anexos') {
                                for (let img = 0; img < v.length; img++) {
                                    arrImage.push(v[img].anexo);
                                }
                            }
                            if (v.length > 0 && k == 'imagens_externas') {
                                for (let img = 0; img < v.length; img++) {
                                    arrImage.push(v[img].imagem_externa.url);
                                }
                            }
                            if (v.length > 0 && k == 'variacoes') {
                                for (let variation = 0; variation < v.length; variation++) {
                                    viewCountVar = variation + 1;
                                    strVariation = `<div class="col-md-12 alert alert-warning mb-1 mt-1 pb-3 pt-3"><h4 class="font-weight-bold mb-1">#${viewCountVar} <?=$this->lang->line('application_variation')?></h4></div><table class="table"><thead><tr><th><?=$this->lang->line('application_field')?></th><th><?=$this->lang->line('application_value')?></th></tr></thead><tbody>`;
                                    for (var [k_var, v_var] of Object.entries(v[variation].variacao)) {
                                        if (k_var == 'grade') {
                                            arrGrade = [];
                                            for (var [k_var_grade, v_var_grade] of Object.entries(v_var)) {
                                                arrGrade.push(`<strong>${k_var_grade}</strong>: ${v_var_grade}`);
                                            }
                                            v_var = arrGrade.join('<br><hr style="margin: 5px 0px;">');
                                        }
                                        strVariation += `<tr><td>${k_var}</td><td>${v_var}</td></tr>`;
                                    }
                                    strVariation += '</tbody></table>'
                                    $('#viewVariation .variations').append(strVariation);
                                }
                                arrImage.push('<button class="btn btn-info col-md-4" data-toggle="modal" data-target="#viewVariation"><?=$this->lang->line("application_variations_view")?></button>')
                            }
                            v = arrImage.join('<br>');
                        }
                    }
                    else if (integration == 'bling') {

                        if (k == 'categoria') {
                            v = v.descricao
                        }
                        if (k == 'imagem') {
                            let new_v_img = [];
                            for (var [k_img, v_img] of Object.entries(v)) {
                                new_v_img.push(v_img.link);
                            }
                            v = new_v_img.join('<br><hr style="margin: 5px 0px;">');
                        }
                        if (k == 'produtoLoja') {
                            v = 'Preço_multiloja: ' + v.preco.preco;
                        }
                        if (k == 'depositos') {
                           continue;
                        }
                        if (k == 'variacoes') {
                            console.log(v);
                            for (let variation = 0; variation < v.length; variation++) {
                                viewCountVar = variation + 1;
                                strVariation = `<div class="col-md-12 alert alert-warning mb-1 mt-1 pb-3 pt-3"><h4 class="font-weight-bold mb-1">#${viewCountVar} <?=$this->lang->line('application_variation')?></h4></div><table class="table"><thead><tr><th><?=$this->lang->line('application_field')?></th><th><?=$this->lang->line('application_value')?></th></tr></thead><tbody>`;
                                for (var [k_var, v_var] of Object.entries(v[variation].variacao)) {
                                    if (k_var == 'depositos') {
                                        continue;
                                    }
                                    strVariation += `<tr><td>${k_var}</td><td>${v_var}</td></tr>`;
                                }
                                strVariation += '</tbody></table>'
                                $('#viewVariation .variations').append(strVariation);
                            }
                            v = '<button class="btn btn-info col-md-4" data-toggle="modal" data-target="#viewVariation"><?=$this->lang->line("application_variations_view")?></button>';
                        }
                    }
                    else if (integration == 'eccosys') {
                        if (k == 'anexos' || k == 'imagens_externas' || k == 'variacoes') {
                            if (v.length > 0 && k == 'anexos') {
                                for (let img = 0; img < v.length; img++) {
                                    arrImage.push(v[img].anexo);
                                }
                            }
                            if (v.length > 0 && k == 'imagens_externas') {
                                for (let img = 0; img < v.length; img++) {
                                    arrImage.push(v[img].imagem_externa.url);
                                }
                            }
                            if (v.length > 0 && k == 'variacoes') {
                                for (let variation = 0; variation < v.length; variation++) {
                                    viewCountVar = variation + 1;
                                    strVariation = `<div class="col-md-12 alert alert-warning mb-1 mt-1 pb-3 pt-3"><h4 class="font-weight-bold mb-1">#${viewCountVar} <?=$this->lang->line('application_variation')?></h4></div><table class="table"><thead><tr><th><?=$this->lang->line('application_field')?></th><th><?=$this->lang->line('application_value')?></th></tr></thead><tbody>`;
                                    for (var [k_var, v_var] of Object.entries(v[variation].variacao)) {
                                        if (k_var == 'grade') {
                                            arrGrade = [];
                                            for (var [k_var_grade, v_var_grade] of Object.entries(v_var)) {
                                                arrGrade.push(`<strong>${k_var_grade}</strong>: ${v_var_grade}`);
                                            }
                                            v_var = arrGrade.join('<br><hr style="margin: 5px 0px;">');
                                        }
                                        strVariation += `<tr><td>${k_var}</td><td>${v_var}</td></tr>`;
                                    }
                                    strVariation += '</tbody></table>'
                                    $('#viewVariation .variations').append(strVariation);
                                }
                                arrImage.push('<button class="btn btn-info col-md-4" data-toggle="modal" data-target="#viewVariation"><?=$this->lang->line("application_variations_view")?></button>')
                            }
                            v = arrImage.join('<br>');
                        }
                    }
                    
                    strResult += `<tr><td>${k}</td><td>${v}</td></tr>`;
                }
                strResult += "</tbody></table>";

                $('#testProduct .result .result-search').html(strResult);
                $('#testProduct .result').slideDown(1000);
                $('#testProduct .search').slideUp(1000);
            }, error: e => {
                console.log(e);
            }
        });
    })

    const getTable = () => {
        return $('#manageTable').DataTable( {
            "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
            "processing": true,
            "serverSide": true,
            "responsive": true,
            "sortable": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline( {
                url: base_url + 'integrations/fetchJobIntegrationData',
                pages: 2, // number of pages to cache
                data: { store_id: $('#storeJob').val()}
            } ),
            "order": [[ 3, 'desc' ]],
            "aoColumns": [
                null,
                null,
                null,
                null,
                { "sClass": "d-flex justify-content-around" }
            ],
            "initComplete": function( settings, json ) {
                $('[data-toggle="tootip"]').tooltip()
            }
        } );
    }

    $('#storeJob').on('change', function () {
        manageTable.destroy();
        manageTable = getTable();
    });

    $('[name="store"]').change(function(){
        const store = $(this).val();
        const elementJob = $('[name="job"]');

        if (store === '') {
            elementJob.find('optgroup[label="Produtos"], optgroup[label="Pedidos"], optgroup[label="Fila de Notificações"], optgroup[label="Todos"]').empty().append('');
            elementJob.prop('disabled', true).find('option[value=""]').text("<?=$this->lang->line('application_select_store')?>");
            return false;
        }

        elementJob.prop('disabled', true).find('option[value=""]').text("<?=$this->lang->line('application_loading')?>");

        const url = "<?=base_url('integrations/getJobsByStore')?>";
        $.ajax({
            url,
            type: "POST",
            data: { store },
            dataType: 'json',
            success: response => {
                console.log(response);
                let product = '';
                let order = '';
                let queueNotification = '';
                let arrProductOrder = [];
                let productOrder = '';

                for (let [job, nameJob] of Object.entries(response.product)) {
                    product += `<option value="${job}">${nameJob}</option>`;
                    arrProductOrder.push(`"${job}"`);
                }
                for (let [job, nameJob] of Object.entries(response.order)) {
                    order += `<option value="${job}">${nameJob}</option>`;
                    arrProductOrder.push(`"${job}"`);
                }
                for (let [job, nameJob] of Object.entries(response.queueNotifications)) {
                    queueNotification += `<option value="${job}">${nameJob}</option>`;
                    arrProductOrder.push(`"${job}"`);
                }
                productOrder = "<option value='[" + arrProductOrder.join(',') + "]'><?=$this->lang->line('application_job_all')?></option>";

                elementJob.find('optgroup[label="Produtos"]').empty().append(product);
                elementJob.find('optgroup[label="Pedidos"]').empty().append(order);
                elementJob.find('optgroup[label="Fila de Notificações"]').empty().append(queueNotification);
                elementJob.find('optgroup[label="Todos"]').empty().append(productOrder);
                elementJob.prop('disabled', false).find('option[value=""]').text("<?=$this->lang->line('application_select')?>");
                $('#newJob input[name="integration"]').val(response.integration);
                if (arrProductOrder.length === 0) {
                    elementJob.prop('disabled', true).find('option[value=""]').text("<?=$this->lang->line('messages_integration_unavailable_to_configuration')?>");
                }
                elementJob.find('optgroup[label="Fila de Notificações"]').hide();
                if(queueNotification.length > 0) {
                    elementJob.find('optgroup[label="Fila de Notificações"]').show();
                }
            }, error: e => {
                console.log(e);
            }
        })
    });

</script>
