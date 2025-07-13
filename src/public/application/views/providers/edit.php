
  <?php include_once(APPPATH . '/third_party/zipcode.php') ?>
<!--
SW Serviços de Informática 2019

Editar Fornecedores
-->
<div class="content-wrapper">
    
	  
	<?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data)  ?>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#fornecedor">Fornecedores</a></li>
                        <li id="tabWebhook"><a data-toggle="tab" href="#webhook">Webhook</a></li>
                    </ul>
             
    
        

                <div class="tab-content">
                    <div id="fornecedor" class="tab-pane active">  
                                <?php if($this->session->flashdata('success')): ?>
                                    <div class="alert alert-success alert-dismissible" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <?=$this->session->flashdata('success')?>
                                    </div>
                                <?php elseif($this->session->flashdata('error')): ?>
                                    <div class="alert alert-error alert-dismissible" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <?=$this->session->flashdata('error')?>
                                    <?=validation_errors()?>
                                    </div>
                                <?php endif ?>
                                    <h3 class="box-title"><?=$this->lang->line('application_update_information');?></h3>
                                   
                                    <form id="formPrincipal" role="form" action="<?php base_url('providers/update') ?>" method="post">
                                        <input type="hidden" id="crcli" name="crcli" value="S">
                                        
                                            <div class="row">
                                                <div class="form-group col-md-4">
                                                    <label for="name"><?=$this->lang->line('application_name');?>(*)</label>
                                                    <input type="text" class="form-control" id="name" name="name" required placeholder="<?=$this->lang->line('application_enter_user_fname')?>" autocomplete="off" value="<?=set_value('name', $fields['name'])?>">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="raz_soc"><?=$this->lang->line('application_raz_soc');?>(*)</label>
                                                    <input type="text" class="form-control" id="raz_soc" name="raz_soc" required placeholder="<?=$this->lang->line('application_enter_razao_social')?>" autocomplete="off" value="<?=set_value('raz_soc', $fields['razao_social'])?>">
                                                </div>
                                                <div class="form-group col-md-4">
                                            <label for="txt_insc_estadual"><?=$this->lang->line('application_iest');?></label>
                                            <input type="text" class="form-control" <?php echo $fields['insc_estadual'] == "0" ? 'disabled' : '' ?> id="txt_insc_estadual" name="txt_insc_estadual" placeholder="<?=$this->lang->line('application_iest')?>" autocomplete="off" value="<?=set_value('txt_insc_estadual', $fields['insc_estadual'])?>">
                                            <div class="form-check">
                                                <input class="form-check-input" <?php echo $fields['insc_estadual'] == "0" ? 'checked' : '' ?> type="checkbox" value="1" name="exempted" onchange="exemptIE()" id="exempted">
                                                <label class="form-check-label" for="exempted">
                                                <?= $this->lang->line('application_exempted'); ?>
                                                </label>
                                            </div>
                                            </div>
                                            </div>
                                                <div class="row">
                                                    <div class="col-md-5">
                                                        <label for="active_token_api"><?=$this->lang->line('application_token');?></label>
                                                            <br />
                                                        <div class="input-group">
                                                            <span class="input-group-addon" style="padding: 0; border: none;" >
                                                                <input type="checkbox" id="active_token_api" name="active_token_api" <?=set_checkbox('active_token_api', 'on', $fields['active_token_api'] == 1) ?> data-toggle="toggle" data-on="<?= $fields['token_api'] ? 'Ativado' : 'Gerar' ?>" data-off="<?= $fields['token_api'] ? 'Desativado' : 'Não gerado' ?>">
                                                            </span>
                                                            <input type="text" class="form-control" id="token_api" name="token_api" readonly value="<?=set_value('token_api', $fields['token_api'])?>">
                                                            <span class="input-group-btn">
                                                                <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>
                                                            </span>
                                                        </div>
                                                        <?php 
                                                            if (strlen($fields['token_api']) > 5) { ?>
                                                                <span class="label label-primary" data-toggle="tooltip" title="Dados complementares ao Token para acesso às APIs"><?='x-provider-key:'.str_repeat('&nbsp;', 3).$provider_data['id'].str_repeat('&nbsp;', 10).'x-email:'.str_repeat('&nbsp;', 3).$provider_data['responsible_email']?></span>
                                                            <?php }
                                                        ?>
                                                    </div>
                                                <div class="form-group col-md-3">
                                                    <label for="cnpj"><?=$this->lang->line('application_cnpj');?>(*)</label>
                                                    <input type="tel" class="form-control" id="cnpj" name="cnpj" required placeholder="<?=$this->lang->line('application_enter_CNPJ')?>" autocomplete="off" maxlength="18" size="18" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CNPJ',this,event);" value="<?=set_value('cnpj', $fields['cnpj'])?>">
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="phone_1"><?=$this->lang->line('application_phone');?>(*)</label>
                                                    <input type="tel" class="form-control" id="phone" name="phone" required placeholder="<?=$this->lang->line('application_enter_user_phone')?>" autocomplete="off" maxlength="15" size="15" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" value="<?=set_value('phone', $fields['phone'])?>">
                                                </div>
                                                <div class="form-group col-md-2 d-none">
                                                    <label for="addr_uf"><?=$this->lang->line('application_providers_type');?></label>
                                                    <select class="form-control" id="slc_tipo_provider" name="slc_tipo_provider" required>
                                                        <option value="Outros">Outros</option>
                                                    </select>
                                                    </div>
                                                    <div class="form-group col-md-1">
                                                        <label for="active"><?=$this->lang->line('application_active');?></label>
                                                        <br />
                                                        <input type="checkbox" class="minimal" id="active" name="active" <?=set_checkbox('active', 'on', $fields['active'] == 1) ?>>
                                                    </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-4">
                                                    <label for="responsible_name"><?=$this->lang->line('application_responsible_name');?>(*)</label>
                                                    <input type="text" class="form-control" id="responsible_name" name="responsible_name" required placeholder="<?=$this->lang->line('application_enter_responsible_name')?>" autocomplete="off" value="<?=set_value('responsible_name', $fields['responsible_name'])?>">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="responsible_email"><?=$this->lang->line('application_responsible_email');?>(*)</label>
                                                    <input type="text" class="form-control" id="responsible_email" name="responsible_email" required placeholder="<?=$this->lang->line('application_enter_responsible_email')?>" autocomplete="off" value="<?=set_value('responsible_email', $fields['responsible_email'])?>">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="responsible_cpf"><?=$this->lang->line('application_responsible_cpf');?>(*)</label>
                                                    <input type="tel" class="form-control" id="responsible_cpf" name="responsible_cpf"  placeholder="<?=$this->lang->line('application_enter_responsible_cpf')?>" autocomplete="off" maxlength="14" size="14" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" value="<?=set_value('responsible_cpf', $fields['responsible_cpf'])?>">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-2">
                                                    <label for="zipcode"><?=$this->lang->line('application_zip_code');?>(*)</label>
                                                    <input type="tel" class="form-control" id="zipcode" name="zipcode"  placeholder="<?=$this->lang->line('application_enter_zipcode')?>" autocomplete="off" maxlength="9" size="8" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CEP',this,event);" value="<?=set_value('zipcode', $fields['zipcode'])?>">
                                                </div>
                                                <div class="form-group col-md-8">
                                                    <label for="address"><?=$this->lang->line('application_address');?>(*)</label>
                                                    <input type="text" class="form-control" id="address" name="address"  placeholder="<?=$this->lang->line('application_enter_address')?>" autocomplete="off" value="<?=set_value('address', $fields['address'])?>">
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="addr_num"><?=$this->lang->line('application_number');?>(*)</label>
                                                    <input type="text" class="form-control" id="addr_num" name="addr_num"  placeholder="<?=$this->lang->line('application_enter_number')?>r" autocomplete="off" value="<?=set_value('addr_num', $fields['addr_num'])?>">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-4">
                                                    <label for="addr_compl"><?=$this->lang->line('application_complement');?></label>
                                                    <input type="text" class="form-control" id="addr_compl" name="addr_compl" placeholder="<?=$this->lang->line('application_enter_complement')?>" autocomplete="off" value="<?=set_value('addr_compl', $fields['addr_compl'])?>">
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="addr_neigh"><?=$this->lang->line('application_neighb');?>(*)</label>
                                                    <input type="text" class="form-control" id="addr_neigh" name="addr_neigh"  placeholder="<?=$this->lang->line('application_enter_neighborhood')?>" autocomplete="off" value="<?=set_value('addr_neigh', $fields['addr_neigh'])?>">
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="addr_city"><?=$this->lang->line('application_city');?>(*)</label>
                                                    <input type="text" class="form-control" id="addr_city" name="addr_city"  placeholder="<?=$this->lang->line('application_enter_city')?>" autocomplete="off" value="<?=set_value('addr_city', $fields['addr_city'])?>">
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="addr_uf"><?=$this->lang->line('application_uf');?>(*)</label>
                                                    <select class="form-control" id="addr_UF" name="addr_uf" >
                                                        <option value=""><?=$this->lang->line('application_select');?></option>
                                                        <?php foreach ($ufs as $k => $v): ?>
                                                            <option value="<?=trim($k)?>" <?=set_select('addr_uf', trim($k), $fields['addr_uf'] == trim($k))?>><?=$v ?></option>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                            </div>
                                        
                                            <div class="row">
                                            <div class="form-group col-md-3">
                                                <label for="slc_tipo_pagamento"><?=$this->lang->line('application_billet_type_payment');?>(*)</label>
                                                <select class="form-control" id="slc_tipo_pagamento" name="slc_tipo_pagamento" value="<?=set_value('tipo_pagamento')?>"> 
                                                    <option value=""><?=$this->lang->line('application_select');?></option>
                                                    <option value="Boleto" <?=set_select('slc_tipo_pagamento', "val_tipo_pagamento", $fields['tipo_pagamento'] == "Boleto")?>>Boleto</option>
                                                    <option value="Transferencia" <?=set_select('slc_tipo_pagamento', "val_tipo_pagamento", $fields['tipo_pagamento'] == "Transferencia")?>>Transferência Bancária</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3 d-none">
                                                <label for="addr_uf"><?=$this->lang->line('application_company');?></label>
                                                <select class="form-control" onchange="getListCompanyStores(this);" name="slc_company" id="slc_company">
                                                    <option value=""></option>
                                                    <?php foreach($company_list as $key => $value) { ?>
                                                        <?php if($fields['company_id'] == $value['id'] ) { ?>
                                                            <option value="<?php echo $value['id'];?>" selected ><?php echo $value['name'];?></option>
                                                        <?php } else { ?>
                                                            <option value="<?php echo $value['id'];?>"><?php echo $value['name'];?></option>
                                                        <?php } ?>								
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3 d-none">
                                                <label for="store"><?=$this->lang->line('application_store');?></label>
                                                <select class="form-control" name="slc_store" id="slc_store" >
                                                    <option value=""></option>							   
                                                    <?php foreach($stores as $key => $value ) { ?>                        
                                                        <?php if($fields['store_id'] == $value['id'] ) { ?>
                                                            <option value="<?php echo $value['id'];?>" selected ><?php echo $value['name'];?></option>
                                                        <?php } else { ?>
                                                            <option value="<?php echo $value['id'];?>"><?php echo $value['name'];?></option>
                                                        <?php } ?>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            </div>
                                        
                                            <div id="divTransferencia">
                                            
                                            <div class="row">
                                                <div class="form-group col-md-3">
                                                    <label for="bank"><?=$this->lang->line('application_bank');?></label>
                                                    <select class="form-control" id="bank" name="bank" >
                                                        <option value=""><?=$this->lang->line('application_select');?></option>
                                                        <?php foreach ($banks as $k => $v): ?>
                                                            <option value="<?=trim($v)?>" <?=set_select('bank', $v, $fields['bank'] == trim($v)) ?>><?=$v ?></option>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="agency"><?=$this->lang->line('application_agency');?></label>
                                                    <input type="text" class="form-control" id="agency" name="agency"  placeholder="<?=$this->lang->line('application_enter_agency')?>" autocomplete="off" value="<?=set_value('agency', $fields['agency'])?>">
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="currency"><?=$this->lang->line('application_type_account');?></label>
                                                    <select class="form-control" id="account_type" name="account_type" >
                                                        <option value=""><?=$this->lang->line('application_select');?></option>
                                                        <?php foreach ($type_accounts as $k => $v): ?>
                                                            <option value="<?=trim($v)?>" <?=set_select('account_type', trim($v), $fields['account_type'] == trim($v))?>><?=$v ?></option>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="account"><?=$this->lang->line('application_account');?></label>
                                                    <input type="text" class="form-control" id="account" name="account"  placeholder="<?=$this->lang->line('application_enter_bank_account')?>" autocomplete="off" value="<?=set_value('account', $fields['account'])?>">
                                                </div>
                                            </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="form-group col-md-12">
                                                    <label for="group_name"><?=$this->lang->line('application_extract_obs');?></label>
                                                    <textarea class="form-control" id="txt_observacao" name="txt_observacao" placeholder="<?=$this->lang->line('application_extract_obs');?>" ><?=set_value('"txt_observacao"', $fields['observacao'])?></textarea>
                                                </div>
                                            </div>

                                            <div class="row" id="parentDataApi">
                                                <div class="form-group col-md-4">
                                                    <label for="currency"><?=$this->lang->line('application_marketplace');?></label>
                                                    <select class="form-control" id="marketplace" name="marketplace" >
                                                        <option value="0"><?=$this->lang->line('application_select');?></option>
                                                        <?php foreach ($marketplaces as $k => $v): ?>
                                                            <option value="<?=$v['id']?>" <?=set_select('marketplace', $v['id'], $fields['marketplace'] == $v['id'])?>><?=$v['name']?></option>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="currency"><?=$this->lang->line('application_stores');?></label>
                                                    <select class="form-control selectpicker show-tick" id="stores" name ="stores[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                                                        <?php foreach ($stores as $store): ?>
                                                            <option value="<?=$store['id']?>" <?=set_select('marketplace', $store['id'], in_array($store['id'], $stores_by_provider))?>><?=$store['name']?></option>
                                                        <?php endforeach ?>
                                                    </select>
                                                    <small>Lojas que o fornecedor terá acesso</small>
                                                </div>
                                            </div>                                
                                                            
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                                                    <a href="<?=base_url('providers/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                                                </div>
                                            </div>
                                    </form>
                                          
                            
                    </div>

                    <div id="webhook" class="tab-pane fade">
                            <form id="formWebhook" action="" method="POST" enctype="multipart/form-data">
                                    <div id="webhooks-container" class="row">
                                                <div class="col-md-10 form-group">
                                                    <label for="url-webhook">URL</label>
                                                    <input type="text" class="form-control" name="url-webhook[${formGroupCount}][]" value="" placeholder="Digite a URL que será notificada">
                                                </div>
                                                <div class="col-md-12 form-group">
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
                                    <div id="webhooks-button">
                                        <button type="button" id="add-webhook" class="btn btn-primary">Adicionar URL</button>
                                        <button type="submit" id="submitWebhook" class="btn btn-success">Salvar Webhooks</button>         
                                    </div> 
                            </form>       
                    </div>  
                </div> 
             </div>
            </div>
        </div>                                              
    </section>   
</div>

<script type="text/javascript">
$(document).ready(function() {
	$("#slc_tipo_pagamento").trigger('change');
    $("#ad_valorem, #gris, #toll, #shipping_revenue,#slc_tipo_cubage").trigger('change');

	$("#slc_val_credito").trigger('change');

	$("#slc_val_ship_min").trigger('change');

	$("#slc_qtd_min").trigger('change');

    $('input[type="checkbox"].minimal').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass   : 'iradio_minimal-blue'
    });

    $("#mainProvidersNav").addClass('active');
    $("#manageProvidersNav").addClass('active');

    $('#active_token_api').trigger('change');



  
        let formGroupCount = 0;

        function createNewFormGroup() {
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
                <div class="col-md-10 form-group">
                    <label for="url-webhook">URL</label>
                    <input type="text" class="form-control" name="url-webhook[${formGroupCount}][]" value="" placeholder="Digite a URL que será notificada">
                </div>
                <div class="col-md-12 form-group">
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
            var $row = $(this).closest('.row');

            var urlValue = $(this).closest('.form-group').prev('.form-group').find('input[name^="url-webhook"]').val();

            let url1 = "<?= base_url('integrations/deleteGroupFormDataWebhookProvider') ?>";

            var storeIds = $('#stores').val();
            var idProvider = <?= json_encode($fields['idProvider']) ?>;

   
            if (storeIds === null || storeIds.length === 0) {
                // Se nenhum valor foi selecionado, você pode adicionar seu tratamento aqui
                alert('Por favor selecione ao menos uma Loja na aba Fornecedores');
                return; // Por exemplo, você pode optar por não prosseguir
            }

            var formData = '';
             // Adiciona os IDs selecionados ao formData
            storeIds.forEach(function(storeId) {
                formData += '&storeId[]=' + storeId;
            });
                    
            formData += '&nameUrl=' + urlValue + '&id_supplier=' + idProvider + '&is_supplier=true';

                   
            $.ajax({
                url: url1,
                type: 'POST',
                data: formData, 
                dataType: 'json',
                success: function(response) {
                    if (response['success'] == 1) {
                        Swal.fire({
                            title: '<?=$this->lang->line("messages_successfully_removed")?>',
                            text: response['data'],
                            icon: 'success'
                        }).then(function() {
                            window.location.reload();
                        });
                    } 
                }, 
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });


            $row.find('.col-md-12.form-group').last().remove();
            $row.find('.col-md-10.form-group').last().remove();
            formGroupCount--;
            
            var webhooksContainer = $("#webhooks-container");
            if (webhooksContainer.children().length === 0) {
                $("#submitWebhook").hide();
            }
        });

        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if ($(e.target).attr('href') === '#webhook') {
                formGroupCount = 0;

                var idProvider = <?= json_encode($fields['idProvider']) ?>;
                let url = "<?= base_url('integrations/getModalDataSupplierWebhook') ?>";

                if(idProvider == null || idProvider === ""){
                    var webhooksContainer = $("#webhooks-container");
                    webhooksContainer.empty();
                return;
                }
                $.ajax({
                    url: url,
                    type: 'GET',
                    data: { providerId: idProvider },
                    dataType: 'json',
                    success: function(response) {
                        if (response['success'] == 1) {
                            Swal.fire(
                                '<?=$this->lang->line("no_find_type_webhook")?>',
                                response['data'],
                                'error'
                            );
                        } else {
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
            }
        });

        function preencherModal(data) {
            var webhooksContainer = $("#webhooks-container");
                webhooksContainer.empty();
            
            for (let i = 0; i < data.length; i++) {
                const item = data[i];
                
                // Cria um novo formulário de grupo
                let newFormGroup = `
                    <div class="col-md-10 form-group">
                        <label for="url-webhook">URL</label>
                        <input type="text" class="form-control" name="url-webhook[${i}][]" value="${item.url}" placeholder="Digite a URL que será notificada">
                    </div>
                    <div class="col-md-12 form-group">
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
                `;
                
                webhooksContainer.append(newFormGroup);

                formGroupCount++;
            }
        }

        $('#submitWebhook').click(function(e) {
            e.preventDefault();            
            const baseUrl = "<?=base_url('integrations/saveUrlCallbackintegrationSupplier')?>";
            
           
            var storeIds = $('#stores').val();
            var idProvider = <?= json_encode($fields['idProvider']) ?>;
   
            if (storeIds === null || storeIds.length === 0) {
                // Se nenhum valor foi selecionado, você pode adicionar seu tratamento aqui
                alert('Por favor selecione ao menos uma Loja na aba Fornecedores');
                return; // Por exemplo, você pode optar por não prosseguir
            }

             // Adiciona os IDs selecionados ao formData
            storeIds.forEach(function(storeId) {
                formData += '&storeId[]=' + storeId;
            });
                    
            var formData = $('#formWebhook').serialize();
            formData += '&storeId=' + storeIds + '&id_supplier=' + idProvider;

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

$("#slc_val_credito").change(function(){
    if ( $("#slc_val_credito").val() == "Sim") {
        $("#div_txt_val_credito").show();
    }else{
        $("#div_txt_val_credito").hide();
    }
});

$("#slc_val_ship_min").change(function(){
    if ( $("#slc_val_ship_min").val() == "Sim") {
        $("#div_txt_val_ship_min").show();
    }else{
        $("#div_txt_val_ship_min").hide();
    }
});

$("#slc_qtd_min").change(function(){
    if ( $("#slc_qtd_min").val() == "Sim") {
        $("#div_txt_qtd_min").show();
    }else{
        $("#div_txt_qtd_min").hide();
    }
});

$("#slc_tipo_pagamento").change(function(){
    if ( $("#slc_tipo_pagamento").val() == "Transferencia") {
        $("#divTransferencia").show();
    }else{
        $("#divTransferencia").hide();
    }
});

$('.copy-input').click(function() {
    // Seleciona o conteúdo do input
    $(this).closest('.input-group').find('input').select();
    // Copia o conteudo selecionado
    const copy = document.execCommand('copy');
    if(copy){
        Toast.fire({
            icon: 'success',
            title: "Conteúdo copiado com sucesso!"
        })
    } else {
        Toast.fire({
            icon: 'success',
            title: "Não foi possível copiar o conteúdo!"
        })
    }
});

$("#slc_tipo_cubage").change(function(){
    if ($("#slc_tipo_cubage").val() == "FreteCubadoSim") {

        $("#divFretecubado").show();
        $("#cubage_factor").attr("required", "req");

    } else {
        $("#divFretecubado").hide();
        $("#cubage_factor").removeAttr("required", "req");
        $("#cubage_factor").val("");
    }
});

$("#ad_valorem").on("change",function(){
    if ($(this).val() !== '') {
        $(this).val(parseFloat($(this).val()).toFixed(2));
    }
});

$("#gris").on("change",function(){
    if ($(this).val() !== '') {
        $(this).val(parseFloat($(this).val()).toFixed(2));
    }
});

$("#toll").on("change",function(){
    if ($(this).val() !== '') {
        $(this).val(parseFloat($(this).val()).toFixed(2));
    }
});

$("#ad_valorem, #gris, #toll, #shipping_revenue").on('input', function() {
    var c = this.selectionStart,
        r = /[^0-9.]/gi,
        v = $(this).val();
    if(r.test(v)) {
        $(this).val(v.replace(r, ''));
        c--;
    }
    this.setSelectionRange(c, c);
});

$("#ad_valorem").blur(function(){
    var value = $(this).val();
    if(value == "NaN" || value == "0.00" ){
        $('#ad_valorem').val("");
    }
});

$("#ad_valorem").blur(function(){
    var value = $(this).val();
    if(value == "NaN" || value == "0.00" ){
        $('#ad_valorem').val("");
    }
});

$("#gris").blur(function(){
    var value = $(this).val();
    if(value == "NaN" || value == "0.00"){
        $('#gris').val("");
    }
});

$("#toll").blur(function(){
    var value = $(this).val();
    if(value == "NaN" || value == "0.00"){
        $('#toll').val("");
    }
});

$("#shipping_revenue").blur(function(){
    var value = $(this).val();
    if(value == "NaN" || value == "0.00"){
        $('#shipping_revenue').val("");
    }
});

function exemptIE() {
  const ie = $('#txt_insc_estadual')[0].hasAttribute('disabled')
  if (!ie) {
    $('#txt_insc_estadual').attr('disabled', 'disabled')
  } else {
    $('#txt_insc_estadual').removeAttr('disabled')
  }
}

$('#active_token_api').on('change', function(){
    if ($(this).is(':checked')) {
        $('#parentDataApi').show();
        $('#tabWebhook').show();
    } else {
        $('#parentDataApi').hide();
        $('#tabWebhook').hide();
    }
});


</script>

