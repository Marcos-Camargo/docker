<?php
	require_once APPPATH."/third_party/load.koolreport.php";
    use \koolreport\widgets\google\ColumnChart;
    use \koolreport\widgets\google\Gauge;
    use \koolreport\widgets\google\BarChart;

?>
<!--
SW Serviços de Informática 2019

Dashboard Main

-->
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_control_panel";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->

        <div class="row dashboard-boxs">
        	<div class="col-md-12 col-xs-12">
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
            <div class="col-lg-12 col-xs-12 mb-4">
                <?=$metabase_graph_seller_index?>
            </div>
            <?php if($dashboard_conecta == "1"){ ?>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?php echo $total_products ?></h3>

                        <p><?=$this->lang->line('application_total_products');?></p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="<?php echo base_url('products/') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?php echo $total_products_active ?></h3>

                        <p><?=$this->lang->line('application_total_products_active');?></p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('products', 'products_complete');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
              <!--      <a href="<?php echo base_url('products/') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a> -->
                </div>
            </div>
            <!--
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?=$total_products_campaigns?></h3>
                        <p><?=$this->lang->line('application_total_products_campaign')?></p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-money" aria-hidden="true"></i>
                    </div>
                    <a href="<?php echo base_url('campaigns/storeIndex') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
               	</div>
            </div>-->
                
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?=$total_products_incomplet?></h3>
                        <p><?=$this->lang->line('application_incomplete_active_products')?></p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-warning" aria-hidden="true"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('products', 'products_incomplete');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?=$total_products_without_stock?></h3>
                        <p><?=$this->lang->line('application_products_without_stock')?></p>
                    </div>
                    <div class="icon">
                        <i class="<?=$total_products_without_stock == 0 ? 'ion ion-android-home' : 'fa fa-bomb'?>" aria-hidden="true"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('products', 'products_without_stock');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?=$total_products_out_price?></h3>
                        <p><?=$this->lang->line('application_products_out_price')?></p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-money" aria-hidden="true"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('products', 'products_out_price');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>


            <!--<div class="col-lg-3 col-xs-6">
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?=$total_orders_pending_action?></h3>
                        <p><?=$this->lang->line('application_orders_pending_action')?></p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-pencil" aria-hidden="true"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('orders', 'orders_pending_action');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>-->
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?=$total_orders_waiting_invoice?></h3>
                        <p><?=$this->lang->line('application_orders_waiting_invoice')?></p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('orders', 'orders_waiting_invoice');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?=$total_order_awaiting_collection?></h3>
                        <p><?=$this->lang->line('application_orders') . ' ' . $this->lang->line('application_order_4')?></p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-shopping-basket" aria-hidden="true"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('orders', 'order_awaiting_collection');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?=$total_orders_in_transport?></h3>
                        <p><?=$this->lang->line('application_orders') . ' ' . $this->lang->line('application_order_5')?></p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('orders', 'orders_in_transport');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?=$total_orders_delivered?></h3>
                        <p><?=$this->lang->line('application_orders_delivered')?></p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('orders', 'orders_delivered');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>


            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?=$total_orders?></h3>
                        <p><?=$this->lang->line('application_total_orders')?></p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <a href="<?php echo base_url('orders/') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?=$total_orders_delayed_post?></h3>
                        <p><?=$this->lang->line('application_orders_delayed_post')?></p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-calendar-times-o" aria-hidden="true"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('orders', 'orders_delayed_post');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3>SAC</h3>
                        <p>&nbsp;</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <a href="https://agidesk.com/br/login" target="_blank" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>

        <?php if(in_array('admDashboard', $permissions)) {   // Only system admin ?>
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-blue-light">
              <div class="inner">
                <h3><?php echo $total_users; ?></h3>

                <p><?=$this->lang->line('application_total_users');?></p>
              </div>
              <div class="icon">
                <i class="ion ion-android-people"></i>
              </div>
              <a href="<?php echo base_url('users/') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-blue-light">
              <div class="inner">
	                <h3><?php echo $total_companies ?></h3>
	                <p><?=$this->lang->line('application_total_companies');?></p>
              </div>
              <div class="icon">
                  <i class="fas fa-building"></i>
              </div>
              <a href="<?php echo base_url('company/') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>

          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-blue-light">
                <div class="inner">
                    <h3><?php echo $total_stores ?></h3>
                    <p><?=$this->lang->line('application_total_stores');?></p>
                </div>
                <div class="icon">
                    <i class="fas fa-store"></i>
                </div>
                <a href="<?php echo base_url('stores/') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-blue-light">
                <div class="inner">
                    <h3><?php echo $total_stores_active ?></h3>
                    <p><?=$this->lang->line('application_total_stores_active');?></p>
                </div>
                <div class="icon">
                    <i class="fas fa-store"></i>
                </div>
                <a href="<?php echo base_url('stores/') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-blue-light">
              <div class="inner">
	                <h3><?php echo $total_erros_batch ?></h3>
	                <p><?=$this->lang->line('application_total_errors_batch');?></p>
              </div>
              <div class="icon">
                <i class="ion ion-android-home"></i>
              </div>
              <a href="<?php echo base_url('listlog/') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          
          <!-- remoção do bling
          <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-blue-light">
              <div class="inner">
	                <h3><?php echo $total_produtos_sem_integracao ?></h3>
	                <p><?=$this->lang->line('application_total_products_no_integration');?></p>
              </div>
              <div class="icon">
                <i class=" <?php if ($total_produtos_sem_integracao==0) { echo 'ion ion-android-home'; } else { echo 'fa fa-bomb'; } ?>" aria-hidden="true"></i>
              </div>
              <a href="<?php echo base_url('products/produtosIntegracao') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          ---> 
          
          <!--- remoção do Bling
          <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-blue-light">
              <div class="inner">
	                <h3><?php echo $total_produtos_sem_categoria_bling ?></h3>
	                <p><?=$this->lang->line('application_products_to_categorize_bling');?></p>
              </div>
              <div class="icon">
                <i class=" <?php if ($total_produtos_sem_categoria_bling==0) { echo 'ion ion-android-home'; } else { echo 'fa fa-bomb'; } ?>" aria-hidden="true"></i>
              </div>
               <a href="<?php echo base_url('waitingIntegration/semintegracao') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          -->
          
          <!---- Remoção do frete rápido
          <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-blue-light">
              <div class="inner">
	                <h3><?php echo $total_lojas_sem_terminar_cadastro_fr ?></h3>
	                <p><?=$this->lang->line('application_store_to_be_register_frete_rapido');?></p>
              </div>
              <div class="icon">
                <i class=" <?php if ($total_lojas_sem_terminar_cadastro_fr==0) { echo 'ion ion-android-home'; } else { echo 'fa fa-bomb'; } ?>" aria-hidden="true"></i>
              </div>
               <a href="<?php echo base_url('stores/') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          --->
          
          <!--- remoção do bling
          <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-blue-light">
              <div class="inner">
	                <h3><?php echo $total_campos_sem_integracao_bling ?></h3>
	                <p><?=$this->lang->line('application_ML_fields_categories_bling');?></p>
              </div>
              <div class="icon">
                <i class=" <?php if ($total_campos_sem_integracao_bling==0) { echo 'ion ion-android-home'; } else { echo 'fa fa-bomb'; } ?>" aria-hidden="true"></i>
              </div>
               <a href="<?php echo base_url('attributesMLIntegrate/index') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          -->
          
           <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-blue-light">
              <div class="inner">
	                <h3><?php echo $total_pedidos_sem_frete ?></h3>
	                <p><?=$this->lang->line('application_orders_to_hire_freight');?></p>
              </div>
              <div class="icon">
                <i class=" <?php if ($total_pedidos_sem_frete==0) { echo 'ion ion-android-home'; } else { echo 'fa fa-bomb'; } ?>" aria-hidden="true"></i>
              </div>
               <a href="<?php echo base_url('orders/semfrete') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          
          <?php if (ENVIRONMENT != 'production' && ENVIRONMENT !== 'production_x'): ?>
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-blue-light">
              <div class="inner">
	                <h3><?php echo $total_pedidos_entregues_marcar_mkt ?></h3>
	                <p><?=$this->lang->line('application_orders_delivered_to_mark_marketplace');?></p>
              </div>
              <div class="icon">
                <i class=" <?php if ($total_pedidos_entregues_marcar_mkt==0) { echo 'ion ion-android-home'; } else { echo 'fa fa-bomb'; } ?>" aria-hidden="true"></i>
              </div>
               <a href="<?php echo base_url('orders/deliverySentToMarketplace') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <?php endif; ?>
          
  			<!---- remoção do frete rápido
          <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-blue-light">
              <div class="inner">
	                <h2><strong><?php echo $total_novas_categorias_lojas ?></strong><small> <?=$this->lang->line('application_store_categorys_news');?></small> 
	                	<strong><?php echo '/'.$total_categorias_cadastrar_FR_expiradas ?></strong><small> <?=$this->lang->line('application_store_categorys_expired');?></small> </h2>
	                <p><?=$this->lang->line('application_new_categories_stores_frete_rapido');?></p>
              </div>
              <div class="icon">
                <i class=" <?php if (($total_novas_categorias_lojas==0) && ($total_categorias_cadastrar_FR_expiradas==0)) { echo 'fa fa-truck'; } else { echo 'fa fa-bomb'; } ?>" aria-hidden="true"></i>
              </div>
               <a href="<?php echo base_url('stores/avisoFreteRapido') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
        	-->
        	
          <!-- ./col -->
          <?php }   // endif onlyadmin ?>
          <?php } ?>
        </div>
        <!-- /.row -->
        <div class="row">
            <div class="col-md-12">
                <?=$metabase_graph?>
            </div>
        </div>
    </section>
</div>
  <!-- /.content-wrapper -->
<script type="text/javascript">
$(document).ready(function() {
    $("#dashboardMainMenu").addClass('active');
});
const openBoxFilter = (type, filter) => {
    var url = "<?=base_url()?>" + type + '/filter';
    var form = $(`<form action="${url}" method="post" role="form">
                    <input type="hidden" name="do_filter" value="" />
                    <input type="text" name="${filter}" value="true" />
                  </form>`);
    $('body').append(form);
    form.submit();
}
</script>