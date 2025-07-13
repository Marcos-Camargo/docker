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
</head>

<body>
    <form action="<?= base_url('AnyMarket/configsave'); ?>" method="POST">
        <div class="tab-content">
            <ul class="nav nav-tabs">
                <li class="active nav-item">
                    <a class="nav-link" data-toggle="tab" href="#home" id="home_config">Autenticação</a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#menu1">Link</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#menu2">Link</a>
                </li> -->
                <!-- <li class="nav-item">
                    <a class="nav-link disabled" href="#">Disabled</a>
                </li> -->
            </ul>
            <div id="home" class="tab-pane fade in active">
                <?php if (isset($info)) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?= $info['msg'] ?>
                    </div>
                <?php endif; ?>
                <?php if ($this->session->flashdata('success') || $this->session->flashdata('error')) : ?>
                    <div class="alert alert-<?= $this->session->flashdata('success') ? 'success' : 'danger' ?>" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?= $this->session->flashdata('success') ?? $this->session->flashdata('error') ?>
                    </div>
                <?php endif ?>
                <h3>Login e cadastramento <?= $sellercenter_name ?></h3>
                <div class="container">
                    <input type="hidden" class="form-control" id="input_oi" name="oi" aria-describedby="oi_help" placeholder="Digite o O.I." value="<?= $oi ?>">
                    <input type="hidden" class="form-control" id="input_oi" name="token" aria-describedby="oi_help" placeholder="Digite o O.I." value="<?= $token ?>">
                    <div class="form-group">
                        <label for="input_oi">Login/E-mail <?= $sellercenter_name ?></label>
                        <input type="text" class="form-control" id="input_oi" name="login" value="<?=set_value('login', $this->session->flashdata('data')['login'] ?? '')?>" aria-describedby="oi_help" placeholder="Digite seu usuario ou email de acesso <?= $sellercenter_name ?>">
                        <small id="oi_help" class="form-text text-muted">Digite o e-mail ou nome de usuário <?= $sellercenter_name ?></small>
                    </div>
                    <div class="form-group">
                        <label for="input_senha_conectala">Token de acesso da loja no <?= $sellercenter_name ?></label>
                        <input type="text" class="form-control" id="input_senha_conectala" aria-describedby="api_token_help" name="token_in" value="<?=set_value('token_in', $this->session->flashdata('data')['token_in'] ?? '')?>" placeholder="Digite o token de acesso <?= $sellercenter_name ?>">
                        <small id="api_token_help" class="form-text text-muted">Digite o token de acesso <?= $sellercenter_name ?></small>
                    </div>
                    <div class="form-group">
                        <label for="input_api_key">Nome/ID da loja</label>
                        <input type="text" class="form-control" id="input_name_id_loja" name="store" value="<?=set_value('store', $this->session->flashdata('data')['store'] ?? '')?>" aria-describedby="oi_help" placeholder="Digite o nome da loja ou id da mesma na <?= $sellercenter_name ?>">
                        <small id="oi_help" class="form-text text-muted">Digite o nome da loja ou id da mesma na <?= $sellercenter_name ?></small>
                    </div>
                </div>
            </div>
            <div id="menu1" class="tab-pane fade">
                <h3>Configuração de preço</h3>
            </div>
            <div id="menu2" class="tab-pane fade">
                <h3>Menu 2</h3>
                <p>Some content in menu 2.</p>
            </div>
            <footer class="fixed-bottom" style="margin-top: 3rem;">
                <?php if ($sellercenter_name!="Conecta Lá") : ?>
                    <small class="form-text text-muted">Powered by Conecta lá</small>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" id="save_button" style="margin: 1rem;position: absolute;bottom: 0;right: 0;margin-right:103px">Salvar</button>
            </footer>
        </div>
    </form>
    <script>
        $('document').ready(function() {
            console.log('home_config');
            $('#home_config').click();
        });
    </script>
</body>

</html>