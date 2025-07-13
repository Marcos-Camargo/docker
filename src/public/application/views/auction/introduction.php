<div class="content-wrapper">
    <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>
    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box box-primary">
                    <div class="box-body">
                        <div class="col-md-12 padding-40">
                            <img src="<?=base_url('assets/images/system/introduction_auction_rules_background.png')?>" alt="Imagem intrudução" width="585">
                            <div class="overlay-introduction">
                                <h1 class="font-weight-bold">Você ainda não tem uma<br>regra de frete cadastrada</h1>
                                <p class="mt-1">Você pode cadastrar suas regras para controlar:</p>
                                <ul>
                                    <li>Promoções de logística</li>
                                    <li>Regras de Leilão</li>
                                </ul>
                                <a href="<?=base_url('auction/addRulesAuction')?>" class="btn btn-success col-md-6 mt-4">Iniciar cadastro de regras</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<style>
    .padding-40 {
        padding: 40px;
    }

    .overlay-introduction ul {
        padding-left: 15px;
    }

    .overlay-introduction {
        position: absolute;
        top: -10px;
        left: 35%;
        background: #fff;
        height: 447px;
        padding: 7%;
        box-shadow: -10px 0 10px rgb(0 0 0 / 10%);
    }
</style>
<script>
    $(function(){
        $("#mainLogisticsNav").addClass('active');
        $("#auctionRulesNav").addClass('active');
    })
</script>