<!--

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
                <?php elseif($this->session->flashdata('warning')): ?>
                <div class="alert alert-warning alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <?php echo $this->session->flashdata('warning'); ?>
                </div>
                <?php endif; ?>

                <?php if (in_array('createManageIntegrationErp', $this->permission)): ?>
                    <div class="box box-primary mt-2" id="showActions">
                        <div class="box-body">
                            <a href="<?php echo base_url('integrations/createIntegration') ?>" class="btn btn-primary"><?=$this->lang->line('application_create_integration');?></a>
                        </div>
                    </div>
                <?php endif;?>

                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><?=$this->lang->line('application_manage_integration');?></h3>
                    </div>
                    <div class="box-body">
                        <table id="manageTable" class="table table-striped table-hover responsive display table-condensed" cellspacing="0" style="border-collapse: collapse; width: 99%;">
                            <thead>
                                <tr>
                                    <th style="width: 40%"><?=$this->lang->line('application_name');?></th>
                                    <th style="width: 20%"><?=$this->lang->line('application_type');?></th>
                                    <th style="width: 20%"><?=$this->lang->line('application_visible_in_the_system');?></th>
                                    <th style="width: 20%"><?=$this->lang->line('application_action');?></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript" src="<?=HOMEPATH; ?>/assets/bower_components/bootstrap/dist/js/pipeline.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.4/moment.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/plug-ins/1.10.21/sorting/datetime-moment.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>

<script type="text/javascript">
    let manageTable;
    const base_url = "<?php echo base_url(); ?>";

    $(document).ready(function() {
        $("#mainIntegrationApiNav").addClass('active');
        $("#manageIntegrationErp").addClass('active');

        manageTable = getTable();
    });

    const getTable = () => {
        return $('#manageTable').DataTable( {
            "language": { "url": base_url + 'assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang'},
            "processing": true,
            "serverSide": true,
            "responsive": true,
            "sortable": true,
            "serverMethod": "post",
            "ajax": $.fn.dataTable.pipeline({
                url: base_url + 'integrations/fetchmanageIntegration',
                pages: 2
            })
        } );
    }
</script>
