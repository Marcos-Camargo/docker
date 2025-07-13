<style>
.gutter-13.row {
    margin-right: -13px;
    margin-left: -13px;
  }
  .gutter-13 > [class^="col-"], .gutter-3 > [class^=" col-"] {
    padding-right: 2px;
    padding-left: 2px;
  }`
</style>


<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_edit";  $data['page_now'] ='products_kit'; $this->load->view('templates/content_header',$data); ?>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

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
	        <br />
        <div class="box">
          <div class="box-header">
	      	<h3 class="box-title">Produtos Escolhidos</h3>
	      </div>
          <form role="form" id="formedit" id="name" action="<?php base_url('productsKit/create') ?>" method="post">
	          <!-- /.box-header -->
	          <div class="box-body border">
		          <div>
		          	<input type="hidden" id="numprod" name="numprod" value="0" >
		          </div>
		          <div class="container">
		            <div class="row border gutter-13">
						<div id="divid" class="col-sm-1">
					    	<label><?=$this->lang->line('application_sku');?></label>
						</div>
						<div id="divname" class="col-sm-4">
					    	<label><?=$this->lang->line('application_product');?></label>
						</div>
						<div id="divprice" class="col-sm-2">
					    	<label><?=$this->lang->line('application_price');?></label>
						</div>
						<div id="divqty" class="col-sm-1">
					    	<label><?=$this->lang->line('application_qty');?></label>
						</div>
						<div id="divde" class="col-sm-1">
					    	<label><?=$this->lang->line('application_price_from');?></label>
						</div>
						<div id="divpor" class="col-sm-1">
					    	<label><?=$this->lang->line('application_price_sale');?></label>
						</div>
						<!--
						<div id="divtotal" class="col-md-3">
					    	<label><?=$this->lang->line('application_total');?></label>
						</div>
						-->
				     </div>
				     <div class="input_fields_wrap"></div>
				     <div class="row gutter-13" >
						<div id="divid" class="col-sm-1">
						</div>
						<div id="divname" class="col-sm-4">  
						</div>
						<div id="divprice" class="col-sm-2">
					    	<label>Total :</label>
						</div>
						<div id="divqty" class="col-sm-1">
					    	<input type="text" disabled id="total_qty" style="text-align:right;" class="form-control" name="total_qty" value="0" >
						</div>
						<div id="divde" class="col-sm-1" >
							<input type="text" disabled id="total_original_price" style="text-align:right;" class="form-control" name="total_original_price" value="0" >
						</div>
						<div id="divpor" class="col-sm-1" >
							<input type="text" disabled id="total_price" style="text-align:right;" class="form-control" name="total_price" value="0" >
						</div>
				    </div>
				  </div>
				  <hr style="border-top: 1px dotted #000000 !important; " />
				  
				  <div class="box-footer">
	                <button type="submit"  class="btn btn-primary"><?=$this->lang->line('application_create');?></button>
	                <a href="<?php echo base_url('products/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
	              </div>
	          
	          	  <div>
	          	  	<h4 class="box-title">Escolha os produtos para fazer parte do kit</h4>
		            <table id="manageTable" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
		              <thead>
		              <tr>
		                <th><?=$this->lang->line('application_image');?></th>
		                <th><?=$this->lang->line('application_sku');?></th>
		                <th><?=$this->lang->line('application_name');?></th>
		                <th><?=$this->lang->line('application_price');?></th>
		                <th><?=$this->lang->line('application_qty');?></th>
		                <th><?=$this->lang->line('application_store');?></th>
		                <th><?=$this->lang->line('application_id');?></th>
		                <?php if(in_array('updateProduct', $user_permission) || in_array('deleteProduct', $user_permission)): ?>
		                  <th><?=$this->lang->line('application_action');?></th>
		                <?php endif; ?>
		              </tr>
		              </thead>
		            </table>
	          	  </div>
	          	
	          </div>
	          <!-- /.box-body -->
	          
          </form>
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


<div class="modal fade" tabindex="-1" role="dialog" id="quantityModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php 
			$titulo = $this->lang->line('application_item_qty');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:blue;"><?php echo $this->lang->line('application_item_qty');?></span></h4>
		</div>
	    <div class="modal-body">
		    <div class="row">
				<div class="form-group col-md-2"><label><?=$this->lang->line('application_product');?></label></div>
				<div class="form-group col-md-9">
					<input type="text" required name="name_product" id="name_product" disabled class="form-control" value="" />
				</div>
			</div>	    
			<div class="row" >
				<div class="form-group col-md-2"><label><?=$this->lang->line('application_current_price');?></label></div>
				<div class="form-group col-md-3 price-group ">
					<input type="text" style="text-align:right;" id="original_price_product" required class="form-control maskdecimal2" value="" autocomplete="off" disabled/>					
				</div>
			</div>

			<div class="row" >
				<div class="form-group col-md-2"><label><?=$this->lang->line('application_price');?></label></div>
				<div class="form-group col-md-3 price-group ">
					<input type="text" required name="price_product" style="text-align:right;" id="price_product" required class="form-control maskdecimal2" value="" autocomplete="off" />
					<span id="price_erro" style="color:red;"></span>			
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-2"><label><?=$this->lang->line('application_item_qty');?></label></div>
				<div class="form-group qty-group col-md-3">
					<input type="text" required name="prod_qty" style="text-align:right;" id="prod_qty" required class="form-control" value="" autocomplete="off" onKeyPress="return digitos(event, this);" />
					<span id="qty_erro" style="color:red;"></span>			
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-2"><label><?=$this->lang->line('application_stock');?></label></div>
				<div class="form-group qty-group col-md-3">
					<input type="text" required name="qty_max" style="text-align:right;" id="qty_max" disabled class="form-control" value="" autocomplete="off" />	
				</div>
			</div>
			<input type="hidden" id="id_product" name="id_product" value="" autocomplete="off">
			<input type="hidden" id="qty_maxxxxx" name="qty_maxxxx" value="" autocomplete="off">
			<input type="hidden" id="sku_product" name="sku_product" value="" autocomplete="off">
			<input type="hidden" id="original_price" name="original_price" value="" autocomplete="off">
			
		</div> <!-- modal-body -->
	    <div class="modal-footer">
	   		<button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
	        <button type="button" class="btn btn-primary" onclick="checkQty(event)" ><?=$this->lang->line('application_confirm');?></button>
			
	    </div>		
   	</form>  	
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";
var total_prod = 0;
var total_price = 0;
var total_original_price = 0;
var total_qty = 0;
var price_format = 0;
var store_id =0;
var wrapper = $(".input_fields_wrap"); //Fields wrapper

$(document).ready(function() {

	$('.maskdecimal2').inputmask({
		  alias: 'numeric', 
		  allowMinus: false,  
		  digits: 2, 
		  max: 999999999.99
		});
		
    manageTable = $('#manageTable').DataTable( {
        "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
        "processing": true,
        "serverSide": true,
        "responsive": true,
        "sortable": true,
        "pageLength": 100,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline( {
            url: base_url + 'productsKit/fetchProductData',
            pages: 2, // number of pages to cache
        } )
    } );
    
    
	$(wrapper).on("click",".remove_field", function(e){ //user click on remove text
		e.preventDefault(); 
		total_prod--;
		$('#numprod').val(total_prod);
		$(this).parent('div').parent('div').remove();
		
		total_price = 0; 
	    $('input[name^="TOTAL"]').each(function() {
	   		 total_price = total_price + Number($(this).val());
	    });
		$('#total_price').val(total_price.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'}));
		
		total_original_price = 0; 
		$('input[name^="ORIGINAL"]').each(function() {
	   		 total_original_price = total_original_price + Number($(this).val());
	    });
		$('#total_original_price').val(total_original_price.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'}));
		
		total_qty = 0; 
	    $('input[name^="QTY"]').each(function() {
	   		 total_qty = total_qty +Number($(this).val());
	    }); 
		$('#total_qty').val(total_qty);
		if (total_qty == 0) {
			store_id =0;
		}
	})
	
});
function submitMe() 
{
	alert('aqui');
	$("#formedit").submit();
}

function qtyProduct(e,product_id,store_id_new,sku,qty,price,name)
  { 	
  	e.preventDefault();
  	var erro = false;
  	if (store_id == 0) {
  		store_id = store_id_new;
  	}
  	if (store_id != store_id_new) {
  		Swal.fire({
				  icon: 'error',
				  title: "<?php echo $this->lang->line('messages_only_products_same_store'); ?>"
				});
	    erro = true;	
  	}
  	$('input[name^="ID"]').each(function() {
	    if ($(this).val() == product_id) {
	    	Swal.fire({
				  icon: 'error',
				  title: "<?php echo $this->lang->line('messages_product_already_chosen'); ?>"
				});
	    	// alert('Este produto já foi escolhido');
	    	erro = true;	
	    }
	});
	if (erro == false) {
		var id = $("#prod_qty");
		id.closest('.qty-group')
          .removeClass('has-error')
          .removeClass('has-success');
        $("#qty_erro").text("");
        
        var id = $("#price_product");
        id.closest('.price-group')
          .removeClass('has-error')
          .removeClass('has-success'); 
        $("#price_erro").text("");
		document.getElementById('sku_product').value=sku;
		document.getElementById('id_product').value=product_id;
		document.getElementById('price_product').value=price;
		document.getElementById('original_price').value=price;
		document.getElementById('original_price_product').value=price;
	    document.getElementById('qty_max').value=qty;
	    document.getElementById('name_product').value=name;
	    $("#quantityModal").modal('show');
	}

  }

function checkQty(e) {
	e.preventDefault();
	$(".text-danger").remove();
    var qty = document.getElementById('prod_qty').value;
    var product_id = document.getElementById('id_product').value;
    var qty_max = document.getElementById('qty_max').value;
    var prd_name =  document.getElementById('name_product').value;
    var price = document.getElementById('price_product').value;
    var original_price = document.getElementById('original_price').value;
    var sku = document.getElementById('sku_product').value; 
    var store_id
    
    var erro=false;
    var id = $("#prod_qty");
	if (Number(qty)>Number(qty_max)) {
        id.closest('.qty-group')
          .removeClass('has-error')
          .removeClass('has-success')
          .addClass('has-error');
        $("#qty_erro").text("<?php echo $this->lang->line('messages_qty_greater_than_stock'); ?>");
        erro=true;
	} else if (Number(qty)==0) {
        id.closest('.qty-group')
          .removeClass('has-error')
          .removeClass('has-success')
          .addClass('has-error');
        $("#qty_erro").text("<?php echo $this->lang->line('messages_qty_not_zero'); ?>");
         erro=true;
	} else {
		id.closest('.qty-group')
          .removeClass('has-error')
          .removeClass('has-success');
        $("#qty_erro").text("");
	} 
	
	var id = $("#price_product");

	if(parseFloat(price) > parseFloat(original_price)) {
		id.closest('.price-group')
          .removeClass('has-error')
          .removeClass('has-success')
          .addClass('has-error');
          $("#price_erro").text("<?php echo $this->lang->line('messages_price_bigger_than_original_price'); ?>");
          erro=true;
	} else
	if (Number(price)==0) {
        id.closest('.price-group')
          .removeClass('has-error')
          .removeClass('has-success')
          .addClass('has-error');
          $("#price_erro").text("<?php echo $this->lang->line('messages_price_not_zero'); ?>");
          erro=true;
    } else {
        id.closest('.price-group')
          .removeClass('has-error')
          .removeClass('has-success'); 
        $("#price_erro").text("");
    }
    
    if (erro==0) {
		$("#quantityModal").modal('hide');

		total_prod++;
		total_line = Number(qty) * Number(price);
		
		total_price = total_price + total_line;
		// alert('qty='+qty+ ' original_price='+original_price+' total='+ total_original_price);
		total_line_original = Number(qty) * Number(original_price);
		total_original_price  = total_original_price + total_line_original; 
		total_qty = total_qty + Number(qty);
		price_format = Number(price);
		
		perc = ((1-Number(price)/Number(original_price)) * 100).toFixed(2)
		var linha = '<div class="row gutter-13" id="prod'+total_prod+'">';
		linha = linha + '<div><input type="hidden" id="TOTAL[]" name="TOTAL[]" value="'+total_line+'" /></div>';	
		linha = linha + '<div><input type="hidden" id="ID[]" name="ID[]" value="'+product_id+'" /></div>';
		linha = linha + '<div><input type="hidden" id="PRICE[]" name="PRICE[]" value="'+price+'" /></div>';	
		linha = linha + '<div><input type="hidden" id="ORIGINAL[]" name="ORIGINAL[]" value="'+total_line_original+'" /></div>';	
		linha = linha + '<div><input type="hidden" id="QTY[]" name="QTY[]" value="'+qty+'" /></div>';		
		linha = linha + '<div id="divid" class="col-sm-1"><input type="text" disabled class="form-control" id="SKU[]" name="SKU[]" value="'+sku+'" /></div>';
		linha = linha + '<div id="divname" class="col-sm-4"><input type="text" disabled class="form-control" id="NAME[]" name="NAME[]" value="'+prd_name+'" /></div>';
		linha = linha + '<div id="divprice" class="col-sm-2"><input type="text" disabled style="text-align:right;" class="form-control" id="PRICEFORMAT[]" name="PRICEFORMAT[]" value="'+price_format.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'})+' ('+perc+'%)" /></div>';
		linha = linha + '<div id="divqty" class="col-sm-1"><input type="text" disabled style="text-align:right;" class="form-control" id="QTXYSHOW[]" name="QTXYSHOW[]" value="'+qty+'" /></div>';
		linha = linha + '<div id="divpor" class="col-sm-1"><input type="text" disabled style="text-align:right;" class="form-control" id="TODTAL[]" name="TOFDAL[]" value="'+total_line_original.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'})+'" /></div>';
		linha = linha + '<div id="divpor" class="col-sm-1"><input type="text" disabled style="text-align:right;" class="form-control" id="TOPTAL[]" name="TOPDAL[]" value="'+total_line.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'})+'" /></div>';
		//linha = linha + '<div id="divtotal" class="col-md-3"><span>De: '+total_line_original.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'})+' Por:'+total_line.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'})+' </span></div>';
		linha = linha + '<div class="col-sm-1"><button type="button" class="btn btn-default remove_field"><i class="fa fa-trash"></i></button></div>';		
		linha = linha + '</div>';

		
		
		$(wrapper).append(linha);
		$('#numprod').val(total_prod);
		$('#total_price').val(total_price.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'}));
		$('#total_original_price').val(total_original_price.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'}));
	
		
		$('#total_qty').val(total_qty);
		document.getElementById('prod_qty').value='';
	}
}

</script>
