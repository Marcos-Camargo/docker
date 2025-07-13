<?php include_once(APPPATH . '/third_party/zipcode.php'); ?>
<div class="content-wrapper">

    <?php $data['pageinfo'] = 'dashboard';  $this->load->view('templates/content_header',$data); ?>

    <section class="content">
        <!-- CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/css/bootstrap-select.min.css" />

        <!-- JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script>
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger">
                        <?= $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>
                <div class="alert alert-info">
                    Os dados configurados nessa tela serão utilizados apenas quando o pedido for atualizado para entregue caso não estejam preenchidos no backoffice do seller. Todas as datas de atualização de pedidos serão preenchidas com a data de mudança do status no sistema.
                </div>

                <div class="box box-primary">
                    <form role="form" method="post" id="formOrderToDelivered" action="<?= base_url('orderToDelivered/save') ?>">
                        <div class="form-group col-md-6 col-xs-12">
                            <label for="marketplace">Marketplace</label>
                            <select class="form-control" id="marketplace" name="marketplace">
                                <?php foreach ($marketplaces as $marketplace): ?>
                                    <?php $value = $marketplace->marketplace; ?>
                                    <option value="<?= $marketplace->int_to ?>" <?= $value == ($_GET['marketplace'] ?? $marketplace->int_to) ? 'selected' : '' ?>>
                                        <?= $marketplace->marketplace ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6 col-xs-12">
                            <label for="lojas">Lojas</label>
                            <div class="dropdown" id="dropdownLojas">
                                <button type="button" class="form-control text-left" onclick="toggleDropdownLojas()" id="btnLojas">
                                    Selecionado <?= count($lojas) ?> de <?= count($lojas) ?>
                                </button>
                                <div class="dropdown-menu" style="display: none; padding: 10px; width: 100%; max-height: 300px; overflow-y: auto;">
                                    <input type="text" class="form-control" placeholder="Buscar..." onkeyup="filtrarLojas(this)">
                                    <!-- Botões -->
                                    <div style="display: flex; margin: 10px 0;">
                                        <button type="button" class="btn btn-default" style="flex: 1; border-radius: 0;" onclick="selecionarTodasLojas()">Selecionar Todos</button>
                                        <button type="button" class="btn btn-default" style="flex: 1; border-radius: 0;" onclick="desmarcarTodasLojas()">Desmarcar Todos</button>
                                    </div>
                                    <div id="listaLojas">
                                        <?php foreach ($lojas as $loja): ?>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="lojas[]" value="<?= $loja['id'] ?>" <?= $loja['has_order_to_send_config'] == 1 ? 'checked' : '' ?> onchange="atualizarContadorLojas()">
                                                    <?= $loja['name'] ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <small>Lojas que estão configuradas para utilizar essa funcionalidade</small>
                        </div>

                        <div class="form-group col-md-6 col-xs-12">
                            <label for="dias">Atualizar apenas pedidos criados a mais de quantos dias?<span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="dias" name="dias"
                                value="<?= $config['dias_para_atualizar'] ?? 2 ?>">
                        </div>

                        <!-- Painel: Dados de Faturamento -->
                        <div class="col-md-12 form-group">
                            <div class="panel panel-primary">
                                <div class="panel-heading">Dados de faturamento</div>
                                <div class="panel-body">
                                    <div class="form-group col-md-6 col-xs-12">
                                        <label for="url-consulta">URL de consulta</label>
                                        <input type="text" class="form-control" id="url-consulta" name="url-consulta"
                                            value="<?= $config['url_consulta'] ?? '' ?>">
                                    </div>
                                    <div class="form-group col-md-6 col-xs-12">
                                        <label>
                                            <input type="checkbox" id="sequencial" name="sequencial"
                                                <?= isset($config['sequencial_nfe']) && $config['sequencial_nfe'] ? 'checked' : '' ?>>
                                            Preencher dados de Nota Fiscal (Chave, n°, série, etc)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Painel: Dados de Rastreio e Entrega -->
                        <div class="col-md-12 form-group">
                            <div class="panel panel-primary">
                                <div class="panel-heading">Dados de rastreio e entrega</div>
                                <div class="panel-body">
                                    <div class="form-group col-md-6 col-xs-12">
                                        <label for="transportadora">Transportadora</label>
                                        <input type="text" class="form-control" id="transportadora" name="transportadora" value="<?= $config['transportadora'] ?? '' ?>">
                                    </div>
                                    <div class="form-group col-md-6 col-xs-12">
                                        <label for="metodo-envio">Método de envio</label>
                                        <input type="text" class="form-control" id="metodo-envio" name="metodo-envio" value="<?= $config['metodo_envio'] ?? '' ?>">
                                    </div>
                                    <div class="form-group col-md-6 col-xs-12">
                                        <label for="codigo-rastreio">Código de rastreio</label>
                                        <input type="text" class="form-control" id="codigo-rastreio" name="codigo-rastreio" value="<?= $config['codigo_rastreio'] ?? '' ?>">
                                    </div>
                                    <div class="form-group col-md-6 col-xs-12">
                                        <label for="url-rastreio">URL de rastreio</label>
                                        <input type="text" class="form-control" id="url-rastreio" name="url-rastreio" value="<?= $config['url_rastreio'] ?? '' ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                </div> <!-- /.box-body -->

                <div class="form-group col-md-12" >
                            <button type="submit" class="btn btn-primary" >Salvar Alterações</button>
                            <a href="<?= base_url('dashboard') ?>" class="btn btn-warning" >Voltar</a>
                        </div>
                    </form>
                </div> <!-- /.box -->
            </div> <!-- /.col -->
        </div> <!-- /.row -->
    </section>
</div>

<script>
    const lojasPorMarketplace = <?= json_encode($lojas_por_marketplace ?? [], JSON_UNESCAPED_UNICODE) ?>;

    function renderizarLojas(marketplaceKey) {
        const lista = document.getElementById('listaLojas');
        lista.innerHTML = '';

        const lojas = lojasPorMarketplace[marketplaceKey] || [];

        lojas.forEach(loja => {
            const div = document.createElement('div');
            div.classList.add('checkbox');
            div.innerHTML = `
                <label>
                    <input type="checkbox" name="lojas[]" value="${loja.id}" ${loja.has_order_to_send_config == 1 ? 'checked' : ''} onchange="atualizarContadorLojas()">
                    ${loja.name}
                </label>
            `;
            lista.appendChild(div);
        });

        atualizarContadorLojas();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const marketplaceSelect = document.getElementById('marketplace');
        renderizarLojas(marketplaceSelect.value);

        marketplaceSelect.addEventListener('change', function() {
            renderizarLojas(this.value);
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const configs = <?= json_encode($configs ?? [], JSON_UNESCAPED_UNICODE) ?>;

        const marketplaceSelect = document.getElementById('marketplace');
        const diasInput = document.getElementById('dias');
        const urlConsultaInput = document.getElementById('url-consulta');
        const sequencialCheckbox = document.getElementById('sequencial');
        const transportadoraInput = document.getElementById('transportadora');
        const metodoEnvioInput = document.getElementById('metodo-envio');
        const codigoRastreioInput = document.getElementById('codigo-rastreio');
        const urlRastreioInput = document.getElementById('url-rastreio');

        function applyConfig(config) {
            diasInput.value = config?.dias_para_atualizar ?? 2;
            urlConsultaInput.value = config?.url_consulta ?? '';
            sequencialCheckbox.checked = config?.sequencial_nfe == 1;
            transportadoraInput.value = config?.transportadora ?? '';
            metodoEnvioInput.value = config?.metodo_envio ?? '';
            codigoRastreioInput.value = config?.codigo_rastreio ?? '';
            urlRastreioInput.value = config?.url_rastreio ?? '';
        }

        function updateFormForSelectedMarketplace() {
            const selected = marketplaceSelect.value;
            if (configs[selected]) {
                applyConfig(configs[selected]);
            } else {
                applyConfig({}); // limpa
            }
        }

        updateFormForSelectedMarketplace(); // ao carregar
        marketplaceSelect.addEventListener('change', updateFormForSelectedMarketplace);
    });

    function abrirListaLojas() {
        document.getElementById('lista-lojas').style.display = 'block';
    }

    function fecharListaLojas() {
        document.getElementById('lista-lojas').style.display = 'none';
    }

    function toggleDropdownLojas() {
        const menu = document.querySelector('#dropdownLojas .dropdown-menu');
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }

    function atualizarContadorLojas() {
        const total = document.querySelectorAll('#listaLojas input[type="checkbox"]').length;
        const selecionadas = document.querySelectorAll('#listaLojas input[type="checkbox"]:checked').length;
        document.getElementById('btnLojas').innerText = `Selecionado ${selecionadas} de ${total}`;
    }

    function selecionarTodasLojas() {
        document.querySelectorAll('#listaLojas input[type="checkbox"]').forEach(cb => cb.checked = true);
        atualizarContadorLojas();
    }

    function desmarcarTodasLojas() {
        document.querySelectorAll('#listaLojas input[type="checkbox"]').forEach(cb => cb.checked = false);
        atualizarContadorLojas();
    }

    function filtrarLojas(input) {
        const termo = input.value.toLowerCase();
        document.querySelectorAll('#listaLojas .checkbox').forEach(div => {
            const texto = div.innerText.toLowerCase();
            div.style.display = texto.includes(termo) ? '' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', atualizarContadorLojas);
</script>
</div>