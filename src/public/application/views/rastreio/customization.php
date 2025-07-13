<!--
Personalização da tela de rastreio de pedidos dos seller centers.
-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <style>
        .normal {
            font-weight: normal;
        }
        
        .bootstrap-select .dropdown-toggle .filter-option {
            background-color: white !important;
        }

        .bootstrap-select .dropdown-menu li a {
            border: 1px solid gray;
        }

        form li, div > p {
            display: none;
        }
    </style>

    <!-- Custom CSS -->
    <link href="<?= base_url('assets/tracking/css/style-custom.css') ?>" rel="stylesheet">

    <?php 
    $data['pageinfo'] = "application_tracking_custom_short";
    $this->load->view('templates/content_header', $data);

    $tracking_url = 'http://localhost/fase1/rastreio';
    if (strpos(base_url(), 'localhost') === false) {
        $tracking_url = base_url("rastreio");
    }
    ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div id="messages"></div>

                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <div style="padding-top: 10px; padding-bottom: 10px;">
                    <b>Link da tela de rastreio (URL)</b>
                </div>
                <div style="padding-top: 10px; padding-bottom: 10px;">
                    <b style="color: black;" id="tracking_url" name="tracking_url"><?php echo $tracking_url; ?></b> <img src="<?= base_url('assets/tracking/images/copy-files.svg') ?>" onclick="copyToClipboard();" style="height: 11px; color: blue; padding-left: 5px;" data-toggle="tooltip" data-placement="right" title="Ao clicar aqui, o endereço é copiado para a área de transferência.">
                    <input type="checkbox" name="send_tracking_url" id="send_tracking_url" style="margin-left: 20px;" onclick="toggleSendTrackingCode();"> Ativar e enviar</input> <img src="<?= base_url('assets/tracking/images/info_icon.svg') ?>" style="height: 15px; color: blue; padding-left: 5px;" data-toggle="tooltip" data-placement="right" title="Ao ativar este campo, a página de rastreio será enviada automaticamente para o Marketplace para que o consumidor possa rastrear seus pedidos.">
                </div>
            </div>

            <div class="col-md-6 col-xs-6">
                <div class="box box-primary">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div style="padding-bottom: 15px; font-size: 25px;" class="col-md-12"><b>Topo</b></div>

                                <div class="col-md-12" style="padding-bottom: 15px;">
                                    <input type="radio" id="top_basic" name="top_custom" value="top_basic" onclick="regionVisibility('top_basic_div', 'top_image_div');" checked>
                                    <label for="top_basic"> Versão básica</label>
                                    <input type="radio" id="top_image" name="top_custom" value="top_image" onclick="regionVisibility('top_image_div', 'top_basic_div');" style="margin-left: 25px;">
                                    <label for="top_image"> Versão com imagem</label>
                                </div>

                                <div style="display: block; padding-bottom: 15px;" id="top_basic_div" class="col-md-12">
                                    <div class="col-md-6" style="padding-left: 0px;">
                                        <label for="background_top">Cor do fundo</label>
                                        <div class="input-group">
                                            <span class="input-group-addon" id="background_top" style="padding: 6px;">
                                                <input type="color" value="#d3d3d3" id="background_top_color_input" name="background_top_color_input" style="height: 40px; width: 40px; padding: 0px; background-color: #ebebeb; border-color: #ebebeb;" onchange="changeColor(this, 'background', 'top')" oninput="changeColor(this, 'background', 'top')" onkeyup="changeColor(this, 'background', 'top')"></input>
                                            </span>

                                            <input type="text" value="#d3d3d3" id="background_top_input" onchange="changeColor(this, 'background', 'top')" oninput="changeColor(this, 'background', 'top')" onkeyup="changeColor(this, 'background', 'top')" class="form-control" placeholder="Código" style="height: 54px;">
                                        </div>
                                    </div>

                                    <div class="col-md-6" style="padding-left: 0px;">
                                        <label for="text_top">Cor do texto</label>
                                        <div class="input-group">
                                            <span class="input-group-addon" id="text_top" style="padding: 6px;">
                                                <input type="color" value="#777777" id="text_top_color_input" name="text_top_color_input" style="height: 40px; width: 40px; padding: 0px; background-color: #ebebeb; border-color: #ebebeb;" onchange="changeColor(this, 'text', 'top')" oninput="changeColor(this, 'text', 'top')" onkeyup="changeColor(this, 'text', 'top')"></input>
                                            </span>

                                            <input type="text" value="#777777" id="text_top_input" onchange="changeColor(this, 'text', 'top')" oninput="changeColor(this, 'text', 'top')" onkeyup="changeColor(this, 'text', 'top')" class="form-control" placeholder="Código" style="height: 54px;">
                                        </div>
                                    </div>
                                </div>

                                <div style="display: none; padding: 0px; padding-bottom: 15px;" id="top_image_div" class="col-md-12">
                                    <div class="col-md-12">
                                        <form role="form" action="<?php base_url('Rastreio/customization') ?>" method="post" id="formCustomization" enctype="multipart/form-data">
                                            <label for="background_top">Adicione uma imagem</label> <img src="<?= base_url('assets/tracking/images/info_icon.svg') ?>" style="height: 15px; color: blue; padding-left: 5px;" data-toggle="tooltip" data-placement="right" title="Insira uma imagem de 1679 x 143px para garantir a perfeita exibição do topo.">
                                            <div class="input-group">
                                                <input class=" form-control" type="text" placeholder="Nome do arquivo" id="image_name" name="image_name" disabled>
                                                <div class="input-group-btn">
                                                    <label for="file_upload" class="btn btn-default"><span class="glyphicon glyphicon-folder-open"></span> &nbsp; Procurar...</label>
                                                    <input id="file_upload" name="file_upload" type="file" class="btn btn-default" style="display: none;" accept=".jpg, .jpeg" onchange="uploadFile()" />
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row"></div>

                <div class="box box-primary">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div style="padding-bottom: 15px; font-size: 25px;" class="col-md-12"><b>Busca</b></div>

                                <div style="padding-bottom: 15px;" class="col-md-12">
                                    <div class="col-md-6" style="padding-left: 0px;">
                                        <label for="background_search">Cor do fundo</label>
                                        <div class="input-group">
                                            <span class="input-group-addon" id="background_search" style="padding: 6px;">
                                                <input type="color" value="#808080" id="background_search_color_input" name="background_search_color_input" style="height: 40px; width: 40px; padding: 0px; background-color: #ebebeb; border-color: #ebebeb;" onchange="changeColor(this, 'background', 'search', true)" oninput="changeColor(this, 'background', 'search', true)" onkeyup="changeColor(this, 'background', 'search', true)"></input>
                                            </span>

                                            <input type="text" value="#808080" id="background_search_input" onchange="changeColor(this, 'background', 'search', true)" oninput="changeColor(this, 'background', 'search', true)" onkeyup="changeColor(this, 'background', 'search', true)" class="form-control" placeholder="Código" style="height: 54px;">
                                        </div>
                                    </div>

                                    <div class="col-md-6" style="padding-left: 0px;">
                                        <label for="text_search">Cor do texto do fundo</label>
                                        <div class="input-group">
                                            <span class="input-group-addon" id="text_search" style="padding: 6px;">
                                                <input type="color" value="#ffffff" id="text_search_color_input" name="text_search_color_input" style="height: 40px; width: 40px; padding: 0px; background-color: #ebebeb; border-color: #ebebeb;" onchange="changeColor(this, 'text', 'search', true)" oninput="changeColor(this, 'text', 'search', true)" onkeyup="changeColor(this, 'text', 'search', true)"></input>
                                            </span>

                                            <input type="text" value="#ffffff" id="text_search_input" onchange="changeColor(this, 'text', 'search', true)" oninput="changeColor(this, 'text', 'search', true)" onkeyup="changeColor(this, 'text', 'search', true)" class="form-control" placeholder="Código" style="height: 54px;">
                                        </div>
                                    </div>

                                    <div class="col-md-6" style="padding-left: 0px;">
                                        <label for="background_button">Cor do fundo do botão</label>
                                        <div class="input-group">
                                            <span class="input-group-addon" id="background_button" style="padding: 6px;">
                                                <input type="color" value="#3a2c51" id="background_button_color_input" name="background_button_color_input" style="height: 40px; width: 40px; padding: 0px; background-color: #ebebeb; border-color: #ebebeb;" onchange="changeColor(this, 'background', 'button')" oninput="changeColor(this, 'background', 'button')" onkeyup="changeColor(this, 'background', 'button')"></input>
                                            </span>

                                            <input type="text" value="#3a2c51" id="background_button_input" onchange="changeColor(this, 'background', 'button')" oninput="changeColor(this, 'background', 'button')" onkeyup="changeColor(this, 'background', 'button')" class="form-control" placeholder="Código" style="height: 54px;">
                                        </div>
                                    </div>

                                    <div class="col-md-6" style="padding-left: 0px;">
                                        <label for="text_button">Cor do texto do botão</label>
                                        <div class="input-group">
                                            <span class="input-group-addon" id="text_button" style="padding: 6px;">
                                                <input type="color" value="#ffffff" id="text_button_color_input" name="text_button_color_input" style="height: 40px; width: 40px; padding: 0px; background-color: #ebebeb; border-color: #ebebeb;" onchange="changeColor(this, 'text', 'button')" oninput="changeColor(this, 'text', 'button')" onkeyup="changeColor(this, 'text', 'button')"></input>
                                            </span>

                                            <input type="text" value="#ffffff" id="text_button_input" onchange="changeColor(this, 'text', 'button')" oninput="changeColor(this, 'text', 'button')" onkeyup="changeColor(this, 'text', 'button')" class="form-control" placeholder="Código" style="height: 54px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row"></div>

                <div class="box box-primary">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div style="padding-bottom: 15px; font-size: 25px;" class="col-md-12"><b>Rodapé</b></div>

                                <div style="padding-bottom: 15px;" class="col-md-12">
                                    <div class="form-group">
                                        <input type="radio" id="footer_basic" name="footer_custom" value="footer_basic" onclick="regionVisibility('bottom_basic_div', 'bottom_html_div');" checked>
                                        <label for="footer_basic"> Versão básica</label>
                                        <input type="radio" id="footer_html" name="footer_custom" value="footer_html" onclick="regionVisibility('bottom_html_div', 'bottom_basic_div');" style="margin-left: 25px;">
                                        <label for="footer_html"> Versão personalizada em HTML</label>
                                    </div>
                                </div>

                                <div style="display: block; padding-bottom: 15px;" id="bottom_basic_div" class="col-md-12">
                                    <div class="col-md-6" style="padding-left: 0px;">
                                        <label for="background_footer">Cor do fundo</label>
                                        <div class="input-group">
                                            <span class="input-group-addon" id="background_footer" style="padding: 6px;">
                                                <input type="color" value="#d3d3d3" id="background_footer_color_input" name="background_footer_color_input" style="height: 40px; width: 40px; padding: 0px; background-color: #ebebeb; border-color: #ebebeb;" onchange="changeColor(this, 'background', 'footer', true)" oninput="changeColor(this, 'background', 'footer', true)" onkeyup="changeColor(this, 'background', 'footer', true)"></input>
                                            </span>

                                            <input type="text" value="#d3d3d3" id="background_footer_input" onchange="changeColor(this, 'background', 'footer', true)" oninput="changeColor(this, 'background', 'footer', true)" onkeyup="changeColor(this, 'background', 'footer', true)" class="form-control" placeholder="Código" style="height: 54px;">
                                        </div>
                                    </div>

                                    <div class="col-md-6" style="padding-left: 0px;">
                                        <label for="text_footer">Cor do texto</label>
                                        <div class="input-group">
                                            <span class="input-group-addon" id="text_footer" style="padding: 6px;">
                                                <input type="color" value="#808080" id="text_footer_color_input" name="text_footer_color_input" style="height: 40px; width: 40px; padding: 0px; background-color: #ebebeb; border-color: #ebebeb;" onchange="changeColor(this, 'text', 'footer', true)" oninput="changeColor(this, 'text', 'footer', true)" onkeyup="changeColor(this, 'text', 'footer', true)"></input>
                                            </span>

                                            <input type="text" value="#808080" id="text_footer_input" onchange="changeColor(this, 'text', 'footer', true)" oninput="changeColor(this, 'text', 'footer', true)" onkeyup="changeColor(this, 'text', 'footer', true)" class="form-control" placeholder="Código" style="height: 54px;">
                                        </div>
                                    </div>
                                </div>

                                <div style="display: none; padding-bottom: 15px;" id="bottom_html_div" class="col-md-12">
                                    <label for="bottom_html">Adicione abaixo o HTML para personalizar o rodapé</label><br/>

                                    <?php
                                    $html = htmlentities('<div>&copy; ' . date("Y") . ' ' . $seller_center . ' &mdash; Todos os direitos reservados.</div>');
                                    ?>

                                    <textarea id="bottom_html" name="bottom_html" rows="5" style="width: 100%;" oninput="changeFooter();" placeholder=""><?=$html ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.box -->
            </div>
            <!-- col-md-12 -->

            <div class="col-md-6 col-xs-6">
                <div class="box box-primary">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div style="padding-bottom: 15px;"><b>Personalize ao lado para visualizar</b></div>
                            </div>

                            <!-- Banner Start -->
                            <div id="top" class="header">
                                <div class="container header-container">
                                    <div style="background-color: #d3d3d3; color: #777777; text-align: center; height: 71px; width: 100%; padding: 0; margin: 0px; font-size: 45px;" id="top_preview" name="top_preview">Rastreie seu pedido</div>
                                </div>
                            </div>
                            <!-- Banner End -->

                            <!-- Tracker Start -->
                            <section class="tracker-section" style="width: 100%;">
                                <div class="container tracker-container" style="width: 100%;">
                                    <div class="row">
                                        <div style="width: 90%; margin: auto;">
                                            <div class="tracker-form" style="background-color: #808080;" id="background_search_preview" name="background_search_preview">
                                                <h3 class="tracker-heading" style="color: #ffffff;" id="text_search_preview" name="text_search_preview">Informe o CPF/CNPJ do comprador para rastrear o pedido:</h3>

                                                <div class="col-md-8" style="padding-left: 0px;">
                                                    <div class="form-group" style="width: 100%; margin-top: 6px;">
                                                        <input type="text" class="form-control" id="tracking_code" style="width: 100%;" placeholder="CPF/CNPJ">
                                                    </div>
                                                </div>

                                                <div class="learn-more-btn-section">
                                                    <a class="nav-link btn btn-primary" href="#" style="background-color: #3a2c51; color: #ffffff;" id="button_preview" name="button_preview">Rastrear</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                            <!-- Tracker End -->

                            <!-- footer Start -->
                            <section class="footer" style="background-color: #d3d3d3;" id="background_footer_preview" name="background_footer_preview">
                                <div class="container" style="width: 100%;">
                                    <div class="row">
                                        <div class="col-md-12" style="padding: 0px; min-height: 78px;">
                                            <div class="footer-copyright" id="text_footer_preview" name="text_footer_preview">
                                                <div>
                                                    &copy; <?=date('Y') . ' ' . $seller_center ?> &mdash; Todos os direitos reservados.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                            <!-- footer End -->

                        </div>
                    </div>
                </div>
            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->

        <a class="nav-link btn btn-primary" href="#" id="restoreChanges" onclick="restoreChanges();">Restaurar</a>
        <a class="nav-link btn btn-primary" href="#" id="saveChanges" onclick="saveChanges();">Salvar alterações</a>
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
var manageTable;
var base_url = "<?php echo base_url(); ?>";

const default_fields = {
    'top_selected': 'basic',
    'top_basic_back_color': '#d3d3d3',
    'top_basic_text_color': '#777777',
    'top_image_name': '',
    'middle_back_color': '#808080',
    'middle_text_color': '#ffffff',
    'middle_button_back_color': '#3a2c51',
    'middle_button_text_color': '#ffffff',
    'bottom_selected': 'basic',
    'bottom_basic_back_color': '#d3d3d3',
    'bottom_basic_text_color': '#808080',
    'bottom_html_content': "&lt;div&gt;&amp;copy; 2022 <?=$seller_center; ?> &amp;mdash; Todos os direitos reservados.&lt;/div&gt;"
};

var changed_fields = {
    'top_selected': 'basic',
    'top_basic_back_color': '#d3d3d3',
    'top_basic_text_color': '#777777',
    'top_image_name': '',
    'middle_back_color': '#808080',
    'middle_text_color': '#ffffff',
    'middle_button_back_color': '#3a2c51',
    'middle_button_text_color': '#ffffff',
    'bottom_selected': 'basic',
    'bottom_basic_back_color': '#d3d3d3',
    'bottom_basic_text_color': '#808080',
    'bottom_html_content': "&lt;div&gt;&amp;copy; 2022 <?=$seller_center; ?> &amp;mdash; Todos os direitos reservados.&lt;/div&gt;"
};

function tagsDecoder(input_text)
{
    const parsed_text = new DOMParser().parseFromString(input_text, "text/html");
    return parsed_text.documentElement.textContent;
}

function loadSettings()
{
    changed_fields['bottom_html_content'] = tagsDecoder(changed_fields['bottom_html_content']);    

    if (changed_fields['top_selected'] == 'image') {
        regionVisibility('top_image_div', 'top_basic_div');
        $('input[id=top_image]').prop('checked', true);

        document.getElementById("top_preview").outerHTML = `<div style="width: 100%; height: 70px; display: block; margin: 0px; background: url('../assets/files/sellercenter/${changed_fields['top_image_name']}') no-repeat; background-size: cover;" id="top_preview" name="top_preview"></div>`;
    } else if (changed_fields['top_selected'] == 'basic') {
        regionVisibility('top_basic_div', 'top_image_div');
        $('input[id=top_basic]').prop('checked', true);

        document.getElementById("top_preview").outerHTML = `<div style="background-color: #d3d3d3; color: #777777; text-align: center; width: 100%; padding: 0; margin: 0px; font-size: 45px;" id="top_preview" name="top_preview">Rastreie seu pedido</div>`;
    }

    if (changed_fields['bottom_selected'] == 'html') {
        regionVisibility('bottom_html_div', 'bottom_basic_div');
        $('input[id=footer_html]').prop('checked', true);
    } else if (changed_fields['bottom_selected'] == 'basic') {
        regionVisibility('bottom_basic_div', 'bottom_html_div');
        $('input[id=footer_basic]').prop('checked', true);
    }

    document.getElementById("send_tracking_url").checked = false;
    if (changed_fields['send_tracking_code_to_mkt'] == '1') {
        document.getElementById("send_tracking_url").checked = true;
    }

    document.getElementById("image_name").value = '';
    let image_name = String(changed_fields['top_image_name']);
    if (image_name !== '') {
        document.getElementById("image_name").value = image_name.substring(19);
    } else {
        document.getElementById("image_name").value = image_name;
    }
    document.getElementById("background_top_color_input").value = changed_fields['top_basic_back_color'];
    document.getElementById("background_top_input").value = changed_fields['top_basic_back_color'];
    document.getElementById("top_preview").style.backgroundColor = changed_fields['top_basic_back_color'];
    document.getElementById("text_top_color_input").value = changed_fields['top_basic_text_color'];
    document.getElementById("text_top_input").value = changed_fields['top_basic_text_color'];
    document.getElementById("top_preview").style.color = changed_fields['top_basic_text_color'];

    document.getElementById("background_search_color_input").value = changed_fields['middle_back_color'];
    document.getElementById("background_search_input").value = changed_fields['middle_back_color'];
    document.getElementById("background_search_preview").style.backgroundColor = changed_fields['middle_back_color'];
    document.getElementById("text_search_color_input").value = changed_fields['middle_text_color'];
    document.getElementById("text_search_input").value = changed_fields['middle_text_color'];
    document.getElementById("text_search_preview").style.color = changed_fields['middle_text_color'];

    document.getElementById("background_button_color_input").value = changed_fields['middle_button_back_color'];
    document.getElementById("background_button_input").value = changed_fields['middle_button_back_color'];
    document.getElementById("button_preview").style.backgroundColor = changed_fields['middle_button_back_color'];
    document.getElementById("text_button_color_input").value = changed_fields['middle_button_text_color'];
    document.getElementById("text_button_input").value = changed_fields['middle_button_text_color'];
    document.getElementById("button_preview").style.color = changed_fields['middle_button_text_color'];

    document.getElementById('bottom_html').value = changed_fields['bottom_html_content'];
    document.getElementById("background_footer_color_input").value = changed_fields['bottom_basic_back_color'];
    document.getElementById("background_footer_input").value = changed_fields['bottom_basic_back_color'];
    document.getElementById("background_footer_preview").style.backgroundColor = changed_fields['bottom_basic_back_color'];
    document.getElementById("text_footer_color_input").value = changed_fields['bottom_basic_text_color'];
    document.getElementById("text_footer_input").value = changed_fields['bottom_basic_text_color'];
    document.getElementById("text_footer_preview").style.color = changed_fields['bottom_basic_text_color'];
    document.getElementById('text_footer_preview').innerHTML = document.getElementById('bottom_html').value;
}

$(document).ready(function() {
    $("#mainLogisticsNav").addClass('active');
    $("#mainRastreioCustomNav").addClass('active');
    $(".select2").select2();

    $.ajax({
        url: `${base_url}Rastreio/loadSettings`,
        type: 'get',
        dataType: 'json',
        async: true,
        success: function(response) {
            changed_fields = JSON.parse(response);
            loadSettings();
        }
    });
});

function regionVisibility(show_region, hide_region)
{
    document.getElementById(hide_region).style.display = 'none';
    document.getElementById(show_region).style.display = 'block';

    if ((show_region == 'top_basic_div') && (hide_region == 'top_image_div')) {
        document.getElementById("top_preview").outerHTML = `<div style="background-color: #d3d3d3; color: #777777; text-align: center; width: 100%; padding: 0; margin: 0px; font-size: 45px;" id="top_preview" name="top_preview">Rastreie seu pedido</div>`;
        document.getElementById("top_preview").style.color = changed_fields['top_basic_text_color'];
        document.getElementById("top_preview").style.backgroundColor = changed_fields['top_basic_back_color'];
    } else if ((show_region == 'top_image_div') && (hide_region == 'top_basic_div')) {
        document.getElementById("top_preview").outerHTML = `<div style="width: 100%; height: 70px; display: block; margin: 0px; background: url('../assets/files/sellercenter/${changed_fields['top_image_name']}') no-repeat; background-size: cover;" id="top_preview" name="top_preview"></div>`;
    }
}

function changeColor(new_color, component_type, component_place, component_prefix = false)
{
    let new_color_code = new_color.value;
    if (String(new_color_code).substring(0, 1) != '#') {
        new_color_code = '#' + new_color_code;
    }

    if (component_place == 'top') {
        changed_fields['top_selected'] = 'basic';

        if (component_type == 'background') {
            changed_fields['top_basic_back_color'] = new_color_code;
        } else if (component_type == 'text') {
            changed_fields['top_basic_text_color'] = new_color_code;
        }
    } else if (component_place == 'search') {
        if (component_type == 'background') {
            changed_fields['middle_back_color'] = new_color_code;
        } else if (component_type == 'text') {
            changed_fields['middle_text_color'] = new_color_code;
        }
    } else if (component_place == 'button') {
        if (component_type == 'background') {
            changed_fields['middle_button_back_color'] = new_color_code;
        } else if (component_type == 'text') {
            changed_fields['middle_button_text_color'] = new_color_code;
        }
    } else if (component_place == 'footer') {
        changed_fields['bottom_selected'] = 'basic';

        if (component_type == 'background') {
            changed_fields['bottom_basic_back_color'] = new_color_code;
        } else if (component_type == 'text') {
            changed_fields['bottom_basic_text_color'] = new_color_code;
        }
    }

    if (component_prefix) {
        component_prefix = component_type + '_';
    } else {
        component_prefix = '';
    }

    if (component_type == 'text') {
        document.getElementById(`${component_prefix}${component_place}_preview`).style.color = new_color_code;
    } else if (component_type == 'background') {
        document.getElementById(`${component_prefix}${component_place}_preview`).style.backgroundColor = new_color_code;
    }

    if (String(new_color_code).length == 4) {
        let aux_color_code = '#';
        for (let index = 1; index < String(new_color_code).length; index++) {
            aux_color_code += new_color_code[index] + new_color_code[index];
        }
        new_color_code = aux_color_code;
    }

    if (String(new_color_code).length == 7) {
        document.getElementById(`${component_type}_${component_place}_color_input`).value = new_color_code;
        document.getElementById(`${component_type}_${component_place}_input`).value = new_color_code;
    }
}

function restoreChanges()
{
    changed_fields = default_fields;
    loadSettings();
}

function saveChanges()
{
    let update_url = `${base_url}Rastreio/updateSettings/`;
    let bottom_html_content = String(changed_fields['bottom_html_content']);
    bottom_html_content = bottom_html_content.replace(/\n/g,' ');
    bottom_html_content = bottom_html_content.replace(/\r/g,' ');
    bottom_html_content = bottom_html_content.replace(/\t/g,' ');

    if (bottom_html_content.search("<") != -1) {
        bottom_html_content = String(bottom_html_content)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/=/g, '&equals;')
            .replace(/'/g, '&apos;');
    }

    changed_fields['bottom_html_content'] = bottom_html_content;

    $.ajax({
        url: update_url,
        type: 'post',
        dataType: 'text',
        data: changed_fields,
        async: true,
        success: function() {
            console.log('Configurações salvas com sucesso.');
            Swal.fire('Configurações salvas com sucesso.');
        },
        error: function() {
            console.log('Erro ao tentar salvar as configurações.');
            Swal.fire('Erro ao tentar salvar as configurações.');
        },
    });
}

function changeFooter()
{
    document.getElementById('text_footer_preview').innerHTML = document.getElementById('bottom_html').value;
    changed_fields['bottom_selected'] = 'html';
    changed_fields['bottom_html_content'] = document.getElementById('bottom_html').value;
}

function uploadFile()
{
    let upload_image = "";
    let formData = new FormData(document.getElementById("formCustomization"));
    let upload_url = `${base_url}Rastreio/imageUpload`;
    document.getElementById("image_name").value = 'Arquivo sendo enviado...';

    $.ajax({
        url: upload_url,
        type: "POST",
        data: formData,
        processData: false,  // tell jQuery not to process the data
        contentType: false,  // tell jQuery not to set contentType
        success: function(data) {
            upload_image = JSON.parse(data);

            document.getElementById("image_name").value = '';
            let image_name = String(upload_image);
            if (image_name !== '') {
                document.getElementById("image_name").value = image_name.substring(19);
            }

            if (image_name != 'fail') {
                document.getElementById("top_preview").outerHTML = `<div style="width: 100%; height: 70px; display: block; margin: 0px; background: url('../assets/files/sellercenter/${image_name}') no-repeat; background-size: cover;" id="top_preview" name="top_preview"></div>`;

                changed_fields['top_selected'] = 'image';
                changed_fields['top_image_name'] = image_name;
            } else {
                document.getElementById("image_name").value = 'Um erro foi encontrado ao tentar fazer o upload do arquivo.';
            }
        },
        error: function() {
            document.getElementById("image_name").value = 'Um erro foi encontrado ao tentar fazer o upload do arquivo.';
        }
    });

    return false;
}

function copyToClipboard()
{
    let copy_text = document.getElementById("tracking_url");
    navigator.clipboard.writeText(copy_text.innerText);

    Swal.fire('Endereço copiado para a área de transferência.');
}

function toggleSendTrackingCode()
{
    let send_tracking_code = `${base_url}Rastreio/toggleSendTrackingCode/`;
    let send_url = {
        'toggle_send_tracking_code': document.getElementById("send_tracking_url").checked,
        'tracking_url': document.getElementById("tracking_url").innerText
    };

    $.ajax({
        url: send_tracking_code,
        type: 'post',
        dataType: 'json',
        data: send_url,
        async: true,
        success: function() {
            console.log('Configurações salvas com sucesso.');
            // Swal.fire('Configurações salvas com sucesso.');
        },
        error: function() {
            console.log('Erro ao tentar salvar as configurações.');
            // Swal.fire('Erro ao tentar salvar as configurações.');
        },
    });
}
</script>
