
<!-- Content Wrapper. Contains page content -->
<script>

    // let transfer_value = 0;
    // let transfers_selected = [];
    // let current_cycles = [];
    // let modal_data;
    let base_url = "<?php echo base_url(); ?>";


</script>
<div class="content-wrapper">
	  
    <?php  $data['pageinfo'] = ""; 
	       $data['page_now'] = "iugu_plans_view_title";
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
        
        <?php if(in_array('createIuguPlans', $user_permission)): ?>

            <div class="box  p-5" id="box-filters">
              
                <form method="post" name="create-plan" id="create-plan" action="<?php echo base_url('iugu/createPlan') ?>">

                    <div class="box-body ml-3 pb-5">

                        <h3 class="box-title"><?=$this->lang->line('iugu_plans_create_title');?></h3>

                        

                            <div class="row">

                                <div class="form-group mt-5">

                                    <div class="col-md-3 col-xs-3">
                                        
                                        <label for="plan_title"><?= $this->lang->line('iugu_plans_labels_title') ?> *</label>

                                        <input type="text" 
                                                class="form-control" 
                                                id="plan_title" 
                                                onkeyup="characterLimit(this)" 
                                                maxlength="100" 
                                                name="plan_title" 
                                                required="required" 
                                                placeholder="<?= $this->lang->line('iugu_plans_placeholder_title') ?>" 
                                                autocomplete="off">
                                        <span id="char_plan_title"><?= $this->lang->line('iugu_plans_types_chars') ?>: 0/100</span><br>
                                        
                                        <div class="alert alert-warning alert-dismissible" role="alert">                                            
                                            <span class="alert-msg"><?= $this->lang->line('iugu_plans_title_alert_msg') ?></span>
                                        </div>

                                    </div>

                                    <div class="col-md-2 col-xs-2">

                                        <label for="plan_type"><?= $this->lang->line('iugu_plans_labels_type') ?> *</label>

                                        <select class="form-control" id="plan_type" name="plan_type" >
                                            
                                            <option value="cash" selected><?= $this->lang->line('iugu_plans_option_cash') ?></option>
                                            <option value="financed"><?= $this->lang->line('iugu_plans_option_financed') ?></option>

                                        </select>

                                    </div>
                                    
                                </div>

                            </div>


                            <div class="row">

                                <div class="form-group mt-4">

                                    <div class="col-md-2">
                                        
                                        <label for="plan_value"><?= $this->lang->line('iugu_plans_labels_value') ?> *</label>

                                        <input type="text" value="R$" class="form-control input-icon">
                                        <input type="number" min="0.00" max="10000.00" step="any"
                                                class="form-control input-icon-value" 
                                                id="plan_value" 
                                                maxlength="10" 
                                                name="plan_value" 
                                                required="required" 
                                                placeholder="0,00" 
                                                autocomplete="off"
                                                onkeydown="return event.keyCode !== 69">
                                        
                                    </div>
                                

                                    <div class="col-md-2 field-installments">
                                            
                                            <label for="plan_installments"><?= $this->lang->line('iugu_plans_labels_installments') ?></label>

                                            <input type="number" 
                                                    class="form-control " 
                                                    id="plan_installments" 
                                                    name="plan_installments" 
                                                    maxlength="10" 
                                                    placeholder="10" 
                                                    value="1"
                                                    autocomplete="off">
                                            
                                    </div>

                                    <div class="col-md-2 field-installment-value">
                                        
                                        <label for="installment_value"><?= $this->lang->line('iugu_plans_labels_installment_value') ?></label>

                                        <input type="text" value="R$" class="form-control input-icon" disabled="disabled">
                                        <input 
                                                type="number" min="0.00" max="10000.00" step="any"
                                                class="form-control input-icon-value" 
                                                id="installment_value" 
                                                maxlength="10" 
                                                name="installment_value"
                                                placeholder="0,00" 
                                                autocomplete="off"
                                                disabled="disabled">
                                        
                                    </div>

                                    <div class="col-md-2 col-xs-2">

                                        <label for="plan_status"><?= $this->lang->line('iugu_plans_labels_plan_status') ?> *</label>

                                        <select class="form-control" id="plan_status" name="plan_status" >
                                            
                                            <option value="1" selected><?= $this->lang->line('iugu_plans_labels_plan_status_active') ?></option>
                                            <option value="2"><?= $this->lang->line('iugu_plans_labels_plan_status_disabled') ?></option>

                                        </select>

                                    </div>

                                
                                </div>

                            </div>

                        

                        <style>

                            .alert{
                                display:none;
                            }
                            .field-installments, .field-installment-value{
                                display: none;
                            }
                            
                            .input-icon{
                                position: absolute;
                                width: 42px;
                            }

                            .input-icon-value{
                                padding-left: 52px;
                            }
                        </style>

                        <script>

                            $(function()
                            {
                                $('#plan_type').change(function()
                                {
                                    var type = $(this).val();
                                    var fields = ['.field-installments', '.field-installment-value'];

                                    if (type == 'cash')
                                        showHideField(fields, 'Out');
                                    else
                                        showHideField(fields, 'In');
                                });


                                $('#plan_value, #plan_installments').blur(function()
                                {
                                    var amount = parseFloat($('#plan_value').val());
                                    var installments = parseInt($('#plan_installments').val());

                                    var total = (amount / installments);

                                    $('#installment_value').val(total.toFixed(2));

                                    $('#plan_value').val(amount.toFixed(2));

                                });


                                $('#plan_title').blur(function()
                                {
                                    var page_url = base_url.concat("iugu/checkPlanTitle");

                                    var sendData = {
                                        plan_title:  $(this).val()
                                    };

                                    $.post(page_url, sendData, function(data)
                                    {
                                        if (data == 'OK')
                                            $(".alert").fadeOut();
                                        else if ($('#plan_title').val().trim() != '')
                                            $('.alert').fadeIn();
                                        else
                                            $('.alert').fadeOut();
                                    });
                                });


                                $('#btnSave').click(function()
                                {
                                    if ($('.alert').is(":visible"))
                                    {
                                        alert('<?= $this->lang->line('iugu_plans_title_alert_alert') ?>');
                                    }
                                    else
                                    {
                                        setTimeout(function()
                                        {
                                            $('#btnSaveHidden').click();
                                        },350);
                                        
                                    }
                                });

                            });


                            function showHideField(field, show)
                            {
                                $.each(field, function(k,v)
                                {
                                    eval('$(\''+ v + '\').fade' + show + '()');
                                });
                            }


                            function characterLimit(object)
                            {
                                var limit = object.getAttribute('maxlength');
                                var attribute = object.getAttribute('id');
                                var quantity = object.value.length;

                                $('#char_' + attribute).text(`<?= $this->lang->line('iugu_plans_types_chars') ?>: ${quantity}/${limit}`);
                            }

                        </script>

                    </div>
                <!-- /.box-body -->

                    <div class="box-footer ml-3 pb-5">
                        <button type="button" id="btnSave" name="btnSave" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                        <button type="submit" id="btnSaveHidden" name="btnSaveHidden" style="display: none;"></button>
                        <button type="button" id="btnVoltar" name="btnVoltar" onClick="window.location.href = base_url + 'iugu/listPlans';" class="btn btn-warning"><?=$this->lang->line('application_back');?></button>
                    </div>

                </form>
            </div>
        <?php endif; ?>


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

