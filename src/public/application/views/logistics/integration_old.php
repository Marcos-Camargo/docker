<div class="content-wrapper">
    <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>
    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <?php if($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#manage_logistics" data-toggle="tab"><?=$this->lang->line('application_manage_integrations')?></a></li>
                        <li><a href="#logistics_seller" data-toggle="tab"><?=$this->lang->line('application_manage_integrations_stores')?></a></li>
                    </ul>
                    <div class="tab-content col-md-12">
                        <div class="tab-pane active" id="manage_logistics">
                            <div class="row">
                                <div class="col-md-6 div-logistics">
                                    <div class="callout callout-success">
                                        <h4><i class="fa fa-building"></i> Logística do Seller Center</h4>
                                        <p>Disponibilize a logística do seller center para que as lojas utilizem esse tipo de logística.</p>
                                    </div>
                                    <hr>
                                    <div id="content_logistic_seller_center">
                                        <div class="col-md-12 form-group">
                                            <label for="integration_seller_center">Selecione a integração para uso</label>
                                            <div class="input-group">
                                                <select class="form-control select2-img" id="integration_seller_center"></select>
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-primary btn-flat" id="btnSelectIntegrationSellerCenter">Adicionar</button>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-12 form-group" style="display: none">
                                            <h4 class="border-bottom-integrations">Integrações Selecionadas</h4>
                                            <div id="listIntegrationsSellerCenter">
                                                <div class="overlay">
                                                    <i class="fa fa-refresh fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="callout callout-success">
                                        <h4><i class="fa fa-user"></i> Logística do Seller</h4>
                                        <p>Selecione as integrações de logística com que as lojas poderão integrar com suas credenciais.</p>
                                    </div>
                                    <div class="col-md-12 form-group d-none">
                                        <label for="logistic_seller" class="d-flex align-items-center">
                                            <input type="checkbox" class="icheck" id="logistic_seller"> <h4 class="ml-2">Logística Seller</h4>
                                        </label>
                                    </div>
                                    <hr>
                                    <div id="content_logistic_seller">
                                        <div class="col-md-12 form-group">
                                            <label for="integration_seller">Selecione a integração para uso ao seller</label>
                                            <div class="input-group">
                                                <select class="form-control select2-img" id="integration_seller"></select>
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-primary btn-flat" id="btnSelectIntegrationSeller">Adicionar</button>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-12 form-group" style="display: none">
                                            <h4 class="border-bottom-integrations">Integrações Selecionadas</h4>
                                            <div id="listIntegrationsSeller">
                                                <div class="overlay">
                                                    <i class="fa fa-refresh fa-spin"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="logistics_seller">

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="callout callout-success">
                                        <h4><i class="fa fa-truck"></i> Definições logísticas por loja</h4>
                                        <p>Defina qual método logístico cada seller usará para a venda nos marketplaces.</p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Loja</label>
                                        <select class="form-control" name="stores">
                                            <option value="0">Selecione</option>
                                            <?php foreach ($stores as $store) {
                                                echo "<option value='{$store['id']}'>{$store['name']}</option>";
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row" id="methodLogistic">
                                <div class="col-md-12 d-flex justify-content-between flex-wrap">
                                    <h3 class="mb-4">Selecione o métodos logístico para o seller</h3>
                                    <div>
                                    <button type="button" class="btn" id="activeModuloFrete"></button>
                                    <button type="button" class="btn btn-danger" id="removeIntegrationSelected"><i class="fa fa-trash"></i> Excluir Integração da Loja</button>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div id="listIntegrationSellerCenter" class="col-md-6 div-logistics">
                                        <h4 class="mb-3">Integrações Seller Center</h4>
                                        <div class="overlay">
                                            <i class="fa fa-refresh fa-spin"></i>
                                        </div>
                                    </div>
                                    <div id="listIntegrationSeller" class="col-md-6">
                                        <h4 class="mb-3">Integrações Seller</h4>
                                        <div class="overlay">
                                            <i class="fa fa-refresh fa-spin"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<style>
    div[id*="listIntegrationsSeller"] {
        padding: 0;
    }
    div[id*="listIntegrationsSeller"] div[integration] {
        background: #ddd;
        padding: 5px;
        border: 1px solid #999;
        margin-top: 4px;
        cursor: pointer;
    }
    .border-bottom-integrations {
        border-bottom: 1px solid #999;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .panel-collapse {
        border: 1px solid #999;
    }
    div[id*="listIntegrations"] .overlay,
    div[id*="listIntegration"] .overlay {
        z-index: 50;
        background: rgba(255,255,255,0.7);
        border-radius: 3px;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: none;
        justify-content: center;
    }
    div[id*="listIntegrations"] .overlay i,
    div[id*="listIntegration"] .overlay i {
        display: flex;
        justify-content: center;
        align-self: center;
        font-size: 30px;
    }
    .swal2-content ul {
        list-style-type: none;
        padding: 0;
    }
    #methodLogistic {
        display: none;
    }
    #methodLogistic label {
        cursor: pointer;
    }
    .content_seller_use label[for^="integration_seller_use_"],
    .content_seller_use label[for^="integration_sellercenter_use_"]{
        width: 100%;
        border: 1px solid #aaa;
        padding: 5px;
        margin-bottom: 0px;
    }
    @media (min-width: 992px) {
        .div-logistics {
            border-right: 1px solid #ccc;
        }
    }

    .swal2-title {
        word-break: break-word;
    }
</style>
<script type="application/javascript" src="<?=base_url('assets/dist/js/pages/logistic.js')?>"></script>
<script>
    var baseUrl = "<?=base_url()?>";
    var viewAlertChangeIntegrationStore = false;
    var storeHaveIntegration = false;

    let integrationSelectedValue = null;
    let integrationSelectedType  = null;

    $(function(){
        $('#mainLogisticsNav').addClass('active');
        $('#manageLogisticIntegrationsNav').addClass('active');

        $('.icheck').iCheck({
            checkboxClass: 'icheckbox_square-blue',
            radioClass: 'iradio_square',
            increaseArea: '20%' // optional
        });

        $('.select2-img').select2({
            templateResult: getImageIntegration,
            templateSelection: getImageIntegration
        });

        $('[name="stores"]').select2();

        getComboSelectIntegrationSeller();
        getComboSelectIntegrationSellerCenter();
        getIntegrationsSellerCenterInUse();
        getIntegrationsSellerInUse();
    });

    $('#btnSelectIntegrationSellerCenter, #btnSelectIntegrationSeller').on('click', async function (){
        const idEl = $(this).attr('id');
        const integration = $(idEl === 'btnSelectIntegrationSeller' ? '#integration_seller' : '#integration_seller_center').val();

        let createElement;

        //save integration seller
        let stopApp = false;
        if (idEl === 'btnSelectIntegrationSeller') {
            await $.post(`${baseUrl}/logistics/saveIntegration`, { integration, data: null, type: 'seller' }, response => {
                Swal.fire({
                    icon: response.success ? 'success' : 'error',
                    title: response.data,
                    confirmButtonText: "Ok",
                });
                $('.overlay').hide();

                if (response.success) {
                    $(`#${integration}`).collapse('hide')
                } else stopApp = true;
            }).fail(e => {
                console.log(e);
            });
        }

        if (stopApp) {
            return false;
        }

        if (integration === 'select') {
            return false;
        }

        if ($(idEl === 'btnSelectIntegrationSeller' ? `#listIntegrationsSeller div[integration="${integration}"]` : `#listIntegrationsSellerCenter div[integration="${integration}"]`).length) {
            Swal.fire({
                icon: 'error',
                title: "Essa integração já foi selecionada."
            });
            return false;
        }

        createElement = await createElementIntegration(integration, idEl === 'btnSelectIntegrationSeller');

        $(idEl === 'btnSelectIntegrationSeller' ? '#listIntegrationsSeller' : '#listIntegrationsSellerCenter').append(createElement);

        // $(idEl === 'btnSelectIntegrationSeller' ? '#integration_seller' : '#integration_seller_center')
        //     .val('select')
        //     .select2('destroy')
        //     .select2({
        //         templateResult: getImageIntegration,
        //         templateSelection: getImageIntegration
        //     });

        $('.icheck_integration_selected').iCheck({
            checkboxClass: 'icheckbox_square-blue',
            radioClass: 'iradio_square-blue',
            increaseArea: '20%' // optional
        });

        setTimeout(() => {
            if (idEl === 'btnSelectIntegrationSellerCenter')
                $(`#${integration}`).collapse('show').find('[name*="token"]').focus();
        }, 750);

        hideShowIntegrationsSelected(idEl === 'btnSelectIntegrationSeller');
        if (idEl === 'btnSelectIntegrationSeller') {
            getComboSelectIntegrationSeller();
            getComboSelectIntegrationSellerCenter();
        }
    });

    $(document).on('click', '.btnRemoveIntegration', function (){
        const elCard         = $(this).closest('div[integration]');
        const integration    = elCard.attr('integration');
        const sellerCenter   = elCard.closest('div[id*="listIntegrationsSeller"]').attr('id') === 'listIntegrationsSellerCenter';
        const imgIntegration = elCard.find('img').attr('src');
        const htmlAlert      = sellerCenter ? '' : '<h4 class="font-weight-bold text-red">Ao excluir a integração, todos os sellers que estão integrados consequentemente perderão sua integração.</h4><br/>';

        if ($(`div[integration="${integration}"] h4`).length) {
            elCard.slideUp(500);
            $(`div#${integration}`).remove();

            setTimeout(() => {
                elCard.remove();
                hideShowIntegrationsSelected(!sellerCenter);
            }, 600);
            return false;
        }

        $(`#${integration}`).hide();

        setTimeout(() => {$(`#${integration}`).removeClass('in').removeAttr('style') }, 500);

        Swal.fire({
            title: 'Tem certeza que deseja excluir a integração?',
            html : `<img height="30px" src="${imgIntegration}">` + htmlAlert,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Excluir Integração',
            cancelButtonText: 'Cancelar Operação'
        }).then((result) => {
            if (result.value) {
                $.post(`${baseUrl}/logistics/removeIntegration`, { integration, type: sellerCenter ? 'sellercenter' : 'seller' }, response => {
                    Swal.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.data,
                        confirmButtonText: "Ok",
                    });
                    if (response.success) {

                        elCard.slideUp(500);
                        $(`div#${integration}`).remove();

                        setTimeout(() => {
                            elCard.remove();
                            hideShowIntegrationsSelected(!sellerCenter);
                        }, 600);

                        getComboSelectIntegrationSeller();
                        getComboSelectIntegrationSellerCenter();
                    }
                });
            } else if (result.dismiss === 'cancel') {
                Swal.fire({
                    icon: 'error',
                    title: 'Operação cancelada',
                    confirmButtonText: "Ok",
                });
            }
        });
    });

    $(document).on('click', '.saveIntegration', async function (){

        const integration = $(this).closest('.panel-collapse').attr('id');
        // disabled fields
        $(this).closest(`div[id^="listIntegrations"]`).find('.overlay').css('display', 'flex');

        // get data inputs
        let name, value, checked, label;
        let data = [];
        let msgError = [];
        await $(this).closest('.panel-collapse').find('[name]').each(function() {
            name    = $(this).attr('name');
            value   = $(this).val();
            checked = $(this).is(':checked');
            label   = $(this).closest('.form-group').find('label').text();

            if (($(this).attr('type') !== 'radio' && $(this).attr('type') !== 'checkbox') || checked) {
                if (value === '') {
                    msgError.push(`Campo ${label} precisa ser preenchido`);
                }
                data.push({name, value});
            }
        });

        if (msgError.length) {
            Swal.fire({
                icon: 'error',
                title: 'Não foi possível salvar a integração',
                html: '<ul><li>' + msgError.join('</li><li>') + '</li></ul>'
            });
            $('.overlay').hide();
            return false;
        }

        // request ajax save data
        await $.post(`${baseUrl}/logistics/saveIntegration`, { integration, data, type: 'sellercenter' }, response => {
            Swal.fire({
                icon: response.success ? 'success' : 'error',
                title: response.data,
                confirmButtonText: "Ok",
            });
            $('.overlay').hide();

            if (response.success) {
                $(`#${integration}`).collapse('hide');
                $(`div[integration="${integration}"] h4`).remove();
                getComboSelectIntegrationSeller();
                getComboSelectIntegrationSellerCenter();
            }
        }).fail(e => {
            console.log(e);
        });
    });

    $(document).on('click', '.saveIntegration_card_seller', async function (){

        const integration = $(this).closest('.panel-collapse').attr('id').replace('_card_seller', '');
        // disabled fields
        $(this).closest('div[id*="listIntegration"]').find('.overlay').css('display', 'flex');

        const integrationValue = $(this).closest('.content_seller_use').find('input[name="integration_seller_use"]').val();
        const integrationType = 'seller';

        // request ajax save data
        await $.post(`${baseUrl}/logistics/saveIntegrationSeller`, { integration, integrationType, data: '{}', store: $('[name="stores"]').val() }, response => {
            Swal.fire({
                icon: response.success ? 'success' : 'error',
                title: response.data,
                confirmButtonText: "Ok",
            });

            $('.overlay').hide();

            if (response.success) {
                $(`#${integration}`).collapse('hide');
                $(`div[integration="${integration}"] h4`).remove();
            }

            integrationSelectedType = integrationType;
            integrationSelectedValue = integrationValue;
            $('#removeIntegrationSelected').prop('disabled', false);
        }).fail(e => {
            console.log(e);
        });
    });

    $('[name="stores"]').change(async function(){

        integrationSelectedValue = null;
        integrationSelectedType  = null;

        const btn = $(this);

        btn.prop('disabled', true);

        $('#methodLogistic').slideDown('slow');
        const sellerId = parseInt($(this).val());
        let response;

        $('#listIntegrationSellerCenter div:not(.overlay)').remove();
        $('#listIntegrationSeller div:not(.overlay)').remove();

        if (sellerId === 0) {
            $('#methodLogistic').slideUp('slow');
            btn.prop('disabled', false);
            return false;
        }

        response = await $.ajax({
            url: `${baseUrl}/logistics/getIntegrationSeller/${sellerId}`,
            async: true,
            type: 'GET',
        });

        $('#activeModuloFrete')
            .text(response.itsStoresModuloFrete ? 'Inativar Módulo Frete' : 'Ativar Módulo Frete')
            .removeClass('btn-success btn-danger')
            .addClass(response.itsStoresModuloFrete ? 'btn-danger' : 'btn-success');

        $("select[name=status]").val(0);


        for await (let [key, value] of Object.entries(response.integrationsSellerCenter)) {
            await createMethodLogistic($('#listIntegrationSellerCenter'), value.name, false);
        }
        for await (let [key, value] of Object.entries(response.integrationsSeller)) {
            await createMethodLogistic($('#listIntegrationSeller'), value.name);
        }

        const lengthIntegration = Object.getOwnPropertyNames(response.integrationSeller).length;

        storeHaveIntegration = lengthIntegration ? true : false;

        if(lengthIntegration) {

            const credentials = response.integrationSeller.credentials;
            let typeElement;

            if (credentials !== null) {
                $(`#${response.integrationSeller.integration}_card_seller`).collapse('show');
                $(`#integration_seller_use_${response.integrationSeller.integration}`).iCheck('check');
            } else
                $(`#integration_sellercenter_use_${response.integrationSeller.integration}`).iCheck('check');

            if (credentials !== null) {
                Object.keys(credentials).forEach(function (key) {
                    typeElement = $(`#${response.integrationSeller.integration}_card_seller [name="${key}"]`).attr('type');
                    if (typeElement !== 'radio' && typeElement !== 'checkbox')
                        $(`#${response.integrationSeller.integration}_card_seller [name="${key}"]`).val(credentials[key]);
                    else
                        $(`#${response.integrationSeller.integration}_card_seller [name="${key}"][value="${credentials[key]}"]`).iCheck('check');
                });
            }
            $('#removeIntegrationSelected').prop('disabled', false);
        } else {
            $('#removeIntegrationSelected').prop('disabled', true);
        }

        $('#methodLogistic').slideDown('slow');
        btn.prop('disabled', false);

    });

    $(document).on('ifChecked', '.icheck_integration_user', function(){
        $('div.panel-collapse[id*="_card_seller"]').collapse('hide');

        if ($(this).closest('.content_seller_use').find('div.panel-collapse').length) {
            $(this).closest('.content_seller_use').find('div.panel-collapse').collapse('show');
        }
    });

    $(document).on('ifChecked', '#listIntegrationSeller .icheck_integration_user', function(){

        let integrationValue = $(this).val();
        let integrationType  = 'seller';

        if (integrationSelectedType === null) {
            integrationSelectedType = integrationType;
            integrationSelectedValue = integrationValue;
        }
    });

    $(document).on('ifChecked', '#listIntegrationSellerCenter .icheck_integration_user', function(){

        const integration = $(this).val();
        const store = $('[name="stores"]').val();
        const divIntegration = $(this).closest('div[id*="listIntegration"]');
        let integrationValue = $(this).val();
        let integrationType  = 'sellercenter';

        //console.log([integrationValue, integrationType], [integrationSelectedValue, integrationSelectedType]);

        if (integrationSelectedType !== null || !storeHaveIntegration) {

            if (integrationValue === integrationSelectedValue && integrationType === integrationSelectedType) {
                return true;
            }

            Swal.fire({
                title: 'Tem certeza que deseja alterar o método logístico?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#f39c12',
                confirmButtonText: 'Alterar Integração',
                cancelButtonText: 'Cancelar Operação'
            }).then((result) => {
                if (result.value) {
                    divIntegration.find('.overlay').css('display', 'flex');

                    $.post(`${baseUrl}/logistics/saveIntegrationSeller`, {
                        integration,
                        data: null,
                        store,
                        integrationType
                    }, response => {
                        Swal.fire({
                            icon: response.success ? 'success' : 'error',
                            title: response.data,
                            confirmButtonText: "Ok",
                        });
                        $('.overlay').hide();

                        if (response.success)
                            $(`#${integration}`).collapse('hide')

                        integrationSelectedType = integrationType;
                        integrationSelectedValue = integrationValue;
                        storeHaveIntegration = true;
                    }).fail(e => {
                        console.log(e);
                    });
                } else if (result.dismiss === 'cancel' || result.dismiss === 'backdrop') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Operação cancelada',
                        confirmButtonText: "Ok",
                    });

                    if (!storeHaveIntegration) {
                        $(integrationType === 'sellercenter' ? '#listIntegrationSellerCenter' : '#listIntegrationSeller').find(`[type="radio"][value="${integrationValue}"]`).iCheck('uncheck');
                    } else {
                        $(integrationSelectedType === 'sellercenter' ? '#listIntegrationSellerCenter' : '#listIntegrationSeller').find(`[type="radio"][value="${integrationSelectedValue}"]`).iCheck('check');
                    }
                }
            });
        } else {
            integrationSelectedType = integrationType;
            integrationSelectedValue = integrationValue;
        }

        $('#removeIntegrationSelected').prop('disabled', false);
    });

    $('a[data-toggle="tab"][href="#manage_logistics"]').on('shown.bs.tab', function (e) {
        $('[name="stores"]').val(0).trigger('change');
    });

    $('#removeIntegrationSelected').on('click', function(){

        const storeName = $('[name="stores"] option:selected').text();
        const storeId   = parseInt($('[name="stores"]').val());

        Swal.fire({
            title: `Tem certeza que deseja excluir a integração da loja ${storeName}?`,
            text: 'Essa ação tornará essa loja apenas com o cadastro de transportadoras, sem integração.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Excluir Integração',
            cancelButtonText: 'Cancelar Operação'
        }).then((result) => {
            if (result.value) {
                $.post(`${baseUrl}/logistics/removeIntegrationStore`, { storeId }, response => {
                    Swal.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.data,
                        confirmButtonText: "Ok",
                    });
                    $('.overlay').hide();

                    if (response.success) {
                        $('[name="stores"]').trigger('change');
                    }

                }).fail(e => {
                    console.log(e);
                });
            } else if (result.dismiss === 'cancel' || result.dismiss === 'backdrop') {
                Swal.fire({
                    icon: 'error',
                    title: 'Operação cancelada',
                    confirmButtonText: "Ok",
                });
            }
        });
    });

    const createElementIntegration = async (integration, hideCollapse, preLoad = false) => {

        let cardCredentials = '';

        if (!hideCollapse) {
            let formBtnSave = `
                <div class="form-group col-md-12 no-padding d-flex justify-content-end">
                    <button class="btn btn-success col-md-12 btn-flat saveIntegration"><i class="fa fa-save"></i> Salvar</button>
                </div>
            `;
            cardCredentials = await getFieldCredentials(baseUrl, integration, formBtnSave);
        }

        let propCollapse = hideCollapse ? '' : `data-toggle="collapse" data-parent="#accordion" href="#${integration}"`;
        let textNoSave = hideCollapse || preLoad ? '' : '<h4 class="font-weight-bold text-red mt-0 mb-0">NÃO SALVO</h4>';

        return `
            <div integration='${integration}' ${propCollapse} class="col-md-12 d-flex justify-content-between align-items-center flex-wrap">
                <img src='${getPathImageIntegration(integration)}' style='height:20px' alt="${integration}"/>
                ${textNoSave}
                <button class="btn btn-flat btn-sm btn-danger btnRemoveIntegration"><i class="fa fa-trash"></i></button>
            </div>
            ${cardCredentials}
        `;
    }

    const getImageIntegration = data => {
        if (data.loading) {
            return '';
        }
        if (data.id === 'select') {
            return $(`<span>${data.text}</span>`);
        }

        return $(`<span><img src='${getPathImageIntegration(data.id)}' style='height:20px'/> ${data.text}</span>`);
    }

    const getPathImageIntegration = integration => {
        return `${baseUrl}/assets/files/integrations/${integration}/${integration}.png`;
    }

    const hideShowIntegrationsSelected = (isSeller) => {
        $(isSeller ? '#listIntegrationsSeller div:not(.overlay)' : '#listIntegrationsSellerCenter div:not(.overlay)').length ?
            $(isSeller ? '#listIntegrationsSeller' : '#listIntegrationsSellerCenter').parent().slideDown('slow') :
            $(isSeller ? '#listIntegrationsSeller' : '#listIntegrationsSellerCenter').parent().slideUp('slow');
    }

    const getIntegrationsSellerCenterInUse = () => {
        $.get(`${baseUrl}/logistics/getIntegrationsInUseSellerCenter`, response => {

            if (response.length) {
                $('#listIntegrationsSellerCenter').parent().slideDown('slow');
            }

            let credentials, typeElement, createElement;

            $(response).each(async function (key, value) {
                createElement = await createElementIntegration(value.name, false, true);
                $('#listIntegrationsSellerCenter').append(createElement);

                $(`#${value.name} .icheck_integration_selected`).iCheck({
                    checkboxClass: 'icheckbox_square-blue',
                    radioClass: 'iradio_square-blue',
                    increaseArea: '20%' // optional
                });

                $.get(`${baseUrl}/logistics/getDataIntegration/${value.name}/0`, response => {
                    credentials = JSON.parse(response.credentials);

                    Object.keys(credentials).forEach(function(key) {
                        typeElement = $(`#listIntegrationsSellerCenter #${value.name} [name="${key}"]`).attr('type');
                        if (typeElement !== 'radio' && typeElement !== 'checkbox')
                            $(`#listIntegrationsSellerCenter #${value.name} [name="${key}"]`).val(credentials[key]);
                        else
                            $(`#${value.name} [name="${key}"][value="${credentials[key]}"]`).iCheck('check');
                    });

                }).fail(e => {
                    console.log('getDataIntegration',e);
                });
            });

        }).fail(e => {
            console.log(e);
        });
    }

    const getIntegrationsSellerInUse = async () => {
        await $.get(`${baseUrl}/logistics/getIntegrationsInUseSeller`, response => {

            let createElement;

            if (response.length) {
                $('#logistic_seller').iCheck('check');
                $('#listIntegrationsSeller').parent().slideDown('slow');
            }

            $(response).each(async function (key, value) {
                createElement = await createElementIntegration(value.name, true, true);
                $('#listIntegrationsSeller').append(createElement);
            });
        })
        .fail(e => {
            console.log(e);
        });
    }

    const createMethodLogistic = async (el, integration, seller = true) => {

        const idEl = seller ? `integration_seller_use_${integration}` : `integration_sellercenter_use_${integration}`;

        const formBtnSave = `
                <div class="form-group col-md-12 no-padding d-flex justify-content-end">
                    <button class="btn btn-success col-md-12 btn-flat saveIntegration_card_seller"><i class="fa fa-save"></i> Salvar</button>
                </div>
            `;

        const cardCredentials = seller ? `<div id="${integration}_card_seller" class="panel-collapse collapse col-md-12 pt-2">${formBtnSave}</div>` : '';

        el.append(`
            <div class="col-md-12 no-padding mb-2 content_seller_use">
                <label for="${idEl}">
                    <input type="radio" class="form-control icheck_integration_user" id="${idEl}" name="integration_seller_use" value="${integration}">
                        <img src="${getPathImageIntegration(integration)}" height="25px">
                </label>
                ${cardCredentials}
            </div>`
        );

        $(`input[class*="icheck_integration"]`).iCheck({
            checkboxClass: 'icheckbox_square-blue',
            radioClass: 'iradio_square',
            increaseArea: '20%'
        });
    }

    const getComboSelectIntegrationSeller = () => {
        $.get(`${baseUrl}/logistics/getIntegrationsSellerActiveNotUse`, response => {
            console.log(response);
            let createElement = '<option value="select">Selecione</option>';

            $(response).each(async function (key, value) {
                createElement += `<option value="${value.name}"></option>`;
            });
            $('#integration_seller').empty().append(createElement);
        })
        .fail(e => {
            console.log(e);
        });
    }

    const getComboSelectIntegrationSellerCenter = () => {
        $.get(`${baseUrl}/logistics/getIntegrationsSellerCenterActiveNotUse`, response => {
            console.log(response);
            let createElement = '<option value="select">Selecione</option>';

            $(response).each(async function (key, value) {
                createElement += `<option value="${value.name}"></option>`;
            });
            $('#integration_seller_center').empty().append(createElement);
        })
        .fail(e => {
            console.log(e);
        });
    }

</script>