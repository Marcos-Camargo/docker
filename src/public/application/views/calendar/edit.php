<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
      <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>
  
      <!-- Main content -->
      <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">
  
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
                
                <?php 
                    $action = base_url('calendar/'.$function);
                    $readonly = '';
                    $optionRO = ''; 
                    if ($function == 'delete') {
                        $header = $this->lang->line('application_delete_event');
                        $action .= '/'.$data['ID'];
                        $readonly = ' readonly '; 
                        $optionRO = ' disabled '; 
                    } elseif ($function == 'create') {
                        $header = $this->lang->line('application_add_calendar_event');
                    } elseif ($function == 'edit') {
                        $header = $this->lang->line('application_update_calendar_event');
                        $action .= '/'.$data['ID'];
                    } else {
                        $header = $this->lang->line('application_view');
                        $action .= '/'.$data['ID'];
                        $readonly = ' readonly '; 
                        $optionRO = ' disabled '; 
                    }
                ?>


                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><?php echo  $header;?></h3>
                    </div>
                    <form role="form" action="<?php echo $action ?>" method="post" id="calendarForm">
                        <input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
                        <div class="box-body">
                            <div id="messages"></div>
                            <div class="form-group <?php echo (form_error('edit_title')) ? 'has-error' : '';  ?>">
                                <label for="ev_name" class="col-md-2 label-heading"><?=$this->lang->line('application_event_name')?></label>
                                <div class="col-md-6 input-group  ui-front ">
                                    <input type="text" <?= $readonly;?> class="form-control" name="edit_title" id="edit_title" requiredXY value="<?php echo set_value('edit_title',$data['title']) ?>" placeholder="<?=$this->lang->line('application_enter_event_name')?>" autocomplete="off">
                                    <?php echo '<i style="color:red">'.form_error('edit_title').'</i>'; ?>
                                </div>
                                </div>
                                <div class="form-group  <?php echo (form_error('edit_event_type')) ? 'has-error' : '';  ?>">
                                    <label for="event_type" class="col-md-2 label-heading"><?=$this->lang->line('application_type')?></label>
                                    <div class="col-md-4 input-group  ui-front">
                                    
                                        <select class="selectpicker form-control select_group" name="edit_event_type" id="edit_event_type">
                                            <optgroup label="<?=$this->lang->line('application_specific')?>">
                                                <option value="71" <?=$optionRO;?> <?= set_select('edit_event_type',71, $data['event_type'] == 71)?> ><?=$this->lang->line('application_daily')?></option>
                                                <option value="72" <?=$optionRO;?> <?= set_select('edit_event_type',72, $data['event_type'] == 72)?> > <?=$this->lang->line('application_weekly')?></option>
                                                <option value="73" <?=$optionRO;?> <?= set_select('edit_event_type',73, $data['event_type'] == 73)?> ><?=$this->lang->line('application_monthly')?></option>
                                                <option value="74" <?=$optionRO;?> <?= set_select('edit_event_type',74, $data['event_type'] == 74)?> ><?=$this->lang->line('application_annually')?></option>
                                            </optgroup>
                                            <optgroup label="<?=$this->lang->line('application_timed')?>">
                                                <option value="5" <?=$optionRO;?> <?= set_select('edit_event_type', 5, $data['event_type'] == 5)?> >5 Min</option>
                                                <option value="10" <?=$optionRO;?> <?= set_select('edit_event_type', 10, $data['event_type'] == 10)?> >10 Min</option>
                                                <option value="15" <?=$optionRO;?> <?= set_select('edit_event_type', 15, $data['event_type'] == 15)?> >15 Min</option>
                                                <option value="20  <?=$optionRO;?> <?= set_select('edit_event_type', 20, $data['event_type'] == 20)?>">20 Min</option>
                                                <option value="30" <?=$optionRO;?> <?= set_select('edit_event_type', 30, $data['event_type'] == 30)?> >30 Min</option>
                                                <option value="45" <?=$optionRO;?> <?= set_select('edit_event_type', 45, $data['event_type'] == 45)?> >45 Min</option>
                                                <option value="60" <?=$optionRO;?> <?= set_select('edit_event_type', 60, $data['event_type'] == 60)?> >60 Min</option>
                                                <option value="120" <?=$optionRO;?> <?= set_select('edit_event_type', 120, $data['event_type'] == 120)?> >2 Horas</option>
                                                <option value="240" <?=$optionRO;?> <?= set_select('edit_event_type', 240, $data['event_type'] == 240)?> >4 Horas</option>
                                                <option value="480" <?=$optionRO;?> <?= set_select('edit_event_type', 480, $data['event_type'] == 480)?> >8 Horas</option>
                                            </optgroup>
                                        </select>
                                    <?php echo '<i style="color:red">'.form_error('edit_event_type').'</i>'; ?>
                                </div>
                            </div>
                            <div class="form-group <?php echo (form_error('edit_module_path')) ? 'has-error' : '';  ?>">
                                <label for="ev_module" class="col-md-2 label-heading"><?=$this->lang->line('application_module')?></label>
                                <div class="col-md-6 input-group ui-front">
                                    <input type="text" <?= $readonly;?> class="form-control" name="edit_module_path" requiredXY value="<?php echo set_value('edit_module_path',$data['module_path']) ?>" id="edit_module_path" placeholder="<?=$this->lang->line('application_enter_module')?>" autocomplete="off">
                                    <?php echo '<i style="color:red">'.form_error('edit_module_path').'</i>'; ?>
                                </div>
                            </div>
                            <div class="form-group <?php echo (form_error('edit_module_method')) ? 'has-error' : '';  ?>">
                                <label for="ev_method"  class="col-md-2 label-heading"><?=$this->lang->line('application_method')?></label>
                                <div class="col-md-6 input-group ui-front ">
                                    <input type="text" <?= $readonly;?> class="form-control" name="edit_module_method" requiredXY value="<?php echo set_value('edit_module_method',$data['module_method']) ?>" id="edit_module_method" placeholder="<?=$this->lang->line('application_enter_method')?>" autocomplete="off">
                                    <?php echo '<i style="color:red">'.form_error('edit_module_method').'</i>'; ?>
                                </div>
                            </div>
                            <div class="form-group <?php echo (form_error('edit_params')) ? 'has-error' : '';  ?>">
                                <label for="ev_params" class="col-md-2 label-heading"><?=$this->lang->line('application_params')?></label>
                                <div class="col-md-6 input-group  ui-front ">
                                    <input type="text" <?= $readonly;?> class="form-control" name="edit_params" requiredXY value="<?php echo set_value('edit_params',$data['params']) ?>" id="edit_params" placeholder="<?=$this->lang->line('application_enter_params')?>" autocomplete="off">
                                    <?php echo '<i style="color:red">'.form_error('edit_params').'</i>'; ?>
                                </div>
                            </div>
                
                            <div class="form-group  <?php echo (form_error('edit_start')) ? 'has-error' : '';  ?>">
                                <label for="start_date" class="col-md-2 label-heading"><?=$this->lang->line('application_start_date')?></label>
                                <div class='col-md-2 input-group'>
                                    <div class='input-group date'>
                                        <input type='text' <?= $readonly;?> class="form-control" name="edit_start" id="edit_start" value="<?php echo set_value('edit_start',$data['start']) ?>" />
                                        <span class="input-group-addon">
                                            <span class="glyphicon glyphicon-calendar"></span>
                                        </span>
                                    </div>
                                    <?php echo '<i style="color:red">'.form_error('edit_start').'</i>'; ?>
                                </div>
                            </div>
                            <div class="form-group <?php echo (form_error('edit_end')) ? 'has-error' : '';  ?>"> 
                                <label for="end_date" class="col-md-2 label-heading"><?=$this->lang->line('application_end_date')?></label>
                                <div class='col-md-2 input-group'>
                                    <div class='input-group date'>
                                        <input type='text' <?= $readonly;?> class="form-control" name="edit_end" id="edit_end" value="<?php echo set_value('edit_end',$data['end']) ?>" />
                                        <span class="input-group-addon">
                                            <span class="glyphicon glyphicon-calendar"></span>
                                        </span>    
                                    </div>
                                    <?php echo '<i style="color:red">'.form_error('edit_end').'</i>'; ?>
                                </div>                                
                            </div>

                            <div class="form-group <?php echo (form_error('edit_alert_after')) ? 'has-error' : '';  ?>">
                                <label for="edit_alert_after" class="col-md-2 label-heading"><?=$this->lang->line('application_alert_after')?></label>
                                <div class="col-md-2 input-group  ui-front ">
                                    <input type="number" min="5" max="1440" <?= $readonly;?> class="form-control" name="edit_alert_after" value="<?php echo set_value('edit_alert_after',$data['alert_after']) ?>" id="edit_alert_after" placeholder="<?=$this->lang->line('application_alert_after')?>" autocomplete="off" required>
                                    <span class="input-group-addon"><?=$this->lang->line('application_minutes')?></span>
                                    <?php echo '<i style="color:red">'.form_error('edit_alert_after').'</i>'; ?>
                                </div>
                            </div>

                            <?php if ($function == 'delete') { ?>
                                <h2><?=$this->lang->line('messages_delete_message_confirm');?></h2>
                            <?php } ?>
                        </div>
        
                        <div class="box-footer">
                            <?php if ($function == 'delete') {  ?>
                                <input type="submit" class="btn btn-primary" name="confirm" value="<?=$this->lang->line('application_confirm');?>">
                                <a href="<?php echo base_url('calendar') ?>" class="btn btn-warning"><?=$this->lang->line('application_cancel');?></a>
                            <?php } elseif ($function == 'view') { ?>
                                <a href="<?php echo base_url('calendar/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                            <?php } else { ?>
                                <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                                <a href="<?php echo base_url('calendar/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                            <?php } ?>
                           
                        </div>
        
                    </form>
                </div>
                <!-- /.box -->
            </div>
        </div>
      </section>
      <!-- /.content -->
    </div>

<script type="text/javascript">
    $(function () {
    	$('#edit_start').datetimepicker({format: 'YYYY/MM/DD HH:mm' });
        $('#edit_end').datetimepicker({
	        format: 'YYYY/MM/DD HH:mm',
            useCurrent: false //Important! See issue #1075
        });
        $("#edit_start").on("dp.change", function (e) {
            $('#edit_end').data("DateTimePicker").minDate(e.date);
        });
        $("#edit_end").on("dp.change", function (e) {
            $('#edit_start').data("DateTimePicker").maxDate(e.date);
        });
    	
        $('#edit_start').datetimepicker({format: 'YYYY/MM/DD HH:mm' });
        $('#edit_end').datetimepicker({
	        format: 'YYYY/MM/DD HH:mm',
            useCurrent: false //Important! See issue #1075
        });
        $("#edit_start").on("dp.change", function (e) {
            $('#edit_end').data("DateTimePicker").minDate(e.date);
        });
        $("#edit_end").on("dp.change", function (e) {
            $('#edit_start').data("DateTimePicker").maxDate(e.date);
        });
    });
</script>

<script type="text/javascript">
  $(document).ready(function() {
    $("#mainCalendarNav").addClass('active');
    $("#manageCalendarNav").addClass('active');

  });
</script>