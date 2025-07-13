<!--
SW Serviços de Informática 2019

-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

	<?php $data['pageinfo'] = "application_manage";  
	 $data['page_now'] ='integration_price_qty';
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
		
         <!-- <a class="pull-right btn btn-primary" href="<?php echo base_url('export/ProductIntegrationXls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_data_export')?></a>
          <br /> <br /> -->
        </div>
        <div class="box">
          <!-- /.box-header -->
          <div class="box-body">
          	
	      	<div class="row">
                <div class="col-md-3">
                    <select class="form-control input-sm col-md-3 col-sm-3 col-sx-12 select-table-filter-inside" name="selectMarketplace" id="selectMarketplace">
                        <option value="">Filtre por um MarketPlace (Limpar)</option>
                        <option value="ML">Mercado Livre</option>
                        <option value="VIA">Via Varejo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control input-sm col-md-3 col-sm-3 col-sx-12 select-table-filter-inside" name="selectIntegration" id="selectIntegration">
                        <option value="">Todos</option>
                        <option value="Preço">Preço</option>
                        <option value="Estoque">Estoque</option>
                    </select>
                </div>
           	</div>
            
            <form role="form" action="<?php echo base_url('waitingIntegration/markIntegrated') ?>" method="post" id="selectForm">
            <table id="manageTable" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>             	
	            <th><input type="checkbox" name="select_all" value="1" id="manageTable-select-all"></th>              
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_runmarketplaces');?></th>
                <th><?=$this->lang->line('application_store');?></th>
                <th><?=$this->lang->line('application_sku');?></th>
                <th><?=$this->lang->line('application_name');?></th>          
                <th><?=$this->lang->line('application_price');?></th>
                <th><?=$this->lang->line('application_qty');?></th>
                <th width="10%" ><?=$this->lang->line('application_ship_date');?></th>
                <th><?=$this->lang->line('application_instructions');?></th>
           
              </tr>
              </thead>
            </table>
            
            <div class="col-md-12">
	          
	          <div class="col-md-7">  
				  <button type="submit" class="btn btn-primary" id="selectPrice" name="selectPrice"><i class="fa fa-usd" aria-hidden="true"></i> <?=$this->lang->line('application_mark_integration_price')?></button>
			  	  <button type="submit" class="btn btn-primary" id="selectQty" name="selectQty"><i class="fas fa-warehouse"></i> <?=$this->lang->line('application_mark_integration_qty')?></button>
	          </div>
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
var table;

$(document).ready(function() {
    $("#loaderDiv").hide();

    $("#mainProcessesNav").addClass('active');
    $("#integrationPriceQtyNav").addClass('active');

  // initialize the datatable 
  //var table = $('#manageTable').DataTable({
  // 'ajax': base_url + 'produtosProblemas/semIntegracaoData',
  //  'order': []
  //});

    table = $('#manageTable').DataTable( {
        "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
        "processing": true,
        "serverSide": true,
        "serverMethod": "post",
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
            url: base_url + 'waitingIntegration/integrationPriceQtyData',
            data: { marketplace: $('#selectMarketplace').val(), integration: $('#selectIntegration').val() },
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

   $('#selectMarketplace, #selectIntegration').on('change', function () {
       table.destroy();
       table = $('#manageTable').DataTable( {
           "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
           "processing": true,
           "serverSide": true,
           "serverMethod": "post",
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
               url: base_url + 'waitingIntegration/integrationPriceQtyData',
               data: { marketplace: $('#selectMarketplace').val(), integration: $('#selectIntegration').val()  },
               pages: 2 // number of pages to cache
           } )
       } );
   })

});

</script>
