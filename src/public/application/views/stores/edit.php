<?php use App\Libraries\Enum\AntecipationTypeEnum;
use App\Libraries\FeatureFlag\FeatureManager;

include_once(APPPATH . '/third_party/zipcode.php'); ?>
<!--
SW Serviços de Informática 2019

Editar Lojas

-->

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <ol id="barra-simples-2" class="mid step-progress-bar">
                    <li class="<?= $dataProgressBar['date_store'] ? 'step-past' : 'step-future' ?>" style="width: 33.3333%; z-index: 3;">
                        <span class="content-wpp"><?=$this->lang->line('application_store_registration');?></span>
                        <span class="content-bullet">1</span>
                        <span class="content-wpp"><?= $dataProgressBar['date_store'] ? date('d/m/Y', strtotime($dataProgressBar['date_store'])) : '' ?></span>
                    </li>
                    <li class="<?= $dataProgressBar['date_product'] ? 'step-past' : 'step-future' ?>" style="width: 33.3333%; z-index: 2;">
                        <span class="content-wpp"><?=$this->lang->line('application_first_product');?></span>
                        <span class="content-bullet">2</span>
                        <span class="content-wpp"><?= $dataProgressBar['date_product'] ? date('d/m/Y', strtotime($dataProgressBar['date_product'])) : '' ?></span>
                        <span class="content-stick <?= $dataProgressBar['date_product'] ? 'step-past' : 'step-future' ?>"></span>
                    </li>
                    <li class="<?= $dataProgressBar['date_order'] ? 'step-past' : 'step-future' ?>" style="width: 33.3333%; z-index: 1;">
                        <span class="content-wpp"><?=$this->lang->line('application_first_sale');?></span>
                        <span class="content-bullet">3</span>
                        <span class="content-wpp"><?= $dataProgressBar['date_order'] ? date('d/m/Y', strtotime($dataProgressBar['date_order'])) : '' ?></span>
                        <span class="content-stick <?= $dataProgressBar['date_order'] ? 'step-past' : 'step-future' ?>"></span>
                    </li>
                </ol>

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
                    <?php endif; 
                    ?>

                <?php if ($store_data['is_vacation'] == 1): ?>  
                    <div class="alert alert-danger" role="alert">
                        <?php echo $this->lang->line('application_atention_vacation_on') . $store_data['name'] . $this->lang->line('application_atention_vacation_on_continue'); ?>
                    </div>
                <?php endif; 
                
                ?>
                <!--- retirada do frete rápido
          <?php if ($tipos_volumes_novos >0): ?>
          	<div class="alert alert-error alert-dismissible" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <?= $this->lang->line('application_msg_store_new_categories'); ?>
            </div>
          <?php endif; ?>
		---->
                <?php if(in_array('updateStore', $user_permission)): ?>
                    <a type="button" class="<?=$store_data['active'] != 2 ?'btn btn-danger':'btn btn-success'?>" href="<?=$store_data['active'] != 2 ?base_url('stores/inactive/' . $store_data['id']) : base_url('stores/active/' . $store_data['id'])?>">
                        <?=$store_data['active'] != 2 ?$this->lang->line('application_inactive_store'):$this->lang->line('application_active_store');?>
                    </a>
                <?php endif; ?>
                <?php if($external_marketplace_integration): ?>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalIssueInvoice">
                        <i class="fa fa-file-text-o"></i> <?=$this->lang->line('application_issue_invoice')?></i>
                    </button>
                <?php endif; ?>

                <?php if(in_array('enableVacation', $user_permission)): ?>
                    <?php if ($store_data['is_vacation'] == 0): ?> 
                        <a type="button" 
                        class="btn btn-warning" 
                        href="<?= base_url('stores/vacationOn/' . $store_data['id']) ?>">
                        <i class="bi bi-pause-fill"></i> <?= $this->lang->line('application_store_on_vacation') ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>     

                <?php if(in_array('enableVacation', $user_permission)): ?>
                    <?php if ($store_data['is_vacation'] == 1): ?>  
                        <a type="button" 
                        class="btn btn-success" 
                        href="<?= base_url('stores/vacationOff/' . $store_data['id']) ?>">
                        <i class="bi bi-play-fill"></i> <?= $this->lang->line('application_store_off_vacation') ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                

                <form role="form" action="<?php base_url('stores/update') ?>" method="post"  enctype="multipart/form-data" id="editForm">
                    <div class="box">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_update_information');?></h3>
                             <div class="float-right">
                                <?php if(in_array('initStoreMigration', $user_permission) && $migration_store) : ?>
                                    <?php
                                    switch(intval($store_data['integrate_status'])){
                                        case 1:
                                            print('<span class="label label label-info">'.$this->lang->line('product_migration_status').'</span>');
                                            break;
                                        case 2:
                                            print('<span class="label label label-info">'.$this->lang->line('orders_migration_status').'</span>');
                                            break;
                                        case 3:
                                            print('<span class="label label-success">'.$this->lang->line('completed_migration_status').'</span>');
                                            break;
                                    }
                                    ?>
                                <?php endif; ?>
                             </div>
                        </div>
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
                                        <?php
                                    }
                                }
                            } ?>

                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label for="company"><?=$this->lang->line('application_company_name');?></label>
                                    <select class="form-control" id="company_id" name="company_id" required >
                                        <option value=""><?=$this->lang->line('application_select');?></option>
                                        <?php
                                        foreach ($empresas as $empresa): ?>
                                            <?php $disabled = $stores_multi_cd && in_array($store_data['type_store'], array('1', '2')) && $store_data['company_id'] != $empresa['id'] ? 'disabled' : '' ?>
                                            <option value="<?php echo $empresa['id']; ?>" <?=set_select('company_id', $empresa['id'], $store_data['company_id'] == $empresa['id'])?> <?=$disabled?>><?php echo $empresa['name'] ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-5">
                                    <label for="name"><?=$this->lang->line('application_name');?></label>
                                    <input type="text" class="form-control" id="name" name="name" placeholder="<?=$this->lang->line('application_enter_store_name');?>" value="<?php echo set_value('name',$store_data['name']) ?>" autocomplete="off">
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="edit_active"><?=$this->lang->line('application_status');?></label>
                                    <select class="form-control" id="edit_active" name="edit_active" required>
                                        <option value="1" <?=set_select('edit_active', 1, $store_data['active'] == 1)?> ><?=$this->lang->line('application_active');?></option>
                                        <option value="2" <?=set_select('edit_active', 2, $store_data['active'] == 2)?> ><?=$this->lang->line('application_inactive');?></option>
                                        <option value="3" <?=set_select('edit_active', 3, $store_data['active'] == 3)?> ><?=$this->lang->line('application_in_negociation');?></option>
                                        <option value="4" <?=set_select('edit_active', 4, $store_data['active'] == 4)?> ><?=$this->lang->line('application_billet');?></option>
                                        <option value="5" <?=set_select('edit_active', 5, $store_data['active'] == 5)?> ><?=$this->lang->line('application_churn');?></option>
                                        <option value="6" <?=set_select('edit_active', 6, $store_data['active'] == 6)?> >Incompleto</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="flag_store_migration"><?=$this->lang->line('application_store_migration');?></label>
                                    <select class="form-control" id="flag_store_migration" name="flag_store_migration" disabled>
                                        <option value="0" <?=set_select('flag_store_migration', 0, $store_data['flag_store_migration'] == 0)?> disabled="disabled" ><?=$this->lang->line('application_no');?></option>
                                        <option value="1" <?=set_select('flag_store_migration', 1, $store_data['flag_store_migration'] == 1)?> disabled="disabled" ><?=$this->lang->line('application_yes');?></option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="onboarding"><?=$this->lang->line('application_onboarding');?></label>
                                    <input type="date" class="form-control" id="onboarding" name="onboarding" value="<?php echo set_value('onboarding',$store_data['onboarding']) ?>">
                                </div>
                                <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
                                <div class="form-group col-md-4">
                                    <label for="website_url"><?=$this->lang->line('application_website');?></label>
                                    <input type="text" class="form-control" id="website_url" name="website_url" value="<?php echo set_value('website_url',$store_data['website_url']); ?>" placeholder="<?=$this->lang->line('application_entre_website_url');?>">
                                </div>
                                <?php endif; ?>
                                <?php if ($stores_multi_cd && in_array($store_data['type_store'], array('1', '2'))): ?>
                                    <div class="form-group col-md-2">
                                        <label for="raz_soc"><?=$this->lang->line('application_id_multi_cd');?></label>
                                        <input type="text" class="form-control" value="<?=$store_id_principal_multi_cd ? $this->lang->line('application_principal_store') : $this->lang->line('application_additional_cd') ?>" disabled>
                                    </div>
                                    <?php if ($store_id_principal_multi_cd): ?>
                                    <div class="form-group col-md-2">
                                        <label><?=$this->lang->line('application_maximum_time_to_invoice_order');?></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="max_time_to_invoice_order" value="<?php echo set_value('max_time_to_invoice_order',$store_data['max_time_to_invoice_order']);?>" name="max_time_to_invoice_order" autocomplete="off">
                                            <span class="input-group-addon text-uppercase"><strong><?=$this->lang->line('application_hours');?></strong></span>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="inventory_utilization"><?=$this->lang->line('application_inventory_utilization');?></label>
                                        <select class="form-control" id="inventory_utilization" name="inventory_utilization">
                                            <option value="all_stores" <?=set_select('inventory_utilization', 'all_stores', $store_data['inventory_utilization'] == 'all_stores')?> ><?=$this->lang->line('application_all_stores');?></option>
                                            <option value="main_store_only" <?=set_select('inventory_utilization', 'main_store_only', $store_data['inventory_utilization'] == 'main_store_only')?> ><?=$this->lang->line('application_main_store_only');?></option>
                                            <option value="cd_store_only" <?=set_select('inventory_utilization', 'cd_store_only', $store_data['inventory_utilization'] == 'cd_store_only')?> ><?=$this->lang->line('application_cd_store_only');?></option>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12" id="hideButton">
                                    <!-- label for="edit_same"><?=$this->lang->line('application_copycompany');?></label -->
                                    <span class="btn btn-success" onclick="copyFunc('<?=$store_data['company_id']; ?>')" ><i class="fa fa-copy"></i>   <?=$this->lang->line('application_copycompany');?></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6 show_pj <?php echo (form_error('raz_soc')) ? "has-error" : "";?>" >
                                    <label for="raz_soc"><?=$this->lang->line('application_raz_soc');?></label>
                                    <input type="text" class="form-control" required id="raz_soc" name="raz_soc" placeholder="<?=$this->lang->line('application_enter_razao_social');?>" value="<?php echo set_value('raz_soc',$store_data['raz_social']) ?>" autocomplete="off">
                                    <?php echo '<i style="color:red">'.form_error('raz_soc').'</i>';  ?>
                                </div>

                                <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
                                <div class="form-group col-md-3 show_pf <?php echo (form_error('CPF')) ? 'has-error' : '';  ?>">
                                    <label for="CPF"><?=$this->lang->line('application_cpf')?>(*)</label>
                                    <input type="text" class="form-control" readonly <?= $cnpj_disabled ?> maxlength="14" minlenght="14" id="CPF" name="CPF" placeholder="<?=$this->lang->line('application_enter_cpf') ?>" autocomplete="off" value="<?=set_value('CPF',$store_data['CNPJ']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);">
                                    <?php echo '<i style="color:red">'.form_error('CPF').'</i>'; ?>
                                </div>
                                <div class="form-group col-md-2 show_pf <?php echo (form_error('RG')) ? 'has-error' : '';  ?>">
                                    <label for="RG"><?=$this->lang->line('application_rg')?></label>
                                    <input type="text" class="form-control" id="RG" name="RG" placeholder="<?=$this->lang->line('application_enter_rg')?>" autocomplete="off" value = "<?=set_value('RG',$store_data['inscricao_estadual']) ?>">
                                    <?php echo '<i style="color:red">'.form_error('RG').'</i>'; ?>
                                </div>
                                <?php endif; ?>

                                <div class="form-group col-md-3 show_pj <?php echo (form_error('CNPJ')) ? "has-error" : "";?>" >
                                    <label for="CNPJ"><?=$this->lang->line('application_cnpj');?></label>
                                    <input type="text" class="form-control" readonly <?= $cnpj_disabled ?> id="CNPJ" name="CNPJ"  maxlength="18" minlenght="18" placeholder="<?=$this->lang->line('application_enter_CNPJ');?>" value="<?php echo set_value('CNPJ',$store_data['CNPJ']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CNPJ',this,event);">
                                    <?php echo '<i style="color:red">'.form_error('CNPJ').'</i>';  ?>
                                </div>

                                <div class="form-group col-md-2 show_pj <?php echo (form_error('inscricao_estadual')) ? "has-error" : "";?>" >
                                    <label for="inscricao_estadual"><?=$this->lang->line('application_iest');?></label>
                                    <input type="text" class="form-control" <?php echo set_value('exempted') == 1 ? 'disabled' : (set_value('CNPJ') ? '' : ($store_data['inscricao_estadual'] == "0" ? 'disabled' : '')) ?> id="inscricao_estadual" name="inscricao_estadual" placeholder="<?=$this->lang->line('application_enter_incricao_estadual');?>" value="<?php echo set_value('inscricao_estadual',$store_data['inscricao_estadual']) ?>" autocomplete="off" required>
                                    <div class="form-check">
                                        <input class="form-check-input" <?php echo set_checkbox('exempted', '1',$store_data['inscricao_estadual'] == "0"); ?> type="checkbox" value="1" onchange="exemptIE()" name="exempted" id="exempted">
                                        <label class="form-check-label" for="exempted">
                                            <?= $this->lang->line('application_exempted'); ?>
                                        </label>
                                    </div>
                                    <?php echo '<i style="color:red">'.form_error('inscricao_estadual').'</i>';  ?>
                                </div>
                            </div>
                            <?php if ($stores_multi_cd && $store_data['type_store'] == 2): ?>
                        </div>
                    </div>
                    <div class="box">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_cover_area_cd');?></h3>
                        </div>
                        <div class="box-body">
                            <div class="row" id="data-zipcode">
                                <div class="form-group col-md-3">
                                    <label><?=$this->lang->line('application_cep_inicial');?>(*)</label>
                                    <input type="text" class="form-control zipcode_start" onKeyDown="Mascara('CEP',this,event);" maxlength="9">
                                </div>
                                <div class="form-group col-md-3">
                                    <label><?=$this->lang->line('application_cep_final');?>(*)</label>
                                    <input type="text" class="form-control zipcode_end" onKeyDown="Mascara('CEP',this,event);" maxlength="9">
                                </div>
                                <div class="form-group col-md-3">
                                    <label>&nbsp;</label>
                                    <br>
                                    <button type="button" class="btn btn-primary" id="btn_add_cep_multi_channel_fulfillment"><i class="fa fa-plus"></i> <?=$this->lang->line('application_add');?></button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <h3 class="box-title"><?=$this->lang->line('application_zipcodes_created');?></h3>
                                </div>
                            </div>
                            <div class="zipcodes_multi_channel_fulfillment">
                                <?php foreach ($range_zipcode_multi_channel_fulfillment as $range): ?>
                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <input type="text" class="form-control" required name="zipcode_start[]" value="<?=$range['zipcode_start']?>" readonly>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <input type="text" class="form-control" required name="zipcode_end[]" value="<?=$range['zipcode_end']?>" readonly>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <button type="button" class="btn btn-danger btn_remove_range_cep"><i class="fa fa-trash"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="box">
                        <div class="box-body">
                            <?php endif; ?>
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label for="responsible_cs">CS Responsável</label>
                                    <select class="form-control" name="responsible_cs" id="responsible_cs" <?= $usercomp != 1 ? 'disabled' : '' ?> >
                                        <option value="">Selecione...</option>
                                        <?php foreach ($CSs as $CS) { ?>
                                            <option value="<?= $CS['id'] ?>" <?=set_select('responsible_cs', $CS['id'], $store_data['responsible_cs'] == $CS['id'])?>><?= $CS['firstname'].' '.$CS['lastname'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
                                <div class="form-group col-md-2 <?php echo (form_error('inscricao_municipal')) ? "has-error" : "";?>" >
                                    <label for="inscricao_municipal"><?=$this->lang->line('application_imun');?></label>
                                    <input type="text" class="form-control" <?php echo set_value('exempted_mun') == 1 ? 'disabled' : (set_value('CNPJ') ? '' : ($store_data['inscricao_municipal'] == "0" ? 'disabled' : '')) ?> id="inscricao_municipal" name="inscricao_municipal" placeholder="<?=$this->lang->line('application_enter_inscricao_municipal');?>" value="<?php echo set_value('inscricao_municipal',$store_data['inscricao_municipal']) ?>" autocomplete="off" required>
                                    <div class="form-check">
                                        <input class="form-check-input" <?php echo set_checkbox('exempted_mun', '1',$store_data['inscricao_municipal'] == "0"); ?> type="checkbox" value="1" onchange="exemptMun()" name="exempted_mun" id="exempted_mun">
                                        <label class="form-check-label" for="exempted_mun">
                                            <?= $this->lang->line('application_exempted'); ?>
                                        </label>
                                    </div>
                                    <?php echo '<i style="color:red">'.form_error('inscricao_estadual').'</i>';  ?>
                                </div>
                                <?php endif;?>
                                <div class="form-group col-md-2 <?php echo (form_error('phone_1')) ? "has-error" : "";?>">
                                    <label for="phone_1"><?=$this->lang->line('application_phone');?>1</label>
                                    <input type="tel" class="form-control" id="phone_1" required name="phone_1" placeholder="<?=$this->lang->line('application_enter_phone');?>" value="<?php echo set_value('phone_1',$store_data['phone_1']) ?>" autocomplete="off" maxlength="15" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);">
                                    <?php echo '<i style="color:red">'.form_error('phone_1').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-2 <?php echo (form_error('phone_2')) ? "has-error" : "";?>">
                                    <label for="phone_2"><?=$this->lang->line('application_phone');?>2</label>
                                    <input type="tel" class="form-control" id="phone_2" name="phone_2" placeholder="<?=$this->lang->line('application_enter_phone');?>" value="<?php echo set_value('phone_2',$store_data['phone_2']) ?>" autocomplete="off" maxlength="15" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);">
                                    <?php echo '<i style="color:red">'.form_error('phone_2').'</i>';  ?>
                                </div>

                                <div class="form-group col-md-2 mt-5">
                                    <div class="form-check">
                                        <input type="checkbox" name="invoice_cnpj" id="invoice_cnpj" value="1" <?php echo set_checkbox('invoice_cnpj', '1',$store_data['invoice_cnpj'] == "1"); ?>  />
                                        <label for="invoice_cnpj"><?=$this->lang->line('application_cnpj_fatured');?></label>
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <?php
                                $allcatalogs = array();
                                foreach($linkcatalogs as $lc) {
                                    $allcatalogs[] = $lc['catalog_id'];
                                }
                                ?>
                                <div class="form-group col-md-6 col-xs-12 <?php echo (form_error('catalogs[]')) ? "has-error" : ""; ?>">
                                    <label for="catalogs" class="normal"><?=$this->lang->line('application_catalogs');?>(*)</label>
                                    <select class="form-control selectpicker show-tick" id="catalogs" name ="catalogs[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 2" title="<?=$this->lang->line('application_select');?>">
                                        <?php foreach ($catalogs as $catalog) { ?>
                                            <option value="<?= $catalog['id'] ?>"  <?php echo set_select('catalogs', $catalog['id'], in_array($catalog['id'], $allcatalogs)); ?> ><?= $catalog['name'] ?></option>
                                        <?php } ?>
                                    </select>
                                    <?php echo '<i style="color:red">'.form_error('catalogs[]').'</i>'; ?>
                                </div>
                                <div class="form-group col-md-3 show_pj <?php echo (form_error('associate_type_pj')) ? 'has-error' : '';  ?>">
                                    <label for="associate_type_pj"><?=$this->lang->line('application_associate_type')?>(*)</label>
                                    <?php ?>
                                    <select class="form-control" id="associate_type_pj" name="associate_type_pj">
                                        <option value="0" <?= set_select('associate_type_pj', 0, ($store_data['associate_type'] == 0)) ?>><?=$this->lang->line('application_parent_company')?></option>
                                        <option value="1" <?= set_select('associate_type_pj', 1, ($store_data['associate_type'] == 1)) ?>><?=$this->lang->line('application_agency')?></option>
                                        <option value="2" <?= set_select('associate_type_pj', 2, ($store_data['associate_type'] == 2)) ?>><?=$this->lang->line('application_partner')?></option>
                                        <option value="3" <?= set_select('associate_type_pj', 3, ($store_data['associate_type'] == 3)) ?>><?=$this->lang->line('application_affiliate')?></option>
                                        <option value="4" <?= set_select('associate_type_pj', 4, ($store_data['associate_type'] == 4)) ?>><?=$this->lang->line('application_autonomous')?></option>
                                        <option value="5" <?= set_select('associate_type_pj', 5, ($store_data['associate_type'] == 5)) ?>><?=$this->lang->line('application_indicator')?></option>
                                        <?php if ($franchise_on_store == 1) { // utilizado pelo gruposoma ?>
                                        <option value="6" <?= set_select('associate_type_pj', 6, ($store_data['associate_type'] == 6)) ?>><?=$this->lang->line('application_franchise')?></option>
 										<?php }?>
 										<?php if ($big_sellers_on_store == 1) { // utilizado pelo Vertem ?>
                                        <option value="7" <?= set_select('associate_type_pj', 7, ($store_data['associate_type'] == 7)) ?>><?=$this->lang->line('application_big_store')?></option>
 										<?php }?>
                                    </select>
                                    <?php echo '<i style="color:red">'.form_error('associate_type_pj').'</i>'; ?>
                                </div>
                                <div class="form-group col-md-3 <?php echo (form_error('erp_customer_supplier_code')) ? "has-error" : "";?>">
                                    <label for="erp_customer_supplier_code"><?=$this->lang->line('application_store_erp_customer_supplier_code');?> <?= $required_clifor == "1" ? "(*)" : "" ?></label>
                                    <input type="tel" class="form-control" id="erp_customer_supplier_code" name="erp_customer_supplier_code" placeholder="<?=$this->lang->line('application_store_erp_customer_supplier_code');?>" value="<?php echo set_value('erp_customer_supplier_code',$store_data['erp_customer_supplier_code']) ?>" autocomplete="off" maxlength="255" <?= $required_clifor == "1" ? "required=\"true\"" : "" ?>>
                                    <?php echo '<i style="color:red">'.form_error('erp_customer_supplier_code').'</i>';  ?>
                                </div>
                            </div>
                            <div class="row">
                                <?php if (is_null($store_data['seller'])) { ?>
                                    <div class="form-group col-md-4" >
                                        <label for="seller"><?=$this->lang->line('application_seller');?></label>
                                        <select class="form-control" name="seller" id="seller" <?= $usercomp != 1 ? 'disabled' : '' ?> >
                                            <option value=""><?=$this->lang->line('application_select');?></option>
                                            <?php foreach ($CSs as $CS) { ?>
                                                <option value="<?= $CS['id'] ?>" <?=set_select('seller', $CS['id'], $store_data['seller'] == $CS['id'])?>><?= $CS['firstname'].' '.$CS['lastname'].' ('.$CS['email'].')'?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                <?php } else {
                                    $seller ='';
                                    foreach ($CSs as $CS) {
                                        if ($store_data['seller'] == $CS['id']) {
                                            $seller = $CS['firstname'].' '.$CS['lastname'].' ('.$CS['email'].')';

                                        }
                                    }
                                    ?>
                                    <div class="form-group col-md-4" >
                                        <label for="seller"><?=$this->lang->line('application_seller');?></label>
                                        <span class="form-control" ><?php echo $seller; ?></span>
                                        <input type="hidden" name="seller" readonly value="<?php echo set_value('seller',$store_data['seller']) ?>" >
                                    </div>

                                <?php }  ?>
                                <div class="form-group col-md-3 <?=form_error('additional_operational_deadline') ? "has-error" : ""?>">
                                    <label for="additional_operational_deadline"><?=$this->lang->line('application_store_additional_operational_deadline');?></label>
                                    <input type="number" class="form-control" id="additional_operational_deadline" name="additional_operational_deadline" placeholder="<?=$this->lang->line('application_store_additional_operational_deadline')?>" value="<?=set_value('additional_operational_deadline',$store_data['additional_operational_deadline'])?>">
                                    <?='<i style="color:red">'.form_error('additional_operational_deadline').'</i>'?>
                                </div>

                                <?php if ($use_buybox){ ?>
                                <div class="form-group col-md-2 mt-5">
                                    <div class="form-check">
                                        <input type="checkbox" name="buybox" id="buybox" value="1" <?php echo set_checkbox('buybox', '1',$store_data['buybox'] == "1"); ?>  />
                                        <label for="buybox"><?=$this->lang->line('application_buy_box');?></label>
                                    </div>
                                </div>
                                <?php } ?>

                                <?php if ($use_ativacaoAutomaticaProdutos){ ?>
                                <div class="form-group col-md-3 mt-5">
                                    <div class="form-check">
                                        <input type="checkbox" name="ativacaoAutomaticaProdutos" id="ativacaoAutomaticaProdutos" value="1" <?php echo set_checkbox('ativacaoAutomaticaProdutos', '1',$store_data['ativacaoAutomaticaProdutos'] == "1"); ?> />
                                        <label for="ativacaoAutomaticaProdutos"><?=$this->lang->line('application_automate_products');?></label>
                                    </div>
                                </div>
                                 <?php } ?>

                                <div class="form-group col-md-3 <?=form_error('seller_name_vtex') ? "has-error" : ""?>">
                                    <label for="seller_name_vtex"><?=$this->lang->line('application_seller_name_vtex');?></label>
                                    <input type="text" class="form-control" id="seller_name_vtex" name="seller_name_vtex"
                                           placeholder="<?=$this->lang->line('application_seller_name_vtex')?>"
                                           value="<?=$seller_id?>"
                                            disabled="disabled">
                                    <?='<i style="color:red">'.form_error('seller_name_vtex').'</i>'?>
                                </div>

                            </div>
                            <div class="row">
                                <div class="form-group col-md-12 mb-0">
                                    <legend><h4>Comissão do Seller</h4></legend>
                                </div>
                                <div class="form-group col-md-2 <?php echo (form_error('service_charge_value')) ? "has-error" : "";?>" >
                                    <label for="service_charge_value"><?=$this->lang->line('application_charge_amount');?></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control maskperc" required id="service_charge_value" value="<?php echo set_value('service_charge_value',$store_data['service_charge_value']);?>" name="service_charge_value" placeholder="<?=$this->lang->line('application_charge_amount');?>" autocomplete="off" maxlength="5" <?=$store_id_principal_multi_cd ? 'readonly' : ''?>>
                                        <span class="input-group-addon"><strong>%</strong></span>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="service_charge_freight_option" id="service_charge_freight_option" value="1" <?php echo set_checkbox('service_charge_freight_option', '1', $store_data['service_charge_value'] == $store_data['service_charge_freight_value']); ?>  onclick="comissaoFrete()" <?=$store_id_principal_multi_cd ? 'readonly' : ''?>/>
                                        <label for="service_charge_freight_option"><?=$this->lang->line('application_commission_freight_same_products');?></label>
                                    </div>
                                    <?php echo '<i style="color:red">'.form_error('service_charge_value').'</i>';  ?>
                                </div>

                                <div style="display:none" class="form-group col-md-2 comissaoFrete <?php echo (form_error('service_charge_freight_value')) ? "has-error" : "";?>">
                                    <label for="service_charge_freight_value"><?=$this->lang->line('application_charge_amount_freight');?></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control maskperc" required id="service_charge_freight_value" value="<?php echo set_value('service_charge_freight_value',$store_data['service_charge_freight_value']);?>" name="service_charge_freight_value" placeholder="<?=$this->lang->line('application_charge_amount_freight');?>" autocomplete="off" maxlength="5"  <?=$store_id_principal_multi_cd ? 'readonly' : ''?>>
                                        <span class="input-group-addon"><strong>%</strong></span>
                                    </div>
                                    <?php echo '<i style="color:red">'.form_error('service_charge_freight_value').'</i>';  ?>
                                </div>
                            </div>
                            <!-- endereço de coleta -->
                            <fieldset>
                                <legend><h4><?=$this->lang->line('application_collection_address');?></h4></legend>
                                <div class="row">
                                    <div class="form-group col-md-2 <?php echo (form_error('zipcode')) ? "has-error" : "";?>">
                                        <label for="zipcode"><?=$this->lang->line('application_zip_code');?></label>
                                        <input type="tel" class="form-control" required id="zipcode" name="zipcode" placeholder="<?=$this->lang->line('application_enter_zipcode');?>" value="<?php echo set_value('zipcode',$store_data['zipcode']) ?>" autocomplete="off" maxlength="9" onkeyup="this.value=this.value.replace(/[^\d]/,'')">
                                        <?php echo '<i style="color:red">'.form_error('zipcode').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-8 <?php echo (form_error('address')) ? "has-error" : "";?>">
                                        <label for="address"><?=$this->lang->line('application_address');?></label>
                                        <input type="text" class="form-control" required id="address" name="address" placeholder="<?=$this->lang->line('application_enter_address');?>" value="<?php echo set_value('address',$store_data['address']) ?>" autocomplete="off">
                                        <?php echo '<i style="color:red">'.form_error('address').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-2 <?php echo (form_error('addr_num')) ? "has-error" : "";?>">
                                        <label for="addr_num"><?=$this->lang->line('application_number');?></label>
                                        <input type="text" class="form-control" required id="addr_num" name="addr_num" placeholder="<?=$this->lang->line('application_enter_number');?>" value="<?php echo set_value('addr_num',$store_data['addr_num']) ?>" autocomplete="off">
                                        <?php echo '<i style="color:red">'.form_error('addr_num').'</i>';  ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-2 <?php echo (form_error('addr_compl')) ? "has-error" : "";?>">
                                        <label for="addr_compl"><?=$this->lang->line('application_complement');?></label>
                                        <input type="text" class="form-control" id="addr_compl" name="addr_compl" placeholder="<?=$this->lang->line('application_enter_complement');?>" value="<?php echo set_value('addr_compl',$store_data['addr_compl']) ?>" autocomplete="off">
                                        <?php echo '<i style="color:red">'.form_error('addr_compl').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-3 <?php echo (form_error('addr_neigh')) ? "has-error" : "";?>">
                                        <label for="addr_neigh"><?=$this->lang->line('application_neighb');?></label>
                                        <input type="text" class="form-control" id="addr_neigh" name="addr_neigh" placeholder="<?=$this->lang->line('application_enter_neighborhood');?>" value="<?php echo set_value('addr_neigh',$store_data['addr_neigh']) ?>" autocomplete="off">
                                        <?php echo '<i style="color:red">'.form_error('addr_neigh').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-3 <?php echo (form_error('addr_city')) ? "has-error" : "";?>">
                                        <label for="addr_city"><?=$this->lang->line('application_city');?></label>
                                        <input type="text" class="form-control" required id="addr_city" name="addr_city" placeholder="<?=$this->lang->line('application_enter_city');?>" value="<?php echo set_value('addr_city',$store_data['addr_city']) ?>" autocomplete="off">
                                        <?php echo '<i style="color:red">'.form_error('addr_city').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-2 <?php echo (form_error('addr_uf')) ? "has-error" : "";?>">
                                        <label for="addr_uf"><?=$this->lang->line('application_uf');?></label>
                                        <select class="form-control" id="addr_UF" required name="addr_uf">
                                            <option disabled value=""><?=$this->lang->line('application_select');?></option>
                                            <?php foreach ($ufs as $k => $v): ?>
                                                <option value="<?php echo trim($k) ?>" <?php echo set_select('addr_uf', trim($k),trim($k) == $store_data['addr_uf']) ?> ><?php echo $v ?></option>
                                            <?php endforeach ?>
                                        </select>
                                        <?php echo '<i style="color:red">'.form_error('addr_uf').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-2 <?php echo (form_error('country')) ? "has-error" : "";?>">
                                        <label for="country"><?=$this->lang->line('application_country');?></label>
                                        <select class="form-control" id="country" required name="country">
                                            <option disabled value=""><?=$this->lang->line('application_select');?></option>
                                            <?php foreach ($paises as $k => $v): ?>
                                                <option value="<?php echo trim($k); ?>" <?php echo set_select('country', trim($k),$k == $store_data['country'])?>><?php echo $v ?></option>
                                            <?php endforeach ?>
                                        </select>
                                        <?php echo '<i style="color:red">'.form_error('country').'</i>';  ?>
                                    </div>
                                </div>
                            </fieldset>
                            <!-- endereço comercial -->
                            <fieldset>
                                <legend><h4><?=$this->lang->line('application_business_address');?></h4></legend>
                                <div class="row">
                                    <div class="col-md-12">
                                        <input type="checkbox" name="same" <?php echo $store_data['business_code'] == $store_data['zipcode'] ? 'checked' : '' ?> id="same" value="1" onchange="sameAddress()">
                                        <label for="same"><?=$this->lang->line('application_identical_to_collection_address');?></label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-2 <?php echo (form_error('business_code')) ? "has-error" : "";?>">
                                        <label for="business_code"><?=$this->lang->line('application_zip_code');?></label>
                                        <input type="tel" class="form-control" required <?php echo $store_data['business_code'] == $store_data['zipcode'] ? 'disabled' : '' ?> id="business_code" name="business_code" placeholder="<?=$this->lang->line('application_enter_zipcode');?>" value="<?php echo set_value('business_code',$store_data['business_code']) ?>" autocomplete="off" maxlength="9" onkeyup="this.value=this.value.replace(/[^\d]/,'')" onblur="consultZip(this.value)">
                                        <?php echo '<i style="color:red">'.form_error('business_code').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-8 <?php echo (form_error('business_street')) ? "has-error" : "";?>">
                                        <label for="business_street"><?=$this->lang->line('application_address');?></label>
                                        <input type="text" class="form-control" required <?php echo $store_data['business_code'] == $store_data['zipcode'] ? 'disabled' : '' ?> id="business_street" name="business_street" placeholder="<?=$this->lang->line('application_enter_address');?>" value="<?php echo set_value('business_street',$store_data['business_street']) ?>" autocomplete="off">
                                        <?php echo '<i style="color:red">'.form_error('business_street').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-2 <?php echo (form_error('business_addr_num')) ? "has-error" : "";?>">
                                        <label for="business_addr_num"><?=$this->lang->line('application_number');?></label>
                                        <input type="text" class="form-control" required <?php echo $store_data['business_code'] == $store_data['zipcode'] ? 'disabled' : '' ?> id="business_addr_num" name="business_addr_num" placeholder="<?=$this->lang->line('application_enter_number');?>" value="<?php echo set_value('business_addr_num',$store_data['business_addr_num']) ?>" autocomplete="off">
                                        <?php echo '<i style="color:red">'.form_error('business_addr_num').'</i>';  ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-2 <?php echo (form_error('business_addr_compl')) ? "has-error" : "";?>">
                                        <label for="business_addr_compl"><?=$this->lang->line('application_complement');?></label>
                                        <input type="text" class="form-control" <?php echo $store_data['business_code'] == $store_data['zipcode'] ? 'disabled' : '' ?> id="business_addr_compl" name="business_addr_compl" placeholder="<?=$this->lang->line('application_enter_complement');?>" value="<?php echo set_value('business_addr_compl',$store_data['business_addr_compl']) ?>" autocomplete="off">
                                        <?php echo '<i style="color:red">'.form_error('business_addr_compl').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-3 <?php echo (form_error('business_neighborhood')) ? "has-error" : "";?>">
                                        <label for="business_neighborhood"><?=$this->lang->line('application_neighb');?></label>
                                        <input type="text" class="form-control" <?php echo $store_data['business_code'] == $store_data['zipcode'] ? 'disabled' : '' ?> id="business_neighborhood" name="business_neighborhood" placeholder="<?=$this->lang->line('application_enter_neighborhood');?>" value="<?php echo set_value('business_neighborhood',$store_data['business_neighborhood']) ?>" autocomplete="off">
                                        <?php echo '<i style="color:red">'.form_error('business_neighborhood').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-3 <?php echo (form_error('business_town')) ? "has-error" : "";?>">
                                        <label for="business_town"><?=$this->lang->line('application_city');?></label>
                                        <input type="text" class="form-control" required <?php echo $store_data['business_code'] == $store_data['zipcode'] ? 'disabled' : '' ?> id="business_town" name="business_town" placeholder="<?=$this->lang->line('application_enter_city');?>" value="<?php echo set_value('business_town',$store_data['business_town']) ?>" autocomplete="off">
                                        <?php echo '<i style="color:red">'.form_error('business_town').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-2 <?php echo (form_error('business_uf')) ? "has-error" : "";?>">
                                        <label for="business_uf"><?=$this->lang->line('application_uf');?></label>
                                        <select class="form-control" <?php echo $store_data['business_code'] == $store_data['zipcode'] ? 'disabled' : '' ?> id="business_uf" required name="business_uf">
                                            <option disabled value=""><?=$this->lang->line('application_select');?></option>
                                            <?php foreach ($ufs as $k => $v): ?>
                                                <option value="<?php echo trim($k) ?>" <?php echo set_select('business_uf', trim($k),trim($k) == $store_data['business_uf']) ?> ><?php echo $v ?></option>
                                            <?php endforeach ?>
                                        </select>
                                        <?php echo '<i style="color:red">'.form_error('business_uf').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-2 <?php echo (form_error('business_nation')) ? "has-error" : "";?>">
                                        <label for="business_nation"><?=$this->lang->line('application_country');?></label>
                                        <select class="form-control" <?php echo $store_data['business_code'] == $store_data['zipcode'] ? 'disabled' : '' ?> id="business_nation" required name="business_nation">
                                            <option disabled value=""><?=$this->lang->line('application_select');?></option>
                                            <?php foreach ($paises as $k => $v): ?>
                                                <option value="<?php echo trim($k); ?>" <?php echo set_select('business_nation', trim($k),$k == $store_data['business_nation'])?>><?php echo $v ?></option>
                                            <?php endforeach ?>
                                        </select>
                                        <?php echo '<i style="color:red">'.form_error('business_nation').'</i>';  ?>
                                    </div>
                                </div>
                            </fieldset>
                            <!-- até aqui -->
                            <div class="row">
                                <hr>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_name')) ? "has-error" : "";?>">
                                    <label for="responsible_name"><?=$this->lang->line('application_responsible_name');?></label>
                                    <input type="text" class="form-control" required id="responsible_name" name="responsible_name" placeholder="<?=$this->lang->line('application_enter_responsible_name');?>" value="<?php echo set_value('responsible_name',$store_data['responsible_name']) ?>" autocomplete="off">
                                    <?php echo '<i style="color:red">'.form_error('responsible_name').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_email')) ? "has-error" : "";?>">
                                    <label for="responsible_email"><?=$this->lang->line('application_responsible_email');?></label>
                                    <input type="text" class="form-control" required id="responsible_email" name="responsible_email" placeholder="<?=$this->lang->line('application_enter_responsible_email');?>" value="<?php echo set_value('responsible_email',$store_data['responsible_email']) ?>" autocomplete="off">
                                    <?php echo '<i style="color:red">'.form_error('responsible_email').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_cpf')) ? "has-error" : "";?>">
                                    <label for="responsible_cpf"><?=$this->lang->line('application_responsible_cpf');?><?php echo ($store_cpf_optional==1) ? '' : '*';?></label>
                                    <input type="text" class="form-control" <?php echo ($store_cpf_optional==1) ? '' : 'required';?> id="responsible_cpf" name="responsible_cpf" placeholder="<?=$this->lang->line('application_enter_responsible_cpf');?>" value="<?php echo set_value('responsible_cpf',$store_data['responsible_cpf']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" maxlength="14">
                                    <?php echo '<i style="color:red">'.form_error('responsible_cpf').'</i>';  ?>
                                </div>
                                
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_mother_name')) ? "has-error" : "";?>">
                                    <label for="responsible_mother_name"><?=$this->lang->line('application_responsible_mother_name');?></label>
                                    <input type="text" class="form-control" required id="responsible_mother_name" name="responsible_mother_name" placeholder="<?=$this->lang->line('application_responsible_mother_name');?>" value="<?php echo set_value('responsible_mother_name',$store_data['responsible_mother_name']) ?>" autocomplete="off">
                                    <?php echo '<i style="color:red">'.form_error('responsible_mother_name').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_position')) ? "has-error" : "";?>">
                                    <label for="responsible_position"><?=$this->lang->line('application_responsible_position');?></label>
                                    <input type="text" class="form-control" required id="responsible_position" name="responsible_position" placeholder="<?=$this->lang->line('application_responsible_position');?>" value="<?php echo set_value('responsible_position',$store_data['responsible_position']) ?>" autocomplete="off">
                                    <?php echo '<i style="color:red">'.form_error('responsible_position').'</i>';  ?>
                                </div>


                                <div class="form-group col-md-4 <?php echo (form_error('responsible_monthly_income')) ? "has-error" : "";?>">
                                    <label for="responsible_monthly_income">Renda Mensal Aproximada</label>
                                    <input type="number" class="form-control" required id="responsible_monthly_income" name="responsible_monthly_income" placeholder="Renda Mensal Aproximada" value="<?php echo set_value('company_annual_revenue',$store_data['responsible_monthly_income']) ?>" autocomplete="off">
                                    <?php echo '<i style="color:red">'.form_error('responsible_monthly_income').'</i>';  ?>
                                </div>

                                <div class="form-group col-md-4 <?php echo (form_error('company_annual_revenue')) ? "has-error" : "";?>">
                                    <label for="company_annual_revenue">Faturalmente Anual da empresa</label>
                                    <input type="number" class="form-control" required id="company_annual_revenue" name="company_annual_revenue" placeholder="Faturalmente Anual da empresa" value="<?php echo set_value('company_annual_revenue',$store_data['company_annual_revenue']) ?>" autocomplete="off">
                                    <?php echo '<i style="color:red">'.form_error('company_annual_revenue').'</i>';  ?>
                                </div>
                                
                            </div>
                            <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
                            <div class="row">
                                <hr>
                            </div>
                            <h4><?=$this->lang->line('application_contacts');?></h4>
                           <div class="row">
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_sac_name')) ? 'has-error' : '';  ?>">
                                    <label for="responsible_sac_name"><?=$this->lang->line('application_responsible_sac_name');?>(*)</label>
                                    <input type="text" class="form-control" id="responsible_sac_name" name="responsible_sac_name" autocomplete="off" value="<?=set_value('responsible_sac_name',$store_data['responsible_sac_name'])?>">
                                    <?php echo '<i style="color:red">'.form_error('responsible_sac_name').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_sac_email')) ? 'has-error' : '';  ?>">
                                    <label for="responsible_sac_email"><?=$this->lang->line('application_responsible_sac_email');?>(*)</label>
                                    <input type="email" class="form-control" id="responsible_sac_email" name="responsible_sac_email" autocomplete="off" value="<?=set_value('responsible_sac_email',$store_data['responsible_sac_email'])?>">
                                    <?php echo '<i style="color:red">'.form_error('responsible_sac_email').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_sac_tell')) ? 'has-error' : '';  ?>">
                                    <label for="responsible_sac_tell"><?=$this->lang->line('application_responsible_sac_tell');?>(*)</label>
                                    <input type="text" class="form-control" id="responsible_sac_tell" name="responsible_sac_tell" autocomplete="off" value="<?=set_value('responsible_sac_tell',$store_data['responsible_sac_tell'])?>" onkeypress="return digitos(event, this);" onkeydown="Mascara('TEL',this,event);" maxlength="15" placeholder="Digite o telefone">
                                    <?php echo '<i style="color:red">'.form_error('responsible_sac_tell').'</i>';  ?>
                                </div>
                            </div> 
                            <div class="row">
                                <hr>
                            </div>
                            <?php endif;?>
                            <div class="row">
                                <div class="form-group col-md-3 <?php echo (form_error('bank')) ? "has-error" : "";?>">
                                    <label for="bank"><?=$this->lang->line('application_bank');?></label>
                                    <select class="form-control" id="bank" name="bank" <?php echo ($bank_is_optional==1) ? '' : 'required' ?>>
                                        <option value=""><?=$this->lang->line('application_select');?></option>
                                        <?php foreach ($banks as $k => $v): ?>
                                            <option value="<?=trim($v['name'])?>" <?=set_select('bank', $v['name'], $store_data['bank'] == trim($v['name'])) ?>><?=$v['name'] ?></option>
                                        <?php endforeach ?>
                                    </select>
                                    <?php echo '<i style="color:red">'.form_error('bank').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-3 <?php echo (form_error('agency')) ? "has-error" : "";?>">
                                    <label for="agency"><?=$this->lang->line('application_agency');?></label>
                                    <input type="text" class="form-control" id="agency" name="agency" <?php echo ($bank_is_optional==1) ? '' : 'required' ?> placeholder="<?=$this->lang->line('application_enter_agency')?>" autocomplete="off" value="<?=set_value('agency', $store_data['agency'])?>">
                                    <?php echo '<i style="color:red">'.form_error('agency').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-3 <?php echo (form_error('account_type')) ? "has-error" : "";?>">
                                    <label for="currency"><?=$this->lang->line('application_type_account');?></label>
                                    <select class="form-control" id="account_type" name="account_type" <?php echo ($bank_is_optional==1) ? '' : 'required' ?>>
                                        <option value=""><?=$this->lang->line('application_select');?></option>
                                        <?php foreach ($type_accounts as $k => $v): ?>
                                            <option value="<?=trim($v)?>" <?=set_select('account_type', trim($v), $store_data['account_type'] == trim($v))?>><?=$v ?></option>
                                        <?php endforeach ?>
                                    </select>
                                    <?php echo '<i style="color:red">'.form_error('account_type').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-3 <?php echo (form_error('account')) ? "has-error" : "";?>">
                                    <label for="account"><?=$this->lang->line('application_account');?></label>
                                    <input type="text" class="form-control" id="account" name="account" <?php echo ($bank_is_optional==1) ? '' : 'required' ?> placeholder="<?=$this->lang->line('application_enter_bank_account')?>" autocomplete="off" value="<?=set_value('account', $store_data['account'])?>">
                                    <?php echo '<i style="color:red">'.form_error('account').'</i>';  ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-3 <?php echo (form_error('addr_neigh')) ? "has-error" : ""; ?>">
                                    <label for="responsable_birth_date"><?=$this->lang->line('application_birth_date');?><?=$birth_date_requered===true?'*':''?></label>
                                    <input type="date" class="form-control" id="responsable_birth_date" name="responsable_birth_date" value="<?=set_value('responsable_birth_date', $store_data['responsable_birth_date'])?>" placeholder="<?=$this->lang->line('application_enter_birth_date');?>" <?=$birth_date_requered===true?'required':''?>>
                                    <?php echo '<i style="color:red">' . form_error('birth_date') . '</i>'; ?>
                                </div>

                                <div class="form-group col-md-3">
                                    <label for="flag_bloqueio_repasse"><?=$this->lang->line('flag_bloqueio_repasse');?></label>
                                    <select class="form-control" id="flag_bloqueio_repasse" name="flag_bloqueio_repasse" >
                                            <option value="N">Não</option>
                                            <option value="S">Sim</option>
                                    </select>
                                    <?php echo '<i style="color:red">' . form_error('flag_bloqueio_repasse') . '</i>'; ?>
                                </div>
                                   
                                <?php if(in_array('transferAnticipationRelease', $user_permission)){ ?>
                                    <div class="form-group col-md-3">
                                        <label for="flag_antecipacao_repasse"><?=$this->lang->line('flag_antecipacao_repasse'); ?></label>
                                        <select <?= $store_data['flag_antecipacao_repasse'] == "N" ? "disabled" : "" ?> class="form-control" id="flag_antecipacao_repasse" name="flag_antecipacao_repasse" >
                                            <option value="N" <?= $store_data['flag_antecipacao_repasse'] == "N" ? "selected" : "" ?>>Não</option>
                                            <option value="S" <?= $store_data['flag_antecipacao_repasse'] == "S" ? "selected" : "" ?>>Sim</option>
                                        </select>
                                        <?php echo '<i style="color:red">' . form_error('flag_antecipacao_repasse') . '</i>'; ?>
                                    </div>
                                <?php } ?>

                                <?php
                                if ($allow_payment_reconciliation_installments){
                                    ?>
                                    <div class="form-group col-md-3">
                                        <label for="allow_payment_reconciliation_installments"><?=$this->lang->line('conciliation_payment_installment');?></label>
                                        <select class="form-control" id="allow_payment_reconciliation_installments" name="allow_payment_reconciliation_installments">
                                            <option value="0" <?=set_select('allow_payment_reconciliation_installments', $store_data['allow_payment_reconciliation_installments'], $store_data['allow_payment_reconciliation_installments'] == 0)?>>Não</option>
                                            <option value="1" <?=set_select('allow_payment_reconciliation_installments', $store_data['allow_payment_reconciliation_installments'], $store_data['allow_payment_reconciliation_installments'] == 1)?>>Sim</option>
                                        </select>
                                        <?php echo '<i style="color:red">' . form_error('allow_payment_reconciliation_installments') . '</i>'; ?>
                                    </div>
                                    <?php
                                }
                                ?>
                               
                            </div>


                            <?php if (in_array('doIntegration', $user_permission)) : ?>
                                <div class="row">
                                    <hr>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <h4>Indicação</h4>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4 col-xs-12">
                                        <label>Indicador</label>
                                        <select class="form-control select2" name="id_indicator" <?=$store_data['id_indicator'] != null ? 'disabled' : ''?>>
                                            <?php if($store_data['id_indicator'] == null): ?><option value="0"><?=$this->lang->line('application_select')?></option><?php endif ?>
                                            <?php if (count($users_indicator)): ?>
                                                <optgroup label="Usuário">
                                                    <?php foreach ($users_indicator as $user_indicator): ?>
                                                        <option value="u-<?=$user_indicator['id']?>" <?=set_select('id_indicator', trim($user_indicator['id']), $store_data['id_indicator'] == trim($user_indicator['id']))?>><?=$user_indicator['email']?></option>
                                                    <?php endforeach ?>
                                                </optgroup>
                                            <?php endif ?>
                                            <?php if (count($stores_indicator)): ?>
                                                <optgroup label="Loja">
                                                    <?php foreach ($stores_indicator as $store_indicator): ?>
                                                        <option value="s-<?=$store_indicator['id']?>" <?=set_select('id_indicator', trim($store_indicator['id']), $store_data['id_indicator'] == trim($store_indicator['id']))?>><?=$store_indicator['name']?></option>
                                                    <?php endforeach ?>
                                                </optgroup>
                                            <?php endif ?>
                                            <?php if (count($companies_indicator)): ?>
                                            <optgroup label="Empresa">
                                                <?php foreach ($companies_indicator as $company_indicator): ?>
                                                    <option value="c-<?=$company_indicator['id']?>" <?=set_select('id_indicator', trim($company_indicator['id']), $store_data['id_indicator'] == trim($company_indicator['id']))?>><?=$company_indicator['name']?></option>
                                                <?php endforeach ?>
                                                <?php endif ?>
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2 col-xs-12">
                                        <label>Percentual de Comissão</label>
                                        <input type="number" class="form-control" name="percentage_indication" placeholder="% Comissão" value="<?=set_value('percentage_indication', (int)$store_data['percentage_indication'])?>" <?=$store_data['id_indicator'] != null ? 'disabled' : ''?>>
                                    </div>
                                    <div class="form-group col-md-2 col-xs-12">
                                        <label>Origem do Seller</label>
                                        <select class="form-control select2" name="utm_source">
                                            <option value="0"><?=$this->lang->line('application_select')?></option>
                                            <?php foreach ($get_attribute_value_utm_param as $k => $v){ ?>
                                                <option value="<?=$v['value']?>" <?=set_select('utm_source', $v['value'], $store_data['utm_source'] == $v['value']) ?>><?=$v['value'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            <?php endif ?>

                            <div class="row">
                                <hr>
                            </div>

                            <div class="row">
                                <div class="form-group col-md-12" >
                                    <h4>
                                        <?php
                                        echo $this->lang->line('application_store_subaccount_status');
                                        echo " $gateway_name: $labelSubaccountStatus";
                                        ?> 
                                    </h4>
                                    <button type="button" class="btn btn-sm btn-<?php echo $alertColorlabelSubaccountStatus; ?>" onclick="listarMensagensSubconta('<?php echo $store_data['id']; ?>')" data-toggle="modal" data-target="#listaObs" title="Ver Mensagens">
                                        <i class="fa fa-eye"></i> Ver Logs
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <hr>
                            </div>
                            
                            <div class="row">
                                <div class="form-group col-md-12" >
                                    <h4>
                                        <?php
                                        echo $this->lang->line('application_vacation');
                                        ?> 
                                    </h4>
                                    <button type="button" class="btn btn-sm btn-<?php echo $alertColorlabelSubaccountStatus; ?>" onclick="listaVacation('<?php echo $store_data['id']; ?>')" data-toggle="modal" data-target="#logsVacation" title="Ver Mensagens">
                                        <i class="fa fa-eye"></i> Ver Logs
                                    </button>
                                </div>
                            </div>
                            <?php if ($this->data['mostrar_campos_envio_erp'] == 1): ?>
                                <div class="row">
                                    <hr>
                                </div>
                                
                                <?php
                                $camposAdicionais = isset($camposAdicionais) ? $camposAdicionais : [];
                                $camposObrigatorios = isset($camposObrigatorios) ? $camposObrigatorios : [];
                                ?>
                                <div class="row mt-4">
                                    <div class="form-group col-md-12">
                                        <h4 class="mb-1">Campos adicionais no envio de pedidos</h4>
                                        <p class="text-muted small">
                                            Selecione os campos adicionais que serão enviados na criação de pedidos para o ERP do seller.
                                            O envio depende se a integração do seller suporta receber esses dados.
                                        </p>
                                    </div>
                                </div>

                                <div class="row">
                                    <?php foreach ($camposAdicionais as $campo => $checked): ?>
                                        <div class="form-group col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    name="campos_adicionais[]"
                                                    id="campo_adicional_<?= md5($campo) ?>"
                                                    value="<?= htmlspecialchars($campo) ?>"
                                                    <?= $checked ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="campo_adicional_<?= md5($campo) ?>">
                                                    <?= htmlspecialchars($campo) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="row mt-4">
                                    <div class="form-group col-md-12">
                                        <h4 class="mb-1">Campos obrigatórios para criação de pedido</h4>
                                        <p class="text-muted small">
                                            Marque os campos que deverão ser obrigatórios para o envio do pedido ao ERP.
                                            O pedido só será enviado se todos os campos obrigatórios estiverem preenchidos.
                                        </p>
                                    </div>
                                </div>

                                <div class="row">
                                    <?php foreach ($camposObrigatorios as $campo => $checked): ?>
                                        <div class="form-group col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    name="campos_obrigatorios[]"
                                                    id="campo_obrigatorio_<?= md5($campo) ?>"
                                                    value="<?= htmlspecialchars($campo) ?>"
                                                    <?= $checked ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="campo_obrigatorio_<?= md5($campo) ?>">
                                                    <?= htmlspecialchars($campo) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            if ($is_using_financial_management_system){
                            ?>

                                <div class="row">
                                    <div class="form-group col-md-12" >
                                        <h4>
                                            <?php
                                            echo $this->lang->line('application_store_financial_management_system_status');
                                            echo " $financialManagementSystemName: $labelFinancialManagementSystemStatus";
                                            ?>
                                        </h4>
                                        <button type="button" class="btn btn-sm btn-<?php echo $alertColorlabelFinancialManagementSystemStatus; ?>" onclick="listLogsFinancialManagementSystem('<?php echo $store_data['id']; ?>')" data-toggle="modal" data-target="#listLogsFinancialManagementSystem" title="Ver Histórico">
                                            <i class="fa fa-eye"></i> Ver Logs
                                        </button>
                                    </div>
                                </div>

                            <?php
                            }
                            ?>

                            <div class="row">
                                <hr>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12" >
                                    <h4><?=$this->lang->line('application_logistics');?></h4>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 form-group">
                                    <input type="checkbox" name="freight_seller" id="freight_seller" value="1" <?php echo set_checkbox('freight_seller', '1',  $store_data['freight_seller'] == 1); ?>  onclick="ownLogistic()" />
                                    <label for="freight_seller"><?=$this->lang->line('application_own_logistic');?></label>
                                </div>
                            </div>
                            <div class="row">
                                <div style="display:none" class="form-group col-md-3 own_logistic <?php echo (form_error('freight_seller_type')) ? "has-error" : "";?>">
                                    <label for="freight_seller_type"><?=$this->lang->line('application_own_logistic_type');?></label>
                                    <select class="form-control" id="freight_seller_type" name="freight_seller_type">
                                        <option value="1" <?= set_select('freight_seller_type', 1, $store_data['freight_seller_type'] == 1 ) ?> ><?=$this->lang->line('application_precode')?></option>
                                        <option value="2" <?= set_select('freight_seller_type', 2, $store_data['freight_seller_type'] == 2 ) ?> ><?=sprintf($this->lang->line('application_tabela'),$sellercenter_name)?></option>
                                        <option value="3" <?= set_select('freight_seller_type', 3, $store_data['freight_seller_type'] == 3 ) ?> ><?=sprintf($this->lang->line('application_intelipost'), "Seller")?></option>
                                        <option value="4" <?= set_select('freight_seller_type', 4, $store_data['freight_seller_type'] == 4 ) ?> ><?=sprintf($this->lang->line('application_intelipost'), $sellercenter_name)?></option>
                                        <option value="5" <?= set_select('freight_seller_type', 5, $store_data['freight_seller_type'] == 5 ) ?> ><?=sprintf($this->lang->line('application_frete_rapido'), "Seller")?></option>
                                        <!--<option value="6" <?= set_select('freight_seller_type', 6, $store_data['freight_seller_type'] == 6 ) ?> >Frete Rápido (<?=$sellercenter_name?>)</option>-->
                                        <!--<option value="7" <?= set_select('freight_seller_type', 7, $store_data['freight_seller_type'] == 7 ) ?> >Sequoia (Seller)</option>-->
                                        <?php if ($data['settingSellerCenter'] == 'somaplace') {?>
                                        <option value="8" <?= set_select('freight_seller_type', 8, $store_data['freight_seller_type'] == 8 ) ?> ><?=sprintf($this->lang->line('application_sequoia'), $sellercenter_name)?></option>
                                        <option value="10" <?= set_select('freight_seller_type', 10, $store_data['freight_seller_type'] == 10) ?> ><?=$this->lang->line('application_dress_and_go')?></option>
                                        <?php } ?>
                                        <option value="9" <?= set_select('freight_seller_type', 9, $store_data['freight_seller_type'] == 9 ) ?> ><?=sprintf($this->lang->line('application_integration_named'), "ERP")?></option>
                                    </select>
                                    <?php echo '<i style="color:red">'.form_error('freight_seller_type').'</i>';  ?>
                                </div>
                                <div style="display:none" class="form-group col-md-3 own_logistic <?php echo (form_error('freight_seller_end_point')) ? "has-error" : "";?>">
                                    <label for="freight_seller_code">ID Intelipost</label>
                                    <input type="text" class="form-control" id="freight_seller_code" name="freight_seller_code" placeholder="<?=$this->lang->line('application_own_logistic_code')?>" autocomplete="off" value="<?=set_value('freight_seller_code', $store_data['freight_seller_code'])?>" />
                                    <?php echo '<i style="color:red">'.form_error('freight_seller_code').'</i>';  ?>
                                </div>
                                <div style="display:none" class="form-group col-md-9 own_logistic <?php echo (form_error('freight_seller_end_point')) ? "has-error" : "";?>">
                                    <label for="freight_seller_end_point"><?=$this->lang->line('application_own_logistic_endpoint');?></label>
                                    <input type="url" class="form-control" id="freight_seller_end_point" name="freight_seller_end_point" placeholder="<?=$this->lang->line('application_own_logistic_code')?>" autocomplete="off" value="<?=set_value('freight_seller_end_point', $store_data['freight_seller_end_point'])?>" />
                                    <?php echo '<i style="color:red">'.form_error('freight_seller_end_point').'</i>';  ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 form-group">
                                    <label for="type_view_tag">Tipo de Etiquetas</label>
                                    <select name="type_view_tag" id="type_view_tag" class="form-control">
                                        <option <?= set_select('type_view_tag', 'all', $store_data['type_view_tag'] == 'all' ) ?> value="all">Correios, Transportadora e/ou Gateway Logístico</option>
                                        <option <?= set_select('type_view_tag', 'correios', $store_data['type_view_tag'] == 'correios' ) ?> value="correios">Correios</option>
                                        <option <?= set_select('type_view_tag', 'shipping_company_gateway', $store_data['type_view_tag'] == 'shipping_company_gateway' ) ?> value="shipping_company_gateway">Transportadora e/ou Gateway Logístico</option>
                                    </select>
                                </div>
                            </div>
                            <?php if(in_array('baton_pass', $user_permission)): ?>
                                <h4><?=$this->lang->line('application_baton_pass');?></h4>
                                <div class="row">
                                    <div class="form-group col-md-4<?php echo (form_error('what_integration')) ? "has-error" : "";?>">
                                        <label for="what_integration"><?=$this->lang->line('application_what_integration');?></label>
                                        <input type="text" class="form-control" id="what_integration" name="what_integration" placeholder="<?=$this->lang->line('application_what_integration')?>" autocomplete="off" value="<?=set_value('what_integration', $store_data['what_integration'])?>"/>
                                        <?php echo '<i style="color:red">'.form_error('what_integration').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-4<?php echo (form_error('billing_expectation')) ? "has-error" : "";?>">
                                        <label for="billing_expectation"><?=$this->lang->line('application_billing_expectation');?></label>
                                        <input type="text" class="form-control" id="billing_expectation" name="billing_expectation" placeholder="<?=$this->lang->line('application_billing_expectation')?>" autocomplete="off" value="<?=set_value('billing_expectation', $store_data['billing_expectation'])?>"/>
                                        <?php echo '<i style="color:red">'.form_error('billing_expectation').'</i>';  ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4<?php echo (form_error('operation_store')) ? "has-error" : "";?>">
                                        <label for="operation_store" rowspan='2'><?=$this->lang->line('application_operation_store');?><br></label>
                                        <textarea rows="4" type="text" class="form-control" id="operation_store" name="operation_store" placeholder="<?=$this->lang->line('application_operation_store')?>" autocomplete="off"><?=set_value('operation_store', $store_data['operation_store'])?></textarea>
                                        <?php echo '<i style="color:red">'.form_error('operation_store').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-4<?php echo (form_error('mix_of_product')) ? "has-error" : "";?>">
                                        <label for="mix_of_product"><?=$this->lang->line('application_mix_of_product');?></label>
                                        <textarea rows="3" type="text" class="form-control" id="mix_of_product" name="mix_of_product" placeholder="<?=$this->lang->line('application_mix_of_product')?>" autocomplete="off"><?=set_value('mix_of_product', $store_data['mix_of_product'])?></textarea>
                                        <?php echo '<i style="color:red">'.form_error('mix_of_product').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-4<?php echo (form_error('how_up_and_fature')) ? "has-error" : "";?>" id='how_up_and_fature_div'>
                                        <label for="how_up_and_fature"><?=$this->lang->line('application_how_up_and_fature');?></label>
                                        <textarea rows="4" type="text" class="form-control align-bottom" id="how_up_and_fature" name="how_up_and_fature" placeholder="<?=$this->lang->line('application_how_up_and_fature')?>" autocomplete="off"><?=set_value('how_up_and_fature', $store_data['how_up_and_fature'])?></textarea>
                                        <?php echo '<i style="color:red">'.form_error('how_up_and_fature').'</i>';  ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php
                            if ($allow_automatic_antecipation){
                            ?>
                                <h4><?=$this->lang->line('rav_antecipation');?></h4>
                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label class="checkbox-inline">
                                            <input onchange="return changeAutomaticAntecipation(this);" id="use_automatic_antecipation" name="use_automatic_antecipation" type="checkbox" <?php echo set_checkbox('use_automatic_antecipation', '1', $store_data['use_automatic_antecipation'] == 1); ?> value="1">
                                            Permitir Utilizar Antecipação Automática
                                        </label>
                                    </div>
                                </div>
                                <div class="row configs-automatic-antecipation" style="<?php if ($store_data['use_automatic_antecipation'] == 0){?>display:none;<?php } ?>">
                                    <div class="form-group col-md-3 <?php echo (form_error('antecipation_type')) ? "has-error" : "";?>">
                                        <label for="antecipation_type"><?=$this->lang->line('antecipation_type');?></label>
                                        <select class="form-control" id="antecipation_type" name="antecipation_type">
                                            <?php foreach (AntecipationTypeEnum::generateList() as $k => $v): ?>
                                                <option value="<?=trim($k)?>" <?=set_select('antecipation_type', trim($v), $store_data['antecipation_type'] == trim($k) || (!$store_data['antecipation_type'] && trim($k) == $antecipacao_dx_default) || (!$antecipacao_dx_default && trim($v) == AntecipationTypeEnum::FULL && !$store_data['antecipation_type'] ) )?>><?=$v ?></option>
                                            <?php endforeach ?>
                                        </select>
                                        <?php echo '<i style="color:red">'.form_error('antecipation_type').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-3 <?php echo (form_error('percentage_amount_to_be_antecipated')) ? "has-error" : "";?>" >
                                        <label for="percentage_amount_to_be_antecipated"><?=$this->lang->line('percentage_amount_to_be_antecipated');?></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control maskperc" id="percentage_amount_to_be_antecipated" value="<?php echo set_value('percentage_amount_to_be_antecipated',$store_data['percentage_amount_to_be_antecipated'] ?: $porcentagem_antecipacao_default);?>" name="percentage_amount_to_be_antecipated" autocomplete="off" maxlength="5"  >
                                            <span class="input-group-addon"><strong>%</strong></span>
                                        </div>
                                        <?php echo '<i style="color:red">'.form_error('percentage_amount_to_be_antecipated').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-3 number_days_advance <?php echo (form_error('number_days_advance')) ? "has-error" : "";?>" >
                                        <label for="number_days_advance"><?=$this->lang->line('number_days_advance');?></label>
                                        <input type="number" step="1" class="form-control" id="number_days_advance" value="<?php echo set_value('number_days_advance',$store_data['number_days_advance'] ?: $numero_dias_dx_default);?>" name="number_days_advance" autocomplete="off" maxlength="2">
                                        <?php echo '<i style="color:red">'.form_error('number_days_advance').'</i>';  ?>
                                    </div>
                                    <div class="form-group col-md-3 <?php echo (form_error('automatic_anticipation_days')) ? "has-error" : "";?>" >
                                        <label for="automatic_anticipation_days"><?=$this->lang->line('automatic_anticipation_days');?></label>
                                        <input placeholder="<?=lang('placeholder_automatic_anticipation_days');?>" type="text" class="form-control" id="automatic_anticipation_days" value="<?php echo set_value('automatic_anticipation_days',$store_data['automatic_anticipation_days'] ?: $automatic_anticipation_days_default);?>" name="automatic_anticipation_days" autocomplete="off">
                                        <?php echo '<i style="color:red">'.form_error('automatic_anticipation_days').'</i>';  ?>
                                    </div>
                                </div>
                            <?php
                            }
                            ?>

                            <div class="row">
                                <hr>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12" >
                                    <h4><?=$this->lang->line('application_financing');?></h4>
                                </div>
                            </div>
                            <?php
                            if (ENVIRONMENT === "development") {
                            ?>
                                <div class="row">
                                    <div class="col-md-12 form-group">
                                        <label class="" for="use_exclusive_cycle" onclick="checkUseExclusiveCycle()">
                                            <?=$this->lang->line('application_use_exclusive_cycle');?>?
                                        </label>
                                        <input type="checkbox" name="use_exclusive_cycle" id="use_exclusive_cycle"
                                           value="1"
                                            <?php
                                            if ($store_data['use_exclusive_cycle']){
                                                echo "checked='checked'";
                                            }
                                            ?>
                                               data-toggle="toggle"
                                               data-on="Sim"
                                               data-off="Não"
                                               data-onstyle="success" data-offstyle="primary"
                                               data-size="mini"
                                        />
                                    </div>
                                </div>
                                <div class="row row-cycles" style="display: <?php echo $store_data['use_exclusive_cycle'] ? 'block' : 'none'; ?>;">
                                    <div class="col-md-12 form-group">
                                        <table id="store_cycles" class="table table-bordered table-striped table-condensed">
                                            <thead>
                                                <tr>
                                                    <th>Marketplace</th>
                                                    <th>Dia - Inicio ciclo</th>
                                                    <th>Dia - Fim ciclo</th>
                                                    <th>Data de pagamento</th>
                                                    <?= $this->data['sellercenter_name'] == 'conectala' ? '<th>Data de pagamento Conectala</th>' : '' ?>
                                                    <th>Data de corte</th>
                                                    <th data-orderable="false" style="width: 5%;"></th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                    <div class="col-md-12 form-group">
                                        <a class="btn btn-link" style="padding-left: 0;" data-toggle="modal" data-target="#register-cycle" onclick="addCycle()">
                                            <i class="fa fa-plus-circle"></i>
                                            Adicionar outro ciclo
                                        </a>
                                    </div>

                                </div>
                            <?php
                            }
                            ?>

                            <div class="row"></div>

                            <div class="row">
                                <hr>
                            </div>

                            <div class="row"></div>

                            <?php if(in_array('createUserFreteRapido', $user_permission)): ?>
                                <!---  Retirada Frete Rápido
				<?php if ($store_data['fr_cadastro'] == 1) { ?> <div class="row"><div class="form-group col-md-12"><strong class="text-red">** <?=$this->lang->line('messages_store_confirm_table_freight');?> **</strong></div></div> <?php } ?>
                <?php if ($store_data['fr_cadastro'] == 2) { ?> <div class="row"><div class="form-group col-md-12"><strong class="text-red">** <?=$this->lang->line('messages_store_wait_create_frete_rapido');?> **</strong></div></div> <?php } ?>
                <?php if ($store_data['fr_cadastro'] == 3) { ?> <div class="row"><div class="form-group col-md-12"><strong class="text-red">** <?=$this->lang->line('messages_store_wait_send_frete_rapido');?> **</strong></div></div> <?php } ?>
                <?php if ($store_data['fr_cadastro'] == 5) { ?> <div class="row"><div class="form-group col-md-12"><strong class="text-red">** <?=$this->lang->line('messages_store_wait_send_frete_rapido');?> **</strong></div></div> <?php } ?>
                <?php if (is_null($store_data['fr_cadastro'])) { ?> <div class="row"><div class="form-group col-md-12"><strong class="text-red">** <?=$this->lang->line('messages_store_fill_field_send_frete_rapido');?> **</strong></div></div> <?php } ?>
                <div class="row">
                    <div class="form-group col-md-6 <?php echo (form_error('fr_email_contato')) ? "has-error" : ""; ?>">
                      	<label for="fr_email_contato"><?= $this->lang->line('application_fr_contact_email'); ?></label>
                      	<input type="email" class="form-control" id="fr_email_contato" name="fr_email_contato" required value="<?php echo set_value('fr_email_contato', $store_data['fr_email_contato']); ?>" placeholder="<?= $this->lang->line('application_enter_contact_email'); ?>" autocomplete="off">
                    	<?php echo '<i style="color:red">' . form_error('fr_email_contato') . '</i>'; ?>
                    </div>
                    <div class="form-group col-md-6 <?php echo (form_error('fr_email_nfe')) ? "has-error" : ""; ?>">
                      	<label for="fr_email_nfe"><?= $this->lang->line('application_fr_nfe_email'); ?></label>
                      	<input type="email" class="form-control" id="fr_email_nfe" name="fr_email_nfe" required value="<?php echo set_value('fr_email_nfe', $store_data['fr_email_nfe']); ?>" placeholder="<?= $this->lang->line('application_enter_nfe_email'); ?>" autocomplete="off">
                    	<?php echo '<i style="color:red">' . form_error('fr_email_nfe') . '</i>'; ?>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                      	<label for="fr_email_login"><?= $this->lang->line('application_fr_login_email'); ?></label>
                      	<span  class="form-control"><?php echo $store_data['fr_email_login']; ?></span>
                	</div>
                	<div class="form-group col-md-3">
                      	<label for="fr_email_login"><?= $this->lang->line('application_fr_password'); ?></label>
                      	<span  class="form-control"><?php echo $store_data['fr_senha']; ?></span>
                	</div>
                </div>
                --->

                                <!---
                <?php if (($store_data['fr_cadastro'] == 2 ) || (is_null($store_data['fr_cadastro']))): ?>
                <div class="row">
                    <div class="form-group col-md-6 <?php echo (form_error('fr_email_login')) ? "has-error" : ""; ?>">
                      	<label for="fr_email_login"><?= $this->lang->line('application_fr_login_email'); ?></label>
                      	<input type="email" class="form-control" id="fr_email_login" name="fr_email_login" required value="<?php echo set_value('fr_email_login', $store_data['fr_email_login']); ?>" placeholder="<?= $this->lang->line('application_enter_fr_login_email'); ?>" autocomplete="off">
                    	<?php echo '<i style="color:red">' . form_error('fr_email_login') . '</i>'; ?>
                	</div>
                     <div class="form-group col-md-3 <?php echo (form_error('fr_senha')) ? "has-error" : ""; ?>">
                      	<label for="fr_senha"><?= $this->lang->line('application_fr_password'); ?></label>
                      	<input type="text" class="form-control" id="fr_senha" name="fr_senha" required minlength="8" maxlength="16" value="<?php echo set_value('fr_senha', $store_data['fr_senha']); ?>" placeholder="<?= $this->lang->line('application_enter_password'); ?>" autocomplete="off">
                    	<?php echo '<i style="color:red">' . form_error('fr_senha') . '</i>'; ?>
                    </div>
                   	<div class="form-group col-md-3 <?php echo (form_error('fr_senha_confirmacao')) ? "has-error" : ""; ?>">
                      	<label for="fr_senha_confirmacao"><?= $this->lang->line('application_fr_confirm_password'); ?></label>
                      	<input type="password" class="form-control" id="fr_senha_confirmacao" name="fr_senha_confirmacao" minlength="8" maxlength="16" value="<?php echo set_value('fr_senha_confirmacao'); ?>" placeholder="<?= $this->lang->line('application_enter_confirm_password'); ?>" autocomplete="off">
                    	<?php echo '<i style="color:red">' . form_error('fr_senha_confirmacao') . '</i>'; ?>
                    </div>
                </div>
                <?php endif; ?>
                --->


                                <div class="row showedit">
                                    <div class="form-group col-md-6 <?php echo (form_error('tipos_volumes[]')) ? "has-error" : "";?>" style="display:none" >  <!---- escondi até ver se --->
                                        <label for="addr_uf"><?=$this->lang->line('application_categories_frete_rapido');?></label>
                                        <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="tipos_volumes" name="tipos_volumes[]" multiple="multiple" title="<?=$this->lang->line('application_select');?>" multiple data-selected-text-format="count > 5">
                                            <!--  <option  disabled value=""><?=$this->lang->line('application_select');?></option> -->
                                            <?php foreach ($tipos_volumes as $tipo_volume):
                                                $selecionado=false;
                                                foreach($tipos_volumes_loja as $selecionado) {
                                                    if ($selecionado['tipos_volumes_id'] == $tipo_volume['id']) {
                                                        $selecionado=true;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <option value="<?php echo $tipo_volume['id']; ?>" <?=set_select('tipos_volumes', $tipo_volume['id'],  $selecionado)?> data-subtext="<?php echo "(".$tipo_volume['codigo'].")" ?>" ><?php echo $tipo_volume['produto'] ?></option>
                                            <?php endforeach ?>
                                        </select>
                                        <?php echo '<i style="color:red">'.form_error('tipos_volumes[]').'</i>';  ?>
                                    </div>
                                </div>

                                <?php if (count($tipos_volumes_loja)>= 0) : ?>
                                    <div class="row hideedit"  style="display:none" >  <!---- escondi até ver se --->
                                        <div class="form-group col-md-6">
                                            <table style="width:100%">
                                                <tr>
                                                    <th><?=$this->lang->line('application_categories_frete_rapido');?>  <button class="btn btn-primary" type="button" id="editTiposVolumes">Editar</button></th>
                                                    <th><?=$this->lang->line('application_status');?></th>
                                                    <th><?=$this->lang->line('application_date');?></th>

                                                </tr>
                                                <?php
                                                foreach($tipos_volumes_loja as $tipo_volume) {
                                                    /* Retirada Frete Rápido
                                                    if ($tipo_volume['status'] == 1) {
                                                        $newCat = '<span class="label label-danger">'.$this->lang->line('application_New').'</span>';
                                                    } elseif ($tipo_volume['status'] == 2)
                                                    {
                                                        $newCat = '<span class="label label-default">'.$this->lang->line('application_correio_ok').'</span>';
                                                    } elseif ($tipo_volume['status'] == 3)
                                                    {
                                                        $newCat = '<span class="label label-warning">'.$this->lang->line('application_in_registration').'</span>';
                                                    } else
                                                    {
                                                        $newCat = '<span class="label label-success">'.$this->lang->line('application_ok').'</span>';
                                                  };
                                                  */
                                                    $newCat = '<span class="label label-success">'.$this->lang->line('application_ok').'</span>';
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $tipo_volume['tipo_volume']; ?></td>
                                                        <td><?php echo $newCat ?></td>
                                                        <td><?php echo date("d/m/Y H:i:s",strtotime($tipo_volume['date_update'])); ?></td>
                                                    </tr>
                                                <?php } ?>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($store_data['fr_cadastro'] == 'XXX'): // era 1, coloquei XXX para sumir com o botão?>

                                    <div class="row">
                                        <div class="form-group col-md-12">
                                            <label class="checkbox-inline">
                                                <input id="confirmfr" name="confirmfr" type="checkbox" <?php echo set_checkbox('confirmfr', '1', false); ?> data-toggle="toggle" data-on="Confirmado" data-off="Não confirmado" data-onstyle="success" data-offstyle="danger" >
                                                Envie os dados de ponto de coleta desta loja para o Frete Rápido e confirme que as tabelas de frete foram criadas
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            <?php endif; ?>

                            <div style="<?php echo ($create_seller_vtex==1) ? '' : 'display:none;';?>" >
                                <div class="row">
                                    <div class="form-group col-md-12" >
                                        <h4><?=$this->lang->line('application_sellercenter');?></h4>
                                    </div>
                                </div>
                                <div class="form-group col-md-7" style="display:none" id="logochange">
                                    <label for="store_image"><?=$this->lang->line('application_logo');?>&nbsp;&nbsp;
                                        <li class="label label-default">
                                            <span class="bg-silver">
                                                Formatos aceitos JPG ou PNG
                                            </span>
                                        </li>
                                    </label>
                                    <div class="kv-avatar">
                                        <div class="file-loading">
                                            <input id="store_image" name="store_image" type="file">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group col-md-5" id="logopreview">
                                    <label class="col-md-12 row">
                                        <?=$this->lang->line('application_logo');?>&nbsp;
                                        <button onClick="toggleLogo(event)" class="btn btn-primary" style="display: block;margin: 10px 0px;"><i class="fa fa-exchange-alt"></i> <?= (is_null( $store_data['logo'])) ? $this->lang->line('application_include_logo') : $this->lang->line('application_change_logo');?></button>
                                    </label>
                                    <span class="logo-lg"><img src="<?php echo base_url() . $store_data['logo'] ?>" width="150" height="150"></span>
                                </div>
                                <div class="row"></div>
                                <div class="form-group col-md-12 <?php echo (form_error('description')) ? 'has-error' : '';  ?>">
                                    <label for="description"><?=$this->lang->line('application_store_description');?></label>
                                    <textarea type="text" class="form-control" id="description" name="description" placeholder="<?=$this->lang->line('application_store_description');?>"><?=set_value('description', $store_data['description'])?></textarea>
                                    <?php echo '<i style="color:red">'.form_error('description').'</i>'; ?>
                                </div>
                                <div class="form-group col-md-12 <?php echo (form_error('exchange_return_policy')) ? 'has-error' : '';  ?>">
                                    <label for="exchange_return_policy"><?=$this->lang->line('application_store_exchange_return_policy');?></label>
                                    <textarea type="text" class="form-control" id="exchange_return_policy" name="exchange_return_policy" placeholder="<?=$this->lang->line('application_store_exchange_return_policy');?>"><?=set_value('exchange_return_policy', $store_data['exchange_return_policy'])?></textarea>
                                    <?php echo '<i style="color:red">'.form_error('exchange_return_policy').'</i>'; ?>
                                </div>
                                <div class="form-group col-md-12 <?php echo (form_error('delivery_policy')) ? 'has-error' : '';  ?>">
                                    <label for="delivery_policy"><?=$this->lang->line('application_store_delivery_policy');?></label>
                                    <textarea type="text" class="form-control" id="delivery_policy" name="delivery_policy" placeholder="<?=$this->lang->line('application_store_delivery_policy');?>"><?=set_value('delivery_policy', $store_data['delivery_policy'])?></textarea>
                                    <?php echo '<i style="color:red">'.form_error('delivery_policy').'</i>'; ?>
                                </div>
                                <div class="form-group col-md-12 <?php echo (form_error('security_privacy_policy')) ? 'has-error' : '';  ?>">
                                    <label for="security_privacy_policy"><?=$this->lang->line('application_store_security_privacy_policy');?></label>
                                    <textarea type="text" class="form-control" id="security_privacy_policy" name="security_privacy_policy" placeholder="<?=$this->lang->line('application_store_security_privacy_policy');?>"><?=set_value('security_privacy_policy', $store_data['security_privacy_policy'])?></textarea>
                                    <?php echo '<i style="color:red">'.form_error('security_privacy_policy').'</i>'; ?>
                                </div>
                            </div>
                            <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
                            <div style="<?php echo ($create_seller_mosaico==1 && $create_seller_vtex!=1) ? '' : 'display:none;';?>" >
                                <div class="row">
                                    <hr>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12" >
                                        <h4><?=$this->lang->line('application_sellercenter');?></h4>
                                    </div>
                                </div>

                                <div class="form-group col-md-7" style="display:none" id="logochange_msc">
                                    <label for="store_image"><?=$this->lang->line('application_logo');?>&nbsp;&nbsp;
                                        <li class="label label-default">
                                            <span class="bg-silver">
                                                Formatos aceitos JPG ou PNG
                                            </span>
                                        </li>
                                    </label>
                                    <div class="kv-avatar">
                                        <div class="file-loading">
                                            <input id="store_image_msc" name="store_image" type="file">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group col-md-5" id="logopreview_msc">
                                    <label class="col-md-12 row">
                                        <?=$this->lang->line('application_logo');?>&nbsp;
                                        <button onClick="toggleLogoMsc(event)" class="btn btn-primary" style="display: block;margin: 10px 0px;"><i class="fa fa-exchange-alt"></i> <?= (is_null( $store_data['logo'])) ? $this->lang->line('application_include_logo') : $this->lang->line('application_change_logo');?></button>
                                    </label>
                                    <span class="logo-lg"><img src="<?php echo base_url() . $store_data['logo'] ?>" width="200" height="200"></span>
                                </div>

                                <div class="row"></div>
                                <div class="form-group col-md-3">
                                    <label for="aggregate_select">Aggregate Merchant</label>
                                    <select class="form-control" id="aggregate_select" name="aggregate_select" style="width: 100%" >
                                         <?php if ($selected_aggregate):?>
                                            <option value="<?=$selected_aggregate['id']?>" selected><?= htmlspecialchars($selected_aggregate['name']) ?></option>
                                        <?php endif; ?>
                                    </select>
                                    <input type="hidden" name="aggregate_select_name" id="aggregate_select_name_hidden" value="<?= $selected_aggregate['name']??''?>">
                                </div>
                                <div class="row"></div>
                                <?php
                                $allsc = array();
                                foreach($selected_sc as $slc) {
                                    $allsc[] = $slc['sc_id'];
                                }
                                ?>
                                <div class="form-group col-md-6 col-xs-12">
                                    <label for="sales_channel" class="normal"><?=$this->lang->line('application_rules_buy_channel');?>(*)</label>
                                    <select class="form-control selectpicker show-tick" id="msc_sales_channel" name ="msc_sales_channel[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 2" title="<?=$this->lang->line('application_select');?>">
                                        <?php foreach ($available_sc as $sc) { ?>
                                            <option value="<?= $sc['id'] ?>"  <?php echo set_select('sales_channel', $sc['id'],in_array($sc['id'],$allsc)); ?> ><?= $sc['mosaico_value'] ?></option>
                                        <?php } ?>
                                    </select>
                                    <?php echo '<i style="color:red">'.form_error('msc_sales_channel[]').'</i>'; ?>
                                </div>
                            </div>
                            <?php endif;?>
                        </div>

                        <div class="box-footer">
                        <?php if(in_array('updateStore', $this->permission)): ?>
                            <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
                        <?php endif;?>
                            <a href="<?php echo base_url('stores/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                        </div>
                    </div>
                    <?php if ($data_company['multi_channel_fulfillment'] && $store_id_principal_multi_cd): ?>
                    <div class="box">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_multi_cds');?></h3>
                            <a class="btn btn-primary pull-right" href="<?=base_url("stores/create/$data_company[id]")?>"><?=$this->lang->line('application_create_new_cd');?></a>
                        </div>
                        <div class="box-body">
                            <table id="manageTable" class="table table-bordered table-striped" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                                <thead>
                                <tr>
                                    <th><?=$this->lang->line('application_id');?></th>
                                    <th><?=$this->lang->line('application_seller_l');?></th>
                                    <th><?=$this->lang->line('application_company');?></th>
                                    <th><?=$this->lang->line('application_date_create');?></th>
                                    <th ><?=$this->lang->line('application_store_status');?></th>
                                    <th style="width:100px"><?=$this->lang->line('application_action');?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach($stores_by_company as $key => $store): ?>
                                    <?php
                                    // Não mostrar a primeira loja porque é a loja pulmão
                                    if ($key != 0): ?>
                                    <tr>
                                        <td><?=$store['id']?></td>
                                        <td><?=$store['name']?></td>
                                        <td><?=$data_company['name']?></td>
                                        <td><?=dateFormat($store['create_at'], DATETIME_BRAZIL)?></td>
                                        <td><?=$store['active'] ? '<span class="label label-success">' . $this->lang->line('application_active') . '</span>' : '<span class="label label-warning">' . $this->lang->line('application_inactive') . '</span>'?></td>
                                        <td><a href="<?=base_url('stores/update/' . $store['id'])?>" class="btn btn-default"><i class="fa fa-pencil"></i></a></td>
                                    </tr>
                                    <?php endif;?>
                                <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>
                    </div>
                    <?php endif;?>
                    <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
                    <input type="hidden" name="pj_pf" id="pj_pf" value="<?=$store_data['pj_pf']?>">
                    <?php endif; ?>
                </form>
                <!-- /.box -->
            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->


    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<div class="modal fade" tabindex="-1" role="dialog" id="listaObs">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="width: 800px;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    Logs Cadastro Subconta
                </h4>
            </div>
            <div class="modal-body" id="divListObsFunc">
                Carregando....
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="logsVacation">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="width: 800px;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    Logs de Férias
                </h4>
            </div>
            <div class="modal-body" id="divLogsVacation">
                Carregando....
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="listLogsFinancialManagementSystem">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="width: 800px;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    Logs Cadastro <?php echo $is_using_financial_management_system ? $financialManagementSystemName : ''; ?>
                </h4>
            </div>
            <div class="modal-body" id="divListLogsFinancialManagementSystem">
                Carregando....
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close')?></button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="register-cycle" style="display: none" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <span class="modal-title-initial-text">Adicionar</span>
                    ciclo por Loja
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>

            <form role="form" id="formCycle" method="post">
                <input type="hidden" name="vStores" value="<?=$this->data['store_data']['id']; ?>">
                <input type="hidden" name="vHiddenId" value="">
                <div class="modal-body">

                    <div id="modal-insert" class="container-fluid">

                        <div class="overlay-wrapper" id="modal-cycle-form">

                            <div class="overlay saving" style="display: none;">
                                <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                                <div class="text-bold pt-2 text-loading">Aguarde, tentando salvar o ciclo...</div>
                            </div>

                            <div id="messages_cycle"></div>

                            <div class="row">
                                <div class="col-md-8">
                                    <label for="">Marketplace</label>
                                    <select class="form-control" style="width: 100%;" tabindex="-1" aria-hidden="true" name="vMarketplace">
                                        <option disabled value="">Selecione</option>
                                        <?php
                                        if ($this->data['marketplaces']) foreach ($this->data['marketplaces'] as $marketplace){
                                        ?>
                                            <option value="<?=$marketplace->id;?>">
                                                <?=$marketplace->mkt_place;?>
                                            </option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="">Data de corte</label>
                                    <select class="form-control" style="width: 100%;" tabindex="-1" aria-hidden="true" name="vDateCut">
                                        <option disabled value="">Selecione</option>
                                        <?php
                                        if ($this->data['cycle_cut_dates']) foreach ($this->data['cycle_cut_dates'] as $cut_date){
                                        ?>
                                            <option value="<?=$cut_date->id;?>"><?=$cut_date->cut_date;?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <span style="color:#000;margin-top: 24px;font-size:18px;width: 100%;float: left;margin-bottom: 12px;">Período do ciclo</span>

                            <div class="row">
                                <div class="col-md-4">
                                    <label for="">Dia - INÍCIO ciclo</label>
                                    <input class="form-control" type="number" min="1" max="31" placeholder="Digite uma data para inicio de ciclo" name="vDataInicio">
                                </div>
                                <div class="col-md-4">
                                    <label for="">Dia - FIM ciclo</label>
                                    <input class="form-control" type="number" min="1" max="31" placeholder="Digite uma data para encerramento do ciclo" name="vDataFim">
                                </div>
                                <div class="col-md-4">
                                    <div class="row" style="padding-top: 20px;">
                                        ou
                                        <button type="button" class="btn btn-outline-primary"
                                                style="margin-top:5px;margin-left:5px;border-color: #0066CC; background-color: #ffffff;"
                                                onclick="showCyclesExisting()">
                                            <i class="fas fa-plus-circle"></i> Adicionar ciclo existente
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <span style="color:#000;margin-top: 24px;font-size:18px;width: 100%;float: left;margin-bottom: 12px;">Datas de pagamento</span>

                            <div class="row">
                                <div class="col-md-6">
                                    <label for="">Data de Pagamento</label>
                                    <input class="form-control" type="number" min="1" max="31" placeholder="Digite a data de pagamento" name="vDataPagamentoMkt">
                                </div>
                                <?php
                                if ($this->data['sellercenter_name'] == 'conectala'){
                                ?>
                                    <div class="col-md-6">
                                        <label for="">Data de Pagamento - Conectalá</label>
                                        <input class="form-control" type="number" min="1" max="31" placeholder="Digite a data de pagamento Conecta Lá" name="vDataPagamentoConectala">
                                    </div>
                                <?php
                                }
                                ?>
                            </div>

                        </div>

                        <div id="modal-existing" class="container-fluid" style="display: none;">

                            <div class="overlay-wrapper">

                                <div class="overlay loading" style="display: none;">
                                    <i class="fas fa-3x fa-sync-alt fa-spin"></i>
                                    <div class="text-bold pt-2">Aguarde, checando ciclo escolhido...</div>
                                </div>

                                <table class="table table-hover table-bordered" id="cycles_registered">
                                    <thead>
                                    <tr>
                                        <th>Dia - Início Ciclo</th>
                                        <th>Dia - Fim Ciclo</th>
                                        <th>Data de pagamento</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>

                            <button type="button" class="btn btn-default float-right" style="margin-top:15px;"
                                    onclick="closeModalModels()">
                                <i class="fa fa-arrow-left"></i> Voltar
                            </button>
                        </div>

                    </div>

                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        Fechar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <span class="modal-title-initial-text">Adicionar</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_confirm_add_cycle" tabindex="-1" role="dialog" aria-labelledby="modal_confirm_add_cycleLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title text-black" id="modal_confirm_add_cycleLabel">Atenção!</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-black">
                <strong>Ao alterar o ciclo, os pedidos não pagos serão recalculados para os novos ciclos.</strong>
                <br />
                <br />
                Deseja continuar e adicionar um ciclo específico para a loja?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cancelAddCycle()">Cancelar</button>
                <button type="button" class="btn btn-primary" style="width: 150px;" onclick="confirmAddCycle()">Sim</button>
            </div>
        </div>
    </div>
</div>


<?php
// modal para mandar pedido para frete a contratar
if ($external_marketplace_integration) {?>
    <div class="modal fade" tabindex="-1" role="dialog" id="modalIssueInvoice">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_issue_invoice')?></span></h4>
                </div>
                <form role="form" action="<?=base_url('stores/getExternalNfse') ?>" method="post" id="formExternalNfse">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="month_year_issue_invoice"><?=$this->lang->line('application_search_input')?> <?=$this->lang->line('application_conciliacao_month_year')?></label>
                                <select class="form-control" id="month_year_issue_invoice" required>
                                    <option value=""><?=$this->lang->line('application_select')?></option>
                                    <?php for ($x = 0; $x < 6; $x++) { ?>
                                        <option value="<?=dateFormat(subtractMonthToDate(dateNow()->format(DATE_INTERNATIONAL), $x), 'Y-m')?>"><?=dateFormat(subtractMonthToDate(dateNow()->format(DATE_INTERNATIONAL), $x), 'm/Y')?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 response mt-3" style="display: none">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?=$this->lang->line('application_name')?></th>
                                            <th><?=$this->lang->line('application_issue_date')?></th>
                                            <th><?=$this->lang->line('application_number')?></th>
                                            <th><?=$this->lang->line('application_amount')?></th>
                                            <th><?=$this->lang->line('application_action')?></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <input type="hidden" id="store_id_issue_invoice" value="<?=$store_data['id']?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                        <button type="submit" class="btn btn-primary" id="confirmaReturn" name="confirmaReturn"><?=$this->lang->line('application_issue_invoice');?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
<script type="text/javascript">

    var base_url = "<?php echo base_url(); ?>";

    var flag_bloqueio_repasse = "<?php echo $store_data['flag_bloqueio_repasse']; ?>";
    var flag_antecipacao_repasse = "<?php echo $store_data['flag_antecipacao_repasse']; ?>";
    var usar_mascara_banco = "<?php echo $usar_mascara_banco ?>";
    var banks = <?php echo json_encode($banks) ?>;
    var empresas = <?php echo json_encode($empresas); ?>;
    var fr_cadastro = <?php echo $store_data['fr_cadastro']; ?>

        $(document).ready(function() {
            var bank_name = $('#bank option:selected').val();
            var agency = $('#agency').val();
            var account = $('#account').val();
            
            if(usar_mascara_banco == true){
                applyBankMask(bank_name);
            }
            
            $('#flag_bloqueio_repasse').val(flag_bloqueio_repasse);
            $('#flag_antecipacao_repasse').val(flag_antecipacao_repasse);
            $('#flag_store_migration').attr("disabled", true);

            if($('#what_integration').val()==''){
                $("#how_up_and_fature_div").removeClass("hidden");
            }else{
                $("#how_up_and_fature_div").addClass("hidden");
            }
            $('#what_integration').change(()=>{
                console.log()
                if($('#what_integration').val()==''){
                    $("#how_up_and_fature_div").removeClass("hidden");
                }else{
                    $("#how_up_and_fature_div").addClass("hidden");
                }
            })
            $('.select2').select2();

            <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
            $('#aggregate_select').select2({
                placeholder: '<?=$this->lang->line('application_select_merchant')?>',
                tags: true,
                minimumInputLength: 1,
                ajax: {
                    url: '<?= site_url('Stores/fetchAggregate') ?>',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term // search term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: $.map(data, function(item) {
                                return {
                                    id: item.id,
                                    text: item.text
                                }
                            })
                        };
                    },
                    cache: true
                },
                createTag: function(params) {
                    return {
                        id: params.term,
                        text: params.term,
                        newOption: true
                    };
                },
                templateResult: function(data) {
                    var $result = $("<span></span>");
                    $result.text(data.text);
                    if (data.newOption) {
                        $result.append(" <em>(<?=$this->lang->line('application_new_merchant')?>)</em>");
                    }
                    return $result;
                }
            });

            $('#aggregate_select').data('select2').on('results:message', function (e) {
                this.dropdown._positionDropdown();
            }) 

            $('#aggregate_select').on('select2:select', function (e) {
                var selectedText = e.params.data.text;
                $('#aggregate_select_name_hidden').val(selectedText);
            });
            <?php endif;?>
            $("#storeNav").addClass('active');
            $('form input').not('.zipcodes_multi_channel_fulfillment input, #service_charge_value, #service_charge_freight_option, #service_charge_freight_value')
                .attr("readonly", <?=!in_array('updateStore', $this->permission)?'true':'false' ?>);
            if(<?=!in_array('updateStore', $this->permission)?'true':'false' ?>){
                $('form input[type=checkbox]')
                    .attr("onclick", "return false;");
            }

            $('form select')
                .attr("disabled", <?=!in_array('updateStore', $this->permission)?'true':'false' ?>);
            $('form textarea')
                .attr("disabled", <?=!in_array('updateStore', $this->permission)?'true':'false' ?>);

            if(flag_antecipacao_repasse == "N"){
                $('#flag_antecipacao_repasse').attr("disabled", true);
            }


            if (fr_cadastro==2) {  // permite alterar as categorias logo de cara
                $(".hideedit").hide();
                $(".showedit").hide(); // todos hide por enquanto
            } else {
                $(".hideedit").hide(); // todos hide por enquanto
                $(".showedit").hide();
            }

            $("#editTiposVolumes").click(function(event){
                event.preventDefault();
                $(".hideedit").hide();
                $(".showedit").show();
            });

			$('.maskperc').inputmask({
			  alias: 'numeric',
			  allowMinus: false,
			  digits: 2,
			  max: 100.00
			});

            var id_company=$('#company_id option:selected').val();
            $.each(empresas, function(i,empresa){
                // console.log(i+" "+empresas[i].id+" "+empresas[i].name+" "+empresas[i].raz_social+" ASS= "+empresas[i].associate_type);
                if(empresas[i].id ==id_company) {
                    if (empresas[i].associate_type == 0) {
                        $("#hideButton").show();
                    }
                    else {
                        $("#hideButton").hide();
                    }
                }
            });
            $("#bank").change(function () {
                $('#agency').val('');
                $('#account').val('');
                var bank_name = $('#bank option:selected').val();
                if(usar_mascara_banco == true){
                    applyBankMask(bank_name);
                }
            });
            $("#company_id").change(function () {
                var id_company=$('#company_id option:selected').val();
                $.each(empresas, function(i,empresa){
                    // console.log(i+" "+empresas[i].id+" "+empresas[i].name+" "+empresas[i].raz_social+" ASS= "+empresas[i].associate_type);
                    if(empresas[i].id ==id_company) {
                        if (empresas[i].associate_type == 0) {
                            $("#hideButton").show();
                        }
                        else {
                            $("#hideButton").hide();
                        }
                    }
                });
            });

            ownLogistic();
            comissaoFrete();

            var btnCust = '<button type="button" class="btn btn-secondary" title="Add picture tags" ' +
                'onclick="alert(\'Call your custom code here.\')">' +
                '<i class="glyphicon glyphicon-tag"></i>' +
                '</button>';
            $("#store_image").fileinput({
                overwriteInitial: true,
                uploadUrl: "<?= base_url('/Products/saveImageProduct'); ?>",
                maxFileSize: 1500,
                showClose: false,
                showCaption: false,
                browseLabel: '',
                removeLabel: '',
                browseIcon: '<i class="glyphicon glyphicon-folder-open"></i>',
                removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
                maxFileCount: 1,
                removeTitle: 'Cancel or reset changes',
                elErrorContainer: '#kv-avatar-errors-1',
                msgErrorClass: 'alert alert-block alert-danger',
                layoutTemplates: {main2: '{preview} {remove} {browse}'},
                allowedFileExtensions: ["jpg","png", "gif"],
                fileActionSettings: {
                    showUpload: false,
                },
            });

            <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
            $("#store_image_msc").fileinput({
                overwriteInitial: true,
                uploadUrl: "<?= base_url('/Products/saveImageProduct'); ?>",
                maxFileSize: 1500,
                showClose: false,
                showCaption: false,
                browseLabel: '',
                removeLabel: '',
                browseIcon: '<i class="glyphicon glyphicon-folder-open"></i>',
                removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
                maxFileCount: 1,
                removeTitle: 'Cancel or reset changes',
                elErrorContainer: '#kv-avatar-errors-1',
                msgErrorClass: 'alert alert-block alert-danger',
                layoutTemplates: {main2: '{preview} {remove} {browse}'},
                allowedFileExtensions: ["jpg","png", "gif"],
                fileActionSettings: {
                    showUpload: false,
                },
            });
            <?php endif; ?>
            $("#CNPJ").on('focusout',function(){
				var val = $(this).val();
			    $(this).val(val.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/g,"\$1.\$2.\$3\/\$4\-\$5"));
			});

			$("#responsible_cpf").on('focusout',function(){
				var val = $(this).val();
			    $(this).val(val.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/g,"\$1.\$2.\$3\-\$4"));
			});

            changeAntecipationType();

            $('#manageTable').DataTable({
                "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
                "processing": true,
                "scrollX": true,
                "sortable": true,
                "searching": true
            });

            <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
            const is_pj = $('#pj_pf').val() === "pj";

            $(".show_pf").css({'display': is_pj ? 'none': 'block'});
            $(".show_pj").css({'display': !is_pj ? 'none': 'block'});
            $('#raz_soc, #CNPJ, #inscricao_estadual').prop('required', is_pj);
            $('#exempted').prop('checked', false).trigger('change');
            $('#inscricao_estadual').prop('disabled', false);
            $('#CPF').prop('required', !is_pj);
            <?php endif; ?>
        });

    $('#antecipation_type').change(function () {
        changeAntecipationType();
    });
    function applyBankMask(bank_name){
        $.each(banks, function(i,bank){
            if(banks[i].name == bank_name) {
                var pattern = /[a-zA-Z0-9]/ig;
                mask_account = banks[i].mask_account.replaceAll(pattern, "#")
                mask_agency = banks[i].mask_agency.replace(pattern, "#")
                $('#agency').mask(mask_agency);
                $('#agency').attr("placeholder", banks[i].mask_agency);
                $('#agency').attr("maxlength", mask_agency.length);
                $('#agency').attr("minlength", mask_agency.length);
                $('#account').mask(mask_account);
                $('#account').attr("placeholder", banks[i].mask_account);
                $('#account').attr("maxlength", mask_account.length);
                $('#account').attr("minlength", mask_account.length);
            }
        });
    }

    function changeAntecipationType()
    {
        if($('#antecipation_type').val()=='<?=AntecipationTypeEnum::DX;?>')
        {
            $('.number_days_advance').show();
            $('#number_days_advance').prop('required', true);
        }else{
            $('.number_days_advance').hide();
            $('#number_days_advance').val('0');
            $('#number_days_advance').removeAttr('required');
        }
    }

    // edit function
    function copyFunc(id)
    {
        idSel =  document.getElementById('company_id').value;
        if (id != idSel) {
            id = idSel;
            alert('ATENÇÂO: Voce irá mudar a Empresa de uma Loja. Se não for isso que você pretendia, não salve a alteração!')
        }
        $.ajax({
            url: '<?php echo base_url(); ?>' + 'index.php/Stores/fetchCompanyDataById/'+id,
            type: 'get',
            dataType: 'json',
            success:function(response) {
                $("#raz_soc").val(response.raz_social);
                $("#CNPJ").val(response.CNPJ);
                $("#inscricao_estadual").val(response.IEST);
                $("#address").val(response.address);
                $("#addr_num").val(response.addr_num);
                $("#addr_compl").val(response.addr_compl);
                $("#addr_neigh").val(response.addr_neigh);
                $("#addr_city").val(response.addr_city);
                $("select[name*='addr_uf']").val(response.addr_uf).attr('selected', true).trigger('change');
                // $("select[name*='addr_uf']").trigger('change');
                $("#zipcode").val(response.zipcode);
                $("#country").val(response.country);
                $("#phone_1").val(response.phone_1);
                $("#phone_2").val(response.phone_2);
                $("#responsible_name").val(response.gestor);
                $("#responsible_email").val(response.email);
                alert('Dados da Empresa Copiados');
                return false;
            }
        });
    }

    function mascaraMutuario(o,f){
        v_obj=o
        v_fun=f
        setTimeout('execmascara()',1)
    }

    function execmascara(){
        v_obj.value=v_fun(v_obj.value)
    }

    function cnpj(v){

        //Remove tudo o que não é dígito
        v=v.replace(/\D/g,"")
        //Coloca ponto entre o segundo e o terceiro dígitos
        v=v.replace(/^(\d{2})(\d)/,"$1.$2")
        //Coloca ponto entre o quinto e o sexto dígitos
        v=v.replace(/^(\d{2})\.(\d{3})(\d)/,"$1.$2.$3")
        //Coloca uma barra entre o oitavo e o nono dígitos
        v=v.replace(/\.(\d{3})(\d)/,".$1/$2")
        //Coloca um hífen depois do bloco de quatro dígitos
        v=v.replace(/(\d{4})(\d)/,"$1-$2")

        return v
    }

    function exemptIE() {
        const ie = $('#inscricao_estadual')[0].hasAttribute('disabled')
        if (!ie) {
            $('#inscricao_estadual').attr('disabled', 'disabled')
        } else {
            $('#inscricao_estadual').removeAttr('disabled')
        }
    }

    <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
    function exemptMun() {
        const ie = $('#inscricao_municipal')[0].hasAttribute('disabled')
        if (!ie) {
            $('#inscricao_municipal').attr('disabled', 'disabled')
        } else {
            $('#inscricao_municipal').removeAttr('disabled')
        }
    }
    <?php endif; ?>

    function sameAddress() {
        const sameAddress = $('#same')[0].hasAttribute('checked')

        const fields = [
            {original: 'zipcode', copy: 'business_code'},
            {original: 'address', copy: 'business_street'},
            {original: 'addr_num', copy: 'business_addr_num'},
            {original: 'addr_compl', copy: 'business_addr_compl'},
            {original: 'addr_neigh', copy: 'business_neighborhood'},
            {original: 'addr_city', copy: 'business_town'},
            {original: 'addr_uf', copy: 'business_uf'},
            {original: 'country', copy: 'business_nation'},
        ]

        if (!sameAddress) {
            fields.forEach((item) => {
                if ($('#'+item.original).val() === undefined) {
                    $('#'+item.copy)[0].value = $("select[name*='addr_uf']").val();
                    $('#'+item.copy).attr('readonly', 'readonly')
                } else{
                    $('#'+item.copy)[0].value = $('#'+item.original).val()
                    $('#'+item.copy).attr('readonly', 'readonly')
                }
            })
            $('#same').attr('checked', 'checked')
        } else {
            fields.forEach((item) => {
                $('#'+item.copy)[0].value = ''
                $('#'+item.copy).removeAttr('readonly')
            })
            $('#same').removeAttr('checked')
        }
    }

    function consultZip(id) {
        $.ajax({
            url: 'https://viacep.com.br/ws/'+id+'/json/',
            type: 'get',
            dataType: 'json',
            success: function(response) {
                $('#business_street')[0].value = response.logradouro
                $('#business_neighborhood')[0].value = response.bairro
                $('#business_town')[0].value = response.localidade
                $('#business_uf')[0].value = response.uf
                $('#business_nation')[0].value = 'BR'
                return
            }
        });
    }

    function ownLogistic() {
        const typeSeller = $('#freight_seller_type').val();
        var OL = document.getElementById("freight_seller");
        if (OL.checked == true) {
            if (typeSeller == 1)
                $('#freight_seller_end_point').attr("required",true);

            $(".own_logistic").show();
        }
        else {
            $('#freight_seller_end_point').attr("required",false);
            $(".own_logistic").hide();
        }
        $('#freight_seller_type').trigger('change');
    }

    function comissaoFrete() {

        var OPtion = document.getElementById("service_charge_freight_option");
        if (OPtion.checked == false) {
            $('#service_charge_freight_value').attr("required",true);
            $(".comissaoFrete").show();
        }
        else {
            $('#service_charge_freight_value').attr("required",false);
            $(".comissaoFrete").hide();
        }
    }

    $('#freight_seller_type').change(function(){
        const id = parseInt($(this).val());

        if ($('#freight_seller').is(':not(:checked)')) return false;

        if (id === 1 || id === 10) {
            $('#freight_seller_code').val('').attr("required", false).closest('div').hide();
            $('#freight_seller_end_point').closest('div').addClass('col-md-9').removeClass('col-md-6');
            $('#freight_seller_end_point').attr({"required":true, "type": "url", "disabled": false});
            $.get( "<?=base_url('Api/Language/application_own_logistic_endpoint') ?>", data => {
                $('label[for="freight_seller_end_point"]').text(data.text);
                $('#freight_seller_end_point').attr('placeholder', data.text);
            });
        }
        else if (id === 2) {
            $('#freight_seller_code').val('').attr("required", false).closest('div').hide();
            $('#freight_seller_end_point').closest('div').addClass('col-md-9').removeClass('col-md-6');
            $('#freight_seller_end_point').val('').attr({"required": false, "type": "url", "disabled": true});
            $.get( "<?=base_url('Api/Language/application_own_logistic_endpoint') ?>", data => {
                $('label[for="freight_seller_end_point"]').text(data.text);
                $('#freight_seller_end_point').attr('placeholder', data.text);
            });
        }
        else if (id === 3) {
            $('#freight_seller_code').attr("required", true).closest('div').show();
            $('#freight_seller_end_point').closest('div').addClass('col-md-6').removeClass('col-md-9');
            $('#freight_seller_end_point').attr({"required": true, "type": "text", "disabled": false});
            $.get( "<?=base_url('Api/Language/application_intelipost_token') ?>", data => {
                $('label[for="freight_seller_end_point"]').text(data.text);
                $('#freight_seller_end_point').attr('placeholder', data.text);
            });
        }
        else if (id === 4) {
            $('#freight_seller_code').val('').attr("required", false).closest('div').hide();
            $('#freight_seller_end_point').closest('div').addClass('col-md-9').removeClass('col-md-6');
            $('#freight_seller_end_point').val('').attr({"required": false, "type": "text", "disabled": true});
            $.get( "<?=base_url('Api/Language/application_intelipost_token') ?>", data => {
                $('label[for="freight_seller_end_point"]').text(data.text);
                $('#freight_seller_end_point').attr('placeholder', data.text);
            });
        }
        else if (id === 5 || id === 7) {
            $('#freight_seller_code').val('').attr("required", false).closest('div').hide();
            $('#freight_seller_end_point')
                .attr({"required": true, "type": "text", "disabled": false})
                .closest('div')
                .addClass('col-md-9')
                .removeClass('col-md-6');
            $('label[for="freight_seller_end_point"]').text('Token');
            $('#freight_seller_end_point').attr('placeholder', 'Token');
        }
        else if (id === 6 || id === 8 || id === 9) {
            $('#freight_seller_code').val('').attr("required", false).closest('div').hide();
            $('#freight_seller_end_point')
                .val('')
                .attr({"required": false, "type": "url", "disabled": true})
                .closest('div')
                .addClass('col-md-9')
                .removeClass('col-md-6');
            $('label[for="freight_seller_end_point"]').text('Token');
            $('#freight_seller_end_point').attr('placeholder', 'Token');
        }
    });

    $('#freight_seller').change(function(){
        const status = $(this).is(':checked');

        if (status) $('#freight_seller_type').trigger('change');
        else $('#freight_seller_end_point').attr('required', false);
    });

    function toggleLogo(e) {
        e.preventDefault();
        $("#logochange").toggle();
        $("#logopreview").toggle();
    }

    <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
    function toggleLogoMsc(e) {
        e.preventDefault();
        $("#logochange_msc").toggle();
        $("#logopreview_msc").toggle();
    }
    <?php endif;?>

    function listarMensagensSubconta(id){
        if(id){

            $("#divListObsFunc").html("Carregando...");

            var pageURL = base_url.concat("stores/buscamensagenssubconta");

            $.post( pageURL, {storeId: id}, function( data ) {

                var obj = JSON.parse(data);
                var texto = '<table class="table table-bordered table-striped"><thead><tr><th>Status</th><th>Mensagem</th><th>Data do Log</th></tr></thead><tbody>';

                Object.keys(obj).forEach(function(k){
                    texto = texto.concat("<tr><td>",obj[k].status,"</td><td><pre style='max-width: 555px;'>",obj[k].description,"</pre></td><td>",obj[k].date_insert,"</td></tr>");
                });

                texto = texto.concat("</tbody></table>");
                $("#divListObsFunc").html(texto);
            });
        }
    }

    function listaVacation(id){
        if(id){

            $("#divLogsVacation").html("Carregando...");

            var pageURL = base_url.concat("stores/buscavacation");

            $.post( pageURL, {storeId: id}, function( data ) {
                var obj = JSON.parse(data);
                var texto = '<table class="table table-bordered table-striped"><thead><tr><th>Status</th><th>Email do Responsável</th><th>Data da alteração</th></tr></thead><tbody>';
                obj = obj["logs"];

                for(let log of obj){
                texto = texto.concat("<tr><td>", log["status"] == "ON Vacation"?"Férias Ativada":"Férias Desativada", "</td><td>", log["email_do_responsavel"], "</td><td>", log["date_log"], "</td></tr>");
                }

                texto = texto.concat("</tbody></table>");
                $("#divLogsVacation").html(texto);
            });
        }
    }

    function listLogsFinancialManagementSystem(id){
        if(id){

            $("#divListLogsFinancialManagementSystem").html("Carregando...");

            var pageURL = base_url.concat("stores/findlogsfinancialmanagementsystem");

            $.post( pageURL, {storeId: id}, function( data ) {

                var obj = JSON.parse(data);
                var texto = '<table class="table table-bordered table-striped"><thead><tr><th>Status</th><th>Cód. Resposta</th><th>Requisição/Resposta</th><th>Data do Log</th></tr></thead><tbody>';

                Object.keys(obj).forEach(function(k){
                    texto = texto.concat("<tr><td>",obj[k].job_name,"</td><td>",obj[k].response_code,"</td><td><pre style='max-width: 555px;'>",obj[k].payload,"</pre>" +
                        "<br />Resposta: <pre style='max-width: 555px;'>",obj[k].response_json,"</pre></td><td>",obj[k].created_at,"</td></tr>");
                });

                texto = texto.concat("</tbody></table>");
                $("#divListLogsFinancialManagementSystem").html(texto);
            });
        }
    }

    function changeAutomaticAntecipation(obj){
        if (obj.checked){
            $('.configs-automatic-antecipation').slideDown();

            if($("#automatic_anticipation_days").is(":visible"))
                $('#automatic_anticipation_days').prop('required', true);
            else
                $('#automatic_anticipation_days').removeAttr('required');

            if($("#number_days_advance").is(":visible"))
                $('#number_days_advance').prop('required', true);
            else
                $('#number_days_advance').removeAttr('required');
        }
        else
        {
            $('.configs-automatic-antecipation').slideUp();
            $('#automatic_anticipation_days').removeAttr('required');
            $('#number_days_advance').removeAttr('required');
        }
    }

    function mountStoreCyclesDataTable() {

        // initialize the datatable
        store_cycles_datatable = $('#store_cycles').DataTable({
            "language": {
                "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang',
            },
            "scrollX": true,
            "autoWidth": false,
            "processing": true,
            "searching": false,
            "paging": false,
            "serverSide": true,
            "serverMethod": "POST",
            "ajax": {
                "url": base_url + 'cycles/getAllCyclesByStoreToStoreScreen/<?php echo $this->data['store_data']['id']; ?>',
                "type": 'POST'
            },
            "order": [[ 0, "desc" ]],
            "columns": [
                {"data": "marketplace"},
                {"data": "start_date", "class":"text-center"},
                {"data": "end_date", "class":"text-center"},
                {"data": "payment_date", "class":"text-center"},
                <?php
                if ($this->data['sellercenter_name'] == 'conectala'){
                ?>
                    {"data": "payment_date_conecta", "class":"text-center"},
                <?php
                }
                ?>
                {"data": "cut_date"},
                {"data": "buttons"},
            ],
            fnServerParams: function(data) {
                data['order'].forEach(function(items, index) {
                    data['order'][index]['column'] = data['columns'][items.column]['data'];
                });
            },
        });

    }

    $(document).ready(function () {
        <?php
        if ($store_data['use_exclusive_cycle']){
        ?>
            mountStoreCyclesDataTable();
        <?php
        }
        ?>
    });

    function addCycle(){
        $('.modal-title-initial-text').text('Adicionar');
        $('#messages_cycle').html('');
        $('input[name="vHiddenId"]').val('');
        $('select[name="vMarketplace"]').val('');
        $('select[name="vDateCut"]').val('');
        $('input[name="vDataInicio"]').val('');
        $('input[name="vDataFim"]').val('');
        $('input[name="vDataPagamentoMkt"]').val('');
        $('input[name="vDataPagamentoConectala"]').val('');
        bindFormCycle();
    }

    function editThisCycle(cycleId){
        $('.modal-title-initial-text').text('Editar');
        $('.text-loading').text('Carregando detalhes do ciclo...');
        $('.saving').show();
        $('#messages_cycle').html('');
        $.ajax({
            url: base_url+'cycles/getStoreCycleById/'+cycleId,
            type: 'post',
            dataType: 'json',
            success:function(response) {

                $('.saving').hide();

                $('input[name="vHiddenId"]').val(response[0].pmc_id);
                $('select[name="vMarketplace"]').val(response[0].id_mkt);
                $('select[name="vDateCut"]').val(response[0].cut_id);
                $('input[name="vDataInicio"]').val(response[0].data_inicio);
                $('input[name="vDataFim"]').val(response[0].data_fim);
                $('input[name="vDataPagamentoMkt"]').val(response[0].data_pagamento);
                <?php
                if ($this->data['sellercenter_name'] == 'conectala'){
                ?>
                    $('input[name="vDataPagamentoConectala"]').val(response[0].data_pagamento_conecta);
                <?php
                }
                ?>

                bindFormCycle();

            }
        });
    }

    function bindFormCycle(){
        $("#formCycle").unbind('submit').bind('submit', function() {
            var form = $(this);

            // remove the text-danger
            $(".text-danger").remove();

            $('.text-loading').text('Salvando Ciclo...');
            $('.saving').show();

            $.ajax({
                url: base_url+'<?php echo 'cycles/saveCycle'; ?>',
                type: form.attr('method'),
                data: form.serialize(), // /converting the form data into array and sending it to server
                dataType: 'json',
                success:function(response) {

                    $('.saving').hide();

                    if(response == '1') {

                        store_cycles_datatable.ajax.reload(null, false);

                        // hide the modal
                        $('#register-cycle').modal('hide');

                        // reset the form
                        $("#register-cycle .form-group").removeClass('has-error').removeClass('has-success');

                    } else {

                        $('.saving').hide();

                        $("#messages_cycle").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                            '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong> Não é possível cadastrar/editar o ciclo solicitado, por favor revise as datas de início de fim para que não haja conflito com outros ciclos</div>');
                    }

                }
            });

            return false;
        });
    }

    function removeCycle(cycleId){

        $.ajax({
            url: base_url+'cycles/removeCycle/'+cycleId,
            type: 'post',
            dataType: 'json',
            success:function(response) {

                store_cycles_datatable.ajax.reload(null, false);

                // hide the modal
                $('#register-cycle').modal('hide');

            }
        });

    }

    function checkUseExclusiveCycle() {

        if ($('#use_exclusive_cycle').is(':checked')){
            $('#use_exclusive_cycle').prop('checked', false);
        }else{
            $('#use_exclusive_cycle').prop('checked', true);
        }

    }

    function confirmAddCycle() {

        $('.row-cycles').show();

        mountStoreCyclesDataTable();

        $('#modal_confirm_add_cycle').modal('hide');

    }

    function cancelAddCycle() {

        $('#modal_confirm_add_cycle').modal('hide');

        $('#use_exclusive_cycle').prop('checked', false).change();

    }

    $('#use_exclusive_cycle').bind('change', function () {

        if ($(this).is(':checked')){

            $('#modal_confirm_add_cycle').modal();

        }else{
            if (typeof store_cycles_datatable !== 'undefined'){
                $('.row-cycles').hide();
                store_cycles_datatable.destroy();
            }
        }

    });

    function showCyclesExisting(){

        $('#modal-existing').show();
        $('#modal-cycle-form').hide();

        $('#modal-existing').find('.loading').show();

        $.ajax({
            url: base_url+'cycles/getModelCycles',
            type: 'post',
            dataType: 'json',
            success:function(response) {

                $('#modal-existing').find('.loading').hide();

                html = '';
                $.each(response, function(i,model){

                    json = JSON.stringify(model);

                    html+= "<tr>" +
                        "<td>"+model.data_inicio+"</td>" +
                        "<td>"+model.data_fim+"</td>" +
                        "<td>"+model.data_pagamento+"</td>" +
                        "<td style='width: 10%'><button type='button' class='btn btn-outline-primary' onclick='useThisCircle("+json+")' style='margin-top:5px;margin-left:5px;border-color: #0066CC; background-color: #ffffff;'> <i class='fas fa-plus-circle'></i> Usar este ciclo </button></td>" +
                        "</tr>";
                });

                if (!html){
                    html = "<tr><td colspan='4'>Você ainda não cadastrou nenhum modelo de ciclo</td></tr>";
                }

                $('#cycles_registered').find('tbody').html(html);
            }
        });

    }

    function useThisCircle(obj){

        closeModalModels();

        $('input[name="vDataInicio"]').val(obj.data_inicio);
        $('input[name="vDataFim"]').val(obj.data_fim);
        $('input[name="vDataPagamentoMkt"]').val(obj.data_pagamento);

    }

    function closeModalModels(){
        $('#modal-existing').hide();
        $('#modal-cycle-form').show();
    }

    $('form#formCycle').bind('change', function () {

        var form = $(this);

        if ( $('select[name="vMarketplace"]').val() && $('select[name="vDateCut"]').val() && $('input[name="vDataInicio"]').val() && $('input[name="vDataFim"]').val() && $('input[name="vDataPagamentoMkt"]').val()){

            $.ajax({
                url: base_url+'<?php echo 'cycles/saveCycle/0'; ?>',
                type: form.attr('method'),
                data: form.serialize(), // /converting the form data into array and sending it to server
                dataType: 'json',
                success:function(response) {

                    $('.saving').hide();

                    if(response !== '1') {

                        $("#messages_cycle").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                            '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong> Não é possível cadastrar/editar o ciclo com base nas informações informadas atualmente, por favor revise as datas de início de fim para que não haja conflito com outros ciclos</div>');
                    }

                }
            });

        }

    });

    $('#btn_add_cep_multi_channel_fulfillment').on('click', function(){
        const zipcode_start = $('#data-zipcode .zipcode_start');
        const zipcode_end = $('#data-zipcode .zipcode_end');

        if (zipcode_start.val().length !== 9 || zipcode_end.val().length !== 9) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção!',
                html:'Informe o cep corretamente.'
            });
            return false;
        }

        $('.zipcodes_multi_channel_fulfillment').append(`
        <div class="row">
            <div class="form-group col-md-3">
                <input type="text" class="form-control" required name="zipcode_start[]" value="${zipcode_start.val()}" readonly>
            </div>
            <div class="form-group col-md-3">
                <input type="text" class="form-control" required name="zipcode_end[]" value="${zipcode_end.val()}" readonly>
            </div>
            <div class="form-group col-md-3">
                <button type="button" class="btn btn-danger btn_remove_range_cep"><i class="fa fa-trash"></i></button>
            </div>
        </div>
        `);

        zipcode_start.val('');
        zipcode_end.val('');
    });

    $(document).on('click', '.btn_remove_range_cep', function(){
        $(this).closest('.row').remove();
    });

    $('#formExternalNfse').on('submit', function(e){
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        const input_month_year = $(this).find('#month_year_issue_invoice');
        const uri = $(this).attr('action');
        const reference = $('#month_year_issue_invoice').val();
        const store_id = $('#store_id_issue_invoice').val();
        $('#formExternalNfse .response tbody').empty();
        btn.prop('disabled', true);
        input_month_year.prop('disabled', true);
        $.ajax({
            url: uri + `/${store_id}/${reference}`,
            type: 'get',
            dataType: 'json',
            success:function(response) {
                if (!response.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Atenção!',
                        html: response.message
                    });
                    $('#formExternalNfse .response').hide();
                    return false;
                }

                $('#formExternalNfse .response').show();
                let links = '';
                $(response.message).each(function(k, v){
                    links = '';

                    if (v.xml) {
                        links += `<a class="btn btn-primary btn-sm mr-1" href="${v.xml}" download target="_blank" data-toggle="tooltip" title="XML"><i class="fa fa-file-code-o" aria-hidden="true"></i></a>`;
                    }
                    if (v.pdf) {
                        links += `<a class="btn btn-primary btn-sm mr-1" href="${v.pdf}" download target="_blank" data-toggle="tooltip" title="PDF"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>`;
                    }
                    if (v.zip) {
                        links += `<a class="btn btn-primary btn-sm mr-1" href="${v.zip}" download target="_blank" data-toggle="tooltip" title="ZIP"><i class="fa fa-file-archive-o" aria-hidden="true"></i></a>`;
                    }

                    $('#formExternalNfse .response tbody').append(`
                        <tr>
                            <td>${v.type}</td>
                            <td>${v.invoice_date}</td>
                            <td>${v.invoice_num}</td>
                            <td>${v.total_amount}</td>
                            <td>${links}</td>
                        </tr>
                    `);
                });
                $('#formExternalNfse .response tbody a[data-toggle="tooltip"]').tooltip();
            }
        }).always(() => {
            btn.prop('disabled', false);
            input_month_year.prop('disabled', false);
        });
    });

</script>