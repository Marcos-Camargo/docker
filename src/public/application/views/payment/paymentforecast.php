<div class="content-wrapper">
	  
	<?php  $data['pageinfo'] = ""; 
	       $data['page_now'] = "parameter_payment_forecast";
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
        
        <div class="box">
        <div class="box-header">
                  <h3 class="box-title">Data dos ciclos nos Marketplace - </h3> <button type="button" id="btnExcel" name="btnExcel" class="btn btn-success">Excel</button>
        	 	
                  <br><br>
                  - Aqui na previsão de pagamentos você encontra a expectativa de recebimento em relação ao ciclo de cada um dos marketplaces disponíveis na Conecta Lá. <br>Os ciclos são compostos pelos pedidos entregues dentro das datas estipuladas na segunda e terceira coluna da tabela abaixo:
        </div>
          <div class="box-body">
            <table id="manageTable2" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_marketplace');?></th>
                <th>Data de entrega de:</th>
                <th>Data de entrega até:</th>
                <th>Dia repasse Conecta Lá</th>
                <th>Expectativa de Recebimento</th>
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

<script type="text/javascript">
var manageTable1;
var manageTable2;

var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

  $("#paraMktPlaceNav").addClass('active');
  $("#paymentforcastNav").addClass('active');

  //initialize the datatable 
  manageTable2 = $('#manageTable2').DataTable({
    'ajax': base_url + 'payment/extratopaymentforecast',
    "pageLength": 100,
    "lengthChange": false,
    "bFilter": false,
    "paging": false,
    "bInfo": false,
      'order': []
  });

  $("#btnExcel").click(function(){
		var saida = 'payment/exportextratopaymentforecast/';
		window.open(base_url.concat(saida),'_blank');
  });

});

</script>
