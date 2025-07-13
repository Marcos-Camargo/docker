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
                                        <div class="form-group col-md-6 col-xs-12">
                                            <label>Cliente</label>
                                            <input type="text" class="form-control" name="customer_name" value="<?=$order['customer_name']?>" disabled>
                                        </div>
                                        <div class="form-group col-md-3 col-xs-12">
                                            <label>Telefone 1</label>
                                            <input type="text" class="form-control phone_mask" name="customer_phone1" value="<?=$client['phone_1']?>" disabled>
                                        </div>
                                        <div class="form-group col-md-3 col-xs-12">
                                            <label>Telefone 2</label>
                                            <input type="text" class="form-control phone_mask" name="customer_phone2" value="<?=$client['phone_2']?>" disabled>
                                        </div>
                                        <div class="form-group col-md-6 col-xs-12">
                                            <label>Email</label>
                                            <input type="email" class="form-control" name="customer_email" value="<?=$client['email']?>" disabled>
                                        </div>
                                        <div class="form-group col-md-3 col-xs-12">
                                            <label>CPF</label>
                                            <input type="text" class="form-control" name="customer_cpf_cnpj" value="<?=$client['cpf_cnpj']?>" disabled>
                                        </div>
                                        <div class="form-group col-md-3 col-xs-12">
                                            <label>RG</label>
                                            <input type="text" class="form-control" name="customer_rg_ie" value="<?=$client['rg']?>" disabled>
                                        </div>
                                        <div class="form-group col-md-3 col-xs-12">
                                            <label>CEP</label>
                                            <input type="text" class="form-control" name="zipcode" value="<?=$order['customer_address_zip']?>" disabled>
                                        </div>
                                        <div class="form-group col-md-6 col-xs-12">
                                            <label>Logradouro</label>
                                            <input type="text" class="form-control" name="address" value="<?=$order['customer_address']?>" disabled>
                                        </div>
                                        <div class="form-group col-md-3 col-xs-12">
                                            <label>Número</label>
                                            <input type="text" class="form-control" name="addr_num" value="<?=$order['customer_address_num']?>" disabled>
                                        </div>
                                        <div class="form-group col-md-6 col-xs-12">
                                            <label>Complemento</label>
                                            <input type="text" class="form-control" name="addr_compl" value="<?=$order['customer_address_compl']?>" disabled>
                                        </div>
                                        <div class="form-group col-md-6 col-xs-12">
                                            <label>Reference</label>
                                            <input type="text" class="form-control" name="customer_reference" value="<?=$order['customer_reference']?>" disabled>
                                        </div>
                                        <div class="form-group col-md-4 col-xs-12">
                                            <label>Bairro</label>
                                            <input type="text" class="form-control" name="addr_neigh" value="<?=$order['customer_address_neigh']?>" disabled>
                                        </div>
                                        <div class="form-group col-md-4 col-xs-12">
                                            <label>Cidade</label>
                                            <input type="text" class="form-control" name="addr_city" value="<?=$order['customer_address_city']?>" disabled>
                                        </div>
                                        <div class="form-group col-md-4 col-xs-12">
                                            <label>Estado (UF)</label>
                                            <input type="text" class="form-control" name="addr_uf" value="<?=$order['customer_address_uf']?>" disabled>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group col-md-12 col-xs-12 mb-0">
                                <h3 style="border-bottom: 1px solid #ddd">Selecione a empresa e loja para qual pretender transferir o pedido</h3>
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
                                <select name="store" class="form-control" readonly required></select>
                            </div>
                            <div class="col-md-12 form-group">
                                <div class="panel panel-primary">
                                    <div class="panel-heading">Grade de Produtos</div>
                                    <div class="panel-body">
                                        <h4 class="text-center">Serão atualizados apenas store_id, company_id, name, sku, product_id, variant e skumkt do produto. Valores não podem serem alterados.</h4>
                                        <br><br>
                                        <div class="col-md-12 col-xs-12 form-group">
                                            <table class="table" id="gridProducts">
                                                <thead>
                                                    <tr>
                                                        <th>Produto Atual</th>
                                                        <th>Produto do novo Seller</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                    foreach ($items as $iten) {
                                                        echo "
                                                        <tr>
                                                            <td>
                                                                <p><b>ID:</b> {$iten['id']}</p>
                                                                <p><b>SKU:</b> {$iten['sku']}</p>
                                                                <p><b>VARIAÇÃO:</b> {$iten['variation']}</p>
                                                                <p><b>Nome:</b> {$iten['name']}</p>
                                                            </td>
                                                            <td>
                                                                <div class='col-md-7 col-xs-12 form-group'>
                                                                    <label>Produtos</label>
                                                                    <select name='get_products[]' class='form-control' readonly></select>
                                                                </div>
                                                                <div class='col-md-5 col-xs-12 form-group'>
                                                                    <label>Variação</label>
                                                                    <select name='get_variant[]' class='form-control' readonly></select>
                                                                </div>
                                                                <input type='hidden' name='product_original[]' value='{$iten['id_item']}'>
                                                            </td>
                                                        </tr>
                                                        ";
                                                    }
                                                ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Valor Desconto</label>
                                <input type="text" class="form-control" value="<?=number_format($order['discount'], 2, ',', '.')?>" disabled>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Valor Produtos</label>
                                <input type="text" class="form-control" value="<?=number_format($order['total_order'], 2, ',', '.')?>" disabled>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Valor do Frete</label>
                                <input type="text" class="form-control" value="<?=number_format($order['total_ship'], 2, ',', '.')?>" disabled>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Valor Total Pedido</label>
                                <input type="text" class="form-control" value="<?=number_format($order['gross_amount'], 2, ',', '.')?>" disabled>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Valor Líquido</label>
                                <input type="text" class="form-control" value="<?=number_format($order['net_amount'], 2, ',', '.')?>" disabled>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Status</label>
                                <input type="text" class="form-control" value="<?=$this->lang->line('application_order_'.$order['paid_status'])?>" disabled>
                            </div>
                            <div class="row"></div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Data pagamento</label>
                                <input type="date" class="form-control" value="<?=empty($order['data_pago']) ? '' : date('Y-m-d', strtotime($order['data_pago']))?>" disabled>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Data para expedir</label>
                                <input type="date" class="form-control" value="<?=empty($order['data_limite_cross_docking']) ? '' : date('Y-m-d', strtotime($order['data_limite_cross_docking']))?>" disabled>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Data envio</label>
                                <input type="date" class="form-control" value="<?=empty($order['data_envio']) ? '' : date('Y-m-d', strtotime($order['data_envio']))?>" disabled>
                            </div>
                            <div class="col-md-3 col-xs-12 form-group">
                                <label>Data entrega</label>
                                <input type="date" class="form-control" value="<?=empty($order['data_entrega']) ? '' : date('Y-m-d', strtotime($order['data_entrega']))?>" disabled>
                            </div>
                            <div class="row"></div>
                            <div class="col-md-12 col-xs-12 form-group">
                                <label><input type="checkbox" name="updateStockOldsProducts"> Voltar o estoque dos produtos atual</label>
                            </div>
                            <div class="col-md-12 col-xs-12 form-group">
                                <label><input type="checkbox" name="updateStockNewsProducts" checked> Descontar estoque dos novos produtos</label>
                            </div>
                            <div class="col-md-12 col-xs-12 form-group">
                                <label><input type="checkbox" name="sendOrderToIntegration" checked> Enviar pedido para fila de integração da nova loja e remover pedido da fila da loja atual</label>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_order');?></button>
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

    $(document).ready(function() {
        $('[name="company"], [name="store"], [name="get_products[]"], [name="get_variant[]"]').select2();

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

        cleanProducts();
    });

    const getStores = company => {

        $('[name="store"]').select2('destroy').empty().prop('readonly', true);

        company = parseInt(company);
        if (company === 0) {
            $('[name="store"]').select2();
            return false;
        }

        cleanProducts();

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
            $('[name="store"]').prop('readonly', false);
        });
    }

    const cleanProducts = () => {

        let optionsPrd = '<option value="0">Selecione um produto</option>';
        let optionsVar = '<option value="">Selecione uma variação</option>';

        $('[name="get_products[]"]').empty().append(optionsPrd).select2().attr('readonly', true);
        $('[name="get_variant[]"]').empty().append(optionsVar).select2().attr('readonly', true);
    }

    const getProducts = store => {

        $('[name="get_products[]"]').select2('destroy').empty().prop('readonly', true);

        store = parseInt(store);
        if (store === 0) {
            $('[name="get_products[]"]').select2();
            return false;
        }

        cleanProducts();

        const url = "<?=base_url('products/getProductsAjaxByStore')?>";
        $.post( url, { store }, response => {
            let options = '<option value="0">Selecione um produto</option>';
            $.each(response, function( index, value ) {
                options += `<option value="${value.id}">${value.sku} - ${value.name}</option>`;
            });

            $('[name="get_products[]"]').empty().append(options).select2();
        }, "json").fail(e => {
            console.log(e)
        }).always(function() {
            $('[name="get_products[]"]').prop('readonly', false);
        });
    }

    const sleep = ms => {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    const getVariants = async product => {
        dataProduct = [];

        await sleep(10);

        $('[name="get_variant[]"], [name="get_products[]"]').select2('destroy');
        $('[name="get_variant[]"]').empty().prop('readonly', true);

        product = parseInt(product);
        if (product === 0) {
            $('[name="get_variant[]"], [name="get_products[]"]').select2();
            return false;
        }

        const url = "<?=base_url('products/getVariantAjaxByProduct')?>";
        $.post( url, { product }, response => {

            console.log(response);

            let options = '<option value="">Selecione uma variação</option>';

            dataProduct = response.product;

            if (response.var.length !== 0) {
                $.each(response.var, function (index, value) {
                    options += `<option value="${value.variant}">${value.sku} | ${value.name.replaceAll(';', ' - ')}</option>`;
                });

            } else {
                options = '<option value="">Produto sem variação</option>';
            }

            $('[name="get_variant[]"]').prop('readonly', response.var.length === 0).append(options).prop('disabled', response.var.length === 0).select2();
            $('[name="get_products[]"]').select2();
        }, "json").fail(e => {
            console.log(e)
        });
    }

    const finishOrder = async () => {
        let id, variant;
        let stopApp = false;
        $('[name="product_id[]"], [name="product_var[]"]').remove();
        await $('#gridProducts tbody tr').each(function() {
            id = $(this).find('td:eq(1) [name="get_products[]"]').val();
            variant = $(this).find('td:eq(1) [name="get_variant[]"]').val();

            if (id == '' || id == 0) {
                alert('selecione todos os produtos para troca dos itens');
                stopApp = true;
            }

            $('#formCreateOrder').append(`
                <input type="hidden" name="product_id[]" value="${id}">
                <input type="hidden" name="product_var[]" value="${variant}">
            `);
        });

        if (stopApp) return true;

        return false;
    }

    $(document).on('submit', '#formCreateOrder', async function (e){

        const btnSubmit = $(this).find('button[type="submit"]');
        btnSubmit.prop('disabled', true);

        let stopApp = await finishOrder();

        if (stopApp) {
            btnSubmit.prop('disabled', false);
            e.preventDefault(e);
            return false;
        }
        await sleep(1000);
    });

    $('[name="company"]').change(function(){
        getStores($(this).val());
    });

    $('[name="store"]').change(function(){
        getProducts($(this).val());
    });

    $('[name="get_products[]"]').change(function(){
        getVariants($(this).val());
    });

</script>