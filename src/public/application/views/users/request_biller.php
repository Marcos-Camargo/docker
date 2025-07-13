<!--
SW Serviços de Informática 2019

Ver Profile

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_myprofile";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">
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
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><?=$this->lang->line('application_request_biller_module')?></h3>
                    </div>
                    <!-- /.box-header -->
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
                        <form role="form" action="<?php base_url('users/request_biller') ?>" enctype="multipart/form-data" method="post" accept-charset="utf-8">
                            <input type="hidden" name="<?=isset($csrf) ? $csrf['name'] : '';?>" value="<?=isset($csrf) ? $csrf['hash'] : '';?>" />
                            <div class="row">
                                <div class="form-group col-md-12 text-center">
                                    <h3>Faça sua solicitação para faturar seus pedidos em nossa plataforma!</h3>
                                </div>
                                <div class="form-group col-md-12 text-center">
                                    <hr>
                                </div>
                            </div>
                            <?php if($allStoreIntegrate == true){ ?>
                                <div class="row">
                                    <div class="form-group col-md-12 text-center">
                                        <h4>Você já está faturando dentro da plataforma!</h4>
                                    </div>
                                </div>
                            <?php } elseif(count($storesView) == 0){ ?>
                                <div class="row">
                                    <div class="form-group col-md-12 text-center">
                                        <h4>A solicitaçao para faturar em nossa plataforma já foi enviada, aguarde enquanto analisamos!</h4>
                                    </div>
                                </div>
                            <?php } else { ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group col-md-12">
                                        <h3 class="mb-5">Vamos precisar do certificado e senha para configurarmos.</h3>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group col-md-4">
                                        <label>Qual ERP usa atualmente?</label>
                                        <input type="text" name="erp" class="form-control" placeholder="Digite o ERP usado atualmente" autocomplete="off">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Certificado Digital A1 - (*.pfx)</label>
                                        <input type="file" name="file_certificado" class="form-control" accept=".pfx" required>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Senha</label>
                                        <input type="password" autocomplete="off" name="password_certificado" class="form-control" required>
                                    </div>
                                    <div class="form-group col-md-12" style="display: <?=$this->data['userstore'] == 0 && count($storesView) > 1  ? 'block' : 'none'?>">
                                        <label>Seleciona a loja que irá faturar</label>
                                        <select class="form-control" name="stores">
                                            <?php
                                            foreach ($storesView as $store)
                                                echo "<option value='{$store['id']}'>{$store['name']}</option>";
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-5">
                                <div class="col-md-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success col-xs-12 col-md-4">Solicitar</button>
                                </div>
                            </div>
                            <?php } ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<script>
    $(function(){
        $('#moduleBillerNav').addClass('active');
        $('[data-toggle="tooltip"]').tooltip();
        setTimeout(() => { $('[name="password_certificado"], [name="erp"]').val('') }, 1250);
    });
</script>