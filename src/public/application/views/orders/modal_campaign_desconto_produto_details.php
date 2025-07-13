<div style="margin-top: 20px;">

    <!-- Total Geral -->
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-md-12">
            <div style="border-radius: 8px; padding: 15px; background-color: #eaf5ff; color: #31708f; border: 1px solid #bcdff1; font-size: 18px; font-weight: bold; display: flex; align-items: center;">
                <i class="fa fa-calculator" style="margin-right: 10px; font-size: 20px;"></i>
                Total Geral de Descontos (Campanhas + Cupons):
                <span style="font-size: 22px; display: inline-block; margin-left: auto;"><?= money($total_campaigns + $total_pricetags) ?></span>
            </div>
        </div>
    </div>

    <!-- Seção de Campanhas -->
    <div class="row">
        <div class="col-md-12">
            <h3 style="font-weight: bold; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 2px solid #ddd;">
                <i class="fa fa-bullhorn" style="margin-right: 5px; color: #007bff;"></i> Campanhas
            </h3>
        </div>
        <?php
        if (!$campaigns){
            ?>
            <!-- Mensagem discreta quando não há campanhas -->
            <div class="col-md-12">
                <div style="border-radius: 8px; padding: 10px; background-color: #f5f5f5; color: #6c757d; border: 1px solid #e9ecef; font-size: 16px; display: flex; align-items: center;">
                    <i class="fa fa-info-circle" style="margin-right: 8px; font-size: 18px;"></i>
                    Nenhuma campanha de desconto está vinculada a este pedido.
                </div>
            </div>

            <?php
        }else{

            foreach ($campaigns as $campaign): ?>
                <div class="col-md-12" style="margin-bottom: 15px;">
                    <div style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; background-color: #f9f9f9;">

                        <!-- Produto e SKU -->
                        <div style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ccc;">
                            <h4 style="font-size: 16px; margin: 0;">
                                <i class="fa fa-cube" style="color: #007bff; margin-right: 5px;"></i>
                                Produto: <?= $campaign['product_name'] ?> - <?= $campaign['qty'] ?> und
                            </h4>
                            <p style="margin: 5px 0; font-size: 14px;">
                                <i class="fa fa-barcode" style="margin-right: 5px; color: #007bff;"></i>
                                <strong>SKU:</strong> <?= $campaign['sku'] ?>
                            </p>
                        </div>

                        <!-- Nome da Campanha -->
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center;">
                                <h4 style="margin: 0; font-size: 16px;">
                                    <i class="fa fa-bullhorn" style="margin-right: 5px; color: #007bff;"></i>
                                    Campanha: <?= $campaign['campanha_name'] ?>
                                </h4>
                            </div>
                            <a target="_blank" href="<?= base_url('campaigns_v2/products/' . $campaign['campanha_id']) ?>" style="text-decoration: none; font-size: 14px; color: #007bff;">
                                <i class="fa fa-eye"></i> Ver Detalhes
                            </a>
                        </div>

                        <!-- Descontos do Seller e Marketplace -->
                        <div class="row" style="margin-top: 15px;">
                            <div class="col-md-6" style="padding-right: 5px;">
                                <label style="font-weight: bold; font-size: 12px; margin-bottom: 3px;">
                                    <i class="fa fa-tags" style="margin-right: 5px; color: #007bff;"></i> Desconto do Seller:
                                </label>
                                <input type="text" readonly class="form-control" style="font-size: 14px; height: 34px; padding: 5px;" value="<?= money($campaign['desconto_campanha_seller']) ?>">
                            </div>
                            <div class="col-md-6" style="padding-left: 5px;">
                                <label style="font-weight: bold; font-size: 12px; margin-bottom: 3px;">
                                    <i class="fa fa-tags" style="margin-right: 5px; color: #007bff;"></i> Desconto do Marketplace:
                                </label>
                                <input type="text" readonly class="form-control" style="font-size: 14px; height: 34px; padding: 5px;" value="<?= money($campaign['desconto_campanha_marketplace']) ?>">
                            </div>
                        </div>

                        <!-- Total de Descontos -->
                        <div class="row" style="margin-top: 15px;">
                            <div class="col-md-12 text-center">
                                <p style="font-weight: bold; font-size: 14px; margin: 0;">
                                    <i class="fa fa-money" style="margin-right: 5px; color: #007bff;"></i> Total de Descontos desta Campanha:
                                </p>
                                <span style="font-size: 20px; font-weight: bold; color: #007bff;"><?= money($campaign['total_campaign']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            endforeach;
        }
        ?>
    </div>

    <!-- Seção de Cupons -->
    <div class="row" style="margin-top: 20px;">
        <div class="col-md-12">
            <h3 style="font-weight: bold; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 2px solid #ddd;">
                <i class="fa fa-ticket" style="margin-right: 5px; color: #007bff;"></i> Cupons
            </h3>
            <div style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; background-color: #f9f9f9;">
                <div>
                    <label style="font-weight: bold; font-size: 12px; margin-bottom: 3px;">
                        <i class="fa fa-tags" style="margin-right: 5px; color: #007bff;"></i> Valor Total de Cupons:
                    </label>
                    <input type="text" readonly class="form-control" style="font-size: 14px; height: 34px; padding: 5px;" value="<?= money($total_pricetags) ?>">
                </div>
            </div>
        </div>
    </div>
</div>
