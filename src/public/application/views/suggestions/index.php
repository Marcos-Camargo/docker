<div class="content-wrapper">

    <?php $data['pageinfo'] = "application_manage";
    $this->load->view('templates/content_header', $data); ?>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <?php if ($this->session->flashdata('success')) : ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('success'); ?>
                    </div>
                <?php elseif ($this->session->flashdata('error')) : ?>
                    <div class="alert alert-error alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <?php echo $this->session->flashdata('error'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <a href="<?php echo base_url('suggestions/create') ?>" class="btn btn-primary"><?= $this->lang->line('application_new_suggestion'); ?></a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <label for="buscanome" class="normal"><?= $this->lang->line('application_title'); ?></label>
                <div class="input-group">
                    <input type="search" id="buscanome" onchange="buscaLoja()" class="form-control" placeholder="<?= $this->lang->line('application_title'); ?>" aria-label="Search" aria-describedby="basic-addon1">
                    <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                </div>
            </div>
            <div class="col-md-3">
                <label for="busca_tag" class="normal"><?= $this->lang->line('application_tags'); ?></label>
                <div class="input-group">
                    <input type="search" id="busca_tag" onchange="buscaLoja()" class="form-control" placeholder="<?= $this->lang->line('application_tags'); ?>" aria-label="Search" aria-describedby="basic-addon1">
                    <span class="input-group-addon" id=""><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
                </div>
            </div>
            <div class="col-md-3">
                <label for="type" class="normal"><?= $this->lang->line('application_type_label'); ?></label>
                <select class="form-control" name="busca_type" id="busca_type" onchange="buscaLoja()">
                    <option value="" selected="true" disabled="disabled"><?= $this->lang->line('application_type_label_select'); ?></option>
                    <option value=""><?= $this->lang->line('application_type_label_all'); ?></option>
                    <?php foreach ($types as $type) : ?>
                        <!-- <option>Fazer uma Pergunta</option> -->
                        <option value="<?= $type['value'] ?>"><?= $type['text'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-3">
                <div class="form-group">
                    <label for="type" class="normal"><?= $this->lang->line('application_categories_label'); ?></label>
                    <select class="form-control" name="busca_categorie" id="busca_categorie" required  onchange="buscaLoja()">
                        <option value="" selected="true" disabled="disabled"><?= $this->lang->line('application_categories_label_select'); ?></option>
                        <option value=""><?= $this->lang->line('application_categories_label_all'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option <?= $category['value'] == '' ? '' : "value=\"" . $category['value'] . "\"" ?>><?= $category['text'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="box">
                <div class="box-body">
                    <table id="manageTable" class="table table-bordered table-striped" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Titulo</th>
                                <th>likes</th>
                                <?php if (in_array('delete_suggestions', $user_permission)) : ?>
                                    <th style="width:100px"><?= $this->lang->line('application_action'); ?></th>
                                <?php endif; ?>
                            </tr>
                        </thead>

                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript" src="<?= HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript">
    var manageTable;
    var base_url = "<?php echo base_url(); ?>";

    $(document).ready(function() {
        buscaLoja();
    });

    function buscaLoja() {
        let title = $('#buscanome').val();
        let tags = $('#busca_tag').val();
        let type = $('#busca_type').val();
        let categorie = $('#busca_categorie').val();
        if (typeof manageTable === 'object' && manageTable !== null) {
            manageTable.destroy();
        }

        manageTable = $('#manageTable').DataTable({
            "language": {
                "url": "<?php echo base_url('assets/bower_components/datatables.net/i18n/' . ucfirst($this->input->cookie('swlanguage')) . '.lang'); ?>"
            },
            "processing": true,
            "serverSide": true,
            "scrollX": true,
            "sortable": true,
            "searching": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline({
                url: base_url + 'Suggestions/fetchData',
                data: {
                    title: title,
                    tags: tags,
                    type: type,
                    categorie: categorie,
                },
                pages: 0
            })
        });
    }
</script>