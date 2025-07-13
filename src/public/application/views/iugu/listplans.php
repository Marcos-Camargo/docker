<!-- Content Wrapper. Contains page content -->
<script>

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
        

        <style>

            .box-total, .box-btns{
                position: relative;
                float: right;
                margin: 0 20px;
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

            #box-btns, #plans-list-header{
                position: -webkit-sticky;
                position: sticky;
                top: 0;
                z-index: 100;
                background-color: #fff;
                padding: 10px 10px 15px 15px;   
            }

            .modal-details-value{
                font-size: 1.5rem;
                font-weight: 900;
                display: inline-block;
                padding-left: 20px;
            }

            .modal-detail-row{
                border-bottom: 1px solid #ccc;
                margin-top: 12px;
            }

            .plan-line td{
                overflow-wrap: break-word;
            }

            </style>


        <?php if(in_array('viewIuguPlans', $user_permission)): ?>

            <div class="box" id="box-filters">
              <div class="box-body">
              	<div class="box-header">                  
                </div>



                <div class="col-md-12 col-xs-12">

                    <div class="box-totals">

                        <div class="box-total new-plan">
                            <button id="btn-new-plan" type="button" class="btn btn-primary"><?=$this->lang->line('iugu_plans_list_btns_new_plan');?><!-- &nbsp;&nbsp; <i class="fa fa-fw fa-history"></i>--></button>
                        </div>

                        <div class="box-total history">
                            <button id="btn-modal-billing-history" type="button" class="btn btn-primary" ><?=$this->lang->line('iugu_plans_list_btns_transaction_history');?><!-- &nbsp;&nbsp; <i class="fa fa-fw fa-history"></i>--></button>
                        </div>

                    </div>

                </div>

                
                
                <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3>

                <div class="form-group col-md-2 col-xs-2">
                                
                    <label for="slc_store"><?= $this->lang->line('application_stores') ?></label>
                	
                    <select class="form-control selectpicker222" id="slc_store" name="slc_store" >
                        <option value="*"><?= $this->lang->line('application_search_input') ?></option>
                        <?php foreach ($stores as $key => $store): ?>
                            <option value="<?=$store['plan_id']?>"><?=$store['name']?></option>
                        <?php endforeach ?>

                    </select>
                </div>

                <div class="form-group col-md-2 col-xs-2">

                    <label for="plan_status"><?= $this->lang->line('iugu_plans_labels_plan_status') ?></label>
                    
                    <select v-model.trim="entry.plan_status" class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" 
                        id="plan_status" :disabled="allInputsDisabled" :readonly="allInputsDisabled" multiple="multiple">
                        <option value="1" selected><?= $this->lang->line('iugu_plans_labels_plan_status_active') ?></option>
                        <option value="2"><?= $this->lang->line('iugu_plans_labels_plan_status_disabled') ?></option>
                    </select>

                </div>

                <div class="form-group col-md-2 col-xs-2">
                    <br/>
                    <button style="margin-top: 4px;" class="btn btn-primary" id="btn_limpar" name="btn_limpar"><?=$this->lang->line('payment_balance_transfers_btn_filter');?><!-- &nbsp;&nbsp; <i class="fa fa-fw fa-filter"></i>--></button>
                </div>

              </div>
              <!-- /.box-body -->
            </div>

        <?php endif; ?>
        
        <div class="box">
          <div class="box-body" id="grid-body">        

            <table  id="plans_table" class="table table-bordered table-striped" >
              <thead>
                <tr id="plans-list-header">
        
                    <th><?=$this->lang->line('iugu_plans_labels_title');?></th>
                    <th><?=$this->lang->line('iugu_plans_labels_type');?></th>
                    <th><?=$this->lang->line('iugu_plans_labels_value');?></th>
                    <th><?=$this->lang->line('iugu_plans_labels_installments');?></th>
                    <th><?=$this->lang->line('iugu_plans_labels_installment_value');?></th>
                    <th><?=$this->lang->line('iugu_plans_labels_plan_user');?></th>
                    <th><?=$this->lang->line('iugu_plans_labels_date_creation');?></th>
                    <th><?=$this->lang->line('iugu_plans_labels_stores_errors');?></th>
                    <th><?=$this->lang->line('iugu_plans_labels_plan_status');?></th>
                    <th  data-orderable="false"><?=$this->lang->line('iugu_plans_labels_actions');?></th>
                </tr>
              </thead>

              
              <tbody id="transfer-list">
                  
                <?php 

                    if ($plans):
                        foreach ($plans as $key => $plan):
                    
                            $active_icon = 'fa-check-circle';
                            $active_opacity = '1';

                            switch ($plan['plan_status'])
                            {
                                case '1': $plan_status = $this->lang->line('iugu_plans_labels_plan_status_active'); break;
                                default:  $plan_status = $this->lang->line('iugu_plans_labels_plan_status_disabled');  $active_icon = 'fa-circle-o';  $active_opacity = '0.5';
                            }

                            $plan_value = money(round(($plan['plan_value'] / 100), 2));
                            $plan_installments = ($plan['plan_type'] == 'cash') ? '' : $plan['plan_installments'];
                            $plan_installment_value = ($plan['plan_type'] == 'cash') ? '' : money(round(($plan['installment_value'] / 100), 2));

                            $plan_responsible = '<a href="'.base_url('users/edit/'.$plan['user_id']).'" target="_blank">'.$plan['user_name'].'</a>';

                            switch ($plan['plan_type'])
                            {
                                case 'cash': $plan_type = $this->lang->line('iugu_plans_option_cash'); break;
                                default:     $plan_type = $this->lang->line('iugu_plans_option_financed'); break;
                            }

                            $created_at = date('d/m/Y',strtotime($plan['created_at']));
                                   
                    ?>
                      <tr 
                            id="plan-<?=$plan['id']?>"
                            class="plan-line"
                            data-status="<?=$plan['plan_status']?>" 
                            style="opacity: <?=$active_opacity?>;"
                            data-user="<?=$plan['user_name']?>"
                        >
                        <td><?=$plan['plan_title']?></td>
                        <td><?=$plan_type?></td>
                        <td><?=$plan_value?></td>
                        <td><?=$plan_installments?></td>
                        <td><?=$plan_installment_value?></td>
                        <td><?=$plan_responsible?></td>
                        <td><?=$created_at?></td>
                        <td>0</td>
                        <td><?=$plan_status?></td>
                        <td>
                            <a href="<?=base_url('/iugu/storesInPlan/'.$plan['id'])?>" class="btn btn-dark" role="button"><i class="fa fa-home"></i></a>
                            <button type="button" title="<?=$this->lang->line('iugu_plans_list_actions_title_details');?>" class="btn btn-dark btn-modal-plan-details"><i class="fa fa-eye"></i></button>
                            <button type="button" title="<?=$this->lang->line('iugu_plans_list_actions_activate');?>" class="btn btn-dark btn-sm222 toggle-plan"><i class="toggle-plan-icon fa <?=$active_icon?>"></i></button>
                        </td>
                      </tr>

                <?php 
                        endforeach;
                    endif;
                ?>
            
                    <!-- <script src="https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js"></script> -->
                    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/amcharts/3.21.15/plugins/export/libs/FileSaver.js/FileSaver.min.js"></script>
                    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.7.12/xlsx.core.min.js"></script>
                    <script type="text/javascript" src="https://unpkg.com/tableexport.jquery.plugin@1.26.0/tableExport.min.js"></script>
                    <script>

                        $(function() 
                        {
                            setTimeout(function()
                            {
                                setFilter();
                            }, 100);


                            $("#plans_table").DataTable(
                            {
                                // columnDefs: [
                                //     { orderable: false, targets: 5 }
                                // ],
                                paging:     false,
                                bFilter:    false,
                                bInfo :     false,
                                "order":    []
                            });


                            $('#btn-new-plan').click(function()
                            {
                                window.location.href = base_url + 'iugu/createPlan';
                            });
                            

                            $('.toggle-plan').click(function(e)
                            {
                                e.preventDefault();

                                var plan_id         = $(this).parents('.plan-line').attr('id').split('-')[1];
                                var plan_status     = $(this).parents('.plan-line').attr('data-status');
                                var filter_status   = $('#plan_status').val();
                                
                                var sendData = {
                                    plan_id:        plan_id,
                                    plan_status:    plan_status
                                }

                                $.post(base_url + 'iugu/togglePlanStatus', sendData, function(new_status)
                                {
                                    if (new_status == 'active')
                                    {
                                        $('#plan-' + plan_id).attr('data-status', '1').css({'opacity': 1});
                                        $('#plan-' + plan_id).find('.toggle-plan-icon').addClass('fa-check-circle').removeClass('fa-circle-o');

                                        if (jQuery.inArray("1", filter_status) < 0)
                                            setTimeout(function(){$('#plan-' + plan_id).fadeOut();}, 600);
                                    }
                                    else
                                    {
                                        $('#plan-' + plan_id).attr('data-status', '2').css({'opacity': 0.5});
                                        $('#plan-' + plan_id).find('.toggle-plan-icon').removeClass('fa-check-circle').addClass('fa-circle-o');

                                        if (jQuery.inArray("2", filter_status) < 0)
                                            setTimeout(function(){$('#plan-' + plan_id).fadeOut();}, 600);
                                    }
                                });
                            });


                            $('#slc_store, #plan_status').change(function()
                            {
                                setFilter();
                            });


                            $('#btn_limpar').click(function()
                            {
                                clearFilters();
                            });


                            $('.btn-modal-plan-details').click(function()
                            {
                                var plan_id = parseInt($(this).parents('tr').attr('id').replace('plan-', ''));

                                $.get(base_url + 'iugu/getModalPlanDetails/'+plan_id, function(data)
                                {
                                    fillModalDetails(data);
                                });

                                $("#modal-plan-details").modal();
                            });


                            $("#modal-plan-details").on('hidden.bs.modal', function()
                            {
                                fillModalDetails();
                            });


                            $('#btn-modal-billing-history').click(function()
                            {
                                $("#modal-billing-history").modal();
                            });


                            $("#modal-history-slc-store, #modal-history-status, #modal-history-view").change(function()
                            {
                                var filter_store = $("#modal-history-slc-store").val();
                                var filter_status = $("#modal-history-status").val();
                                var filter_view = $("#modal-history-view").val();
                                
                                var store;
                                var status;
                                var view;

                                $(".modal-billing-history-row").each(function(k,v)
                                {
                                    store = $(this).attr('data-store');
                                    status = $(this).attr('data-status');
                                    view = $(this).attr('data-view');
                                    
                                    if (
                                        (store == filter_store || filter_store == 0)
                                        &&
                                        $.inArray(status, filter_status) > -1 
                                        && 
                                        (view == filter_view || filter_view == 'all')
                                    )
                                    {
                                        $(this).show();
                                    }
                                    else
                                    {
                                        $(this).hide();
                                    }
                                });
                            });


                            $('#btn-moda-history-export').click(function()
                            {
                                // TableToExcel.convert(document.getElementById("modal-history-grid"), {
                                //     name: slugify('<?=substr($this->lang->line('application_balances_transfers'),0,30);?>')+'.xlsx',
                                //     sheet: {
                                //     name: "Sheet1"
                                //     }
                                // });
                                $('#modal-history-grid').tableExport(
                                {
                                    type:'excel',
                                    exportHiddenCells:false,
                                    fileName:'historico_de_cobrancas',
                                });
                            });
                      

                        });
                            
                            
                        function setFilter()
                        {                     
                            var status;
                            var plan_id;
                            var store = $('#slc_store').val();
                            var filter_status = $('#plan_status').val();

                            $('.plan-line').each(function(k,v)
                            {
                                status = "" + ($(this).attr('data-status')) + "";
                                plan_id = $(this).attr('id');
                            
                                if (
                                    $.inArray(status, filter_status) > -1
                                    &&
                                    (store == '*' || plan_id == 'plan-'+ store)
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


                        function clearFilters()
                        {
                            $('#slc_store').find('option').prop('selected', false).parent('select').selectpicker('refresh');
                            $('#plan_status').selectpicker('val', '1').parent('select').selectpicker('refresh');

                            setTimeout(function()
                            {
                                setFilter();
                            }, 10);
                        }


                        function clearModalHistoryFilters()
                        {
                            $("#modal-history-slc-store").val('0').trigger('change');
                            // $("#modal-history-status").val(['success', 'fail']);
                            $("#modal-history-status").selectpicker('val', ['success', 'fail']).parent('select').selectpicker('refresh');
                            $("#modal-history-view").val('last').trigger('change');
                        }
                        

                        function fillModalDetails(details)
                        {      
                            var empty_details = {"id":"","plan_title":"","plan_type":"","plan_value":"","plan_installments":"","installment_value":"","plan_status":"","user_id":"","user_id":"","username":"","date":"","iugu_plan_id":"","created_at":""};                                                  

                            if (typeof details == 'undefined' || !isJson(details))
                                details = empty_details;
                            else
                                details = JSON.parse(details);

                            var plan_type = plan_status = '';
                            
                            switch (details.plan_type)
                            {
                                case 'cash': plan_type = '<?=$this->lang->line('iugu_plans_option_cash')?>'; break;
                                case 'financed': plan_type = '<?=$this->lang->line('iugu_plans_option_financed')?>'; break;
                            }

                            switch (details.plan_status)
                            {
                                case '1': plan_status = '<?=$this->lang->line('iugu_plans_labels_plan_status_active')?>'; break;
                                case '2': plan_status = '<?=$this->lang->line('iugu_plans_labels_plan_status_disabled')?>'; break;
                            }

                            $('#plan-detail-title').text(details.plan_title);
                            $('#plan-detail-type').text(plan_type);
                            $('#plan-detail-value').text((details.plan_value > 0) ? 'R$ '+parseFloat(details.plan_value / 100).toLocaleString('pt-br', {minimumFractionDigits: 2}) : '');
                            $('#plan-detail-installments').text(details.plan_installments);
                            $('#plan-detail-installment-value').text((details.installment_value> 0) ? 'R$ '+parseFloat(details.installment_value / 100).toLocaleString('pt-br', {minimumFractionDigits: 2}) : '');
                            $('#plan-detail-plan_status').text(plan_status);
                            $('#plan-detail-user-id').text(details.user_id);
                            $('#plan-detail-user-name').text(details.username);
                            $('#plan-detail-date').text(details.date);
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

                    </script>


              </tbody>
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


<!-- waiting orders modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="modal-plan-details">
  <div class="modal-dialog" role="document" style2222="width: 80vw !important; margin: 10vh auto !important;"> 
    <div class="modal-content">
      
        <div class="modal-header" style="border-bottom:0 !important;">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="application_close"><span aria-hidden="true">&times;</span></button>
            <h3 class="modal-title"><?=$this->lang->line('iugu_plans_list_actions_view_plan');?></h3>
        </div>

        <div class="modal-body">
            
            <div class="container col-md-12" >

                <div class="row modal-detail-row">
                    <div class="col-md-3"><label><?=$this->lang->line('iugu_plans_labels_title');?>:</label></div>
                    <div class="modal-details-value col-md-9" id="plan-detail-title"></div>
                </div>
                <div class="row modal-detail-row">
                    <div class="col-md-3"><label><?=$this->lang->line('iugu_plans_labels_type');?>:</label></div>
                    <div class="modal-details-value col-md-9" id="plan-detail-type"></div>
                </div>
                <div class="row modal-detail-row">
                    <div class="col-md-3"><label><?=$this->lang->line('iugu_plans_labels_value');?>:</label></div>
                    <div class="modal-details-value col-md-9" id="plan-detail-value"></div>
                </div>
                <div class="row modal-detail-row">
                    <div class="col-md-3"><label><?=$this->lang->line('iugu_plans_labels_installments');?>:</label></div>
                    <div class="modal-details-value col-md-9" id="plan-detail-installments"></div>
                </div>
                <div class="row modal-detail-row">
                    <div class="col-md-3"><label><?=$this->lang->line('iugu_plans_labels_installment_value');?>:</label></div>
                    <div class="modal-details-value col-md-9" id="plan-detail-installment-value"></div>
                </div>
                <div class="row modal-detail-row">
                    <div class="col-md-3"><label><?=$this->lang->line('iugu_plans_labels_plan_status');?>:</label></div>
                    <div class="modal-details-value col-md-9" id="plan-detail-plan_status"></div>
                </div>
                <div class="row modal-detail-row">
                    <div class="col-md-3"><label><?=$this->lang->line('iugu_plans_labels_username');?>:</label></div>
                    <div class="modal-details-value col-md-9" id="plan-detail-user-name"></div>
                </div>
                <div class="row modal-detail-row">
                    <div class="col-md-3"><label><?=$this->lang->line('iugu_plans_labels_date_creation');?>:</label></div>
                    <div class="modal-details-value col-md-9" id="plan-detail-date"></div>
                </div>


            </div> 
        </div> <!-- modal-body -->

        <div class="modal-footer d-flex ">
            <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$this->lang->line("application_close")?></button>
        </div>	

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->




<!-- Billing History modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="modal-billing-history">
  <div class="modal-dialog" role="document" style="width: 80vw !important; margin: 10vh auto !important;"> 
    <div class="modal-content" style="height: 80vh !important; overflow-y: auto;">
      
        <div class="modal-header" style="border-bottom:0 !important;">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="application_close"><span aria-hidden="true">&times;</span></button>
            <h3 class="modal-title"><?=$this->lang->line('iugu_billing_history_modal_title');?></h3>
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
                            <select class="form-control selectpicker show-tick" id="modal-history-slc-store">
                                <option value="0" selected><?=$this->lang->line('iugu_filter_option_select')?></option>
                                <?php foreach ($modal_stores as $key => $store): ?>
                                    <option value="<?=$key?>"><?=$store?></option>
                                <?php endforeach ?>
                            </select>
                        </div>

                        <div class="form-group col-md-2 col-xs-2">

                            <label for="modal-history-status"><?= $this->lang->line('application_status') ?></label>
                            <select v-model.trim="entry.modal-history-status" class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" 
                                id="modal-history-status" :disabled="allInputsDisabled" :readonly="allInputsDisabled" multiple="multiple">
                                <option value="success" selected><?=$this->lang->line('iugu_filter_option_success')?></option>
                                <option value="fail" selected><?=$this->lang->line('iugu_filter_option_fail')?></option>
                            </select>

                        </div>


                        <div class="form-group col-md-2 col-xs-2">
                            <label for="modal-history-view"><?= $this->lang->line('iugu_filter_title_view') ?></label>
                            <select class="form-control show-tick" id="modal-history-view">
                                <option value="last" selected><?=$this->lang->line('iugu_filter_option_view_last')?></option>
                                <option value="all"><?=$this->lang->line('iugu_filter_option_view_all')?></option>
                            </select>
                        </div>


                        <div class="form-group col-md-2 vbottom">
                            <button type="button" onClick="clearModalHistoryFilters()" id="btn_modal_history_clear" name="btn_modal_history_clear" class="btn btn-primary"><?=$this->lang->line('payment_balance_transfers_btn_filter');?><!-- &nbsp;&nbsp; <i class="fa fa-fw fa-filter"></i>--></button>
                        </div>

                        <div class="form-group col-md-2 vbottom">
                            <button type="button" id="btn-moda-history-export" style="float: left;" class="btn btn-success"><?=$this->lang->line('payment_balance_transfers_btn_export');?><!-- &nbsp; <i class="fa fa-fw fa-file-excel-o"></i>--></button>
                        </div>
                        <style>

                        .vbottom {
                            padding-top: 26px;
                        }

                        </style>

                    </div>
                    
                    <div style="overflow-y: auto; overflow-x: hidden; height: 100% !important; max-height: 45vh;">

                        <table  id="modal-history-grid" class="table table-bordered table-striped" >

                            <thead>
                                <tr>                                    
                                    <th><?=$this->lang->line('application_store');?></th>
                                    <th><?=$this->lang->line('iugu_billing_history_modal_tr_plan_title');?></th>
                                    <th><?=$this->lang->line('iugu_billing_history_modal_tr_plan_type');?></th>
                                    <th><?=$this->lang->line('iugu_billing_history_modal_tr_amount');?></th>
                                    <th><?=$this->lang->line('iugu_billing_history_modal_tr_installments');?></th>
                                    <th><?=$this->lang->line('iugu_billing_history_modal_tr_billing_date');?></th>
                                    <th><?=$this->lang->line('iugu_billing_history_modal_tr_status');?></th>                                    
                                    <!-- <th><?=$this->lang->line('iugu_billing_history_modal_tr_action');?></th>                                     -->
                                </tr>
                            </thead>
                
                            <tbody id="modal-billing-history-list">

                                <?php

                                    $log_unique = [];

                                    foreach ($billing_history_initital_logs as $log):

                                        $display = 'none';
                                        $view  = 'all';

                                        if (!in_array($log['name'], $log_unique))
                                        {
                                            $log_unique[] = $log['name'];
                                            $display = 'table-row';
                                            $view = 'last';
                                        }
                                ?>

                                <tr class="modal-billing-history-row"
                                    style="display: <?=$display?>;" 
                                    data-view="<?=$view?>"
                                    data-status="<?=$log['status']?>"
                                    data-store="<?=$log['store_id']?>"
                                    >
                                    <td><?=$log['name']?></td>
                                    <td><?=$log['plan_title']?></td>
                                    <td><?=($log['plan_type'] == 'cash') ? $this->lang->line('iugu_plans_option_cash') : $this->lang->line('iugu_plans_option_financed');?></td>
                                    <td><?=$log['amount']?></td>
                                    <td><?=$log['installments']?></td>
                                    <td><?=$log['billing_date']?></td>
                                    <td><?=($log['status'] == 'success') ? $this->lang->line('iugu_filter_option_success') : $this->lang->line('iugu_filter_option_fail');?></td>                                    
                                    <!-- <td><button type="button" title="Visualizar Detalhes do Plano" class="btn btn-dark btn-modal-plan-details"><i class="fa fa-eye" aria-hidden="true"></i></button></td>                                     -->
                                </tr>
                                <?php
                                    endforeach;
                                ?>
                            </tbody>

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