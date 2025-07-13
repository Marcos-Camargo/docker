<style>
	a.menuhref:link {text-decoration: none}
</style>

<?php $url_active = $_SERVER["REQUEST_URI"]; ?>

<aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
      
      <!-- sidebar menu: : style can be found in sidebar.less -->
      <ul class="sidebar-menu" data-widget="tree">
        
        <li id="dashboardMainMenu">
          <a class="menuhref" href="<?php echo base_url('dashboard') ?>">
            <i class="fa fa-dashboard"></i> <span>Dashboard</span>
          </a>
        </li>

		<?php if ($tranning_url): ?>
			<li id="tranning_videos"><a class="menuhref" target="_blank" href="<?php echo $tranning_url; ?>"><i class="fas fa-graduation-cap"></i> <?=$this->lang->line('application_trainning_videos');?></a></li>
		<?php endif; ?>
		
        <?php if($user_permission): ?>

            <?php if (
                (in_array('doIntegration', $user_permission)) || 
                (in_array('createUserFreteRapido', $user_permission)) ||
                (in_array('updateTrackingOrder', $user_permission)) ||
                (in_array('viewTrackingOrder', $user_permission))
            ) : ?>

            <li class="treeview" id="mainProcessesNav">
            <a class="menuhref" href="#">
              <i class="fa fa-toggle-on"></i>
              <span><?=$this->lang->line('application_daily_process');?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">

              <?php if (in_array('viewTrackingOrder', $user_permission)) : ?>
                <li id="semFreteNav"><a class="menuhref" href="<?php echo base_url('orders/semfrete') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_freight_to_wire'); ?></a></li>
              <?php endif; ?>

              <?php if (in_array('doIntegration', $user_permission)) : ?>
              <?php if (ENVIRONMENT != 'production' && ENVIRONMENT !== 'production_x'): ?>
              <li id="envioMktNav"><a class="menuhref" href="<?php echo base_url('orders/inTransitSentToMarketplace') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_sendto_mkt');?></a></li>
              <li id="trackingMktNav"><a class="menuhref" href="<?php echo base_url('orders/trackingSentToMarketplace') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_tracking_to_mkt');?></a></li>
              <li id="freteentregueNav"><a class="menuhref" href="<?php echo base_url('orders/deliverySentToMarketplace') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_freight_delivered_mkt');?></a></li>
              <li id="cancelaMktNav"><a class="menuhref" href="<?php echo base_url('orders/cancelSentoToMarketplace') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_cancela_mkt');?></a></li>
              <?php endif; ?>
              <li id="billerModuleNav"><a class="menuhref" href="<?php echo base_url('stores/setting') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_biller_module');?></a></li>
              <li id="errorTransformationModuleNav"><a class="menuhref" href="<?php echo base_url('errorsTransformation/index') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_errors_tranformation');?></a></li>
              <li id="manageIntegrationsNav"><a class="menuhref" href="<?php echo base_url('stores/manage_integrations') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_integrations');?></a></li>
              <li id="productsNotCorreiosNav"><a class="menuhref" href="<?php echo base_url('products/productsNotCorreios') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_products_not_post_office');?></a></li>
              <li id="manageOrdersTagsAdmNav"><a class="menuhref" href="<?php echo base_url('orders/manage_tags_adm') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_tags_correios');?></a></li>
			  <li id="manageOrdersInProgressNav"><a class="menuhref" href="<?php echo base_url('orders/order_in_progress') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_order_in_progress');?></a></li>
              <li id="manageOrdersNav"><a class="menuhref" href="<?php echo base_url('orders/internal') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_orders');?></a></li>
              <li id="integrationPriceQtyNav"><a class="menuhref" href="<?php echo base_url('waitingIntegration/integrationPriceQty') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_integration_price_qty');?></a></li>
              <?php if ((in_array('viewCuration', $user_permission)) || (in_array('doProductsApproval', $user_permission)))  { ?>
              	<li class="treeview" id="curadoriaNav">
		            <a class="menuhref" href="#">
		              <i class="fa fa-glasses"></i>
		              <span>Curadoria</span>
		              <span class="pull-right-container">
		                <i class="fa fa-angle-left pull-right"></i>
		              </span>
		            </a>
	              	<ul class="treeview-menu">
	              		<?php if (in_array('viewCuration', $user_permission)) : ?>
		                	<li id="blackListNav"><a class="menuhref" href="<?php echo base_url('BlacklistWords/') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_blacklistwords')?> </a></li>
		                	<li id="whiteListNav"><a class="menuhref" href="<?php echo base_url('Whitelist/') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_whitelist')?> </a></li>
						<?php endif; ?>
						<?php if (in_array('doProductsApproval', $user_permission)) : ?>
			               <li id="productsApprovalNav"><a class="menuhref" href="<?php echo base_url('products/productsApprove') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_products_approval');?></a></li>
			            <?php endif; ?>
					</ul> 
				</li>             
              <?php } ?>
              <?php endif; ?>
              <?php if(in_array('viewLogIUGU', $user_permission)): ?>
              <li id="logIUGU"><a href="<?php echo base_url('iugu/subcontastatus') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_log_iugu_view');?></a></li>
              <li id="logIUGU"><a href="<?php echo base_url('iugu/relatoriosaque') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_iugu_withdraw');?></a></li>
              <?php endif; ?>

              <?php if (in_array('updateTrackingOrder', $user_permission)) : ?>
              <li id="chargeUpdateStatusOrder"><a class="menuhref" href="<?php echo base_url('orders/charge_update_status') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_charge_status_order'); ?></a></li>
              <?php endif; ?>

              <?php if (in_array('doIntegration', $user_permission)) : ?>
              <li id="manageReports"><a class="menuhref" href="<?php echo base_url('reports/manageReports') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_reports'); ?></a></li>
              <?php endif; ?>

              <?php if (in_array('manageProductsOmnilogicSent', $user_permission)) : ?>
              <li id="sentOmnilogic"><a class="menuhref" href="<?php echo base_url('products/sentOmnilogic') ?>"><i class="fa fa-circle-o"></i><?=$this->lang->line('application_sent_omnilogic');?></a></li>
              <?php endif; ?>

              <?php if (in_array('cleanCache', $user_permission)) : ?>
                  <li id="manageCleanCache"><a class="menuhref" href="<?php echo base_url('cache') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_clean_cache'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>	

          <?php if(in_array('createUser', $user_permission) || in_array('updateUser', $user_permission) || in_array('viewUser', $user_permission) || 
                   in_array('createExternalAuthentication', $user_permission) || in_array('updateExternalAuthentication', $user_permission) || in_array('viewExternalAuthentication', $user_permission) ): ?>
            <li class="treeview" id="mainUserNav">
            <a class="menuhref" href="#">
              <i class="fa fa-user-o"></i>
              <span><?=$this->lang->line('application_users');?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if(in_array('createUser', $user_permission)): ?>
              <li id="createUserNav"><a class="menuhref" href="<?php echo base_url('users/create') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_add_user');?></a></li>
              <?php endif; ?>

              <?php if(in_array('updateUser', $user_permission) || in_array('viewUser', $user_permission) || in_array('deleteUser', $user_permission)): ?>
              <li id="manageUserNav"><a class="menuhref" href="<?php echo base_url('users') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_users');?></a></li>
            <?php endif; ?>

              <?php if(in_array('createExternalAuthentication', $user_permission) || in_array('updateExternalAuthentication', $user_permission) || in_array('viewExternalAuthentication', $user_permission)): ?>
              <li id="manageExternalAuthenticationNav"><a class="menuhref" href="<?php echo base_url('externalAuthentication') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_externalAuthentication');?></a></li>
              <?php endif; ?>

            </ul>
          </li>
          <?php endif; ?>

          <?php if(in_array('createGroup', $user_permission) || in_array('updateGroup', $user_permission) || in_array('viewGroup', $user_permission) || in_array('deleteGroup', $user_permission)): ?>
            <li class="treeview" id="mainGroupNav">
              <a class="menuhref" href="#">
                <i class="fa fa-users"></i>
                <span><?=$this->lang->line('application_groups');?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <?php if(in_array('createGroup', $user_permission)): ?>
                  <li id="addGroupNav"><a class="menuhref" href="<?php echo base_url('groups/create') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_add_group');?></a></li>
                <?php endif; ?>
                <?php if(in_array('updateGroup', $user_permission) || in_array('viewGroup', $user_permission) || in_array('deleteGroup', $user_permission)): ?>
                <li id="manageGroupNav"><a class="menuhref" href="<?php echo base_url('groups') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_groups');?></a></li>
                <?php endif; ?>
              </ul>
            </li>
          <?php endif; ?>

          <?php if(in_array('createConfig', $user_permission) || in_array('updateConfig', $user_permission) || in_array('viewConfig', $user_permission) || in_array('deleteConfig', $user_permission)): ?>
            <li id="configNav">
              <a class="menuhref" href="<?php echo base_url('settings/') ?>">
                <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_systemconfig');?></span>
              </a>
            </li>
          <?php endif; ?>

            <?php if(in_array('viewPaymentGatewayConfig', $user_permission) ): ?>
                <li id="paymentgatewaysettingsNav">
                    <a class="menuhref" href="<?php echo base_url('paymentGatewaySettings/') ?>">
                        <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_payment_gateway_settings');?></span>
                    </a>
                </li>
            <?php endif; ?>

          <?php if(in_array('createCompany', $user_permission) || in_array('updateCompany', $user_permission) || in_array('viewCompany', $user_permission) || in_array('deleteCompany', $user_permission)): ?>
             <li class="treeview" id="mainCompanyNav">
              <a class="menuhref" href="#">
                <i class="fa fa-industry"></i>
                <span><?=$this->lang->line('application_companies');?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <?php if(in_array('createCompany', $user_permission)): ?>
                  <li id="addCompanyNav"><a class="menuhref" href="<?php echo base_url('company/create') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_add_company');?></a></li>
                <?php endif; ?>
                <?php if(in_array('updateCompany', $user_permission) || in_array('viewCompany', $user_permission) || in_array('deleteCompany', $user_permission)): ?>
                <li id="manageCompanyNav"><a class="menuhref" href="<?php echo base_url('company') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_companies');?></a></li>
                <?php endif; ?>
                <?php if(in_array('viewMerchant', $user_permission)): ?>
                  <li id="viewMerchant"><a class="menuhref" href="<?php echo base_url('merchants') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_merchant');?></a></li>
                <?php endif; ?>
              </ul>
            </li>
          <?php endif; ?>

          <?php if(in_array('createStore', $user_permission) || in_array('updateStore', $user_permission) || in_array('viewStore', $user_permission) || in_array('deleteStore', $user_permission)): ?>
            <li id="storeNav">
              <a class="menuhref" href="<?php echo base_url('stores/') ?>">
                <i class="fa fa-home"></i> <span><?=$this->lang->line('application_stores');?></span>
              </a>
            </li>
          <?php endif; ?>
            <?php if (in_array('createPhases', $user_permission) || in_array('updatePhases', $user_permission) || in_array('viewPhases', $user_permission) || in_array('deletePhases', $user_permission)) : ?>
                <li class="treeview" id="mainPhasesNav">
                    <a class="menuhref" href="#">
                        <i class="fa fa-level-up" aria-hidden="true"></i>
                        <span><?= $this->lang->line('application_phases'); ?></span>
                        <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
                    </a>
                    <ul class="treeview-menu">
                        <?php if (in_array('createPhases', $user_permission) || in_array('updatePhases', $user_permission) || in_array('viewPhases', $user_permission) || in_array('deletePhases', $user_permission)) : ?>
                            <li id="managePhasesStores"><a class="menuhref" href="<?php echo base_url('phases/managePhases') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_phases'); ?></a></li>
                        <?php endif; ?>
                        <?php if (in_array('updateStore', $user_permission) || in_array('viewStore', $user_permission)) : ?>
                            <li id="managePhasesNav"><a class="menuhref" href="<?php echo base_url('phases') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_phases_store'); ?></a></li>
                        <?php endif; ?>
                        <?php if (in_array('createPhases', $user_permission)) : ?>
                            <li id="importByCSVPhasesNav"><a class="menuhref" href="<?php echo base_url('phases/import') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_phases_store_by_csv'); ?></a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>
          <?php if(in_array('createBank', $user_permission) || in_array('updateBank', $user_permission) || in_array('viewBank', $user_permission) || in_array('deleteBank', $user_permission)): ?>
            <li id="bankNav">
              <a class="menuhref" href="<?php echo base_url('banks/') ?>">
                <i class="fas fa-dollar-sign"></i> <span><?=$this->lang->line('application_Banks');?></span>
              </a>
            </li>
          <?php endif; ?>
          <?php if(in_array('createBrand', $user_permission) || in_array('updateBrand', $user_permission) || in_array('viewBrand', $user_permission) || in_array('deleteBrand', $user_permission)): ?>
            <li id="brandNav">
              <a class="menuhref" href="<?php echo base_url('brands/') ?>">
                <i class="glyphicon glyphicon-tags"></i> <span><?=$this->lang->line('application_brands');?></span>
              </a>
            </li>
          <?php endif; ?>

          <?php if(
                  in_array('createCategory', $user_permission) ||
                  in_array('updateCategory', $user_permission) ||
                  in_array('viewCategory', $user_permission) ||
                  in_array('deleteCategory', $user_permission) ||
                  $this->data['only_admin']
            ): ?>
            <li class="treeview" id="mainCategoryNav">
              <a class="menuhref" href="#">
                <i class="fa fa-list"></i>
                <span><?=$this->lang->line('application_categories');?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <?php if(in_array('updateCompany', $user_permission) || in_array('viewCompany', $user_permission) || in_array('deleteCompany', $user_permission)): ?>
                <li id="manageCategoryNav"><a class="menuhref" href="<?php echo base_url('category/') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_categories');?></a></li>
                <?php endif; ?>
                <?php if($this->data['only_admin']): ?>
                  <li id="changeProductCategoryNav"><a class="menuhref" href="<?php echo base_url('Category/changeProductCategory') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_import_change_product_category_csv');?></a></li>
                <?php endif; ?>
              </ul>
            </li>       
          <?php endif; ?>

          <?php if(in_array('createAttribute', $user_permission) || in_array('updateAttribute', $user_permission) || in_array('viewAttribute', $user_permission) || in_array('deleteAttribute', $user_permission)): ?>
          <li id="attributeNav">
            <a class="menuhref" href="<?php echo base_url('attributes/') ?>">
              <i class="fa fa-object-group"></i> <span><?=$this->lang->line('application_attributes');?></span>
            </a>
          </li>
          <?php endif; ?>
   			
		  <?php if( in_array('createParamktplace', $user_permission) || 
                    in_array('updateParamktplace', $user_permission) || 
                    in_array('viewParammktplace', $user_permission) || 
                    in_array('deleteParamktplace', $user_permission) ||
                    in_array('createBillet', $user_permission) ||
                    in_array('updateBillet', $user_permission) ||
                    in_array('viewBillet', $user_permission) ||
                    in_array('deleteBillet', $user_permission) ||
                    in_array('createPayment', $user_permission) ||
                    in_array('updatePayment', $user_permission) ||
                    in_array('viewPayment', $user_permission) ||
                    in_array('deletePayment', $user_permission) ||
                    in_array('createExtract', $user_permission) ||
                    in_array('updateExtract', $user_permission) ||
                    in_array('viewExtract', $user_permission) ||
                    in_array('deleteExtract', $user_permission) ||
                    in_array('createPaymentForecast', $user_permission) ||
                    in_array('updatePaymentForecast', $user_permission) ||
                    in_array('viewPaymentForecast', $user_permission) ||
                    in_array('deletePaymentForecast', $user_permission ) ||
                    in_array('createTTMkt', $user_permission) ||
                    in_array('updateTTMkt', $user_permission) ||
                    in_array('viewTTMkt', $user_permission) ||
                    in_array('deleteTTMkt', $user_permission ) ||

                    in_array('createParamktplaceCiclo', $user_permission) ||
                    in_array('updateParamktplaceCiclo', $user_permission) ||
                    in_array('viewParammktplaceCiclo', $user_permission) ||
                    in_array('deleteParamktplaceCiclo', $user_permission) ||
                    in_array('createParamktplaceCicloTransp', $user_permission) ||
                    in_array('updateParamktplaceCicloTransp', $user_permission) ||
                    in_array('viewParammktplaceCicloTransp', $user_permission) ||
                    in_array('deleteParamktplaceCicloTransp', $user_permission ) ||

                    in_array('createNFS', $user_permission) ||
                    in_array('updateNFS', $user_permission) ||
                    in_array('viewNFS', $user_permission) ||
                    in_array('deletetNFS', $user_permission ) ||
                    in_array('balanceTransfers', $user_permission ) 
                    
              ): ?>
            <li class="treeview" id="paraMktPlaceNav">
              <a class="menuhref" href="#">
                <i class="fa fa-money"></i>
                <span><?=$this->lang->line('application_financial_panel');?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
              
              	<?php if($gsoma_painel_financeiro['status'] == "1" || $novomundo_painel_financeiro['status'] == "1"){?>
              		<?php if(in_array('createPaymentForecast', $user_permission) || in_array('updatePaymentForecast', $user_permission) || in_array('viewPaymentForecast', $user_permission) || in_array('deletePaymentForecast', $user_permission)): ?>
                      <li id="attributeNav"> <a class="menuhref" href="<?php echo base_url('payment/listprevisaosellercenter') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_parameter_payment_forecast');?></span> </a> </li>
                    <?php endif; ?>
              	<?php }else{?>
              		<?php if(in_array('createPaymentForecast', $user_permission) || in_array('updatePaymentForecast', $user_permission) || in_array('viewPaymentForecast', $user_permission) || in_array('deletePaymentForecast', $user_permission)): ?>
                      <li id="attributeNav"> <a class="menuhref" href="<?php echo base_url('payment/listprevisao') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_parameter_payment_forecast');?></span> </a> </li>
                    <?php endif; ?>
              	<?php }?>
              	
              	<!-- Extrato da conta -->
              	<?php if(in_array('createExtract', $user_permission) || in_array('updateExtract', $user_permission) || in_array('viewExtract', $user_permission) || in_array('deleteExtract', $user_permission)): ?>
                  <?php if($novomundo_painel_financeiro['status'] == "1"){ ?>
                    <li id="extratoNav"> <a class="menuhref" href="<?php echo base_url('payment/extrato') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_extract_novomundo');?></span> </a> </li>
                  <?php }else{ ?>
                    <li id="extratoNav"> <a class="menuhref" href="<?php echo base_url('payment/extrato') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_extract');?></span> </a> </li>
                  <?php } ?>
                <?php endif; ?>
                
                <?php if(in_array('createExtractParceiro', $user_permission) || in_array('updateExtractParceiro', $user_permission) || in_array('viewExtractParceiro', $user_permission) || in_array('deleteExtractParceiro', $user_permission)): ?>
                  <li id="attributeNav"> <a class="menuhref" href="<?php echo base_url('payment/extratoparceiro') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_extract_partner');?></span> </a> </li>
                <?php endif; ?>
              
                <?php if(in_array('createParamktplace', $user_permission) || in_array('updateParamktplace', $user_permission) || in_array('viewParammktplace', $user_permission) || in_array('deleteParamktplace', $user_permission)): ?>
                  <li id="attributeNav"> <a class="menuhref" href="<?php echo base_url('paramktplace/list') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_parameter_mktplace_comissao');?></span> </a> </li>
                <?php endif; ?>


                <?php if($gsoma_painel_financeiro['status'] == "1" || $novomundo_painel_financeiro['status'] == "1"){?>
                  <?php if(in_array('createParamktplaceCiclo', $user_permission) || in_array('updateParamktplaceCiclo', $user_permission) || in_array('viewParammktplaceCiclo', $user_permission) || in_array('deleteParamktplaceCiclo', $user_permission)): ?>
                              <li id="attributeNav"> <a class="menuhref" href="<?php echo base_url('paramktplace/listciclosellercenter') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_parameter_mktplace_ciclos');?></span> </a> </li>
                            <?php endif; ?>
                <?php }else{?>
                  <?php if(in_array('createParamktplaceCiclo', $user_permission) || in_array('updateParamktplaceCiclo', $user_permission) || in_array('viewParammktplaceCiclo', $user_permission) || in_array('deleteParamktplaceCiclo', $user_permission)): ?>
                              <li id="listCicloNav"> <a class="menuhref" href="<?php echo base_url('paramktplace/listciclo') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_parameter_mktplace_ciclos');?></span> </a> </li>
                            <?php endif; ?>
                <?php }?>
                
                <?php if(in_array('createParamktplaceCicloTransp', $user_permission) || in_array('updateParamktplaceCicloTransp', $user_permission) || in_array('viewParammktplaceCicloTransp', $user_permission) || in_array('deleteParamktplaceCicloTransp', $user_permission)): ?>
                   <li id="attributeNav"> <a class="menuhref" href="<?php echo base_url('paramktplace/listciclotransp') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_parameter_providers_ciclos');?></span> </a> </li>
                <?php endif; ?>

                  <?php if (in_array('createPaymentRelease', $user_permission) || in_array('updatePaymentRelease', $user_permission) || in_array('viewPaymentRelease', $user_permission) || in_array('deletePaymentRelease', $user_permission)) : ?>
                      <li id="paymentReleaseNav"> <a class="menuhref" href="<?php echo base_url('billet/listsellercenter') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_payment_release');?></span> </a> </li>
                <?php endif; ?>

                  <?php if (ENVIRONMENT === 'development'): ?>
                      <li id="paymentReleaseNav"> <a class="menuhref" href="<?php echo base_url('cycles') ?>"> <i class="fa fa-cogs"></i> <span><?php echo $this->lang->line('application_parameter_payment_cycles');?></span> </a> </li>
                  <?php endif; ?>

                <?php if(in_array('createLegalPanel', $user_permission) || in_array('updateLegalPanel', $user_permission) || in_array('viewLegalPanel', $user_permission) || in_array('deleteLegalPanel', $user_permission)): ?>
                    <li id="paineljuridicoNav"> <a class="menuhref" href="<?php echo base_url('legalpanel/') ?>"> <i class="fa fa-cogs"></i> <span><?php echo $this->lang->line('application_legal_panel'); ?></span> </a> </li> 
                <?php endif; ?>
                
                <?php if(in_array('createBilletTransp', $user_permission) || in_array('updateBilletTransp', $user_permission) || in_array('viewBilletTransp', $user_permission) || in_array('deleteBilletTransp', $user_permission)): ?>
                   <li id="attributeNav"> <a class="menuhref" href="<?php echo base_url('billet/listtranspresumo') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_conciliacao_transp');?></span> </a> </li> 
                <?php endif; ?>

                  <?php if(in_array('createBilletConcil', $user_permission) || in_array('updateBilletConcil', $user_permission) || in_array('viewBilletConcil', $user_permission) || in_array('deleteBilletConcil', $user_permission)): ?>
                      <li id="conciliacaoNav"> <a class="menuhref" href="<?php echo base_url('billet/list') ?>"> <i class="fa fa-cogs"></i> <span><?php if($novomundo_painel_financeiro['status'] == "1"){echo $this->lang->line('application_conciliacao_novomundo');}else{echo $this->lang->line('application_conciliacao');}?></span> </a> </li>
                  <?php endif; ?>

                <?php if(in_array('createPaymentForcastConcil', $user_permission) || in_array('updatePaymentForcastConcil', $user_permission) || in_array('viewPaymentForcastConcil', $user_permission) || in_array('deletePaymentForcastConcil', $user_permission)): ?>
                  <li id="attributeNav"> <a class="menuhref" href="<?php echo base_url('payment/listprevisaocontrole') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_parameter_payment_forecast_concilia');?></span> </a> </li>
                <?php endif; ?>

                <?php if(in_array('createIugu', $user_permission) || in_array('updateIugu', $user_permission) || in_array('viewIugu', $user_permission) || in_array('deleteIugu', $user_permission)): ?>
                  <li id="attributeNav"> <a class="menuhref" href="<?php echo base_url('iugu/list') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_iugu_panel');?></span> </a> </li>
                <?php endif; ?>
                
                <?php if(in_array('createTTMkt', $user_permission) || in_array('updateTTMkt', $user_permission) || in_array('viewTTMkt', $user_permission) || in_array('deleteTTMkt', $user_permission)): ?>
                  <li id="attributeNav"> <a class="menuhref" href="<?php echo base_url('TroubleTicket/list') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_adm_troubleticket_mktplace');?></span> </a> </li>
                <?php endif; ?>
                
                <?php if(in_array('createNFS', $user_permission) || in_array('updateNFS', $user_permission) || in_array('viewNFS', $user_permission) || in_array('deletetNFS', $user_permission)): ?>
                  <li id="painelFiscalMenu"> <a class="menuhref" href="<?php echo base_url('payment/listfiscal') ?>"> <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_panel_fiscal');?></span> </a> </li>
                <?php endif; ?>

                <?php if (in_array('createParamktplaceFiscal', $user_permission) || in_array('updateParamktplaceFiscal', $user_permission) || in_array('viewParammktplaceFiscal', $user_permission) || in_array('deleteParamktplaceFiscal', $user_permission)) : ?>
                  <li id="cicloFiscalNav" class="<?php if($url_active == "/app/paramktplace/listciclofiscalsellercenter"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('paramktplace/listciclofiscalsellercenter') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_parameter_mktplace_ciclo_fiscal'); ?></span> </a> </li>
                <?php endif; ?>

                  <!-- braun -->
				  <?php
				  $gateway_id = $this->model_settings->getSettingDatabyName('payment_gateway_id')['value'];

				  $gateways_with_payment_report = [];
				  $setting_gateways_with_payment_report = $this->model_settings->getSettingDatabyName('payment_gateways_with_payment_report');

				  if (isset($setting_gateways_with_payment_report['value']))
				  {
					  $gateways_with_payment_report = explode(';', $setting_gateways_with_payment_report['value']);
				  }

				  $allow_transfer_between_accounts = $this->model_gateway_settings->getGatewaySettingByName($gateway_id, 'allow_transfer_between_accounts');
				  ?>

				  <?php
				  include('__financeiro.php');
				  ?>

              </ul>
            </li>
          <?php endif; ?>

          <?php if(in_array('creditCredseller', $user_permission)): ?>
            <li class="treeview" id="creditNav">
                <a class="menuhref" href="#">
                    <i class="fa fa-credit-card-alt"></i>
                    <span><?= $this->lang->line('application_credit'); ?></span>
                    <span class="pull-right-container">
                      <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <?php if(in_array('creditCredseller', $user_permission)): ?>
                  <ul class="treeview-menu">
                      <li id="loanNav"><a class="menuhref" target="_blank" href="https://simulador.credseller.com.br/#/conectala/giro"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_credit_loan'); ?></a></li>
                  </ul>
                <?php endif; ?>
            </li>
          <?php endif; ?>

          <?php if(in_array('showcaseCatalog', $user_permission) || in_array('createCatalog', $user_permission) || in_array('updateCatalog', $user_permission) || in_array('viewCatalog', $user_permission) || in_array('deleteCatalog', $user_permission) || in_array('createProductsCatalog', $user_permission) ): ?>
            <li class="treeview" id="mainCatalogNav">
              <a class="menuhref" href="#"><i class="fa fa-layer-group"></i><span><?=$this->lang->line('application_catalogs');?></span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
              <ul class="treeview-menu">
                <?php if(in_array('createCatalog', $user_permission)): ?>
                  <li id="addCatalogNav"><a class="menuhref" href="<?php echo base_url('catalogs/create') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_add_catalog');?></a></li>
                <?php endif; ?>
                <?php if(in_array('viewCatalog', $user_permission)): ?>
                <li id="manageCatalogNav"><a class="menuhref" href="<?php echo base_url('catalogs/index') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_catalog');?></a></li>
                <?php endif; ?>
                <?php if(in_array('createProductsCatalog', $user_permission)): ?>
                <li id="addProductCatalogNav"><a class="menuhref" href="<?php echo base_url('catalogProducts/create') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_add_product_catalog');?></a></li>
                <?php endif; ?>
                <?php if(in_array('viewProductsCatalog', $user_permission)): ?>
                <li id="manageProductCatalogNav"><a class="menuhref" href="<?php echo base_url('catalogProducts/index') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_product_catalog');?></a></li>
                <?php endif; ?>
                <?php if(in_array('showcaseCatalog', $user_permission)): ?> 
                <li id="manageCatalogShowCaseNav"><a class="menuhref" href="<?php echo base_url('catalogProducts/showcase') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_showcase_products_catalog');?></a></li>
                <?php endif; ?>
              </ul>
            </li>
          <?php endif; ?>

          <?php include_once __DIR__ . '/items/products.php'; ?>
          
          <?php if(in_array('createPromotions', $user_permission) || in_array('updatePromotions', $user_permission) || in_array('viewPromotions', $user_permission) || in_array('deletePromotions', $user_permission)): ?>
            <li class="treeview" id="mainPromotionsNav">
              <a class="menuhref" href="#">
                <i class="fa fa-star"></i>
                <span><?=$this->lang->line('application_promotions');?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <?php if(in_array('createPromotions', $user_permission)): ?>
                  <li id="addPromotionNav"><a href="<?php echo base_url('promotions/createpromo') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_add_promotion');?></a></li>
                <?php endif; ?>
                <?php if(in_array('updatePromotions', $user_permission) || in_array('viewPromotions', $user_permission) || in_array('deletePromotions', $user_permission)): ?>
                <li id="managePromotionsNav"><a class="menuhref" href="<?php echo base_url('promotions') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_promotions');?></a></li>
                <?php endif; ?>
              </ul>
            </li>
          <?php endif; ?>
          <li id="downloadcenterNav">
                <a class="menuhref" href="<?php echo base_url('DownloadCenter') ?>">
                    <i class="fas fa-download"></i>
                    <span><?=$this->lang->line('application_download_center');?></span>
                    <span class="pull-right-container">
                </span>
                </a>
          </li>
      <?php
      /*
      if(in_array('createCampaigns', $user_permission) || in_array('updateCampaigns', $user_permission) || in_array('viewCampaigns', $user_permission) || in_array('deleteCampaigns', $user_permission) || in_array('viewCampaignsStore', $user_permission) || in_array('updateCampaignsStore', $user_permission)): ?>
          <li class="treeview" id="mainCampaignsNav">
              <a class="menuhref" href="#">
                  <i class="fa fa-newspaper-o"></i>
                  <span><?=$this->lang->line('application_campaigns');?></span>
                  <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
              </a>
              <ul class="treeview-menu">
                  <?php if(in_array('createCampaigns', $user_permission) && !$this->session->userdata('userstore') && $this->data['only_admin'] && $this->data['usercomp'] == 1): ?>
                      <li id="addCampaignsNav"><a href="<?php echo base_url('campaigns_v2/createcampaigns') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_add_campaign_v2');?></a></li>
                  <?php endif; ?>
                  <?php if(in_array('updateCampaigns', $user_permission) || in_array('viewCampaigns', $user_permission) || in_array('deleteCampaigns', $user_permission)): ?>
                      <li id="manageCampaignsNav"><a class="menuhref" href="<?php echo base_url('campaigns_v2') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_campaigns_v2');?></a></li>
                  <?php endif; ?>
              </ul>
          </li>
      <?php endif;
      */
      ?>

    <?php if(in_array('createOrder', $user_permission) || in_array('updateOrder', $user_permission) || in_array('viewOrder', $user_permission) || in_array('deleteOrder', $user_permission)): ?>
      <li class="treeview" id="mainOrdersNav">
        <a class="menuhref" href="#">
          <i class="fa fa-dollar"></i>
          <span><?=$this->lang->line('application_orders');?></span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <?php if(in_array('createOrder', $user_permission)): ?>
            <li id="addOrderNav"><a class="menuhref" href="<?php echo base_url('orders/create') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_add_order');?></a></li>
          <?php endif; ?>
          <?php if(in_array('updateOrder', $user_permission) || in_array('viewOrder', $user_permission) || in_array('deleteOrder', $user_permission)): ?>
          <li id="loadOrdersNFENav"><a class="menuhref" href="<?php echo base_url('orders/loadnfe') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_upload_nfes');?></a></li>
          <li id="manageOrdersNav"><a class="menuhref" href="<?php echo base_url('orders') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_orders');?></a></li>
          <?php endif; ?>
          <?php if(in_array('createInvoice', $user_permission) || in_array('cancelInvoice', $user_permission)): ?>
            <li id="manageOrdersInvoiceNav"><a class="menuhref" href="<?php echo base_url('orders/invoice') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_orders_invoice');?></a></li>
          <?php endif; ?>
          <?php if (in_array('createReturnOrder', $user_permission) || in_array('updateReturnOrder', $user_permission) || in_array('viewReturnOrder', $user_permission)): ?>
            <li id="ReturnOrderNav"><a class="menuhref" href="<?php echo base_url('ProductsReturn/return') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_return_order');?></a></li>
          <?php endif; ?>
        </ul>
      </li>
    <?php endif; ?>

    <?php if(in_array('createInvoice', $user_permission) || in_array('cancelInvoice', $user_permission)): ?>
      <li id="moduleBillerNav">
        <a class="menuhref" href="<?php echo base_url('users/request_biller/') ?>">
          <i class="far fa-file"></i> <span><?=$this->lang->line('application_request_biller_module');?></span>
        </a>
      </li>
    <?php endif; ?>

    <?php if(in_array('viewLogistics', $user_permission)): ?>

      <li class="treeview" id="mainLogisticsNav">
          <a class="menuhref" href="#">
              <i class="fa fa-truck"></i>
              <span><?=$this->lang->line('application_logistics');?></span>
              <span class="pull-right-container">
        <i class="fa fa-angle-left pull-right"></i>
      </span>
          </a>
          <ul class="treeview-menu">

              <?php if(in_array('createCarrierRegistration', $user_permission) || in_array('updateCarrierRegistration', $user_permission) || in_array('viewCarrierRegistration', $user_permission) || in_array('deleteCarrierRegistration', $user_permission)){ ?>
                  <li id="carrierRegistrationNav"><a class="menuhref" href="<?php echo base_url('shippingcompany/index') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_list_shipping_company');?></a></li>
              <?php } ?>

              <?php /* if(in_array('createPromotionsLogistic', $user_permission) || in_array('updatePromotionsLogistic', $user_permission) || in_array('viewPromotionsLogistic', $user_permission) || in_array('deletePromotionsLogistic', $user_permission)){ ?>
                  <?php if((int)$this->session->userdata['usercomp'] == 1) { ?>
                      <li id="logisticPromotionAdminNav"><a class="menuhref" href="<?php echo base_url('PromotionLogistic') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_logistic_promotion_admin');?></a></li>
                  <?php } else { ?>
                      <li id="logisticPromotionNav"><a class="menuhref" href="<?php echo base_url('PromotionLogistic/seller') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_logistic_promotion');?></a></li>
                  <?php } ?>
              <?php } */ ?>

              <?php if (in_array('viewPickUpPoint', $user_permission)): ?>
                <li id="pickupPointRulesNav"><a class="menuhref" href="<?php echo base_url('PickupPoint') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_pickup_point');?></a></li>
              <?php endif; ?>

                <?php if (in_array('createPricingRules', $user_permission) || in_array('updatePricingRules', $user_permission) || in_array('viewPricingRules', $user_permission) || in_array('deletePricingRules', $user_permission)) { ?>
                <li id="shippingPricingRulesNav"><a class="menuhref" href="<?php echo base_url('shippingpricingrules') ?>"><i class="fa fa-circle-o"></i> Precificação de Frete <!-- ?= $this->lang->line('application_logistic_promotion'); ?--></a></li>
              <?php } ?>

                    <?php if (in_array('createCarrierRegistration', $user_permission)) { ?>
                        <li id="navFileProcess"><a class="menuhref" href="<?php echo base_url('FileProcess/index') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_file_process');?></a></li>
                    <?php } ?>

                  <?php if (in_array('viewTrackingOrder', $user_permission) || in_array('updateTrackingOrder', $user_permission)) { ?>
                    <li id="pageTracking">
                      <a class="menuhref" href="<?php echo base_url('rastreio'); ?>" target="_blank">
                        <i class="fa fa-circle-o"></i> <?= $this->lang->line('page_tracking'); ?>
                      </a>
                    </li>
                  <?php } ?>
                  <?php if (in_array('viewTrackingPage', $user_permission) || in_array('updateTrackingPage', $user_permission)) { ?>
                    <li id="mainRastreioCustomNav">
                      <a class="menuhref" href="<?php echo base_url('Rastreio/customization') ?>">
                        <i class="fa fa-circle-o"></i> <?=$this->lang->line('application_tracking_custom_menu'); ?>
                      </a>
                    </li>
                  <?php } ?>

                    <li id="manageOrdersTagsNav"><a class="menuhref" href="<?php echo base_url('orders/manage_tags') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage tags');?></a></li>

                    <?php if(in_array('createAuctionRules', $user_permission) || in_array('updateAuctionRules', $user_permission) || in_array('viewAuctionRules', $user_permission) || in_array('deleteAuctionRules', $user_permission)){ ?>
                        <li id="auctionRulesNav"><a class="menuhref" href="<?php echo base_url('auction/introduction')?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage rules');?></a></li>
                    <?php } ?>

                    <?php if(in_array('createIntegrationLogistic', $user_permission) || in_array('updateIntegrationLogistic', $user_permission) || in_array('viewIntegrationLogistic', $user_permission) || in_array('deleteIntegrationLogistic', $user_permission)): ?>
                        <li id="manageLogisticIntegrationsNav"><a class="menuhref" href="<?php echo base_url('logistics/introduction') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_logistic');?></a></li>
                    <?php endif; ?>
                    <li id="manageLogisticNav" class="<?php if($url_active == "/app/logistics/introduction_manage_logistic"){ echo 'active';} ?>"><a class="menuhref" href="<?=base_url('logistics/introduction_manage_logistic') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_logisticsnew'); ?></a></li>
                </ul>
            </li>
          <?php endif; ?>

          <?php if(in_array('createClients', $user_permission) || in_array('updateClients', $user_permission) || in_array('viewClients', $user_permission) || in_array('deleteClients', $user_permission)): ?>
            <li class="treeview" id="mainClientsNav">
              <a class="menuhref" href="#">
                <i class="fa fa-address-book"></i>
                <span><?=$this->lang->line('application_clients');?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <?php if(in_array('createClients', $user_permission)): ?>
                  <li id="addClientsNav"><a class="menuhref" href="<?php echo base_url('clients/create') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_add_clients');?></a></li>
                <?php endif; ?>
                <?php if(in_array('updateClients', $user_permission) || in_array('viewClients', $user_permission) || in_array('deleteClients', $user_permission)): ?>
                <li id="manageClientsNav"><a class="menuhref" href="<?php echo base_url('clients') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_clients');?></a></li>
                <?php endif; ?>
              </ul>
            </li>
          <?php endif; ?>

            <?php if(in_array('createProviders', $user_permission) || in_array('updateProviders', $user_permission) || in_array('viewProviders', $user_permission) || in_array('deleteProviders', $user_permission)): ?>
                <li class="treeview" id="mainProvidersNav">
                    <a class="menuhref" href="#">
                        <i class="fa fa-plane"></i>
                        <span><?=$this->lang->line('application_providers');?></span>
                        <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
                    </a>
                    <ul class="treeview-menu">
                        <?php if(in_array('createProviders', $user_permission)): ?>
                            <li id="addProvidersNav"><a class="menuhref" href="<?php echo base_url('providers/create') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_add_providers');?></a></li>
                        <?php endif; ?>
                        <?php if(in_array('updateProviders', $user_permission) || in_array('viewProviders', $user_permission) || in_array('deleteProviders', $user_permission)): ?>
                            <li id="manageProvidersNav"><a class="menuhref" href="<?php echo base_url('providers') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_providers');?></a></li>
                        <?php endif; ?>
                        <?php if(in_array('updateProviders', $user_permission) || in_array('viewProviders', $user_permission) || in_array('deleteProviders', $user_permission)): ?>
                            <li id="manageProvidersNav"><a class="menuhref" href="<?php echo base_url('providers/listindicacao') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_providers_program');?></a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

          <?php if(in_array('createReceivables', $user_permission) || in_array('updateReceivables', $user_permission) || in_array('viewReceivables', $user_permission) || in_array('deleteReceivables', $user_permission)): ?>
            <li class="treeview" id="mainReceivableNav">
              <a class="menuhref" href="#">
                <i class="fa fa-money"></i>
                <span><?=$this->lang->line('application_receivables');?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <?php if(in_array('createReceivables', $user_permission)): ?>
                  <li id="addReceivableNav"><a class="menuhref" href="<?php echo base_url('receivables/account') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_account');?></a></li>
                <?php endif; ?>
                <?php if(in_array('updateReceivables', $user_permission) || in_array('viewReceivables', $user_permission) || in_array('deleteReceivables', $user_permission)): ?>
                <li id="manageReceivableNav"><a class="menuhref" href="<?php echo base_url('receivables') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_receivables');?></a></li>
                <?php endif; ?>
              </ul>
            </li>
          <?php endif; ?>

          <?php if(in_array('viewContracts', $user_permission) || in_array('viewContractSignatures', $user_permission) ): ?>
            <li class="treeview" id="mainContractsNav">
                <a class="menuhref" href="#">
                    <i class="far fa-file"></i>
                    <span><?=$this->lang->line('application_contracts');?></span>
                    <span class="pull-right-container">
                      <i class="fa fa-angle-left pull-right"></i>
                </span>
                </a>
                <ul class="treeview-menu">
                  <?php if(in_array('viewContracts', $user_permission)): ?>
                    <li id="contracts"><a class="menuhref" href="<?php echo base_url('contracts') ?>"><i class="fa fa-circle-o"></i><?=$this->lang->line('application_contracts');?></a></li>
                  <?php endif; ?>  
                  <?php if(in_array('viewContractSignatures', $user_permission)): ?>
                    <li id="contractSignatures"><a class="menuhref" href="<?php echo base_url('contractSignatures') ?>"><i class="fa fa-circle-o"></i><?=$this->lang->line('application_contract_signatures');?></a></li>
                  <?php endif; ?>    
                </ul>
            </li>
          <?php endif; ?>


          <?php
            //DESATIVADO Integrações de Sistema - 10/06/20 - PEDRO HENRIQUE
            if( (in_array('createIntegrations', $user_permission) ||
                    in_array('updateIntegrations', $user_permission) ||
                    in_array('viewIntegrations', $user_permission) ||
                    in_array('deleteIntegrations', $user_permission)) && false
                ): ?>
            <li class="treeview" id="mainIntegrationNav">
              <a class="menuhref" href="#">
                <i class="fa fa-sitemap"></i>
                <span><?=$this->lang->line('application_integrations');?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <?php if(in_array('createIntegrations', $user_permission)): ?>
                  <li id="addIntegrationNav"><a class="menuhref" href="<?php echo base_url('integrations/create') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_add_integration');?></a></li>
                <?php endif; ?>
                <?php if(in_array('updateIntegrations', $user_permission) || in_array('viewIntegrations', $user_permission) || in_array('deleteIntegrations', $user_permission)): ?>
                <li id="manageIntegrationNav"><a class="menuhref" href="<?php echo base_url('integrations') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_integrations');?></a></li>
                <?php endif; ?>
              </ul>
            </li>
          <?php endif; ?>

          <?php if(in_array('productsMarketplace', $user_permission) || in_array('updateMarketplace', $user_permission) || in_array('viewMarketplace', $user_permission) || in_array('deleteMarketplace', $user_permission)): ?>
            <li class="treeview" id="mainMarketPlaceNav">
              <a class="menuhref" href="#">
                <i class="fa fa-cloud-upload"></i>
                <span><?=$this->lang->line('application_runmarketplaces');?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <?php if(in_array('productsMarketplace', $user_permission)): ?>
                  <li id="allocProductNav"><a class="menuhref" href="<?php echo base_url('products/allocate') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_alloc_product');?></a></li>
                <?php endif; ?>
                <?php if(in_array('viewMarketplace', $user_permission)): ?>
                <li id="loadIntegrationNav"><a class="menuhref" href="<?php echo base_url('calendar/index') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_job_schedule');?></a></li>
                <?php endif; ?>
              </ul>
            </li>
          <?php endif; ?>

          <?php if((in_array('viewReports', $user_permission)|| ($hasReportGroups)) && count($menuMetabse_seller)): ?>
                  <li class="treeview" id="reportNav">
                    <a class="menuhref" href="#">
                        <i class="fa fa-cloud-upload"></i>
                        <span><?=$this->lang->line('application_reports');?></span>
                        <span class="pull-right-container">
                      <i class="fa fa-angle-left pull-right"></i>
                    </span>
                    </a>
                    <ul class="treeview-menu">
                        <?php foreach ($menuMetabse_seller as $metabaseSeller):?>
                            <li id="<?=$metabaseSeller['selector_menu']?>"><a class="menuhref" href="<?=base_url('reports/report/'.$metabaseSeller['name_href'])?>"><i class="fa fa-circle-o"></i><?=$metabaseSeller['title']?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if((in_array('viewManagementReport', $user_permission) || ($hasReportGroupsAdmin)) && count($menuMetabse_adm)): ?>
                <li class="treeview" id="reportNavAdm">
                    <a class="menuhref" href="#">
                        <i class="fas fa-file-contract"></i>
                        <span><?=$this->lang->line('application_management_report');?></span>
                        <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
                    </a>
                    <ul class="treeview-menu">
                        <?php foreach ($menuMetabse_adm as $metabaseAdm):?>
                            <li id="<?=$metabaseAdm['selector_menu']?>"><a class="menuhref" href="<?=base_url('reports/report/'.$metabaseAdm['name_href'])?>"><i class="fa fa-circle-o"></i><?=$metabaseAdm['title']?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <li class="treeview" id="mainIntegrationNav">
              <a class="menuhref" href="#">
                <i class="fa fa-ticket"></i>
                <span><?=$this->lang->line('application_support');?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">			              
                <li id="agiDeskCatalogoNav"><a target="_blank" href="https://<?php echo $site_agidesk;?>.agidesk.com/servicos?access_token=<?php echo $token_agidesk ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_agidesk_servicos');?></a></li>
                <li id="agiDeskAtendimentosNav"><a target="_blank" href="https://<?php echo $site_agidesk;?>.agidesk.com/atendimentos?access_token=<?php echo $token_agidesk ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_agidesk_solicitacoes');?></a></li>
                <li id="agiDeskCentralNav"><a target="_blank" href="https://<?php echo $site_agidesk;?>.agidesk.com/central-de-ajuda?access_token=<?php echo $token_agidesk ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_agidesk_central');?></a></li>
                <?php if ($link_atendimento_externo_status == 1) { 

                    $valuesToMenu = explode(',',$link_atendimento_externo);

                    if(count($valuesToMenu) >= 2) {
                      $nameMenu = $valuesToMenu[0];
                      $urlExternal = $valuesToMenu[1];

                      $urlExternal = trim($urlExternal, "'\" ");

                            // Verifica se a URL não começa com "http://" ou "https://"
                          if (!preg_match("~^(?:f|ht)tps?://~i", $urlExternal)) {
                            // Se não começar com "http://" ou "https://", presume-se que seja uma URL relativa,
                            // então você pode adicionar o protocolo "http://" aqui
                            $urlExternal = "http://$urlExternal";
                        }

                        // Remove a parte do URL base, se presente
                        $base_url = base_url();
                        if (strpos($urlExternal, $base_url) === 0) {
                            $urlExternal = substr($urlExternal, strlen($base_url));
                        }
                    } else {
                        $nameMenu = 'Atendimento Externo';
                        $urlExternal = '';
                    }
                    
                  ;?>
                      <li id="linkExternalAtendimento"><a target="_blank" href="<?php echo $urlExternal;?>"><i class="fa fa-circle-o"></i> <?php echo $nameMenu;?></a></li>
                <?php }
                  ?>
                  <?php if ($use_agidesk == 1) {
                  if ((is_null($token_agidesk)) || (!$site_agidesk)) { ?>
                    <li id="gotoAgidesk"><a target="_blank" href="https://agidesk.com/br/login"><i class="glyphicon glyphicon-flag"></i> <span><?= $this->lang->line('application_support'); ?></span></a></li>
                  <?php } 
                  } ?>
              </ul>
          </li>
         
          <li id="gotoAgidesk2"><a target="_blank" href="https://api.whatsapp.com/send/?phone=552137337708&text&app_absent=0"><i class="glyphicon glyphicon-flag"></i> <span><?= $this->lang->line('application_see_sac_url'); ?></span></a></li>
           
          
        <?php if ((in_array('reportProblem', $user_permission))  && (!is_null($token_agidesk_conectala)) ): ?>
          <li id="report_problem_url"><a class="menuhref" target="_blank" href="https://conectala.agidesk.com/br/painel/servicos/secoes/suporte-seller-center?access_token=<?php echo $token_agidesk_conectala ?>"><i class="fas fa-exclamation-triangle"></i> <?=$this->lang->line('application_report_problem');?></a></li>
        <?php endif; ?>
        <?php if ($fac_url): ?>
          <li id="fac_url"><a class="menuhref" target="_blank" href="<?=$fac_url?>"><i class="glyphicon glyphicon-headphones"></i> <?=$this->lang->line('application_see_fac_url');?></a></li>
        <?php endif; ?>
        <?php if(in_array('viewProfile', $user_permission)): ?>
          <li id="profileNav"><a class="menuhref" href="<?php echo base_url('users/profile/') ?>"><i class="fa fa-user-o"></i> <span><?=$this->lang->line('application_profile');?></span></a></li>
        <?php endif; ?>
        <li class="treeview" id="mainIntegrationApiNav">
            <a class="menuhref" href="#">
                <i class="fas fa-plug"></i>
                <span> <?=$this->lang->line('application_integration');?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
            </span>
            </a>
            <ul class="treeview-menu">
                <li id="requestIntegration"><a class="menuhref" href="<?php echo base_url('stores/integration') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_request_integration');?></a></li>
                <li id="logIntegration"><a class="menuhref" href="<?php echo base_url('integrations/log_integration') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_history_integration');?></a></li>
                <li id="jobIntegration"><a class="menuhref" href="<?php echo base_url('integrations/job_integration') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_manage_integrations');?></a></li>
                <?php if(in_array('viewManageIntegrationErp', $user_permission)): ?>
                    <li id="manageIntegrationErp"><a class="menuhref" href="<?=base_url('integrations/manageIntegration') ?>"><i class="fa fa-circle-o"></i><?=$this->lang->line('application_manage_integration');?></a></li>
                <?php endif; ?>
                <?php if(
                    in_array('viewIntegrationAttributeMap', $user_permission) ||
                    in_array('createIntegrationAttributeMap', $user_permission) ||
                    in_array('updateIntegrationAttributeMap', $user_permission) ||
                    in_array('deleteIntegrationAttributeMap', $user_permission)):
                    ?>
                    <li id="integrationAttributes"><a class="menuhref" href="<?php echo base_url('integrations/configuration/attributes') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_data_normalization');?></a></li>
                <?php endif; ?>
                <li id="webhookIntegration" class="<?php if($url_active == "/app/stores/webhookintegration"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('stores/webhookintegration') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_request_webhook'); ?></a></li>
            </ul>
        </li>
        
        <?php if(in_array('marketplaces_integrations', $user_permission)): ?>
<!--        <li class="treeview" id="mainIntegrationMarketplaceNav">-->
<!--            <a class="menuhref" href="#">-->
<!--                <i class="fas fa-plug"></i>-->
<!--                <span> --><?php //=$this->lang->line('application_marketplaces_integrations');?><!--</span>-->
<!--                <span class="pull-right-container">-->
<!--                  <i class="fa fa-angle-left pull-right"></i>-->
<!--            </span>-->
<!--            </a>-->
<!--            <ul class="treeview-menu">-->
<!--                <li id="MLIntegrationMarketplace"><a class="menuhref" href="--><?php //echo base_url('loginML/index') ?><!--"><i class="fa fa-circle-o"></i> --><?php //=$this->lang->line('application_mercado_livre');?><!--</a></li>-->
<!--            </ul>-->
<!--        </li>-->
        <?php endif; ?>

        <?php if(in_array('updateSetting', $user_permission)): ?>
          <li id="settingNav"><a class="menuhref" href="<?php echo base_url('users/setting/') ?>"><i class="fa fa-wrench"></i> <span><?=$this->lang->line('application_settings');?></span></a></li>
        <?php endif; ?>
        <?php if(in_array('initStoreMigration', $user_permission) || in_array('storeMigration', $user_permission)): ?>
        <li class="treeview  
            <?php if($url_active == '/app/MigrationSeller/index' || $url_active == '/app/MigrationSeller/index') { echo 'active menu-open'; } ?>" 
            id="migrationSeller">
            <a class="menuhref" href="#">
              <i class="fa fa-plug"></i>
              <span><?= $this->lang->line('application_migration_seller'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
            <?php if(in_array('initStoreMigration', $user_permission)): ?>
              <li id="migrationSeller" class="<?php if($url_active == "/app/MigrationSeller/index"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('MigrationSeller/index') ?>"><i class="fa fa-circle-o"></i> <span><?=$this->lang->line('application_manage_migration');?></span></a></li>
            <?php endif; ?>
            <?php if(in_array('storeMigration', $user_permission)): ?>
              <li id="migrationSeller" class="<?php if($url_active == "/app/MigrationSeller/viewmigrations"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('MigrationSeller/viewmigrations') ?>"><i class="fa fa-circle-o"></i> <span><?=$this->lang->line('application_migration_seller_mach');?></span></a></li>
            <?php endif; ?>
            <?php /**if(in_array('initStoreMigration', $user_permission)): ?> -->
              <li id="migrationSeller" class="<?php if($url_active == "/app/MigrationSeller/endmigration"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('MigrationSeller/endmigration') ?>"><i class="fa fa-circle-o"></i> <span><?=$this->lang->line('application_migration_seller_end');?></span></a></li>
              <?php endif;**/ ?>
            </ul>
        </li>
        <?php endif; ?>
        <?php
        if ((in_array('updateShopkeeperForm', $user_permission)) || (in_array('createFieldShopkeeperForm', $user_permission))) : ?>
          <li class="treeview" id="mainShopkeeperformNav">
            <a class="menuhref" href="#">
              <i class="fa fa-sitemap"></i>
              <span><?= $this->lang->line('application_shopkeeper_form'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('createFieldShopkeeperForm', $user_permission)) : ?>
                <li id="addShopkeeperformNav"><a class="menuhref" href="<?php echo base_url('ShopkeeperForm/list') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_adm_shopkeeper_form'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('updateShopkeeperForm', $user_permission)) : ?>
                <li id="manageShopkeeperformNav"><a class="menuhref" href="<?php echo base_url('ShopkeeperForm') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_add_fields_form'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>
        <?php endif; ?>
        <li><a class="menuhref" href="<?php echo base_url('suggestions') ?>"><i class="fa fa-lightbulb-o" aria-hidden="true"></i> <span><?=$this->lang->line('application_manage_suggestions');?></span></a></li>
        
    <?php if (in_array('marketplaces_integrations', $user_permission)) : ?>
    <li class="treeview" id="mainIntegrationMarketplaceNav">
        <a class="menuhref" href="#">
            <i class="fas fa-bullhorn"></i>
            <span> <?= $this->lang->line('application_notification_center'); ?></span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
        </a>
        <ul class="treeview-menu">
            <li id="TemplacteEmailIndex"><a class="menuhref" href="<?php echo base_url('templateEmail/index') ?>"><i class="fa fa-circle-o"></i> <?='Criar modelo de email' ?></a></li>
            <li id="MLIntegrationMarketplace"><a class="menuhref" href="<?php echo base_url('templateEmailSchedule/index') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_template_email_schedule'); ?></a></li>
          </ul>
    </li>
        <?php endif; ?>

        <?php if ($this->data['only_admin']) : ?>
          <li id="systemHealthli"><a class="menuhref" href="<?php echo base_url('dashboard/systemHealth') ?>"><i class="fa fa-heartbeat" aria-hidden="true"></i> <span><?=$this->lang->line('application_system_health');?></span></a></li>
        <?php endif; ?>
        
        <!-- user permission info -->
        <li><a class="menuhref" href="<?php echo base_url('auth/logout') ?>"><i class="glyphicon glyphicon-log-out"></i> <span><?=$this->lang->line('application_logout');?></span></a></li>

      </ul>
    </section>
    <!-- /.sidebar -->

  </aside>
