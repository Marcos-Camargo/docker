<?php include_once(APPPATH . '/third_party/zipcode.php'); ?>
<!--
SW Serviços de Informática 2019
Criar Empresa
 
-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_add";  $this->load->view('templates/content_header',$data) ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <?php if($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success') ?>
                    </div>
                <?php elseif($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error') ?>
                    </div>
                <?php endif; ?>

                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><?=$this->lang->line('application_add_company')?></h3>
                    </div>
                    <form role="form" action="<?php base_url('company/create') ?>" method="post">
                        <div class="box-body">
                            <div class="row">
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
                            </div>
                            <div class="row d-flex justify-content-between">
                            <div class="form-group col-md-3 <?php echo (form_error('pj_pf')) ? 'has-error' : '';  ?>">
                                <label for="pj_pf"><?=$this->lang->line('application_person_type')?></label>
                                <select class="form-control" id="pj_pf" required name="pj_pf">
                                    <option value="PJ" <?= set_select('pj_pf', 'PJ') ?>><?=$this->lang->line('application_person_pj')?></option>
                                    <option value="PF" <?= set_select('pj_pf', 'PF') ?>><?=$this->lang->line('application_person_pf')?></option>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('pj_pf').'</i>'; ?>
                            </div>

                            <div class="form-group col-md-3 show_pj <?php echo (form_error('associate_type_pj')) ? 'has-error' : '';  ?>">
                                <label for="associate_type_pj"><?=$this->lang->line('application_associate_type')?>(*)</label>
                                <?php ?>
                                <select class="form-control" id="associate_type_pj" name="associate_type_pj">
                                    <option value="0" <?= set_select('associate_type_pj', 0) ?>><?=$this->lang->line('application_parent_company')?></option>
                                    <option value="1" <?= set_select('associate_type_pj', 1) ?>><?=$this->lang->line('application_agency')?></option>
                                    <option value="2" <?= set_select('associate_type_pj', 2) ?>><?=$this->lang->line('application_partner')?></option>
                                    <option value="3" <?= set_select('associate_type_pj', 3) ?>><?=$this->lang->line('application_affiliate')?></option>
                                    <option value="4" <?= set_select('associate_type_pj', 4) ?>><?=$this->lang->line('application_autonomous')?></option>
                                    <option value="5" <?= set_select('associate_type_pj', 5) ?>><?=$this->lang->line('application_indicator')?></option>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('associate_type_pj').'</i>'; ?>
                            </div>

                            <div class="form-group col-md-3 show_pf <?php echo (form_error('associate_type_pf')) ? 'has-error' : '';  ?>">
                                <label for="associate_type_pf"><?=$this->lang->line('application_associate_type')?>(*)</label>
                                <?php ?>
                                <select class="form-control" id="associate_type_pf" name="associate_type_pf">
                                    <option value="2" <?= set_select('associate_type_pj', 2) ?>><?=$this->lang->line('application_partner')?></option>
                                    <option value="3" <?= set_select('associate_type_pf', 3) ?>><?=$this->lang->line('application_affiliate')?></option>
                                    <option value="4" <?= set_select('associate_type_pf', 4) ?>><?=$this->lang->line('application_autonomous')?></option>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('associate_type_pf').'</i>'; ?>
                            </div>

                            <div class="form-group col-md-3 <?php echo (form_error('service_charge_value')) ? 'has-error' : ''; ?>">
                                <label for="service_charge_value"><?=$this->lang->line('application_charge_amount')?></label>
                                <input type="text" class="form-control" id="service_charge_value" name="service_charge_value" maxlength="2" placeholder="<?=$this->lang->line('application_enter_charge_amount')?>" autocomplete="off" value = "<?= set_value('service_charge_value') ?>" onKeyPress="return digitos(event, this);">
                                <?php echo '<i style="color:red">'.form_error('service_charge_value').'</i>'; ?>
                            </div>

                            <div class="form-group col-md-3">
                                <label for="monthly_store">Plano Mensalista
                                    <button id="info_plan" class="btn-link"></button>
                                </label>
                                <select class="form-control" name="monthly_plan"
                                        id="monthly_plan">
                                    <option value="0">Sem plano</option>
                                    <?php foreach ($plans as $plan) {
                                        if (!$store_data) $store_data['monthly_plan'] = null;?>
                                        <option data-prices="<?= $plan['id'] ?>"
                                                value="<?= $plan['id'] ?>" <?= set_select('monthly_plan', $plan['id'], $store_data['monthly_plan'] == $plan['id']) ?>><?= $plan['description'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <?php if ($stores_multi_cd): ?>
                            <div class="form-group col-md-2">
                                <label for="monthly_store"><?=$this->lang->line('application_multi_cd_store')?></label>
                                <select class="form-control" name="multi_channel_fulfillment" id="multi_channel_fulfillment">
                                    <option value="0"><?=$this->lang->line('application_no')?></option>
                                    <option value="1"><?=$this->lang->line('application_yes')?></option>
                                </select>
                            </div>
                            <?php endif; ?>
                            </div>
                            <div class="row">
                            <div class="form-group col-md-12 <?php echo (form_error('name')) ? 'has-error' : '';  ?>">
                                <label for="name"><?=$this->lang->line('application_name')?>(*)</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="<?=$this->lang->line('application_enter_name')?>" autocomplete="off" value = "<?= set_value('name') ?>"  required>
                                <?php echo '<i style="color:red">'.form_error('name').'</i>'; ?>
                            </div>
                            </div>
                            <div class="row">
                            <div class="form-group col-md-12 show_pj <?php echo (form_error('raz_soc')) ? 'has-error' : '';  ?>">
                                <label for="raz_soc"><?=$this->lang->line('application_raz_soc')?>(*)</label>
                                <input type="text" class="form-control" id="raz_soc" name="raz_soc" placeholder="<?=$this->lang->line('application_enter_razao_social')?>" autocomplete="off" value = "<?= set_value('raz_soc') ?>"  required>
                                <?php echo '<i style="color:red">'.form_error('raz_soc').'</i>'; ?>
                            </div>
                            </div>
                            <div class="row">
                            <div class="form-group col-md-4 show_pj <?php echo (form_error('CNPJ')) ? 'has-error' : '';  ?>">
                                <label for="CNPJ"><?=$this->lang->line('application_cnpj')?>(*)</label>
                                <input type="text" class="form-control" maxlength="18" minlenght="18" id="CNPJ" name="CNPJ" placeholder="<?=$this->lang->line('application_enter_CNPJ') ?>" autocomplete="off" value = "<?= set_value('CNPJ') ?>" autocomplete="off" onkeypress='mascaraMutuario(this,cnpj)' onblur='clearTimeout()'  required>
                                <?php echo '<i style="color:red">'.form_error('CNPJ').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-4 show_pj <?php echo (form_error('IEST')) ? 'has-error' : '';  ?>">
                                <label for="IEST"><?=$this->lang->line('application_iest')?>(*)</label>
                                <input type="text" class="form-control" id="IEST" name="IEST" placeholder="<?=$this->lang->line('application_enter_incricao_estadual')?>" autocomplete="off" value = "<?= set_value('IEST') ?>" required>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" <?php echo set_checkbox('exempted', '1',false); ?>  name="exempted" onchange="exemptIE()" id="exempted">
                                    <label class="form-check-label" for="exempted">
                                        <?= $this->lang->line('application_exempted'); ?>
                                    </label>
                                </div>
                                <?php echo '<i style="color:red">'.form_error('IEST').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-4 show_pj <?php echo (form_error('IMUN')) ? 'has-error' : '';  ?>">
                                <label for="IMUN"><?=$this->lang->line('application_imun')?>(*)</label>
                                <input type="text" class="form-control" id="IMUN" name="IMUN" placeholder="<?=$this->lang->line('application_enter_incricao_municipal')?>" autocomplete="off" value = "<?= set_value('IMUN') ?>" required>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" <?php echo set_checkbox('exemptmd', '1',false); ?>  name="exemptmd" onchange="exemptIM()" id="exemptmd">
                                    <label class="form-check-label" for="exemptmd">
                                        <?= $this->lang->line('application_exempted'); ?>
                                    </label>
                                </div>
                                <?php echo '<i style="color:red">'.form_error('IMUN').'</i>'; ?>
                            </div>
                            </div>
                            <div class="row">
                            <div class="form-group col-md-4 show_pj <?php echo (form_error('gestor')) ? 'has-error' : '';  ?>">
                                <label for="gestor"><?=$this->lang->line('application_gestor')?>(*)</label>
                                <input type="text" class="form-control" id="gestor" name="gestor" placeholder="<?=$this->lang->line('application_enter_manager')?>" autocomplete="off" value = "<?= set_value('gestor') ?>" required>
                                <?php echo '<i style="color:red">'.form_error('gestor').'</i>'; ?>
                            </div>


                            <div class="form-group col-md-3 show_pf <?php echo (form_error('CPF')) ? 'has-error' : '';  ?>">
                                <label for="CPF"><?=$this->lang->line('application_cpf')?>(*)</label>
                                <input type="text" class="form-control" maxlength="14" minlenght="14" id="CPF" name="CPF" placeholder="<?=$this->lang->line('application_enter_cpf') ?>" autocomplete="off" value = "<?= set_value('CPF') ?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" onblur='clearTimeout()'>
                                <?php echo '<i style="color:red">'.form_error('CPF').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 show_pf <?php echo (form_error('RG')) ? 'has-error' : '';  ?>">
                                <label for="RG"><?=$this->lang->line('application_rg')?>(*)</label>
                                <input type="text" class="form-control" id="RG" name="RG" placeholder="<?=$this->lang->line('application_enter_rg')?>" autocomplete="off" value = "<?= set_value('RG') ?>">
                                <?php echo '<i style="color:red">'.form_error('RG').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-3 show_pf <?php echo (form_error('rg_expedition_agency')) ? 'has-error' : '';  ?>">
                                <label for="rg_expedition_agency"><?=$this->lang->line('application_enter_rg_expedition_agency')?>(*)</label>
                                <input type="text" class="form-control" id="rg_expedition_agency" name="rg_expedition_agency" placeholder="<?=$this->lang->line('application_enter_rg_expedition_agency')?>" autocomplete="off" value ="<?= set_value('rg_expedition_agency') ?>">
                                <?php echo '<i style="color:red">'.form_error('rg_expedition_agency').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 show_pf <?php echo (form_error('rg_expedition_date')) ? 'has-error' : '';  ?>">
                                <label for="rg_expedition_date"><?=$this->lang->line('application_rg_expedition_date');?>(*)</label>
                                <div class='input-group date' id='rg_expedition_date_pick' name="rg_expedition_date_pick">
                                    <input type='text' class="form-control" id='rg_expedition_date' name="rg_expedition_date" autocomplete="off" value="<?php echo set_value('rg_expedition_date');?>" />
                                    <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
                                </div>
                                <?php echo '<i style="color:red">'.form_error('rg_expedition_date').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 show_pf <?php echo (form_error('birth_date')) ? 'has-error' : '';?>">
                                <label for="birth_date"><?=$this->lang->line('application_birth_date');?>(*)</label>
                                <div class='input-group date' id='birth_date_pick' name="birth_date_pick">
                                    <input type='text' class="form-control" id='birth_date' name="birth_date" autocomplete="off" value="<?php echo set_value('birth_date');?>" />
                                    <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
                                </div>
                                <?php echo '<i style="color:red">'.form_error('birth_date').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-4 show_pf <?php echo (form_error('affiliation')) ? 'has-error' : '';  ?>">
                                <label for="affiliation"><?=$this->lang->line('application_affiliation')?>(*)</label>
                                <input type="text" class="form-control" id="affiliation" name="affiliation" placeholder="<?=$this->lang->line('application_enter_affiliation')?>" autocomplete="off" value = "<?= set_value('affiliation') ?>">
                                <?php echo '<i style="color:red">'.form_error('affiliation').'</i>'; ?>
                            </div>



                            <div class="form-group col-md-4 <?php echo (form_error('email')) ? 'has-error' : '';  ?>">
                                <label for="email"><?=$this->lang->line('application_email')?>(*)</label>
                                <input type="email" class="form-control" required id="email" name="email" placeholder="<?=$this->lang->line('application_enter_email')?>" autocomplete="off" value = '<?= set_value('email') ?>'>
                                <?php echo '<i style="color:red">'.form_error('email').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('phone_1')) ? 'has-error' : '';  ?>">
                                <label for="phone"><?=$this->lang->line('application_phone')?> 1(*)</label>
                                <input type="text" class="form-control" required id="phone_1" name="phone_1" placeholder="<?=$this->lang->line('application_enter_phone')?>" autocomplete="off" value = '<?= set_value('phone_1') ?>'  onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" maxlength="15">
                                <?php echo '<i style="color:red">'.form_error('phone_1').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('phone_2')) ? 'has-error' : '';  ?>">
                                <label for="phone"><?=$this->lang->line('application_phone')?> 2</label>
                                <input type="text" class="form-control" id="phone_2" name="phone_2" placeholder="<?=$this->lang->line('application_enter_phone')?>" autocomplete="off" value = '<?= set_value('phone_2') ?>' onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" maxlength="15">
                                <?php echo '<i style="color:red">'.form_error('phone_2').'</i>'; ?>
                            </div>
                            </div>
                            <div class="row">
                            <div class="form-group col-md-2 <?php echo (form_error('zipcode')) ? 'has-error' : '';  ?>">
                                <label for="zipcode"><?=$this->lang->line('application_zip_code')?>(*)</label>
                                <input type="text" class="form-control" required id="zipcode" name="zipcode" placeholder="<?=$this->lang->line('application_enter_zipcode')?>" autocomplete="off" value = '<?= set_value('zipcode') ?>' onkeyup="this.value=this.value.replace(/[^\d]/,'')">
                                <?php echo '<i style="color:red">'.form_error('zipcode').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-8 <?php echo (form_error('address')) ? 'has-error' : '';  ?>">
                                <label for="address"><?=$this->lang->line('application_address')?>(*)</label>
                                <input type="text" class="form-control" required id="address" name="address" placeholder="<?=$this->lang->line('application_enter_address')?>" autocomplete="off" value = '<?= set_value('address') ?>'>
                                <?php echo '<i style="color:red">'.form_error('address').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('addr_num')) ? 'has-error' : '';  ?>">
                                <label for="addr_num"><?=$this->lang->line('application_number')?>(*)</label>
                                <input type="text" class="form-control" required id="addr_num" name="addr_num" placeholder="<?=$this->lang->line('application_enter_number')?>" autocomplete="off" value = '<?= set_value('addr_num') ?>'>
                                <?php echo '<i style="color:red">'.form_error('addr_num').'</i>'; ?>
                            </div>
                            </div>
                            <div class="row">
                            <div class="form-group col-md-2 <?php echo (form_error('addr_compl')) ? 'has-error' : '';  ?>">
                                <label for="addr_compl"><?=$this->lang->line('application_complement')?></label>
                                <input type="text" class="form-control" id="addr_compl" name="addr_compl" placeholder="<?=$this->lang->line('application_enter_complement')?>" autocomplete="off" value = '<?= set_value('addr_compl') ?>'>
                                <?php echo '<i style="color:red">'.form_error('addr_compl').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-3 <?php echo (form_error('addr_neigh')) ? 'has-error' : '';  ?>">
                                <label for="addr_neigh"><?=$this->lang->line('application_neighb')?>(*)</label>
                                <input type="text" class="form-control" required id="addr_neigh" name="addr_neigh" placeholder="<?php echo set_value('addr_neigh')?>" placeholder="<?=$this->lang->line('application_enter_neighborhood')?>" autocomplete="off" value = '<?= set_value('addr_neigh') ?>'>
                                <?php echo '<i style="color:red">'.form_error('addr_neigh').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-3 <?php echo (form_error('addr_city')) ? 'has-error' : '';  ?>">
                                <label for="addr_city"><?=$this->lang->line('application_city')?>(*)</label>
                                <input type="text" class="form-control" required id="addr_city" name="addr_city" placeholder="<?php echo set_value('addr_city')?>" autocomplete="off" value = '<?= set_value('addr_city') ?>'>
                                <?php echo '<i style="color:red">'.form_error('addr_city').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('addr_uf')) ? 'has-error' : '';  ?>">
                                <label for="addr_uf"><?=$this->lang->line('application_uf')?>(*)</label>
                                <select class="form-control" id="addr_UF" required name="addr_uf">
                                    <option value=""><?=$this->lang->line('application_select')?></option>
                                    <?php foreach ($ufs as $k => $v): ?>
                                        <option value="<?php echo trim($k) ?>" <?= set_select('addr_uf', trim($k)) ?>><?php echo $v ?></option>
                                    <?php endforeach ?>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('addr_uf').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('country')) ? 'has-error' : '';  ?>">
                                <label for="country"><?=$this->lang->line('application_country')?></label>
                                <select class="form-control" id="country" name="country">
                                    <option value=""><?=$this->lang->line('application_select')?></option>
                                    <?php foreach ($paises as $k => $v): ?>
                                        <option value="<?php echo trim($k) ?>" <?= set_select('country', trim($k)) ?>><?php echo $v ?></option>
                                    <?php endforeach ?>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('country').'</i>'; ?>
                            </div>
                            <!-- retirada da mensagem em 02/03/2020
                <div class="form-group col-md-12">
                  <label for="permission"><?=$this->lang->line('application_message')?></label>
                  <textarea class="form-control" id="message" name="message" value = '<?= set_value('message') ?>'>
                  </textarea>
                </div>
                -->

                            </div>
                            <div class="row">
                            <div class="form-group col-md-3 hide_matriz <?php echo (form_error('bank')) ? 'has-error' : '';  ?>">
                                <label for="bank"><?=$this->lang->line('application_bank');?>(*)</label>
                                <select class="form-control" id="bank" name="bank" >
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($banks as $k => $v): ?>
                                        <option value="<?=trim($v['name'])?>" <?=set_select('bank', $v['name'])?>><?=$v['name']?></option>
                                    <?php endforeach ?>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('bank').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-3 hide_matriz <?php echo (form_error('agency')) ? 'has-error' : '';  ?>">
                                <label for="agency"><?=$this->lang->line('application_agency');?>(*)</label>
                                <input type="text" class="form-control" id="agency" name="agency"  placeholder="<?=$this->lang->line('application_enter_agency')?>" autocomplete="off" value="<?=set_value('agency')?>" >
                                <?php echo '<i style="color:red">'.form_error('agency').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-3 hide_matriz <?php echo (form_error('account_type')) ? 'has-error' : '';  ?>">
                                <label for="currency"><?=$this->lang->line('application_type_account');?>(*)</label>
                                <select class="form-control" id="account_type" name="account_type" >
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($type_accounts as $k => $v): ?>
                                        <option value="<?=trim($v)?>" <?= set_select('account_type', $v) ?>><?=$v ?></option>
                                    <?php endforeach ?>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('account_type').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-3 hide_matriz <?php echo (form_error('account')) ? 'has-error' : '';  ?>">
                                <label for="account"><?=$this->lang->line('application_account');?>(*)</label>
                                <input type="text" class="form-control" id="account" name="account" placeholder="<?=$this->lang->line('application_enter_bank_account')?>" autocomplete="off" value="<?=set_value('account')?>" onKeyPress="return digitos(event, this);">
                                <?php echo '<i style="color:red">'.form_error('account').'</i>'; ?>
                            </div>
                        </div>
                        <h4><?=$this->lang->line('application_contacts');?></h4>
                        <div class="row">
                            <div class="form-group col-md-4 <?php echo (form_error('responsible_finan_name')) ? 'has-error' : '';  ?>">
                                <label for="responsible_finan_name"><?=$this->lang->line('application_responsible_finan_name');?></label>
                                <input type="text" class="form-control" id="responsible_finan_name" name="responsible_finan_name" autocomplete="off" value="<?=set_value('responsible_finan_name')?>">
                                <?php echo '<i style="color:red">'.form_error('responsible_finan_name').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-4 <?php echo (form_error('responsible_finan_email')) ? 'has-error' : '';  ?>">
                                <label for="responsible_finan_email"><?=$this->lang->line('application_responsible_finan_email');?></label>
                                <input type="email" class="form-control" id="responsible_finan_email" name="responsible_finan_email" autocomplete="off" value="<?=set_value('responsible_finan_email')?>">
                                <?php echo '<i style="color:red">'.form_error('responsible_finan_email').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-4 <?php echo (form_error('responsible_finan_tell')) ? 'has-error' : '';  ?>">
                                <label for="responsible_finan_tell"><?=$this->lang->line('application_responsible_finan_tell');?></label>
                                <input type="text" class="form-control" id="responsible_finan_tell" name="responsible_finan_tell" autocomplete="off" value="<?=set_value('responsible_finan_tell')?>" onkeypress="return digitos(event, this);" onkeydown="Mascara('TEL',this,event);" maxlength="15" placeholder="Digite o telefone">
                                <?php echo '<i style="color:red">'.form_error('responsible_finan_tell').'</i>';  ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4 <?php echo (form_error('responsible_ti_name')) ? 'has-error' : '';  ?>">
                                <label for="responsible_ti_name"><?=$this->lang->line('application_responsible_ti_name');?></label>
                                <input type="text" class="form-control" id="responsible_ti_name" name="responsible_ti_name" autocomplete="off" value="<?=set_value('responsible_ti_name')?>">
                                <?php echo '<i style="color:red">'.form_error('responsible_ti_name').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-4 <?php echo (form_error('responsible_ti_email')) ? 'has-error' : '';  ?>">
                                <label for="responsible_ti_email"><?=$this->lang->line('application_responsible_ti_email');?></label>
                                <input type="email" class="form-control" id="responsible_ti_email" name="responsible_ti_email" autocomplete="off" value="<?=set_value('responsible_ti_email')?>">
                                <?php echo '<i style="color:red">'.form_error('responsible_ti_email').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-4 <?php echo (form_error('responsible_ti_tell')) ? 'has-error' : '';  ?>">
                                <label for="responsible_ti_tell"><?=$this->lang->line('application_responsible_ti_tell');?></label>
                                <input type="text" class="form-control" id="responsible_ti_tell" name="responsible_ti_tell" autocomplete="off" value="<?=set_value('responsible_ti_tell')?>" onkeypress="return digitos(event, this);" onkeydown="Mascara('TEL',this,event);" maxlength="15" placeholder="Digite o telefone">
                                <?php echo '<i style="color:red">'.form_error('responsible_ti_tell').'</i>';  ?>
                            </div>
                        </div>
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
                    </div>
                </div>
                <!-- /.box-body -->

                <div class="box-footer">
                    <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save')?></button>
                    <a href="<?php echo base_url('company/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back')?></a>
                </div>
                </form>
            </div>
            <!-- /.box -->
        </div>
        <!-- col-md-12 -->
</div>
<!-- /.row -->


</section>
<!-- /.content -->
</div>
<!-- /.content-wrapper -->
<script type="text/javascript">
    var banks = <?php echo json_encode($banks); ?>;
    var usar_mascara_banco = "<?php echo $usar_mascara_banco ?>";    
    var agency = $('#agency').val();
    var account = $('#account').val();


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
        
    $(document).ready(function() {
        $("#mainCompanyNav").addClass('active');
        $("#addCompanyNav").addClass('active');
        $("#message").wysihtml5();
        var agency = $('#agency').val();
        var account = $('#account').val();
        var bank_name = $('#bank option:selected').val();

        if(usar_mascara_banco == true){
            applyBankMask(bank_name);
        }
        $("#bank").change(function () {
            $('#agency').val('');
            $('#account').val('');
            bank_name = $('#bank option:selected').val();
            if(usar_mascara_banco == true){
                applyBankMask(bank_name);
            }
        });
        
        $("select[name*='country']").val('BR').attr('selected', true).trigger('change');
        // $("select[name*='country']").trigger('change');

        $('#birth_date_pick').datepicker({
            format: "dd/mm/yyyy",
            autoclose: true,
            language: "pt-BR",
            endDate: new Date(new Date().setFullYear(new Date().getFullYear() - 18)),  // tem que ter 18 anos completos...
            todayBtn: true,
            todayHighlight: true
        });
        $('#rg_expedition_date_pick').datepicker({
            format: "dd/mm/yyyy",
            autoclose: true,
            language: "pt-BR",
            endDate: new Date(),
            todayBtn: true,
            todayHighlight: true
        });

        if ($('#pj_pf option:selected').val() == "PF")  {
            $(".hide_matriz").show();
            $(".show_pf").show();
            $(".show_pj").hide();
        } else {
            $(".show_pf").hide();
            $(".show_pj").show();
            if ($('#associate_type_pj option:selected').val() == "0")  {
                $(".hide_matriz").hide();
            } else {
                $(".hide_matriz").show();
            }
        }
        toggleRequiredField($('#pj_pf option:selected').val());

        /*if ($('#exempted')[0].hasAttribute('checked')) {
            $('#IEST').attr({'disabled': true, 'required': false})
        } else {
            $('#IEST').attr({'disabled': false, 'required': true})
        }*/

        exemptIM();
        exemptIE();

    });

    $("#pj_pf").change(function () {
        if ($('#pj_pf option:selected').val() == "PF")  {
            $(".show_pf").show();
            $(".show_pj").hide();
            $(".hide_matriz").show();
        } else {
            $(".show_pf").hide();
            $(".show_pj").show();
            if ($('#associate_type_pj option:selected').val() == "0")  {
                $(".hide_matriz").hide();
            } else {
                $(".hide_matriz").show();
            }
        }
        toggleRequiredField($('#pj_pf option:selected').val());
    });

    $("#associate_type_pj").change(function () {
        if ($('#associate_type_pj option:selected').val() == "0")  {
            $(".hide_matriz").hide();
        } else {
            $(".hide_matriz").show();
        }
    });

    const toggleRequiredField = type => {
        $('[name="raz_soc"]').attr('required', type === 'PJ');
        $('[name="CNPJ"]').attr('required', type === 'PJ');
        $('[name="IEST"]').attr('required', type === 'PJ');
        $('[name="IMUN"]').attr('required', type === 'PJ');
        $('[name="gestor"]').attr('required', type === 'PJ');

        $('[name="CPF"]').attr('required', type === 'PF');
        $('[name="RG"]').attr('required', type === 'PF');
        $('[name="rg_expedition_agency"]').attr('required', type === 'PF');
        $('[name="rg_expedition_date"]').attr('required', type === 'PF');
        $('[name="birth_date"]').attr('required', type === 'PF');
        $('[name="affiliation"]').attr('required', type === 'PF');
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
        const checked = $('#exempted').is(':checked');
        $('#IEST').attr({'disabled': true, 'required': false})
        if (!checked && $('#pj_pf').val() === 'PJ') {
            $('#IEST').attr({'disabled': false, 'required': true})
        }
    }
    function exemptIM() {
        const checked = $('#exemptmd').is(':checked');
        $('#IMUN').attr({'disabled': true, 'required': false})
        if (!checked && $('#pj_pf').val() === 'PJ') {
            $('#IMUN').attr({'disabled': false, 'required': true});
        }
    }

</script>