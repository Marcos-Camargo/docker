<!--
SW Serviços de Informática 2019

Editar Pedidos

-->

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

	<?php $data['pageinfo'] = "application_edit";  $this->load->view('templates/content_header',$data); ?>
  <!-- Main content -->
  <section class="content">

    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="order_messages"></div>

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
    	<?php if ($order_data['order']['exchange_request']==1) { ?>
			<div class="alert alert-warning alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php echo $this->lang->line('application_exchange_order') ;
            if (!is_null($order_data['order']['original_order_marketplace'])) {
            	echo '! '.$this->lang->line('application_original_order').': '.$order_data['order']['original_order_marketplace'];
            }
            ?>

          </div>
		<?php }else if($order_data['order']['exchange_request']==2){
      // dd($order_data['order']['exchange_request']==2);
       ?>
          <div class="alert alert-warning alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php
            echo sprintf($this->lang->line('application_exchange_order_partial'),$order_data['order']['original_order_marketplace']) ;
            ?>
          </div>
    <?php } ?>
        <?php // se tem incidente no marketplace aparece só para o ADMIN
        if (( $order_data['order']['has_incident']) && (in_array('admDashboard', $user_permission)))  : ?>
			<div class="alert alert-error alert-dismissible" role="alert">
	            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	            <?php echo $this->lang->line('application_has_incident'); ?>

	     	</div>
		<?php endif ?>
      <?php if (!empty($forced_to_delivery)): ?>
        <div class="alert alert-info">
            Este pedido foi atualizado automaticamente pela funcionalidade de atualização direta para entregue.
        </div>
      <?php endif; ?>

    <div class="box box-primary mt-1">
      <div class="d-flex justify-start flex-wrap btns-action-order box-body">
        <?php
        // mostro botão de inserir ou alterar nota fiscal
        if (in_array('updateOrder', $user_permission)) {
			if (in_array($order_data['order']['paid_status'], array(3, 50, 57, 101, 41))) {
		        if (isset($order_data['nfes']) && in_array('viewChangeInvoiceOrder', $user_permission)) {
					$btn_label = $this->lang->line('application_change_invoice');
                    echo '<button type="button" class="btn btn-primary" onclick="editNfe('.$order_data['order']['id'].')" data-toggle="modal" data-target="#nfeModal"><i class="fa fa-filter">'.$btn_label.'</i></button>'; ?>
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#nfXML"><i class="fa fa-file-text-o">&nbsp<strong><?=$this->lang->line('application_nfxml');?></strong></i></button>
                <?php
                } elseif (!isset($order_data['nfes'])) {
                    $btn_label = $this->lang->line('application_insert_invoice');
                    echo '<button type="button" class="btn btn-primary" onclick="editNfe('.$order_data['order']['id'].')" data-toggle="modal" data-target="#nfeModal"><i class="fa fa-filter">'.$btn_label.'</i></button>'; ?>
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#nfXML"><i class="fa fa-file-text-o">&nbsp<strong><?=$this->lang->line('application_nfxml');?></strong></i></button>
          <?php
                }
			}
		 }
		?>

		<?php
        // mostro botão para tentar novamento com os correios
        if(in_array('updateOrder', $user_permission) && ($order_data['order']['paid_status']==101)) {   ?>
       	    <button type="button" class="btn btn-primary" onclick="returnCorreios()" data-toggle="modal" data-target="#returnCorreiosModal"><i class="fa fa-mail-bulk">&nbsp<?=$this->lang->line('application_try_again_correios');?></i></button>
		<?php }

        // mostro botão para marcar o pedido como enviado
        if(in_array('updateTrackingOrder', $user_permission) && (in_array($order_data['order']['paid_status'], array(4,43,53))) ) { ?>
       	    <button type="button" class="btn btn-primary" onclick="postItem()" data-toggle="modal" data-target="#postItemModal"><i class="fas fa-truck-loading">&nbsp<?=$this->lang->line('application_mark_as_posted');?></i></button>
		<?php }

        // mostro botão para marcar o pedido como entregue
        if(in_array('updateTrackingOrder', $user_permission) && in_array($order_data['order']['paid_status'],[5, 45, 58])) { ?>
       	    <button type="button" class="btn btn-primary" onclick="deliveryItem()" data-toggle="modal" data-target="#deliveryItemModal"><i class="fas fa-truck">&nbsp<?=$this->lang->line('application_mark_as_delivered');?></i></button>
		<?php }

        // mostro botão para tela de etiqueta
        if(in_array('viewLogistics', $user_permission) && in_array($order_data['order']['paid_status'], [4,5,43,45,50,51,53,54,55,58]) && !$order_data['order']['is_pickup_in_point']) { ?>
       	    <button type="button" class="btn btn-primary" onclick="goToManageTags()" ><i class="fa fa-truck">&nbsp<?=$this->lang->line('application_label');?>
            </i></button>
		<?php }

        // mostro botão para cancelar pedido

        if ($order_data['order']['product_return_status'] == 0 && !in_array($order_data['order']['paid_status'], [95,96,97,98,99])) {
            if ($order_data['order']['origin'] != 'MAD') {  // Madeira Madeira não permite cancelar o pedido
                ?>
                <button type="button" class="btn btn-danger" onclick="cancelOrder()" data-toggle="modal" data-target="#cancelarOrdemModal"><i class="fa fa-window-close">&nbsp<?=$this->lang->line('application_cancel_order');?></i></button>
            <?php } else {?>
                <button type="button" class="btn btn-danger" onclick="cantCancelOrder('<?=$order_data['order']['origin']?>')" <i class="fa fa-window-close">&nbsp<?=$this->lang->line('application_cancel_order');?></i></button>
            <?php }
        }
		

        // mostro botão para registrar pedido de troca
        if(in_array('admDashboard', $user_permission) && ($order_data['order']['exchange_request'])) { ?>
       	    <button type="button" class="btn btn-primary" onclick="registerExchange()" data-toggle="modal" data-target="#registerExchangeModal"><i class="fa fa-exchange">&nbsp<?=$this->lang->line('application_exchange_register_original_order');?></i></button>
		<?php }

        // mostro botão para criar pedido de troca
        if(in_array('admDashboard', $user_permission) && ($order_data['order']['paid_status']==6 || $order_data['order']['paid_status']==60) &&  ($order_data['order']['origin']!='B2W')) { // B2W não cria pedido de troca  ?>
       	    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#newExchangeModal"><i class="fa fa-exchange">&nbsp<?=$this->lang->line('application_new_exchange');?></i></button>
		<?php }

        // mostro botão para criar pedido de troca
        if(in_array('updateOrder', $user_permission) && ($order_data['order']['paid_status']==101)) {   ?>
       	    <button type="button" class="btn btn-primary" onclick="returnCorreios()" data-toggle="modal" data-target="#returnCorreiosModal"><i class="fa fa-mail-bulk">&nbsp<?=$this->lang->line('application_new_exchange');?></i></button>
		<?php }

        // botão para mandar pedido para frete a contratar
        if(in_array($order_data['order']['paid_status'], array(40, 50, 80)) && (in_array('admDashboard', $user_permission) || in_array('sendFreightToHire', $user_permission))) { ?>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#sendFreightHire"><i class="fa fa-truck">&nbsp<?=$this->lang->line('application_send_freight_hire');?></i></button>
        <?php }

        // mostro botão para registrar o motivo do cancelamento
        if(in_array('deleteOrder', $user_permission) && (!$pedido_cancelado) && in_array($order_data['order']['paid_status'], [95,96,97,98])) { ?>
       	    <button type="button" class="btn btn-primary" onclick="cancelReason()" data-toggle="modal" data-target="#cancelReasonModal"><i class="fa fa-comment-dots">&nbsp<?=$this->lang->line('application_cancel_reason');?></i></button>
		<?php }

        if(in_array('updateOrdersCancelCommissionCharges', $user_permission) && ($pedido_cancelado) && (!$commision_charges) && in_array($order_data['order']['paid_status'], [95,96,97,98]) && ($checkMaximumDaysToRefundComission)) {
              if($pedido_cancelado['commission_charges_attribute_value'] == 1) {?>
                <button type="button" class="btn btn-danger" onclick="cancelCommisionChargesOrder()" data-toggle="modal" data-target="#CancelComissionChargesModal"><i class="fa fa-window-close">&nbsp<?=$this->lang->line('application_orders_cancel_commission_charges_button');?></i></button>
        <?php }
        }

        // botão para trocar se seller
        if ($use_change_seller && ($order_data['order']['paid_status'] == 1 || $order_data['order']['paid_status'] == 3)) { ?>
           <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#sendChangeSeller"><i class="fa fa-user-times">&nbsp<?=$this->lang->line('application_not_fulfill_request');?></i></button>
        <?php }

        // mostro botão para criar pedido de troca
        if(in_array('admDashboard', $user_permission)) { // B2W não cria pedido de troca  ?>
            <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#incidence"><i class="fa fa-warning"> <?=$this->lang->line('application_incidence');?></i></button>
        <?php }

        // redireciona para portal da raia drogasil
        $settingRedirectOut = $this->model_settings->settingRedirectOut('button_redirect_out_rd');
        if($settingRedirectOut && $settingRedirectOut['status'] == 1) {?>
          <a target="_blak" href="https://www.lojistard.com.br/SignIn?ReturnUrl=%2Fmapa-suporte%2Fnovo-chamado-lojista%2Fcriar-chamado-lojista%2F%3Fetn%3Dobter_form%26formid%3D90941ef3-6e41-ec11-8c62-00224837d1c3%26assunt1%3D003c7b44-870d-eb11-a813-000d3ac1707e%26assunt2%3Da1cfa44c-8a0d-eb11-a812-000d3ac17b5f%26assunt3%3D3efe7cfd-390e-eb11-a812-000d3ac17aea%26assunt3name%3DCancelamento%2520indevido%26pedido-vtex%3Dnull%26origempagina%3Dlogista" type="button" class="btn btn-primary" ><i class="fa fa-external-link-square"> <?=$this->lang->line('application_out_link');?></i></a>
        <?php }

        // botão para reenviar o pedido
        if ($order_data['order']['paid_status'] == 59 && !$order_data['order']['in_resend_active']) {
            echo "<button type='button' class='btn btn-primary' data-toggle='modal' data-target='#modalResend'><i class='fa fa-truck'></i> {$this->lang->line('application_new_delivery')}</i></button>";
        }

        // botão para reenviar o pedido
        if (in_array($order_data['order']['paid_status'], array(5,45,58)) && in_array('updateTrackingOrder', $user_permission)) {
            echo "<button type='button' class='btn btn-primary' data-toggle='modal' data-target='#modalResend'><i class='fas fa-truck-loading'></i> {$this->lang->line('application_mark_returned_or_lost')}</i></button>";
        }

        // botão para solicitar cancelamento

        if ($order_data['order']['product_return_status'] == 0 && !in_array($order_data['order']['paid_status'], [95,96,97,98,99]) && in_array('createRequestCancelOrder', $user_permission)) {
            echo "<button type='button' class='btn btn-danger' data-toggle='modal' data-target='#modalRequestCancel'><i class='fa fa-window-close'></i> {$this->lang->line('application_request_cancel')}</i></button>";
        }


        // botão para cancelar a solicitar cancelamento
        if ($order_data['order']['paid_status'] == 90 && in_array('deleteRequestCancelOrder', $user_permission) && in_array('admDashboard', $user_permission)) {
            echo "<button type='button' class='btn btn-success' data-toggle='modal' data-target='#modalCancelRequestCancel'><i class='fa fa-refresh'></i> {$this->lang->line('application_cancel_request_cancel')}</i></button>";
        }

        // botão para fazer mediação do pedido
        if(in_array('orderMediation', $user_permission)) { ?>
            <?php if ($orderMediation == 0): ?>
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#mediation"><i class="fa fa-warning"> <?=$this->lang->line('application_order_mediation_button_create');?></i></button>
            <?php else: ?>
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#mediation" <?=($orderMediationResolved == 1) ? 'disabled' : '' ?>><?=$this->lang->line('application_order_mediation_button_resolve');?></i></button>
            <?php endif; ?>
        <?php
        }

        if (in_array('createReturnOrder', $user_permission)) {
          // Botão de cadastro manual de devolução de produto.
          if (($order_data['order']['paid_status'] == 6) && ($order_data['order']['product_return_status'] == 0)) {
            echo '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#newReturnOrderModal"><i class="fa fa-exchange"> ' . $this->lang->line('application_request_order_return') . '</i></button>';

          // Botão de acompanhamento da devolução do produto.
          // } else if ($order_data['order']['paid_status'] == 59) {
          } else if (in_array($order_data['order']['paid_status'], array(7, 81))) {
            echo '<button type="button" class="btn btn-primary" onclick="goToTrackOrderReturn()"><i class="fa fa-exchange"> ' . $this->lang->line('application_track_order_return') . '</i></button>';
          }
        }
        // botão para cancelar a solicitar cancelamento
        if (in_array($order_data['order']['paid_status'], array(1,2,3)) && in_array('partialCancellationOrder', $user_permission) && empty($order_value_refund_on_gateways)) {
            echo "<button type='button' class='btn btn-danger' data-toggle='modal' data-target='#modalPartialCancellationOrder'><i class='fa fa-times'></i> {$this->lang->line('application_make_partial_cancellation')}</i></button>";
        }
        // botão para cancelar a solicitar cancelamento
        if (
            $days_to_refund_value_tuna &&
            in_array('refundOrderValue', $user_permission) &&
            !in_array($order_data['order']['paid_status'], array(1,2,95,96,97,98,99)) &&
            strtotime(addDaysToDate($order_data['order']['date_time'], $days_to_refund_value_tuna)) > strtotime(dateNow()->format(DATETIME_INTERNATIONAL))
        ) {
            if (!in_array($order_data['order']['paid_status'], array(3, 52)) || (in_array($order_data['order']['paid_status'], array(3, 52)) && !$has_canceled_item)) {
                echo "<button type='button' class='btn btn-primary' data-toggle='modal' data-target='#modalRefundOrderValue'><i class='fa fa-money-bill-wave'></i> {$this->lang->line('application_refunds_order')}</i></button>";
            }
        }
        ?>
      </div>
    </div>

      <?php
      if ($order_data['order']['status_integration']){
          ?>
          <div class="alert alert-info alert-dismissible" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              Pedido no status <?php echo $order_data['order']['status_integration']; ?> na integração -
              <?php echo $order_data['order']['status_integration_description']; ?>
          </div>
          <?php
      }
      ?>

        <div class="box box-primary">
          <form role="form" action="<?php base_url('orders/update') ?>" method="post">
              <div class="box-body">
            <?php
            if (validation_errors()) {
	          foreach (explode("</p>",validation_errors()) as $erro) {
		        $erro = trim($erro);
		        if ($erro!="") { ?>
				<div class="alert alert-error alert-dismissible" role="alert">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	                <?php echo $erro."</p>"; ?>
				</div>
		<?php	}
			  }
	       	} ?>
	       		<div class="row">
	       			<ol id="barra-simples-2" class="mid step-progress-bar order-step">
	       			<?php if (in_array($order_data['order']['paid_status'], [1,2])) { ?>
						<li class="<?= $order_data['order']['date_time'] ? 'step-past' : 'step-future' ?>" style="width: 50%; z-index: 2;">
							<span class="content-wpp"><?=$this->lang->line('application_request_received');?></span>
							<span class="content-bullet">1</span>
							<span class="content-wpp"><?= $order_data['order']['date_time']? date('d/m/Y H:i:s', strtotime($order_data['order']['date_time'])) : '' ?></span>
						</li>
						<li class='step-future' style="width: 50%; z-index: 1;">
							<span class="content-wpp"><?=$this->lang->line('application_payment_accept');?></span>
							<span class="content-bullet">2</span>
							<span class="content-wpp"></span>
							<span class="content-stick step-future"></span>
						</li>
					<?php } else { ?>
						<li class="<?= $order_data['order']['date_time'] ? 'step-past' : 'step-future' ?>" style="width: 20%; z-index: 5;">
							<span class="content-wpp"><?=$this->lang->line('application_request_received');?></span>
							<span class="content-bullet">1</span>
							<span class="content-wpp"><?= $order_data['order']['date_time']? date('d/m/Y H:i:s', strtotime($order_data['order']['date_time'])) : '' ?></span>
						</li>
						<li class="<?= $order_data['order']['data_pago'] ? 'step-past' : 'step-future' ?>" style="width: 20%; z-index: 4;">
							<span class="content-wpp"><?=$this->lang->line('application_payment_accept');?></span>
							<span class="content-bullet">2</span>
							<span class="content-wpp"><?= $order_data['order']['data_pago'] ? date('d/m/Y H:i:s', strtotime($order_data['order']['data_pago'])) : '' ?></span>
							<span class="content-stick <?= $order_data['order']['data_pago'] ? 'step-past' : 'step-future' ?>"></span>
						</li>
						<?php if (in_array($order_data['order']['paid_status'], [95,96,97,98,99])) { ?>
							<li class="step-future" style="width: 20%; z-index: 3;">
								<span class="content-wpp"><?=$this->lang->line('application_invoice_issued');?></span>
								<span class="content-bullet">3</span>
								<span class="content-wpp"></span>
								<span class="content-stick step-future"></span>
							</li>
						<?php } elseif (is_null($order_data['order']['data_envio'])) { ?>
                            <li class="<?= ($order_data['order']['data_limite_cross_docking'] < date('Y-m-d H:i:s')) ? 'step-delay' : 'step-present' ?>" style="width: 20%; z-index: 3;">
                                <span class="content-wpp"><?=$this->lang->line('application_invoice_issued');?></span>
                                <span class="content-bullet">3</span>
                                <span class="content-wpp"><?= isset($order_data['nfes']) && $order_data['nfes'][0] ? $order_data['nfes'][0]['date_emission'] : ''/*$order_data['order']['data_limite_cross_docking'] ? date('d/m/Y H:i:s', strtotime($order_data['order']['data_limite_cross_docking'])) : ''*/ ?></span>
                                <span class="content-stick <?= ($order_data['order']['data_limite_cross_docking'] <  date('Y-m-d H:i:s'))? 'step-present' : 'step-future' ?>"></span>
                            </li>
                        <?php } else { ?>
							<li class="<?= $order_data['order']['data_limite_cross_docking'] ? 'step-past' : 'step-future' ?>" style="width: 20%; z-index: 2;">
								<span class="content-wpp"><?=$this->lang->line('application_invoice_issued');?></span>
								<span class="content-bullet">3</span>
								<span class="content-wpp"><?= isset($order_data['nfes']) && $order_data['nfes'][0] ? $order_data['nfes'][0]['date_emission'] : ''/*$order_data['order']['data_limite_cross_docking'] ? date('d/m/Y H:i:s', strtotime($order_data['order']['data_limite_cross_docking'])) : ''*/ ?></span>
								<span class="content-stick <?= /*$order_data['order']['data_limite_cross_docking']*/ isset($order_data['nfes']) && $order_data['nfes'][0] ? 'step-past' : 'step-future' ?>"></span>
							</li>
						<?php } ?>

                        <?php if (!$order_data['order']['is_pickup_in_point']): ?>
						<li class="<?= $order_data['order']['data_envio'] ? 'step-past' : 'step-future' ?>" style="width: 20%; z-index: 2;">
							<span class="content-wpp"><?=$this->lang->line('application_expedited_order');?></span>
							<span class="content-bullet">4</span>
							<span class="content-wpp" <?= $order_data['order']['data_limite_cross_docking'] && !$order_data['order']['data_entrega'] ? 'data-tooltip="Você tem até '.date('d/m/Y H:i:s', strtotime($order_data['order']['data_limite_cross_docking'])).' para expedir o pedido."' : '' ?>><?= $order_data['order']['data_envio'] ? date('d/m/Y H:i:s', strtotime($order_data['order']['data_envio'])) : '' ?></span>
							<span class="content-stick <?= $order_data['order']['data_envio'] ? 'step-past' : 'step-future' ?>"></span>
						</li>
                        <?php endif; ?>

						<li class="<?= $order_data['order']['data_entrega'] ? 'step-past' : 'step-future' ?>" style="width: 20%; z-index: 1;">
							<span class="content-wpp"><?=$this->lang->line('application_order_delivered');?></span>
							<span class="content-bullet">5</span>
							<span class="content-wpp"><?= $order_data['order']['data_entrega'] ? date('d/m/Y H:i:s', strtotime($order_data['order']['data_entrega'])) : '' ?></span>
							<span class="content-stick <?= $order_data['order']['data_entrega'] ? 'step-past' : 'step-future' ?>"></span>
						</li>

					<?php }  ?>
					</ol>
				</div>

	      	<?php if ($orderMediation == 1 && $orderMediationResolved == 0): ?>
                <div class="alert alert-error alert-dismissible" role="alert">
                    <?=$this->lang->line('application_order_mediation_warning_phrase_in_mediation');?>
                </div>
                <?php endif; ?>

                <?php if ($orderMediation == 1 && $orderMediationResolved == 1): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <?=$this->lang->line('application_order_mediation_warning_phrase_mediation_resolved');?>
                </div>
                <?php endif; ?>

                <div class="col-md-12 col-xs-12 pull pull-left" style="margin-top: 25px;">
				<?php if(in_array('createOrder', $user_permission)) { $more = "readonly"; } else { $more = "readonly"; } ?>

                  <h3><?=$this->lang->line('application_order_data')?></h3>
                 </div>
                 <div class="row col-md-12">
                  <div class="form-group col-md-2">
                    <label for="id"><?=$this->lang->line('application_order');?></label>
                    <div>
                      <input type="text" class="form-control" id="id" name="id" <?php echo $more; ?> value="<?php echo $order_data['order']['id'] ?>" autocomplete="off"/>
                        <?php if ($order_data['order']['order_id_integration']): ?>
                          <small><b><?=$this->lang->line('application_integrated_order');?></b>: <?=$order_data['order']['order_id_integration']?></small>
                        <?php endif ?>
                    </div>
                  </div>

                  <div class="form-group col-md-4">
                    <label for="numero_marketplace"><?=$this->lang->line('application_order_marketplace_full');?></label>
                    <div>
                      <input type="text" class="form-control" id="numero_marketplace" name="numero_marketplace" <?php echo $more; ?> value="<?php echo $order_data['order']['numero_marketplace'] ?>" autocomplete="off"/>
                    </div>
                  </div>


                  <div class="form-group col-md-2">
                    <label for="date_time"><?=$this->lang->line('application_date');?></label>
                    <div>
                      <input type="text" class="form-control" id="date_time" name="date_time" <?php echo $more; ?> value="<?php echo date('d/m/Y', strtotime($order_data['order']['date_time'])); ?>" autocomplete="off"/>
                    </div>
                  </div>

                  <div class="form-group col-md-4">
                    <label for="paid_status"><?=$this->lang->line('application_status');?></label>
                     <?php if ($pedido_cancelado) : ?>
                    <button onClick="toggleCancelReason(event)" data-toggle="tooltip"  data-placement="top" title="<?=$this->lang->line('application_cancel_reason');?>" ><i class="fa fa-question-circle"></i></button>
                    <?php elseif ($order_data['order']['paid_status'] == 58 && !empty($occurrence)) : ?>
                        <button onClick="toggleAddressWithdrawal(event)" data-toggle="tooltip"  data-placement="top" title="<?=$this->lang->line('application_pickup_location');?>" ><i class="fa fa-question-circle"></i></button>
                     <?php elseif ($order_data['order']['paid_status'] == 80) : ?>
                         <button onClick="toggleProblemOrder(event)" data-toggle="tooltip"  data-placement="top" title="<?=$this->lang->line('application_order_with_problem');?>" ><i class="fa fa-question-circle"></i></button>
                     <?php elseif ($order_data['order']['paid_status'] == 90) : ?>
                         <button onClick="toggleRequestCancelOrder(event)" data-toggle="tooltip"  data-placement="top" title="<?=$this->lang->line('application_request_cancel');?>" ><i class="fa fa-question-circle"></i></button>
                     <?php elseif ($order_error_integration) : ?>
                         <button onClick="toggleOrderErrorIntegration(event)" data-toggle="tooltip"  data-placement="top" title="<?=$this->lang->line('application_cause_of_error');?>" ><i class="fa fa-question-circle"></i></button>
                    <?php elseif ($invoice_error_reason && $order_data['order']['paid_status'] == 57) : ?>
                        <button onClick="toggleInvoiceErrorIntegration(event)" data-toggle="tooltip"  data-placement="top" title="<?=$this->lang->line('application_cause_of_error');?>" ><i class="fa fa-question-circle"></i></button>
                    <?php elseif ($has_order_value_refund && !empty(array_filter($order_value_refund_on_gateways, function($order_value_refund){ return is_null($order_value_refund['refunded_at']) && !is_null($order_value_refund['response_error']); }))) : ?>
                        <button type="button" data-toggle="collapse" data-target="#collapseOrderValueRefundError" aria-expanded="false" aria-controls="collapseOrderValueRefundError"><i class="fa fa-question-circle"></i></button>
                    <?php endif; ?>
                    <div class="<?=in_array('viewQueueOrderIntegration', $user_permission) ? 'input-group' : ''?>">
                        <span class="form-control" data-toggle="tooltip"  data-placement="top" id="status_code" status-code="<?=$order_data['order']['paid_status']?>" title="<?php echo $order_data['order']['paid_status']?>" ><?php  echo $status_str;?>  </span>
                        <?php if(in_array('viewQueueOrderIntegration', $user_permission)) { ?>
                        <span class="input-group-btn">
                            <button type="button" data-toggle="tooltip" class="btn btn-primary btn-flat" id="btnViewQueueOrder" title="<?=$this->lang->line('application_view_queue_orders')?>"><i class="fa fa-eye"></i></button>
                        </span>
                        <?php } ?>
                    </div>
                  </div>
                 </div>
				  <?php if ($pedido_cancelado) : ?>
				  	<div id="cancel_reason" style="display: none;">
						  <table border="2" bordercolor="red" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
						  	<TR>
		   					 	<TD>

								  <div class="form-group col-md-5">
				                    <label><?=$this->lang->line('application_cancel_reason');?></label>
				                    <div>
				                    	<span ><?php  echo $pedido_cancelado['reason'];?>  </span>
				                    </div>
				                  </div>

				                   <div class="form-group col-md-2">
				                    <label><?=$this->lang->line('application_penalty_to');?></label>
				                    <div>
				                    	<span ><?php  echo $pedido_cancelado['penalty_to'];?>  </span>
				                    </div>
				                  </div>

				                  <div class="form-group col-md-3">
				                    <label><?=$this->lang->line('application_registered_by');?></label>
				                    <div>
				                    	<span ><?php  echo $pedido_cancelado['username'] ?? $this->lang->line('application_conciliacao_grids_naoencontrado');?>  </span>
				                    </div>
				                  </div>

				                  <div class="form-group col-md-2">
				                    <label><?=$this->lang->line('application_date');?></label>
				                    <div>
				                    	<span ><?php  echo date("d/m/Y H:i:s",strtotime($pedido_cancelado['date_update']));?>  </span>
				                    </div>
				                  </div>

					       		 <?php if(in_array('deleteOrder',  $user_permission) && ($pedido_cancelado) && (!$allowChanceCancelReason)) { ?>
					       		 	<div class="row"></div>
					       	    	<div class="form-group col-md-2">
					       	    		<button type="button" class="btn btn-primary" onclick="alterCancelReason(event,'<?= $pedido_cancelado['id'];?>','<?= $pedido_cancelado['reason'];?>','<?= $pedido_cancelado['penalty_to'];?>')" ><i class="fa fa-edit">&nbsp<?=$this->lang->line('application_edit');?></i></button>
							 		</div>
							 		<?php } ?>
		                  		</TD>
							</TR>
						  </TABLE>
				  </div>
				  <?php endif; ?>
                  <?php if ($order_data['order']['paid_status'] == 58 && !empty($occurrence)) : ?>
                      <div id="pickup_location" style="display: none;">
                          <table border="2" bordercolor="red" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                              <TR>
                                  <TD>

                                      <div class="form-group col-md-6">
                                          <label><?=$this->lang->line('application_location');?></label>
                                          <span class="form-control"><?=$occurrence['addr_place'];?></span>
                                      </div>

                                      <div class="form-group col-md-4">
                                          <label><?=$this->lang->line('application_address');?></label>
                                          <span class="form-control"><?=$occurrence['addr_name'];?>  </span>
                                      </div>

                                      <div class="form-group col-md-2">
                                          <label><?=$this->lang->line('application_number');?></label>
                                          <span class="form-control"><?=$occurrence['addr_num'];?>  </span>
                                      </div>

                                      <div class="form-group col-md-3">
                                          <label><?=$this->lang->line('application_zip_code');?></label>
                                          <span class="form-control"><?=$occurrence['addr_cep'];?>  </span>
                                      </div>

                                      <div class="form-group col-md-3">
                                          <label><?=$this->lang->line('application_neighb');?></label>
                                          <span class="form-control"><?=$occurrence['addr_neigh'];?>  </span>
                                      </div>

                                      <div class="form-group col-md-3">
                                          <label><?=$this->lang->line('application_city');?></label>
                                          <span class="form-control"><?=$occurrence['addr_city'];?>  </span>
                                      </div>

                                      <div class="form-group col-md-3">
                                          <label><?=$this->lang->line('application_uf');?></label>
                                          <span class="form-control"><?=$occurrence['addr_state'];?>  </span>
                                      </div>

                                      <div class="form-group col-md-12">
                                          <label><?=$this->lang->line('application_warning');?></label>
                                          <span class="form-control"><?=$occurrence['mensagem'];?>  </span>
                                      </div>
                                  </TD>
                              </TR>
                          </TABLE>
                      </div>
                  <?php endif; ?>
                  <?php if ($order_data['order']['paid_status'] == 80) : ?>
                      <div id="problem_order" class="col-md-12" style="display: none;">
                          <div class="col-md-12 pt-3 pb-2 mb-4" style="border: 2px solid red">
                              <ul>
                                  <?php
                                  if (count($problem_order)) {
                                      foreach ($problem_order as $problem) {
                                          echo "<li>{$problem['description']}</li>";
                                      }
                                  } else echo "<li>Problema desconhecido.</li>";
                                  ?>
                              </ul>
                          </div>
                      </div>
                  <?php endif; ?>
                  <?php if ($order_error_integration) : ?>
                      <div id="order_error_integration" class="col-md-12" style="display: none;">
                          <div class="col-md-12 pt-3 pb-2 mb-4" style="border: 2px solid red">
                              <?=$order_error_integration->description?>
                          </div>
                      </div>
                  <?php endif; ?>
                  <?php if ($order_data['order']['paid_status'] == 90) : ?>
                      <div id="requestCancelOrder" style="display: none;">
                          <table border="2" bordercolor="red" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                              <tr>
                                  <td>
                                      <div class="form-group col-md-8">
                                          <label><?=$this->lang->line('application_cancel_reason');?></label>
                                          <span class="form-control"><?=$reasonRequestCancel['reason'] ?? ''?></span>
                                      </div>
                                      <div class="form-group col-md-4">
                                          <label><?=$this->lang->line('application_date');?></label>
                                          <span class="form-control"><?=isset($reasonRequestCancel['date_created']) ? date('d/m/Y H:i', strtotime($reasonRequestCancel['date_created'])) : ''?>  </span>
                                      </div>
                                      <?php if (in_array('admDashboard', $user_permission)) {
                                          echo "
                                              <div class='form-group col-md-12'>
                                                  <label>{$this->lang->line('application_user')}</label>
                                                  <span class='form-control'>".$reasonRequestCancel['email'] ?? ''."</span>
                                              </div>
                                          ";
                                      } ?>
                                  </td>
                              </tr>
                          </table>
                      </div>
                  <?php endif; ?>
                  <?php if ($invoice_error_reason && $order_data['order']['paid_status'] == 57) : ?>
                      <div id="invoice_error_integration" class="col-md-12" style="display: none;">
                          <div class="col-md-12 pt-3 pb-2 mb-4" style="border: 2px solid red">
                              <pre class="format_json"><?=$invoice_error_reason;?></pre>
                          </div>
                      </div>
                  <?php endif; ?>
                  <div class="collapse col-md-12" id="collapseOrderValueRefundError">
                      <div class="col-md-12 pt-3 pb-2 mb-4" style="border: 2px solid red">
                          <h3><?=$this->lang->line('application_refunded_values');?></h3>
                          <pre class="format_json">[
                            <?=implode(',', array_map(
                                function ($order_value_refund) {
                                    return $order_value_refund['response_error'];
                                }, array_filter($order_value_refund_on_gateways,
                                    function ($order_value_refund) {
                                        return is_null($order_value_refund['refunded_at']) && !is_null($order_value_refund['response_error']);
                                    }
                                )
                            ));?>]</pre>
                      </div>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="origin"><?=$this->lang->line('application_origin');?></label>
                    <div>
                      <input type="text" class="form-control" id="origin" name="origin" <?php echo $more; ?> value="<?php echo $order_data['order']['origin'] ?>" autocomplete="off"/>
                    </div>
                  </div>

                  <div class="form-group col-md-5">
                    <label for="customer_name"><?=$this->lang->line('application_name');?></label>
                    <div>
                      <input type="text" class="form-control" id="customer_name" name="customer_name" <?php echo $more; ?> value="<?php echo $order_data['order']['customer_name'] ?>" autocomplete="off"/>
                    </div>
                  </div>

                  <div class="form-group col-md-3">
                    <label for="cpf_cnpj"><?php echo $this->lang->line('application_cpf').'/'.$this->lang->line('application_cnpj');?></label>
                    <div>
                    	<input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" <?php echo $more; ?> value="<?php echo $order_data['order']['cliente']['cpf_cnpj'] ?>" autocomplete="off"/>

                    </div>
                  </div>

                  <div class="form-group col-md-2"  <?= (!in_array('viewInfoContactUserOrder', $user_permission) ? '<th style="visibility: hidden;"' : '') ?>>
                    <label for="phone_customer"><?php echo $this->lang->line('application_phone');?></label>
                    <div>
                        <input type="text" class="form-control" id="phone_customer" name="phone_customer" <?php echo $more; ?> value="<?php echo $order_data['order']['customer_phone'] ?>" autocomplete="off"/>

                    </div>
                  </div>
                  <div class="row"></div>

                  <div class="form-group col-md-6">
                    <label for="company_id"><?=$this->lang->line('application_company');?></label>
                    <div>
                    	<!---
                      <input type="text" class="form-control" id="company_id" name="company_id" <?php echo $more; ?> value="<?php echo $order_data['order']['empresa']['name'] ?>" autocomplete="off"/>
                   	  --->
                   	  	<?php  if(in_array('updateCompany', $user_permission)) { ?>
                   	  	<a id="company_id" name="company_id" href="<?php echo base_url().'company/update/'.$order_data['order']['company_id'];?>"  target="_blank"><span "><?php echo $order_data['order']['empresa']['name']  ?> &nbsp </span><i class="fa fa-eye"></i></a>
                   		<?php  } else { ?>
                   		<span class="form-control"><?php echo $order_data['order']['empresa']['name']; ?></span>
                   		<?php  }  ?>
                    </div>
                  </div>

                  <div class="form-group col-md-6">
                    <label for="store_id"><?=$this->lang->line('application_store');?></label>
                    <div>
						<?php  if(in_array('updateStore', $user_permission)) { ?>
                    	<a id="store_id" name="store_id" href="<?php echo base_url().'stores/update/'.$order_data['order']['store_id'];?>"  target="_blank"><span "><?php echo $order_data['order']['loja']['name']  ?> &nbsp </span><i class="fa fa-eye"></i></a>
                   		<?php  } else { ?>
                   		<span class="form-control"><?php echo $order_data['order']['loja']['name']; ?></span>
                   		<?php  }  ?>
                    </div>
                  </div>

                <?php if(!empty($stores_multi_channel_fulfillment)): ?>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="col-md-12">
                                <h3><?=$this->lang->line('application_multi_cd_order_not_met')?></h3>
                            </div>
                            <?php foreach($stores_multi_channel_fulfillment as $store_multi_channel_fulfillment): ?>
                            <div class="col-md-3">
                                <label><?=$this->lang->line('application_store_set_to_fulfill_order')?></label>
                                <br>
                                <a href="<?=base_url("stores/update/$store_multi_channel_fulfillment[old_store_id]")?>" target="_blank"><span><?=$store_multi_channel_fulfillment['old_store_name']?> &nbsp; </span><i class="fa fa-eye"></i></a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="col-md-12">
                	<h3><?=$this->lang->line('application_items')?></h3>
                </div>
                <div class="col-md-12" style="overflow-x:auto;">
				<table class="table table-bordered" id="product_info_table" >
                  <thead>
                    <tr>
                      <!--<th style="width:5%"><?=$this->lang->line('application_item');?></th>-->
                      <th style="width:10%"><?=$this->lang->line('application_sku');?></th>
                      <th style="width:5%"><?=$this->lang->line('application_image');?></th>
                      <th style="width:22%"><?=$this->lang->line('application_product');?></th>
                      <?=$order_data['order_item'][0]['catalog'] ? "<th style='width:15%'>{$this->lang->line('application_catalog')}</th>" : ''?>
                      <th style="width:7%"><?=$this->lang->line('application_qty');?></th>
                      <th style="width:10%">
                        <div data-toggle="tooltip" data-container="body" data-placement="top" title="<?=$sellercenter=='somaplace'?$this->lang->line("application_hints_gs_value"):''?>">
                          <?=$this->lang->line('application_value');?>
                        </div>
                      </th>
                      <th style="width:10%">
                        <div data-toggle="tooltip" data-container="body" data-placement="top" title="<?=$sellercenter=='somaplace'?$this->lang->line("application_hints_gs_discount"):''?>">
                          <?=$this->lang->line('application_discount');?>
                        </div>
                      </th>
                      <th style="width:10%">
                        <div data-toggle="tooltip" data-container="body" data-placement="top" title="<?=$sellercenter=='somaplace'?$this->lang->line("application_hints_gs_net_value"):''?>">
                          <?=$this->lang->line('application_net_value');?>
                        </div>
                      </th>
                      <th style="width:10%"><?=$this->lang->line('application_kit');?></th>
                    </tr>
                  </thead>

                   <tbody>

                    <?php if(isset($order_data['order_item'])): ?>
                      <?php $x = 1; ?>
                      <?php foreach ($order_data['order_item'] as $key => $val): ?>
                       <?php if($val['qty'] != $val['qty_canceled']): ?>
                       <tr id="row_<?php echo $x; ?>">
                           <!---<td><?=$x; ?></td>-->
                           <?php if($val['status'] == Model_products::DELETED_PRODUCT) {?>
                               <td class="deleted-order-item">
                                   <a href="#" data-toggle="tooltip" data-placement="right"
                                      title="<?= $this->lang->line('application_excluded_products') ?>">
                                       <span><?php echo $val['sku'] ?></span>
                                       <i class="fa fa-trash-o"></i>
                                   </a>
                               </td>
                           <?php } else { ?>
                               <td class="link-order-item">
                                  <a href="<?php echo base_url().'products/update/'.$val['product_id'];?>" target="_blank">
                                      <span><?php echo $val['sku'] ?></span>
                                      <i class="fa fa-eye"></i>
                                  </a>
                              </td>
                           <?php } ?>
                          <!---
                          <td><input type="text" name="sku[]" id="sku_<?php echo $x; ?>" class="form-control" required <?php echo $more; ?> onkeyup="getTotal(<?php echo $x; ?>)" value="<?php echo $val['sku'] ?>" autocomplete="off"></td>
                          --->
                          <td><img style="width: 60px; height: 60px;" class="img-circle" src="<?php
                            if ($val['picture']) {
                              // echo base_url('/assets/images/product_image/').$val['image'].'/'.$val['picture'];
                              echo $val['picture'];
                            } else {
                              echo base_url('/assets/images/system/sem_foto.png');
                            } ?>" /></td>
                          <td><input type="text" name="name[]" id="name_<?php echo $x; ?>" class="form-control" required <?php echo $more; ?> onkeyup="getTotal(<?php echo $x; ?>)" value="<?php echo $val['name'] ?>" autocomplete="off"></td>
                          <?=$val['catalog'] ? "<td><input type='text' id='catalog_{$x}' class='form-control' required {$more} value='{$val['catalog']}' autocomplete='off'></td>" : ''?>
                          <td><input type="text" name="qty[]" id="qty_<?php echo $x; ?>" class="form-control" required <?php echo $more; ?> onkeyup="getTotal(<?php echo $x; ?>)" value="<?php echo ($val['qty'] - $val['qty_canceled']) ?>" autocomplete="off"></td>
                          <td>
                            <input type="text" name="rate[]" id="rate_<?php echo $x; ?>" class="form-control"  <?php echo $more; ?> value="<?php echo get_instance()->formatprice($val['rate'] + $val['discount']) ?>" autocomplete="off">
                          </td>
                          <td>
                            <input type="text" name="discount[]" id="discount_<?php echo $x; ?>" class="form-control"  <?php echo $more; ?> value="<?php echo get_instance()->formatprice($val['discount']) ?>" autocomplete="off">
                          </td>
                          <td>
                            <input type="text" name="amount[]" id="amount_<?php echo $x; ?>" class="form-control"  <?php echo $more; ?> value="<?php echo get_instance()->formatprice($val['rate']) ?>" autocomplete="off">
                          </td>
                          <td>
                          	<?php if ($val['kit_id']) : ?>
                            <a id="kit_id" name="kit_id" href="<?php echo base_url().'productsKit/update/'.$val['kit_id'];?>"  target="_blank"><span "><?php echo $val['kit_id']; ?> &nbsp </span><i class="fa fa-eye"></i></a>
                          	<?php endif; ?>
                          </td>
                       </tr>
                       <?php endif; ?>
                       <?php $x++; ?>
                     <?php endforeach; ?>
                   <?php endif; ?>
                   </tbody>
                </table>
                </div>
              <br /> <br/>

              <?php if ($has_canceled_item) : ?>
              <div class="col-md-12">
                  <h3><?=$this->lang->line('application_canceled_items')?></h3>
              </div>
              <div class="col-md-12" style="overflow-x:auto;">
                  <table class="table table-bordered" id="product_info_table" >
                      <thead>
                      <tr>
                          <!--<th style="width:5%"><?=$this->lang->line('application_item');?></th>-->
                          <th style="width:10%"><?=$this->lang->line('application_sku');?></th>
                          <th style="width:5%"><?=$this->lang->line('application_image');?></th>
                          <th style="width:22%"><?=$this->lang->line('application_product');?></th>
                          <?=$order_data['order_item'][0]['catalog'] ? "<th style='width:15%'>{$this->lang->line('application_catalog')}</th>" : ''?>
                          <th style="width:7%"><?=$this->lang->line('application_qty');?></th>
                          <th style="width:10%">
                              <div data-toggle="tooltip" data-container="body" data-placement="top" title="<?=$sellercenter=='somaplace'?$this->lang->line("application_hints_gs_value"):''?>">
                                  <?=$this->lang->line('application_value');?>
                              </div>
                          </th>
                          <th style="width:10%">
                              <div data-toggle="tooltip" data-container="body" data-placement="top" title="<?=$sellercenter=='somaplace'?$this->lang->line("application_hints_gs_discount"):''?>">
                                  <?=$this->lang->line('application_discount');?>
                              </div>
                          </th>
                          <th style="width:10%">
                              <div data-toggle="tooltip" data-container="body" data-placement="top" title="<?=$sellercenter=='somaplace'?$this->lang->line("application_hints_gs_net_value"):''?>">
                                  <?=$this->lang->line('application_net_value');?>
                              </div>
                          </th>
                          <th style="width:10%"><?=$this->lang->line('application_kit');?></th>
                      </tr>
                      </thead>

                      <tbody>

                      <?php if(isset($order_data['order_item'])): ?>
                          <?php $x = 1; ?>
                          <?php foreach ($order_data['order_item'] as $key => $val): ?>
                              <?php if($val['qty_canceled'] != 0): ?>
                                  <tr id="row_<?php echo $x; ?>">
                                      <!---<td><?=$x; ?></td>-->
                                      <?php if($val['status'] == Model_products::DELETED_PRODUCT) {?>
                                          <td class="deleted-order-item">
                                              <a href="#" data-toggle="tooltip" data-placement="right"
                                                 title="<?= $this->lang->line('application_excluded_products') ?>">
                                                  <span><?php echo $val['sku'] ?></span>
                                                  <i class="fa fa-trash-o"></i>
                                              </a>
                                          </td>
                                      <?php } else { ?>
                                          <td class="link-order-item">
                                              <a href="<?php echo base_url().'products/update/'.$val['product_id'];?>" target="_blank">
                                                  <span><?php echo $val['sku'] ?></span>
                                                  <i class="fa fa-eye"></i>
                                              </a>
                                          </td>
                                      <?php } ?>
                                      <!---
                      <td><input type="text" name="sku[]" id="sku_<?php echo $x; ?>" class="form-control" required <?php echo $more; ?> onkeyup="getTotal(<?php echo $x; ?>)" value="<?php echo $val['sku'] ?>" autocomplete="off"></td>
                      --->
                                      <td><img style="width: 60px; height: 60px;" class="img-circle" src="<?php
                                          if ($val['picture']) {
                                              // echo base_url('/assets/images/product_image/').$val['image'].'/'.$val['picture'];
                                              echo $val['picture'];
                                          } else {
                                              echo base_url('/assets/images/system/sem_foto.png');
                                          } ?>" /></td>
                                      <td><input type="text" name="name[]" id="name_<?php echo $x; ?>" class="form-control" required <?php echo $more; ?> onkeyup="getTotal(<?php echo $x; ?>)" value="<?php echo $val['name'] ?>" autocomplete="off"></td>
                                      <?=$val['catalog'] ? "<td><input type='text' id='catalog_{$x}' class='form-control' required {$more} value='{$val['catalog']}' autocomplete='off'></td>" : ''?>
                                      <td><input type="text" name="qty[]" id="qty_<?php echo $x; ?>" class="form-control" required <?php echo $more; ?> onkeyup="getTotal(<?php echo $x; ?>)" value="<?php echo $val['qty_canceled'] ?>" autocomplete="off"></td>
                                      <td>
                                          <input type="text" name="rate[]" id="rate_<?php echo $x; ?>" class="form-control"  <?php echo $more; ?> value="<?php echo get_instance()->formatprice($val['rate'] + $val['discount']) ?>" autocomplete="off">
                                      </td>
                                      <td>
                                          <input type="text" name="discount[]" id="discount_<?php echo $x; ?>" class="form-control"  <?php echo $more; ?> value="<?php echo get_instance()->formatprice($val['discount']) ?>" autocomplete="off">
                                      </td>
                                      <td>
                                          <input type="text" name="amount[]" id="amount_<?php echo $x; ?>" class="form-control"  <?php echo $more; ?> value="<?php echo get_instance()->formatprice($val['rate']) ?>" autocomplete="off">
                                      </td>
                                      <td>
                                          <?php if ($val['kit_id']) : ?>
                                              <a id="kit_id" name="kit_id" href="<?php echo base_url().'productsKit/update/'.$val['kit_id'];?>"  target="_blank"><span "><?php echo $val['kit_id']; ?> &nbsp </span><i class="fa fa-eye"></i></a>
                                          <?php endif; ?>
                                      </td>
                                  </tr>
                              <?php endif; ?>
                              <?php $x++; ?>
                          <?php endforeach; ?>
                      <?php endif; ?>
                      </tbody>
                  </table>
              </div>
                <br /> <br/>
                  <?php endif; ?>


                  <div class="form-group col-md-3">
                    <label for="num_itens"><?=$this->lang->line('application_items');?></label>
                    <div>
                      <input type="text" class="form-control" id="num_items" name="num_items" <?php echo $more; ?> value="<?php echo $order_data['order']['num_items'] ?>" autocomplete="off">
                    </div>
                  </div>

                  <div class="form-group col-md-3">
                    <label for="sum_qty"><?=$this->lang->line('application_total_qty');?></label>
                    <div>
                      <input type="text" class="form-control" id="sum_qty" name="num_items" <?php echo $more; ?> value="<?php echo $order_data['order']['sum_qty'] ?>" autocomplete="off">
                    </div>
                  </div>

                  <div class="form-group col-md-3">
                    <label for="total_order" data-toggle="tooltip" title="<?=$sellercenter=='somaplace'?$this->lang->line("application_hints_gs_total_products"):''?>"><?=$this->lang->line('application_total_products');?></label>
                    <div>
                      <input type="text" class="form-control" id="gross_amount" name="total_order"  <?php echo $more; ?> value="<?php echo get_instance()->formatprice($order_data['order']['total_order']) ?>" autocomplete="off">
                    </div>
                  </div>

                  <div class="form-group col-md-3">
                    <label for="discount"><?=$this->lang->line('application_discount');?></label>
                      <span id="verModalResumoDesconto" orderId="<?= $order_data['order']['id'] ?>" style="float:right;font-weight:normal;cursor:pointer">
                        Ver detalhamento <i class="fa fa-eye"></i>
                      </span>
                    <div>
                      <input type="text" class="form-control" id="discount" name="discount" <?php echo $more; ?> onkeyup="subAmount()" value="<?php echo get_instance()->formatprice($order_data['order']['discount']) ?>" autocomplete="off">
                    </div>
                  </div>
                  <div class="form-group col-md-3">
                    <label for="freight" data-toggle="tooltip" title="<?=$sellercenter=='somaplace'?$this->lang->line("application_hints_gs_ship_value"):''?>"><?=$this->lang->line('application_ship_value');?></label>

                    <span id="verModalFreteDetalhado" orderId="<?= $order_data['order']['id'] ?>" style="float: right; font-weight: normal; cursor: pointer;">
                      Ver detalhamento <i class="fa fa-eye"></i>
                    </span>
                    <div>
                      <input type="text" class="form-control" id="freight" name="freight" <?php echo $more; ?> onkeyup="subAmount()" value="<?php echo get_instance()->formatprice($order_data['order']['total_ship']) ?>" autocomplete="off">
                      <input type="hidden" id="freight_price" value="<?php echo $order_data['order']['total_ship'] ?>">
                    </div>
                  </div>
                  <div class="form-group col-md-3">
                    <label for="gross_amount" data-toggle="tooltip" title="<?=$sellercenter=='somaplace'?$this->lang->line("application_hints_gs_gross_amount"):$this->lang->line('application_value_products')." + ".$this->lang->line('application_freight')." - ".$this->lang->line('application_discount')?>"><?=$this->lang->line('application_gross_amount');?></label>
                    <div>
                      <input type="text" class="form-control" id="gross_amount" name="gross_amount" <?php echo $more; ?> value="<?php echo get_instance()->formatprice($order_data['order']['gross_amount']) ?>" autocomplete="off">
                    </div>
                  </div>
                  <div class="form-group col-md-3" style="display: <?=$hide_taxes ? 'none' : 'block'?>">
                    <label for="service_charge"><?=$this->lang->line('application_taxes');?></label>


                    <span id="verModalResumoTaxas" orderId="<?= $order_data['order']['id'] ?>" style="float:right;font-weight:normal;cursor:pointer">
                      Ver detalhamento <i class="fa fa-eye"></i>
                    </span>

                    <?php $taxas = $order_data['order']['service_charge'] + $order_data['order']['vat_charge']; ?>

                    <div>
                      <input type="text" class="form-control" id="total_charge" name="total_charge"  <?php echo $more; ?> value="<?php
                        if($sellercenter=='somaplace'){
                            echo get_instance()->formatprice($taxas);
                        }
                        else if ($totalTax > 0 || $totalTax < 0)
                        {
                            echo get_instance()->formatprice($totalTax);
                        }
                        else if ($tipo_frete['expectativaReceb'] == "0" || $sellercenter=='novomundo')
                        {
                            echo get_instance()->formatprice($taxas);
                        }
                        else
                        {
                            echo get_instance()->formatprice($tipo_frete['taxa_descontada']);
                        }
                        ?>" autocomplete="off">
                    </div>
                  </div>
                  <div class="form-group col-md-3">
                    <label for="net_order"  data-toggle="tooltip"
                    title="
                    <?php if ($sellercenter=='somaplace'){
                      echo $this->lang->line("application_hints_gs_total_net_value");
                    }else{
                      if($order_data['order']['origin'] == "B2W"){
                        if($tipo_frete['tipo_frete'] == "Conecta Lá"){
                          if($tipo_frete['tipo_taxa'] == "Especial"){
                            echo $this->lang->line("application_hints_b2w_net_value_2");
                          }else{
                            echo $this->lang->line("application_hints_via_ml_carref_net_value_2");
                          }
                        }else{
                          if($tipo_frete['tipo_taxa'] == "Especial"){
                            echo $this->lang->line("application_hints_b2w_net_value_1");
                          }else{
                            echo $this->lang->line("application_hints_via_ml_carref_net_value_1");
                          }
                        }
                      }else{
                        if($tipo_frete['tipo_frete'] == "Conecta Lá"){
                          echo $this->lang->line("application_hints_via_ml_carref_net_value_2");
                        }else{
                          echo $this->lang->line("application_hints_via_ml_carref_net_value_1");
                        }
                      }
                    }?>">
                    <?=$this->lang->line('application_net_value');?></label>
                    <div>
                      <input type="text" class="form-control" id="net_order" name="net_order" <?=$more?> value="
<?php // Se alterar essa lógica, não esqueça de alterar na API :-) ?>
<?=$tipo_frete['expectativaReceb'] == "0" || $sellercenter=='somaplace' || $sellercenter=='novomundo' ? get_instance()->formatprice($order_data['order']['net_amount']) : get_instance()->formatprice($tipo_frete['expectativaReceb'])?>" autocomplete="off">
                    </div>
                  </div>

               <div class="col-md-12">
                	<h3><?=$this->lang->line('application_payments')?></h3>
                </div>
               <div class="col-md-12 " style="overflow-x:auto;">
                <table class="table table-bordered" id="product_info_table">
                  <thead>
                    <tr>
                      <th style="width:7%">ID <?=$this->lang->line('application_payment');?></th>
                      <th style="width:8%"><?=$this->lang->line('application_order');?></th>
                      <th style="width:10%"><?=$this->lang->line('application_parcel');?></th>
                      <th style="width:20%"><?=$this->lang->line('application_date');?></th>
                      <th style="width:15%">
                        <div id="popover" style="font-size: small;" data-container="body" data-toggle="tooltip" data-html="true" data-placement="top" title="<?=$sellercenter=='somaplace'?$this->lang->line("application_hints_gs_payments_value"):''?>">
                      <?=$this->lang->line('application_value');?>
                        </div>

                      </th>
                      <th style="width:20%"><?=$this->lang->line('application_description');?></th>
                      <th style="width:20%"><?=$this->lang->line('application_type');?></th>
                    </tr>
                  </thead>

                   <tbody>

                    <?php if(isset($order_data['pagtos'])): ?>
                      <?php $x = 1; ?>
                      <?php foreach ($order_data['pagtos'] as $key => $val): ?>
                        <?php //print_r($v); ?>
                       <tr id="row_<?php echo $x; ?>">
                          <td><input type="text" name="id[]" id="id_<?php echo $x; ?>" class="form-control" required <?php echo $more; ?> onkeyup="getTotal(<?php echo $x; ?>)" value="<?php echo $val['id'] ?>" autocomplete="off"></td>
                          <td><input type="text" name="order_id[]" id="order_id_<?php echo $x; ?>" class="form-control" required <?php echo $more; ?> onkeyup="getTotal(<?php echo $x; ?>)" value="<?php echo $val['order_id'] ?>" autocomplete="off"></td>
                          <td><input type="text" name="parcela[]" id="parcela_<?php echo $x; ?>" class="form-control" required <?php echo $more; ?> onkeyup="getTotal(<?php echo $x; ?>)" value="<?php echo $val['parcela'] ?>" autocomplete="off"></td>
                          <td><input type="text" name="date_sent[]" id="date_sent_<?php echo $x; ?>" class="form-control" required <?php echo $more; ?> onkeyup="getTotal(<?php echo $x; ?>)" value="<?=date('d/m/Y H:i', strtotime($val['data_vencto']))?>" autocomplete="off"></td>
                          <td><input type="text" name="valparc[]" id="valparc_<?php echo $x; ?>" class="form-control"  <?php echo $more; ?> value="<?php echo get_instance()->formatprice($val['valor']) ?>" autocomplete="off"></td>
                          <td>
                            <div class="<?=$only_admin == 1 && !empty($val['gift_card_provider']) ? 'input-group' : '' ?>">
                                <input type="text" name="forma_desc[]" id="forma_desc_<?php echo $x; ?>" class="form-control"  <?php echo $more; ?> value="<?php echo $val['forma_desc'] ?>" autocomplete="off">
                                <?php if ($only_admin == 1 && !empty($val['gift_card_provider'])): ?>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-primary" data-toggle="tooltip" title="<?=$val['gift_card_provider']?>"><i class="fa fa-eye"></i></button>
                                </span>
                                <?php endif; ?>
                            </div>
                          </td>
                          <td><input type="text" name="forma_id[]" id="forma_desc_<?=$x?>" class="form-control"  <?=$more?> value="<?=$val['forma_id']?>" autocomplete="off"></td>
                       </tr>
                       <?php $x++; ?>
                     <?php endforeach; ?>
                   <?php else: echo "<tr><td colspan=6>" . $this->lang->line('messages_no_informations') . "</td></tr>";  ?>
                   <?php endif; ?>
                   </tbody>
                </table>
                </div>
                <?php
                    if (in_array('transactionInteractionsOrder', $user_permission)):
                ?>
                <div class="col-md-12">
                    <h3><?=$this->lang->line('application_marketplace_payment_history')?></h3>
                </div>
                <div class="col-md-12 " style="overflow-x:auto;">
                    <table class="table table-bordered" id="product_info_table">
                        <thead>
                            <tr>
                                <th style="width:15%">ID <?=$this->lang->line('application_payment');?></th>
                                <th style="width:35%"><?=$this->lang->line('application_order_status_update_date');?></th>
                                <th style="width:40%"><?=$this->lang->line('application_status');?></th>
                                <th style="width:10%"><?=$this->lang->line('application_action');?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_data['interactionsPayment'] as $interaction): ?>
                                <tr>
                                    <td><input type="text" class="form-control" value="<?=$interaction['payment_id'] ?>" disabled></td>
                                    <td><input type="text" class="form-control" value="<?=dateFormat($interaction['interaction_date'], 'd/m/Y H:i', null)?>" disabled></td>
                                    <td><input type="text" class="form-control"  value="<?=$interaction['transaction_status'] ?>" disabled></td>
                                    <td><button type="button" class="btn btn-default viewInteractionsPayment" payment-id="<?=$interaction['payment_id'] ?>"><i class="fa fa-eye"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <div class="col-md-12">
                	<h3>NFe</h3>
                </div>

                  <?php
                  if (!in_array($order_data['order']['paid_status'], [95,96,97,98,99])):
                  ?>
                 <div class="form-group col-md-3">
                    <label><?=$this->lang->line('application_crossdocking_limit_date');?></label>
                    <div>
                    	<span class="form-control"><?php $date = new DateTime($order_data['order']['data_limite_cross_docking']); echo !$order_data['order']['data_limite_cross_docking'] ? '' : date_format($date, 'd/m/Y'); ?></span>
                    </div>
                 </div>
                  <?php
                  endif;
                  ?>
				 <div class="col-md-12 " style="overflow-x:auto;">
                 <table class="table table-bordered" id="product_info_table">
                  <thead>
                    <tr>
                      <th style="width:5%"><?=$this->lang->line('application_item');?></th>
                      <th style="width:8%"><?=$this->lang->line('application_ship_date');?></th>
                      <th style="width:8%"><?=$this->lang->line('application_serie');?></th>
                      <th style="width:8%"><?=$this->lang->line('application_number');?></th>
                      <th style="width:20%"><?=$this->lang->line('application_key');?></th>
                        <th style="width:20%">NFe</th>
                    </tr>
                  </thead>

                   <tbody>

                    <?php if(isset($order_data['nfes'])): ?>
                      <?php $x = 1; ?>
                      <?php foreach ($order_data['nfes'] as $key => $val): ?>
                        <?php //print_r($v); ?>
                       <tr id="row_<?php echo $x; ?>">
                          <td><input type="text" name="id[]" id="id_<?php echo $x; ?>" class="form-control" required <?php echo $more; ?> onkeyup="getTotal(<?php echo $x; ?>)" value="<?php echo $val['id'] ?>" autocomplete="off"></td>
                          <td><input type="text" name="date_emission[]" id="date_emission_<?php echo $x; ?>" class="form-control" required <?php echo $more; ?> onkeyup="getTotal(<?php echo $x; ?>)" value="<?php echo $val['date_emission'] ?>" autocomplete="off"></td>

                          <td>
                            <input type="text" name="nfe_serie[]" id="nfe_serie_<?php echo $x; ?>" class="form-control"  <?php echo $more; ?> value="<?php echo $val['nfe_serie'] ?>" autocomplete="off">
                          </td>
                          <td>
                            <input type="text" name="nfe_num[]" id="nfe_num_<?php echo $x; ?>" class="form-control"  <?php echo $more; ?> value="<?php echo $val['nfe_num'] ?>" autocomplete="off">
                          </td>
                          <td>
                            <input type="text" name="chave[]" id="chave_<?php echo $x; ?>" class="form-control"  <?php echo $more; ?> value="<?php echo $val['chave'] ?>" autocomplete="off">
                          </td>
                           <td>
                               <input type="text" name="link_nfe[]" id="link_nfe_<?php echo $x; ?>" class="form-control" <?php echo $more; ?> value="<?php echo $val['link_nfe'] ?>" autocomplete="off">
                           </td>
                       </tr>
                       <?php $x++; ?>
                     <?php endforeach; ?>
                   <?php else: echo "<tr><td colspan=6>" . $this->lang->line('messages_no_informations') . "</td></tr>";  ?>
                   <?php endif; ?>
                   </tbody>
                </table>
                </div>

                <?php if(in_array($order_data['order']['paid_status'], array(1,3)) && $sellercenter == 'conectala' && in_array($order_data['order']['ship_service_preview'], array(1,'Transportadora'))) { ?>
                <div class="col-md-12">
                	<h3><?=$this->lang->line('application_ship_company_info')?></h3>
                </div>
                 <div class="form-group card col-md-12">
                      <div class="card-body">
                      <?=$this->lang->line('messages_request_shipping_company_billing_data')?>
                      <br><a  target= _blank href="https://<?=$site_agidesk?>.agidesk.com/servicos/secoes/logistica?access_token=<?=$token_agidesk ?>" class = 'btn btn-default' > <i class="fas fa-link"> </i> &nbsp; <?=$this->lang->line('application_request_billing_data');?> </a>
                      </div>
                 </div>
                 <?php  }?>

               <div class="col-md-12 col-xs-12 pull pull-left">
                <h3><?=$this->lang->line($order_data['order']['is_pickup_in_point'] ? 'application_delivery_withdrawal' : 'application_delivery_address')?> <small>(<?=$this->lang->line('application_hints_use_for_billing')?>)</small>
                    <?php
                    if(in_array('chageDeliveryAddress', $this->permission)):
                        if(in_array($order_data['order']['paid_status'], $paid_status_authorized_change_address)): ?>
                            <a href="<?= base_url(); ?>orders/updateaddress/<?= $order_data['order']['id'] ?>" class="btn btn-primary"><i class="fa fa-truck">&nbsp<?=$this->lang->line('application_change_delivery_address');?></i></a>
                    <?php endif;
                    endif; ?>
                </h3>
               </div>

            	  <div class="form-group col-md-5">
                    <label for="customer_address"><?=$this->lang->line('application_address');?> </label>
                    <div>
                      <input type="text" class="form-control" id="customer_address" name="customer_address" <?php echo $more; ?> value="<?php echo $order_data['order']['customer_address'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-1">
                    <label for="customer_address_num"><?=$this->lang->line('application_number');?></label>
                    <div>
                      <input type="text" class="form-control" id="customer_address_num" name="customer_address_num" <?php echo $more; ?> value="<?php echo $order_data['order']['customer_address_num'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="customer_address_compl"><?=$this->lang->line('application_complement');?></label>
                    <div>
                      <input type="text" class="form-control" id="customer_address_compl" name="customer_address_compl" <?php echo $more; ?> value="<?php echo $order_data['order']['customer_address_compl'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-4">
                    <label for="customer_address_neigh"><?=$this->lang->line('application_neighb');?></label>
                    <div>
                      <input type="text" class="form-control" id="customer_address_neigh" name="customer_address_neigh" <?php echo $more; ?> value="<?php echo $order_data['order']['customer_address_neigh'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-4">
                    <label for="customer_address_city"><?=$this->lang->line('application_city');?></label>
                    <div>
                      <input type="text" class="form-control" id="customer_address_city" name="customer_address_city" <?php echo $more; ?> value="<?php echo $order_data['order']['customer_address_city'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                   <div class="form-group col-md-1">
                    <label for="customer_address_uf"><?=$this->lang->line('application_uf');?></label>
                    <div>
                      <input type="text" class="form-control" id="customer_address_uf" name="customer_address_uf" <?php echo $more; ?> value="<?php echo $order_data['order']['customer_address_uf'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="customer_address_zip"><?=$this->lang->line('application_zip_code');?></label>
                    <div>
                      <input type="text" class="form-control" id="customer_address_zip" name="customer_address_zip" <?php echo $more; ?> value="<?php echo $order_data['order']['customer_address_zip'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-5">
                    <label for="customer_reference"><?=$this->lang->line('application_reference');?></label>
                    <div>
                      <input type="text" class="form-control" id="customer_reference" name="customer_reference" <?php echo $more; ?> value="<?php echo $order_data['order']['customer_reference'] ?>" autocomplete="off"/>
                    </div>
                  </div>

                  <?php if ($order_data['order']['is_pickup_in_point']): ?>
                      <div class="form-group col-md-5">
                          <label for="customer_reference"><?=$this->lang->line('pickup_in_point_method_chosen_in_the_order');?></label>
                          <div>
                              <a href="<?=base_url('PickupPoint/edit/' . ($pickup_point_order->id ?? 0))?>" target="_blank"><span "><?=$pickup_point_order->name ?? 'Ponto de Retirada'?> &nbsp; </span><i class="fa fa-eye"></i></a>
                          </div>
                      </div>
                  <?php else: ?>
                      <div class="form-group col-md-5">
                          <label for="customer_reference"><?=$this->lang->line('carrier_chosen_on_order');?></label>
                          <div>
                            <input type="text" class="form-control" id="carrier_chosen_on_order" name="carrier_chosen_on_order" <?php echo $more; ?> value="<?php echo $order_data['order']['ship_companyName_preview']?:$order_data['order']['ship_company_preview'] ?>" autocomplete="off" readonly/>
                          </div>
                      </div>
                      <div class="form-group col-md-5">
                          <label for="customer_reference"><?=$this->lang->line('shipping_method_chosen_in_the_order');?></label>
                          <div>
                              <input type="text" class="form-control" id="carrier_chosen_on_order" name="carrier_chosen_on_order" <?php echo $more; ?> value="<?php echo $order_data['order']['ship_service_preview'] ?>" autocomplete="off" readonly/>
                          </div>
                      </div>
                      <?php if(in_array('updateOrder', $user_permission)): ?>
                          <div class="form-group col-md-2">
                              <label for="customer_reference">&nbsp;</label>
                              <div>
                                  <button type="button" class="btn btn-primary col-md-12" id="update_shipping_method" data-toggle="modal" data-target="#updateShippingMethodModal"><?=$this->lang->line('application_update_shipping_method');?></button>
                              </div>
                          </div>
                      <?php endif; ?>
                  <?php endif; ?>

               <div class="col-md-12 col-xs-12 pull pull-left">
                <h3><?=$this->lang->line('application_invoice_data')?></h3>
               </div>
                  <div class="form-group col-md-5">
                    <label for="client_name"><?=$this->lang->line('application_client_name');?></label>
                    <div>
                      <input type="text" class="form-control" id="client_name" name="client_name" <?php echo $more; ?> value="<?php echo $order_data['order']['cliente']['customer_name'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-3">
                    <label for="client_cpf_cnpj"><?php echo $this->lang->line('application_cpf').'/'.$this->lang->line('application_cnpj');?></label>
                    <div>
                    	<input type="text" class="form-control" id="client_cpf_cnpj" name="client_cpf_cnpj" <?php echo $more; ?> value="<?php echo $order_data['order']['cliente']['cpf_cnpj'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-4" <?= (!in_array('viewInfoContactUserOrder', $user_permission) ? '<th style="visibility: hidden;"' : '') ?>>
                    <label for="client_email"><?=$this->lang->line('application_email');?></label>
                    <div>
                      <input type="text" class="form-control" id="client_email" name="client_email" <?php echo $more; ?> value="<?php echo $order_data['order']['cliente']['email'] ?>" autocomplete="off"/>
                    </div>
                  </div>
            	  <div class="form-group col-md-5">
                    <label for="client_address"><?=$this->lang->line('application_address');?></label>
                    <div>
                      <input type="text" class="form-control" id="client_address" name="client_address" <?php echo $more; ?> value="<?php echo $order_data['order']['cliente']['customer_address'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-1">
                    <label for="client_address_num"><?=$this->lang->line('application_number');?></label>
                    <div>
                      <input type="text" class="form-control" id="client_address_num" name="client_address_num" <?php echo $more; ?> value="<?php echo $order_data['order']['cliente']['addr_num'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="client_address_compl"><?=$this->lang->line('application_complement');?></label>
                    <div>
                      <input type="text" class="form-control" id="client_address_compl" name="client_address_compl" <?php echo $more; ?> value="<?php echo $order_data['order']['cliente']['addr_compl'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-4">
                    <label for="client_address_neigh"><?=$this->lang->line('application_neighb');?></label>
                    <div>
                      <input type="text" class="form-control" id="client_address_neigh" name="client_address_neigh" <?php echo $more; ?> value="<?php echo $order_data['order']['cliente']['addr_neigh'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-5">
                    <label for="client_address_city"><?=$this->lang->line('application_city');?></label>
                    <div>
                      <input type="text" class="form-control" id="client_address_city" name="client_address_city" <?php echo $more; ?> value="<?php echo $order_data['order']['cliente']['addr_city'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                   <div class="form-group col-md-1">
                    <label for="client_address_uf"><?=$this->lang->line('application_uf');?></label>
                    <div>
                      <input type="text" class="form-control" id="client_address_uf" name="client_address_uf" <?php echo $more; ?> value="<?php echo $order_data['order']['cliente']['addr_uf'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="client_address_zip"><?=$this->lang->line('application_zip_code');?></label>
                    <div>
                      <input type="text" class="form-control" id="client_address_zip" name="client_address_zip" <?php echo $more; ?> value="<?php echo $order_data['order']['cliente']['zipcode'] ?>" autocomplete="off"/>
                    </div>
                  </div>
                  <div class="form-group col-md-2" <?= (!in_array('viewInfoContactUserOrder', $user_permission) ? '<th style="visibility: hidden;"' : '') ?>>
                   <label for="phone_1"><?=$this->lang->line('application_phone');?> 1</label>
                   <div>
                       <input type="text" class="form-control" id="phone_1" name="phone_1" <?php echo $more; ?> value="<?php echo $order_data['order']['cliente']['phone_1'] ?>" autocomplete="off"/>
                   </div>
                  </div>
                  <div class="form-group col-md-2" <?= (!in_array('viewInfoContactUserOrder', $user_permission) ? '<th style="visibility: hidden;"' : '') ?>>
                   <label for="phone_2"><?=$this->lang->line('application_phone');?> 2</label>
                   <div>
                       <input type="text" class="form-control" id="phone_2" name="phone_2" <?php echo $more; ?> value="<?php echo $order_data['order']['cliente']['phone_2'] ?>" autocomplete="off"/>
                   </div>
                  </div>

            <?php if (!$order_data['order']['is_pickup_in_point']): ?>
               <div class="col-md-12">
                <h3><?=$this->lang->line('application_freight')?></h3>
                <h4>Logistíca do <?= $order_data['order']['freight_seller'] == 1 ? "Seller" : "Marketplace" ?></h4>
               </div>
               <div class="col-md-12 " style="overflow-x:auto;">
                <table class="table table-bordered" id="product_info_table">
                  <thead>
                    <tr>
                      <th style="width:5%"><?=$this->lang->line('application_item');?></th>
                      <th style="width:20%"><?=$this->lang->line('application_ship_company');?></th>
                      <th style="width:10%"><?=$this->lang->line('application_service');?></th>
                      <th style="width:8%"><?=$this->lang->line('application_delivered_date');?></th>
                      <?=(in_array('admDashboard', $user_permission)) ? '<th style="width:10%">'.$this->lang->line('application_value').'</th>' : ''?>
                      <th style="width:10%"><?=$this->lang->line('application_tracking code');?></th>
                      <th style="width:10%"><?=$this->lang->line('application_gather_date');?></th>
                        <th style="width:10%"><?=$this->lang->line('application_tracker_link');?></th>
                       <!--
                      <th style="width:8%"><?=$this->lang->line('application_status');?></th>
                       -->
                    </tr>
                  </thead>

                   <tbody>

                    <?php if(isset($order_data['freights'])): ?>
                      <?php $x = 1; ?>
                      <?php foreach ($order_data['freights'] as $key => $val): ?>
                        <?php //print_r($v); ?>
                       <tr id="row_<?php echo $x; ?>">
                          <td><span class="form-control"><?php echo $val['id'] ?></span></td>
                          <td><span class="form-control"><?php echo $val['ship_company'] ?></span></td>
                          <td><span class="form-control"><?php echo $val['method'] ?></span></td>
                          <td><span class="form-control"><?php echo $val['date_delivered'] ?></span></td>
                          <?=(in_array('admDashboard', $user_permission)) ? '<td><span class="form-control">'.get_instance()->formatprice($val['ship_value']).'</span></td>' : ''?>
                          <td><span class="form-control"><?php echo $val['codigo_rastreio'] ?></span></td>
                          <td><span class="form-control"><?php echo $order_data['order']['data_coleta'] ?></span></td>
                           <td><span class="form-control"><a href="<?php echo $val['url_tracking'] ?>" target="_blank"><?php echo $val['url_tracking'] ?></a></span></td>
                          <!--
                          <td>
                            <input type="text" name="status_ship[]" id="status_ship_<?php echo $x; ?>" class="form-control" disabled value="<?php echo $val['status_ship'] ?>" autocomplete="off">
                          </td>
                          -->
                       </tr>
                       <?php $x++; ?>
                     <?php endforeach; ?>
                   <?php else: echo "<tr><td colspan=6>" . $this->lang->line('messages_no_informations') . "</td></tr>";  ?>
                   <?php endif; ?>
                   </tbody>
                </table>
                    <div class="list-row">
                      <?php if(isset($order_data['frete_ocorrencias'])): ?>
                     	<?php
                        foreach ($order_data['frete_ocorrencias'] as $code => $freight):?>
                            <h3 class="col-md-12 mt-3 mb-3"><?=$code?></h3>
                            <ol class="timeline-occurrence">
                            <?php foreach($freight as $val):?>
                     		<li>
                                <p class="event-date"><?=date('d/m/Y H:i', strtotime($val['data_atualizacao']))?></p>
                                <p class="event-description"><?=$val['nome'] ?></p>
                     		</li>
                            <?php endforeach; ?>
                            </ol>
                        <?php endforeach; ?>
                     <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
                  <div class="col-md-12">
                      <div class="container pt-5" style="max-width: 535px">
                          <h3 class="text-center"><?=$this->lang->line('application_order_historical');?></h3>
                          <table class="table table-bordered">
                              <thread>
                                  <th><?=$this->lang->line('application_order_status_update');?></th>
                                  <th><?=$this->lang->line('application_order_date_update');?></th>
                              </thread>
                              <?php if($status_now){
                                  foreach ($status_now as $status){ ?>
                                      <tbody>
                                         <td><?= $status['status'] ?></td>
                                         <td><?= $status['date_status_update'] ?></td>
                                      </tbody>
                                  <?php } ?>
                              <?php } else { ?>
                                  <tbody>
                                    <td colspan="2" class="text-center">Nenhuma Atualização</td>
                                  </tbody>
                              <?php } ?>
                          </table>
                      </div>
                  </div>

                  <?php if($commision_charges){ ?>
                  <div class="col-md-5">
                        <table class="table table-bordered">
                          <tr>
                            <th> <h3 >Estorno de cobrança de comissão realizado por <?=$commision_charges['firstname']." ".$commision_charges['lastname']." - ".$commision_charges['email']." ".$commision_charges['data_criacao_formatada']?></h3>
                             <button type="button" class="btn btn-primary" onclick="" data-toggle="modal" data-target="#ViewCancelComissionChargesModal"><i class="fa fa-eye"></i></button></th>
                          </tr>
                        </table>
                  </div>
                  <?php } ?>

                <?php if($has_order_value_refund){ ?>
                    <h3 class="col-md-12 d-flex justify-content-center align-items-center">Estorno de valor de pedido<button type="button" class="btn btn-primary ml-3" onclick="" data-toggle="modal" data-target="#viewOrderValueRefund"><i class="fa fa-eye"></i></button></th></h3>
                <?php } ?>


              </div>



              <!-- /.box-body -->

              <div class="box-footer">

                <input type="hidden" name="service_charge_rate" value="<?php echo $company_data['service_charge_value'] ?>" autocomplete="off">
                <input type="hidden" name="vat_charge_rate" value="<?php echo $company_data['vat_charge_value'] ?>" autocomplete="off">

                <?php if($sellercenter=='conectala'): ?>
                <a target="__blank" href="<?php echo base_url() . 'orders/printDiv/'.$order_data['order']['id'] ?>" class="btn btn-default" ><?=$this->lang->line('application_print');?></a>
                <?php endif; ?>
                <?php if(in_array('updateOrder', $user_permission)): ?>
                <!--- <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_update_changes');?></button> --->
                <?php endif; ?>
                <a href="<?php echo base_url('orders/') ?>" class="btn btn-warning"><?=$this->lang->line('application_back');?></a>
              </div>
            </form>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->
      </div>
      <!-- col-md-12 -->


  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php if(in_array('updateOrder', $user_permission)): ?>
<div class="modal fade" tabindex="-1" role="dialog" id="nfeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php
		    // var_dump($order_data['nfes']);
			if(isset($order_data['nfes'])) {
				$nfe = $order_data['nfes'][0];
				$titulo = $this->lang->line('application_change_invoice');
			}

			else {
				$nfe = array(
				'id' => '',
				'order_id' => $order_data['order']['id'],
				'nfe_num' => '',
				'nfe_serie' => '',
				'date_emission' => '',
				'chave' => '',
				'company_id' => $order_data['order']['company_id']
				);
				$titulo = $this->lang->line('application_insert_invoice');
			}
			$nfe['valor_nfe'] = get_instance()->formatprice($order_data['order']['gross_amount']);
			$nfe['valor_itens'] = get_instance()->formatprice($order_data['order']['total_order']);
      $valorNF = $nfe['valor_nfe'];
      $valorTotalItems = $nfe['valor_itens'];
			if ($gsoma_painel_financeiro == '1') {
          $valorNF = get_instance()->formatprice($order_data['order']['net_amount']);
          $valorTotalItems = get_instance()->formatprice($order_data['order']['total_order'] - $order_data['order']['discount']);
      } else if (is_array($gsoma_painel_financeiro)) {
        if ($gsoma_painel_financeiro['status'] == 1) {
          $valorNF = get_instance()->formatprice($order_data['order']['net_amount']);
          $valorTotalItems = get_instance()->formatprice($order_data['order']['total_order'] - $order_data['order']['discount']);
        }
      }

		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php echo $titulo;?></span></h4>
		</div>
	    <form role="form" action="<?php echo base_url('orders/updatenfe') ?>" method="post" id="nfeForm">
	    <div class="modal-body">

		    <div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_number');?></label></div>
				<div class="form-group col-md-3">
					<input type="text" maxlength="9" minlength="1" name="num_nfe" id="num_nfe" class="form-control" required onKeyPress="return digitos(event, this);" value="<?php echo $nfe['nfe_num']; ?>" autocomplete="off">
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_serie');?></label></div>
				<div class="form-group col-md-3">
					<input type="text" maxlength="3" minlength="1" name="serie_nfe" id="serie_nfe" class="form-control" required onKeyPress="return digitos(event, this);" value="<?php echo $nfe['nfe_serie']; ?>" autocomplete="off">
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_issuance_date');?></label></div>
				<div class="form-group col-md-4">
                    <input type="text" maxlength="19" minlength="19" name="date_emission_nfe" id="date_emission_nfe" class="form-control" required  onKeyPress="return digitos(event, this);" onKeyUp="Mascara('DATAHORA',this,event);" value="<?php echo $nfe['date_emission']; ?>" autocomplete="off">
					<span STYLE="color: blue; font-size: 10pt">&nbsp;&nbsp;dd/mm/aaaa hh:mm:ss</span>
				</div>
			</div>
            <div class="row">
                <div class="form-group col-md-3"><label>Link de Consulta da Nota</label></div>
                <div class="form-group col-md-8">
                    <input type="text" maxlength="255" minlength="1" name="consultation_link_nfe" id="consultation_link_nfe" class="form-control" required value="" autocomplete="off">
                </div>
            </div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_access_key');?></label></div>
				<div class="form-group col-md-8">
                    <input type="text" maxlength="44" minlength="44" name="chave_nfe" id="chave_nfe" class="form-control" required  onKeyPress="return digitos(event, this);" value="<?php echo $nfe['chave']; ?>" autocomplete="off">
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_gather_date');?></label></div>
				<div class="form-group col-md-4">
                    <input type="text" maxlength="10" minlength="10" name="data_coleta_nfe" id="data_coleta_nfe" class="form-control" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('DATA',this,event);" value="<?php echo $order_data['order']['data_coleta']; ?>" autocomplete="off">
					<span STYLE="color: blue; font-size: 10pt">&nbsp;&nbsp;dd/mm/aaaa</span>
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_valuenfe');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="valor_nfe" id="valor_nfe" class="form-control" required readonly onKeyPress="return digitos(event, this);" onKeyUp="Mascara('MOEDA',this,event);" value="<?php echo $valorNF; ?>" autocomplete="off">
				</div>
			</div>
			<div class="row">
				<div class="form-group col-md-3"><label><?=$this->lang->line('application_valueitems');?></label></div>
				<div class="form-group col-md-3">
                     <input type="text" name="valor_itens" id="valor_itens" class="form-control" required readonly onKeyPress="return digitos(event, this);" onKeyUp="Mascara('MOEDA',this,event);" value="<?php echo $valorTotalItems; ?>" autocomplete="off"></td>
				</div>
			</div>
			<input type="hidden" name="id_nfe" value="<?php echo $nfe['id'] ?>" autocomplete="off">
     		<input type="hidden" name="id_pedido" value="<?php echo $nfe['order_id'] ?>" autocomplete="off">
     		<input type="hidden" name="company_id" value="<?php echo $nfe['company_id'] ?>" autocomplete="off">
     		<input type="hidden" name="nfe_data_pago" value="<?php echo $order_data['order']['data_pago'] ?>" autocomplete="off">

		</div> <!-- modal-body -->
	    <div class="modal-footer">
	      <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_cancel');?></button>
	      <button type="submit" class="btn btn-primary" id="do_filter" name="do_filter"><?=$this->lang->line('application_confirm');?></button>

	    </div>
   	</form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<?php if(in_array('deleteOrder', $user_permission) && $order_data['order']['product_return_status'] == 0): ?>

<div class="modal fade" tabindex="-1" role="dialog" id="cancelarOrdemModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php
			$titulo = $this->lang->line('application_cancel_order');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:red;"><?php echo $this->lang->line('application_cancel_order');?></span></h4>
			<br><?=$order_data['order']['paid_status'] == 6 ? $this->lang->line('application_cancel_warning_delivered') : $this->lang->line('application_cancel_warning')?>
		</div>
	    <form role="form" action="<?php echo base_url('orders/cancelarPedido') ?>" method="post" id="cancelarPedidoForm">


                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-2"><label><?=$this->lang->line('application_cancel_reason');?></label></div>
                        <div class="form-group col-md-9">
                            <select class="form-control" id="motivo_cancelamento" name="motivo_cancelamento" required>
                                <?php foreach ($cancel_reasons as $option) {?>
                                    <option value="<?=$option['value']?>"><?=$option['value']?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-2"><label><?=$this->lang->line('application_observation');?></label></div>
                        <div class="form-group col-md-9">
                            <input type="text" class="form-control" id="observation" name="observation">
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-2"><label><?=$this->lang->line('application_penalty_to');?></label></div>
                        <div class="form-group col-md-9">
                          <select class="form-control" id="penalty_to" name="penalty_to">
                            <?php foreach ($cancel_penalty_to as $key => $option) { ?>
                              <option value="<?php echo $option.'|sep|'.$key;?>" ><?=$option;?></option>
                            <?php } ?>
                          </select>
                        </div>
                    </div>


                    <input type="hidden" id="id_cancelamento" name="id_cancelamento" value="<?php echo $order_data['order']['id'] ?>" autocomplete="off">

                </div> <!-- modal-body -->
                <div class="modal-footer">
                    <div class="overlay-wrapper" style="position: relative;height: 60px;">
                        <div class="overlay"  id="overlay-cancel-order" style="display:none">
                            <i class="fas fa-2x fa-sync-alt fa-spin"></i>
                            <div class="text-bold pt-2">Cancelando pedido...</div>
                        </div>
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                        <button type="submit" class="btn btn-primary" id="confirmacancelamento" name="confirmacancelamento"><?=$this->lang->line('application_confirm');?></button>
                    </div>
                </div>

   	</form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>


<!-- Modal para cancelamento de comissão em pedido penalizado -->
<?php if(in_array('updateOrdersCancelCommissionCharges', $user_permission)): ?>

<div class="modal fade" tabindex="-1" role="dialog" id="CancelComissionChargesModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php
			$titulo = $this->lang->line('application_orders_cancel_commission_charges');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:red;"><?php echo $this->lang->line('application_orders_cancel_commission_charges');?></span></h4>
		</div>
      <form role="form" action="<?php echo base_url('orders/cancelcommissioncharges') ?>" method="post" enctype="multipart/form-data" id="CancelComissionChargesForm">

        <div class="modal-body">

          <div class="row">
              <div class="form-group col-md-11">
                <label for="exampleInputFile">Anexos</label><br>
                <input type="file" id="fl_commision_charges_input_file" name="fl_commision_charges_input_file">
              </div>
          </div>

          <div class="row">
              <div class="form-group col-md-12">
                <label><?=$this->lang->line('application_observation');?>:</label><br>
                <textarea class="form-control" id="txt_commission_charges_descricao" name="txt_commission_charges_descricao" placeholder="<?= $this->lang->line('application_observation'); ?>" rows="8"></textarea>
              </div>
          </div>

          <input type="hidden" id="id_pedido_cancelamento_comissao" name="id_pedido_cancelamento_comissao" value="<?php echo $order_data['order']['id'] ?>" autocomplete="off">

        </div> <!-- modal-body -->
        <div class="modal-footer">
            <div class="overlay-wrapper" style="position: relative;height: 60px;">
                <div class="overlay"  id="overlay-cancel-commission-charges-order" style="display:none">
                    <i class="fas fa-2x fa-sync-alt fa-spin"></i>
                    <div class="text-bold pt-2">Estornando Comissão do pedido...</div>
                </div>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                <button type="submit" class="btn btn-primary" id="confirmCancelCommissionCharges" name="confirmCancelCommissionCharges"><?=$this->lang->line('application_confirm');?></button>
            </div>
        </div>

   	  </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<!-- Modal para visualização cancelamento de comissão em pedido penalizado -->
<?php if($commision_charges){ ?>

<div class="modal fade" tabindex="-1" role="dialog" id="ViewCancelComissionChargesModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php
			$titulo = $this->lang->line('application_orders_cancel_commission_charges');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:red;"><?php echo $this->lang->line('application_orders_cancel_commission_charges');?></span></h4>
		</div>
      <form role="form" action="<?php echo base_url('orders/cancelcommissioncharges') ?>" method="post" enctype="multipart/form-data" id="ViewCancelComissionChargesForm">

        <div class="modal-body">

          <div class="row">
              <div class="form-group col-md-11">
                <label >Anexos</label><br>
                <?php if($commision_charges['file']) { ?>
                  <a href="<?php echo base_url($commision_charges['file']) ?>"><?="Anexo"?></a>
                <?php }else{?>
                  Nenhum arquivo anexado ao estorno de comissão.
                <?php }?>
              </div>
          </div>

          <div class="row">
              <div class="form-group col-md-12">
                <label><?=$this->lang->line('application_observation');?>:</label><br>
                <textarea class="form-control" id="txt_commission_charges_descricao" name="txt_commission_charges_descricao" placeholder="<?= $this->lang->line('application_observation'); ?>" rows="8" readonly><?php echo $commision_charges['observation'];?></textarea>
              </div>
          </div>

        </div> <!-- modal-body -->
        <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
        </div>

   	  </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php } ?>

<!-- Modal para visualização de atualização de método de entrega -->
<?php if(in_array('updateOrder', $user_permission)){ ?>

<div class="modal fade" tabindex="-1" role="dialog" id="updateShippingMethodModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content modal-lg">
    	<?php
			$titulo = $this->lang->line('application_update_shipping_method');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:red;"><?php echo $this->lang->line('application_update_shipping_method');?></span></h4>
		</div>
      <form role="form" action="<?=base_url("orders/updateShippingMethod/{$order_data['order']['id']}") ?>" method="post" enctype="multipart/form-data" id="updateShippingMethodForm">

        <div class="modal-body">

          <div class="row">

              <div class="form-group col-md-5">
                  <label><?=$this->lang->line('carrier_chosen_on_order');?></label>
                  <div>
                      <input type="text" class="form-control" name="shipping_name" value="<?=$order_data['order']['ship_company_preview'] ?>" autocomplete="off" required/>
                  </div>
              </div>
              <div class="form-group col-md-5">
                  <label><?=$this->lang->line('shipping_method_chosen_in_the_order');?></label>
                  <div>
                      <input type="text" class="form-control" name="shipping_method" value="<?=$order_data['order']['ship_service_preview'] ?>" autocomplete="off" required/>
                  </div>
              </div>
          </div>

        </div> <!-- modal-body -->
        <div class="modal-footer">
            <button type="button" class="btn btn-default col-md-3 pull-left" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            <button type="submit" class="btn btn-primary col-md-3 pull-right"><?=$this->lang->line('application_update');?></button>
        </div>

   	  </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php } ?>

<?php if($has_order_value_refund){ ?>
<div class="modal fade" tabindex="-1" role="dialog" id="viewOrderValueRefund">
  <div class="modal-dialog" role="document">
    <div class="modal-content modal-lg">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php echo $this->lang->line('application_refunded_values');?></span></h4>
		</div>

        <div class="modal-body">

          <div class="row">
              <div class="col-md-12">
                  <table class="table table-bordered">
                      <thead>
                      <tr>
                          <th><?=$this->lang->line('application_date')?></th>
                          <th><?=$this->lang->line('application_user')?></th>
                          <th><?=$this->lang->line('application_comment')?></th>
                          <th><?=$this->lang->line('application_value')?></th>
                      </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($order_value_refund_on_gateways as $order_value_refund_on_gateway): ?>
                          <tr>
                              <td><?=datetimeBrazil($order_value_refund_on_gateway['created_at'])?></td>
                              <td><?=$order_value_refund_on_gateway['email_user']?></td>
                              <td><?=$order_value_refund_on_gateway['description']?></td>
                              <td><?=money($order_value_refund_on_gateway['value'])?></td>
                          </tr>
                      <?php endforeach; ?>

                      <?php if(isset($order_data['order_item'])): ?>
                        <?php $x = 1; ?>
                        <?php foreach ($order_data['order_item'] as $key => $val): ?>
                            <?php if($val['qty_canceled'] != 0): ?>
                              <tr>
                                  <td><?=datetimeBrazil($val['created_at_cancelled'])?></td>
                                  <td><?=$val['email_user_cancelled']?></td>
                                  <td><?=$this->lang->line('application_partial_cancellation')?> | SKU: <?=$val['sku']?> | <?=$this->lang->line('application_item_qty')?>: <?=$val['qty_canceled']?></td>
                                  <td><?=$val['total_amount_canceled_mkt'] ? money($val['total_amount_canceled_mkt']) : $this->lang->line('application_in_progress')?></td>
                              </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                      <?php endif; ?>
                      </tbody>
                  </table>
              </div>
          </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
        </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php } ?>

<?php if(in_array('updateTrackingOrder', $user_permission) && (in_array($order_data['order']['paid_status'], array(4,43,53)))): ?>
<div class="modal fade" tabindex="-1" role="dialog" id="postItemModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php
			$titulo = $this->lang->line('application_mark_as_posted');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:blue;"><?php echo $this->lang->line('application_mark_as_posted');?></span></h4>
			<br><?php echo $this->lang->line('application_mark_as_posted_warning');?>
		</div>
	    <form role="form" action="<?php echo base_url('orders/postItem') ?>" method="post" id="postItemForm">
	    <div class="modal-body">

		    <div class="row">
				<div class="form-group col-md-2"><label><?=$this->lang->line('application_post_date');?></label></div>
				<div class="form-group input-group date col-md-4" id='post_date_pick' name="post_date_pick">
					<input type='text' required class="form-control" id='post_date' name="post_date" autocomplete="off" value="<?php echo set_value('post_date');?>" />
	                <span class="input-group-addon">
	                    <span class="glyphicon glyphicon-calendar"></span>
	                </span>
	            </div>
			</div>

			<input type="hidden" id="id_order_post" name="id_order_post" value="<?php echo $order_data['order']['id'] ?>" autocomplete="off">

		</div> <!-- modal-body -->
	    <div class="modal-footer">
	   		<button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
	      	<button type="submit" class="btn btn-primary" id="confirmaPostItem" name="confirmaPostItem"><?=$this->lang->line('application_confirm');?></button>

	    </div>
   	</form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<div class="modal fade" tabindex="-1" role="dialog" id="nfXML">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" style="color:blue;"><?=$this->lang->line('application_import_nfxml');?><?php echo $order_data['order']['id'] ?> </h4>
      </div>
        <form role="form" action="<?php echo base_url('orders/postxml') ?>" method="post" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="company_id" value="<?= $order_data['order']['loja']['company_id'] ?>" />
        <input type="hidden" name="order" value="<?php echo $order_data['order']['id'] ?>" />
        <input type="file" name="fileNfXml" id="fileNfXml" />
      </div> <!-- modal-body -->
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
        <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
      </div>
        </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<?php if(in_array('updateTrackingOrder', $user_permission) && in_array($order_data['order']['paid_status'],[5,45,58])): ?>
<div class="modal fade" tabindex="-1" role="dialog" id="deliveryItemModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php
			$titulo = $this->lang->line('application_mark_as_delivered');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:blue;"><?php echo $this->lang->line('application_mark_as_delivered');?></span></h4>
			<br><?php echo $this->lang->line('application_mark_as_deliver_warning');?>
		</div>
	    <form role="form" action="<?php echo base_url('orders/deliveryItem') ?>" method="post" id="deliveryItemForm">
	    <div class="modal-body">

		    <div class="row">
				<div class="form-group col-md-2"><label><?=$this->lang->line('application_delivered_date');?></label></div>
				<div class="form-group input-group date col-md-4" id='delivery_date_pick' name="delivery_date_pick">
					<input type='text' required class="form-control" id='delivery_date' name="delivery_date" autocomplete="off" value="<?php echo set_value('delivery_date');?>" />
	                <span class="input-group-addon">
	                    <span class="glyphicon glyphicon-calendar"></span>
	                </span>
	            </div>
			</div>

			<input type="hidden" id="id_order_delivery" name="id_order_delivery" value="<?php echo $order_data['order']['id'] ?>" autocomplete="off">

		</div> <!-- modal-body -->
	    <div class="modal-footer">
	   		<button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
	      	<button type="submit" class="btn btn-primary" id="confirmaPostItem" name="confirmaPostItem"><?=$this->lang->line('application_confirm');?></button>

	    </div>
   	</form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>


<?php if(in_array('admDashboard', $user_permission)): ?>

<div class="modal fade" tabindex="-1" role="dialog" id="registerExchangeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php
			$titulo = $this->lang->line('application_exchange_register_original_order');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:red;"><?php echo $this->lang->line('application_exchange_register_original_order');?></span></h4>
		</div>
	    <form role="form" action="<?php echo base_url('orders/registerExchange') ?>" method="post" id="registerExchangeForm">
	    <div class="modal-body">

		    <div class="row">
				<div class="form-group col-md-2"><label><?=$this->lang->line('application_original_order');?></label></div>
				<div class="form-group col-md-9">
					<input type="text" required name="original_order" id="original_order" class="form-control" value="<?php echo (is_null($order_data['order']['original_order_marketplace']))? '' : $order_data['order']['original_order_marketplace']; ?>" autocomplete="off">
				</div>

			</div>

			<input type="hidden" id="id_register_exchange" name="id_register_exchange" value="<?php echo $order_data['order']['id'] ?>" autocomplete="off">

		</div> <!-- modal-body -->
	    <div class="modal-footer">
	   		<button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
	      	<button type="submit" class="btn btn-primary" id="confirmExchange" name="confirmExchange"><?=$this->lang->line('application_confirm');?></button>

	    </div>
   	</form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<?php if(in_array('admDashboard', $user_permission) && ($order_data['order']['paid_status']==6 || $order_data['order']['paid_status']==60)): ?>

<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" id="newExchangeModal">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
    	<?php
			$titulo = $this->lang->line('application_new_exchange');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:red;"><?php echo $this->lang->line('application_new_exchange');?></span></h4>
			<br><?php echo $this->lang->line('application_new_exchange_warning');?>
		</div>
	    <form role="form" id="newExchangeForm">
	    <div class="modal-body">

		    <!--<div class="row" id="input_to_troca">
				<div class="form-group col-md-2"><label><?=$this->lang->line('application_type_exchange');?></label></div>
				<div class="form-group col-md-9">
					<input type="text" required name="confirm_new_exchange" id="confirm_new_exchange" class="form-control" value="" autocomplete="off">
          <br>
          <span style="color:red;" id="type_exchange"><?=$this->lang->line('application_type_exchange')?></span>
				</div>
			</div>-->

			<table class="table table-striped table-bordered">
                  <thead>
                    <tr>
                      <th style="width:10%"><?=$this->lang->line('application_item');?></th>
                      <th style="width:15%"><?=$this->lang->line('application_sku');?></th>
                      <th style="width:40%"><?=$this->lang->line('application_product');?></th>
                      <th style="width:30%"><?=$this->lang->line('application_qty');?></th>
                      <th style="width:10%"><?=$this->lang->line('application_value');?></th>
                      <th style="width:15%"><?=$this->lang->line('application_stock');?></th>
                    </tr>
                  </thead>
				           <tbody id="body_itens">
                   </tbody>
             </table>
             	<br><span style="color:red;" id="disabled_Troca"><?=$this->lang->line('application_order_exchange_no_stock')?></span>

			<input type="hidden" id="id_new_exchange" name="id_new_exchange" value="<?php echo $order_data['order']['id'] ?>" autocomplete="off">

		</div> <!-- modal-body -->
	    <div class="modal-footer">
	   		<button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
	      	<button type="submit" class="btn btn-primary" id="confirmNewExchange" name="confirmNewExchange"><?=$this->lang->line('application_confirm');?></button>

	    </div>
   	</form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<?php if(in_array('deleteOrder', $user_permission)): ?>

<div class="modal fade" tabindex="-1" role="dialog" id="cancelReasonModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php
			$titulo = $this->lang->line('application_cancel_reason');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:red;"><?php echo $this->lang->line('application_cancel_reason');?></span></h4>
		</div>
	    <form role="form" action="<?php echo base_url('orders/cancelReason') ?>" method="post" id="cancelReasonForm">
	    <div class="modal-body">

			 <div class="row">
				<div class="form-group col-md-2"><label><?=$this->lang->line('application_cancel_reason');?></label></div>
				<div class="form-group col-md-9">
					<input type="text" required name="cancelReason" id="cancelReason" class="form-control" value="" autocomplete="off" list="datalist_reasons">
					<datalist id="datalist_reasons">
					<?php foreach ($cancel_reasons as $option) {?>

		               <option value="<?=$option['value']?>"</option>
		             <?php } ?>
					</datalist>
				</div>
			</div>

			<div class="row">
				<div class="form-group col-md-2"><label><?=$this->lang->line('application_penalty_to');?></label></div>
				<div class="form-group col-md-9">
                  <select class="form-control" id="penalty_to_reason" name="penalty_to_reason">
                  	<?php foreach ($cancel_penalty_to as $option) { ?>
                    <option value="<?=$option;?>" ><?=$option;?></option>
                 	<?php } ?>
                  </select>
                </div>
			</div>

			<input type="hidden" id="id_cancel" name="id_cancel" value="" >

			<input type="hidden" id="id_order_cancelReason" name="id_order_cancelReason" value="<?php echo $order_data['order']['id'] ?>" autocomplete="off">

		</div> <!-- modal-body -->
	    <div class="modal-footer">
	   		<button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
	      	<button type="submit" class="btn btn-primary" id="confirmacancelamento" name="confirmacancelamento"><?=$this->lang->line('application_confirm');?></button>

	    </div>
   	</form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<?php if(in_array('deleteOrder', $user_permission) && ($order_data['order']['paid_status']==101)) : ?>

<div class="modal fade" tabindex="-1" role="dialog" id="returnCorreiosModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    	<?php
			$titulo = $this->lang->line('application_try_again_correios');
		    ?>
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" style="color:red;"><?php echo $this->lang->line('application_try_again_correios');?></span></h4>
		</div>
	    <form role="form" action="<?php echo base_url('orders/returnCorreios') ?>" method="post" id="returnCorreiosForm">
	    <div class="modal-body">


			<input type="hidden" id="id_returnCorreios" name="id_returnCorreios" value="<?php echo $order_data['order']['id'] ?>" autocomplete="off">

		</div> <!-- modal-body -->
	    <div class="modal-footer">
	   		<button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
	      	<button type="submit" class="btn btn-primary" id="confirmaReturn" name="confirmaReturn"><?=$this->lang->line('application_confirm');?></button>

	    </div>
   	</form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php endif; ?>

<?php
// modal para mandar pedido para frete a contratar
if(in_array($order_data['order']['paid_status'], array(40,50,80)) && (in_array('admDashboard', $user_permission) || in_array('sendFreightToHire', $user_permission))) {?>
<div class="modal fade" tabindex="-1" role="dialog" id="sendFreightHire">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php
            $titulo = $this->lang->line('application_try_again_correios');
            ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_send_freight_hire');?></span></h4>
            </div>
            <form role="form" action="<?=base_url('orders/sendFreightHire') ?>" method="post" id="returnCorreiosForm">
                <div class="modal-body">
                    <h4 class="text-center"><?=$this->lang->line('messages_send_freight_hire');?></h4>
                    <input type="hidden" name="order_id" value="<?=$order_data['order']['id'] ?>" autocomplete="off">
                </div> <!-- modal-body -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-primary" id="confirmaReturn" name="confirmaReturn"><?=$this->lang->line('application_confirm');?></button>

                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php } ?>

<?php
// modal para mandar pedido para frete a contratar
if ($use_change_seller && ($order_data['order']['paid_status'] == 1 || $order_data['order']['paid_status'] == 3)) {?>
    <div class="modal fade" tabindex="-1" role="dialog" id="sendChangeSeller">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_not_fulfill_request');?></span></h4>
                </div>
                <form role="form" action="<?=base_url('orders/changeSellerOrder') ?>" method="post" id="returnChangeSellerForm">
                    <div class="modal-body">
                        <h4 class="text-center"><?=$this->lang->line('messages_not_fulfill_request');?></h4>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label><?=$this->lang->line('application_justification')?></label>
                                <textarea class="form-control" name="message" required></textarea>
                            </div>
                        </div>
                        <input type="hidden" name="order_id" value="<?=$order_data['order']['id'] ?>" autocomplete="off">
                    </div> <!-- modal-body -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                        <button type="submit" class="btn btn-primary" id="confirmaReturn" name="confirmaReturn"><?=$this->lang->line('application_confirm');?></button>
                    </div>
                </form>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
<?php } ?>

<?php
// modal para mandar pedido para frete a contratar
if (in_array('admDashboard', $user_permission)) {?>
    <div class="modal fade" tabindex="-1" role="dialog" id="incidence">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_incidence');?></span></h4>
                </div>
                <form role="form" action="<?=base_url('orders/updateOrderIncidence') ?>" method="post" id="updateOrderIncidence">
                    <div class="modal-body">
                        <h4 class="text-center"><?=$this->lang->line('messages_add_reason_incidence_order');?></h4>
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label class="d-flex justify-content-between"><?=$this->lang->line('application_incidence')?> <?=$order_data['order']['incidence_message'] ? '<span><input type="checkbox" name="cancelIncidence" value="1"/>'.$this->lang->line('application_remove_incidence').'</span>' : ''?></label>
                                <textarea class="form-control" name="incidence" required><?=$order_data['order']['incidence_message'] ?></textarea>
                            </div>
                        </div>
                        <input type="hidden" name="order_id" value="<?=$order_data['order']['id'] ?>" autocomplete="off">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                        <button type="submit" class="btn btn-primary" id="confirmaReturn" name="confirmaReturn"><?=$this->lang->line('application_update');?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<?php
// modal para mandar pedido para reenvio
if (
        // ($order_data['order']['paid_status'] == 59 && !$order_data['order']['in_resend_active']) ||
        (in_array($order_data['order']['paid_status'], array(5,45,58,59)) && in_array('updateTrackingOrder', $user_permission))
    ) {?>
    <div class="modal fade" tabindex="-1" role="dialog" id="modalResend">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_request_for_resend')?></span></h4>
                </div>
                <form role="form" action="<?=base_url('orders/updateOrderResend') ?>" method="post">
                    <div class="modal-body text-center">
                        <?php if ($order_data['order']['paid_status'] == 5) {?>

                            <h3 class="mb-5"><?=$this->lang->line('messages_request_orders_delivered')?></h3>
                            <p><?=$this->lang->line('messages_orders_not_delivered_reason')?></p>
                            <p><?=$this->lang->line('messages_last_status_order_transport')?><strong><?=$order_data['order']['last_occurrence']?></strong> </p>

                        <?php } else { ?>

                            <h3 class="mb-5"><?=$this->lang->line('messages_orders_not_delivered')?></h3>
                            <p><?=$this->lang->line('messages_orders_not_delivered_necessary_resend')?></p>
                            <p><?=$this->lang->line('messages_orders_not_delivered_reason_manual')?></p>
                            <p><?=$this->lang->line('messages_last_status_order_transport')?><strong><?=$order_data['order']['last_occurrence']?></strong> </p>

                        <?php } ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                        <?=in_array('updateTrackingOrder', $user_permission) ?
                            (
                                $order_data['order']['paid_status'] == 5 ?
                                    '<button type="submit" class="btn btn-primary pull-right">'.$this->lang->line('application_request_new_delivery').'</button>' :
                                    '<button type="submit" class="btn btn-primary pull-right">'.$this->lang->line('application_authorize_new_delivery').'</button>'
                            ) :
                            '<a href="https://'.$site_agidesk.'.agidesk.com/br/servicos/secoes/pedidos?access_token='.$token_agidesk.'" target="_blank" class="btn btn-primary pull-right">'.$this->lang->line('application_contact_SAC').'</a>'?>
                    </div>
                    <input type="hidden" name="order_id" value="<?=$order_data['order']['id'] ?>" autocomplete="off">
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<?php
// modal para mandar pedido para frete a contratar
if (in_array('createRequestCancelOrder', $user_permission) && $order_data['order']['product_return_status'] == 0) {?>
    <div class="modal fade" tabindex="-1" role="dialog" id="modalRequestCancel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_request_cancel')?></span></h4>
                </div>
                <form role="form" action="<?=base_url('orders/updateOrderRequestCancel') ?>" method="post" id="updateOrderRequestCancel">
                    <div class="modal-body">
                        <div class="row">
                            <?php foreach ($cancel_reason as $key => $reason) { ?>
                                <div class="col-md-12">
                                    <label><input type="radio" name="reason_cancel_request" value="<?=$reason?>" class="optRequestrcancel" required> <?=$reason?></label>
                                </div>
                            <?php } ?>
                        </div>
                        <input type="hidden" name="order_id" value="<?=$order_data['order']['id'] ?>" autocomplete="off">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                        <button type="submit" class="btn btn-danger" id="confirmaReturn" name="confirmaReturn"><?=$this->lang->line('application_request_cancel');?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<?php
// modal para mandar pedido para frete a contratar
if ($order_data['order']['paid_status'] == 90 && in_array('deleteRequestCancelOrder', $user_permission) && in_array('admDashboard', $user_permission)) {?>
    <div class="modal fade" tabindex="-1" role="dialog" id="modalCancelRequestCancel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_cancel_request_cancel')?></span></h4>
                </div>
                <form role="form" action="<?=base_url('orders/updateOrderCancelRequestCancel') ?>" method="post" id="updateOrderCancelRequestCancel">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <label><?=$this->lang->line('application_cancel_reason')?></label>
                                <textarea name="reason_cancel_request" class="form-control" required></textarea>
                            </div>
                        </div>
                        <input type="hidden" name="order_id" value="<?=$order_data['order']['id'] ?>" autocomplete="off">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                        <button type="submit" class="btn btn-success" id="confirmaReturnCancel" name="confirmaReturnCancel"><?=$this->lang->line('application_cancel_request_cancel');?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<?php
// modal para ver fila de integração do pedido
if(in_array('viewQueueOrderIntegration', $user_permission)){ ?>
    <div class="modal fade" tabindex="-1" role="dialog" id="viewQueueOrder">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?=$this->lang->line('application_view_queue_orders')?></span></h4>
                </div>
                <form role="form" action="<?=base_url('orders/addOrderQueueIntegration') ?>" method="post" id="addOrderQueueIntegration">
                    <div class="modal-body pt-0">
                        <div class="row">
                            <div class="form-group">
                                <?php if (count($queue_order_integration)) { ?>
                                <ul class="timeline-queue">
                                    <?php foreach($queue_order_integration as $order_integration) { ?>
                                    <li class="event" data-date="<?=date('d/m/Y H:i', strtotime($order_integration['updated_at']))?>">
                                        <h3><?=$this->lang->line("application_order_{$order_integration['paid_status']}")?></h3>
                                    </li>
                                    <?php } ?>
                                </ul>
                                <?php } else { ?>
                                    <h3 class="text-center"><?=$this->lang->line('messages_no_exist_status_in_integration_queue')?></h3>
                                <?php } ?>
                            </div>
                        </div>

                        <?php if(in_array('createQueueOrderIntegration', $user_permission)){ ?>
                        <div class="row">
                            <div class="form-group col-md-12 text-center">
                                <button type="submit" class="btn btn-primary" id="addOrderQueue" order-id="<?=$order_data['order']['id']?>">
                                    Adicionar Status <b><?=$this->lang->line("application_order_{$order_data['order']['paid_status']}")?></b> novamente na fila
                                </button>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php } ?>

<!-- Modal com resumo do desconto :: inicio -->
<div class="modal fade in" tabindex="-1" role="dialog" id="modalResumoDesconto">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Detalhamento descontos</span></h4>
            </div>

            <div class="modal-body pt-0">
                <div class="row">

                    <div class="col-sm-12">
                        <div class="panel-group">
                          <div class="panel-group detalhesDescontosBody" id="panels">

                          </div>

                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>

        </div>
    </div>
</div>
<!-- Modal com resumo do desconto :: fim -->

<!-- Modal com resumo de taxa :: inicio -->
<div class="modal fade" tabindex="-1" role="dialog" id="modalResumoTaxas">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Detalhamento de Taxas (Comissão de Produto + Frete)</span></h4>
            </div>

            <div class="modal-body pt-0">
                <div class="row">

                    <div class="col-sm-12">
                        <div class="panel-group">
                          <div class="panel-group detalhesTaxaBody" id="panels">

                          </div>

                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>

        </div>
    </div>
</div>
<!-- Modal com resumo de taxa :: fim -->

<!-- Modal com detalhamento do frete :: inicio -->
<div class="modal fade" tabindex="-1" role="dialog" id="modalFreteDetalhado">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Detalhamento de Frete</span></h4>
      </div>

      <div class="modal-body pt-0">
        <div class="row">
          <div class="col-sm-12">
            <div class="panel-group">
              <div class="panel-group detalhesFreteBody" id="panels"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
      </div>
    </div>
  </div>
</div>
<!-- Modal com detalhamento do frete :: fim -->

<?php
// modal para mediação de pedido
if(in_array('orderMediation', $user_permission)){ ?>
    <?php if ($orderMediation == 0): ?>
        <div class="modal fade" tabindex="-1" role="dialog" id="mediation">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?= $this->lang->line('application_order_mediation_title_create')?></span></h4>
                    </div>
                    <form role="form" action="<?=base_url('orders/addMediation/' . $order_data['order']['id']) ?>" method="post" id="addMediationOrder">
                        <div class="modal-body pt-0">
                            <div class="row">
                                <h4 class="text-center"><?=$this->lang->line('application_order_mediation_question_create')?></h4>
                                <input type="hidden" id="orderStatus" name="orderStatus" value="<?=$order_data['order']['paid_status']?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                            <button type="submit" class="btn btn-success" id="btnMediationCreate"><?=$this->lang->line('application_yes');?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php elseif($orderMediationResolved == 0): ?>
        <div class="modal fade" tabindex="-1" role="dialog" id="mediation">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?=$this->lang->line('application_order_mediation_title_resolve')?></span></h4>
                    </div>
                    <form role="form" action="<?=base_url('orders/resolveMediation/' . $order_data['order']['id']) ?>" method="post" id="addMediationOrder">
                        <div class="modal-body pt-0">
                            <div class="row">
                                <h4 class="text-center"><?=$this->lang->line('application_order_mediation_question_resolve')?></h4>
                                <input type="hidden" id="orderStatus" name="orderStatus" value="<?=$order_data['order']['paid_status']?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                            <button type="submit" class="btn btn-success" id="btnMediationResolve"><?=$this->lang->line('application_yes');?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php } ?>

<?php if (in_array('createReturnOrder', $user_permission) && $order_data['order']['paid_status'] == 6): ?>

  <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" id="newReturnOrderModal">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
    	  <?php
			    $titulo = $this->lang->line('application_new_return');
		    ?>

		    <div class="modal-header">
			    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			    <h4 class="modal-title" style="color: red;"><?php echo $this->lang->line('application_new_return');?></span></h4><br>
          <?php echo $this->lang->line('application_new_return_warning');?>
		    </div>

  	    <form role="form" id="newReturnOrderForm">
	        <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-striped table-bordered">
                          <thead>
                            <tr>
                              <th style="width: 10%"><?=$this->lang->line('application_item');?></th>
                              <th style="width: 15%"><?=$this->lang->line('application_sku');?></th>
                              <th style="width: 40%"><?=$this->lang->line('application_product');?></th>
                              <th style="width: 30%"><?=$this->lang->line('application_qty');?></th>
                              <th style="width: 10%"><?=$this->lang->line('application_value');?></th>
                            </tr>
                          </thead>
                          <tbody id="return_body_items"></tbody>
                        </table>
                    </div>
                </div>
                <?php if ($has_order_value_refund): ?>
                <div class="row">
                    <div class="form-group col-md-4 pull-right">
                        <?php
                        $total_refund = array_sum(
                            array_map(function ($item) {
                                return (float)$item['value'];
                            }, $order_value_refund_on_gateways)
                        );
                        $total_return = array_sum(
                            array_map(function ($item) {
                                return (float)($item['shipping_value_returned'] ?? 0) + (float)($item['product_value_returned'] ?? 0);
                            }, $product_return)
                        );

                        $total_return_value = 0;
                        if (isset($order_data['order_item'])) {
                            foreach ($order_data['order_item'] as $key => $val) {
                                if ($val['qty_canceled'] != 0 && !empty($val['total_amount_canceled_mkt'])) {
                                    $total_return_value += $val['total_amount_canceled_mkt'];
                                }
                            }
                        }

                        $max_to_refund = ($order_data['order']['total_order'] + $order_data['order']['total_ship']) - ($total_refund + $total_return + $total_return_value);
                        ?>
                        <label for="refund_on_gateway_value_to_return"><?=$this->lang->line('application_enter_refund_value')?></label>
                        <input type="text" name="refund_on_gateway_value_to_return" id="refund_on_gateway_value_to_return" class="form-control" value="<?=set_value('refund_on_gateway_value')?>" autocomplete="off" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('MOEDA',this,event);checkMaxValue(this, <?=$max_to_refund?>)" required>
                        <small>
                            <strong><?=$this->lang->line('application_max_value_to_refund')?>: </strong>
                            <span><?=money($max_to_refund, '')?></span>
                        </small>
                    </div>
                </div>
                <?php endif; ?>
		      </div> <!-- modal-body -->

          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            <button type="submit" class="btn btn-primary" id="confirmNewReturn" name="confirmNewReturn"><?=$this->lang->line('application_confirm');?></button>
          </div>
   	    </form>

      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
  </div><!-- /.modal -->

<?php endif; ?>
<?php if (!$has_order_value_refund && in_array($order_data['order']['paid_status'], array(1,2,3)) && in_array('partialCancellationOrder', $user_permission) && empty($order_value_refund_on_gateways)): ?>

  <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" id="modalPartialCancellationOrder">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
    	  <?php
			    $titulo = $this->lang->line('application_make_partial_cancellation');
		    ?>

		    <div class="modal-header">
			    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			    <h4 class="modal-title" style="color: red;"><?php echo $this->lang->line('application_partial_cancellation');?></span></h4><br>
                <?php echo $this->lang->line('application_partial_cancellation_warning');?>
		    </div>

  	    <form role="form" id="confirmPartialCancellationForm" action="<?=base_url('orders/saveItemsToPartialCancellation/' . $order_data['order']['id']) ?>">
	        <div class="modal-body">
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th style="width: 10%"><?=$this->lang->line('application_item');?></th>
                  <th style="width: 10%"><?=$this->lang->line('application_sku');?></th>
                  <th style="width: 40%"><?=$this->lang->line('application_product');?></th>
                  <th style="width: 20%"><?=$this->lang->line('application_quantity_in_order');?></th>
                  <th style="width: 20%"><?=$this->lang->line('application_qty');?></th>
                </tr>
              </thead>

              <tbody id="partial_cancellation_body_items"></tbody>
            </table>
		      </div> <!-- modal-body -->

          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            <button type="submit" class="btn btn-primary" id="confirmPartialCancellation" name="confirmPartialCancellation"><?=$this->lang->line('application_confirm');?></button>
          </div>
   	    </form>

      </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
  </div><!-- /.modal -->

<?php endif; ?>
<?php if (
        $days_to_refund_value_tuna &&
        in_array('refundOrderValue', $user_permission) &&
        !in_array($order_data['order']['paid_status'], array(1,2,95,96,97,98,99)) &&
        strtotime(addDaysToDate($order_data['order']['date_time'], $days_to_refund_value_tuna)) > strtotime(dateNow()->format(DATETIME_INTERNATIONAL))
    ): ?>
    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" id="modalRefundOrderValue">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?php echo $this->lang->line('application_refunds_order');?></span></h4><br>
                </div>

                <form role="form" id="refundOrderValueToGateway" action="<?=base_url('orders/refundOrderValueToGateway/' . $order_data['order']['id']) ?>" method="POST">
                    <div class="modal-body">
                        <?php
                        $total_refund = array_sum(
                            array_map(function ($item) {
                                return (float)$item['value'];
                            }, $order_value_refund_on_gateways)
                        );
                        $total_return = array_sum(
                            array_map(function ($item) {
                                return (float)($item['shipping_value_returned'] ?? 0) + (float)($item['product_value_returned'] ?? 0);
                            }, $product_return)
                        );

                        $total_return_value = 0;
                        if (isset($order_data['order_item'])) {
                            foreach ($order_data['order_item'] as $key => $val) {
                                if ($val['qty_canceled'] != 0 && !empty($val['total_amount_canceled_mkt'])) {
                                    $total_return_value += $val['total_amount_canceled_mkt'];
                                }
                            }
                        }

                        $max_to_refund = ($order_data['order']['total_order'] + $order_data['order']['total_ship']) - ($total_refund + $total_return + $total_return_value);
                        ?>
                        <?php if ($max_to_refund != 0): ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info alert-dismissible" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    É possível realizar devoluções até: <?=dateFormat(addDaysToDate($order_data['order']['date_time'], $days_to_refund_value_tuna), DATE_BRAZIL)?>
                                </div>
                            </div>
                        </div>
                        <?php endif ?>
                        <div class="row">
                            <?php if ($max_to_refund == 0): ?>
                                <div class="form-group col-md-12">
                                    <div class="alert alert-warning alert-dismissible" role="alert">
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        Não é possível mais devolver valores, já foi devolvido o valor total do pedido.
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="form-group col-md-4">
                                    <label for="refund_on_gateway_value"><?=$this->lang->line('application_enter_refund_value')?></label>
                                    <input type="text" name="refund_on_gateway_value" id="refund_on_gateway_value" class="form-control" value="<?=set_value('refund_on_gateway_value')?>" autocomplete="off" onblur="checkValue(this)" onKeyPress="return digitos(event, this);" onKeyUp="Mascara('MOEDA',this,event);checkMaxValue(this, <?=$max_to_refund?>)" required>
                                    <small>
                                        <strong><?=$this->lang->line('application_max_value_to_refund')?>: </strong>
                                        <span><?=money($max_to_refund, '')?></span>
                                    </small>
                                </div>
                                <div class="form-group col-md-8">
                                    <label for="refund_on_gateway_description"><?=$this->lang->line('application_description')?></label>
                                    <input type="text" class="form-control" name="refund_on_gateway_description" id="refund_on_gateway_description" value="<?=set_value('refund_on_gateway_description')?>" required>
                                </div>
                            <?php endif ?>
                        </div>
                        <?php if (!empty($has_order_value_refund)): ?>
                        <h3>Valores já devolvidos</h3>
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th><?=$this->lang->line('application_date')?></th>
                                    <th><?=$this->lang->line('application_user');?></th>
                                    <th><?=$this->lang->line('application_description');?></th>
                                    <th><?=$this->lang->line('application_value');?></th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($order_value_refund_on_gateways as $order_value_refund_on_gateway): ?>
                                <tr>
                                    <td><?=datetimeBrazil($order_value_refund_on_gateway['created_at'])?></td>
                                    <td><?=$order_value_refund_on_gateway['email_user']?></td>
                                    <td><?=$order_value_refund_on_gateway['description']?></td>
                                    <td><?=money($order_value_refund_on_gateway['value'])?></td>
                                </tr>
                                <?php endforeach; ?>

                                <?php if(isset($order_data['order_item'])): ?>
                                    <?php $x = 1; ?>
                                    <?php foreach ($order_data['order_item'] as $key => $val): ?>
                                        <?php if($val['qty_canceled'] != 0): ?>
                                            <tr>
                                                <td><?=datetimeBrazil($val['created_at_cancelled'])?></td>
                                                <td><?=$val['email_user_cancelled']?></td>
                                                <td><?=$this->lang->line('application_partial_cancellation')?> | SKU: <?=$val['sku']?> | <?=$this->lang->line('application_item_qty')?>: <?=$val['qty_canceled']?></td>
                                                <td><?=$val['total_amount_canceled_mkt'] ? money($val['total_amount_canceled_mkt']) : $this->lang->line('application_in_progress')?></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                        <button type="submit" class="btn btn-primary" id="btnRefundOrderValueToGateway" name="btnRefundOrderValueToGateway"><?=$this->lang->line('application_confirm');?></button>
                    </div>
                </form>
            </div>
        </div>
    </div><

<?php endif; ?>

<?php if (in_array('transactionInteractionsOrder', $user_permission)): ?>
<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" id="modalInteractionsPayment">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><span class="icon"></span> <?=$this->lang->line('application_marketplace_payment_history');?></h4>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php if ($has_notification_popup) { ?>
<div class="modal fade" tabindex="-1" role="dialog" id="notificationPopupModal">
  <div class="modal-dialog" role="document">
    <img class="img-responsive" src="<?=$url_notification_popup;?>" alt="Card image">
  </div>
</div>

<script type="text/javascript">
  $('#notificationPopupModal').modal('show');
</script>
<?php } ?>

<link rel="stylesheet" href="<?=base_url('assets/dist/css/views/orders/edit.css')?>"/>
<style>
    div[aria-controls*="collapse_interaction_payment_"] {
        background: #ddd;
        margin: 6px 0;
        display: flex;
        align-items: center;
        padding: 2px 5px;
        border-radius: 5px;
        cursor: pointer;
        border: 1px solid;
    }

    div[aria-controls*="collapse_interaction_payment_"][aria-expanded=true] {
        border-bottom: 0;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 0;
    }

    div[aria-controls*="collapse_interaction_payment_"][aria-expanded=true] {
        border-bottom: 0;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 0;
    }

    div[id*="collapse_interaction_payment_"] {
        border: 1px solid;
        padding: 10px 10px 10px 10px;
        border-radius: 5px;
        margin-top: -7px;
        border-top-right-radius: 0;
        border-top-left-radius: 0;
        border-top: 0;
    }
</style>
<script type="text/javascript">

  var msg_impcancel = " <?=$this->lang->line('application_unable_to_cancel_order')?>";
  var msg_impcancel_explain = " <?=$this->lang->line('messages_unable_to_cancel_order')?>";

  var disable_trade=false;
  var base_url = "<?php echo base_url(); ?>";
  var isAdm = "<?=in_array('admDashboard', $user_permission) ? 1 : 0?>"
  var order=<?=json_encode($order_data)?>;
  var total_total=order.order_item.reduce((acumulador, element)=>{
    return acumulador+parseInt(element.qty);
  },0)
  var total=0;
  function plus(element_name,limit,stock){
    var qtd=parseInt($('#'+element_name).text());
    if(qtd<limit){
      qtd += 1;
      $('#'+element_name).text(qtd);
      total++;
      checkIfHasStock();
      checkTotalSelected();
    }
  }
  function minus(element_name,limit,stock){
    var qtd=parseInt($('#'+element_name).text());
    if(qtd>limit){
      qtd -= 1;
      $('#'+element_name).text(qtd);
      total--;
      checkIfHasStock();
      checkTotalSelected();
    }
  }

  function checkTotalSelected()
  {
    let total_selected = 0;
    order.order_item.forEach((element, index) => {
      let ret_field_name = `ret_item_${element.id}_${index}`;
      let ret_total = parseInt($(`#${ret_field_name}`).text());
      total_selected += ret_total;
    });

    if (total_selected == 0) {
      $('#confirmNewReturn').hide();
    } else if (total_selected > 0) {
      $('#confirmNewReturn').show();
    }
  }

  function checkIfHasStock(){
    if(order!=null){
      if(order.order_item){
        $('#type_exchange').hide();
        $('#type_return').hide();
        disable_trade=false;
        total=order.order_item.reduce((acumulador, element)=>{
          return acumulador+parseInt(element.qty);
        },0)
        order.order_item.forEach((element,index) => {
          var fild_name='item_'+element.id+'_'+index;
          var qtd=parseInt($('#'+fild_name).text());
          if(qtd>element.stock){
            disable_trade=true;
          }else{
          }
        });
        if(disable_trade){
          $('#disabled_Troca').show();
          $('#confirmNewExchange').hide();
          $('#input_to_troca').hide();
        }else{
          $('#disabled_Troca').hide();
          $('#confirmNewExchange').show();
          $('#input_to_troca').show();
          // $('.change-qtd').show();
        }
      }
    }
  }
  $(document).ready(function() {
      $(".format_json").each(function(){
          $(this).text(JSON.stringify(JSON.parse($(this).html()), undefined, 2));
      });

      if(order!=null){
      if(order.order_item){
        $('#type_exchange').hide();
        $('#type_return').hide();
        $('#confirmNewReturn').hide();

        disable_trade=false;
        total=order.order_item.reduce((acumulador, element)=>{
          return acumulador+parseInt(element.qty);
        },0);
        let order_item_qty = 0;
        order.order_item.forEach((element,index) => {

          if (element.qty == element.qty_canceled) {
              return;
          }

          var string='<tr>';
          string+='<td>'+(index+1)+'</td>';
          string+='<td>'+element.sku+'</td>';
          string+='<td>'+element.name+'</td>';

          order_item_qty = element.qty - element.qty_canceled;

          if(total==1){
            string+='<td>'+order_item_qty+'</td>';
          }else{
            var fild_name='item_'+element.id+'_'+index;

            string+='<td>'+
            '<button type="button" class="btn btn-link change-qtd-'+fild_name+'"><i class="fa fa-minus-square" aria-hidden="true" onclick="minus(\''+fild_name+'\',0,'+element.stock+')"></i></button>'+
            '<span id="'+fild_name+'">'+
            order_item_qty+
            '</span>'+
            '<button type="button" class="btn btn-link change-qtd-'+fild_name+'"><i class="fa fa-plus-square" aria-hidden="true" onclick="plus(\''+fild_name+'\','+order_item_qty+','+element.stock+')"></i></button>'+
            '</td>';
          }

          var return_order_string='<tr>';
          return_order_string += '<td>' + (index + 1) + '</td>';
          return_order_string += '<td>' + element.sku + '</td>';
          return_order_string += '<td>' + element.name + '</td>';

          if (total >= 1) {
            let return_order_id = 'ret_item_' + element.id + '_' + index;
            return_order_string += '<td>'+
              '<button type="button" class="btn btn-link change-qtd-' + return_order_id + '"><i class="fa fa-minus-square" aria-hidden="true" onclick="minus(\'' + return_order_id + '\',0,' + element.stock + ')"></i></button>'+
              '<span id="' + return_order_id + '">0</span>' +
              '<button type="button" class="btn btn-link change-qtd-' + return_order_id + '"><i class="fa fa-plus-square" aria-hidden="true" onclick="plus(\'' + return_order_id + '\',' + order_item_qty + ',' + element.stock + ')"></i></button>' +
            '</td>';
          }
          return_order_string += '<td>' + element.amount + '</td>';

          string+='<td>'+element.amount+'</td>';

          if(element.stock<=0){
            disable_trade=true;
          }
          string+='<td>'+(!element.stock?'0':element.stock)+'</td>'
          string+='</tr>'
          $('#body_itens').append(string);

          $('#return_body_items').append(return_order_string);
        });
        checkIfHasStock();
      }
    }
   $('#newExchangeForm').submit((event )=>{
      event.preventDefault();

      /*if($('#confirm_new_exchange').val()!='TROCA'){
        $('#type_exchange').show();
        // alert('Digite o valor correto para criar o pedido de troca.');
        return;
      }else{
        $('#type_exchange').hide();
      }*/
      if(order!=null){
        if (order.order_item) {
          var total=order.order_item.reduce((acumulador, element)=>{
              return acumulador+parseInt(element.qty);
          },0)
          if(total>1){
            order.order_item.forEach((element,index) => {
              var fild_name='item_'+element.id+'_'+index;
              var qtd=parseInt($('#'+fild_name).text());
              if(qtd!=parseInt(element.qty) && qtd!=0){
                order.order_item[index].qty=qtd;
                order.order_item[index].new=true;
              }else if(qtd==0){
                delete order.order_item[index];
              }
            });
          }
        }
      }
      var total=order.order_item.reduce((acumulador, element)=>{
        return acumulador+parseInt(element.qty);
      },0);
      console.log(total);
      console.log(total_total);
      if(total>0){
        var data=[]
        order.order_item.forEach(element => {
          data.push({
            sku:element.sku,
            id:element.id,
            qty:element.qty
          });
        });
        console.log(JSON.stringify({
            confirm_new_exchange:true,
            itens:data,
            total_total,
            total,
            aa:total_total==total,
          }));
          // return;
        $.ajax({
          url: base_url + 'orders/newExchange/'+order.order.id,
          type: 'post',
          dataType: 'json',
          data:{
            confirm_new_exchange:true,
            id:order.order.id,
            itens:data,
            all:total_total==total,
          },
          success:function(response) {
            // console.log(response);
            $('#newExchangeModal').hide();
            location.reload();
          }
        });
      }else{
      }
   });

    $(".select_group").select2();
    // $("#description").wysihtml5();

    $("#mainOrdersNav").addClass('active');
    $("#manageOrdersNav").addClass('active');

    $('#post_date_pick').datetimepicker({
		format: "DD/MM/YYYY HH:mm:ss",
		maxDate: new Date(+new Date()),
		showTodayButton: true,
		showClear: true,
	});
    $('#delivery_date_pick').datetimepicker({
		format: "DD/MM/YYYY HH:mm:ss",
		maxDate: new Date(+new Date()),
		showTodayButton: true,
		showClear: true,
	});
    // Add new row in the table
    $("#add_row").unbind('click').bind('click', function() {
      var table = $("#product_info_table");
      var count_table_tbody_tr = $("#product_info_table tbody tr").length;
      var row_id = count_table_tbody_tr + 1;

      $.ajax({
          url: base_url + '/orders/getTableProductRow/',
          type: 'post',
          dataType: 'json',
          success:function(response) {


               var html = '<tr id="row_'+row_id+'">'+
                   '<td>'+
                    '<select class="form-control select_group product" data-row-id="'+row_id+'" id="product_'+row_id+'" name="product[]" style="width:100%;" onchange="getProductData('+row_id+')">'+
                        '<option value=""></option>';
                        $.each(response, function(index, value) {
                          html += '<option value="'+value.id+'">'+value.name+'</option>';
                        });

                      html += '</select>'+
                    '</td>'+
                    '<td><input type="number" name="qty[]" id="qty_'+row_id+'" class="form-control" onkeyup="getTotal('+row_id+')"></td>'+
                    '<td><input type="text" name="rate[]" id="rate_'+row_id+'" class="form-control" disabled><input type="hidden" name="rate_value[]" id="rate_value_'+row_id+'" class="form-control"></td>'+
                    '<td><input type="text" name="amount[]" id="amount_'+row_id+'" class="form-control" disabled><input type="hidden" name="amount_value[]" id="amount_value_'+row_id+'" class="form-control"></td>'+
                    '<td><button type="button" class="btn btn-default" onclick="removeRow(\''+row_id+'\')"><i class="fa fa-close"></i></button></td>'+
                    '</tr>';

                if(count_table_tbody_tr >= 1) {
                $("#product_info_table tbody tr:last").after(html);
              }
              else {
                $("#product_info_table tbody").html(html);
              }

              $(".product").select2();

          }
        });

      return false;
    });

    const statusOrder = parseInt($('#status_code').attr('status-code'));
    if (statusOrder === 59) $('#modalResend').modal();

    if (!$('.btns-action-order button').length) $('.btns-action-order').closest('.box').hide();

    $("#CancelComissionChargesForm").submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        $("#confirmCancelCommissionCharges").prop('disabled', true);

        if( $("#txt_commission_charges_descricao").val() 	== "" ){
            alert("O campo Observação é de preenchimento obrigatório");
            $("#confirmCancelCommissionCharges").prop('disabled', false);
            return false;
        }

        $.ajax({
            url: base_url.concat("orders/cancelcommissioncharges"),
            type: 'POST',
            data: formData,
            success: function (data) {

              var retorno = JSON.parse(data);

              if(retorno.ret == "sucesso"){

                $('#overlay-cancel-commission-charges-order').hide();
                  $("#order_messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                    '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+retorno.msg+
                  '</div>');

                  // hide the modal
                  $("#CancelComissionChargesModal").modal('hide');
                  // reset the form
                  $('#CancelComissionChargesForm').trigger("reset");
                  //alert(response.messages);

                  // window.location.reload(false);
                  Swal.fire({
                    icon: 'success',
                    title: retorno.msg,
                    showCancelButton: false,
                    confirmButtonText: "Ok",
                  }).then((result) => {
                    //window.location.href = isAdm == 1 ? base_url + 'orders/cancelaMkt' : '';
                              location.reload();
                  });


              }else{
                $("#confirmCancelCommissionCharges").prop('disabled', false);
                // hide the modal
                $("#CancelComissionChargesModal").modal('hide');
                  // reset the form
                $('#CancelComissionChargesForm').trigger("reset");

                $("#order_messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                    '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+retorno.msg+
                  '</div>');
              }


            },
            cache: false,
            contentType: false,
            processData: false
        });


    });

    loadItemsToPartialCancellation();
  }); // /document

  function getTotal(row = null) {
    if(row) {
      var total = Number($("#rate_value_"+row).val()) * Number($("#qty_"+row).val());

      console.log($("#rate_value_"+row).val());
      total = total.toFixed(2);
  //    $("#amount_"+row).val(total);
      $("#amount_value_"+row).val(total);

      subAmount();

    } else {
      alert('no row !! please refresh the page');
    }
  }

  // get the product information from the server
  function getProductData(row_id)
  {
    var product_id = $("#product_"+row_id).val();
    if(product_id == "") {
      $("#rate_"+row_id).val("");
      $("#rate_value_"+row_id).val("");

      $("#qty_"+row_id).val("");

      $("#amount_"+row_id).val("");
      $("#amount_value_"+row_id).val("");

    } else {
      $.ajax({
        url: base_url + 'orders/getProductValueById',
        type: 'post',
        data: {product_id : product_id},
        dataType: 'json',
        success:function(response) {
          // setting the rate value into the rate input field

          $("#rate_"+row_id).val(response.price);
          $("#rate_value_"+row_id).val(response.price);

          $("#qty_"+row_id).val(1);
          $("#qty_value_"+row_id).val(1);

          var total = Number(response.price) * 1;
          total = total.toFixed(2);
          $("#amount_"+row_id).val(total);
          $("#amount_value_"+row_id).val(total);

          subAmount();
        } // /success
      }); // /ajax function to fetch the product data
    }
  }

  // calculate the total amount of the order
  function subAmount() {
    var service_charge = <?php echo ($company_data['service_charge_value'] > 0) ? $company_data['service_charge_value']:0; ?>;
    var vat_charge = <?php echo ($company_data['vat_charge_value'] > 0) ? $company_data['vat_charge_value']:0; ?>;

    var tableProductLength = $("#product_info_table tbody tr").length;
    var totalSubAmount = 0;
    for(x = 0; x < tableProductLength; x++) {
      var tr = $("#product_info_table tbody tr")[x];
      var count = $(tr).attr('id');
      count = count.substring(4);

      totalSubAmount = Number(totalSubAmount) + Number($("#amount_"+count).val());
    } // /for

    totalSubAmount = totalSubAmount.toFixed(2);

    // sub total
    $("#gross_amount").val(totalSubAmount);
    $("#gross_amount_value").val(totalSubAmount);

    // vat
    var vat = (Number($("#gross_amount").val())/100) * vat_charge;
    vat = vat.toFixed(2);
    $("#vat_charge").val(vat);
    $("#vat_charge_value").val(vat);

    // service
    var service = (Number($("#gross_amount").val())/100) * service_charge;
    service = service.toFixed(2);
    $("#service_charge").val(service);
    $("#service_charge_value").val(service);

    // total amount
    var totalAmount = (Number(totalSubAmount) + Number(vat) + Number(service));
    totalAmount = totalAmount.toFixed(2);
    // $("#net_amount").val(totalAmount);
    // $("#totalAmountValue").val(totalAmount);

    var discount = $("#discount").val();
    if(discount) {
      var grandTotal = Number(totalAmount) - Number(discount);
      grandTotal = grandTotal.toFixed(2);
      $("#net_amount").val(grandTotal);
      $("#net_amount_value").val(grandTotal);
    } else {
      $("#net_amount").val(totalAmount);
      $("#net_amount_value").val(totalAmount);

    } // /else discount

    var paid_amount = Number($("#paid_amount").val());
    if(paid_amount) {
      var net_amount_value = Number($("#net_amount_value").val());
      var remaning = net_amount_value - paid_amount;
      $("#remaining").val(remaning.toFixed(2));
      $("#remaining_value").val(remaning.toFixed(2));
    }

  } // /sub total amount

  function paidAmount() {
    var grandTotal = $("#net_amount_value").val();

    if(grandTotal) {
      var dueAmount = Number($("#net_amount_value").val()) - Number($("#paid_amount").val());
      dueAmount = dueAmount.toFixed(2);
      $("#remaining").val(dueAmount);
      $("#remaining_value").val(dueAmount);
    } // /if
  } // /paid amoutn function

  function removeRow(tr_id)
  {
    $("#product_info_table tbody tr#row_"+tr_id).remove();
    subAmount();
  }

  function editNfe()
  {

      // submit the edit from
      $("#nfeForm").unbind('submit').bind('submit', function() {
        var form = $(this);
        form.find('button[type="submit"]').attr('disabled', true);

        // remove the text-danger
        $(".text-danger").remove();
        $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize(), // /converting the form data into array and sending it to server
          dataType: 'json',
          success:function(response) {
            if(response.success === true) {
              $("#order_messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
              '</div>');

              // hide the modal
              $("#nfeModal").modal('hide');
              // reset the form
              $("#nfeForm.form-group").removeClass('has-error').removeClass('has-success');
              //alert(response.messages);
              //window.location.reload(false);
              Swal.fire({
				  icon: 'success',
				  title: response.messages,
				  showCancelButton: false,
				   confirmButtonText: "Ok",
				}).then((result) => {
					window.location.reload(false)
				});

            } else {
              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);
                 // alert(index+":"+value);
                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  id.after(value);
                });
              } else {
              	$("#nfeModal").modal('hide');
                $("#order_messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                  '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
                '</div>');
              }
            }
          }, complete: e => {
            form.find('button[type="submit"]').attr('disabled', false);
          }
        });

        return false;
      });
	}

  function goToManageTags()
  {
      window.location.href = "<?=base_url( 'orders/manage_tags')?>"
  }

  function cantCancelOrder($int_to)
  {
  	 Swal.fire({
		  icon: 'error',
		  title: msg_impcancel,
		  text: 'Marketplace '+$int_to+' '+msg_impcancel_explain
		});
  }

  function cancelOrder()
  {
      // submit the edit from
      $("#cancelarPedidoForm").unbind('submit').bind('submit', function() {
          $('#overlay-cancel-order').show();
        var form = $(this);
        form.find('button[type="submit"]').attr('disabled', true);
		// remove the text-danger
        $(".text-danger").remove();
        $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize(), // /converting the form data into array and sending it to server
          dataType: 'json',
          error: function(jqXHR, textStatus, errorThrown)
          {
              $('#overlay-cancel-order').hide();
              //alert(errorThrown+ " resposta = " + jqXHR.responseText);
              Swal.fire({
				  icon: 'error',
				  title: errorThrown,
				  text: jqXHR.responseText
				});
          },
          success:function(response) {
            if(response.success === true) {
                $('#overlay-cancel-order').hide();
              $("#order_messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
              '</div>');

              // hide the modal
              $("#cancelarOrdemModal").modal('hide');
              // reset the form
              $("#cancelarPedidoForm.form-group").removeClass('has-error').removeClass('has-success');
              //alert(response.messages);

              // window.location.reload(false);
              Swal.fire({
				  icon: 'success',
				  title: response.messages,
				  showCancelButton: false,
				   confirmButtonText: "Ok",
				}).then((result) => {
					//window.location.href = isAdm == 1 ? base_url + 'orders/cancelaMkt' : '';
                    location.reload();
				});


            } else {
                $('#overlay-cancel-order').hide();
              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);

                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  id.after(value);

                });
              } else {
              	$("#cancelarOrdemModal").modal('hide');
                $("#order_messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                  '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
                '</div>');
              }
            }
          }, complete: e => {
            form.find('button[type="submit"]').attr('disabled', false);
          }
        });

        return false;
      });
	}

  function cancelCommisionChargesOrder()
  {
    $('#CancelComissionChargesForm').trigger("reset");
	}

  function postItem()
  {
      // submit the edit from
      $("#postItemForm").unbind('submit').bind('submit', function() {
        var form = $(this);
        form.find('button[type="submit"]').attr('disabled', true);
		// remove the text-danger
        $(".text-danger").remove();
        $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize(), // /converting the form data into array and sending it to server
          dataType: 'json',
          error: function(jqXHR, textStatus, errorThrown)
          {
            console.log(jqXHR, textStatus, errorThrown, form.serialize());
              alert(errorThrown+ " resposta = " + jqXHR.responseText);
              Swal.fire({
				  icon: 'error',
				  title: errorThrown,
				  text: jqXHR.responseText
				});
              console.log(jqXHR.responseText);
          },
          success:function(response) {
            if(response.success === true) {
              $("#order_messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
              '</div>');

              // hide the modal
              $("#postItemModal").modal('hide');
              // reset the form
              $("#postItemForm.form-group").removeClass('has-error').removeClass('has-success');
              //alert(response.messages);

              // window.location.reload(false);
              Swal.fire({
				  icon: 'success',
				  title: response.messages,
				  showCancelButton: false,
				   confirmButtonText: "Ok",
				}).then((result) => {
					//window.location.href = base_url + 'orders/envioMkt'
                    location.reload();
				});


            } else {
              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);

                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  id.after(value);

                });
              } else {
              	$("#postItemModal").modal('hide');
                $("#order_messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                  '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
                '</div>');
              }
            }
          }, complete: e => {
            form.find('button[type="submit"]').attr('disabled', false);
          }
        });

        return false;
      });
	}

  function deliveryItem()
  {
      // submit the edit from
      $("#deliveryItemForm").unbind('submit').bind('submit', function() {
        var form = $(this);
        form.find('button[type="submit"]').attr('disabled', true);
		// remove the text-danger
        $(".text-danger").remove();
        $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize(), // /converting the form data into array and sending it to server
          dataType: 'json',
          error: function(jqXHR, textStatus, errorThrown)
          {
              //alert(errorThrown+ " resposta = " + jqXHR.responseText);
              Swal.fire({
				  icon: 'error',
				  title: errorThrown,
				  text: jqXHR.responseText
				});
              //console.log(jqXHR.responseText);
          },
          success:function(response) {
            if(response.success === true) {
              $("#order_messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
              '</div>');

              // hide the modal
              $("#deliveryItemModal").modal('hide');
              // reset the form
              $("#deliveryItemForm.form-group").removeClass('has-error').removeClass('has-success');
              //alert(response.messages);

              // window.location.reload(false);
              Swal.fire({
				  icon: 'success',
				  title: response.messages,
				  showCancelButton: false,
				   confirmButtonText: "Ok",
				}).then((result) => {
					//window.location.href = base_url + 'orders/freteEntregue'
                    location.reload()
				});


            } else {
              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);

                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  id.after(value);

                });
              } else {
              	$("#deliveryItemModal").modal('hide');
                $("#order_messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                  '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
                '</div>');
              }
            }
          }, complete: e => {
            form.find('button[type="submit"]').attr('disabled', false);
          }
        });

        return false;
      });
	}

  function registerExchange()
  {
      // submit the edit from
      $("#registerExchangeForm").unbind('submit').bind('submit', function() {
        var form = $(this);
        form.find('button[type="submit"]').attr('disabled', true);
		// remove the text-danger
        $(".text-danger").remove();
        $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize(), // /converting the form data into array and sending it to server
          dataType: 'json',
          error: function(jqXHR, textStatus, errorThrown)
          {
              //alert(errorThrown+ " resposta = " + jqXHR.responseText);
              Swal.fire({
				  icon: 'error',
				  title: errorThrown,
				  text: jqXHR.responseText
				});
              //console.log(jqXHR.responseText);
          },
          success:function(response) {
            if(response.success === true) {
              $("#order_messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
              '</div>');

              // hide the modal
              $("#registerExchangeModal").modal('hide');
              // reset the form
              $("#registerExchangeForm.form-group").removeClass('has-error').removeClass('has-success');
              //alert(response.messages);

              // window.location.reload(false);
              Swal.fire({
				  icon: 'success',
				  title: response.messages,
				  showCancelButton: false,
				   confirmButtonText: "Ok",
				}).then((result) => {
					window.location.href = base_url + 'orders'
				});


            } else {
              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);

                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  id.after(value);

                });
              } else {
              	$("#registerExchangeModal").modal('hide');
                $("#order_messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                  '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
                '</div>');
              }
            }
          }, complete: e => {
            form.find('button[type="submit"]').attr('disabled', false);
          }
        });

        return false;
      });
	}


	function toggleCancelReason(e) {
	  	e.preventDefault();
	  	$("#cancel_reason").toggle();
  	}

  function toggleAddressWithdrawal(e) {
      e.preventDefault();
      $("#pickup_location").toggle();
  }

  function toggleProblemOrder(e) {
      e.preventDefault();
      $("#problem_order").toggle();
  }

  function toggleRequestCancelOrder(e) {
      e.preventDefault();
      $("#requestCancelOrder").toggle();
  }

  	function cancelReason()
  {
      // submit the edit from
      $("#cancelReasonForm").unbind('submit').bind('submit', function() {
        var form = $(this);
        form.find('button[type="submit"]').attr('disabled', true);
		// remove the text-danger
        $(".text-danger").remove();
        $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize(), // /converting the form data into array and sending it to server
          dataType: 'json',
          error: function(jqXHR, textStatus, errorThrown)
          {
              //alert(errorThrown+ " resposta = " + jqXHR.responseText);
              Swal.fire({
				  icon: 'error',
				  title: errorThrown,
				  text: jqXHR.responseText
				});
              //console.log(jqXHR.responseText);
          },
          success:function(response) {
            if(response.success === true) {
              $("#order_messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
              '</div>');

              // hide the modal
              $("#cancelReasonModal").modal('hide');
              // reset the form
              $("#cancelReasonForm.form-group").removeClass('has-error').removeClass('has-success');
              //alert(response.messages);

              // window.location.reload(false);
              Swal.fire({
				  icon: 'success',
				  title: response.messages,
				  showCancelButton: false,
				   confirmButtonText: "Ok",
				}).then((result) => {
					window.location.reload(false);
				});


            } else {
              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);

                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  id.after(value);

                });
              } else {
              	$("#cancelReasonModal").modal('hide');
                $("#order_messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                  '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
                '</div>');
              }
            }
          }, complete: e => {
            form.find('button[type="submit"]').attr('disabled', false);
          }
        });

        return false;
      });
	}

	function alterCancelReason(e, id_cancel,reason,penalty) {
	  	e.preventDefault();
	    document.getElementById('id_cancel').value=id_cancel;
	    document.getElementById('cancelReason').value=reason;
	    document.getElementById('penalty_to_reason').value=penalty;
		$("#cancelReasonModal").modal('show');
		cancelReason();
	}

	function returnCorreios()
    {
      // submit the edit from
      $("#returnCorreiosForm").unbind('submit').bind('submit', function() {
        var form = $(this);
        form.find('button[type="submit"]').attr('disabled', true);
        // remove the text-danger
        $(".text-danger").remove();
        $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize(), // /converting the form data into array and sending it to server
          dataType: 'json',
          success:function(response) {
            if(response.success === true) {
              $("#order_messages").html('<div class="alert alert-success alert-dismissible" role="alert">'+
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                '<strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>'+response.messages+
              '</div>');

              // hide the modal
              $("#returnCorreiosModal").modal('hide');
              // reset the form
              $("#returnCorreiosForm.form-group").removeClass('has-error').removeClass('has-success');
              //alert(response.messages);
              // window.location = base_url + 'orders/semfrete'
              window.location.reload(false);

            } else {
              if(response.messages instanceof Object) {
                $.each(response.messages, function(index, value) {
                  var id = $("#"+index);

                  id.closest('.form-group')
                  .removeClass('has-error')
                  .removeClass('has-success')
                  .addClass(value.length > 0 ? 'has-error' : 'has-success');
                  id.after(value);

                });
              } else {
              	$("#returnCorreiosModal").modal('hide');
                $("#order_messages").html('<div class="alert alert-warning alert-dismissible" role="alert">'+
                  '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                  '<strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>'+response.messages+
                '</div>');
              }
            }
          }, complete: e => {
            form.find('button[type="submit"]').attr('disabled', false);
          }
        });

        return false;
      });
	}

    $("#returnChangeSellerForm").on('submit', function() {
      var form = $(this);
      form.find('button[type="submit"]').attr('disabled', true);
      $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize(), // /converting the form data into array and sending it to server
          dataType: 'json',
          success: response => {
              if(response.success === true) {
                  $("#order_messages").html(`
                    <div class="alert alert-success alert-dismissible" role="alert">
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      <strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>
                      ${response.messages}
                    </div>
                  `);
                  $("#sendChangeSeller").modal('hide');
                  window.location.reload(false);
              } else {
                  $("#sendChangeSeller").modal('hide');
                  $("#order_messages").html(`
                    <div class="alert alert-warning alert-dismissible" role="alert">
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      <strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>${response.messages}
                    </div>
                  `);
              }
          } ,error: e => {
              console.log(e);
          }, complete: e => {
              form.find('button[type="submit"]').attr('disabled', false);
          }
      });

      return false;
    });

    $("#updateOrderIncidence").on('submit', function() {
      var form = $(this);
      form.find('button[type="submit"]').attr('disabled', true);
      $.ajax({
          url: form.attr('action'),
          type: form.attr('method'),
          data: form.serialize(), // /converting the form data into array and sending it to server
          dataType: 'json',
          success: response => {
              if(response.success === true) {
                  $("#order_messages").html(`
                    <div class="alert alert-success alert-dismissible" role="alert">
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      <strong> <span class="glyphicon glyphicon-ok-sign"></span> </strong>
                      ${response.messages}
                    </div>
                  `);
                  $("#incidence").modal('hide');
                  window.location.reload(false);
              } else {
                  $("#incidence").modal('hide');
                  $("#order_messages").html(`
                    <div class="alert alert-warning alert-dismissible" role="alert">
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      <strong> <span class="glyphicon glyphicon-exclamation-sign"></span> </strong>${response.messages}
                    </div>
                  `);
              }
          } ,error: e => {
              console.log(e);
          }, complete: e => {
              form.find('button[type="submit"]').attr('disabled', false);
          }
      });

      return false;
    });
    $('[name="cancelIncidence"]').on('change', function(){
        if ($(this).is(':checked')) $('[name="incidence"]').val('').attr('readonly', true);
        else $('[name="incidence"]').attr('readonly', false);
    });

  $('#btnViewQueueOrder').click(function(){
    $('#viewQueueOrder').modal();
  });

  $('#verModalResumoDesconto').click(function(){
      var orderId = $(this).attr('orderId');
      $.ajax({
          url:'<?= base_url() ?>/orders/getModalDiscountDetail/'+orderId,
          method: 'post',
          dataType: 'html',
          success: function(response){
              $('.detalhesDescontosBody').html(response);
          }
      });
      $('#modalResumoDesconto').modal();
  });

  $('#verModalResumoTaxas').click(function(){
      var orderId = $(this).attr('orderId');
      $.ajax({
          url:'<?= base_url() ?>/orders/getModalTaxDetail/'+orderId,
          method: 'post',
          dataType: 'html',
          success: function(response){
              $('.detalhesTaxaBody').html(response);
          }
      });
      $('#modalResumoTaxas').modal();
  });

  $('#verModalFreteDetalhado').click(function() {
    $.ajax({
      url: '<?= base_url() ?>orders/freightDetails/'+$(this).attr('orderId')+'/'+document.getElementById('freight_price').value,
      method: 'post',
      dataType: 'html',
      success: function(response){
        let resp = JSON.parse(response);
        $('.detalhesFreteBody').html(resp);
      }
    });

    $('#modalFreteDetalhado').modal();
  });

  $('#addOrderQueueIntegration').submit(function(){
    const order  = $(this).find('button[type="submit"]').attr('order-id');
    const form   = $(this);

    form.find('button[type="submit"]').attr('disabled', true);

    $.ajax({
      url: form.attr('action'),
      type: form.attr('method'),
      data: { order }, // /converting the form data into array and sending it to server
      dataType: 'json',
      success: response => {
          console.log(response);

          Swal.fire({
              icon: response.success ? 'success' : 'error',
              title: response.message,
              showCancelButton: false,
              confirmButtonText: "Ok",
          }).then((result) => {
              if (response.success) window.location.reload(false)
          });
      } ,error: e => {
          console.log(e);
      }, complete: e => {
          form.find('button[type="submit"]').attr('disabled', false);
      }
    });

    return false;
  });

  $(function(){
    $('#addMediationOrder').on("submit", function(){
	    $('#btnMediationCreate, #btnMediationResolve').prop('disabled', true);
    });
  });

  var total_order_items = 0;
  // Submissão do formulário de devolução manual de produtos.
  $('#newReturnOrderForm').submit((event) => {
    event.preventDefault();

    /*if ($('#confirm_new_return').val() !== 'DEVOLUÇÃO') {
      $('#type_return').show();
      return;
    } else {
      $('#type_return').hide();
    }*/

    if (order !== null) {
      var data = [];
      if (order.order_item) {
        var total = order.order_item.reduce((acumulador, element) => {
          if ((acumulador + parseInt(element.qty)) >= total_order_items) {
            total_order_items = acumulador + parseInt(element.qty);
          }
          return acumulador + parseInt(element.qty);
        }, 0);

        if (total > 0) {
          let total_items_selected = 0;
          order.order_item.forEach((element, index) => {
            let element_id = `ret_item_${element.id}_${index}`;
            let quantity = parseInt($(`#${element_id}`).text());
            order.order_item[index].qty = quantity;
            total_items_selected += quantity;
            order.order_item[index].new = true;

            if (element.qty > 0) {
              data.push({
                product_id: element.product_id,
                variant: element.variant,
                order_id: element.order_id,
                store_id: element.store_id,
                product_quantity: element.qty,
                sku: element.sku,
                name: element.name,
                price: element.rate,
                picture: element.picture,
                order_quantity: total_order_items,
                return_quantity: total_items_selected
              });
            }
          });

          let complete_order = 0;
          if (total_items_selected == total_order_items) {
            complete_order = 1;
          }

          let payload = {
            id: order.order.id,
            complete_order: complete_order,
            items: JSON.stringify(data),
            refund_on_gateway_value_to_return: $('#newReturnOrderForm [name="refund_on_gateway_value_to_return"]').val() ?? null
          };

          let form = document.createElement('form');
          form.style.visibility = 'hidden';
          form.method = 'POST';
          form.action = `${base_url}ProductsReturn/requestReturnOrders`;

          $.each(Object.keys(payload), function(index, key) {
            let input = document.createElement('input');
            input.name = key;
            input.value = payload[key];
            form.appendChild(input)
          });

          document.body.appendChild(form);
          form.submit();
        }
      }
    }
  });

    $('.viewInteractionsPayment').on('click', function(){
        const paymentId = $(this).attr('payment-id');

        $.get(`${base_url}Payment/getInteractionsPaymentByOrder/${order.order.id}/${paymentId}`)
        .done(response => {
            $('#modalInteractionsPayment .modal-body').empty();
            let date;
            $(response).each(function(key, value) {
                date = moment(value.interaction_date).format('DD/MM/YYYY HH:mm');
                $('#modalInteractionsPayment .modal-body').append(`<div class="row"><div class="col-md-12"><div class="d-flex justify-content-between" data-toggle="collapse" data-target="#collapse_interaction_payment_${value.id}" aria-expanded="false" aria-controls="collapse_interaction_payment_${value.id}"><h4>${value.status}</h4><span>${date}</span></div><div class="collapse" id="collapse_interaction_payment_${value.id}"><p style="word-wrap: break-word">${value.description}</p></div></div></div>`)
            });
        })
        .fail(e => {
            console.log(e);
            Swal.fire({
                icon: 'error',
                title: 'Histórico de Integração',
                text: 'Não foi possível abrir o histórico de integração!'
            });
        })
        .always(function() {
            $('#modalInteractionsPayment').modal()
        });
    });

  function goToTrackOrderReturn()
  {
    window.location.href = "<?=base_url('ProductsReturn/return')?>"
  }

  function toggleOrderErrorIntegration(e) {
      e.preventDefault();
      $("#order_error_integration").toggle();
  }

  const loadItemsToPartialCancellation = () => {
      $.get(`${base_url}/Orders/getItemsToPartialCancellation/${order.order.id}`)
      .done(response => {
          $('#modalPartialCancellationOrder #partial_cancellation_body_items').empty();
          let string='';
          let index = 0;
          let fild_name = '';
          $(response).each(function(key, value) {
              index = key + 1;
              fild_name = `item_${value.id}_${index}`;

              string+=`<tr data-item-id="${value.id}">`;
              string+=`<td>${index}</td>`;
              string+=`<td>${value.sku}</td>`;
              string+=`<td>${value.name}</td>`;
              string+=`<td>${value.qty}</td>`;
              string+=`<td>
                  <button type="button" class="btn btn-link change-qtd-${fild_name}"><i class="fa fa-minus-square" aria-hidden="true" onclick="minus('${fild_name}',0,${value.qty})"></i></button>
                  <span id="${fild_name}">${value.qty_canceled}</span>
                  <button type="button" class="btn btn-link change-qtd-${fild_name}"><i class="fa fa-plus-square" aria-hidden="true" onclick="plus('${fild_name}',${value.qty},${value.qty})"></i></button>
              </td>`;
              string+='</tr>';
          });
          $('#modalPartialCancellationOrder #partial_cancellation_body_items').append(string);
      });
  }

  $('#confirmPartialCancellationForm').on('submit', function(e){
      e.preventDefault();
      const btn = $(this).find('button[type="submit"]');
      btn.prop('disabled', true);

      let data_post = [];
      $('#modalPartialCancellationOrder #partial_cancellation_body_items tr').each(function () {
          data_post.push({
              item_id: $(this).data('item-id'),
              qty: parseInt($(this).find('td:eq(4) span').text())
          });
      });

      const action = $(this).attr('action');

      $.post( action, { data_post }, response => {
          if (response.success) {
              $('#modalPartialCancellationOrder').modal('hide');
          }

          Swal.fire({
              icon: response.success ?'success' : 'error',
              title: response.message
          }).then(() => {
              if (response.success) {
                  window.location.reload();
              }
          });
      }).always(() => {
          btn.prop('disabled', false);
      });
  });

  $('#updateShippingMethodForm').on('submit', function(e){
      e.preventDefault();
      const btn = $(this).find('button[type="submit"]');
      btn.prop('disabled', true);

      const action = $(this).attr('action');

      const shipping_name = $('[name="shipping_name"]').val();
      const shipping_method = $('[name="shipping_method"]').val();

      $.post( action, { shipping_name, shipping_method }, response => {
          if (response.success) {
              $('#updateShippingMethodModal').modal('hide');
          }

          Swal.fire({
              icon: response.success ?'success' : 'error',
              title: response.message
          }).then(() => {
              if (response.success) {
                  window.location.reload();
              }
          });
      }).always(() => {
          btn.prop('disabled', false);
      });
  });

  function toggleInvoiceErrorIntegration(e) {
      e.preventDefault();
      $("#invoice_error_integration").toggle();
  }

  const checkMaxValue = (e, max_value) => {
    if (e.value > max_value) {
        e.value = max_value;
    }
  }

  const checkValue = (e) => {
    if (isNaN(e.value)) {
        e.value = 0.00;
    }
  }
</script>
