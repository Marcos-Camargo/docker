<!--
SW Serviços de Informática 2019

Criar Grupos de Acesso

-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_add";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

            <div id="messages2" name="messages2"></div>

                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <div class="box">
                    <form role="form" id="frmCadastrar" name="frmCadastrar" action="" method="post">
                        <div class="box-body">

                            <?php
                            if (validation_errors()) {
                                foreach (explode("</p>", validation_errors()) as $erro) {
                                    $erro = trim($erro);
                                    if ($erro != "") { ?>
                                        <div class="alert alert-error alert-dismissible" role="alert">
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span></button>
                                            <?php echo $erro . "</p>"; ?>
                                        </div>
                                    <?php }
                                }
                            } ?>

                            <input type="hidden" class="form-control" id="hdnLote" name="hdnLote"
                                   value="<?php echo $hdnLote ?>">
                            <input type="hidden" class="form-control" id="hdnEdit" name="hdnEdit"
                                   value="<?php echo $hdnEdit ?>">

                            <!-- desabilitando os campos -->
                            <div class="col-md-12 col-xs-12">
                                <label for="group_isadmin">Nome da Promoção</label>
                                <input type='text' disabled="disabled" class="form-control" id='txt_nome_promocao' name="txt_nome_promocao"
                                       autocomplete="off" placeholder="Nome da Promoção"
                                       value="<?php echo $promocao['nome']; ?>"
                                       <?php echo $promocao['lock_edit_promocao']; ?>
                                       />
                                </select>
                            </div>

                            <div class="col-md-12 col-xs-12">
                                <label for="group_name">Descrição da Promoção</label>
                                <textarea disabled="disabled" class="form-control" id="txt_desc_promocao" name="txt_desc_promocao"
                                          placeholder="Descrição da Promoção"
                                          <?php echo $promocao['lock_edit_promocao']; ?>
                                          ><?php echo $promocao['descricao']; ?></textarea>
                            </div>

                            <div class="col-md-2 col-xs-2">
                                <label for="group_isadmin"><?= $this->lang->line('application_runmarketplaces'); ?></label>
                                <select disabled="disabled" class="form-control" id="slc_marketplace" name="slc_marketplace"
                                <?php echo $promocao['lock_edit_promocao']; ?>>
                                    <option value="">~~SELECT~~</option>
                                    <option value="Todos">Todos</option>
                                    <?php foreach ($mktplaces as $mktplace): ?>
                                        <option value="<?php echo trim($mktplace['apelido']); ?>"><?php echo trim($mktplace['mkt_place']); ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>

                            <div class="col-md-2 col-xs-2">
                                <label for="txt_start_date"><?= $this->lang->line('application_start_date'); ?>
                                    (*)</label>
                                <div class='input-group date' id='start_date_pick' name="start_date_pick">
                                    <input disabled="disabled" type='text' class="form-control" id='txt_start_date' name="txt_start_date"
                                           autocomplete="off" value="<?php echo $promocao['data_inicio']; ?>"
                                           <?php echo $promocao['lock_edit_promocao']; ?>/>
                                    <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
                                </div>
                            </div>

                            <div class="col-md-2 col-xs-2">
                                <label for="start_date_hour">Hora Início(*)</label>
                                <input disabled="disabled" type='time' class="form-control" id='start_date_hour' name="start_date_hour"
                                       autocomplete="off" value="<?php echo $promocao['data_inicio_hora']; ?>"
                                       min="00:00" max="23:59"
                                       <?php echo $promocao['lock_edit_promocao']; ?>/>
                            </div>

                            <div class="col-md-2 col-xs-2">
                                <label for="txt_start_date"><?= $this->lang->line('application_end_date'); ?>(*)</label>
                                <div class='input-group date' id='end_date_pick' name="end_date_pick">
                                    <input disabled="disabled" type='text' class="form-control" id='txt_end_date' name="txt_end_date"
                                           autocomplete="off" value="<?php echo $promocao['data_fim']; ?>"
                                           <?php echo $promocao['lock_edit_promocao']; ?>/>
                                    <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
                                </div>
                            </div>

                            <div class="col-md-2 col-xs-2">
                                <label for="end_date_hour">Hora Fim(*)</label>
                                <input disabled="disabled" type='time' class="form-control" id='end_date_hour' name="end_date_hour"
                                       autocomplete="off" value="<?php echo $promocao['data_fim_hora']; ?>" min="00:00"
                                       max="23:59"
                                       <?php echo $promocao['lock_edit_promocao']; ?>/>
                            </div>

                            <div class="col-md-2 col-xs-2" <?php if ($promocao['percentual_seller'] == "") {
                                echo 'style="display: none"';
                            } ?>>
                                <label for="group_isadmin">Taxa % Desconto Seller</label>
                                <input disabled="disabled"  type='text' class="form-control" id='txt_desconto_seller'
                                       name="txt_desconto_seller" autocomplete="off"
                                       value="<?php echo $promocao['percentual_seller']; ?>%" readonly="readonly"/>
                                </select>
                            </div>

                            <div class="form-group col-md-2 col-xs-2" 
                                <?php 
                                    if(!$promocao['tipo_promocao'] == ""){
                                        if(!($promocao['tipo_promocao'] == "checked" && $promocao_compartilhada))
                                        {
                                            //echo "style=\"display: none\"";
                                        } 
                                    }                                
                                ?>
                            >
                                <label for="typepromo"><?= $this->lang->line('application_promotion_type'); ?>:</label>
                                <div class='input-group'>
                                    <input disabled="disabled"  id="typeAtivo" name="typeAtivo" type="checkbox" value="1"
                                           data-toggle="toggle"
                                           data-on="<?= $this->lang->line('application_active'); ?>"
                                           data-off="<?= $this->lang->line('application_inactive'); ?>"
                                           data-onstyle="success"
                                           data-offstyle="primary" <?php echo $promocao['ativo']; ?> >
                                </div>
                            </div>

                            <div class="form-group col-md-2 col-xs-2" 
                                <?php 
                                if(!($promocao_compartilhada))
                                {
                                    //echo "style=\"display: none\"";
                                } 
                                ?>
                            >
                                <label for="typepromo"><?= $this->lang->line('application_promotions'); ?>:</label>
                                <div class='input-group'>
                                    <input disabled="disabled"  id="typepromo" name="typepromo" type="checkbox" value="1"
                                           data-toggle="toggle" data-on="Pública" data-off="Privada"
                                           data-onstyle="success"
                                           data-offstyle="danger" <?php echo $promocao['tipo_promocao']; ?> >
                                </div>
                            </div>

                            

                        </div>

                        <!-- desabilitando botões de salvar -->
                        <!--

                        <div class="box-footer">
                            <button type="button" id="btnSave" name="btnSave" disabled="disabled"
                                    class="btn btn-primary"><?= $this->lang->line('application_save'); ?></button>
                            <button type="button" id="btnVoltar" name="btnVoltar"
                                    class="btn btn-warning"><?= $this->lang->line('application_back'); ?></button>
                        </div>

                        -->

                    </form>


                    <div id="divTabelaProdutosAdd" name="divTabelaProdutosAdd" style="display:block">
                        <div class="box">
                            <div class="box-header">
                                <h3 class="box-title">Produtos em aprovação</h3>
                            </div>
                            <div class="box-body">
                                <table id="TabelaProdutosAdd" name="TabelaProdutosAdd"
                                       class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th><?= $this->lang->line('application_id'); ?></th>
                                        <th><?= $this->lang->line('application_store'); ?></th>
                                        <th><?= $this->lang->line('application_sku'); ?></th>
                                        <th><?= $this->lang->line('application_name'); ?></th>
                                        <th><?= $this->lang->line('application_category'); ?></th>
                                        <th width="80px"><?= $this->lang->line('application_price_from'); ?></th>
                                        <th width="80px"><?= $this->lang->line('application_price_sale'); ?></th>
                                        <th width="70px"><?= $this->lang->line('application_discount'); ?></th>
                                        <th width="70px"><?= $this->lang->line('application_qty'); ?></th>
                                        <th width="70px"><?= $this->lang->line('application_promotion_qty'); ?></th>
                                        <th width="80px"><?= $this->lang->line('application_start_date'); ?></th>
                                        <th width="80px"><?= $this->lang->line('application_end_date'); ?></th>
                                        <th><?= $this->lang->line('application_action'); ?></th>
                                    </tr>
                                    </thead>

                                </table>
                            </div>
                        </div>
                    </div>

                    <?php
                    //desabilitando todas as grids em php
                    /*
                     * ?>
                    <form id="filtroCategoria" name="filtroCategoria">
                        <div class="box">
                            <div class="box-header">
                                <h3 class="box-title">Filtro <?= $this->lang->line('application_category'); ?></h3>
                            </div>
                            <div class="box-body">
                                <div class="col-md-2 col-xs-2">
                                    <label for="group_isadmin"><?= $this->lang->line('application_search'); ?></label>
                                    <input id="search" name="search" class="form-control"/>
                                </div>
                                <div class="col-md-2 col-xs-2">
                                    <label for="group_isadmin"><?= $this->lang->line('application_category'); ?> Nível
                                        1</label>
                                    <select class="form-control filtros" id="slc_categoria_n1" name="slc_categoria_n1">
                                        <option value="">~~SELECT~~</option>
                                        <?php foreach ($categoriaN1 as $n1): ?>
                                            <option value="<?php echo trim($n1['categoryN1']); ?>"><?php echo trim($n1['categoryN1']); ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="col-md-2 col-xs-2">
                                    <label for="group_isadmin"><?= $this->lang->line('application_category'); ?> Nível
                                        2</label>
                                    <select class="form-control filtros" id="slc_categoria_n2" name="slc_categoria_n2">
                                        <option value="">~~SELECT~~</option>
                                        <?php foreach ($categoriaN2 as $n2): ?>
                                            <option value="<?php echo trim($n2['categoryN2']); ?>"><?php echo trim($n2['categoryN2']); ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="col-md-2 col-xs-2">
                                    <label for="group_isadmin"><?= $this->lang->line('application_category'); ?> Nível
                                        3</label>
                                    <select class="form-control filtros" id="slc_categoria_n3" name="slc_categoria_n3">
                                        <option value="">~~SELECT~~</option>
                                        <?php foreach ($categoriaN3 as $n3): ?>
                                            <option value="<?php echo trim($n3['categoryN3']); ?>"><?php echo trim($n3['categoryN3']); ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>

                                <div class="col-md-5 col-xs-5">
                                    <label for="group_isadmin"><?= $this->lang->line('application_sku'); ?></label>
                                    <input type="text" class="form-control" id="txt_sku" name="txt_sku" value="">
                                </div>

                                <div class="col-md-1 col-xs-1">
                                    <br>
                                    <button type="button" id="btnAddSku" name="btnAddSku" class="btn btn-success">
                                        Adicionar SKU
                                    </button>
                                </div>

                            </div>
                        </div>
                    </form>


                    <div id="divTabelaProdutosList" name="divTabelaProdutosList" style="display:block">
                        <div class="box">
                            <div class="box-header">
                                <h3 class="box-title"><?= $this->lang->line('application_products'); ?></h3>
                            </div>
                            <div class="box-body">

                                <table id="tabelaProdutosList" name="tabelaProdutosList"
                                       class="table table-bordered table-striped">
                                    <thead>
                                    <tr>
                                        <th><?= $this->lang->line('application_id'); ?></th>
                                        <th><?= $this->lang->line('application_store'); ?></th>
                                        <th><?= $this->lang->line('application_sku'); ?></th>
                                        <th><?= $this->lang->line('application_name'); ?></th>
                                        <th><?= $this->lang->line('application_category'); ?> Nível 1</th>
                                        <th><?= $this->lang->line('application_category'); ?> Nível 2</th>
                                        <th><?= $this->lang->line('application_category'); ?> Nível 3</th>
                                        <th><?= $this->lang->line('application_qty'); ?></th>
                                        <th><?= $this->lang->line('application_price'); ?></th>
                                        <th><?= $this->lang->line('application_action'); ?></th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>

                        </div>
                    </div>


                    <div id="retornoTeste" name="retornoTest"></div>

                    <?php
                    */
                    ?>

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

<script type="text/javascript">
var manageTableProducts;
var manageTableProductsAdd;
var base_url = "<?php echo base_url(); ?>";
var indice = 0;
var marketplaceVal = "<?php echo $promocao['marketplace'];?>";

var categoryN1 = "<?php echo $promocao['categoryN1'];?>";
var categoryN2 = "<?php echo $promocao['categoryN2'];?>";
var categoryN3 = "<?php echo $promocao['categoryN3'];?>";

$(document).ready(function() {

    $("#mainPromotionsNav").addClass('active');
    $("#addPromotionNav").addClass('active');

	$("#slc_marketplace").val(marketplaceVal);

	$("#slc_categoria_n1").val(categoryN1);
	$("#slc_categoria_n2").val(categoryN2);
	$("#slc_categoria_n3").val(categoryN3);


	if($('#slc_categoria_n1 option').length == "2"){
		$('#slc_categoria_n1').attr("disabled", true); 
	}

	if($('#slc_categoria_n2 option').length == "2"){
		$('#slc_categoria_n2').attr("disabled", true); 
	}

	if($('#slc_categoria_n3 option').length == "2"){
		$('#slc_categoria_n3').attr("disabled", true); 
	}

    if ( !$('#txt_start_date').is('[readonly]') ) {
        
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
            startDate: new Date(),
            todayBtn: true, 
            todayHighlight: true
        });
        $("#start_date_pick").on("changeDate", function (e) {
            var atual = new Date(e.date);
            var maisum = new Date(atual.setTime(atual.getTime() + 1 * 86400000 )); 
            $('#end_date_pick').datepicker('setStartDate', Date());
        });

    }

    var categoryN1 = "<?php echo $promocao['categoryN1'];?>";
    var categoryN2 = "<?php echo $promocao['categoryN2'];?>";
    var categoryN3 = "<?php echo $promocao['categoryN3'];?>";

        $("#btnVoltar").click(function () {
            window.location.assign(base_url.concat("promotions"));
        });


        $("#btnAddSku").click( function(){

            $("#messages2").html("");

            if( $("#txt_sku").val() == "" || 
                $("#txt_start_date").val() == "" ||
                $("#start_date_hour").val() == "" ||
                $("#txt_end_date").val() == "" ||
                $("#end_date_hour").val() == ""){

                $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                        '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+"Os campos de data e SKU são obrigatórios para incluir produtos em promoções"+
                        '</div>');
                return false;

            }


            var pageURL = base_url.concat("promotions/addskumassivo");
            var form = $("#frmCadastrar").serialize()+"&SKU="+$("#txt_sku").val();
            console.log(form);

            $.post( pageURL, form , function( data ) {
                console.log(data);
                $("#txt_sku").val("");
                $('#tabelaProdutosList').DataTable().ajax.reload();
                $('#TabelaProdutosAdd').DataTable().ajax.reload();
            });

        });


        $("#btnSave").click(function () {

            if (confirm("Deseja realmente salvar essa promoção?")) {

                $("#messages2").html("");

                var msg = "";

                if ($("#txt_nome_promocao").val() == "") {
                    msg = msg + "Campo Nome da Promoção é de preenchimento obrigatório<br>";
                }

                if ($("#txt_desc_promocao").val() == "") {
                    msg = msg + "Campo Descrição da Promoção é de preenchimento obrigatório<br>";
                }

                if ($("#slc_marketplace").val() == "") {
                    msg = msg + "Campo MarketPlaces é de preenchimento obrigatório<br>";
                }

                if ($("#txt_start_date").val() == "") {
                    msg = msg + "Campo Data de Início é de preenchimento obrigatório<br>";
                }

                if ($("#start_date_hour").val() == "") {
                    msg = msg + "Campo Hora Início é de preenchimento obrigatório<br>";
                }

                if ($("#txt_end_date").val() == "") {
                    msg = msg + "Campo Data Final é de preenchimento obrigatório<br>";
                }

                if ($("#end_date_hour").val() == "") {
                    msg = msg + "Campo Hora Fim é de preenchimento obrigatório<br>";
                }

                if(msg != ""){

                    $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                            '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+msg+
                            '</div>');
                    return false;

                }

                var pageURL = base_url.concat("promotions/insertpromo");
                var form = $("#frmCadastrar").serialize();

                $.post(pageURL, form, function (data) {

                    var saida = data.split(";");

                    if(saida[0] == "0"){
                        $("#messages2").html('<div class="alert alert-success alert-dismissible" role="alert">' +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + "Promoção criada com sucesso" +
                            '</div>');

                        $("#hdnEdit").val(saida[1]);
                        
                    } else {
                        $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">' +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                            '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + "Erro ao criar promoção" +
                            '</div>');
                    }

                });

            }

        });

        montaDataTable();

        var sellers = $("#txt_desconto_seller").val();
        var seller = sellers.replace("%", "");

        manageTableProductsAdd = $('#TabelaProdutosAdd').DataTable({
            'ajax': base_url + 'promotions/fetchProductsPromotionListData/' + $("#hdnLote").val() + '/' + seller,
            'order': []
        });

        $('.filtros').change(() => {
            montaDataTable();
        })
    });

    let executeSearch = true;
    $('#search').on('keyup', () => {
        if (executeSearch) {
            setTimeout(()=>{
                montaDataTable();
                executeSearch = true;
            },500)
        }
        executeSearch = false;
    })

    function montaDataTable() {
        if ($('#tabelaProdutosList').length) {
            $('#tabelaProdutosList').DataTable().destroy();
        }

        filtro = $("#filtroCategoria").serialize() + "&hdnLote=" + $("#hdnLote").val();

        tabelaProdutos = $('#tabelaProdutosList').DataTable({
            "language": {"url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"},
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            "ajax": base_url + 'promotions/fetchProductsListData?' + filtro,
            'searching': false,
        });
    }

    function incluirPedidoPromocao(id) {

        //remove a função do botao pois promocoes foi desativada apenas mostra o que tem
        return false;

        if (
            $("#txt_start_date").val() == "" ||
            $("#start_date_hour").val() == "" ||
            $("#txt_end_date").val() == "" ||
            $("#end_date_hour").val() == "") {

            $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">' +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>' + "Os campos de data são obrigatórios para incluir produtos em promoções" +
                '</div>');
            return false;

        }

        $("#messages2").html("");

        var pageURL1 = base_url.concat("promotions/checkaddproductpromotiontemp");
        var form1 = $("#frmCadastrar").serialize() + "&produto=" + id;

        $.post(pageURL1, form1, function (data) {

            var pageURL = base_url.concat("promotions/addproductpromotiontemp");
            var form = $("#frmCadastrar").serialize() + "&produto=" + id;

            if(data){
                if (confirm("Este produto já está em outra promoção, deseja incluir ele nesta?")) {
                    $.post(pageURL, form, function (data) {
                        $('#tabelaProdutosList').DataTable().ajax.reload();
                        $('#TabelaProdutosAdd').DataTable().ajax.reload();
                    });
                }
            }else{
                $.post(pageURL, form, function (data) {
                    $('#tabelaProdutosList').DataTable().ajax.reload();
                    $('#TabelaProdutosAdd').DataTable().ajax.reload();
                });
            }
            
        });

    }

    function aprovarPedidoPromocao(id) {

        if (confirm("Deseja aprovar esse produto na promoção?")) {

            $("#messages2").html("");

            var pageURL = base_url.concat("promotions/aproveproductpromotion");

            var preco = $("#preco_" + id).val();
            var qtdPromo = $("#qtd_" + id).val();
            var desconto = $("#qtd_percent_" + id).val();

            var form = $("#frmCadastrar").serialize() + "&produto=" + id + "&preco=" + preco + "&desconto=" + desconto + "&qtdPromo=" + qtdPromo;
            console.log(form);
            $.post(pageURL, form, function (data) {

                if (data == true) {
                    $("#messages2").html('<div class="alert alert-success alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + "Produto aprovado com sucesso" +
                        '</div>');
                } else {
                    $("#messages2").html('<div class="alert alert-warning alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + "Erro ao aprovar produto" +
                        '</div>');
                }

                // $('#tabelaProdutosList').DataTable().ajax.reload();
                $('#TabelaProdutosAdd').DataTable().ajax.reload();
            });

        }

    }

    function removerPedidoPromocao(id) {

        //removendo interações de promocoes
       return false;

        if (confirm("Deseja remover esse produto da promoção?")) {

            $("#messages2").html("");

            var pageURL = base_url.concat("promotions/removeproductpromotion");
            var form = $("#frmCadastrar").serialize() + "&produto=" + id;

            $.post(pageURL, form, function (data) {

                if (data == true) {
                    $("#messages2").html('<div class="alert alert-success alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + "Produto removido com sucesso" +
                        '</div>');
                } else {
                    $("#messages2").html('<div class="alert alert-success alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>' + "Erro ao remover produto" +
                        '</div>');
                }

                // $('#tabelaProdutosList').DataTable().ajax.reload();
                $('#TabelaProdutosAdd').DataTable().ajax.reload();
            });

        }

    }

</script>