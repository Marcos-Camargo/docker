<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.3.2/css/bootstrap3/bootstrap-switch.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo base_url('assets/plugins/iCheck/all.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/dist/css/styles.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.0.17/sweetalert2.min.css" integrity="sha512-fZ1HwrDVLoUUUDGK7gZdHJ4TIMQ9KnleLU/Jgf98v1nGz9umOciIbF3zs3R5stCIY/MVMqReXgUGnxOoWUdZDQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <form id="form_configsave" action="<?= base_url('AnyMarket/configsave'); ?>" method="POST">
        <div class="tab-content">
            <ul class="nav nav-tabs">
                <li class="active nav-item">
                    <a class="nav-link" data-toggle="tab" href="#home" id="home_config">Autenticação</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#menu1">Preço</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#menu2">Vendas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#menu3">Anúncios</a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link disabled" href="#">Disabled</a>
                </li>-->
            </ul>
            <div id="home" class="tab-pane fade show active">
                <div class="container">
                    <h3>Configuração de parâmetros do sistema</h3>
                    <input type="hidden" class="form-control" id="input_oi" name="oi" aria-describedby="oi_help" placeholder="Digite o O.I." value="<?= $oi ?>">
                    <input type="hidden" class="form-control" id="input_oi" name="token" aria-describedby="oi_help" placeholder="Digite o O.I." value="<?= $token ?>">
                    <input type="hidden" class="form-control" id="input_oi" name="token2" aria-describedby="oi_help" placeholder="Digite o O.I." value="<?= $token ?>">
                    <div class="form-group">
                        <label for="input_oi">Login/E-mail <?= $sellercenter_name ?></label>
                        <input type="text" class="form-control" id="input_oi" name="login" aria-describedby="oi_help" placeholder="Digite seu usuario ou email de acesso <?= $sellercenter_name ?>" value="<?= isset($email) ? $email : '' ?>" <?= isset($email) ? '' : '' ?>>
                        <small id="oi_help" class="form-text text-muted">Digite o e-mail ou nome de usuário <?= $sellercenter_name ?></small>
                    </div>
                    <div class="form-group">
                        <label for="input_senha_conectala">Token de acesso da loja no <?= $sellercenter_name ?></label>
                        <input type="text" class="form-control" id="input_senha_conectala" aria-describedby="api_token_help" name="token" placeholder="Digite o token de acesso <?= $sellercenter_name ?>" value="<?= isset($token_api) ? $token_api : '' ?>" <?= isset($token_api) ? '' : '' ?>>
                        <small id="api_token_help" class="form-text text-muted">Digite o token de acesso <?= $sellercenter_name ?></small>
                    </div>
                    <div class="form-group">
                        <label for="input_api_key">Nome/ID da loja</label>
                        <input type="text" class="form-control" id="input_name_id_loja" name="store" aria-describedby="oi_help" placeholder="Digite o nome da loja ou id da mesma na <?= $sellercenter_name ?>" value="<?= isset($store) ? $store : '' ?>" <?= isset($store) ? '' : '' ?>>
                        <small id="oi_help" class="form-text text-muted">Digite o nome da loja ou id da mesma na <?= $sellercenter_name ?></small>
                    </div>
                    <div class="form-group">
                        <label for="token_anymarket">Token ANYMARKET</label>
                        <input type="text" class="form-control" id="input_name_id_loja" name="token_anymarket" aria-describedby="oi_help" placeholder="Digite a token para a <?= $sellercenter_name ?> se conectar a ANYMARKET" value="<?= isset($token_anymerket) ? $token_anymerket : '' ?>">
                        <small id="oi_help" class="form-text text-muted">Digite o token de integração ANYMARKET</small>
                    </div>
                </div>
            </div>
            <div id="menu1" class="tab-pane fade">
                <div class="container">
                    <h3>Configuração de preço</h3>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-lg-12">
                                <label for="priceFactor">Fator de Precificação</label>
                                <div class="input-group">
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="priceFactor"
                                        name="priceFactor"
                                        aria-describedby="priceFactor_help"
                                        placeholder="Digite o fator de precificação para este marketplace"
                                        value="<?= $priceFactor ?>"
                                        data-original-value="<?= isset($priceFactor) ? $priceFactor : '0' ?>"
                                        required
                                    >
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button" id="button_refresh"><i class="fa fa-refresh"></i></button>
                                    </span>
                                </div>
                                <small id="priceFactor_help" class="form-text text-muted">Digite o fator de precificação para este marketplace</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="defaultDiscountValue">Desconto padrão</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="defaultDiscountValue" 
                            name="defaultDiscountValue" 
                            aria-describedby="defaultDiscountValue_help" 
                            placeholder="Digite o Desconto padrão a ser aplicado neste marketplace" 
                            value="<?= $defaultDiscountValue?>" 
                            required>
                        <small id="defaultDiscountValue_help" class="form-text text-muted">Digite o desconto padrão a ser aplicado neste marketplace</small>
                    </div>
                    <div class="form-group">
                        <label for="defaultDiscountType">Tipo de desconto</label>
                        <select class="form-control" name="defaultDiscountType" id="defaultDiscountType" aria-describedby="defaultDiscountType_help">
                            <option value="" <?= $defaultDiscountType == '' ? 'selected' : '' ?> disabled>Selecione o tipo de porcentagem para cadastro de produto</option>
                            <option value="VALUE" <?= isset($defaultDiscountType) ? ($defaultDiscountType == 'VALUE' ? 'selected' : '') : '' ?>>Valor</option>
                            <option value="PERCENT" <?= isset($defaultDiscountType) ? ($defaultDiscountType == 'PERCENT' ? 'selected' : '') : 'selected' ?>>Porcentagem</option>
                        </select>
                        <small id="defaultDiscountType_help" class="form-text text-muted">Selecione o tipo de porcentagem para cadastro de produto</small>
                    </div>
                </div>
            </div>
            <div id="menu2" class="tab-pane fade">
                <div class="container">
                    <h3>Configuração de vendas</h3>
                    <div class="container">
                        <div class="form-group">
                            <label for="inicial_date_order">Data inicial de vendas</label>
                            <input 
                                type="datetime-local" 
                                class="form-control" 
                                id="inicial_date_order" 
                                name="inicial_date_order" 
                                aria-describedby="inicial_date_order_help" 
                                placeholder="Importar pedidos em andamento a partir de:" 
                                value="<?= $inicial_date_order?>">
                            <small id="inicial_date_order_help" class="form-text text-muted">Serão importados somente pedidos cuja data seja igual ou superior à data configurada. Não serão importados pedidos que sua data de criação seja anterior da data informada.</small>
                            <button class="btn" id="button_refresh_order_force"><i class="fa fa-refresh"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="menu3" class="tab-pane fade">
                <div class="container">
                    <h3>Configuração Anúncio</h3>
                    <div class="container">
                        <div class="form-group">
                            <div class="form-check form-switch">
                                <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="updateProductPriceStock"
                                        name="updateProductPriceStock"
                                        value="1"
                                    <?= isset($updateProductPriceStock) && $updateProductPriceStock ? 'checked' : '' ?>
                                >
                                <label class="form-check-label" for="updateProductPriceStock">Atualizar somente preço e estoque.</label>
                                <small class="form-text text-muted">
                                    Em caso de produtos publicados, serão atualizados somente preço, estoque e status do produto.
                                </small>
                            </div>
                        </div>
                        <div class="form-group" style="<?= isset($updateProductPriceStock) && $updateProductPriceStock ? '' : 'display: none;' ?>">
                            <div class="form-check form-switch">
                                <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="updateProductCrossdocking"
                                        name="updateProductCrossdocking"
                                        value="1"
                                    <?= isset($updateProductCrossdocking) && $updateProductCrossdocking ? 'checked' : '' ?>
                                >
                                <label class="form-check-label" for="updateProductCrossdocking">Atualizar Prazo
                                    Operacional (crossdocking)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="fixed-bottom" style="margin-top: 3rem;">
                <?php if ($sellercenter_name != "Conecta Lá") : ?>
                    <small class="form-text text-muted">Powered by Conecta lá</small>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" id="save_button" style="margin: 1rem;position: absolute;bottom: 0;right: 0;margin-right:103px">Salvar</button>
            </footer>
        </div>
    </form>

    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script type='text/javascript' src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/bower_components/sweetalert/dist/sweetalert2.all.min.js') ?>"></script>
    <script>

        $('document').ready(function() {

            $('#priceFactor').on('keyup', function () {
                var originalValue = $(this).data('originalValue');
                if (originalValue != $(this).val()) {
                    $('#button_refresh').prop('disabled', true);
                } else {
                    $('#button_refresh').prop('disabled', false);
                }
            });

            $("#updateProductPriceStock").on('change', function () {
                if ($("#updateProductPriceStock:checked").length > 0) {
                    $("#updateProductCrossdocking").parent().parent().show();
                } else {
                    $("#updateProductCrossdocking").parent().parent().hide();
                }
            });

            let send_request_to_refresh = function() {
                let dataform = getFormData('form_configsave');
                $.ajax({
                    url: "<?= base_url('AnyMarket/configpricerefresh'); ?>",
                    type: 'POST',
                    data: dataform,
                    success: function(data) {
                        $("button").prop('disabled', false)
                        let response = JSON.parse(data);
                        console.log(response);
                        if (response.httpcode == 200) {
                            Swal.fire({
                                icon: 'success',
                                title: "Sucesso na atualização",
                                showCancelButton: false,
                                confirmButtonText: "Ok",
                            }).then((result) => {});
                        } else {
                            if (response.content.length > 0) {
                                response = JSON.parse(response.content);
                                var details = response.error ?? response.message;
                                Swal.fire({
                                    icon: 'error',
                                    title: details,
                                    showCancelButton: false,
                                    confirmButtonText: "Ok",
                                }).then((result) => {
                                });
                            }
                        }
                    },
                    error: function(jqXHR, textStatus, err) {}
                });
            }
            let send_request_to_refresh_force_order = function() {
                let dataform = getFormData('form_configsave');
                $.ajax({
                    url: "<?= base_url('AnyMarket/forcesyncorders'); ?>",
                    type: 'POST',
                    data: dataform,
                    success: function(data) {
                        $("button").prop('disabled', false)
                        let response = JSON.parse(data);
                        console.log(response);
                        if (response.sucess) {
                            Swal.fire({
                                icon: 'success',
                                title: response.message,
                                showCancelButton: false,
                                confirmButtonText: "Ok",
                            }).then((result) => {});
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: response.message,
                                showCancelButton: false,
                                confirmButtonText: "Ok",
                            }).then((result) => {});
                        }
                    },
                    error: function(jqXHR, textStatus, err) {}
                });
            }
            $('#button_refresh_order_force').click(function(event) {
                event.preventDefault();
                $("button").prop('disabled', true)
                Swal.fire({
                    icon: 'question',
                    title: "Deseja forçar o recebimento dos pedidos?",
                    showCancelButton: true,
                    confirmButtonText: "Sim",
                    cancelButtonText: "Não",
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        send_request_to_refresh_force_order();
                    }
                })
            });
            $('#button_refresh').click(function(event) {
                event.preventDefault();
                $("button").prop('disabled', true)
                Swal.fire({
                    icon: 'question',
                    title: "Deseja solicitar a atualização de preço em massa?",
                    showCancelButton: true,
                    confirmButtonText: "Sim",
                    cancelButtonText: "Não",
                    allowOutsideClick: false
                }).then((result) => {
                    console.log(result)
                    if (result.isConfirmed) {
                        send_request_to_refresh();
                    }
                })

            });

            function getFormData(form_id) {
                let inputs = $("#" + form_id + " input")
                let data = {};
                inputs.each((index, element) => {
                    data[element.name] = element.value
                });
                let defaultDiscountType = $("#defaultDiscountType")
                data['defaultDiscountType'] = defaultDiscountType.val()
                data['updateProductPriceStock'] = $("#updateProductPriceStock:checked").length;
                data['updateProductCrossdocking'] = $("#updateProductCrossdocking:checked").length;
                return data;
            };
            $('#priceFactor').inputmask('numeric', {
                radixPoint: ',',
                autoUnmask: true,
                rightAlign: false,
                unmaskAsNumber: true,
                removeMaskOnSubmit: true,
                digits: 6,
                max: 99.999999,
                min: 0.000001,
                require: true
            });
            $('#defaultDiscountValue').inputmask('numeric', {
                radixPoint: ',',
                autoUnmask: true,
                rightAlign: false,
                unmaskAsNumber: true,
                removeMaskOnSubmit: true,
                max: 99999999.9999,
                digits: 6,
                min: 0,
                require: true
            });
            $("form").submit(function(event) {
                event.preventDefault();
                let dataform = getFormData('form_configsave');
                $("button").prop('disabled', true)
                $.ajax({
                    url: "<?= base_url('AnyMarket/configsaverest'); ?>",
                    type: 'POST',
                    data: dataform,
                    success: function(data) {
                        $('#priceFactor').data('originalValue', $('#priceFactor').val());
                        data = JSON.parse(data);
                        $("button").prop('disabled', false)
                        if (data.sucess == 'true') {
                            Swal.fire({
                                icon: 'success',
                                title: data.Message,
                                showCancelButton: false,
                                confirmButtonText: "Ok",
                            }).then((result) => {});
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: data.Message,
                                showCancelButton: false,
                                confirmButtonText: "Ok",
                            }).then((result) => {});
                        }
                    },
                    error: function(jqXHR, textStatus, err) {
                        // alert('text status '+textStatus+', err '+err)
                    }
                });
            });
        });
    </script>
</body>

</html>