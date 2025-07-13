<?php
if (hasPermission(['viewHierarchyComission'], $user_permission)) {
    $this->load->view('comissioning/commissioning_modal_details');
}
?>
<div style="margin-top: 20px;">

    <!-- Total Geral de Taxas -->
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-12">
            <div style="border-radius: 10px; padding: 20px; background-color: #007bff; color: #ffffff; border: 1px solid #0056b3; font-size: 20px; font-weight: bold; display: flex; align-items: center;">
                <i class="fa fa-calculator" style="margin-right: 15px; font-size: 24px;"></i>
                Total Geral das Taxas a Pagar:
                <span style="font-size: 26px; display: inline-block; margin-left: auto;"><?= money($total_taxes) ?></span>
            </div>
        </div>
    </div>

    <!-- Menu Compacto -->
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-12">
            <div style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; background-color: #f9f9f9;">
                <h5 style="font-size: 16px; margin: 0 0 10px; color: #31708f; font-weight: bold;">
                    <i class="fa fa-list-ul" style="margin-right: 5px; color: #007bff;"></i> Produtos
                </h5>
                <?php foreach ($campaigns as $index => $product): ?>
                    <!-- Produto e Detalhes -->
                    <div style="margin-bottom: 15px;">
                        <!-- Botão do Produto -->
                        <button class="btn btn-light" style="width: 100%; text-align: left; margin-bottom: 5px; border: 1px solid #ddd; border-radius: 5px;"
                                data-toggle="collapse"
                                data-target="#product-<?= $index ?>">
                            <i class="fa fa-box" style="color: #007bff; margin-right: 5px;"></i>
                            <strong><?= substr($product['product_name'], 0, 50) ?>...</strong> (SKU: <?= $product['sku'] ?>)
                            <span style="float: right; display: flex; align-items: center;">
                                <i class="fa fa-coins" style="margin-right: 5px;"></i> <?= money($product['total_taxes']) ?>
                                <i class="fa fa-eye" style="margin-left: 10px; color: #007bff;"></i>
                            </span>
                        </button>

                        <!-- Detalhes do Produto -->
                        <div id="product-<?= $index ?>" class="collapse" style="margin-top: 10px;">
                            <div style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; background-color: #f9f9f9;">
                                <!-- Informações do Produto -->
                                <h4 style="font-size: 16px; margin: 0;">
                                    <i class="fa fa-box-open" style="color: #007bff; margin-right: 5px;"></i>
                                    Produto: <?= $product['product_name'] ?> (SKU: <?= $product['sku'] ?>) - <?= $product['qty'] ?> und
                                </h4>

                                <div style="margin-top: 10px;">
                                    <p style="margin: 0;">
                                        <i class="fa fa-chart-line" style="color: #007bff; margin-right: 5px;"></i>
                                        <strong>Comissão Produto:</strong> <?= money($product['comissao_produto']) ?>
                                        <span style="color: #28a745; font-size: 14px;">(<?= $product['service_charge_rate'] ?>%)</span>
                                    </p>
                                    <p style="margin: 5px 0;">
                                        <i class="fa fa-coins" style="color: #007bff; margin-right: 5px;"></i>
                                        <strong>Total das Taxas do Produto:</strong> <?= money($product['total_taxes']) ?>
                                    </p>
                                </div>

                                <!-- Lista de Campanhas -->
                                <?php if ($product['has_campaigns']): ?>
                                    <div style="margin-top: 15px;">
                                        <h5 style="font-size: 14px; font-weight: bold;">
                                            <i class="fa fa-bullhorn" style="margin-right: 5px; color: #007bff;"></i> Campanhas:
                                        </h5>
                                        <?php foreach ($product['campaigns'] as $campaign): ?>
                                            <div style="border: 1px solid #007bff; border-radius: 8px; padding: 10px; margin-bottom: 10px; background-color: #f0f8ff;">
                                                <p style="margin: 0;">
                                                    <i class="fa fa-tag" style="color: #007bff; margin-right: 5px;"></i>
                                                    <strong>Campanha:</strong> <?= $campaign['name'] ?>
                                                    <a target="_blank" href="<?= base_url('campaigns_v2/products/' . $campaign['idcampaign']) ?>" style="margin-left: 10px; text-decoration: none; font-size: 12px; color: #007bff;">
                                                        <i class="fa fa-eye"></i> Ver Detalhes
                                                    </a>
                                                </p>
                                                <?php if ($campaign['comissao_campanha'] > 0): ?>
                                                    <p style="margin: 5px 0;">
                                                        <i class="fa fa-percent" style="color: #007bff; margin-right: 5px;"></i>
                                                        Comissão Campanha: <?= money($campaign['comissao_campanha']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($campaign['reembolso_mkt'] > 0): ?>
                                                    <p style="margin: 5px 0;">
                                                        <i class="fa fa-hand-holding-usd" style="color: #007bff; margin-right: 5px;"></i>
                                                        Reembolso Marketplace: <?= money($campaign['reembolso_mkt']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($campaign['total_rebate'] > 0): ?>
                                                    <p style="margin: 5px 0;">
                                                        <i class="fa fa-hand-holding" style="color: #007bff; margin-right: 5px;"></i>
                                                        Total Rebate: <?= money($campaign['total_rebate']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($campaign['reducao_comissao'] > 0): ?>
                                                    <p style="margin: 5px 0;">
                                                        <i class="fa fa-minus-circle" style="color: #007bff; margin-right: 5px;"></i>
                                                        Redução Comissão: <?= money($campaign['reducao_comissao']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <p style="margin: 5px 0;">
                                                    <i class="fa fa-coins" style="color: #007bff; margin-right: 5px;"></i>
                                                    Taxas da Campanha: <?= money($campaign['total_taxes']) ?>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Comissionamento Personalizado -->
                                <?php if ($product['custom_comission']): ?>
                                    <div style="margin-top: 15px;">
                                        <h5 style="font-size: 14px; font-weight: bold;">
                                            <i class="fa fa-cogs" style="margin-right: 5px; color: #007bff;"></i> Comissionamento Personalizado:
                                        </h5>
                                        <div style="border: 1px solid #007bff; border-radius: 8px; padding: 15px; margin-bottom: 10px; background-color: #f0f8ff;">
                                            <a class="btn btn-info btn-sm" style="display: inline-block; text-align: center; font-weight: bold;"
                                               onclick="return comissionDetails('<?= $product['custom_comission']['id'] ?>')"
                                               data-toggle="modal"
                                               data-target="#detailModal">
                                                <?= $product['custom_comission']['id'] ?> - <?= $product['custom_comission']['name'] ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Total Taxas do Produto -->
                                <div style="margin-top: 20px; padding: 15px; background-color: #e9ecef; color: #495057; border: 1px solid #ced4da; border-radius: 8px; text-align: center; font-size: 14px; font-weight: bold;">
                                    <i class="fa fa-coins" style="margin-right: 10px;"></i>
                                    Total Taxas do Produto: <?= money(array_sum(array_column($product['campaigns'], 'total_taxes'))) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Total Taxas de Todos os Produtos -->
                <div style="margin-top: 20px; padding: 15px; background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 8px; font-size: 16px; font-weight: bold; display: flex; align-items: center;">
                    <i class="fa fa-coins" style="margin-right: 10px; font-size: 20px;"></i>
                    Total Taxas de Todos os Produtos:
                    <span style="font-size: 20px; margin-left: auto;"><?= money(array_sum(array_map(fn($product) => array_sum(array_column($product['campaigns'], 'total_taxes')), $campaigns))) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Comissão do Frete -->
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-12">
            <div style="border-radius: 8px; padding: 15px; background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; font-size: 16px; font-weight: bold; display: flex; align-items: center;">
                <i class="fa fa-shipping-fast" style="margin-right: 10px; font-size: 20px;"></i>
                Comissão Total do Frete:
                <span style="font-size: 20px; margin-left: auto;"><?= money($total_shipping_commission) ?></span>
            </div>
        </div>
    </div>

</div>
