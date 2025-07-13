<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

  <?php 
    $data['pageinfo'] = "application_manage";  
    $this->load->view('templates/content_header',$data); 
  ?>

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
        <div>
          <?php if(in_array('createCuration', $this->permission)) { ?>
            <a href="<?php echo base_url('whitelist/createnew') ?>" class="btn btn-primary"><?=$this->lang->line('application_button_add_permission');?></a>
          <?php } ?>
        </div>
        <br />
        <div class="row">
          <div class="col-md-12">
            <div class="callout callout-warning">
              <h4><?=$this->lang->line('application_warning')?>!</h4>
              <p><?=$this->lang->line('messages_warning_update_white_black_list')?></p>
            </div>
          </div>
        </div>
        <div class="box">
          <div class="box-body"> 
            <table id="manageTable" aria-label="" class="table table-striped table-hover display table-condensed" style="border-collapse: collapse; width: 99%; border-spacing: 0; ">
              <thead>
              <tr>
                <th><?= $this->lang->line('application_quotation_id') ?></th>
                <th><?= $this->lang->line('application_permission') ?></th>
                <th>Responsável</th>
                <th>Início</th>
                <th>Fim</th>
                <th><?= $this->lang->line('application_status') ?></th>
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

<style>
    .form-group .bootstrap-select {
        border: 1px solid #d2d6de;
    }
</style>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">

  var manageTable;
  var base_url = "<?php echo base_url(); ?>";

  $(document).ready(function() {
    getWords();
  });

  function getWords(){
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
        url: base_url + 'Whitelist/fetchWhitelistData',
        data: {},
        pages: 2, // number of pages to cache
      })
    });
  }

</script>