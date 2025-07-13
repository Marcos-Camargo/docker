<!--
Listar Pedidos Devolvidos

Observações:
- Cada usuário só pode ver pedidos da sua empresa;
- Agências podem ver todos os pedidos das suas empresas;
- Administradores podem ver todas as empresas e agências.
-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <style>
        .filters {
            position: relative;
            top: 30px;
            display: flex;
            justify-content: center;
            width: 70%;
            margin: auto;
        }

        .normal {
            font-weight: normal;
        }
        
        .bootstrap-select .dropdown-toggle .filter-option {
            background-color: white !important;
        }

        .bootstrap-select .dropdown-menu li a {
            border: 1px solid gray;
        }
    </style>
        
    <?php
    // Se o usuário não tem permissão de acesso.
    if (!in_array('createReturnOrder', $this->permission)) {
        redirect('dashboard', 'refresh');
    }

    // Se as informações do pedido não foram fornecidas.
    if (!isset($order_information)) {
        redirect('dashboard', 'refresh');
    }

    foreach ($products_return as $current_product) {
        $store_id = $current_product['store_id'];
        $order_id = $current_product['order_id'];
        break;
    }

    $data['pageinfo'] = "application_return_order_short";
    $this->load->view('templates/content_header', $data);
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

                <!-- div class="box box-primary mt-2">
                    <div class="box-body">
                        <a class="pull-right btn btn-primary" href="<?php // echo base_url('export/returnproductxls') ?>"><i class="fa fa-file-excel-o"></i> <?=$this->lang->line('application_data_export')?></a>
                    </div>
                </div -->

                <div class="box box-primary">
                    <form role="form" action="<?php base_url('orders/request_return') ?>" method="post" id="formReturnOrder">
                        <div class="box-body">

                            <?php echo $order_information; ?>

                            <div class="row"></div>

                            <!-- Operador logístico -->
                            <div class="col-md-3 col-xs-12 form-group">
                                <label id="label_oplogistico">* Operador logístico</label>
                                <select class="form-control" id="oplogistico" name="oplogistico" onchange="buscaoplogistico()" required>
                                    <!-- option value="ESCOLHA" selected>Escolha</option>
                                    <option value="CORREIOS">Correios</option>
                                    <option value="TRANSPORTADORA">Transportadora</option -->

                                    <?php echo $ship_company_preview; ?>
                                </select>
                            </div>

                            <!-- Nome da transportadora -->
                            <div class="col-md-3 col-xs-12 form-group" id="divntransportadora">
                                <label id="label_ntransportadora">* Nome da transportadora</label>
                                <select class="form-control" id="ntransportadora" name="ntransportadora"
                                    <?php echo $ntransportadora; ?>
                                </select>
                            </div>

                            <!-- Código de rastreio -->
                            <div class="col-md-3 col-xs-12 form-group">
                                <label id="label_ship_service_preview">* Código de rastreio</label>
                                <input type="text" name="ship_service_preview" id="ship_service_preview" class="form-control"
                                <?php
                                if (isset($ship_service_preview)) {
                                    echo ' value="' . $ship_service_preview . '" ';
                                }
                                ?>
                                required>
                            </div>

                            <!-- Valor do frete -->
                            <div class="col-md-3 col-xs-12 form-group">
                                <label id="label_ship_service_price">Valor do frete</label>
                                <input type="text" id="ship_service_price" class="form-control" 
                                <?php
                                if (isset($ship_service_price)) {
                                    echo ' value="R$' . $ship_service_price . '" ';
                                }
                                ?>
                                >
                            </div>

                            <!-- Data da solicitação de devolução -->
                            <div class="col-md-3 col-xs-12 form-group">
                                <label id="label_return_date">* Data da solicit. devolução</label>
                                <input type="date" name="return_date" id="return_date" class="form-control" 
                                <?php
                                if (isset($return_date)) {
                                    echo ' value="' . $return_date . '" ';
                                }
                                ?>
                                required>
                            </div>

                            <!-- Número da nota fiscal da devolução -->
                            <div class="col-md-3 col-xs-12 form-group">
                                <label id="label_return_nfe_number">* No. nota fiscal da devolução</label>
                                <input type="textarea" class="form-control" id="return_nfe_number" name="return_nfe_number"
                                    <?php
                                    if (isset($return_nfe_number)) {
                                        echo ' value="' . $return_nfe_number . '" ';
                                    }
                                    ?>
                                       required>
                            </div>

                            <!-- Data de emissão da nota fiscal da devolução -->
                            <div class="col-md-3 col-xs-12 form-group">
                                <label id="label_return_nfe_emission_date">* Data de emissão da nota fiscal da devolução</label>
                                <input type="text" class="form-control" id="return_nfe_emission_date" name="return_nfe_emission_date"
                                    <?php
                                    if (isset($return_nfe_emission_date)) {
                                        echo ' value="' . $return_nfe_emission_date . '" ';
                                    }
                                    ?>
                                       required>
                            </div>

                            <div class="row"></div>

                            <!-- Nota fiscal de devolução -->
                            <div class="col-md-3 col-xs-12 form-group" class="div_upload" id="div_upload">
                                <label id="label_upload_nfe">
                                    <?php if (!isset($return_action) || ($return_action == 'create')) echo '*'; ?> Nota fiscal de devolução
                                </label>
                                <input type="file" name="upload_nfe" id="upload_nfe" accept=".pdf" onchange="uploadFile('nfe')" required />
                            </div>

                            <!-- Carta de correção -->
                            <div class="col-md-3 col-xs-12 form-group" class="div_upload" id="div_upload">
                                <label>Carta de correção</label>
                                <input type="file" name="upload_letter" id="upload_letter" accept=".pdf" onchange="uploadFile('letter')" required />
                            </div>

                            <!-- Motivo da devolução -->
                            <div class="col-md-6 col-xs-12 form-group">
                                <label id="label_return_reason">* Motivo da devolução</label>
                                <input type="textarea" class="form-control" id="return_reason" name="return_reason" 
                                <?php
                                if (isset($return_reason)) {
                                    echo ' value="' . $return_reason . '" ';
                                }
                                ?>
                                required></textarea>
                            </div>

                            <div class="row"></div>

                            <?php echo $items_list; ?>

                            <?php if (!empty($refund_on_gateway_value_to_return)): ?>
                            <h3 class="font-weight-bold"><?=$this->lang->line('payment_balance_transfers_box_total_returned')?>: <span><?=money($refund_on_gateway_value_to_return)?></span></h3>
                            <?php endif; ?>
                            <div class="row"></div>
                        </div>

                        <div class="box-footer">
                            <a href="<?php echo base_url("orders/update/$order_id") ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                            <button type="submit" class="btn btn-primary" id="submit_form">Criar Devolução</button>
                        </div>
                        <input type="hidden" id="return_action" value="<?php if (isset($return_action)) echo $return_action; ?>">
                        <input type="hidden" id="complete_order" value="<?php if (isset($complete_order)) echo $complete_order; ?>">
                        <input type="hidden" id="refund_on_gateway_value_to_return" value="<?=$refund_on_gateway_value_to_return?>">
                    </form>
                </div>
            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script src="https://plentz.github.io/jquery-maskmoney/javascripts/jquery.maskMoney.min.js"></script>
<script type="application/javascript" src="https://cdn.datatables.net/buttons/2.3.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript">

var base_url = "<?php echo base_url(); ?>";
var products_return = <?php print_r($post_items); ?>;
var providers = "";
var upload_nfe = "";
var upload_letter = "";
var return_action = "create";
var return_company = "";
var complete_order = 0;
var store_id = <?=$store_id?>

$("#ship_service_price").maskMoney({
    thousands: '.', 
    decimal: ',', 
    prefix: 'R$'
});

// Deixa o submenu "Devolução de Produtos" em negrito, marcando o submenu como selecionado.
$(document).ready(function() {
    $("#mainOrdersNav").addClass('active');
    $("#ReturnOrderNav").addClass('active');
    $(".select2").select2();

    complete_order = document.getElementById("complete_order").value;

    return_action = document.getElementById("return_action").value;
    if (return_action == "edit") {
        document.getElementById("submit_form").innerText = "Salvar informações";
        document.getElementById("submit_form").style.visibility = "visible";
    }

    $.ajax({
        url: base_url + 'ProductsReturn/returnOrdersProvidersList/' + store_id,
        type: 'get',
        success: function(response) {
            providers = JSON.parse(response);
        }
    });

    $('#return_nfe_emission_date').datetimepicker({
        format: "DD/MM/YYYY HH:mm:ss",
        maxDate: new Date(+new Date()),
        showTodayButton: true,
        showClear: true,
    });
});

function buscaoplogistico()
{
    if ($('#oplogistico').val() === "CORREIOS") {
        document.getElementById("divntransportadora").innerHTML = `
            <label id="label_ntransportadora">Nome da transportadora</label>
            <select class="form-control" id="ntransportadora" name="ntransportadora" required>
                <option value="ESCOLHA" selected>Escolha</option>
                <option value="PAC">PAC</option>
                <option value="SEDEX">SEDEX</option>
            </select>`;
    } else if ($('#oplogistico').val() === "TRANSPORTADORA") {
        if (return_action == "create") {
            document.getElementById("submit_form").innerText = "Criar devolução";
        } else if (return_action == "edit") {
            document.getElementById("submit_form").innerText = "Salvar informações";
        }
        document.getElementById("submit_form").style.visibility = "visible";

        let divntransportadora = `
            <label id="label_ntransportadora">* Nome da transportadora</label>
            <select class="form-control" id="ntransportadora" name="ntransportadora" required>
                <option value="ESCOLHA" selected>Escolha</option>`;

        let keys = Object.keys(providers);
        for (index of keys) {
            divntransportadora += `<option value="${providers[index]["id"]}">${providers[index]["name"]}</option>`;
        }
        divntransportadora += '</select>';
        document.getElementById("divntransportadora").innerHTML = divntransportadora;
    }
}

function uploadFile(category = 'nfe')
{
    var formData = new FormData(document.getElementById("formReturnOrder"));

    let upload_url = `${base_url}ProductsReturn/nfeUpload`;
    if (category == 'letter') {
        upload_url = `${base_url}ProductsReturn/letterUpload`;
    }

    $.ajax({
        url: upload_url,
        type: "POST",
        data: formData,
        processData: false,  // tell jQuery not to process the data
        contentType: false   // tell jQuery not to set contentType
    }).done(function(data) {
        if (category == "nfe") {
            upload_nfe = decodeURIComponent(JSON.parse(data));
        } else if (category == "letter") {
            upload_letter = decodeURIComponent(JSON.parse(data));
        }
    });

    return false;
}

document.getElementById("submit_form").addEventListener("click", function(event) {
    event.preventDefault();

    var nfe_faturamento = <?php echo json_encode($nfe_faturamento); ?>;
    let transportadora = "";
    let return_nfe_number = $('#return_nfe_number').val();
    let return_nfe_emission_date = $('#return_nfe_emission_date').val();
    let ship_service_preview = $('#ship_service_preview').val();
    let return_price = String($('#ship_service_price').val());
    let return_date = $('#return_date').val();
    let return_reason = $('#return_reason').val();
    let oplogistico = $('#oplogistico').val();
    let validated = 0;

    let transportadora_id = $('#ntransportadora').val();
    // Transportadora.
    if (transportadora_id !== "ESCOLHA") {
        document.getElementById("ntransportadora").style.borderColor = "#d2d6de";
        document.getElementById("label_ntransportadora").style.color = "#0066CC";

        let keys = Object.keys(providers);
        let provider_index = -1;
        for (index of keys) {
            provider_index = providers[index]["id"];
            if (provider_index == transportadora_id) {
                transportadora = providers[index]["name"];
                validated += 1;
            }
        }

        // Carta de correção.
        if (upload_letter !== "") {
            upload_letter = upload_letter.replaceAll('"', '');
        }
    } else {
        document.getElementById("ntransportadora").style.borderColor = "red";
        document.getElementById("label_ntransportadora").style.color = "red";
    }

    // Número da nota fiscal da devolução.
    if (return_nfe_number !== "") {
        document.getElementById("return_nfe_number").style.borderColor = "#d2d6de";
        document.getElementById("label_return_nfe_number").style.color = "#0066CC";
    } else {
        document.getElementById("return_nfe_number").style.borderColor = "red";
        document.getElementById("label_return_nfe_number").style.color = "red";
    }

    // Data de emissão da nota fiscal da devolução.
    if (return_nfe_emission_date !== "") {
        document.getElementById("return_nfe_emission_date").style.borderColor = "#d2d6de";
        document.getElementById("label_return_nfe_emission_date").style.color = "#0066CC";
        validated += 1;
    } else {
        document.getElementById("return_nfe_emission_date").style.borderColor = "red";
        document.getElementById("label_return_nfe_emission_date").style.color = "red";
    }

    // Nota fiscal de devolução.
    if (return_action == 'create') {
        if (upload_nfe !== "") {
            upload_nfe = upload_nfe.replaceAll('"', '');
            document.getElementById("upload_nfe").style.border = "1px solid #d2d6de";
            document.getElementById("label_upload_nfe").style.color = "#0066CC";
            validated += 1;
        } else {
            document.getElementById("upload_nfe").style.border = "1px solid red";
            document.getElementById("label_upload_nfe").style.color = "red";
        }
    }

    // Código de rastreio.
    if (ship_service_preview) {
        document.getElementById("ship_service_preview").style.borderColor = "#d2d6de";
        document.getElementById("label_ship_service_preview").style.color = "#0066CC";
        validated += 1;
    } else {
        document.getElementById("ship_service_preview").style.borderColor = "red";
        document.getElementById("label_ship_service_preview").style.color = "red";
    }

    // Valor do frete.
    return_price = return_price.substring(2);
    return_price = return_price.replace(",", ".");
    return_price = Number(return_price);

    // Data da solicitação da devolução.
    if (return_date !== "") {
        document.getElementById("return_date").style.borderColor = "#d2d6de";
        document.getElementById("label_return_date").style.color = "#0066CC";
        validated += 1;
    } else {
        document.getElementById("return_date").style.borderColor = "red";
        document.getElementById("label_return_date").style.color = "red";
    }

    // Motivo da devolução.
    if (return_reason !== "") {
        document.getElementById("return_reason").style.borderColor = "#d2d6de";
        document.getElementById("label_return_reason").style.color = "#0066CC";
        validated += 1;
    } else {
        document.getElementById("return_reason").style.borderColor = "red";
        document.getElementById("label_return_reason").style.color = "red";
    }

    // Operador logístico.
    if (oplogistico === "TRANSPORTADORA") {
        document.getElementById("oplogistico").style.borderColor = "#d2d6de";
        document.getElementById("label_oplogistico").style.color = "#0066CC";
        validated += 1;
    } else if (oplogistico === "ESCOLHA") {
        document.getElementById("oplogistico").style.borderColor = "red";
        document.getElementById("label_oplogistico").style.color = "red";
    }

    if (return_nfe_number !== '' && return_nfe_number !== nfe_faturamento) {
        document.getElementById("return_nfe_number").style.borderColor = "#d2d6de";
        document.getElementById("label_return_nfe_number").style.color = "#0066CC";
        validated += 1;
        console.log(`validated: ${validated}`);

        if (
            ((return_action == 'create') && (validated == 8)) ||
            ((return_action == 'edit') && (validated == 7))
        ) {
            const button_text = $('#submit_form').text();
            $('#submit_form').attr('disabled',true).text('Aguarde...');
            payload = {
                oplogistico: oplogistico, 
                shipping_co: transportadora,
                return_nfe_number: return_nfe_number,
                return_nfe_emission_date: return_nfe_emission_date,
                upload_nfe: upload_nfe, 
                ship_service_preview: ship_service_preview, 
                return_price: return_price, 
                return_reason: return_reason, 
                upload_letter: upload_letter, 
                return_date: return_date, 
                products_return: products_return, 
                return_action: return_action,
                complete_order: complete_order,
                refund_on_gateway_value_to_return: $('#refund_on_gateway_value_to_return').val()
            };
            $.ajax({
                url: base_url + 'ProductsReturn/insertReturnedProduct',
                type: 'post', 
                dataType: 'json', 
                data: payload, 
                success: function(response) {
                    console.log(response);
                    if (response.success) {
                        window.location.href = "<?=base_url('ProductsReturn/return')?>";
                        return;
                    }

                    $('#submit_form').attr('disabled',false).text(button_text);
                    Swal.fire({
                        icon: 'error',
                        title: response.message
                    });
                }, 
                fail: function(response) {
                    console.log(response);
                }
            });
        }
    } else {
        Swal.fire({
                icon: 'error',
                title: 'Nota Fiscal de Devolução deve ser diferente da Nota Fiscal de Faturamento!'
            });
        document.getElementById("return_nfe_number").style.borderColor = "red";
        document.getElementById("label_return_nfe_number").style.color = "red";
    }
});
</script>
