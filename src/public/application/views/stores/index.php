<!--
SW Serviços de Informática 2019

Listar Lojas

Obs:
cada usuario so pode ver lojas da sua empresa.
Agencias podem ver todos as lojas das suas empresas
Admin pode ver lojas de todas as empresas e agencias

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

        <?php if(in_array('createStore', $user_permission)): ?>
          <a href="<?php echo base_url('stores/create') ?>" class="btn btn-primary"><?=$this->lang->line('application_add_store');?></a>
        <?php endif; ?>
        <a class="pull-right btn btn-primary" href="<?php echo base_url('export/LojaXls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_data_export')?></a>
          <br /> <br />

          <div class="box">
              <div class="box-body">
                  <div class="col-md-3">
                      <label for="buscanome" class="normal"><?= $this->lang->line('application_name'); ?></label>
                      <div class="input-group">
                          <input type="search" id="buscanome" onchange="buscaLoja()" class="form-control"
                                 placeholder="<?= $this->lang->line('application_name'); ?>" aria-label="Search"
                                 aria-describedby="basic-addon1">
                          <span class="input-group-addon" id=""><i class="fas fa-search text-grey"
                                                                   aria-hidden="true"></i></span>
                      </div>
                  </div>

                  <div class="col-md-3">
                      <label for="buscaempresa" class="normal"><?= $this->lang->line('application_company'); ?></label>
                      <div class="input-group">
                          <input type="search" id="buscaempresa" onchange="buscaLoja()" class="form-control"
                                 placeholder="<?= $this->lang->line('application_company'); ?>" aria-label="Search"
                                 aria-describedby="basic-addon1">
                          <span class="input-group-addon" id=""><i class="fas fa-search text-grey"
                                                                   aria-hidden="true"></i></span>
                      </div>
                  </div>

                  <div class="col-md-3">
                      <label for="buscarazao" class="normal"><?= $this->lang->line('application_raz_soc'); ?></label>
                      <div class="input-group">
                          <input type="search" id="buscarazao" onchange="buscaLoja()" class="form-control"
                                 placeholder="<?= $this->lang->line('application_raz_soc'); ?>" aria-label="Search"
                                 aria-describedby="basic-addon1">
                          <span class="input-group-addon" id=""><i class="fas fa-search text-grey"
                                                                   aria-hidden="true"></i></span>
                      </div>
                  </div>

                  <div class="col-md-3">
                      <label for="buscacnpj" class="normal"><?= $this->lang->line('application_cnpj'); ?></label>
                      <div class="input-group">
                          <input type="search" id="buscacnpj" onchange="buscaLoja()" class="form-control"
                                 placeholder="<?= $this->lang->line('application_cnpj'); ?>" aria-label="Search"
                                 aria-describedby="basic-addon1">
                          <span class="input-group-addon" id=""><i class="fas fa-search text-grey"
                                                                   aria-hidden="true"></i></span>
                      </div>
                  </div>

                  <div class="col-md-3 form-group mt-3"
                       style="<?= (count($sellers_filter) > 1) ? "margin-left:0px" : "display: none;" ?>">
                      <label for="buscavendedores"><?=$this->lang->line('application_seller');?></label>
                      <select class="form-control selectpicker show-tick" id="buscavendedores" name="vendedor[]"
                              onchange="buscaLoja()"
                              data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue"
                              data-selected-text-format="count > 1"
                              title="<?= $this->lang->line('application_search_for_sellers'); ?>">
                          <?php foreach ($sellers_filter as $seller_filter) { ?>
                              <option value="<?= $seller_filter['id'] ?>"><?= $seller_filter['desc'] ?></option>
                          <?php } ?>
                      </select>
                  </div>

                  <div class="col-md-5 row mt-3">
                      <div class="col-md-5">
                          <div class="">
                              <label for="buscastatus"
                                     class="normal"><?= $this->lang->line('application_status'); ?></label>
                              <select class="form-control" id="buscastatus" onchange="buscaLoja()">
                                  <option value=""><?= $this->lang->line('application_all'); ?></option>
                                  <option value="1" selected><?= $this->lang->line('application_active'); ?></option>
                                  <option value="2"><?= $this->lang->line('application_inactive'); ?></option>
                                  <option value="3"><?= $this->lang->line('application_in_negociation'); ?></option>
                                  <option value="4"><?= $this->lang->line('application_billet'); ?></option>
                                  <option value="5"><?= $this->lang->line('application_churn'); ?></option>
                                  <option value="6"><?= $this->lang->line('application_incomplete'); ?></option>
                                  <option value="7"><?= $this->lang->line('application_vacation_status'); ?></option>
                              </select>
                          </div>
                      </div>
                      <div class="col-md-3">
                          <div class="">
                              <label for="filtrocnpj"
                                     class="normal"><?= $this->lang->line('application_cnpj_fatured'); ?></label>
                              <select class="form-control" id="filtrocnpj" onchange="buscaLoja()">
                                  <option value="0"><?= $this->lang->line('application_cnpj_fatured_all'); ?></option>
                                  <option value="1"><?= $this->lang->line('application_cnpj_fatured_yes'); ?></option>
                                  <option value="2"><?= $this->lang->line('application_cnpj_fatured_not'); ?></option>
                              </select>
                          </div>
                      </div>
                      <div class="col-md-4">
                          <div class="">
                              <label for="filtrostatussubconta" class="normal"><?= $this->lang->line('application_gateway_subaccount_status'); ?></label>
                              <select class="form-control" id="filtrostatussubconta" onchange="buscaLoja()">
                                  <option value="">Todos</option>
                                  <?php
                                  foreach (\App\libraries\Enum\StoreSubaccountStatusFilterEnum::generateList() as $key => $value){
                                  ?>
                                    <option value="<?=$key;?>"><?=$value;?></option>
                                  <?php
                                  }
                                  ?>
                              </select>
                          </div>
                      </div>
                  </div>
                  <div class="col-md-2 mt-3">
                      <label class="normal" style="display: block;">&nbsp; </label>
                      <button type="button" onclick="clearFilters()" class="btn btn-primary"><i
                                  class="fa fa-eraser"></i> Limpar
                      </button>
                  </div>
              </div>
          </div>

          <div class="row"></div>

        <div class="box">
          <div class="box-body">
            <table id="manageTable" class="table table-bordered table-striped" cellspacing="0" style="border-collapse: collapse; width: 100%;">
              <thead>
              <tr>
                <th><?=$this->lang->line('application_id');?></th>
                <th><?=$this->lang->line('application_seller_l');?></th>
                <th><?=$this->lang->line('application_company');?></th>
                <th><?=$this->lang->line('application_date_create');?></th>
                <th><?=$this->lang->line('application_first_product');?></th>
                <th><?=$this->lang->line('application_first_sale');?></th>
                <th ><?=$this->lang->line('application_commission');?></th>
                <th ><?=$this->lang->line('application_store_status');?></th>
                <th ><?=$this->lang->line('application_store_subaccount_status');?></th>
                <?php if(in_array('updateStore', $user_permission) || in_array('deleteStore', $user_permission)): ?>
                  <th style="width:100px"><?=$this->lang->line('application_action');?></th>
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


<?php if(in_array('deleteStore', $user_permission)): ?>
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_store');?><span id="deletestorename"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('stores/remove') ?>" method="post" id="removeForm">
        <div class="modal-body">
          <p><?=$this->lang->line('messages_delete_message_confirm');?></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
          <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
        </div>
      </form>


    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {
    $('#buscacnpj').mask('99.999.999/9999-99');
  $("#storeNav").addClass('active');

	buscaLoja();
  // initialize the datatable 
//  manageTable = $('#manageTable').DataTable({
//	"language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>" },	  
//    "scrollX": true,
 //   'ajax': 'fetchStoresData',
//    'order': []
 //});


});

// remove functions 
// function removeFunc(id,name)
// {
//   if(id) {
// 	document.getElementById("deletestorename").innerHTML= ': '+name;  
//     $("#removeForm").on('submit', function() {

//       var form = $(this);

//       // remove the text-danger
//       $(".text-danger").remove();

//       $.ajax({
//         url: form.attr('action'),
//         type: form.attr('method'),
//         data: { store_id:id }, 
//         dataType: 'json',
//         success:function(response) {

//           manageTable.ajax.reload(null, false); 

//           if(response.success === true) {
//             $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
//               '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
//               '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
//             '</div>');

//             // hide the modal
//             $("#removeModal").modal('hide');

//           } else {

//             $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
//               '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
//               '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
//             '</div>'); 
//           }
//         }
//       }); 

//       return false;
//     });
//   }
// }

function buscaLoja(){
  let nome = $('#buscanome').val();
  let empresa = $('#buscaempresa').val();
  let razaosocial = $('#buscarazao').val();
  let status  = $('#buscastatus').val();
  let filtrocnpj  = $('#filtrocnpj').val();
    let filtrostatussubconta  = $('#filtrostatussubconta').val();
    let cnpj = $('#buscacnpj').val();
    var vendedores = [];
    $('#buscavendedores  option:selected').each(function () {
        vendedores.push($(this).val());
    });
    if (vendedores == '') {
        vendedores = ''
    }

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
      url: base_url + 'stores/fetchStoresData',
      data: {
          nome: nome,
          empresa: empresa,
          status: status,
          razaosocial: razaosocial,
          cnpj: cnpj,
          filtrocnpj: filtrocnpj,
          filtrostatussubconta: filtrostatussubconta,
          vendedores: vendedores
      },
      pages: 2
    })
  });
}

function clearFilters(){
  $('#buscanome').val('');
  $('#buscaempresa').val('');
  $('#buscastatus').val('');
  $('#buscarazao').val('');
  $('#buscacnpj').val('');
  $('#filtrostatussubconta').val('');
  $('#filtrocnpj').val(0);
  buscaLoja();
  
}

</script>
