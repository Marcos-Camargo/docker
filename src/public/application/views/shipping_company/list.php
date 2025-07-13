<!--
SW Serviços de Informática 2019

Index de Fornecedores

-->

<!-- Content Wrapper. Contains page content -->

<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage"; $this->load->view('templates/content_header', $data);?>

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
                    <div class="box box-primary <?=count($stores) == 1 ? 'd-none' : ''?>">
                        <div class="box-body d-flex align-items-center justify-content-between flex-wrap">
                            <div class="col-md-5">
                                <label for="filter_transport">Filtro por Loja</label>
                                <select class="form-control" onchange="getListTableData(this);" name="filter_transport" id="filter_transport">
                                    <?php if (count($stores) != 1) {?><option value=""></option><?php } ?>
                                    <?php foreach($stores as $key => $value) { ?>
                                        <option value="<?=$value['id']?>" <?=set_select('addr_uf', $value['id'], $value['id'] == $store_id_selected) ?>><?=$value['name']?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <li class="active"><a href="#shipping_companies" data-toggle="tab"><?=$this->lang->line('application_shipping_price_shipping_companies')?></a></li>
                            <li><a href="#integrations" data-toggle="tab"><?=$this->lang->line('application_integrations')?></a></li>
                        </ul>
                        <div class="tab-content col-md-12">
                            <div class="tab-pane active" id="shipping_companies">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="console-event"></div>
                                        <table id="tableShippingCompanies" class="table table-bordered table-striped mt-5">
                                            <thead>
                                                <tr>
                                                    <th class="text-center"><?=$this->lang->line('application_id');?></th>
                                                    <th class="text-center"><?=$this->lang->line('application_store');?></th>
                                                    <th class="text-center"><?=$this->lang->line('application_ship_company');?></th>
                                                    <th class="text-center"><?=$this->lang->line('application_status');?></th>
                                                    <th class="text-center"><?=$this->lang->line('application_table_shipping');?></th>
                                                    <th class="text-center"></th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="integrations">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="console-event"></div>
                                        <table id="tableIntegrations" class="table table-bordered table-striped mt-5">
                                            <thead>
                                            <tr>
                                                <th class="text-center"><?=$this->lang->line('application_id');?></th>
                                                <th class="text-center"><?=$this->lang->line('application_store');?></th>
                                                <th class="text-center"><?=$this->lang->line('application_integration');?></th>
                                                <th class="text-center"><?=$this->lang->line('application_credentials');?></th>
                                                <th class="text-center"><?=$this->lang->line('application_status');?></th>
                                            </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </section>
</div>

<div class="modal fade" id="modal_logitic_integration_credential" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Credenciais</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="formAlertMsg">
        </div>
        <form class="row" id="formIntegrationLogistic">
            <div class="col-md-12 dataFormIntegration"></div>
            <input type="hidden" name="type_integration_ms">
            <input type="hidden" name="integration">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-primary" onclick="sendFormCredential('formIntegrationLogistic','modal_logitic_integration_credential');">Salvar</button>
      </div>
    </div>
  </div>
</div>

<div id="integration_div"></div>

<div class="modal fade" id="modal_frete_simplificado">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gray color-palette">
                <h4 class="modal-title">Preenchimento simplificado
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </h4>
            </div>
            <div class="modal-body" style="text-align:justify;">
                <span>
                    No preenchimento simplificado, você será direcionado para uma nova página onde deve informar os dados
                    referentes à sua tabela de frete como <b>regiões do país</b>, <b>prazo de entrega</b> e <b>valor do frete</b>. </br></br>
                    Ao concluir o preenchimento, você deve clicar em <b>"Salvar"</b> e pronto! As informações de sua tabela de
                    frete estarão cadastrada no sistema.
                </span>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                <a href="<?php echo base_url('shippingcompany/tableshippingsimplified/')?>" class="btn btn-primary"> Continuar</a>
            </div>
        </div>
    </div>
</div>

<style>
   .box-body:before,
   .box-body:after {
       display: none;
   }

   div.dt-buttons {
       float: right;
       width: 20%;
   }

   #tableShippingCompanies_filter{
       float: left;
       width: 30%;
   }

   #tableShippingCompanies_filter label,
   #tableShippingCompanies_filter input[type="search"] {
       width: 100%;
   }

   #tableShippingCompanies,
   #tableIntegrations {
       width: 100% !important;
   }
</style>
<script type="application/javascript" src="https://cdn.datatables.net/buttons/2.3.2/js/dataTables.buttons.min.js"></script>
<script type="application/javascript" src="<?=base_url('assets/dist/js/pages/logistic.js')?>"></script>
<script type="text/javascript">


var tableShippingCompanies;
var tableIntegrations;
var baseUrl = "<?=base_url()?>";

function removeAlert()
{
    $(".sucesso").remove();
    $(".erro").remove();
    $(".warning").remove();
}

async function sendFormCredential(idForm,idModal) {
   
    removeAlert();
    const type_integration_ms   = $('#modal_logitic_integration_credential [name="type_integration_ms"]').val();
    const integration           = $('#modal_logitic_integration_credential [name="integration"]').val();
    const store_id              = $('[name="filter_transport"]').val();
    const btn                   = $('[onclick^="sendFormCredential"]');

    // disabled fields
    btn.prop('disabled', true);

    // get data inputs
    let name, value, checked, label;
    let data = [];
    let msgError = [];
    let ignore_name = [];
    ignore_name.push('type_integration_ms');
    ignore_name.push('integration');
    await $(`#${idForm} [name]`).each(function () {
        name = $(this).attr('name');
        value = $(this).val();
        checked = $(this).is(':checked');
        label = $(this).closest('.form-group').find('label:first').text();

        if (ignore_name.includes(name)) {
            return true;
        }

        if ($(this).attr('type') === 'checkbox') {
            value = $(`#${idForm} [name="${name}"]:checked`).map(function (d, i) {
                return $(i).val();
            }).toArray();
        } else if ($(this).attr('type') === 'radio') {
            if (!$(`#${idForm} [name="${name}"]`).is(':checked')) {
                msgError.push(`Campo ${label} precisa ser selecionado.`);
            }
            value = $(`#${idForm} [name="${name}"]:checked`).val();
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

    $.ajax({
        url: baseUrl+"shippingcompany/updateCredentialIntegration",
    	type: "POST",
        data: {
            store_id,
            type_integration_ms,
            integration,
            data
        },
        async: true,
        success: function(obj) {
            if (("success" in obj)==true){obj.response = "success";}

            if (("error" in obj)==true){obj.response = "error";}

            if(obj.response == "success"){
                Toast.fire({
                    icon: 'success',
                    title: obj.message
                });
                $('#tableShippingCompanies').DataTable().ajax.reload();
                $('#modal_logitic_integration_credential').modal('hide');

            } else if(obj.response == "error"){
                var alertMsg = "";
                $.each(obj.error, function(i, item) {
                    $.each(item, function(z, msg) {
                        alertMsg = "Campo: " + z + " " + msg
                    });
                });

                Toast.fire({
                    icon: 'error',
                    title: alertMsg
                });

                $('#tableShippingCompanies').DataTable().ajax.reload();
            }  
        }, error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }, always: () => {
            btn.prop('disabled', false);
        }
    });
}

async function modalIntegration(idModal, integration, element, type_integration, store_id) {
    const id            = $(element).attr('data-id');
    const credential    = await $.post(baseUrl+"shippingcompany/getCredentialIntegration", {"id": id, integration, type_integration, store_id}, function(data, status){});
    let credentials     = credential.credentials;
    let typeElement;

    if (typeof credentials == "string") {
        credentials = JSON.parse(credentials);
    }

    const htmlForm = await getFieldCredentials(baseUrl, integration, '', true);

    $("#formIntegrationLogistic .dataFormIntegration").empty().append(htmlForm);
    $(`#${idModal}`).find('[name="type_integration_ms"]').val(type_integration);
    $(`#${idModal}`).find('[name="integration"]').val(integration);
    $(`#${idModal}`).modal('show');


    let lengthCredential = credentials.length;
    if (typeof lengthCredential == 'undefined') {
        lengthCredential = Object.getOwnPropertyNames(credentials).length;
    }

    if (lengthCredential) {
        Object.keys(credentials).forEach(function (key) {
            typeElement = $(`[name="${key}"]`).attr('type');
            if (typeElement === 'radio') {
                $(`[name="${key}"][value="${credentials[key]}"]`).iCheck('check');
            } else if (typeElement === 'checkbox') {
                $(credentials[key]).each(function(k, v){
                    $(`[name="${key}"][value="${v}"]`).iCheck('check');
                })
            } else {
                $(`[name="${key}"]`).val(credentials[key]);
            }
        });
    }

    $(`input[class*="icheck_integration"]`).iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
        increaseArea: '20%'
    });
}

function openModal(id, store) {
    $(window).on('shown.bs.modal', function () {
        $('#modal_frete_simplificado a').attr('href',baseUrl+`shippingcompany/tableshippingsimplified/${id}/${store}`);
    });
}

function updateStatus(idTransportadora, freight_seller, element) {
    
    var company_id = "";
    var store_id = $( "#filter_transport option:selected" ).val();

    <?php if ((int) $this->session->userdata['usercomp'] == 1) {?>        
        company_id =  $( "#filter_company option:selected" ).val();        
    <?php } ?>

	$.ajax({
        url: baseUrl+"shippingcompany/updateStatusShippingCompany",
    	type: "POST",
        data: {
            id_transportadora: idTransportadora,
            status: element.prop("checked") ? 1 : 0,
            storeId: store_id,
            freight_seller,
            companyId: company_id
        },
        async: true,
        success: function(response) {
            var obj = JSON.parse(response);
            if (("success" in obj)==true){obj.response = "success";}

            if (("error" in obj)==true){obj.response = "error";}

            if(obj.response == "success"){
                Toast.fire({
                    icon: 'success',
                    title: obj.message
                });
                $('#tableShippingCompanies').DataTable().ajax.reload();

            } else if(obj.response == "error"){
                Toast.fire({
                    icon: 'error',
                    title: obj.message
                });
                $('#tableShippingCompanies').DataTable().ajax.reload();
            }

            setTimeout(function(){ 
                    if($(".sucesso")){
                        $(".sucesso").remove(); 
                    } if($(".erro")){
                        $(".erro").remove(); 
                    }
                }, 7000);
               
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }
    });
}

function updateStatusIntegration(idIntegration,element, type_integration, integration) {
    var store_id = $( "#filter_transport option:selected" ).val();
    var company_id = "";

    <?php if ((int) $this->session->userdata['usercomp'] == 1) {?>        
        company_id =  $( "#filter_company option:selected" ).val();        
    <?php } ?>
    
	$.ajax({
        url: baseUrl+"shippingcompany/updateStatusIntegration",
    	type: "POST",
        data: {
            id_integration: idIntegration,
            status: element.prop("checked") ? 1 : 0,
            storeId: store_id,
            companyId: company_id,
            integration: integration,
            type_integration
        },
        async: true,
        success: function(response) {
            var obj = JSON.parse(response);
            if (("success" in obj)==true){obj.response = "success";}

            if (("error" in obj)==true){obj.response = "error";}

            if(obj.response == "success"){
                Toast.fire({
                    icon: 'success',
                    title: obj.message
                });
                $('#tableIntegrations').DataTable().ajax.reload();

            } else if(obj.response == "error"){
                Toast.fire({
                    icon: 'error',
                    title: obj.message
                });
                $('#tableIntegrations').DataTable().ajax.reload();
            }

            setTimeout(function(){ 
                if($(".sucesso")){
                    $(".sucesso").remove(); 
                } if($(".erro")){
                    $(".erro").remove(); 
                }
            }, 7000);
               
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }
    });
}

function getListTableData(sel) {
    var storeId = sel.value;
    if(storeId != "") {
        $(".sucesso").remove();
        $(".erro").remove();
        $(".warning").remove();
        var companyId = "";
        $("#tableShippingCompanies").dataTable().fnDestroy();
        onloadTable(companyId,storeId);
    } else {
        Swal.fire({
            icon: 'warning',
            title: 'Informe uma loja no filtro',
            confirmButtonText: "Ok",
        });
        $('#tableShippingCompanies').DataTable().ajax.reload();
        $('#tableIntegrations').DataTable().ajax.reload();
    }
}

function onloadTable(companyId = null, storeId = null) {
    $("#mainShippingCompanyNav").addClass('active');
    $("#manageShippingCompanyNav").addClass('active');

    // initialize the datatable
    tableShippingCompanies = $('#tableShippingCompanies').DataTable({
        'paging': false,
        "destroy": true,
        "language": { "url": "<?=base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang');?>" },
        'ajax': {
            "url": baseUrl + 'shippingcompany/fetchShippingCompanyData',
            "data": {
                "store_id": storeId,
                "company_id": companyId
            }
        },
        "type": "POST",
        'providers': [],
        'fnDrawCallback': function(result){
            $("input[data-bootstrap-switch]").each(function(result){
                $(this).bootstrapSwitch({size: "small"});
            })
        },
        'columnDefs': [{
            "targets": '_all',
            "className": "text-center",
        }],
        dom: "Bfrtip",
        "buttons": [
            {
                text: '<i class="fa fa-plus"></i> Adicionar Transportadora',
                className: 'btn btn-success col-md-12',
                action: () => {
                    window.location.href = "<?=base_url($this->session->userdata['usercomp'] == 1 ? 'shippingcompany/create' : 'shippingcompany/createsimplified')?>"
                }
            }
        ]
    });

    // initialize the datatable
    tableIntegrations = $('#tableIntegrations').DataTable({
        'paging': false,
        "destroy": true,
        "language": { "url": "<?=base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang');?>" },
        'ajax': {
            "url": baseUrl + 'shippingcompany/fetchIntegrationsData',
            "data": {
                "store_id": storeId,
                "company_id": companyId
            }
        },
        "type": "POST",
        'providers': [],
        'fnDrawCallback': function(result){
            $("input[data-bootstrap-switch]").each(function(result){
                $(this).bootstrapSwitch({size: "small"});
            })
        },
        'columnDefs': [{
            "targets": '_all',
            "className": "text-center",
        }]
    });

    if (storeId !== null) {
        $.ajax({
            url: baseUrl + "shippingcompany/fetchModalIntegrations",
            type: "POST",
            data: {
                store_id: storeId
            },
            async: true,
            success: function(response) {
                if (response !== "") {
                    document.getElementById("integration_div").innerHTML = response;
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(jqXHR,textStatus, errorThrown);
            }
        });
    }

}

$(document).ready(function() {
    $("#mainLogisticsNav").addClass('active');
    $("#carrierRegistrationNav").addClass('active');
    $('[name="filter_transport"]').select2();
    var storeId = store_id = $( "#filter_transport option:selected" ).val();
    if (storeId != "") {
        $(".sucesso").remove();
        $(".erro").remove();
        $(".warning").remove();
        var companyId = "";
        $("#tableShippingCompanies").dataTable().fnDestroy();
        $("#tableIntegrations").dataTable().fnDestroy();
        onloadTable(companyId,storeId);
    } else {
        onloadTable();
    }
});

$(document).on('click', '.remove-shipping-company', function (){
    const shipping_company = $(this).data('shipping-company');

    Swal.fire({
        title: 'Tem certeza que deseja excluir a transportadora?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Excluir transportadora',
        cancelButtonText: 'Cancelar Operação'
    }).then((result) => {
        if (result.value) {
            $.post(baseUrl + "shippingcompany/removeShippingCompany", { shipping_company }, response => {
                Toast.fire({
                    icon: response.success ? 'success' : 'error',
                    title: response.message
                });

                if (response.success) {
                    $('#tableShippingCompanies').DataTable().ajax.reload();
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
</script>
