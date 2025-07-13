<!--
SW Serviços de Informática 2019

Editar Promoção 


<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
-->
<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data); ?>

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

          <div class="box">
            <div class="box-header">
              <h3 class="box-title"><?=$this->lang->line('application_update_information');?></h3>
            </div>
            <form role="form" id="formCadastro" method="post">
              <div class="box-body">
              
                <input type="hidden" class="form-control" id="hdnLote" name="hdnLote" value="<?php echo $hdnLote;?>">
                <input type="hidden" class="form-control" id="hdnChamado" name="hdnChamado" value="<?php echo $campanha['id'];?>">
            
	       		<div class="form-group col-md-12 col-xs-12">
                  	<label for="name"><?=$this->lang->line('application_name_campaign');?></label>
                  	<input type="text"   class="form-control" id="name" name="name" required autocomplete="off" placeholder="<?=$this->lang->line('application_enter_name_campaign') ?>" value="<?php echo $campanha['campanha'];?>"/>
                </div>
                
				<div class="form-group col-md-12 col-xs-12 <?php echo (form_error('description')) ? 'has-error' : '';?>">
                  	<label for="description"><?=$this->lang->line('application_description');?></label>
                  	<textarea class="form-control" rows="4" id="description" required name="description" autocomplete="off" placeholder="<?=$this->lang->line('application_enter_description') ?>"><?php echo $campanha['descricao'];?></textarea>
                </div>
				                     	
                <div class="form-group col-md-3 col-xs-3">
                  	<label for="addr_uf"><?=$this->lang->line('application_marketplace')?></label>
              		<select class="form-control" id="slc_marketplace" required name="slc_marketplace">
              		<option value="">SELECIONE</option>
                	<?php foreach ($marketplaces as $marketplace): ?>
                  		<option  value="<?php echo $marketplace['apelido'] ?>"><?php echo $marketplace['descloja'] ?></option>
                	<?php endforeach ?>
              		</select>
                </div> 
                
       			<div class="form-group col-md-3">
					<label for="start_date"><?=$this->lang->line('application_start_date');?>(*)</label>
					<div class='input-group date' id='start_date_pick' name="start_date_pick">
		                <input type='text'   required class="form-control" id='start_date' name="start_date" autocomplete="off" value="<?php echo $campanha['data_inicio_campanha'];?>" />
		                <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
		            </div>
		        </div>
		        
		        <div class="form-group col-md-3">
					<label for="end_date"><?=$this->lang->line('application_end_date');?>(*)</label>
					<div class='input-group date' id='end_date_pick' name="end_date_pick">
		                <input type='text'   required class="form-control" id='end_date' name="end_date" autocomplete="off" value="<?php echo $campanha['data_fim_campanha'];?>"/>
		                <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
		            </div>
		        </div>
                <div class="form-group col-md-3 col-xs-3">
		  			<label for="scl_tipo_campanha">Tipo de Campanha</label>
              		<select class="form-control" id="scl_tipo_campanha" name="scl_tipo_campanha">
                  		<option value="">SELECIONE</option>
                  		<option value="1">Negociação</option>
                  		<option value="2">Promoção</option>
              		</select>
				</div>  
				
              </div>
              
              <div class="box-body" id="divNegociacao" name="divNegociacao" style="display: none">
              <div class="box-header">
					<h3 class="box-title">Negociação</h3>
              </div>
              	<div class="form-group col-md-3 col-xs-3">
	                  	<label for="txt_taxa_mktplace_nova"><?=$this->lang->line('application_commission_mkt_campaign');?></label>
	                  	<input type="number" class="form-control" style="text-align:right;" id="txt_taxa_mktplace_nova" name="txt_taxa_mktplace_nova"  value="<?php echo $campanha['taxa_reduzida_marketplace'];?>" autocomplete="off" placeholder="<?=$this->lang->line('application_enter_commission_mkt') ?>" />
	                </div>
	                <div class="form-group col-md-3 col-xs-3">
	                  	<label for="txt_taxa_seller_nova"><?=$this->lang->line('application_commission_store_campaign');?></label>
	                  	<input type="number" class="form-control" style="text-align:right;" id="txt_taxa_seller_nova" name="txt_taxa_seller_nova"  value="<?php echo $campanha['taxa_reduzida_seller'];?>" autocomplete="off" placeholder="<?=$this->lang->line('application_enter_commission_store') ?>" />
	                </div>
              </div>
              
              <div class="box-body" id="divPromocao" name="divPromocao" style="display: none">
              <div class="box-header">
					<h3 class="box-title">Promoção</h3>
              </div>
              	<div class="form-group col-md-3 col-xs-3" <?php if($sellercenter <> 'conectala'){ echo 'style="display: none"'; } ?> >
                  	<label for="addr_uf">Tipo de Pagamento</label>
              		<select class="form-control" id="scl_tipo_pagamento" name="scl_tipo_pagamento">
                  		<option value="">SELECIONE</option>
                  		<option value="1">No cartão à vista</option>
                  		<option value="2">No cartão parcelado</option>
                  		<option value="3">À vista</option>
                  		<option value="4">Boleto</option>
              		</select>
                </div>
              	<div class="form-group col-md-3 col-xs-3">
	                  	<label for="commission_mkt_campaign">Total % da promoção</label>
	                  	<input type="number" class="form-control" style="text-align:right;" id="txt_percent_promo" name="txt_percent_promo"  value="<?php echo $campanha['total_percent_promocao'];?>" autocomplete="off" placeholder="Total % da promoção" />
                </div>
                <div class="form-group col-md-3 col-xs-3">
                  	<label for="commission_store_campaign">Total % pago pelo Seller</label>
                  	<input type="number" class="form-control" style="text-align:right;" id="txt_percent_seller" name="txt_percent_seller"  value="<?php echo $campanha['total_percent_seller'];?>" autocomplete="off" placeholder="Total % pago pelo Seller" />
                </div>
                <div class="form-group col-md-3 col-xs-3">
		  			<label for="typepromo"><?=$this->lang->line('application_promotions');?>:</label>
		  			<div class='input-group' >
	       			 <input id="typepromo" name="typepromo" type="checkbox" value="1" data-toggle="toggle" data-on="Criar Promoção" data-off="Não Criar" data-onstyle="success" data-offstyle="danger" >
					</div>
				</div>
				
				<div class="col-md-4 col-xs-4">
              		<label for="group_isadmin"><?=$this->lang->line('application_category');?> Nível 1</label>
                  <select class="form-control" id="slc_categoria_n1" name="slc_categoria_n1">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($categoriaN1 as $n1): ?>
                          <option value="<?php echo trim($n1['categoryN1']); ?>"><?php echo trim($n1['categoryN1']); ?></option>
                        <?php endforeach ?>
                  </select>
          		</div>
          		
          		<div class="col-md-4 col-xs-4">
              		<label for="group_isadmin"><?=$this->lang->line('application_category');?> Nível 2</label>
                  <select class="form-control" id="slc_categoria_n2" name="slc_categoria_n2">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($categoriaN2 as $n2): ?>
                          <option value="<?php echo trim($n2['categoryN2']); ?>"><?php echo trim($n2['categoryN2']); ?></option>
                        <?php endforeach ?>
                  </select>
          		</div>
          		
          		<div class="col-md-4 col-xs-4">
              		<label for="group_isadmin"><?=$this->lang->line('application_category');?> Nível 3</label>
                  <select class="form-control" id="slc_categoria_n3" name="slc_categoria_n3">
                    <option value="">~~SELECT~~</option>
                    <?php foreach ($categoriaN3 as $n3): ?>
                          <option value="<?php echo trim($n3['categoryN3']); ?>"><?php echo trim($n3['categoryN3']); ?></option>
                        <?php endforeach ?>
                  </select>
          		</div>
				
				
              </div>
              
              
              <div class="box-body" id="divCartegoria" name="divCartegoria" style="display: none">
              <div class="box-header">
					<h3 class="box-title">Categoria</h3>
              </div>
              	<div class="form-group col-md-3 col-xs-3">
	                  	<label for="commission_mkt_campaign"><?=$this->lang->line('application_commission_mkt_campaign');?></label>
	                  	<input type="text" class="form-control" maxlength=5 style="text-align:right;" id="commission_mkt_campaign" name="commission_mkt_campaign"  value="" autocomplete="off" placeholder="<?=$this->lang->line('application_enter_commission_mkt') ?>" />
	                </div>
	                <div class="form-group col-md-3 col-xs-3">
	                  	<label for="commission_store_campaign"><?=$this->lang->line('application_commission_store_campaign');?></label>
	                  	<input type="text" class="form-control" maxlength=5 style="text-align:right;" id="commission_store_campaign" name="commission_store_campaign"  value="" autocomplete="off" placeholder="<?=$this->lang->line('application_enter_commission_store') ?>" />
	                </div>
              </div>
              
              <!-- /.box-body -->
              <div class="box-footer">
                <button type="button" id="btnSave" name="btnSave" class="btn btn-primary"><?=$this->lang->line('application_save');?></button> 
                <a href="<?php echo base_url('campaigns') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
              </div>
            </form>
          </div>
          <!-- /.box -->
          <form id="frmRadio" name="frmRadio">
          <div id="divTabelaSKU" name="divTabelaSKU" style="display: none">
                <div class="box">
                <div class="box-header">
                  <h3 class="box-title">Produtos</h3>
                </div>
                  <div class="box-body">
                  <div class="form-group col-md-2 col-xs-2">
                      <input type="radio" id="sku" name="buscaAuto" value="sku" checked>
                      <label for="sku">SKU</label><br>
                      <input type="radio" id="id" name="buscaAuto" value="id">
                      <label for="id">ID - Produto</label><br>  
                      <input type="radio" id="nome" name="buscaAuto" value="nome">
                      <label for="nome">Nome</label><br>
                      <input type="radio" id="categoria" name="buscaAuto" value="categoria">
                      <label for="nome">Categoria</label><br>
                  </div>
                  	<div class="form-group col-md-8 col-xs-8">
	                  	<label for="commission_mkt_campaign">Produtos</label>
	                  	<input type="text" class="form-control" style="text-align:right;" id="txt_sku" name="txt_sku"  value="" autocomplete="off" placeholder="SKU" />
	                  	<input type="hidden" class="form-control" style="text-align:right;" id="hdn_sku" name="hdn_sku"  value="" autocomplete="off" placeholder="SKU" />
	                </div>
    	                <div class="col-md-2 col-xs-2">
                    	<br>
                    	<button type="button" id="btnAddSKU" name="btnAddSKU" class="btn btn-success">Adicionar Produto</button>
                    </div>
                  </div>
                  
				</div>
            </div>
            <div id="divTabelaSKU2" name="divTabelaSKU2" style="display: none">
                <div class="box">
                	<div class="box-body">
                        <table id="tabelaSKUAdd" name="tabelaSKUAdd"  class="table table-bordered table-striped">
                        <thead>
                          <tr>
                          	<th><?=$this->lang->line('application_quotation_id');?></th>
                            <th><?=$this->lang->line('application_marketplace');?></th>
                            <th>Id Produto</th>
                            <th>SKU</th>
                            <th>Nome</th>
                            <th><?=$this->lang->line('application_action');?></th>
                          </tr>
                        </thead>
                      	</table>
                	</div>
                </div>
            </div>
            </form>
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
var base_url = "<?php echo base_url(); ?>";
var manageTableProducts;

var marketplace = "<?php echo $campanha['marketplace'];?>";
var tipo_campanha = "<?php echo $campanha['tipo_campanha'];?>";
var tipo_pagamento = "<?php echo $campanha['tipo_pagamento'];?>";
var categoria_n1 = "<?php echo $campanha['categoria_n1'];?>";
var categoria_n2 = "<?php echo $campanha['categoria_n2'];?>";
var categoria_n3 = "<?php echo $campanha['categoria_n3'];?>";

 $(document).ready(function() {
	  
	 $("#slc_marketplace").val(marketplace);
	 $("#scl_tipo_campanha").val(tipo_campanha);
	 $("#scl_tipo_pagamento").val(tipo_pagamento);
	 $("#slc_categoria_n1").val(categoria_n1);
	 $("#slc_categoria_n2").val(categoria_n2);
	 $("#slc_categoria_n3").val(categoria_n3);

	 $("#btnSave").prop('disabled', true);

	 if( $("#scl_tipo_campanha").val() == "1" ){  
			$("#divNegociacao").show();
			$("#divPromocao").hide();
			$("#divCartegoria").hide();
			$("#btnSave").prop('disabled', false);
			$("#divTabelaSKU").show();
			$("#divTabelaSKU2").show();
		}

		if( $("#scl_tipo_campanha").val() == "2" ){  
			$("#divNegociacao").hide();
			$("#divPromocao").show();
			$("#divCartegoria").hide();
			$("#btnSave").show();
			$("#btnSave").prop('disabled', false);
			$("#divTabelaSKU").hide();
			$("#divTabelaSKU2").hide();
		}

		if( $("#scl_tipo_campanha").val() == "3" ){  
			$("#divNegociacao").hide();
			$("#divPromocao").hide();
			$("#divCartegoria").show();
			$("#btnSave").show();
			$("#btnSave").prop('disabled', false);
			$("#divTabelaSKU").hide();
			$("#divTabelaSKU2").hide();
		}

		if( $("#scl_tipo_campanha").val() == "" ){  
			$("#divNegociacao").hide();
			$("#divPromocao").hide();
			$("#divCartegoria").hide();
			$("#btnSave").prop('disabled', true);
			$("#divTabelaSKU").hide();
			$("#divTabelaSKU2").hide();
		}

	var filtro = $("#formCadastro").serialize();
     manageTableProducts = $('#tabelaSKUAdd').DataTable({
       'ajax': base_url + 'campaigns/getproductscampanhatemp?'+filtro,
       'order': []
     });

	 $("#scl_tipo_campanha").change(function(){

		if( $("#scl_tipo_campanha").val() == "1" ){  
			$("#divNegociacao").show();
			$("#divPromocao").hide();
			$("#divCartegoria").hide();
			$("#btnSave").prop('disabled', false);
			$("#divTabelaSKU").show();
			$("#divTabelaSKU2").show();
		}

		if( $("#scl_tipo_campanha").val() == "2" ){  
			$("#divNegociacao").hide();
			$("#divPromocao").show();
			$("#divCartegoria").hide();
			$("#btnSave").show();
			$("#btnSave").prop('disabled', false);
			$("#divTabelaSKU").hide();
			$("#divTabelaSKU2").hide();
		}

		if( $("#scl_tipo_campanha").val() == "3" ){  
			$("#divNegociacao").hide();
			$("#divPromocao").hide();
			$("#divCartegoria").show();
			$("#btnSave").show();
			$("#btnSave").prop('disabled', false);
			$("#divTabelaSKU").hide();
			$("#divTabelaSKU2").hide();
		}

		if( $("#scl_tipo_campanha").val() == "" ){  
			$("#divNegociacao").hide();
			$("#divPromocao").hide();
			$("#divCartegoria").hide();
			$("#btnSave").prop('disabled', true);
			$("#divTabelaSKU").hide();
			$("#divTabelaSKU2").hide();
		}

	 });

    $("#mainCampaignsNav").addClass('active');
    $("#addCampaignsNav").addClass('active');
    
    $('#start_date_pick').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR", 
		startDate: new Date(),
		todayBtn: true, 
		todayHighlight: true
	});
	$('#end_date_pick').datepicker({
		format: "dd/mm/yyyy",
		autoclose: true,
		language: "pt-BR", 
		startDate: new Date(+new Date() + 86400000),
		todayBtn: true, 
		todayHighlight: true
	});

	$("#btnSave").click(function(){
		
		if(confirm("Deseja realmente salvar essa campanha?")){
			
    		$("#btnVoltar").prop('disabled', true);
    		$("#btnSave").prop('disabled', true);

    		var dados = $("#formCadastro").serialize();
    		var pageURL = base_url.concat("campaigns/salvarcampaigns");
    		
    		$.post( pageURL, dados, function( data ) {

    			var retorno = data.split(";");
    			if(retorno[0] == "0"){
    				$("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
        		              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
        		              '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+retorno[1]+
        		            '</div>');
    				$("#btnVoltar").prop('disabled', false);
    			}else{
    				$("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
    	              '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
    	              '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+retorno[1]+
    	            '</div>'); 
    				$("#btnSave").prop('disabled', false);
    				$("#btnVoltar").prop('disabled', false);
    			}
    			
    		});

		}

	});


	$("#btnAddSKU").click( function(){

        if( $("#txt_sku").val() == ""){
            alert("Selecione um SKU");
            return false;
        }

        if( $("#slc_marketplace").val() == ""){
            alert("Selecione um marketplace");
            return false;
        }

		var dados = $("#formCadastro").serialize()+"&"+$("#frmRadio").serialize();
		var pageURL = base_url.concat("campaigns/addprodutotempcampanha");
    		
		$.post( pageURL, dados, function( data ) {

		     filtro = $("#formCadastro").serialize();
				$('#tabelaSKUAdd').DataTable().destroy();
				manageTableProducts = $('#tabelaSKUAdd').DataTable({
			        'ajax': base_url + 'campaigns/getproductscampanhatemp?'+filtro,
			        'order': []
			      });
			
		});

        $("#txt_sku").val("");
        
    });
					   
  });  

 function removerPedidoCampanha(id){

		if(confirm("Deseja remover esse produto da campanha?")){

			$("#messages").html("");

	    	var pageURL = base_url.concat("campaigns/removerprodutotempcampanha");
	    	var form = $("#formCadastro").serialize()+"&produto="+id;
	    
	    	$.post( pageURL, form , function( data ) {

	    		if(data == true){
	    			$("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
	        	            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	        	            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+"Produto removido com sucesso"+
	        	          '</div>');
	    		}else{
	    			$("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
	        	            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
	        	            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+"Erro ao remover produto"+
	        	          '</div>');
	    		}
	        	
	    		$('#tabelaSKUAdd').DataTable().ajax.reload();
	    	});

		}
		
	}


</script>
