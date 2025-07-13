<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	  
  <?php $data['pageinfo'] = "application_manage"; $data['page_now'] = 'file_process';  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="messages"></div>

        <?php if($this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?=$this->session->flashdata('success')?>
          </div>
        <?php elseif($this->session->flashdata('error')): ?>
          <div class="alert alert-error alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?=$this->session->flashdata('error')?>
          </div>
        <?php endif?>

        <div class="box box-primary" id="collapseFilter">
          <div class="box-body">
              <h4 class="mt-0">Filtro</h4>
              <!--<div class="col-md-2 form-group no-padding">
                  <select class="form-control" id="filter_type" name="filter_type">
                      <option value="Shippingcompany">Tabela de Frete</option>
                  </select>
              </div>-->
              <div class="col-md-3 form-group no-padding" style="<?=(count($stores_filter) > 1) ? "" : "display: none;"?>">
                  <select class="form-control selectpicker show-tick" id="buscalojas" name="loja[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_search_for_store');?>">
                      <?php foreach ((array)$stores_filter as $store_filter) {?>
                          <option value="<?=$store_filter['id']?>" <?=(count($stores_filter) == 1) ? "selected" : ""?>><?=$store_filter['name']?></option>
                      <?php }?>
                  </select>
              </div>
          </div>
        </div>

        <div class="box box-primary">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_file');?></th>
                <th><?=$this->lang->line('application_status');?></th>
                <th><?=$this->lang->line('application_user');?></th>
                <th><?=$this->lang->line('application_shipping_company');?></th>
                <th><?=$this->lang->line('application_store');?></th>
                <th><?=$this->lang->line('application_date_create');?></th>
                <th><?=$this->lang->line('application_action');?></th>
              </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="viewStatusFile">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_status_file_process');?><span id="deletecategoryname"></span></h4>
      </div>
      <div class="modal-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript" src="<?=HOMEPATH?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
let manageTable;
let base_url = "<?=base_url()?>";

$(document).ready(function() {
    $("#mainLogisticsNav").addClass('active');
    $("#navFileProcess").addClass('active');
    getTable();
});

$('#buscalojas').on('change', function(){
    getTable();
});

const getTable = () => {

    let type = $('#filter_type').val();
    
    if (typeof type === 'undefined') {
        type = 'Shippingcompany';
    }
    let stores = $('#buscalojas').val();

    if (typeof manageTable !== 'undefined')
        manageTable.destroy();

    if (!(stores.length)) {
        stores = '';
    }

    // initialize the datatable
    manageTable = $('#manageTable').DataTable({
        "language": {"url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
        "processing": true,
        "serverSide": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url +'FileProcess/fetchFileProcessShippingCompanyData',
            data: { stores, type },
            pages: 2
        }),
        "order": [[ 0, 'desc' ]]
    });
}

$(document).on('click', '.view-status-file', function(){
    let type = $('#filter_type').val();
    let idFile = $(this).attr('file-id');
    let body_content = $(this).attr('body-content');

    if (typeof type === 'undefined') {
        type = 'Shippingcompany';
    }

    $.post(`${base_url}/FileProcess/getResponseFile`, {type, idFile}, response => {
        var messages = response.hasOwnProperty('errors') && response.errors.length > 0
            ? response.errors : (
                response.hasOwnProperty('messages') && response.messages.length > 0 ? response.messages : ''
            );
        // var ul = document.createElement("ul");
        // messages.forEach(function(item) {
        //     var li = document.createElement("li");
        //     li.textContent = item;
        //     ul.appendChild(li);
        // });
        $("#viewStatusFile").modal('show').find('.modal-body').html(messages);
    });
});
</script>
