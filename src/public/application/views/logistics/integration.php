<div class="content-wrapper">
    <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>
    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <h5 class="mb-5"><span class="mr-5"><span class="border-radius-index bg-primary">1</span> Defina sua logística principal</span><span><span class="border-radius-index bg-primary">2</span> Defina a Logística externa para cada seller</span></h5>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box box-primary">
                    <div class="box-body">
                        <h5><span class="border-radius-index bg-primary">1</span> Defina sua logística principal</h5>

                        <div class="col-md-12 d-flex flex-nowrap card-integrations" data-type-card="sellercenter">
                            <div class="col-md-5 no-padding">
                                <div class="itens-integration-no-selected">
                                    <p class="mt-4"><b>Logísticas disponíveis no sistema</b></p>
                                </div>
                            </div>
                            <div class="col-md-1 no-padding">
                                <h2 class="w-100 integration-arrow-right">
                                    <i class="fa fa-arrow-right"></i>
                                </h2>
                            </div>

                            <div class="col-md-6 no-padding">
                                <div class="itens-integration-selected flex-4">
                                    <p class="mt-4"><b>Suas logísticas principais liberadas para sellers</b></p>
                                    <div class="flex-1 card-drop">
                                        <div class="item-integration-header"><b>Arraste uma logística ao lado</b></div>
                                    </div>
                                </div>
                                <div class="item-integration-credentials col-md-12"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box box-primary">
                    <div class="box-body">
                        <h5><span class="border-radius-index bg-primary">2</span> Defina a integração de logística externa que cada seller usará</h5>

                        <div class="col-md-12 d-flex flex-nowrap card-integrations mt-5 no-padding" data-type-card="seller">
                            <div class="col-md-3 no-padding mr-3">
                                <div class="form-group">
                                    <label>Busque por loja</label>
                                    <select class="form-control" name="stores">
                                        <option value="0">Selecione</option>
                                        <?php foreach ($stores as $store) {
                                            echo "<option value='{$store['id']}'>{$store['name']}</option>";
                                        } ?>
                                    </select>
                                </div>

                                <div id="active-ferigth-module"></div>

                            </div>



                            <div class="col-md-9 no-padding list-integration-to-seller">
                                <h4>Selecione a integração para o seller selecionado:</h4>

                                <p class="mt-3 font-weight-bold">Sua logística de Seller Center liberada aos sellers</p>

                                <div class="item-integration-sellercenter"></div>

                                <p class="mt-5 font-weight-bold">Logísticas externas liberada aos sellers

                                <div class="item-integration-seller"></div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="modal_logitic_integration_credential" tabindex="-1" role="dialog" aria-labelledby="modal_logitic_integration_credential" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title col-md-11 no-padding" id="modal_logitic_integration_credential">Credenciais</h5>
                <button type="button" class="close col-md-1 no-padding" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="formAlertMsg">
                </div>
                <form class="row" id="formIntegrationLogistic">
                    <div class="col-md-12 dataFormIntegration"></div>
                </form>
            </div>
        </div>
    </div>
</div>
<style>
    .border-radius-index {
        padding: 1px 6px;
        border-radius: 50%;
        margin-right: 10px;
    }

    .flex-1 {
        width: 23%;
        float: left;
    }

    .flex-4 {
        flex: 4;
    }

    .item-integration {
        border: 1px solid;
        border-radius: 10px;
        padding: 10px;
        height: 100px;
        text-align: center;
        cursor: pointer;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        margin: 0 2px;
        box-shadow: 0 0 10px 0 rgb(0 0 0 / 40%);
    }

    .itens-integration-no-selected .item-integration {
        height: 65px
    }

    .item-integration button {
        top: 10%;
        position: relative;
    }

    .item-integration-header {
        height: 40px;
    }

    .itens-integration-no-selected .item-integration-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
    }

    .card-drop {
        text-align: center;
        border: 1px solid;
        border-radius: 10px;
        height: 100px;
        padding-top: 30px;
    }

    .itens-integration-no-selected,
    .itens-integration-selected {
        margin: 5px;
        height: 100px;
    }

    .itens-integration-no-selected button.btn-configure {
        display: none;
    }

    .itens-integration-selected button.btn-configure {
        display: block;
    }

    .item-integration-credentials {
        display: none;
        width: 100%;
        box-shadow: 0 0 10px 0 rgb(0 0 0 / 40%);
        border-radius: 10px;
        margin-top: 20px !important;
        padding: 10px;
    }

    .item-integration-placeholder {
        border: 1px solid;
        border-radius: 10px;
        padding: 10px;
        height: 100px;
        width: 23%;
        float: left;
    }

    .swal2-html-container ul {
        text-align: left;
    }

    .integration-arrow-right {
        margin-top: 60px;
    }

    .btn-outline-primary {
        color: #007bff;
        background-color: transparent;
        background-image: none;
        border-color: #007bff;
    }

    .options-integration {
        display: none;
    }

    .list-integration-to-seller {
        display: none;
    }
</style>
<script type="application/javascript" src="<?=base_url('assets/dist/js/pages/logistic.js')?>"></script>
<script>
    var baseUrl = "<?=base_url()?>";
    var viewAlertChangeIntegrationStore = false;
    var storeHaveIntegration = false;

    let integrationSelectedValue = null;
    let integrationSelectedType  = null;
    let integrationSelectedExternalId  = null;
    let integration_selected_value_old = null;
    let integration_selected_type_old = null;
    let integration_selected_external_id_old = null;

    let typesIntegrations = {
        "seller": {
            "use": {
                "carrier": [],
                "integrator": []
            },
            "not_use": {
                "carrier": [],
                "integrator": []
            }
        },
        "sellercenter": {
            "use": {
                "carrier": [],
                "integrator": []
            },
            "not_use": {
                "carrier": [],
                "integrator": []
            }
        }
    };

    let typesIntegrationsStore = {
        "seller": {
            "carrier": [],
            "integrator": []
        },
        "sellercenter": {
            "carrier": [],
            "integrator": []
        }
    };

    $(function() {
        $( ".itens-integration-no-selected" ).sortable({
            connectWith: ".itens-integration-selected",
            items: ".item-integration",
            cursor: "move",
            activate : function(event, ui) {
            },
            stop: function(event, ui) {
            }
        }).disableSelection();

        $( ".itens-integration-selected" ).sortable({
            items: ".item-integration",
            receive: function(event, ui){ // depois que soltou
                const integration = ui.item.find('.btn-configure').data('integration-name');
                ui.item.find('.btn-configure').trigger('click');
                $('.card-drop').hide();

                $.post(`${baseUrl}/logistics/saveIntegration`, {
                    integration,
                    data: {},
                    type: 'sellercenter',
                    type_integration: 'carrier',
                    type_operation: 'create'
                }, response => {
                    $('[name="stores"]').trigger('change');
                    typesIntegrations['sellercenter']['use']['carrier'].push(integration);
                }).fail(e => {
                    console.log(e);
                });

            },
            change: () => {
                $( ".itens-integration-selected" ).sortable( "cancel" );
            }
        }).disableSelection();


        $('#mainLogisticsNav').addClass('active');
        $('#manageLogisticIntegrationsNav').addClass('active');

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

    $(document).on('click', '.btnRemoveIntegration', function (){
        const elCard         = $(this).closest('.item-integration-credentials');
        const integration    = elCard.data('integration-name');
        const sellerCenter   = elCard.closest('.card-integrations').data('type-card') === 'sellercenter';
        const imgIntegration = $(`.itens-integration-selected .item-integration[data-integration="${integration}"]`).find('img').attr('src');
        const htmlAlert      = sellerCenter ? '<h4 class="font-weight-bold text-red">Ao excluir a integração, todos os sellers que estão integrados consequentemente perderão a sua integração.</h4><br/>' : ''
        const type           = 'sellercenter';

        console.log(integration);
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

                const type = sellerCenter ? 'sellercenter' : 'seller';
                let type_integration = null;

                if (typesIntegrations[type]['use']['integrator'].includes(integration)) {
                    type_integration = 'integrator';
                } else if (typesIntegrations[type]['use']['carrier'].includes(integration)) {
                    type_integration = 'carrier';
                }

                $.post(`${baseUrl}/logistics/removeIntegrationSellerCenter`, { integration, type, type_integration }, response => {
                    console.log(response);
                    Swal.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.data,
                        confirmButtonText: "Ok",
                    });
                    if (response.success) {
                        $(`.itens-integration-selected .item-integration[data-integration="${integration}"]`).remove();
                        $('[data-type-card="sellercenter"] .item-integration-credentials').data('integration-name', false).hide();
                        if (!$('.itens-integration-selected .item-integration').length) {
                            $('.card-drop').show();
                        }

                        //getComboSelectIntegrationSeller();
                        getComboSelectIntegrationSellerCenter();
                        $('[name="stores"]').trigger('change');
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

        const integration = $(this).closest('.item-integration-credentials').data('integration-name');
        const btn = $(this);

        // disabled fields
        btn.prop('disabled', true);

        // get data inputs
        let name, value, checked, label;
        let data = [];
        let msgError = [];
        let ignore_name = [];
        await $(this).closest('.item-integration-credentials').find('[name]').each(function() {
            name    = $(this).attr('name');
            value   = $(this).val();
            checked = $(this).is(':checked');
            label   = $(this).closest('.form-group').find('label:first').text()

            if (ignore_name.includes(name)) {
                return true;
            }

            if ($(this).attr('type') === 'checkbox') {
                value = $(`.item-integration-credentials [name="${name}"]:checked`).map(function (d, i) {
                   return $(i).val();
                }).toArray();
            } else if ($(this).attr('type') === 'radio') {
                if (!$(`.item-integration-credentials [name="${name}"]`).is(':checked')) {
                    msgError.push(`Campo ${label} precisa ser selecionado.`);
                }

                value = $(`.item-integration-credentials [name="${name}"]:checked`).val();
            } else {
                if (value === '') {
                    msgError.push(`Campo ${label} precisa ser preenchido.`);
                }
            }
            data.push({name, value});
            ignore_name.push(name);
        });

        if (msgError.length) {
            Swal.fire({
                icon: 'error',
                title: 'Não foi possível salvar a integração',
                html: '<ul><li>' + msgError.join('</li><li>') + '</li></ul>'
            });
            btn.prop('disabled', false);
            return false;
        }

        const type = 'sellercenter';
        let type_integration = null;
        let type_operation = null;

        if (typesIntegrations[type]['not_use']['integrator'].includes(integration)) {
            type_integration = 'integrator';
            type_operation = 'create';
        } else if (typesIntegrations[type]['not_use']['carrier'].includes(integration)) {
            type_integration = 'carrier';
            type_operation = 'create';
        }

        if (typesIntegrations[type]['use']['integrator'].includes(integration)) {
            type_integration = 'integrator';
            type_operation = 'update';
        } else if (typesIntegrations[type]['use']['carrier'].includes(integration)) {
            type_integration = 'carrier';
            type_operation = 'update';
        }

        // request ajax save data
        await $.post(`${baseUrl}/logistics/saveIntegration`, {
            integration,
            data,
            type,
            type_integration,
            type_operation
        }, response => {
            Swal.fire({
                icon: response.success ? 'success' : 'error',
                title: response.data,
                confirmButtonText: "Ok",
            });
            btn.prop('disabled', false);

            if (response.success) {
                $(`#${integration}`).collapse('hide');
                $(`div[integration="${integration}"] h4`).remove();
                getComboSelectIntegrationSeller();
                getComboSelectIntegrationSellerCenter();
                addTypeIntegrationInMemory(true, false, type_integration, integration);
            }
        }).fail(e => {
            console.log(e);
        });
    });

    $('[name="stores"]').change(async function(){

        document.getElementById('active-ferigth-module').innerHTML = ""
        integration_selected_value_old = null;
        integration_selected_type_old = null;
        integration_selected_external_id_old = null;
        integrationSelectedValue = null;
        integrationSelectedType  = null;
        integrationSelectedExternalId  = null;
        viewAlertChangeIntegrationStore = false;

        const btn = $(this);

        btn.prop('disabled', true);

        const sellerId = parseInt($(this).val());
        let response;
        let type_integration = null;

        $('.item-integration-sellercenter, .item-integration-seller').empty().append('<h2 class="text-center"><i class="fa fa-spinner fa-spin"></i></h2>');

        if (sellerId === 0) {
            $('.list-integration-to-seller').hide();
            btn.prop('disabled', false);
            return false;
        }
        $('.list-integration-to-seller').show();

        $(`.options-integration`).hide();

        response = await $.ajax({
            url: `${baseUrl}/logistics/getIntegrationSeller/${sellerId}`,
            async: true,
            type: 'GET',
        });
        console.log(response);

        $("select[name=status]").val(0);


        $('.item-integration-sellercenter, .item-integration-seller').empty();

        let integrationTypeSeller, integrationTypeSellerCenter, logo_integration, external_integration_id;
        for await (let [key, value] of Object.entries(response.integrationsSellerCenter)) {
            if (value.type) {
                typesIntegrationsStore['sellercenter'][value.type].push(value.name);
            }
            if (value.name == (response.integrationSeller.integration ?? '')) {
                integrationTypeSellerCenter = value.type;
                integrationSelectedType = 'sellercenter';
                integrationSelectedExternalId = null;
            }
            await createMethodLogistic($('.item-integration-sellercenter'), value.name, false, response, value.type ? value.type : '');
        }
        for await (let [key, value] of Object.entries(response.integrationsSeller)) {
            if (value.type) {
                typesIntegrationsStore['seller'][value.type].push(value.name);
            }
            logo_integration = value.hasOwnProperty("external_integration_image") ? value.external_integration_image : null;
            external_integration_id = value.hasOwnProperty("external_integration_id") ? value.external_integration_id : null;
            if (value.name == (response.integrationSeller.integration ?? '')) {
                integrationTypeSeller = value.type;
                integrationSelectedType = 'seller';
                integrationSelectedExternalId = external_integration_id;
            }
            await createMethodLogistic($('.item-integration-seller'), value.name, true, response, value.type ? value.type : '', logo_integration, external_integration_id);
        }

        let lengthIntegration = response.integrationSeller.length;
        if (typeof lengthIntegration == 'undefined') {
            lengthIntegration = Object.getOwnPropertyNames(response.integrationSeller).length;
        }

        storeHaveIntegration = !!lengthIntegration;

        if (!storeHaveIntegration) {
            viewAlertChangeIntegrationStore = true;
        }

        if(lengthIntegration) {

            const credentials = response.integrationSeller.credentials;
            let typeElement;

            if (response.integrationSeller.seller) {
                $(`#${response.integrationSeller.integration}_card_seller`).collapse('show');
                $(`#integration_seller_use_${response.integrationSeller.integration}_${response.integrationSeller.external_integration_id}`).iCheck('check');
                $(`#integration_seller_use_${response.integrationSeller.integration}_${response.integrationSeller.external_integration_id}`).attr('data-integration-type-current', integrationTypeSeller);
                $(`#integration_seller_use_${response.integrationSeller.integration}_${response.integrationSeller.external_integration_id}`).attr('data-integration-current', response.integrationSeller.integration);
            } else {
                $(`#integration_sellercenter_use_${response.integrationSeller.integration}_null`).iCheck('check');
                $(`#integration_sellercenter_use_${response.integrationSeller.integration}_null`).attr('data-integration-type-current', integrationTypeSellerCenter);
                $(`#integration_sellercenter_use_${response.integrationSeller.integration}_null`).attr('data-integration-current', response.integrationSeller.integration);
            }

            if (credentials !== null) {
                Object.keys(credentials).forEach(function (key) {
                    typeElement = $(`#${response.integrationSeller.integration}_card_seller [name="${key}"]`).attr('type');
                    if (typeElement !== 'radio' && typeElement !== 'checkbox') {
                        $(`#${response.integrationSeller.integration}_card_seller [name="${key}"]`).val(credentials[key]);
                    } else {
                        $(`#${response.integrationSeller.integration}_card_seller [name="${key}"][value="${credentials[key]}"]`).iCheck('check');
                    }
                });
            }
            if (response.integrationSeller.seller) {
                $(`.item-integration-seller .options-integration[data-integration-name="${response.integrationSeller.integration}"][data-external-integration-id="${response.integrationSeller.external_integration_id}"]`).show();
            } else {
                $(`.item-integration-sellercenter .options-integration[data-integration-name="${response.integrationSeller.integration}"][data-external-integration-id="null"]`).show();
            }
            loadCredentialsIntegration();
        }

        $(`input[class*="icheck_integration"], input[class*="icheck_services"]`).iCheck({
            checkboxClass: 'icheckbox_square-blue',
            radioClass: 'iradio_square col-md-2',
            increaseArea: '20%'
        });
        btn.prop('disabled', false);

    });

    $(document).on('click', '.saveIntegration_card_seller', async function (e) {
        e.preventDefault();
        const integrationContent = $('[name="integration_seller_use"]:checked');
        const integration = integrationContent.val();
        const integrationType = 'seller';
        const externalIntegrationId = integrationContent.data('external-integration-id');
        const btn = $(this);

        // disabled fields
        btn.prop('disabled', true);

        // get data inputs
        let name, value, checked, label;
        let data = [];
        let msgError = [];
        let ignore_name = [];
        await $('#formIntegrationLogistic .dataFormIntegration [name]').each(function () {
            name = $(this).attr('name');
            value = $(this).val();
            checked = $(this).is(':checked');
            label = $(this).closest('.form-group').find('label:first').text();

            if (ignore_name.includes(name)) {
                return true;
            }

            if ($(this).attr('type') === 'checkbox') {
                value = $(`#formIntegrationLogistic .dataFormIntegration [name="${name}"]:checked`).map(function (d, i) {
                    return $(i).val();
                }).toArray();
            } else if ($(this).attr('type') === 'radio') {
                if (!$(`#formIntegrationLogistic .dataFormIntegration [name="${name}"]`).is(':checked')) {
                    msgError.push(`Campo ${label} precisa ser selecionado.`);
                }
                value = $(`#formIntegrationLogistic .dataFormIntegration [name="${name}"]:checked`).val();
            } else {
                if (value === '') {
                    msgError.push(`Campo ${label} precisa ser preenchido.`);
                }
            }

            data.push({name, value});
            ignore_name.push(name);
        });

        if (msgError.length) {
            Swal.fire({
                icon: 'error',
                title: 'Não foi possível salvar a integração',
                html: '<ul><li>' + msgError.join('</li><li>') + '</li></ul>'
            });
            btn.prop('disabled', false);
            return false;
        }

        let storeId = $('[name="stores"]').val();
        let dataIntegration = getIntegrationSaveSellerData();
        console.log({
            integration,
            data,
            store: storeId,
            integrationType,
            typeIntegration: dataIntegration.typeIntegration ?? null,
            externalIntegrationId
        }, '635');
        await $.post(`${baseUrl}/logistics/saveIntegrationSeller`, {
            integration,
            data,
            store: storeId,
            integrationType,
            typeIntegration: dataIntegration.typeIntegration ?? '',
            typeIntegrationCurrent: '',
            integrationCurrent: '',
            externalIntegrationId
        }, response => {
            Swal.fire({
                icon: response.success ? 'success' : 'error',
                title: response.data,
                confirmButtonText: "Ok",
            });
            if (response.success) {
                integrationSelectedType = integrationType;
                integrationSelectedExternalId = externalIntegrationId;
                integrationSelectedValue = integration;
                storeHaveIntegration = true;

                $(`input[name^="integration_seller_use"]`).removeAttr('data-integration-type-current');
                $(`input[name^="integration_seller_use"]`).removeAttr('data-integration-current');
                $(`input[name^="integration_seller_use"]:checked`).attr('data-integration-type-current', dataIntegration.typeIntegration ?? '');
                $(`input[name^="integration_seller_use"]:checked`).attr('data-integration-current', integration ?? '');
            } else {
                if (integrationSelectedType === null && integrationSelectedValue === null) {
                    $('.options-integration').hide();
                }

                viewAlertChangeIntegrationStore = false;

                if (!storeHaveIntegration) {
                    $(integrationType === 'sellercenter' ? '.item-integration-sellercenter' : '.item-integration-seller').find(`[type="radio"][value="${integrationValue}"][data-external-integration-id="${integrationSelectedExternalId}"]`).iCheck('uncheck');
                } else {
                    $(integrationSelectedType === 'sellercenter' ? '.item-integration-sellercenter' : '.item-integration-seller').find(`[type="radio"][value="${integrationSelectedValue}"][data-external-integration-id="${integrationSelectedExternalId}"]`).iCheck('check');
                }
            }
            btn.prop('disabled', false);
            //loadCredentialsIntegration();
        }).fail(e => {
            console.log(e);
        });
    });

    $(document).on('ifChecked', '.icheck_integration_user', function() {
        $(`.options-integration`).hide();
        $(this).closest('.content_seller_use').find('.options-integration').show();
    });

    $(document).on('ifChecked', '.item-integration-seller .icheck_integration_user', async function(){

        const integrationContent = $(this).closest('.content_seller_use').find('input[name="integration_seller_use"]');
        const integrationValue = integrationContent.val();
        const integrationType = 'seller';
        const integration = integrationValue;
        const externalIntegrationId = integrationContent.data('external-integration-id');

        if (!storeHaveIntegration || !viewAlertChangeIntegrationStore) {
            if (viewAlertChangeIntegrationStore) {
                setIntegrationSeller(integration, '{}', $('[name="stores"]').val(), integrationType, integrationValue, externalIntegrationId);
            } else {
                integrationSelectedValue        = $('[name="integration_seller_use"]:checked').val();
                integrationSelectedType         = $('[name="integration_seller_use"]:checked').closest('.item-integration-sellercenter').length ? 'sellercenter' : 'seller';
                integrationSelectedExternalId   = $('[name="integration_seller_use"]:checked').data('external-integration-id');
            }
            viewAlertChangeIntegrationStore = true;
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
                setIntegrationSeller(integration, '{}', $('[name="stores"]').val(), integrationType, integrationValue, externalIntegrationId);
            } else if (result.dismiss === 'cancel' || result.dismiss === 'backdrop') {
                Swal.fire({
                    icon: 'error',
                    title: 'Operação cancelada',
                    confirmButtonText: "Ok",
                })

                if (integrationSelectedType === null && integrationSelectedValue === null) {
                    $('.options-integration').hide();
                }

                viewAlertChangeIntegrationStore = false;

                if (!storeHaveIntegration) {
                    $(integrationType === 'sellercenter' ? '.item-integration-sellercenter' : '.item-integration-seller').find(`[type="radio"][value="${integrationValue}"][data-external-integration-id="${integrationSelectedExternalId}"]`).iCheck('uncheck');
                } else {
                    $(integrationSelectedType === 'sellercenter' ? '.item-integration-sellercenter' : '.item-integration-seller').find(`[type="radio"][value="${integrationSelectedValue}"][data-external-integration-id="${integrationSelectedExternalId}"]`).iCheck('check');
                }
            }
        });
    });

    $(document).on('ifChecked', '.item-integration-sellercenter .icheck_integration_user', function() {
        const integration    = $(this).val();
        const store          = $('[name="stores"]').val();
        let integrationValue = $(this).val();
        let integrationType  = 'sellercenter';
        const externalIntegrationId = $(this).data('external-integration-id');

        if (!storeHaveIntegration || !viewAlertChangeIntegrationStore) {
            if (viewAlertChangeIntegrationStore) {
                setIntegrationSeller(integration, null, store, integrationType, integrationValue, externalIntegrationId);
            } else {
                integrationSelectedValue        = $('[name="integration_seller_use"]:checked').val();
                integrationSelectedType         = $('[name="integration_seller_use"]:checked').closest('.item-integration-sellercenter').length ? 'sellercenter' : 'seller';
                integrationSelectedExternalId   = $('[name="integration_seller_use"]:checked').data('external-integration-id');
            }
            viewAlertChangeIntegrationStore = true;
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
                setIntegrationSeller(integration, null, store, integrationType, integrationValue, externalIntegrationId);
            } else if (result.dismiss === 'cancel' || result.dismiss === 'backdrop') {
                Swal.fire({
                    icon: 'error',
                    title: 'Operação cancelada',
                    confirmButtonText: "Ok",
                });

                if (integrationSelectedType === null && integrationSelectedValue === null) {
                    $('.options-integration').hide();
                }

                viewAlertChangeIntegrationStore = false;

                if (!storeHaveIntegration) {
                    $(integrationType === 'sellercenter' ? '.item-integration-sellercenter' : '.item-integration-seller').find(`[type="radio"][value="${integrationValue}"][data-external-integration-id="${integrationSelectedExternalId}"]`).iCheck('uncheck');
                } else {
                    $(integrationSelectedType === 'sellercenter' ? '.item-integration-sellercenter' : '.item-integration-seller').find(`[type="radio"][value="${integrationSelectedValue}"][data-external-integration-id="${integrationSelectedExternalId}"]`).iCheck('check');
                }
            }
        });
    });

    $('a[data-toggle="tab"][href="#manage_logistics"]').on('shown.bs.tab', function (e) {
        $('[name="stores"]').val(0).trigger('change');
    });

    const setIntegrationSeller = (integration, data, store, integrationType, integrationValue, externalIntegrationId) => {
        let dataIntegration = getIntegrationSaveSellerData();
        $(`.card-integrations .list-integration-to-seller [type="radio"]`).iCheck('disable')
        $.post(`${baseUrl}/logistics/saveIntegrationSeller`, {
            integration,
            data: null,
            store,
            integrationType,
            ...dataIntegration,
            externalIntegrationId
        }, response => {
            Swal.fire({
                icon: response.success ? 'success' : 'error',
                title: response.data,
                confirmButtonText: "Ok",
            });
            if (response.success) {
                integrationSelectedType = integrationType;
                integrationSelectedValue = integrationValue;
                integrationSelectedExternalId = externalIntegrationId;
                storeHaveIntegration = true;

                $(`input[name^="integration_seller_use"]`).removeAttr('data-integration-type-current');
                $(`input[name^="integration_seller_use"]`).removeAttr('data-integration-current');
                $(`input[name^="integration_seller_use"]:checked`).attr('data-integration-type-current', dataIntegration.typeIntegration ?? '');
                $(`input[name^="integration_seller_use"]:checked`).attr('data-integration-current', integration ?? '');
            } else {
                if (integrationSelectedType === null && integrationSelectedValue === null) {
                    $('.options-integration').hide();
                }

                viewAlertChangeIntegrationStore = false;

                if (!storeHaveIntegration) {
                    $(integrationType === 'sellercenter' ? '.item-integration-sellercenter' : '.item-integration-seller').find(`[type="radio"][value="${integrationValue}"][data-external-integration-id="${integrationSelectedExternalId}"]`).iCheck('uncheck');
                } else {
                    $(integrationSelectedType === 'sellercenter' ? '.item-integration-sellercenter' : '.item-integration-seller').find(`[type="radio"][value="${integrationSelectedValue}"][data-external-integration-id="${integrationSelectedExternalId}"]`).iCheck('check');
                }
            }
            loadCredentialsIntegration();
        }).fail(e => {
            console.log(e);
        }).always(() => {
            $(`.card-integrations .list-integration-to-seller [type="radio"]`).iCheck('enable');
        });
    }

    const removeIntegration = () => {

        const storeName = $('[name="stores"] option:selected').text();
        const storeId   = parseInt($('[name="stores"]').val());
        const external_integration_id = $('[onclick="removeIntegration()"]:visible').data('external-integration-id')

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

                let type_integration_current = null;
                let integration_current = integrationSelectedValue;

                if (integrationSelectedType && typesIntegrationsStore[integrationSelectedType]['integrator'].includes(integrationSelectedValue)) {
                    type_integration_current = 'integrator';
                } else if (integrationSelectedType && typesIntegrationsStore[integrationSelectedType]['carrier'].includes(integrationSelectedValue)) {
                    type_integration_current = 'carrier';
                }

                $.post(`${baseUrl}/logistics/removeIntegrationStore`, {
                    storeId,
                    type_integration_current,
                    integration_current,
                    external_integration_id
                }, response => {
                    Swal.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.data,
                        confirmButtonText: "Ok",
                    });
                    $('.overlay').hide();

                    if (response.success) {
                        $('[name="integration_seller_use"]:checked')
                            .iCheck('uncheck')
                            .closest('.content_seller_use')
                            .find('.options-integration')
                            .hide();

                        storeHaveIntegration = false;
                        integrationSelectedValue = null;
                        integrationSelectedType  = null;
                        integrationSelectedExternalId = null;

                        $(`input[name^="integration_seller_use"]`).removeAttr('data-integration-type-current');
                        $(`input[name^="integration_seller_use"]`).removeAttr('data-integration-current');
                        //$('[name="stores"]').trigger('change');
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
    }

    $(document).on('click', '.btn-configure', async function(){
        const integration = $(this).attr('data-integration-name');
        const credentials_open = $('[data-type-card="sellercenter"] .item-integration-credentials').data('integration-name');
        const integration_type = $(this).data('integration-type');

        console.log(credentials_open, integration, integration_type);

        if ($('[data-type-card="sellercenter"] .item-integration-credentials').is(':visible') && credentials_open === integration) {
            $('[data-type-card="sellercenter"] .item-integration-credentials').hide();
            return;
        }

        if ($('[data-type-card="sellercenter"] .item-integration-credentials').is(':not(:visible)') && credentials_open === integration) {
            $('[data-type-card="sellercenter"] .item-integration-credentials').show();
            return;
        }

        $('[data-type-card="sellercenter"] .item-integration-credentials').show().empty().append('<h2 class="text-center"><i class="fa fa-spinner fa-spin"></i></h2>');

        $('[data-type-card="sellercenter"] .item-integration-credentials').data('integration-name', integration);

        const createElement = await createElementIntegration(integration);
        let credentials, typeElement;

        $.get(`${baseUrl}/logistics/getDataIntegration/${integration}/${integration_type}/0`, response => {
            $('[data-type-card="sellercenter"] .item-integration-credentials').empty().append(createElement);

            if (response) {
                credentials = response.credentials ?? null;
                credentials = (typeof credentials === 'string' || credentials instanceof String) ? JSON.parse(credentials) : credentials;
                if (credentials !== null && credentials !== undefined) {
                    Object.keys(credentials).forEach(function(key) {
                        typeElement = $(`.item-integration-credentials [name="${key}"]`).attr('type');
                        if (typeElement === 'radio') {
                            $(`[data-type-card="sellercenter"] .item-integration-credentials [name="${key}"][value="${credentials[key]}"]`).iCheck('check');
                        } else if (typeElement === 'checkbox') {
                            $(credentials[key]).each(function(k, v){
                                $(`[data-type-card="sellercenter"] .item-integration-credentials [name="${key}"][value="${v}"]`).iCheck('check');
                            })
                        } else {
                            $(`[data-type-card="sellercenter"] .item-integration-credentials [name="${key}"]`).val(credentials[key]);
                        }
                    });
                }
            }


            $(`[data-type-card="sellercenter"] .item-integration-credentials .icheck_integration_selected`).iCheck({
                checkboxClass: 'icheckbox_square-blue',
                radioClass: 'iradio_square-blue',
                increaseArea: '20%' // optional
            });

        }).fail(e => {
            console.log('getDataIntegration',e);
        });
    });

    $(document).on('click', '.validate_credentials', async function() {
        const integration = $(this).closest('.item-integration-credentials').data('integration-name') ?? $('[name="integration_seller_use"]:checked').val();

        const btn = $(this).closest('.item-integration-credentials, .dataFormIntegration').find('button');

        // disabled fields
        btn.prop('disabled', true);

        // get data inputs
        let name, value, checked, label;
        let data = {};
        let msgError = [];
        let ignore_name = [];
        let content;

        await $(this).closest('.item-integration-credentials, .dataFormIntegration').find('[name]').each(function() {
            content = $(this).closest('.item-integration-credentials, .dataFormIntegration');
            name    = $(this).attr('name');
            value   = $(this).val();
            checked = $(this).is(':checked');
            label   = $(this).closest('.form-group').find('label:first').text()

            if (ignore_name.includes(name)) {
                return true;
            }

            if ($(this).attr('type') === 'checkbox') {
                value = content.find(`[name="${name}"]:checked`).map(function (d, i) {
                    return $(i).val();
                }).toArray();
            } else if ($(this).attr('type') === 'radio') {
                if (!content.find(`[name="${name}"]`).is(':checked')) {
                    msgError.push(`Campo ${label} precisa ser selecionado.`);
                }

                value = content.find(`[name="${name}"]:checked`).val();
            } else {
                if (value === '') {
                    msgError.push(`Campo ${label} precisa ser preenchido.`);
                }
            }
            data[name] = value;
            ignore_name.push(name);
        });

        if (msgError.length) {
            Swal.fire({
                icon: 'error',
                title: 'Não foi possível validar as credenciais',
                html: '<ul><li>' + msgError.join('</li><li>') + '</li></ul>'
            });
            btn.prop('disabled', false);
            return false;
        }

        // request ajax save data
        await $.get(`${baseUrl}/logistics/validateCredentials/${integration}`, { data }, response => {
            Swal.fire({
                icon: 'success',
                title: 'Credenciais válidas. Clique em salvar para guardar as alterações feitas.',
                confirmButtonText: "Ok",
            });
        }).fail(e => {
            Swal.fire({
                icon: 'error',
                title: 'Falha na validação',
                html: e.responseJSON.data,
                confirmButtonText: "Ok",
            });
        }).always(function() {
            btn.prop('disabled', false);
        });
    });

    const createElementIntegration = async (integration, sellercenter = true) => {
        const class_save_integartion = sellercenter ? 'saveIntegration' : 'saveIntegration_card_seller';
        let btn_validate_credentials = '';

        if (integration === 'correios') {
            btn_validate_credentials = `<button type="button" class="btn btn-primary col-md-12 btn-flat validate_credentials mt-1"><i class="fa fa-key"></i> Validar credenciais</button>`;
        }

        let formBtnSave = `
            <div class="form-group col-md-12 no-padding">
                <button class="btn btn-success col-md-12 btn-flat ${class_save_integartion}"><i class="fa fa-save"></i> Salvar</button>
                ${btn_validate_credentials}
            </div>
        `;

        let cardCredentials = await getFieldCredentials(baseUrl, integration, formBtnSave, false, true);

        if (sellercenter) {
            return `
                <div class="d-flex justify-content-end">
                    <button class="btn btn-flat btn-sm btn-danger btnRemoveIntegration"><i class="fa fa-trash"></i></button>
                </div>
                ${cardCredentials}
            `;
        }

        return cardCredentials;
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
        //addTypeIntegrationInMemory(true, false, 'integrator', null, true);
        addTypeIntegrationInMemory(true, false, 'carrier', null, true);

        $.get(`${baseUrl}/logistics/getIntegrationsInUseSellerCenter`, response => {
            let createElement = '<p class="mt-4"><b>Suas logísticas principais liberadas para sellers</b></p>';

            const hideShowCardDrop = response.length === 0 ? 'display: block' : 'display: none';

            createElement += `<div class="flex-1 card-drop" style="${hideShowCardDrop}">
                                <div class="item-integration-header"><b>Arraste uma logística ao lado</b></div>
                            </div>`

            $(response).each(async function (key, value) {
                addTypeIntegrationInMemory(true, false, 'carrier', value.name);
                createElement += `<div class="item-integration flex-1" data-integration="${value.name}">
                                    <div class="item-integration-header" style='width: 100%'>
                                        <img src='${getPathImageIntegration(value.name)}' style='height: 100%;width: 100%;object-fit: contain;' alt='${value.description}'/>
                                    </div>
                                    <button class="btn btn-link btn-configure" data-integration-type="${value.type}" data-integration-name="${value.name}"><i class="fa fa-edit"></i> Configurar</button>
                                </div>`;
            });
            $('.itens-integration-selected').empty().append(createElement);
        })
        .fail(e => {
            console.log(e);
        });
    }

    const getIntegrationsSellerInUse = async () => {
        addTypeIntegrationInMemory(true, true, 'integrator', null, true);
        addTypeIntegrationInMemory(true, true, 'carrier', null, true);

        await $.get(`${baseUrl}/logistics/getIntegrationsInUseSeller`, response => {

            let createElement;

            if (response.length) {
                $('#logistic_seller').iCheck('check');
                $('#listIntegrationsSeller').parent().slideDown('slow');
            }

            $(response).each(async function (key, value) {
                addTypeIntegrationInMemory(true, true, value.type, value.name);
                createElement = await createElementIntegration(value.name, true, true);
                $('#listIntegrationsSeller').append(createElement);
            });
        })
        .fail(e => {
            console.log(e);
        });
    }

    const createMethodLogistic = async (el, integration, seller, response, typeIntegration, logo = null, external_integration_id = null) => {

        const idEl = seller ? `integration_seller_use_${integration}_${external_integration_id}` : `integration_sellercenter_use_${integration}_${external_integration_id}`;
        let content = `<div class="col-md-12 no-padding mb-2 content_seller_use">
                <label for="${idEl}" class="d-flex flex-nowrap">
                    <input type="radio" class="form-control icheck_integration_user col-md-1" id="${idEl}" name="integration_seller_use" value="${integration}"
                    data-type-integration="${typeIntegration}" data-external-integration-id="${external_integration_id}"
                    >
                    <div class="col-md-3 no-padding">
                        <img src="${logo ?? getPathImageIntegration(integration)}" height="20px">
                    </div>
                    <div class="options-integration col-md-8 no-padding" data-integration-name="${integration}" data-external-integration-id="${external_integration_id}">`;

        if (seller) {
            content += `<button class="btn btn-outline-primary col-md-4" onclick="updateCredentials()">Inserir credenciais do seller</button>`;
        }

        content += `<button class="btn btn-link col-md-3" onclick="removeIntegration()" data-external-integration-id="${external_integration_id}"><i class="fa fa-trash"></i> Excluir integração da loja</button>
                    </div>
                </label>
            </div>`;

        el.append(content);

        setTimeout(() => {
            $(`input[class*="icheck_integration"]`).iCheck({
                checkboxClass: 'icheckbox_square-blue',
                radioClass: 'iradio_square col-md-2',
                increaseArea: '20%'
            });
        }, 250);
    }

    const getComboSelectIntegrationSeller = () => {
        addTypeIntegrationInMemory(false, true, 'integrator', null, true);
        addTypeIntegrationInMemory(false, true, 'carrier', null, true);

        $.get(`${baseUrl}/logistics/getIntegrationsSellerActiveNotUse`, response => {

            let createElement = '<option value="select">Selecione</option>';

            $(response).each(async function (key, value) {
                addTypeIntegrationInMemory(false, true, value.type, value.name);
                createElement += `<option value="${value.name}"></option>`;
            });
            $('#integration_seller').empty().append(createElement).val('select').trigger('change');
        })
        .fail(e => {
            console.log(e);
        });
    }

    const getComboSelectIntegrationSellerCenter = () => {
        addTypeIntegrationInMemory(false, false, 'integrator', null, true);
        addTypeIntegrationInMemory(false, false, 'carrier', null, true);

        $.get(`${baseUrl}/logistics/getIntegrationsSellerCenterActiveNotUse`, response => {
            let createElement = '<p class="mt-4"><b>Logísticas disponíveis no sistema</b></p>';

            $(response).each(async function (key, value) {
                addTypeIntegrationInMemory(false, false, value.type, value.name);
                createElement += `<div class="item-integration flex-1" data-integration="${value.name}">
                                    <div class="item-integration-header" style='width: 100%'>
                                        <img src='${getPathImageIntegration(value.name)}' style='height: 100%;width: 100%;object-fit: contain;' alt='${value.description}'/>
                                    </div>
                                    <button class="btn btn-link btn-configure" data-integration-name="${value.name}"><i class="fa fa-edit"></i> Configurar</button>
                                </div>`;
            });
            $('.itens-integration-no-selected').empty().append(createElement).val('select').trigger('change');
        })
        .fail(e => {
            console.log(e);
        });
    }

    const addTypeIntegrationInMemory = (is_use, is_seller, type, integration, clean = false) => {
        if (type === null) {
            return;
        }

        const type_user = is_seller ? 'seller' : 'sellercenter';
        const type_use = is_use ? 'use' : 'not_use';

        // limpa os dados.
        if (clean) {
            return typesIntegrations[type_user][type_use][type] = [];
        }

        // se não existe a integração, é adicionada.
        if (!typesIntegrations[type_user][type_use][type].includes(integration)) {
            typesIntegrations[type_user][type_use][type].push(integration);
        }
    }

    const loadCredentialsIntegration = async () => {
        const integration = $('[name="integration_seller_use"]:checked').val();
        const store = $('[name="stores"]').val();
        let type_integration = null;

        if (integrationSelectedType && typesIntegrationsStore[integrationSelectedType]['integrator'].includes(integration)) {
            type_integration = 'integrator';
        } else if (integrationSelectedType && typesIntegrationsStore[integrationSelectedType]['carrier'].includes(integration)) {
            type_integration = 'carrier';
        }

        $("#formIntegrationLogistic .dataFormIntegration").empty().append('<h2 class="text-center"><i class="fa fa-spinner fa-spin"></i></h2>');

        const createElement = await createElementIntegration(integration, false);
        let credentials, typeElement;

        $.get(`${baseUrl}/logistics/getDataIntegration/${integration}/${type_integration}/${store}`, response => {
            $("#formIntegrationLogistic .dataFormIntegration").empty().append(createElement);

            if (response) {
                credentials = response.credentials ?? null;
                credentials = (typeof credentials === 'string' || credentials instanceof String) ? JSON.parse(credentials) : credentials;
                if (credentials !== null && credentials !== undefined) {
                    Object.keys(credentials).forEach(function (key) {
                        typeElement = $(`#formIntegrationLogistic .dataFormIntegration [name="${key}"]`).attr('type');
                        if (typeElement !== 'radio' && typeElement !== 'checkbox') {
                        } else {
                        }

                        if (typeElement === 'radio') {
                            $(`#formIntegrationLogistic .dataFormIntegration [name="${key}"][value="${credentials[key]}"]`).iCheck('check');
                        } else if (typeElement === 'checkbox') {
                            $(credentials[key]).each(function(k, v){
                                $(`#formIntegrationLogistic .dataFormIntegration [name="${key}"][value="${v}"]`).iCheck('check');
                            })
                        } else {
                            $(`#formIntegrationLogistic .dataFormIntegration [name="${key}"]`).val(credentials[key]);
                        }
                    });
                }
            }

            $(`#formIntegrationLogistic .dataFormIntegration .icheck_integration_selected`).iCheck({
                checkboxClass: 'icheckbox_square-blue',
                radioClass: 'iradio_square-blue',
                increaseArea: '20%' // optional
            });

        }).fail(e => {
            console.log('getDataIntegration',e);
        });
    }

    const updateCredentials = () => {
        $('#modal_logitic_integration_credential').modal();
    }

    function getIntegrationSaveSellerData() {
        let typeIntegrationCurrent = $(`input[name^="integration_seller_use"][data-integration-type-current]`).data('integrationTypeCurrent') ?? '';
        let integrationCurrent = $(`input[name^="integration_seller_use"][data-integration-current]`).data('integrationCurrent') ?? '';
        let typeIntegration = $(`input[name^="integration_seller_use"]:checked`).data('typeIntegration') ?? '';

        return {
            typeIntegrationCurrent: typeIntegrationCurrent,
            integrationCurrent: integrationCurrent,
            typeIntegration: typeIntegration
        };
    }
</script>