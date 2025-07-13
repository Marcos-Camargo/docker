<!--

-->

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

		    <div class="">
            <div class="col-md-3">
              <label for="buscamarketplace" class="normal"><?=$this->lang->line('application_marketplace')?></label>
              <select class="form-control" id="buscamarketplace" onchange="buscaStore()">                
                  <?php
                  foreach ($marketplaces as $mkt) {
                      echo "<option value='{$mkt['int_to']}'>{$mkt['name']}</option>";
                  }
                  ?>
              </select>
            </div>
        
            <div class="col-md-3">
	            <label for="buscaempresa" class="normal"><?=$this->lang->line('application_company');?></label>
	            <div class="input-group">
	              <input type="search" id="buscaempresa" onchange="buscaStore()" class="form-control" placeholder="<?=$this->lang->line('application_company');?>" aria-label="Search" aria-describedby="basic-addon1">
	              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
	            </div>
	          </div>

	          <div class="col-md-3">
	            <label for="buscaloja" class="normal"><?=$this->lang->line('application_store');?></label>
	            <div class="input-group">
	              <input type="search" id="buscaloja" onchange="buscaStore()" class="form-control" placeholder="<?=$this->lang->line('application_store');?>" aria-label="Search" aria-describedby="basic-addon1">
	              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
	            </div>
	          </div>

            <div class="col-md-2">
	            <div class="input-group" >
	              <label for="buscaautoapprove" class="normal"><?=$this->lang->line('application_curatorship');?></label>
	              <select class="form-control" id="buscaautoapprove" onchange="buscaStore()">
                  <option value="" selected><?=$this->lang->line('application_all');?></option>
                  <option value="1"><?=$this->lang->line('application_approve');?></option>
	                <option value="2"><?=$this->lang->line('application_curatorship');?></option>
	              </select>
	            </div>
	          </div>

            <div class="col-md-2">
	            <div class="input-group" >
	              <label for="buscasellerindex" class="normal"><?=$this->lang->line('application_seller_index');?></label>
	              <select class="form-control" id="buscasellerindex" onchange="buscaStore()">
	                <option value="" selected><?=$this->lang->line('application_all');?></option>
	                <option value="1">1</option>
	                <option value="2">2</option>
                  <option value="3">3</option>
                  <option value="4">4</option>
                  <option value="5">5</option>
	              </select>
	            </div>
	          </div>

            <div class="col-md-2">
	            <div class="input-group" >
	              <label for="buscastatus" class="normal"><?=$this->lang->line('application_status');?></label>
	              <select class="form-control" id="buscastatus" onchange="buscaStore()">
                  <option value="" selected><?=$this->lang->line('application_all');?></option>
                  <option value="1"><?=$this->lang->line('application_active');?></option>
	                <option value="2"><?=$this->lang->line('application_inactive');?></option>
	              </select>
	            </div>
	          </div>
	
	          <div class="pull-right">
				      <label  class="normal" style="display: block;">&nbsp; </label>
	       		  <button type="button" onclick="clearFilters()" class="btn btn-primary"> <i class="fa fa-eraser"></i> <?=$this->lang->line('application_clear');?> </button>
	          </div>
        	</div>
          <div class="row"></div>
         
          <div class="row">
            <div class="col-md-12 col-xs-12">
                <h4><strong><?=$this->lang->line('application_stores_without_integration');?></strong></h3>
            </div>
          </div>

          <div class="box">
            <div class="box-body">
              <table id="manageTableNotIntegrated" aria-label="StoresNotIntegrated" class="table table-bordered table-striped" style="border-collapse: collapse; width: 100%; border-spacing: 0;">
               <thead>
                <tr>
                  <th><input type="checkbox" name="select_all_no_intto" value="1" id="manageTable-select-all-no-intto"></th>  
                  <th><?=$this->lang->line('application_company');?></th>
                  <th><?=$this->lang->line('application_store');?></th>
                  <th><?=$this->lang->line('application_seller_index');?></th>
                  <th><?=$this->lang->line('application_service_charge_amount');?></th>
                  <th><?=$this->lang->line('application_charge_amount_freight');?></th>
                  <th><?=$this->lang->line('application_marketplace');?></th>
                  <th style="width:110px"><?=$this->lang->line('application_action');?></th>
                </tr>
                </thead>
    
              </table>

              <div class="col-md-12">
                <div class="container-fluid">
                  <div class="col-md-4">  
                    <button  onclick="createIntegrations(event)" class="btn btn-success" id="cr_int" name="cr_int"><?=$this->lang->line('application_create_integration_for_selecteds_stores');?></button>
                  </div>
                  <div class="col-md-4">  
                    <button  onclick="createIntegrationsFiltereds(event)" class="btn btn-success" id="cr_int_filtered" name="cr_int_filtered"><?=$this->lang->line('application_create_integration_for_filtered_stores');?></button>
                  </div>	
                </div>
              </div>
            </div>
            <!-- /.box-body -->
          </div>

          <div class="row">
            <div class="col-md-12 col-xs-12">
                <h4><strong><?=$this->lang->line('application_current_integrations');?></strong></h4>
            </div>
          </div>

          <div class="box">
            <div class="box-body">
              <table id="manageTableIntegrated" aria-label="StoresIntegrated" class="table table-bordered table-striped" style="border-collapse: collapse; width: 100%; border-spacing: 0;">
               <thead>
                <tr>
                  <th><input type="checkbox" name="select_all_intto" value="1" id="manageTable-select-all-intto"></th>  
                  <th><?=$this->lang->line('application_marketplace');?></th>
                  <th><?=$this->lang->line('application_company');?></th>
                  <th><?=$this->lang->line('application_store');?></th>
                  <th><?=$this->lang->line('application_seller_index');?></th>
                  <th><?=$this->lang->line('application_service_charge_amount');?></th>
                  <th><?=$this->lang->line('application_charge_amount_freight');?></th>
                  <th><?=$this->lang->line('application_status');?></th>
                  <th><?=$this->lang->line('application_curatorship');?></th>
                  <th style="width:110px"><?=$this->lang->line('application_action');?></th>
                </tr>
                </thead>

              </table>
              <div class="col-md-12">
                  <div class="container-fluid">
                    <div class="col-md-4">  
                      <button  onclick="editIntegrations(event)" class="btn btn-success" id="ed_int" name="ed_int"><?=$this->lang->line('application_edit_integration_for_selecteds_stores');?></button>
                    </div>
                    <div class="col-md-4">  
                      <button  onclick="editIntegrationsFiltereds(event)" class="btn btn-success" id="ed_int_filtered" name="ed_int_filtered"><?=$this->lang->line('application_edit_integration_for_filtered_stores');?></button>
                    </div>	
                  </div>
                </div>
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

  <div class="modal fade" tabindex="-1" role="dialog" id="createIntegrations">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span><?=$this->lang->line('application_create_integration');?></span></h4>
            </div>
            <form role="form" aria-label="createIntegrations" action="<?php echo base_url('IntegrationsConfiguration/tocreateSeveral') ?>" method="post" id="createIntegrationsForm">
	            <div class="modal-body">
					      <div class="row">
						      <div class="col-md-12">
	                	<p><strong><?=$this->lang->line('application_integration_options');?></strong></p>
	                </div>
	              </div>  
					      <div class="row">
						      <div class="col-md-12">
                    <span class=""><?=$this->lang->line('application_automatically_approve_products');?>?</span>
                    <input type="checkbox" checked class="auto_approve_several"  id="auto_approve_several" name="auto_approve_several">
                    <strong><?=$this->lang->line('application_yes');?></strong>
                    <p></p>
                    <p><em><small><span style="color:green">
                    <?=$this->lang->line('application_about_activate_integration');?>
                    </span></em></small></p>
                  </div>
	              </div>				
					      <input type="hidden" name="id_integrate_several"  id="id_integrate_several" value="" autocomplete="off">
                <input type="hidden" name="filter_stores"  id="filter_stores" value="" autocomplete="off">
                <input type="hidden" name="filter_company"  id="filter_company" value="" autocomplete="off">
                <input type="hidden" name="filter_seller_index"  id="filter_seller_index" value="" autocomplete="off">
                <input type="hidden" name="filter_marketplace"  id="filter_marketplace" value="" autocomplete="off">

              </div>
	            
	            <div class="modal-footer">
			    	    <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
			      	  <button type="submit" class="btn btn-primary" id="StoresSubmitSeveral" name="StoresSubmitSeveral"><?=$this->lang->line('application_confirm');?></button>
              </div>
		        </form>
        </div>
    </div>
  </div>

  <div class="modal fade" tabindex="-1" role="dialog" id="createStoreIntegration">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span><?=$this->lang->line('application_create_integration');?></span></h4>
            </div>
            <form role="form" aria-label="createStoreIntegration" action="<?php echo base_url('IntegrationsConfiguration/toCreate') ?>" method="post" id="createStoreIntegrationsForm">
	            <div class="modal-body">
	                <div class="row">
	                    <div class="col-md-6">
	                        <div class="form-group">
	                            <label><?=$this->lang->line('application_company');?></label>
	                            <input type="text" class="form-control" name="create_company_integration" id="create_company_integration" value="" readonly>
	                        </div>
	                    </div>
	                    <div class="col-md-6">
	                        <div class="form-group">
	                            <label><?=$this->lang->line('application_store');?></label>
	                            <input type="text" class="form-control" name="create_store_integration" id="create_store_integration" value="" readonly>
	                        </div>
	                    </div>
                      <div class="col-md-3">
	                        <div class="form-group">
	                            <label><?=$this->lang->line('application_marketplace');?></label>
	                            <input type="text" class="form-control" name="create_int_to_integration" id="create_int_to_integration" value="" readonly>                  
                          </div>
	                    </div>
	                </div>
	                
	                <div class="row">
	                	<div class="col-md-10">
	                	<p><strong><?=$this->lang->line('application_integration_options');?></strong></p>
	                	</div>
	                </div>

                  <div class="row">
                    <div class="col-md-12">
                      <span class=""><?=$this->lang->line('application_automatically_approve_products');?>?</span>
                      <input type="checkbox" checked  id="create_auto_approve_integration" name="create_auto_approve_integration">
                      <strong><?=$this->lang->line('application_yes');?></strong>
                      <p></p>
                      <p><em><small><span style="color:green">
                      <?=$this->lang->line('application_about_activate_integration');?>
                      </span></em></small></p>
                    </div>
	                </div>	
				
					        <input type="hidden" name="create_store_id_integration"  id="create_store_id_integration" value="" autocomplete="off">
	            </div>
	            
	            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
                <button type="submit" class="btn btn-primary" id="createIntegrationSubmit" name="createIntegrationSubmit"><?=$this->lang->line('application_confirm');?></button>
              </div>
            </div>
		      </form>	
        </div>
    </div>
  </div>

  <div class="modal fade" tabindex="-1" role="dialog" id="editIntegrations">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span><?=$this->lang->line('application_edit_integration');?></span></h4>
            </div>
            <form role="form" aria-label="editIntegrations" action="<?php echo base_url('IntegrationsConfiguration/toEditSeveral') ?>" method="post" id="editIntegrationsForm">
	            <div class="modal-body">
					      <div class="row">
						      <div class="col-md-12">
	                	<p><strong><?=$this->lang->line('application_integration_options');?></strong></p>
	                </div>
	              </div>  
					      <div class="row">
						      <div class="col-md-6">
                    <span class=""><?=$this->lang->line('application_automatically_approve_products');?>?</span>
                    <input type="checkbox" checked id="int_auto_approve_several" name="int_auto_approve_several">
                    <strong><?=$this->lang->line('application_yes');?></strong>
                    <p></p>
                    <p><em><small><span style="color:green">
                    <?=$this->lang->line('application_about_approve_products');?>
                    </span></em></small></p>
                  </div>
                  <div class="col-md-6">
                    <span class=""><?=$this->lang->line('application_upload_integrations');?></span>
                    <input type="checkbox" checked id="int_active_several" name="int_active_several">
                    <strong><?=$this->lang->line('application_yes');?></strong>
                    <p></p>
                    <p><em><small><span style="color:green">
                    <?=$this->lang->line('application_about_activate_integration');?>
                    </span></em></small></p>
					        </div>
	              </div>				
					      <input type="hidden" name="int_id_integrate_several"  id="int_id_integrate_several" value="" autocomplete="off">	
                <input type="hidden" name="int_filter_stores"  id="int_filter_stores" value="" autocomplete="off">
                <input type="hidden" name="int_filter_company"  id="int_filter_company" value="" autocomplete="off">
                <input type="hidden" name="int_filter_status"  id="int_filter_status" value="" autocomplete="off">
                <input type="hidden" name="int_filter_marketplace"  id="int_filter_marketplace" value="" autocomplete="off">
                <input type="hidden" name="int_filter_autoapprove"  id="int_filter_autoapprove" value="" autocomplete="off">
                <input type="hidden" name="int_filter_seller_index"  id="int_filter_seller_index" value="" autocomplete="off">

              </div>
	            
	            <div class="modal-footer">
			    	    <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
			      	  <button type="submit" class="btn btn-primary" id="intSubmitSeveral" name="intSubmitSeveral"><?=$this->lang->line('application_confirm');?></button>
              </div>
		        </form>
        </div>
    </div>
  </div>

  <div class="modal fade" tabindex="-1" role="dialog" id="editStoreIntegration">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span><?=$this->lang->line('application_edit_integration');?></span></h4>
            </div>
            <form role="form" aria-label="editStoreIntegration" action="<?php echo base_url('IntegrationsConfiguration/toEdit') ?>" method="post" id="editStoreIntegrationsForm">
	            <div class="modal-body">
	                <div class="row">
	                    <div class="col-md-6">
	                        <div class="form-group">
	                            <label><?=$this->lang->line('application_company');?></label>
	                            <input type="text" class="form-control" name="edit_company_integration" id="edit_company_integration" value="" readonly>
	                        </div>
	                    </div>
	                    <div class="col-md-6">
	                        <div class="form-group">
	                            <label><?=$this->lang->line('application_store');?></label>
	                            <input type="text" class="form-control" name="edit_store_integration" id="edit_store_integration" value="" readonly>
	                        </div>
	                    </div>
                      <div class="col-md-3">
	                        <div class="form-group">
	                            <label><?=$this->lang->line('application_marketplace');?></label>
	                            <input type="text" class="form-control" name="edit_int_to_integration" id="edit_int_to_integration" value="" readonly>
	                        </div>
	                    </div>
	                </div>
	                
	                <div class="row">
	                	<div class="col-md-12">
	                	<p><strong><?=$this->lang->line('application_integration_options');?></strong></p>
	                	</div>
	                </div>

                  <div class="row">
                    <div class="col-md-6">
                      <span class=""><?=$this->lang->line('application_automatically_approve_products');?>?</span>
                      <input type="checkbox" id="edit_auto_approve_integration" name="edit_auto_approve_integration">
                      <strong><?=$this->lang->line('application_yes');?></strong>
                      <p></p>
                      <p><em><small><span style="color:green">
                      <?=$this->lang->line('application_about_approve_products');?>
                      </span></em></small></p>
                    </div>

                    <div class="col-md-6">
                      <span class=""><?=$this->lang->line('application_activate_integration');?>?</span>
                      <input type="checkbox" id="edit_active_integration" name="edit_active_integration" > 
                      <strong><?=$this->lang->line('application_yes');?></strong>
                      <p></p>
                      <p><em><small><span style="color:green">
                      <?=$this->lang->line('application_about_activate_integration');?>
                      </span></em></small></p>
                    </div>
	                </div>	
				
					        <input type="hidden" name="edit_id_integration"  id="edit_id_integration" value="" autocomplete="off">
	            </div>
	            
	            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
                <button type="submit" class="btn btn-primary" id="createIntegrationSubmit" name="createIntegrationSubmit"><?=$this->lang->line('application_confirm');?></button>
              </div>
            </div>
		      </form>	
        </div>
    </div>
  </div>

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
var manageTableIntegrated;
var manageTableNotIntegrated;
var base_url = "<?php echo base_url(); ?>";

// para csrf 
var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
    
$(document).ready(function() {

      buscaStore()

      // Handle click on "Select all" control
      $('#manageTable-select-all-intto').on('click', function(){
          var rows = manageTableIntegrated.rows({ 'search': 'applied' }).nodes();
          $('input[type="checkbox"]', rows).prop('checked', this.checked);
      });
      
       // Handle click on checkbox to set state of "Select all" control
      $('#manageTableIntegrated tbody').on('change', 'input[type="checkbox"]', function(){
          // If checkbox is not checked
          if(!this.checked){
            var el = $('#manageTable-select-all-intto').get(0);
            // If "Select all" control is checked and has 'indeterminate' property
            if(el && el.checked && ('indeterminate' in el)){
                // Set visual state of "Select all" control
                // as 'indeterminate'
                el.indeterminate = true;
            }
          }
      });

      $('#manageTable-select-all-no-intto').on('click', function(){
          var rows = manageTableNotIntegrated.rows({ 'search': 'applied' }).nodes();
          $('input[type="checkbox"]', rows).prop('checked', this.checked);
      });
       // Handle click on checkbox to set state of "Select all" control
      $('#manageTableNotIntegrated tbody').on('change', 'input[type="checkbox"]', function(){
          // If checkbox is not checked
          if(!this.checked){
            var el = $('#manageTable-select-all-no-intto').get(0);
            // If "Select all" control is checked and has 'indeterminate' property
            if(el && el.checked && ('indeterminate' in el)){
                // Set visual state of "Select all" control
                // as 'indeterminate'
                el.indeterminate = true;
            }
          }
      });

});
 
function buscaStore(){
  let loja = $('#buscaloja').val();
  let empresa = $('#buscaempresa').val();
  let marketplace = $('#buscamarketplace').val();
  let status = $('#buscastatus').val();
  let autoapprove = $('#buscaautoapprove').val();
  let seller_index = $('#buscasellerindex').val();

  
  if (typeof manageTableIntegrated === 'object' && manageTableIntegrated !== null) {
  	manageTableIntegrated.destroy();
  }

  if (typeof manageTableNotIntegrated === 'object' && manageTableNotIntegrated !== null) {
  	manageTableNotIntegrated.destroy();
  }

  manageTableIntegrated = $('#manageTableIntegrated').DataTable({
    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
    "processing": true,
    "serverSide": true,
    "scrollX": true,
    "sortable": true,
    "searching": true,
    "serverMethod": "post",
    "selected": undefined, 
    'columnDefs': [{
           'targets': 0,
           'searchable': false,
           'orderable': false,
           'className': 'dt-body-center',
           'render': function (data, type, full, meta){
               return '<input type="checkbox" class="inttoselect" name="id[]" value="' + $('<div/>').text(data).html() + '">';
            }
        }],
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'IntegrationsConfiguration/fetchIntegrationData',
      data: { [csrfName]: csrfHash, loja: loja, empresa: empresa, marketplace: marketplace, seller_index: seller_index, autoapprove: autoapprove, status: status},
      pages: 2
    }), 
    infoCallback: function(settings, start, end, max, total, pre) {
      $('#ed_int').prop('disabled', false);
      $('#ed_int_filtered').prop('disabled', false);
      if (total === 0 ) {
        $('#ed_int').prop('disabled', true);
        $('#ed_int_filtered').prop('disabled', true);
      }   
      return(pre);    
    }


  });

  manageTableNotIntegrated = $('#manageTableNotIntegrated').DataTable({
    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
    "processing": true,
    "serverSide": true,
    "scrollX": true,
    "sortable": true,
    "searching": true,
    "serverMethod": "post",
    "selected": undefined, 
    'columnDefs': [{
           'targets': 0,
           'searchable': false,
           'orderable': false,
           'className': 'dt-body-center',
           'render': function (data, type, full, meta){
               return '<input type="checkbox" class="storesselect" name="id[]" value="' + $('<div/>').text(data).html() + '">';
            }
        }],
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'IntegrationsConfiguration/fetchWithoutIntegrationData',
      data: { [csrfName]: csrfHash, loja: loja, empresa: empresa, marketplace: marketplace, seller_index: seller_index},
      pages: 2
    }), 
    infoCallback: function(settings, start, end, max, total, pre) {
      $('#cr_int').prop('disabled', false);
      $('#cr_int_filtered').prop('disabled', false);
      if (total === 0 ) {
        $('#cr_int').prop('disabled', true);
        $('#cr_int_filtered').prop('disabled', true);
      }
      return(pre);
    }
  });

}

function clearFilters(){
  $('#buscaloja').val('');
  $('#buscaempresa').val('');
  $('#buscastatus').val('');
  $('#buscaautoapprove').val('');
  $('#buscasellerindex').val('');
  
  buscaStore();
}

function createIntegrations(e){
	e.preventDefault();
	
	var checkboxes = document.querySelectorAll('input[class=storesselect]:checked')
	if (checkboxes.length == 0) {
        AlertSweet.fire({
            icon: 'warning',
            title: 'Nenhuma loja selecionado!'
        });
		return;
	}
	document.getElementById('filter_stores').value=''; 
	document.getElementById('filter_company').value=''; 
  let filter_marketplace = $('#buscamarketplace').val();
  document.getElementById('filter_marketplace').value=filter_marketplace; 
  document.getElementById('filter_seller_index').value=''; 
	
	var stores_id ='';
	for (var i = 0; i < checkboxes.length; i++) {
	  stores_id = stores_id + checkboxes[i].value+ ";";
	}
	document.getElementById('id_integrate_several').value=stores_id  
 
  $("#createIntegrations").modal('show');
}

function createIntegrationsFiltereds(e){

	e.preventDefault();
	let filter_stores  = $('#buscaloja').val();
	document.getElementById('filter_stores').value=filter_stores; 
	let filter_company = $('#buscaempresa').val();
	document.getElementById('filter_company').value=filter_company; 
  let filter_marketplace = $('#buscamarketplace').val();
  document.getElementById('filter_marketplace').value=filter_marketplace; 
  let filter_seller_index = $('#buscasellerindex').val();
  document.getElementById('filter_seller_index').value=filter_seller_index; 

  document.getElementById('id_integrate_several').value='FILTER'  
  $("#createIntegrations").modal('show');
}

function createStoreIntegration(e, store_id, int_to, store_name, company_name){
	e.preventDefault();

  document.getElementById('create_store_integration').value=store_name; 
  document.getElementById('create_store_id_integration').value=store_id; 
  document.getElementById('create_company_integration').value=company_name; 
  document.getElementById('create_int_to_integration').value=int_to; 

  $("#createStoreIntegration").modal('show');
}

function editIntegrations(e){
	e.preventDefault();
	
	var checkboxes = document.querySelectorAll('input[class=inttoselect]:checked')
	if (checkboxes.length == 0) {
        AlertSweet.fire({
            icon: 'warning',
            title: 'Nenhuma integração selecionado!'
        });
		return;
	}
	document.getElementById('int_filter_stores').value=''; 
	document.getElementById('int_filter_company').value=''; 
  document.getElementById('int_filter_status').value=''; 
  let filter_marketplace = $('#buscamarketplace').val();
  document.getElementById('int_filter_marketplace').value=filter_marketplace; 
  document.getElementById('int_filter_autoapprove').value=''; 
  document.getElementById('int_filter_seller_index').value=''; 
	
	var int_id ='';
	for (var i = 0; i < checkboxes.length; i++) {
	  int_id = int_id + checkboxes[i].value+ ";";
	}
	document.getElementById('int_id_integrate_several').value=int_id  
 
  $("#editIntegrations").modal('show');
}

function editIntegrationsFiltereds(e){

	e.preventDefault();
	let filter_stores  = $('#buscaloja').val();
	document.getElementById('int_filter_stores').value=filter_stores; 
	let filter_company = $('#buscaempresa').val();
	document.getElementById('int_filter_company').value=filter_company; 
	let filter_status = $('#buscastatus').val();
  document.getElementById('int_filter_status').value=filter_status; 
  let filter_marketplace = $('#buscamarketplace').val();
  document.getElementById('int_filter_marketplace').value=filter_marketplace; 
  let filter_autoapprove = $('#buscaautoapprove').val();
  document.getElementById('int_filter_autoapprove').value=filter_autoapprove; 
  let filter_seller_index = $('#buscasellerindex').val();
  document.getElementById('int_filter_seller_index').value=filter_autoapprove; 

  document.getElementById('int_id_integrate_several').value='FILTER'  
  $("#editIntegrations").modal('show');
}

function editStoreIntegration(e, id_integration, int_to, store_name, company_name, auto_approve, active){
    e.preventDefault();

    document.getElementById('edit_store_integration').value=store_name; 
    document.getElementById('edit_id_integration').value=id_integration; 
    document.getElementById('edit_company_integration').value=company_name; 
    document.getElementById('edit_int_to_integration').value=int_to; 
    let check_auto = document.getElementById('edit_auto_approve_integration'); 
    if (auto_approve ==1) {
      check_auto.checked = true;
    }
    else {
      check_auto.checked = false;
    }
    let check_active = document.getElementById('edit_active_integration');
    if (active == 1) {
      check_active.checked = true;
    }
    else {
      check_active.checked = false;
    }

    $("#editStoreIntegration").modal('show');
}			

</script>
