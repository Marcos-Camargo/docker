<?php include_once(APPPATH . '/third_party/zipcode.php'); ?>
<div class="content-wrapper">
	<?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data); ?>
    <section class="content">
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
                <div class="box box-primary">
                    <form role="form" action="<?php base_url('orders/create') ?>" method="post" id="formCreateOrder">
                        <div class="box-body">
                            <?php
                            if (validation_errors()) {
                                foreach (explode("</p>",validation_errors()) as $erro) {
                                    $erro = trim($erro);
                                    if ($erro!="") { ?>
                                        <div class="alert alert-error alert-dismissible" role="alert">
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            <?php echo $erro."</p>"; ?>
                                        </div>
                            <?php	}
                                }
                            } ?>
                            <div class="col-md-12 form-group">
                                <div class="panel panel-primary">
                                    <div class="panel-heading">Dados do Cliente</div>
                                    <div class="panel-body">
                                        <div class="form-group col-md-12 col-xs-12 d-flex justify-content-center">
                                            <button type="button" class="btn btn-primary col-md-3" id="generateDataFakeCustomer">Gerar Dados de Exemplo</button>
                                        </div>
                                        <div class="form-group col-md-6 col-xs-12">
                                            <label>Cliente</label>
                                            <input type="text" class="form-control" name="customer_name" required>
                                        </div>
                                        <div class="form-group col-md-3 col-xs-12">
                                            <label>Telefone 1</label>
                                            <input type="text" class="form-control phone_mask" name="customer_phone1" required>
                                        </div>
                                        <div class="form-group col-md-3 col-xs-12">
                                            <label>Telefone 2</label>
                                            <input type="text" class="form-control phone_mask" name="customer_phone2" required>
                                        </div>
                                        <div class="form-group col-md-6 col-xs-12">
                                            <label>Email</label>
                                            <input type="email" class="form-control" name="customer_email" required>
                                        </div>
                                        <div class="form-group col-md-3 col-xs-12">
                                            <label>CPF</label>
                                            <input type="text" class="form-control" name="customer_cpf_cnpj" required>
                                        </div>
                                        <div class="form-group col-md-3 col-xs-12">
                                            <label>RG</label>
                                            <input type="text" class="form-control" name="customer_rg_ie" required>
                                        </div>
                                        <div class="form-group col-md-3 col-xs-12">
                                            <label>CEP</label>
                                            <input type="text" class="form-control" name="zipcode" required>
                                        </div>
                                        <div class="form-group col-md-6 col-xs-12">
                                            <label>Logradouro</label>
                                            <input type="text" class="form-control" name="address" required>
                                        </div>
                                        <div class="form-group col-md-3 col-xs-12">
                                            <label>Número</label>
                                            <input type="text" class="form-control" name="addr_num" required>
                                        </div>
                                        <div class="form-group col-md-6 col-xs-12">
                                            <label>Complemento</label>
                                            <input type="text" class="form-control" name="addr_compl" required>
                                        </div>
                                        <div class="form-group col-md-6 col-xs-12">
                                            <label>Reference</label>
                                            <input type="text" class="form-control" name="customer_reference" required>
                                        </div>
                                        <div class="form-group col-md-4 col-xs-12">
                                            <label>Bairro</label>
                                            <input type="text" class="form-control" name="addr_neigh" required>
                                        </div>
                                        <div class="form-group col-md-4 col-xs-12">
                                            <label>Cidade</label>
                                            <input type="text" class="form-control" name="addr_city" required>
                                        </div>
                                        <div class="form-group col-md-4 col-xs-12">
                                            <label>Estado (UF)</label>
                                            <select class="form-control" name="addr_uf" required>
                                                <option value=""><?=$this->lang->line('application_select');?></option>
                                                <?php foreach ($ufs as $k => $v): ?>
                                                    <option value="<?=trim($k)?>" <?= set_select('addr_uf', $k) ?>><?=$v ?></option>
                                                <?php endforeach ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-6 col-xs-12">
                                <label>Empresa</label>
                                <select name="company" class="form-control" required>
                                    <option value="0">Selecione uma empresa</option>
                                    <?php
                                    foreach ($company_data as $company)
                                        echo "<option value='{$company['id']}'>{$company['name']}</option>";
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 col-xs-12 form-group">
                                <label>Loja</label>
                                <select name="store" class="form-control" disabled required></select>
                            </div>
                            <div class="col-md-12 form-group">
                                <div class="panel panel-primary">
                                    <div class="panel-heading">Grade de Produtos</div>
                                    <div class="panel-body">
                                        <div class="col-md-7 col-xs-12 form-group">
                                            <label>Produtos</label>
                                            <select name="get_products" class="form-control" disabled></select>
                                        </div>
                                        <div class="col-md-5 col-xs-12 form-group">
                                            <label>Variação</label>
                                            <select name="get_variant" class="form-control" disabled></select>
                                        </div>
                                        <div class="col-md-2 col-xs-12 form-group">
                                            <label>Estoque</label>
                                            <input type="number" class="form-control" name="get_stock" value="1" min="1" disabled>
                                            <small class="font-weight-bold">Disponível: <span class="stock-available">0</span></small>
                                        </div>
                                        <div class="col-md-2 col-xs-12 form-group">
                                            <label>Preço Unitário</label>
                                            <input type="text" class="form-control" name="get_price" value="0,00" disabled>
                                        </div>
                                        <div class="col-md-2 col-xs-12 form-group">
                                            <label>Desconto Unitário</label>
                                            <input type="text" class="form-control" name="get_discount" value="0,00" disabled>
                                        </div>
                                        <div class="col-md-2 col-xs-12 form-group">
                                            <label>Total Produto Bruto</label>
                                            <input type="text" class="form-control" name="get_value_gross" value="0,00" disabled>
                                        </div>
                                        <div class="col-md-2 col-xs-12 form-group">
                                            <label>Total Produto Líquido</label>
                                            <input type="text" class="form-control" name="get_value_net" value="0,00" disabled>
                                        </div>
                                        <div class="col-md-2 col-xs-12 form-group">
                                            <label>&nbsp;</label><br>
                                            <button type="button" class="btn btn-success btn-flag col-md-6" id="addProductGrid" disabled><i class="fa fa-plus"></i></button>
                                            <button type="button" class="btn btn-danger btn-flag col-md-6" id="cleanProductGrid"><i class="fa fa-trash"></i></button>
                                        </div>
                                        <br><br>
                                        <div class="col-md-12 col-xs-12 form-group">
                                            <table class="table" id="gridProducts">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Nome do Produto</th>
                                                        <th>SKU</th>
                                                        <th>Qtd</th>
                                                        <th>Preço Un.</th>
                                                        <th>Desconto Un.</th>
                                                        <th>Preço Total</th>
                                                        <th>Excluir</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Valor Desconto</label>
                                <input type="text" name="discount_value_total" class="form-control" value="0,00" disabled>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Valor Produtos</label>
                                <input type="text" name="products_value_total" class="form-control" value="0,00" disabled>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Valor do Frete</label>
                                <input type="text" name="ship_value" class="form-control" value="0,00">
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Taxa de Serviço</label>
                                <input type="number" name="tax_service" class="form-control" value="0" max="100" min="0">
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Valor Total Pedido</label>
                                <input type="text" name="value_total_order" class="form-control" value="0,00" disabled>
                                <small>Valor que o cliente pagou</small>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Valor Líquido</label>
                                <input type="text" name="net_value_total" class="form-control" value="0,00" disabled>
                            </div>
                            <div class="row"></div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Origem</label>
                                <select name="origin" class="form-control" required>
                                    <?php
                                    foreach ($origin_data as $origin)
                                        echo "<option value='{$origin['int_to']}'>{$origin['name']}</option>";
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Status</label>
                                <select class="form-control" name="status" required>
                                    <option value="1">1 - <?=$this->lang->line('application_order_1')?></option>
                                    <option value="3">3 - <?=$this->lang->line('application_order_3')?></option>
                                    <option value="4">4 - <?=$this->lang->line('application_order_4')?></option>
                                    <option value="5">5 - <?=$this->lang->line('application_order_5')?></option>
                                    <option value="6">6 - <?=$this->lang->line('application_order_6')?></option>
                                    <option value="40">40 - <?=$this->lang->line('application_order_40')?></option>
                                    <option value="43">43 - <?=$this->lang->line('application_order_43')?></option>
                                    <option value="45">45 - <?=$this->lang->line('application_order_45')?></option>
                                    <option value="50">50 - <?=$this->lang->line('application_order_50')?></option>
                                    <option value="51">51 - <?=$this->lang->line('application_order_51')?></option>
                                    <option value="52">52 - <?=$this->lang->line('application_order_52')?></option>
                                    <option value="53">53 - <?=$this->lang->line('application_order_53')?></option>
                                    <option value="54">54 - <?=$this->lang->line('application_order_54')?></option>
                                    <option value="55">55 - <?=$this->lang->line('application_order_55')?></option>
                                    <option value="56">56 - <?=$this->lang->line('application_order_56')?></option>
                                    <option value="57">57 - <?=$this->lang->line('application_order_57')?></option>
                                    <option value="60">60 - <?=$this->lang->line('application_order_60')?></option>
                                    <option value="95">95 - <?=$this->lang->line('application_order_95')?></option>
                                    <option value="96">96 - <?=$this->lang->line('application_order_96')?></option>
                                    <option value="97">97 - <?=$this->lang->line('application_order_97')?></option>
                                    <option value="98">98 - <?=$this->lang->line('application_order_98')?></option>
                                    <option value="99">99 - <?=$this->lang->line('application_order_99')?></option>
                                    <option value="101">101 - <?=$this->lang->line('application_order_101')?></option>
                                </select>
                            </div>
                            <div class="row"></div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Data pagamento</label>
                                <input type="date" name="data_pago" class="form-control" disabled>
                                <small>Se o status for diferente de 1</small>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Data para expedir</label>
                                <input type="date" name="data_limite_cross_docking" class="form-control" disabled>
                                <small>Se o status for diferente de 1</small>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Data envio</label>
                                <input type="date" name="data_envio" class="form-control" disabled>
                                <small>Se o status for 5, 6, 45, 55, 60, 95, 96, 97, 98, 99</small>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Data entrega</label>
                                <input type="date" name="data_entrega" class="form-control" disabled>
                                <small>Se o status for 6, 60, 95, 96, 97, 98, 99</small>
                            </div>
                            <div class="row"></div>
                            <div class="col-md-6 col-xs-12 form-group">
                                <label>Empresa Prevista de Logística</label>
                                <input type="text" name="ship_company_preview" class="form-control" required>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Serviço Prevista de Logística</label>
                                <input type="text" name="ship_service_preview" class="form-control" required>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Dias Úteis Previsto de Entrega</label>
                                <input type="number" name="ship_time_preview" class="form-control" required>
                            </div>
                            <div id="content-nfe" style="display: none">
                                <div class="col-md-12 form-group d-flex" style="align-items: end;">
                                    <h3 class="mb-0">Nota Fiscal</h3> <label class="ml-4"><input type="checkbox" name="set_nfe_order"> Informar NFe</label>
                                </div>
                                <div class="col-md-2 col-xs-12 form-group">
                                    <label for="nfe_number">Número</label>
                                    <input type="number" name="nfe_number" id="nfe_number" class="form-control" disabled>
                                </div>
                                <div class="col-md-2 col-xs-12 form-group">
                                    <label for="nfe_serie">Série</label>
                                    <input type="number" name="nfe_serie" id="nfe_serie" class="form-control" disabled>
                                </div>
                                <div class="col-md-3 col-xs-12 form-group">
                                    <label for="nfe_date">Data de Emissão</label>
                                    <input type="text" name="nfe_date" id="nfe_date" class="form-control" disabled>
                                </div>
                                <div class="col-md-5 col-xs-12 form-group">
                                    <label for="nfe_key">Chave</label>
                                    <input type="number" name="nfe_key" id="nfe_key" class="form-control" disabled>
                                </div>
                            </div>
                            <div id="content-payment">
                                <div class="col-md-12 form-group d-flex mt-4">
                                    <h3 class="mb-0 mt-0">Pagamento</h3>
                                    <button type="button" class="btn btn-success btn-flat btn-sm ml-5" id="add_payment"><i class="fa fa-plus"></i> Nova Parcela</button>
                                </div>
                                <div id="payments"></div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="button" id="submitForm" class="btn btn-primary"><?=$this->lang->line('application_create_order');?></button>
                            <button type="submit" id="btnSubmitForm" class="d-none"></button>
                            <a href="<?php echo base_url('orders/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.rawgit.com/plentz/jquery-maskmoney/master/dist/jquery.maskMoney.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script type="text/javascript">

    var base_url = "<?php echo base_url(); ?>";
    var dataProduct = [];
    var dataVariant = [];

    $(document).ready(function() {
        $('[name="get_price"], [name="get_discount"], [name="ship_value"], [name="payment_value[]"]').maskMoney({thousands: '.', decimal: ',', allowZero: true});
        $('[name="company"], [name="store"], [name="get_products"], [name="get_variant"], [name="status"]').select2();

        const SPMaskBehavior = function (val) {
            return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
        },
        spOptions = {
            onKeyPress: function(val, e, field, options) {
                field.mask(SPMaskBehavior.apply({}, arguments), options);
            }
        };

        $('.phone_mask').mask(SPMaskBehavior, spOptions);
        $('[name="customer_cpf_cnpj"]').mask("000.000.000-00");
        $('[name="zipcode"]').mask("00.000-000");

        $('#nfe_date').datetimepicker({
            format: "DD/MM/YYYY HH:mm:ss"
        });
    });

    // converte valor de Float -> R$
    const numberToReal = numero => {
        numero = parseFloat(numero);
        numero = numero.toFixed(2).split('.');
        numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
        return numero.join(',');
    }

    // converte valor de R$ -> Float
    const realToNumber = numero => {
        if(numero === undefined) return false;
        numero = numero.toString();
        numero = numero.replace(/\./g, "").replace(/,/g, ".");
        return parseFloat(numero);
    }

    const getStores = company => {

        $('#gridProducts tbody').empty();
        $('[name="store"]').select2('destroy').empty().prop('disabled', true);
        cleanValuesProduct(true);

        company = parseInt(company);
        if (company === 0) {
            $('[name="store"]').select2();
            return false;
        }

        const url = "<?=base_url('stores/getStoresAjaxByCompany')?>";
        $.post( url, { company }, response => {
            let options = '<option value="0">Selecione uma loja</option>';
            $.each(response, function( index, value ) {
                options += `<option value="${value.id}">${value.name}</option>`;
            });

            $('[name="store"]').append(options).select2();
        }, "json").fail(e => {
            console.log(e)
        }).always(function() {
            $('[name="store"]').prop('disabled', false);
        });
    }

    const getProducts = store => {

        $('#gridProducts tbody').empty();
        $('[name="get_products"]').select2('destroy').empty().prop('disabled', true);
        cleanValuesProduct(true);

        store = parseInt(store);
        if (store === 0) {
            $('[name="get_products"]').select2();
            return false;
        }

        const url = "<?=base_url('products/getProductsAjaxByStore')?>";
        $.post( url, { store }, response => {
            let options = '<option value="0">Selecione um produto</option>';
            $.each(response, function( index, value ) {
                options += `<option value="${value.id}">${value.sku} - ${value.name}</option>`;
            });

            $('[name="get_products"]').append(options).select2();
        }, "json").fail(e => {
            console.log(e)
        }).always(function() {
            $('[name="get_products"]').prop('disabled', false);
        });
    }

    const sleep = ms => {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    const getVariants = async product => {
        dataProduct = [];
        dataVariant = [];

        await sleep(10);

        $('[name="get_variant"], [name="get_products"]').select2('destroy');
        $('[name="get_variant"]').empty().prop('disabled', true);
        cleanValuesProduct();

        product = parseInt(product);
        if (product === 0) {
            $('[name="get_variant"], [name="get_products"]').select2();
            return false;
        }

        const url = "<?=base_url('products/getVariantAjaxByProduct')?>";
        $.post( url, { product }, response => {

            let options = '<option value="">Selecione uma variação</option>';

            dataProduct = response.product;

            if (response.var.length !== 0) {
                cleanValuesProduct();
                $.each(response.var, function (index, value) {
                    options += `<option value="${value.variant}">${value.sku} | ${value.name.replaceAll(';', ' - ')}</option>`;
                });

            } else {
                options = '<option value="">Produto sem variação</option>';
                $('[name="get_price"]').val(numberToReal(dataProduct.price));
                $('[name="get_value_net"], [name="get_value_gross"]').val(numberToReal(dataProduct.price));
                $('small .stock-available').text(dataProduct.qty);
                $('[name="get_stock"], [name="get_price"], [name="get_discount"]').prop('disabled', false);
                $('#addProductGrid').prop('disabled', false);
            }

            $('[name="get_variant"]').prop('disabled', response.var.length === 0).append(options).select2();
            $('[name="get_products"]').select2();
        }, "json").fail(e => {
            console.log(e)
        });
    }

    const getDataVariant = (product, variant) => {

        dataVariant = [];
        product = parseInt(product);

        console.log(variant, product);
        cleanValuesProduct();

        if (variant === '' || variant === null || product === 0 || isNaN(product)) return false;

        const url = "<?=base_url('products/getVariantAjaxByVariant')?>";
        $.post( url, { product, variant }, response => {

            if (!response) return false;

            $('[name="get_price"]').val(numberToReal(response.price));
            $('[name="get_value_net"], [name="get_value_gross"]').val(numberToReal(response.price));
            $('small .stock-available').text(response.qty);
            $('[name="get_stock"], [name="get_price"], [name="get_discount"]').prop('disabled', false);
            $('#addProductGrid').prop('disabled', false);

            dataVariant = response;

        }, "json").fail(e => {
            console.log(e)
        });
    }

    const recalculatePriceProduct = () => {
        let stock    = realToNumber($('[name="get_stock"]').val());
        let price    = realToNumber($('[name="get_price"]').val());
        let discount = realToNumber($('[name="get_discount"]').val());

        if (isNaN(stock)) stock = 0;
        if (isNaN(price)) price = 0;
        if (isNaN(discount)) discount = 0;

        $('[name="get_value_gross"]').val(numberToReal(price * stock));
        $('[name="get_value_net"]').val(numberToReal((price - discount) * stock));
    }

    const recalculateValuesTotals = () => {

        let totalPriceProd = 0;
        let totalDiscountProd = 0;
        $('#gridProducts tbody tr').each(function() {
            totalPriceProd += realToNumber($('td:eq(6)', this).text());
            totalDiscountProd += realToNumber($('td:eq(5)', this).text());
        });

        let shipValue     = realToNumber($('[name="ship_value"]').val());
        let taxService    = parseInt($('[name="tax_service"]').val());

        if (isNaN(shipValue)) shipValue = 0;
        if (isNaN(taxService)) taxService = 0;

        $('[name="discount_value_total"]').val(numberToReal(totalDiscountProd));
        $('[name="products_value_total"]').val(numberToReal(totalPriceProd));
        $('[name="value_total_order"]').val(numberToReal(totalPriceProd + shipValue));
        $('[name="net_value_total"]').val(numberToReal((totalPriceProd + shipValue) * ((100 - taxService) / 100)));

    }

    const cleanValuesProduct = (cleanVariant = false) => {
        $('[name="get_stock"]').val(1);
        $('[name="get_price"], [name="get_discount"], [name="get_value_net"], [name="get_value_gross"]').val('0,00');
        $('[name="get_stock"], [name="get_price"], [name="get_discount"]').prop('disabled', true);
        $('small .stock-available').text(0);
        $('#addProductGrid').prop('disabled', true);
        if (cleanVariant)
            $('[name="get_variant"]').select2('destroy').empty().prop('disabled', true).select2();

        recalculateValuesTotals();
    }

    const cleanGridProduct = () => {
        cleanValuesProduct();
        $('[name="get_products"]').val(0).trigger('change');
        dataProduct = [];
        dataVariant = [];
        $('#addProductGrid').prop('disabled', true);
    }

    const finishOrder = () => {
        let id, stock, price, discount, price_total;
        $('[name="product_id[]"], [name="product_stock[]"], [name="product_price[]"], [name="product_discount[]"], [name="product_price_total[]"]').remove();
        $('#gridProducts tbody tr').each(function() {
            id = $(this).find('td:eq(0)').text();
            stock = $(this).find('td:eq(3)').text();
            price = realToNumber($(this).find('td:eq(4)').text());
            discount = realToNumber($(this).find('td:eq(5)').text());
            price_total = realToNumber($(this).find('td:eq(6)').text());

            $('#formCreateOrder').append(`
                <input type="hidden" name="product_id[]" value="${id}">
                <input type="hidden" name="product_stock[]" value="${stock}">
                <input type="hidden" name="product_price[]" value="${price}">
                <input type="hidden" name="product_discount[]" value="${discount}">
                <input type="hidden" name="product_price_total[]" value="${price_total}">
            `);
        });
    }

    $(document).on('click', '#submitForm', function (e){

        const btnSubmit = $(this);
        btnSubmit.prop('disabled', true);

        if ($('#gridProducts tbody tr').length === 0) {
            alert('adicione itens para criar o pedido');
            btnSubmit.prop('disabled', false);
            return false;
        }

        if ($('[name="set_nfe_order"]').is(':checked') && $('[name="set_nfe_order"]').is(':visible')) { // Valida NFe
            const key           = $('#nfe_key').val();
            const serie         = $('#nfe_serie').val();
            const number        = $('#nfe_number').val();
            const dateEmission  = $('#nfe_date').val();
            const store_id      = $('[name="store"]').val();

            const url = "<?=base_url('orders/checkNFeValid')?>";

            $.ajax({
                type: "POST",
                enctype: 'multipart/form-data',
                data: { key, serie, number, dateEmission, store_id },
                url,
                dataType: "json",
                async: true,
                success: function (response) {
                    console.log(response);
                    if (!response.success) {
                        alert(response.message);
                        btnSubmit.prop('disabled', false);
                    } else {
                        finishOrder();
                        setTimeout(() => {
                            $('#btnSubmitForm').trigger('click');
                            setTimeout(() => {
                                btnSubmit.prop('disabled', false);
                            }, 2000);
                        }, 1000);
                    }
                },
                error: function (error) {
                    alert('Erro na validação da nota fiscal, reveja os dados.');
                    btnSubmit.prop('disabled', false);
                }
            });
        } else {
            finishOrder();
            setTimeout(() => {
                $('#btnSubmitForm').trigger('click');
                setTimeout(() => {
                    btnSubmit.prop('disabled', false);
                }, 2000);
            }, 1000);
        }
    });

    $('#generateDataFakeCustomer').click(function(){
        $('input[name="customer_name"]').val('Cliente Ambiente Teste');
        $('input[name="customer_phone1"]').val('(48) 98765-4321');
        $('input[name="customer_phone2"]').val('(48) 4321-5678');
        $('input[name="customer_email"]').val('cliente@email.com');
        $('input[name="customer_cpf_cnpj"]').val('182.068.850-05');
        $('input[name="customer_rg_ie"]').val('25.962.899-2');
        $('input[name="zipcode"]').val('88.010-000');
        $('input[name="addr_num"]').val('100');
        $('input[name="addr_compl"]').val('Apto 507');
        $('input[name="customer_reference"]').val('Próximo ao Cia do homem');

        setTimeout(() => {
            $('input[name="zipcode"]').trigger('keyup');
        },250)
    });

    $('[name="company"]').change(function(){
        getStores($(this).val());
    });

    $('[name="store"]').change(function(){
        getProducts($(this).val());
    });

    $('[name="get_products"]').change(function(){
        getVariants($(this).val());
    });

    $('[name="get_variant"]').change(function(){
        getDataVariant($('[name="get_products"]').val(), $(this).val());
    });

    $('[name="get_stock"], [name="get_price"], [name="get_discount"]').keyup(function(){
        recalculatePriceProduct();
    });

    $('[name="ship_value"], [name="tax_service"]').keyup(function(){
        recalculateValuesTotals();
    });

    $('#cleanProductGrid').click(function(){
        cleanGridProduct();
    });

    $('#addProductGrid').click(async function() {
        const stock = realToNumber($('[name="get_stock"]').val());
        const price = realToNumber($('[name="get_price"]').val());
        const discount = realToNumber($('[name="get_discount"]').val());
        let product = parseInt($('[name="get_products"]').val());
        const total = (price - discount) * stock;

        if (stock === 0) {
            alert('informe estoque');
            return false;
        }
        if (price === 0 || total === 0) {
            alert('informe preço');
            return false;
        }
        if (dataProduct.id === undefined) {
            alert('informe um produto');
            return false;
        }
        if ($('[name="get_variant"] option').length > 1 && dataVariant.id === undefined) {
            alert('informe uma variação');
            return false;
        }

        if (dataVariant.id !== undefined) product += `-${parseInt(dataVariant.variant)}`;

        let productInUse = false;
        $('#gridProducts tbody tr').each(function() {
            if ($(this).find('td:eq(0)').text() == product)
                productInUse = true;
        });
        if (productInUse) {
            alert('produto já selecionado');
            return false;
        }

        let nameProduct = dataProduct.name;
        if (dataVariant.id !== undefined) nameProduct += ` ( ${dataVariant.name.replaceAll(';', ' - ')} )`;

        let skuProduct = dataProduct.sku;
        if (dataVariant.id !== undefined) skuProduct += ` ( ${dataVariant.sku} )`;

        $('#gridProducts tbody').append(`
            <tr>
                <td>${product}</td>
                <td>${nameProduct}</td>
                <td>${skuProduct}</td>
                <td>${stock}</td>
                <td>${numberToReal(price)}</td>
                <td>${numberToReal(discount)}</td>
                <td>${numberToReal(total)}</td>
                <td><button type="button" class="btn btn-danger btn-flat btn-sm removeItemGrid"><i class="fa fa-trash"></i></button></td>
            </tr>
        `);
        cleanGridProduct();

        recalculateValuesTotals();
    });

    $('[name="status"]').change(function(){
        const status        = parseInt($(this).val());
        const dataEntrega   = $('[name="data_entrega"]');
        const dataEnvio     = $('[name="data_envio"]');
        const dataExpedir   = $('[name="data_limite_cross_docking"]');
        const dataPagamento = $('[name="data_pago"]');

        switch (status) {
            case 1:
                dataExpedir.prop({'disabled':true, 'required': false});
                dataPagamento.prop({'disabled':true, 'required': false});
                dataEnvio.prop({'disabled':true, 'required': false});
                dataEntrega.prop({'disabled':true, 'required': false});
                $('#content-nfe').hide();
                break;
            case 3:
            case 4:
            case 40:
            case 43:
            case 50:
            case 51:
            case 52:
            case 53:
            case 54:
            case 56:
            case 57:
            case 101:
                dataExpedir.prop({'disabled':false, 'required': true});
                dataPagamento.prop({'disabled':false, 'required': true});
                dataEnvio.prop({'disabled':true, 'required': false});
                dataEntrega.prop({'disabled':true, 'required': false});
                if (status !== 3) {
                    $('#content-nfe').show();
                } else {
                    $('#content-nfe').hide();
                }
                break;
            case 5:
            case 45:
            case 55:
                dataExpedir.prop({'disabled':false, 'required': true});
                dataPagamento.prop({'disabled':false, 'required': true});
                dataEnvio.prop({'disabled':false, 'required': true});
                dataEntrega.prop({'disabled':true, 'required': false});
                $('#content-nfe').show();
                break;
            case 6:
            case 60:
            case 95:
            case 96:
            case 97:
            case 98:
            case 99:
                dataExpedir.prop({'disabled':false, 'required': true});
                dataPagamento.prop({'disabled':false, 'required': true});
                dataEntrega.prop({'disabled':false, 'required': true});
                dataEnvio.prop({'disabled':false, 'required': true});
                $('#content-nfe').show();
                break;
            default:
                dataEntrega.prop({'disabled':true, 'required': false});
                dataEnvio.prop({'disabled':true, 'required': false});
                dataExpedir.prop({'disabled':true, 'required': false});
                dataPagamento.prop({'disabled':true, 'required': false});
                $('#content-nfe').show();

        }
    });

    $(document).on('click', '.removeItemGrid', function(){
        $(this).closest('tr').remove();
    });

    $('#add_payment').on('click', function(){

        if ($('#payments .payment').length >= 12) {
            Swal.fire({
                icon: 'error',
                title: 'Só é possível adicionar 12 parcelas.',
                showCancelButton: false,
                confirmButtonText: "Ok",
            });
            return false;
        }

        $('#payments').append(`<div class="payment row col-md-12">
            <div class="col-md-2 col-xs-12 form-group">
                <label>Parcela</label>
                <input type="number" name="payment_parcel[]" class="form-control" required>
            </div>
            <div class="col-md-2 col-xs-12 form-group">
                <label>Data</label>
                <input type="text" name="payment_date[]" class="form-control" required>
            </div>
            <div class="col-md-2 col-xs-12 form-group">
                <label>Valor</label>
                <input type="text" name="payment_value[]" class="form-control" required>
            </div>
            <div class="col-md-2 col-xs-12 form-group">
                <label>Descrição</label>
                <input type="text" name="payment_description[]" class="form-control" required>
                <small>Descrição do pagamento. Exemplo: Visa, Mastercard</small>
            </div>
            <div class="col-md-2 col-xs-12 form-group">
                <label>Tipo</label>
                <input type="text" name="payment_type[]" class="form-control" required>
                <small>Tipo do pagamento. Exemplo: Cartão de Crédito</small>
            </div>
            <div class="col-md-2 col-xs-12 form-group">
                <label>&nbsp;</label><br/>
                <button type="button" class="btn btn-danger btn-flat btn-sm remove_payment" title="Remover parcela"><i class="fa fa-trash"></i></button>
            </div>
        </div>`);

        $('[name="payment_date[]"]').datetimepicker({
            format: "DD/MM/YYYY HH:mm:ss"
        });

        $('[name="payment_value[]"]').maskMoney({thousands: '.', decimal: ',', allowZero: true});
    });

    $(document).on('click', '.remove_payment', function(){
         $(this).closest('.payment').remove();
    });

    $('[name="set_nfe_order"]').on('change', function(){
        $('#nfe_key').val('').prop('disabled', $(this).is(':not(:checked)')).prop('required', $(this).is(':checked'));
        $('#nfe_serie').val('').prop('disabled', $(this).is(':not(:checked)')).prop('required', $(this).is(':checked'));
        $('#nfe_number').val('').prop('disabled', $(this).is(':not(:checked)')).prop('required', $(this).is(':checked'));
        $('#nfe_date').val('').prop('disabled', $(this).is(':not(:checked)')).prop('required', $(this).is(':checked'));
    });

</script>