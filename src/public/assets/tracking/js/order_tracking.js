$("#order_form").submit(function(ev) {
    ev.preventDefault();
});

$("#agreement_form").submit(function(ev) {
    ev.preventDefault();
});

// Máscara de CPF/CNPJ.
$("input[id*='tracking_code']").inputmask({
    mask: ['999.999.999-99', '99.999.999/9999-99'],
    keepStatic: true
});

// Máscara de telefone.
$("input[id*='contact_phone']").inputmask({
    mask: ['(99) 9999-9999', '(99) 999-999-999'],
    keepStatic: true
});

$(document).ready(function() {
    localStorage.setItem("stored_order_number", "-1");
});

function consultaPedidos(tracking_code = null, client_order = null)
{
    let prov_seller = document.getElementById("prov_seller").value;
    document.getElementById("tracker_error").innerText = "";
    document.getElementById("tracker_error").style.display = "none";

    var error_msg = "";
    var order_email = false;
    if (prov_seller == 'prov_seller_i') {
        // E-mail.
        order_email = $("input[id*='order_email']").val();
        var valid_email = false;

        if (order_email) {
            valid_email = validateEmail(order_email);

            if (valid_email === false) {
                document.getElementById("tracker_error").innerText = "E-mail informado inválido.";
                document.getElementById("tracker_error").style.display = "block";
            }
        } else {
            error_msg = "E-mail não informado.";
        }
    }

    // CPF/CNPJ.
    var cpf_cnpj = $("input[id*='tracking_code']").val();
    cpf_cnpj = cpf_cnpj.toString();
    if ((tracking_code !== undefined) && (tracking_code !== null)) {
        cpf_cnpj = tracking_code;
    }
    cpf_cnpj = cpf_cnpj.replace(/\D/g, "");
    var valid_tracking_code = false;

    if (cpf_cnpj) {
        if ((cpf_cnpj.length < 11) || ((cpf_cnpj.length > 11) && (cpf_cnpj.length < 14))) {
            if (error_msg == "") {
                error_msg = "CPF/CNPJ informado incorreto.";
            } else {
                error_msg += "\nCPF/CNPJ informado incorreto.";
            }
        } else if ((cpf_cnpj.length == 11) || (cpf_cnpj.length == 14)) {
            valid_tracking_code = true;
        }
    } else {
        if (error_msg == "") {
            error_msg = "CPF/CNPJ não informado.";
        } else {
            error_msg += "\nCPF/CNPJ não informado.";
        }
    }

    if (
        ((prov_seller == 'prov_seller_i') && (valid_email && valid_tracking_code)) ||
        ((prov_seller == 'prov_seller_o') && (valid_tracking_code))
    ) {
        document.getElementById("tracker_form").style.borderRadius = "10px 10px 0px 0px";
        document.getElementById("order_tracking").innerHTML = `
        <div class="row">
            <div class="tracking-item">
            <div class="tracking-icon status-complete"><i class="fas fa-truck"></i></div>
            <div class="tracking-content">Processando. Por favor, aguarde.</div>
        </div>`;

        var lgpd_agree = localStorage.getItem("lgpd_agree");
        if (!lgpd_agree) {
            lgpd_agree = '';
        }

        var corder = "NA";
        if ((client_order !== null) && (client_order !== undefined)) {
            corder = client_order;
        }

        $.ajax({
            url: './rastreio/status',
            type: 'get',
            data: {
                'email': order_email,
                'tracking_code': cpf_cnpj,
                'corder': corder,
                'lgpd_agree': lgpd_agree
            }, 
            dataType: 'json',
            success: function(response) {
                let response_status = "";
                let response_steps = "";
                for (var resp_key in response) {
                    if ((resp_key == "status") && (response[resp_key] == "success")) {
                        response_status = "success";
                    } else if ((resp_key == "status") && (response[resp_key] == "fail")) {
                        response_status = "fail";
                    }

                    if (resp_key == "steps") {
                        response_steps = response[resp_key];
                    }
                }

                if ((response_status != "") && (response_steps != "")) {
                    document.getElementById("order_tracking").innerHTML = response_steps;
                } else if (
                    ((response_status == "") || (response_steps == "")) || (response_status == "fail")
                ) {
                    document.getElementById("order_tracking").innerHTML = `
                    <div class="row">
                        <div class="tracking-item">
                        <div class="tracking-icon status-complete"><i class="fas fa-truck"></i></div>
                        <div class="tracking-content">CPF/CNPJ não encontrado.</div>
                    </div>`;
                }
            },
            fail: function() {
                document.getElementById("order_tracking").innerHTML = `
                <div class="row">
                    <div class="tracking-item">
                    <div class="tracking-icon status-complete"><i class="fas fa-truck"></i></div>
                    <div class="tracking-content">Houve uma falha.</div>
                </div>`;
            }
        });
    }

    if (error_msg != "") {
        document.getElementById("tracker_error").innerText = error_msg;
        document.getElementById("tracker_error").style.display = "block";
    }
}

function searchOrderNumber()
{
    let stored_order_number = localStorage.getItem("stored_order_number");
    let error_msg = "";
    let lookup_timeout = null;

    clearTimeout(lookup_timeout);
    lookup_timeout = setTimeout(function () {
        let order_number = String(document.getElementById("contact_order_number").value);
        if (stored_order_number != order_number) {
            localStorage.setItem("stored_order_number", order_number);

            document.getElementById("contact_form_error").innerText = 'Processando. Por favor, aguarde.';
            document.getElementById("contact_form_error").style.display = "block";

            document.getElementById("contact_name").disabled = true;
            document.getElementById("contact_email").disabled = true;
            document.getElementById("contact_phone").disabled = true;
            document.getElementById("contact_message").disabled = true;

            $.ajax({
                url: './rastreio/orderNumberLookup',
                type: 'get',
                data: {
                    'order_number': order_number
                }, 
                dataType: 'json',
                success: function(response) {
                    let lookup_response = JSON.parse(response);
                    if (lookup_response != 'order_found') {
                        document.getElementById("contact_form_error").innerText = 'Pedido não encontrado.';

                        localStorage.setItem("status_order_number", "not found");
                    } else {
                        document.getElementById("contact_form_error").innerText = "";
                        document.getElementById("contact_form_error").style.display = "none";

                        document.getElementById("contact_name").disabled = false;
                        document.getElementById("contact_email").disabled = false;
                        document.getElementById("contact_phone").disabled = false;
                        document.getElementById("contact_message").disabled = false;

                        localStorage.setItem("status_order_number", "found");
                    }
                },
                fail: function() {
                    document.getElementById("contact_form_error").innerText = 'Falha ao processar informação.';
                }
            });
        }
    }, 500);

    if (error_msg != "") {
        document.getElementById("contact_form_error").innerText = error_msg;
        document.getElementById("contact_form_error").style.display = "block";
    }
}

$("#problemaPedidos").click(function() {
    let prov_seller = document.getElementById("prov_seller").value;

    document.getElementById("contact_form_error").innerText = "";
    document.getElementById("contact_form_error").style.display = "none";

    var status_order_number = localStorage.getItem("status_order_number");
    var contact_order_number = $("input[id*='contact_order_number']").val();
    var contact_name = $("input[id*='contact_name']").val();
    var valid_name = false;
    var contact_email = false;
    if (prov_seller == 'prov_seller_i') {
        contact_email = $("input[id*='contact_email']").val();
        var valid_email = false;
    }
    var contact_phone = $("input[id*='contact_phone']").val();
    var contact_message = $("textarea[id*='contact_message']").val();
    var valid_message = false;
    var error_msg = "";

    if (contact_name) {
        const regex = /^[a-zA-ZÀ-ÖØ-öø-ÿ'-. ]{5,50}$/;

        if (!regex.test(contact_name)) {
            error_msg = "Por favor, informe o nome completo.";
        } else {
            valid_name = true;
        }
    } else {
        error_msg = "Nome não informado.";
    }

    if (prov_seller == 'prov_seller_i') {
        if (contact_email) {
            valid_email = validateEmail(contact_email);
            if (valid_email === false) {
                if (error_msg == "") {
                    error_msg = "E-mail informado inválido.";
                } else {
                    error_msg += "\nE-mail informado inválido.";
                }
            }
        } else {
            if (error_msg == "") {
                error_msg = "E-mail não informado.";
            } else {
                error_msg += "\nE-mail não informado.";
            }
        }
    }

    if (contact_message) {
        if (contact_message.length < 10) {
            if (error_msg == "") {
                error_msg = "Mensagem muito curta.";
            } else {
                error_msg += "\nMensagem muito curta.";
            }
        } else {
            valid_message = true;
        }
    } else {
        if (error_msg == "") {
            error_msg = "Mensagem não informada.";
        } else {
            error_msg += "\nMensagem não informada.";
        }
    }

    if (contact_phone) {
        let phone_number = "";
        for (let cont = 0; cont < contact_phone.length; cont++) {
            let current = contact_phone.substring(cont, cont + 1);
            if ((current >= 0) && (current <= 9)) {
                phone_number += contact_phone.substring(cont, cont + 1);
            }
        }

        if ((phone_number.length < 11) || (phone_number.length > 12)) {
            if (error_msg == "") {
                error_msg = "Número incorreto do telefone.";
            } else {
                error_msg += "\nNúmero incorreto do telefone.";
            }
        } else {
            contact_phone = phone_number;
        }
    }

    if (status_order_number != 'found') {
        error_msg = "Pedido não encontrado.";
    }

    if (
        (
            ((prov_seller == 'prov_seller_i') && (valid_email && valid_name && valid_message)) ||
            ((prov_seller == 'prov_seller_o') && (valid_name && valid_message))
        ) && (status_order_number == 'found')
    ) {
        document.getElementById("contact_form_error").style.color = "#379914";
        document.getElementById("contact_form_error").innerText = 'Processando. Por favor, aguarde.';
        document.getElementById("contact_form_error").style.display = "block";

        $.ajax({
            url: './rastreio/sendEmail',
            type: 'get',
            data: {
                'name': contact_name, 
                'email': contact_email, 
                'phone': contact_phone, 
                'order_number': contact_order_number, 
                'message': contact_message 
            }, 
            dataType: 'json',
            success: function(response) {
                let response_status = "";
                for (var resp_key in response) {
                    if ((resp_key == "status") && (response[resp_key] == "success")) {
                        response_status = "success";
                    } else if ((resp_key == "status") && (response[resp_key] == "fail")) {
                        response_status = "fail";
                    }
                }

                if (response_status != "") {
                    document.getElementById("contact_form_error").innerText = 'Mensagem enviada com sucesso.';
                } else {
                    document.getElementById("contact_form_error").innerText = 'Falha ao enviar mensagem.';
                }
            },
            fail: function() {
                document.getElementById("contact_form_error").innerText = 'Falha ao enviar mensagem.';
            }
        });
    }

    if (error_msg != "") {
        document.getElementById("contact_form_error").innerText = error_msg;
        document.getElementById("contact_form_error").style.display = "block";
    }
});

function validateEmail(email)
{
    const emailRegex = /^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i;
    return emailRegex.test(email);
}

function agreementPopup()
{
    var lgpd_agree = localStorage.getItem("lgpd_agree");
    if (lgpd_agree) {
        document.getElementById("dvAgreement").style.display = "none";
    } else {
        document.getElementById("dvAgreement").style.display = "block";
    }
}

var agreement_location = "none";
function closeAgreement(location)
{
    let proceed = false;
    if (agreement_location == "none") {
        agreement_location = location;

        if (location != "inside") {
            proceed = true;
        }
    }

    if (location == "outside") {
        agreement_location = "none";
    }

    var agreement_form = $('form').serializeArray();
    var lgpd_agree = "";

    for (var resp_key in agreement_form) {
        let form_obj = agreement_form[resp_key];

        for (var agree_key in form_obj) {
            if (form_obj[agree_key] == 'agreement') {
                if (lgpd_agree == "") {
                    lgpd_agree = 'agreement';
                } else {
                    lgpd_agree += ',agreement';
                }
            } else if (form_obj[agree_key] == 'advertisement') {
                if (lgpd_agree == "") {
                    lgpd_agree = 'advertisement';
                } else {
                    lgpd_agree += ',advertisement';
                }
            }
        }
    }

    localStorage.setItem("lgpd_agree", lgpd_agree);

    if (proceed) {
        document.getElementById("dvAgreement").style.display = "none";
    }
}
