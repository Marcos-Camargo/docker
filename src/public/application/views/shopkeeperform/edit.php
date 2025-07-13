<?php include_once(APPPATH . '/third_party/zipcode.php'); ?>

<!--
SW Serviços de Informática 2019

Listar Settings
Add , Edit & Delete

-->
<style>
    .select2-container--default .select2-selection--single {
        border-radius: 0px;
    }
</style>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content --
    <section class="content">
     Small boxes (Stat box) -->
    <div class="row">
        <div class="col-md-12 col-xs-12">

            <div id="messages"></div>

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
        </div>
    </div>
    <div class="box">
        <form action="<?php base_url('ShopkeeperForm/createFormValue') ?>" method="post" enctype="multipart/form-data" id="form-shopkeeper">
            <div class="box-body">
                <div class="panel panel-primary">

                    <div class="panel-heading">&nbsp
                        <span class="h6"> Formulario </span>
                    </div>
                    <div class="panel-body">
                        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
                        <div class="row">
                            <div class="form-group col-md-3 <?php echo (form_error('pj_pf')) ? 'has-error' : '';  ?>">
                                <label for="pj_pf"><?=$this->lang->line('application_person_type')?></label>
                                <select class="form-control" id="pj_pf" required name="pj_pf">
                                    <option value="pj" <?= set_select('pj_pf', 'pj', $shopkeeperform['pj_pf'] === 'pj') ?>><?=$this->lang->line('application_person_pj')?></option>
                                    <option value="pf" <?= set_select('pj_pf', 'pf', $shopkeeperform['pj_pf'] === 'pf') ?>><?=$this->lang->line('application_person_pf')?></option>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('pj_pf').'</i>'; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="form-group col-md-6 show_pj <?php echo (form_error('raz_soc')) ? "has-error" : "";?>">
                                <label for="raz_soc"><?=$this->lang->line('application_raz_soc');?></label>
                                <input type="text" class="form-control" required id="raz_soc" name="raz_soc" value="<?php echo set_value('raz_soc', $shopkeeperform["raz_social"]);?>" placeholder="<?=$this->lang->line('application_enter_razao_social');?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('raz_soc').'</i>';  ?>
                            </div>
                            <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
                            <div class="form-group col-md-3 show_pf <?php echo (form_error('CPF')) ? 'has-error' : '';  ?>">
                                <label for="CPF"><?=$this->lang->line('application_cpf')?></label>
                                <input type="text" class="form-control" maxlength="14" minlenght="14" id="CPF" name="CPF" placeholder="<?=$this->lang->line('application_enter_cpf') ?>" autocomplete="off" value = "<?= set_value('CPF',  $shopkeeperform["CNPJ"]) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" onblur='clearTimeout()'>
                                <?php echo '<i style="color:red">'.form_error('CPF').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 show_pf <?php echo (form_error('RG')) ? 'has-error' : '';  ?>">
                                <label for="RG"><?=$this->lang->line('application_rg')?></label>
                                <input type="text" class="form-control" id="RG" name="RG" placeholder="<?=$this->lang->line('application_enter_rg')?>" autocomplete="off" value = "<?= set_value('RG', $shopkeeperform["insc_estadual"]) ?>">
                                <?php echo '<i style="color:red">'.form_error('RG').'</i>'; ?>
                            </div>
                            <?php endif; ?>
                            <div class="form-group col-md-3 show_pj <?php echo (form_error('CNPJ')) ? "has-error" : "";?>">
                                <label for="CNPJ"><?=$this->lang->line('application_cnpj');?></label>
                                <input type="text" class="form-control" maxlength="18" minlenght="18" required id="CNPJ" name="CNPJ" value="<?php echo set_value('CNPJ',  $shopkeeperform["CNPJ"]);?>" placeholder="<?=$this->lang->line('application_enter_CNPJ');?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CNPJ',this,event);" value="<?=set_value('cnpj')?>">
                                <?php echo '<i style="color:red">'.form_error('CNPJ').'</i>';  ?>
                            </div>

                            <div class="form-group col-md-3 show_pj">
                                <label for="insc_estadual"><?=$this->lang->line('application_iest');?></label>
                                <input type="text" class="form-control" id="insc_estadual" <?php echo $shopkeeperform['insc_estadual'] == "0" ? 'disabled' : '' ?> name="insc_estadual" placeholder="<?=$this->lang->line('application_iest')?>" autocomplete="off" value="<?php echo set_value('insc_estadual',  $shopkeeperform["insc_estadual"]);?>">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" name="exempted" onchange="exemptIE()" <?php echo set_checkbox('exempted', '1', $shopkeeperform["insc_estadual"] == "0"); ?> id="exempted">
                                    <label class="form-check-label" for="exempted">
                                        <?= $this->lang->line('application_exempted'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-7" <?php echo (form_error('name')) ? "has-error" : "";?>">
                            <label for="name"><?=$this->lang->line('application_fantasy_name');?> </label>
                            <input type="text" class="form-control" required id="name" name="name" value="<?php echo set_value('name', $shopkeeperform["name"]);?>" placeholder="<?=$this->lang->line('application_enter_store_name');?>" autocomplete="off">
                            <?php echo '<i style="color:red">'.form_error('name').'</i>';  ?>
                        </div>
                        <div class="form-group col-md-2 <?php echo (form_error('phone_1')) ? "has-error" : "";?>">
                            <label for="phone"><?=$this->lang->line('application_phone');?>1 </label>
                            <input type="text" class="form-control" required id="phone_1" name="phone_1" value="<?php echo set_value('phone_1', $shopkeeperform["phone_1"]);?>" placeholder="<?=$this->lang->line('application_enter_phone');?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" maxlength="15">
                            <?php echo '<i style="color:red">'.form_error('phone_1').'</i>';  ?>
                        </div>
                        <div class="form-group col-md-2 <?php echo (form_error('phone_2')) ? "has-error" : "";?>">
                            <label for="phone"><?=$this->lang->line('application_phone');?>2 </label>
                            <input type="text" class="form-control" id="phone_2" name="phone_2" value="<?php echo set_value('phone_2', $shopkeeperform["phone_2"] );?>" placeholder="<?=$this->lang->line('application_enter_phone');?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" maxlength="15">
                            <?php echo '<i style="color:red">'.form_error('phone_2').'</i>';  ?>
                        </div>
                    </div>
                    <div class="row">
                        <hr>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4 <?php echo (form_error('responsible_name')) ? "has-error" : "";?>">
                            <label for="responsible_name"><?=$this->lang->line('application_responsible_name');?> </label>
                            <input type="text" class="form-control" id="responsible_name" name="responsible_name" required placeholder="<?=$this->lang->line('application_enter_responsible_name')?>" autocomplete="off" value="<?=set_value('responsible_name', $shopkeeperform["responsible_name"])?>">
                            <?php echo '<i style="color:red">'.form_error('responsible_name').'</i>';  ?>
                        </div>
                        <div class="form-group col-md-4 <?php echo (form_error('responsible_email')) ? "has-error" : ""; ?>">
                            <label for="responsible_email"><?=$this->lang->line('application_responsible_email');?> </label>
                            <input type="email" class="form-control" id="responsible_email" name="responsible_email" required placeholder="<?=$this->lang->line('application_enter_responsible_email')?>" autocomplete="off" value="<?=set_value('responsible_email', $shopkeeperform["responsible_email"])?>">
                            <?php  echo '<i style="color:red">'.form_error('responsible_email').'</i>'; ?>
                        </div>
                        <div class="form-group col-md-4 <?php echo (form_error('responsible_cpf')) ? "has-error" : "";?>">
                            <label for="responsible_cpf"><?=$this->lang->line('application_responsible_cpf');?> </label>
                            <input type="text" class="form-control" id="responsible_cpf" name="responsible_cpf" placeholder="<?=$this->lang->line('application_enter_responsible_cpf')?>" autocomplete="off" maxlength="14" size="14" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" value="<?=set_value('responsible_cpf', $shopkeeperform["responsible_cpf"])?>">
                            <?php echo '<i style="color:red">'.form_error('responsible_cpf').'</i>'; ?>
                        </div>

                        <div class="form-group col-md-4 <?php echo (form_error('responsible_mother_name')) ? "has-error" : ""; ?>">
                            <label for="responsible_mother_name"><?=$this->lang->line('application_responsible_mother_name');?> </label>
                            <input type="text" class="form-control" id="responsible_mother_name" name="responsible_mother_name" required placeholder="<?=$this->lang->line('application_responsible_mother_name')?>" autocomplete="off" value="<?=set_value('responsible_mother_name', $shopkeeperform["responsible_mother_name"])?>">
                            <?php  echo '<i style="color:red">'.form_error('responsible_mother_name').'</i>'; ?>
                        </div>
                        <div class="form-group col-md-4 <?php echo (form_error('responsible_position')) ? "has-error" : ""; ?>">
                            <label for="responsible_position"><?=$this->lang->line('application_responsible_position');?> </label>
                            <input type="text" class="form-control" id="responsible_position" name="responsible_position" required placeholder="<?=$this->lang->line('application_responsible_position')?>" autocomplete="off" value="<?=set_value('responsible_position', $shopkeeperform["responsible_position"])?>">
                            <?php  echo '<i style="color:red">'.form_error('responsible_position').'</i>'; ?>
                        </div>




                    </div>
                    <div class="row">
                        <div class="form-group col-md-3">
                            <label for="bank"><?=$this->lang->line('application_bank');?></label>
                            <select class="form-control" id="bank" name="bank" required >
                                <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                <?php foreach ($banks as $k => $v): ?>
                                    <option value="<?=trim($v['name'])?>" <?=set_select('bank', $v['name'], $shopkeeperform['bank'] == trim($v['name'])) ?>><?=$v['name'] ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3 <?php echo (form_error('agency')) ? "has-error" : "";?>">
                            <label for="agency"><?=$this->lang->line('application_agency');?> </label>
                            <input type="text" class="form-control" id="agency" name="agency" required placeholder="<?=$this->lang->line('application_enter_agency')?>" autocomplete="off" value="<?=set_value('agency', $shopkeeperform["agency"])?>">
                            <?php echo '<i style="color:red">'.form_error('agency').'</i>'; ?>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="currency"><?=$this->lang->line('application_type_account');?> </label>
                            <select class="form-control" id="account_type" name="account_type" required >
                                <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                <?php foreach ($type_accounts as $k => $v): ?>
                                    <option value="<?=trim($v)?>" <?=set_select('account_type', trim($v), $shopkeeperform['account_type'] == trim($v))?>><?=$v ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3 <?php echo (form_error('account')) ? "has-error" : "";?>">
                            <label for="account"><?=$this->lang->line('application_account');?> </label>
                            <input type="text" class="form-control" id="account" name="account" required placeholder="<?=$this->lang->line('application_enter_bank_account')?>" autocomplete="off" value="<?=set_value('account', $shopkeeperform["account"])?>">
                            <?php echo '<i style="color:red">'.form_error('account').'</i>'; ?>
                        </div>
                    </div>
                    <fieldset>
                        <legend><h4><?=$this->lang->line('application_collection_address');?></h4></legend>
                        <div class="row">
                            <div class="form-group col-md-2 <?php echo (form_error('zipcode')) ? "has-error" : "";?>">
                                <label for="zipcode"><?=$this->lang->line('application_zip_code');?> </label>
                                <input type="text" class="form-control" required id="zipcode" name="zipcode" value="<?php echo set_value('zipcode', $shopkeeperform["zipcode"]);?>" placeholder="<?=$this->lang->line('application_enter_zipcode');?>" autocomplete="off" onkeyup="this.value=this.value.replace(/[^\d]/,'')">
                                <?php echo '<i style="color:red">'.form_error('zipcode').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-8 <?php echo (form_error('address')) ? "has-error" : "";?>">
                                <label for="address"><?=$this->lang->line('application_address');?> </label>
                                <input type="text" class="form-control" required id="address" name="address" value="<?php echo set_value('address', $shopkeeperform["address"]);?>" placeholder="<?=$this->lang->line('application_enter_address');?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('address').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('addr_num')) ? "has-error" : "";?>">
                                <label for="addr_num"><?=$this->lang->line('application_number');?> </label>
                                <input type="text" class="form-control" required id="addr_num" name="addr_num" value="<?php echo set_value('addr_num', $shopkeeperform["addr_num"]);?>" placeholder="<?=$this->lang->line('application_enter_number');?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('addr_num').'</i>';  ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-2 <?php echo (form_error('addr_compl')) ? "has-error" : "";?>">
                                <label for="addr_compl"><?=$this->lang->line('application_complement');?> </label>
                                <input type="text" class="form-control" id="addr_compl" name="addr_compl" value="<?php echo set_value('addr_compl', $shopkeeperform["addr_compl"] );?>" placeholder="<?=$this->lang->line('application_enter_complement');?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('addr_compl').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-3 <?php echo (form_error('addr_neigh')) ? "has-error" : "";?>">
                                <label for="addr_neigh"><?=$this->lang->line('application_neighb');?> </label>
                                <input type="text" class="form-control" required id="addr_neigh" name="addr_neigh" value="<?php echo set_value('addr_neigh', $shopkeeperform["addr_neigh"] );?>" placeholder="<?=$this->lang->line('application_enter_neighborhood');?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('addr_neigh').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-3 <?php echo (form_error('addr_city')) ? "has-error " : "";?>">
                                <label for="addr_city"><?=$this->lang->line('application_city');?> </label>
                                <input type="text" class="form-control" required id="addr_city" name="addr_city" value="<?php echo set_value('addr_city', $shopkeeperform["addr_city"]);?>" placeholder="<?=$this->lang->line('application_enter_city');?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('addr_city').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="addr_uf"><?=$this->lang->line('application_uf');?> </label>
                                <select class="form-control" id="addr_uf" name="addr_uf">
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($ufs as $k => $v): ?>
                                        <option value="<?php echo trim($k) ?>" <?php echo set_select('addr_uf', trim($k),trim($k) == $shopkeeperform['addr_uf']) ?> ><?php echo $v ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="country"><?=$this->lang->line('application_country');?> </label>
                                <select class="form-control" id="country" name="country">
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($paises as $k => $v): ?>
                                        <option value="<?php echo trim($k); ?>" <?php echo set_select('country', trim($k),$k == $shopkeeperform['country'])?>><?php echo $v ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                    </fieldset>
                    <!-- endereço comercial -->
                    <fieldset>
                        <legend><h4><?=$this->lang->line('application_business_address');?></h4></legend>
                        <div class="row">
                            <div class="col-md-12">
                                <input class="form-check-input" type="checkbox" value="1" <?php echo set_checkbox('same', '1',false); ?>  name="same" onchange="sameAddress()" id="same">
                                <label for="same"><?=$this->lang->line('application_identical_to_collection_address');?></label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-2 <?php echo (form_error('business_code')) ? "has-error" : "";?>">
                                <label for="business_code"><?=$this->lang->line('application_zip_code');?></label>
                                <input type="text" class="form-control" required id="business_code" name="business_code" value="<?php echo set_value('business_code', $shopkeeperform["business_code"]);?>" placeholder="<?=$this->lang->line('application_enter_zipcode');?>" autocomplete="off" onkeyup="this.value=this.value.replace(/[^\d]/,'')" onblur="consultZip(this.value)">
                                <?php echo '<i style="color:red">'.form_error('business_code').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-8 <?php echo (form_error('business_street')) ? "has-error" : "";?>">
                                <label for="business_street"><?=$this->lang->line('application_address');?></label>
                                <input type="text" class="form-control" required id="business_street" name="business_street" value="<?php echo set_value('business_street', $shopkeeperform["business_street"]);?>" placeholder="<?=$this->lang->line('application_enter_address');?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('business_street').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('business_addr_num')) ? "has-error" : "";?>">
                                <label for="business_addr_num"><?=$this->lang->line('application_number');?></label>
                                <input type="text" class="form-control" required id="business_addr_num" name="business_addr_num" value="<?php echo set_value('business_addr_num', $shopkeeperform["business_addr_num"]);?>" placeholder="<?=$this->lang->line('application_enter_number');?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('business_addr_num').'</i>';  ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-2 <?php echo (form_error('business_addr_compl')) ? "has-error" : "";?>">
                                <label for="business_addr_compl"><?=$this->lang->line('application_complement');?></label>
                                <input type="text" class="form-control" id="business_addr_compl" name="business_addr_compl" value="<?php echo set_value('business_addr_compl', $shopkeeperform["business_addr_compl"] );?>" placeholder="<?=$this->lang->line('application_enter_complement');?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('business_addr_compl').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-3 <?php echo (form_error('business_neighborhood')) ? "has-error" : "";?>">
                                <label for="business_neighborhood"><?=$this->lang->line('application_neighb');?></label>
                                <input type="text" class="form-control" required id="business_neighborhood" name="business_neighborhood" value="<?php echo set_value('business_neighborhood', $shopkeeperform["business_neighborhood"] );?>" placeholder="<?=$this->lang->line('application_enter_neighborhood');?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('business_neighborhood').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-3 <?php echo (form_error('business_town')) ? "has-error" : "";?>">
                                <label for="business_town"><?=$this->lang->line('application_city');?></label>
                                <input type="text" class="form-control" required id="business_town" name="business_town" value="<?php echo set_value('business_town', $shopkeeperform["business_town"]);?>" placeholder="<?=$this->lang->line('application_enter_city');?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('business_town').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="business_uf"><?=$this->lang->line('application_uf');?></label>
                                <select class="form-control" id="business_uf" name="business_uf">
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($ufs as $k => $v): ?>
                                        <option value="<?php echo trim($k) ?>" <?php echo set_select('business_uf', trim($k),trim($k) == $shopkeeperform['business_uf']) ?> ><?php echo $v ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="business_nation"><?=$this->lang->line('application_country');?></label>
                                <select class="form-control" id="business_nation" name="business_nation">
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($paises as $k => $v): ?>
                                        <option value="<?php echo trim($k); ?>" <?php echo set_select('business_nation', trim($k),$k == $shopkeeperform['business_nation'])?>><?php echo $v ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                    </fieldset>
                    <div class="row">
                        <hr>
                    </div>
                    <!-- Informações Adicionais -->
                    <fieldset>
                        <legend><h4><?=$this->lang->line('application_add_information');?></h4></legend>
                        <div class="row">



                            <div class="form-group col-md-3 <?php echo (form_error('service_charge_value')) ? "has-error" : "";?>" id="comission" >
                                <label for="service_charge_value"><?=$this->lang->line('application_charge_amount');?>(*)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control maskperc" required id="service_charge_value" value="<?php echo set_value('service_charge_value',$shopkeeperform['service_charge_value']);?>" name="service_charge_value" placeholder="<?=$this->lang->line('application_charge_amount');?>" autocomplete="off" maxlength="2" required />
                                    <span class="input-group-addon"><strong>%</strong></span>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="service_charge_freight_option" id="service_charge_freight_option" value="1" <?php echo set_checkbox('service_charge_freight_option', '1', $shopkeeperform['service_charge_value'] == $shopkeeperform['service_charge_freight_value']); ?>  onclick="comissaoFrete()"  maxlength="2" />
                                    <label for="service_charge_freight_option"><?=$this->lang->line('application_commission_freight_same_products');?></label>
                                </div>
                                <?php echo '<i style="color:red">'.form_error('service_charge_value').'</i>';  ?>
                            </div>

                            <div style="display:none" class="form-group col-md-3 comissaoFrete <?php echo (form_error('service_charge_freight_value')) ? "has-error" : "";?>">
                                <label for="service_charge_freight_value"><?=$this->lang->line('application_charge_amount_freight');?></label>
                                <div class="input-group">

                                    <input type="number" class="form-control maskperc" required id="service_charge_freight_value" value="<?php echo set_value('service_charge_freight_value',$shopkeeperform['service_charge_freight_value']);?>" name="service_charge_freight_value" placeholder="<?=$this->lang->line('application_charge_amount_freight');?>" autocomplete="off" maxlength="5" >

                                    <span class="input-group-addon"><strong>%</strong></span>
                                </div>
                                <?php echo '<i style="color:red">'.form_error('service_charge_freight_value').'</i>';  ?>
                            </div>

                            <?php
                            // $filters = $this->data['filters'];
                            $filters = get_instance()->data['fields'];
                            foreach ($filters as $k => $v) {
                                if($v['type'] == 2){ ?>
                                    <div class="form-group col-md-8">
                                        <label for="form-1[]"><?=$v['label']?></label>
                                        <select class="form-control" id="form-2_<?=$k?>" name="form-2[]">
                                            <option value="1"><?=$this->lang->line('application_yes');?></option>
                                            <option value="2"><?=$this->lang->line('application_no');?></option>
                                        </select>
                                    </div>
                                <?php }elseif($v['type'] == 1){ ?>
                                    <div class="form-group col-md-8">
                                        <label for="form-1[]"><?=$v['label']?></label>
                                        <input type="text" class="form-control" name="form-1[]" id="form-1_<?=$k?>" <?= $v['required'] == 1 ? "required" : "" ?>  value="<?=$v['field_value']?>"/>
                                    </div>
                                <?php } ?>
                            <?php } ?>

                            <div class="row">
                                <div class="form-group col-md-2 col-xs-12">
                                    <label>Origem do Seller</label>
                                    <select class="form-control select2" id="utm_source" name="utm_source" required style="order-radius: 1px!important;">
                                        <option selected disabled value=""><?=$this->lang->line('application_origin_seller')?></option>
                                        <?php foreach ($get_attribute_value_utm_param as $k => $v){ ?>
                                            <option value="<?=$v['value']?>" <?=set_select('utm_source', $v['value'], $shopkeeperform['utm_source'] == $v['value']) ?>><?=$v['value'] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </fieldset>
                    <div class="row">
                        <hr>
                    </div>
                    <fieldset>

                        <?php
                        $filters = get_instance()->data['attachments'];
                        if($filters != null) {?>
                        <legend><h4><?=$this->lang->line('application_attachment');?></h4></legend>
                        <div class="row">
                            <div class=" file-drop-zone clearfix">
                                <?php }?>
                                <?php
                                foreach ($filters as $filtr) { ?>
                                <div class="file-preview-thumbnails clearfix"><div class="file-preview-frame krajee-default file-preview-initial file-sortable kv-preview-thumb" id="thumb-prd_image-init-0" data-fileindex="init-0" data-fileid="thumb-prd_image-init-0" data-template="image" draggable="false"><div class="kv-file-content">
                                            <?php if(substr($filtr['field_value'], -3) == 'pdf' ) {?>
                                                <a href="<?=base_url($filtr['field_value'])?>" target="_blank"><?=$filtr['label']?></a>
                                            <?php } else {?>
                                                <a href="<?=base_url($filtr['field_value'])?>" target="_blank">
                                                    <img src="<?=base_url($filtr['field_value'])?>" class="file-preview-image kv-preview-data" title="<?=$filtr['label']?>" style="width: auto; height: auto; max-width: 50%; max-height: 100%;" draggable="false">
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <div class="file-thumbnail-footer">
                                        <div class="file-footer-caption" title="">
                                            <div class="file-caption-info"></div>
                                            <div class="file-size-info"></div>
                                        </div>
                                        <div class="file-thumb-progress kv-hidden"><div class="progress">
                                                <div class="progress-bar bg-info progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="101" aria-valuemin="0" aria-valuemax="100" style="width: 101%;">
                                                    Inicializando …
                                                </div>
                                            </div></div>
                                        <div class="clearfix"></div>
                                    </div>

                                </div>
                            </div>
                            <div class="file-preview-status text-center text-success"></div>
                            <div class="kv-fileinput-error file-error-message" style="display: none;"></div>
                        </div>
                </div>
                </fieldset>

                <div class="box-footer">
                    <a href="<?php echo base_url('ShopkeeperForm/list'); ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                    <?php if($shopkeeperform['status'] == '1'){?>
                        <button type="submit" class="btn btn-primary" id='btn_save' ><?=$this->lang->line('application_save');?></button>

                        <button type="button" class="btn btn-success" id='btn_aproved' onclick="aproved(<?=$shopkeeperform['id']?>, `<?=$shopkeeperform['name']?>`)" data-toggle="modal" data-target="#aprovedModal"><i class="fa fa-check"> Aprovar</i></button>
                        <button type="button" class="btn btn-danger"  id='btn_reproved' onclick="recused(<?=$shopkeeperform['id']?>, `<?=$shopkeeperform['name']?>`)" data-toggle="modal" data-target="#reprovedModal"><i class="fa fa-times"> Reprovar</i></button>
                    <?php }?>
                    <?php if($shopkeeperform['status'] == '4' && $shopkeeperform['user_id']){?>
                        <button type="button" class="btn btn-primary"  id='btn_copyURL' onclick="$('#copyURLFormModal').modal('show');" data-toggle="modal" data-target="#copyURLModal"><i class="fas fa-external-link-alt"> Copiar URL</i></button>
                    <?php }?>
                </div>
            </div>
    </div>
    </form>
</div>
</section>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="aprovedFormModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span id="aprovedFormName"></span></h4>
            </div>

            <form role="form" action="<?php echo base_url('ShopkeeperForm/aproved') ?>" method="post" id="aprovedForm">
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" id="btn_aproved_shopkeeperform" data-dismiss="modal"><?=$this->lang->line('application_no');?></button>
                    <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_yes');?></button>
                </div>
            </form>


        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="copyURLFormModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_delete_setting');?><span id="copyURLFormName"></span></h4>
            </div>

            <form role="form" action="<?php echo base_url('ShopkeeperForm/copyURL') ?>" method="post" id="copyURLForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="copy_URL"><?=$this->lang->line('application_address');?></label>
                            <input type="text" class="form-control" readonly  id="copy_URL" name="copy_URL" value="<?php echo base_url('ShopkeeperForm/complete/'.$shopkeeperform['id']."/".$shopkeeperform['user_id'])?>" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" id="btn_copyURL_shopkeeperform" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                </div>
            </form>


        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" tabindex="-1" role="dialog" id="reprovedFormModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_delete_setting');?><span id="reprovedFormName"></span></h4>
            </div>

            <form role="form" action="<?php echo base_url('ShopkeeperForm/reproved') ?>" method="post" id="reprovedForm">
                <div class="modal-body">
                    <div class="form-group col-md-6">
                        <label for="reason"><?=$this->lang->line('application_reproved_reason');?></label>
                        <select class="form-control" id="reason" name="reason" required >
                            <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                            <?php foreach ($reproved_reasons as $k => $v): ?>
                                <option value="<?=trim($v['id'])?>" <?= set_select('reason', $v['value']) ?>><?=$v['value']?></option>
                            <?php endforeach ?>
                        </select>
                        <input type="hidden" name="id" value="<?=$shopkeeperform['id']?>">
                    </div>
                </div>
                <br>
                <br>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
                </div>
            </form>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<script type="text/javascript">
    var banks = <?php echo json_encode($banks); ?>;
    var usar_mascara_banco = "<?php echo $usar_mascara_banco ?>";


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

    $('document').ready(function() {

        $('.select2').select2();

        var bank_name = $('#bank option:selected').val();
        var defaultComission = $('#service_charge_value').val();
        if(defaultComission == ''){
            $('#service_charge_value').val('19');
        }
        var defaultComissionfreight = $('#service_charge_freight_value').val();
        if(defaultComissionfreight == ''){
            $('#service_charge_freight_value').val('19');
        }

        $('#btn_aproved_shopkeeperform').click(function(){
            $("#aprovedFormModal").modal('block');
            event.preventDefault();
        });
        $("#bank").change(function () {
        $('#agency').val('');
        $('#account').val('');
        if(usar_mascara_banco == true){
            applyBankMask(bank_name);
        }
        });
        if(usar_mascara_banco == true){
            applyBankMask(bank_name);
        }

        <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
            $("#pj_pf").trigger('change');
            exemptIE();
            $('#exempted').prop('checked', $('#exempted').attr('checked') === 'checked')
        <?php endif; ?>
    });

    <?php if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-2002-permitir-criar-lojas-para-pessoas-fisicas')): ?>
    $("#pj_pf").change(function () {
        const is_pj = $(this).val() === "pj";

        $(".show_pf").css({'display': is_pj ? 'none': 'block'});
        $(".show_pj").css({'display': !is_pj ? 'none': 'block'});

        $('#raz_soc, #CNPJ, #insc_estadual').prop('required', is_pj);
        $('#exempted').prop('checked', false).trigger('change');
        $('#insc_estadual').prop('disabled', false);
        $('#CPF').prop('required', !is_pj);
    });
    <?php endif; ?>

    function recused(id,name)
    {

        if(id) {

            $("#reprovedFormModal").modal('show');

            document.getElementById("reprovedFormName").innerHTML= 'Deseja reprovar a loja '+name+'?';

            $("#reprovedForm").on('submit', function() {

                var form = $(this);

                // remove the text-danger
                $(".text-danger").remove();

                $.ajax({
                    url: form.attr('action'),
                    type: form.attr('method'),

                    data: { id: $('[name = "id"]',form).val(),reason: $('[name = "reason"]',form).val()  },
                    dataType: 'json',
                    success:function(response) {

                        if(response.success === true) {
                            $("#messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
                                '</div>');

                            // hide the modal
                            $("#reprovedFormModal").modal('hide');

                            $("#btn_aproved").hide();
                            $("#btn_reproved").hide();
                            $("#btn_save").hide();

                            Swal.fire({
                                icon: 'success',
                                title: data.Message,
                                showCancelButton: false,
                                confirmButtonText: "Ok",
                            }).then((result) => {
                            });

                        } else {

                            $("#messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                                '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
                                '</div>');
                        }

                    },error:e => {
                        console.log(e);
                        return false;
                    }
                });

                return false;
            });
        }
    }

    function aproved(id,name)
    {

        if(id) {

            if($('#service_charge_value').val() == ''){
                $('.msg').remove();
                $('#service_charge_value').focus();
                $('#comission').addClass("has-error");
                $('.form-check').next().append("<span class='msg'>O campo comissão é obrigatório</span>");
            }else{

                $('#comission').removeClass("has-error");
                $('.msg').remove();
                $("#aprovedFormModal").modal('show');

                document.getElementById("aprovedFormName").innerHTML= 'Deseja aprovar a loja '+name+'?';

                $("#aprovedForm").on('submit', function() {

                    var form = $(this);
                    var service_charge_value         = $('#service_charge_value').val();
                    var service_charge_freight_value = $('#service_charge_freight_value').val();

                    // remove the text-danger
                    $(".text-danger").remove();

                    $.ajax({
                        url: form.attr('action'),
                        type: form.attr('method'),
                        data: {
                            'id':id,
                            'service_charge_value':service_charge_value,
                            'service_charge_freight_value':service_charge_freight_value,
                        },
                        dataType: 'json',
                        success:function(response) {

                            if(response.sucess === true){
                                Swal.fire({
                                    icon: 'success',
                                    title: response.messages,
                                    showCancelButton: false,
                                    confirmButtonText: "Ok",
                                }).then((result) => {
                                });
                                $("#btn_aproved").hide();
                                $("#btn_reproved").hide();
                                $("#btn_save").hide();
                            }else{
                                Swal.fire({
                                    icon: 'error',
                                    title: response.messages,
                                    showCancelButton: false,
                                    confirmButtonText: "Ok",
                                }).then((result) => {
                                });
                            }

                            // hide the modal
                            $("#aprovedFormModal").modal('hide');

                        }
                    });

                    return false;
                });
            }
        }
    }

    comissaoFrete();


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

    function exemptIE() {
        const ie = $('#insc_estadual')[0].hasAttribute('disabled')
        if (!ie) {
            $('#insc_estadual').attr({'disabled': true, 'required': false})
        } else {
            $('#insc_estadual').attr({'disabled': false, 'required': true})
        }
    }

</script>
