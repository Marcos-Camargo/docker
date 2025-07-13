<?php
use App\Libraries\Enum\CampaignSegment;
use App\Libraries\Enum\CampaignTypeEnum;
use App\Libraries\Enum\ComissionRuleEnum;
use App\Libraries\Enum\DiscountTypeEnum;
?>
<div class="row pl-3 pr-5">

    <div class="col-md-9 pr-5">

        <div class="row">
            <div class="col-md-12">
                <b><?= $this->lang->line('application_name_campaign'); ?>:</b>
            </div>
            <div class="col-md-12">
                <?php echo $campaign['name']; ?>
            </div>
        </div>

        <?php
        if ($campaign['deadline_for_joining']){
        ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <b><?= $this->lang->line('application_deadline_for_joining'); ?>:</b>
            </div>
            <div class="col-md-12">
                <?php echo datetimeBrazil($campaign['deadline_for_joining']); ?>
            </div>
        </div>
        <?php
        }
        ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <b><?= $this->lang->line('application_description'); ?>:</b>
            </div>
            <div class="col-md-12">
                <?php echo nl2br($campaign['description']); ?>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <b><?= $this->lang->line('application_campaign_type'); ?>:</b>
            </div>
            <div class="col-md-12">
                <?php echo CampaignTypeEnum::getDescription($campaign['campaign_type']); ?>
            </div>
        </div>
        <?php
        if ($campaign['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT){
        ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <b><?= $this->lang->line('application_participating_comission'); ?>:</b>
            </div>
            <div class="col-md-12">
                <?= $this->lang->line('application_from'); ?> <?php echo $campaign['participating_comission_from']; ?>%
                <?= $this->lang->line('application_to'); ?> <?php echo $campaign['participating_comission_to']; ?>%
            </div>
        </div>
        <?php
        }
        if ($campaign['discount_type']){
        ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <b><?= $this->lang->line('application_promotion_desc_type'); ?>:</b>
            </div>
            <div class="col-md-12">
                <?php echo DiscountTypeEnum::getDescription($campaign['discount_type']); ?>
            </div>
            <div class="col-md-12">
                <?php
                if ($campaign['discount_type'] == DiscountTypeEnum::FIXED_DISCOUNT){
                    echo money($campaign['fixed_discount']);
                }else{
                    echo $campaign['discount_percentage'].'%';
                }
                ?>
            </div>
        </div>
        <?php
        }
        if ($campaign['segment'] != CampaignSegment::PRODUCT){

            if ($campaign['campaign_type'] == CampaignTypeEnum::SHARED_DISCOUNT){
        ?>
                <div class="row mt-3">
                    <?php
                    if ($campaign['discount_type'] == DiscountTypeEnum::PERCENTUAL){
                        ?>
                        <div class="col-md-12">
                            <b><?= $this->lang->line('application_seller_discount'); ?>:</b>
                        </div>
                        <div class="col-md-12">
                            <?php echo $campaign['seller_discount_percentual']; ?>%
                        </div>
                        <div class="col-md-12">
                            <b><?= $this->lang->line('application_marketplace_discount'); ?>:</b>
                        </div>
                        <div class="col-md-12">
                            <?php echo $campaign['marketplace_discount_percentual']; ?>%
                        </div>
                        <?php
                    }else{
                        ?>
                        <div class="col-md-12">
                            <b><?= $this->lang->line('application_seller_discount'); ?>:</b>
                        </div>
                        <div class="col-md-12">
                            <?php echo money($campaign['seller_discount_fixed']); ?>
                        </div>
                        <div class="col-md-12">
                            <b><?= $this->lang->line('application_marketplace_discount'); ?>:</b>
                        </div>
                        <div class="col-md-12">
                            <?php echo money($campaign['marketplace_discount_fixed']); ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            <?php
            }
        }
        if (in_array($campaign['campaign_type'], [CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT,CampaignTypeEnum::SHARED_DISCOUNT, CampaignTypeEnum::MERCHANT_DISCOUNT])){
        ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <b><?= $this->lang->line('application_product_min_value'); ?>:</b>
            </div>
            <div class="col-md-12">
                <?php echo money($campaign['product_min_value']); ?>
            </div>
            <div class="col-md-12">
                <b><?= $this->lang->line('application_product_min_stock_quantity'); ?>:</b>
            </div>
            <div class="col-md-12">
                <?php echo $campaign['product_min_quantity']; ?>
            </div>
        </div>
        <?php if($campaign['seller_type'] == 0){ ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <b><?= $this->lang->line('application_min_seller_index'); ?>:</b>
            </div>
            <div class="col-md-12">            </div>
            <div class="col-md-12">
                <?php echo $campaign['min_seller_index']; ?>
            </div>
        </div>
        <?php } ?>
        <?php
        }
        if (in_array($campaign['campaign_type'], [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])){
        ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <b><?= $this->lang->line('application_comission_rule'); ?>:</b>
            </div>
            <div class="col-md-12">
                <?php
                echo ComissionRuleEnum::getDescription($campaign['comission_rule']).' ';
                if ($campaign['comission_rule'] == ComissionRuleEnum::COMISSION_REBATE){
                    echo money($campaign['rebate_value']);
                }else{
                    echo $campaign['new_comission'].'%';
                }
                ?>
            </div>
        </div>
        <?php
        }
        if ($marketplaces){
        ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <b><?= $this->lang->line('application_promotion_marketplaces'); ?>:</b>
            </div>
            <div class="col-md-12">
                <?php
                $marketplacesArray = [];
                foreach ($marketplaces as $marketplace){
                    $marketplacesArray[] = $marketplace['int_to'];
                }
                echo implode(', ', $marketplacesArray);
                ?>
            </div>
        </div>
        <?php
        }
        if ($categories){
        ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <b><?= $this->lang->line('application_promotion_categories'); ?>:</b>
            </div>
            <div class="col-md-12">
                <table class="table table-hover table-condensed table-bordered">
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <?php
                        if ($campaign['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT){
                            ?>
                            <th><?= $this->lang->line('application_discount'); ?></th>
                            <th><?= $this->lang->line('application_commission'); ?></th>
                            <?php
                        }
                        ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($categories as $category){
                        ?>
                        <tr>
                            <td><?php echo $category['category_name']; ?></td>
                            <?php
                            if ($campaign['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT){
                                ?>
                                <td>
                                    <?php
                                    echo DiscountTypeEnum::getDescription($category['discount_type']).': ';
                                    if ($category['discount_type'] == DiscountTypeEnum::PERCENTUAL){
                                        echo $category['discount_percentage'].' %';
                                    }else{
                                        echo money($category['fixed_discount']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo ComissionRuleEnum::getDescription($category['comission_rule']).': ';
                                    if ($category['comission_rule'] == ComissionRuleEnum::NEW_COMISSION){
                                        echo $category['new_comission'].' %';
                                    }else{
                                        echo money($category['rebate_value']);
                                    }
                                    ?>
                                </td>
                                <?php
                            }
                            ?>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>

                <?php
                foreach ($categories as $category){
                    $categoriesArray[] = $category['category_name'];
                }
                ?>
            </div>
        </div>
        <?php
        }
        ?>
    </div>


    <div class="col-md-3 p-4" style="background-color: #ebecf0; color: #333; border-radius: 5px;">
        <div class="row">
            <div class="col-md-12">
                <b><?= $this->lang->line('application_vigencia_inicial'); ?>:</b>
            </div>
            <div class="col-md-12">
                <?php
                    $start_date = explode(' ', datetimeBrazil($campaign['start_date']));
                    echo $start_date[0].'<br>'.$start_date[1];
                ?>
            </div>

            <div class="col-md-12 mt-4">
                <b><?= $this->lang->line('application_vigencia_final'); ?>:</b>
            </div>
            <div class="col-md-12">
                <?php
                    $end_date = explode(' ', datetimeBrazil($campaign['end_date']));
                    echo $end_date[0].'<br>'.$end_date[1];
                ?>
            </div>

            <div class="col-md-12 mt-4">
                <b><?= $this->lang->line('application_created_on'); ?>:</b>
            </div>
            <div class="col-md-12">
                <?php
                $created_date = explode(' ', datetimeBrazil($campaign['created_at']));
                echo $created_date[0].'<br>'.$created_date[1];
                ?>
            </div>

        </div>
    </div>



</div>

