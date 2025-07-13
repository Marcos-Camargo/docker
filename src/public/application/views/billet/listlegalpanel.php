<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <?php $data['pageinfo'] = ""; $data['page_now'] ='legal_panel'; $this->load->view('templates/content_header',$data); ?>

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
        
        <?php if(in_array('createBillet', $user_permission)): ?>
          <button class="btn btn-primary" id="btn_novo_billet" name="btn_novo_billet">Criar Notificação</button>
          <br /> <br />
        <?php endif; ?>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_date');?></th>
                <th>Número da notificação</th>
                <th>Saldo Total</th>
                <th>Saldo a debitar</th>
                <th>Status</th>
                <th>Responsável pela Abertura</th>
                <th>Responsável pela Edição</th>  
                <th><?=$this->lang->line('application_action');?></th>
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
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

  $("#paraMktPlaceNav").addClass('active menu-open');
	$("#paineljuridicoNav").addClass('active');
    
    $("#btn_novo_billet").click( function(){
    	window.location.assign(base_url.concat("billet/createlegalpanelsellercenter"));
    });

  $("#mainReceivableNav").addClass('active');
  $("#addReceivableNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url + 'billet/fetchlegalpanelsellercenter',
    'order': []
  });

});

function exportaArquivoConciliacao(id){
	if(id){
		  var saida = 'billet/excelnfconciliacaorepasse/' + id;
		   window.open(base_url.concat(saida),'_blank');
	}
}

</script>