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
                                <h1 class="font-weight-bold">Você precisa configurar sua<br>integração</h1>
                                <p class="mt-1">Para começar a cotar frete e enviar seus pedidos através dessa integração você precisa:</p>
                                <p class="mt-5"><span class="border-radius-index bg-primary">1</span> Inserir suas <b>próprias credenciais de integração</b>.</p>
                                <p><span class="border-radius-index bg-primary">2</span><b>Ativar a integração</b>.</p>
                                <p><span class="border-radius-index bg-primary">3</span>Adicionar suas <b>transportadoras de contingência.</b>.</p>
                                <a href="<?=base_url('logistics/manage_logistic')?>" class="btn btn-success col-md-6 mt-4">Iniciar a configuração</a>
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
        $('#manageLogisticNav').addClass('active');
    })
</script>