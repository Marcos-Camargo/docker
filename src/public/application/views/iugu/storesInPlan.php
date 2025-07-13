
<!-- Content Wrapper. Contains page content -->
<script>

    let base_url = "<?php echo base_url(); ?>";

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
	        $data['page_now'] = "iugu_storesinplan_title";
	        $this->load->view('templates/content_header',$data); 
    ?>

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

            .temp-date-list{
                width: 160px;
            }

        </style>
        
        <?php if(in_array('updateIuguPlans', $user_permission)): ?>

            <div class="box" id="box-filters">
              <div class="box-body">
              	<div class="box-header">                  
                </div>

                
                <h3 class="box-title"><?=$this->lang->line('iugu_plans_title_plan_name').' '.$plan_data['plan_title'];?></h3>


                <div class="form-group col-md-2 col-xs-2">

                    <label for="slc_stores"><?= $this->lang->line('iugu_filter_title_stores_included') ?></label>

                    <select v-model.trim="entry.slc_stores" class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" 
                        id="slc_stores" :disabled="allInputsDisabled" :readonly="allInputsDisabled" multiple="multiple">
                        <?php foreach ($available_stores as $store): ?>
                            <option value="<?=$store['store_id']?>"><?=ucfirst($store['name'])?></option>
                        <?php endforeach ?>
                    </select>

                </div>

                <div class="form-group col-md-2 col-xs-2">

                    <label for="temp-date" ><?= $this->lang->line('iugu_plans_labels_select_start_date') ?></label>
                    <input type="text" class="form-control" id="temp-date" name="temp-date" placeholder="dd-mm-yyyy">

                </div>

                <div class="form-group col-md-2 col-xs-2">
                    <br/>
                    <button style="margin-top: 4px;" class="btn btn-primary" id="btn-select" name="btn-select"><?=$this->lang->line('iugu_plans_labels_select_stores');?><!-- &nbsp;&nbsp; <i class="fa fa-fw fa-filter"></i>--></button>
                </div>

              </div>
              <!-- /.box-body -->
            </div>

        <?php endif; ?>
        
        <div class="box">
          <div class="box-body" id="grid-body">        
            <h4>Lista Temporária de Lojas</h4>
            <table  id="temp-table" class="table table-bordered table-striped" >
              <thead>
                <tr id="temp-table-header">        
                    <th width="10%"><?=$this->lang->line('iugu_plans_stores_column_id');?></th>
                    <th width="50%"><?=$this->lang->line('iugu_plans_stores_column_name');?></th>
                    <th width="30%"><?=$this->lang->line('iugu_plans_stores_column_date');?></th>
                    <th width="10%" data-orderable="false"><?=$this->lang->line('iugu_plans_stores_column_action');?></th>
                </tr>
              </thead>              
              <tbody id="temp-table-list"></tbody>
            </table>

            <div class="form-group col-md-12 col-xs-12 text-right">
                <button class="btn btn-primary btn-add-temp-stores" id="btn-add-temp-stores" name="btn-add-temp-stores"><?=$this->lang->line('iugu_plans_stores_btn_add_temp_stores');?><!-- &nbsp;&nbsp; <i class="fa fa-fw fa-filter"></i>--></button>
            </div>

          </div>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->


        <div class="box">
          <div class="box-body" id="grid-body">        
            <h4>Lista de Lojas Associadas e este Plano</h4>
            <table  id="stores-table" class="table table-bordered table-striped" >
              <thead>
                <tr id="stores-table-header">
                    <th width="10%"><?=$this->lang->line('iugu_plans_stores_column_id');?></th>
                    <th width="50%"><?=$this->lang->line('iugu_plans_stores_column_name');?></th>
                    <th width="30%"><?=$this->lang->line('iugu_plans_stores_column_date');?></th>
                    <th width="10%" data-orderable="false"><?=$this->lang->line('iugu_plans_stores_column_action');?></th>
                </tr>
              </thead>

              <tbody id="stores-table-list">
                  
                <?php 

                    $active_icon = 'fa-check-circle';
                    $active_opacity = '1';

                    if ($current_stores):
                        foreach ($current_stores as $key => $store):
                                                                                           
                    ?>
                      <tr 
                            id="store-<?=$store['id']?>"
                            class="store-line"
                        >
                        <td><?=$store['id']?></td>
                        <td><?=$store['name']?></td>
                        <td><?=date('d-m-Y', strtotime($store['date_plan_start']))?></td>
                        <td>
                        <button type="button" title="<?=$this->lang->line('iugu_plans_list_actions_activate');?>" class="btn btn-dark toggle-plan"><i class="toggle-plan-icon fa <?=$active_icon?>"></i></button>
                        </td>
                      </tr>

                <?php 
                        endforeach;
                    endif;
                ?>
    
                    <script>

                        var available_stores = [
                            <?php 
                                $stores_array = [];

                                foreach ($available_stores as $store)
                                {
                                    $stores_array[] = "{ id: '".$store['store_id']."', name: '".addslashes($store['name'])."'}";
                                }

                                echo implode(',', $stores_array);
                            ?>
                        ];

                   
                        $('body').on('focus',".temp-date-list", function()
                        {
                            $(this).datepicker(
                            {
                                format: 'dd-mm-yyyy',
                                ignoreReadonly: true,
                                todayHighlight: true,
                                showTodayButton: true,
                                autoclose: true
                            }).on('changeDate', function(ev)
                            {
                                $(this).parent('td').attr('data-temp-date', ev.target.value);
                            });
                        });


                        $('body').on('click', '.remove-temp-list', function()
                        {
                            var id = $(this).parents('tr').attr('id').replace('temp-date-','');
                            
                            $('#slc_stores').find('[value='+ id + ']').prop('selected', false);
                            $('#slc_stores').selectpicker('refresh');
                            $(this).parents('tr').fadeOut().remove();
                        });


                        $('body').on('click', '.toggle-plan', function(e)
                        {
                            e.preventDefault();
                            
                            loading('show');

                            var store_id = $(this).parents('tr').attr('id').split('-')[1];
                                                        
                            var sendData = {
                                plan_id:  <?=$plan_data['id']?>,
                                store_id: parseInt(store_id)
                            }

                            $.post(base_url + 'iugu/removeStoreInPlan', sendData, function(data)
                            {
                                var title = {
                                    success: '<?=$this->lang->line("iugu_remove_plans_stores_msg_success")?>',
                                    error: '<?=$this->lang->line("iugu_remove_plans_stores_msg_error")?>'
                                };
                            
                                AlertSweet.fire({
                                    icon: data,
                                    title: title[data],
                                }).then((result) => {
                                    loading('hide');
                                    if (data == 'success')
                                        setTimeout(function(){$('#store-'+store_id).fadeOut('slow').remove();}, 500);
                                });
                            });
                        });


                        $(function() 
                        {
                            $('#temp-date').datepicker(
                            {
                                format: 'dd-mm-yyyy',
                                ignoreReadonly: true,
                                todayHighlight: true,
                                showTodayButton: true,
                                autoclose: true
                            });

                            $('#btn-select').click(function()
                            {
                                var stores = $('#slc_stores').val();
                                var store_data,new_line;
                                var temp_date = $('#temp-date').val();

                                if (temp_date.length == 0)
                                {
                                    var today = new Date();
                                    var dd = String(today.getDate()).padStart(2, '0');
                                    var mm = String(today.getMonth() + 1).padStart(2, '0');
                                    var yyyy = today.getFullYear();
                                    temp_date = dd + '-' + mm + '-' + yyyy;
                                }

                                if (stores.length > 0)
                                {                                                                        
                                    $.each(stores, function(index, item)
                                    {
                                        store_data = available_stores.find(store => store.id === item);
                                        
                                        if ($('#temp-date-'+ store_data['id']).length === 0)
                                        {
                                            new_line = '<tr id="temp-date-'+ store_data['id'] +'">'+
                                                        '<td>'+store_data['id']+'</td>'+
                                                        '<td>'+store_data['name']+'</td>'+
                                                        '<td data-temp-date="'+ temp_date +'"><input value="'+ temp_date + '" type="text" class="form-control temp-date-list"  name="temp-date" placeholder="dd-mm-yyyy"></td>'+
                                                        '<td><button type="button" class="btn btn-dark remove-temp-list" title="<?=$this->lang->line('iugu_plans_list_actions_remove');?>"><i class="toggle-plan-icon fa fa-minus-circle"></i></button></td>'+
                                                    '</tr>';

                                            $('#temp-table-list').append(new_line);
                                        }
                                    });
                                }
                            });


                            $('#btn-add-temp-stores').click(function()
                            {
                                var lines = $('#temp-table-list').children();
                                var lines_n = $('#temp-table-list').children().length;
                                var new_store, new_store_date, new_store_id;
                                var sendData = {
                                    plan_id: <?=$plan_data['id']?>,
                                };
                                var store_ids = [];
                                var plan_dates = [];

                                if (lines_n == 0)
                                {
                                    AlertSweet.fire({
                                        icon: 'warning',
                                        title: '<?=$this->lang->line("iugu_plans_stores_msg_add_empty")?>',
                                    }).then((result) => {
                                        loading('hide');
                                    });                                

                                    return false;
                                }                               

                                loading('show');

                                $.each(lines, function(index, item)
                                {
                                    new_store = $(item).clone();
                                    new_store_date = new_store.find('[data-temp-date]').attr('data-temp-date');
                                    new_store_id = new_store.attr('id').replace('temp-date-','');                                    

                                    store_ids.push(parseInt(new_store_id));
                                    plan_dates.push(new_store_date);
                                });

                                sendData.store_ids = store_ids;
                                sendData.plan_dates = plan_dates;
     
                                if (store_ids.length > 0)
                                {
                                    $.post(base_url + 'iugu/storesInPlan', sendData, function(data)
                                    {
                                        var title = {
                                            success: '<?=$this->lang->line("iugu_plans_stores_msg_success")?>',
                                            warning: '<?=$this->lang->line("iugu_plans_stores_msg_warning")?>',
                                            error: '<?=$this->lang->line("iugu_plans_stores_msg_error")?>'
                                        };

                                        AlertSweet.fire(
                                        {
                                            icon: data,
                                            title: title[data],
                                        }).then((result) => {
                                            sendStoresToPlan();
                                            loading('hide');
                                        });
                                        
                                    });
                                }
                            });

                        });


                        function sendStoresToPlan()
                        {
                            var lines = $('#temp-table-list').children();
                            var new_store, new_store_date, new_store_id;

                            $.each(lines, function(index, item)
                            {
                                new_store = $(item).clone();
                                new_store_date = new_store.find('[data-temp-date]').attr('data-temp-date');
                                new_store_id = new_store.attr('id').replace('temp-date-','');                                    
                                new_store.find('[data-temp-date]').html(new_store_date);
                                new_store.attr('id', 'store-'+ new_store_id).addClass('store-line');
                                new_store.find('.btn-dark').removeClass('remove-temp-list').addClass('toggle-plan');
                                new_store.find('.btn-dark').attr('title', '<?=$this->lang->line('iugu_plans_list_actions_activate');?>');
                                new_store.find('.btn-dark i').removeAttr('class').attr('class', 'toggle-plan-icon fa <?=$active_icon?>');
                                
                                // store_ids.push(parseInt(new_store_id));
                                // plan_dates.push(new_store_date);

                                $("#slc_stores option[value='"+ new_store_id +"']").remove();
                                $('#slc_stores').find('[value="'+ new_store_id + '"]').remove();
                                $('#slc_stores').selectpicker('refresh');

                                $(item).remove();                                   
                                $(window).scrollTop(0);

                                $('#stores-table-list').prepend(new_store);

                            });
                        }
                            
                        
                        function loading(display = 'show')
                        {
                            if (display == 'show')
                                $('#loading_wrap').fadeIn();
                            else
                                $('#loading_wrap').fadeOut();
                        }


                    </script>

              </tbody>
            </table>
          </div>
          <!-- /.box-body -->

        <div class="box-footer ml-3 pb-5">
            <button type="button" id="btnVoltar" name="btnVoltar" onClick="window.location.href = base_url + 'iugu/listPlans';" class="btn btn-warning"><?=$this->lang->line('application_back');?></button>
        </div>


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