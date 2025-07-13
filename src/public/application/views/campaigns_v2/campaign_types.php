<link rel="stylesheet" href="<?=base_url()?>/assets/dist/css/views/campaign_v2/styles.css">
<!--<link rel="stylesheet" href="--><?php //=base_url()?><!--/assets/tracking/css/bootstrap.min.css">-->

<?php
    if ($this->model_settings->getStatusbyName('allow_create_campaigns_b2w_type') != "1"):
?>
    <style>
        .types-box {
            width: 50%;
        }
    </style>
<?php
    endif;
?>
<script>

    var type_box_heights = 0;
    var type_box_title_heights = 0;


    $(document).ready(function ()
    {
        setTimeout(function()
        {
            // var types_boxes_height = $('.types-boxes').height();
            var types_boxes_height = $('.types-box').height() * 2;

            $('.types-graph .box').animate({'min-height': (types_boxes_height - 20) + `px`});
            $('.mktplace-graph').animate({'height': (types_boxes_height * 0.8) + `px`});
            $('.tutorial-tip').animate({'min-height': ((types_boxes_height * 0.8) / 3) + `px`});

        },100);

        $('.types-box-content .title').each(function(k,v)
        {
            if ($(v).height() > type_box_title_heights)
            {
                type_box_title_heights = $(v).height();
            }
        });


        $('.types-box-content .text').each(function(k,v)
        {
            if ($(v).height() > type_box_heights)
            {
                type_box_heights = $(v).height();
            }
        });


        $('.types-box-content .title').animate({'height': type_box_title_heights + 'px'});
        $('.types-box-content .text').animate({'height': type_box_heights + 'px'});



    });

</script>


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php
    $data['pageinfo'] = "";
    $data['page_now'] = "campaign_v2_campaign_type_title";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        

        <div class="row mt-3">

            <div class="col-md-3 types-graph">

                <div class="box"><!-- body 1 -->
                    <div class="box-body">

                        <div class="col-md-12 pw-0">


                            <div class="row">

                                <div class="col-md-12 pt-5" style="text-align:center;">
                                    <img src="<?=base_url()?>/assets/images/campaign_v2/mktplace_graph.png" alt="" class="mktplace-graph" style="height:1px;">
                                </div>


<!--                                <div class="col-md-7 tutorial-tip-container" style="margin-top: 5.8vh;">-->
<!---->
<!--                                    <div class="row tutorial-tip">-->
<!--                                        <div class="col-md-1 px-0 num-list">-->
<!--                                            1-->
<!--                                        </div>-->
<!--                                        <div class="col-md-11 font-weight-bold">-->
<!--                                            Marketplace cria campanhas <br/>de acordo com as suas regras<br/>-->
<!--                                        </div>-->
<!--                                    </div>-->
<!--                                    <div class="row tutorial-tip">-->
<!--                                        <div class="col-md-1 px-0 num-list">-->
<!--                                            2-->
<!--                                        </div>-->
<!--                                        <div class="col-md-11 font-weight-bold">-->
<!--                                            As campanhas ficam disponíveis para os sellers escolherem quais aderir-->
<!--                                        </div>-->
<!--                                    </div>-->
<!--                                    <div class="row tutorial-tip" >-->
<!--                                        <div class="col-md-1 px-0 num-list">-->
<!--                                            3-->
<!--                                        </div>-->
<!--                                        <div class="col-md-11 font-weight-bold">-->
<!--                                            Sellers fazem a escolha de quais campanhas vão participar-->
<!--                                        </div>-->
<!--                                    </div>-->
<!--                                </div>-->



                            </div>
                        </div>

                    </div> <!-- body 1 body -->
                </div><!-- body 1 -->

            </div><!-- col 1 -->


            <div class="col-md-9 px-0"><!-- col 2 -->

                <ul class="types-boxes">

                    <li class="types-box">

                            <div class="box">

                                <div class="types-box-content">

                                    <div class="row mx-0">
                                        <div class="col-md-2 px-0">
                                            <i class="fa fa-tag fa-invert fa-green fa-title"></i>
                                        </div>
                                        <div class="col-md-10 px-0 font-weight-bold title">
                                            Desconto compartilhado
                                        </div>
                                    </div>
                                    <div class="col-md-12 px-0 mt-4 mb-3 text">
                                        Marketplace define uma procentagem de desconto dada pelo próprio Marketplace, e uma porcentagem de desconto da diretamente pelo lojista
                                    </div>
                                    <div class="col-md-12 px-0 btn-bottom">
                                        <a href="<?=base_url()?>campaigns_v2/createcampaigns/?defaultType=shared_discount" class="btn btn-success btn222-wider-1 mt-2 btn-block">
                                            Criar Campanha
                                        </a>
                                    </div>

                                </div>
                            </div>

                    </li>
                    <li class="types-box">


                        <div class="box">

                            <div class="types-box-content">

                                <div class="row mx-0">
                                    <div class="col-md-2 px-0">
                                        <i class="fa fa-tag fa-invert fa-green fa-title"></i>
                                    </div>
                                    <div class="col-md-10 px-0 font-weight-bold title">
                                        Redução de comissão e rebate
                                    </div>
                                </div>
                                <div class="col-md-12 px-0 mt-4 mb-3 text">
                                    Marketplace define uma porcentagem de desconto dada pelo próprio Marketplace, e uma porcentagem de desconto dada diretamente pelo lojista
                                </div>
                                <div class="col-md-12 px-0 btn-bottom">
                                    <a href="<?=base_url()?>campaigns_v2/createcampaigns/?defaultType=commission_reduction_and_rebate" class="btn btn-success btn222-wider-1 mt-2 btn-block">
                                        Criar Campanha
                                    </a>
                                </div>

                            </div>
                        </div>


                    </li>
                    <li class="types-box">


                        <div class="box">

                            <div class="types-box-content">

                                <div class="row mx-0">
                                    <div class="col-md-2 px-0">
                                        <i class="fa fa-tag fa-invert fa-green fa-title"></i>
                                    </div>
                                    <div class="col-md-10 px-0 font-weight-bold title">
                                        Desconto custeado pelo Marketplace
                                    </div>
                                </div>
                                <div class="col-md-12 px-0 mt-4 mb-3 text">
                                    Marketplace cobrirá inteiramente o desconto definido para os sellers que aderirem à essa campanha
                                </div>
                                <div class="col-md-12 px-0 btn-bottom">
                                    <a href="<?=base_url()?>campaigns_v2/createcampaigns/?defaultType=channel_funded_discount" class="btn btn-success btn222-wider-1 mt-2 btn-block">
                                        Criar Campanha
                                    </a>
                                </div>

                            </div>
                        </div>


                    </li>
                    <li class="types-box">


                        <div class="box">

                            <div class="types-box-content">

                                <div class="row mx-0">
                                    <div class="col-md-2 px-0">
                                        <i class="fa fa-tag fa-invert fa-green fa-title"></i>
                                    </div>
                                    <div class="col-md-10 px-0 font-weight-bold title">
                                        Desconto custeado pelo Lojista
                                    </div>
                                </div>
                                <div class="col-md-12 px-0 mt-4 mb-3 text">
                                    Lojista que participar da campanha cobreirá inteiramente o desconto definido
                                </div>
                                <div class="col-md-12 px-0 btn-bottom">
                                    <a href="<?=base_url()?>campaigns_v2/createcampaigns/?defaultType=merchant_discount" class="btn btn-success btn222-wider-1 mt-2 btn-block">
                                        Criar Campanha
                                    </a>
                                </div>

                            </div>
                        </div>


                    </li>

                    <?php
					    if ($this->model_settings->getStatusbyName('allow_create_campaigns_b2w_type') == "1"):
                    ?>
                    <li class="types-box">


                        <div class="box">

                            <div class="types-box-content">

                                <div class="row mx-0">
                                    <div class="col-md-2 px-0">
                                        <i class="fa fa-tag fa-invert fa-green fa-title"></i>
                                    </div>
                                    <div class="col-md-10 px-0 font-weight-bold title">
                                        Desconto Negociado pelo Marketplace
                                    </div>
                                </div>
                                <div class="col-md-12 px-0 mt-4 mb-3 text">

                                </div>
                                <div class="col-md-12 px-0 btn-bottom">
                                    <a href="<?=base_url()?>campaigns_v2/createcampaigns/?defaultType=marketplace_trading" class="btn btn-success btn222-wider-1 mt-2 btn-block">
                                        Criar Campanha
                                    </a>
                                </div>

                            </div>
                        </div>


                    </li>

                    <?php
                        endif;
                    ?>

                </ul>

            </div><!-- col 2 -->


        </div> <!-- row -->


    </section>

</div>

<script type="text/javascript">

    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function()
    {
        $("#mainCampaignsNav").addClass('active');
        $("#addCampaignsNav").addClass('active');

    });




</script>