<!--

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

</style>

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>
	  
	<?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box)
   <!--  <div class="row">
      <div class="col-md-12 col-xs-12"> -->
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

        <div class="">
        	
          <div class="col-md-2">
            <label for="buscasku" class="normal"><?=$this->lang->line('application_sku');?></label>
            <div class="input-group">
              <input type="search" id="buscasku" onchange="buscaProduto()" class="form-control" placeholder="<?=$this->lang->line('application_sku');?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>
          
		  <div class="col-md-4">
            <label for="buscanome" class="normal"><?=$this->lang->line('application_name');?></label>
            <div class="input-group">
              <input type="search" id="buscanome" onchange="buscaProduto()" class="form-control" placeholder="<?=$this->lang->line('application_name');?>" aria-label="Search" aria-describedby="basic-addon1">
              <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
            </div>
          </div>

          <div class="col-md-2">
            <div class="input-group" >
              <label for="buscastatus" class="normal"><?=$this->lang->line('application_status');?></label>
              <select class="form-control" id="buscastatus" onchange="buscaProduto()">
                <option value=""><?=$this->lang->line('application_select');?></option>
                <option value="1" selected><?=$this->lang->line('application_active');?></option>
                <option value="2"><?=$this->lang->line('application_inactive');?></option>
              </select>
            </div>
          </div>
          
          <div class="col-md-2">
            <div class="input-group" >
              <label for="buscacompleto" class="normal"><?=$this->lang->line('application_product_complete');?></label>
              <select class="form-control" id="buscacompleto" onchange="buscaProduto()">
                <option value=""><?=$this->lang->line('application_select');?></option>
                <option value="2" selected><?=$this->lang->line('application_complete');?></option>
                <option value="1"><?=$this->lang->line('application_incomplete');?></option>
              </select>
            </div>
          </div>
          
          <div class="col-md-2">
            <div class="input-group" >
              <label for="buscaestoque" class="normal"><?=$this->lang->line('application_stock');?></label>
              <select class="form-control" id="buscaestoque" onchange="buscaProduto()">
                <option value=""><?=$this->lang->line('application_select');?></option>
                <option value="1" selected><?=$this->lang->line('application_with_stock');?></option>
                <option value="2"><?=$this->lang->line('application_no_stock');?></option>
              </select>
            </div>
          </div>
          <div class="row"></div>
          
          <div class="col-md-3">
            <div class="">
              <label for="buscamkt" class="normal"><?=$this->lang->line('application_marketplace');?></label>
              <select class="form-control selectpicker show-tick"  id="buscamkt" name ="buscamkt" onchange="buscaProduto()" data-live-search="true" data-style="btn-blue" multiple="multiple" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>" >
               <!-- <option value="ML">Mercado Livre - ConectaLá</option>
                <option value="B2W">B2W - ConectaLá</option>
                <option value="CAR">Carrefour - ConectaLá</option>
                <option value="VIA">Via Varejo - ConectaLá</option> -->
                <?php foreach ($names_marketplaces as $name_marketplace) { ?>
                <option value="<?= $name_marketplace['int_to'] ?>"><?= $name_marketplace['name'] ?></option>
           		<?php } ?>
              </select>
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="">
              <label for="buscalojas" class="normal">Buscar por Lojas</label>
              <select class="form-control selectpicker show-tick" id="buscalojas" name ="loja[]" onchange="buscaProduto()" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                <?php foreach ($stores_filter as $store_filter) { ?>
                <option value="<?= $store_filter['id'] ?>"><?= $store_filter['name'] ?></option>
           		<?php } ?>
                
              </select>
            </div>
          </div>
 
          
          <!--
          <div class="dropdown col-md-3">
          	<label for="buscalojas" class="normal" style="display: block;">Buscar por Lojas</label>
            <button class="btn btn-default dropdown-toggle" type="button" id="buscalojas" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" style="background-color: #fff;">
              Selecione a(s) loja(s)
              <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="buscalojas" style="border-color: #bbb8b8; padding: 10px 10px;">
              <?php foreach ($stores_filter as $store_filter) { ?>
                <li><input type="checkbox" onchange="buscaProduto()" name="loja[]" class="stores" value="<?= $store_filter['id'] ?>"><?= $store_filter['name'] ?></li>
              <?php } ?>
            </ul>
          </div>
			---->
          <div class="col-md-2">
            <div class="input-group" >
              <label for="buscasameprice" class="normal"><?=$this->lang->line('application_price_change');?></label>
              <select class="form-control" id="buscasameprice" onchange="buscaProduto()">
                <option value=""><?=$this->lang->line('application_select');?></option>
                <option value="1"><?=$this->lang->line('application_diferent_price');?></option>
                <option value="2"><?=$this->lang->line('application_same_price');?></option>
              </select>
            </div>
          </div>
          
          <div class="col-md-2">
            <div class="input-group" >
              <label for="buscasameqty" class="normal"><?=$this->lang->line('application_qty_change');?></label>
              <select class="form-control" id="buscasameqty" onchange="buscaProduto()">
                <option value=""><?=$this->lang->line('application_select');?></option>
                <option value="1"><?=$this->lang->line('application_diferent_qty');?></option>
                <option value="2"><?=$this->lang->line('application_same_qty');?></option>
              </select>
            </div>
          </div>
          
          <div class="col-md-1"  >
			  <label  class="normal" style="display: block;">&nbsp; </label>
       		  <button type="button" onclick="clearFilters()" class="btn btn-primary"> <i class="fa fa-eraser"></i> Limpar </button>
        	</div>
        	
        <div class="row"></div>
        <div class="box">
          <div class="box-body">
            <!-- table id="manageTable" class="table table-bordered table-striped" -->
            <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
              <thead>
              <tr>
              	<th><?=$this->lang->line('application_id');?></th>
              	<th><?=$this->lang->line('application_marketplace');?></th>
                <th><?=$this->lang->line('application_sku');?></th>
                <th><?=$this->lang->line('application_name');?></th>
                <th><?=$this->lang->line('application_price_marketplace');?></th>
                <th><?=$this->lang->line('application_qty');?></th>
                <th><?=$this->lang->line('application_store');?></th>           
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


<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";
var table;

$(document).ready(function() {

  $('.maskdecimal2').inputmask({
		  alias: 'numeric', 
		  allowMinus: false,  
		  digits: 2, 
		  max: 999999999.99
		});


  let sku  = $('#buscasku').val();
  let nome = $('#buscanome').val();
  let status  = $('#buscastatus').val();
  let completo  = $('#buscacompleto').val();
  let estoque  = $('#buscaestoque').val();
  let bsame_price  = $('#buscasameprice').val();
  let bsame_qty  = $('#buscasameqty').val();
	var int_to  = [];
	$('#buscamkt  option:selected').each(function() {
        int_to.push($(this).val());
    });
	if (int_to == ''){int_to = ''}
	
	var lojas = [];
    $('#buscalojas  option:selected').each(function() {
        lojas.push($(this).val());
    });
	if (lojas == ''){lojas = ''}

  table = $('#manageTable').DataTable({
    "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
    "processing": true,
    "serverSide": true,
    "scrollX": true,
    "sortable": true,
    "searching": true,
    "serverMethod": "post",
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'productsMarketplace/fetchPricesData',
      data: {sku: sku, nome: nome, int_to: int_to, status: status, status: status, completo: completo,  estoque: estoque, lojas: lojas, bsame_price: bsame_price, bsame_qty : bsame_qty, internal: false},
      pages: 2 // number of pages to cache
    })
  });
  $('input[type="checkbox"].minimal').iCheck({
      checkboxClass: 'icheckbox_minimal-blue',
      radioClass   : 'iradio_minimal-blue'
  });
});

function buscaProduto(){
  let sku  = $('#buscasku').val();
  let nome = $('#buscanome').val();
  let status  = $('#buscastatus').val();
  let completo  = $('#buscacompleto').val();
  let estoque  = $('#buscaestoque').val();
  let bsame_price  = $('#buscasameprice').val();
  let bsame_qty  = $('#buscasameqty').val();
  var int_to  = [];
	$('#buscamkt  option:selected').each(function() {
        int_to.push($(this).val());
    });
	if (int_to == ''){int_to = ''}
  var lojas = [];
    $('#buscalojas  option:selected').each(function() {
        lojas.push($(this).val());
    });
  if (lojas == ''){lojas = ''}
  
  table.destroy();
  table = $('#manageTable').DataTable({
    "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
    "processing": true,
    "serverSide": true,
    "scrollX": true,
    "sortable": true,
    "searching": true,
    "serverMethod": "post",
    "ajax": $.fn.dataTable.pipeline({
      url: base_url + 'productsMarketplace/fetchPricesData',
      data: {sku: sku, nome: nome, int_to: int_to, status: status, status: status, completo: completo,  estoque: estoque, lojas: lojas, bsame_price: bsame_price, bsame_qty : bsame_qty, internal: false},
      pages: 2, // number of pages to cache
      while: $("input[name='loja[]']").each(function(){$(this).prop('disabled', true)}),
      success: setTimeout(function(){$("input[name='loja[]']").each(function(){$(this).prop('disabled', false)})}, 1500)
    })
  });
}

function clearFilters(){
  $('#buscasku').val('');
  $('#buscanome').val('');
  $('#buscastatus').val('');
  $('#buscacompleto').val('');
  $('#buscaestoque').val('');
  $('#buscamkt').val('');
  $('#buscasameprice').val('');
  $('#buscasameqty').val('');
  $('#buscalojas').selectpicker('val', '');
  $('#buscamkt').selectpicker('val', '');
  buscaProduto();
}

function changeQty(id, old_qty, new_qty, max_qty, same_qty) {
  if (new_qty> max_qty) {
  	Swal.fire({
				  icon: 'error',
				  title: "A quantidade para um marketplace não pode ser maior que o estoque do produto."
				}).then((result) => {
				});
	new_qty =  max_qty;
  }
 $.ajax({
  url: base_url+"productsMarketplace/updateQty",
  type: 'POST',
  data: { id: id, old_qty: old_qty, new_qty: new_qty, same_qty: same_qty }, 
  async: true,
  dataType: 'json'
    });
    return new_qty;
 
}

function changePrice(id, old_price, new_price, same_price) {

  $.ajax({
      url: base_url+"productsMarketplace/updatePrice",
      type: 'POST',
      data: { id: id, old_price: old_price, new_price: new_price , same_price : same_price }, 
      async: true,
      dataType: 'json'
    });
    var priceFloat = parseFloat(new_price);
    var priceFormated = priceFloat.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
    return priceFormated;
}

function formatPrice(value) {
	var newPrice = value.replace(/[^0-9\.]/g, '')
	var decimalPoints = newPrice.match(/\./g);
	if (decimalPoints && decimalPoints.length > 1) {
		newPrice = newPrice.substring(0,newPrice.lastIndexOf('.'));
	}
	if (newPrice.lastIndexOf('.')>0) {
		if (newPrice.length > newPrice.lastIndexOf('.')+3) {
			newPrice = newPrice.substring(0,newPrice.lastIndexOf('.')+3);
		}
	}
	return newPrice;

}
function formatPriceVirgula(value) {
	if (value.indexOf('R$') != -1) {
		var value = value.replace('.','');
		value = value.replace(/[\,]/g, '.');
	}
	return  value.replace(/[^0-9\.]/g, '');
}

function samePrice(id,prdprice) {
	const samePrice = document.getElementById('samePrice_'+id).checked
	var priceFloat = parseFloat(prdprice);
    var priceFormated = priceFloat.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
	
	var oldPrice =  $('#price_'+id).val()
	if (samePrice) {
		$('#price_'+id)[0].value = priceFormated
		$('#price_'+id).attr('disabled', 'disabled')
		$('#samePrice_'+id).attr('checked', 'checked')
		changePrice(id, oldPrice, prdprice, 1 )
	} else {
		
		$('#price_'+id)[0].value = priceFormated
		$('#price_'+id).removeAttr('disabled')
		$('#samePrice_'+id).removeAttr('checked')
		changePrice(id, prdprice, prdprice, 0 )
	}
}

function sameQty(id,prdqty) {
	const sameQty = document.getElementById('sameQty_'+id).checked
	
	var oldQty =  $('#qty_'+id).val()
	if (sameQty) {
		$('#qty_'+id)[0].value = prdqty
		$('#qty_'+id).attr('disabled', 'disabled')
		$('#sameQty_'+id).attr('checked', 'checked')
		changeQty(id, oldQty, prdqty, prdqty, 1 )
	} else {
		$('#qty_'+id)[0].value = prdqty
		$('#qty_'+id).removeAttr('disabled')
		$('#sameQty_'+id).removeAttr('checked')
		changeQty(id, prdqty, prdqty, prdqty, 0 )
	}
}

</script>
