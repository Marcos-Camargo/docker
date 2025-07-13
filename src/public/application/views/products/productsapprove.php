<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
<style>
    .wrapper {
        overflow: hidden;
    }

    .bootstrap-select .dropdown-toggle .filter-option {
        background-color: white !important;
    }

    .bootstrap-select .dropdown-menu li a {
        border: 1px solid gray;
    }

    .dataTables_scrollHeadInner {
        width: auto !important;
    }

    .full-table {
        width: 100% !important;
    }

    button.btn.dropdown-toggle.btn-blue.bs-placeholder {
        padding: 0px;
    }

    .form-space {
        padding: 10px;
    }

    .filter-option-inner-inner {
        padding: 6px;
        border: none;
    }

    .box .form-space .row {
        padding-top: 1em;
    }

    .pd-0 {
        padding: 0px;
    }

    button.btn.dropdown-toggle.btn-blue {
        padding: 0;
    }

    .dropdown-menu > li > a {
        color: #777;
        text-decoration: none;
    }

    button.btn.dropdown-toggle.btn-blue.bs-placeholder {
        background: #fff;
        border-radius: 0;
        border: 1px solid #cecece;
        color: #000;
    }

    i.photoo {
        padding-right: 9px;
    }

    .btn_action {
        width: -webkit-fill-available;
        padding-right: 0px;
        background: white;
        border: none;
        color: #777;
        height: 26px;
    }

    .btn_action:hover {
        background: #dbdbdb;
    }

    td:last-of-type {
        width: 120px !important;
    }
    .swal2-popup.swal2-modal.swal2-icon-warning.swal2-show {
        width: max-content;
    }
    .icon-color{
        padding-left: 5px;
        color: #dd4b39!important;
    }
    .swal2-actions {
        justify-content: end;
        flex-direction: row-reverse;
    }
</style>
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $data['page_now'] = 'products_approval';
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div id="messages"></div>

                <?php if ($this->session->flashdata('success')): ?>
                    <br>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <br>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <br>
                <div class="box">
                    <div class="box-body" id="iconsError">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group col-sm-2">
                                    <label for="name">Nome do Produto</label>
                                    <input type="email" class="form-control"  onchange="GetFilter(this)" id="name" placeholder="">
                                </div>
                                <div class="form-group col-sm-2">
                                    <label for="category">Categoria</label>
                                    <select class="form-control selectpicker show-tick" id="category"
                                            data-live-search="true" data-style="btn-blue"
                                            data-selected-text-format="count > 1"
                                            multiple
                                            onchange="GetFilter(this)"
                                            title="<?= $this->lang->line('application_search_input'); ?>">
                                        <?php if (isset($categories)) {
                                            foreach ($categories as $category) { ?>
                                                <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
                                            <?php }
                                        } ?>
                                    </select>
                                </div>
                                <div class="form-group col-sm-2">
                                    <label for="status">Status de Aprovação</label>
                                    <select class="form-control selectpicker show-tick" id="status"
                                            data-live-search="true" data-actions-box="true"
                                            data-style="btn-blue" data-selected-text-format="count > 1"
                                            onchange="GetFilter(this)"
                                            title="<?= $this->lang->line('application_search_input'); ?>">
                                        <option value="1">Aprovado</option>
                                        <option value="2">Reprovado</option>
                                        <option value="3" selected>Em Aprovação</option>
                                        <option value="4">Rejeitado</option>
                                    </select>
                                </div>
                                <div class="form-group col-sm-2">
                                    <label for="lojas">Buscar por lojas</label>
                                    <select class="form-control selectpicker show-tick" id="lojas"
                                            multiple
                                            data-live-search="true"
                                            data-actions-box="true" data-style="btn-blue"
                                            data-selected-text-format="count > 1"
                                            onchange="GetFilter(this)"
                                            title="<?= $this->lang->line('application_search_input'); ?>">
                                        <?php if (isset($stores)) {
                                            foreach ($stores as $store) { ?>
                                                <option value="<?= $store['id'] ?>"><?= $store['name'] ?></option>
                                            <?php }
                                        } ?>
                                    </select>
                                </div>
                                <?php if($setting_validate_completed_sku_marketplace): ?>
                                <div class="form-group col-sm-2">
                                    <label for="lojas">SKU marketplace preenchido?</label>
                                    <select class="form-control" id="completed_sku_marketplace" name="completed_sku_marketplace" onchange="GetFilter(this)">
                                        <option value="">Selecione</option>
                                        <option value="0">Não</option>
                                        <option value="1">Sim</option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                <div class="form-group col-sm-2" id="show_top" style="width: 222px;margin-top: 6px;margin-left: -16px;">
                                    <br/>
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
                                <div class="form-group col-sm-2" style="width: 165px;margin-top: 26px;">
                                    <button class="btn btn-primary pull-right" id="btn_first_column_hide"
                                            data-toggle="collapse" data-target="#demo">
                                        <i class="fa fa-filter" aria-hidden="true"></i>
                                        Ocultar Filtros
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div id="demo" class="collapse in row" aria-expanded="true">
                                <div class="form-group col-sm-2">
                                    <label for="int_to">Buscar por Marketplace</label>
                                    <select class="form-control selectpicker show-tick" id="int_to"
                                            data-live-search="true" data-actions-box="true" data-style="btn-blue"
                                            data-selected-text-format="count > 1"
                                            onchange="GetFilter(this)" multiple
                                            title="<?= $this->lang->line('application_search_input'); ?>">
                                        <?php if (isset($marketplaces)) {
                                            foreach ($marketplaces as $marketplace) { ?>
                                                <option value="<?= $marketplace['int_to'] ?>"><?= $marketplace['int_to'] ?></option>
                                            <?php }
                                        } ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="sku" class="normal"><?= $this->lang->line('application_sku'); ?></label>
                                    <input type="search" id="sku" class="form-control" placeholder=""  onchange="GetFilter(this)"
                                           aria-label="Search" aria-describedby="basic-addon1">
                                </div>
                                <div class="form-group col-sm-2">
                                    <label for="estoque">Estoque</label>
                                    <select class="form-control selectpicker show-tick" id="estoque"
                                            data-live-search="true" data-actions-box="true" data-style="btn-blue"
                                            data-selected-text-format="count > 1"
                                            onchange="GetFilter(this)"
                                            title="<?= $this->lang->line('application_search_input'); ?>">
                                        <option value="" selected>Todos</option>
                                        <option value="1">Com Estoque</option>
                                        <option value="2">Sem Estoque</option>
                                    </select>
                                </div>
                                <!--<div class="form-group col-sm-2">
                                    <label for="operador">Valor Condicional</label>
                                    <select class="form-control selectpicker show-tick" id="operador"
                                            data-live-search="true" data-actions-box="true" data-style="btn-blue"
                                            data-selected-text-format="count > 1"
                                            title="</?= $this->lang->line('application_search_input'); ?>">
                                        <option value="1">Igual</option>
                                        <option value="2">Maior</option>
                                        <option value="3">Menor</option>
                                        <option value="4">Menor ou igual</option>
                                        <option value="5">Maior ou igual</option>
                                    </select>
                                </div>
                                <div class="form-group col-sm-2">
                                    <label for="operadorvalor" class="normal">% de Comissão</label>
                                    <input type="search" id="operadorvalor" class="form-control" placeholder=""
                                           aria-label="Search" aria-describedby="basic-addon1">
                                </div>-->
                                <div class="form-group col-sm-2" id="show_down" style="width: 222px;margin-top: 5px;margin-left: -35px;">
                                    <br/>
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
            </div>
        </div>


        <div class="row"></div>

        <div class="box">
            <div class="box-header">
                <h3 class="box-title"></h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="manageTable" aria-label="" class="table table-bordered table-striped full-table">
                    <thead>
                    <tr>
                        <th><input type="checkbox" name="select_all" value="1" id="manageTable-select-all"></th>
                        <th>Imagem</th>
                        <th><?= $this->lang->line('application_sku'); ?></th>
                        <th><?= $this->lang->line('application_name'); ?></th>
                        <th>Categoria</th>
                        <th><?= $this->lang->line('application_store'); ?></th>
                        <th><?= $this->lang->line('application_approval_status'); ?></th>
                        <th>Marketplace</th>
                        <th><?= $this->lang->line('application_last_update'); ?></th>
                        <th>Estoque</th>
                        <th><?= $this->lang->line('application_action'); ?></th>
                    </tr>
                    </thead>
                </table>

                <div class="row">
                    <div class="col-sm-12">
                        <div class="dropdown dropup">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="about-us"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                    style="width: 160px;">
                                Ações
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="about-us"
                                style="width: auto;line-height: 2;margin-top: 1px;">
                                <li>
                                    <button class="btn_action" data-identify="approve_product" name="approve_product" id="approve_product" onclick="checkboxDisapproveProductMutiple(event,this)"
                                            style="padding-left: 0px;width: -webkit-fill-available;">
                                        <i class="fas fa-thumbs-up photoo" aria-hidden="true" style="color:#1daa40;padding-right: 9px;"></i>
                                        Aprovar produtos
                                    </button>
                                </li>
                                <li>
                                    <button class="btn_action" data-identify="disapprove_product" name="disapprove_product" id="disapprove_product" onclick="checkboxDisapproveProductMutiple(event,this)"
                                            style="margin-left: 0px;line-height: 2;">
                                        <i class="fas fa-thumbs-down photoo" aria-hidden="true" style="margin-left: 0px;line-height: 2; color: #dd4b39;width: 21px;"></i>
                                        Reprovar produtos
                                    </button>
                                </li>
                                <li>
                                    <button class="btn_action" data-identify="on_approval_product" name="on_approval_product" id="on_approval_product" onclick="checkboxDisapproveProductMutiple(event,this)"
                                            style="margin-left: 3px;line-height: 2;float: left;text-align: left;padding-left: 13.5px;" >
                                        <i class="fas fa-thumbtack photoo" aria-hidden="true" style="color:#3c8dbc;"></i>
                                        Em aprovação
                                    </button>
                                </li>
                                <li>
                                    <form action="<?php echo base_url('Products/viewFast') ?>" method="GET" target="__blank" id="formPreview">
                                        <button class="btn_action" onclick="checkCheckbox(event);"
                                                style="margin-left: 0px;line-height: 2;">
                                            <i class="fa fa-edit photoo" aria-hidden="true" style="color:#367fa9;"></i>
                                            Visualização rápida
                                        </button>
                                        <input type="hidden" name="listview[]" id="listview">
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
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

<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
    var base_url = "<?php echo base_url(); ?>";
    var table;

    $(document).ready(function () {
        getStart();
    });

    function getStart() {
        if (typeof table === 'object' && table !== null) {
            table.destroy();
        }
        table = $('#manageTable').DataTable({
            "language": {"url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"},
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            'columnDefs': [{
                'targets': 0,
                'searchable': false,
                'orderable': false,
                'className': 'dt-body-center',
                'render': function (data, type, full, meta) {
                    return '<input type="checkbox" class="productsselect" name="id[]" value="' + $('<div/>').text(data).html() + '">';
                }
            }],
            "ajax": $.fn.dataTable.pipeline({
                url: base_url + 'products/fetchProductsApproval',
                data: {
                    status: $('#status').val(),
                },
                pages: 2
            })
        });

        $('#manageTable-select-all').prop("checked", false);
        var rows = table.rows({'search': 'applied'}).nodes();
        $('input[type="checkbox"]', rows).prop('checked', false);
    }

    // BUSCAR TODOS OS CHECKBOX MARCADOS
    var productsList = [];
    $('#manageTable-select-all').on('click', function () {

        // DESMARCA TODOS OS CHECKBOX
        if (productsList.length) {
            productsList = [];
            $(this).prop('checked', false);
            var rows = table.rows({'search': 'applied'}).nodes();
            $('input[type="checkbox"]', rows).prop('checked', false);

            // MARCA TODOS OS CHECKBOX
        } else {
            $(this).prop('checked', true);
            var rows = table.rows({'search': 'applied'}).nodes();
            $('input[type="checkbox"]', rows).prop('checked', true);
            $('.productsselect').each(function (index, value) {
                productsList.push($(value).val());
            });
        }
    });

    // CHECKBOX MARCANDO/DESMARCANDO MANUAL
    $(document).on('change', '.productsselect', function () {
        if (this.checked) {
            if (!productsList.includes($(this).val())) {
                productsList.push($(this).val());
            }
        } else {
            var remove = productsList.indexOf($(this).val());
            if (remove != -1) productsList.splice(remove, 1);
        }
    });

    function GetFilter(element) {
        if (typeof table === 'object' && table !== null) {
            table.destroy();
        }
        table = $('#manageTable').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?= ucfirst($this->input->cookie('swlanguage')) ?>.lang'
            },
            "processing": true,
            "serverSide": true,
            "serverMethod": "post",
            'columnDefs': [{
                'targets': 0,
                'searchable': false,
                'orderable': false,
                'className': 'dt-body-center',
                'render': function (data, type, full, meta) {
                    return '<input type="checkbox" class="productsselect" name="id[]" value="' + $('<div/>').text(data).html() + '">';
                }
            }],
            "ajax": $.fn.dataTable.pipeline({
                url: base_url + 'products/fetchProductsApproval',
                data: {
                    nome: $('#name').val(),
                    category: $('#category').val(),
                    status: $('#status').val(),
                    lojas: $('#lojas').val(),
                    int_to: $('#int_to').val(),
                    sku: $('#sku').val(),
                    estoque: $('#estoque').val(),
                    operador: $('#operador').val(),
                    operadorvalor: $('#operadorvalor').val(),
                    completed_sku_marketplace: $('#completed_sku_marketplace').val()
                },
                pages: 2,
            }),
        });
    }
    
    const requestToChangeIntegrationApproval = (sku, id, prd_id, approve, old_approve, int_to) => {
        $.ajax({
            url: base_url + "products/changeIntegrationApproval",
            type: "POST",
            data: {
                id, sku, prd_id, approve, old_approve, int_to
            },
            async: true,
            success: function (data) {
                console.log(data);
                span = document.getElementById("statusApproval_" + id);
                span.innerHTML = "";
                if (approve == 1) {
                    span.className = 'label label-success';
                    var aprovado_txt = "<?php echo mb_strtoupper($this->lang->line('application_approved'), 'UTF-8'); ?>";
                } else if (approve == 2) {
                    span.className = 'label label-danger';
                    var aprovado_txt = "<?php echo mb_strtoupper($this->lang->line('application_disapproved'), 'UTF-8'); ?>";
                } else if (approve == 3) {
                    span.className = 'label label-primary';
                    var aprovado_txt = "<?php echo mb_strtoupper($this->lang->line('application_approval'), 'UTF-8'); ?>";
                }
                txt = document.createTextNode(aprovado_txt);
                span.appendChild(txt);

                for (let approve_count = 1; approve_count <= 3; approve_count++) {
                    $(`[onclick="changeIntegrationApproval(event,'${sku}','${id}','${prd_id}','${approve_count}','${old_approve}','${int_to}')"]`)
                        .attr('onclick', `changeIntegrationApproval(event,'${sku}','${id}','${prd_id}','${approve_count}','${approve}','${int_to}')`);
                }
            },
            error: function (data) {
                AlertSweet.fire({
                    icon: 'Error',
                    title: 'Houve um erro ao atualizar o produto!'
                });
            }
        });
    }

    // APENAS ALTERA O STATUS NO FRONT
    async function changeIntegrationApproval (e, sku, id, prd_id, approve, old_approve, int_to) {
        e.preventDefault();
        if(approve == 2){
            return checkboxDisapproveProduct(e, sku, id, prd_id, approve, old_approve, int_to);
        }
        if (approve == 1) {
            let bodyForm = await $.ajax({
                url: base_url + "products/checkProductWithoutSkuMkt",
                async: true,
                type: "POST",
                data: {
                    products: [prd_id]
                }
            });

            if (bodyForm !== '') {
                $(e.target).closest('tr')
                    .find('td:eq(0) input[type="checkbox"]')
                    .prop('checked', true)
                    .trigger('change');

                Swal.fire({
                    title: 'Atenção',
                    html: bodyForm,
                    icon: 'warning',
                    showCancelButton: true,
                    cancelButtonText: "Cancelar",
                    confirmButtonText: "Confirmar",
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.value == true) {
                        requestToChangeIntegrationApproval(sku, id, prd_id, approve, old_approve, int_to);
                    }
                });
            } else {
                requestToChangeIntegrationApproval(sku, id, prd_id, approve, old_approve, int_to);
            }
        } else {
            requestToChangeIntegrationApproval(sku, id, prd_id, approve, old_approve, int_to);
        }
    }

    // AÇÃO DE MUDAR O STATUS NO BACK MUTIPLOS
    async function checkboxDisapproveProductMutiple(event,element) {
        event.preventDefault();

        if ($(".productsselect:checked").length <= 0) {
            Swal.fire(
                'Por favor, Selecione algum(s) produto(s)',
                '',
                'info'
            );
            return false;
        }

        var identify = $(element).data("identify");
        var msg,bodyForm = '';
        var count = $(".productsselect:checked").length;

        if (identify == 'approve_product') {
            msg = "Tem certeza que deseja aprovar " + count + " produto(s)?";
            bodyForm = '';

            const check_skumkt = await $.ajax({
                url: base_url + "products/checkProductWithoutSkuMkt",
                async: true,
                type: "POST",
                data: {
                    products: productsList
                }
            });
            bodyForm += check_skumkt;
        }else if(identify == 'disapprove_product'){
            msg = "Tem certeza que deseja reprovar todos "+count+" produto(s)?";
            bodyForm =`
                            <div class="row" style="line-height: 4;">
                                <div class="col-sm-2">
                                    <div class="form-check">
                                        <input class="form-check-input" name="check_image" type="checkbox" id="check_image">
                                        <label class="form-check-label" for="check_image">
                                            <i class="fa fa-camera icon-color">&nbsp</i> Imagem
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-check">
                                        <input class="form-check-input" name="check_categeory" type="checkbox" id="check_categeory">
                                        <label class="form-check-label" for="check_categeory">
                                            <i class="fa-solid fa-sitemap icon-color">&nbsp;</i> Categoria
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-check">
                                        <input class="form-check-input" name="check_dimensions" type="checkbox" id="check_dimensions">
                                        <label class="form-check-label" for="check_dimensions">
                                            <i class="fa-solid fa-ruler-vertical icon-color">&nbsp;</i> Dimensões
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-check">
                                        <input class="form-check-input" name="check_price" type="checkbox" id="check_price">
                                        <label class="form-check-label" for="check_price">
                                            <i class="fa-solid fa-dollar-sign icon-color">&nbsp;</i> Preço
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <div class="form-check">
                                        <input class="form-check-input" name="check_description" type="checkbox" id="check_description">
                                        <label class="form-check-label" for="check_description">
                                            <i class="fa-solid fa-file-lines icon-color">&nbsp;</i> Descrição
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-check container-fluid row">
                                        <a data-toggle="collapse" class="btn btn-primary" data-target="#comment" style="float: left;margin-bottom: 11px;">Adicionar comentário</a>
                                    </div>
                                    <div id="comment" class="collapse">
                                        <textarea class="form-control" name="comment_error" id="comment_error" onkeyup="countCaracter()" placeholder="Máximo 100 caracteres" cols="10" maxlength="100" rows="5"></textarea>
                                        <span id="infor"></span>
                                    </div>
                                </div>
                            </div>
                        `;
        }else{
            msg = "Tem certeza que deseja colocar em aprovação " + count + " produto(s)?";
            bodyForm = '';
        }

        Swal.fire({
            title: msg,
            html: bodyForm,
            icon: 'warning',
            showCancelButton: true,
            cancelButtonText: "Cancelar",
            confirmButtonText: "Confirmar",
            allowOutsideClick: false
        }).then((result) => {
            if (result.value == true) {

                if(identify == 'disapprove_product'){
                    if ($('.form-check-input:checked').length <= 0) {
                        AlertSweet.fire({
                            icon: 'Error',
                            title: 'É obrigatório marcar alguma opção de erro.'
                        });
                        return false;
                    }
                }

                var check_image = $('#check_image:checked').val();
                var check_categeory = $('#check_categeory:checked').val();
                var check_dimensions = $('#check_dimensions:checked').val();
                var check_price = $('#check_price:checked').val();
                var check_description = $('#check_description:checked').val();
                var comment_error = $('#comment_error').val();

                $.ajax({
                    url: base_url + "products/markProductsApproval",
                    type: "POST",
                    data: {
                        id: productsList,
                        check_image:check_image,
                        check_categeory:check_categeory,
                        check_dimensions:check_dimensions,
                        check_price:check_price,
                        check_description:check_description,
                        comment_error:comment_error,
                        identify:identify
                    },
                    async: true,
                    success: function (data) {
                        location.reload();
                    }
                });
            }
            return false;
        })
    }

    // AÇÃO DE MUDAR O STATUS NO BACK SINGULAR
    function checkboxDisapproveProduct(e, sku, id, prd_id, approve, old_approve, int_to) {
        event.preventDefault();
        var msg,bodyForm = '';
        msg = "Tem certeza que deseja reprovar esse produto?";
        bodyForm =`
                        <div class="row" style="line-height: 4;">
                            <div class="col-sm-2">
                                <div class="form-check">
                                    <input class="form-check-input" name="check_image" type="checkbox" id="check_image">
                                    <label class="form-check-label" for="check_image">
                                        <i class="fa fa-camera icon-color">&nbsp</i> Imagem
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-check">
                                    <input class="form-check-input" name="check_categeory" type="checkbox" id="check_categeory">
                                    <label class="form-check-label" for="check_categeory">
                                        <i class="fa-solid fa-sitemap icon-color">&nbsp;</i> Categoria
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-check">
                                    <input class="form-check-input" name="check_dimensions" type="checkbox" id="check_dimensions">
                                    <label class="form-check-label" for="check_dimensions">
                                        <i class="fa-solid fa-ruler-vertical icon-color">&nbsp;</i> Dimensões
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-check">
                                    <input class="form-check-input" name="check_price" type="checkbox" id="check_price">
                                    <label class="form-check-label" for="check_price">
                                        <i class="fa-solid fa-dollar-sign icon-color">&nbsp;</i> Preço
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-check">
                                    <input class="form-check-input" name="check_description" type="checkbox" id="check_description">
                                    <label class="form-check-label" for="check_description">
                                        <i class="fa-solid fa-file-lines icon-color">&nbsp;</i> Descrição
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-check container-fluid row">
                                    <a data-toggle="collapse" class="btn btn-primary" data-target="#comment" style="float: left;margin-bottom: 11px;">Adicionar comentário</a>
                                </div>
                                <div id="comment" class="collapse">
                                    <textarea class="form-control" name="comment_error" id="comment_error" onkeyup="countCaracter()" placeholder="Máximo 100 caracteres" cols="10" maxlength="100" rows="5"></textarea>
                                    <span id="infor"></span>
                                </div>
                            </div>
                        </div>
                    `;
        Swal.fire({
            title: msg,
            html: bodyForm,
            icon: 'warning',
            showCancelButton: true,
            cancelButtonText: "Cancelar",
            confirmButtonText: "Confirmar reprovação",
            allowOutsideClick: false
        }).then((result) => {
            if (result.value == true) {

                if ($('.form-check-input:checked').length <= 0) {
                    AlertSweet.fire({
                        icon: 'Error',
                        title: 'É obrigatório marcar alguma opção de erro.'
                    });
                    return false;
                }

                var check_image = $('#check_image:checked').val();
                var check_categeory = $('#check_categeory:checked').val();
                var check_dimensions = $('#check_dimensions:checked').val();
                var check_price = $('#check_price:checked').val();
                var check_description = $('#check_description:checked').val();
                var comment_error = $('#comment_error').val();
                var disapprove_product = 2;

                $.ajax({
                    url: base_url + "products/changeIntegrationApproval",
                    type: "POST",
                    data: {
                        id: id,
                        sku: sku,
                        prd_id: prd_id,
                        approve:approve,
                        old_approve:old_approve,
                        int_to:int_to,
                        check_image:check_image,
                        check_categeory:check_categeory,
                        check_dimensions:check_dimensions,
                        check_price:check_price,
                        check_description:check_description,
                        comment_error:comment_error,
                        disapprove_product:disapprove_product,
                    },
                    async: true,
                    success: function (data) {
                        span = document.getElementById("statusApproval_" + id);
                        span.innerHTML = "";
                        if (approve == 2) {
                            span.className = 'label label-danger';
                            var aprovado_txt = "<?php echo mb_strtoupper($this->lang->line('application_disapproved'), 'UTF-8'); ?>";
                        }
                        txt = document.createTextNode(aprovado_txt);
                        span.appendChild(txt);

                        for (let approve_count = 1; approve_count <= 3; approve_count++) {
                            $(`[onclick="changeIntegrationApproval(event,'${sku}','${id}','${prd_id}','${approve_count}','${old_approve}','${int_to}')"]`)
                                .attr('onclick', `changeIntegrationApproval(event,'${sku}','${id}','${prd_id}','${approve_count}','${approve}','${int_to}')`);
                        }

                        return true;
                    }
                });
            }
            return false;
        })
    }

    function checkCheckbox(event){
        event.preventDefault();
        if ($(".productsselect:checked").length <= 0) {
            Swal.fire(
                'Por favor, Selecione algum(s) produto(s)',
                '',
                'info'
            );
            return false;
        }
        $('#listview').val(JSON.stringify({productsList}));
        $('#formPreview').submit();
    }

    function countCaracter(){
        count = $('#comment_error').val();
        showCount = count.length;
        if(showCount >= 100){
            $('#infor').text('Você atingiu 100 caracteres').css({'float':'left','color':'red'});
            return false;
        }
        $('#infor').text("Total: "+showCount).css({'float':'left','color':'black'});
    }

    function clearFilters() {
        $('#name').val('');
        $('#category').val('default').selectpicker("refresh");
        $('#status').val('default').selectpicker("refresh");
        $('#lojas').val('default').selectpicker("refresh");
        $('#int_to').val('default').selectpicker("refresh");
        $('#sku').val('');
        $('#estoque').val('default');
        $('#operador').val('default').selectpicker("refresh");
        $('#operadorvalor').val(''),
            $('#manageTable-select-all').prop("checked", false);
        getStart();
    }

    $('#show_top').hide();
    $(document).on('click', '#btn_first_column_show', function () {
        $('#show_top').hide();
        $('#show_down').show();
        $(this).attr("id", "btn_first_column_hide").html('Ocultar Filtros')
            .append('<i class="fa fa-filter pull-left" style="padding: 4px 5px 0px 0px;"></i>');
    });

    $(document).on('click', '#btn_first_column_hide', function () {
        $('#show_top').show();
        $('#show_down').show();
        $(this).attr("id", "btn_first_column_show").html('Exibir Filtros')
            .append('<i class="fa fa-filter pull-left" style="padding: 3px 5px 0px 0px;"></i>');
    });

</script>
