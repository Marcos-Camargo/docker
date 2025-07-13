<!--
SW Serviços de Informática 2019

Listar Pedidos

Obs:
cada usuario so pode ver pedidos da sua empresa.
Agencias podem ver todos os pedidos das suas empresas
Admin pode ver todas as empresas e agencias

-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<script type='text/javascript' src="https://rawgit.com/RobinHerbots/jquery.inputmask/3.x/dist/jquery.inputmask.bundle.js"></script>
<style>
  .filters {
    position: relative;
    top: 30px;
    /* left: 170px; */
    display: flex;
    justify-content: center;
    /* align-items: flex-end; */
    width: 70%;
    margin: auto;
  }

  .normal {
    font-weight: normal;
  }
</style>
	  
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
        <div class="pull-right">
 		   <a class=" btn btn-primary" href="<?php echo base_url('export/ordersxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_data_export')?></a>
           <a class=" btn btn-primary" style="display: none" id="exportAddressPickUp" href="<?php echo base_url('export/AddressPickUpXls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_data_export') . ' ' . $this->lang->line('application_order_58')?></a>
        </div>
        <br />
        <br />
        <div class="box box-primary">
            <div class="box-body">
             <div class="row">
              <div class="col-md-3">
                <label for="buscapedido" class="normal">Pedido Conecta Lá</label>
                <div class="input-group">
                  <input type="search" id="buscapedido" onchange="buscapedido()" class="form-control" placeholder="Nº do pedido" aria-label="Search" aria-describedby="basic-addon1">
                  <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                </div>
              </div>

              <div class="col-md-3">
                <label for="buscaloja" class="normal">Buscar por Loja</label>
                <div class="input-group">
                  <input type="search" id="buscaloja" onchange="buscapedido()" class="form-control" placeholder="Digite a loja" aria-label="Search" aria-describedby="basic-addon1">
                  <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                </div>
              </div>

              <div class="col-md-3">
                  <label for="buscaentrega" class="normal">Tipo de entrega</label>
                  <select class="form-control" id="buscaentrega" onchange="buscapedido()">
                    <option value=""><?=$this->lang->line('application_all');?></option>
                    <option value="RETIRADA"><?= $this->lang->line('application_pick_up_in') ?></option>
                    <?php foreach ($freights_filter as $freight_filter) {
                      if (trim($freight_filter['ship_company'])) { ?>
                        <option value="<?= $freight_filter['ship_company'] ?>"><?= $freight_filter['ship_company'] ?></option>
                      <?php }
                    } ?>
                  </select>
              </div>

              <div class="col-md-3">
                <label for="buscastatus" class="normal">Status de pedido</label>
                <select class="form-control select2" id="buscastatus" onchange="buscapedido()">
                  <option value=""><?=$this->lang->line('application_all');?></option>
                  <option value="1"><?=$this->lang->line('application_order_1');?></option>
                  <option value="2"><?=$this->lang->line('application_order_2');?></option>
                  <option value="3"><?=$this->lang->line('application_order_3');?></option>
                  <option value="4"><?=$this->lang->line('application_order_4');?></option>
                  <option value="5"><?=$this->lang->line('application_order_5');?></option>
                  <option value="59"><?=$this->lang->line('application_loss_return'); /* application_order_59 */ ?></option>
                  <option value="6"><?=$this->lang->line('application_order_6');?></option>
                  <option value="57"><?=$this->lang->line('application_order_57');?></option>
                  <option value="9"><?=$this->lang->line('application_order_9');?></option>
                  <option value="return_product">Em devolução</option>
                  <option value="returned_product">Devolvido</option>
                  <option value="50"><?=$this->lang->line('application_order_50');?></option>
                  <option value="58"><?=$this->lang->line('application_order_58');?></option>
                  <option value="80"><?=$this->lang->line('application_order_80');?></option>
                  <option value="90"><?=$this->lang->line('application_order_90');?></option>
                  <option value="95"><?=$this->lang->line('application_order_95');?></option>
                  <option value="97"><?=$this->lang->line('application_order_97');?></option>
                  <option value="96"><?=$this->lang->line('application_order_96');?></option>
                  <option value="98"><?=$this->lang->line('application_order_98');?></option>
                  <option value="99"><?=$this->lang->line('application_order_99');?></option>
                  <option value="101"><?=$this->lang->line('application_order_101');?></option>
                </select>
              </div>
             </div>
             <div class="row">
              <div class="col-md-3">
                <label for="buscapedidomkt" class="normal">Pedido Marketplace</label>
                <div class="input-group">
                  <input type="search" id="buscapedidomkt" onchange="buscapedido()" class="form-control" placeholder="Nº do pedido" aria-label="Search" aria-describedby="basic-addon1">
                  <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                </div>
              </div>

              <div class="col-md-3">
                <label for="incidence" class="normal"><?=$this->lang->line('application_orders_with_incidence');?></label>
                <select class="form-control" id="incidence" onchange="buscapedido()">
                    <option value="0"><?=$this->lang->line('application_all')?></option>
                    <option value="1"><?=$this->lang->line('application_with_incidence')?></option>
                    <option value="2"><?=$this->lang->line('application_without_incidence')?></option>
                </select>
              </div>

              <div class="col-md-3">
                <label for="marketplace" class="normal"><?=$this->lang->line('application_marketplace')?></label>
                <select class="form-control" id="marketplace" onchange="buscapedido()">
                    <option value=""><?=$this->lang->line('application_all')?></option>
                    <?php
                    foreach ($marketplaces as $mkt)
                        echo "<option value='{$mkt['int_to']}'>{$mkt['name']}</option>";
                    ?>
                </select>
              </div>
              <div class="col-md-3">
                <div class="input-group col-md-12" >
                  <label for="buscacpf_cnpj" class="normal"><?= $this->lang->line('application_cpf_cnpj_client') ?></label>
                  <div class="input-group">
                    <input type="search" id="buscacpf_cnpj" onchange="buscapedido()" class="form-control" placeholder="<?= $this->lang->line('application_cpf_cnpj_client_placeholder') ?>" aria-label="Search" aria-describedby="basic-addon1">
                    <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                  </div>
                </div>
              </div>
                 <?php if(!empty($phases)) { ?>
                     <div class="col-md-3">
                         <label for="search_phases" class="normal"><?= $this->lang->line('application_phase'); ?></label>
                         <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="search_phases" multiple="multiple" title="<?= $this->lang->line('application_select'); ?>" onchange="buscapedido()">
                             <option value="" disabled><?= $this->lang->line('application_select'); ?></option>
                             <?php foreach ($phases as $k => $v) { ?>
                                 <option value="<?php echo $v['id'] ?>"><?php echo $v['name'] ?></option>
                             <?php } ?>
                         </select>
                     </div>
                 <?php }?>
              <div class="col-md-3 text-right pull-right">
                  <label  class="normal" style="display: block;">&nbsp; </label>
                  <button type="button" onclick="clearFilters()" class="btn btn-primary"> <i class="fa fa-eraser"></i> Limpar </button>
              </div>
             </div>
            </div>
        </div>
        
        <div class="row mt-2"></div>
        
        <div class="box box-primary">
          <div class="box-body">
            <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>
                <th data-toggle="tooltip" data-placement="top" title="Número do pedido na Conecta Lá" data-container="body"><?=$this->lang->line('application_conecta_orders');?></th>
                <th data-toggle="tooltip" data-placement="top" title="Número do pedido no marketplace" data-container="body"><?=$this->lang->line('application_order_marketplace');?></th>
                <!--- <th><?=$this->lang->line('application_order_bling');?></th>  -->
                <th data-toggle="tooltip" data-placement="top" title="Nome da loja" data-container="body"><?=$this->lang->line('application_store');?></th>
                <th><?=$this->lang->line('application_client');?></th>
                <!-- <th><?=$this->lang->line('application_cpf');?>/<?=$this->lang->line('application_cnpj');?></th> -->
                <th data-toggle="tooltip" data-placement="top" title="Data que o pedido foi incluído na plataforma" data-container="body"><?=$this->lang->line('application_included');?></th>
                <th data-toggle="tooltip" data-placement="top" title="Data que o pedido foi aprovado" data-container="body"><?=$this->lang->line('application_approved');?></th>
                <th data-toggle="tooltip" data-placement="top" title="Data limite que o pedido deverá ser expedido" data-container="body"><?=$this->lang->line('application_dispatched');?></th>
                <!-- <th data-toggle="tooltip" data-placement="top" title="Data limite que o pedido deverá ser entregue" data-container="body"><?=$this->lang->line('application_promised');?></th> -->
                <th data-toggle="tooltip" data-placement="top" class="text-center" title="Data limite que o pedido deverá ser entregue de acordo com o markeplace" data-container="body"><?=$this->lang->line('application_promised_marketplace');?></th>
                <th data-toggle="tooltip" data-placement="top" class="text-center" title="Data limite que o pedido deverá ser entregue de acordo com a logística" data-container="body"><?=$this->lang->line('application_promised_lead_time');?></th>
                <!-- <th><?=$this->lang->line('application_items');?></th> -->
                <th data-toggle="tooltip" data-placement="top" title="Valor total do pedido" data-container="body"><?=$this->lang->line('application_total_amount');?></th>
                <th data-toggle="tooltip" data-placement="top" title="Tipo de entrega" data-container="body"><?=$this->lang->line('application_delivery');?></th>
                <th data-toggle="tooltip" data-placement="top" title="Status do pedido" data-container="body"><?=$this->lang->line('application_status');?></th>
                <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_transportation_status') ?>" data-container="body"><?=$this->lang->line('application_transportation_status');?></th>
                <?php if(in_array('updateOrder', $user_permission) || in_array('viewOrder', $user_permission) || in_array('deleteOrder', $user_permission)): ?>
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

<?php if(in_array('deleteOrder', $user_permission)): ?>
<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="removeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?=$this->lang->line('application_delete_order');?><span id="deleteordername"></span></h4>
      </div>

      <form role="form" action="<?php echo base_url('orders/remove') ?>" method="post" id="removeForm">
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

<!-- <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script> -->
<script type='text/javascript' src="https://rawgit.com/RobinHerbots/jquery.inputmask/3.x/dist/jquery.inputmask.bundle.js"></script>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">

var manageTable;
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {

  $("#mainOrdersNav").addClass('active');
  $("#manageOrdersNav").addClass('active');
  $(".select2").select2();

  buscapedido();
  $('input[type="checkbox"].minimal').iCheck({
      checkboxClass: 'icheckbox_minimal-blue',
      radioClass   : 'iradio_minimal-blue'
  });
  
  // $('#buscacpf_cnpj').mask('00.000.000/0000-00|000.000.000-00', {reverse: true});
});
function setMaskCpfCnpj(){
  var options =  {
    onKeyPress: function(cpf, e, field, options) { //Quando uma tecla for pressionada
        var masks = ['0000.000.000-00', '00.000.000/0000-01']; //Mascaras
        var mask = (cpf.length > 14) ? masks[1] : masks[0]; //Se for de tamanho 11, usa a 2 mascara
        $('#buscacpf_cnpj').mask(mask, options); //Sobrescreve a mascara
    },
    reverse:true
  };

  $('#buscacpf_cnpj').mask('0000.000.000-00', options); //Aplica a mascara
}
function buscapedido(){
  let pedido        = $('#buscapedido').val();
  let entrega       = $('#buscaentrega').val();
  let status        = $('#buscastatus').val();
  let lojas         = $('#buscaloja').val();
  let nummkt        = $('#buscapedidomkt').val();
  let incidence     = $('#incidence').val();
  let marketplace   = $('#marketplace').val();
  let buscacpf_cnpj  = $('#buscacpf_cnpj').unmask().val();

  let phases = [];
  $('#search_phases  option:selected').each(function () {
      phases.push($(this).val());
  });

  setMaskCpfCnpj();
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
      url: base_url + 'orders/fetchOrdersData',
      data: {
          pedido,
          entrega,
          lojas,
          status,
          nummkt,
          internal: true,
          incidence,
          marketplace,
          buscacpf_cnpj :buscacpf_cnpj,
          phases: phases
      },
      pages: 2 // number of pages to cache
    })
  });
}

function clearFilters(){
  $('#buscapedidomkt').val('');
  $('#buscapedido').val('');
  $('#buscaentrega').val('');
  $('#buscaloja').val('');
  $('#search_phases').val('');
  $('#buscastatus').val('');
  $('#incidence').val(0);
  $('#marketplace').val('');
  buscapedido();
}

function removeFunc(id,name)
{
  if(id) {
	document.getElementById("deleteordername").innerHTML= ': '+name;  
    $("#removeForm").on('submit', function() {

      var form = $(this);

      $(".text-danger").remove();

      $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: { order_id:id }, 
        dataType: 'json',
        success:function(response) {

          manageTable.ajax.reload(null, false); 

          if(response.success === true) {
            $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
            '</div>');

            // hide the modal
            $("#removeModal").modal('hide');

          } else {

            $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
            '</div>'); 
          }
        }
      }); 

      return false;
    });
  }
}

$('#buscastatus').change(function(){
    if (parseInt($(this).val()) === 58) $('#exportAddressPickUp').slideDown('slow');
    else $('#exportAddressPickUp').slideUp('slow');
})


</script>
