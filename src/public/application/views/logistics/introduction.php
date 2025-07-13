<div class="content-wrapper">
    <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>
    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box box-primary">
                    <div class="box-body">
                        <div class="col-md-12 padding-40">
                            <img src="<?=base_url('assets/images/system/introduction_logistic_background.png')?>" alt="Imagem intrudução" width="600px">
                            <div class="overlay-introduction">
                                <h1 class="font-weight-bold">Você precisa configurar a<br> logística para seus sellers</h1>
                                <p class="mt-1">Para que seus sellers possam começar a cotar frete e enviar seus pedidos, você precisa:</p>
                                <p class="mt-5"><span class="border-radius-index bg-primary">1</span> Definir sua <b>logística principal</b> para os sellers usarem</p>
                                <p><span class="border-radius-index bg-primary">2</span> Definir a <b>integração de logística externa que cada seller</b> usará</p>
                                <a href="<?=base_url('logistics/integrations')?>" class="btn btn-success col-md-6 mt-4">Iniciar a configuração</a>
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

    .border-radius-index {
        padding: 1px 6px;
        border-radius: 50%;
        margin-right: 10px;
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
        $('#mainLogisticsNav').addClass('active');
        $('#manageLogisticIntegrationsNav').addClass('active');
    })
</script>