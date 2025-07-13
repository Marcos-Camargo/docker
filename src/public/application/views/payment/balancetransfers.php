
<!-- Content Wrapper. Contains page content -->
<script>

    let transfer_value = 0;
    let transfers_selected = [];
    let current_cycles = [];
    let modal_data;
    let base_url = "<?php echo base_url(); ?>";
    // let double_store = 0;

    $("#paraMktPlaceNav").addClass('active');
    $("#balanceTransfers").addClass('active');

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
	  
    <?php
            $data['pageinfo'] = "";
	        $data['page_now'] = "balances_transfers_not_allowed_menu";

	        if ($this->allow_transfer_between_accounts == 1)
			{
				$data['page_now'] = "balances_transfers_menu";
            }

	       $this->load->view('templates/content_header',$data); ?>

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
        
        <?php if(in_array('balanceTransfers', $user_permission)): ?>

            <div class="box" id="box-filters">
              <div class="box-body">
              	<div class="box-header">                  
                </div>

                <style>
                    
                    .box-totals, .box-totals-modal{
                        position: absolute;
                        right: 0px;
                        top: -20px;  
                    }

                    .box-title{
                        margin-top: 80px !important;
                    }
                    .box-title-modal{
                        margin-top: 40px !important;
                        margin-left: 20px;
                    }

                    .box-total, .box-btns{
                        position: relative;
                        float: right;
                        padding: 20px;
                        margin: 20px;
                        border: 1px solid #666;
                        text-align: right;
                        border-radius: 3px;
                    }

                    .box-total .total-missing, .box-total .total-returned{                        
                        font-size: 30px;
                    }

                    .box-total.history{
                        border: 0;
                    }

                    .box-total-grid, .box-total-grid-mktplace, .box-total-grid-update{
                        margin: 0 20px 0 0;
                        padding: 4px 10px;
                        position: relative;
                        float: left;                        
                        border: 1px solid #666;
                        text-align: right;
                        border-radius: 3px;
                    }

                    .box-total-grid-update{
                        margin-top: 5px;
                        border: 0;
                        font-weight: bold;
                    }

                    .total-selected{
                        font-size: 18px;
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

                    .modal-message, .modal-footer{
                        text-align: center;
                    }

                    .modal-message .amount{
                        font-size: 50px;
                        margin: 30px 0 30px 0;
                    }

                    .large-price{
                        font-size: 50px;
                    }

                    .swal2-title{
                        display: block !important;
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

                    .box-total-dash{
                        position: relative;
                        float: right;
                        width: auto !important;
                    }

                    .bg-blue-light{
                        border: 1px solid #eee;
                    }

                    .cycle-month{
                        font-weight: bold;
                    }

                    .mktplace-balance-alert{
                        background-color: #dc3545;
                        color: #fff;
                        font-weight: bold;
                    }

                    .tax_bubble{
                        font-size: 8px;
                        position: relative;
                        top: 0px;
                        left: 6px;
                        /*color: red; */
                        background-color: red;
                        color: white;
                        border-radius: 50%;
                        width: 10px;
                        height: 10px;
                        text-align: center;
                        line-height: 10px;
                        display: inline-block;
                    }
                </style>

                <div class="col-md-12 col-xs-12" style="min-height: 125px;">

                    <div class="box-totals" >


                    <?php
						if ($this->allow_transfer_between_accounts == 1):
                    ?>
                            <div class="col-xs-6 box-total-dash">
                                <div class="small-box bg-blue-light">
                                    <div class="inner">
                                        <h3 class="total-return">R$ 0,00</h3>
                                        <p><?=$this->lang->line('payment_balance_transfers_box_total_returned');?></p>
                                    </div>
                                </div>
                            </div>
                    <?php
                        endif;
                    ?>

                        <div class="col-xs-6 box-total-dash">
                            <div class="small-box bg-blue-light">
                                <div class="inner">
                                    <h3 class="total-missing">R$ 0,00</h3>
                                    <p><?=$this->lang->line('payment_balance_transfers_box_total_missing');?></p>
                                </div>                                
                            </div>
                        </div>

                        <?php
                            if ($this->allow_transfer_between_accounts == 1):
                        ?>
                                <div class="box-total history">
                                    <a href="<?=base_url()?>payment/balanceTransfersHistory" id="btn-modal-transfer-history" type="button" class="btn btn-primary"><?=$this->lang->line('payment_balance_transfers_btn_history');?><!-- &nbsp;&nbsp; <i class="fa fa-fw fa-history"></i>--></a>
                                </div>
                        <?php
                            endif;
                        ?>

                    </div>

                </div>

                
                <?php
                if (false == $chargeback_adjustments):
                ?>
                    <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3>


                    <div class="form-group col-md-3 col-xs-3">
                        <label for="slc_store"><?= $this->lang->line('application_stores') ?></label>
                        <select v-model.trim="entry.slc_store" class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true"
                            id="slc_store" :disabled="allInputsDisabled" :readonly="allInputsDisabled" multiple="multiple">

                                <?php foreach ($filter_stores as $key => $filter_store): ?>
                                    <option value="<?php echo trim($key); ?>"><?php echo trim($filter_store); ?></option>
                                <?php endforeach ?>

                        </select>
                    </div>

                    <div class="form-group col-md-2 col-xs-2">
                        <label for="slc_cycle"><?= $this->lang->line('payment_balance_transfers_label_cycle') ?></label>
                        <select v-model.trim="entry.slc_cycle" class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true"
                                id="slc_cycle" :disabled="allInputsDisabled" :readonly="allInputsDisabled" multiple="multiple">

							<?php foreach ($cycles_filter as $key => $cycle): ?>
                                <option value="<?php echo str_replace('/' ,'-', trim($cycle)); ?>"><?php echo trim($cycle); ?></option>
							<?php endforeach ?>

                        </select>
                    </div>

                    <div class="form-group col-md-2 col-xs-2">
                        <br/>
                        <!-- <button class="btn btn-primary" id="btn_filtrar_transp" name="btn_filtrar_transp"><?=$this->lang->line('payment_balance_transfers_btn_filter');?></button> -->
                        <button style="margin-top: 4px;" class="btn btn-primary" id="btn_limpar" name="btn_limpar"><?=$this->lang->line('payment_balance_transfers_btn_filter');?><!-- &nbsp;&nbsp; <i class="fa fa-fw fa-filter"></i>--></button>
                    </div>

                <?php
                endif;
                ?>

              </div>
              <!-- /.box-body -->
            </div>

            <div class="box">
              <div class="box-body" id="grid-body">

                <div class="box-btns" id="box-btns">

                    <button type="button" id="btn-payment" style="display: none;" class="btn btn-success"><?=$this->lang->line('payment_balance_transfers_btn_payment');?><!-- <i class="fa fa-fw fa-usd"></i>--></button>

                    <button type="button" id="btn-transfer" style="display: none;" class="btn btn-primary"><?=$this->lang->line('payment_balance_transfers_btn_transfer');?><!-- <i class="fa fa-fw fa-usd"></i>--></button>

                    <button type="button" id="btn-export" class="btn btn-success"><?=$this->lang->line('payment_balance_transfers_btn_export');?><!-- &nbsp; <i class="fa fa-fw fa-file-excel-o"></i>--></button>

                    <?php
                    if ($this->allow_transfer_between_accounts == 1):
                    ?>
                    <div class="box-total-grid">
                        <span class="total-selected">R$ <span id="total-selected-value">0,00</span></span>&nbsp;&nbsp;<?=$this->lang->line('payment_balance_transfers_box_total_selected');?>
                    </div>
                    <?php
                    endif;
                    ?>

                    <div class="box-total-grid-mktplace">
                        <span class="total-selected">R$ <span id="mktplace-balance" data-clean-balance="0">0,00</span></span>&nbsp;&nbsp;<?=$this->lang->line('payment_balance_transfers_box_total_mktplace');?>
                    </div>


                    <?php
                        if ($allowed_tranfers == '1'):
                    ?>
                    <div class="box-total-grid-update">
                        <?=$this->lang->line('application_dt_alteracao').': '.$last_update?>
                        <button type="button" id="btn-update-balance" class="btn btn-danger btn-sm" style="display: none;"><?=$this->lang->line('payment_balance_transfers_btn_update_balance');?></button>
                    </div>
                    <?php
                        endif;
                    ?>



                </div>

                  <?php

				  $tax_tooltip = '';

                  if ($pagarme_fee_seller > 0)
                  {
                      if (($pagarme_fee - $pagarme_fee_seller) != 0)
					  {
						  $tax = $pagarme_fee - $pagarme_fee_seller;

						  $tooltip = $this->lang->line('payment_balance_transfers_tax_tooltip');
						  $tooltip = str_replace('####', money($tax / 100), $tooltip);

						  $tax_tooltip = ' <div id="tax-bubble-help" class="tax_bubble" style="width: 14px; height: 14px; line-height: 14px;" data-toggle="tooltip" data-placement="bottom" 
                            title="'.$tooltip.'"><i class="fa fa-question" aria-hidden="true"></i></div>';
                      }
                  }

                  ?>

                  <script src="https://igorescobar.github.io/jQuery-Mask-Plugin/js/jquery.mask.min.js"></script>

                <table  id="orders_table" class="table table-bordered table-striped" >
                  <thead>
                    <tr id="transfer-list-header">
                        <th style="width: 30px !important;" data-orderable="false"><input type="checkbox" id="check-all" title="<?=$this->lang->line('payment_balance_transfers_grid_selectall');?>"></th>
                        <th><?=$this->lang->line('application_store');?></th>
                        <th><?=$this->lang->line('payment_report_list_column_amount');?><?=$tax_tooltip?></th>
                        <th>Taxa Transferência</th>
                        <th><?=$this->lang->line('payment_balance_transfers_grid_totalsubaccount');?></th>
                        <th><?=$this->lang->line('payment_balance_transfers_grid_totalmissing');?></th>
                        <th><?=$this->lang->line('payment_balance_transfers_grid_conciliationdate');?></th>
                        <th><?=$this->lang->line('payment_balance_transfers_grid_transferstatus');?></th>
                        <!-- <th  data-orderable="false"><?=$this->lang->line('payment_balance_transfers_grid_blocked');?></th> -->
                    </tr>
                  </thead>

                  <tbody id="transfer-list">

                    <?php

                        $current_cycles = [];

                        foreach ($grid_balance as $key => $balance)
                        {
                            switch ($balance['transfer_status_flag'])
                            {
                                case 't': $flag = 'info'; break;
                                case 'r': $flag = 'success'; break;
                                default: $flag = 'warning';
                            }

                            $line_pagarme_fee = $balance['pagarme_fee'];

                            $balance['missing_clear'] = ($balance['valor_seller_total'] + $line_pagarme_fee) - $balance['available'];

                            $cycle = '';

                            if (false !== strpos($balance['data_ciclo'], '/') || false !== strpos($balance['data_ciclo'], '-'))
                            {
                                if (false !== strpos($balance['data_ciclo'], '-'))
                                {
                                    $cycle = explode('-', $balance['data_ciclo']);
                                    $current_cycles[] = $cycle[2].'/'.$cycle[1].'/'.substr($cycle[0], -2);
                                    $cycle = $cycle[2].'/<span class="cycle-month">'.$cycle[1].'</span>/'.substr($cycle[0], -2);
                                }
                                else
                                {
                                    $cycle = explode('/', $balance['data_ciclo']);
                                    $current_cycles[] = $cycle[0].'/'.$cycle[1].'/'.substr($cycle[2], -2);
                                    $cycle = $cycle[0].'/<span class="cycle-month">'.$cycle[1].'</span>/'.substr($cycle[2], -2);
                                }
                            }
                        ?>
                          <tr   data-store="<?=$balance['store_id']?>"
                                data-line-total="<?=$balance['valor_seller_total']?>"
                                data-line-tax="<?=$balance['pagarme_fee']?>"
                                data-line-available="<?=$balance['available']?>"
                                data-line-missing="<?=$balance['missing_clear']?>"
                                data-line-new-available="<?=$balance['available']?>"
                                data-line-new-missing="<?=$balance['missing_clear']?>"
                                data-line-fee="<?=$balance['pagarme_fee']?>"
                                id="row-<?=$balance['store_id'].'-'.$balance['conciliation_id']?>"
                                class="transfer-row "
                                <?php /* data-status="<?=@$balance['transfer_status_flag']?>" */?>
                                data-status="p"
                                data-cycle ="<?=str_replace('/', '-', $balance['data_ciclo'])?>"
                                data-cycle ="<?=str_replace('/', '-', $balance['data_ciclo'])?>"
                                >
                            <td>
                                <input type="checkbox" class="transfer-check<?php
                                    if ($balance['transfer_status_flag'] != 'p'){ echo '-disabled';}
                                    ?>"
                                    <?php
                                    if ($balance['transfer_status_flag'] != 'p'){ echo ' disabled="disabled;"';}
                                    ?>></td>
                            <td><?=$balance['name']?></td>
                            <td class="line-total">
                                <?php
                                echo money($balance['valor_seller_total']);
                                ?>
                            </td>
                            <td class="line-tax"><?=money($balance['pagarme_fee'])?></td>
                            <td class="line-available"><?=money($balance['available'])?></td>

                            <td class="line-missing">
                                <?php
							    if ($this->allow_transfer_between_accounts == 1 && $chargeback_adjustments === true){
                                ?>
                                    <label for="dinheiro">R$</label>
                                    <input type="text" name="missing" class="money chargeback-value" style="border: 1px solid #ccc;" value="<?php echo $balance['missing'] > 0 ? moneyToFloat($balance['missing']) : 0;?>" />
                                <?php
                                } else {
                                    if ($balance['missing_clear'] > 0) {
                                        echo money($balance['missing_clear']);
                                    }else{
                                        echo 0;
                                    }
                                    ?>
                                <?php
                                }
                                ?>
                            </td>
                            <td><?=$cycle?></td>
                            <td><span class="label label-warning"><?=$balance['transfer_status']?></span></td>
                            <?php
                                /*
                                <td><button type="button" class="btn btn-dark btn-sm modal-order-list"><?=$this->lang->line('payment_balance_transfers_btn_waiting');?>&nbsp; <i class="fa fa-fw fa-file-excel-o"></i></button></td>
                                */
                            ?>
                          </tr>

                    <?php
                        } //endforeach
                    ?>

                        <tr id="hidden-row" style="display: none;">
                            <td colspan="7" style="height: 60px; line-height: 3em !important;  text-align: center; background-color: #f7f7f7">
                                <h4><?=$this->lang->line('payment_balance_transfers_hidden_row_text');?></h4>
                            </td>
                        </tr>

                        <script src="https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js"></script>
                        <script>

                            var transfer_status = 'locked';  //inicia com a transferencia bloqueada, para conferir se o saldo esta atualizado ai sim libera

                            var modal_grid_empty = '<tr class="odd"><td valign="top" colspan="7" class="dataTables_empty"><?=$this->lang->line('payment_balance_transfers_modal_grid_empty')?></td></tr>';

                            var total_status_p = <?=$total_status['p']?>;
                            var total_status_t = <?=$total_status['t']?>;
                            var total_status_r = <?=$total_status['r']?>;

                            var last_update_minutes = <?=$last_update_minutes?>;
                            var last_update_minutes_limit = <?=$last_update_minutes_limit?>;

                            var allowed_tranfers = <?=$allowed_tranfers?>;

                            // console.log(last_update_minutes);
                            // console.log(last_update_minutes_limit);
                            // console.log(allowed_tranfers);

                            $(function()
                            {
                                $('.money').mask('#.##0,00', {reverse: true});

                                $('.chargeback-value').blur(function()
                                {
                                    var new_missing = $(this).val();
                                    var new_line = $(this).parents('tr');

                                    updateTransferValue(new_line);

                                    if (chargeback_adjustments)
                                    {
                                        $.each(transfers_selected, function(key,val)
                                        {
                                            var new_json = JSON.parse(transfers_selected[key]);
                                            var new_store_id = new_json.store_id;
                                            var new_value = $('#row-' + new_store_id + '-' + new_json.conciliation_id).find('.money').val();

                                            if (parseInt(new_value) > 0)
                                            {
                                                new_value = new_value.replace(/[.]/g, '');
                                                new_value = new_value.replace(/,/g, '.');
                                            }
                                            else
                                            {
                                                new_value = '0';
                                            }

                                            new_json.value = new_value;

                                            adjust_transfers_selected.push(JSON.stringify(new_json));
                                        });

                                        transfers_selected = adjust_transfers_selected;
                                    }
                                });


                                lockTransfers();

                                //confere se o saldo está atualizado em pelo menos 30 minutos
                                if (last_update_minutes <= last_update_minutes_limit || allowed_tranfers <= 0)
                                {
                                    unLockTransfers();
                                }


                                var transfer_rows = $('.transfer-row').length;

                                if (transfer_rows == 0)
                                {
                                    $('#btn-transfer').hide();
                                    $('#btn-payment').show();
                                    $('#hidden-row').show();
                                }
                                else
                                {
                                    if (allowed_tranfers != 1)
                                    {
                                        $('#btn-transfer').hide();
                                        $('#btn-payment').show();
                                        $('#hidden-row').hide();
                                        $('#check-all, .transfer-check').hide();
                                        // $('#btn-update-balance').show();
                                    }
                                    else
                                    {
                                        $('#btn-transfer').show();
                                    }
                                }


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


                                if(!chargeback_adjustments)
                                {
                                    $('.total-missing, .modal-total-missing').text('<?=money($total_boxes['total_missing'])?>');
                                }

                                $('.total-return, .modal-total-return').text('<?=money($mktplace_totalreturn)?>');

                                $('#mktplace-balance').html(parseFloat('<?=$mktplace_balance?>').toLocaleString('pt-br', {minimumFractionDigits: 2}));
                                $('#mktplace-balance').attr('data-clean-balance', '<?=$mktplace_balance?>');

                                setTimeout(function(){$('#orders_table thead tr th:first-child').removeClass('sorting_asc')},50);

                                setTimeout(function(){
                                    $('#slc_store option').attr("selected","selected");
                                    $('#slc_store').selectpicker('refresh');
                                },10);


                                setTimeout(function()
                                {
                                    $('#slc_cycle option').attr("selected","selected");
                                    $('#slc_cycle').selectpicker('refresh');
                                },30);


                                $("#check-all").click(function()
                                {
                                    if (transfer_status == 'locked')
                                    {
                                        return false;
                                    }

                                    var check_status = $(this).is(':checked');
                                    var speed = 30;

                                    $("#transfer-list tr td input:checkbox[class=transfer-check]").each(function(k,v)
                                    {
                                        setTimeout(function()
                                        {
                                            if ($(v).parents('tr').is(':visible'))
                                                $(v).prop('checked', check_status).change();

                                            //braun hack
                                            // $(v).parents('tr').find('.line-missing').find('.money').focus();

                                        },k * speed);
                                    });
                                });


                                $('#slc_store, #slc_cycle, #slc_status').change(function()
                                {
                                    setFilter();
                                });


                                $('#btn_limpar').click(function()
                                {
                                    clearFilters();
                                });


                                $('#btn-export').click(function()
                                {
                                    TableToExcel.convert(document.getElementById("orders_table"), {
                                        name: slugify('<?=substr($this->lang->line('application_balances_transfers'),0,30);?>')+'.xlsx',
                                        sheet: {
                                        name: "Sheet1"
                                        }
                                    });
                                });


                                $('#btn-modal-orders-export').click(function()
                                {
                                    TableToExcel.convert(document.getElementById("modal-orders-grid"), {
                                        name: slugify('<?=substr($this->lang->line('payment_balance_transfers_waiting_modal_title'),0,30);?>')+'.xlsx',
                                        sheet: {
                                        name: "Sheet1"
                                        }
                                    });
                                });


                                $('#btn-modal_history_export').click(function()
                                {
                                    TableToExcel.convert(document.getElementById("modal-history-grid"), {
                                        name: slugify('<?=substr($this->lang->line('payment_transfer_history_modal_title'),0,30);?>')+'.xlsx',
                                        sheet: {
                                        name: "Sheet1"
                                        }
                                    });
                                });
                            });


                            function lockTransfers()
                            {
                                transfer_status = 'locked';
                                $('#btn-transfer, #btn-payment, #check-all').prop('disabled', true);
                                $('#btn-transfer, #btn-payment, #check-all').attr('title', '<?=$this->lang->line('payment_balance_transfers_btn_disabled');?>');
                                $('#btn-update-balance').show();
                            }


                            function unLockTransfers()
                            {
                                transfer_status = 'unlocked';
                                $('#btn-transfer, #btn-payment, #check-all').prop('disabled', false);
                                $('#btn-transfer, #btn-payment, #check-all').attr('title', '');
                                $('#btn-update-balance').hide();
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


                            $('.transfer-check:checkbox').on('change', function()
                            {
                                var id = $(this).parents('tr').attr('id');
                                var store = $(this).parents('tr').data('store');
                                var qtt = $('*[data-store="'+ store + '"]').length;
                                var initial_value;
                                var new_value;
                                var new_id;

                                //braun hack
                                if (qtt > 1)
                                {
                                    var same_checks = $('*[data-store="'+ store + '"]').find('input[type="checkbox"]:checked').length;

                                    if (same_checks == 0)
                                    {
                                        $('*[data-store="'+ store + '"]').each(function(k, v)
                                        {
                                            reset_line($(this));
                                            // double_store = 0;
                                        });

                                        return true;
                                    }

                                    if (same_checks == 1)
                                    {
                                        var line = $('tr[data-store="'+ store + '"]').find('input[type="checkbox"]:checked');
                                        var line_id = line.parents('tr').attr('id');

                                        reset_line($('#'+line_id));
                                        recalc_other_lines(line_id, store);

                                        return true;
                                    }

                                    recalc_line($(this));
                                }
                                else
                                {
                                    $('*[data-store="'+ store + '"]').each(function(k, v)
                                    {
                                        initial_value = $(this).find('.line-available').html();
                                        $(this).find('.line-available').html(unstrikeValue(initial_value));
                                    });
                                }

                                // if (!chargeback_adjustments)
                                // {
                                    updateTransferValue($(this));
                                // }
                            });


                            function recalc_other_lines(id, store)
                            {
                                $('*[data-store="'+ store + '"]').each(function(k, v)
                                {
                                    new_id = $(v).attr('id');

                                    if (id != new_id)
                                    {
                                        recalc_line($(this));
                                    }

                                });
                            }


                            function recalc_line(line)
                            {
                                var missing = parseFloat(line.attr('data-line-missing'));
                                var available = parseFloat(line.attr('data-line-available'));
                                var new_transfer = missing + available;

                                // console.log('missing: '+missing);
                                // console.log('available: '+available);
                                // console.log('new_transfer: '+new_transfer);

                                var new_available = strikeValue('R$ '+available);

                                // console.log('new_available: '+new_available);

                                if(false !== new_available)
                                {
                                    line.find('.line-missing').html('R$ '+ parseFloat(new_transfer).toLocaleString('pt-br', {minimumFractionDigits: 2}));
                                    line.find('.line-available').html(new_available);
                                    line.attr('data-line-new-missing', new_transfer);
                                    line.attr('data-line-new-available', 0);
                                }
                            }


                            function reset_line(line)
                            {
                                var missing = line.attr('data-line-missing');
                                var available = line.attr('data-line-available');

                                // console.log('missing: '+missing);
                                // console.log('available: '+available);

                                line.find('.line-missing').html('R$ '+ parseFloat(missing).toLocaleString('pt-br', {minimumFractionDigits: 2}));
                                line.find('.line-available').html(unstrikeValue('R$ '+ parseFloat(available).toLocaleString('pt-br', {minimumFractionDigits: 2})));
                                line.attr('data-line-new-available', available);
                                line.attr('data-line-new-missing', missing);

                                setTimeout(function()
                                {
                                    updateTransferValue(line);
                                },20);

                            }


                            function strikeValue(value)
                            {
                                value = value.toString();
                                var myKey = '<s>';
                                var myMatch = value.search(myKey);

                                if(myMatch == -1)
                                    return '<s>' + value + '</s> R$ 0,00';

                                return false;
                            }


                            function unstrikeValue(value)
                            {
                                return value.replace('<s>', '').replace('</s>', '').replace(' R$ 0,00', '');
                            }


                            function updateTransferValue(element)
                            {
                                $('.transfer-row').each(function(k,v)
                                {
                                    if (element.is(":hidden"))
                                    {
                                        var id = $(this).attr('id');
                                        $('#' + id + ' td input:checkbox').prop( "checked", false);
                                    }
                                });

                                setTimeout(function()
                                {
                                    transfers_selected = [];
                                    transfer_value = 0;

                                    $('.transfer-row').each(function(k,v)
                                    {
                                        // console.log(v);

                                        var id = $(this).attr('id').split('-');

                                        if ($(this).is(":visible") && $('#row-' + id[1] + '-'+ id[2] +' td input:checkbox').is(":checked"))
                                        {
                                            if (chargeback_adjustments)
                                            {
                                                var new_value = $(this).find('.chargeback-value').val();
                                                if (new_value)
                                                {
                                                    new_value = new_value.replace('.', '');
                                                    new_value = new_value.replace('.', '');
                                                    new_value = new_value.replace('.', '');
                                                    new_value = new_value.replace(/,/g, '.');
                                                }

                                                if (new_value > 0)
                                                {
                                                    transfer_value = transfer_value + parseFloat(new_value);
                                                }
                                            }
                                            else
                                            {
                                                transfer_value = transfer_value + parseFloat($(this).attr('data-line-new-missing'));
                                            }

                                            transfers_selected.push ('{'+
                                                '"store_id" : "'+ id[1] +'",'+
                                                '"conciliation_id" : "' + id[2] + '",'+
                                                '"value": "'+$(this).attr('data-line-new-missing')+'",'+
                                                '"cycle": "'+$(this).attr('data-cycle')+'",'+
                                                '"fee": "'+$(this).attr('data-line-fee')+'"'+
                                            '}');
                                        }
                                        else
                                        {
                                            // console.log('remover da lista');
                                            // alert('remover da lista')
                                        }

                                        // console.log(transfers_selected);
                                    });

                                    if ($('.transfer-row').find('input[type="checkbox"]:checked').length == 0)
                                        $('#check-all').prop('checked', false);

                                    if (transfer_value > parseFloat($('#mktplace-balance').attr('data-clean-balance')))
                                        $('#mktplace-balance').parents('.box-total-grid-mktplace').addClass('mktplace-balance-alert');
                                    else
                                        $('#mktplace-balance').parents('.box-total-grid-mktplace').removeClass('mktplace-balance-alert');

                                    $('#total-selected-value').text(parseFloat(transfer_value).toLocaleString('pt-br', {minimumFractionDigits: 2}));

                                    if (chargeback_adjustments)
                                    {
                                        $('.total-missing').text('R$ ' + parseFloat(transfer_value).toLocaleString('pt-br', {minimumFractionDigits: 2}));
                                    }
                                }, 20);
                            }


                            function setFilter()
                            {
                                var store   = $('#slc_store').val();
                                var cycle   = $('#slc_cycle').val();
                                // var status  = $('#slc_status').val();
                                // var status  = 'p';

                                $('.transfer-row').each(function(k,v)
                                {
                                    if (
                                        (jQuery.inArray($(this).attr('data-store'), store) > -1)
                                        &&
                                        (jQuery.inArray($(this).attr('data-cycle'), cycle) > -1)
                                        // &&
                                        // ($(this).attr('data-status') == status)
                                    )
                                    {
                                        $(this).show();
                                    }
                                    else
                                    {
                                        $(this).find('input:checkbox').prop('checked', false).change();
                                        $(this).hide();
                                    }
                                });

                                return true;
                            }


                            function clearFilters()
                            {
                                $('#slc_store').find('option').prop('selected', true).parent('select').selectpicker('refresh');
                                $('#slc_cycle').find('option').prop('selected', true).parent('select').selectpicker('refresh');
                                // $('#slc_status').val('p');

                                setTimeout(function()
                                {
                                    setFilter();
                                },10);
                            }


                            let filters_height = $('#box-filters').outerHeight();
                            let title_height = $('.content-header').outerHeight();

                            function getScrollTop()
                            {
                                if (typeof window.pageYOffset !== "undefined" ) {
                                    // Most browsers
                                    return window.pageYOffset;
                                }

                                var d = document.documentElement;
                                if (typeof d.clientHeight !== "undefined") {
                                    // IE in standards mode
                                    return d.scrollTop;
                                }
                                // IE in quirks mode
                                return document.body.scrollTop;
                            }

                            window.onscroll = function()
                            {
                                var box = document.getElementById("box-btns");
                                var tblheader = document.getElementById("transfer-list-header");
                                var scroll = getScrollTop(box);

                                var activate_height = filters_height + title_height + 32;

                                if (scroll <= activate_height)
                                {
                                    box.style.top = "30px";
                                    tblheader.style.top = "90px";
                                    // $("#box-btns").removeClass('box-btns-floating');
                                    $("#transfer-list-header").removeClass('box-btns-floating');
                                }
                                else
                                {
                                    // box.style.top = (scroll + 2) + "px";
                                    box.style.top = scroll + "px";
                                    tblheader.style.top = scroll + 60 + "px";
                                    // $("#box-btns").addClass('box-btns-floating');
                                    $("#transfer-list-header").addClass('box-btns-floating');
                                }
                            };


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
              </div>
              <!-- /.box-body -->
            </div>
            <!-- /.box -->
        <?php
        endif;
        ?>

      </div>
      <!-- col-md-12 -->
    </div>
    <!-- /.row -->
    

  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->


<!-- waiting orders modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="modal-waiting-orders">
  <div class="modal-dialog" role="document" style="width: 80vw !important; margin: 10vh auto !important;"> 
    <div class="modal-content" style=" height: 80vh !important;" >
      
        <div class="modal-header" style="border-bottom:0 !important;">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="application_close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><?=$this->lang->line('payment_balance_transfers_waiting_modal_title');?></h4>
        </div>

      <div class="modal-body" style="height: 80% !important;">
        <div class="container col-md-12" style="height: 90% !important;">

            <!-- <form role="form" action="<?php echo base_url('attributes/updateValue') ?>" method="post" id="updateForm"> -->
                <!-- <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" /> -->

                <div class="box-totals-modal">

                    <div class="col-xs-6 box-total-dash">
                        <div class="small-box bg-blue-light">
                            <div class="inner">
                                <h3 class="modal-waiting-return">R$ 0,00</h3>
                                <p><?=$this->lang->line('payment_balance_transfers_box_total_returned');?></p>
                            </div>                                
                        </div>
                    </div>


                    <div class="col-xs-6 box-total-dash">
                        <div class="small-box bg-blue-light">
                            <div class="inner">
                                <h3 class="modal-waiting-missing">R$ 0,00</h3>
                                <p><?=$this->lang->line('payment_transfer_history_column_total_returned');?></p>
                            </div>
                        </div>
                    </div>


                </div>


                    <div class="row">
                        
                        <h4 class="box-title-modal"><?=$this->lang->line('application_set_filters');?></h4>
                    </div>
                    
                    <div class="row">
                        <div class="form-group col-md-2">
                            <select class="form-control" id="modal_status" name="modal_status" >

                            <?php foreach ($filter_status as $key => $status): ?>
                                <?php if ($key != 't'): ?>
                                    <option value="<?=$key?>" class="status-<?=$key?>-background" <?php if ($key == 'p'){echo 'selected';} ?>><?=ucfirst($status)?></option>
                                <?php endif; ?>
                            <?php endforeach ?>

                            </select>
                        </div>

                        <div class="form-group col-md-2">
                            <input type="text" class="form-control" id="modal-filter-order-number" name="modal-filter-order-number" placeholder="<?=$this->lang->line('application_numero_marketlace')?>" 
                            autocomplete="off">
                        </div>

                        <div class="form-group col-md-1">
                            <button type="button" id="btn_modal_clear" name="btn_modal_clear" class="btn btn-primary"><?=$this->lang->line('payment_balance_transfers_btn_filter');?><!-- &nbsp;&nbsp; <i class="fa fa-fw fa-filter"></i>--></button>
                        </div>

                        <div class="form-group col-md-1">
                            <button type="button" id="btn-modal-orders-export" style="float: left;" class="btn btn-success"><?=$this->lang->line('payment_balance_transfers_btn_export');?><!-- &nbsp; <i class="fa fa-fw fa-file-excel-o"></i>--></button>
                        </div>


                        <div class="form-group col-md-6"></div>

                    </div>
                    
                    <div class="row" style="overflow-y: auto; overflow-x: hidden; height: 100% !important;">

                        <table  id="modal-orders-grid" class="table table-bordered table-striped" >

                            <thead>
                                <tr>                                    
                                    <th><?=$this->lang->line('application_numero_marketlace');?></th>
                                    <th><?=$this->lang->line('application_store');?></th>
                                    <th><?=$this->lang->line('payment_balance_transfers_waiting_modal_column_transfered');?></th>                                
                                    <th><?=$this->lang->line('payment_balance_transfers_modal_grid_th_date_devolution');?></th>
                                    <th><?=$this->lang->line('payment_balance_transfers_grid_conciliationdate');?></th>
                                    <th><?=$this->lang->line('payment_balance_transfers_grid_credflag');?></th>
                                    <th><?=$this->lang->line('payment_balance_transfers_grid_transferstatus');?></th>
                                </tr>
                            </thead>
                
                            <tbody id="modal-orders-list"></tbody>

                        </table>

                    </div>


                <!-- <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_back');?></button>
                </div> -->

            <!-- </form> -->

        </div>
      </div>

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<!-- Transfer History modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="modal-transfer-history">
  <div class="modal-dialog" role="document" style="width: 80vw !important; margin: 10vh auto !important;">
    <div class="modal-content" style="height: 80vh !important;">

        <div class="modal-header" style="border-bottom:0 !important;">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="application_close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><?=$this->lang->line('payment_transfer_history_modal_title');?></h4>
        </div>

      <div class="modal-body"  style="height: 80% !important;">
        <div class="container col-md-12" style="height: 90% !important;">

            <!-- <form role="form" action="<?php echo base_url('attributes/updateValue') ?>" method="post" id="updateForm"> -->
                <!-- <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" /> -->

                    <div class="row">
                        <h4 class="box-title-modal"><?=$this->lang->line('application_set_filters');?></h4>
                    </div>

                    <div class="row">

                        <div class="form-group col-md-2">

                            <label for="modal-history-slc-store"><?= $this->lang->line('application_stores') ?></label>
                            <select v-model.trim="entry.modal-history-slc-store" class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true"
                                id="modal-history-slc-store" :disabled="allInputsDisabled" :readonly="allInputsDisabled" multiple="multiple">
                            </select>
                        </div>

                        <div class="form-group col-md-2 col-xs-2">
                            <label for="modal_history_status"><?= $this->lang->line('payment_balance_transfers_status_title') ?></label>
                            <select class="form-control" id="modal_history_status" name="modal_history_status" >

                            <?php foreach ($filter_status as $key => $status): ?>
                                <?php if ($key != 'p'): ?>
                                    <option value="<?=$key?>" class="status-<?=$key?>-background" <?php if ($key == 'p'){echo 'selected';} ?>><?=ucfirst($status)?></option>
                                <?php endif; ?>
                            <?php endforeach ?>

                            </select>
                        </div>


                        <div class="form-group col-md-2 col-xs-2">
                            <label for="modal-history-slc-user"><?= $this->lang->line('payment_transfer_history_filter_user') ?></label>
                            <select v-model.trim="entry.modal-history-slc-user" class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true"
                                id="modal-history-slc-user" :disabled="allInputsDisabled" :readonly="allInputsDisabled" multiple="multiple">
                            </select>
                        </div>


                        <div class="form-group col-md-2 col-xs-2">
                            <label for="modal-history-slc-cycle"><?= $this->lang->line('payment_balance_transfers_label_cycle') ?></label>
                            <select v-model.trim="entry.modal-history-slc-cycle" class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true"
                                id="modal-history-slc-cycle" :disabled="allInputsDisabled" :readonly="allInputsDisabled" multiple="multiple">
                            </select>
                        </div>


                        <div class="form-group col-md-2 vbottom">
                            <button type="button" onClick="clearModalHistoryFilters()" id="btn_modal_history_clear" name="btn_modal_history_clear" class="btn btn-primary"><?=$this->lang->line('payment_balance_transfers_btn_filter');?><!-- &nbsp;&nbsp; <i class="fa fa-fw fa-filter"></i>--></button>
                        </div>

                        <div class="form-group col-md-2 vbottom">
                            <button type="button" id="btn-modal_history_export" style="float: left;" class="btn btn-success"><?=$this->lang->line('payment_balance_transfers_btn_export');?><!-- &nbsp; <i class="fa fa-fw fa-file-excel-o"></i>--></button>
                        </div>
                        <style>

                        .vbottom {
                            padding-top: 26px;
                        }

                        </style>

                    </div>

                    <div style="overflow-y: auto; overflow-x: hidden; height: 100% !important;">

                        <table  id="modal-history-grid" class="table table-bordered table-striped" >

                            <thead>
                                <tr>
                                    <th><?=$this->lang->line('application_store');?></th>
                                    <th><?=$this->lang->line('payment_transfer_history_column_total_transfered');?></th>
                                    <th><?=$this->lang->line('payment_transfer_history_column_total_card');?></th>
                                    <th><?=$this->lang->line('payment_transfer_history_column_total_returned');?></th>
                                    <th><?=$this->lang->line('payment_balance_transfers_grid_conciliationdate');?></th>
                                    <th><?=$this->lang->line('payment_transfer_history_column_responsible');?></th>
                                    <th><?=$this->lang->line('payment_balance_transfers_grid_transferstatus');?></th>
                                </tr>
                            </thead>

                            <tbody id="modal-history-list"></tbody>

                        </table>


                    </div>
                    <!-- <div class="modal-footer222">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_back');?></button>
                    </div> -->

                </div>




            <!-- </form> -->

        </div>





    </div><!-- /.modal-content -->

  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->




<script>

    var orders_table;
    var chargeback_adjustments = <?php echo ($chargeback_adjustments) ? 'true' : 'false'; ?>;
    var adjust_transfers_selected = [];
    // console.log(chargeback_adjustments);

    $('#modal_status').on('change', function()
    {
        setModalFilter();
    });


    $('#modal-filter-order-number').on('input', function() 
    {
        setModalFilter();
    });


    function setModalFilter()
    {       
        $('.modal-row').show();

        var status  = $('#modal_status').val();
        var order_id = $("#modal-filter-order-number").val();    

        $('.modal-row').each(function(k,v)
        {
            var id = $(this).attr('id');

            if ($(this).attr('data-status') == status && id.search(order_id) != -1)
                $(this).show();
            else
                $(this).hide();
        });
    }


    function clearModalFilter()
    {
        $('.modal-row').show();

        $('#modal_status').val('p');
        $("#modal-filter-order-number").val('');
    }


    function clearModalHistoryFilters()
    {
        $('#modal-history-slc-store').find('option').prop('selected', true).parent('select').selectpicker('refresh');
        $('#modal_history_status').val('t');
        $('#modal-history-slc-user').find('option').prop('selected', true).parent('select').selectpicker('refresh');
        $('#modal-history-slc-cycle').find('option').prop('selected', true).parent('select').selectpicker('refresh');


        setTimeout(function()
        {
            setModalHistoryFilter();
        },10);
    }


    function setModalHistoryFilter()
    {
        var stores    = $('#modal-history-slc-store').val();
        var statuses  = $('#modal_history_status').val();
        var users     = $('#modal-history-slc-user').val();
        var cycles    = $('#modal-history-slc-cycle').val();

        $('.modal-history-row').each(function(k,v)
        {
            if (
                (jQuery.inArray($(this).attr('data-history-store'), stores) > -1)
                &&
                (jQuery.inArray($(this).attr('data-history-user'), users) > -1)
                &&
                (jQuery.inArray($(this).attr('data-history-cycle'), cycles) > -1)
                &&
                ($(this).attr('data-history-status') == statuses)
            )
            {
                $(this).show();
            }
            else
            {
                $(this).hide();
            }
        });

        return true;
    }


    function loading(display = 'show')
    {
        if (display == 'show')
            $('#loading_wrap').fadeIn();
        else
            $('#loading_wrap').fadeOut();
    }


    $(function()
    {
        if (chargeback_adjustments)
        {
            setTimeout(function()
            {
                $("#check-all").click();
            },300);
        }



        var table_modal_history_grid =
            $("#modal-history-grid").DataTable({
                paging:  false,
                bFilter: false,
                bInfo : false
            });


        $('#modal-history-slc-store, #modal_history_status, #modal-history-slc-user, #modal-history-slc-cycle').change(function()
        {
            setModalHistoryFilter();
        });


        $('#btn_modal_history_clear').click(function()
        {
            // console.log('limpa filtro');
            clearModalHistoryFilters();
        });


        $('#btn_modal_clear').click(function()
        {
            clearModalFilter();
        });


        $('#btn-transfer').click(function()
        {
            if (transfer_status == 'locked')
            {
                // console.log('locked');
                return false;
            }

            if ('<?=$allowed_tranfers?>' != '1')
            {
                return false;
            }

            //var title = '<?php //=$this->lang->line('payment_balance_transfers_modal_title_error')?>//';
            //var html = '<?php //=$this->lang->line('payment_balance_transfers_modal_text_error')?>//';
            //var icon = 'error';
            //var showConfirmButton = false;
            //var cancelButtonText = '<?php //=$this->lang->line('payment_balance_transfers_modal_btn_close')?>//';
            //var confirmButtonText = '';

            title = '<?=$this->lang->line('payment_balance_transfers_modal_title_simple')?>';
            html = '<?=$this->lang->line('payment_balance_transfers_modal_text_simple')?>';
            icon = 'question';
            showConfirmButton = true;
            cancelButtonText = '<?=$this->lang->line('payment_balance_transfers_modal_btn_cancel')?>';
            confirmButtonText = '<?=$this->lang->line('payment_balance_transfers_modal_btn_confirm_simple')?>';

            adjust_transfers_selected = [];

            // console.log(adjust_transfers_selected);
            // return false;

            if (transfers_selected.length > 0)
            {
                title = '<div style="width:100%"><?=$this->lang->line('payment_balance_transfers_modal_title')?></div><div style="width:100%"><p class="large-price">R$ ' +
                    parseFloat(transfer_value).toLocaleString('pt-br', {minimumFractionDigits: 2}) + '</p></div>';
                html = '<?=$this->lang->line('payment_balance_transfers_modal_text')?>';
                icon = 'question';
                showConfirmButton = true;
                cancelButtonText = '<?=$this->lang->line('payment_balance_transfers_modal_btn_cancel')?>';
                confirmButtonText = '<?=$this->lang->line('payment_balance_transfers_modal_btn_confirm')?>';
            }

            if (transfers_selected.length > 0)
            {
                if (chargeback_adjustments)
                {
                    $.each(transfers_selected, function(key,val)
                    {
                        var new_json = JSON.parse(transfers_selected[key]);
                        var new_store_id = new_json.store_id;
                        var new_value = $('#row-' + new_store_id + '-' + new_json.conciliation_id).find('.money').val();

                        new_value = new_value.replace(/[.]/g, '');
                        new_value = new_value.replace(/,/g, '.');

                        if('' == new_value)
                        {
                            new_value = '0';
                        }

                        new_json.value = new_value;

                        if (new_value >= 0)
                        {
                            // delete transfers_selected[key];
                            adjust_transfers_selected.push(JSON.stringify(new_json));
                        }
                    });

                    transfers_selected = adjust_transfers_selected;
                }
            }
            else
            {
                transfers_selected = 'transfer';
            }

            // console.log(transfers_selected);
            // return false;

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
                        var sendData = {
                            selected_ids: transfers_selected,
                            chargeback_adjustments: chargeback_adjustments
                        }

                        loading('show');

                        $.post(base_url + 'payment/executeTransfers', sendData, function(data)
                        {
                            loading('hide');

                            if (data = 'ok')
                            {
                                Swal.fire({
                                    icon: 'success',
                                    title: '<?=$this->lang->line('payment_balance_transfers_modal_transfer_success_title')?>',
                                    html: '<?=$this->lang->line('payment_balance_transfers_modal_transfer_success_text')?>'
                                }).then(() => {
                                    window.location = base_url + 'payment/paymentReports';
                                });
                            }
                            else
                            {
                                Swal.fire({
                                    icon: 'error',
                                    title: '<?=$this->lang->line('payment_balance_transfers_modal_transfer_error_title')?>',
                                    html: '<?=$this->lang->line('payment_balance_transfers_modal_transfer_error_text')?>',                        
                                }).then(() => {
                                    // location.reload();
                                    window.location = base_url + 'payment/paymentReports';
                                });
                            }
                        })
                    }
            });
        });

 
        $('#btn-payment').click(function()
        {
            if (transfer_status == 'locked') 
            {           
                return false;
            }
            
            var title = '<div style="width:100%"><?=$this->lang->line('payment_balance_transfers_payment_modal_title')?></div>';
            var html = '<?=$this->lang->line('payment_balance_transfers_payment_modal_text')?>';
            var icon = 'question';            
            var showConfirmButton = true;
            var cancelButtonText = '<?=$this->lang->line('payment_balance_transfers_modal_btn_cancel')?>';
            var confirmButtonText = '<?=$this->lang->line('payment_balance_transfers_payment_modal_btn_confirm')?>';
                
            Swal.fire({
                title: title,
                html: html,
                icon: icon,
                showCancelButton: true,  
                showConfirmButton: showConfirmButton,     
                cancelButtonText: cancelButtonText,
                confirmButtonColor: '#008D4C',
                confirmButtonText: confirmButtonText

                }).then((result) => {
                    if (result.value) 
                    {
                        // alert('repasse');
                        // return false;

                        var sendData = {
                            selected_ids: 'transfer'
                        }

                        loading('show');

                        $.post(base_url + 'payment/executeTransfers', sendData, function(data)
                        {
                            loading('hide');

                            if (data = 'ok')
                            {
                                Swal.fire({
                                    icon: 'success',
                                    title: '<?=$this->lang->line('payment_balance_transfers_modal_transfer_success_title')?>',
                                    html: '<?=$this->lang->line('payment_balance_transfers_payment_modal_transfer_success_text')?>',
                                    confirmButtonColor: '#008D4C',
                                }).then(() => {
                                    // location.reload();
                                    window.location = base_url + 'payment/paymentReports';
                                });
                            }
                            else
                            {
                                Swal.fire({
                                    icon: 'error',
                                    title: '<?=$this->lang->line('payment_balance_transfers_modal_transfer_error_title')?>',
                                    html: '<?=$this->lang->line('payment_balance_transfers_modal_transfer_error_text')?>',                        
                                }).then(() => {
                                    // location.reload();
                                    window.location = base_url + 'payment/paymentReports';
                                });
                            }
                        });
                    }
                }); 
             });


    });


</script>