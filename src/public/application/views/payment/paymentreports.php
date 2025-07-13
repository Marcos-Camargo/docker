
<!-- Content Wrapper. Contains page content -->
<script>

    let transfer_value = 0;
    let transfers_selected = [];
    let current_cycles = [];
    let modal_data;
    let base_url = "<?php echo base_url(); ?>";
    // let double_store = 0;
    $("#paraMktPlaceNav").addClass('active');
    $("#createPaymentReport").addClass('active');
</script>

<div id='loading_wrap' style='
            display: none; 
            position:fixed; 
            z-index: 1050; 
            padding: 20vw calc(50vw - 50px); 
            color: #fff; 
            font-size: 2rem;
            height:100%; 
            width:100%; 
            overflow:hidden; 
            top:0; left:0; bottom:0; right:0; 
            background-color: rgba(0,0,0,0.5);'><?=$this->lang->line('application_process');?>...</div>

<div class="content-wrapper">

    <?php  $data['pageinfo'] = "";
    $data['page_now'] = "payment_report";
    $this->load->view('templates/content_header',$data); ?>

    <style>

        .box-title{
            margin-left: 14px;
            /* margin-top: 80px !important; */
        }
        .box-title-modal{
            margin-top: 40px !important;
            margin-left: 20px;
        }

        .box-btns{
            padding: 10px;
            margin: 10px;
            border: 0;
            width: 100%;
        }

        .box-btns button{
            margin-left: 20px;
            position: relative;
            float: right;
        }

        #box-btns, #transfer-list-header{
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            z-index: 100;
            background-color: #fff;
            padding: 10px 10px 15px 15px;
        }

        .box-btns-floating{
            -webkit-box-shadow: 0px 15px 9px -9px rgba(0,0,0,0.29);
            box-shadow: 0px 15px 9px -9px rgba(0,0,0,0.29);
        }

        .negative{
            color:  #dc3545;
            /* color:  #008000; */
            font-weight: bold;
        }

        .payment-report-icon{
            margin-right: 4px;
        }

        .rep-btns-mark{
            position: absolute;
            top: 15px;
            right: -7px;
            font-size: 7px;
            background-color: green;
            color: white;
            border-radius: 50%;
            padding: 3px;
        }

        .box-total-grid-update{
            margin: 0 20px 0 0;
            padding: 4px 10px;
            position: relative;
            float: right;
            border: 1px solid #666;
            text-align: right;
            border-radius: 3px;
            margin-top: 5px;
            border: 0;
            /*font-weight: bold;*/
        }

        .box-total-grid-update button{
            margin-left: 20px;
        }

    </style>

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

                <?php if(in_array('createPaymentReport', $user_permission)): ?>

                    <div class="box">
                        <div class="box-body" id="grid-body">





                            <table  id="report-table" class="table table-bordered table-striped" >
                                <thead>
                                <tr>
                                    <td colspan="<?=(isset($this->allow_transfer_between_accounts) && $this->allow_transfer_between_accounts == "1") ? 9 : 10;?>">
                                        <div class="col-md-6">
                                            <h4><?=$this->lang->line('payment_report_box_title');?>:</h4>
                                            <table style="border-spacing: 10px; border-collapse: separate;">
                                                <tr>
                                                    <td><?=$this->lang->line('payment_report_report_tag_id');?>:</td>
                                                    <td id="conciliation-id" data-conciliation-id = "<?=$conciliation_data['id']?>"><strong><?=$conciliation_data['id']?></strong></td>
                                                </tr>
                                                <tr>
                                                    <td><?=$this->lang->line('payment_report_report_tag_transfer');?>:</td>
                                                    <td><strong><span id="cycle-reference"><?=$conciliation_data['ano_mes']?></span></strong> <span id="cycle-reference">(<?=$conciliation_data['data_inicio']. ' '. strtolower($this->lang->line('application_until')).' '.$conciliation_data['data_fim'] ?>)</span></td>
                                                </tr>
                                                <tr>
                                                    <td><?=$this->lang->line('payment_report_report_tag_lot');?>:</td>
                                                    <td><strong><span id="cycle-reference"><?=$conciliation_data['lote']?></span></strong></td>
                                                </tr>
                                                <tr>
                                                    <td><?=$this->lang->line('payment_report_report_tag_status');?>:</td>
                                                    <td><strong><?=$conciliation_data['conciliation_status']?></strong></td>
                                                </tr>
                                            </table>
                                        </div>

                                        <div class="col-md-6 text-right">
                                            <label for="slc_cycle"><?=$this->lang->line('payment_report_filter_cycle_label');?></label>
                                            <br/>
                                            <select class="form-control text-right" style="width: auto !important; position: relative; float: right; clear: both !important; margin: 10px auto; " id="slc_cycle" name="slc_cycle" >

                                                <?php foreach ($all_cycles as $key => $cycle): ?>
                                                    <option value="<?=$cycle['ano_mes'].'/'.$cycle['lote']?>"
                                                        <?php
                                                        if ($cycle['ano_mes'].$cycle['lote'] == $conciliation_data['ano_mes'].$conciliation_data['lote']):
                                                            ?>
                                                            disabled="disabled" selected
                                                        <?php
                                                        endif;
                                                        ?>
                                                    ><?=$cycle['ano_mes'].' ('.$cycle['data_inicio'].' '.strtolower($this->lang->line('application_until')).' '.$cycle['data_fim'].')'?></option>
                                                <?php endforeach ?>

                                            </select>

                                            <button type="button" style="margin:10px 20px; position: relative; float: right; clear2222: both !important;" id="btn-export" class="btn btn-success text-right"><?=$this->lang->line('payment_balance_transfers_btn_export');?> &nbsp; <i class="fa fa-fw fa-file-excel-o"></i></button>
                                        </div>

                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="<?=(isset($this->allow_transfer_between_accounts) && $this->allow_transfer_between_accounts == "1") ? 9 : 10;?>" class="text-right">

                                        <div class="box-total-grid-update text-right">
                                            <?=$this->lang->line('application_dt_alteracao').': '.$last_update?>
                                            <button type="button" id="btn-update-balance" class="btn btn-danger btn-sm"><?=$this->lang->line('payment_balance_transfers_btn_update_balance');?></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr id="transfer-list-header">
                                    <th><?=$this->lang->line('payment_report_list_column_store_id');?></th>
                                    <th><?=$this->lang->line('payment_report_list_column_store_name');?></th>
                                    <th><?=$this->lang->line('payment_report_list_column_amount');?></th>
                                    <th><?=$this->lang->line('payment_report_list_column_return');?></th>
                                    <th><?=$this->lang->line('payment_report_list_column_legal_negative');?></th>
                                    <th><?=$this->lang->line('payment_report_list_column_legal_positive');?></th>
                                    <th><?=$this->lang->line('payment_report_list_column_liquid');?></th>
                                    <th>Saldo Em Carteira</th>
                                    <th><?=$this->lang->line('action_cycle');?></th>
                                    <?php
                                    if (isset($this->allow_transfer_between_accounts) && $this->allow_transfer_between_accounts == "1"):
                                        ?>
                                        <th style="text-align: center;" width="5%"><?=$this->lang->line('payment_report_list_column_adjustment');?></th>
                                    <?php
                                    endif;
                                    ?>
                                </tr>
                                </thead>


                                <tbody id="report-table-list">

                                <tr style="display:none">
                                    <td><?=$this->lang->line('payment_report_report_tag_transfer');?>:</td>
                                    <td colspan="5"><?=$conciliation_data['ano_mes']?></td>
                                </tr>

                                <tr style="display:none">
                                    <td><?=$this->lang->line('payment_report_report_tag_status');?>:</td>
                                    <td colspan="6"><?=$conciliation_data['conciliation_status']?></td>
                                </tr>

                                <tr style="display:none"><td colspan=<?php
                                    if (isset($this->allow_transfer_between_accounts) && $this->allow_transfer_between_accounts == "1")
                                    {
                                        echo '7';
                                    }
                                    else
                                    {
                                        echo '6';
                                    }
                                    ?>></td></tr>

                                <tr style="display:none">
                                    <th><?=$this->lang->line('payment_report_list_column_store_id');?></th>
                                    <th><?=$this->lang->line('payment_report_list_column_store_name');?></th>
                                    <th><?=$this->lang->line('payment_report_list_column_amount');?></th>
                                    <th><?=$this->lang->line('payment_report_list_column_return');?></th>
                                    <th><?=$this->lang->line('payment_report_list_column_legal_negative');?></th>
                                    <th><?=$this->lang->line('payment_report_list_column_legal_positive');?></th>
                                    <th><?=$this->lang->line('payment_report_list_column_liquid');?></th>
                                    <th>Saldo Em Carteira</th>
                                    <th>Responsável Ajuste Manual</th>
                                    <th><?=$this->lang->line('action_cycle');?></th>
                                    <?php
                                    if (isset($this->allow_transfer_between_accounts) && $this->allow_transfer_between_accounts == "1"):
                                        ?>
                                        <th style="text-align: center;"><?=$this->lang->line('payment_report_list_column_adjustment');?></th>
                                    <?php
                                    endif;
                                    ?>
                                </tr>

                                <?php

                                if (is_array($transfer_rows) && !empty($transfer_rows))
                                {
                                    $unadjustables = [21, 25, 26];

                                    foreach ($transfer_rows as $key => $transfer) {
                                        $multiple_positives = 0;

                                        $transfer['positive']       = number_format($transfer['positive'] ?? 0, 2, ",", ".");
                                        $transfer['negative']       = number_format($transfer['negative'] ?? 0, 2, ",", ".");
                                        $transfer['legal_positive'] = number_format($transfer['legal_positive'] ?? 0, 2, ",", ".");
                                        $transfer['legal_negative'] = number_format($transfer['legal_negative'] ?? 0, 2, ",", ".");
                                        $transfer['liquid']         = number_format($transfer['liquid'] ?? 0, 2, ",", ".");

                                        $transfer['positive_status']       = $transfer['positive_status'] ?? '';

                                        if (is_array($transfer['positive_status']))
                                        {
                                            $multiple_positives = max($transfer['positive_status']);
                                        }

                                        $transfer['negative_status']       = $transfer['negative_status'] ?? '';
                                        $transfer['legal_positive_status'] = $transfer['legal_positive_status'] ?? '';
                                        $transfer['legal_negative_status'] = $transfer['legal_negative_status'] ?? '';

                                        $transfer['positive_status_icon']       = $transfer['positive_status_icon'] ?? '';
                                        $transfer['negative_status_icon']       = $transfer['negative_status_icon'] ?? '';
                                        $transfer['legal_positive_status_icon'] = $transfer['legal_positive_status_icon'] ?? '';
                                        $transfer['legal_negative_status_icon'] = $transfer['legal_negative_status_icon'] ?? '';
                                        $transfer['liquid_status_icon']         = $transfer['liquid_status_icon'] ?? '';

                                        $transfer['positive_status_message']       = $transfer['positive_status_message'] ?? '';
                                        $transfer['negative_status_message']       = $transfer['negative_status_message'] ?? '';
                                        $transfer['legal_positive_status_message'] = $transfer['legal_positive_status_message'] ?? '';
                                        $transfer['legal_negative_status_message'] = $transfer['legal_negative_status_message'] ?? '';
                                        $transfer['liquid_status_message']         = $transfer['liquid_status_message'] ?? '';

                                        $allow_ajustment = 0;

                                        if ($transfer['positive_status']) {
                                            foreach ($transfer['positive_status'] as $k => $v)
                                            {
                                                if (in_array($v, $unadjustables)){
                                                    $allow_ajustment++;
                                                }
                                            }
                                        }
                                        if ($transfer['negative_status']) {
                                            foreach ($transfer['negative_status'] as $k => $v)
                                            {
                                                if (in_array($v, $unadjustables)){
                                                    $allow_ajustment++;
                                                }
                                            }
                                        }
                                        if ($transfer['legal_positive_status'])
                                        {
                                            foreach ($transfer['legal_positive_status'] as $k => $v)
                                            {
                                                if (in_array($v, $unadjustables)){
                                                    $allow_ajustment++;
                                                }
                                            }
                                        }
                                        if ($transfer['legal_negative_status'])
                                        {
                                            foreach ($transfer['legal_negative_status'] as $k => $v)
                                            {
                                                if (in_array($v, $unadjustables)){
                                                    $allow_ajustment++;
                                                }
                                            }
                                        }
                                        ?>
                                        <tr id="rep-row-<?=$key?>">
                                            <td class="rep-col-id"><?=$key?></td>
                                            <td class="rep-col-name"><?=$transfer['store_name']?></td>
                                            <td class="rep-col-positive <?php echo ($transfer['positive_status'] == 26 || $multiple_positives >= 26) ? ' negative' : ''; ?>" title="<?=$transfer['positive_status_message']?>"><?=$transfer['positive_status_icon']?> R$ <?=$transfer['positive']?></td>
                                            <td class="rep-col-negative <?php echo ($transfer['negative_status'][0] == 26) ? ' negative' : ''; ?>" title="<?=$transfer['negative_status_message']?>"><?=$transfer['negative_status_icon']?> R$ <?=$transfer['negative']?></td>
                                            <td class="rep-col-legal_negative <?php echo ($transfer['legal_negative_status'][0] == 26) ? ' negative' : ''; ?>" title="<?=$transfer['legal_negative_status_message']?>"><?=$transfer['legal_negative_status_icon']?> R$ <?=$transfer['legal_negative']?></td>
                                            <td class="rep-col-legal_positive <?php echo ($transfer['legal_positive_status'][0] == 26) ? ' negative' : ''; ?>" title="<?=$transfer['legal_positive_status_message']?>"><?=$transfer['legal_positive_status_icon']?> R$ <?=$transfer['legal_positive']?></td>
                                            <td class="rep-col-liquid" title="<?=$transfer['liquid_status_message']?>"><?=$transfer['liquid_status_icon']?> <?=money($transfer['liquid'])?></td>
                                            <td class="rep-col-liquid" title="Saldo Em Carteira"> <?=money($transfer['balance'])?></td>
                                            <td class="" style="display: none;" title="Saldo Em Carteira"><?=$transfer['paid_status_responsible']?></td>
                                            <td class="reprocess text-center repasse3"><?php
                                                $color = 'green';
                                                $icon = 'check';
                                                $opacity = 1;
                                                $disabled = '';
                                                $errorOccurred = false; // Variável para controlar a ocorrência de erro

                                                // Condicional para repasse com sucesso
                                                if (isset($transfer['positive_status'][0]) && $transfer['positive_status'][0] == 21) {
                                                    if (intval($transfer['balance'] * 100) >= intval($transfer['withdraw'] * 100)) {
                                                        $disabled = ''; // Manter o botão habilitado se houver saldo suficiente
                                                        $opacity = 1; // Ajustar a opacidade para indicar que está desabilitado

                                                    }   else{
                                                        $disabled = ' disabled="disabled" '; // Desabilitar o botão se não houver saldo suficiente
                                                        $color = 'red'; // Definir a cor como vermelha quando desativado
                                                        $opacity = 0.3; // Ajustar a opacidade para indicar que está desativado
                                                    } } else {
                                                    // Condicional para repasse com erro
                                                    if (
                                                        isset($transfer['positive_status'][0]) &&
                                                        ($transfer['positive_status'][0] == 25 ||
                                                            $transfer['positive_status'][0] == 26 ||
                                                            $multiple_positives >= 26 ||
                                                            isset($transfer['negative_status'][0]) && $transfer['negative_status'][0] == 26 ||
                                                            isset($transfer['legal_negative_status'][0]) && $transfer['legal_negative_status'][0] == 26)
                                                    ) {
                                                        $color = 'red';
                                                        $icon = 'ban';
                                                        $disabled = ' disabled = "disabled" ';
                                                        $opacity = 0.3;
                                                        $errorOccurred = true; // Marca que ocorreu um erro

                                                        // Verificação secundária de erro
                                                        if (
                                                            (''.intval($transfer['balance'] * 100) >= ''.intval($transfer['withdraw'] * 100) &&
                                                                ($transfer['positive_status'][0] == 25 || $transfer['positive_status'][0] == 26 || $multiple_positives >= 26)) // positivo com erro, excluindo status 21
                                                            ||
                                                            (isset($transfer['negative_status'][0]) && $transfer['negative_status'][0] == 26 &&
                                                                ''.intval($transfer['balance'] * 100) > ''.intval(abs($transfer['negative']) * 100)) // cancelamento com erro
                                                            ||
                                                            (isset($transfer['legal_negative_status'][0]) && $transfer['legal_negative_status'][0] == 26 &&
                                                                ''.intval($transfer['balance'] * 100) >= ''.intval(abs($transfer['legal_negative'] * 100))) // juridico negativo com erro
                                                        ) {
                                                            $color = 'green';
                                                            $icon = 'check';
                                                            $disabled = ''; // Habilitar botão novamente se as verificações secundárias passarem
                                                            $opacity = 1;
                                                            $errorOccurred = false; // Desmarca o erro se as verificações secundárias passarem
                                                        }
                                                    }
                                                }

                                                if ($allow_ajustment > 0) {
                                                    echo '<button class="btn-reprocess" style="position: relative; opacity: '.$opacity.';" data-store-id="'.$key.'" '.$disabled.' 
                                                        data-store-name="'.$transfer['store_name'].'" title="'.$this->lang->line('payment_report_rep_title').'">
                                                        
                                                        <i class="fa fa-refresh" aria-hidden="true" style="color: '.$color.';"></i></button>';
                                                            echo '&nbsp;';
                                                            echo '<button class="btn-mark-paid" style="position: relative;" data-store-id="'.$key.'" 
                                                        data-store-name="'.$transfer['store_name'].'" title="Marcar Repasse como pago manualmente">
                                                        <i class="fa fa-solid fa-money-bill" aria-hidden="true" style="color: orange;"></i>
                                                    </button>';
                                                }
                                                ?>
                                            </td>
                                            <?php
                                            if (isset($this->allow_transfer_between_accounts) && $this->allow_transfer_between_accounts == "1"):
                                                ?>
                                                <td class="check-chargeback" style="text-align: center;">
                                                    <?php
                                                    if ($allow_ajustment > 0 && $transfer['withdraw'] > 0):
                                                        ?>
                                                        <input type="checkbox" class="transfer-selected" value="<?=$key?>">
                                                    <?php
                                                    endif;
                                                    ?>
                                                </td>
                                            <?php
                                            endif;
                                            ?>
                                        </tr>
                                        <?php
                                    }
                                }


                                ?>

                                <script src="https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js"></script>
                                <script>

                                    $(function()
                                    {
                                        $('#slc_cycle').change(function()
                                        {
                                            var cycle = $(this).val();

                                            if (cycle)
                                            {
                                                window.location.href = base_url + 'payment/paymentReports/' + cycle;
                                            }
                                        });


                                        $('#btn-export').click(function()
                                        {
                                            var cycle = $('#cycle-reference').text();

                                            TableToExcel.convert(document.getElementById("report-table-list"), {
                                                name: slugify(cycle+'-<?=substr($this->lang->line('application_payment_report'),0,30);?>')+'.xlsx',
                                                sheet: {
                                                    name: "Relatorio"
                                                }
                                            });
                                        });


                                        $('#btn-update-balance').click(function()
                                        {
                                            Swal.fire({
                                                title: '<?=$this->lang->line('payment_balance_transfers_modal_balances_title')?>',
                                                icon: 'info',
                                                html: "<?=$this->lang->line('payment_balance_transfers_modal_balances_text')?>",
                                                showCancelButton: true,
                                                confirmButtonText: '<?=$this->lang->line('application_update')?>',
                                                showLoaderOnConfirm: true,
                                                preConfirm: (balanceUpdate) => {
                                                    return fetch(base_url+`payment/updateBalances`)
                                                        .then(response => {
                                                            // console.log(response);
                                                            if (!response.ok)
                                                            {
                                                                throw new Error(response.statusText)
                                                            }
                                                        })
                                                        .catch(error => {
                                                            Swal.showValidationMessage(
                                                                `Request failed: ${error}`
                                                            )
                                                        })
                                                },
                                                allowOutsideClick: () => !Swal.isLoading()
                                            }).then((result) => {
                                                if (result.value)
                                                {
                                                    Swal.fire({
                                                        title: '<?=$this->lang->line('payment_balance_transfers_modal_balances_done_title')?>',
                                                        icon: 'success',
                                                        html: "<?=$this->lang->line('payment_balance_transfers_modal_balances_done_text')?>",
                                                    }).then((result) => {
                                                        window.location.reload();
                                                    })
                                                }
                                            });
                                        });

                                        $('.btn-reprocess').click(function()
                                        {
                                            var store_id = $(this).data('store-id');
                                            var store_name = $(this).data('store-name');
                                            conciliation_id = $('#conciliation-id').data('conciliation-id');

                                            Swal.fire({
                                                title: '<?=$this->lang->line('payment_report_rep_title')?>',
                                                html: '<?=$this->lang->line('payment_report_rep_html')?><br/>' + store_name,
                                                icon: 'question',
                                                showCancelButton: true,
                                                showConfirmButton: true,
                                                cancelButtonText: '<?=$this->lang->line('payment_balance_transfers_modal_btn_close')?>',
                                                confirmButtonText: '<?=$this->lang->line('payment_report_rep_btn_ok')?>'

                                            }).then((result) => {
                                                if (result.value)
                                                {
                                                    var sendData = {
                                                        selected_ids: ['{"store_id":"' + store_id + '","conciliation_id":"' + conciliation_id + '","value":"0","cycle":"<?=$conciliation_data['data_pagamento'].'-'.$conciliation_data['ano_mes']?>","fee": "0"}'],
                                                        "chargeback_adjustments": true,
                                                        "chargeback_adjustments_rep_row": true,
                                                    }

                                                    loading('show');

                                                    $.post(base_url + 'payment/executeTransfers', sendData, function(data)
                                                    {
                                                        loading('hide');

                                                        if (data == 'ok' || isJson(data))
                                                        {
                                                            rep_result = JSON.parse(data);

                                                            if (rep_result['message'])
                                                            {
                                                                Swal.fire({
                                                                    icon: 'warning',
                                                                    title: '<?=$this->lang->line('application_error')?>',
                                                                    html: '<?=$this->lang->line('payment_report_modal_error_v5')?>',
                                                                });
                                                                return false;
                                                            }

                                                            Swal.fire({
                                                                icon: 'success',
                                                                title: '<?=$this->lang->line('payment_report_rep_transfer_success_title')?>',
                                                                html: '<?=$this->lang->line('payment_report_rep_transfer_success_test')?>'
                                                            }).then(() => {
                                                                var row_rep = $('#rep-row-'+ store_id);

                                                                row_rep.find('.negative').attr('title', '<?=$this->lang->line('payment_report_list_icon_status_23')?>');
                                                                row_rep.find('.negative').find('i').removeClass('fa-ban').addClass('fa-check');
                                                                row_rep.find('.negative').removeClass('negative');
                                                                row_rep.find('.btn-reprocess').remove();
                                                                row_rep.find('.btn-mark-paid').remove();
                                                                row_rep.find('.check-chargeback').find('input').remove();
                                                                row_rep.find('.reprocess').html('<i class="fa fa-check" style="color: #8FBC8F;"></i>');
                                                            });
                                                        }
                                                        else
                                                        {
                                                            Swal.fire({
                                                                icon: 'error',
                                                                title: '<?=$this->lang->line('payment_balance_transfers_modal_transfer_error_title')?>',
                                                                html: '<?=$this->lang->line('payment_balance_transfers_modal_transfer_error_text')?>',
                                                            }).then(() => {
                                                                location.reload();
                                                            });
                                                        }
                                                    })

                                                }
                                            });

                                        });

                                        $('.btn-mark-paid').click(function()
                                        {
                                            var store_id = $(this).data('store-id');
                                            var store_name = $(this).data('store-name');
                                            conciliation_id = $('#conciliation-id').data('conciliation-id');

                                            Swal.fire({
                                                title: 'Marcar Repasse como Pago Manualmente',
                                                html: 'Tem certeza de que deseja marcar esse repasse como pago manualmente para a loja <br/><b>' + store_name+'</b>',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                showConfirmButton: true,
                                                cancelButtonText: '<?=$this->lang->line('payment_balance_transfers_modal_btn_close')?>',
                                                confirmButtonText: 'Confirmar'

                                            }).then((result) => {
                                                if (result.value)
                                                {
                                                    var sendData = {
                                                        "selected_id": store_id,
                                                        "conciliation_id": conciliation_id,
                                                    }

                                                    loading('show');

                                                    $.post(base_url + 'payment/markAsPaid', sendData, function(data)
                                                    {
                                                        loading('hide');

                                                        if (data == 'ok' || isJson(data))
                                                        {
                                                            rep_result = JSON.parse(data);

                                                            if (rep_result['message'])
                                                            {
                                                                Swal.fire({
                                                                    icon: 'warning',
                                                                    title: '<?=$this->lang->line('application_error')?>',
                                                                    html: '<?=$this->lang->line('payment_report_modal_error_v5')?>',
                                                                });
                                                                return false;
                                                            }
                                                            Swal.fire({
                                                                icon: 'success',
                                                                title: 'Repasse Marcado Como Pago com sucesso',
                                                                html: 'Nenhuma transferência foi executada nessa ação, foi apenas alterado o status do repasse da loja selecionada.'
                                                            }).then(() => {
                                                                var row_rep = $('#rep-row-'+ store_id);

                                                                row_rep.find('.negative').attr('title', '<?=$this->lang->line('payment_report_list_icon_status_23')?>');
                                                                row_rep.find('.negative').find('i').removeClass('fa-ban').addClass('fa-check');
                                                                row_rep.find('.negative').removeClass('negative');
                                                                row_rep.find('.btn-reprocess').remove();
                                                                row_rep.find('.btn-mark-paid').remove();
                                                                row_rep.find('.check-chargeback').find('input').remove();
                                                                row_rep.find('.reprocess').html('<i class="fa fa-check" style="color: #8FBC8F;"></i>');
                                                            });
                                                        }
                                                        else
                                                        {
                                                            Swal.fire({
                                                                icon: 'error',
                                                                title: '<?=$this->lang->line('payment_balance_transfers_modal_transfer_error_title')?>',
                                                                html: '<?=$this->lang->line('payment_balance_transfers_modal_transfer_error_text')?>',
                                                            }).then(() => {
                                                                location.reload();
                                                            });
                                                        }
                                                    })
                                                }
                                            });
                                        });
                                    });
                                    
                                    let rap_result = '';

                                    function returnReprocessResult(row_rep, data, status, store_id)
                                    {
                                        rep_result = JSON.parse(data);

                                        if (status == 'liquid')
                                        {
                                            row_rep.find('.rep-col-'+ status).text('R$ '+ parseFloat(rep_result[status]).toLocaleString('pt-br', {minimumFractionDigits: 2}));
                                        }

                                        if (rep_result[status] == 23)
                                        {
                                            row_rep.find('.rep-col-'+ status)
                                                .removeClass('negative')
                                                .attr('title', '<?=$this->lang->line('payment_report_list_icon_status_23')?>')
                                                .find('i')
                                                .removeClass()
                                                .addClass('fa fa-check');

                                            if (status == 'positive')
                                            {
                                                $('input[type=checkbox][value='+store_id+']').remove();
                                            }
                                        }
                                        else if (rep_result[status] == 26)
                                        {
                                            row_rep.find('.rep-col-'+ status)
                                                .addClass('negative')
                                                .attr('title', '<?=$this->lang->line('payment_report_list_icon_status_26')?>')
                                                .find('i')
                                                .removeClass()
                                                .addClass('fa fa-ban');
                                        }
                                    }


                                    function slugify(text) {
                                        const from = "ãàáäâẽèéëêìíïîõòóöôùúüûñç·/_,:;"
                                        const to = "aaaaaeeeeeiiiiooooouuuunc------"

                                        const newText = text.split('').map(
                                            (letter, i) => letter.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i)))

                                        return newText
                                            .toString()                     // Cast to string
                                            .toLowerCase()                  // Convert the string to lowercase letters
                                            .trim()                         // Remove whitespace from both sides of a string
                                            .replace(/\s+/g, '-')           // Replace spaces with -
                                            .replace(/&/g, '-y-')           // Replace & with 'and'
                                            .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
                                            .replace(/\-\-+/g, '-');        // Replace multiple - with single -
                                    }


                                    function loading(display = 'show')
                                    {
                                        if (display == 'show')
                                            $('#loading_wrap').fadeIn();
                                        else
                                            $('#loading_wrap').fadeOut();
                                    }

                                    function isJson(str)
                                    {
                                        try
                                        {
                                            JSON.parse(str);
                                        }
                                        catch (e)
                                        {
                                            return false;
                                        }

                                        return true;
                                    }


                                </script>


                                </tbody>
                            </table>

                            <?php
                            if (isset($this->allow_transfer_between_accounts) && $this->allow_transfer_between_accounts == "1"):
                                ?>
                                <div class="row mt-5">
                                    <div class="col-md-12 text-right">
                                        <button type="button" id="btn-adjust" style="" class="btn btn-primary" title=""><?=$this->lang->line('payment_report_list_btn_adjustment');?><!-- <i class="fa fa-fw fa-usd"></i>--></button>
                                    </div>
                                </div>

                                <script>

                                    $(function()
                                    {
                                        var title = '<?=$this->lang->line('payment_report_modal_title_error')?>';
                                        var html = '<?=$this->lang->line('payment_report_modal_title_error_text')?>';
                                        var icon = 'error';
                                        var showConfirmButton = false;
                                        var cancelButtonText = '<?=$this->lang->line('payment_balance_transfers_modal_btn_close')?>';
                                        var confirmButtonText = '';
                                        var stores = [];
                                        var conciliation_id = 0;

                                        $('#btn-adjust').click(function()
                                        {
                                            var transfers_selected = $('input[class="transfer-selected"]:checked');

                                            if (transfers_selected.length > 0)
                                            {
                                                title = '<?=$this->lang->line('payment_report_modal_title_ok')?>';
                                                html = '<?=$this->lang->line('payment_report_modal_title_ok_text')?>';
                                                icon = 'question';
                                                showConfirmButton = true;
                                                cancelButtonText = '<?=$this->lang->line('payment_balance_transfers_modal_btn_cancel')?>';
                                                confirmButtonText = '<?=$this->lang->line('payment_report_modal_btn_ok')?>';

                                                transfers_selected.each(function(k,v)
                                                {
                                                    stores.push($(v).val());
                                                });

                                                conciliation_id = $('#conciliation-id').data('conciliation-id');
                                            }

                                            Swal.fire({
                                                title: title,
                                                html: html,
                                                icon: icon,
                                                showCancelButton: true,
                                                showConfirmButton: showConfirmButton,
                                                cancelButtonText: cancelButtonText,
                                                confirmButtonText: confirmButtonText

                                            }).then((result) => {
                                                if (result.value)
                                                {
                                                    var form_data = "?a="+ btoa('{"cid":"'+conciliation_id + '","stores":"' + stores.join(',')+'"}');
                                                    // location.href = base_url + 'payment/balanceTransfers?cid=' + conciliation_id + '&stores=' + stores.join(',');
                                                    location.href = base_url + 'payment/balanceTransfers' + form_data;
                                                }
                                            });
                                        });

                                    });

                                </script>
                            <?php
                            endif;
                            ?>


                        </div>
                        <!-- /.box-body -->
                    </div>
                    <!-- /.box -->

                <?php endif; ?>



            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->


    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->