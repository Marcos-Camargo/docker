<div class="modal fade" tabindex="-1" role="dialog" id="detailModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content" id="modal-detail-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeModal(event, 'detailModal')">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    Informações sobre a Comissão Cadastrada
                </h4>
            </div>
            <div class="modal-body" id="modal-detail-body">
            </div>
            <div class="modal-footer">
                <a href="" class="btn btn-primary pull-left" id="log-details">
                    <i class="fa fa-eye"></i>
                    <?=lang('application_log_history')?>
                </a>
                <button type="button" class="btn btn-default pull-right" data-dismiss="modal" onclick="closeModal(event, 'detailModal')">
                    <?=lang('application_close')?>
                </button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

<script>
    function comissionDetails(id){
        $("#modal-detail-body").html('<i class="fa fa-spin fa-spinner"></i>');

        let logUrl = "<?php echo base_url('commissioning/logs/'); ?>"+id;
        $("#log-details").attr('href', logUrl);

        var pageURL = base_url.concat("commissioning/details/"+id);

        $.get(pageURL, function(data) {
            $("#modal-detail-body").html(data);
        });
    }

    function closeModal(event, modalId) {
        event.stopPropagation();
        $('#' + modalId).modal('hide');
    }
</script>
