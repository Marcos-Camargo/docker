<!--
SW Serviços de Informática 2019

Ver Profile

-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_myprofile";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
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
                <div class="box">
                    <div class="box-body">
                        <?php
                        if (validation_errors()) {
                            foreach (explode("</p>",validation_errors()) as $erro) {
                                $erro = trim($erro);
                                if ($erro!="") { ?>
                                    <div class="alert alert-error alert-dismissible" role="alert">
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <?php echo $erro."</p>"; ?>
                                    </div>
                                <?php	}
                            }
                        } ?>
                        <div class="row mb-5">
                            <div class="form-group col-md-12 text-center">
                                <h3 class="text-uppercase"><?=$this->lang->line('application_webhook')?></h3>
                            </div>
                        </div>

                        <div class="row d-flex justify-content-center mb-5" style="display: <?=count($storesView) == 1 ? 'none' : 'flex'?>">
                            <div class="form-group col-md-6">
                                <label>Seleciona uma loja para cadastrar a url webhook</label>
                                <select class="form-control select2" name="storeFilter" id="storeFilter" required>
                                        <option value="" selected>-- SELECIONE UMA LOJA --</option>
                                    <?php
                                    foreach ($storesView as $storeView)
                                        echo "<option value='{$storeView['id']}' " . set_select('store', $storeView['id'], false) . ">{$storeView['name']}</option>";
                                    ?>
                                </select>
                                <input type="hidden" name="storeValue" id="storeValue" value="<?=$storeView['id']?>">
                            </div>
                        </div>
                        
                    <div class="row d-flex justify-content-center flex-wrap group-integration">
                        <form id="formWebhook" action="" method="POST" enctype="multipart/form-data">
                                <div role="document">
                                        <div class="modal-body" id="modal-body">
                                            <div id="webhooks-container">
                                                <div class="form-group">
                                                    <div class="row">
                                                        <div class="col-md-10">
                                                            <label for="url-webhook">URL</label>
                                                            <input type="text" class="form-control" name="url-webhook[${formGroupCount}][]" value="" placeholder="Digite a URL que será notificada">
                                                        </div>
                                                        <div class="col-md-12">
                                                            <label for="eventos-webhook"></label>
                                                            <div class="d-flex justify-content-space-between">
                                                                <div class="form-check form-check-inline mr-3">
                                                                    <input class="form-check-input" type="checkbox" value=<?=$this->lang->line('application_order_create')?> name="eventos-webhook[${formGroupCount}][]" ${arrayItem.type_webhook && arrayItem.type_webhook.includes("pedido_criado") ? 'checked' : ''}>
                                                                    <label class="form-check-label" for="pedido-criado">
                                                                        Pedido Criado
                                                                    </label>
                                                                </div>
                                                                <div class="form-check form-check-inline mr-3">
                                                                    <input class="form-check-input" type="checkbox" value=<?=$this->lang->line('application_order_paid')?> name="eventos-webhook[${formGroupCount}][]" ${arrayItem.type_webhook && arrayItem.type_webhook.includes("pedido_pago") ? 'checked' : ''}>
                                                                    <label class="form-check-label" for="pedido-pago">
                                                                        Pedido Pago
                                                                    </label>
                                                                </div>
                                                                <div class="form-check form-check-inline mr-3">
                                                                    <input class="form-check-input" type="checkbox" value=<?=$this->lang->line('application_order_cancel')?> name="eventos-webhook[${formGroupCount}][]" ${arrayItem.type_webhook && arrayItem.type_webhook.includes("pedido_cancelado") ? 'checked' : ''}>
                                                                    <label class="form-check-label" for="pedido-cancelado">
                                                                        Pedido Cancelado
                                                                    </label>
                                                                </div>
                                                                <button type="button" class="btn btn-outline-danger remove-webhook" title="Clique para remover a url">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="webhooks-button" >
                                            <button type="button" id="add-webhook" class="btn btn-primary">Adicionar URL</button>
                                            <button type="submit" id="submitWebhook"  class="btn btn-success">Salvar Webhooks</button>         
                                        </div> 
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div id="MODALNEWINTEGRATION"></div>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
<script>
    
      
    $(document).ready(function() {
        
    $('#webhooks-button').hide();
    
    let formGroupCount = 0;

        
    function createNewFormGroup() {
        // Verifica se já existem três URLs adicionadas
        if (formGroupCount === 3) {
            Swal.fire({
                    icon: 'warning',
                    title: 'Atenção',
                    html: 'Você pode cadastrar somente 3 webhooks'
                });
            return;
        }

        // Cria um novo formulário de grupo
        let formGroups = `
            <div class="form-group">
                <div class="row">
                    <div class="col-md-10">
                        <label for="url-webhook">URL</label>
                        <input type="text" class="form-control" name="url-webhook[${formGroupCount}][]" value="" placeholder="Digite a URL que será notificada">
                    </div>
                    <div class="col-md-12">
                        <label for="eventos-webhook"></label>
                        <div class="d-flex justify-content-space-between">
                            <div class="form-check form-check-inline mr-3">
                                <input class="form-check-input" type="checkbox" value=<?=$this->lang->line('application_order_create')?> name="eventos-webhook[${formGroupCount}][]">
                                <label class="form-check-label" for="pedido-criado">
                                    Pedido Criado
                                </label>
                            </div>
                            <div class="form-check form-check-inline mr-3">
                                <input class="form-check-input" type="checkbox" value=<?=$this->lang->line('application_order_paid')?> name="eventos-webhook[${formGroupCount}][]">
                                <label class="form-check-label" for="pedido-pago">
                                    Pedido Pago
                                </label>
                            </div>
                            <div class="form-check form-check-inline mr-3">
                                <input class="form-check-input" type="checkbox" value=<?=$this->lang->line('application_order_cancel')?> name="eventos-webhook[${formGroupCount}][]">
                                <label class="form-check-label" for="pedido-cancelado">
                                    Pedido Cancelado
                                </label>
                            </div>
                            <button type="button" class="btn btn-outline-danger remove-webhook" title="Clique para remover a url">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        formGroupCount++; // Incrementa o contador de formulários de grupo

        return formGroups;
    }
   
      
        $(document).on('click', '#add-webhook', function() {
            const newFormGroup = createNewFormGroup(); 
            if (newFormGroup) {
                $("#webhooks-container").append(newFormGroup);
                $("#submitWebhook").show(); 
            }
           
        });

        $(document).on("click", ".remove-webhook", function() {

                var storeId = $('#storeFilter').val();
                let url1 = "<?= base_url('integrations/deleteGroupFormDataWebhook') ?>";

                var formGroup = $(this).closest('.form-group');
                var urlValue = formGroup.find('[name^="url-webhook"]').val();

        
                $.ajax({
                    url: url1,
                    type: 'POST',
                    data: { storeId: storeId,
                            nameUrl: urlValue            
                          }, 
                    dataType: 'json',
                    success: function(response) {
                        if (response['success'] == 1) {
                            Swal.fire(
                            '<?=$this->lang->line("messages_successfully_removed")?>',
                            response['data'],
                            'Sucesso'
                            );
                        } 
                    }, 
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
               
                $(this).closest('.form-group').remove();
                formGroupCount--;
                
                var webhooksContainer = $("#webhooks-container");
                if (webhooksContainer.children().length === 0) {
                    $("#submitWebhook").hide();
                }
 
        });

        $('#storeFilter').change(function() {
            formGroupCount = 0;
            var storeId = $(this).val(); // Obtém o ID da loja selecionada
            let url = "<?= base_url('integrations/getModalDataWebhook') ?>";

            if(storeId == null || storeId === ""){
                var webhooksContainer = $("#webhooks-container");
                webhooksContainer.empty();
                $('#webhooks-button').hide();
               return;
            }


            $.ajax({
                url: url,
                type: 'GET',
                data: { storeId: storeId }, 
                dataType: 'json',
                success: function(response) {
                    if (response['success'] == 1) {
                        Swal.fire(
                            '<?=$this->lang->line("no_find_type_webhook")?>',
                            response['data'],
                            'error'
                        );
                    } else {
                        $('#webhooks-button').show();
                        preencherModal(response);
                        var webhooksContainer = $("#webhooks-container");
                        if (webhooksContainer.children().length === 0) {
                            $("#submitWebhook").hide();
                        }else{
                            $("#submitWebhook").show();
                        }
                    }
                }, 
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        });

        function preencherModal(data) {
            var webhooksContainer = $("#webhooks-container");
                webhooksContainer.empty();
            
            for (let i = 0; i < data.length; i++) {
                const item = data[i];
                
                // Cria um novo formulário de grupo
                let newFormGroup = `
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-10">
                                <label for="url-webhook">URL</label>
                                <input type="text" class="form-control" name="url-webhook[${i}][]" value="${item.url}" placeholder="Digite a URL que será notificada">
                            </div>
                            <div class="col-md-12">
                                <label for="eventos-webhook"></label>
                                <div class="d-flex justify-content-space-between">
                                    <div class="form-check form-check-inline mr-3">
                                        <input class="form-check-input" type="checkbox" value=<?=$this->lang->line('application_order_create')?> name="eventos-webhook[${i}][]" ${item.type_webhook.includes("pedido_criado") ? 'checked' : ''}>
                                        <label class="form-check-label" for="pedido-criado">
                                            Pedido Criado
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline mr-3">
                                        <input class="form-check-input" type="checkbox" value=<?=$this->lang->line('application_order_paid')?> name="eventos-webhook[${i}][]" ${item.type_webhook.includes("pedido_pago") ? 'checked' : ''}>
                                        <label class="form-check-label" for="pedido-pago">
                                            Pedido Pago
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline mr-3">
                                        <input class="form-check-input" type="checkbox" value=<?=$this->lang->line('application_order_cancel')?> name="eventos-webhook[${i}][]" ${item.type_webhook.includes("pedido_cancelado") ? 'checked' : ''}>
                                        <label class="form-check-label" for="pedido-cancelado">
                                            Pedido Cancelado
                                        </label>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger remove-webhook" title="Clique para remover a url">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                webhooksContainer.append(newFormGroup);

                formGroupCount++;
            }
        }

        $('#submitWebhook').click(function(e) {
            e.preventDefault();            
            const baseUrl = "<?=base_url('integrations/saveUrlCallbackintegration')?>";
            
            var storeId = $('#storeFilter').val();   
            if(storeId == null || storeId === ""){
                storeId = $('#storeValue').val();
            }
            
            var formData = $('#formWebhook').serialize();
            formData += '&storeId=' + storeId;

                        // Inicia a variável para rastrear se há algum bloco inválido
            var anyBlockInvalid = false;

            // Itera sobre cada bloco individualmente
            $('[name^="url-webhook"]').each(function(index) {
                // Obtém a URL do bloco atual
                var fieldValue = $(this).val().trim();

                // Verifica se a URL está preenchida
                if (fieldValue === '' || fieldValue.length === 0) {
                    Swal.fire(
                        'Erro',
                        'Por favor, o campo URL deve ser preenchido para todos os blocos.',
                        'error'
                    );
                    anyBlockInvalid = true; // Define que há um bloco inválido
                    return false; // Sai do loop se encontrar um campo vazio
                }

                // Verifica se pelo menos um tipo de evento está selecionado para o bloco atual
                var eventTypeCheckboxes = $('[name^="eventos-webhook[' + index + ']"]');
                var eventTypeChecked = false;
                var checkedCount = 0; // Contador de checkboxes selecionados

                // Itera sobre cada checkbox no bloco atual
                eventTypeCheckboxes.each(function() {
                    if ($(this).is(':checked')) {
                        eventTypeChecked = true;
                    }
                    checkedCount++;
                });

                // Verifica se pelo menos um tipo de evento está selecionado em cada conjunto de 3 checkboxes
                if (checkedCount === 3 && !eventTypeChecked) {
                    Swal.fire(
                        'Erro',
                        'Por favor, preencha pelo menos um tipo de evento para todos os blocos.',
                        'error'
                    );
                    anyBlockInvalid = true; // Define que há um bloco inválido
                    return false; // Sai do loop se não encontrar um tipo selecionado
                }
            });

            // Se houver algum bloco inválido, retorna para interromper a execução
            if (anyBlockInvalid) {
                return;
            }

            $.ajax({
                    type: "POST",
                    url: baseUrl,
                    data: formData,
                    success: response => {
                        if (response['success'] == 1) {
                            Swal.fire(
                                '<?=$this->lang->line("post_error_saving")?>',
                                response['data']
                                
                            );
                        } else if(response['success'] == 2 ){
                            Swal.fire(
                                    '<?=$this->lang->line("no_find_type_webhook")?>',
                                    response['data']
                                    
                                );
                        }else if(response['success'] == 3 ){
                            Swal.fire(
                                    '<?=$this->lang->line("missing_type_webhook")?>',
                                    response['data']
                                    
                                );
                        }else {
                            Swal.fire(
                                    '<?=$this->lang->line("messages_successfully_updated")?>'
                                );
                        }
                        $('[type="checkbox"]').attr('disabled', false);
                    }, error: e => {
                        console.log(e);
                    }
                })
        });

        var webhooksContainer = $("#webhooks-container");
            webhooksContainer.empty()
    });

</script>
<style>
    .widget-user .box-footer{
        border-left: 1px solid #eee;
        border-right: 1px solid #eee;
    }
    .widget-user-image img{
        border: 0px !important;
    }
    .box-widget .widget-user-header h3{
        color: #fff;
    }
    .widget-user .widget-user-header{
        height: 115px;
    }
    .widget-user .widget-user-image{
        top: 40px
    }
    .modal-footer::before,
    .modal-footer::after{
        display: none;
    }
    .group-integration:not(:nth-child(3)) {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px dashed #8d8d8d;
        width: 98%;
        margin-left: 1%;
    }

    /* colors ERP */
    .box-widget.default-integration .widget-user-header{
        background-color: #fff;
        border: 1px solid #eee;
    }
    .box-widget.default-integration .widget-user-image {
        display: flex;
        justify-content: center;
        left: unset;
        margin-left: unset;
        top: 45px;
        position: absolute;
        width: 100%;
    }
    .box-widget.default-integration .widget-user-image>img {
        width: 150px;
        height: 50px;
    }



    .box-widget.bling .widget-user-header{
        background-color: #3ca710
    }
    .box-widget.tiny .widget-user-header{
        background-color: #81aaf3
    }
    .box-widget.vtex .widget-user-header{
        background-color: #ffa2c1
    }
    .box-widget.shopify .widget-user-header{
        background-color: #6b872f
    }
    .box-widget.bseller .widget-user-header{
        background-color: #17A086
    }
    .box-widget.novomundo .widget-user-header{
        background-color: #fff;
    }
    .box-widget.jn2 .widget-user-header{
        background-color: #1a212d;
    }
    .box-widget.anymarket .widget-user-header{
        background-color: rgb(39,86,179);
    }
    .box-widget.lojaintegrada .widget-user-header{
        background-color: #E4F6F7;
    }
    .box-widget.precode .widget-user-header{
        background-color: #FFFFFF;
    }
    .box-widget.viavarejo_b2b .widget-user-header{
        background-color: #aec5dd;
    }
    .box-widget.tray .widget-user-header{
        background-color: #FFFFFF;
    }
    .box-widget.hub2b .widget-user-header{
        background-color: #008800;
    }
    .box-widget.ideris .widget-user-header{
        background-color: #008800;
    }
    .box-widget.NEWINTEGRATION .widget-user-header{
        background-color: #FFFFFF;
    }
    
    .center {
        display: block;
        margin-left: auto;
        margin-right: auto;
        width: 50%;
    }
</style>
