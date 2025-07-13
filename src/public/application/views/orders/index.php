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
  
	.bootstrap-select .dropdown-toggle .filter-option {
		background-color: white !important;
	}
	.bootstrap-select .dropdown-menu li a {
		 border: 1px solid gray;
	}
  .daterangepicker .ranges li:hover {
      color: #000;
  }

</style>
	  
	<?php
        $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data);
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

    <div class="box box-primary mt-2">
      <div class="box-body">
	    <button class="pull-right btn btn-primary" id="exportOrders"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_data_export')?></button>
      </div>
    </div>
     <div class="box box-primary">
       <div class="box-body" id="filters">
        <div class="row d-flex flex-wrap">
          <div class="form-group col-md-3" <?php echo ($show_marketplace_order_id ==1) ? '': 'style="display:none;"' ;?>>
            <label for="buscapedidomkt" class="normal"><?= $this->lang->line('application_order_marketplace') ?></label>
            <div class="input-group">
              <input type="search" id="buscapedidomkt" onchange="buscapedido()" class="form-control" placeholder="<?= $this->lang->line('application_order_marketplace') ?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>
          
          <div class="form-group col-md-3">
            <label for="buscapedido" class="normal"><?= $this->lang->line('application_order_number') ?></label>
            <div class="input-group">
              <input type="search" id="buscapedido" onchange="buscapedido()" class="form-control" placeholder="<?= $this->lang->line('application_purchase_id') ?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>
          
          <div class="form-group col-md-3" style="<?= (count($stores_filter) > 1) ? "" : "display: none;"?>">
            <div class="">
              <label for="buscalojas" class="normal"><?= $this->lang->line('application_search_for_store') ?></label>
              <select class="form-control selectpicker show-tick" id="buscalojas" name ="loja[]" onchange="buscapedido()" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                <?php foreach ($stores_filter as $store_filter) { ?>
                <option value="<?= $store_filter['id'] ?>"><?= $store_filter['name'] ?></option>
           		<?php } ?>
                
              </select>
            </div>
          </div>

          <div class="form-group col-md-3">
            <div class="input-group col-md-12">
              <label for="buscaentrega" class="normal"><?= $this->lang->line('application_delivery_type') ?></label>
              <select class="form-control" id="buscaentrega" onchange="buscapedido()">
                <option value=""><?=$this->lang->line('application_all');?></option>
                <option value="CORREIOS"><?= $this->lang->line('application_post_offices') ?></option>
                <option value="TRANSPORTADORA"><?= $this->lang->line('application_ship_company') ?></option>
                <option value="RETIRADA"><?= $this->lang->line('application_pick_up_in') ?></option>
              </select>
            </div>
          </div>

          <div class="form-group col-md-3">
              <label for="buscastatus" class="normal"><?= $this->lang->line('application_order_status') ?></label>
              <select class="form-control select2" name="buscastatus" id="buscastatus" onchange="buscapedido()">
                <option value=""><?=$this->lang->line('application_all');?></option>
                <option value="1"><?=$this->lang->line('application_order_1');?></option>
                <option value="2"><?=$this->lang->line('application_order_2');?></option>
                <option <?= set_select('orders_waiting_invoice', 'true') ?> value="3"><?=$this->lang->line('application_order_3');?></option>
                <option <?= set_select('order_awaiting_collection', 'true') ?> value="4"><?=$this->lang->line('application_order_4');?></option>
                <option <?= set_select('orders_in_transport', 'true') ?> value="5"><?=$this->lang->line('application_order_5');?></option>
                <option value="59"><?=$this->lang->line('application_loss_return'); /* application_order_59 */ ?></option>
                <option <?= set_select('orders_delivered', 'true') ?> value="6"><?=$this->lang->line('application_order_6');?></option>
                <option value="57"><?=$this->lang->line('application_order_57');?></option>
                <option value="9"><?=$this->lang->line('application_order_9');?></option>
                <option value="9"><?=$this->lang->line('application_order_9');?></option>
                <option value="50"><?=$this->lang->line('application_order_50');?></option>
                <option value="58"><?=$this->lang->line('application_order_58');?></option>
                <option value="80"><?=$this->lang->line('application_order_80');?></option>
                <option value="90"><?=$this->lang->line('application_order_90');?></option>
                <option value="95"><?=$this->lang->line('application_order_95');?></option>
                <option value="97"><?=$this->lang->line('application_order_97');?></option>
                <option value="96"><?=$this->lang->line('application_order_96');?></option>
                <option value="returned_product">Devolvido</option>
                <option value="return_product">Em devolução</option>
                <option value="110"><?=$this->lang->line('application_order_110');?></option>
                <option value="111"><?=$this->lang->line('application_order_111');?></option>
              </select>
          </div>
          <div class="form-group col-md-3">
            <div class="input-group col-md-12">
              <label for="buscacpf_cnpj" class="normal"><?= $this->lang->line('application_cpf_cnpj_client') ?></label>
              <div class="input-group">
                <input type="search" id="buscacpf_cnpj" onchange="buscapedido()" class="form-control" placeholder="<?= $this->lang->line('application_cpf_cnpj_client_placeholder') ?>" aria-label="Search" aria-describedby="basic-addon1">
                <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
              </div>
            </div>
          </div>
            <?php if(!empty($phases)) { ?>
                <div class="form-group col-md-3">
                    <label for="search_phases" class="normal"><?= $this->lang->line('application_phase'); ?></label>
                    <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="search_phases" multiple="multiple" title="<?= $this->lang->line('application_select'); ?>" onchange="buscapedido()">
                        <option value="" disabled><?= $this->lang->line('application_select'); ?></option>
                        <?php foreach ($phases as $k => $v) { ?>
                            <option value="<?php echo $v['id'] ?>"><?php echo $v['name'] ?></option>
                        <?php } ?>
                    </select>
                </div>
            <?php }?>
        </div>
        <div class="row">
            <div class="col-md-3 pull-right text-right">
                <label  class="normal" style="display: block;">&nbsp; </label>
                <button type="button" onclick="clearFilters()" class=" btn btn-primary"> <i class="fa fa-eraser"></i> <?= $this->lang->line('application_clear') ?> </button>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-3">
                <div class="input-group col-md-12">
                    <label for="buscacpf_cnpj" class="normal"><?= $this->lang->line('application_product_added_date') ?></label>
                    <div class="input-group">
                        <input type="text" id="daterange_order" class="form-control">
                        <span class="input-group-addon " id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                    </div>
                </div>
            </div>
        </div>
       </div>
     </div>
        
        <div class="row"></div>
<!--         <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#filterModal"><i class="fa fa-filter"></i> --><?php //=$this->lang->line('application_change_filter')?><!--</button>-->
        
        <div class="box box-primary">
          <div class="box-body">
            <!-- table id="manageTable" class="table table-bordered table-striped" -->
            <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>
              	<?php if ($show_marketplace_order_id==1) { ?>
              	<th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_order_marketplace') ?>" data-container="body" ><?=$this->lang->line('application_order_marketplace');?></th>
                <?php } ?>
                <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_order_number') ?>" data-container="body"><?=$this->lang->line('application_order');?></th>
                <!-- <th data-toggle="tooltip" data-placement="top" title="Número do pedido no marketplace" data-container="body"><?=$this->lang->line('application_order_marketplace');?></th> -->
                <!--- <th><?=$this->lang->line('application_order_bling');?></th>  -->
                <!-- <th data-toggle="tooltip" data-placement="top" title="Nome da loja" data-container="body"><?=$this->lang->line('application_store');?></th> -->
                <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_client_name') ?>" data-container="body"><?=$this->lang->line('application_name');?></th>
                <!-- <th><?=$this->lang->line('application_cpf');?>/<?=$this->lang->line('application_cnpj');?></th> -->
                <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_date_inclusion_platform') ?>" data-container="body"><?=$this->lang->line('application_included');?></th>
                <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_date_order_approved') ?>" data-container="body"><?=$this->lang->line('application_approved');?></th>
                <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_deadline_order_shipped') ?>" data-container="body"><?=$this->lang->line('application_dispatched');?></th>
                <th data-toggle="tooltip" data-placement="top" class="text-center" title="<?= $this->lang->line('application_deadline_order_must_delivered') ?>" data-container="body"><?=$this->lang->line('application_promised_marketplace');?></th>
                <th data-toggle="tooltip" data-placement="top" class="text-center" title="<?= $this->lang->line('application_deadline_order_must_delivered') ?>" data-container="body"> <?=$this->lang->line('application_promised_lead_time');?></th>
                <!-- <th><?=$this->lang->line('application_items');?></th> -->
                <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_total_order_amount') ?>" data-container="body"><?=$this->lang->line('application_total_amount');?></th>
                <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_delivery_type') ?>" data-container="body"><?=$this->lang->line('application_delivery');?></th>
                <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_store') ?>" data-container="body"><?=$this->lang->line('application_store');?></th>
                <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_order_status') ?>" data-container="body"><?=$this->lang->line('application_status');?></th>
                <th data-toggle="tooltip" data-placement="top" title="<?= $this->lang->line('application_transportation_status') ?>" data-container="body"><?=$this->lang->line('application_transportation_status');?></th>
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

<div class="modal fade" tabindex="-1" role="dialog" id="filterModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?=$this->lang->line('application_set_filters');?></span></h4>
		</div>
	    <form role="form" action="<?php echo base_url('orders/filter') ?>" method="post" id="filterForm">
	    <div class="modal-body">
		    <?php
			// $filters = $this->data['filters'];
		    $filters = get_instance()->data['filters'];
			foreach ($filters as $k => $v) { ?>
			<div class="row">
				<div class="form-group col-md-3">
			    	<label><?=$v['nm'];?></label>
				</div>
				<div class="form-group col-md-3">
			        <div>
			          <select type="text" class="form-control" id="<?=$k; ?>_op" name="<?=$k; ?>_op">
			            <option value="0" ><?=$this->lang->line('application_codition')?></option>
			        <?php foreach ($v['op'] as $op) { ?>}    
			            <option value="<?=$op; ?>" ><?=$op; ?></option>
			        <?php } ?>    
			          </select>
			        </div>
				</div>
				<div class="form-group col-md-5">
					<div>
					    <input type="text" class="form-control" id="<?=$k; ?>" name="<?=$k; ?>" placeholder="<?=$this->lang->line('application_enter_value')?>"  />
					</div>
				</div>
				<!--- <div class="form-group col-md-1">+</div> --->	
			</div>
            <?php
                }
            ?>
            <div class="row">
                <div class="form-group col-md-12">
                    <label class="col-md-6"><input type="checkbox" class="minimal" name="orders_pending_action" id="orders_pending_action" value="true" <?=set_checkbox('orders_pending_action', 'true')?>> <?=$this->lang->line('application_orders_pending_action')?></label>
                    <label class="col-md-6"><input type="checkbox" class="minimal" name="orders_waiting_invoice" id="orders_waiting_invoice" value="true" <?=set_checkbox('orders_waiting_invoice', 'true')?>> <?=$this->lang->line('application_orders_waiting_invoice')?></label>
                    <label class="col-md-6"><input type="checkbox" class="minimal" name="orders_delivered" id="orders_delivered" value="true" <?=set_checkbox('orders_delivered', 'true')?>> <?=$this->lang->line('application_orders_delivered')?></label>
                    <label class="col-md-6"><input type="checkbox" class="minimal" name="orders_canceled" id="orders_canceled" value="true" <?=set_checkbox('orders_canceled', 'true')?>> <?=$this->lang->line('application_orders_canceled')?></label>
                    <label class="col-md-6"><input type="checkbox" class="minimal" name="orders_last_post_day" id="orders_last_post_day" value="true" <?=set_checkbox('orders_last_post_day', 'true')?>> <?=$this->lang->line('application_orders_last_post_day')?></label>
                    <label class="col-md-6"><input type="checkbox" class="minimal" name="orders_delayed_post" id="orders_delayed_post" value="true" <?=set_checkbox('orders_delayed_post', 'true')?>> <?=$this->lang->line('application_orders_delayed_post')?></label>
                    <label class="col-md-6"><input type="checkbox" class="minimal" name="order_awaiting_collection" id="order_awaiting_collection" value="true" <?=set_checkbox('order_awaiting_collection', 'true')?>> <?=$this->lang->line('application_order_awaiting_collection')?></label>
                    <label class="col-md-6"><input type="checkbox" class="minimal" name="orders_in_transport" id="orders_in_transport" value="true" <?=set_checkbox('orders_in_transport', 'true')?>> <?=$this->lang->line('application_orders') . ' ' . $this->lang->line('application_order_5')?></label>
                	<label class="col-md-6"><input type="checkbox" class="minimal" name="exchange_orders" id="exchange_orders" value="true" <?=set_checkbox('exchange_orders', 'true')?>> <?=$this->lang->line('application_exchange_orders')?></label>
                </div>
            </div>
		</div> <!-- modal-body -->
	    <div class="modal-footer">
          <a href="<?=base_url('orders')?>" class="btn btn-default"><?=$this->lang->line('application_clear');?></a>
	      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


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

<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";
let days_interval_range_date = 30;

$(document).ready(function() {

    $("#mainOrdersNav").addClass('active');
    $("#manageOrdersNav").addClass('active');
    $(".select2").select2();

    $('[onclick="clearFilters()"]').closest('.pull-right.text-right').attr('style', $('#filters .form-group:visible').length <= 5 ? 'position: absolute; right: 0; bottom: 40px;' : 'position: absolute; right: 0; bottom: 90px;')

    $('#daterange_order').daterangepicker({
        showDropdowns: true,
        autoApply: false,
        opens: 'right',
        startDate: moment().startOf().subtract(days_interval_range_date, 'day'),
        endDate: moment().startOf(),
        maxDate: moment().startOf(),
        locale: {
            format: 'DD/MM/YYYY',
            separator: " - ",
            applyLabel: "<?=$this->lang->line('application_apply')?>",
            cancelLabel: "<?=$this->lang->line('application_cancel')?>",
            fromLabel: "<?=$this->lang->line('application_price_from')?>",
            toLabel: "<?=$this->lang->line('application_until')?>",
            customRangeLabel: "<?=$this->lang->line('application_custom')?>",
            daysOfWeek: [
                "<?=$this->lang->line('application_abbreviated_Sun')?>",
                "<?=$this->lang->line('application_abbreviated_Mon')?>",
                "<?=$this->lang->line('application_abbreviated_Tue')?>",
                "<?=$this->lang->line('application_abbreviated_Wed')?>",
                "<?=$this->lang->line('application_abbreviated_Thu')?>",
                "<?=$this->lang->line('application_abbreviated_Fri')?>",
                "<?=$this->lang->line('application_abbreviated_Sat')?>"
            ],
            monthNames: [
                "<?=$this->lang->line('application_Jan')?>",
                "<?=$this->lang->line('application_Feb')?>",
                "<?=$this->lang->line('application_Mar')?>",
                "<?=$this->lang->line('application_Apr')?>",
                "<?=$this->lang->line('application_May')?>",
                "<?=$this->lang->line('application_Jun')?>",
                "<?=$this->lang->line('application_Jul')?>",
                "<?=$this->lang->line('application_Aug')?>",
                "<?=$this->lang->line('application_Sep')?>",
                "<?=$this->lang->line('application_Oct')?>",
                "<?=$this->lang->line('application_Nov')?>",
                "<?=$this->lang->line('application_Dec')?>"
            ]
        },
        ranges: {
            '<?=$this->lang->line('application_today')?>': [moment(), moment()],
            '<?=$this->lang->line('application_yesterday')?>': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            '<?=$this->lang->line('application_last_7_days')?>': [moment().subtract(6, 'days'), moment()],
            '<?=$this->lang->line('application_last_30_days')?>': [moment().subtract(29, 'days'), moment()],
            '<?=$this->lang->line('application_this_month')?>': [moment().startOf('month'), moment().endOf('month')],
            '<?=$this->lang->line('application_last_month')?>': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    buscapedido();
});

$('#daterange_order').on('apply.daterangepicker', function(ev, picker) {
    buscapedido();
});

function buscapedido(){
    let date_start      = $('#daterange_order').data('daterangepicker').startDate.format('YYYY-MM-DD');
    let date_end        = $('#daterange_order').data('daterangepicker').endDate.format('YYYY-MM-DD');
    let pedido          = $('#buscapedido').val();
    let entrega         = $('#buscaentrega').val();
    let status          = $('#buscastatus').val();
    let nummkt          = $('#buscapedidomkt').val();
    let buscacpf_cnpj   = $('#buscacpf_cnpj').val();
    let phases          = [];
    $('#search_phases  option:selected').each(function() {
        if ($(this).val() !== '') {
            phases.push($(this).val());
        }
    });
  
    let lojas = [];
    $('#buscalojas  option:selected').each(function() {
        lojas.push($(this).val());
    });

    if (!lojas.length) {
        lojas = ''
    }
  
    if (typeof manageTable === 'object' && manageTable !== null) {
  	    manageTable.destroy();
    }

    manageTable = $('#manageTable').DataTable({
        "order": [[1, "asc"]],
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
                pedido: pedido,
                entrega: entrega,
                lojas: lojas,
                status: status,
                nummkt: nummkt,
                internal: false,
                buscacpf_cnpj :buscacpf_cnpj,
                phases: phases,
                date_start,
                date_end
            },
            pages: 2, // number of pages to cache
            while: $("input[name='loja[]']").each(function(){$(this).prop('disabled', true)}),
            success: setTimeout(function(){$("input[name='loja[]']").each(function(){$(this).prop('disabled', false)})}, 1500)
        })
    });
}

function clearFilters(){
    $('#buscapedidomkt').val('');
    $('#buscapedido').val('');
    $('#buscaentrega').val('');
    $('#search_phases').val('');
    $('#buscalojas').val('');
    $('#buscastatus').val('');
    $('#buscacpf_cnpj').val('');

    $('#buscalojas').selectpicker('val', '');
    $('#search_phases').selectpicker('val', '');

    $('#daterange_order').data('daterangepicker').setStartDate(moment().startOf().subtract(days_interval_range_date, 'day'));
    $('#daterange_order').data('daterangepicker').setEndDate(moment().startOf());

    buscapedido();
}

// remove functions 
function removeFunc(id,name)
{
  if(id) {
	document.getElementById("deleteordername").innerHTML= ': '+name;  
    $("#removeForm").on('submit', function() {

      var form = $(this);

      // remove the text-danger
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

$('#exportOrders').on('click', function(){

    let date_start  = moment($('#daterange_order').data('daterangepicker').startDate.format('YYYY-MM-DD'));
    let date_end    = moment($('#daterange_order').data('daterangepicker').endDate.format('YYYY-MM-DD'));
    let days_interval_range_date_export = 90;
    if (date_end.diff(date_start, 'days') > days_interval_range_date_export) {
        Swal.fire({
            icon: 'error',
            title: 'Não foi possível exportar os pedidos.',
            html: `É possível realizar a exportação de pedidos de apenas <b>${days_interval_range_date_export} dias por planilha</b>.<br>Verifique novamente os campos de data inicial e data final para que esteja dentro deste limite e tente novamente.`
        });
        return false;
    }

    window.location.href = "<?=base_url('export/ordersxls') ?>";
});
</script>
