
  <?php include_once(APPPATH . '/third_party/zipcode.php') ?>
<!--
SW Serviços de Informática 2019

Editar Fornecedores
-->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data) ?>

    <section class="content">
      <div class="row">
        <div class="col-md-12 col-xs-12">
          
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

          <div class="box">
            <div class="box-header">
              <h3 class="box-title"><?=$this->lang->line('application_update_information');?></h3>
            </div>
              <form role="form" action="<?php base_url('shipping_company/update') ?>" method="post">
                  <input type="hidden" id="crcli" name="crcli" value="S">
                  <div class="box-body">
                      <div class="row">
                          <div class="form-group col-md-4">
                              <label for="name"><?=$this->lang->line('application_name');?></label>
                              <input type="text" class="form-control" id="name" name="name" required placeholder="<?=$this->lang->line('application_enter_user_fname')?>" autocomplete="off" value="<?=set_value('name', $fields['name'])?>">
                          </div>
                          <div class="form-group col-md-4">
                              <label for="raz_soc"><?=$this->lang->line('application_raz_soc');?></label>
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
                          <div class="form-group col-md-2">
                              <label for="cnpj"><?=$this->lang->line('application_cnpj');?></label>
                              <input type="tel" class="form-control" id="cnpj" name="cnpj" required placeholder="<?=$this->lang->line('application_enter_CNPJ')?>" autocomplete="off" maxlength="18" size="18" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CNPJ',this,event);" value="<?=set_value('cnpj', $fields['cnpj'])?>">
                          </div>
                          <div class="form-group col-md-2">
                              <label for="phone_1"><?=$this->lang->line('application_phone');?></label>
                              <input type="tel" class="form-control" id="phone" name="phone" required placeholder="<?=$this->lang->line('application_enter_user_phone')?>" autocomplete="off" maxlength="15" size="15" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" value="<?=set_value('phone', $fields['phone'])?>">
                          </div>
                          <div class="form-group col-md-2">
        	                  <label for="addr_uf"><?=$this->lang->line('application_providers_type');?></label>
        	                  <select class="form-control" id="slc_tipo_provider" name="slc_tipo_provider" required>
        	                    <option value=""><?=$this->lang->line('application_select');?></option>
        	                    <option value="Transportadora" <?=set_select('slc_tipo_provider', "Transportadora", $fields['tipo_fornecedor'] == "Transportadora")?> >Transportadora</option>
        	                    <option value="Outros" <?=set_select('slc_tipo_provider', "Outros", $fields['tipo_fornecedor'] == "Outros")?>>Outros</option>
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
                              <label for="responsible_name"><?=$this->lang->line('application_responsible_name');?></label>
                              <input type="text" class="form-control" id="responsible_name" name="responsible_name" required placeholder="<?=$this->lang->line('application_enter_responsible_name')?>" autocomplete="off" value="<?=set_value('responsible_name', $fields['responsible_name'])?>">
                          </div>
                          <div class="form-group col-md-4">
                              <label for="responsible_email"><?=$this->lang->line('application_responsible_email');?></label>
                              <input type="text" class="form-control" id="responsible_email" name="responsible_email" required placeholder="<?=$this->lang->line('application_enter_responsible_email')?>" autocomplete="off" value="<?=set_value('responsible_email', $fields['responsible_email'])?>">
                          </div>
                          <div class="form-group col-md-4">
                              <label for="responsible_cpf"><?=$this->lang->line('application_responsible_cpf');?></label>
                              <input type="tel" class="form-control" id="responsible_cpf" name="responsible_cpf"  placeholder="<?=$this->lang->line('application_enter_responsible_cpf')?>" autocomplete="off" maxlength="14" size="14" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" value="<?=set_value('responsible_cpf', $fields['responsible_cpf'])?>">
                          </div>
                      </div>
                      
                      
                      <div id="divOutros">
                          <div class="row">
                              <div class="form-group col-md-2">
                                  <label for="zipcode"><?=$this->lang->line('application_zip_code');?></label>
                                  <input type="tel" class="form-control" id="zipcode" name="zipcode"  placeholder="<?=$this->lang->line('application_enter_zipcode')?>" autocomplete="off" maxlength="9" size="8" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CEP',this,event);" value="<?=set_value('zipcode', $fields['zipcode'])?>">
                              </div>
                              <div class="form-group col-md-8">
                                  <label for="address"><?=$this->lang->line('application_address');?></label>
                                  <input type="text" class="form-control" id="address" name="address"  placeholder="<?=$this->lang->line('application_enter_address')?>" autocomplete="off" value="<?=set_value('address', $fields['address'])?>">
                              </div>
                              <div class="form-group col-md-2">
                                  <label for="addr_num"><?=$this->lang->line('application_number');?></label>
                                  <input type="text" class="form-control" id="addr_num" name="addr_num"  placeholder="<?=$this->lang->line('application_enter_number')?>r" autocomplete="off" value="<?=set_value('addr_num', $fields['addr_num'])?>">
                              </div>
                          </div>
                          <div class="row">
                              <div class="form-group col-md-4">
                                  <label for="addr_compl"><?=$this->lang->line('application_complement');?></label>
                                  <input type="text" class="form-control" id="addr_compl" name="addr_compl" placeholder="<?=$this->lang->line('application_enter_complement')?>" autocomplete="off" value="<?=set_value('addr_compl', $fields['addr_compl'])?>">
                              </div>
                              <div class="form-group col-md-3">
                                  <label for="addr_neigh"><?=$this->lang->line('application_neighb');?></label>
                                  <input type="text" class="form-control" id="addr_neigh" name="addr_neigh"  placeholder="<?=$this->lang->line('application_enter_neighborhood')?>" autocomplete="off" value="<?=set_value('addr_neigh', $fields['addr_neigh'])?>">
                              </div>
                              <div class="form-group col-md-3">
                                  <label for="addr_city"><?=$this->lang->line('application_city');?></label>
                                  <input type="text" class="form-control" id="addr_city" name="addr_city"  placeholder="<?=$this->lang->line('application_enter_city')?>" autocomplete="off" value="<?=set_value('addr_city', $fields['addr_city'])?>">
                              </div>
                              <div class="form-group col-md-2">
                                  <label for="addr_uf"><?=$this->lang->line('application_uf');?></label>
                                  <select class="form-control" id="addr_UF" name="addr_uf" >
                                      <option value=""><?=$this->lang->line('application_select');?></option>
                                      <?php foreach ($ufs as $k => $v): ?>
                                          <option value="<?=trim($k)?>" <?=set_select('addr_uf', trim($k), $fields['addr_uf'] == trim($k))?>><?=$v ?></option>
                                      <?php endforeach ?>
                                  </select>
                              </div>
                          </div>
                      </div>
                      
                      
                      <div id="divTransportadora">
                      	<div class="row">
                      	  <div class="form-group col-md-12">
                              <label for="tracking_web_site"><?=$this->lang->line('application_tracking_web_site');?></label>
                              <input type="url" class="form-control" id="tracking_web_site" name="tracking_web_site"  placeholder="<?=$this->lang->line('application_enter_tracking_web_site')?>" autocomplete="off" value="<?=set_value('tracking_web_site', $fields['tracking_web_site'])?>">
                          </div>
                      	</div>
						<div class="row">
                          <div class="form-group col-md-4">
                              <label for="responsible_name"><?=$this->lang->line('application_responsible_oper_name');?></label>
                              <input type="text" class="form-control" id="responsible_oper_name" name="responsible_oper_name"  placeholder="<?=$this->lang->line('application_enter_responsible_oper_name')?>" autocomplete="off" value="<?=set_value('responsible_oper_name', $fields['responsible_oper_name'])?>">
                          </div>
                          <div class="form-group col-md-4">
                              <label for="responsible_email"><?=$this->lang->line('application_responsible_oper_email');?></label>
                              <input type="text" class="form-control" id="responsible_oper_email" name="responsible_oper_email"  placeholder="<?=$this->lang->line('application_enter_responsible_oper_email')?>" autocomplete="off" value="<?=set_value('responsible_oper_email', $fields['responsible_oper_email'])?>">
                          </div>
                          <div class="form-group col-md-4">
                              <label for="responsible_cpf"><?=$this->lang->line('application_responsible_oper_cpf');?></label>
                              <input type="text" class="form-control" id="responsible_oper_cpf" name="responsible_oper_cpf"  placeholder="<?=$this->lang->line('application_enter_responsible_oper_cpf')?>" autocomplete="off" maxlength="14" size="14" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" value="<?=set_value('responsible_oper_cpf', $fields['responsible_oper_cpf'])?>">
                          </div>
                      </div>
                      
                      <div class="row">
                          <div class="form-group col-md-4">
                              <label for="responsible_name"><?=$this->lang->line('application_responsible_finan_name');?></label>
                              <input type="text" class="form-control" id="responsible_finan_name" name="responsible_finan_name"  placeholder="<?=$this->lang->line('application_enter_responsible_finan_name')?>" autocomplete="off" value="<?=set_value('responsible_finan_name', $fields['responsible_finan_name'])?>">
                          </div>
                          <div class="form-group col-md-4">
                              <label for="responsible_email"><?=$this->lang->line('application_responsible_finan_email');?></label>
                              <input type="text" class="form-control" id="responsible_finan_email" name="responsible_finan_email"  placeholder="<?=$this->lang->line('application_enter_responsible_finan_email')?>" autocomplete="off" value="<?=set_value('responsible_finan_email', $fields['responsible_finan_email'])?>">
                          </div>
                          <div class="form-group col-md-4">
                              <label for="responsible_cpf"><?=$this->lang->line('application_responsible_finan_cpf');?></label>
                              <input type="text" class="form-control" id="responsible_finan_cpf" name="responsible_finan_cpf"  placeholder="<?=$this->lang->line('application_enter_responsible_finan_cpf')?>" autocomplete="off" maxlength="14" size="14" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" value="<?=set_value('responsible_finan_cpf', $fields['responsible_finan_cpf'])?>">
                          </div>
                      </div>
    					<div class="row">
                        <div class="form-group col-md-2">
                            <label for="addr_uf"><?=$this->lang->line('application_uf');?></label>
                            <select class="form-control" id="addr_UF" name="addr_uf" >
                                <option value=""><?=$this->lang->line('application_select');?></option>
                                <?php foreach ($ufs as $k => $v): ?>
                                    <option value="<?=trim($k)?>" <?=set_select('addr_uf', trim($k), $fields['addr_uf'] == trim($k))?>><?=$v ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
    	                <div class="form-group col-md-2">
        	                  <label for="account"><?=$this->lang->line('application_providers_region_delivery');?></label>
        	                  <input type="text" class="form-control" id="txt_regiao_entrega" name="txt_regiao_entrega" placeholder="<?=$this->lang->line('application_providers_region_delivery')?>" autocomplete="off" value="<?=set_value('txt_regiao_entrega', $fields['regiao_entrega'])?>">
        	             </div> 
        	             
        	             <div class="form-group col-md-2">
        	                  <label for="account"><?=$this->lang->line('application_providers_region_collect');?></label>
        	                  <input type="text" class="form-control" id="txt_regiao_coleta" name="txt_regiao_coleta" placeholder="<?=$this->lang->line('application_providers_region_collect')?>" autocomplete="off" value="<?=set_value('regiao_coleta', $fields['regiao_coleta'])?>">
        	             </div> 
        	             
        	             <div class="form-group col-md-3">
        	                  <label for="account"><?=$this->lang->line('application_providers_time_collect');?></label>
        	                  <input type="text" class="form-control" id="txt_tempo_coleta" name="txt_tempo_coleta" placeholder="<?=$this->lang->line('application_providers_time_collect')?>" autocomplete="off" value="<?=set_value('tempo_coleta', $fields['tempo_coleta'])?>">
        	             </div> 
        	             
        	             <div class="form-group col-md-3">
        	                  <label for="account"><?=$this->lang->line('application_providers_finan_flow');?></label>
        	                  <input type="text" class="form-control" id="txt_fluxo_fin" name="txt_fluxo_fin" placeholder="<?=$this->lang->line('application_providers_finan_flow')?>" autocomplete="off" value="<?=set_value('fluxo_fin', $fields['fluxo_fin'])?>">
        	             </div> 
        	        </div>
        	        
        	        <div class="row">
    	                <div class="form-group col-md-2">
    	                	<label for="bank"><?=$this->lang->line('application_providers_credit_value');?></label>
    	                	<select class="form-control" id="slc_val_credito" name="slc_val_credito" value="<?=set_value('credito')?>"> 
        	                    <option value=""><?=$this->lang->line('application_select');?></option>
        	                    <option value="Sim" <?=set_select('slc_val_credito', "val_credito", $fields['credito'] == "Sim")?> >Sim</option>
        	                    <option value="Nao" <?=set_select('slc_val_credito', "val_credito", $fields['credito'] == "Nao")?>>Não</option>
        	                  </select>
    	                </div>
    	                <div class="form-group col-md-2" id="div_txt_val_credito">
        	                  <label for="account"><?=$this->lang->line('application_value');?></label>
        	                  <input type="text" class="form-control" id="txt_val_credito" name="txt_val_credito" placeholder="<?=$this->lang->line('application_value')?>" autocomplete="off" value="<?=set_value('val_credito', $fields['val_credito'])?>">
        	             </div> 
        	              
        	             <div class="form-group col-md-2">
    	                	<label for="bank"><?=$this->lang->line('application_providers_ship_min');?></label>
    	                	<select class="form-control" id="slc_val_ship_min" name="slc_val_ship_min" value="<?=set_value('ship_min')?>"> 
        	                    <option value=""><?=$this->lang->line('application_select');?></option>
        	                    <option value="Sim" <?=set_select('slc_val_ship_min', "val_ship_min", $fields['ship_min'] == "Sim")?> >Sim</option>
        	                    <option value="Nao" <?=set_select('slc_val_ship_min', "val_ship_min", $fields['ship_min'] == "Nao")?>>Não</option>
        	                  </select>
    	                </div>
    	                <div class="form-group col-md-2" id="div_txt_val_ship_min">
        	                  <label for="txt_val_ship_min"><?=$this->lang->line('application_value');?></label>
        	                  <input type="text" class="form-control" id="txt_val_ship_min" name="txt_val_ship_min" placeholder="<?=$this->lang->line('application_value')?>" autocomplete="off" value="<?=set_value('val_ship_min', $fields['val_ship_min'])?>">
        	             </div> 
        	             
        	             <div class="form-group col-md-2">
    	                	<label for="slc_qtd_min"><?=$this->lang->line('application_providers_qtd_min');?></label>
    	                	<select class="form-control" id="slc_qtd_min" name="slc_qtd_min" value="<?=set_value('qtd_min')?>"> 
        	                    <option value=""><?=$this->lang->line('application_select');?></option>
        	                    <option value="Sim" <?=set_select('slc_qtd_min', "val_qtd_min", $fields['qtd_min'] == "Sim")?> >Sim</option>
        	                    <option value="Nao" <?=set_select('slc_qtd_min', "val_qtd_min", $fields['qtd_min'] == "Nao")?>>Não</option>
        	                  </select>
    	                </div>
    	                <div class="form-group col-md-2" id="div_txt_qtd_min">
        	                  <label for="txt_qtd_min"><?=$this->lang->line('application_value');?></label>
        	                  <input type="text" class="form-control" id="txt_qtd_min" name="txt_qtd_min" placeholder="<?=$this->lang->line('application_value')?>" autocomplete="off" value="<?=set_value('val_qtd_min', $fields['val_qtd_min'])?>">
        	             </div> 
    	                
	           		</div>

				</div>
				
				<div class="row">
	                <div class="form-group col-md-3">
	                	<label for="slc_tipo_pagamento"><?=$this->lang->line('application_billet_type_payment');?></label>
	                	<select class="form-control" id="slc_tipo_pagamento" name="slc_tipo_pagamento" value="<?=set_value('tipo_pagamento')?>"> 
    	                    <option value=""><?=$this->lang->line('application_select');?></option>
    	                    <option value="Boleto" <?=set_select('slc_tipo_pagamento', "val_tipo_pagamento", $fields['tipo_pagamento'] == "Boleto")?>>Boleto</option>
    	                    <option value="Transferencia" <?=set_select('slc_tipo_pagamento', "val_tipo_pagamento", $fields['tipo_pagamento'] == "Transferencia")?>>Transferência Bancária</option>
    	                  </select>
	                </div>
                    <div class="form-group col-md-3">
						<label for="addr_uf">Empresa.</label>                                 
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
	                <div class="form-group col-md-3">
						<label for="store"><?=$this->lang->line('application_store');?></label>
						<select class="form-control" name="slc_store" id="slc_store" required >
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
                        <div class="form-group col-md-3 <?php echo (form_error('bank')) ? 'has-error' : '';  ?>">
                            <label for="bank"><?=$this->lang->line('application_bank');?>(*)</label>
                            <select class="form-control" id="bank" name="bank" >
                                <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                <?php foreach ($banks as $k => $v): ?>
                                    <option value="<?=trim($v['name'])?>" <?=set_select('bank', $v['name'])?>><?=$v['name'] ?></option>
                                <?php endforeach ?>
                            </select>
                            <?php echo '<i style="color:red">'.form_error('bank').'</i>';  ?>
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
    	                <div class="form-group col-md-12" id="div_txt_tipo_produto">
    	                  	<label for="group_name"><?=$this->lang->line('application_providers_product_type');?></label>
              				<textarea class="form-control" id="txt_tipo_produto" name="txt_tipo_produto" placeholder="<?=$this->lang->line('application_providers_product_type');?>"><?=set_value('txt_tipo_produto', $fields['tipo_produto'])?></textarea>
    	                </div>
    				</div> <!-- row -->
    				  
    				<div class="row">
    	                <div class="form-group col-md-12">
    	                  	<label for="group_name"><?=$this->lang->line('application_extract_obs');?></label>
              				<textarea class="form-control" id="txt_observacao" name="txt_observacao" placeholder="<?=$this->lang->line('application_extract_obs');?>" ><?=set_value('"txt_observacao"', $fields['observacao'])?></textarea>
    	                </div>
    				</div> <!-- row -->
                      
                      
                  </div>
                  <div class="box-footer">
                      <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                      <a href="<?=base_url('shippingcompany/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                  </div>
              </form>
          </div>
        </div>
      </div>
    </section>
</div>

<script type="text/javascript">

var banks = <?php echo json_encode($banks); ?>;

$(document).ready(function() {

	if ( $("#slc_tipo_provider").val() == "Transportadora") {
		$("#divTransportadora").show();
		$("#div_txt_tipo_produto").show();
		$("#divOutros").hide();

		$("#slc_tipo_pagamento").attr("required", "req");
		$("#slc_qtd_min").attr("required", "req");
		$("#slc_val_ship_min").attr("required", "req");
		$("#txt_regiao_coleta").attr("required", "req");
		$("#txt_fluxo_fin").attr("required", "req");
		
	}else{
		$("#divTransportadora").hide();
		$("#div_txt_tipo_produto").hide();
		$("#divOutros").show();

		$("#slc_tipo_pagamento").removeAttr('required');
		$("#slc_qtd_min").removeAttr('required');
		$("#slc_val_ship_min").removeAttr('required');
		$("#txt_regiao_coleta").removeAttr('required');
		$("#txt_fluxo_fin").removeAttr('required');
	}

	$("#slc_tipo_provider").change(function(){
		if ( $("#slc_tipo_provider").val() == "Transportadora") {
			$("#divTransportadora").show();
			$("#div_txt_tipo_produto").show();
			$("#divOutros").hide();

			$("#slc_tipo_pagamento").attr("required", "req");
			$("#slc_qtd_min").attr("required", "req");
			$("#slc_val_ship_min").attr("required", "req");
			$("#txt_regiao_coleta").attr("required", "req");
			$("#txt_fluxo_fin").attr("required", "req");
			
		}else{
			$("#divTransportadora").hide();
			$("#div_txt_tipo_produto").hide();
			$("#divOutros").show();

			$("#slc_tipo_pagamento").removeAttr('required');
			$("#slc_qtd_min").removeAttr('required');
			$("#slc_val_ship_min").removeAttr('required');
			$("#txt_regiao_coleta").removeAttr('required');
			$("#txt_fluxo_fin").removeAttr('required');
		}
	});


	if ( $("#slc_tipo_pagamento").val() == "Transferencia") {
		$("#divTransferencia").show();
	}else{
		$("#divTransferencia").hide();
	}

	$("#slc_tipo_pagamento").change(function(){
		if ( $("#slc_tipo_pagamento").val() == "Transferencia") {
			$("#divTransferencia").show();
		}else{
			$("#divTransferencia").hide();
		}
	});

	if ( $("#slc_val_credito").val() == "Sim") {
		$("#div_txt_val_credito").show();
	}else{
		$("#div_txt_val_credito").hide();
	}

	$("#slc_val_credito").change(function(){
		if ( $("#slc_val_credito").val() == "Sim") {
			$("#div_txt_val_credito").show();
		}else{
			$("#div_txt_val_credito").hide();
		}
	});

	if ( $("#slc_val_ship_min").val() == "Sim") {
		$("#div_txt_val_ship_min").show();
	}else{
		$("#div_txt_val_ship_min").hide();
	}

	$("#slc_val_ship_min").change(function(){
		if ( $("#slc_val_ship_min").val() == "Sim") {
			$("#div_txt_val_ship_min").show();
		}else{
			$("#div_txt_val_ship_min").hide();
		}
	});

	if ( $("#slc_qtd_min").val() == "Sim") {
		$("#div_txt_qtd_min").show();
	}else{
		$("#div_txt_qtd_min").hide();
	}

	$("#slc_qtd_min").change(function(){
		if ( $("#slc_qtd_min").val() == "Sim") {
			$("#div_txt_qtd_min").show();
		}else{
			$("#div_txt_qtd_min").hide();
		}
	});
	
    $("#mainLogisticsNav").addClass('active');
    $("#manageProvidersNav").addClass('active');

    $('input[type="checkbox"].minimal').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass   : 'iradio_minimal-blue'
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

    $("#bank").change(function () {
        $('#agency').val('');
        $('#account').val('');
        var bank_name = $('#bank option:selected').val();

        applyBankMask(bank_name);
    });
});

function exemptIE() {
  const ie = $('#txt_insc_estadual')[0].hasAttribute('disabled')
  if (!ie) {
    $('#txt_insc_estadual').attr('disabled', 'disabled')
  } else {
    $('#txt_insc_estadual').removeAttr('disabled')
  }
}
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
</script>

