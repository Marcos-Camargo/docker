<!--
SW Serviços de Informática 2019

Listar Pedidos

Obs:
cada usuario so pode ver pedidos da sua empresa.
Agencias podem ver todos os pedidos das suas empresas
Admin pode ver todas as empresas e agencias

-->

<!-- Content Wrapper. Contains page content --> 
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_order_in_progress";  $this->load->view('templates/content_header',$data); ?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div id="messages"></div>
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
                    <div class="box-body"> 
                            <div class="row select-table-filter-inside"> 
                                <div class="col-md-3">
                                <?php if($sellercenter_name != 'RaiaDrogasil'): ?>
                                    <select class="form-control input-sm col-md-3 col-sm-3 col-sx-12  selectpicker" name="selectMarketplace" id="selectMarketplace">
                                        <option value="">Filtre por um MarketPlace (Limpar)</option>
                                        <option value="ML">Mercado Livre</option>
                                        <option value="B2W">B2W</option>
                                        <option value="VIA">Via Varejo</option>
                                        <option value="CAR">Carrefour</option>
                                    </select>
                                <?php endif ?>
                                </div>
                                <!--                            <div class="col-md-3">-->
<!--                                <select class="form-control input-sm col-md-3 col-sm-3 col-sx-12 selectpicker" name="selectOcorrencia" id="selectOcorrencia">-->
<!--                                    <option value="">Filtre por um Status (Limpar)</option>-->
<!--                                    --><?php
//                                    foreach($ocorrencias as $ocorrencia) {
//                                        echo "<option value='{$ocorrencia['nome']}'>{$ocorrencia['nome']}</option>";
//                                    }
//                                    ?>
<!--                                </select>-->
<!--                            </div>-->
                            </div>
                        <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                            <thead>
                            <tr>
                                <th><?=$this->lang->line('application_order');?></th>
                                <th><?=$this->lang->line('application_client');?></th>
                                <th><?=$this->lang->line('application_product');?></th>
                                <th><?=$this->lang->line('application_tracking');?></th>
                                <th><?=$this->lang->line('application_ship_company');?></th>
                                <th><?=$this->lang->line('application_pi_opened');?></th>
                                <th><?=$this->lang->line('application_marketplace');?></th>
                                <th><?=$this->lang->line('application_order_marketplace');?></th>
                                <th><?=$this->lang->line('application_delivery_forecast');?></th>


                                <?php if(in_array('doIntegration', $user_permission)): ?>
                                    <th><?=$this->lang->line('application_action');?></th>
                                <?php endif; ?>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php if(in_array('doIntegration', $user_permission)): ?>
<div class="modal fade" tabindex="-1" role="dialog" id="edit-tracking-modal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Atualização de Rastreio do Pedido</h4>
            </div>
            <div class="modal-body update-code-traking">

            </div>
            <div class="modal-body no-padding">
                <hr class="no-margin">
            </div>
            <div class="modal-body observation">
                <div class="row">
                    <div class="col-md-12 form-group">
                        <div class="list-observation mt-5"></div>
                    </div>
                </div>
                <form role="form" action="<?=base_url('orders/createCommentOrderInProgress') ?>" method="post">
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label>Adicionar Novo Comentário</label>
                            <textarea class="form-control" name="comment" required></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 form-group d-flex justify-content-end">
                            <input type="submit" class="btn btn-success" value="Enviar Comentário">
                        </div>
                    </div>
                    <input type="hidden" name="order_id_comment" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<style>

    @media (min-width: 768px) {
        #manageTable_wrapper .row:first-of-type {
            display: flex;
            justify-content: space-between;
            width: 100%;
            flex-wrap: wrap;
        }

        #manageTable_wrapper .row:first-of-type .col-sm-6 {
            width: 25%;
        }

        .dropdown.bootstrap-select {
            z-index: 2;
        }

        .row:before, .row:after {
            display: none;
        }
    }
    .paginate_button.active a {
        z-index: 0;
    }
</style>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.5/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.5/css/responsive.dataTables.min.css"/>
<script type="text/javascript">
    var manageTable;
    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function() {

        $("#mainProcessesNav").addClass('active');
        $("#manageOrdersInProgressNav").addClass('active');

        manageTable = $('#manageTable').DataTable( {
            "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "sortable": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline( {
                url: base_url + 'orders/fetchOrdersInProgress',
                data: {
                    marketplace: $('#selectMarketplace').val(),
                    //ocorrencia: $('#selectOcorrencia').val()
                },
                pages: 2 // number of pages to cache
            } )
        } );

    });

    $(document).on('click', '.edit-tracking', function () {
        const order = $(this).attr('order-id');
        const url = "<?=base_url('orders/getDataTrackingOrderInProgress')?>";

        $.ajax({
            url,
            type: "POST",
            data: { order },
            dataType: 'json',
            success: response => {

                let comment = null;
                let history_tracking = "";
                let disable = '';
                let temRastreio = true;

                $('[name="order_id_comment"]').val(order);
                $('#edit-tracking-modal .update-code-traking').empty();
                $.each(response, function( index, value ) {
                    comment = value.comments_adm;
                    if(!value.codigo_rastreio) {
                        temRastreio = false;
                        return false;
                    }
                    history_tracking = "";
                    if(value.history_update != null) {
                        $.each(value.history_update, function( index, value ) {
                            history_tracking += `<small>codigo anterior: ${value.codigo_anterior} - codigo novo: ${value.codigo_novo} - usuário que alterou: ${value.user_name} em ${value.date}</small><br>`;
                        });
                    }

                    $('#edit-tracking-modal .update-code-traking').append(`
                    <div class="row d-flex justify-content-center flex-wrap">
                        <div class="col-md-6">
                            <form role="form" action="${value.url_post}" method="post" >
                                <label>Alterar Código de Rastreio (<i>${value.codigo_rastreio}</i>)</label>
                                <div class="input-group ">
                                    <input type="text" class="form-control" name="code-tracking-new" autocomplete="off" required>
                                    <span class="input-group-btn">
                                        <button type="submit" class="btn btn-success btn-flat" ${disable}>Atualizar Código</button>
                                        <input type="hidden" class="form-control" name="code-tracking-real" value="${value.codigo_rastreio}">
                                        <input type="hidden" class="form-control" name="order-id" value="${value.order_id}">
                                    </span>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-center form-group">
                            ${history_tracking}
                        </div>
                    </div>
                    `);
                });
                if(!temRastreio) {
                    $('#edit-tracking-modal .update-code-traking').append('<h4 class="text-center">Pedido ainda sem rastreio!</h4>');
                }
                $('.observation .list-observation').empty();
                if(comment.length > 0) {
                    $('.observation .list-observation').append('<h4 class="text-center">Histórico de Comentário</h4> <ul class="mt-3"></ul>')
                    $.each(comment, function( index, value ) {
                        $('.observation .list-observation ul').append(`<li>${value.comment} - <strong>${value.user_name}</strong> - ${value.date}</li>`)
                    });
                }

                $('#edit-tracking-modal').modal();
            }, error: error => {
                console.log(error);
            }
        });
    })

    $('#selectMarketplace, #selectOcorrencia').on('change', function () {
        manageTable.destroy();

        manageTable =
            $('#manageTable').DataTable( {
                "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang' },
                "processing": true,
                "serverSide": true,
                "scrollX": true,
                "sortable": true,
                "serverMethod": "post",
                "ajax": $.fn.dataTable.pipeline( {
                    url: base_url + 'orders/fetchOrdersInProgress',
                    data: {
                        marketplace: $('#selectMarketplace').val(),
                        //ocorrencia: $('#selectOcorrencia').val()
                    },
                    pages: 2 // number of pages to cache
                } )
            } );
    })

</script>
