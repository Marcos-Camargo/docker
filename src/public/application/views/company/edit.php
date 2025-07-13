<?php include_once(APPPATH . '/third_party/zipcode.php'); ?>
<!--
SW Serviços de Informática 2019
Editar Empresa
 
-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12 col-print-12">

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
                <?php if(in_array('updateCompany', $user_permission)): ?>
                    <a type="button" class="<?=$company_data['active'] != 2 ?'btn btn-danger':'btn btn-success'?> no-print" href="<?=$company_data['active'] != 2 ?base_url('company/inactive/' . $company_data['id']) : base_url('company/active/' . $company_data['id'])?>">
                        <?=$company_data['active'] != 2 ?$this->lang->line('application_inactive_company'):$this->lang->line('application_active_company');?>
                    </a>
                <?php endif; ?>
                <a id='print_button' href="#" class="btn btn-default no-print"><i class="fa fa-print" aria-hidden="true"></i></a>
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><?=$this->lang->line('application_update_information');?></h3>
                    </div>
                    <form role="form" action="<?php base_url('company/update') ?>" method="post"  enctype="multipart/form-data">
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
                            <div class="row">
                            <div class="form-group col-md-5 col-print-5">
                                <label class="col-md-12 col-print-12" id="logo_view"><?=$this->lang->line('application_logo_preview');?>: </label>
                                <span class="logo-lg"><img src="<?php echo base_url() . $company_data['logo'] ?>" width="150" height="50"></span>
                            </div>

                            <div class="form-group col-md-7 no-print">
                                <label for="company_image"><?=$this->lang->line('application_logo');?> (Melhor visualização Largura:150px Altura:50 px)</label>
                                <div class="kv-avatar">
                                    <div class="file-loading">
                                        <input id="company_image" name="company_image" type="file">
                                    </div>
                                </div>
                            </div>
                            </div>

                            <div class="row">
                            <div class="form-group col-md-3 col-print-3 <?php echo (form_error('pj_pf')) ? 'has-error' : '';  ?>">
                                <label for="pj_pf"><?=$this->lang->line('application_person_type')?></label>
                                <select class="form-control" id="pj_pf" required name="pj_pf">
                                    <option value="PJ" <?= set_select('pj_pf', 'PJ',($company_data['pj_pf'] == "PJ")) ?>><?=$this->lang->line('application_person_pj')?></option>
                                    <option value="PF" <?= set_select('pj_pf', 'PF',($company_data['pj_pf'] == "PF")) ?>><?=$this->lang->line('application_person_pf')?></option>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('pj_pf').'</i>'; ?>
                            </div>

                            <div class="form-group col-md-3 col-print-3 show_pj <?php echo (form_error('associate_type_pj')) ? 'has-error' : '';  ?>">
                                <label for="associate_type_pj"><?=$this->lang->line('application_associate_type')?>(*)</label>
                                <?php ?>
                                <select class="form-control" id="associate_type_pj" name="associate_type_pj">
                                    <option value="0" <?= set_select('associate_type_pj', 0, ($company_data['associate_type'] == 0)) ?>><?=$this->lang->line('application_parent_company')?></option>
                                    <option value="1" <?= set_select('associate_type_pj', 1, ($company_data['associate_type'] == 1)) ?>><?=$this->lang->line('application_agency')?></option>
                                    <option value="2" <?= set_select('associate_type_pj', 2, ($company_data['associate_type'] == 2)) ?>><?=$this->lang->line('application_partner')?></option>
                                    <option value="3" <?= set_select('associate_type_pj', 3, ($company_data['associate_type'] == 3)) ?>><?=$this->lang->line('application_affiliate')?></option>
                                    <option value="4" <?= set_select('associate_type_pj', 4, ($company_data['associate_type'] == 4)) ?>><?=$this->lang->line('application_autonomous')?></option>
                                    <option value="5" <?= set_select('associate_type_pj', 5, ($company_data['associate_type'] == 5)) ?>><?=$this->lang->line('application_indicator')?></option>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('associate_type_pj').'</i>'; ?>
                            </div>

                            <div class="form-group col-md-3 col-print-3 show_pf <?php echo (form_error('associate_type_pf')) ? 'has-error' : '';  ?>">
                                <label for="associate_type_pf"><?=$this->lang->line('application_associate_type')?>(*)</label>
                                <?php ?>
                                <select class="form-control" id="associate_type_pf" name="associate_type_pf">
                                    <option value="2" <?= set_select('associate_type_pf', 2, ($company_data['associate_type'] == 2)) ?>><?=$this->lang->line('application_partner')?></option>
                                    <option value="3" <?= set_select('associate_type_pf', 3, ($company_data['associate_type'] == 3)) ?>><?=$this->lang->line('application_affiliate')?></option>
                                    <option value="4" <?= set_select('associate_type_pf', 4, ($company_data['associate_type'] == 4)) ?>><?=$this->lang->line('application_autonomous')?></option>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('associate_type_pf').'</i>'; ?>
                            </div>

                            <div class="form-group col-md-3 col-print-3 hide_matriz <?php echo (form_error('service_charge_value')) ? 'has-error' : ''; ?>">
                                <label for="service_charge_value"><?=$this->lang->line('application_charge_amount')?></label>
                                <input type="text" class="form-control" id="service_charge_value" name="service_charge_value" maxlength="2" placeholder="<?=$this->lang->line('application_enter_charge_amount')?>" autocomplete="off" value ="<?php echo set_value('service_charge_value', $company_data['service_charge_value']) ?>" onKeyPress="return digitos(event, this);">
                                <?php echo '<i style="color:red">'.form_error('service_charge_value').'</i>'; ?>
                            </div>

                            <div class="form-group col-md-3 col-print-3">
                                <label for="monthly_store">Plano Mensalista
                                    <button id="info_plan" class="btn-link"></button>
                                </label>
                                <select class="form-control" name="monthly_plan"
                                        id="monthly_plan">
                                    <option value="0">Sem plano</option>
                                    <?php foreach ($plans as $plan) {
                                        if (!$store_data) $store_data['monthly_plan'] = null;
                                        if ($plan['id'] === $company_data['plan_id']) $selected = 'selected'; else $selected = ''; ?>
                                        <option <?php echo $selected ?> data-prices="<?= $plan['id'] ?>"
                                                                        value="<?= $plan['id'] ?>" <?= set_select('monthly_plan', $plan['id'], $store_data['monthly_plan'] == $plan['id']) ?>><?= $plan['description'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <?php if ($stores_multi_cd): ?>
                            <div class="form-group col-md-2">
                                <label for="multi_channel_fulfillment"><?=$this->lang->line('application_multi_cd_store')?></label>
                                <select class="form-control" name="multi_channel_fulfillment" id="multi_channel_fulfillment" disabled>
                                    <option value="0" <?= set_select('multi_channel_fulfillment', 0, $company_data['multi_channel_fulfillment'] == 0) ?>><?=$this->lang->line('application_no')?></option>
                                    <option value="1" <?= set_select('multi_channel_fulfillment', 1, $company_data['multi_channel_fulfillment'] == 1) ?>><?=$this->lang->line('application_yes')?></option>
                                </select>
                            </div>
                            <?php endif; ?>
                            </div>

                            <div class="row">
                            <div class="form-group col-md-12 col-print-12 <?php echo (form_error('name')) ? 'has-error' : '';  ?>">
                                <label for="name"><?=$this->lang->line('application_name');?>(*)</label>
                                <input type="text" class="form-control" id="name" name="name" required placeholder="<?=$this->lang->line('application_enter_name');?>" value="<?php echo set_value('name', $company_data['name']) ?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('name').'</i>'; ?>
                            </div>
                            </div>

                            <div class="row">
                            <div class="form-group col-md-12 col-print-12 show_pj <?php echo (form_error('raz_soc')) ? 'has-error' : '';  ?>">
                                <label for="raz_soc"><?=$this->lang->line('application_raz_soc');?>(*)</label>
                                <input type="text" class="form-control" id="raz_soc" name="raz_soc" placeholder="<?=$this->lang->line('application_enter_razao_social');?>" value="<?php echo set_value('raz_soc', $company_data['raz_social']) ?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('raz_soc').'</i>'; ?>
                            </div>
                            </div>

                            <div class="row">
                            <div class="form-group col-md-4 col-print-4 show_pj <?php echo (form_error('CNPJ')) ? 'has-error' : '';  ?>">
                                <label for="CNPJ"><?=$this->lang->line('application_cnpj');?>(*)</label>
                                <input type="text" class="form-control" maxlength="18" minlenght="18" id="CNPJ" name="CNPJ" placeholder="<?=$this->lang->line('application_enter_CNPJ'); ?>" value="<?php echo set_value('CNPJ', $company_data['CNPJ']) ?>" autocomplete="off" onkeypress='mascaraMutuario(this,cnpj)' onblur='clearTimeout()'>
                                <?php echo '<i style="color:red">'.form_error('CNPJ').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-4 col-print-4 show_pj <?php echo (form_error('IEST')) ? 'has-error' : '';  ?>">
                                <label for="IEST"><?=$this->lang->line('application_iest');?>(*)</label>
                                <input type="text" <?php echo set_value('exempted') == 1 ? 'checked' : (set_value('CNPJ') ? '' : ($company_data['IEST'] == "0" ? 'disabled' : '')) ?> class="form-control" id="IEST" name="IEST" placeholder="<?=$this->lang->line('application_enter_incricao_estadual');?>" value="<?php echo set_value('IEST', $company_data['IEST']) ?>" autocomplete="off">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" <?php echo set_checkbox('exempted', '1',$company_data['IEST'] == "0"); ?>  name="exempted" onchange="exemptIE()" id="exempted">
                                    <label class="form-check-label" for="exempted">
                                        <?= $this->lang->line('application_exempted'); ?>
                                    </label>
                                </div>
                                <?php echo '<i style="color:red">'.form_error('IEST').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-4 show_pj <?php echo (form_error('IMUN')) ? 'has-error' : '';  ?>">
                                <label for="IMUN"><?=$this->lang->line('application_imun');?>(*)</label>
                                <input type="text" class="form-control" id="IMUN" name="IMUN" placeholder="<?=$this->lang->line('application_enter_incricao_municipal');?>" value="<?php echo set_value('IMUN', $company_data['IMUN']) ?>" autocomplete="off" <?php echo set_value('exempted') == 1 ? 'checked' : (set_value('CNPJ') ? '' : ($company_data['IMUN'] == "0" ? 'disabled' : '')) ?>>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" <?php echo set_checkbox('exemptmd', '1',$company_data['IMUN'] == "0"); ?>  name="exemptmd" onchange="exemptIM()" id="exemptmd">
                                    <label class="form-check-label" for="exemptmd">
                                        <?= $this->lang->line('application_exempted'); ?>
                                    </label>
                                </div>
                                <?php echo '<i style="color:red">'.form_error('IMUN').'</i>'; ?>
                            </div>
                            </div>

                            <div class="row">
                            <div class="form-group col-md-4 show_pj <?php echo (form_error('gestor')) ? 'has-error' : '';  ?>">
                                <label for="gestor"><?=$this->lang->line('application_gestor');?>(*)</label>
                                <input type="text" class="form-control" id="gestor" name="gestor" placeholder="<?=$this->lang->line('application_enter_manager');?>" value="<?php echo set_value('gestor', $company_data['gestor']) ?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('gestor').'</i>'; ?>
                            </div>

                            <div class="form-group col-md-3 show_pf <?php echo (form_error('CPF')) ? 'has-error' : '';  ?>">
                                <label for="CPF"><?=$this->lang->line('application_cpf')?>(*)</label>
                                <input type="text" class="form-control" maxlength="14" minlenght="14" id="CPF" name="CPF" placeholder="<?=$this->lang->line('application_enter_cpf') ?>" autocomplete="off" value = "<?= set_value('CPF', $company_data['CPF']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" onblur='clearTimeout()'>
                                <?php echo '<i style="color:red">'.form_error('CPF').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 show_pf <?php echo (form_error('RG')) ? 'has-error' : '';  ?>">
                                <label for="RG"><?=$this->lang->line('application_rg')?>(*)</label>
                                <input type="text" class="form-control"  id="RG" name="RG" placeholder="<?=$this->lang->line('application_enter_rg')?>" autocomplete="off" value = "<?= set_value('RG', $company_data['RG']) ?>">
                                <?php echo '<i style="color:red">'.form_error('RG').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-3 show_pf <?php echo (form_error('rg_expedition_agency')) ? 'has-error' : '';  ?>">
                                <label for="rg_expedition_agency"><?=$this->lang->line('application_enter_rg_expedition_agency')?>(*)</label>
                                <input type="text" class="form-control"  id="rg_expedition_agency" name="rg_expedition_agency" placeholder="<?=$this->lang->line('application_enter_rg_expedition_agency')?>" autocomplete="off" value ="<?= set_value('rg_expedition_agency', $company_data['rg_expedition_agency']) ?>">
                                <?php echo '<i style="color:red">'.form_error('rg_expedition_agency').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 show_pf <?php echo (form_error('rg_expedition_date')) ? 'has-error' : '';  ?>">
                                <label for="rg_expedition_date"><?=$this->lang->line('application_rg_expedition_date');?>(*)</label>
                                <div class='input-group date' id='rg_expedition_date_pick' name="rg_expedition_date_pick">
                                    <input type='text' class="form-control" id='rg_expedition_date' name="rg_expedition_date" autocomplete="off" value="<?php echo set_value('rg_expedition_date', $company_data['rg_expedition_date']);?>" />
                                    <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
                                </div>
                                <?php echo '<i style="color:red">'.form_error('rg_expedition_date').'</i>'; ?>
                            </div>
                            <div class="form-group col-md-2 show_pf <?php echo (form_error('birth_date')) ? 'has-error' : '';?>">
                                <label for="birth_date"><?=$this->lang->line('application_birth_date');?>(*)</label>
                                <div class='input-group date' id='birth_date_pick' name="birth_date_pick">
                                    <input type='text' class="form-control" id='birth_date' name="birth_date" autocomplete="off" value="<?php echo set_value('birth_date', $company_data['birth_date']);?>" />
                                    <span class="input-group-addon">
		                    <span class="glyphicon glyphicon-calendar"></span>
		                </span>
                                </div>
                                <?php echo '<i style="color:red">'.form_error('birth_date').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-4 show_pf <?php echo (form_error('affiliation')) ? 'has-error' : '';  ?>">
                                <label for="affiliation"><?=$this->lang->line('application_affiliation')?>(*)</label>
                                <input type="text" class="form-control"  id="affiliation" name="affiliation" placeholder="<?=$this->lang->line('application_enter_affiliation')?>" autocomplete="off" value = "<?= set_value('affiliation', $company_data['affiliation']) ?>">
                                <?php echo '<i style="color:red">'.form_error('affiliation').'</i>'; ?>
                            </div>


                            <div class="form-group col-md-4 <?php echo (form_error('email')) ? 'has-error' : '';  ?>">
                                <label for="email"><?=$this->lang->line('application_email');?>(*)</label>
                                <input type="text" class="form-control" id="email" name="email" required placeholder="<?=$this->lang->line('application_enter_email');?>" value="<?php echo set_value('email', $company_data['email']) ?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('email').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('phone_1')) ? 'has-error' : '';  ?>">
                                <label for="phone"><?=$this->lang->line('application_phone');?> 1(*)</label>
                                <input type="text" class="form-control" id="phone_1" name="phone_1" required placeholder="<?=$this->lang->line('application_enter_phone');?>" value="<?php echo set_value('phone_1', $company_data['phone_1']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" maxlength="15">
                                <?php echo '<i style="color:red">'.form_error('phone_1').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('phone_2')) ? 'has-error' : '';  ?>">
                                <label for="phone"><?=$this->lang->line('application_phone');?> 2</label>
                                <input type="text" class="form-control" id="phone_2" name="phone_2" placeholder="<?=$this->lang->line('application_enter_phone');?>" value="<?php echo set_value('phone_2', $company_data['phone_2']) ?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" maxlength="15">
                                <?php echo '<i style="color:red">'.form_error('phone_2').'</i>';  ?>
                            </div>
                            </div>

                            <div class="row">
                            <div class="form-group col-md-2 <?php echo (form_error('zipcode')) ? 'has-error' : '';  ?>">
                                <label for="zipcode"><?=$this->lang->line('application_zip_code');?>(*)</label>
                                <input type="text" class="form-control" id="zipcode" name="zipcode" required placeholder="<?=$this->lang->line('application_enter_zipcode');?>" value="<?php echo set_value('zipcode', $company_data['zipcode']) ?>" autocomplete="off" onkeyup="this.value=this.value.replace(/[^\d]/,'')">
                                <?php echo '<i style="color:red">'.form_error('zipcode').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-8 <?php echo (form_error('address')) ? 'has-error' : '';  ?>">
                                <label for="address"><?=$this->lang->line('application_address');?>(*)</label>
                                <input type="text" class="form-control" id="address" name="address" required placeholder="<?=$this->lang->line('application_enter_address');?>" value="<?php echo set_value('address', $company_data['address']) ?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('address').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('addr_num')) ? 'has-error' : '';  ?>">
                                <label for="addr_num"><?=$this->lang->line('application_number');?>(*)</label>
                                <input type="text" class="form-control" id="addr_num" name="addr_num" required placeholder="<?=$this->lang->line('application_enter_number');?>" value="<?php echo set_value('addr_num', $company_data['addr_num']) ?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('addr_num').'</i>';  ?>
                            </div>
                            </div>

                            <div class="row">
                            <div class="form-group col-md-2 <?php echo (form_error('addr_compl')) ? 'has-error' : '';  ?>">
                                <label for="addr_compl"><?=$this->lang->line('application_complement');?></label>
                                <input type="text" class="form-control" id="addr_compl" name="addr_compl" placeholder="<?=$this->lang->line('application_enter_complement');?>" value="<?php echo set_value('addr_compl', $company_data['addr_compl']) ?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('addr_compl').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-3 <?php echo (form_error('addr_neigh')) ? 'has-error' : '';  ?>">
                                <label for="addr_neigh"><?=$this->lang->line('application_neighb');?>(*)</label>
                                <input type="text" class="form-control" id="addr_neigh" name="addr_neigh" required placeholder="<?php echo set_value('addr_neigh');?>" value="<?php echo set_value('addr_neigh', $company_data['addr_neigh']) ?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('addr_neigh').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-3 <?php echo (form_error('addr_city')) ? 'has-error' : '';  ?>">
                                <label for="addr_city"><?=$this->lang->line('application_city');?>(*)</label>
                                <input type="text" class="form-control" id="addr_city" name="addr_city" required placeholder="<?php echo set_value('addr_city');?>" value="<?php echo set_value('addr_city', $company_data['addr_city']) ?>" autocomplete="off">
                                <?php echo '<i style="color:red">'.form_error('addr_city').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('addr_uf')) ? 'has-error' : '';  ?>">
                                <label for="addr_uf"><?=$this->lang->line('application_uf');?>(*)</label>
                                <select class="form-control" id="addr_UF" name="addr_uf" required>
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($ufs as $k => $v): ?>
                                        <option value="<?php echo trim($k); ?>" <?php echo set_select('addr_uf', trim($k), trim($k) == $company_data['addr_uf']) ?>><?php echo $v ?></option>
                                    <?php endforeach ?>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('addr_uf').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-2 <?php echo (form_error('country')) ? 'has-error' : '';  ?>">
                                <label for="country"><?=$this->lang->line('application_country');?>(*)</label>
                                <select class="form-control" id="country" name="country" required>
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($paises as $k => $v): ?>
                                        <option value="<?php echo trim($k); ?>" <?php echo set_select('country', trim($k), $k==$company_data['country']) ?>><?php echo $v ?></option>
                                    <?php endforeach ?>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('country').'</i>';  ?>
                            </div>
                            </div>

                            <div class="row">
                            <div class="form-group col-md-3 hide_matriz <?php echo (form_error('bank')) ? 'has-error' : '';  ?>">
                                <label for="bank"><?=$this->lang->line('application_bank');?>(*)</label>
                                <select class="form-control" id="bank" name="bank" >
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($banks as $k => $v): ?>
                                        <option value="<?=trim($v['name'])?>" <?=set_select('bank', $v['name'], $company_data['bank'] == trim($v['name'])) ?>><?=$v['name'] ?></option>
                                    <?php endforeach ?>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('bank').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-3 hide_matriz <?php echo (form_error('agency')) ? 'has-error' : '';  ?>">
                                <label for="agency"><?=$this->lang->line('application_agency');?>(*)</label>
                                <input type="text" class="form-control" id="agency" name="agency" maxlength="10" placeholder="<?=$this->lang->line('application_enter_agency')?>" autocomplete="off" value="<?=set_value('agency',$company_data['agency'])?>" >
                                <?php echo '<i style="color:red">'.form_error('agency').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-3 hide_matriz <?php echo (form_error('account_type')) ? 'has-error' : '';  ?>">
                                <label for="currency"><?=$this->lang->line('application_type_account');?>(*)</label>
                                <select class="form-control" id="account_type" name="account_type" >
                                    <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                    <?php foreach ($type_accounts as $k => $v): ?>
                                        <option value="<?=trim($v)?>" <?= set_select('account_type', $v, $v == $company_data['account_type']) ?>><?=$v ?></option>
                                    <?php endforeach ?>
                                </select>
                                <?php echo '<i style="color:red">'.form_error('account_type').'</i>';  ?>
                            </div>
                            <div class="form-group col-md-3 hide_matriz <?php echo (form_error('account')) ? 'has-error' : '';  ?>">
                                <label for="account"><?=$this->lang->line('application_account');?>(*)</label>
                                <input type="text" class="form-control" id="account" name="account" maxlength="12" placeholder="<?=$this->lang->line('application_enter_bank_account')?>" autocomplete="off" value="<?=set_value('account',$company_data['account'])?>" onKeyPress="return digitos(event, this);">
                                <?php echo '<i style="color:red">'.form_error('account').'</i>';  ?>
                            </div>
                            </div>
                        </div>
                        <!-- /.box-body -->
                        <div class="container-fluid">
                            <h4><?=$this->lang->line('application_contacts');?></h4>
                            <div class="row">
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_finan_name')) ? 'has-error' : '';  ?>">
                                    <label for="responsible_finan_name"><?=$this->lang->line('application_responsible_finan_name');?>(*)</label>
                                    <input type="text" class="form-control" id="responsible_finan_name" name="responsible_finan_name" autocomplete="off" value="<?=set_value('responsible_finan_name',$company_data['responsible_finan_name'])?>">
                                    <?php echo '<i style="color:red">'.form_error('responsible_finan_name').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_finan_email')) ? 'has-error' : '';  ?>">
                                    <label for="responsible_finan_email"><?=$this->lang->line('application_responsible_finan_email');?>(*)</label>
                                    <input type="email" class="form-control" id="responsible_finan_email" name="responsible_finan_email" autocomplete="off" value="<?=set_value('responsible_finan_email',$company_data['responsible_finan_email'])?>">
                                    <?php echo '<i style="color:red">'.form_error('responsible_finan_email').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_finan_tell')) ? 'has-error' : '';  ?>">
                                    <label for="responsible_finan_tell"><?=$this->lang->line('application_responsible_finan_tell');?>(*)</label>
                                    <input type="text" class="form-control" id="responsible_finan_tell" name="responsible_finan_tell" autocomplete="off" value="<?=set_value('responsible_finan_tell',$company_data['responsible_finan_tell'])?>" onkeypress="return digitos(event, this);" onkeydown="Mascara('TEL',this,event);" maxlength="15"   ="Digite o telefone">
                                    <?php echo '<i style="color:red">'.form_error('responsible_finan_tell').'</i>';  ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_ti_name')) ? 'has-error' : '';  ?>">
                                    <label for="responsible_ti_name"><?=$this->lang->line('application_responsible_ti_name');?>(*)</label>
                                    <input type="text" class="form-control" id="responsible_ti_name" name="responsible_ti_name" autocomplete="off" value="<?=set_value('responsible_ti_name',$company_data['responsible_ti_name'])?>">
                                    <?php echo '<i style="color:red">'.form_error('responsible_ti_name').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_ti_email')) ? 'has-error' : '';  ?>">
                                    <label for="responsible_ti_email"><?=$this->lang->line('application_responsible_ti_email');?>(*)</label>
                                    <input type="email" class="form-control" id="responsible_ti_email" name="responsible_ti_email" autocomplete="off" value="<?=set_value('responsible_ti_email',$company_data['responsible_ti_email'])?>">
                                    <?php echo '<i style="color:red">'.form_error('responsible_ti_email').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_ti_tell')) ? 'has-error' : '';  ?>">
                                    <label for="responsible_ti_tell"><?=$this->lang->line('application_responsible_ti_tell');?>(*)</label>
                                    <input type="text" class="form-control" id="responsible_ti_tell" name="responsible_ti_tell" autocomplete="off" value="<?=set_value('responsible_ti_tell',$company_data['responsible_ti_tell'])?>" onkeypress="return digitos(event, this);" onkeydown="Mascara('TEL',this,event);" maxlength="15" placeholder="Digite o telefone">
                                    <?php echo '<i style="color:red">'.form_error('responsible_ti_tell').'</i>';  ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_sac_name')) ? 'has-error' : '';  ?>">
                                    <label for="responsible_sac_name"><?=$this->lang->line('application_responsible_sac_name');?>(*)</label>
                                    <input type="text" class="form-control" id="responsible_sac_name" name="responsible_sac_name" autocomplete="off" value="<?=set_value('responsible_sac_name',$company_data['responsible_sac_name'])?>">
                                    <?php echo '<i style="color:red">'.form_error('responsible_sac_name').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_sac_email')) ? 'has-error' : '';  ?>">
                                    <label for="responsible_sac_email"><?=$this->lang->line('application_responsible_sac_email');?>(*)</label>
                                    <input type="email" class="form-control" id="responsible_sac_email" name="responsible_sac_email" autocomplete="off" value="<?=set_value('responsible_sac_email',$company_data['responsible_sac_email'])?>">
                                    <?php echo '<i style="color:red">'.form_error('responsible_sac_email').'</i>';  ?>
                                </div>
                                <div class="form-group col-md-4 <?php echo (form_error('responsible_sac_tell')) ? 'has-error' : '';  ?>">
                                    <label for="responsible_sac_tell"><?=$this->lang->line('application_responsible_sac_tell');?>(*)</label>
                                    <input type="text" class="form-control" id="responsible_sac_tell" name="responsible_sac_tell" autocomplete="off" value="<?=set_value('responsible_sac_tell',$company_data['responsible_sac_tell'])?>" onkeypress="return digitos(event, this);" onkeydown="Mascara('TEL',this,event);" maxlength="15" placeholder="Digite o telefone">
                                    <?php echo '<i style="color:red">'.form_error('responsible_sac_tell').'</i>';  ?>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <?php if(in_array('updateCompany', $this->permission)): ?>
                            <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button>
                            <?php endif;?>
                            <a id="back_button" href="<?php echo base_url('company/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                        </div>
                    </form>

                    <div class="box">
                        <div class="box-header">
                            <h3 class="box-title"><?=$this->lang->line('application_stores');?></h3>
                        </div>
                        <div class="box-body">
                            <table id="manageTable" class="table table-bordered table-striped" cellspacing="0" style="">
                                <thead>
                                <tr>
                                    <th><?=$this->lang->line('application_id');?></th>
                                    <th><?=$this->lang->line('application_name');?></th>
                                    <th><?=$this->lang->line('application_raz_soc');?></th>
                                    <th><?=$this->lang->line('application_responsible_name');?></th>
                                    <th><?=$this->lang->line('application_responsible_email');?></th>
                                    <th><?=$this->lang->line('application_phone');?></th>
                                    <th><?=$this->lang->line('application_active');?></th>
                                </tr>
                                </thead>

                            </table>
                        </div>
                    </div>

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

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
    var manageTable;
    var base_url = "<?php echo base_url(); ?>";
    var banks = <?php echo json_encode($banks); ?>;
    var usar_mascara_banco = "<?php echo $usar_mascara_banco ?>";       

    

    $(document).ready(function() {
        $("#mainCompanyNav").addClass('active');
        $("#manageCompanyNav").addClass('active');
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
        
        
        var btnCust = '<button type="button" class="btn btn-secondary" title="Add picture tags" ' +
            'onclick="alert(\'Call your custom code here.\')">' +
            '<i class="glyphicon glyphicon-tag"></i>' +
            '</button>';
        $("#company_image").fileinput({
            overwriteInitial: true,
            maxFileSize: 1500,
            showClose: false,
            showCaption: false,
            browseLabel: '',
            removeLabel: '',
            browseIcon: '<i class="glyphicon glyphicon-folder-open"></i>',
            removeIcon: '<i class="glyphicon glyphicon-remove"></i>',
            removeTitle: 'Cancel or reset changes',
            elErrorContainer: '#kv-avatar-errors-1',
            msgErrorClass: 'alert alert-block alert-danger',
            // defaultPreviewContent: '<img src="/uploads/default_avatar_male.jpg" alt="Your Avatar">',
            layoutTemplates: {main2: '{preview} {remove} {browse}'},
            allowedFileExtensions: ["jpg", "png", "gif"]
        });

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
            $(".show_pf").show();
            $(".show_pj").hide();
        } else {
            $(".show_pj").show();
            $(".show_pf").hide();
        }

        if ($('#associate_type_pj option:selected').val() == "0")  {
            $(".hide_matriz").hide();
        } else {
            $(".hide_matriz").show();
        }
        toggleRequiredField($('#pj_pf option:selected').val());

        /*if ($('#exempted')[0].hasAttribute('checked')) {
            $('#IEST').attr({'disabled': true, 'required': false})
        } else {
            if ($('#pj_pf').val() === 'PJ') $('#IEST').attr({'disabled': false, 'required': true})
        }*/

        exemptIM();
        exemptIE();

        manageTable = $('#manageTable').DataTable({
            "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"	 },
            "scrollX": true,
            'ajax': base_url + 'company/fetchCompaniesStores/<?=$company_data['id']; ?>',
            'company': []
        });
        if(<?=!in_array('updateCompany', $this->permission)? 'true' : 'false' ?>){
            $('form input[type=checkbox]')
                .attr("onclick", "return false;");
            $('form input')
            .attr("readonly",<?=!in_array('updateCompany', $this->permission)?'true':'false' ?>);
            $('form select')
                .attr("disabled", <?=!in_array('updateCompany', $this->permission)?'true':'false' ?>);
            $('form textarea')
                .attr("disabled", <?=!in_array('updateCompany', $this->permission)?'true':'false' ?>);
        }
        $('#print_button').on('click',function(event){
            event.preventDefault();
            window.print();
        })
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
    $("#associate_type_pj").change(function () {
        if ($('#associate_type_pj option:selected').val() == "0")  {
            $(".hide_matriz").hide();
        } else {
            $(".hide_matriz").show();
        }
    });

    $("#pj_pf").change(function () {
        if ($('#pj_pf option:selected').val() == "PF")  {
            $(".show_pf").show();
            $(".hide_matriz").show();
            $(".show_pj").hide();
        } else {
            $(".show_pj").show();
            if ($('#associate_type_pj option:selected').val() == "0")  {
                $(".hide_matriz").hide();
            } else {
                $(".hide_matriz").show();
            }
            $(".show_pf").hide();
        }

        toggleRequiredField($('#pj_pf option:selected').val());
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
<style>
    @media print{
        body {
            zoom: .8
        }
        .form-control{
            border: 1px solid #fff;
        }
        button,#back_button{
            visibility: hidden;
        }
        a[href]:after {
            content: none
        }
        .globalClass_ebef{
            visibility: hidden !important;
        }
        #manageTable_length,#manageTable_filter{
            visibility: hidden;
            display: none;
        }
        @page { size: landscape; }
        .logo-lg>img{
            width: 450px;
            height: 150px;
        }
        #logo_view{
            visibility: hidden;
        }
        select {
            appearance: none;
        }
        .col-md-1 {width:8%;  float:left;}
        .col-md-2 {width:16%; float:left;}
        .col-md-3 {width:25%; float:left;}
        .col-md-4 {width:33%; float:left;}
        .col-md-5 {width:42%; float:left;}
        .col-md-6 {width:50%; float:left;}
        .col-md-7 {width:58%; float:left;}
        .col-md-8 {width:66%; float:left;}
        .col-md-9 {width:75%; float:left;}
        .col-md-10{width:83%; float:left;}
        .col-md-11{width:92%; float:left;}
        .col-md-12{width:100%; float:left;}
        ::-webkit-input-placeholder { /* WebKit browsers */
            color: transparent;
        }
        :-moz-placeholder { /* Mozilla Firefox 4 to 18 */
            color: transparent;
        }
        ::-moz-placeholder { /* Mozilla Firefox 19+ */
            color: transparent;
        }
        :-ms-input-placeholder { /* Internet Explorer 10+ */
            color: transparent;
        }
    }
</style>