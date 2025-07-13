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
                    <div class="box-body">
                        <div class="col-md-5">
                            <label for="filter_transport">Filtro por Loja</label>
                            <select class="form-control" onchange="getListTableData(this);" name="filter_transport" id="filter_transport">
                                <?=count($stores) != 1 ? '<option value=""></option>' : ''?>
                                <?php foreach($stores as $value) { ?>
                                    <option value="<?=$value['id']?>"><?=$value['name']?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="box box-primary">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="console-event"></div>
                                <table id="tableIntegration" class="table table-bordered table-striped mt-5">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 25%"></th>
                                            <th class="text-center" style="width: 15%"></th>
                                            <th class="text-center" style="width: 25%"></th>
                                            <th class="text-center" style="width: 10%"></th>
                                            <th class="text-center" style="width: 25%"></th>
                                        </tr>
                                    </thead>
                                </table>
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

<style>
    .border-radius-index {
        padding: 1px 6px;
        border-radius: 50%;
        margin-right: 10px;
    }

    #tableIntegration {
        border: 0;
    }

    #tableIntegration th, #tableIntegration td {
        border: 0;
    }

</style>
<script type="application/javascript" src="<?=base_url('assets/dist/js/pages/logistic.js')?>"></script>
<script type="text/javascript">

var tableIntegration;
var baseUrl = "<?=base_url()?>";

$(document).ready(function() {
    $('#mainLogisticsNav').addClass('active');
    $('#manageLogisticNav').addClass('active');
    $('[name="filter_transport"]').select2();
    var storeId = store_id = $( "#filter_transport option:selected" ).val();
    if (storeId != "") {
        $("#tableIntegrations").dataTable().fnDestroy();
        onloadTable(storeId);
    } else {
        onloadTable();
    }
});

function sendFormCredential(idForm,idModal) {
    let dataForm    = $('#'+idForm+'').serialize();
    const store     = $('[name="filter_transport"]').val();

    if (dataForm !== '') dataForm += '&';
    else dataForm += '?';

    dataForm += `store_id=${store}`;

    $.ajax({
        url: baseUrl+"shippingcompany/updateCredentialIntegration",
    	type: "POST",
        data: dataForm,
        async: true,
        success: function(obj) {
            if (("success" in obj)==true){obj.response = "success";}

            if (("error" in obj)==true){obj.response = "error";}

            if(obj.response == "success"){
                Toast.fire({
                    icon: 'success',
                    title: obj.message
                });
                $('#tableIntegration').DataTable().ajax.reload();
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

                $('#tableIntegration').DataTable().ajax.reload();
            }  
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }
    });
}

async function modalIntegration(integration, element, type_integration, store_id) {
    const id            = $(element).attr('data-id');
    const credential    = await $.post(baseUrl+"shippingcompany/getCredentialIntegration", {"id": id, integration, type_integration, store_id}, function(data, status){});
    let credentials     = credential.credentials;
    let typeElement;

    if (typeof credentials == "string") {
        credentials = JSON.parse(credentials);
    }
    

    $("#formIntegrationLogistic .dataFormIntegration").empty().append('<h2 class="text-center"><i class="fa fa-spinner fa-spin"></i></h2>');

    const htmlForm = await getFieldCredentials(baseUrl, integration, '', true);

    $("#formIntegrationLogistic .dataFormIntegration").empty().append(htmlForm);

    $(`#modal_logitic_integration_credential`).find('[name="type_integration_ms"]').val(type_integration);
    $(`#modal_logitic_integration_credential`).find('[name="integration"]').val(integration);
    $(`#modal_logitic_integration_credential`).modal('show');

    if (credentials !== '{}') {
        Object.keys(credentials).forEach(function (key) {
            typeElement = $(`[name="${key}"]`).attr('type');
            if (typeElement !== 'radio' && typeElement !== 'checkbox')
                $(`[name="${key}"]`).val(credentials[key]);
            else
                $(`[name="${key}"][value="${credentials[key]}"]`).iCheck('check');
        });


        $(`input[class*="icheck_integration"]`).iCheck({
            checkboxClass: 'icheckbox_square-blue',
            radioClass: 'iradio_square',
            increaseArea: '20%'
        });
    }
     
}

function updateStatus(idTransportadora, element) {
    
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
                $('#tableIntegration').DataTable().ajax.reload();

            } else if(obj.response == "error"){
                Toast.fire({
                    icon: 'error',
                    title: obj.message
                });
                $('#tableIntegration').DataTable().ajax.reload();
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
        $("#tableIntegration").dataTable().fnDestroy();
        onloadTable(storeId);
    } else {
        Swal.fire({
            icon: 'warning',
            title: 'Informe uma loja no filtro',
            confirmButtonText: "Ok",
        });
        $('#tableIntegration').DataTable().ajax.reload();
        $('#tableIntegrations').DataTable().ajax.reload();
    }
}

function onloadTable(storeId = null) {
    $("#mainShippingCompanyNav").addClass('active');
    $("#manageShippingCompanyNav").addClass('active');

    // initialize the datatable
    tableIntegration = $('#tableIntegration').DataTable({
        'paging': false,
        "destroy": true,
        "language": { "url": "<?=base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang');?>" },
        'ajax': {
            "url": baseUrl + 'logistics/fetchIntegrationsData',
            "data": {
                "store_id": storeId
            }
        },
        "type": "POST",
        'providers': [],
        'fnDrawCallback': () => {
            $("input[data-bootstrap-switch]").each(function(result){
                $(this).bootstrapSwitch({size: "small"});
            })
        },
        "ordering": false,
        "searching": false,
        "info": false
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
                    $("#integration_div").html(response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(jqXHR,textStatus, errorThrown);
            }
        });
    }
}
</script>
