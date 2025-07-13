<!--
SW Serviços de Informática 2019

Listar Produtos

Obs:
cada usuario so pode ver produtos da sua empresa.
Agencias podem ver todos os produtos das suas empresas
Admin pode ver produtos detodas as empresas e agencias

-->
<!-- Content Wrapper. Contains page content -->

<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";  $this->load->view('templates/content_header',$data); ?>

    <section class="content">
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
                <div id="showActions">
                    <span class ="pull-right">&nbsp</span>
                    <button type="button" data-toggle="modal" data-target="#newReport" class="btn btn-primary"><?=$this->lang->line('application_add_report');?></button>
                </div>
                <br />
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"></h3>
                    </div>
                    <div class="box-body">
                        <table id="manageTable" class="table table-striped table-hover display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                            <thead>
                            <tr>
                                <th><?=$this->lang->line('application_name');?></th>
                                <th><?=$this->lang->line('application_title');?></th>
                                <th><?=$this->lang->line('application_type');?></th>
                                <th><?=$this->lang->line('application_code_production');?></th>
                                <th><?=$this->lang->line('application_admin');?></th>
                                <th><?=$this->lang->line('application_active');?></th>
                                <th><?=$this->lang->line('application_action');?></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>


<div class="modal fade" tabindex="-1" role="dialog" id="newReport">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_register_report');?></h4>
            </div>
            <form role="form" action="<?php echo base_url('reports/newReport') ?>" method="post" id="formNewReport">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Nome do Relatório</label>
                            <input type="text" class="form-control valid-restrict" name="name_report" required>
                            <small>Não deve conter espaços, acentos ou caracter especiais. Ex.: <strong>report_seller_index</strong></small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="col-md-12 no-padding">Tipo de Relatório</label>
                            <div class="col-md-6 no-padding">
                                <input type="radio" name="type_report" value="dashboard" id="type_report_dashboard" checked required>
                                <label for="type_report_dashboard">Dashboard</label>
                            </div>
                            <div class="col-md-6 no-padding">
                                <input type="radio" name="type_report" value="question" id="type_report_question" required>
                                <label for="type_report_question">Question</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Título do Relatório (PT)</label>
                            <input type="text" class="form-control" name="title_pt" required>
                            <small>Título do relatório em portugês</small>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Título do Relatório (EN)</label>
                            <input type="text" class="form-control" name="title_en" required>
                            <small>Título do relatório em inglês</small>
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?=$this->lang->line('application_groups');?></label>
                            <select class="form-control selectpicker show-tick" name ="groups[]"  data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 2">
				            <?php foreach ($groups as $group) { ?>
				                <option value="<?= $group['id'] ?>"><?= $group['group_name'] ?></option>
				       		<?php } ?>
				       	   </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 form-group">
                            <label for="report_admin_new"><input type="checkbox" name="admin" id="report_admin_new"> Somente Admin</label>
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="report_active_new"><input type="checkbox" name="active" id="report_active_new" checked> Ativo</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>Código AWS (Admin)</label>
                            <input type="number" class="form-control" name="code_prod_aws">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Código GCP (Admin)</label>
                            <input type="number" class="form-control" name="code_prod_gcp">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Código OCI (Admin)</label>
                            <input type="number" class="form-control" name="code_prod_oci">
                        </div>
                    </div>
                    <div class="row codes-seller">
                        <div class="col-md-3 form-group">
                            <label>Código AWS (Seller)</label>
                            <input type="number" class="form-control" name="code_prod_aws_seller">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Código GCP (Seller)</label>
                            <input type="number" class="form-control" name="code_prod_gcp_seller">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Código OCI (Seller)</label>
                            <input type="number" class="form-control" name="code_prod_oci_seller">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" tabindex="-1" role="dialog" id="editReport">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?=$this->lang->line('application_register_report');?></h4>
            </div>
            <form role="form" action="<?php echo base_url('reports/editReport') ?>" method="post" id="formEditReport">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Nome do Relatório</label>
                            <input type="text" class="form-control valid-restrict" name="name_report" required>
                            <small>Não deve conter espaços, acentos ou caracter especiais. Ex.: <strong>report_seller_index</strong></small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="col-md-12 no-padding">Tipo de Relatório</label>
                            <div class="col-md-6 no-padding">
                                <input type="radio" name="type_report" value="dashboard" id="type_report_dashboard" checked required>
                                <label for="type_report_dashboard">Dashboard</label>
                            </div>
                            <div class="col-md-6 no-padding">
                                <input type="radio" name="type_report" value="question" id="type_report_question" required>
                                <label for="type_report_question">Question</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Título do Relatório (PT)</label>
                            <input type="text" class="form-control" name="title_pt" required>
                            <small>Título do relatório em portugês</small>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Título do Relatório (EN)</label>
                            <input type="text" class="form-control" name="title_en" required>
                            <small>Título do relatório em inglês</small>
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?=$this->lang->line('application_groups');?></label>
                            <select class="form-control selectpicker show-tick" id="groupsedit" name ="groups[]"  data-live-search="true" data-actions-box="true" multiple="multiple" data-style="btn-blue" data-selected-text-format="count > 2" >
				            <?php foreach ($groups as $group) { ?>
				                <option value="<?= $group['id'] ?>"><?= $group['group_name'] ?></option>
				       		<?php } ?>
				       	   </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 form-group">
                            <label for="report_admin_edit"><input type="checkbox" name="admin" id="report_admin_edit"> Somente Admin</label>
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="report_active_edit"><input type="checkbox" name="active" id="report_active_edit" checked> Ativo</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>Código AWS (Admin)</label>
                            <input type="number" class="form-control" name="code_prod_aws">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Código GCP (Admin)</label>
                            <input type="number" class="form-control" name="code_prod_gcp">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Código OCI (Admin)</label>
                            <input type="number" class="form-control" name="code_prod_oci">
                        </div>
                    </div>
                    <div class="row codes-seller">
                        <div class="col-md-3 form-group">
                            <label>Código AWS (Seller)</label>
                            <input type="number" class="form-control" name="code_prod_aws_seller">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Código GCP (Seller)</label>
                            <input type="number" class="form-control" name="code_prod_gcp_seller">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Código OCI (Seller)</label>
                            <input type="number" class="form-control" name="code_prod_oci_seller">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?=$this->lang->line('application_close');?></button>
                    <button type="submit" class="btn btn-primary"><?=$this->lang->line('application_confirm');?></button>
                </div>
                <input type="hidden" name="report_id">
            </form>
        </div>
    </div>
</div>

<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.5/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.5/css/responsive.dataTables.min.css"/>
<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>

<script type="text/javascript">
    var manageTable;
    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function() {
        getTable();
    });

    const cleanFieldsCreate = () => {
        const form = $('#formNewReport');

        form.find('[name="name_report"]').val('');
        form.find('[name="title_pt"]').val('');
        form.find('[name="title_en"]').val('');
        form.find('[name^="code_prod"]').val('');
        form.find('[name="admin"]').prop('checked', false);
        form.find('[name="active"]').prop('checked', true);
        form.find('[name="type_report"][value="dashboard"]').prop('checked', true);
        form.find('.codes-seller input').val('');
        form.find('.codes-seller').slideDown('slow');
        form.find('[name="groups"]').val('');
    }

    const getTable = () => {
        if (typeof manageTable !== 'undefined')
            manageTable.destroy();

        manageTable = $('#manageTable').DataTable( {
            "language": { "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/'.ucfirst($this->input->cookie('swlanguage')).'.lang'); ?>"},
            "processing": true,
            "serverSide": true,
            "responsive": true,
            "sortable": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline( {
                url: base_url + 'reports/fetchReportData',
                pages: 2 // number of pages to cache
            } )
        } );
    }

    // https://pt.stackoverflow.com/a/216701
    const validRestrict = str => {

        // Converte o texto para caixa baixa:
        str = str.toLowerCase();

        // Remove qualquer caractere em branco do final do texto:
        str = str.replace(/^\s+|\s+$/g, '');

        // Lista de caracteres especiais que serão substituídos:
        const from = "ãàáäâẽèéëêìíïîõòóöôùúüûñç·/,:;";

        // Lista de caracteres que serão adicionados em relação aos anteriores:
        const to   = "aaaaaeeeeeiiiiooooouuuunc-----";

        // Substitui todos os caracteres especiais:
        for (let i = 0, l = from.length; i < l; i++) {
            str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
        }

        // Remove qualquer caractere inválido que possa ter sobrado no texto:
        str = str.replace(/[^a-z0-9 -]/g, '_');

        // Substitui os espaços em branco por hífen:
        str = str.replace(/\s+/g, '-');

        return str;
    };

    $('.valid-restrict').on('keyup', function() {
        var str = $(this).val();
        $(this).val(validRestrict(str));
    });


    $('#formNewReport').submit(function () {
        const form = $('#formNewReport');

        console.log(form.serialize());

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success: response => {

                console.log(response);

                if (!response.success) {
                    AlertSweet.fire({
                        icon: 'warning',
                        html: `<ul class="text-left">${response.data}</ul>`
                    });
                    return false;
                }

                $('#newReport').modal('hide');
                cleanFieldsCreate();

                setTimeout(() => {

                    getTable();

                    AlertSweet.fire({
                        icon: 'success',
                        html: `<h4>${response.data}</h4>`
                    });

                }, 500);


            }, error: e => {
                console.log(e);
            }
        });
        return false;
    });

    $(document).on('click', '.editReport', function () {
        const id_report = $(this).attr('id-report');
        $('#editReport').modal();

        $.ajax({
            url: base_url + 'reports/getReport/'+id_report,
            type: 'GET',
            dataType: 'json',
            success: response => {
                console.log(response);

                if (!response.success) {
                    $('#editReport').modal('hide');
                    AlertSweet.fire({
                        icon: 'warning',
                        html: `<h4>${response.data}</h4>`
                    });
                    return false;
                }

                const form = $('#formEditReport');

                form.find('[name="name_report"]').val(response.data.name);
                form.find('[name="title_pt"]').val(response.data.title_br);
                form.find('[name="title_en"]').val(response.data.title_en);
                form.find('[name="admin"]').prop('checked', parseInt(response.data.admin) === 1).trigger('change');
                form.find('[name="active"]').prop('checked', parseInt(response.data.active) === 1);
                form.find(`[name="type_report"][value="${response.data.type}"]`).prop('checked', true)
                form.find('input[name="report_id"]').val(id_report);
                form.find('[name="code_prod_aws"]').val(response.data.cod_type_prod);
                form.find('[name="code_prod_aws_seller"]').val(response.data.cod_type_prod_adm_seller);
                form.find('[name="code_prod_gcp"]').val(response.data.cod_type_prod_gcp);
                form.find('[name="code_prod_gcp_seller"]').val(response.data.cod_type_prod_adm_seller_gcp);
                form.find('[name="code_prod_oci"]').val(response.data.cod_type_prod_oci);
                form.find('[name="code_prod_oci_seller"]').val(response.data.cod_type_prod_adm_seller_oci);

				$("#groupsedit").selectpicker('val', '');
				var groupsedit = document.getElementById( 'groupsedit' ); 
				var optionsToSelect= JSON.parse(response.data.groups);
				if (optionsToSelect != null) {
					for ( var i = 0, l = groupsedit.options.length, o; i < l; i++ )
					{
					  o = groupsedit.options[i];
					  if ( optionsToSelect.indexOf( o.value ) != -1 )
					  {
					    o.selected = true;
					  }
					}					
				}
				$("#groupsedit").selectpicker('refresh');
				
            }, error: e => {
                console.log(e);
            }
        });
    });


    $('#formEditReport').submit(function () {
        const form = $('#formEditReport');

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success: response => {
                console.log(response);

                if (!response.success) {
                    AlertSweet.fire({
                        icon: 'warning',
                        html: `<ul class="text-left">${response.data}</ul>`
                    });
                    return false;
                }

                $('#editReport').modal('hide');
                cleanFieldsCreate();

                setTimeout(() => {

                    getTable();

                    AlertSweet.fire({
                        icon: 'success',
                        html: `<h4>${response.data}</h4>`
                    });

                }, 500);


            }, error: e => {
                console.log(e);
            }
        });
        return false;
    });

    $(document).on('click', '.delReport', function (){
        const report_id = $(this).attr('id-report');
        const name_report = $(this).attr('name-report');

        Swal.fire({
            title: 'Exclusão de Relatório',
            html: "Deseja excluir definitivamente o relatório  <br><strong>"+name_report+"</strong>?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#bbb',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: base_url + 'reports/removeReport',
                    type: 'POST',
                    data: { report_id },
                    dataType: 'json',
                    success: response => {

                        if (!response.success) {
                            AlertSweet.fire({
                                icon: 'warning',
                                html: `<h4>${response.data}</h4>`
                            });
                            return false;
                        }

                        setTimeout(() => {

                            getTable();

                            AlertSweet.fire({
                                icon: 'success',
                                html: `<h4>${response.data}</h4>`
                            });

                        }, 500);

                    }, error: e => {
                        console.log(e);
                    }
                });
            }
        })
    });

    $('[name="admin"]').change(function(){
        const el = $(this).closest('form');
        if ($(this).is(':checked')) {
            el.find('.codes-seller input').val('');
            el.find('.codes-seller').slideUp('slow');
        } else {
            el.find('.codes-seller').slideDown('slow');
        }
    })

</script>
