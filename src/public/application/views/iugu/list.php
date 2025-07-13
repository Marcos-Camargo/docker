<!--
SW Serviços de Informática 2019

Acompanhar Creditos em conta

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_iugu_parameter";  $this->load->view('templates/content_header',$data); ?>

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
        
	<div class="row">
    	<div class="box">
      		<div class="box-body">
          		<div class="box-header">
                  <h3 class="box-title"><?=$this->lang->line('application_set_filters');?></h3>
                </div> 
                <form id="formFiltro" name="formFiltro">
    		      	<div class="col-md-2 col-xs-2">
                  		<label for="cnpj">Data Criação - Início</label>
                                <input type="date" class="form-control" id="txt_data_inicio" name="txt_data_inicio"  placeholder="<?=$this->lang->line('application_start_date')?>">
              		</div>
              		<div class="col-md-2 col-xs-2">
                  		<label for="cnpj">Data Criação - Fim</label>
                                <input type="date" class="form-control" id="txt_data_fim" name="txt_data_fim"  placeholder="<?=$this->lang->line('application_end_date')?>">
              		</div>
              		<div class="col-md-2 col-xs-2">
                  		<label for="cnpj">Data pagamento - Início</label>
                                <input type="date" class="form-control" id="txt_data_pagamento_inicio" name="txt_data_pagamento_inicio" required placeholder="<?=$this->lang->line('application_start_date')?>">
              		</div>
              		<div class="col-md-2 col-xs-2">
                  		<label for="cnpj">Data pagamento - Fim</label>
                                <input type="date" class="form-control" id="txt_data_pagamento_fim" name="txt_data_pagamento_fim" required placeholder="<?=$this->lang->line('application_end_date')?>">
              		</div>
              		
              		<div class="col-md-2 col-xs-2">
                  		<label for="group_isadmin">Status</label>
                      <select class="form-control" id="slc_status_ciclo" name="slc_status_ciclo">
                        <option value="">~~SELECT~~</option>
                        <?php foreach ($status_billets as $status_billet): ?>
                              <option value="<?php echo trim($status_billet['id']); ?>"><?php echo trim($status_billet['nome']); ?></option>
                            <?php endforeach ?>
                      </select>
              		</div>
              		
              		<div class="col-md-2 col-xs-2"><br>
                		<button type="button" id="btnFilter" name="btnFilter" class="btn btn-primary"><?=$this->lang->line('application_filter');?></button>
                		<button type="button" id="btnExcel" name="btnExcel" class="btn btn-success">Excel</button>
            		</div>
        		</form>
            		
            		
            		
            		<div class="col-md-12 col-xs-12"><br>
                		<?php if(in_array('createIugu', $user_permission)): ?>
                          <button class="btn btn-primary" id="btn_novo_billet" name="btn_novo_billet"><?=$this->lang->line('application_create_billet');?></button>
                          <button class="btn btn-danger" id="btn_cancelar_boleto" name="btn_cancelar_boleto"><?=$this->lang->line('application_billet_cancel');?></button>
                          <button class="btn btn-primary" id="btn_status_subconta" name="btn_status_subconta"><?=$this->lang->line('application_billet_subconta_iugu');?></button>
                          <button class="btn btn-warning" id="btn_novo_split" name="btn_novo_split"><?=$this->lang->line('application_payment_split');?></button>
                        <?php endif; ?>
            		</div>
        	</div>
        </div>
 	</div>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_billet_recipient');?></th> 
                <!-- <th><?=$this->lang->line('application_number_billet');?></th>  -->
                <th><?=$this->lang->line('application_email');?></th>  
                <th><?=$this->lang->line('application_date');?></th>
                <th><?=$this->lang->line('application_due_date');?></th>
                <th><?=$this->lang->line('application_payment_date');?></th>
                <th><?=$this->lang->line('application_value');?></th>
                <th><?=$this->lang->line('application_status_billet');?></th>
                <th><?=$this->lang->line('application_status_billet_iugu');?></th>
                <?php if(in_array('viewIugu', $user_permission)): ?>
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

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

	var filtro = $("#formFiltro").serialize()
    
	$("#btn_novo_billet").click( function(){
    	window.location.assign(base_url.concat("iugu/createbillet"));
    });

	$("#btn_novo_split").click( function(){
    	window.location.assign(base_url.concat("iugu/createsplit"));
    });

	$("#btn_cancelar_boleto").click( function(){
    	window.location.assign(base_url.concat("iugu/cancelbillet"));
    });

	$("#btn_status_subconta").click( function(){
    	window.location.assign(base_url.concat("iugu/subcontastatus"));
    });
	
  // $("#mainReceivableNav").addClass('active');
  $("#addReceivableNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url + 'iugu/fetchBalanceData?'+filtro,
    'order': []
  });

	$("#btnFilter").click( function(){

		filtro = $("#formFiltro").serialize();

		$('#manageTable').DataTable().destroy();
		manageTable = $('#manageTable').DataTable({
			    'ajax': base_url + 'iugu/fetchBalanceData?'+filtro,
			    'order': []
		});
		
	});

	$("#btnExcel").click(function(){
        filtros = $("#formFiltro").serialize();
        var saida = 'iugu/fetchBalanceDataExcel?' + filtros;
        window.open(base_url.concat(saida),'_blank');
	});
	  


});

</script>
