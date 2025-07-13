<!--
SW Serviços de Informática 2019

Listar Empresas 

Obs:
Agencias podem ver todos as suas empresas
Admin pode ver todas as empresas e agencias

-->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

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

        <?php if(in_array('createCompany', $user_permission)): ?>
          <a href="<?php echo base_url('templateEmail/create') ?>" class="btn btn-primary"><?='Adicionar Template de Email' //$this->lang->line('application_add_company');?></a>
        <?php endif; ?>
		<br /> <br />
		<div class="box">
      <div class="box-body">
        <div class="col-md-12">
          <label for="buscaTitulo" class="normal"><?= 'Filtro por título' //$this->lang->line('application_name');?></label>
          <div class="input-group">
            <input type="search" id="buscaTitulo" onchange="buscaTemplate()" class="form-control" placeholder="<?= 'Filtro por título' //$this->lang->line('application_name');?>" aria-label="Search" aria-describedby="basic-addon1">
            <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
          </div>
        </div>
      </div>
      <div class="box-body">
        <div class="col-md-9">
          <label for="buscaAssunto" class="normal"><?= 'Filtro por assunto' //$this->lang->line('application_raz_soc');?></label>
          <div class="input-group">
            <input type="search" id="buscaAssunto" onchange="buscaTemplate()" class="form-control" placeholder="<?= 'Filtro por assunto' //$this->lang->line('application_raz_soc');?>" aria-label="Search" aria-describedby="basic-addon1">
            <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
          </div>
        </div>
        <div class="col-md-2">
          <div class="input-group" >
            <label for="buscaStatus" class="normal"><?='Status' //$this->lang->line('application_associate_type');?></label>
            <select class="form-control" id="buscaStatus" onchange="buscaTemplate()">
              <option value=""><?=$this->lang->line('application_all');?></option>
              <option value="1"><?='Ativo' //$this->lang->line('application_agency');?></option>
              <option value="0"><?='Inativo' //$this->lang->line('application_partner');?></option>
            </select>
          </div>
        </div>
        <div class="col-md-1">
          <div class="pull-right">
              <label  class="normal" style="display: block;">&nbsp; </label>
              <button type="button" onclick="clearFilters()" class="btn btn-primary"> <i class="fa fa-eraser"></i> Limpar </button>
          </div>
        </div>
      </div>
    </div>
    <div class="box">
      <div class="box-body">
        <table id="manageTable" class="table table-bordered table-striped" cellspacing="0" style="border-collapse: collapse; width: 100%;">
          <thead>
            <tr>
              <th><?=$this->lang->line('application_title');?></th>
              <th><?=$this->lang->line('application_subject');?></th>
              <th style="width:80px;"><?=$this->lang->line('application_status');?></th>
              <th style="width:130px;"><?=$this->lang->line('application_action');?></th>
            </tr>
          </thead>
        </table>
      </div>
    </div>

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
    // $("#mainCompanyNav").addClass('active');
    // $("#manageCompanyNav").addClass('active');
    buscaTemplate();
});

function buscaTemplate(){
  let title = $('#buscaTitulo').val();
  let subject = $('#buscaAssunto').val();
  let status = $('#buscaStatus').val();

  if (typeof manageTable === 'object' && manageTable !== null) {
  	manageTable.destroy();
  }
  
  manageTable = $('#manageTable').DataTable({
    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
    "processing": true,
    "serverSide": true,
    "scrollX": true,
    "sortable": true,
    "searching": true,
    "serverMethod": "post",
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'templateEmail/fetchTemplatesData',
      data: { title, subject, status },
      pages: 2
    })
  });
}

function clearFilters(){
  $('#buscaTitulo').val('');
  $('#buscaAssunto').val('');
  $('#buscaStatus').val('');
  buscaTemplate();
}
  
</script>

