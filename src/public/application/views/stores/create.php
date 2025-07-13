<?php use App\Libraries\Enum\AntecipationTypeEnum;
use App\Libraries\FeatureFlag\FeatureManager;

include_once APPPATH . '/third_party/zipcode.php';?>
<!--
SW Serviços de Informática 2019

Criar Lojas

-->
<style>
    .select2-container--default .select2-selection--single {
        border-radius: 0px;
    }
</style>

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_add";
$this->load->view('templates/content_header', $data);?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif;?>
                <form role="form" action="<?=base_url('stores/create')?>" method="post" 
                    <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
                        enctype="multipart/form-data"
                    <?php endif;?>
                >
                    <div class="box box-primary">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_add_store');?></h3>
                        </div>
                        <div class="box-body">
                        <?php if (validation_errors()) {
                            foreach (explode("</p>", validation_errors()) as $erro) {
                                $erro = trim($erro);
                                if ($erro != "") {?>
                                    <div class="alert alert-error alert-dismissible" role="alert">
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <?php echo $erro . "</p>"; ?>
                                    </div>
                                <?php	}
                            }
                        }
                        $defenable = ($this->session->flashdata('company_id')) ? " disabled " : "";
                        ?>
                        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
                        <div class="row">
                            <div class="form-group col-md-3 <?php echo (form_error('pj_pf')) ? 'has-error' : '';  ?>">
                                <label for="pj_pf"><?=$this->lang->line('application_person_type')?></label>
                                <select class="form-control" id="pj_pf" required name="pj_pf">
                                    <option value="PJ" <?= set_select('pj_pf', 'PJ') ?>><?=$this->lang->line('application_person_pj')?></option>
                                    <option value="PF" <?= set_select('pj_pf', 'PF') ?>><?=$this->lang->line('application_person_pf')?></option>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('pj_pf').'</i>'; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label for="company"><?=$this->lang->line('application_company_name');?></label>
                                <select class="form-control" id="company_id" name="company_id" required >
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($empresas as $empresa) {
                                        $enable = $defenable;
                                        if ($empresa['id'] == $this->session->flashdata('company_id')) {
                                            $enable = "";
                                        }
                                    ?>
                                        <option <?php echo $enable; ?> value="<?php echo $empresa['id']; ?>" <?=set_select('company_id', $empresa['id'], $empresa['id'] == $this->session->flashdata('company_id'))?>><?php echo $empresa['name'] ?></option>
                                    <?php }?>
                                </select>
                            </div>
                            <div class="form-group col-md-5" <?php echo (form_error('name')) ? "has-error" : ""; ?>>
                                <label for="name"><?=$this->lang->line('application_name');?></label>
                                <input type="text" class="form-control" required id="name" name="name" value="<?php echo set_value('name'); ?>" placeholder="<?=$this->lang->line('application_enter_store_name');?>" autocomplete="off">
                                <?php echo '<i style="color:red">' . form_error('name') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="edit_active"><?=$this->lang->line('application_status');?></label>
                                <select class="form-control" id="edit_active" name="edit_active">
                                    <option value="1"><?=$this->lang->line('application_active');?></option>
                                    <option value="2"><?=$this->lang->line('application_inactive');?></option>
                                    <option value="3"><?=$this->lang->line('application_in_negociation');?></option>
                                    <option value="4"><?=$this->lang->line('application_billet');?></option>
                                    <option value="5"><?=$this->lang->line('application_churn');?></option>
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="flag_store_migration"><?=$this->lang->line('application_store_migration');?></label>
                                <select class="form-control" id="flag_store_migration" name="flag_store_migration">
                                    <option <?=set_select('flag_store_migration', 0)?> value="0"><?=$this->lang->line('application_no');?></option>
                                    <option <?=set_select('flag_store_migration', 1)?> value="1"><?=$this->lang->line('application_yes');?></option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4" id="hideButton">
                                <!-- label for="edit_same"><?=$this->lang->line('application_copycompany');?></label -->
                                <span class="btn btn-success" onclick="copyFunc('<?=$this->data['usercomp'];?>')" ><i class="fa fa-copy"></i>   <?=$this->lang->line('application_copycompany');?></span>
                            </div>
                            <?php if ($stores_multi_cd): ?>
                            <div class="form-group col-md-3 multi_cd">
                                <label><?=$this->lang->line('application_id_multi_cd');?></label>
                                <input type="text" class="form-control" value="" id="id_multi_cd" disabled>
                                <?php echo '<i style="color:red">' . form_error('raz_soc') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 multi_cd multi_cd_principal">
                                <label><?=$this->lang->line('application_maximum_time_to_invoice_order');?></label>
                                <div class="input-group">
                                    <input type="text" class="form-control maskperc" id="max_time_to_invoice_order" value="<?php echo set_value('max_time_to_invoice_order', '48');?>" name="max_time_to_invoice_order" autocomplete="off">
                                    <span class="input-group-addon text-uppercase"><strong><?=$this->lang->line('application_hours');?></strong></span>
                                </div>
                            </div>
                            <div class="form-group col-md-2 multi_cd multi_cd_principal">
                                <label for="inventory_utilization"><?=$this->lang->line('application_inventory_utilization');?></label>
                                <select class="form-control" id="inventory_utilization" name="inventory_utilization">
                                    <option value="all_stores" <?=set_select('inventory_utilization', 'all_stores')?> ><?=$this->lang->line('application_all_stores');?></option>
                                    <option value="main_store_only" <?=set_select('inventory_utilization', 'main_store_only')?> ><?=$this->lang->line('application_main_store_only');?></option>
                                    <option value="cd_store_only" <?=set_select('inventory_utilization', 'cd_store_only')?> ><?=$this->lang->line('application_cd_store_only');?></option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6 show_pj <?php echo (form_error('raz_soc')) ? "has-error" : ""; ?>">
                                <label for="raz_soc"><?=$this->lang->line('application_raz_soc');?></label>
                                <input type="text" class="form-control" required id="raz_soc" name="raz_soc" value="<?php echo set_value('raz_soc'); ?>" placeholder="<?=$this->lang->line('application_enter_razao_social');?>" autocomplete="off">
                                <?php echo '<i style="color:red">' . form_error('raz_soc') . '</i>'; ?>
                            </div>
                            <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
                            <div class="form-group col-md-3 show_pf <?php echo (form_error('CPF')) ? 'has-error' : '';  ?>">
                                <label for="CPF"><?=$this->lang->line('application_cpf')?>(*)</label>
                                <input type="text" class="form-control" maxlength="14" minlenght="14" id="CPF" name="CPF" placeholder="<?=$this->lang->line('application_enter_cpf') ?>" autocomplete="off" value = "<?= set_value('CPF') ?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" onblur='clearTimeout()'>
                                <?php echo '<i style="color:red">'.form_error('CPF').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 show_pf <?php echo (form_error('RG')) ? 'has-error' : '';  ?>">
                                <label for="RG"><?=$this->lang->line('application_rg')?></label>
                                <input type="text" class="form-control" id="RG" name="RG" placeholder="<?=$this->lang->line('application_enter_rg')?>" autocomplete="off" value = "<?= set_value('RG') ?>">
                                <?php echo '<i style="color:red">'.form_error('RG').'</i>'; ?>
                            </div>
                            <?php endif; ?>
                            <div class="form-group col-md-3 show_pj <?php echo (form_error('CNPJ')) ? "has-error" : ""; ?>">
                                <label for="CNPJ"><?=$this->lang->line('application_cnpj');?></label>
                                <input type="text" class="form-control" maxlength="18" minlenght="18" required id="CNPJ" name="CNPJ" value="<?php echo set_value('CNPJ'); ?>" placeholder="<?=$this->lang->line('application_enter_CNPJ');?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CNPJ',this,event);" value="<?=set_value('cnpj')?>">
                                <?php echo '<i style="color:red">' . form_error('CNPJ') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-3 show_pj <?php echo (form_error('inscricao_estadual')) ? "has-error" : ""; ?>">
                                <label for="inscricao_estadual"><?=$this->lang->line('application_iest');?></label>
                                <input type="text" <?=set_value('exempted') == 1 ? 'disabled' : ''?> class="form-control" id="inscricao_estadual" value="<?php echo set_value('inscricao_estadual'); ?>" name="inscricao_estadual" placeholder="<?=$this->lang->line('application_enter_incricao_estadual');?>" autocomplete="off" required>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" <?php echo set_checkbox('exempted', '1', false); ?>  name="exempted" onchange="exemptIE()" id="exempted">
                                    <label class="form-check-label" for="exempted">
                                        <?=$this->lang->line('application_exempted');?>
                                    </label>
                                </div>
                                <?php echo '<i style="color:red">' . form_error('inscricao_estadual') . '</i>'; ?>
                            </div>
                            <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
                            <div class="form-group col-md-2 <?php echo (form_error('inscricao_municipal')) ? "has-error" : ""; ?>">
                                <label for="inscricao_municipal"><?=$this->lang->line('application_imun');?></label>
                                <input type="text" <?=set_value('exempted_mun') == 1 ? 'disabled' : ''?> class="form-control" id="inscricao_municipal" value="<?php echo set_value('inscricao_municipal'); ?>" name="inscricao_municipal" placeholder="<?=$this->lang->line('application_enter_incricao_municipal');?>" autocomplete="off" required>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" <?php echo set_checkbox('exempted_mun', '1', false); ?>  name="exempted_mun" onchange="exemptMun()" id="exempted_mun">
                                    <label class="form-check-label" for="exempted_mun">
                                        <?=$this->lang->line('application_exempted');?>
                                    </label>
                                </div>
                                <?php echo '<i style="color:red">' . form_error('inscricao_municipal') . '</i>'; ?>
                            </div>
                            <?php endif;?>
                        </div>
                        <div class="row">

                            <div class="form-group col-md-3" >
                                <label for="responsible_cs">CS Responsável</label>
                                <select class="form-control" name="responsible_cs" id="responsible_cs">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($CSs as $CS) {?>
                                        <option value="<?=$CS['id']?>" <?=set_select('responsible_cs', $CS['id'])?>><?=$CS['firstname'] . ' ' . $CS['lastname']?></option>
                                    <?php }?>
                                </select>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('phone_1')) ? "has-error" : ""; ?>">
                                <label for="phone"><?=$this->lang->line('application_phone');?>1</label>
                                <input type="text" class="form-control" required id="phone_1" name="phone_1" value="<?php echo set_value('phone_1'); ?>" placeholder="<?=$this->lang->line('application_enter_phone');?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" maxlength="15">
                                <?php echo '<i style="color:red">' . form_error('phone_1') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('phone_2')) ? "has-error" : ""; ?>">
                                <label for="phone"><?=$this->lang->line('application_phone');?>2</label>
                                <input type="text" class="form-control" id="phone_2" name="phone_2" value="<?php echo set_value('phone_2'); ?>" placeholder="<?=$this->lang->line('application_enter_phone');?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" maxlength="15">
                                <?php echo '<i style="color:red">' . form_error('phone_2') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-3 <?php echo (form_error('service_charge_value')) ? "has-error" : ""; ?>">
                                <label for="service_charge_value"><?=$this->lang->line('application_charge_amount');?></label>
                                <div class="input-group">
                                    <input type="text" class="form-control maskperc" required id="service_charge_value" value="<?php echo set_value('service_charge_value'); ?>" name="service_charge_value" placeholder="<?=$this->lang->line('application_charge_amount');?>" autocomplete="off" maxlength="5"  >
                                    <span class="input-group-addon"><strong>%</strong></span>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="service_charge_freight_option" id="service_charge_freight_option" value="1" <?php echo set_checkbox('service_charge_freight_option', '1', true); ?>  onclick="comissaoFrete()" />
                                    <label for="service_charge_freight_option"><?=$this->lang->line('application_commission_freight_same_products');?></label>
                                </div>
                                <?php echo '<i style="color:red">' . form_error('service_charge_value') . '</i>'; ?>
                            </div>
                            <div style="display:none" class="form-group col-md-3 comissaoFrete <?php echo (form_error('service_charge_freight_value')) ? "has-error" : ""; ?>">
                                <label for="service_charge_freight_value"><?=$this->lang->line('application_charge_amount_freight');?></label>
                                <div class="input-group">
                                    <input type="text" class="form-control maskperc" required id="service_charge_freight_value" value="<?php echo set_value('service_charge_freight_value'); ?>" name="service_charge_freight_value" placeholder="<?=$this->lang->line('application_charge_amount_freight');?>" autocomplete="off" maxlength="5"  >
                                    <span class="input-group-addon"><strong>%</strong></span>
                                </div>
                                <?php echo '<i style="color:red">' . form_error('service_charge_freight_value') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="onboarding"><?=$this->lang->line('application_onboarding');?></label>
                                <input type="date" class="form-control" id="onboarding" name="onboarding" value="<?php echo set_value('onboarding') ?>">
                            </div>
                            <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
                            <div class="form-group col-md-4">
                                <label for="website_url"><?=$this->lang->line('application_website');?></label>
                                <input type="text" class="form-control" id="website_url" name="website_url" value="<?php echo set_value('website_url'); ?>" placeholder="<?=$this->lang->line('application_entre_website_url');?>">
                            </div>
                            <?php endif;?>
                        </div>
                        <?php if ($stores_multi_cd): ?>
                    </div>
                </div>
                <div class="box box-primary multi_cd multi_cd_no_principal">
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
                        <div class="zipcodes_multi_channel_fulfillment"></div>
                    </div>
                </div>
                <div class="box box-primary">
                    <div class="box-body">
                        <?php endif; ?>
                        <div class="row">
                            <div class="form-group col-md-4 col-xs-12 <?php echo (form_error('catalogs[]')) ? "has-error" : ""; ?>">
                                <label for="catalogs" class="normal"><?=$this->lang->line('application_catalogs');?>(*)</label>
                                <select class="form-control selectpicker show-tick" id="catalogs" name ="catalogs[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 2" title="<?=$this->lang->line('application_select');?>">
                                    <?php foreach ($catalogs as $catalog) {?>
                                        <option value="<?=$catalog['id']?>" ><?=$catalog['name']?></option>
                                    <?php }?>
                                </select>
                                <?php echo '<i style="color:red">' . form_error('catalogs[]') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-3 show_pj <?php echo (form_error('associate_type_pj')) ? 'has-error' : ''; ?>">
                                <label for="associate_type_pj"><?=$this->lang->line('application_associate_type')?>(*)</label>
                                <?php ?>
                                <select class="form-control" id="associate_type_pj" name="associate_type_pj">
                                    <option value="0" <?=set_select('associate_type_pj', 0)?>><?=$this->lang->line('application_parent_company')?></option>
                                    <option value="1" <?=set_select('associate_type_pj', 1)?>><?=$this->lang->line('application_agency')?></option>
                                    <option value="2" <?=set_select('associate_type_pj', 2)?>><?=$this->lang->line('application_partner')?></option>
                                    <option value="3" <?=set_select('associate_type_pj', 3)?>><?=$this->lang->line('application_affiliate')?></option>
                                    <option value="4" <?=set_select('associate_type_pj', 4)?>><?=$this->lang->line('application_autonomous')?></option>
                                    <option value="5" <?=set_select('associate_type_pj', 5)?>><?=$this->lang->line('application_indicator')?></option>
                                	<?php if ($franchise_on_store == 1) { // utilizado pelo gruposoma  ?>
                                	<option value="6" <?=set_select('associate_type_pj', 6)?>><?=$this->lang->line('application_franchise')?></option>
                                	<?php }?>
                                	<?php if ($big_sellers_on_store == 1) { // utilizado pela Vertem  ?>
                                	<option value="7" <?=set_select('associate_type_pj', 7)?>><?=$this->lang->line('application_big_store')?></option>
                                	<?php }?>
                                </select>
                                <?php echo '<i style="color:red">' . form_error('associate_type_pj') . '</i>'; ?>
                            </div>

                            <div class="form-group col-md-3 <?php echo (form_error('erp_customer_supplier_code')) ? "has-error" : ""; ?>">
                                <label for="erp_customer_supplier_code"><?=$this->lang->line('application_store_erp_customer_supplier_code');?> <?= $required_clifor == "1" ? "(*)" : "" ?></label>
                                <input type="tel" class="form-control" id="erp_customer_supplier_code" name="erp_customer_supplier_code" placeholder="<?=$this->lang->line('application_store_erp_customer_supplier_code');?>" value="<?php echo set_value('erp_customer_supplier_code') ?>" autocomplete="off" maxlength="255" <?= $required_clifor == "1" ? "required=\"true\"" : "" ?>>
                                <?php echo '<i style="color:red">' . form_error('erp_customer_supplier_code') . '</i>'; ?>
                            </div>

                            <div class="form-group col-md-2 mt-5">
                                <div class="form-check">
                                    <input type="checkbox" name="invoice_cnpj" id="invoice_cnpj" value="1"  />
                                    <label for="invoice_cnpj"><?=$this->lang->line('application_cnpj_fatured');?></label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4" >
                                <label for="seller"><?=$this->lang->line('application_seller');?></label>
                                <select class="form-control" name="seller" id="seller" <?=$usercomp != 1 ? 'disabled' : ''?> >
                                    <option value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($CSs as $CS) {?>
                                        <option value="<?=$CS['id']?>" <?=set_select('seller', $CS['id'])?>><?=$CS['firstname'] . ' ' . $CS['lastname'] . ' (' . $CS['email'] . ')'?></option>
                                    <?php }?>
                                </select>
                            </div>
                            <div class="form-group col-md-3 <?=form_error('additional_operational_deadline') ? "has-error" : ""?>">
                                <label for="additional_operational_deadline"><?=$this->lang->line('application_store_additional_operational_deadline');?></label>
                                <input type="number" class="form-control" id="additional_operational_deadline" name="additional_operational_deadline" placeholder="<?=$this->lang->line('application_store_additional_operational_deadline')?>" value="<?=set_value('additional_operational_deadline', 0)?>">
                                <?='<i style="color:red">'.form_error('additional_operational_deadline').'</i>'?>
                            </div>

                            <?php if ($use_buybox){ ?>
                                <div class="form-group col-md-2 mt-5">
                                    <div class="form-check">
                                        <input type="checkbox" name="buybox" id="buybox" value="1"  />
                                        <label for="buybox"><?=$this->lang->line('application_buy_box');?></label>
                                    </div>
                                </div>
                            <?php } ?>

                            <?php if ($use_ativacaoAutomaticaProdutos){ ?>
                                <div class="form-group col-md-3 mt-5">
                                    <div class="form-check">
                                        <input type="checkbox" name="ativacaoAutomaticaProdutos" id="ativacaoAutomaticaProdutos" value="1"  />
                                        <label for="ativacaoAutomaticaProdutos"><?=$this->lang->line('application_automate_products');?></label>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <!-- endereço de coleta -->
                        <fieldset>
                            <legend><h4><?=$this->lang->line('application_collection_address');?></h4></legend>
                            <div class="row">
                                <div class="form-group col-md-2 <?php echo (form_error('zipcode')) ? "has-error" : ""; ?>">
                                    <label for="zipcode"><?=$this->lang->line('application_zip_code');?></label>
                                    <input type="text" class="form-control" required id="zipcode" name="zipcode" value="<?php echo set_value('zipcode'); ?>" placeholder="<?=$this->lang->line('application_enter_zipcode');?>" autocomplete="off" onkeyup="this.value=this.value.replace(/[^\d]/,'')">
                                    <?php echo '<i style="color:red">' . form_error('zipcode') . '</i>'; ?>
                                </div>
                                <div class="form-group col-md-8 <?php echo (form_error('address')) ? "has-error" : ""; ?>">
                                    <label for="address"><?=$this->lang->line('application_address');?></label>
                                    <input type="text" class="form-control" required id="address" name="address" value="<?php echo set_value('address'); ?>" placeholder="<?=$this->lang->line('application_enter_address');?>" autocomplete="off">
                                    <?php echo '<i style="color:red">' . form_error('address') . '</i>'; ?>
                                </div>
                                <div class="form-group col-md-2 <?php echo (form_error('addr_num')) ? "has-error" : ""; ?>">
                                    <label for="addr_num"><?=$this->lang->line('application_number');?></label>
                                    <input type="text" class="form-control" required id="addr_num" name="addr_num" value="<?php echo set_value('addr_num'); ?>" placeholder="<?=$this->lang->line('application_enter_number');?>" autocomplete="off">
                                    <?php echo '<i style="color:red">' . form_error('addr_num') . '</i>'; ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-2 <?php echo (form_error('addr_compl')) ? "has-error" : ""; ?>">
                                    <label for="addr_compl"><?=$this->lang->line('application_complement');?></label>
                                    <input type="text" class="form-control" id="addr_compl" name="addr_compl" value="<?php echo set_value('addr_compl'); ?>" placeholder="<?=$this->lang->line('application_enter_complement');?>" autocomplete="off">
                                    <?php echo '<i style="color:red">' . form_error('addr_compl') . '</i>'; ?>
                                </div>
                                <div class="form-group col-md-3 <?php echo (form_error('addr_neigh')) ? "has-error" : ""; ?>">
                                    <label for="addr_neigh"><?=$this->lang->line('application_neighb');?></label>
                                    <input type="text" class="form-control" required id="addr_neigh" name="addr_neigh" value="<?php echo set_value('addr_neigh'); ?>" placeholder="<?=$this->lang->line('application_enter_neighborhood');?>" autocomplete="off">
                                    <?php echo '<i style="color:red">' . form_error('addr_neigh') . '</i>'; ?>
                                </div>
                                <div class="form-group col-md-3 <?php echo (form_error('addr_city')) ? "has-error" : ""; ?>">
                                    <label for="addr_city"><?=$this->lang->line('application_city');?></label>
                                    <input type="text" class="form-control" required id="addr_city" name="addr_city" value="<?php echo set_value('addr_city'); ?>" placeholder="<?=$this->lang->line('application_enter_city');?>" autocomplete="off">
                                    <?php echo '<i style="color:red">' . form_error('addr_city') . '</i>'; ?>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="addr_uf"><?=$this->lang->line('application_uf');?></label>
                                    <select class="form-control" id="addr_uf" name="addr_uf">
                                        <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                        <?php foreach ($ufs as $k => $v): ?>
                                            <option value="<?php echo trim($k); ?>" <?=set_select('addr_uf', trim($k))?>><?php echo $v ?></option>
                                        <?php endforeach?>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="country"><?=$this->lang->line('application_country');?></label>
                                    <select class="form-control" id="country" name="country">
                                        <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                        <?php foreach ($paises as $k => $v): ?>
                                            <option value="<?php echo trim($k); ?>"><?php echo $v ?></option>
                                        <?php endforeach?>
                                    </select>
                                </div>
                            </div>
                        </fieldset>
                        <!-- endereço comercial -->
                        <fieldset>
                            <legend><h4><?=$this->lang->line('application_business_address');?></h4></legend>
                            <div class="row">
                                <div class="col-md-12">
                                    <input class="form-check-input" type="checkbox" value="1" <?php echo set_checkbox('same', '1', false); ?>  name="same" onchange="sameAddress()" id="same">
                                    <label for="same"><?=$this->lang->line('application_identical_to_collection_address');?></label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-2 <?php echo (form_error('business_code')) ? "has-error" : ""; ?>">
                                    <label for="business_code"><?=$this->lang->line('application_zip_code');?></label>
                                    <input type="text" class="form-control" required id="business_code" name="business_code" value="<?php echo set_value('business_code'); ?>" placeholder="<?=$this->lang->line('application_enter_zipcode');?>" autocomplete="off" onkeyup="this.value=this.value.replace(/[^\d]/,'')" onblur="consultZip(this.value)">
                                    <?php echo '<i style="color:red">' . form_error('business_code') . '</i>'; ?>
                                </div>
                                <div class="form-group col-md-8 <?php echo (form_error('business_street')) ? "has-error" : ""; ?>">
                                    <label for="business_street"><?=$this->lang->line('application_address');?></label>
                                    <input type="text" class="form-control" required id="business_street" name="business_street" value="<?php echo set_value('business_street'); ?>" placeholder="<?=$this->lang->line('application_enter_address');?>" autocomplete="off">
                                    <?php echo '<i style="color:red">' . form_error('business_street') . '</i>'; ?>
                                </div>
                                <div class="form-group col-md-2 <?php echo (form_error('business_addr_num')) ? "has-error" : ""; ?>">
                                    <label for="business_addr_num"><?=$this->lang->line('application_number');?></label>
                                    <input type="text" class="form-control" required id="business_addr_num" name="business_addr_num" value="<?php echo set_value('business_addr_num'); ?>" placeholder="<?=$this->lang->line('application_enter_number');?>" autocomplete="off">
                                    <?php echo '<i style="color:red">' . form_error('business_addr_num') . '</i>'; ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-2 <?php echo (form_error('business_addr_compl')) ? "has-error" : ""; ?>">
                                    <label for="business_addr_compl"><?=$this->lang->line('application_complement');?></label>
                                    <input type="text" class="form-control" id="business_addr_compl" name="business_addr_compl" value="<?php echo set_value('business_addr_compl'); ?>" placeholder="<?=$this->lang->line('application_enter_complement');?>" autocomplete="off">
                                    <?php echo '<i style="color:red">' . form_error('business_addr_compl') . '</i>'; ?>
                                </div>
                                <div class="form-group col-md-3 <?php echo (form_error('business_neighborhood')) ? "has-error" : ""; ?>">
                                    <label for="business_neighborhood"><?=$this->lang->line('application_neighb');?></label>
                                    <input type="text" class="form-control" required id="business_neighborhood" name="business_neighborhood" value="<?php echo set_value('business_neighborhood'); ?>" placeholder="<?=$this->lang->line('application_enter_neighborhood');?>" autocomplete="off">
                                    <?php echo '<i style="color:red">' . form_error('business_neighborhood') . '</i>'; ?>
                                </div>
                                <div class="form-group col-md-3 <?php echo (form_error('business_town')) ? "has-error" : ""; ?>">
                                    <label for="business_town"><?=$this->lang->line('application_city');?></label>
                                    <input type="text" class="form-control" required id="business_town" name="business_town" value="<?php echo set_value('business_town'); ?>" placeholder="<?=$this->lang->line('application_enter_city');?>" autocomplete="off">
                                    <?php echo '<i style="color:red">' . form_error('business_town') . '</i>'; ?>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="business_uf"><?=$this->lang->line('application_uf');?></label>
                                    <select class="form-control" id="business_uf" name="business_uf">
                                        <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                        <?php foreach ($ufs as $k => $v): ?>
                                            <option value="<?php echo trim($k); ?>" <?=set_select('business_uf', trim($k))?>><?php echo $v ?></option>
                                        <?php endforeach?>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="business_nation"><?=$this->lang->line('application_country');?></label>
                                    <select class="form-control" id="business_nation" name="business_nation">
                                        <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                        <?php foreach ($paises as $k => $v): ?>
                                            <option value="<?php echo trim($k); ?>" <?=set_select('business_nation', trim($k))?>><?php echo $v ?></option>
                                        <?php endforeach?>
                                    </select>
                                </div>
                            </div>
                        </fieldset>
                        <!-- até aqui -->
                        <div class="row">
                            <hr>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4 <?php echo (form_error('responsible_name')) ? "has-error" : ""; ?>">
                                <label for="responsible_name"><?=$this->lang->line('application_responsible_name');?></label>
                                <input type="text" class="form-control" id="responsible_name" name="responsible_name" required placeholder="<?=$this->lang->line('application_enter_responsible_name')?>" autocomplete="off" value="<?=set_value('responsible_name')?>">
                                <?php echo '<i style="color:red">' . form_error('responsible_name') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-4 <?php echo (form_error('responsible_email')) ? "has-error" : ""; ?>">
                                <label for="responsible_email"><?=$this->lang->line('application_responsible_email');?></label>
                                <input type="email" class="form-control" id="responsible_email" name="responsible_email" required placeholder="<?=$this->lang->line('application_enter_responsible_email')?>" autocomplete="off" value="<?=set_value('responsible_email')?>">
                                <?php echo '<i style="color:red">' . form_error('responsible_email') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-4 <?php echo (form_error('responsible_cpf')) ? "has-error" : ""; ?>">
                                <label for="responsible_cpf"><?=$this->lang->line('application_responsible_cpf');?><?php echo ($store_cpf_optional==1) ? '' : '*';?></label>
                                <input type="text" class="form-control" <?php echo ($store_cpf_optional == 1) ? '' : 'required'; ?> id="responsible_cpf" name="responsible_cpf" placeholder="<?=$this->lang->line('application_enter_responsible_cpf')?>" autocomplete="off" maxlength="14" size="14" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" value="<?=set_value('responsible_cpf')?>">
                                <?php echo '<i style="color:red">' . form_error('responsible_cpf') . '</i>'; ?>
                            </div>

                            <div class="form-group col-md-4 <?php echo (form_error('responsible_mother_name')) ? "has-error" : ""; ?>">
                                <label for="responsible_mother_name"><?=$this->lang->line('application_responsible_mother_name');?></label>
                                <input type="text" class="form-control" id="responsible_mother_name" name="responsible_mother_name" required placeholder="<?=$this->lang->line('application_responsible_mother_name')?>" autocomplete="off" value="<?=set_value('responsible_mother_name')?>">
                                <?php echo '<i style="color:red">' . form_error('responsible_mother_name') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-4 <?php echo (form_error('responsible_position')) ? "has-error" : ""; ?>">
                                <label for="responsible_position"><?=$this->lang->line('application_responsible_position');?></label>
                                <input type="text" class="form-control" id="responsible_position" name="responsible_position" required placeholder="<?=$this->lang->line('application_responsible_position')?>" autocomplete="off" value="<?=set_value('responsible_position')?>">
                                <?php echo '<i style="color:red">' . form_error('responsible_position') . '</i>'; ?>
                            </div>

                            <div class="form-group col-md-4 <?php echo (form_error('responsible_monthly_income')) ? "has-error" : ""; ?>">
                                <label for="responsible_monthly_income">Renda Mensal Aproximada</label>
                                <input type="number" class="form-control" id="responsible_monthly_income" name="responsible_monthly_income" required placeholder="Renda Mensal Aproximada" autocomplete="off" value="<?=set_value('responsible_monthly_income')?>">
                                <?php echo '<i style="color:red">' . form_error('responsible_monthly_income') . '</i>'; ?>
                            </div>

                            <div class="form-group col-md-4 <?php echo (form_error('responsible_monthly_income')) ? "has-error" : ""; ?>">
                                <label for="company_annual_revenue">Faturalmente Anual da empresa</label>
                                <input type="number" class="form-control" id="company_annual_revenue" name="company_annual_revenue" required placeholder="Faturalmente Anual da empresa" autocomplete="off" value="<?=set_value('company_annual_revenue')?>">
                                <?php echo '<i style="color:red">' . form_error('company_annual_revenue') . '</i>'; ?>
                            </div>
                            
                        </div>
                        <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
                         <div class="row">
                            <hr>
                        </div>
                        <h4><?=$this->lang->line('application_contacts');?></h4>
                        <div class="row">
                            <div class="form-group col-md-4 <?php echo (form_error('responsible_sac_name')) ? 'has-error' : '';  ?>">
                                <label for="responsible_sac_name"><?=$this->lang->line('application_responsible_sac_name');?></label>
                                <input type="text" class="form-control" id="responsible_sac_name" name="responsible_sac_name" autocomplete="off" value="<?=set_value('responsible_sac_name')?>">
                                <?php echo '<i style="color:red">'.form_error('responsible_sac_name').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-4 <?php echo (form_error('responsible_sac_email')) ? 'has-error' : '';  ?>">
                                <label for="responsible_sac_email"><?=$this->lang->line('application_responsible_sac_email');?></label>
                                <input type="email" class="form-control" id="responsible_sac_email" name="responsible_sac_email" autocomplete="off" value="<?=set_value('responsible_sac_email')?>">
                                <?php echo '<i style="color:red">'.form_error('responsible_sac_email').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-4 <?php echo (form_error('responsible_sac_tell')) ? 'has-error' : '';  ?>">
                                <label for="responsible_sac_tell"><?=$this->lang->line('application_responsible_sac_tell');?></label>
                                <input type="text" class="form-control" id="responsible_sac_tell" name="responsible_sac_tell" autocomplete="off" value="<?=set_value('responsible_sac_tell')?>" onkeypress="return digitos(event, this);" onkeydown="Mascara('TEL',this,event);" maxlength="15" placeholder="Digite o telefone">
                                <?php echo '<i style="color:red">'.form_error('responsible_sac_tell').'</i>';  ?>
                            </div>
                        </div>
                        <div class="row">
                            <hr>
                        </div>
                        <?php endif;?>
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label for="bank"><?=$this->lang->line('application_bank');?></label>
                                <select class="form-control" id="bank" name="bank" <?php echo ($bank_is_optional == 1) ? '' : 'required' ?>>
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($banks as $k => $v): ?>
                                        <option value="<?=trim($v['name'])?>" <?=set_select('bank', $v['name'])?>><?=$v['name']?></option>
                                    <?php endforeach?>
                                </select>
                            </div>
                            <div class="form-group col-md-3 <?php echo (form_error('agency')) ? "has-error" : ""; ?>">
                                <label for="agency"><?=$this->lang->line('application_agency');?></label>
                                <input type="text" class="form-control" id="agency" name="agency" <?php echo ($bank_is_optional == 1) ? '' : 'required' ?> placeholder="<?=$this->lang->line('application_enter_agency')?>" autocomplete="off" value="<?=set_value('agency')?>">
                                <?php echo '<i style="color:red">' . form_error('agency') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="currency"><?=$this->lang->line('application_type_account');?></label>
                                <select class="form-control" id="account_type" name="account_type" <?php echo ($bank_is_optional == 1) ? '' : 'required' ?>>
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($type_accounts as $k => $v): ?>
                                        <option value="<?=trim($v)?>" <?=set_select('account_type', $v)?>><?=$v?></option>
                                    <?php endforeach?>
                                </select>
                            </div>
                            <div class="form-group col-md-3 <?php echo (form_error('account')) ? "has-error" : ""; ?>">
                                <label for="account"><?=$this->lang->line('application_account');?></label>
                                <input type="text" class="form-control" id="account" name="account" <?php echo ($bank_is_optional == 1) ? '' : 'required' ?> placeholder="<?=$this->lang->line('application_enter_bank_account')?>" autocomplete="off" value="<?=set_value('account')?>">
                                <?php echo '<i style="color:red">' . form_error('account') . '</i>'; ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-3 <?php echo (form_error('addr_neigh')) ? "has-error" : ""; ?>">
                                <label for="responsable_birth_date"><?=$this->lang->line('application_birth_date');?><?=$birth_date_requered===true?'*':''?></label>
                                <input type="date" class="form-control" id="responsable_birth_date" name="responsable_birth_date" value="<?php echo set_value('responsable_birth_date'); ?>" placeholder="<?=$this->lang->line('application_enter_birth_date');?>" <?=$birth_date_requered===true?'required':''?>>
                                <?php echo '<i style="color:red">' . form_error('responsable_birth_date') . '</i>'; ?>
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
                                    <select class="form-control" id="flag_antecipacao_repasse" name="flag_antecipacao_repasse" disabled>
                                        <option value="N">Não</option>
                                        <option value="S">Sim</option>
                                    </select>
                                    <?php echo '<i style="color:red">' . form_error('flag_antecipacao_repasse') . '</i>'; ?>
                                </div>
                            <?php } ?>

                            <?php 
				
                            if ($allow_payment_reconciliation_installments){
				    
                            ?>
                                <div class="form-group col-md-3">
                                    <label for="allow_payment_reconciliation_installments"><?=$this->lang->line('conciliation_payment_installment');?></label>
                                    <select class="form-control" id="allow_payment_reconciliation_installments" name="allow_payment_reconciliation_installments" >
                                        <option value="0" <?=!$default_payment_reconciliation_installments_enabled? 'selected="selected"' : '';?>>Não</option>
                                        <option value="1" <?=$default_payment_reconciliation_installments_enabled ? 'selected="selected"' : '';?>>Sim</option>
                                    </select>
                                    <?php echo '<i style="color:red">' . form_error('allow_payment_reconciliation_installments') . '</i>'; ?>
                                </div>
                            <?php
                            }
                            ?>
                        </div>
                        <?php if (in_array('doIntegration', $user_permission)): ?>
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
                                    <label>Usuário Indicador</label>
                                    <select class="form-control select2" name="id_indicator">
                                        <option value="0"><?=$this->lang->line('application_select')?></option>
                                        <?php if (count($users_indicator)): ?>
                                            <optgroup label="Usuário">
                                                <?php foreach ($users_indicator as $user_indicator): ?>
                                                    <option value="u-<?=$user_indicator['id']?>"><?=$user_indicator['email']?></option>
                                                <?php endforeach?>
                                            </optgroup>
                                        <?php endif?>
                                        <?php if (count($stores_indicator)): ?>
                                            <optgroup label="Loja">
                                                <?php foreach ($stores_indicator as $store_indicator): ?>
                                                    <option value="s-<?=$store_indicator['id']?>"><?=$store_indicator['name']?></option>
                                                <?php endforeach?>
                                            </optgroup>
                                        <?php endif?>
                                        <?php if (count($companies_indicator)): ?>
                                            <optgroup label="Empresa">
                                                <?php foreach ($companies_indicator as $company_indicator): ?>
                                                    <option value="c-<?=$company_indicator['id']?>"><?=$company_indicator['name']?></option>
                                                <?php endforeach?>
                                            </optgroup>
                                        <?php endif?>
                                    </select>
                                </div>
                                <div class="form-group col-md-2 col-xs-12">
                                    <label>Percentual de Comissão</label>
                                    <input type="number" class="form-control" name="percentage_indication" placeholder="% Comissão" value="<?=set_value('url_callback_integracao')?>">
                                </div>
                                <div class="form-group col-md-2 col-xs-12">
                                    <label><?=$this->lang->line('application_origin_seller')?></label>
                                    <select class="form-control select2" name="utm_source">
                                        <option value=""><?=$this->lang->line('application_select')?></option>
                                        <?php foreach($get_attribute_value_utm_param as $utm_param) { ?>
                                            <option value="<?=$utm_param['value'] ?>"><?=$utm_param['value'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                
                            </div>
                        <?php endif?>
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label for="type_view_tag">Tipo de Etiquetas</label>
                                <select name="type_view_tag" id="type_view_tag" class="form-control">
                                    <option <?= set_select('type_view_tag', 'all') ?> value="all">Correios, Transportadora e/ou Gateway Logístico</option>
                                    <option <?= set_select('type_view_tag', 'correios') ?> value="correios">Correios</option>
                                    <option <?= set_select('type_view_tag', 'shipping_company_gateway') ?> value="shipping_company_gateway">Transportadora e/ou Gateway Logístico</option>
                                </select>
                            </div>
                        </div>
                        <?php if (in_array('baton_pass', $user_permission)): ?>
                            <h4><?=$this->lang->line('application_baton_pass');?></h4>
                            <div class="row">
                                <div class="form-group col-md-4<?php echo (form_error('what_integration')) ? "has-error" : ""; ?>">
                                    <label for="what_integration"><?=$this->lang->line('application_what_integration');?></label>
                                    <input type="text" class="form-control" id="what_integration" name="what_integration" placeholder="<?=$this->lang->line('application_what_integration')?>" autocomplete="off" value="<?=set_value('what_integration')?>"/>
                                    <?php echo '<i style="color:red">' . form_error('what_integration') . '</i>'; ?>
                                </div>
                                <div class="form-group col-md-4<?php echo (form_error('billing_expectation')) ? "has-error" : ""; ?>">
                                    <label for="billing_expectation"><?=$this->lang->line('application_billing_expectation');?></label>
                                    <input type="text" class="form-control" id="billing_expectation" name="billing_expectation" placeholder="<?=$this->lang->line('application_billing_expectation')?>" autocomplete="off" value="<?=set_value('billing_expectation')?>"/>
                                    <?php echo '<i style="color:red">' . form_error('billing_expectation') . '</i>'; ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4<?php echo (form_error('operation_store')) ? "has-error" : ""; ?>">
                                    <label for="operation_store"><?=$this->lang->line('application_operation_store');?></label>
                                    <textarea  rows="4" type="text" class="form-control" id="operation_store" name="operation_store" placeholder="<?=$this->lang->line('application_operation_store')?>" autocomplete="off"><?=set_value('operation_store')?></textarea>
                                    <?php echo '<i style="color:red">' . form_error('operation_store') . '</i>'; ?>
                                </div>
                                <div class="form-group col-md-4<?php echo (form_error('mix_of_product')) ? "has-error" : ""; ?>">
                                    <label for="mix_of_product"><?=$this->lang->line('application_mix_of_product');?></label>
                                    <textarea rows="3" type="text" class="form-control" id="mix_of_product" name="mix_of_product" placeholder="<?=$this->lang->line('application_mix_of_product')?>" autocomplete="off"><?=set_value('mix_of_product')?></textarea>
                                    <?php echo '<i style="color:red">' . form_error('mix_of_product') . '</i>'; ?>
                                </div>
                                <div class="form-group col-md-4<?php echo (form_error('how_up_and_fature')) ? "has-error" : ""; ?>" id='how_up_and_fature_div'>
                                    <label for="how_up_and_fature"><?=$this->lang->line('application_how_up_and_fature');?></label>
                                    <textarea rows="4" type="text" class="form-control" id="how_up_and_fature" name="how_up_and_fature" placeholder="<?=$this->lang->line('application_how_up_and_fature')?>" autocomplete="off"><?=set_value('how_up_and_fature')?></textarea>
                                    <?php echo '<i style="color:red">' . form_error('how_up_and_fature') . '</i>'; ?>
                                </div>
                            </div>
                        <?php endif;?>

                        <?php
                        if ($allow_automatic_antecipation){
                            ?>
                            <h4><?=$this->lang->line('rav_antecipation');?></h4>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label class="checkbox-inline">
                                        <input onchange="return changeAutomaticAntecipation(this);" id="use_automatic_antecipation" name="use_automatic_antecipation" type="checkbox" <?php echo set_checkbox('use_automatic_antecipation', '1', false); ?> value="1">
                                        Permitir Utilizar Antecipação Automática
                                    </label>
                                </div>
                            </div>
                            <div class="row configs-automatic-antecipation" style="display: none;">
                                <div class="form-group col-md-3 <?php echo (form_error('antecipation_type')) ? "has-error" : "";?>">
                                    <label for="antecipation_type"><?=$this->lang->line('antecipation_type');?></label>
                                    <select class="form-control" id="antecipation_type" name="antecipation_type">
                                        <?php foreach (AntecipationTypeEnum::generateList() as $k => $v): ?>
                                            <option value="<?=trim($k)?>" <?=set_select('antecipation_type', trim($v), (trim($k) == $antecipacao_dx_default) || (!$antecipacao_dx_default && trim($v) == AntecipationTypeEnum::FULL ) )?>><?=$v ?></option>
                                        <?php endforeach ?>
                                    </select>
                                    <?php echo '<i style="color:red">'.form_error('antecipation_type').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-3 <?php echo (form_error('percentage_amount_to_be_antecipated')) ? "has-error" : "";?>" >
                                    <label for="percentage_amount_to_be_antecipated"><?=$this->lang->line('percentage_amount_to_be_antecipated');?></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control maskperc" id="percentage_amount_to_be_antecipated" value="<?php echo set_value('percentage_amount_to_be_antecipated',$porcentagem_antecipacao_default);?>" name="percentage_amount_to_be_antecipated" autocomplete="off" maxlength="5"  >
                                        <span class="input-group-addon"><strong>%</strong></span>
                                    </div>
                                    <?php echo '<i style="color:red">'.form_error('percentage_amount_to_be_antecipated').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-3 number_days_advance <?php echo (form_error('number_days_advance')) ? "has-error" : "";?>" >
                                    <label for="number_days_advance"><?=$this->lang->line('number_days_advance');?></label>
                                    <input type="number" step="1" class="form-control" id="number_days_advance" value="<?php echo set_value('number_days_advance',$numero_dias_dx_default);?>" name="number_days_advance" autocomplete="off" maxlength="2">
                                    <?php echo '<i style="color:red">'.form_error('number_days_advance').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-3 <?php echo (form_error('automatic_anticipation_days')) ? "has-error" : "";?>" >
                                    <label for="automatic_anticipation_days"><?=$this->lang->line('automatic_anticipation_days');?></label>
                                    <input placeholder="<?=lang('placeholder_automatic_anticipation_days');?>" type="text" class="form-control" id="automatic_anticipation_days" value="<?php echo set_value('automatic_anticipation_days',$automatic_anticipation_days_default);?>" name="automatic_anticipation_days" autocomplete="off">
                                    <?php echo '<i style="color:red">'.form_error('automatic_anticipation_days').'</i>';  ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>

                        <div class="row"></div>



                        <?php if (in_array('createUserFreteRapido', $user_permission)): ?>

                            <!---- remoção do Frete Rápido
                    <div class="row">
                        <div class="form-group col-md-6 <?php echo (form_error('fr_email_contato')) ? "has-error" : ""; ?>">
                          	<label for="fr_email_contato"><?=$this->lang->line('application_fr_contact_email');?></label>
                          	<input type="email" class="form-control" id="fr_email_contato" name="fr_email_contato" required value="<?php echo set_value('fr_email_contato'); ?>" placeholder="<?=$this->lang->line('application_enter_contact_email');?>" autocomplete="off">
                          	<?php echo '<i style="color:red">' . form_error('fr_email_contato') . '</i>'; ?>
                        </div>
                        <div class="form-group col-md-6 <?php echo (form_error('fr_email_nfe')) ? "has-error" : ""; ?>">
                          	<label for="fr_email_nfe"><?=$this->lang->line('application_fr_nfe_email');?></label>
                          	<input type="email" class="form-control" id="fr_email_nfe" name="fr_email_nfe" required value="<?php echo set_value('fr_email_nfe'); ?>" placeholder="<?=$this->lang->line('application_enter_nfe_email');?>" autocomplete="off">
						  	<?php echo '<i style="color:red">' . form_error('fr_email_nfe') . '</i>'; ?>
                        </div>
                    </div>
                    --->

                            <div class="row">
                                <div class="form-group col-md-6 <?php echo (form_error('tipos_volumes[]')) ? "has-error" : ""; ?>">
                                    <label for="addr_uf"><?=$this->lang->line('application_categories_frete_rapido');?></label>
                                    <select class="form-control selectpicker show-tick" data-live-search="true" data-actions-box="true" id="tipos_volumes" name="tipos_volumes[]" multiple="multiple" title="<?=$this->lang->line('application_select');?>" multiple data-selected-text-format="count > 5">
                                        <!--  <option  disabled value=""><?=$this->lang->line('application_select');?></option> -->
                                        <?php foreach ($tipos_volumes as $tipo_volume): ?>
                                            <option value="<?php echo $tipo_volume['id']; ?>" <?=set_select('tipos_volumes', $tipo_volume['id'])?> data-subtext="<?php echo "(" . $tipo_volume['codigo'] . ")" ?>" ><?php echo $tipo_volume['produto'] ?></option>
                                        <?php endforeach?>
                                    </select>
                                    <?php echo '<i style="color:red">' . form_error('tipos_volumes[]') . '</i>'; ?>
                                </div>
                            </div>
                        <?php endif;?>

                        <div style="<?php echo ($create_seller_vtex == 1) ? '' : 'display:none;'; ?>" >
                            <div class="row">
                                <div class="form-group col-md-12" >
                                    <h4><?=$this->lang->line('application_sellercenter');?></h4>
                                </div>
                            </div>
                            <div class="form-group col-md-12 <?php echo (form_error('description')) ? 'has-error' : ''; ?>">
                                <label for="description"><?=$this->lang->line('application_store_description');?></label>
                                <textarea type="text" class="form-control" id="description" name="description" placeholder="<?=$this->lang->line('application_store_description');?>"><?=set_value('description')?></textarea>
                                <?php echo '<i style="color:red">' . form_error('description') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-12 <?php echo (form_error('exchange_return_policy')) ? 'has-error' : ''; ?>">
                                <label for="exchange_return_policy"><?=$this->lang->line('application_store_exchange_return_policy');?></label>
                                <textarea type="text" class="form-control" id="exchange_return_policy" name="exchange_return_policy" placeholder="<?=$this->lang->line('application_store_exchange_return_policy');?>"><?=set_value('exchange_return_policy')?></textarea>
                                <?php echo '<i style="color:red">' . form_error('exchange_return_policy') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-12 <?php echo (form_error('delivery_policy')) ? 'has-error' : ''; ?>">
                                <label for="delivery_policy"><?=$this->lang->line('application_store_delivery_policy');?></label>
                                <textarea type="text" class="form-control" id="delivery_policy" name="delivery_policy" placeholder="<?=$this->lang->line('application_store_delivery_policy');?>"><?=set_value('delivery_policy')?></textarea>
                                <?php echo '<i style="color:red">' . form_error('delivery_policy') . '</i>'; ?>
                            </div>
                            <div class="form-group col-md-12 <?php echo (form_error('security_privacy_policy')) ? 'has-error' : ''; ?>">
                                <label for="security_privacy_policy"><?=$this->lang->line('application_store_security_privacy_policy');?></label>
                                <textarea type="text" class="form-control" id="security_privacy_policy" name="security_privacy_policy" placeholder="<?=$this->lang->line('application_store_security_privacy_policy');?>"><?=set_value('security_privacy_policy')?></textarea>
                                <?php echo '<i style="color:red">' . form_error('security_privacy_policy') . '</i>'; ?>
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
                                        <button onClick="toggleLogoMsc(event)" class="btn btn-primary" style="display: block;margin: 10px 0px;"><i class="fa fa-exchange-alt"></i> <?=$this->lang->line('application_include_logo') ?></button>
                                    </label>
                                    <span class="logo-lg"><img src="" width="200" height="200"></span>
                            </div>

                            <div class="row"></div>
                            <div class="form-group col-md-3">
                                <label for="aggregate_select">Aggregate Merchant</label>
                                <select class="form-control" id="aggregate_select" name="aggregate_select" style="width: 100%" >
                                </select>
                                <input type="hidden" name="aggregate_select_name" id="aggregate_select_name_hidden">
                            </div>
                            <div class="row"></div>
                            <div class="form-group col-md-6 col-xs-12">
                                    <label for="sales_channel" class="normal"><?=$this->lang->line('application_rules_buy_channel');?>(*)</label>
                                    <select class="form-control selectpicker show-tick" id="msc_sales_channel" name ="msc_sales_channel[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 2" title="<?=$this->lang->line('application_select');?>">
                                        <?php foreach ($available_sc as $sc) { ?>
                                            <option value="<?= $sc['id'] ?>"><?= $sc['mosaico_value'] ?></option>
                                        <?php } ?>
                                    </select>
                                    <?php echo '<i style="color:red">'.form_error('msc_sales_channel[]').'</i>'; ?>
                            </div>
                        </div>
                        <?php endif;?>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                    <a href="<?php echo base_url('stores/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                </div>
                </div>
                <input type="hidden" name="type_store" value="0">
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>

<script src="<?= base_url('assets/tracking/js/order_tracking.js') ?>"></script>
<style>
    .multi_cd {
        display: none;
    }
</style>

<script type="text/javascript">
    var empresas = <?php echo json_encode($empresas); ?>;
    var banks = <?php echo json_encode($banks); ?>;
    var usar_mascara_banco = "<?php echo $usar_mascara_banco ?>";

    $('#company_id').on('change', function(){
        $('.btn_remove_range_cep').trigger('click');
        $('#max_time_to_invoice_order').val('48');
        $('.multi_cd').hide();
        $('#id_multi_cd').val('...');
        $('.multi_cd.multi_cd_principal input').attr('required', false);
        $('[name="type_store"]').val(0);
        $('#service_charge_value, #service_charge_freight_value').val('').attr('readonly', false);
        $('#service_charge_freight_option').prop('checked', true).attr('disabled', false);
        $(".comissaoFrete").hide();

        if ($(this).val() == null) {
            return false;
        }

        $.ajax({
            url: "<?=base_url('Stores/getDataCompanyToMultiCdStore/')?>" + $(this).val(),
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.data_company.multi_channel_fulfillment == 1) {

                    const company_id = parseInt(window.location.href.split('/').pop());

                    if (!isNaN(company_id)) {
                        loadMultiCd(response);
                    } else {
                        Swal.fire({
                            title: 'A empresa está configurada para usar Multi CD. <br>Essa loja usará Multi CD?',
                            html: '',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#00a65a',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Usar Multi CD',
                            cancelButtonText: 'Não Usar Multi CD'
                        }).then((result) => {
                            if (result.value) {
                                loadMultiCd(response);
                            }
                        });
                    }
                }
            }
        });
    });

    const loadMultiCd = response => {
        $('.multi_cd').show();

        if (response.count_stores) {
            $('[name="type_store"]').val(2);
            $('.multi_cd.multi_cd_no_principal').show();
            $('.multi_cd.multi_cd_principal').hide();
            $('.multi_cd.multi_cd_principal input').attr('required', false);
        } else {
            $('[name="type_store"]').val(1);
            $('.multi_cd.multi_cd_principal').show();
            $('.multi_cd.multi_cd_no_principal').hide();
            $('.multi_cd.multi_cd_principal input').attr('required', true);
            $('#service_charge_value, #service_charge_freight_value').val(100).attr('readonly', true);
            $('#service_charge_freight_option').prop('checked', false).attr('disabled', true);
            $(".comissaoFrete").show();
        }

        const language = response.count_stores ? 'application_additional_cd' : 'application_principal_store';

        $.get( "<?=base_url('Api/Language/')?>" + language, data => {
            $('#id_multi_cd').val(data.text);
        });
    }

    <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
    function toggleLogoMsc(e) {
        e.preventDefault();
        $("#logochange_msc").toggle();
        $("#logopreview_msc").toggle();
    }
    <?php endif;?>

    $(document).ready(function() {
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
        $("#storeNav").addClass('active');
        $("select[name*='country']").val('BR').attr('selected', true).trigger('change');
        // $("select[name*='country']").trigger('change');

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

            $('#aggregate_select').on('select2:select', function (e) {
                var selectedText = e.params.data.text;
                $('#aggregate_select_name_hidden').val(selectedText);
            });

            $('#aggregate_select').data('select2').on('results:message', function (e) {
                this.dropdown._positionDropdown();
            })

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
        <?php endif;?>
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
        if(usar_mascara_banco == true){
            var bank_name = $('#bank option:selected').val();
            applyBankMask(bank_name);
        }
        $("#bank").change(function () {
            $('#agency').val('');
            $('#account').val('');
            var bank_name = $('#bank option:selected').val();
            if(usar_mascara_banco == true){
                applyBankMask(bank_name);
            }
        });
        if ($('#exempted')[0].hasAttribute('checked')) {
            $('#inscricao_estadual').attr('disabled', 'disabled')
        } else {
            $('#inscricao_estadual').removeAttr('disabled')
        }

        <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
        if ($('#exempted_mun')[0].hasAttribute('checked')) {
            $('#inscricao_municipal').attr('disabled', 'disabled')
        } else {
            $('#inscricao_municipal').removeAttr('disabled')
        }
        <?php endif;?>
        if ($('#same')[0].hasAttribute('checked')) {
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
            fields.forEach((item) => {
                $('#'+item.copy)[0].value = $('#'+item.original).val()
                $('#'+item.copy).attr('disabled', 'disabled')
            })
        }

        ownLogistic();
        comissaoFrete();

        $('.maskperc').inputmask({
            alias: 'numeric',
            allowMinus: false,
            digits: 2,
            max: 100.00
        });

		$("#CNPJ").on('focusout',function(){
			var val = $(this).val();
		    $(this).val(val.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/g,"\$1.\$2.\$3\/\$4\-\$5"));
		});

		$("#responsible_cpf").on('focusout',function(){
			var val = $(this).val();
		    $(this).val(val.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/g,"\$1.\$2.\$3\-\$4"));
		});

        changeAntecipationType();

        $('#company_id').trigger('change');

        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
        $("#pj_pf").trigger('change');
        <?php endif; ?>
    });

    <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
    $("#pj_pf").change(function () {
        const is_pj = $(this).val() === "PJ";

        $(".show_pf").css({'display': is_pj ? 'none': 'block'});
        $(".show_pj").css({'display': !is_pj ? 'none': 'block'});

        $('#raz_soc, #CNPJ, #inscricao_estadual').prop('required', is_pj);
        $('#exempted').prop('checked', false).trigger('change');
        $('#inscricao_estadual').prop('disabled', false);
        $('#CPF').prop('required', !is_pj);
    });
    <?php endif; ?>

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
        id =  document.getElementById('company_id').value;
        if (id == '') {
            return false;
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
                $("#zipcode").val(response.zipcode);
                $("#country").val(response.country);
                $("#phone_1").val(response.phone_1);
                $("#phone_2").val(response.phone_2);
                $("#responsible_name").val(response.gestor);
                $("#responsible_email").val(response.email);

                <?php if (FeatureManager::isFeatureAvailable("oep-1897-mosaico-integration")): ?>
                $("#responsible_sac_name").val(response.responsible_sac_name);
                $("#responsible_sac_email").val(response.responsible_sac_email);
                $("#responsible_sac_tell").val(response.responsible_sac_tell);
                <?php endif;?>
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
        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
        if (!ie && $('#pj_pf').val() === 'PJ') {
            $('#inscricao_estadual').attr('disabled', 'disabled')
        } else {
            $('#inscricao_estadual').removeAttr('disabled')
        }
        <?php elseif (!\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
        if (!ie) {
            $('#inscricao_estadual').attr('disabled', 'disabled')
        } else {
            $('#inscricao_estadual').removeAttr('disabled')
        }
        <?php endif; ?>
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
    <?php endif;?>

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
                $('#'+item.copy)[0].value = $('#'+item.original).val()
                $('#'+item.copy).attr('disabled', 'disabled')
            })
            $('#same').attr('checked', 'checked')
        } else {
            fields.forEach((item) => {
                $('#'+item.copy)[0].value = ''
                $('#'+item.copy).removeAttr('disabled')
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
        var OPtion = document.getElementById("freight_seller");
        if (OPtion !== null && OPtion.checked == true) {
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

        const typeSeller = $('#freight_seller_type').val() ?? 0;
        var OPtion = document.getElementById("service_charge_freight_option");
        if (OPtion.checked == false) {
            if (typeSeller == 1)
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

        if (id === 1) {
            $('#freight_seller_code').val('').attr("required", false).closest('div').hide();
            $('#freight_seller_end_point').closest('div').addClass('col-md-9').removeClass('col-md-6');
            $('#freight_seller_end_point').attr({"required":true, "type": "url", "disabled": false});
            $.get( "<?=base_url('Api/Language/application_own_logistic_endpoint')?>", data => {
                $('label[for="freight_seller_end_point"]').text(data.text);
                $('#freight_seller_end_point').attr('placeholder', data.text);
            });
        }
        else if (id === 2) {
            $('#freight_seller_code').val('').attr("required", false).closest('div').hide();
            $('#freight_seller_end_point').closest('div').addClass('col-md-9').removeClass('col-md-6');
            $('#freight_seller_end_point').val('').attr({"required": false, "type": "url", "disabled": true});
            $.get( "<?=base_url('Api/Language/application_own_logistic_endpoint')?>", data => {
                $('label[for="freight_seller_end_point"]').text(data.text);
                $('#freight_seller_end_point').attr('placeholder', data.text);
            });
        }
        else if (id === 3) {
            $('#freight_seller_code').attr("required", true).closest('div').show();
            $('#freight_seller_end_point').closest('div').addClass('col-md-6').removeClass('col-md-9');
            $('#freight_seller_end_point').attr({"required": true, "type": "text", "disabled": false});
            $.get( "<?=base_url('Api/Language/application_intelipost_token')?>", data => {
                $('label[for="freight_seller_end_point"]').text(data.text);
                $('#freight_seller_end_point').attr('placeholder', data.text);
            });
        }
        else if (id === 4) {
            $('#freight_seller_code').val('').attr("required", false).closest('div').hide();
            $('#freight_seller_end_point').closest('div').addClass('col-md-9').removeClass('col-md-6');
            $('#freight_seller_end_point').val('').attr({"required": false, "type": "text", "disabled": true});
            $.get( "<?=base_url('Api/Language/application_intelipost_token')?>", data => {
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
                .attr({"required": false, "type": "text", "disabled": true})
                .closest('div')
                .addClass('col-md-9')
                .removeClass('col-md-6');
            $('label[for="freight_seller_end_point"]').text('Token');
            $('#freight_seller_end_point').attr('placeholder', 'Token');
        }
    });

    $('#freight_seller').change(function(){
        const status = $(this).is(':checked');

        if (status) $('#freight_seller_end_point').trigger('change');
        else $('#freight_seller_end_point').attr('required', false);
    });
    $('#flag_store_migration').change(function(){
        const flag_status = parseInt($(this).val());
        if(flag_status == 1){
            Swal.fire({
            icon: 'info',
            title: 'AVISO!',
            html:'<?=$this->lang->line('application_warning')?>: <b> <?=$this->lang->line('application_store_migration_selected')?>.</b><?=$this->lang->line('application_store_migration_message')?>'
        })
        }
    });

    $('form').on('submit', function(event) {
        is_migration = $('#flag_store_migration').val();
        if(is_migration == 1){
            event.preventDefault();
            Swal.fire({
                icon: 'question',
                title: 'AVISO!',
                html:'<?=$this->lang->line('application_store_migration_alert')?>: <b>'
            })
            Swal.fire({
            title: '<?=$this->lang->line('application_really_delete')?>',
            text: '<?=$this->lang->line('application_store_migration_alert')?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '<?=$this->lang->line('application_yes')?>'
            }).then((result) => {
            if (!result.isConfirmed) {
                event.target.submit();
            }
            })
        }
  
    });
    function changeAutomaticAntecipation(obj)
    {
        if (obj.checked)
        {
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

</script>
