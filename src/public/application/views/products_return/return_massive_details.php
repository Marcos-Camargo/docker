<div class="content-wrapper">

	<?php
	$data['pageinfo'] = "application_manage";
	$this->load->view('templates/content_header', $data);
	?>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box box-info mt-2">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?= lang('application_massive_order_refund_details'); ?></h3>
                    </div>
                    <div class="box-body">

                        <?php
                        if ($entry['errors']['general']){
                            ?>
                            <div class="alert alert-error alert-dismissible" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                                            aria-hidden="true">&times;</span></button>
                                <?=$entry['errors']['general']?>
                            </div>
                            <?php
                        }
                        ?>

                        <?php
                        if ($entry['errors']['itens']){
                        ?>
                            <a href="<?php echo base_url('ProductsReturn/returnMassiveDownloadErrors/'.$entry['id']); ?>" class="btn btn-primary mb-3">Baixar Arquivo com Erros</a>

                            <table class="table table-bordered table-condensed table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            Pedido
                                        </th>
                                        <th>
                                            Erro
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($entry['errors']['itens'] as $order_id => $message){
                                    ?>
                                        <tr>
                                            <td><?=$order_id;?></td>
                                            <td><?=$message;?></td>
                                        </tr>
                                    <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        <?php
                        }
                        ?>
                    </div>
                    <!-- /.box-body -->
                </div>

                <a href="<?php echo base_url('ProductsReturn/returnMassive'); ?>" class="btn btn-info">Voltar para Lista de Importações</a>

            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script type="text/javascript">

    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function () {

        $("#mainOrdersNav").addClass('active');
        $("#ReturnOrderNavMassive").addClass('active');

        filter();

    });


</script>