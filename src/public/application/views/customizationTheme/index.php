<link rel="stylesheet" href="<?= base_url('assets/bower_components/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css') ?>">
<style>
    .nav.nav-tabs li a {
        text-decoration: none;
    }

    .colorpicker-element .input-group-addon i, .colorpicker-element .add-on i {
        height: 21px;
        width: 100% !important;
        padding: 15px;
    }
</style>
<div class="content-wrapper">

    <?php

    $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data);

    $sellerCenter =  $this->session->userdata('layout');
    @$handle = fopen("assets/dist/css/CustomizationTheme/styles_".$sellerCenter['value'].".txt", "r");
    $array = [];
    if ($handle) {
        while (($buffer = fgets($handle, 4096)) !== false) {
            array_push($array, $buffer);
            $array = implode(",", $array);
            $array = (explode(",", $array));
        }
    }
    ?>

    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div id="messages"></div>
                <br>
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')): ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>

                <br>
                <div class="row">
                    <div class="col-md-12">
                        <div class="nav-tabs-custom">
                            <ul class="nav nav-tabs">
                                <li class="active"><a href="#tab_1-1" data-toggle="tab">Campos para Personalização</a></li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane active" id="tab_1-1">
                                    <div class="box-body">
                                        <form action="<?php echo base_url('CustomizationTheme/create') ?>" method="post" enctype="multipart/form-data">
                                            <div class="col-sm-12">
                                                <?php $this->load->view('customizationTheme/partials/menu', ['array' => $array ]); ?>
                                            </div>
                                            <div class="col-sm-4">
                                                <?php $this->load->view('customizationTheme/partials/content'); ?>
                                            </div>
                                            <div class="col-sm-4">
                                                <?php $this->load->view('customizationTheme/partials/footer'); ?>
                                            </div>
                                            <div class="col-sm-12">
                                                <?php $this->load->view('customizationTheme/partials/buttons'); ?>
                                            </div>
                                            <div class="col-sm-12">
                                                <?php $this->load->view('customizationTheme/partials/faviconAndBanner'); ?>
                                            </div>
                                            <div class="col-sm-12">
                                                <br/><br/>
                                                <button class="btn btn-primary pull-right" id="btn_aplicar" style="background-color:#367fa9!important;color:#fff!important;border-color:#fff!important;" >Aplicar no Tema</button>
                                        </form>
                                            <a class="btn btn-warning pull-left" id="btn_remover_theme" onclick="QuestionConfirmTheme('removeTheme')" style="background-color: brown!important;color: #fff!important;border-color: brown!important;" >Remover do Tema</a>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<script type="text/javascript" src="<?= base_url('assets/bower_components/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js') ?>"></script>
<!-- <script src="</?php echo base_url('assets/dist/js/personalize.js'); ?>"></script> -->
<script type="text/javascript" src="<?php echo base_url('/assets/bower_components/sweetalert/dist/sweetalert2.all.min.js') ?>"></script>
<script type="text/javascript">
    $(function () {
        $('.cp').colorpicker();
    });
    var theme = "<?php echo $this->session->userdata('layout')['value'] ?>";
    function QuestionConfirmTheme(value) {
        Swal.fire({
            title: 'Tem certeza que deseja remover no Seller Center '+theme+'?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim',
            cancelButtonText: 'Não',
        }).then((result) => {
            if (result.value) {
                window.location.href = '<?= base_url('CustomizationTheme/') ?>'+value;
            }
        })
    }
    function QuestionConfirm(value) {
        Swal.fire({
            title: 'Tem certeza que deseja remover o '+value+' do tema '+theme+'?',
            text: "Essa ação não poderá ser desfeita!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim',
            cancelButtonText: 'Não',
        }).then((result) => {
            if (result.value) {
                window.location.href = '<?= base_url('CustomizationTheme/remove') ?>'+value;
            }
        })
    }
    $('#clearFavicon, #clearBanner').hide();

    $('#inputFavicon').change(function(){
        $('#btnComfimFavicon').hide();
        $('#clearFavicon').show();
    });
    $('#clearFavicon').click(function(){
        $('#inputFavicon').val('')
        $('#pic1').removeAttr('src');
        $('#clearFavicon').hide();
        $('#pic1').attr('src','<?= $favicon ?>');
        $('#btnComfimFavicon').show();
    });

    $('#inputBanner').change(function(){
        $('#btnComfimBanner').hide();
        $('#clearBanner').show();
    });
    $('#clearBanner').click(function(){
        $('#inputBanner').val('')
        $('#pic2').removeAttr('src');
        $('#clearBanner').hide();
        $('#pic2').attr('src','<?= $banner ?>');
        $('#btnComfimBanner').show();
    });
</script>