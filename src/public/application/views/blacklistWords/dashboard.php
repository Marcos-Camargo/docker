<style>

    .tableMarketplaceView {
        width: 100%;
        border: solid thin;
        text-align: center;
    }

    .theadMarketplaceView {
        font-weight: bold;
    }

    td {
        width: 20%;
        height: 50px;
    }

    td:first-child {
        background-color: #4F81BD;
        color: white;
    }

    .b2w {
        background-color: #C3D69B;
    }

    .viaVarejo {
        background-color: red;
    }

    .carrefour {
        background-color: #1F497D;
    }

    .mercadoLivre {
        background-color: yellow;
    }

    .fontBlack {
        color: black;
    }

</style>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <?php 
        $data['pageinfo'] = "application_control_panel";  
        $this->load->view('templates/content_header', $data); 
    ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row dashboard-boxs">
            <div class="col-md-12 col-xs-12">
                <?php
                    if ($this->session->flashdata('success')) {
                ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?= $this->session->flashdata('success'); ?>
                        </div>
                <?php
                    } elseif ($this->session->flashdata('error')) {
                ?>
                        <div class="alert alert-error alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?= $this->session->flashdata('error'); ?>
                        </div>
                <?php
                    }
                ?>
			</div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?= $quantityOfRegisteredProducts ?></h3>
                        <p>Cadastrados</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="<?= base_url('products/') ?>" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?= $quantityOfActivedProducts ?></h3>
                        <p>Ativos</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('products', 'activedProducts');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?= $quantityOfInactivedProducts ?></h3>
                        <p>Inativos</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('products', 'InactivedProducts');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?= $quantityOfDiscontinuedProducts ?></h3>
                        <p>Descontinuados</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('products', 'discontinuedProducts');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?= $quantityOfLockedProducts ?></h3>
                        <p>Bloqueados</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('products', 'lockedProducts');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
        <!-- </div>
        <div class="row dashboard-boxs"> -->
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?= $quantityOfCompletedProducts ?></h3>
                        <p>Completos</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('products', 'CompletedProducts');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?= $quantityOfIncompletedProducts ?></h3>
                        <p>Incompletos</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('products', 'incompletedProducts');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
        <!-- </div>
        <div class="row dashboard-boxs"> -->
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?= $quantityOfIntegratedProducts ?></h3>
                        <p>Enviados para Publicação</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('products', 'integratedProducts');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?= $quantityOfErrorsTransformationProducts ?></h3>
                        <p>Não aceito no marketplace</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('products', 'errorsTransformationProducts');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-blue-light">
                    <div class="inner">
                        <h3><?= $quantityOfPublichedProducts ?></h3>
                        <p>Publicados</p>
                    </div>
                    <div class="icon">
                        <i class="ion ion-bag"></i>
                    </div>
                    <a href="#" onclick="openBoxFilter('products', 'publichedProducts');return false" class="small-box-footer bg-blue"><?=$this->lang->line('application_more_info');?> <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>
        <table class="tableMarketplaceView">
            <thead class="theadMarketplaceView">
                <td>Por marketplace</td>
                <td class="b2w fontBlack">B2W</td>
                <td class="viaVarejo fontBlack">Via Varejo</td>
                <td class="carrefour fontBlack">Carrefour</td>
                <td class="mercadoLivre fontBlack">Mercado Livre</td>
            </thead>
            <tbody>
                <td>Enviados</td>
                <td>
                    <div class="inner">
                        <h2><?= $quantityOfIntegratedProductsB2B ?></h2>
                    </div>
                </td>
                <td>
                    <div class="inner">
                        <h2><?= $quantityOfIntegratedProductsVia ?></h2>
                    </div>
                </td>
                <td>
                    <div class="inner">
                        <h2><?= $quantityOfIntegratedProductsCar ?></h2>
                    </div>
                </td>
                <td>
                    <div class="inner">
                        <h2><?= $quantityOfIntegratedProductsMl ?></h2>
                    </div>
                </td>
            </tbody>
            <tbody>
                <td>Não aceito no marketplace</td>
                <td>
                    <div class="inner">
                        <h2><?= $quantityOfErrorsTransformationProductsB2W ?></h2>
                    </div>
                </td>
                <td>
                    <div class="inner">
                        <h2><?= $quantityOfErrorsTransformationProductsVia ?></h2>
                    </div>
                </td>
                <td>
                    <div class="inner">
                        <h2><?= $quantityOfErrorsTransformationProductsCar ?></h2>
                    </div>
                </td>
                <td>
                    <div class="inner">
                        <h2><?= $quantityOfErrorsTransformationProductsMl ?></h2>
                    </div>
                </td>
            </tbody>
            <tbody>
                <td>Publicados</td>
                <td>
                    <div class="inner">
                        <h2><?= $quantityOfPublichedProductsB2B ?></h2>
                    </div>
                </td>
                <td>
                    <div class="inner">
                        <h2><?= $quantityOfPublichedProductsVia ?></h2>
                    </div>
                </td>
                <td>
                    <div class="inner">
                        <h2><?= $quantityOfPublichedProductsCar ?></h2>
                    </div>
                </td>
                <td>
                    <div class="inner">
                        <h2><?= $quantityOfPublichedProductsMl ?></h2>
                    </div>
                </td>
            </tbody>
        </table>
    </section>
</div>

<script type="text/javascript">

const openBoxFilter = (type, filter) => {
    var url = "<?=base_url()?>" + type + '/filtered';
    var form = $(`<form action="${url}" method="post" role="form">
                    <input type="hidden" name="do_filter" value="" />
                    <input type="text" name="${filter}" value="true" />
                  </form>`);
    $('body').append(form);
    form.submit();
}


</script>