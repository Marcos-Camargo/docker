<style>

.card-danger.card-outline {
    border-top: 3px solid #dc3545;
}

.card-primary.card-outline {
    border-top: 3px solid #007bff;
}

.card {
    box-shadow: 0 0 1px rgb(0 0 0 / 13%), 0 1px 3px rgb(0 0 0 / 20%);
    margin-bottom: 1rem;
}
.card {
    position: relative;
    display: -ms-flexbox;
    display: flex;
    -ms-flex-direction: column;
    flex-direction: column;
    min-width: 0;
    word-wrap: break-word;
    background-color: #fff;
    background-clip: border-box;
    border: 0 solid rgba(0,0,0,.125);
    border-radius: 0.25rem;
}

.card-header {
    background-color: transparent;
    border-bottom: 1px solid rgba(0,0,0,.125);
    padding: 0.75rem 1.25rem;
    position: relative;
    border-top-left-radius: 0.25rem;
    border-top-right-radius: 0.25rem;
}

.card-body {
    -ms-flex: 1 1 auto;
    flex: 1 1 auto;
    min-height: 1px;
    padding: 1.25rem;
}


</style>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['page_now'] = "system_health"; $data['pageinfo'] = "application_dashboard";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
        <div class="col-md-12">
              
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title">Alertas</h3>
                </div>
                <div class="card-body">         
                
                <?php 
                $sellercenter = ''; 
                foreach($events_month as $event) {
                    $box_color = "bg-red"; // outras cores bg-yellow bg-aqua bg-green bg-red 
                    if ($event['status']) {
                        continue; 
                    }
                    if ($sellercenter != $event['sellercenter']) {
                        echo '<h4 class="box-title">'.strtoupper($event['sellercenter']).'</h4>';
                        $sellercenter = $event['sellercenter'];
                    }
                    switch ($event['subject']) {
                        case "Publicação": 
                            $icon = "fa fa-shopping-basket";
                            break;
                        case "Automação": 
                            $icon = "fa fa-industry";
                            break;
                        case "Pedidos": 
                            $icon = "fa fa-money";
                            break;
                        case "Logística": 
                            $icon = "fa fa-truck";
                            break;                  
                        default :  
                            $icon = "fa fa-tachometer";
                    }
                    $avaibility =  round($event['total_up'] / ($event['total_up'] + $event['total_down']) * 100,2);  

                    ?>
                    <div class="info-box <?=$box_color?>">
                        <div class="info-box-icon" style="height: 100%">
                            <i class="<?=$icon?>"></i>
                        </div>

                        <div class="info-box-content">
                            <span class="info-box-text"><?=$event['subject']?></i></span>
                            <span class="info-box-number"><?=$event['event_name'].' - '.$avaibility.'%' ?></span>
                            <div class="progress" style="width: 98%">
                                <div class="progress-bar" style="width: <?=$avaibility?>%"></div>
                            </div>
                            <span class="progress-description">
                                <?=is_null($event['message']) ? 'Tudo OK' : $event['message']?>
                            </span>
                        </div>
                    </div>
                <?php 
                }
                ?>

                </div>

            </div>

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Sistemas Funcionando</h3>
                </div>
                <div class="card-body">         
                
                <?php 
                $sellercenter = ''; 
                foreach($events_month as $event) {
                    $box_color = "bg-aqua"; // outras cores bg-yellow bg-aqua bg-green bg-red 
                    if (!$event['status']) {
                        continue;
                    }
                    if ($sellercenter != $event['sellercenter']) {
                        echo '<h4 class="box-title">'.strtoupper($event['sellercenter']).'</h4>';
                        $sellercenter = $event['sellercenter'];
                    }
                    switch ($event['subject']) {
                        case "Publicação": 
                            $icon = "fa fa-shopping-basket";
                            break;
                        case "Automação": 
                            $icon = "fa fa-industry";
                            break;
                        case "Pedidos": 
                            $icon = "fa fa-money";
                            break;
                        case "Logística": 
                            $icon = "fa fa-truck";
                            break;                  
                        default :  
                            $icon = "fa fa-tachometer";
                    }
                    $avaibility =  round($event['total_up'] / ($event['total_up'] + $event['total_down']) * 100,2);  

                    ?>
                    <div class="info-box <?=$box_color?>">
                        <div class="info-box-icon" style="height: 100%">
                            <i class="<?=$icon?>"></i>
                        </div>

                        <div class="info-box-content">
                            <span class="info-box-text"><?=$event['subject']?></i></span>
                            <span class="info-box-number"><?=$event['event_name'].' - '.$avaibility.'%' ?></span>
                            <div class="progress" style="width: 98%">
                                <div class="progress-bar" style="width: <?=$avaibility?>%"></div>
                            </div>
                            <span class="progress-description">
                                <?=is_null($event['message']) ? 'Tudo OK' : $event['message']?>
                            </span>
                        </div>
                    </div>
                <?php 
                }
                ?>

                </div>

            </div>
            <!---
            <div class="info-box bg-yellow">
                <span class="info-box-icon"><i class="ion ion-ios-pricetag-outline"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Inventory</span>
                    <span class="info-box-number">5,200</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: 50%"></div>
                    </div>
                    <span class="progress-description">
                        50% Increase in 30 Days
                    </span>
                </div>
            </div>

            <div class="info-box bg-green">
                <span class="info-box-icon"><i class="ion ion-ios-heart-outline"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Mentions</span>
                    <span class="info-box-number">92,050</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: 20%"></div>
                    </div>
                    <span class="progress-description">
                        20% Increase in 30 Days
                    </span>
                </div>
            </div>

            <div class="info-box bg-red">
                <span class="info-box-icon"><i class="ion ion-ios-cloud-download-outline"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Downloads</span>
                    <span class="info-box-number">114,381</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: 70%"></div>
                    </div>
                    <span class="progress-description">
                        70% Increase in 30 Days
                    </span>
                </div>
            </div>

            <div class="info-box bg-aqua">
                <span class="info-box-icon"><i class="ion-ios-chatbubble-outline"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Direct Messages</span>
                    <span class="info-box-number">163,921</span>
                    <div class="progress">
                        <div class="progress-bar" style="width: 40%"></div>
                    </div>
                    <span class="progress-description">
                        40% Increase in 30 Days
                    </span>
                </div>
            </div>
            --->
        </div>
    </section>
</div>
  <!-- /.content-wrapper -->
<script type="text/javascript">
$(document).ready(function() {
    $("#dashboardMainMenu").addClass('active');
});

</script>