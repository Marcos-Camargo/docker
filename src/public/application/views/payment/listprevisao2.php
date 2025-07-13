<!--
SW Serviços de Informática 2019

Acompanhar Creditos em conta

-->


<!-- Content Wrapper. Contains page content -->
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
                  <h3 class="box-title">Expectativa de recebimento dos Marketplaces</h3> 
        	</div>
          <div class="box-body">
            <table id="manageTable1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_marketplace');?></th>
                <?php foreach($dataSaida as $data2){ ?>
                    <th>Data Repasse: <?php echo $data2['data'];?> </th>
                <?php }?>
              </tr>
              </thead>
            </table>
          </div>
          <!-- /.box-body -->
        </div>
        
        <div class="box">
        <div class="box-header">
                  <h3 class="box-title">Data dos ciclos nos Marketplace</h3> 
                  <br><br>
                  - Detalhamento dos ciclos para os próximos dois meses
        </div>
          <div class="box-body">
              <table id="manageTable2" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                      <th><?= $this->lang->line('application_marketplace'); ?></th>
                      <th class="data-corte-de">Data de Entrega de:</th>
                      <th class="data-corte-ate">Data de Entrega até:</th>
                      <th>Dia pagamento Marketplace</th>
                      <th>Dia repasse Conecta Lá</th>
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

  // $("#mainReceivableNav").addClass('active');
  $("#addReceivableNav").addClass('active');

  // initialize the datatable 
  manageTable1 = $('#manageTable1').DataTable({
    'ajax': base_url + 'payment/extratoprevisao2',
    "pageLength": 100,
	"lengthChange": false,
	"bFilter": false,
	"paging": false,
	"bInfo": false,
    'order': []
  });

  //initialize the datatable 
  manageTable2 = $('#manageTable2').DataTable({
    'ajax': base_url + 'payment/extratoprevisaodataspagamento',
    "pageLength": 100,
	"lengthChange": false,
	"bFilter": false,
	"paging": false,
	"bInfo": false,
    'order': [],
      'columns': [
          {data: 'marktplace'},
          {data: 'data_inicio'},
          {data: 'data_fim'},
          {data: 'data_pagamento_mktplace'},
          {data: 'data_pagamento_conecta'},
          {
              data: 'data_corte', visible: false,
              render: function (desc) {
                  if ($('#manageTable2').find('th[class^=data-corte]').length > 0) {
                      $('#manageTable2').find('th.data-corte-de').text(desc + ' de:').removeClass('data-corte-de');
                      $('#manageTable2').find('th.data-corte-ate').text(desc + ' até:').removeClass('data-corte-ate');
                  }
                  return desc;
              }
          },
      ]
  });

});

</script>
