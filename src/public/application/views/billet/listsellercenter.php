<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <?php $data['pageinfo'] = ""; $data['page_now'] ='payment_release'; $this->load->view('templates/content_header',$data); ?>

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

        <?php if(in_array('createPaymentRelease', $user_permission)): ?>
          <button class="btn btn-primary" id="btn_novo_billet" name="btn_novo_billet"><?=$this->lang->line('application_payment_release_create');?></button>
          <br /> <br />
        <?php endif; ?>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_date');?></th>
                <th><?=$this->lang->line('application_conciliacao_month_year');?></th>
                <th><?=$this->lang->line('application_runmarketplaces');?></th>
                <th><?=$this->lang->line('application_parameter_mktplace_value_ciclo');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <th><?=$this->lang->line('application_Paid');?></th>
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
    
    $("#btn_novo_billet").click( function(){
    	window.location.assign(base_url.concat("billet/createconciliasellercenter"));
    });

  $("#paraMktPlaceNav").addClass('active');
  $("#paymentReleaseNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url + 'billet/fetchConciliacaoGridData/sellercenter',
    'order': []
  });

});

function exportaArquivoConciliacao(id){
	if(id){
		  var saida = 'billet/excelnfconciliacaorepasse/' + id;
		   window.open(base_url.concat(saida),'_blank');
	}
}

function confirmarLiberacaoPagamento(lote)
{
    Swal.fire({
        title: 'Você tem certeza que quer marcar como PAGA esta liberação de pagamento?',
        text: "Após confirmação não será possível desfazer a ação!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim',
        cancelButtonText: 'Não',
    }).then((result) => {
        if (result.value) {
            window.location.href = '<?= base_url('billet/payconciliationsellercenter/') ?>' + lote;
        }
    })
}

</script>