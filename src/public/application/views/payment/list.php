<!--
SW Serviços de Informática 2019

Acompanhar Creditos em conta

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_parameter_payment_split";  $this->load->view('templates/content_header',$data); ?>

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
        
        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_runmarketplaces');?></th> 
                <th><?=$this->lang->line('application_number_billet');?></th>
                <th><?=$this->lang->line('application_date');?></th>
                <th><?=$this->lang->line('application_value');?></th>
                <th><?=$this->lang->line('application_status_billet');?></th>
                <th><?=$this->lang->line('application_status_billet_iugu');?></th>
                <th><?=$this->lang->line('application_paymeny_status_split');?></th>
                <?php if(in_array('createPayment', $user_permission)): ?>
                  <th><?=$this->lang->line('application_action');?></th>
                <?php endif; ?>
              </tr>
              </thead>

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

<?php if(in_array('viewBillet', $user_permission)): ?>
<!-- create brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="addModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_view_billet');?></h4>
      </div>

      <form role="form" action="" method="post" id="createForm" name="createForm">

        <div class="modal-body">

		<div class="form-group">
            <label for="volume_type"><?=$this->lang->line('application_store');?></label>
            <select class="form-control" id="cmb_mktplace" name="cmb_mktplace">
              <option value="">~~SELECT~~</option>
                <?php foreach ($mktPlaces as $mktPlaces): ?>
                  <option value="<?php echo trim($mktPlaces['id']); ?>"><?php echo trim($mktPlaces['mkt_place']); ?></option>
                <?php endforeach ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="volume_type"><?=$this->lang->line('application_category');?></label>
            <select class="form-control" id="cmb_categoria" name="cmb_categoria">
              <option value="">~~SELECT~~</option>
                <?php foreach ($categs as $categ): ?>
                  <option value="<?php echo trim($categ['id']); ?>"><?php echo trim($categ['categoria']); ?></option>
                <?php endforeach ?>
            </select>
          </div>

			<div style="display:none" class="form-group" id="divCategoria" name="divCategoria">
            <label for="txt_categoria"><?=$this->lang->line('application_category');?></label>
            <input  type="text" class="form-control" id="txt_categoria" name="txt_categoria" placeholder="Nova Categoria" autocomplete="off">
         	 </div>

          <div class="form-group">
            <label for="txt_valor_aplicado"><?=$this->lang->line('application_valor_percentual');?></label>
            <input type="number" class="form-control" id="txt_valor_aplicado" name="txt_valor_aplicado" placeholder="Percentual de Desconto" autocomplete="off" min="1" max="100" step='0.01' value='0.00'>
          </div>
          
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal" id="btnFechar" name="btnFechar"><?=$this->lang->line('application_close');?></button>
          <button type="button" class="btn btn-primary" id="btnSave" name="btnSave"><?=$this->lang->line('application_save');?></button>
        </div>

      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!-- print_r($categ); print_r($mktPlace); -->

<?php endif; ?>

<?php if(in_array('deleteParamktplace', $user_permission)): ?>
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Remover Categoria - Marketplace</h4>
      </div>

      <form role="form" action="" method="post" id="removeForm">
        <div class="modal-body">
          <p>Você quer remover essa categoria - marketplace?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="btnRemover" name="btnRemover">Save changes</button>
        </div>
      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {
    
  $("#mainReceivableNav").addClass('active');
  $("#addReceivableNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url + 'payment/fetchBilletListsPaymentData',
    'order': []
  });

});

function gerarsplit(id){
	var url = "payment/createeditsplit/"+id;
	window.location.assign(base_url.concat(url));
}
function gerarboleto(id){
	/*if(confirm("Deseja gerar o boleto IUGU?")){
		var pageURL = base_url.concat("billet/gerarboletoiugu");
		$.post( pageURL, {id:id}, function( data ) {
			var saida = data.split(";");
      		  if(saida[0] == "1"){
      			  $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
      		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
      		              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+saida[1]+
      		            '</div>'); 
      		  }else{
      			  $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
      		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
      		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+saida[1]+
      		            '</div>');
    
      			  $('#manageTable').DataTable().ajax.reload();
      		  }
		});
	}*/
	alert("teste");
}


</script>
