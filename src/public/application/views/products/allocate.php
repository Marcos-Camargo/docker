<!--
SW Serviços de Informática 2019

Listar Produtos

Obs:
cada usuario so pode ver produtos da sua empresa.
Agencias podem ver todos os produtos das suas empresas
Admin pode ver produtos de todas as empresas e agencias

-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_integration";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">
	  	<!-- Filters DIV -->
		<div  tabindex="-1" role="dialog" id="filterModal" style="display:none;">
		  <div role="document">
		    <div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" id="hide" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title"><?=$this->lang->line('application_set_filters');?></span></h4>
				</div>
			    <form role="form" action="<?php echo base_url('products/filter') ?>" method="post" id="filterForm">
				<input type="hidden" name="from" value="allocate" />    
			    <div class="modal-body">
				    <?php
					$filters = $this->data['filters'];
				    $filters = get_instance()->data['filters'];
					foreach ($filters as $k => $v) { ?>
					<div class="row">
						<div class="form-group col-md-1">
					    	<label><?=$v['nm'];?></label>
						</div>
						<div class="form-group col-md-1">
					        <div>
					          <select type="text" class="form-control" id="<?=$k; ?>_op" name="<?=$k; ?>_op">
					            <option value="0"><?=$this->lang->line('application_codition')?></option>
					        <?php foreach ($v['op'] as $op) { ?>}    
					            <option value="<?=$op; ?>" ><?=$op; ?></option>
					        <?php } ?>    
					          </select>
					        </div>
						</div>
						<div class="form-group col-md-5">
							<div>
							    <input type="text" class="form-control" id="<?=$k; ?>" name="<?=$k; ?>" placeholder="<?=$this->lang->line('application_enter_value');?>"  />
							</div>
						</div>
						<div class="form-group col-md-1">+</div>	
					</div>
				<?php			
					}  
				?>
				</div> <!-- modal-body -->
			    <div class="modal-footer">
				  <div align="center">  
			      <button type="submit" class="btn btn-default" id="reset_filter" name="reset_filter"><?=$this->lang->line('application_clear');?></button>
			      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>
				  </div>
			    </div>		
		   	</form>  	
		    </div><!-- /.modal-content -->
		  </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
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
		<div id="showActions">
			<a class="pull-right btn btn-primary" href="<?php echo base_url('export/productsxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_data_export')?></a>
			<button type="button" id="show" class="btn btn-primary" <i class="fa fa-filter"></i> <?=$this->lang->line('application_change_filter')?></button>
		</div>
        <br />

        <div class="box">
          <div class="box-header">
            <h3 class="box-title"></h3>
          </div>
          <!-- /.box-header -->
          <div class="box-body">
		  <form role="form" action="<?php echo base_url('products/mktselect') ?>" method="post" id="selectForm">
    
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>             	
	            <th><input type="checkbox" name="select_all" value="1" id="manageTable-select-all"></th>  	             
                <th><?=$this->lang->line('application_image');?></th>
                <th><?=$this->lang->line('application_sku');?></th>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_price');?></th>
                <th><?=$this->lang->line('application_qty');?></th>
                <th><?=$this->lang->line('application_store');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <th><?=$this->lang->line('application_platform');?></th>
                <?php if(in_array('updateProduct', $user_permission) || in_array('deleteProduct', $user_permission)): ?>
                  <th><?=$this->lang->line('application_action');?></th>
                <?php endif; ?>
              </tr>
              </thead>
            </table>
            <!--- removido em 03/03/2020 - BlingProducts sendMarketplace enviará todos os nossos produtos para todos os marketplaces
            <div class="col-md-12">
	          <div class="col-md-2">  
		          <?php $plats = $this->data['plats']; ?>
		          <select type="text" class="form-control" id="mkt" name="mkt">
		            <option value="0" >Escolha a Plataforma</option>
		        <?php foreach ($plats as $plat) { ?>}    
		            <option value="<?=$plat['id']; ?>" ><?=$plat['int_to']; ?></option>
		        <?php } ?>    
		          </select>
	          </div>
	          
	          <div class="col-md-3">  
				  <button type="submit" class="btn btn-primary" id="select" name="select">Selecionar Produtos para Integração</button>
	          </div>	
	          <div class="col-md-3">  
				  <button type="submit" class="btn btn-primary" id="deselect" name="deselect">Retirar Produtos da Integração</button>
	          </div>
	          
			  <div id="loaderDiv" class="loader col-md-3"></div>
            </div>
            !---> 
		  </form>
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



<?php if(in_array('deleteProduct', $user_permission)): ?>
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_product');?><span id="deleteproductname"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('products/remove') ?>" method="post" id="removeForm">
        <div class="modal-body">
          <p><?=$this->lang->line('messages_delete_message_confirm');?></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
        </div>
      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>


<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {
    $("#loaderDiv").hide();

    $("#mainMarketPlaceNav").addClass('active');
	$("#allocProductNav").addClass('active');
	$("#hide").click(function(){
		$("#filterModal").hide();
		$("#showActions").show();
	});
	$("#show").click(function(){
		$("#filterModal").show();
		$("#showActions").hide();
	});

  // initialize the datatable 
  // manageTable = $('#manageTable').DataTable({
  //  'ajax': base_url + 'products/fetchProductData',
  //  'order': []
  // });

    var table = $('#manageTable').DataTable( {
	    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"	 },
        "processing": false,
        "serverSide": true,
        "serverMethod": "post",  
        "scrollX": true,
         selected: undefined,   
        'columnDefs': [{
           'targets': 0,
           'searchable': false,
           'orderable': false,
           'className': 'dt-body-center',
           'render': function (data, type, full, meta){
               return '<input type="checkbox" name="id[]" value="' + $('<div/>').text(data).html() + '">';
				//return '<input type="hidden" name="id[]" value="' + $('<div/>').text(data).html() + '">';
            }
        }],
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'products/fetchProductData',
			data: { ismkt:1 }, 
            pages: 2 // number of pages to cache
        } )
    } );
   // Handle click on "Select all" control
   $('#manageTable-select-all').on('click', function(){
      // Get all rows with search applied
      var rows = table.rows({ 'search': 'applied' }).nodes();
      // Check/uncheck checkboxes for all rows in the table
      $('input[type="checkbox"]', rows).prop('checked', this.checked);
   });

   // Handle click on checkbox to set state of "Select all" control
   $('#manageTable tbody').on('change', 'input[type="checkbox"]', function(){
      // If checkbox is not checked
      if(!this.checked){
         var el = $('#manageTable-select-all').get(0);
         // If "Select all" control is checked and has 'indeterminate' property
         if(el && el.checked && ('indeterminate' in el)){
            // Set visual state of "Select all" control
            // as 'indeterminate'
            el.indeterminate = true;
         }
      }
   });
   // Handle form submission event
   $('#selectForm').on('submit', function(e){
      var form = this;
       $("#loaderDiv").show();
      // Iterate over all checkboxes in the table
      table.$('input[type="checkbox"]').each(function(){
         // If checkbox doesn't exist in DOM
         if(!$.contains(document, this)){
            // If checkbox is checked
            if(this.checked){
               // Create a hidden element
               $(form).append(
                  $('<input>')
                     .attr('type', 'hidden')
                     .attr('name', this.name)
                     .val(this.value)
               );
            }
         }
      });
   });

});

// remove functions 
function removeFunc(id,name)
{
  if(id) {
	document.getElementById("deleteproductname").innerHTML= ': '+name;  
    $("#removeForm").on('submit', function() {

      var form = $(this);

      // remove the text-danger
      $(".text-danger").remove();

      $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: { product_id:id }, 
        dataType: 'json',
        success:function(response) {

          manageTable.ajax.reload(null, false); 

          if(response.success === true) {
            $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
            '</div>');

            // hide the modal
            $("#removeModal").modal('hide');

          } else {

            $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
            '</div>'); 
          }
        }
      }); 

      return false;
    });
  }
}

</script>
