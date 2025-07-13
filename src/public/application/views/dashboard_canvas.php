<?php
	require_once APPPATH."/third_party/load.koolreport.php";
    use \koolreport\widgets\google\ColumnChart;
    use \koolreport\widgets\google\Gauge;
    use \koolreport\widgets\google\BarChart;

?>
  <script src="<?php echo base_url('assets/plugins/SW/gauge.min.js') ?>"></script>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['pageinfo'] = "application_control_panel";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
        <div class="row">
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-olive">
              <div class="inner">
                <h3><?php echo $total_products ?></h3>

                <p><?=$this->lang->line('application_total_products');?></p>
              </div>
              <div class="icon">
                <i class="ion ion-bag"></i>
              </div>
              <a href="<?php echo base_url('products/') ?>" class="small-box-footer"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-green">
              <div class="inner">
                <h3><?php echo $total_paid_orders ?></h3>

                <p><?=$this->lang->line('application_total_paid_orders');?></p>
              </div>
              <div class="icon">
                <i class="ion ion-stats-bars"></i>
              </div>
              <a href="<?php echo base_url('orders/') ?>" class="small-box-footer"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-purple">
              <div class="inner">
                <h3><?php echo $total_users; ?></h3>
                <p><?=$this->lang->line('application_orders_waiting');?></p>
              </div>
              <div class="icon">
                <i class="fa fa-thermometer-empty"></i>
              </div>
              <a href="<?php echo base_url('users/') ?>" class="small-box-footer"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-red">
              <div class="inner">
	                <h3><?php echo $total_companies ?></h3>
	                <p><?=$this->lang->line('application_overdue_orders');?></p>
              </div>
              <div class="icon">
                <i class="fa fa-thermometer-full" aria-hidden="true"></i>
              </div>
              <a href="<?php echo base_url('company/') ?>" class="small-box-footer"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
        </div>
        <!-- /.row -->
        <?php if($usergroup<12) { ?>
        <div class="row">
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-yellow">
              <div class="inner">
                <h3><?php echo $total_products ?></h3>
                <p><?=$this->lang->line('application_integrations');?></p>
              </div>
              <div class="icon">
                <i class="fa fa-cloud-upload" aria-hidden="true"></i>
              </div>
              <a href="<?php echo base_url('calendar/') ?>" class="small-box-footer"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-red">
              <div class="inner">
                <h3><?php echo $total_paid_orders ?></h3>
                <p><?=$this->lang->line('messages_errors');?></p>
              </div>
              <div class="icon">
                <i class="fa fa-bomb" aria-hidden="true"></i>
              </div>
              <a href="<?php echo base_url('orders/') ?>" class="small-box-footer"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
        <?php if($usergroup<5) {   // Only system admin ?>
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-aqua">
              <div class="inner">
                <h3><?php echo $total_users; ?></h3>

                <p><?=$this->lang->line('application_total_users');?></p>
              </div>
              <div class="icon">
                <i class="ion ion-android-people"></i>
              </div>
              <a href="<?php echo base_url('users/') ?>" class="small-box-footer"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-maroon">
              <div class="inner">
	                <h3><?php echo $total_companies ?></h3>
	                <p><?=$this->lang->line('application_total_companies');?></p>
              </div>
              <div class="icon">
                <i class="ion ion-android-home"></i>
              </div>
              <a href="<?php echo base_url('company/') ?>" class="small-box-footer"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <?php }   // endif onlyadmin ?>
        </div>
        <!-- /.row -->
		<?php } // No show admin information ?>
    </section>
    <!-- /.content -->
    <section class="content">    
 
 <div class="row">
    <div class="col-lg-3 col-xs-12">
        <!-- TABLE: SALES -->
        <div class="box box-warning">
            <div class="box-header with-border box-green">
                <h3 class="box-title">Flash do Mês Atual</h3>
            </div>
            <div class="box-body">
<?php    
	$tot = 0; $fat = 0; $ent = 0;
	foreach ($MonthGauges as $row) {
		if ($row['ps'] == '3') {
			$fat = $fat + $row['qtd'];	
		} elseif ($row['ps'] == '4') {
			$ent = $ent + $row['qtd'];	
		}	
		$tot = $tot + $row['qtd'];
	}	
	$fat = $fat + $ent;
?>
<canvas data-type="linear-gauge" data-width="100" data-height="350" data-border-radius="0" data-borders="0" data-bar-begin-circle="15" data-minor-ticks="10" data-value="<?= $tot ?>" data-min-value="0" data-max-value="80" data-title="Vendas" data-major-ticks="0,10,20,30,40,50,60,70" data-ticks-width="18" data-ticks-width-minor="7.5" data-bar-width="5" data-highlights="false" data-color-value-box-shadow="true" data-value-box-stroke="0" data-color-value-box-background="false" data-value-int="1" data-value-dec="0" width="100" height="350" style="width: 100px; height: 350px;"></canvas>
<canvas data-type="linear-gauge" data-width="100" data-height="350" data-border-radius="0" data-borders="0" data-bar-begin-circle="15" data-minor-ticks="10" data-value="<?= $fat ?>" data-min-value="0" data-max-value="80" data-title="Faturadas" data-major-ticks="0,10,20,30,40,50,60,70" data-ticks-width="18" data-ticks-width-minor="7.5" data-bar-width="5" data-highlights="false" data-color-value-box-shadow="true" data-value-box-stroke="0" data-color-value-box-background="false" data-value-int="1" data-value-dec="0" width="100" height="350" style="width: 100px; height: 350px;"></canvas>
<canvas data-type="linear-gauge" data-width="100" data-height="350" data-border-radius="0" data-borders="0" data-bar-begin-circle="15" data-minor-ticks="10" data-value="<?= $ent ?>" data-min-value="0" data-max-value="80" data-title="Entregues" data-major-ticks="0,10,20,30,40,50,60,70" data-ticks-width="18" data-ticks-width-minor="7.5" data-bar-width="5" data-highlights="false" data-color-value-box-shadow="true" data-value-box-stroke="0" data-color-value-box-background="false" data-value-int="1" data-value-dec="0" width="100" height="350" style="width: 100px; height: 350px;"></canvas>
<canvas data-type="radial-gauge" data-width="180" data-height="180" data-units="VENDAS" data-title="false" data-min-value="0" data-max-value="120" data-value="<?= $tot ?>" data-animate-on-init="true" data-major-ticks="0,20,40,60,80,100,120" data-minor-ticks="2" data-stroke-ticks="true" data-highlights="[{&quot;from&quot;: 80, &quot;to&quot;: 120, &quot;color&quot;: &quot;rgba(0, 255, 0, .75)&quot;}]" data-color-plate="#FFFFFF" data-color-major-ticks="#000000" data-color-minor-ticks="#000000" data-color-title="#000000" data-color-units="#000000" data-color-numbers="#000000" data-color-needle-start="rgba(240, 128, 128, 1)" data-color-needle-end="rgba(255, 160, 122, .9)" data-value-box="true" data-font-value="Repetition" data-font-numbers="Repetition" data-animated-value="true" data-borders="false" data-border-shadow-width="0" data-needle-type="arrow" data-needle-width="2" data-needle-circle-size="7" data-needle-circle-outer="true" data-needle-circle-inner="false" data-animation-duration="1500" data-animation-rule="linear" data-ticks-angle="250" data-start-angle="55" data-color-needle-shadow-down="#333" data-value-box-width="0" data-value-int="1" data-value-dec="0" width="180" height="180" style="width: 180px; height: 180px; visibility: visible;"></canvas>
             </div><!-- /.box-body -->
            <!-- /.box-footer -->
        </div><!-- /.box -->
    </div><!-- /.col -->
    <div class="col-lg-3 col-xs-12">
        <!-- TABLE: SALES -->
        <div class="box box-warning">
            <div class="box-header with-border box-green">
                <h3 class="box-title">ALERTAS!!!</h3>
            </div>
            <div class="box-body">
             </div><!-- /.box-body -->
            <!-- /.box-footer -->
        </div><!-- /.box -->
    </div><!-- /.col -->
    <div class="col-lg-6 col-xs-12">
        <!-- TABLE: SALES -->
        <div class="box box-warning">
            <div class="box-header with-border box-green">
                <h3 class="box-title">Vendas nos últimos 3 meses</h3>
            </div>
            <div class="box-body">
    <?php
/*
    ColumnChart::create(array(
        "title"=>"Por MarketPlace",
        "dataSource"=>$Last3M,
        "columns"=>array(
            "mes",
            "BLING"=>array("label"=>"BLING","type"=>"number","prefix"=>"$"),
            "B2W"=>array("label"=>"B2W","type"=>"number","prefix"=>"$"),
            "ML"=>array("label"=>"ML","type"=>"number","prefix"=>"$"),
        )
    ));
*/
    BarChart::create(array(
        "title"=>"Por MarketPlace",
        "dataSource"=>$Last3M,
        "columns"=>$Last3MOrigins,
        "options"=>array(
            "isStacked"=>true
        )
    ));
?>
             </div><!-- /.box-body -->
            <!-- /.box-footer -->
        </div><!-- /.box -->
    </div><!-- /.col -->
 </div><!-- /.row -->
    </section><!-- ./ section -->
        
    <section class="content">    
 
 <div class="row">
    <div class="col-lg-8 col-xs-12">
        <!-- TABLE: LATEST ORDERS -->
        <div class="box box-warning">
            <div class="box-header with-border box-green">
                <h3 class="box-title"><?=$this->lang->line('application_latest_purchases');?></h3>
            </div>
            <div class="box-body">
                <table class="table no-margin">
                    <thead>
                    <tr>
                        <th><?=$this->lang->line('application_purchase_id');?></th>
                        <th><?=$this->lang->line('application_buyer');?></th>
                        <th><?=$this->lang->line('application_date');?></th>
                        <th><?=$this->lang->line('application_status');?></th>
                        <th><?=$this->lang->line('application_gross_amount');?></th>
                        <th><?=$this->lang->line('application_view');?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($due_amounts as $due_amount) : ?>
                        <tr>
                            <td>
                                <a href="<?= base_url(); ?>orders/view/<?= $due_amount['id'] ?>"><?= $due_amount['bill_no']; ?></a>
                            </td>
                            <td><?= $due_amount['customer_name']; ?></td>
                            <td><?= date("d-m-Y", strtotime($due_amount['date_time'])); ?></td>
                            <td>
							<?php
								if($due_amount['paid_status'] == 1) {
									echo '<span class="label label-danger">'.$this->lang->line('application_order_1').'</span>';
								}
								elseif($due_amount['paid_status'] == 2) {
									echo '<span class="label label-success">'.$this->lang->line('application_order_2').'</span>';	
								}
								elseif($due_amount['paid_status'] == 3) {
									echo '<span class="label label-warning">'.$this->lang->line('application_order_3').'</span>';	
								}
								elseif($due_amount['paid_status'] == 4) {
									echo '<span class="label label-primary">'.$this->lang->line('application_order_4').'</span>';	
								}
								elseif($due_amount['paid_status'] == 5) {
									echo '<span class="label label-primary">'.$this->lang->line('application_order_5').'</span>';	
								}
							?>	
                            </td>
                            <td><?= get_instance()->formatprice($due_amount['gross_amount']); ?></td>
                            <td>
                                <a href="<?= base_url(); ?>orders/view/<?= $due_amount['id'] ?>"
                                   class="btn btn-info"><?=$this->lang->line('application_view_purchase');?></a>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div><!-- /.box-body -->
            <!-- /.box-footer -->
        </div><!-- /.box -->
    </div><!-- /.col -->

    <div class="col-lg-4 col-xs-12">
        <!--work progress start-->
        <div class="box box-success">
            <div class="box-header with-border box-green">
                <h3 class="box-title"><?=$this->lang->line('application_stock_report');?></h3>
            </div>
            <div class="box-body">
                <table class="table table-hover personal-task">
                    <tbody>
                    <tr>
                        <td><?=$this->lang->line('application_item_qty');?></td>
                        <td><?=$this->lang->line('application_name');?></td>
                        <td><?=$this->lang->line('application_price');?></td>
                    </tr>
                    <?php $i = 1;
                    foreach ($daily_st as $daily_st) { ?>
                        <tr>
                            <td><span class="date">
                <?php $aaa = $daily_st['qty'];
                if ($aaa < 10) {
                    ?>
                    <font style="text-decoration:blink; color:#F00; font-size:18px">
                        <?php
                        echo "<span class='label label-danger'>$aaa</span>";


                        ?>
                    </font>
                    <?php

                } else {
                    echo " <span class='label label-success'>$aaa</span>";
                }
                ?>
                </span> <span class="time">
                <?php //echo $daily_st->category_name;?>
                </span></td>
                            <td><a href="<?= base_url(); ?>products/update/" onclick="myFunction(<?= $daily_st['id']; ?>)"><?php echo $daily_st['name']; ?></a></td>
                            <td><span class="price"><?php echo get_instance()->formatprice($daily_st['price']); ?></span></td>
                        </tr>
                    <?php } ?>

                    </tbody>
                    <tfoot>

                    </tfoot>
                </table>
            </div>
        </div>
        <!--work progress end-->
    </div>
    
 </div>
</section>
    
  </div>
  <!-- /.content-wrapper -->
<script type="text/javascript"><!--
function print_specific_div_content()
{
 var content = "<html>";
 content += document.getElementById("coupon_deal_id").innerHTML ;
 content += "</body>";
 content += "</html>";
 var printWin = window.open('','','left=0,top=0,width=auto,height=auto,toolbar=0,scrollbars=0,status =0');
 printWin.document.write(content);
 printWin.document.close();
 printWin.focus();
 printWin.print();
 printWin.close();
}
</script>
  <script type="text/javascript">
    $(document).ready(function() {
      $("#dashboardMainMenu").addClass('active');
    }); 

function myFunction(params) {
   alert(params);
   document.cookie = "getparam="+params+ ";" + "" + ";path=/";
}
</script>
