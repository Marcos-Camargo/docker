<!--
SW Serviços de Informática 2019

Acompanhar Creditos em conta

-->


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_extract_screen";
    $this->load->view('templates/content_header', $data);
    if ($gsoma == '1' || $nmundo == '1' || $ortobom == "1" || $sellercenter == "1") {
        $seller_or_marketplace = $this->lang->line('application_seller');
    } else {
        $seller_or_marketplace = $this->lang->line('application_marketplace');
    }
    ?>

    <!-- Main content -->
    <section class="content">
        <?php if ($perfil == 1 && $gsoma == "2" && $nmundo == "2" && $sellercenter == "2")  { ?>
            <div class="row">
                <div class="col-md-12 col-xs-12">
                    <div class="box">
                        <div class="box-header">
                            <h3 class="box-title"><?= $this->lang->line('application_resume_extract'); ?> - Valores na
                                conta</h3>
                        </div>
                        <div class="box-body">
                            <table class="col-md-12 col-xs-12" id=resumoExtrato">
                                <tr>
                                    <td><h5><b> Saldo da Conta: </b> </h3></td>
                                    <td><h5><b> Saldo disponível para saque: </b> </h3></td>
                                    <td><h5><b> Saldo a receber: </b> </h3></td>
                                    <td><h5><b> Recebimentos esse mês: </b> </h3></td>
                                    <td><h5><b> Recebimentos esse mês anterior: </b> </h3></td>
                                    <td><h5><b> </b> </h3></td>
                                </tr>
                                <?php if ($dataws[0] == "0") { ?>
                                    <tr>
                                        <td><h5><b> <?php echo $dataws[1]['balance'] ?> </b> </h3></td>
                                        <td><h5>
                                                <b> <?php echo $dataws[1]['balance_available_for_withdraw'] ?> </b> </h3>
                                        </td>
                                        <td><h5><b> <?php echo $dataws[1]['receivable_balance'] ?> </b> </h3></td>
                                        <td><h5><b> <?php echo $dataws[1]['volume_this_month'] ?> </b> </h3></td>
                                        <td><h5><b> <?php echo $dataws[1]['volume_last_month'] ?> </b> </h3></td>
                                        <td>
                                            <button type="button" class="btn btn-block btn-success btn-flat"
                                                    id="btnSacar" data-toggle="modal" data-target="#saqueModal">
                                                Solicitar Saque
                                            </button>
                                        </td>
                                    </tr>
                                <?php } else { ?>
                                    <tr>
                                        <td><h5><b> R$ 0,00 </b> </h3></td>
                                        <td><h5><b> R$ 0,00 </b> </h3></td>
                                        <td><h5><b> R$ 0,00 </b> </h3></td>
                                        <td><h5><b> R$ 0,00 </b> </h3></td>
                                        <td><h5><b> R$ 0,00 </b> </h3></td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
        <form id="formFiltro">
            <div class="row">
                <div class="box">
                    <div class="box-body">
                        <div class="box-header">
                            <h3 class="box-title"><?= $this->lang->line('application_set_filters'); ?></h3>
                        </div>
                        <div class="form-group col-md-3 col-xs-3">
                            <label for="group_isadmin"><?= $this->lang->line('application_status'); ?></label>
                            <select class="form-control" multiple id="slc_status_form" name="slc_status_form">
                                <?php foreach ($filtrosts as $statu): ?>
                                    <option value="<?php echo trim($statu['id']); ?>"><?php echo trim($statu['status']); ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>

                        <div class="col-md-2 col-xs-2 hide">
                            <label for="group_isadmin"><?= $this->lang->line('application_search'); ?></label>
                            <input id="search" name="search" class="form-control"/>
                        </div>

                        <div class="form-group col-md-2 col-xs-2">
                            <label for="group_isadmin"><?= $this->lang->line('application_runmarketplaces'); ?></label>
                            <select class="form-control" id="slc_mktplace" name="slc_mktplace">
                                <option value="">~~SELECT~~</option>
                                <?php foreach ($mktplaces as $mktPlaces): ?>
                                    <option value="<?php echo trim($mktPlaces['apelido']); ?>"><?php echo trim($mktPlaces['mkt_place']); ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>

                        <div class="form-group col-md-2 col-xs-2">
                            <label for="txt_id_pedido"><?= $this->lang->line('application_purchase_id'); ?></label>
                            <input type="text" class="form-control" id="txt_id_pedido" name="txt_id_pedido" required placeholder="<?= $this->lang->line('application_purchase_id') ?>">
                        </div>

                        <div class="form-group col-md-2 col-xs-2">
                            <label for="cnpj">Data do Pedido - Início</label>
                            <input type="date" class="form-control" id="txt_data_inicio" name="txt_data_inicio" required placeholder="<?= $this->lang->line('application_start_date') ?>">
                        </div>
                        <div class="form-group col-md-2 col-xs-2">
                            <label for="cnpj">Data do Pedido - Fim</label>
                            <input type="date" class="form-control" id="txt_data_fim" name="txt_data_fim" required
                                   placeholder="<?= $this->lang->line('application_end_date') ?>">
                        </div>

                        <div class="form-group col-md-2 col-xs-2">
                            <label for="group_isadmin"><?= $this->lang->line('application_store'); ?></label>
                            <select class="form-control" id="slc_loja" name="slc_loja">
                                <option value="">~~SELECT~~</option>
                                <?php foreach ($filtrostore as $loja): ?>
                                    <option value="<?php echo trim($loja['id']); ?>"><?php echo trim($loja['name']); ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-2 col-xs-2">
                            <label for="group_isadmin">Pedidos Antecipados</label>
                            <select class="form-control" id="slc_antecipado" name="slc_antecipado">
                                <option value="">~~SELECT~~</option>                                
                                <option value="SIM">SIM</option>                                
                                <option value="NAO">NÃO</option>  
                            </select>
                        </div>
                       
                        <!-- <div class="form-group col-md-2 col-xs-2"> -->
                            <!-- Espaço para organização de tela -->
                        <!-- </div> -->                        

                        <div class="form-group col-md-2 col-xs-2">
                            <label for="cnpj">Data de Pagamento - Início</label>
                            <input type="date" class="form-control" id="txt_data_inicio_repasse" name="txt_data_inicio_repasse" required
                                   placeholder="<?= $this->lang->line('application_start_date') ?>">
                        </div>
                        <div class="form-group col-md-2 col-xs-2">
                            <label for="cnpj">Data de Pagamento - Fim</label>
                            <input type="date" class="form-control" id="txt_data_fim_repasse" name="txt_data_fim_repasse" required
                                   placeholder="<?= $this->lang->line('application_end_date') ?>">
                        </div>

                        <div class="col-md-12 col-xs-12"><br>
                            <button type="button" id="btnFilter" name="btnFilter" class="btn btn-primary"><?= $this->lang->line('application_filter'); ?></button>
                            <?php if ($gsoma == "1") { ?>
                                <button type="button" id="btnExcel" name="btnExcel" class="btn btn-success">Excel - Pedidos</button>
                                <button type="button" id="btnExcelLoja" name="btnExcelLoja" class="btn btn-success">Excel - Consolidado Loja</button>
                                <button type="button" id="btnExcelLojaMarca" name="btnExcelLojaMarca" class="btn btn-success">Excel - Consolidado Loja x Marca</button>
                            <?php }elseif($nmundo == "1"){?>
                                <button type="button" id="btnExcel" name="btnExcel" class="btn btn-success">Exportar</button>
                            <?php }else{ ?>
                                <button type="button" id="btnExcel" name="btnExcel" class="btn btn-success">Excel</button>
                            <?php } ?>

                            <?php
                            if (in_array('externalAntecipation', $user_permission)){
                            ?>
                                <button type="button" id="btnExternalAntecipation" name="btnExternalAntecipation" class="btn btn-success" disabled="disabled">PDF - <?=lang('application_external_antecipation');?></button>
                            <?php
                            }
                            ?>

                            <button type="button" id="btnExcellExtrato" name="btnExcellExtrato" class="btn btn-success">Excel - Extrato</button>

                        </div>
                        <div class="col-md-2 col-xs-2"><br>
                            
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><?= $this->lang->line('application_resume_extract'); ?> - Valores na
                            conta</h3>
                    </div>
                    <div class="box-body">
                        <table id="manageTableResumo" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th><?= $seller_or_marketplace; ?></th>
                                <th><?= $this->lang->line('application_date'); ?> <?= $this->lang->line('application_bank_transfer'); ?></th>
                                <th><?= $this->lang->line('application_value'); ?></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-md-12 col-xs-12">

                <div id="messages"></div>

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

                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><?= $this->lang->line('application_extract_orders'); ?> </h3> - Veja todos os pedidos pagos ou em processo.
                    </div>
                    <div class="box-body">
                        <table id="manageTable" class="table table-bordered table-striped">
                            <thead>

                                <?php if ($gsoma == "1") { ?>
                                <tr>
                                    <th><?= $this->lang->line('application_id'); ?> - <?= $this->lang->line('application_order'); ?></th>
                                    <th><?= $this->lang->line('application_status'); ?></th>
                                    <th><?= $this->lang->line('application_date'); ?> <?= $this->lang->line('application_order'); ?></th>
                                    <th><?= $this->lang->line('application_delivered_date'); ?></th>
                                    <th><?= $this->lang->line('application_payment_date'); ?></th>
                                    <?php if ($data_transferencia_gateway_extrato == "1"){ ?>
                                        <th>Data Transferência Bancária Gateway</th>
                                    <?php } ?>
                                    
                                    <th>Repasse Antecipado</th>                                    

                                    <?php
                                    if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                                    ?>
                                        <th><?= $this->lang->line('application_parcels'); ?></th>
                                        <th><?= $this->lang->line('application_cicle_total_transfer'); ?></th>
                                        <th><?= $this->lang->line('application_value_paid'); ?></th>
                                    <?php
                                    }
                                    ?>
                                    <?php if ($show_new_columns_fin_46 == "1"){ ?>
                                        <th>Tipo de Frete</th>
                                        <?php if ($show_new_columns_fin_46_temp == "1"){ ?>
                                            <th>Valor Comissão</th>
                                            <th>Expectativa de recebimento</th>
                                        <?php } ?>
                                        <th>Valor recebido</th>
                                    <?php } ?>

                                    <th><?= $this->lang->line('application_order_2'); ?></th>
                                    <th><?= $this->lang->line('application_receipt_expectation'); ?></th>
                                    <th><?= $this->lang->line('application_discount'); ?></th>
                                    <th><?= $this->lang->line('application_commission'); ?></th>
                                    <?php if ($valor_pagamento_gateway_extrato == "1"){ ?>
                                        <th>Valor Real Repasse</th>
                                    <?php } ?>
                                    <th><?= $this->lang->line('application_extract_obs'); ?></th>
                                    <th><?= $this->lang->line('application_store'); ?></th>
                                    <th>Data de Envio</th>
                                </tr>
                                <?php } elseif ($nmundo == "1") { ?>
                                <tr>
                                    <th><?= $this->lang->line('application_store'); ?></th>
                                    <th><?= $this->lang->line('application_status'); ?></th>
                                    <th><?= $this->lang->line('application_date'); ?> <?= $this->lang->line('application_order'); ?></th>
                                    <th><?= $this->lang->line('application_delivered_date'); ?></th>
                                    <th>Data de Envio</th>
                                    <th><?= $this->lang->line('application_payment_date'); ?></th>
                                    <?php if ($data_transferencia_gateway_extrato == "1"){ ?>
                                        <th>Data Transferência Bancária Gateway</th>
                                    <?php } ?>
                                    
                                    <th>Repasse Antecipado</th>                                    

                                    <?php
                                    if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                                        ?>
                                        <th><?= $this->lang->line('application_parcels'); ?></th>
                                        <th><?= $this->lang->line('application_cicle_total_transfer'); ?></th>
                                        <th><?= $this->lang->line('application_value_paid'); ?></th>
                                        <?php
                                    }
                                    ?>

                                    <?php if ($show_new_columns_fin_46 == "1"){ ?>
                                        <th>Tipo de Frete</th>
                                        <?php if ($show_new_columns_fin_46_temp == "1"){ ?>
                                            <th>Valor Comissão</th>
                                            <th>Expectativa de recebimento</th>
                                        <?php } ?>
                                        <th>Valor recebido</th>
                                    <?php } ?>

                                    <th><?= $this->lang->line('application_order_2'); ?></th>
                                    <th><?= $this->lang->line('application_receipt_expectation'); ?></th>
                                    <th><?= $this->lang->line('application_discount'); ?></th>
                                    <th><?= $this->lang->line('application_commission'); ?></th>
                                    <th><?= $this->lang->line('application_extract_obs'); ?></th>
                                    <?php if ($valor_pagamento_gateway_extrato == "1"){ ?>
                                       <th>Valor Real Repasse</th>
                                    <?php } ?>
                                </tr>
                                <?php } elseif ($ortobom == "1") { ?>
                                <tr>
                                    <th><?= $this->lang->line('application_id'); ?> - <?= $this->lang->line('application_order'); ?></th>
                                    <th><?= $this->lang->line('application_store'); ?></th>
                                    <th><?= $this->lang->line('application_status'); ?></th>
                                    <th><?= $this->lang->line('application_date'); ?> <?= $this->lang->line('application_order'); ?></th>
                                    <th><?= $this->lang->line('application_delivered_date'); ?></th>
                                    <th>Data de Envio</th>
                                    <th><?= $this->lang->line('application_payment_date'); ?></th>
                                    <?php if ($data_transferencia_gateway_extrato == "1"){ ?>
                                       <th>Data Transferência Bancária Gateway</th>
                                    <?php } ?>
                                    
                                    <th>Repasse Antecipado</th>                                    

                                    <?php
                                    if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                                        ?>
                                        <th><?= $this->lang->line('application_parcels'); ?></th>
                                        <th><?= $this->lang->line('application_cicle_total_transfer'); ?></th>
                                        <th><?= $this->lang->line('application_value_paid'); ?></th>
                                        <?php
                                    }
                                    ?>

                                    <?php if ($show_new_columns_fin_46 == "1"){ ?>
                                        <th>Tipo de Frete</th>
                                        <?php if ($show_new_columns_fin_46_temp == "1"){ ?>
                                            <th>Valor Comissão</th>
                                            <th>Expectativa de recebimento</th>
                                        <?php } ?>
                                        <th>Valor recebido</th>
                                    <?php } ?>

                                    <th><?= $this->lang->line('application_order_2'); ?></th>
                                    <th><?= $this->lang->line('application_receipt_expectation'); ?></th>
                                    <th><?= $this->lang->line('application_discount'); ?></th>
                                    <th><?= $this->lang->line('application_commission'); ?></th>
                                    <th><?= $this->lang->line('application_extract_obs'); ?></th>
                                    <?php if ($valor_pagamento_gateway_extrato == "1"){ ?>
                                        <th>Valor Real Repasse</th>
                                    <?php } ?>
                                </tr>
                                <?php } elseif ($sellercenter == "1") { ?>
                                <tr>
                                    <th><?= $this->lang->line('application_id'); ?> - <?= $this->lang->line('application_order'); ?></th>
                                    <th><?= $this->lang->line('application_store'); ?></th>
                                    <th><?= $this->lang->line('application_status'); ?></th>
                                    <th><?= $this->lang->line('application_date'); ?> <?= $this->lang->line('application_order'); ?></th>
                                    <th><?= $this->lang->line('application_delivered_date'); ?></th>
                                    <th>Data de Envio</th>
                                    <th><?= $this->lang->line('application_payment_date'); ?></th>
                                    <?php if ($data_transferencia_gateway_extrato == "1"){ ?>
                                        <th>Data Transferência Bancária Gateway</th>
                                    <?php } ?>
                                    
                                    <th>Repasse Antecipado</th>

                                    <?php
                                    if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                                        ?>
                                        <th><?= $this->lang->line('application_parcels'); ?></th>
                                        <th><?= $this->lang->line('application_cicle_total_transfer'); ?></th>
                                        <th><?= $this->lang->line('application_value_paid'); ?></th>
                                        <?php
                                    }
                                    ?>

                                    <?php if ($show_new_columns_fin_46 == "1"){ ?>
                                        <th>Tipo de Frete</th>
                                        <?php if ($show_new_columns_fin_46_temp == "1"){ ?>
                                            <th>Valor Comissão</th>
                                            <th>Expectativa de recebimento</th>
                                        <?php } ?>
                                        <th>Valor recebido</th>
                                    <?php } ?>

                                    <th><?= $this->lang->line('application_order_2'); ?></th>
                                    <th><?= $this->lang->line('application_receipt_expectation'); ?></th>
                                    <th><?= $this->lang->line('application_discount'); ?></th>
                                    <th><?= $this->lang->line('application_commission'); ?></th>
                                    <th><?= $this->lang->line('application_extract_obs'); ?></th>
                                    <?php if ($valor_pagamento_gateway_extrato == "1"){ ?>
                                        <th>Valor Real Repasse</th>
                                    <?php } ?>
                                </tr>
                                <?php } else { ?>
                                <tr>
                                    <th><?= $this->lang->line('application_id'); ?> - <?= $this->lang->line('application_order'); ?></th>
                                    <th><?= $this->lang->line('application_status'); ?></th>
                                    <th><?= $this->lang->line('application_date'); ?> <?= $this->lang->line('application_order'); ?></th>
                                    <th><?= $this->lang->line('application_delivered_date'); ?></th>
                                    <th><?= $this->lang->line('application_payment_date'); ?> - Marketplace</th>
                                    <?php if($data_transferencia_gateway_extrato == "1"){ ?>
                                        <th>Data Transferência Bancária Gateway</th>
                                    <?php } ?>
                                    
                                    <th>Repasse Antecipado</th>                                    

                                    <?php if($show_date_conectala_in_payment): ?>
                                        <th><?= $this->lang->line('application_payment_date_conecta'); ?></th>
                                    <?php endif; ?>
                                    <?php
                                    if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                                        ?>
                                        <th><?= $this->lang->line('application_parcels'); ?></th>
                                        <th><?= $this->lang->line('application_cicle_total_transfer'); ?></th>
                                        <th><?= $this->lang->line('application_value_paid'); ?></th>
                                        <?php
                                    }
                                    ?>                                    

                                    <?php if ($show_new_columns_fin_46 == "1"){ ?>
                                        <th>Tipo de Frete</th>
                                        <?php if ($show_new_columns_fin_46_temp == "1"){ ?>
                                            <th>Valor Comissão</th>
                                            <th>Expectativa de recebimento</th>
                                        <?php } ?>
                                        <th>Valor recebido</th>
                                    <?php } ?>

                                    <th><?= $this->lang->line('application_order_2'); ?></th>
                                    <th><?= $this->lang->line('application_date'); ?> <?= $this->lang->line('application_bank_transfer'); ?></th>
                                    <th><?= $this->lang->line('application_receipt_expectation'); ?></th>
                                    <th><?= $this->lang->line('application_extract_paid_marketplace'); ?></th>
                                    <th><?= $this->lang->line('application_extract_conciliado'); ?></th>
                                    <?php if ($valor_pagamento_gateway_extrato == "1"){ ?>
                                        <th>Valor Real Repasse</th>
                                    <?php } ?>
                                    <th><?= $this->lang->line('application_extract_obs'); ?></th>
                                    <th>Data de Envio</th>
                                </tr>
                            <?php } ?>
                            </thead>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->


    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="saqueModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Saque</h4>
            </div>
            <form role="form" action="" method="post" id="formSaque">
                <div class="modal-body">
                    <label for="group_name">Valor para saque</label>
                    <input type="number" class="form-control" id="txt_valor_saque" name="txt_valor_saque"
                           placeholder="valor para saque">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                            data-dismiss="modal"><?= $this->lang->line('application_close') ?></button>
                    <button type="button" class="btn btn-success" id="btnSacar" name="btnSalvarObs">Solicitar Saque
                    </button>
                </div>
            </form>


        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<!-- remove brand modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="listaObs">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?= $this->lang->line('application_extract_obs') ?></h4>
            </div>
            <div class="modal-body" id="divListObsFunc">
                Carregando....
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default"
                        data-dismiss="modal"><?= $this->lang->line('application_close') ?></button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<script type="text/javascript">
    var manageTable;
    var manageTableResumo;
    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function () {

        $("#paraMktPlaceNav").addClass('active');
        $("#extratoNav").addClass('active');

        function montaDataTable() {
            if ($('#manageTable').length) {
                $('#manageTable').DataTable().destroy();
            }

            var filtros = $("#formFiltro").serialize();
            slc_status = $('#slc_status_form').val().join(',');
            filtros += '&slc_status='+slc_status;
            //console.log(filtros);

            tabelaProdutos = $('#manageTable').DataTable({
                "language": {"url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"},
                "processing": true,
                "serverSide": true,
                "pageLength": 100,
                "serverMethod": "post",
                'ajax': base_url + 'payment/extratopedidos?' + filtros,
                'searching': false
            });
            

            if ($('#manageTableResumo').length) {
                $('#manageTableResumo').DataTable().destroy();
            }

            //initialize the datatable
            manageTableResumo = $('#manageTableResumo').DataTable({
                "language": {"url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"},
                "processing": true,
                "serverSide": true,
                "lengthMenu": [[3, 10, 25, 50, 100], [3, 10, 25, 50, 100]],
                "pageLength": 3,
                "serverMethod": "post",
                'ajax': base_url + 'payment/extratopedidosresumo?' + filtros,
                'searching': false,
            });
        }

        montaDataTable()

        $("#btnFilter").click(function () {
            montaDataTable();
        });

        $("#btnExcel").click(function () {

            var filtros = $("#formFiltro").serialize();
            slc_status = $('#slc_status_form').val().join(',');
            filtros += '&slc_status='+slc_status;

            var saida = 'payment/extratopedidosexcel?' + filtros;
            window.open(base_url.concat(saida), '_blank');

        });

        $("#btnExcelLoja").click(function () {

            var filtros = $("#formFiltro").serialize();
            slc_status = $('#slc_status_form').val().join(',');
            filtros += '&slc_status='+slc_status;

            var saida = 'payment/extratopedidosexcelconloja?' + filtros;
            window.open(base_url.concat(saida), '_blank');

        });

        $("#btnExcelLojaMarca").click(function () {

            var filtros = $("#formFiltro").serialize();
            slc_status = $('#slc_status_form').val().join(',');
            filtros += '&slc_status='+slc_status;

            var saida = 'payment/extratopedidosexcelconlojamarca?' + filtros;
            window.open(base_url.concat(saida), '_blank');

        });
        $("#slc_loja").change(function () {
            if ($('#slc_loja').val()){
                $('#btnExternalAntecipation').attr('disabled', false);
            }else{
                $('#btnExternalAntecipation').attr('disabled', 'disabled');
            }
        });
        $("#btnExternalAntecipation").click(function () {

            var filtros = $("#formFiltro").serialize();
            slc_status = $('#slc_status_form').val().join(',');
            filtros += '&slc_status='+slc_status;

            var saida = 'payment/externalantecipation?' + filtros;
            window.open(base_url.concat(saida), '_blank');

        });
        $("#btnExcellExtrato").click(function () {

            var filtros = $("#formFiltro").serialize();
            slc_status = $('#slc_status_form').val().join(',');
            filtros += '&slc_status='+slc_status;

            var saida = 'payment/extratopedidos?' + filtros+'&output=excell';
            window.open(base_url.concat(saida), '_blank');

        });

        let executeSearch = true;
        $('#search').on('keyup', () => {
            if (executeSearch) {
                setTimeout(() => {
                    montaDataTable();
                    executeSearch = true;
                }, 500)
            }
            executeSearch = false;
        })


    });

    function listarObservacao(id, lote) {
        if (id) {
            $("#divListObsFunc").html("Carregando...");
            var pageURL = base_url.concat("payment/buscaobservacaopedido");

            $.post(pageURL, {pedido: id, lote: lote}, function (data) {

                var obj = JSON.parse(data);
                var texto = '<table class="table table-bordered table-striped"><tr><td>Pedido</td><td>Observação</td><td>Data Observação</td></tr>';

                Object.keys(obj).forEach(function (k) {
                    texto = texto.concat("<tr><td>", obj[k].num_pedido, "</td><td>", obj[k].observacao, "</td><td>", obj[k].data_criacao, "</td></tr>");
                });

                texto = texto.concat("</table>");
                $("#divListObsFunc").html(texto);
            });
        }
    }

</script>
