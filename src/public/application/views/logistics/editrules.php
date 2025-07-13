<div class="content-wrapper">	  
	<?php
        $data['pageinfo'] = "application_manage";
        $this->load->view('templates/content_header', $data);
    ?>

    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12" id="rowcol12">
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?=$this->session->flashdata('success');?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?=$this->session->flashdata('error');?>
                    </div>
                <?php endif;?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box box-primary">
                    <div class="box-header">
                        <h3 class="box-title">Regra de Precificação de Frete</h3>
                    </div>

                    <div class="box-body">
                        <div id="rules"></div>

                        <div class="row" style="padding-top: 6px;">
                            <div class="col-md-2">
                                <button type="button" id="btnSave" name="btnSave" class="btn btn-primary" onclick="saveRules();"><?=$this->lang->line('application_save');?></button>
                                <a href="<?= base_url('Shippingpricingrules/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">

var base_url = "<?=base_url()?>";
var rule_index = -1;
var rules_available = {};
var messages = {};
var price_range = true;
var error_fields = [];

$(document).ready(function() {  
    $("#mainLogisticsNav").addClass('active');
    $("#shippingPricingRulesNav").addClass('active');

    const pathname = window.location.href.replace(base_url, '');
    const slash_split = pathname.split("/");

    if (slash_split.length === 3) {
        rule_index = slash_split[2];
        loadRules(rule_index === '' ? '[NA]' : rule_index);
    } else if (slash_split.length === 2) {
        loadRules('[NA]');
    }
});

function deleteRules(deleting_index)
{
    if (rules_available[deleting_index]) {
        delete rules_available[deleting_index];
        if (document.getElementById(`rule-${Number(deleting_index)}`)) {
            document.getElementById(`rule-${Number(deleting_index)}`).remove();
        }
    }

    if (messages[`current_msg${Number(deleting_index)}`]) {
        delete messages[`current_msg${Number(deleting_index)}`];
        if (document.getElementById(`current_msg${Number(deleting_index)}`)) {
            document.getElementById(`current_msg${Number(deleting_index)}`).remove();
        }
    }

    if (messages[`cross_current_msg${Number(deleting_index)}`]) {
        delete messages[`cross_current_msg${Number(deleting_index)}`];
        if (document.getElementById(`cross_current_msg${Number(deleting_index)}`)) {
            document.getElementById(`cross_current_msg${Number(deleting_index)}`).remove();
        }
    }

    if (messages[`cross_previous_msg${Number(deleting_index)}`]) {
        delete messages[`cross_previous_msg${Number(deleting_index)}`];
        if (document.getElementById(`cross_previous_msg${Number(deleting_index)}`)) {
            document.getElementById(`cross_previous_msg${Number(deleting_index)}`).remove();
        }
    }

    if (document.getElementById(`pdg${Number(deleting_index)}`)) {
        document.getElementById(`pdg${Number(deleting_index)}`).remove();
    }

    let key_counter = 1;
    let rules_available_size = Object.keys(rules_available).length;
    for (const [k, v] of Object.keys(rules_available)) {
        if (rules_available_size == 1) {
            if (!document.getElementById(`adicionar_regra${String(k)}`)) {
                document.getElementById(`remover_regra${String(k)}`).outerHTML = `<button type="button" id="adicionar_regra${k}" class="btn btn-primary" onclick="createRules();">+</button>`;
            } else if (document.getElementById(`adicionar_regra${String(k)}`)) {
                document.getElementById(`remover_regra${String(k)}`).remove();
            }
        } else if (
            (rules_available_size > 1) && 
            (key_counter == rules_available_size) && 
            (!document.getElementById(`adicionar_regra${String(k)}`))
        ) {
            document.getElementById(`remover_regra${String(k)}`).outerHTML = `${document.getElementById(`remover_regra${String(k)}`).outerHTML}
            <button type="button" id="adicionar_regra${k}" class="btn btn-primary" onclick="createRules();">+</button>`;
        }

        key_counter += 1;
    }
}

function checkValues(current_index = null)
{
    let response = checkFields(current_index);
    let inactive_pdg = [];
    let active_pdg = [];
    let message_types = ["cross_current_msg", "cross_previous_msg", "current_msg"];
    let value_return = 0;
    for (let [k, v] of Object.entries(response)) {
        let index_number = -1;
        let index_type = "";
        if (k.search("cross_current_msg") > -1) {
            index_type = "cross_current_msg";
            index_number = k.substr(17);
        } else if (k.search("cross_previous_msg") > -1) {
            index_type = "cross_previous_msg";
            index_number = k.substr(18);
        } else if (k.search("current_msg") > -1) {
            index_type = "current_msg";
            index_number = k.substr(11);
        }

        message_types.forEach((current_type) => {
            const menor_que = document.getElementById("menor_que" + String(index_number));
            const maior_que = document.getElementById("maior_que" + String(index_number));
            const custo_mkt = document.getElementById("custo_mkt" + String(index_number));
            const custo_rma = document.getElementById("custo_rma" + String(index_number));
            const margem_frete = document.getElementById("margem_frete" + String(index_number));

            inactive_pdg[Number(index_number)] = Number(index_number);
            if ((index_type == current_type) && (v != "[NA]")) {
                value_return += 1;
                if (document.getElementById(k)) {
                    document.getElementById(k).innerHTML = v;
                    document.getElementById(k).style.display = 'block';
                }

                active_pdg[Number(index_number)] = Number(index_number);
            } else if ((index_type == current_type) && (v == "[NA]")) {
                if (document.getElementById(k)) {
                    document.getElementById(k).innerHTML = "";
                    document.getElementById(k).style.display = 'none';
                }
            }
        });
    }

    inactive_pdg.forEach((cindex) => {
        const pdg = `pdg${String(cindex)}`;
        if (document.getElementById(pdg)) {
            document.getElementById(`maior_que${String(cindex)}`).style.backgroundColor = "#fff";
            document.getElementById(`maior_que${String(cindex)}`).style.color = "#555";

            document.getElementById(`menor_que${String(cindex)}`).style.backgroundColor = "#fff";
            document.getElementById(`menor_que${String(cindex)}`).style.color = "#555";

            document.getElementById(`custo_mkt${String(cindex)}`).style.backgroundColor = "#fff";
            document.getElementById(`custo_mkt${String(cindex)}`).style.color = "#555";

            document.getElementById(`custo_rma${String(cindex)}`).style.backgroundColor = "#fff";
            document.getElementById(`custo_rma${String(cindex)}`).style.color = "#555";

            document.getElementById(`margem_frete${String(cindex)}`).style.backgroundColor = "#fff";
            document.getElementById(`margem_frete${String(cindex)}`).style.color = "#555";
        }
    });

    error_fields.forEach((cindex) => {
        let value_size = String(cindex).length;
        let line_value = 0;
        let row_value = 0;

        line_value = String(cindex).substring(0, Number(value_size) - 1);
        row_value = String(cindex).substring(Number(value_size) - 1);

        if (row_value == 1) {
            document.getElementById(`maior_que${String(line_value)}`).style.backgroundColor = "yellow";
            document.getElementById(`maior_que${String(line_value)}`).style.color = "#686868";
        } else if (row_value == 2) {
            document.getElementById(`menor_que${String(line_value)}`).style.backgroundColor = "yellow";
            document.getElementById(`menor_que${String(line_value)}`).style.color = "#686868";
        } else if (row_value == 3) {
            document.getElementById(`custo_mkt${String(line_value)}`).style.backgroundColor = "yellow";
            document.getElementById(`custo_mkt${String(line_value)}`).style.color = "#686868";
        } else if (row_value == 4) {
            document.getElementById(`custo_rma${String(line_value)}`).style.backgroundColor = "yellow";
            document.getElementById(`custo_rma${String(line_value)}`).style.color = "#686868";
        } else if (row_value == 5) {
            document.getElementById(`margem_frete${String(line_value)}`).style.backgroundColor = "yellow";
            document.getElementById(`margem_frete${String(line_value)}`).style.color = "#686868";
        }
    });

    return value_return;
}

function checkFields(field_index)
{
    if ((field_index != 0) && (!field_index)) {
        field_index = null;
    }

    var greater_than_current = -1, less_than_current = -1;
    var greater_than_previous = -1, less_than_previous = -1;
    var current_index = -1, previous_index = -1;

    // Gera o vetor de índices.
    var indices = [];
    for (let [k, v] of Object.entries(rules_available)) {
        if ((field_index !== null) && (k <= field_index)) {
            indices.push(k);
        } else if (field_index === null) {
            indices.push(k);
        }
    }

    // Caso um índice tenha sido informado, mantém somente dois valores no vetor.
    let finish = false;
    if (field_index !== null) {
        while (finish === false) {
            if (indices.length > 2) {
                indices.shift();
            } else if (indices.length <= 2) {
                finish = true;
            }
        }
        current_index = field_index;
    } else {
        current_index = indices[0];
    }

    // Encontra o valor do índice anterior.
    indices.forEach((cindex) => {
        if (cindex < current_index) {
            previous_index = Number(cindex);
        }
    });

    finish = false;
    while ((finish === false) && (current_index > -1)) {
        let current_msg = "[NA]";
        let previous_msg = "[NA]";
        let cross_current_msg = "[NA]";
        let cross_previous_msg = "[NA]";

        less_than_current = document.getElementById("menor_que" + String(current_index)).value;
        less_than_current = String(less_than_current).replace(".", "");
        less_than_current = String(less_than_current).replace(",", ".");

        greater_than_current = document.getElementById("maior_que" + String(current_index)).value;
        greater_than_current = String(greater_than_current).replace(".", "");
        greater_than_current = String(greater_than_current).replace(",", ".");

        if (Number(greater_than_current) < Number(less_than_current)) {
            //
        } else {
            current_msg = `O valor do campo "maior que" precisa ser menor do que o valor do campo "menor que".`;

            if (error_fields.indexOf(`${current_index}1`) == -1) {
                error_fields.push(`${current_index}1`);
            }

            if (error_fields.indexOf(`${current_index}2`) == -1) {
                error_fields.push(`${current_index}2`);
            }
        }

        if (
            (previous_index >= 0) &&
            (document.getElementById("menor_que" + String(previous_index))) &&
            (document.getElementById("maior_que" + String(previous_index)))
        ) {
            less_than_previous = document.getElementById("menor_que" + String(previous_index)).value;
            less_than_previous = String(less_than_previous).replace(".", "");
            less_than_previous = String(less_than_previous).replace(",", ".");

            greater_than_previous = document.getElementById("maior_que" + String(previous_index)).value;
            greater_than_previous = String(greater_than_previous).replace(".", "");
            greater_than_previous = String(greater_than_previous).replace(",", ".");

            if (Number(greater_than_previous) < Number(less_than_previous)) {
                //
            } else {
                previous_msg = `O valor do campo "maior que" precisa ser menor do que o valor do campo "menor que".`;

                if (error_fields.indexOf(`${previous_index}1`) == -1) {
                    error_fields.push(`${previous_index}1`);
                }

                if (error_fields.indexOf(`${current_index}1`) == -1) {
                    error_fields.push(`${current_index}1`);
                }

                if (error_fields.indexOf(`${previous_index}2`) == -1) {
                    error_fields.push(`${previous_index}2`);
                }

                if (error_fields.indexOf(`${current_index}2`) == -1) {
                    error_fields.push(`${current_index}2`);
                }
            }

            if (Number(less_than_previous) < Number(greater_than_current)) {
                //
            } else {
                cross_previous_msg = `Os valores informados na faixa de preços atual devem ser menores do que os valores informados na faixa de preços abaixo.`;
                cross_current_msg = `Os valores informados na faixa de preços atual devem ser maiores do que os valores informados na faixa de preços anterior.`;

                if (error_fields.indexOf(`${previous_index}1`) == -1) {
                    error_fields.push(`${previous_index}1`);
                }

                if (error_fields.indexOf(`${current_index}1`) == -1) {
                    error_fields.push(`${current_index}1`);
                }

                if (error_fields.indexOf(`${previous_index}2`) == -1) {
                    error_fields.push(`${previous_index}2`);
                }

                if (error_fields.indexOf(`${current_index}2`) == -1) {
                    error_fields.push(`${current_index}2`);
                }
            }
        }

        if ((current_msg == '[NA]') && (cross_current_msg == '[NA]')) {
            let current_el = error_fields.indexOf(`${current_index}1`);
            if (current_el > -1) {
                error_fields.splice(current_el, 1);
            }

            current_el = error_fields.indexOf(`${current_index}2`);
            if (current_el > -1) {
                error_fields.splice(current_el, 1);
            }
        }

        let filled_index = 0;
        let not_filled = "É necessário preencher: ";
        if (String(greater_than_current) == "") {
            filled_index += 1;
            not_filled += `campo "Maior que"`;

            if (error_fields.indexOf(`${current_index}1`) == -1) {
                error_fields.push(`${current_index}1`);
            }
        }

        if (String(less_than_current) == "") {
            if (filled_index > 0) {
                not_filled += `; `;
            } else {
                filled_index += 1;
            }
            not_filled += `campo "Menor que"`;

            if (error_fields.indexOf(`${current_index}2`) == -1) {
                error_fields.push(`${current_index}2`);
            }
        }

        const custo_mkt = document.getElementById("custo_mkt" + String(current_index)).value;
        if (String(custo_mkt) == "") {
            if (filled_index > 0) {
                not_filled += `; `;
            } else {
                filled_index += 1;
            }
            not_filled += `campo "Custo Marketplace"`;

            if (error_fields.indexOf(`${current_index}3`) == -1) {
                error_fields.push(`${current_index}3`);
            }
        } else {
            let current_el = error_fields.indexOf(`${current_index}3`);
            if (current_el > -1) {
                error_fields.splice(current_el, 1);
            }
        }

        const custo_rma = document.getElementById("custo_rma" + String(current_index)).value;
        if (String(custo_rma) == "") {
            if (filled_index > 0) {
                not_filled += `; `;
            } else {
                filled_index += 1;
            }
            not_filled += `campo "Custo RMA"`;

            if (error_fields.indexOf(`${current_index}4`) == -1) {
                error_fields.push(`${current_index}4`);
            }
        } else {
            let current_el = error_fields.indexOf(`${current_index}4`);
            if (current_el > -1) {
                error_fields.splice(current_el, 1);
            }
        }

        const margem_frete = document.getElementById("margem_frete" + String(current_index)).value;
        if (String(margem_frete) == "") {
            if (filled_index > 0) {
                not_filled += `; `;
            } else {
                filled_index += 1;
            }
            not_filled += `campo "Margem de Frete"`;

            if (error_fields.indexOf(`${current_index}5`) == -1) {
                error_fields.push(`${current_index}5`);
            }
        } else {
            let current_el = error_fields.indexOf(`${current_index}5`);
            if (current_el > -1) {
                error_fields.splice(current_el, 1);
            }
        }

        if (filled_index) {
            current_msg = `${not_filled}.`;
        }

        messages[`current_msg${current_index}`] = current_msg;
        messages[`cross_current_msg${current_index}`] = cross_current_msg;
        if (previous_index >= 0) {
            messages[`cross_previous_msg${previous_index}`] = cross_previous_msg;
        }

        const last_index = indices[indices.length - 1];
        if ((field_index !== null) || (current_index == last_index)) {
            finish = true;
        } else if ((field_index === null) && (current_index == last_index)) {
            finish = true;
        } else if ((field_index === null) && (current_index < last_index)) {
            let found = false;
            // Encontra os valores do próximo índice e do índice anterior a ele (isto é, o índice atual).
            indices.forEach((cindex) => {
                if ((cindex > current_index) && !found) {
                    previous_index = current_index;
                    current_index = Number(cindex);
                    found = true;
                }
            });
        }
    }

    return messages;
}

function createRules()
{
    let current_key = Number(Object.keys(rules_available).pop());
    let object_size = Object.keys(rules_available).length;
    if (object_size == 1) {
        document.getElementById("adicionar_regra" + String(current_key)).outerHTML = `<button type="button" id="remover_regra${current_key}" class="btn btn-primary" onclick="deleteRules(${current_key});">-</button>`;
    }

    let next_index = Number(Object.keys(rules_available).pop()) + 1;
    rules_available[next_index] = 'rule-' + String(next_index);

    let next_range = `
    <div class="row" id="rule-${next_index}">
        <!-- Maior que -->
        <div class="col-md-2" style="padding-bottom: 6px;">
            <input type="text" id="maior_que${next_index}" class="form-control" placeholder="R$">
        </div>

        <!-- Menor que -->
        <div class="col-md-2">
            <input type="text" id="menor_que${next_index}" class="form-control" placeholder="R$" onblur="checkFields(${next_index});">
        </div>

        <!-- Custo Marketplace -->
        <div class="col-md-2">
            <input type="text" id="custo_mkt${next_index}" class="form-control" placeholder="%"">
        </div>

        <!-- Custo RMA -->
        <div class="col-md-2">
            <input type="text" id="custo_rma${next_index}" class="form-control" placeholder="%">
        </div>

        <!-- Margem de Frete -->
        <div class="col-md-2">
            <input type="text" id="margem_frete${next_index}" class="form-control" placeholder="%">
        </div>

        <!-- Ação -->
        <div class="col-md-2">
            <button type="button" id="remover_regra${next_index}" class="btn btn-primary" onclick="deleteRules(${next_index});">-</button>
            <button type="button" id="adicionar_regra${next_index}" class="btn btn-primary" onclick="createRules();">+</button>
        </div>
    </div>

    <div id="current_msg${next_index}" style="color: red; padding-bottom: 6px; font-weight: bold; display: none;"></div>
    <div id="cross_current_msg${next_index}" style="color: red; padding-bottom: 6px; font-weight: bold; display: none;"></div>
    <div id="cross_previous_msg${next_index}" style="color: red; padding-bottom: 6px; font-weight: bold; display: none;"></div>
    <div id="pdg${next_index}" style="padding-bottom: 25px; display: none;"></div>

    <div id="next"></div>`;

    document.getElementById("next").outerHTML = next_range;

    if (object_size > 1) {
        let previous_rule = Object.keys(rules_available)[Object.keys(rules_available).length - 2];
        document.getElementById("adicionar_regra" + String(previous_rule)).remove();
    }
}

function saveRules()
{
    let values_error = checkValues();

    // Alguma transportadora foi selecionada?
    let shipping = "";
    let indices = $('#shipping_companies').val();
    for (index = 0; index < indices.length; index++) {
        if (!shipping) {
            shipping += indices[index];
        } else {
            shipping += ";" + indices[index];
        }
    }

    let shipping_companies_filled = true;
    if (!shipping) {
        shipping_companies_filled = false;

        let sc_selector = document.querySelectorAll('button[title="Selecione..."]');
        if (sc_selector.length >= 1) {
            sc_selector[0].style.backgroundColor = "yellow";
            sc_selector[0].style.color = '#686868';
            sc_selector[0].id = 'shipping_companies_select';
        }
    } else if (document.getElementById('shipping_companies_select')) {
        document.getElementById('shipping_companies_select').style.backgroundColor = "#f0f0f0";
        document.getElementById('shipping_companies_select').style.color = "#686868";
    }

    // Algum canal de vendas foi selecionado?
    let channels = "";
    indices = $('#mkt_channels').val();
    for (index = 0; index < indices.length; index++) {
        if (!channels) {
            channels += indices[index];
        } else {
            channels += ";" + indices[index];
        }
    }

    let channels_filled = true;
    if (!channels) {
        channels_filled = false;

        let market_channels_index = 0;
        let mc_selector = document.querySelectorAll('button[title="Selecione..."]');
        if (mc_selector.length == 2) {
            market_channels_index = 1;
        }

        if (mc_selector.length >= 1) {
            mc_selector[market_channels_index].style.backgroundColor = "yellow";
            mc_selector[market_channels_index].style.color = '#686868';
            mc_selector[market_channels_index].id = 'market_channels_select';
        }
    } else if (document.getElementById('market_channels_select')) {
        document.getElementById('market_channels_select').style.backgroundColor = "#f0f0f0";
        document.getElementById('market_channels_select').style.color = "#686868";
    }

    price_range = true;
    // Todos os campos das regras foram preenchidos?
    for (let [k, v] of Object.entries(rules_available)) {
        let maior_que = String(document.getElementById(`maior_que${k}`).value).replace(".", "");
        maior_que = String(maior_que).replace(",", ".");

        let menor_que = String(document.getElementById(`menor_que${k}`).value).replace(".", "");
        menor_que = String(menor_que).replace(",", ".");

        let custo_mkt = String(document.getElementById(`custo_mkt${k}`).value).replace(".", "");
        custo_mkt = String(custo_mkt).replace(",", ".");

        let custo_rma = String(document.getElementById(`custo_rma${k}`).value).replace(".", "");
        custo_rma = String(custo_rma).replace(",", ".");

        let margem_frete = String(document.getElementById(`margem_frete${k}`).value).replace(".", "");
        margem_frete = String(margem_frete).replace(",", ".");

        if ((maior_que == "") || (menor_que == "") || (custo_mkt == "") || (custo_rma == "") || (margem_frete == "")) {
            price_range = false;
        } else if (price_range !== false) {
            if (price_range === true) {
                price_range = "";
            } else {
                price_range += ";";
            }

            price_range += `${maior_que},${menor_que},${custo_mkt},${custo_rma},${margem_frete}`;
        }
    }

    if (!shipping_companies_filled || !channels_filled || !price_range) {
        Swal.fire('É necessário preencher todos os campos.');
    } else if (shipping_companies_filled && channels_filled && !values_error && price_range) {
        let rules = {
            'rule_index': rule_index,
            'shipping': shipping,
            'channels': channels,
            'range': price_range
        };

        let url = `${base_url}Shippingpricingrules/saveRules`;
        $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: rules,
            async: true,
            success: function(response) {
                console.log('Configurações salvas com sucesso.');
                Swal.fire('Configurações salvas com sucesso.');
            },
            error: function() {
                console.log('Erro ao tentar salvar as configurações.');
                Swal.fire('Erro ao tentar salvar as configurações.');
            },
        });
    }
}

function loadRules(id)
{
    let rule_id = {
        'id': id
    };

    let url = `${base_url}Shippingpricingrules/loadRule`;
    $.ajax({
        url: url,
        type: 'post',
        dataType: 'json',
        data: rule_id,
        async: true,
        success: function(response) {
            let res = response;

            console.log('Configurações carregadas com sucesso.');
            document.getElementById("rules").innerHTML = res;

            let cont = 0;
            let finish = false;
            while (finish === false) {
                if (document.getElementById(`rule-${String(cont)}`)) {
                    rules_available[Number(cont)] = `rule-${String(cont)}`;
                } else {
                    finish = true;
                }
                cont += 1;
            }
        },
        error: function() {
            console.log('Erro ao tentar carregar as configurações.');
            Swal.fire('Erro ao tentar carregar as configurações.');
        },
        complete: () => {
            $('#shipping_companies').selectpicker();
            $('#mkt_channels').selectpicker();
        }
    });
}
</script>
