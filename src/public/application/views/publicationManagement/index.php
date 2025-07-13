<div class="content-wrapper">
    <?php
    $data['page_now'] = 'publication_management';
    $data['pageinfo'] = "";
    $this->load->view('templates/content_header', $data);
    ?>
    <style>
        button.btn.dropdown-toggle.btn-blue {
            background-color: #fff;
            border: 1px solid #cecece;
            border-radius: 0;
        }
        .modo-box{
            width: -webkit-fill-available;
            backgroud-color:#059!important;
            height: fit-content;
        }
        .m-space{
            padding:0px;
        }
        .col-min{
            min-width: 300px;
        }
        .col-max{
            width:100%;
        }
        .box-fix{
            height:350px;
            overflow:auto;
        }
        .row.pd-right{
            margin-right: 0px;
        }
        @media(max-width:991px){
            .btn-hidden{
                display:none;
            }
            .row.pd-right{
                margin-right: 0px;
            }
        }
        #showActions{
            box-shadow:none;
        }
    </style>
    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div id="messages"></div>
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <div class="box box-primary" id="collapseFilter">
                    <div class="box-body">

                        <div class="col-sm-12 text-right btn-hidden" style="padding-right: 0px;">
                            <button class="btn btn-primary grid_view"><i id="grid_view_icon" class="fa fa-th-large"></i></button>
                        </div>

                        <div class="container-fluid pb-5">

                            <div class="col-md-4 col-min" id="grid_view_one">
                                <div class="row">
                                    <h2 class="text-bold total_incomplete_products">Aguarde...</h2>
                                    <h5><?= $this->lang->line('application_incomplete_products'); ?></h5>
                                    <button class="btn btn-primary btn-sm" id="btn_first_column_show" data-toggle="collapse" data-target="#demo1">
                                        <i class="fa fa-eye"></i>
                                        <?= $this->lang->line('application_show_details'); ?>
                                    </button>
                                </div>

                                <div id="demo1" class="collapse">
                                    <div class="row pd-right">
                                    <div class="card card-body pt-5">
                                        <table class="table table-bordered table-striped pt-5" id="table1">
                                           <thead>
                                                <tr>
                                                    <th class="ignore">#</th>
                                                    <th>Motivo</th>
                                                    <th>Quantidade</th>
                                                    <th>Representa</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bodyIncompletes"></tbody>
                                        </table>
                                            <div class="pull-right">
                                            <div class="btn-group">
                                                <button class="btn btn-primary" id="export1">
                                                    <i class="fa fa-file-excel-o"></i> <?= $this->lang->line('application_data_export'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            </div>
                            <div class="col-md-4" id="grid_view_two">
                                <div class="row">
                                    <h2 class="text-bold total_transformation_errors">Aguarde...</h2>
                                    <h5><?= $this->lang->line('conciliation_title_erros_transform') ?></h5>
                                        <button class="btn btn-primary btn-sm" id="btn_second_column_show" data-toggle="collapse" data-target="#demo2">
                                        <i class="fa fa-eye"></i>
                                        <?= $this->lang->line('application_show_details'); ?>
                                    </button>
                                </div>
                                <div id="demo2" class="collapse">
                                    <div class="row">
                                    <div class="card card-body pt-5">
                                        <table class="table table-bordered table-striped pt-5" id="table2">
                                            <thead>
                                                <tr>
                                                    <th class="ignore">#</th>
                                                    <th>Marketplace</th>
                                                    <th>Motivo</th>
                                                    <th>Quantidade</th>
                                                    <th>Representa</th>
                                                </tr>
                                            </thead>
                                            <div>
                                            <tbody class="bodyErrorTransform"></tbody>
                                        </table>
                                        <div class="pull-right">
                                            <div class="btn-group">
                                                <button class="btn btn-primary" id="export2">
                                                    <i class="fa fa-file-excel-o"></i> <?= $this->lang->line('application_data_export'); ?>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="modo-box">
                                            <div class="col-sx-8 col-md-8 col-lg-8 m-space">
                                                <div class="form-group">
                                                    <input class="form-control" id="myInput" type="text" placeholder="Buscar..." autocomplete="off" />
                                                    <p id="emptyMsg2" style="display:none;">Nenhum registro encontrado.</p>
                                                </div>
                                            </div>
                                            <div class="col-sm-12 m-space">
                                            <div class="card card-body box-fix">
                                                <table class="table table-bordered table-striped pt-5" id="table3">
                                                    <thead>
                                                        <tr>
                                                            <th class="ignore">#</th>
                                                            <th>Marketplace</th>
                                                            <th>Erro</th>
                                                            <th>Total</th>
                                                            <th>Representa</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bodyResult"></tbody>
                                                </table>
                                            </div>
                                            <div class="row container-fluid pt-4">
                                                <a href="javascript:void(0)" class="btn btn-primary pull-left mb-4 btn-back">
                                                    <i class="fa fa-angle-double-left"></i>
                                                    Voltar</a>
                                                <button class="btn btn-primary pull-right" id="export3">
                                                    <i class="fa fa-file-excel-o"></i> <?= $this->lang->line('application_data_export'); ?>
                                                </button>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box box-primary mt-2" id="showActions">
                    <div class="container-fluid">
                        <span class="pull-right">&nbsp</span><h4><?= $this->lang->line('application_filter_advance'); ?></h4>
                    </div>
                    <div class="box-body">
                        <div class="container-fluid row">
                            <div class="col-md-3 col-sm-4 form-group">
                                <label for="buscavendedores"><?= $this->lang->line('application_search_category'); ?></label>
                                <select class="form-control selectpicker show-tick"
                                        id="buscarCatergoria"
                                        onchange="filter(this)"
                                        name="categoria[]"
                                        data-live-search="true"
                                        data-actions-box="true"
                                        data-style="btn-blue"
                                        data-selected-text-format="count > 1"
                                        title="<?= $this->lang->line('application_search_input'); ?>">
                                    <option value="0" selected="selected"><?= $this->lang->line('application_search_input'); ?></option>
                                    <?php foreach ($categories as $category) { ?>
                                        <option value="<?=$category['id'] ?>"><?=$category['name'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-md-3 col-sm-4 form-group">
                                <label for="buscavendedores"><?= $this->lang->line('application_search_seller'); ?></label>
                                <select class="form-control selectpicker show-tick"
                                        id="buscarloja"
                                        onchange="filter(this)"
                                        name="loja[]"
                                        data-live-search="true" data-actions-box="true" data-style="btn-blue"
                                        data-selected-text-format="count > 1"
                                        title="<?= $this->lang->line('application_search_input'); ?>">
                                    <option value="0" selected="selected"><?= $this->lang->line('application_search_input'); ?></option>
                                    <?php foreach ($stores as $store) { ?>
                                        <option value="<?=$store['id'] ?>"><?=$store['name'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-md-3 col-sm-4 form-group">
                                <label for="buscavendedores"><?= $this->lang->line('application_search_marketplace'); ?></label>
                                <select class="form-control selectpicker show-tick"
                                        id="buscarMarketplace"
                                        onchange="filter(this)"
                                        name="company"
                                        data-live-search="true" data-actions-box="true" data-style="btn-blue"
                                        data-selected-text-format="count > 1"
                                        title="<?= $this->lang->line('application_search_input'); ?>">
                                    <option value="0" selected="selected"><?= $this->lang->line('application_search_input'); ?></option>
                                    <?php foreach ($integrations as $integrate) { ?>
                                        <option value="<?=$integrate['id'] ?>"><?=$integrate['name'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-md-3 col-sm-4 form-group">
                                <label for="buscavendedores"><?= $this->lang->line('application_search_brands'); ?></label>
                                <select class="form-control selectpicker show-tick"
                                        id="buscarMarca"
                                        onchange="filter(this)"
                                        name="marca"
                                        data-live-search="true" data-actions-box="true" data-style="btn-blue"
                                        data-selected-text-format="count > 1"
                                        title="<?= $this->lang->line('application_search_input'); ?>">
                                    <option value="0" selected="selected"><?= $this->lang->line('application_search_input'); ?></option>
                                    <?php foreach ($brands as $brand) { ?>
                                        <option value="<?=$brand['id'] ?>"><?=$brand['name'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-md-3 col-sm-4 form-group">
                                <label for="buscavendedores"><?= $this->lang->line('application_search_products_status'); ?></label>
                                <select class="form-control selectpicker show-tick"
                                        id="buscaStatus"
                                        onchange="filter(this)"
                                        name="status"
                                        data-live-search="true" data-actions-box="true" data-style="btn-blue"
                                        data-selected-text-format="count > 1"
                                        title="<?= $this->lang->line('application_search_input'); ?>">
                                    <option value="0" selected="selected"><?= $this->lang->line('application_search_input'); ?></option>
                                    <option value="1"><?=$this->lang->line('application_active')?></option>
                                    <option value="4"><?=$this->lang->line('application_under_analysis')?></option>
                                    <option value="2"><?=$this->lang->line('application_inactive')?></option>
                                </select>
                            </div>

                            <div class="col-md-3 col-sm-4 form-group">
                                <label for="buscavendedores"><?= $this->lang->line('application_search_situation_products'); ?></label>
                                <select class="form-control selectpicker show-tick"
                                        id="buscaSituacao"
                                        name="situacao"
                                        onchange="filter(this)"
                                        data-live-search="true" data-actions-box="true" data-style="btn-blue"
                                        data-selected-text-format="count > 1"
                                        title="<?= $this->lang->line('application_search_input'); ?>">
                                    <option value="0" selected="selected"><?= $this->lang->line('application_search_input'); ?></option>
                                    <option value="2"><?=$this->lang->line('application_complete')?></option>
                                    <option value="1"><?=$this->lang->line('application_incomplete')?></option>
                                </select>
                            </div>

                            <div class="col-md-3 col-sm-4 form-group">
                                <label for="buscavendedores"><?= $this->lang->line('application_search_quantity_products'); ?></label>
                                <select class="form-control selectpicker show-tick"
                                        id="buscaEstoque"
                                        name="estoque"
                                        onchange="filter(this)"
                                        data-live-search="true" data-actions-box="true" data-style="btn-blue"
                                        data-selected-text-format="count > 1"
                                        title="<?= $this->lang->line('application_search_input'); ?>">
                                    <option value="0" selected"><?= $this->lang->line('application_search_input'); ?></option>
                                    <option value="1"><?=$this->lang->line('application_with_stock')?></option>
                                    <option value="2"><?=$this->lang->line('application_no_stock')?></option>
                                </select>
                            </div>

                            <div class="col-md-12 form-group">
                                <button type="button" onclick="clearFilters()" class="btn btn-primary pull-right"> <i class="fa fa-eraser"></i> <?= $this->lang->line('application_clear'); ?>
                                </button>
                            </div>

                        </div>

                    </div>
                </div>

                <div class="box box-primary">
                    <div class="box-header">
                        <div class="products-actions">
                            <div class="pull-right">
                                <div class="btn-group">
                                    <form action="<?php echo base_url('PublicationManagement/exportCsv') ?>" method="post">
                                        <button class="btn btn-primary">
                                            <i class="fa fa-file-excel-o"></i> <?= $this->lang->line('application_data_export'); ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <table id="manageTable" class="table table-striped table-hover display table-condensed"
                               cellspacing="0" style="border-collapse: collapse; width: 99%;">
                            <thead>
                            <tr>
                                <th><?= $this->lang->line('application_image'); ?></th>
                                <th><?= $this->lang->line('application_sku'); ?></th>
                                <th><?= $this->lang->line('application_name'); ?></th>
                                <th><?= $this->lang->line('application_price'); ?></th>
                                <th><?= $this->lang->line('application_qty'); ?></th>
                                <th><?= $this->lang->line('application_store'); ?></th>
                                <th><?= $this->lang->line('application_id'); ?></th>
                                <th><?= $this->lang->line('application_status'); ?></th>
                                <th><?= $this->lang->line('application_situation'); ?></th>
                                <th><?= $this->lang->line('application_platform'); ?></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?php
include_once APPPATH . 'views/products/components/popup.update.status.product.php';
?>
<script type="text/javascript" src="<?=HOMEPATH;?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script src="<?php echo base_url('assets/dist/js/components/products/product.base.update.js') ?>"></script>
<script src="<?php echo base_url('assets/dist/js/components/products/product.update.status.js') ?>"></script>
<script src="<?php echo base_url('assets/dist/js/components/products/product.move.trash.js') ?>"></script>
<script src="<?php echo base_url('assets/dist/js/export_csv/export_csv.min.js') ?>"></script>

<script type="text/javascript">

    $(window).on("load", function(){
        getTotalIncompleteProductsAsync();
        getTotalErrorProductsAsync();
    });

    function showBlock(){
        $('#table2').hide();
        $('#export2').hide();
        $('.modo-box').show();
    }

    $('.btn-back').click(function(){
        $('#table2').show();
        $('#export2').show();
        $('.modo-box').hide();
    });

    $('.grid_view').click(function(){
        $('#grid_view_icon').toggleClass('fa fa-bars fa fa-th-large');
        $('#grid_view_one').toggleClass('col-lg-12').toggleClass('col-max');
        $('#grid_view_two').toggleClass('col-lg-12').toggleClass('col-max');
    });

    $("#myInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".bodyResult tr")
            .show().removeClass('ignore')
            .filter((i, tr) => !$(tr).text().toLowerCase().includes(value)).hide().addClass('ignore');
        $("#emptyMsg2").toggle($(".bodyResult tr:visible").length === 0);
    });

    var manageTable;
    var base_url = "<?php echo base_url(); ?>";

    $('.modo-box').css("display", "none");

    function filterForMkt(element){
        var mkt = $(element).data("mkt");
        var cols = "";
        var ct = 1;
        var totalErros = 0;
        var porcentagemProduto = 0;
        var totalPorcento = 0;
        $.ajax({
            url: base_url+ 'PublicationManagement/getProductsErrorTransformDetails',
            type:'POST',
            dataType: 'json',
            data:{
                mkt:mkt
            },
            success: function(result){

                $.each(result, function(key, value) {
                    totalErros += result[key].total++;
                });
                $.each(result, function(key, value) {

                    porcentagemProduto = (100 / totalErros);
                    totalPorcento = (porcentagemProduto * (result[key].total-1));

                    cols +='<tr>';
                    cols +='<td class="ignore">'+ct+++'</td>';
                    cols +='<td>'+result[key].marketplace+'</td>';
                    cols +='<td>'+result[key].message+'</td>';
                    cols +='<td>'+(result[key].total-1)+'</td>';
                    cols +='<td>'+totalPorcento.toFixed(2)+'%'+'</td>';
                    cols +='</tr>';

                });
                $(".bodyResult").html(cols);
            },
        });
        $(".bodyResult").html('<tr><td colspan="5" class="text-center">Aguarde...</td></tr>');
    };

    $('#export1').click(function(){
        $("#table1").tableHTMLExport(
            {
                type: 'csv',
                ignoreColumns:'.ignore',
                ignoreRows:'.ignore',
                separator: ';',
                quoteFields:false,
                filename: 'tabela_incompletos.csv'
            }
        );
    });

    $('#export2').click(function(){
        $("#table2").tableHTMLExport(
            {
                type: 'csv',
                ignoreColumns:'.ignore',
                ignoreRows:'.ignore',
                separator: ';',
                quoteFields:false,
                filename: 'tabela_erros_transformacao.csv'
            }
        );
    });

    $('#export3').click(function(){
        $("#table3").tableHTMLExport(
            {   type: 'csv',
                ignoreColumns:'.ignore',
                ignoreRows:'.ignore',
                separator: ';',
                quoteFields:false,
                filename: 'tabela_detalhes_erros_transf.csv'
            }
        );
    });

    $(document).on('click', '#btn_first_column_show', function() {
        getIncompletes();
        $(this).attr("id","btn_first_column_hide").html('<?= $this->lang->line('application_hide_details'); ?>' )
            .append('<i class="fa fa-eye-slash pull-left" style="padding: 4px 5px 0px 0px;"></i>');
    });

    $(document).on('click', '#btn_first_column_hide', function() {
        $(this).attr("id","btn_first_column_show").html('<?= $this->lang->line('application_show_details'); ?>')
            .append('<i class="fa fa-eye pull-left" style="padding: 3px 4px 0px 0px;"></i>');
    });

    $(document).on('click', '#btn_second_column_show', function() {
        getErrorTransform();
        $(this).attr("id","btn_second_column_hide").html('<?= $this->lang->line('application_hide_details'); ?>' )
            .append('<i class="fa fa-eye-slash pull-left" style="padding: 4px 5px 0px 0px;"></i>');
    });

    $(document).on('click', '#btn_second_column_hide', function() {
        $(this).attr("id","btn_second_column_show").html('<?= $this->lang->line('application_show_details'); ?>')
            .append('<i class="fa fa-eye pull-left" style="padding: 3px 4px 0px 0px;"></i>');
    });

    $('.block').click(function(){
        $('.block').removeClass('text-bold');
        $(this).addClass('text-bold');
       $('.btn.dropdown-toggle.btn-blue:eq(2), .btn.dropdown-toggle.btn-blue:eq(5)').prop('disabled', true);
    });

    $('.btn.btn-primary.pull-right').click(function(){
        $('.btn.dropdown-toggle.btn-blue:eq(2), .btn.dropdown-toggle.btn-blue:eq(5)').prop('disabled', false);
        $('.block').removeClass('text-bold');
    });

    function findBold(element){
        $('.block').removeClass('text-bold');
        $(element).addClass('text-bold');
    }

    function getTotalIncompleteProductsAsync(){
        $.ajax({
            url: base_url + 'PublicationManagement/getTotalIncompleteProductsAsync',
            type: 'POST',
            dataType: 'json',
            success: function (result) {
                $(".total_incomplete_products").html(result);
            },
        });
    }

    function getTotalErrorProductsAsync(){
        $.ajax({
            url: base_url + 'PublicationManagement/getTotalErrorProductsAsync',
            type: 'POST',
            dataType: 'json',
            success: function (result) {
                $(".total_transformation_errors").html(result);
            },
        });
    }

    function getIncompletes(){

        var cols = "";
        var ctd = 1;
        var totalIncompletos = 0;
        var porcentagemProduto = 0;
        var totalPorcento = 0;
        var somaQuantidade = 0;
        var somaPorcentagem = 0;

            $.ajax({
                url: base_url + 'PublicationManagement/indicators',
                type: 'POST',
                dataType: 'json',
                success: function (result) {

                    $.each(result, function (key, value) {
                        totalIncompletos += result[key].total++;
                    });

                    $.each(result, function (key, value) {

                        porcentagemProduto = (100 / totalIncompletos);
                        totalPorcento = (porcentagemProduto * (result[key].total - 1));

                        cols += '<tr class="block" onclick="findBold(this);">';
                        cols += '<td class="ignore">' + ctd++ + '</td>';
                        cols += '<td>' + '<a href="javascript:void(0)" data-indicador="' + result[key].filter + '" class="block" onclick="filter(this)">' + result[key].msg + '</a>' + '</td>';
                        cols += '<td>' + (result[key].total - 1) + '</td>';
                        cols += '<td>' + totalPorcento.toFixed(2) + '%' + '</td>';
                        cols += '</tr>';

                        somaQuantidade += result[key].total - 1;
                        somaPorcentagem += totalPorcento;

                    });

                    cols += '<tr class="text-bold">';
                    cols += '<td colspan="2">Total de erros</td>';
                    cols += '<td>' + somaQuantidade + '</td>';
                    cols += '<td>' + somaPorcentagem.toFixed(0) + '%' + '</td>';
                    cols += '</tr>';

                    $(".bodyIncompletes").html(cols);

                },
            });
            $(".bodyIncompletes").html('<tr><td colspan="5" class="text-center">Aguarde...</td></tr>');
    }

    function getErrorTransform(){
        var cols = "";
        var ctd = 1;
        var totalErrorTransforme = 0;
        var porcentagemProduto = 0;
        var totalPorcentoErrorTransforme = 0;
        var somaQuantidade = 0;
        var somaPorcentagem = 0;
        $.ajax({
            url: base_url+ 'PublicationManagement/getProductsErrorTransformForMkt',
            type:'POST',
            dataType: 'json',
            success: function(result){

                $.each(result, function(key, value) {
                    totalErrorTransforme += result[key].total++;
                });

                $.each(result, function(key, value) {

                    porcentagemProduto = (100 / totalErrorTransforme);
                    totalPorcentoErrorTransforme = (porcentagemProduto * (result[key].total-1));

                    cols +='<tr class="block" onclick="findBold(this);">';
                        cols +='<td class="ignore">'+ctd+++'</td>';
                        cols +='<td>'+result[key].marketplace+'</td>';
                        cols +='<td>'+'<a href="javascript:void(0)" data-mkt="'+result[key].marketplace+'" onclick="filterForMkt(this);showBlock(this);" class="btn-link show-block">Erros</a>'+'</td>';
                        cols +='<td>'+(result[key].total-1)+'</td>';
                        cols +='<td>'+totalPorcentoErrorTransforme.toFixed(2)+'%'+'</td>';
                    cols +='</tr>';

                    somaQuantidade += result[key].total-1;
                    somaPorcentagem += totalPorcentoErrorTransforme;

                });

                $(".bodyErrorTransform").html(cols);
            },
        });
        $(".bodyErrorTransform").html('<tr><td colspan="5" class="text-center">Aguarde...</td></tr>');
    }

    function filter(element){

        var buscarIncompleto = $(element).data("indicador");

        if(buscarIncompleto){
            localStorage.setItem("buscarIncompleto", buscarIncompleto);
        }else{
            buscarIncompleto = localStorage.getItem("buscarIncompleto");
        }

        var buscarCategoria = $('#buscarCatergoria').val();
        var buscarLoja = $('#buscarloja').val();
        var buscarMarketplace = $('#buscarMarketplace').val();
        var buscarMarca = $('#buscarMarca').val();
        var buscaStatus = $('#buscaStatus').val();
        var buscaSituacao = $('#buscaSituacao').val();
        var buscaEstoque = $('#buscaEstoque').val();

        if (typeof manageTable === 'object' && manageTable !== null) {
            manageTable.destroy();
        }

        manageTable = $('#manageTable').DataTable({
            "language": {"url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "sortable": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline({
                url: base_url + 'PublicationManagement/fetchProductData',
                data:{
                    buscarIncompleto:buscarIncompleto,
                    buscarCategoria:buscarCategoria,
                    buscarLoja:buscarLoja,
                    buscarMarketplace:buscarMarketplace,
                    buscarMarca:buscarMarca,
                    buscaStatus:buscaStatus,
                    buscaSituacao:buscaSituacao,
                    buscaEstoque:buscaEstoque,
                },
                pages: 2,
            }),
        });
    }

    manageTable = $('#manageTable').DataTable({
        "language": {"url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
        "processing": true,
        "serverSide": true,
        "scrollX": true,
        "sortable": true,
        "serverMethod": "post",
        "ajax": $.fn.dataTable.pipeline({
            url: base_url + 'PublicationManagement/fetchProductData',
            pages: 2, // number of pages to cache
        }),
    });

    function clearFilters(){
        $('#buscarIncompleto').val('default').selectpicker("refresh");
        $('#buscarCatergoria').val('default').selectpicker("refresh");
        $('#buscarloja').val('default').selectpicker("refresh");
        $('#buscarMarketplace').val('default').selectpicker("refresh");
        $('#buscarMarca').val('default').selectpicker("refresh");
        $('#buscaStatus').val('default').selectpicker("refresh");
        $('#buscaSituacao').val('default').selectpicker("refresh");
        $('#buscaEstoque').val('default').selectpicker("refresh");
        localStorage.clear();
        filter();
    }

</script>