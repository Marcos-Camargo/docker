<!--

Lista produtos que não estão no padrão dos correios

-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_manage";  
	 $data['page_now'] ='products_not_post_office';
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
		<div id="showActions">
		</div>
        <br />
        
        <div class="box">
          <div class="box-header">
            <h3 class="box-title"></h3>
          </div>
          <!-- /.box-header -->
          <div class="box-body">
		  <form role="form" action="<?php echo base_url('products/markproductasok') ?>" method="post" id="selectForm">  
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>             	
	            <th><input type="checkbox" name="select_all" value="1" id="manageTable-select-all"></th>   
	            <th><?=$this->lang->line('application_id');?></th>  
	            <th><?=$this->lang->line('application_store');?></th>         
	            <th><?=$this->lang->line('application_sku');?></th>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_weight');?> (Max:30)</th>
                <th><?=$this->lang->line('application_cubic_weight');?> (Max:30)</th>
                <th><?=$this->lang->line('application_width');?> (Max:105)</th>
                <th><?=$this->lang->line('application_height');?> (Max:105)</th>
                <th><?=$this->lang->line('application_depth');?> (Max:105)</th>
                <th><?=$this->lang->line('application_sum_of_dimensions');?> (Max:200)</th>
                <th><?=$this->lang->line('application_price');?></th>   
                <th><?=$this->lang->line('application_last_update');?></th>  
                <th><?=$this->lang->line('application_action');?></th>     
              </tr>
              </thead>
            </table>

            <div class="col-md-12">
	          
	          <div class="col-md-3">  
				  <button type="submit" class="btn btn-primary" id="select" name="select"><?=$this->lang->line('application_mark_products_ok');?></button>
	          </div>	
	          <!----
	          <div class="col-md-3">  
				  <button type="submit" class="btn btn-primary" id="deselect" name="deselect">Desmarcar como Integrados no Bling</button>
	          </div>
	          ----> 
			  <div id="loaderDiv" class="loader col-md-3"></div>
            </div>
            
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

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {
    $("#loaderDiv").hide();

    $("#mainProcessesNav").addClass('active');
    $("#productsNotCorreiosNav").addClass('active');

  // initialize the datatable 
  //var table = $('#manageTable').DataTable({
  // 'ajax': base_url + 'produtosProblemas/semIntegracaoData',
  //  'order': []
  //});

    var table = $('#manageTable').DataTable( {
	    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
        "processing": true,
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
            }
        }],
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'products/fetchProductsNotCorreios',
            data: { }, 
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

</script>
