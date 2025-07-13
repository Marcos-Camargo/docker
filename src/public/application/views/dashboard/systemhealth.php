  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
	  
	<?php $data['page_now'] = "system_health"; $data['pageinfo'] = "application_dashboard";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
      <!-- Small boxes (Stat box) -->
        <div class="col-md-12">

            <?php 
            foreach($events_month as $event) {
                $box_color = "bg-red"; // outras cores bg-yellow bg-aqua bg-green bg-red 
                if ($event['status']) {
                    $box_color = "bg-aqua";
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

            if ($events_oci_queue && !isset($events_oci_queue['success'])){
                ?>
                <div class="form-group col-md-12 col-xs-12">
                    <h3>Filas OCI</h3>
                    <table class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                        <thead>
                            <tr>
                                <th>Fila</th>
                                <th>Mensagens Visíveis</th>
                                <th>Mensagens Em Processo</th>
                                <th>Tamanho em Bytes</th>
                                <th>Mensagens Mortas Visíveis</th>
                                <th>Mensagens Mortas Em Processo</th>
                                <th>Tamanho em Bytes Mortas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($events_oci_queue as $item){
                                $color = '';
                                if ($item['status']['queue']['visibleMessages']
                                || $item['status']['dlq']['visibleMessages']){
                                    $color = '#F39C12';
                                }
                            ?>
                                <tr style="background-color: <?php echo $color; ?>;">
                                    <td><?php echo $item['queue']['queueName']; ?></td>
                                    <td class="text-center"><?php echo $item['status']['queue']['visibleMessages']; ?></td>
                                    <td class="text-center"><?php echo $item['status']['queue']['inFlightMessages']; ?></td>
                                    <td class="text-center"><?php echo $item['status']['queue']['sizeInBytes']; ?></td>
                                    <td class="text-center"><?php echo $item['status']['dlq']['visibleMessages']; ?></td>
                                    <td class="text-center"><?php echo $item['status']['dlq']['inFlightMessages']; ?></td>
                                    <td class="text-center"><?php echo $item['status']['dlq']['sizeInBytes']; ?></td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

            <?php
            }
            ?>

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
    $("#systemHealthli").addClass('active');
});

</script>