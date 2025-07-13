<link rel="stylesheet" href="<?=base_url()?>/assets/dist/css/views/campaign_v2/styles.css">
<script>

    $(document).ready(function ()
    {
        var tutorial_tip_height = $('.tutorial-container').height() - $('.tutorial-title').outerHeight();

        $('.mktplace-graph').animate({'height': tutorial_tip_height + `px`});
        $('.tutorial-tip').animate({'min-height': ((tutorial_tip_height + (tutorial_tip_height * 0.11)) / 3) + `px`});

    });

</script>


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php
    $data['pageinfo'] = "";
    $data['page_now'] = "first_campaign";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        

        <div class="row mt-3">

            <div class="col-md-12">

                <div class="box">
                    <div class="box-body">

                        <div class="col-md-11 pw-0 tutorial-container">

                            <div class="col-md-5 tutorial-illustration" >
                                <img src="<?=base_url()?>/assets/images/campaign_v2/tutorial_shopping.png" alt="">
                            </div>

                            <div class="col-md-7">
                                <div class="col-md-12 tutorial-title">
                                    <h2>Crie campanhas de descontos <br/>para os seus sellers aderirem</h2>
                                </div>
                                <div class="row">
                                    <div class="col-md-5 px-0" style="text-align:center;">
                                        <img src="<?=base_url()?>/assets/images/campaign_v2/mktplace_graph.png" alt="" class="mktplace-graph" style="height:1px;">
                                    </div>
                                    <div class="col-md-7 px-0 tutorial-tip-container">
                                        <div class="row tutorial-tip">
                                            <div class="col-md-1 px-0 num-list">
                                                1
                                            </div>
                                            <div class="col-md-11 font-weight-bold">
                                                Marketplace cria campanhas de acordo com as suas regras<br/>
<!--                                                <button class="btn-success btn-wider-1 mt-2 px-1">Criar Primeira Campanha</button>-->
                                                <a href="<?=base_url()?>campaigns_v2/campaigntypes" class="btn btn-success btn-wider-1 mt-4">
                                                    Criar Primeira Campanha
                                                </a>
                                            </div>
                                        </div>
                                        <div class="row tutorial-tip pt-3">
                                            <div class="col-md-1 px-0 num-list">
                                                2
                                            </div>
                                            <div class="col-md-11 font-weight-bold">
                                                As campanhas ficam disponíveis para os sellers escolherem quais aderir
                                            </div>
                                        </div>
                                        <div class="row tutorial-tip pt-1" >
                                            <div class="col-md-1 px-0 num-list">
                                                3
                                            </div>
                                            <div class="col-md-11 font-weight-bold">
                                                Sellers fazem a escolha de quais campanhas vão participar
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-1"></div>
                    </div>
                </div>


            </div>

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