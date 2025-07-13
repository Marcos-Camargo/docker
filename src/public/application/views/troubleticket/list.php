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
                  		<label for="cnpj"><?=$this->lang->line('application_number_troubleticket');?></label>
                                <input type="text" class="form-control" id="txt_numero_chamado" name="txt_numero_chamado" placeholder="<?=$this->lang->line('application_number_troubleticket')?>">
              		</div>
                
                	<div class="col-md-2 col-xs-2">
                  		<label for="group_isadmin"><?=$this->lang->line('application_runmarketplaces');?></label>
                      <select class="form-control" id="slc_marketplace" name="slc_marketplace">
                        <option value="">~~SELECT~~</option>
                        <?php foreach ($mktplaces as $mktplace): ?>
                              <option value="<?php echo trim($mktplace['id']); ?>"><?php echo trim($mktplace['mkt_place']); ?></option>
                            <?php endforeach ?>
                      </select>
              		</div>
              		
              		<div class="col-md-2 col-xs-2">
                  		<label for="group_isadmin"><?=$this->lang->line('application_status');?></label>
                      <select class="form-control" id="slc_status_ciclo" name="slc_status_ciclo">
                        <option value="">~~SELECT~~</option>
                        <?php foreach ($status_billets as $status_billet): ?>
                              <option value="<?php echo trim($status_billet['id']); ?>"><?php echo trim($status_billet['nome']); ?></option>
                            <?php endforeach ?>
                      </select>
              		</div>
                
                
    		      	<div class="col-md-2 col-xs-2">
                  		<label for="cnpj">Data Criação - Início</label>
                                <input type="date" class="form-control" id="txt_data_inicio" name="txt_data_inicio" placeholder="<?=$this->lang->line('application_start_date')?>">
              		</div>
              		<div class="col-md-2 col-xs-2">
                  		<label for="cnpj">Data Criação - Fim</label>
                                <input type="date" class="form-control" id="txt_data_fim" name="txt_data_fim" placeholder="<?=$this->lang->line('application_end_date')?>">
              		</div>
              		
              		<div class="col-md-2 col-xs-2"><br>
                		<button type="button" id="btnFilter" name="btnFilter" class="btn btn-primary"><?=$this->lang->line('application_filter');?></button>
                		<button type="button" id="btnExcel" name="btnExcel" class="btn btn-success">Excel</button>
            		</div>
        		</form>
            		
            		<div class="col-md-12 col-xs-12"><br>
                		<?php if(in_array('createTTMkt', $user_permission)): ?>
                          <button class="btn btn-primary" id="btn_novo_chamado" name="btn_novo_chamado"><?=$this->lang->line('application_agidesk_servicos');?></button>
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
                <th><?=$this->lang->line('application_runmarketplaces');?></th>
                <th><?=$this->lang->line('application_number_troubleticket');?></th>
                <th><?=$this->lang->line('application_date_troubleticket');?></th>
                <th><?=$this->lang->line('application_date_forcast_troubleticket');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <?php if(in_array('viewTTMkt', $user_permission)): ?>
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
    
	$("#btn_novo_chamado").click( function(){
    	window.location.assign(base_url.concat("TroubleTicket/createtroubleticket"));
    });

  // $("#mainReceivableNav").addClass('active');
  $("#addReceivableNav").addClass('active');

  // initialize the datatable 
  manageTable = $('#manageTable').DataTable({
    'ajax': base_url + 'TroubleTicket/fetchBalanceData?'+filtro,
    'order': []
  });

	$("#btnFilter").click( function(){

		filtro = $("#formFiltro").serialize();

		$('#manageTable').DataTable().destroy();
		manageTable = $('#manageTable').DataTable({
			    'ajax': base_url + 'TroubleTicket/fetchBalanceData?'+filtro,
			    'order': []
		});
		
	});

	$("#btnExcel").click(function(){
        filtros = $("#formFiltro").serialize();
        var saida = 'TroubleTicket/fetchBalanceDataExcel?' + filtros;
        window.open(base_url.concat(saida),'_blank');
	});
	  


});

</script>
