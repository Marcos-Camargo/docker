<!--
SW Serviços de Informática 2019

Agendador 
Add, Edit & delete

-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
	    <div class="row">
			<div class="col-md-12 col-xs-12">
				<div class="box">
					<div class="box-body">
						<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addModal"><?=$this->lang->line('application_add_event');?></button>  
				    <h1><?=$this->lang->line('application_scheduled');?></h1>
					<div id="calendar">
					</div>
					</div>
				</div>
		    </div>
	    </div>
		<div class="row">
			<div class="col-md-2">
			<a href="<?php echo base_url('calendar/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>  
			</div>
		</div>
		<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title" id="myModalLabel"><?=$this->lang->line('application_add_calendar_event')?></h4>
		      </div>
		      <div class="modal-body">
		        <?php echo form_open(site_url("calendar/add_event"), array("class" => "form-horizontal")) ?>
		        <div class="form-group">
                    <label for="name" class="col-md-4 label-heading"><?=$this->lang->line('application_event_name')?></label>
                    <div class="col-md-8 ui-front">
                        <input type="text" class="form-control" name="name" id="name" required placeholder="<?=$this->lang->line('application_enter_event_name')?>" value="" autocomplete="off">
                    </div>
		        </div>
		        <div class="form-group">
		                <label for="event_type" class="col-md-4 label-heading"><?=$this->lang->line('application_type')?></label>
		                <div class="col-md-8 ui-front">
							<select class="selectpicker form-control select_group" name="event_type">
                                <optgroup label="<?=$this->lang->line('application_specific')?>">
                                    <option value="71"><?=$this->lang->line('application_daily')?></option>
                                    <option value="72"><?=$this->lang->line('application_weekly')?></option>
                                    <option value="73"><?=$this->lang->line('application_monthly')?></option>
                                    <option value="74"><?=$this->lang->line('application_annually')?></option>
                                </optgroup>
                                <optgroup label="<?=$this->lang->line('application_timed')?>">
                                    <option value="1">1 Min</option>
                                    <option value="5">5 Min</option>
                                    <option value="10">10 Min</option>
                                    <option value="15">15 Min</option>
                                    <option value="20">20 Min</option>
                                    <option value="30">30 Min</option>
                                    <option value="60">60 Min</option>
                                    <option value="120">2 Horas</option>
                                    <option value="240">4 Horas</option>
                                    <option value="480">8 Horas</option>
                                </optgroup>
							</select>
		                </div>
		        </div>
		        <div class="form-group">
		                <label for="module" class="col-md-4 label-heading"><?=$this->lang->line('application_module')?></label>
		                <div class="col-md-8 ui-front">
		                    <input type="text" class="form-control" name="module" id="module" required placeholder="<?=$this->lang->line('application_enter_module')?>" autocomplete="off">
		                </div>
		        </div>
		        <div class="form-group">
		                <label for="method" class="col-md-4 label-heading"><?=$this->lang->line('application_method')?></label>
		                <div class="col-md-8 ui-front">
		                    <input type="text" class="form-control" name="method" id="method" required placeholder="<?=$this->lang->line('application_enter_method')?>" autocomplete="off">
		                </div>
		        </div>
		        <div class="form-group">
		                <label for="params" class="col-md-4 label-heading"><?=$this->lang->line('application_params')?></label>
		                <div class="col-md-8 ui-front">
		                    <input type="text" class="form-control" name="params" id="params" required placeholder="<?=$this->lang->line('application_enter_params')?>" autocomplete="off">
		                </div>
		        </div>
		        <div class="form-group">
	                <label for="start_date" class="col-md-4 label-heading"><?=$this->lang->line('application_start_date')?></label>
		            <div class='col-md-5 input-group date'>
		                <input type='text' class="form-control" name="start_date" id="start_date_ini"  />
		                <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
		            </div>
		        </div>
		        <div class="form-group">
	                <label for="end_date" class="col-md-4 label-heading"><?=$this->lang->line('application_end_date')?></label>
		            <div class='col-md-5 input-group date'>
		                <input type='text' class="form-control" name="end_date" id="end_date_ini"  />
		                <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
		            </div>
		        </div>
		        

		      <div class="modal-footer">
		        <button type="button" class="btn btn-warning" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
		        <input type="submit" class="btn btn-primary" value="<?=$this->lang->line('application_add_event')?>">
		        <?php echo form_close() ?>
		      </div>
		    </div>
		  </div>
          </div>
        </div>

		<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		        <h4 class="modal-title" id="myModalLabel"><?=$this->lang->line('application_update_calendar_event')?></h4>
		      </div>
		      <div class="modal-body">
		      <?php echo form_open(site_url("calendar/edit_event"), array("class" => "form-horizontal")) ?>
		      <div class="form-group">
                    <label for="ev_name" class="col-md-4 label-heading"><?=$this->lang->line('application_event_name')?></label>
                    <div class="col-md-8 ui-front">
                        <input type="text" class="form-control" name="ev_name" id="ev_name" required value="" placeholder="<?=$this->lang->line('application_enter_event_name')?>" autocomplete="off">
                    </div>
		        </div>
		        <div class="form-group">
		                <label for="event_type" class="col-md-4 label-heading"><?=$this->lang->line('application_type')?></label>
		                <div class="col-md-8 ui-front">
							<select class="selectpicker form-control select_group" name="event_type" id="event_type">
                                <optgroup label="<?=$this->lang->line('application_specific')?>">
                                    <option value="71"><?=$this->lang->line('application_daily')?></option>
                                    <option value="72"><?=$this->lang->line('application_weekly')?></option>
                                    <option value="73"><?=$this->lang->line('application_monthly')?></option>
                                    <option value="74"><?=$this->lang->line('application_annually')?></option>
                                </optgroup>
                                <optgroup label="<?=$this->lang->line('application_timed')?>">
                               <!--     <option value="1">1 Min</option> -->
                                    <option value="5">5 Min</option>
                                    <option value="10">10 Min</option>
                                    <option value="15">15 Min</option>
                                    <option value="20">20 Min</option>
                                    <option value="30">30 Min</option>
                                    <option value="45">45 Min</option>
                                    <option value="60">60 Min</option>
                                    <option value="120">2 Horas</option>
                                    <option value="240">4 Horas</option>
                                    <option value="480">8 Horas</option>
                                </optgroup>
							</select>
		                </div>
		        </div>
		        <div class="form-group">
		                <label for="ev_module" class="col-md-4 label-heading"><?=$this->lang->line('application_module')?></label>
		                <div class="col-md-8 ui-front">
		                    <input type="text" class="form-control" name="ev_module" required value="" id="ev_module" placeholder="<?=$this->lang->line('application_enter_module')?>" autocomplete="off">
		                </div>
		        </div>
		        <div class="form-group">
		                <label for="ev_method" class="col-md-4 label-heading"><?=$this->lang->line('application_method')?></label>
		                <div class="col-md-8 ui-front">
		                    <input type="text" class="form-control" name="ev_method" required value="" id="ev_method" placeholder="<?=$this->lang->line('application_enter_method')?>" autocomplete="off">
		                </div>
		        </div>
		        <div class="form-group">
		                <label for="ev_params" class="col-md-4 label-heading"><?=$this->lang->line('application_params')?></label>
		                <div class="col-md-8 ui-front">
		                    <input type="text" class="form-control" name="ev_params" required value="" id="ev_params" placeholder="<?=$this->lang->line('application_enter_params')?>" autocomplete="off">
		                </div>
		        </div>
		        <div class="form-group">
	                <label for="start_date" class="col-md-4 label-heading"><?=$this->lang->line('application_start_date')?></label>
		            <div class='col-md-5 input-group date'>
		                <input type='text' class="form-control" name="start_date" id="start_date" />
		                <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
		            </div>
		        </div>
		        <div class="form-group">
	                <label for="end_date" class="col-md-4 label-heading"><?=$this->lang->line('application_end_date')?></label>
		            <div class='col-md-5 input-group date'>
		                <input type='text' class="form-control" name="end_date" id="end_date" />
		                <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
		            </div>
		        </div>
		        

		        <div class="form-group">
                    <label for="delete" class="col-md-4 label-heading"><?=$this->lang->line('application_delete_event')?></label>
                    <div class="col-md-8">
                        <input type="checkbox" name="delete" class="minimal" value="1">
                    </div>
                </div>
                <input type="hidden" name="eventid" id="event_id" value="0" />
		      </div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-warning" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
		        <input type="submit" class="btn btn-primary" value="<?=$this->lang->line('application_update_event')?>">
		        <?php echo form_close() ?>
		      </div>
		    </div>
		  </div>
		</div>

  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<script type="text/javascript">
    $(function () {
    	$('#start_date_ini').datetimepicker({format: 'YYYY/MM/DD HH:mm' });
        $('#end_date_ini').datetimepicker({
	        format: 'YYYY/MM/DD HH:mm',
            useCurrent: false //Important! See issue #1075
        });
        $("#start_date_ini").on("dp.change", function (e) {
            $('#end_date_ini').data("DateTimePicker").minDate(e.date);
        });
        $("#end_date_ini").on("dp.change", function (e) {
            $('#start_date_ini').data("DateTimePicker").maxDate(e.date);
        });
    	
    	
        $('#start_date').datetimepicker({format: 'YYYY/MM/DD HH:mm' });
        $('#end_date').datetimepicker({
	        format: 'YYYY/MM/DD HH:mm',
            useCurrent: false //Important! See issue #1075
        });
        $("#start_date").on("dp.change", function (e) {
            $('#end_date').data("DateTimePicker").minDate(e.date);
        });
        $("#end_date").on("dp.change", function (e) {
            $('#start_date').data("DateTimePicker").maxDate(e.date);
        });
    });
</script>

<script type="text/javascript">
$(document).ready(function() {
  $("#mainIntegrationNav").addClass('active');
  $("#loadIntegrationNav").addClass('active');
    var date_last_clicked = null;

    $('#calendar').fullCalendar({
    	    	 plugins: [ 'list' ],
			  		defaultView: 'listDay',
        eventSources: [
           {
           events: function(start, end, timezone, callback) {
                $.ajax({
                    url: '<?php echo base_url() ?>calendar/get_events',
                    dataType: 'json',
                    data: {
                        // our hypothetical feed requires UNIX timestamps
                        start: start.unix(),
                        end: end.unix()
                    },
                    success: function(msg) {
                        var events = msg.events;
                        callback(events);
                    }
                });
              }
            },
        ],
        dayClick: function(date, jsEvent, view) {
            date_last_clicked = $(this);
            $(this).css('background-color', '#bed7f3');
            $('#addModal').modal();
        },
       eventClick: function(event, jsEvent, view) {
          $('#ev_name').val(event.title_orig);
          $("#event_type").val(event.event_type).change();

          $('#start_date').val(moment(event.start).format('YYYY/MM/DD HH:mm'));
          if(event.end) {
            $('#end_date').val(moment(event.end).format('YYYY/MM/DD HH:mm'));
          } else {
            $('#end_date').val(moment(event.start).format('YYYY/MM/DD HH:mm'));
          }
          $('#ev_module').val(event.module);
          $('#ev_method').val(event.method);
          $('#ev_params').val(event.params);
          $('#event_id').val(event.id);
          $('#editModal').modal();
       },
    });
    // $('.datepicker-days').css('display', 'none');
    $('input[type="checkbox"].minimal').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass   : 'iradio_minimal-blue'
    });
});
</script>