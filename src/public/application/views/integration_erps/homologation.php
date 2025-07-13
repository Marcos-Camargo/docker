<div class="content-wrapper">
    <?php $data['pageinfo'] = "application_edit";
    $this->load->view('templates/content_header', $data); ?>
    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <div class="box">
                    <div class="box-body">
                        <div class="row">
                            <?php if ($this->session->flashdata('success')): ?>
                                <div class="alert alert-success alert-dismissible" role="alert">
                                    <button type="button" class="close" data-dismiss="alert"
                                            aria-label="Close"><span aria-hidden="true">&times;</span>
                                    </button>
                                    <?php echo $this->session->flashdata('success'); ?>
                                </div>
                            <?php elseif ($this->session->flashdata('error')): ?>
                                <div class="alert alert-error alert-dismissible" role="alert">
                                    <button type="button" class="close" data-dismiss="alert"
                                            aria-label="Close"><span aria-hidden="true">&times;</span>
                                    </button>
                                    <?php echo $this->session->flashdata('error'); ?>
                                </div>
                            <?php endif; ?>
                                <div class="form-group col-md-12 col-xs-12 text-center">
                                    <label><?= $integration['description'] ?></label><br>
                                    <img src="<?=base_url("assets/images/integration_erps/$integration[image]")?>" alt="<?= $integration['description'] ?>" width="200px">
                                </div>
                                <?php if(empty($success_homologation)): ?>
                                <div class="form-group col-md-12 col-xs-12 d-flex justify-content-center flex-wrap">
                                    <h3 class="col-md-12 text-center font-weight-bold">Siga o passo a passo abaixo para realizar a homologação da integração.</h3>
                                    <h5 class="col-md-12 text-center">Faça o login na conta Bling, onde foi criado o aplicativo.</h5>
                                    <h5 class="col-md-12 text-center">Acesse o aplicativo em <a href="https://www.bling.com.br/cadastro.aplicativos.php#list" target="_blank">Preferências ➡ Cadastros ➡ Cadastro de aplicativos</a>, dentro da conta Bling e acesse o aplicativo.</h5>
                                    <h5 class="col-md-12 text-center text-danger">Revise a configuração das credenciais de <b>Client Id</b> e <b>Client Secret</b>, caso haja divergência, faça a configuração em <a href="<?=base_url("integrations/updateIntegration/$integration[id]")?>" target="_blank">Gestão de Integração de <?=$integration['description']?></a> antes de iniciar a homologação.</h5>
                                    <h5 class="col-md-12 text-center">Dentro do aplicativo, clique em <b>Iniciar revisão</b>, confirme os dados preenchidos conforme a <a href="https://developer.bling.com.br/homologacao#valida%C3%A7%C3%A3o-de-dados">documentação</a> e clique em <b>Confirmar preenchimento dos dados</b>.</h5>
                                    <h5 class="col-md-12 text-center">A próxima etapa é dentro do seller center, clique no botão de <b>Iniciar homologação</b>.</h5>
                                </div>
                                <div class="form-group col-md-12 col-xs-12 d-flex justify-content-center">
                                    <a href="<?= $url_validation_token ?>" class="btn btn-primary col-md-2">Iniciar homologação</a>
                                    <a href="<?= base_url('integrations/manageIntegration') ?>" class="btn btn-default col-md-2 ml-2"><?= lang('application_cancel'); ?></a>
                                </div>
                            <?php else: ?>
                                <h3 class="text-center">Resultado na homologação</h3>

                                <table id="manageTableLog" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th><?=$this->lang->line('application_method');?></th>
                                            <th><?=$this->lang->line('application_credentials_erp_endpoint');?></th>
                                            <th><?=$this->lang->line('application_date');?></th>
                                            <th><?=$this->lang->line('application_status');?></th>
                                            <th>Request</th>
                                            <th>Response</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($success_homologation as $log): ?>
                                            <tr>
                                                <td><?=$log[0]?></td>
                                                <td><?=$log[1]?></td>
                                                <td><?=$log[2]?></td>
                                                <td><?=$log[3]?></td>
                                                <td><?=$log[4]?></td>
                                                <td><?=$log[5]?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
    </section>
</div>

<link href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.css" rel="stylesheet">
<script type="application/javascript" src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
<script type="application/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.js"></script>
<script type="application/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.dataTables.js"></script>
<script type="application/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script type="application/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script type="application/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script type="application/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
<script type="application/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min"></script>
<script type="text/javascript">

    const imagePreLoad = `<?=base_url('assets/images/integration_erps')?>/${$('#imagePreLoad').val()}`;

    $(document).ready(function () {
        $("#mainIntegrationApiNav").addClass('active');
        $("#manageIntegrationErp").addClass('active');

        // initialize the datatable
        if ($('#manageTableLog').length) {
            $('#manageTableLog').DataTable({
                "ordering": false,
                'paging': true,
                "processing": true,
                "scrollX": true,
                "sortable": true,
                "searching": true,
                "language": {"url": "<?=base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang');?>"},
                "layout": {
                    "topStart": {
                        "buttons": ['copy', 'excel']
                    }
                }
            });
        }
    });
</script>