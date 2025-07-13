<!--
SW Serviços de Informática 2019

Index de Fornecedores

-->

<!-- Content Wrapper. Contains page content -->

<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
$this->load->view('templates/content_header', $data);?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12" id="rowcol12">

                    <?php if ($this->session->flashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?=$this->session->flashdata('success');?>
                        </div>
                    <?php elseif ($this->session->flashdata('error')): ?>
                        <div class="alert alert-error alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?=$this->session->flashdata('error');?>
                        </div>
                    <?php endif;?>                    
                    <form role="form" action="<?php echo base_url('shippingrules/saverules') ?>" method="post"  enctype="multipart/form-data" id="form-create-rules" >
                    <div class="box">
                        <div class="box-body">
                        <div id="console-event"></div>
                            <div class="row">
                                <div class="form-group col-md-4">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_name');?></label>
          				            <input class="form-control" id="name_rule" name="rules[name]" placeholder="<?=$this->lang->line('application_rules_name');?>"><?=set_value('name_rule')?>
	                            </div>
                                <div class="form-group col-md-4">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_dt_validated_start');?></label>
          				            <input class="form-control" id="dt_start" name="rules[dt_start]" placeholder="<?=$this->lang->line('application_rules_dt_validated_start');?>"><?=set_value('dt_start')?>
	                            </div>
                                <div class="form-group col-md-4">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_dt_validated_end');?></label>
          				            <input class="form-control" id="dt_end" name="rules[dt_end]" placeholder="<?=$this->lang->line('application_rules_dt_validated_end');?>"><?=set_value('dt_end')?>
	                            </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-3">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_conditions');?></label>
                                      <button type="button" style="margin-top:5%;" class="form-control btn btn-default float-center" id="estado_regiao">Estado/Região</button>
                                      <button type="button" style="margin-top:5%;" class="form-control btn btn-default float-center" id="cep_regiao">CEP Região</button>
                                      <button type="button" style="margin-top:5%;" class="form-control btn btn-default float-center" id="transportadora">Transportadora</button>
                                      <button type="button" style="margin-top:5%;" class="form-control btn btn-default float-center" id="val_pedido">Valor do Pedido</button>
                                      <button type="button" style="margin-top:5%;" class="form-control btn btn-default float-center" id="areas_especiais">Áreas Especiais</button>
                                      <button type="button" style="margin-top:5%;" class="form-control btn btn-default float-center" id="cat_produtos">Categoria de Produtos</button>
                                      <button type="button" style="margin-top:5%;" class="form-control btn btn-default float-center" id="canal_vendas">Canal de Vendas</button>
                                      <button type="button" style="margin-top:5%;" class="form-control btn btn-default float-center" id="periodo">Período</button>
                                      <button type="button" style="margin-top:5%;" class="form-control btn btn-default float-center" id="grupo_produtos">Grupo de Produtos</button>
                                      <button type="button" style="margin-top:5%;" class="form-control btn btn-default float-center" id="produto">Produto</button>
	                            </div>
                                <div class="form-group col-md-6" >
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_action');?></label>                                      
                                      <select class="form-control" name="rules[action]" id="rules_action">
                                        <option value=""></option>
                                        <option value="" > Excluir</option>
                                        <option value="" > Excluir Transportadoras Selecionadas</option>
                                        <option value="" > Adicionar no preço uma porcentagem do valor da nota(X%)</option>
                                        <option value="" > Fixar o preço em uma porcentagem do valor da nota(X%)</option>
                                        <option value="" > Nunca aplicar Frete grátis</option>
                                        <option value="" > Fixar o Preço unitário (R$ X)</option>
                                        <option value="" > Alterar prazo por intervalo de janela fixa</option>
                                        <option value="" > Alterar o preço (X%)</option>
                                        <option value="" > Alterar o preço(R$)</option>
                                        <option value="" > Fixar o preço em</option>
                                        <option value="" > Adicionar X dias no prazo</option>
                                        <option value="" > Fixar o prazo em X dias</option>
                                        <option value="" > Frete grátis para o mais barato</option>
                                    </select>
	                            </div>
                                <div class="form-group col-md-3">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_complement_action');?></label>
          				            <input class="form-control" id="complement_action" name="rules[complement_action]" placeholder="<?=$this->lang->line('application_rules_complement_action');?>"><?=set_value('complement_action')?>
	                            </div>
                            </div>
                        </div>
                        <!-- /.box-body -->
                    </div>
                    <!-- /.box -->
                        <div class="box" id="campos_estado_regiao">
                            <div class="box-header">
                                <h3 class="box-title"><?=$this->lang->line('application_rules_state_region');?></h3>
                            </div>
                            <div class="box-body">
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="group_name"><?=$this->lang->line('application_rules_region');?></label>
                                        <select class="form-control js-example-basic-multiple" name="rules[region]" multiple="multiple" id="rules_region">
                                            <option value=""></option>
                                            <?php foreach ($states as $key => $value) {?>
                                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                            <?php }?>
                                        </select>
                                    </div>
                            
                                </div>
                            </div>
                        </div>
                    <!-- final form application_rules_state_region -->
                    <!-- /.box -->
                    <div class="box" id="campos_cep_regiao">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_rules_zipcode');?></h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="group_name"><?=$this->lang->line('application_rules_zipcode_zone');?></label>                                    
                                    <input class="form-control" maxlength="9" onkeydown="Mascara('CEP',this,event);" id="zipcode_start" name="rules[zipcode_start]" placeholder="De"><?=set_value('zipcode_start')?>
                                </div>                                
                                <div class="form-group col-md-4">
                                    <label for="group_name">&nbsp;</label>
                                    <input class="form-control" maxlength="9" onkeydown="Mascara('CEP',this,event);" id="zipcode_end" name="rules[zipcode_end]" placeholder="Para"><?=set_value('zipcode_end')?>
                                </div>
                                <div class="form-group col-md-4">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_select_current');?></label>
	                            </div>
                            </div>
                            <div class="row">                                
                                <div class="form-group col-md-4">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_zipcode_import');?></label>
                                      <input type="file" class="form-control" id="rules_zipcode_import" name="rules_zipcode_import" placeholder="De"><?=set_value('rules_zipcode_import')?>
	                            </div>                                
                            </div>
                        </div>
                        <div class="box-footer">
                            <button class="btn btn-primary"><i class="fa fa-plus"></i> <?=$this->lang->line('application_rules_zipcode_import_button');?></button>
                            <button class="btn btn-primary"><i class="fa fa-plus"></i> <?=$this->lang->line('application_rules_zipcode_add_zone');?></button>
                            <a href="" class="btn btn-primary"><i class="fa fa-plus"></i> <?=$this->lang->line('application_rules_zipcode_model');?></a>
                        </div>
                    </div>
                    <!-- /.box -->

                    <div class="box" id="campos_transportadora">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_rules_transport');?></h3>
                        </div>
                        <div class="box-body">
                            <div class="row">

                            <div class="form-group col-lg-12">
                            <label for="group_name"><?=$this->lang->line('application_rules_transport');?></label>
                                    <select class="form-control js-example-basic-multiple" name="rules[transport][]" multiple="multiple" id="rules_transport">
                                        <option value=""></option>
                                        <?php foreach ($shippingCompany as $key => $value) {?>
                                            <option value="<?php echo $value['id']; ?>"><?php echo $value['name']; ?></option>
                                        <?php }?>
                                    </select>
                            </div>
                                <!-- <div class="form-group col-md-4">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_transport');?></label>
                                    <select class="form-control js-example-basic-multiple" name="rules_transport[]" multiple="multiple" id="rules_transport">
                                        <option value=""></option>
                                        <?php foreach ($shippingCompany as $key => $value) {?>
                                            <option value="<?php echo $value['id']; ?>"><?php echo $value['name']; ?></option>
                                        <?php }?>
                                    </select>
	                            </div> -->
                                <!-- <div class="form-group col-md-4">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_select_current');?></label>

	                            </div> -->
                            </div>
                        </div>
                        <!-- <div class="box-footer">
                            <button class="btn btn-primary"><i class="fa fa-plus"></i> <?=$this->lang->line('application_rules_add_transport');?></button>
                        </div> -->
                    </div>
                    <!-- /.box -->
                    <div class="box" id="campos_val_pedido">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_rules_order_price');?></h3>
                        </div>
                        <div class="box-body" id="groupOrderPrice">
                            <?php $rowOrderPrice = 0;?>
                            <div class="row">
                                <!-- <div class="form-group col-lg-3">
                                    <label for="group_name"><?=$this->lang->line('application_rules_zipcode_zone');?></label>                                
                                </div>
                                <div class="form-group col-lg-3">
                                    <label for="group_name"><br></label>                                    
                                </div> -->
                            </div>
                        </div>
                        <div class="box-footer">
                            <button class="btn btn-primary" type="button" onclick="addRowOrderPrice();"><i class="fa fa-plus"></i> <?=$this->lang->line('application_rules_add_order_price');?></button>
                        </div>
                    </div>
                    <!-- /.box -->
                    <div class="box" id="campos_areas_especiais">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_rules_special_area');?></h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label for="group_name"><?=$this->lang->line('application_rules_zipcode_zone');?></label>
                                    <input class="form-control" id="zipcode_zone_start" name="rules[zipcode_zone_start]" placeholder="De"><?=set_value('zipcode_zone_start')?>
                                    <input class="form-control" id="zipcode_zone_end" name="rules[zipcode_zone_end]" placeholder="Para"><?=set_value('zipcode_zone_end')?>
                                </div>
                                <div class="form-group col-md-4">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_select_area');?></label>
                                    <div><input type="checkbox" value="" name="rules[riskzone][]"> Destino Área de Risco</div>
                                    <div><input type="checkbox" value="" name="rules[riskzone][]"> Destino Área de Risco: Entrega domiciliar diferenciada</div>
                                    <div><input type="checkbox" value="" name="rules[riskzone][]"> Destino Área de Risco: Entrega interna</div>
                                    <div><input type="checkbox" value="" name="rules[riskzone][]"> Destino cidade divisa</div>
                                    <div><input type="checkbox" value="" name="rules[riskzone][]"> Destino área metropolitana</div>
	                            </div>
                                <div class="form-group col-md-4">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_select_current');?></label>
	                            </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.box -->
                    <div class="box" id="campos_cat_produtos">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_rules_cat_product');?></h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-md-12">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_select_cat');?></label>
                                    <select class="form-control" name="rules[category][]" id="rules_region_type">
                                        <option value=""></option>
                                    </select>
	                            </div>
                                <!-- <div class="form-group col-md-4">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_select_current');?></label>
	                            </div> -->
                            </div>
                        </div>
                        <div class="box-footer">
                            <button class="btn btn-primary"><i class="fa fa-plus"></i> <?=$this->lang->line('application_rules_add_cat');?></button>
                        </div>
                    </div>
                    <!-- /.box -->
                    <div class="box" id="campos_canal_vendas">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_rules_buy_channel');?></h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-md-12" id="buychannel">
                                    <label for="group_name"><?=$this->lang->line('application_rules_region');?></label>
                                    <?php $rowBuyChannel = 0 ;?>                                    
                                    <!-- <div class="input-group input-group-sm" id="divRowBuychnnel<?php echo $rowBuyChannel;?>">
                                        <input type="text" class="form-control" name="rules_buy_channel[]">
                                        <span class="input-group-btn">
                                            <button type="button" onclick="removeRow('divRowBuychnnel<?php echo $rowBuyChannel;?>');" class="btn btn-danger btn-flat"><i class="fa fa-minus-circle"></i></button>
                                        </span>
                                    </div> -->
                                </div>
                            </div>
                            <div class="box-footer">
                                <a class="btn btn-primary" onclick="addRowButChannel(<?php echo $rowBuyChannel;?>)"><i class="fa fa-plus"></i> <?=$this->lang->line('application_rules_add_buy_channel');?></a>
                            </div>
                        </div>
                    </div>
                    <!-- /.box -->
                    <div class="box" id="campos_periodo">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_rules_time_course');?></h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-md-4">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_week');?></label>
                                    <div><input type="checkbox" value="" name="rules[weekday][]"> Segunda-feira</div>
                                    <div><input type="checkbox" value="" name="rules[weekday][]"> Terça-feira</div>
                                    <div><input type="checkbox" value="" name="rules[weekday][]"> Quarta-feira</div>
                                    <div><input type="checkbox" value="" name="rules[weekday][]"> Quinta-feira</div>
                                    <div><input type="checkbox" value="" name="rules[weekday][]"> Sexta-feira</div>
                                    <div><input type="checkbox" value="" name="rules[weekday][]"> Sábado</div>
                                    <div><input type="checkbox" value="" name="rules[weekday][]"> Domingo</div>
	                            </div>
                                <div class="form-group col-md-4">
                                    <label for="group_name"><?=$this->lang->line('application_rules_time');?></label>
                                    <input class="form-control" id="weekday_start" name="rules[weekday][start]" placeholder="De"><?=set_value('weekday_start')?>
                                    <input class="form-control" id="weekday_end" name="rules[weekday][end]" placeholder="Para"><?=set_value('weekday_end')?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.box -->
                    <div class="box" id="campos_grupo_produtos">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_rules_product_group');?></h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-md-4">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_select_product_group');?></label>
                                    <input class="form-control" type="text">
	                            </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button class="btn btn-primary"><i class="fa fa-plus"></i> <?=$this->lang->line('application_rules_add_product_group');?></button>
                        </div>
                    </div>
                    <!-- /.box -->
                    <div class="box" id="campos_produto">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_rules_product');?></h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="form-group col-md-4">
	                  	            <label for="group_name"><?=$this->lang->line('application_rules_info_product');?></label>
                                    <input class="form-control" type="text">
	                            </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button class="btn btn-primary"><i class="fa fa-plus"></i> <?=$this->lang->line('application_rules_add_product_group');?></button>
                        </div>
                    </div>
                    <div class="box">                        
                        <div class="box-footer">
                        <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                        <a href="<?=base_url('shippingrules') ?>" class="btn btn-warning"><?=$this->lang->line('application_cancel');?></a>
                        </div>
                    </div>
                    <!-- /.box -->
                    </form>
                </div>
                <!-- col-md-12 -->
            </div>
            <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script type="text/javascript">
$(document).ready(function() {
    $('.js-example-basic-multiple').select2();
});

var rowBuyChannel = <?php echo ($rowBuyChannel) ? $rowBuyChannel : 0 ;?>

function addRowButChannel() {
    html  = '<div class="input-group input-group-sm" id="divRowBuychnnel'+rowBuyChannel+'">';
    html += '<input type="text" class="form-control" name="rules_buy_channel[]">';
    html += '<span class="input-group-btn">';
    html += '<button type="button" onclick="removeRowBuyChannel('+rowBuyChannel+');" class="btn btn-danger btn-flat"><i class="fa fa-minus-circle"></i></button>';
    html += '</span>';
    html += '</div>';
    
    $("#buychannel label").append(html);
    rowBuyChannel++;
}

var rowOrderPrice = <?php echo ($rowOrderPrice) ? $rowOrderPrice : 0 ;?>

function addRowOrderPrice() {
    html  = '<div class="row" id="rowOrderPrice'+rowOrderPrice+'">';
    html += '<div class="form-group col-lg-3">';
    html += '<input class="form-control" name="rules[order_price][start][]" placeholder="De">';
    html += '</div>';
    html += '<div class="form-group col-lg-3">                                ';
    html += '<input class="form-control" name="rules[order_price][end][]" placeholder="Para">';
    html += '</div>';
    html += '<div class="form-group col-md-3">';
    html += '<button type="button" onclick="removeRowOrderPrice('+rowOrderPrice+');" class="btn btn-danger btn-flat"><i class="fa fa-minus-circle"></i></button>';
    html += '</div>';
    html += '</div>'; 
    
    $("#groupOrderPrice").append(html);
    rowOrderPrice++;
}


function removeRowOrderPrice(idelement) {
    $('#rowOrderPrice'+idelement+'').remove();
}

function removeRowBuyChannel(idelement) {
    $('#divRowBuychnnel'+idelement+'').remove();
}

    $("#campos_estado_regiao").hide();
    $("#campos_cep_regiao").hide();
    $("#campos_transportadora").hide();
    $("#campos_val_pedido").hide();
    $("#campos_areas_especiais").hide();
    $("#campos_cat_produtos").hide();
    $("#campos_canal_vendas").hide();
    $("#campos_periodo").hide();
    $("#campos_grupo_produtos").hide();
    $("#campos_produto").hide();

$("button").click(function() {
    const expr = this.id;

    switch (expr) {
    case 'estado_regiao':
        $("#campos_estado_regiao").toggle();
    break;

    case 'cep_regiao':
        $("#campos_cep_regiao").toggle();
    break;

    case "transportadora":
        $("#campos_transportadora").toggle();
    break;

    case "val_pedido":
        $("#campos_val_pedido").toggle();
    break;

    case "areas_especiais":
        $("#campos_areas_especiais").toggle();
    break;

    case "cat_produtos":
        $("#campos_cat_produtos").toggle();
    break;

    case "canal_vendas":
        $("#campos_canal_vendas").toggle();
    break;

    case "periodo":
        $("#campos_periodo").toggle();
    break;

    case "grupo_produtos":
        $("#campos_grupo_produtos").toggle();
    break;

    case "produto":
        $("#campos_produto").toggle();
    break;

  default:
   
}
});



</script>

