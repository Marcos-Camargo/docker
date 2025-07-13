<style>
  a.menuhref:link {
    text-decoration: none
  }
  input.form-control.filter-search {
      margin: 14px;
      width: -webkit-fill-available;
      height: 30px;
  }
  .btn_clear{
      width: 25px;
      float: right;
      margin: 17px 0px 0px -42px;
      position: absolute;
      z-index: 5;
      height: 24px;
      border: none;
      font-weight: bold;
  }
  .main-sidebar .sidebar .input-group{
    display: flow-root;
  }
  #mySearch {
      width: -moz-available;
  }
</style>


<aside class="main-sidebar">
  <!-- sidebar: style can be found in sidebar.less -->
  <section class="sidebar">

      <?php $url_active = $_SERVER["REQUEST_URI"]; ?>

      <div class="input-group">
           <input type="text" class="form-control filter-search" placeholder="Buscar..." autocomplete="off" id="mySearch">
          <button class="btn_clear">x</button>
      </div>

      <!-- sidebar menu: : style can be found in sidebar.less -->
    <ul class="sidebar-menu" data-widget="tree" id="myMenu">

      <li id="dashboardMainMenu" class=" <?php if(
          // dashboard
          $url_active == '/app/dashboard'
      )
      { echo 'active menu-open'; } ?>">
        <a class="menuhref" href="<?php echo base_url('dashboard') ?>">
          <i class="fa fa-dashboard"></i> <span>Dashboard</span>
        </a>
      </li>

      <?php if (in_array('admDashboard', $this->permission)) : ?>
        <!--<li class="treeview" id="navMainBlackFriday">
          <a class="menuhref" href="#">
            <i class="fas fa-file-contract"></i>
            <span>Black Friday</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
              <li id="navQuoteBlackFriday"><a class="menuhref" href="<?= base_url('reports/blackFridayReports') ?>"><i class="fa fa-circle-o"></i><?= $this->lang->line('application_report_black_friday'); ?></a></li>
              <li id="navSpecialBlackFriday"><a class="menuhref" href="<?= base_url('reports/bfSpecialReports') ?>"><i class="fa fa-circle-o"></i><?= $this->lang->line('application_report_bf_special'); ?></a></li>
          </ul>
        </li>-->
      <?php endif; ?>

      <?php if ($tranning_url) : ?>
        <li id="tranning_videos"><a class="menuhref" target="_blank" href="<?php echo $tranning_url; ?>"><i class="fas fa-graduation-cap"></i> <?= $this->lang->line('application_trainning_videos'); ?></a></li>
      <?php endif; ?>

      <?php if ($user_permission) : ?>

        <?php if (
            (in_array('doIntegration', $user_permission)) || 
            (in_array('createUserFreteRapido', $user_permission)) ||
            (in_array('updateTrackingOrder', $user_permission)) ||
            (in_array('viewTrackingOrder', $user_permission))
        ) : ?>

          <li class="treeview <?php if(

                  // orders
                  $url_active == '/app/orders/semfrete' || $url_active == '/app/orders/invoiceSentToMarketplace' || $url_active == '/app/orders/inTransitSentToMarketplace' || $url_active == '/app/orders/trackingSentToMarketplace' || $url_active == '/app/orders/deliverySentToMarketplace' ||
                  $url_active == '/app/orders/cancelSentoToMarketplace' || $url_active == '/app/orders/manage_tags_adm' || $url_active == '/app/orders/order_in_progress' ||
                  $url_active == '/app/orders/internal' || $url_active == '/app/orders/charge_update_status' || $url_active == '/app/orders/invoiceSentToMarketplace' ||

                  //stores
                  $url_active == '/app/stores/setting' || $url_active == '/app/stores/manage_integrations' || $url_active == '/app/orders/invoiceSentToMarketplace' || $url_active == '/app/orders/invoiceSentToMarketplace' ||

                  
                  // products
                  $url_active == '/app/products/productsNotCorreios' || $url_active == '/app/products/sentOmnilogic' ||
                  // waitingIntegration
                  $url_active == '/app/waitingIntegration/integrationPriceQty' ||
                  // shopify
                  $url_active == '/app/shopify/shopify_requests' ||
                  // iugu
                  $url_active == '/app/iugu/subcontastatus' || $url_active == '/app/iugu/relatoriosaque' ||
                  // reports
                  $url_active == '/app/reports/manageReports' 
                
          )
          { echo 'active menu-open'; } ?>" id="mainProcessesNav">
            <a class="menuhref" href="#">
              <i class="fa fa-toggle-on"></i>
              <span><?= $this->lang->line('application_daily_process'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <!--
              <?php if (in_array('doIntegration', $user_permission)) : ?>
              <li id="doIntegrationNav"><a class="menuhref" href="<?php echo base_url('waitingIntegration/semintegracao') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_waitingintegration'); ?></a></li>
              <li id="attributesmlintegrateNav"><a class="menuhref" href="<?php echo base_url('attributesMLIntegrate/index') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_attributesmlintegrate'); ?></a></li>
              <li id="produtosIntegracaoNav"><a class="menuhref" href="<?php echo base_url('products/produtosIntegracao') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_products_integration'); ?></a></li>
              <?php endif; ?>
              -->
              <?php if (in_array('viewTrackingOrder', $user_permission)) : ?>
                <li id="semFreteNav" class="<?php if($url_active == "/app/orders/semfrete"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('orders/semfrete') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_freight_to_wire'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('doIntegration', $user_permission)) : ?>
              <?php if (ENVIRONMENT != 'production' && ENVIRONMENT !== 'production_x'): ?>
                <li id="enviadoNfeMkt" class="<?php if($url_active == "/app/orders/invoiceSentToMarketplace"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('orders/invoiceSentToMarketplace') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_nfe_to_mkt'); ?></a></li>
                <li id="envioMktNav" class="<?php if($url_active == "/app/orders/inTransitSentToMarketplace"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('orders/inTransitSentToMarketplace') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_sendto_mkt'); ?></a></li>
                <li id="trackingMktNav" class="<?php if($url_active == "/app/orders/trackingSentToMarketplace"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('orders/trackingSentToMarketplace') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_tracking_to_mkt'); ?></a></li>
                <li id="freteentregueNav" class="<?php if($url_active == "/app/orders/deliverySentToMarketplace"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('orders/deliverySentToMarketplace') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_freight_delivered_mkt'); ?></a></li>
                <li id="cancelaMktNav" class="<?php if($url_active == "/app/orders/cancelSentoToMarketplace"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('orders/cancelSentoToMarketplace') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_cancela_mkt'); ?></a></li>
              <?php endif; ?>
                <!-- Retirado do Frete Rápido
           	  <li id="avisoFreteRapidoNav"><a class="menuhref" href="<?php echo base_url('stores/avisoFreteRapido') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_stores_new_categories'); ?></a></li>
              --->
                <li id="billerModuleNav" class="<?php if($url_active == "/app/stores/setting"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('stores/setting') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_biller_module'); ?></a></li>
    
                <li id="manageIntegrationsNav" class="<?php if($url_active == "/app/stores/manage_integrations"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('stores/manage_integrations') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_integrations'); ?></a></li>
                <li id="productsNotCorreiosNav" class="<?php if($url_active == "/app/products/productsNotCorreios"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('products/productsNotCorreios') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_products_not_post_office'); ?></a></li>
                <li id="manageOrdersTagsAdmNav" class="<?php if($url_active == "/app/orders/manage_tags_adm"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('orders/manage_tags_adm') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_tags_correios'); ?></a></li>
                <li id="manageOrdersInProgressNav" class="<?php if($url_active == "/app/orders/order_in_progress"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('orders/order_in_progress') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_order_in_progress'); ?></a></li>
                <li id="manageOrdersNav" class="<?php if($url_active == "/app/orders/internal"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('orders/internal') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_orders'); ?></a></li>
                <li id="integrationPriceQtyNav" class="<?php if($url_active == "/app/waitingIntegration/integrationPriceQty"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('waitingIntegration/integrationPriceQty') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_integration_price_qty'); ?></a></li>
                
              <?php endif; ?>

              <?php if (in_array('updateShopifyRequests', $user_permission) || in_array('viewShopifyRequests', $user_permission) || in_array('deleteShopifyRequests', $user_permission)) : ?>
                <li id="ShopifyRequestsNav" class="<?php if($url_active == "/app/shopify/shopify_requests"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('shopify/shopify_requests') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_shopify_requests'); ?></a></li>
              <?php endif; ?>

              <?php if (in_array('viewLogIUGU', $user_permission)) : ?>
                <li id="logIUGU" class="<?php if($url_active == "/app/iugu/subcontastatus"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('iugu/subcontastatus') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_log_iugu_view'); ?></a></li>
                <li id="logIUGU" class="<?php if($url_active == "/app/iugu/relatoriosaque"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('iugu/relatoriosaque') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_iugu_withdraw'); ?></a></li>
              <?php endif; ?>

              <?php if (in_array('updateTrackingOrder', $user_permission)) : ?>
              <li id="chargeUpdateStatusOrder" class="<?php if($url_active == "/app/orders/charge_update_status"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('orders/charge_update_status') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_charge_status_order'); ?></a></li>
              <?php endif; ?>

              <?php if (in_array('doIntegration', $user_permission)) : ?>
              <li id="manageReports" class="<?php if($url_active == "/app/reports/manageReports"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('reports/manageReports') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_reports'); ?></a></li>
              <?php endif; ?>

              <?php if (in_array('manageProductsOmnilogicSent', $user_permission)) : ?>
                <li id="sentOmnilogic"  class="<?php if($url_active == "/app/products/sentOmnilogic"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('products/sentOmnilogic') ?>"><i class="fa fa-circle-o"></i><?= $this->lang->line('application_sent_omnilogic'); ?></a></li>
              <?php endif; ?>

              <?php if (in_array('cleanCache', $user_permission)) : ?>
                  <li id="manageCleanCache"><a class="menuhref" href="<?php echo base_url('cache') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_clean_cache'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>

        <?php if(in_array('createUser', $user_permission) || in_array('updateUser', $user_permission) || in_array('viewUser', $user_permission) || 
                   in_array('createExternalAuthentication', $user_permission) || in_array('updateExternalAuthentication', $user_permission) || in_array('viewExternalAuthentication', $user_permission) ): ?>
          <li class="treeview <?php if(
              // usuarios
              $url_active == '/app/users/create' || $url_active == '/app/users'
          )
          { echo 'active menu-open'; } ?>" id="mainUserNav">
            <a class="menuhref" href="#">
              <i class="fa fa-user-o"></i>
              <span><?= $this->lang->line('application_users'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('createUser', $user_permission)) : ?>
                <li id="createUserNav" class="<?php if($url_active == "/app/users/create"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('users/create') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_add_user'); ?></a></li>
              <?php endif; ?>

              <?php if (in_array('updateUser', $user_permission) || in_array('viewUser', $user_permission) || in_array('deleteUser', $user_permission)) : ?>
                <li id="manageUserNav" class="<?php if($url_active == "/app/users"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('users') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_users'); ?></a></li>
              <?php endif; ?>
              
              <?php if(in_array('createExternalAuthentication', $user_permission) || in_array('updateExternalAuthentication', $user_permission) || in_array('viewExternalAuthentication', $user_permission)): ?>
              <li id="manageExternalAuthenticationNav"><a class="menuhref" href="<?php echo base_url('externalAuthentication') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_externalAuthentication');?></a></li>
              <?php endif; ?>

            </ul>
          </li>
        <?php endif; ?>

        <?php if (in_array('createGroup', $user_permission) || in_array('updateGroup', $user_permission) || in_array('viewGroup', $user_permission) || in_array('deleteGroup', $user_permission)) : ?>
          <li class="treeview <?php if(
              // grupos
              $url_active == '/app/groups/create' || $url_active == '/app/groups'
          )
          { echo 'active menu-open'; } ?>" id="mainGroupNav">
            <a class="menuhref" href="#">
              <i class="fa fa-users"></i>
              <span><?= $this->lang->line('application_groups'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('createGroup', $user_permission)) : ?>
                <li id="addGroupNav" class="<?php if($url_active == "/app/groups/create"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('groups/create') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_add_group'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('updateGroup', $user_permission) || in_array('viewGroup', $user_permission) || in_array('deleteGroup', $user_permission)) : ?>
                <li id="manageGroupNav" class="<?php if($url_active == "/app/groups"){ echo 'active';} ?>" ><a class="menuhref" href="<?php echo base_url('groups') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_groups'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>

        <?php if (in_array('createConfig', $user_permission) || in_array('updateConfig', $user_permission) || in_array('viewConfig', $user_permission) || in_array('deleteConfig', $user_permission)) : ?>
          <li id="configNav" class="<?php if($url_active == "/app/settings/"){ echo 'active';} ?>" >
            <a class="menuhref" href="<?php echo base_url('settings/') ?>">
              <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_systemconfig'); ?></span>
            </a>
          </li>
        <?php endif; ?>

          <?php if(in_array('viewPaymentGatewayConfig', $user_permission) ): ?>
              <li id="paymentgatewaysettingsNav" class="<?php if($url_active == "/app/paymentGatewaySettings/"){ echo 'active';} ?>" >
                  <a class="menuhref" href="<?php echo base_url('paymentGatewaySettings/') ?>">
                      <i class="fa fa-cogs"></i> <span><?=$this->lang->line('application_payment_gateway_settings');?></span>
                  </a>
              </li>
          <?php endif; ?>

        <?php if (in_array('createCompany', $user_permission) || in_array('updateCompany', $user_permission) || in_array('viewCompany', $user_permission) || in_array('deleteCompany', $user_permission)) : ?>
          <li class="treeview <?php if(
              // empresas
              $url_active == '/app/company/create' || $url_active == '/app/company'
          )
          { echo 'active menu-open'; } ?>" id="mainCompanyNav">
            <a class="menuhref" href="#">
              <i class="fa fa-industry"></i>
              <span><?= $this->lang->line('application_companies'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('createCompany', $user_permission)) : ?>
                <li id="addCompanyNav" class="<?php if($url_active == "/app/company/create"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('company/create') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_add_company'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('updateCompany', $user_permission) || in_array('viewCompany', $user_permission) || in_array('deleteCompany', $user_permission)) : ?>
                <li id="manageCompanyNav" class="<?php if($url_active == "/app/company"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('company') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_companies'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('viewMerchant', $user_permission)) : ?>
                <li id="manageCompanyNav" class="<?php if($url_active == "/app/merchants"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('merchants') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_merchant'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>

        <?php if (in_array('createStore', $user_permission) || in_array('updateStore', $user_permission) || in_array('viewStore', $user_permission) || in_array('deleteStore', $user_permission)) : ?>
          <li id="storeNav" class="<?php if($url_active == "/app/stores/"){ echo 'active';} ?>" >
            <a class="menuhref" href="<?php echo base_url('stores/') ?>">
              <i class="fa fa-home"></i> <span><?= $this->lang->line('application_stores'); ?></span>
            </a>
          </li>
        <?php endif; ?>
        <?php if (in_array('createPhases', $user_permission) || in_array('updatePhases', $user_permission) || in_array('viewPhases', $user_permission) || in_array('deletePhases', $user_permission)) : ?>
          <li class="treeview <?php if(
              // fases
              $url_active == '/app/phases/managePhases' || $url_active == '/app/phases' ||  $url_active == '/app/phases/import'
          )
          { echo 'active menu-open'; } ?>" id="mainPhasesNav">
            <a class="menuhref" href="#">
              <i class="fa fa-level-up" aria-hidden="true"></i>
              <span><?= $this->lang->line('application_phases'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('createPhases', $user_permission) || in_array('updatePhases', $user_permission) || in_array('viewPhases', $user_permission) || in_array('deletePhases', $user_permission)) : ?>
                <li id="managePhasesStores" class="<?php if($url_active == "/app/phases/managePhases"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('phases/managePhases') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_phases'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('updateStore', $user_permission) || in_array('viewStore', $user_permission)) : ?>
                <li id="managePhasesNav" class="<?php if($url_active == "/app/phases"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('phases') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_phases_store'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('createPhases', $user_permission)) : ?>
                <li id="importByCSVPhasesNav" class="<?php if($url_active == "/app/phases/import"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('phases/import') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_phases_store_by_csv'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>
        <?php if (in_array('createBank', $user_permission) || in_array('updateBank', $user_permission) || in_array('viewBank', $user_permission) || in_array('deleteBank', $user_permission)) : ?>
          <li id="bankNav" class="<?php if($url_active == "/app/banks/"){ echo 'active';} ?>">
            <a class="menuhref" href="<?php echo base_url('banks/') ?>">
              <i class="fas fa-dollar-sign"></i> <span><?= $this->lang->line('application_Banks'); ?></span>
            </a>
          </li>
        <?php endif; ?>
        <?php if (in_array('createBrand', $user_permission) || in_array('updateBrand', $user_permission) || in_array('viewBrand', $user_permission) || in_array('deleteBrand', $user_permission)) : ?>
          <li id="brandNav" class="<?php if($url_active == "/app/brands/"){ echo 'active';} ?>">
            <a class="menuhref" href="<?php echo base_url('brands/') ?>">
              <i class="glyphicon glyphicon-tags"></i> <span><?= $this->lang->line('application_brands'); ?></span>
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
          <li class="treeview <?php if(
              // category
              $url_active == '/app/category/'
          )
          { echo 'active menu-open'; } ?>" id="mainCategoryNav">
            <a class="menuhref" href="#">
              <i class="fa fa-list"></i>
              <span><?= $this->lang->line('application_categories'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('updateCompany', $user_permission) || in_array('viewCompany', $user_permission) || in_array('deleteCompany', $user_permission)) : ?>
                <li id="manageCategoryNav" class="<?php if($url_active == "/app/category/"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('category/') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_categories'); ?></a></li>
              <?php endif; ?>
              <?php if($this->data['only_admin']): ?>
                <li id="changeProductCategoryNav"><a class="menuhref" href="<?php echo base_url('Category/changeProductCategory') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_import_change_product_category_csv');?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>

        <?php if (in_array('createAttribute', $user_permission) || in_array('updateAttribute', $user_permission) || in_array('viewAttribute', $user_permission) || in_array('deleteAttribute', $user_permission)) : ?>
          <li id="attributeNav" class="<?php if($url_active == "/app/attributes/"){ echo 'active';} ?>">
            <a class="menuhref" href="<?php echo base_url('attributes/') ?>">
              <i class="fa fa-object-group"></i> <span><?= $this->lang->line('application_attributes'); ?></span>
            </a>
          </li>
        <?php endif; ?>
        <!--
          <?php //if(in_array('createParamktplace', $user_permission) || in_array('updateParamktplace', $user_permission) || in_array('viewParammktplace', $user_permission) || in_array('deleteParamktplace', $user_permission)): 
          ?>
          <li id="paraMktPlaceNav">
            <a class="menuhref" href="<?php echo base_url('paramktplace/list') ?>">
              <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_parameter_mktplace'); ?></span>
            </a>
          </li>
          <?php //endif; 
          ?>
			-->

        <?php

          if (
          in_array('createParamktplace', $user_permission) ||
          in_array('createPaymentRelease', $user_permission) ||
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
          in_array('deletePaymentForecast', $user_permission) ||
          in_array('createTTMkt', $user_permission) ||
          in_array('updateTTMkt', $user_permission) ||
          in_array('viewTTMkt', $user_permission) ||
          in_array('deleteTTMkt', $user_permission) ||

          in_array('createParamktplaceCiclo', $user_permission) ||
          in_array('updateParamktplaceCiclo', $user_permission) ||
          in_array('viewParammktplaceCiclo', $user_permission) ||
          in_array('deleteParamktplaceCiclo', $user_permission) ||
          in_array('createParamktplaceCicloTransp', $user_permission) ||
          in_array('updateParamktplaceCicloTransp', $user_permission) ||
          in_array('viewParammktplaceCicloTransp', $user_permission) ||
          in_array('deleteParamktplaceCicloTransp', $user_permission) ||

          in_array('createNFS', $user_permission) ||
          in_array('updateNFS', $user_permission) ||
          in_array('viewNFS', $user_permission) ||
          in_array('deletetNFS', $user_permission) ||

          in_array('createDiscountWorksheet', $user_permission) ||
          in_array('updateDiscountWorksheet', $user_permission) ||
          in_array('viewDiscountWorksheet', $user_permission) ||
          in_array('deletetDiscountWorksheet', $user_permission)

        ) : ?>
          <li class="treeview <?php if(
              // financial
              $url_active == '/app/payment/listprevisao' || $url_active == '/app/payment/extrato' || $url_active == '/app/payment/extratoparceiro' ||
              $url_active == '/app/paramktplace/list' || $url_active == '/app/paramktplace/listciclo' || $url_active == '/app/paramktplace/listciclotransp' ||
              $url_active == '/app/billet/list' || $url_active == '/app/billet/listsellercenter' || $url_active == '/app/legalpanel/' ||
              $url_active == '/app/billet/listtranspresumo' || $url_active == '/app/payment/listprevisaocontrole' || $url_active == '/app/iugu/list' ||
              $url_active == '/app/TroubleTicket/list' || $url_active == '/app/payment/listfiscal' || $url_active == '/app/billet/listdiscountworksheet'
          )
          { echo 'active menu-open'; } ?>" id="paraMktPlaceNav">
            <a class="menuhref" href="#">
              <i class="fa fa-money"></i>
              <span><?= $this->lang->line('application_financial_panel'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">

              <?php if ($gsoma_painel_financeiro['status'] == "1" || $novomundo_painel_financeiro['status'] == "1") { ?>
                <?php if (in_array('createPaymentForecast', $user_permission) || in_array('updatePaymentForecast', $user_permission) || in_array('viewPaymentForecast', $user_permission) || in_array('deletePaymentForecast', $user_permission)) : ?>
                  <li id="paymentforcastNav" class="<?php if($url_active == "/app/payment/listprevisaosellercenter"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('payment/listprevisaosellercenter') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_parameter_payment_forecast'); ?></span> </a> </li>
                <?php endif; ?>
              <?php } else { ?>
                <?php if (in_array('createPaymentForecast', $user_permission) || in_array('updatePaymentForecast', $user_permission) || in_array('viewPaymentForecast', $user_permission) || in_array('deletePaymentForecast', $user_permission)) : ?>
                  <li id="paymentforcastNav" class="<?php if($url_active == "/app/payment/listprevisao"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('payment/listprevisao') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_parameter_payment_forecast'); ?></span> </a> </li>
                <?php endif; ?>
              <?php } ?>

              <!-- Extrato da conta -->
              <?php if (in_array('createExtract', $user_permission) || in_array('updateExtract', $user_permission) || in_array('viewExtract', $user_permission) || in_array('deleteExtract', $user_permission)) : ?>
                <?php if ($novomundo_painel_financeiro['status'] == "1") { ?>
                  <li id="extratoNav" class="<?php if($url_active == "/app/payment/extrato"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('payment/extrato') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_extract_novomundo'); ?></span> </a> </li>
                <?php } else { ?>
                  <li id="extratoNav" class="<?php if($url_active == "/app/payment/extrato"){ echo 'active';} ?>" > <a class="menuhref" href="<?php echo base_url('payment/extrato') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_extract'); ?></span> </a> </li>
                <?php } ?>
              <?php endif; ?>

              <?php if (in_array('createExtractParceiro', $user_permission) || in_array('updateExtractParceiro', $user_permission) || in_array('viewExtractParceiro', $user_permission) || in_array('deleteExtractParceiro', $user_permission)) : ?>
                <li id="attributeNav" class="<?php if($url_active == "/app/payment/extratoparceiro"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('payment/extratoparceiro') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_extract_partner'); ?></span> </a> </li>
              <?php endif; ?>

              <?php if (in_array('createParamktplace', $user_permission) || in_array('updateParamktplace', $user_permission) || in_array('viewParammktplace', $user_permission) || in_array('deleteParamktplace', $user_permission)) : ?>
                <li id="attributeNav" class="<?php if($url_active == "/app/paramktplace/list"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('paramktplace/list') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_parameter_mktplace_comissao'); ?></span> </a> </li>
              <?php endif; ?>


              <?php if ($gsoma_painel_financeiro['status'] == "1" || $novomundo_painel_financeiro['status'] == "1") { ?>
                <?php if (in_array('createParamktplaceCiclo', $user_permission) || in_array('updateParamktplaceCiclo', $user_permission) || in_array('viewParammktplaceCiclo', $user_permission) || in_array('deleteParamktplaceCiclo', $user_permission)) : ?>
                  <li id="attributeNav" class="<?php if($url_active == "/app/paramktplace/listciclosellercenter"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('paramktplace/listciclosellercenter') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_parameter_mktplace_ciclos'); ?></span> </a> </li>
                <?php endif; ?>
              <?php } else { ?>
                <?php if (in_array('createParamktplaceCiclo', $user_permission) || in_array('updateParamktplaceCiclo', $user_permission) || in_array('viewParammktplaceCiclo', $user_permission) || in_array('deleteParamktplaceCiclo', $user_permission)) : ?>
                  <li id="listCicloNav" class="<?php if($url_active == "/app/paramktplace/listciclo"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('paramktplace/listciclo') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_parameter_mktplace_ciclos'); ?></span> </a> </li>
                <?php endif; ?>
              <?php } ?>

              <?php if (in_array('createParamktplaceCicloTransp', $user_permission) || in_array('updateParamktplaceCicloTransp', $user_permission) || in_array('viewParammktplaceCicloTransp', $user_permission) || in_array('deleteParamktplaceCicloTransp', $user_permission)) : ?>
                <li id="attributeNav" class="<?php if($url_active == "/app/paramktplace/listciclotransp"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('paramktplace/listciclotransp') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_parameter_providers_ciclos'); ?></span> </a> </li>
              <?php endif; ?>

              <?php if (in_array('createPaymentRelease', $user_permission) || in_array('createBilletConcil', $user_permission) || in_array('updateBilletConcil', $user_permission) || in_array('viewBilletConcil', $user_permission) || in_array('deleteBilletConcil', $user_permission)) : ?>
                <?php if ($gsoma_painel_financeiro['status'] == "1" || $novomundo_painel_financeiro['status'] == "1" || $ortobom_painel_financeiro['status'] == "1" || $casaevideo_painel_financeiro['status'] == "1") { ?>
                  <li id="conciliacaoNav" class="<?php if($url_active == "/app/billet/listsellercenter"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('billet/listsellercenter') ?>"> <i class="fa fa-cogs"></i> <span><?php if ($novomundo_painel_financeiro['status'] == "1") {
                        echo $this->lang->line('application_conciliacao_novomundo');
                      } else {
                        echo $this->lang->line('application_conciliacao');
                      } ?></span> </a> </li>
                <?php } else { ?>
                  <li id="conciliacaoNav" class="<?php if($url_active == "/app/billet/list"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('billet/list') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_conciliacao'); ?></span> </a> </li>
                <?php } ?>


                  <?php if (in_array('createPaymentRelease', $user_permission) || in_array('updatePaymentRelease', $user_permission) || in_array('viewPaymentRelease', $user_permission) || in_array('deletePaymentRelease', $user_permission)) : ?>
                      <li id="paymentReleaseNav" class="<?php if($url_active == "/app/billet/listsellercenter"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('billet/listsellercenter') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_payment_release'); ?></span> </a> </li>
                  <?php endif; ?>

              <?php endif; ?>

                <?php if (ENVIRONMENT === 'development'): ?>
                    <li id="paymentReleaseNav"> <a class="menuhref" href="<?php echo base_url('cycles') ?>"> <i class="fa fa-cogs"></i> <span><?php echo $this->lang->line('application_parameter_payment_cycles');?></span> </a> </li>
                <?php endif; ?>

              <?php if (in_array('createLegalPanel', $user_permission) || in_array('updateLegalPanel', $user_permission) || in_array('viewLegalPanel', $user_permission) || in_array('deleteLegalPanel', $user_permission)) : ?>
                <li id="paineljuridicoNav" class="<?php if($url_active == "/app/legalpanel/"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('legalpanel/') ?>"> <i class="fa fa-cogs"></i> <span><?php echo $this->lang->line('application_legal_panel'); ?></span> </a> </li>
              <?php endif; ?>

              <?php if (in_array('createBilletTransp', $user_permission) || in_array('updateBilletTransp', $user_permission) || in_array('viewBilletTransp', $user_permission) || in_array('deleteBilletTransp', $user_permission)) : ?>
                <li id="attributeNav" class="<?php if($url_active == "/app/billet/listtranspresumo"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('billet/listtranspresumo') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_conciliacao_transp'); ?></span> </a> </li>
              <?php endif; ?>

              <?php if (in_array('createPaymentForcastConcil', $user_permission) || in_array('updatePaymentForcastConcil', $user_permission) || in_array('viewPaymentForcastConcil', $user_permission) || in_array('deletePaymentForcastConcil', $user_permission)) : ?>
                <li id="attributeNav" class="<?php if($url_active == "/app/payment/listprevisaocontrole"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('payment/listprevisaocontrole') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_parameter_payment_forecast_concilia'); ?></span> </a> </li>
              <?php endif; ?>

              <?php if (in_array('createIugu', $user_permission) || in_array('updateIugu', $user_permission) || in_array('viewIugu', $user_permission) || in_array('deleteIugu', $user_permission)) : ?>
                <li id="attributeNav" class="<?php if($url_active == "/app/iugu/list"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('iugu/list') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_iugu_panel'); ?></span> </a> </li>
              <?php endif; ?>

              <?php if (in_array('createTTMkt', $user_permission) || in_array('updateTTMkt', $user_permission) || in_array('viewTTMkt', $user_permission) || in_array('deleteTTMkt', $user_permission)) : ?>
                <li id="attributeNav" class="<?php if($url_active == "/app/TroubleTicket/list"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('TroubleTicket/list') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_adm_troubleticket_mktplace'); ?></span> </a> </li>
              <?php endif; ?>

              <?php if (in_array('createNFS', $user_permission) || in_array('updateNFS', $user_permission) || in_array('viewNFS', $user_permission) || in_array('deletetNFS', $user_permission)) : ?>
                <li id="attributeNav" class="<?php if($url_active == "/app/payment/listfiscal"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('payment/listfiscal') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_panel_fiscal'); ?></span> </a> </li>
              <?php endif; ?>

              <?php if (in_array('createDiscountWorksheet', $user_permission) || in_array('updateDiscountWorksheet', $user_permission) || in_array('viewDiscountWorksheet', $user_permission) || in_array('deletetDiscountWorksheet', $user_permission)) : ?>
                <li id="attributeNav" class="<?php if($url_active == "/app/billet/listdiscountworksheet"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('billet/listdiscountworksheet') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_discount_worksheet'); ?></span> </a> </li>
              <?php endif; ?>

              <?php if (in_array('createParamktplaceFiscal', $user_permission) || in_array('updateParamktplaceFiscal', $user_permission) || in_array('viewParammktplaceFiscal', $user_permission) || in_array('deleteParamktplaceFiscal', $user_permission)) : ?>
                <li id="cicloFiscalNav" class="<?php if($url_active == "/app/paramktplace/listciclofiscalsellercenter"){ echo 'active';} ?>"> <a class="menuhref" href="<?php echo base_url('paramktplace/listciclofiscalsellercenter') ?>"> <i class="fa fa-cogs"></i> <span><?= $this->lang->line('application_parameter_mktplace_ciclo_fiscal'); ?></span> </a> </li>
              <?php endif; ?>

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

        <?php if (in_array('showcaseCatalog', $user_permission) || in_array('createCatalog', $user_permission) || in_array('updateCatalog', $user_permission) || in_array('viewCatalog', $user_permission) || in_array('deleteCatalog', $user_permission) || in_array('createProductsCatalog', $user_permission)) : ?>
          <li class="treeview  <?php if(
              // product catalog
              $url_active == '/app/catalogs/create' || $url_active == '/app/catalogs/index' || $url_active == '/app/catalogProducts/create' ||
              $url_active == '/app/catalogProducts/index' || $url_active == '/app/catalogProducts/showcase' || $url_active == '/app/catalogProducts/create'
          )
          { echo 'active menu-open'; } ?>" id="mainCatalogNav">
            <a class="menuhref" href="#"><i class="fa fa-layer-group"></i><span><?= $this->lang->line('application_catalogs'); ?></span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
            <ul class="treeview-menu">
              <?php if (in_array('createCatalog', $user_permission)) : ?>
                <li id="addCatalogNav" class="<?php if($url_active == "/app/catalogs/create"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('catalogs/create') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_add_catalog'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('viewCatalog', $user_permission)) : ?>
                <li id="manageCatalogNav" class="<?php if($url_active == "/app/catalogs/index"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('catalogs/index') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_catalog'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('createProductsCatalog', $user_permission)) : ?>
                <li id="addProductCatalogNav" class="<?php if($url_active == "/app/catalogProducts/create"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('catalogProducts/create') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_add_product_catalog'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('viewProductsCatalog', $user_permission)) : ?>
                <li id="manageProductCatalogNav" class="<?php if($url_active == "/app/catalogProducts/index"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('catalogProducts/index') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_product_catalog'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('showcaseCatalog', $user_permission)) : ?>
                <li id="manageCatalogShowCaseNav" class="<?php if($url_active == "/app/catalogProducts/showcase"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('catalogProducts/showcase') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_showcase_products_catalog'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>

          <?php include_once __DIR__ . '/items/products.php'; ?>


          <li id="downloadcenterNav">
                <a class="menuhref" href="<?php echo base_url('DownloadCenter') ?>">
                    <i class="fas fa-download"></i>
                    <span><?=$this->lang->line('application_download_center');?></span>
                    <span class="pull-right-container">
                </span>
                </a>
          </li>
          <?php if(in_array('createCampaigns', $user_permission) || in_array('updateCampaigns', $user_permission) || in_array('viewCampaigns', $user_permission) || in_array('deleteCampaigns', $user_permission) || in_array('viewCampaignsStore', $user_permission) || in_array('updateCampaignsStore', $user_permission)): ?>
              <li class="treeview  <?php if(
                  // Campaigns
                  $url_active == '/app/campaigns_v2/createcampaigns' || $url_active == '/app/campaigns_v2'
              )
              { echo 'active menu-open'; } ?>" id="mainCampaignsNav">

                  <a class="menuhref" href="#">

                      <i class="fa fa-newspaper-o"></i>
                      <span><?=$this->lang->line('application_campaigns');?></span>
                      <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                      </span>
                  </a>

                  <ul class="treeview-menu">
					  <?php if(in_array('createCampaigns', $user_permission) && !$this->session->userdata('userstore') && $this->data['only_admin'] && $this->data['usercomp'] == 1): ?>

						  <?php if ($this->model_settings->getStatusbyName('enable_campaigns_v2_1') == "1"): ?>
                              <li id="DashboardCampaignsNav"><a class="menuhref" href="<?php echo base_url('campaigns_v2/dashboard') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('campaign_v2_sidemenu_dashboard');?></a></li>
						  <?php endif; ?>

                          <li id="addCampaignsNav"><a class="menuhref" href="<?php
							  $new_campaign_link = ($this->model_settings->getStatusbyName('enable_campaigns_v2_1') == "1") ? 'newcampaign ' : 'createcampaigns';
							  echo base_url('campaigns_v2/'.$new_campaign_link);
							  ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_add_campaign_v2');?></a></li>

					  <?php endif; ?>

					  <?php if(in_array('updateCampaigns', $user_permission) || in_array('viewCampaigns', $user_permission) || in_array('deleteCampaigns', $user_permission)): ?>
                          <li id="manageCampaignsNav"><a class="menuhref" href="<?php echo base_url('campaigns_v2') ?>"><i class="fa fa-circle-o"></i> <?php
								  $new_campaign_text = ($this->model_settings->getStatusbyName('enable_campaigns_v2_1') == "1") ? 'application_manage_campaigns_v2_1' : 'application_manage_campaigns_v2';
								  echo $this->lang->line($new_campaign_text);
								  ?></a></li>
					  <?php endif; ?>
                  </ul>
              </li>
          <?php endif; ?>

        <?php if (in_array('createOrder', $user_permission) || in_array('updateOrder', $user_permission) || in_array('viewOrder', $user_permission) || in_array('deleteOrder', $user_permission)) : ?>
          <li class="treeview  <?php if(
              // Orders
              $url_active == '/app/orders/create' || $url_active == '/app/orders/loadnfe' || $url_active == '/app/orders' ||
              $url_active == '/app/orders/invoice' || $url_active == '/app/ProductsReturn/return'
          )
          { echo 'active menu-open'; } ?>" id="mainOrdersNav">
            <a class="menuhref" href="#">
              <i class="fa fa-dollar"></i>
              <span><?= $this->lang->line('application_orders'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('createOrder', $user_permission)) : ?>
                <li id="addOrderNav" class="<?php if($url_active == "/app/orders/create"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('orders/create') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_add_order'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('updateOrder', $user_permission) || in_array('viewOrder', $user_permission) || in_array('deleteOrder', $user_permission)) : ?>
                <li id="loadOrdersNFENav" class="<?php if($url_active == "/app/orders/loadnfe"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('orders/loadnfe') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_upload_nfes'); ?></a></li>
                <li id="manageOrdersNav" class="<?php if($url_active == "/app/orders"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('orders') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_orders'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('createInvoice', $user_permission) || in_array('cancelInvoice', $user_permission)) : ?>
                <li id="manageOrdersInvoiceNav" class="<?php if($url_active == "/app/orders/invoice"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('orders/invoice') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_orders_invoice'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('createReturnOrder', $user_permission) || in_array('updateReturnOrder', $user_permission) || in_array('viewReturnOrder', $user_permission)): ?>
                  <li id="ReturnOrderNav" class="<?php if($url_active == "/app/ProductsReturn/return"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('ProductsReturn/return') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_return_order');?></a></li>
                <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>
        
        <?php if (in_array('createInvoice', $user_permission) || in_array('cancelInvoice', $user_permission)) : ?>        
          <li id="moduleBillerNav" class="<?php if($url_active == "/app/users/request_biller/"){ echo 'active';} ?>">
            <a class="menuhref" href="<?php echo base_url('users/request_biller/') ?>">
              <i class="far fa-file"></i> <span><?= $this->lang->line('application_request_biller_module'); ?></span>
            </a>
          </li>
        <?php endif; ?>

        <?php if (in_array('viewLogistics', $user_permission)) : ?>

          <li class="treeview  <?php if(
              // Logistics
              $url_active == '/app/shippingcompany/index' || $url_active == '/app/PromotionLogistic' || $url_active == '/app/FileProcess/index' ||
              $url_active == '/app/orders/manage_tags' || $url_active == '/app/auction/addRulesAuction' || $url_active == '/app/logistics/introduction'
          )
          { echo 'active menu-open'; } ?>" id="mainLogisticsNav">
            <a class="menuhref" href="#">
              <i class="fa fa-truck"></i>
              <span><?= $this->lang->line('application_logistics'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('viewPickUpPoint', $user_permission)): ?>
                <li id="pickupPointRulesNav"><a class="menuhref" href="<?php echo base_url('PickupPoint') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_pickup_point');?></a></li>
              <?php endif; ?>

              <?php if (in_array('createPricingRules', $user_permission) || in_array('updatePricingRules', $user_permission) || in_array('viewPricingRules', $user_permission) || in_array('deletePricingRules', $user_permission)) { ?>
                <li id="shippingPricingRulesNav"><a class="menuhref" href="<?php echo base_url('shippingpricingrules') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_shipping_pricing'); ?></a></li>
              <?php } ?>

              <?php if (in_array('createCarrierRegistration', $user_permission) || in_array('updateCarrierRegistration', $user_permission) || in_array('viewCarrierRegistration', $user_permission) || in_array('deleteCarrierRegistration', $user_permission)) { ?>
                <li id="carrierRegistrationNav" class="<?php if($url_active == "/app/shippingcompany/index"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('shippingcompany/index') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_list_shipping_company'); ?></a></li>
              <?php } ?>

              <?php /* if (in_array('createPromotionsLogistic', $user_permission) || in_array('updatePromotionsLogistic', $user_permission) || in_array('viewPromotionsLogistic', $user_permission) || in_array('deletePromotionsLogistic', $user_permission)) { ?>
                <?php if ((int)$this->session->userdata['usercomp'] == 1) { ?>
                  <li id="logisticPromotionAdminNav" class="<?php if($url_active == "/app/PromotionLogistic"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('PromotionLogistic') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_logistic_promotion_admin'); ?></a></li>
                <?php } else { ?>
                  <li id="logisticPromotionNav" class="<?php if($url_active == "/app/PromotionLogistic/seller"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('PromotionLogistic/seller') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_logistic_promotion'); ?></a></li>
                <?php } ?>
              <?php } */ ?>

                <?php if (in_array('createCarrierRegistration', $user_permission)) { ?>
                    <li id="navFileProcess" class="<?php if($url_active == "/app/FileProcess/index"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('FileProcess/index') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_file_process');?></a></li>
                <?php } ?>

              <li id="pageTracking"><a class="menuhref" href="<?php echo base_url('rastreio'); ?>" target="_blank"><i class="fa fa-circle-o"></i> <?= $this->lang->line('page_tracking'); ?></a></li>
              <li id="manageOrdersTagsNav" class="<?php if($url_active == "/app/orders/manage_tags"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('orders/manage_tags') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage tags'); ?></a></li>

              <?php if (in_array('createAuctionRules', $user_permission) || in_array('updateAuctionRules', $user_permission) || in_array('viewAuctionRules', $user_permission) || in_array('deleteAuctionRules', $user_permission)) { ?>
                <li id="auctionRulesNav" class="<?php if($url_active == "/app/auction/addRulesAuction"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('auction/introduction') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage rules'); ?></a></li>
              <?php } ?>

              <?php if (in_array('createIntegrationLogistic', $user_permission) || in_array('updateIntegrationLogistic', $user_permission) || in_array('viewIntegrationLogistic', $user_permission) || in_array('deleteIntegrationLogistic', $user_permission)) : ?>
                <li id="manageLogisticIntegrationsNav" class="<?php if($url_active == "/app/logistics/introduction"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('logistics/introduction') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_logistic'); ?></a></li>
              <?php endif; ?>

              <li id="manageLogisticNav" class="<?php if($url_active == "/app/logistics/introduction_manage_logistic"){ echo 'active';} ?>"><a class="menuhref" href="<?=base_url('logistics/introduction_manage_logistic') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_logisticsnew'); ?></a></li>
            </ul>
          </li>
        <?php endif; ?>

        <?php if (in_array('createClients', $user_permission) || in_array('updateClients', $user_permission) || in_array('viewClients', $user_permission) || in_array('deleteClients', $user_permission)) : ?>
          <li class="treeview  <?php if(
              // Clients
              $url_active == '/app/clients/create' || $url_active == '/app/clients'
          )
          { echo 'active menu-open'; } ?>" id="mainClientsNav">
            <a class="menuhref" href="#">
              <i class="fa fa-address-book"></i>
              <span><?= $this->lang->line('application_clients'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('createClients', $user_permission)) : ?>
                <li id="addClientsNav" class="<?php if($url_active == "/app/clients/create"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('clients/create') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_add_clients'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('updateClients', $user_permission) || in_array('viewClients', $user_permission) || in_array('deleteClients', $user_permission)) : ?>
                <li id="manageClientsNav" class="<?php if($url_active == "/app/clients"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('clients') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_clients'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>

        <?php if (in_array('createProviders', $user_permission) || in_array('updateProviders', $user_permission) || in_array('viewProviders', $user_permission) || in_array('deleteProviders', $user_permission)) : ?>
          <li class="treeview <?php if(
              // Providers
              $url_active == '/app/providers/create' || $url_active == '/app/providers' || $url_active == '/app/providers/listindicacao'
          )
          { echo 'active menu-open'; } ?>" id="mainProvidersNav">
            <a class="menuhref" href="#">
              <i class="fa fa-plane"></i>
              <span><?= $this->lang->line('application_providers'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('createProviders', $user_permission)) : ?>
                <?php if ((int)$this->session->userdata['usercomp'] == 1) { ?>
                  <li id="addProvidersNav" class="<?php if($url_active == "/app/providers/create"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('providers/create') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_add_providers'); ?></a></li>
                <?php } else { ?>
                  <li id="addProvidersNav" class="<?php if($url_active == "/app/providers/createsimplified"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('providers/createsimplified') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_add_providers'); ?></a></li>
                <?php } ?>
              <?php endif; ?>
              <?php if (in_array('updateProviders', $user_permission) || in_array('viewProviders', $user_permission) || in_array('deleteProviders', $user_permission)) : ?>
                <li id="manageProvidersNav" class="<?php if($url_active == "/app/providers"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('providers') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_providers'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('updateProviders', $user_permission) || in_array('viewProviders', $user_permission) || in_array('deleteProviders', $user_permission)) : ?>
                <li id="manageProvidersNav" class="<?php if($url_active == "/app/providers/listindicacao"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('providers/listindicacao') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_providers_program'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>

        <?php if (in_array('createReceivables', $user_permission) || in_array('updateReceivables', $user_permission) || in_array('viewReceivables', $user_permission) || in_array('deleteReceivables', $user_permission)) : ?>
          <li class="treeview <?php if(
              // Receivables
              $url_active == '/app/receivables/account' || $url_active == '/app/receivables'
          )
          { echo 'active menu-open'; } ?>" id="mainReceivableNav">
            <a class="menuhref" href="#">
              <i class="fa fa-money"></i>
              <span><?= $this->lang->line('application_receivables'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('createReceivables', $user_permission)) : ?>
                <li id="addReceivableNav" class="<?php if($url_active == "/app/receivables/account"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('receivables/account') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_account'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('updateReceivables', $user_permission) || in_array('viewReceivables', $user_permission) || in_array('deleteReceivables', $user_permission)) : ?>
                <li id="manageReceivableNav" class="<?php if($url_active == "/app/receivables"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('receivables') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_receivables'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>


        <?php
        //DESATIVADO Integrações de Sistema - 10/06/20 - PEDRO HENRIQUE
        if ((in_array('createIntegrations', $user_permission) ||
            in_array('updateIntegrations', $user_permission) ||
            in_array('viewIntegrations', $user_permission) ||
            in_array('deleteIntegrations', $user_permission)) && false
        ) : ?>
          <li class="treeview" id="mainIntegrationNav">
            <a class="menuhref" href="#">
              <i class="fa fa-sitemap"></i>
              <span><?= $this->lang->line('application_integrations'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('createIntegrations', $user_permission)) : ?>
                <li id="addIntegrationNav"><a class="menuhref" href="<?php echo base_url('integrations/create') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_add_integration'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('updateIntegrations', $user_permission) || in_array('viewIntegrations', $user_permission) || in_array('deleteIntegrations', $user_permission)) : ?>
                <li id="manageIntegrationNav"><a class="menuhref" href="<?php echo base_url('integrations') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_integrations'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>

        <?php if (in_array('productsMarketplace', $user_permission) || in_array('updateMarketplace', $user_permission) || in_array('viewMarketplace', $user_permission) || in_array('deleteMarketplace', $user_permission)) : ?>
          <li class="treeview  <?php if(
              // Marketplace
              $url_active == '/app/products/allocate' || $url_active == '/app/calendar/index'
          )
          { echo 'active menu-open'; } ?>" id="mainMarketPlaceNav">
            <a class="menuhref" href="#">
              <i class="fa fa-cloud-upload"></i>
              <span><?= $this->lang->line('application_runmarketplaces'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('productsMarketplace', $user_permission)) : ?>
                <li id="allocProductNav" class="<?php if($url_active == "/app/products/allocate"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('products/allocate') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_alloc_product'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('viewMarketplace', $user_permission)) : ?>
                <li id="loadIntegrationNav" class="<?php if($url_active == "/app/calendar/index"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('calendar/index') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_job_schedule'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>

        <?php if ((in_array('viewReports', $user_permission) || ($hasReportGroups)) && count($menuMetabse_seller)) : ?>

          <li class="treeview <?php if(
              // reports
              $url_active == '/app/reports/report/stock_coverage' ||  $url_active == '/app/reports/report/marketplace_quotes' ||  $url_active == '/app/reports/report/sales_monitoring' ||
              $url_active == '/app/reports/report/sales_forecast' ||  $url_active == '/app/reports/report/bestsellers' ||  $url_active == '/app/reports/report/sales' ||
              $url_active == '/app/reports/report/test' ||  $url_active == '/app/reports/report/sales_x_product' ||  $url_active == '/app/reports/report/sales_x_negotiated_products' ||
              $url_active == '/app/reports/report/sales_x_seller'
          )
          { echo 'active menu-open'; } ?>" id="reportNav">
            <a class="menuhref" href="#">
              <i class="fa fa-cloud-upload"></i>
              <span><?= $this->lang->line('application_reports'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php foreach ($menuMetabse_seller as $metabaseSeller) :?>
                <li id="<?= $metabaseSeller['selector_menu'] ?>" class="<?php if($url_active == "/app/reports/report/" . $metabaseSeller['name_href']){ echo 'active';} ?>"><a class="menuhref" href="<?= base_url('reports/report/' . $metabaseSeller['name_href']) ?>"><i class="fa fa-circle-o"></i><?= $metabaseSeller['title'] ?></a></li>
              <?php endforeach; ?>
            </ul>
          </li>
        <?php endif; ?>

        <?php if ((in_array('viewManagementReport', $user_permission) || ($hasReportGroupsAdmin)) && count($menuMetabse_adm)) : ?>
          <li class="treeview <?php if(
              // reports
              $url_active == '/app/reports/report/loggi_monitoring' ||  $url_active == '/app/reports/report/step_alert' ||  $url_active == '/app/reports/report/dashboard_onboarding_cs' ||
              $url_active == '/app/reports/report/quality_indicator' ||  $url_active == '/app/reports/report/logistics' ||  $url_active == '/app/reports/report/shopkeeper_x_products' ||
              $url_active == '/app/reports/report/number_quotes_x_sales' ||  $url_active == '/app/reports/report/commission_report' ||  $url_active == '/app/reports/report/general_panel_sales' ||
              $url_active == '/app/reports/report/stock_break' || $url_active == '/app/reports/report/seller_index' || $url_active == '/app/reports/report/phase_validation' ||
              $url_active == '/app/reports/report/sales_adm' || $url_active == '/app/reports/report/sales_hour_hour'
          )
          { echo 'active menu-open'; } ?>" id="reportNavAdm">
            <a class="menuhref" href="#">
              <i class="fas fa-file-contract"></i>
              <span><?= $this->lang->line('application_management_report'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php foreach ($menuMetabse_adm as $metabaseAdm) : ?>
                <li id="<?= $metabaseAdm['selector_menu'] ?>" class="<?php if($url_active == "/app/reports/report/" . $metabaseAdm['name_href']){ echo 'active';} ?>"><a class="menuhref" href="<?= base_url('reports/report/' . $metabaseAdm['name_href']) ?>"><i class="fa fa-circle-o"></i><?= $metabaseAdm['title'] ?></a></li>
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

        <?php if ((in_array('reportProblem', $user_permission))  && (!is_null($token_agidesk_conectala))) : ?>
          <li id="report_problem_url"><a class="menuhref" target="_blank" href="https://conectala.agidesk.com/br/servicos/secoes/suporte-ti?access_token=<?php echo $token_agidesk_conectala ?>"><i class="fas fa-exclamation-triangle"></i> <?= $this->lang->line('application_report_problem'); ?></a></li>
        <?php endif; ?>

     
        <?php if (in_array('viewProfile', $user_permission)) : ?>
          <li id="profileNav" class="<?php if($url_active == "/app/users/profile/"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('users/profile/') ?>"><i class="fa fa-user-o"></i> <span><?= $this->lang->line('application_profile'); ?></span></a></li>
        <?php endif; ?>
        <li class="treeview  <?php if(
            // Integration
            $url_active == '/app/stores/integration' || $url_active == '/app/integrations/log_integration' || $url_active == '/app/integrations/job_integration'
        )
        { echo 'active menu-open'; } ?>" id="mainIntegrationApiNav">
          <a class="menuhref" href="#">
            <i class="fas fa-plug"></i>
            <span> <?= $this->lang->line('application_integration'); ?></span>
            <span class="pull-right-container">
              <i class="fa fa-angle-left pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li id="requestIntegration" class="<?php if($url_active == "/app/stores/integration"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('stores/integration') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_request_integration'); ?></a></li>
            <li id="logIntegration" class="<?php if($url_active == "/app/integrations/log_integration"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('integrations/log_integration') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_history_integration'); ?></a></li>
            <li id="jobIntegration" class="<?php if($url_active == "/app/integrations/job_integration"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('integrations/job_integration') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_manage_integrations'); ?></a></li>
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
              <li id="webhookIntegration" class="<?php if($url_active == "/app/stores/webhookIntegration"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('stores/webhookIntegration') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_request_webhook'); ?></a></li>
          </ul>
        </li>

        <?php if (in_array('marketplaces_integrations', $user_permission)) : ?>
<!--          <li class="treeview --><?php //if(
//              // marketplaces integrations
//              $url_active == '/app/loginML/chooseStore'
//          )
//          { echo 'active menu-open'; } ?><!--" id="mainIntegrationMarketplaceNav">-->
<!--            <a class="menuhref" href="#">-->
<!--              <i class="fas fa-plug"></i>-->
<!--              <span> --><?php //= $this->lang->line('application_marketplaces_integrations'); ?><!--</span>-->
<!--              <span class="pull-right-container">-->
<!--                <i class="fa fa-angle-left pull-right"></i>-->
<!--              </span>-->
<!--            </a>-->
<!--            <ul class="treeview-menu">-->
<!--                <li id="MLIntegrationMarketplace" class="--><?php //if($url_active == "/app/loginML/chooseStore"){ echo 'active';} ?><!--"><a class="menuhref" href="--><?php //echo base_url('loginML/index') ?><!--"><i class="fa fa-circle-o"></i> --><?php //= $this->lang->line('application_mercado_livre'); ?><!--</a></li>-->
<!--            </ul>-->
<!--          </li>-->
        <?php endif; ?>

        <?php if(in_array('viewContracts', $user_permission) || in_array('viewContractSignatures', $user_permission) ): ?>
        <li class="treeview <?php if(
            // Contracts
            $url_active == '/app/contracts' || $url_active == '/app/contractSignatures'
        )
        { echo 'active menu-open'; } ?>" id="mainContractsNav">
            <a class="menuhref" href="#">
                <i class="far fa-file"></i>
                <span><?=$this->lang->line('application_contracts');?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
            </span>
            </a>
            <ul class="treeview-menu">
              <?php if(in_array('viewContracts', $user_permission)): ?>
                <li id="contracts" class="<?php if($url_active == "/app/contracts"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('contracts') ?>"><i class="fa fa-circle-o"></i><?=$this->lang->line('application_contracts');?></a></li>
              <?php endif; ?>  
              <?php if(in_array('viewContractSignatures', $user_permission)): ?>
                <li id="contractSignatures" class="<?php if($url_active == "/app/contractSignatures"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('contractSignatures') ?>"><i class="fa fa-circle-o"></i><?=$this->lang->line('application_contract_signatures');?></a></li>
              <?php endif; ?>    
            </ul>
        </li>
        <?php endif; ?>
              <li class="treeview <?php if($url_active == "/app/users/setting/"){ echo 'active';} ?>" id="MainsettingNav" style="height: auto;">
                <a class="menuhref" href="#">
                <i class="fa fa-table"></i> <span><?=$this->lang->line('application_settings');?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
                </a>
                <ul class="treeview-menu">
                  <li id="UsersettingNav" ><a class="menuhref <?php if($url_active == "/app/users/setting"){ echo 'active';} ?>" href="<?php echo base_url('users/setting/') ?>"><i class="fa fa-circle-o"></i><?=$this->lang->line('application_user');?></a></li>
                  <?php if(in_array('viewIntegrationsSettings', $user_permission) ): ?>
                  <li id="IntegrationsettingNav">
                    <a class="menuhref active<?php if($url_active == "/app/IntegrationsSettings/index"){ echo 'active';} ?>" href="<?php echo base_url('IntegrationsSettings/')?>"><i class="fa fa-circle-o"></i>Integração</a>
                  </li>
                  <?php if ($this->model_settings->getValueIfAtiveByName('external_marketplace_integration')): ?>
                      <li id="MarketplaceExternalsNav"><a class="menuhref" href="<?=base_url('Marketplace/Externals/list') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_external_integration');?></a></li>
                  <?php endif; ?>
                  <?php endif; ?>
                </ul>
              </li>
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
          <li class="treeview <?php if(
              // empresas
              $url_active == '/app/ShopkeeperForm/list' || $url_active == '/app/ShopkeeperForm'
          )
          { echo 'active menu-open'; } ?>" id="mainShopkeeperformNav">
            <a class="menuhref" href="#">
              <i class="fa fa-sitemap"></i>
              <span><?= $this->lang->line('application_shopkeeper_form'); ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if (in_array('createFieldShopkeeperForm', $user_permission)) : ?>
                <li id="addShopkeeperformNav" class="<?php if($url_active == "/app/ShopkeeperForm/list"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('ShopkeeperForm/list') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_adm_shopkeeper_form'); ?></a></li>
              <?php endif; ?>
              <?php if (in_array('updateShopkeeperForm', $user_permission)) : ?>
                <li id="manageShopkeeperformNav" class="<?php if($url_active == "/app/ShopkeeperForm"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('ShopkeeperForm') ?>"><i class="fa fa-circle-o"></i> <?= $this->lang->line('application_add_fields_form'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>

      <?php endif; ?>
      <li class="<?php if($url_active == "/app/suggestions"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('suggestions') ?>"><i class="fa fa-lightbulb-o" aria-hidden="true"></i> <span><?= $this->lang->line('application_manage_suggestions'); ?></span></a></li>

    <?php  if (in_array('marketplaces_integrations', $user_permission)) : ?>
    <li class="treeview  <?php if(
        // center notifications
        $url_active == '/app/templateEmail/index' || $url_active == '/app/templateEmailSchedule/index'
    )
    { echo 'active menu-open'; } ?>" id="mainIntegrationMarketplaceNav">
        <a class="menuhref" href="#">
            <i class="fas fa-bullhorn"></i>
            <span> <?= $this->lang->line('application_notification_center'); ?></span>
            <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
            </span>
        </a>
        <ul class="treeview-menu">
            <li id="MLIntegrationMarketplace" class="<?php if($url_active == "/app/templateEmail/index"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('templateEmail/index') ?>"><i class="fa fa-circle-o"></i> <?='Criar modelo de email' //$this->lang->line('application_mercado_livre'); ?></a></li>
            <li id="MLIntegrationMarketplace" class="<?php if($url_active == "/app/templateEmailSchedule/index"){ echo 'active';} ?>"><a class="menuhref" href="<?php echo base_url('templateEmailSchedule/index') ?>"><i class="fa fa-circle-o"></i> <?=$this->lang->line('application_template_email_schedule'); ?></a></li>
          </ul>
    </li>
    <?php endif; ?>

      <!-- user permission info -->
      <li><a class="menuhref" href="<?php echo base_url('auth/logout') ?>"><i class="glyphicon glyphicon-log-out"></i> <span><?= $this->lang->line('application_logout'); ?></span></a></li>

    </ul>

      <div id="emptyMsg" style="display:none;margin-left: 1em;"><span>Nenhum menu encontrado</span></div>

  </section>
  <!-- /.sidebar -->

</aside>
