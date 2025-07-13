<!--
SW Serviços de Informática 2019

Criar Grupos de Acesso

-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_view_billet";  $this->load->view('templates/content_header',$data); ?>

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
              <!-- <h3 class="box-title"><?=$this->lang->line('application_view_billet');?></h3>  -->
            </div>
            <form role="form" id="frmCadastrar" name="frmCadastrar" method="post">
              <div class="box-body">

            <?php 
            if (validation_errors()) { 
	          foreach (explode("</p>",validation_errors()) as $erro) { 
		        $erro = trim($erro);
		        if ($erro!="") { ?>
				<div class="alert alert-error alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	                <?php echo $erro."</p>"; ?>
				</div>
		<?php	}
			  }
	       	} ?>

                <div class="form-group col-md-12 col-xs-12">
                  <label for="group_isadmin"><?=$this->lang->line('application_billets');?></label>
                  <select class="form-control" id="slc_billet" name="slc_billet" readonly="readonly">
                    <?php foreach ($billets as $billet) { ?>
                    	<option value="<?php echo $billet['id']?>"><?php echo $billet['store_nome']?></option>
                    <?php }?>
                  </select>
                </div>
                
                 <div class="form-group col-md-3 col-xs-3">
                    <label for="group_name"><?=$this->lang->line('application_due_date');?></label>
                    <input type="text" class="form-control" id="txt_dt" name="txt_dt" placeholder="Data Criação" readonly="readonly" value="<?php echo $billets[0]['data_geracao']?>">
                </div>
                
                 <div class="form-group col-md-3 col-xs-3">
                    <label for="group_name"><?=$this->lang->line('application_date');?></label>
                    <input type="text" class="form-control" id="txt_dt_vencimento" name="txt_dt_vencimento" placeholder="Data Vencimento" readonly="readonly" value="<?php echo $billets[0]['data_vencimento']?>">
                </div>
              	<div class="form-group col-md-3 col-xs-3">
                    <label for="group_name"><?=$this->lang->line('application_status_billet');?></label>
                    <input type="text" class="form-control" id="txt_status" name="txt_status" placeholder="Status Boleto"  readonly="readonly" value="<?php echo $billets[0]['status_billet']?>">
                </div>
                <div class="form-group col-md-3 col-xs-3">
                    <label for="group_name"><?=$this->lang->line('application_status_billet_iugu');?></label>
                    <input type="text" class="form-control" id="txt_status_iugu" name="txt_status_iugu" placeholder="Status IUGU"  readonly="readonly" value="<?php echo $billets[0]['status_iugu']?>">
                </div>
                <div class="form-group col-md-6 col-xs-6">
                    <label for="group_name"><?=$this->lang->line('application_billet_id_iugu');?></label>
                    <input type="text" class="form-control" id="txt_id" name="txt_id" placeholder="ID Boleto" readonly="readonly" value="<?php echo $billets[0]['id_boleto_iugu']?>">
                </div>
                <div class="form-group col-md-6 col-xs-6">
                    <label for="group_name"><?=$this->lang->line('application_billet_url_iugu');?></label>
                    <input type="text" class="form-control" id="txt_url" name="txt_url" placeholder="URL IUGU" readonly="readonly" value="<?php echo $billets[0]['url_boleto_iugu']?>">
                </div>
                
                <div class="form-group col-md-3 col-xs-3">
                    <label for="group_name"><?=$this->lang->line('application_value');?></label>
                    <input type="number" class="form-control" id="txt_valor" name="txt_valor" placeholder="Valor Cobrança"  readonly="readonly" value="<?php echo $billets[0]['valor_total']?>">
                </div>
                
              </div>
          
              <div class="box-footer">
                <button type="button" id="btnVoltar" name="btnVoltar" class="btn btn-warning"><?=$this->lang->line('application_back');?></button>
              </div>
            </form>
            
            <div id="retornoTeste" name="retornoTest">
            
            </div>
            
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
var manageTableResult;
var manageTableBillet;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

	$("#btnCancel").prop('disabled', true);

	$("#btnVoltar").click( function(){
    	window.location.assign(base_url.concat("iugu/list"));
    });

	$("#txt_url").click( function(){
		window.open($("#txt_url").val());
    });

	
});


  
</script>