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
                        <h3 class="font-weight-bold"><?= $environment['name'] ?> está autorizada a integrar com Tray</h3>
                    </div>
                    <div class="row d-flex justify-content-center">
                        <h5>A integração com Tray foi configurada com sucesso para a loja
                            <b><?= $data['store_name'] ?></b></h5>
                    </div>
                    <div class="row d-flex justify-content-center">
                        <h5>Agora você está habilitado a importar produtos, criar pedidos de venda e fazer cotações de frete</h5>
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

