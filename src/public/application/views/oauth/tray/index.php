<!DOCTYPE html>
<html style="overflow:hidden; height: 100%" lang="pt" dir="ltr">
<head>
    <link rel="icon" href="<?=base_url("assets/skins/{$environment['sellercenter']}/favicon.ico")?>" type="image/gif">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=2,shrink-to-fit=no"/>
    <title><?= $page_title; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= base_url("assets/dist/css/styles_{$environment['style']}.css") ?>">
    <link rel="stylesheet" href="<?= base_url("assets/dist/css/styles{$environment['style']}.css") ?>">
</head>
<body style="height: 100%">
<div class="container" style="height: 100%">
    <div class="row">

    </div>
    <div class="row justify-content-center align-items-center" style="min-height: 100%">
        <div class="col">
            <div class="row">
                <div class="col">
                    <div class="row">
                        <div class="col">
                            <div class="d-flex justify-content-center">
                                <img src="<?= $environment['logoUrl'] ?>" alt="<?= $environment['name'] ?>"
                                     title="<?= $environment['name'] ?>" class="rounded">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" style="padding: 40px 0">
                <div class="col">
                    <div class="row d-flex justify-content-center" style="padding: 0px 0px 10px 0">
                        <h3 class="font-weight-bold"><?= $environment['name'] ?> está solicitando acesso a conta Tray</h3>
                    </div>
                    <div class="row d-flex justify-content-center">
                        <h5>Acesso ao módulo de produtos, pedidos de venda e frete</h5>
                    </div>
                    <?php if (!empty($data['store_id'])) { ?>
                        <div class="row d-flex justify-content-center">
                            <input type="hidden" id="store_id" name="store_id" value="<?= $data['store_id'] ?>">
                            <h5>Deseja realizar a integração da loja <b><?= $data['store_name'] ?></b> com a Tray?</h5>
                        </div>
                    <?php } elseif (!empty($stores)) { ?>
                        <div class="row d-flex justify-content-center" style="margin-top: 10px;">
                            <div class="col-10">
                                <div class="row">
                                    <select class="form-control form-control-lg" id="store_id" name="store_id">
                                        <option value="0">Selecione uma loja para integrar...</option>
                                        <?php foreach ($stores as $store) { ?>
                                            <optgroup label="<?= $store['company_name'] ?>">
                                                <option value="<?= $store['id'] ?>"><?= "{$store['id']} - {$store['name']}" ?></option>
                                            </optgroup>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="row d-flex justify-content-center" style="margin-top: 10px;">
                            <input type="hidden" id="store_id" name="store_id" value="0">
                            <h6>O usuário <b><?= strtoupper($user['firstname']) ?></b> não está vinculado a nenhuma loja e também não há lojas disponíveis para integração.
                                </br><span class="d-flex justify-content-center">Cadastre uma nova loja ou remova a integração com alguma loja já existente.</span>
                            </h6>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="row">
                        <div class="col">
                            <button type="button" id="btn_confirm" class="btn btn-primary btn-lg btn-block">Continuar
                                com o usuário <b><?= strtoupper($user['firstname']) ?></b></button>
                            <button type="button" id="btn_cancel" class="btn btn-light btn-lg btn-block font-weight-bold">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">

    </div>
</div>
</body>
</html>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script>
    var baseUrl = '<?=base_url();?>';
    var authUrl = '<?=$authUrl;?>';
    $(document).ready(function () {
        var btnConfirmBG = $('#btn_confirm').css('background-color');
        var borderConfirmBG = $('#btn_confirm').css('border-color');
        $('input,button,select').focus(function () {
            $(this).css({'border-color': btnConfirmBG, 'box-shadow': '0 0 0 0.2rem ' + btnConfirmBG.replace(/\,/g, '').replace(')', ' / 25%)')});
        });
        $('input,button,select').blur(function () {
            $(this).removeAttr('style');
        });
        $('#btn_confirm').hover(function () {
            $(this).css({"background-color": btnConfirmBG, "border-color": "#0000", "filter": "brightness(.75)"});
        }, function () {
            $(this).css({"background-color": btnConfirmBG, "border-color": borderConfirmBG, "filter": "brightness(1)"});
        });

        checkStoreId();
        $('#store_id').change(function () {
            checkStoreId();
        });

        $('#btn_cancel').click(function () {
            window.location.href = baseUrl + "/auth/logout";
        });
        $('#btn_confirm').click(function () {
            var storeId = $('#store_id').val();
            if (authUrl.length > 0 && storeId > 0) {
                window.location.href = authUrl + "&integration_store_id=" + storeId;
            }
        });
    });

    function checkStoreId() {
        var btnConfirmBG = $('#btn_confirm').css('background-color');
        if ($("#store_id").val() == 0) {
            $('#btn_confirm').prop('disabled', true).css({
                "background-color": btnConfirmBG,
                "border-color": "#0000",
                "filter": "brightness(1)"
            });
            return;
        }
        $('#btn_confirm').prop('disabled', false);
    }
</script>

