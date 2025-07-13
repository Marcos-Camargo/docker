
  <?php include_once(APPPATH . '/third_party/zipcode.php') ?>
<!--
SW Serviços de Informática 2019

Editar Fornecedores
-->
<div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_edit"; $data['page_now'] = 'add_new_sellercenter_shipping_company';  $this->load->view('templates/content_header',$data) ?>

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

          <div class="box box-primary" id="collapseFilter">
              <form role="form" action="<?php base_url('shipping_company/update') ?>" method="post">
                  <input type="hidden" id="crcli" name="crcli" value="S">
                  <div class="box-body">
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
	                  <label for="txt_insc_estadual"><?=$this->lang->line('application_iest');?>(*)</label>
                      <input type="text" class="form-control" id="txt_insc_estadual" name="txt_insc_estadual" placeholder="<?=$this->lang->line('application_iest')?>" autocomplete="off" value="<?=set_value('txt_insc_estadual', $fields['insc_estadual'])?>">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" name="exempted" onchange="exemptIE()" id="exempted" <?=set_checkbox('exempted', '1', $fields['insc_estadual'] == 0) ?>>
                        <label class="form-check-label" for="exempted">
                          <?= $this->lang->line('application_exempted'); ?>
                        </label>
                      </div>
	                </div>
                      </div>
                        <div class="row">
<!--                            <div class="col-md-5">-->
<!--                                <label for="active_token_api"><?=$this->lang->line('application_token');?></label>-->
<!--                                    <br />-->
<!--                                <div class="input-group">-->
<!--                                    <span class="input-group-addon" style="padding: 0; border: none;" >-->
<!--                                        <input type="checkbox" id="active_token_api" name="active_token_api" <?=set_checkbox('active_token_api', 'on', $fields['active_token_api'] == 1) ?> data-toggle="toggle" data-on="<?= $fields['token_api'] ? 'Ativado' : 'Gerar' ?>" data-off="<?= $fields['token_api'] ? 'Desativado' : 'Não gerado' ?>">-->
<!--                                    </span>-->
<!--                                    <input type="text" class="form-control" id="token_api" name="token_api" readonly value="<?=set_value('token_api', $fields['token_api'])?>">-->
<!--                                    <span class="input-group-btn">-->
<!--                                        <button type="button" data-toggle="tooltip" title="Copiar" class="btn btn-primary btn-flat copy-input"><i class="fas fa-copy"></i></button>-->
<!--                                    </span>-->
<!--                                </div>-->
                                    <?php
                                    if (strlen($fields['token_api']) > 5) { ?>
<!--                                        <span class="label label-primary" data-toggle="tooltip" title="Dados complementares ao Token para acesso às APIs"><?='x-provider-key:'.str_repeat('&nbsp;', 3).$provider_data['id'].str_repeat('&nbsp;', 10).'x-email:'.str_repeat('&nbsp;', 3).$provider_data['responsible_email']?></span>-->
                                    <?php }
                                    ?>
<!--                            </div>-->
                          <div class="form-group col-md-4">
                              <label for="cnpj"><?=$this->lang->line('application_cnpj');?>(*)</label>
                              <input type="tel" class="form-control" id="cnpj" name="cnpj" required placeholder="<?=$this->lang->line('application_enter_CNPJ')?>" autocomplete="off" maxlength="18" size="18" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CNPJ',this,event);" value="<?=set_value('cnpj', $fields['cnpj'])?>">
                          </div>
                          <div class="form-group col-md-4">
                              <label for="phone"><?=$this->lang->line('application_phone');?>(*)</label>
                              <input type="tel" class="form-control" id="phone" name="phone" required placeholder="<?=$this->lang->line('application_enter_user_phone')?>" autocomplete="off" maxlength="15" size="15" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('TEL',this,event);" value="<?=set_value('phone', $fields['phone'])?>">
                          </div>
                          <div class="form-group col-md-3">
        	                  <label for="slc_tipo_provider"><?=$this->lang->line('application_providers_type');?>(*)</label>
        	                  <select class="form-control" id="slc_tipo_provider" name="slc_tipo_provider" required readonly="">
        	                    <option value="Transportadora" <?=set_select('slc_tipo_provider', "Transportadora", $fields['tipo_fornecedor'] == "Transportadora")?>>Transportadora</option>
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
                              <input type="tel" class="form-control" id="responsible_cpf" name="responsible_cpf"  placeholder="<?=$this->lang->line('application_enter_responsible_cpf')?>" autocomplete="off" maxlength="14" size="14" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" value="<?=set_value('responsible_cpf', $fields['responsible_cpf'])?>" required>
                          </div>
                      </div>

                      <div class="row">
                        <div class="form-group col-md-4">
                            <label for="ad_valorem"><?=$this->lang->line('application_ad_valorem');?></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="O Ad Valorem (%) é uma taxa adicional cobrada sobre o valor total do frete, em forma percentual. Representa o seguro da carga. O Ad Valorem será adicionado ao valor do frete: (Valor do frete x Ad Valorem) + Valor do Frete. Digite apenas números. Use ponto para separar os decimais. Ex: 10.25"></i>
                            <input type="text" class="form-control" id="ad_valorem" name="ad_valorem" placeholder="<?=$this->lang->line('application_enter_ad_valorem')?>" autocomplete="off" value="<?=set_value('ad_valorem', $fields['ad_valorem'])?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="gris"><?=$this->lang->line('application_gris');?></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="O GRIS (%) é uma taxa adicional cobrada sobre o valor total do frete, em forma percentual. Funciona como um gerenciamento de risco contra roubos de cargas. O GRIS será adicionado ao valor do frete: (Valor do frete x GRIS) + Valor do Frete. Digite apenas números. Use ponto para separar os decimais. Ex: 10.25"></i>
                            <input type="text" class="form-control" id="gris" name="gris" placeholder="<?=$this->lang->line('application_enter_gris')?>" autocomplete="off" value="<?=set_value('gris', $fields['gris'])?>">
                        </div>				
                        <div class="form-group col-md-4">
                            <label for="toll"><?=$this->lang->line('application_toll');?></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="O pedágio (R$) será adicionado ao valor do frete a cada 100kg do produto. Ex: Para um produto de 220Kg adicionaremos três vezes o valor do pedágio, ao valor final do frete. Digite apenas números. Use ponto para separar os decimais. Ex: 10.25"></i>
                            <input type="text" class="form-control" id="toll" name="toll"  placeholder="<?=$this->lang->line('application_enter_toll')?>" autocomplete="off" value="<?=set_value('toll', $fields['toll'])?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-4">
                            <label for="shipping_revenue"><?=$this->lang->line('application_shipping_revenue');?></label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="A Receita de Frete (%) é uma receita adicional cobrada sobre o valor total do frete, em forma percentual. Possibilita uma receita extra ao vendedor. Será adicionado ao valor do frete: (Valor do Frete x Receita de frete) + Valor do Frete. Digite apenas números. Use ponto para separar os decimais. Ex: 10.25"></i>
                            <input type="text" class="form-control" id="shipping_revenue" name="shipping_revenue" placeholder="<?=$this->lang->line('application_enter_shipping_revenue')?>" autocomplete="off" value="<?=set_value('shipping_revenue', $fields['shipping_revenue'])?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="slc_tipo_cubage"><?=$this->lang->line('application_cubic_weight');?>? (*)</label><i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="Selecione a opção 'Sim' para utilizar o cálculo de frete com o peso cubado. O peso cubado é calculado multiplicando as dimensões do produto (L x C x A) pelo Fator de Cubagem."></i>
                            <select class="form-control" id="slc_tipo_cubage" name="slc_tipo_cubage" required >
                                <option value="FreteCubadoNao" <?=set_select('slc_tipo_cubage', "FreteCubadoNao", $fields['slc_tipo_cubage'] == 'FreteCubadoNao')?>>Não</option>
                                <option value="FreteCubadoSim" <?=set_select('slc_tipo_cubage', "FreteCubadoSim", $fields['slc_tipo_cubage'] == 'FreteCubadoSim')?>>Sim</option>
                            </select>
                        </div>
                        <div id="divFretecubado">
                            <div class="form-group col-md-4">
                                <label for="cubage_factor"><?=$this->lang->line('application_cubage_factor');?> (*)</label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="O fator de cubagem é um número constante definido por cada transportadora para realizar o cálculo do peso cubado. A unidade de medida adotada para o fator de cubagem é em Kg/cm³. Use ponto para separar os decimais. Ex: 10.25"></i>
                                <input type="text" class="form-control" id="cubage_factor" name="cubage_factor" required placeholder="<?=$this->lang->line('application_enter_cubage_factor')?>" autocomplete="off" value="<?=set_value('cubage_factor', $fields['cubage_factor'])?>">
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="freight_calculation_standard">Modo de cálculo do frete</label> <i class="fas fa-info-circle" data-toggle="tooltip" data-placement="top" title="A opção 'Por volume' permite calcular o frete com base na quantidade de itens do pedido que cabem em uma embalagem. A opção 'Por peso' permite calcular o frete com base no peso máxima de itens do pedido que cabem em uma embalagem."></i>
                            <select class="form-control" id="freight_calculation_standard" name="freight_calculation_standard" required>
                                <option value="PorVolume" <?=set_select('freight_calculation_standard', 'freight_calculation_standard', $fields['freight_calculation_standard'] == 0)?>>Por volume</option>
                                <option value="PorPeso" <?=set_select('freight_calculation_standard', 'freight_calculation_standard', $fields['freight_calculation_standard'] == 1)?>>Por peso</option>
                            </select>
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
                              <label for="responsible_oper_name"><?=$this->lang->line('application_responsible_oper_name');?></label>
                              <input type="text" class="form-control" id="responsible_oper_name" name="responsible_oper_name"  placeholder="<?=$this->lang->line('application_enter_responsible_oper_name')?>" autocomplete="off" value="<?=set_value('responsible_oper_name', $fields['responsible_oper_name'])?>">
                          </div>
                          <div class="form-group col-md-4">
                              <label for="responsible_oper_email"><?=$this->lang->line('application_responsible_oper_email');?></label>
                              <input type="text" class="form-control" id="responsible_oper_email" name="responsible_oper_email"  placeholder="<?=$this->lang->line('application_enter_responsible_oper_email')?>" autocomplete="off" value="<?=set_value('responsible_oper_email', $fields['responsible_oper_email'])?>">
                          </div>
                          <div class="form-group col-md-4">
                              <label for="responsible_oper_cpf"><?=$this->lang->line('application_responsible_oper_cpf');?></label>
                              <input type="text" class="form-control" id="responsible_oper_cpf" name="responsible_oper_cpf"  placeholder="<?=$this->lang->line('application_enter_responsible_oper_cpf')?>" autocomplete="off" maxlength="14" size="14" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" value="<?=set_value('responsible_oper_cpf', $fields['responsible_oper_cpf'])?>">
                          </div>
                      </div>
                      
                      <div class="row">
                          <div class="form-group col-md-4">
                              <label for="responsible_finan_name"><?=$this->lang->line('application_responsible_finan_name');?></label>
                              <input type="text" class="form-control" id="responsible_finan_name" name="responsible_finan_name"  placeholder="<?=$this->lang->line('application_enter_responsible_finan_name')?>" autocomplete="off" value="<?=set_value('responsible_finan_name', $fields['responsible_finan_name'])?>">
                          </div>
                          <div class="form-group col-md-4">
                              <label for="responsible_finan_email"><?=$this->lang->line('application_responsible_finan_email');?></label>
                              <input type="text" class="form-control" id="responsible_finan_email" name="responsible_finan_email"  placeholder="<?=$this->lang->line('application_enter_responsible_finan_email')?>" autocomplete="off" value="<?=set_value('responsible_finan_email', $fields['responsible_finan_email'])?>">
                          </div>
                          <div class="form-group col-md-4">
                              <label for="responsible_finan_cpf"><?=$this->lang->line('application_responsible_finan_cpf');?></label>
                              <input type="text" class="form-control" id="responsible_finan_cpf" name="responsible_finan_cpf"  placeholder="<?=$this->lang->line('application_enter_responsible_finan_cpf')?>" autocomplete="off" maxlength="14" size="14" onKeyPress="return digitos(event, this);" onKeyDown="Mascara('CPF',this,event);" value="<?=set_value('responsible_finan_cpf', $fields['responsible_finan_cpf'])?>">
                          </div>
                      </div>
    					<div class="row">
                        <div class="form-group col-md-2">
                            <label for="addr_uf"><?=$this->lang->line('application_uf');?>(*)</label>
                            <select class="form-control" id="addr_uf" name="addr_uf" required>
                                <option value=""><?=$this->lang->line('application_select');?></option>
                                <?php foreach ($ufs as $k => $v): ?>
                                    <option value="<?=trim($k)?>" <?=set_select('addr_uf', trim($k), $fields['addr_uf'] == trim($k))?>><?=$v ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
    	                <div class="form-group col-md-2">
        	                  <label for="txt_regiao_entrega"><?=$this->lang->line('application_providers_region_delivery');?></label>
        	                  <input type="text" class="form-control" id="txt_regiao_entrega" name="txt_regiao_entrega" placeholder="<?=$this->lang->line('application_providers_region_delivery')?>" autocomplete="off" value="<?=set_value('txt_regiao_entrega', $fields['regiao_entrega'])?>">
        	             </div> 
        	             
        	             <div class="form-group col-md-2">
        	                  <label for="txt_regiao_coleta"><?=$this->lang->line('application_providers_region_collect');?></label>
        	                  <input type="text" class="form-control" id="txt_regiao_coleta" name="txt_regiao_coleta" placeholder="<?=$this->lang->line('application_providers_region_collect')?>" autocomplete="off" value="<?=set_value('txt_regiao_coleta', $fields['regiao_coleta'])?>">
        	             </div> 
        	             
        	             <div class="form-group col-md-3">
        	                  <label for="txt_tempo_coleta"><?=$this->lang->line('application_providers_time_collect');?></label>
        	                  <input type="text" class="form-control" id="txt_tempo_coleta" name="txt_tempo_coleta" placeholder="<?=$this->lang->line('application_providers_time_collect')?>" autocomplete="off" value="<?=set_value('txt_tempo_coleta', $fields['tempo_coleta'])?>">
        	             </div> 
        	             
        	             <div class="form-group col-md-3">
        	                  <label for="txt_fluxo_fin"><?=$this->lang->line('application_providers_finan_flow');?>(*)</label>
        	                  <input type="text" class="form-control" id="txt_fluxo_fin" name="txt_fluxo_fin" placeholder="<?=$this->lang->line('application_providers_finan_flow')?>" autocomplete="off" value="<?=set_value('txt_fluxo_fin', $fields['fluxo_fin'])?>" required>
        	             </div> 
        	        </div>
        	        
        	        <div class="row">
    	                <div class="form-group col-md-2">
    	                	<label for="slc_val_credito"><?=$this->lang->line('application_providers_credit_value');?></label>
    	                	<select class="form-control" id="slc_val_credito" name="slc_val_credito">
                                <option value="Nao" <?=set_select('slc_val_credito', "Nao", $fields['credito'] == "Nao")?>>Não</option>
                                <option value="Sim" <?=set_select('slc_val_credito', "Sim", $fields['credito'] == "Sim")?> >Sim</option>
        	                </select>
    	                </div>
    	                <div class="form-group col-md-2" id="div_txt_val_credito">
        	                  <label for="txt_val_credito"><?=$this->lang->line('application_value');?></label>
        	                  <input type="text" class="form-control" id="txt_val_credito" name="txt_val_credito" placeholder="<?=$this->lang->line('application_value')?>" autocomplete="off" value="<?=set_value('txt_val_credito', $fields['val_credito'])?>">
        	             </div> 
        	              
        	             <div class="form-group col-md-2">
    	                	<label for="slc_val_ship_min"><?=$this->lang->line('application_providers_ship_min');?></label>
    	                	<select class="form-control" id="slc_val_ship_min" name="slc_val_ship_min">
                                <option value="Nao" <?=set_select('slc_val_ship_min', "Nao", $fields['ship_min'] == "Nao")?>>Não</option>
                                <option value="Sim" <?=set_select('slc_val_ship_min', "Sim", $fields['ship_min'] == "Sim")?> >Sim</option>
        	                  </select>
    	                </div>
    	                <div class="form-group col-md-2" id="div_txt_val_ship_min">
        	                  <label for="txt_val_ship_min"><?=$this->lang->line('application_value');?></label>
        	                  <input type="text" class="form-control" id="txt_val_ship_min" name="txt_val_ship_min" placeholder="<?=$this->lang->line('application_value')?>" autocomplete="off" value="<?=set_value('txt_val_ship_min', $fields['val_ship_min'])?>">
        	             </div> 
        	             
        	             <div class="form-group col-md-2">
    	                	<label for="slc_qtd_min"><?=$this->lang->line('application_providers_qtd_min');?></label>
    	                	<select class="form-control" id="slc_qtd_min" name="slc_qtd_min">
                                <option value="Nao" <?=set_select('slc_qtd_min', "Nao", $fields['qtd_min'] == "Nao")?>>Não</option>
                                <option value="Sim" <?=set_select('slc_qtd_min', "Sim", $fields['qtd_min'] == "Sim")?> >Sim</option>
        	                  </select>
    	                </div>
    	                <div class="form-group col-md-2" id="div_txt_qtd_min">
        	                  <label for="txt_qtd_min"><?=$this->lang->line('application_value');?></label>
        	                  <input type="text" class="form-control" id="txt_qtd_min" name="txt_qtd_min" placeholder="<?=$this->lang->line('application_value')?>" autocomplete="off" value="<?=set_value('txt_qtd_min', $fields['val_qtd_min'])?>">
        	             </div>
                        <div class="form-group col-md-3">
                            <label for="slc_tipo_pagamento"><?=$this->lang->line('application_billet_type_payment');?>(*)</label>
                            <select class="form-control" id="slc_tipo_pagamento" name="slc_tipo_pagamento" required>
                                <option value="Boleto" <?=set_select('slc_tipo_pagamento', "Boleto", $fields['tipo_pagamento'] == "Boleto")?>>Boleto</option>
                                <option value="Transferencia" <?=set_select('slc_tipo_pagamento', "Transferencia", $fields['tipo_pagamento'] == "Transferencia")?>>Transferência Bancária</option>
                            </select>
                        </div>
    	                
	           		</div>

				</div>
				
				<div id="divTransferencia">
                      
                      <div class="row">
                      <div class="form-group col-md-3 <?php echo (form_error('bank')) ? 'has-error' : '';  ?>">
                            <label for="bank"><?=$this->lang->line('application_bank');?>(*)</label>
                            <select class="form-control" id="bank" name="bank" >
                                <option selected disabled value=""><?=$this->lang->line('application_select');?></option>
                                <?php foreach ($banks as $k => $v): ?>
                                    <option value="<?=trim($v['name'])?>" <?=set_select('bank', $v['name'], $v['name'] == $fields['bank'])?>><?=$v['name'] ?></option>
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
    	                  	<label for="txt_tipo_produto"><?=$this->lang->line('application_providers_product_type');?></label>
              				<textarea class="form-control" id="txt_tipo_produto" name="txt_tipo_produto" placeholder="<?=$this->lang->line('application_providers_product_type');?>"><?=set_value('txt_tipo_produto', $fields['tipo_produto'])?></textarea>
    	                </div>
    				</div> <!-- row -->
    				  
    				<div class="row">
    	                <div class="form-group col-md-12">
    	                  	<label for="txt_observacao"><?=$this->lang->line('application_extract_obs');?></label>
              				<textarea class="form-control" id="txt_observacao" name="txt_observacao" placeholder="<?=$this->lang->line('application_extract_obs');?>" ><?=set_value('txt_observacao', $fields['observacao'])?></textarea>
    	                </div>
    				</div>
                    <div class="row">
                      <div class="form-group col-md-3">
                          <label for="freight_seller"><?=$this->lang->line('application_type_contract');?>(*)</label>
                          <select class="form-control select2" name="freight_seller" id="freight_seller" required>
                              <option value="0" <?=set_select('freight_seller', "0", $fields['freight_seller'] == 0)?>><?=$this->lang->line('application_sellercenter');?></option>
                              <option value="1" <?=set_select('freight_seller', "1", $fields['freight_seller'] == 1)?>><?=$this->lang->line('application_seller_l');?></option>
                          </select>
                      </div>
                      <div class="contract_1">
                          <div class="form-group col-md-3">
                              <label for="slc_company"><?=$this->lang->line('application_company');?>(*)</label>
                              <select class="form-control select2" name="slc_company" id="slc_company">
                                  <option value=""></option>
                                  <?php foreach($company_list as $key => $value) { ?>
                                      <option value="<?php echo $value['id'];?>" <?=set_select('slc_company', $value['id'], $fields['company_id'] == $value['id'])?>><?php echo $value['name'];?></option>
                                  <?php } ?>
                              </select>
                          </div>
                          <div class="form-group col-md-3">
                              <label for="slc_store" class="d-flex justify-content-between"><?=$this->lang->line('application_store');?>(*)</label>
                              <select class="form-control select2" name="slc_store" id="slc_store" >
                                  <option value=""></option>
                              </select>
                          </div>
                      </div>
                      <div class="contract_0">
                          <div class="form-group col-md-6">
                              <label for="stores_sellercenter"><?=$this->lang->line('application_stores');?>(*)</label>
                              <select class="form-control selectpicker show-tick" id="stores_sellercenter" name ="stores_sellercenter[]" data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 1" title="<?=$this->lang->line('application_select');?>">
                                  <?php foreach ($stores as $store) { ?>
                                      <option value="<?= $store['id'] ?>" <?=set_select('stores_sellercenter', $store['id'], in_array($store['id'], $providerToSeller))?>><?= $store['name'] ?></option>
                                  <?php } ?>
                              </select>
                          </div>
                      </div>
                    </div>
                  </div>
                  <div class="box-footer">
                      <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_save');?></button>
                      <a href="<?=base_url('shippingcompany/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
                  </div>
                  <input type="hidden" name="store_old" value="<?=set_value('slc_store', $fields['store_id'])?>">
              </form>
          </div>
        </div>
      </div>
    </section>
</div>

<script type="text/javascript">

var base_url = "<?php echo base_url(); ?>";
var banks = <?php echo json_encode($banks); ?>;

$(document).ready(function() {

    if (parseInt($('[name="store_old"]').val()) !== 0) {
        getStoreForCompany($('#slc_company').val(), $('[name="store_old"]').val());
    }

    exemptIE();

    $('#slc_company, #slc_store').select2();

    $('[data-toggle="tooltip"]').tooltip();

    $("#slc_tipo_cubage").trigger('change');

	$("#slc_tipo_provider").trigger('change');

	$("#slc_tipo_pagamento").trigger('change');

	$("#slc_val_credito").trigger('change');

	$("#slc_val_ship_min").trigger('change');

	$("#slc_qtd_min").trigger('change');

    $('input[type="checkbox"].minimal').iCheck({
        checkboxClass: 'icheckbox_minimal-blue',
        radioClass   : 'iradio_minimal-blue'
    });

    $("#mainLogisticsNav").addClass('active');
    $("#manageProvidersNav").addClass('active');

    $('#freight_seller').trigger('change');

    $("#bank").change(function () {
        $('#agency').val('');
        $('#account').val('');
        var bank_name = $('#bank option:selected').val();

        applyBankMask(bank_name);
    });
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






$("#cubage_factor").blur(function(){
    var value = $(this).val();
    if(value == "NaN" || value == "0.00" ){
        $('#cubage_factor').val("");
    }
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

$("#slc_tipo_pagamento").change(function(){
    if ( $("#slc_tipo_pagamento").val() == "Transferencia") {
        $("#divTransferencia").show();
    }else{
        $("#divTransferencia").hide();
    }
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
    if ( $("#slc_tipo_cubage").val() == "FreteCubadoSim") {
        $("#divFretecubado").show();
        $("#cubage_factor").attr("required", "req");
    }else{
        $("#divFretecubado").hide();

        $("#cubage_factor").removeAttr("required", "req");
        $("#cubage_factor").val("");
    }
});

$("#cubage_factor").on("change",function(){
    $(this).val(parseFloat($(this).val()).toFixed(2));
});

$("#ad_valorem").on("change",function(){
    $(this).val(parseFloat($(this).val()).toFixed(2));
});

$("#gris").on("change",function(){
    $(this).val(parseFloat($(this).val()).toFixed(2));
});

$("#toll").on("change",function(){
    $(this).val(parseFloat($(this).val()).toFixed(2));
});

$("#shipping_revenue").on("change",function(){
    $(this).val(parseFloat($(this).val()).toFixed(2));
});

$('#slc_company').on('change', function() {

    const companyId = parseInt($(this).val());
    $('#slc_store').empty();

    if (isNaN(companyId) || companyId === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Selecione uma empresa',
            showCancelButton: false,
            confirmButtonText: "Ok",
        });
        return false;
    }

    getStoreForCompany(companyId);
});

function getStoreForCompany(companyId, storeId = 0) {
    let contentStore = $('#slc_store');

    contentStore.prop('disabled', true);
    contentStore.closest('div').find('label').append('<span>Carregando <i class="fa fa-spin fa-spinner"></i></span>');

    $.ajax({
        url: `${base_url}/shippingcompany/getStoreForCompany`,
        type: "POST",
        data: { companyId },
        success: function(response) {
            const obj = JSON.parse(response);
            let selected = '';

            contentStore.empty();
            contentStore.append('<option value="" ></option>');
            $.each(obj, function (key, entry) {
                selected = '';
                if (storeId == entry.id) {
                    selected = 'selected';
                }

                contentStore.append($(`<option ${selected}></option>`).attr('value', entry.id).text(entry.name));
            });
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR,textStatus, errorThrown);
        }
    }).always(function() {
        contentStore.prop('disabled', false);
        contentStore.closest('div').find('label span').remove();
    });
}

function exemptIE() {
    if ($('#exempted').is(':checked')) {
        $('#txt_insc_estadual').prop('disabled', true).prop('required', false);
    } else {
        $('#txt_insc_estadual').prop('disabled', false).prop('required', true);
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
$('#freight_seller').on('change', function(){
    const contract = parseInt($(this).val());

    $('div[class*="contract_"]').hide().find('select').prop('required', false);
    if (!isNaN(contract)) {
        $(`.contract_${contract}`).show().find('select').prop('required', true);
    }
});
</script>

