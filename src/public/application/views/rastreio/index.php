<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="keywords" content="página de rastreio, rastreio de pedido, rastrear pedido,"/>
    <meta name="description" content="Com a página de rastreio do Conecta Lá ficou muito mais rápido e prático rastrear seus pedidos!">

    <title>Rastreio &mdash; Conecta Lá</title>

    <!-- Plugins CSS -->
    <link href="<?= base_url('assets/tracking/css/plugins.css') ?>" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= $stylesheet ?>" rel="stylesheet">

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('assets/tracking/images/favicon.png') ?>">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body onload="agreementPopup();">
    <!-- Pre Loader -->
    <div id="dvLoading"></div>

    <!-- Agreement -->
    <div id="dvAgreement" style="display: none;" onclick="closeAgreement('outside');">
        <div class="dvAgreementContainer">
            <div class="dvAgreementMsg" onclick="closeAgreement('inside');">
                Em cumprimento com as determinações legais, a Conecta Lá informa que apenas com o seu consentimento expresso irá utilizar o seu e-mail para criação de uma base de dados e, oportunamente, utilizar as suas informações para envio de e-mails de marketing. Caso você entenda e concorde com os <a href="<?= base_url('assets/tracking/pdf/LGPD.pdf') ?>" style="text-decoration: underline;" target="blank">Termos de Uso e Política de Privacidade</a>, favor clicar em "Concordo com os Termos", os quais são obrigatórios para a utilização da Plataforma. Para o tratamento dos seus dados para o envio de promoções, favor clique em "Aceito receber materiais promocionais"; caso não esteja de acordo, não clique no item.<br/>&nbsp;<br/>

                <form id="agreement_form" name="agreement_form">
                    <input type="checkbox" id="agreement" name="agreement" checked>
                    <label for="agreement">Concordo com os termos.</label><br/>
                    <input type="checkbox" id="advertisement" name="advertisement" checked>
                    <label for="advertisement">Aceito receber materiais promocionais.</label><br/>

                    <button type="submit" class="btn btn-primary" onclick="closeAgreement('button');">Fechar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Navigation Start -->
    <nav id="navbar" class="navbar fixed-top navbar-expand-lg navbar-header navbar-mobile" style="padding: 0;">
        <div class="navbar-container container-fluid" style="padding: 0;">

            <?php
                if (!$conecta_style) {
                    echo '
                    <div class="navbar-brand">
                        <a class="navbar-brand-logo" href="#top">
                            <img src="' . base_url("assets/tracking/images/logo.png") . '" alt="logo">
                        </a>
                    </div>';
                }
            ?>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>

            <div class="collapse navbar-collapse justify-content-around" id="navbarNav">
                <?php
                    if (!$conecta_style) {
                        echo '
                        <ul class="navbar-nav menu-navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="#top">In&iacute;cio</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#tracker">Rastreio</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#services">FAQ?</a>
                            </li>
                        </ul>

                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link learn-more-btn" href="#contact">Contato</a>
                            </li>
                        </ul>';
                    } else {
                        if ($top_selected == 'basic') {
                            echo '<div style="background-color: ' . $top_basic_back_color . '; color: ' . $top_basic_text_color . '; text-align: center; width: 100%; padding: 35px 0px; margin: 0px; font-size: 45px;">Rastreie seu pedido</div>';
                        } else if ($top_selected == 'image') {
                            echo '<div style="width: 100%; height: 145px; display: block; margin: 0px; background: url(\'./assets/files/sellercenter/' . $top_image_name . '\') no-repeat; background-size: cover;"></div>';
                        }
                    }
                ?>
            </div>
        </div>
    </nav>
    <!-- Navigation End -->

    <!-- Banner Start -->
    <div id="top" class="header">
        <div class="container header-container">
            <?php
                if (!$conecta_style) {
                    echo '
                    <div class="d-none d-lg-block col-lg-6  header-img-section">
                        <img src="' . base_url("assets/tracking/images/banner.png") . '" class="img-fluid" alt="banner">
                    </div>

                    <div class="col-lg-5 offset-lg-1 col-sm-12 header-title-section">
                        <p class="header-subtitle">Você está na página de rastreio da Conecta Lá</p>
                        <h1 class="header-title">Veja como é fácil e rápido rastrear seus pedidos!</h1>
                        <p class="header-title-text">Para rastrear seus pedidos, informe seu endereço de e-mail e o CPF/CNPJ do comprador.</p>

                        <div class="learn-more-btn-section">
                            <a class="nav-link learn-more-btn btn-invert" href="#tracker">Rastrear meu pedido</a>
                        </div>
                    </div>';
                }
            ?>
        </div>
    </div>
    <!-- Banner End -->

    <!-- Tracker Start -->
    <div id="tracker"></div>
    <!-- section class="tracker-section" -->
    <section>
        <div class="container tracker-container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="tracker-form" id="tracker_form" <?php if ($conecta_style) echo 'style="background-color: ' . $middle_back_color . ';"'; ?>>
                        <h3 class="tracker-heading" <?php if ($conecta_style) echo 'style="color: ' . $middle_text_color . ';"'; ?>>Informe o CPF/CNPJ do comprador para rastrear o pedido:</h3>
                        <form class="form-inline" id="order_form" name="order_form">
                            <?php
                                if (!$conecta_style) {
                                    echo '
                                    <div class="form-group">
                                        <label for="orderEmail" class="sr-only">E-mail</label>
                                        <input type="email" class="form-control" id="order_email" placeholder="E-mail" id="orderEmail">
                                    </div>';
                                }
                            ?>

                            <div class="form-group">
                                <label for="tracking_code" class="sr-only">Password</label>
                                <input type="text" class="form-control" id="tracking_code" placeholder="CPF/CNPJ" required>
                            </div>

                            <div class="learn-more-btn-section">
                                <a class="nav-link btn btn-primary" href="#tracker" 
                                <?php if ($conecta_style) echo 'style="background-color: ' . $middle_button_back_color . '; color: ' . $middle_button_text_color . ';"'; ?>
                                id="consultaPedidos" onclick="consultaPedidos();">Rastrear</a>
                            </div>
                        </form>
                        <h4 class="tracker-error" id="tracker_error" style="display: none;">Rastreie o seu pedido</h4>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div id="tracking">
                        <div class="tracking-list">
                            <div id="order_tracking"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
    <!-- Tracker End -->

    <?php
        if (!$conecta_style) {
            echo '
            <!-- services Start -->
            <section id="services" class="services-section">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <h2 class="section-heading">NA HORA DE RECEBER SEU PEDIDO:</h2>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-7 col-sm-12">
                            <ul class="service-list">
                                <li><i class="fas fa-check-circle"></i> Confira se a embalagem está em perfeitas condições.</li>
                                <li><i class="fas fa-check-circle"></i> Abra sua compra na presença do entregador e verifique se o produto está em conformidade com o que você adquiriu.</li>
                                <li><i class="fas fa-check-circle"></i> Na compra de eletrônicos, confira na embalagem se a voltagem está correta.</li>
                                <li><i class="fas fa-check-circle"></i> Na compra de móveis, esteja atento à quantidade de volumes recebidos e se a cor está de acordo com o pedido.</li>
                                <li><i class="fas fa-check-circle"></i> Caso seu produto apresente avarias ou não esteja de acordo com o pedido, recuse o recebimento e assinale no verso da nota fiscal ou do comprovante de entrega os motivos da recusa.</li>
                                <li><i class="fas fa-check-circle"></i> Não esqueça de avaliar sua experiência de compra.</li>
                            </ul>
                        </div>

                        <div class="d-none d-lg-block col-lg-5">
                            <img src="' . base_url("assets/tracking/images/service.png") . '" class="img-fluid" alt="service">
                        </div>
                    </div>
                </div>
            </section>
            <!-- services End -->

            <!-- contact Start -->
            <section id="contact" class="contact-section">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <h2 class="section-heading">Algum problema em rastrear seu pedido?</h2>
                            <p>Entre em contato conosco por e-mail e responderemos o mais rápido possível.</p>
                        </div>
                    </div>

                    <div id="contact_form"></div>
                    <div class="row">
                        <div class="col-lg-6 d-none d-lg-block">
                            <img src="' . base_url("assets/tracking/images/contact.png") . '" class="img-fluid" alt="blog">
                        </div>

                        <div class="col-lg-6">
                            <form class="contact-form" id="contact-form">
                                <div class="contact-form-head">
                                    <h3>Enviar mensagem</h3>
                                </div>

                                <div class="contact-form-inner">
                                    <h4 class="contact-form-error" id="contact_form_error" style="display: none;">Rastreie o seu pedido</h4><br/>

                                    <div class="form-group">
                                        <input type="text" class="form-control" id="contact_order_number" name="contact_order_number" placeholder="Número do pedido" onkeyup="searchOrderNumber();">
                                    </div>

                                    <div class="form-group">
                                        <input type="text" class="form-control" id="contact_name" name="contact_name" placeholder="Nome" disabled>
                                    </div>

                                    <div class="form-group">
                                        <input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="E-mail" disabled>
                                    </div>

                                    <div class="form-group">
                                        <input type="phone" class="form-control" id="contact_phone" name="contact_phone" placeholder="Telefone" disabled>
                                    </div>

                                    <div class="form-group">
                                        <textarea class="form-control" id="contact_message" name="contact_message" placeholder="Escreva sua mensagem" disabled></textarea>
                                    </div>

                                    <div class="learn-more-btn-section">
                                        <a class="nav-link btn btn-primary" href="#contact_form" id="problemaPedidos">Enviar</a>
                                    </div>

                                    <div class="alert d-none alert-success mt-3 mb-0"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
            <!-- contact End -->';
        }
    ?>

    <!-- footer Start -->
    <section class="footer" <?php if ($conecta_style) echo 'style="background-color: ' . $bottom_basic_back_color . ';"'; ?>>
        <div class="container">
            <div class="row">
                <?php
                    if (!$conecta_style) {
                        echo '
                        <div class="col-lg-3 col-md-6">
                            <div class="footer-widget">
                                <img src="' . base_url("assets/tracking/images/logo-white.png") . '" alt="logo" />
                                <p class="mt-4">
                                    Oferecemos a solução mais completa do mercado para quem deseja vender em marketplace, ou então para quem quer transformar seu negócio em um marketplace. É mais tecnologia para vender e mais visão estratégica para tomar decisões. 
                                </p>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <div class="footer-widget">
                                <h5>Acesso rápido</h5>
                                <ul>
                                    <li><a href="https://site.conectala.com.br/"><i class="fas fa-angle-right"></i> Institucional</a></li>
                                    <li><a href="https://www.conectala.com.br/app/"><i class="fas fa-angle-right"></i> Sistema</a></li>
                                    <li><a href="https://site.conectala.com.br/sobre/"><i class="fas fa-angle-right"></i> Sobre</a></li>
                                    <li><a href="https://site.conectala.com.br/contato/"><i class="fas fa-angle-right"></i> Contato</a></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <div class="footer-widget">
                                <h5><b>SAC</b></h5>

                                <p class="mt-3">Em caso de dúvidas ou problemas com o seu pedido, você pode nos contatar pelo <b>e-mail atendimento@conectala.com.br</b> ou então pelo <b>telefone (48) 3197-1994</b>.</p>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <div class="footer-widget">
                                <h5>Fique conectado</h5>
                                <div class="social-icons">
                                    <a href="https://www.facebook.com/conectalaa" target="_blank"><i class="fab fa-facebook"></i></a>
                                    <a href="https://www.instagram.com/conecta_la/" target="_blank"><i class="fab fa-instagram"></i></a>
                                    <a href="https://www.linkedin.com/company/conecta-la" target="_blank"><i class="fab fa-linkedin"></i></a>
                                    <a href="https://www.youtube.com/channel/UC4CFf6T4s971Hcw0KHWTs4w" target="_blank"><i class="fab fa-youtube"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">';
                    }
                ?>

                <div class="col-lg-12">
                    <div class="footer-copyright" <?php if ($conecta_style) echo 'style="color: ' . $bottom_basic_text_color . ';"'; ?> id="footer_content">
                        © <?php 
                        if (!$conecta_style) {
                            echo date('Y') . " Conecta Lá &mdash; Todos os direitos reservados.";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- footer End -->

    <?php 
    if ($conecta_style) {
        echo '<input type="hidden" id="content" name="content" value="' . $bottom_html_content . '">
        <input type="hidden" id="prov_seller" value="prov_seller_o">';
    } else {
        echo '<input type="hidden" id="prov_seller" value="prov_seller_i">';
    }
    ?>

    <!-- jQuery Min JS -->
    <script src="<?= base_url('assets/tracking/js/jquery-min.js') ?>"></script>

    <!-- Popper Min JS -->
    <script src="<?= base_url('assets/tracking/js/popper.min.js') ?>"></script>
    <!-- Bootstrap Min JS -->
    <script src="<?= base_url('assets/tracking/js/bootstrap.min.js') ?>"></script>
    <!-- Owl Carousel Min JS -->
    <script src="<?= base_url('assets/tracking/js/owl.carousel.min.js') ?>"></script>
    <!-- Owl Plugins JS -->
    <script src="<?= base_url('assets/tracking/js/plugins.js') ?>"></script>
    <!-- Smooth scroll JS -->
    <script src="<?= base_url('assets/tracking/js/smoothscroll.js') ?>"></script>
    <!-- Inputmask -->
    <script src="<?= base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

    <script type="text/javascript">
        let fc = document.getElementById('content');
        if (fc) {
            document.getElementById('footer_content').innerHTML = fc.value;
        }
    </script>

    <!-- Custom JS -->
    <script src="<?= base_url('assets/tracking/js/custom.js') ?>"></script>
    <!-- Order Tracking JS -->
    <script src="<?= base_url('assets/tracking/js/order_tracking.js') ?>"></script>
</body>

</html>
