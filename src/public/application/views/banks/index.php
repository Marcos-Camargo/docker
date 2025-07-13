<!--
SW Serviços de Informática 2019

Index de atributos
Add, Edit & delete

-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

	<?php $data['pageinfo'] = "application_manage";
$this->load->view('templates/content_header', $data);?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="messages"></div>

        <?php if ($this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('success'); ?>
          </div>
        <?php elseif ($this->session->flashdata('error')): ?>
          <div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->session->flashdata('error'); ?>
          </div>
        <?php endif;?>

        <?php if (in_array('createBank', $user_permission)): ?>
          <button class="btn btn-primary" data-toggle="modal" data-target="#addModal" onclick="openModal()"><?=$this->lang->line('application_add_bank');?></button>
          <br /> <br />
        <?php endif;?>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_number_bank');?></th>
                <th><?=$this->lang->line('application_name_bank');?></th>
                <th><?=$this->lang->line('application_status');?></th>
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


<!-- create attributes modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="addModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="modal_title"></h4>
      </div>

      <form role="form" action="<?php echo base_url('bank/create') ?>" method="post" id="createForm">
      	<input type="hidden" name="<?=$this->security->get_csrf_token_name();?>" value="<?=$this->security->get_csrf_hash();?>" />
        <div class="modal-body">
          <div class="form-group">
            <input type="hidden" class="form-control" id="bank_id" name="id" autocomplete="off">
          </div>
          <div class="form-group">
            <label for="bank_name"><?=$this->lang->line('application_name');?></label>
            <input type="text" class="form-control" id="bank_name" name="name" placeholder="<?=$this->lang->line('application_enter_bank_name')?>" autocomplete="off" required <?=$updateBank == true ? '' : 'disabled'?>>
          </div>
          <div class="form-group">
            <label for="bank_number"><?=$this->lang->line('application_number');?></label>
            <input type="number" class="form-control" id="bank_number" name="number" placeholder="<?=$this->lang->line('application_enter_bank_number')?>" onkeyup="characterLimit(this)" maxlength="3" autocomplete="off" required <?=$updateBank == true ? '' : 'disabled'?>>
            <span id="char_bank_number"></span><br />
          </div>



          <div class="form-group">
            <label for="active"><?=$this->lang->line('application_status');?></label>
            <select class="form-control" id="active" name="active" <?=$updateBank == true ? '' : 'disabled'?>>
              <option value="1"><?=$this->lang->line('application_active');?></option>
              <option value="2"><?=$this->lang->line('application_inactive');?></option>
            </select>
          </div>
          <div class="form-group">
          <h5><?=$this->lang->line('application_mask_title');?></h5>
          <p class="text-muted"><?=$this->lang->line('application_mask_help');?></p>
        </div>
        <div class="form-group">
            <label for="mask_agency"><?=$this->lang->line('application_agency');?></label>
            <input type="text" onkeypress="return inputBankAccount(this)" class="form-control" id="mask_agency" name="mask_agency" placeholder="<?=$this->lang->line('application_enter_mask_bank_number')?>" autocomplete="off" required <?=$updateBank == true ? '' : 'disabled'?>>
            <p class="text-muted"><?=$this->lang->line('application_mask_help_agency');?></p>
          
          </div>
          <div class="form-group ">
            <label for="mask_account"><?=$this->lang->line('application_bank_account');?></label>
            <input type="text" class="form-control" id="mask_account" name="mask_account" placeholder="<?=$this->lang->line('application_enter_mask_bank_agency')?>" autocomplete="off" <?=$updateBank == true ? '' : 'disabled'?>>
            <p class="text-muted"><?=$this->lang->line('application_mask_help_account');?></p>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <?php if ($updateBank): ?>
            <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
            <?php endif;?>
        </div>

      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script type="text/javascript" src="<?=HOMEPATH;?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";
// para csrf 
var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>',
    csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
$(document).ready(function() {

  $('#mask_agency').keyup(function() {
    $(this).val(this.value.replace(/[^0-9-D]/g, ""));
  });
  $('#mask_account').keyup(function() {
    $(this).val(this.value.replace(/[^0-9-a-zA-Z]/g, ""));
  });
  // initialize the datatable
  manageTable = $('#manageTable').DataTable({
	// "processing": true,
    "serverSide": true,
    "sortable": true,
    "scrollX": true,
    "serverMethod": "post",
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'banks/fetchData',
      data: { [csrfName]: csrfHash}
    }),
    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" }
  });
  $( "#addModal" ).on('shown.bs.modal', function(event){
  });

});
function openModal(element){
  if(element){
    $('#createForm').attr('action', "<?php echo base_url('banks/upload') ?>");
    $('#modal_title').text("<?=$this->lang->line('application_title_edit_bank');?>")
    $.get("<?=base_url('banks/bank_info/')?>"+element,function(data, status){
      // alert("Data: " + data + "\nStatus: " + status);
      data=JSON.parse(data);
      // console.log();
      $('#bank_id').val(data.id);
      $('#bank_name').val(data.name);
      $('#mask_agency').val(data.mask_agency);
      $('#mask_account').val(data.mask_account);
      $('#bank_number').val(data.number);
      $('#active').val(data.active).change();
    });
  }else{
    $("#createForm").trigger('reset');
    $('#createForm').attr('action', "<?php echo base_url('banks/create') ?>");
    $('#modal_title').text("<?=$this->lang->line('application_title_create_bank');?>")
  }
}
function characterLimit(object) {
  var limit = object.getAttribute('maxlength');
  var attribute = object.getAttribute('id');
  let value=$("#"+attribute).val();
  if(value.length>limit){
    value=value.substring(0,limit);
    $("#"+attribute).val(value);
  }
  $('#char_' + attribute).text(`<?=$this->lang->line('application_type_char');?> ${value.length}/${limit}`);

}

</script>
