<!--
SW Serviços de Informática 2019

Index de Fornecedores

-->

<!-- Content Wrapper. Contains page content -->



<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">

            <div class="col-md-12 col-xs-12" id="rowcol12">

                <div class="box">

                    <div class="box-header with-border bg-light-blue disabled color-palette"" style="
                        text-align:center;">
                        <h3 class="box-title">Preenchimento simplificado da tabela de frete</h3>
                    </div>

                    <div class="box-body">
                        <br>

                        <?php if ($this->session->flashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?=$this->session->flashdata('success');?>
                        </div>
                        <?php elseif ($this->session->flashdata('error')): ?>
                        <div class="alert alert-error alert-dismissible" role="alert">
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <?=$this->session->flashdata('error');?>
                        </div>
                        <?php endif;?>

                        <p>Selecione Regiões do país e informe os dados do seu fete como, prazo de transporte e preço do envio.</p>

                        <div class="alert alert-warning alert-dismissible">
                            <button type="button" type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h4><i class="icon fa fa-warning"></i> Importante!</h4>
                            Se você optar por não realizar entregas em algum reginão do país, seus itens podem não
                            aparecer
                            nas campanhas de marketing de nossas marcas.
                        </div>

                        <form role="form" id="form_TableShipping" action="<?=base_url("shippingcompany/updatetableshippingsimplified/$shipping_company_id")?>" method="post">
                            <div class="col-md-12">
                                <table class="table" style="border-collapse:collapse;">
                                    <thead>
                                        <tr>
                                            <th class="col-md-3 text-center">Destino</th>
                                            <th class="col-md-2 col-md-offset-1 text-center">Prazo de Transporte</th>
                                            <th class="col-md-2 col-md-offset-1 text-center">Preço de Envio</th>
                                            <th class="col-md-1"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data_form_regions as $region_name => $data_form_region): ?>
                                        <tr id="<?=$region_name?>"
                                            class="color-palette">
                                            <td class="text-center" style="color:#0066CC;"><?=ucfirst($region_name)?></td>

                                            <td class="text-center">
                                                <div class="input-group">
                                                    <span class="input-group-addon"><i class="fa  fa-calendar-check-o"></i></span>
                                                    <input type="number" min="1" name="<?=$region_name?>_qtd_dias" value="<?=$dataRegion[$region_name]["qtd_dias"] ?? ''?>" class="form-control" placeholder="0">
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="input-group">
                                                    <span class="input-group-addon">R$</span>
                                                    <input type="text" name="<?=$region_name?>_valor" value="<?=$dataRegion[$region_name]["valor"] ?? ''?>" class="form-control preco_envio maskdecimal2" placeholder="0,00">
                                                </div>
                                            </td>
                                            <td class="text-success pull-right"><button type="button" disabled class="btn-xs btn-primary accordion-toggle" data-toggle="collapse" data-target=".<?=$region_name?>"><i class="fa fa-plus"></i></button></td>
                                        </tr>

                                        <?php foreach ($data_form_region as $state_uf => $state_name): ?>
                                        <tr>
                                            <td colspan="6" class="hiddenRow" style="padding:0">
                                                <div class="<?=$region_name?> accordian-body collapse">
                                                    <div class="col-md-10 col-md-offset-1 accordian-body collapse <?=$region_name?>" style="padding-top:1%;">
                                                        <div class="box-header with-border bg-gray color-palette" style="text-align:center;">
                                                            <h3 class="box-title pull-left">
                                                                &nbsp;<i class='fa fa-circle' style='color:<?=$dataRegion[$state_uf]["icon"] ?? 'gray'?>; font-size:0.6em;'></i>&nbsp;<?=$state_name?>
                                                            </h3>
                                                            <span>
                                                                <button type="button" class="btn-xs btn-danger  pull-right" id="btn-capital-interior-<?=$state_uf?>">
                                                                    <i class="fa fa-plus"></i>
                                                                </button>
                                                            </span>
                                                        </div>
                                                        <br>
                                                        <div id="capital-interior-<?=$state_uf?>">
                                                            <div class="col-md-12">
                                                                <div class="col-md-3">
                                                                    Capital
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="input-group">
                                                                        <span class="input-group-addon"><i class="fa  fa-calendar-check-o"></i></span>
                                                                        <input type="number" min="1" class="form-control" name="<?=$state_uf?>_capital_qtd_dias" value="<?=$dataRegion[$state_uf]["capital_qtd_dias"] ?? ''?>"placeholder="0">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="input-group">
                                                                        <span class="input-group-addon">R$</span>
                                                                        <input type="text" class="form-control preco_envio maskdecimal2" name="<?=$state_uf?>_capital_valor" value="<?=$dataRegion[$state_uf]["capital_valor"] ?? ''?>" placeholder="0,00">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <br><br>
                                                            <div class="col-md-12">
                                                                <div class="col-md-3">
                                                                    Interior
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="input-group">
                                                                            <span class="input-group-addon"><i class="fa  fa-calendar-check-o"></i></span>
                                                                        <input type="number" min="1" class="form-control" name="<?=$state_uf?>_interior_qtd_dias" value="<?=$dataRegion[$state_uf]["interior_qtd_dias"] ?? ''?>" placeholder="0">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="input-group">
                                                                        <span class="input-group-addon">R$</span>
                                                                        <input type="text" class="form-control preco_envio maskdecimal2" name="<?=$state_uf?>_interior_valor" value="<?=$dataRegion[$state_uf]["interior_valor"] ?? ''?>" placeholder="0,00">
                                                                    </div>
                                                                    <br>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endforeach; ?>

                                    </tbody>
                                </table>
                                <button type="submit"
                                    class="btn btn-primary"><?= $this->lang->line('application_save'); ?></button>
                                <input type="hidden" name="store_id" value="<?=$store_id?>">
                                <input type="hidden" name="freight_seller" value="<?=$freight_seller?>">
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="alertSimplifiedShipping" tabindex="-1" role="dialog"
                aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-light-blue disabled color-palette">
                            <button type="button" type="button" class="close" data-dismiss="modal"
                                aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="myModalLabel">Atenção!</h4>
                        </div>
                        <div class="modal-body" style="text-align:justify;">
                            Identificamos que você já tem uma planilha de frete cadastrada. O envio da planilha
                            de frete simplificada implica na <b>substituição</b> da planilha de frete atual.
                        </div>
                        <div class="modal-footer">
                            <button type="button" type="button" class="btn btn-default" id="cancelModal"
                                data-dismiss="modal">Cancelar</button>
                            <button type="button" type="button" class="btn btn-primary"
                                id="continueModal">Continuar</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
</div>
</section>
</div>

<script src="<?php echo base_url('assets/bower_components/inputmask/dist/jquery.inputmask.bundle.js') ?>"></script>
<script type="text/javascript">
$(document).ready(function() {

    $("#mainLogisticsNav").addClass('active');
    $("#carrierRegistrationNav").addClass('active');
    $(".text-center").on("click", function(e) {
        e.preventDefault();
        return false;
    });

    $('#norte, #centro-oeste, #sul').addClass('bg-gray');

    // remove alert info success or error
    setTimeout(function(){ 
        var msg = document.getElementsByClassName("alert alert-success alert-dismissible");
        var msg2 = document.getElementsByClassName("alert alert-error alert-dismissible");
        while(msg.length > 0 || msg2.length > 0 ){
            if(msg){
                msg[0].parentNode.removeChild(msg[0]);
            }else if(msg2){
                msg2[0].parentNode.removeChild(msg[2]);
            }
        }
    }, 5000);

    const shipping_company_id = <?php echo $shipping_company_id; ?>

    typeTableShipping(shipping_company_id);

    $('#capital-interior-ac, #capital-interior-al, #capital-interior-ap, #capital-interior-am, #capital-interior-ba, #capital-interior-ce, #capital-interior-df, #capital-interior-es, #capital-interior-go, #capital-interior-ma, #capital-interior-mt, #capital-interior-ms, #capital-interior-mg, #capital-interior-pa, #capital-interior-pb, #capital-interior-pr, #capital-interior-pe, #capital-interior-pi, #capital-interior-rj, #capital-interior-rn, #capital-interior-rs, #capital-interior-ro, #capital-interior-rr, #capital-interior-sc, #capital-interior-sp, #capital-interior-se').hide();

    $('#norte').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
    });
    $('#sul').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
    });
    $('#sudeste').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
    });
    $('#centro-oeste').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
    });
    $('#nordeste').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
    });

    $('#btn-capital-interior-ac').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-ac').toggle();
    });

    $('#btn-capital-interior-al').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-al').toggle();
    });

    $('#btn-capital-interior-ap').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-ap').toggle();
    });

    $('#btn-capital-interior-am').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-am').toggle();
    });

    $('#btn-capital-interior-ba').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-ba').toggle();
    });

    $('#btn-capital-interior-ce').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-ce').toggle();
    });
    $('#btn-capital-interior-df').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-df').toggle();
    });
    $('#btn-capital-interior-es').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-es').toggle();
    });
    $('#btn-capital-interior-go').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-go').toggle();
    });
    $('#btn-capital-interior-ma').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-ma').toggle();
    });
    $('#btn-capital-interior-mt').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-mt').toggle();
    });

    $('#btn-capital-interior-ms').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-ms').toggle();
    });
    $('#btn-capital-interior-mg').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-mg').toggle();
    });

    $('#btn-capital-interior-pa').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-pa').toggle();
    });

    $('#btn-capital-interior-pb').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-pb').toggle();
    });
    $('#btn-capital-interior-pr').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-pr').toggle();
    });
    $('#btn-capital-interior-pe').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-pe').toggle();
    });
    $('#btn-capital-interior-pi').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-pi').toggle();
    });
    $('#btn-capital-interior-rj').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-rj').toggle();
    });
    $('#btn-capital-interior-rn').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-rn').toggle();
    });

    $('#btn-capital-interior-rs').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-rs').toggle();
    });

    $('#btn-capital-interior-ro').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-ro').toggle();
    });
    $('#btn-capital-interior-rr').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-rr').toggle();
    });
    $('#btn-capital-interior-sc').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-sc').toggle();
    });

    $('#btn-capital-interior-sp').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-sp').toggle();
    });
    $('#btn-capital-interior-se').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-se').toggle();
    });
    $('#btn-capital-interior-to').click(function() {
        $("i", this).toggleClass("fa fa-plus fa fa-minus");
        $('#capital-interior-to').toggle();
    });

    $('.maskdecimal2').mask('000.000.000.000.000,00', {reverse: true});

});



var manageTable;
var base_url = "<?= base_url(); ?>";

function typeTableShipping(idTransportadora) {
    $.ajax({
        url: base_url + "shippingcompany/typeTableShipping",
        type: "POST",
        data: {
            id_transportadora: idTransportadora,
        },
        async: true,
        success: function(response) {
            var obj = JSON.parse(response);
            if (obj.id_type == 1) {
                $('#alertSimplifiedShipping').modal('show');

                $('#continueModal').click(function() {
                    $('#alertSimplifiedShipping').modal('hide');
                });

                $('#cancelModal').click(function() {
                    window.location.href = base_url + "shippingcompany/";
                });
                // $('#closeModal').click(function() {
                //      window.location.href = base_url+"shippingcompany/";
                // });

            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR, textStatus, errorThrown);
        }
    });
}


// function priceRegionShipping(idTransportadora){
//     $.ajax({
//         url: base_url+"shippingcompany/priceRegionShipping",
//     	type: "POST",
//         data: {
//             idTransportadora: idTransportadora,
//         },
//         async: true,
//         success: function(response) {
//             var obj = JSON.parse(response);
//             console.log(obj);
//             if(response){

//             }
//         },
//         error: function(jqXHR, textStatus, errorThrown) {
//             console.log(jqXHR,textStatus, errorThrown);
//         }
//     });
// }
// priceRegionShipping
</script>