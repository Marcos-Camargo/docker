<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
<style>
    .swal2-popup.swal2-modal.swal2-icon-warning.swal2-show {
        width: auto;
    }
    div#swal2-content {
        text-align: left;
    }
    #about-us{
        padding: 7px 56px!important;
    }
    #iconsError .iconn {
        scale: 2.9;
        color: #dd4b39;
    }
    div#iconsError {
        padding: 15px 0px;
    }
    .m-top {
        margin-top: 4px;
    }
    .m-top2 {
        margin-top: 7px;
    }
    span.text-red {
        margin-left: 51px;
        font-size: 28px;
        font-weight: 600;
    }
    span.text-red2 {
        color: #dd4b39;
        margin-left: 54px;
        font-size: 28px;
        font-weight: 600;
    }
    span.text-red3 {
        color: #dd4b39;
        margin-left: 44px;
        font-size: 28px;
        font-weight: 600;
    }
    span.text-red4 {
        color: #dd4b39;
        margin-left: 47px;
        font-size: 28px;
        font-weight: 600;
    }
    span.text-red5 {
        color: #dd4b39;
        margin-left: 48px;
        font-size: 28px;
        font-weight: 600;
    }
    .b-one {
        float: left;
        margin-top: 14px;
        margin-left: 15px;
        margin-right: 6px;
    }
    .b-two {
        width: -webkit-fill-available;
        float: left;
        margin-right: 8px;
        margin-top: -24px;
        border-bottom: 5px solid #fff;
    }
    .b-two:hover {
        border-bottom: 5px solid #dd4b39;
    }
    .div-fix {
        width: 20%;
    }
    .font-black {
        font-size: 13px;
    }
    .wrapper {
        overflow: hidden;
    }
    .dropdown-menu>li>a {
        color: #777;
        text-decoration: none;
    }
    .b-two:hover {
        border-bottom: 5px solid #dd4b39;
    }
    .last-clicked {
        border-bottom: 5px solid #dd4b39;
    }
    button.btn.dropdown-toggle.btn-blue.bs-placeholder {
        background: #fff;
        border-radius: 0;
        border: 1px solid #cecece;
        color: #000;
    }
    i.photoo{
        padding-right: 9px;
    }
    .btn_action{
        width: -webkit-fill-available;
        padding-right: 0px;
        background: white;
        border: none;
        color: #777;
        height: 26px;
    }
    .btn_action:hover{
        background: #e1e3e9;
        color: #333;
    }
    button.btn.dropdown-toggle.btn-default.bs-placeholder {
        background: white;
        color: #444;
        border: 1px solid #cecece;
        border-radius: 0;
    }
    .last-clicked {
        border-bottom: 5px solid #dd4b39;
    }
</style>
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";    $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div id="messages"></div>

                <?php if($this->session->flashdata('success')): ?>
                    <br>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif($this->session->flashdata('error')): ?>
                    <br>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>
                <br>
                <div id="showActions">
                    <a class="pull-right btn btn-primary exportErrosTrans" href="<?php echo base_url('export/errorsTransformationxls') ?>/"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_products_export');?></a>
                    <br>
                </div>

                <div class="box" style="margin-top: 30px;">
                    <div class="box-body" id="iconsError">
                        <a href="void:(0)" data-indicador="1" class="" onclick="GetFilter(this)">
                            <div class="col-sm-2">
                                <div class="b-one">
                                    <i class="fa fa-camera iconn" aria-hidden="true"></i>
                                </div>
                                <p class="text-black font-black" style="float:left;margin-top:-1px;margin-left: 18px;"><b>Sem Imagens</b></p>
                                <div class="b-two">
                                    <span class="text-red totalProductsNoImage">Aguarde...</span>
                                </div>
                            </div>
                        </a>
                        <a href="void:(0)" data-indicador="2" onclick="GetFilter(this)">
                            <div class="col-sm-2">
                                <div class="b-one">
                                    <i class="fa-solid fa-sitemap iconn"></i>
                                </div>
                                <p class="text-black font-black" style="float:left;margin-top:-1px;margin-left: 18px;"><b>Sem Categoria</b></p>
                                <div class="b-two">
                                    <span class="text-red2 totalProductsNoCategory">Aguarde...</span>
                                </div>
                            </div>
                        </a>
                        <a href="void:(0)" data-indicador="3" onclick="GetFilter(this)">
                            <div class="col-sm-2">
                                <div class="b-one">
                                    <i class="fa-solid fa-ruler-vertical iconn"></i>
                                </div>
                                <p class="text-black font-black" style="float:left;margin-top:-1px;margin-left: 18px;"><b>Sem Dimensões</b> <i class="fa fa-info-circle" data-toggle="tooltip" title="Peso bruto, Peso líquido, Largura, Altura, profundidade, Produto por embalagem"></i></p>
                                <div class="b-two">
                                    <span class="text-red3 totalProductsNoDimensions">Aguarde...</span>
                                </div>
                            </div>
                        </a>
                        <a href="void:(0)" data-indicador="4" onclick="GetFilter(this)">
                            <div class="col-sm-2">
                                <div class="b-one">
                                    <i class="fa-solid fa-dollar-sign iconn"></i>
                                </div>
                                <p class="text-black font-black" style="float:left;margin-top:-1px;margin-left: 18px;"><b>Sem Preço</b></p>
                                <div class="b-two">
                                    <span class="text-red4 totalProductsNoPrice">Aguarde...</span>
                                </div>
                            </div>
                        </a>
                        <a href="void:(0)" data-indicador="5" onclick="GetFilter(this)">
                            <div class="col-sm-2">
                                <div class="b-one">
                                    <i class="fa-solid fa-file-lines iconn"></i>
                                </div>
                                <p class="text-black font-black" style="float:left;margin-top:-1px;margin-left: 18px;"><b>Sem Descrição</b></p>
                                <div class="b-two">
                                    <span class="text-red5 totalProductsNoDescription">Aguarde...</span>
                                </div>
                            </div>
                        </a>
                        <div class="col-sm-2 m-top2">
                            <button class="btn btn-primary pull-right" id="btn_first_column_show" data-toggle="collapse" data-target="#demo">
                                <i class="fa fa-filter" aria-hidden="true"></i>
                                Exibir Filtros
                            </button>
                        </div>
                        <div class="col-sm-12">
                            <div id="demo" class="collapse row">
                                <hr>
                                <div class="form-group col-sm-2">
                                    <label for="searchName">Nome do Produto</label>
                                    <input type="text" class="form-control" onchange="GetFilter(this)" id="searchName" placeholder="">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="buscar_nome">loja</label>
                                    <select class="form-control selectpicker show-tick" onchange="GetFilter(this)" name="selectStore" id="selectStore" data-live-search="true" data-actions-box="true" data-style="btn-blue" data-selected-text-format="count > 1" title="Selecione">
                                        <?php if(isset($stores)){ foreach ($stores as $store) { ?>
                                            <option value="<?= $store['id'] ?>"><?= $store['loja'] ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="buscar_nome">Marketplace</label>
                                    <select class="form-control selectpicker show-tick" onchange="GetFilter(this)" name="selectMarketplace" id="selectMarketplace" data-live-search="true" data-actions-box="true" data-style="btn-blue" data-selected-text-format="count > 1" title="Todos">
                                        <?php if(isset($names_marketplaces)){ foreach ($names_marketplaces as $name_marketplace) { ?>
                                            <option value="<?= $name_marketplace['int_to'] ?>"><?= $name_marketplace['name'] ?></option>
                                        <?php }} ?>
                                    </select>
                                </div>
                                <div class="form-group col-sm-2">
                                    <label for="buscar_pendencia">Tipo de Pendência</label>
                                    <select class="form-control selectpicker show-tick" onchange="GetFilter(this)" id="selectPendency" name="selectPendency[]" data-live-search="true" data-actions-box="true" data-style="btn-blue" data-selected-text-format="count > 1" title="<?= $this->lang->line('application_search_input'); ?>">
                                        <option value="1">Sem imagem</option>
                                        <option value="2">Sem categoria</option>
                                        <option value="3">Sem dimensões</option>
                                        <option value="4">Sem preço</option>
                                        <option value="5">Sem descrição</option>
                                    </select>
                                </div>
                                <div class="form-group col-sm-2" style="width: 225px;">
                                    <br />
                                    <div class="col-sm-6">
                                        <button class="btn btn-primary m-top" onclick="GetFilter(this)">
                                            <i class="fa fa-search"></i>&nbsp;
                                            Buscar
                                        </button>
                                    </div>
                                    <div class="col-sm-6">
                                        <button class="btn btn-default m-top" onclick="clearFilters()">
                                            <i class="fa fa-eraser"></i>&nbsp;
                                            Limpar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box mt-4">
                    <!-- /.box-header -->
                    <div class="box-body">
                        <form role="form" action="<?php echo base_url('errorsTransformation/markSelect') ?>" method="post" id="selectForm">
                            <table id="manageTable" class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th><input type="checkbox" name="select_all" value="1" id="manageTable-select-all"></th>
                                    <th>imagem</th>
                                    <th><?=$this->lang->line('application_sku');?></th>
                                    <th><?=$this->lang->line('application_product');?></th>
                                    <th><?=$this->lang->line('application_store');?></th>
                                    <th><?=$this->lang->line('application_marketplace');?></th>
                                    <th><?=$this->lang->line('application_step');?></th>
                                    <th><?=$this->lang->line('application_date');?></th>
                                    <th><?=$this->lang->line('application_error');?></th>
                                    <th><?=$this->lang->line('application_action');?></th>
                                </tr>
                                </thead>
                            </table>
                                <div class="col-md-3 row">
                                    <button type="submit" class="btn btn-primary" id="select" name="select"><?=$this->lang->line('application_mark_as_solved')?></button>
                                </div>
                            </div>

                        </form>
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
        getStart();
        $('[data-toggle="tooltip"]').tooltip();
        $('#searchName').val('');
        $('#selectStore').val('default').selectpicker("refresh");
        $('#selectMarketplace').val('default').selectpicker("refresh");
        $('#selectPendency').val('default').selectpicker("refresh");
    });

    $(window).on("load", function() {
        getTotalNoImage();
        getTotalNoCategory();
        getTotalNoDimensions();
        getTotalNoPrice();
        getTotalNoDescription();
    });

    function getStart(){

        if (typeof table === 'object' && table !== null) {
            table.destroy();
        }

        table = $('#manageTable').DataTable( {
            "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            selected: undefined,
            'columnDefs': [{
                'targets': 0,
                'searchable': false,
                'orderable': false,
                'className': 'dt-body-center',
                'render': function (data, type, full, meta){
                    return '<input type="checkbox" name="id[]" value="' + $('<div/>').text(data).html() + '">';
                }
            }],
            "ajax": $.fn.dataTable.pipeline( {
                url: base_url + 'errorsTransformation/fetchErrorsData',
                pages: 2 // number of pages to cache
            } )
        } );

        // Handle click on "Select all" control
        $('#manageTable-select-all').on('click', function(){
            // Get all rows with search applied
            var rows = table.rows({ 'search': 'applied' }).nodes();
            // Check/uncheck checkboxes for all rows in the table
            $('input[type="checkbox"]', rows).prop('checked', this.checked);
        });

        // Handle click on checkbox to set state of "Select all" control
        $('#manageTable tbody').on('change', 'input[type="checkbox"]', function(){
            // If checkbox is not checked
            if(!this.checked){
                var el = $('#manageTable-select-all').get(0);
                // If "Select all" control is checked and has 'indeterminate' property
                if(el && el.checked && ('indeterminate' in el)){
                    // Set visual state of "Select all" control
                    // as 'indeterminate'
                    el.indeterminate = true;
                }
            }
        });
        // Handle form submission event
        $('#selectForm').on('submit', function(e){
            var form = this;
            $("#loaderDiv").show();
            // Iterate over all checkboxes in the table
            table.$('input[type="checkbox"]').each(function(){
                // If checkbox doesn't exist in DOM
                if(!$.contains(document, this)){
                    // If checkbox is checked
                    if(this.checked){
                        // Create a hidden element
                        $(form).append(
                            $('<input>')
                                .attr('type', 'hidden')
                                .attr('name', this.name)
                                .val(this.value)
                        );
                    }
                }
            });
        });

        // $('#selectMarketplace').on('change', function () {
        // })

    }

    function GetFilter(element) {
        var filter = $(element).data("indicador");
        let name = $('#searchName').val();
        let store = $('#selectStore').val();
        let marketplace = $('#selectMarketplace').val();
        let pendency = $('#selectPendency').val();

        let splitHref = $('#showActions .exportErrosTrans').attr('href').split('/').slice(0, -1);
        let joinHref = splitHref.join('/') + '/' + $('#selectMarketplace').val();
        $('#showActions .exportErrosTrans').attr('href', joinHref);

        if (filter) {
            localStorage.setItem("buscar", filter);
        } else {
            filter = localStorage.getItem("buscar");
        }

        if (typeof table === 'object' && table !== null) {
            table.destroy();
        }

        table = $('#manageTable').DataTable({
            "language": {"url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"},
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            selected: undefined,
            'columnDefs': [{
                'targets': 0,
                'searchable': false,
                'orderable': false,
                'className': 'dt-body-center',
                'render': function (data, type, full, meta) {
                    return '<input type="checkbox" name="id[]" value="' + $('<div/>').text(data).html() + '">';
                }
            }],
            "ajax": $.fn.dataTable.pipeline({
                url: base_url + 'errorsTransformation/fetchErrorsData',
                data: {
                    filter:filter,
                    name:name,
                    store:store,
                    marketplace:marketplace,
                    pendencia:pendency
                },
                pages: 2
            })
        });
    }

    function getTotalNoImage() {
        $.ajax({
            url: base_url + 'errorsTransformation/totalNoImage',
            type: 'POST',
            dataType: 'json',
            success: function(result) {
                var plus = '';
                if(result >= 10000 ){
                    plus = 'K+';
                }
                $(".totalProductsNoImage").html(result+plus);
            },
        });
    }

    function getTotalNoCategory() {
        $.ajax({
            url: base_url + 'errorsTransformation/totalNoCategory',
            type: 'POST',
            dataType: 'json',
            success: function(result) {
                var plus = '';
                if(result >= 10000 ){
                    result = 10000;
                    plus = 'K+';

                }
                $(".totalProductsNoCategory").html(result+plus);
            },
        });
    }

    function getTotalNoDimensions() {
        $.ajax({
            url: base_url + 'errorsTransformation/totalNoDimensions',
            type: 'POST',
            dataType: 'json',
            success: function(result) {
                var plus = '';
                if(result >= 10000 ){
                    result = 10000;
                    plus = 'K+';
                }
                $(".totalProductsNoDimensions").html(result+plus);
            },
        });
    }

    function getTotalNoPrice() {
        $.ajax({
            url: base_url + 'errorsTransformation/totalNoPrice',
            type: 'POST',
            dataType: 'json',
            success: function(result) {
                var plus = '';
                if(result >= 10000 ){
                    result = 10000;
                    plus = 'K+';
                }
                $(".totalProductsNoPrice").html(result+plus);
            },
        });
    }

    function getTotalNoDescription() {
        $.ajax({
            url: base_url + 'errorsTransformation/totalNoDescription',
            type: 'POST',
            dataType: 'json',
            success: function(result) {
                var plus = '';
                if(result >= 10000 ){
                    result = 10000;
                    plus = 'K+';
                }
                $(".totalProductsNoDescription").html(result+plus);
            },
        });
    }

    function clearFilters() {
        $('.b-two').removeClass('last-clicked');
        $('#searchName').val('');
        $('#selectStore').val('default').selectpicker("refresh");
        $('#selectMarketplace').val('default').selectpicker("refresh");
        $('#selectPendency').val('default').selectpicker("refresh");
        localStorage.clear();
        getStart();
    }

    $(document).on('click', '#btn_first_column_show', function() {
        $(this).attr("id", "btn_first_column_hide").html('Ocultar Filtros')
            .append('<i class="fa fa-filter pull-left" style="padding: 4px 5px 0px 0px;"></i>');
    });

    $(document).on('click', '#btn_first_column_hide', function() {
        $(this).attr("id", "btn_first_column_show").html('Exibir Filtros')
            .append('<i class="fa fa-filter pull-left" style="padding: 3px 5px 0px 0px;"></i>');
    });

    $('.b-two').click(function(){
        $('.b-two').removeClass('last-clicked');
        $(this).addClass('last-clicked');
    });

</script>
