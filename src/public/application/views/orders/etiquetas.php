<div class="content-wrapper">
  <?php $data['pageinfo'] = "application_manage"; $data['page_now'] ='label'; $this->load->view('templates/content_header',$data); ?>
  <section class="content">
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
          	<form role="form" action="<?php echo base_url('orders/etiquetasSelect') ?>" method="post" id="selectForm">
	          <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
	            <thead>
                  <tr>
                    <th><?=$this->lang->line('application_order');?></th>
                    <th><?=$this->lang->line('application_order_marketplace');?></th>
                    <th><?=$this->lang->line('application_store');?></th>
                    <th><?=$this->lang->line('application_items');?></th>
                    <th><?=$this->lang->line('application_item');?></th>
                    <th><?=$this->lang->line('application_total_amount');?></th>
                    <th><?=$this->lang->line('application_date');?></th>
                    <th><?=$this->lang->line('application_action');?></th>
                  </tr>
	            </thead>
	          </table>
	          <div class="row">
                  <div class="col-md-12 col-xs-12 d-flex justify-content-end">
                    <a class="btn btn-primary col-md-3" id="selectA4" href="<?=base_url('orders/manage_tags')?>"><?=$this->lang->line('application_generate_tags');?></a>
                  </div>
	          </div>
	        </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
let manageTable;
const base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {
    $("#mainLogisticsNav").addClass('active');
    $("#etiquetasNav").addClass('active');

    manageTable = $('#manageTable').DataTable( {
	   	"language": { 
            "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>",
            "processing": '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        },
        "processing": true,
        "serverSide": true,
        "scrollX": true,
        "serverMethod": "post",
         selected: undefined,   
        'columnDefs': [{
           'targets': 0,
           'searchable': false,
           'orderable': false,
        }],     
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'orders/etiquetasData',
            data: { }, 
            pages: 2
        })
    });
});



</script>
