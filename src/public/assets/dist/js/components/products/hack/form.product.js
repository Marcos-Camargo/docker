$(document).ready(function () {
    $('#with_variation').on('change', function () {
        if ($(this).val() == '1') {
            $('#formVariations').show();
        } else {
            $('#formVariations').hide();
            $('#semvar').prop('checked', true);
            $('#semvar').trigger('change');
        }
    });
    $('#with_variation').trigger('change');
    if ($('#sizevar').attr('checked') == 'checked') {
        $('#sizevar').prop('checked', true);
        $('#sizevar').trigger('change');
    }
    if ($('#colorvar').attr('checked') == 'checked') {
        $('#colorvar').prop('checked', true);
        $('#colorvar').trigger('change');
    }
    if ($('#voltvar').attr('checked') == 'checked') {
        $('#voltvar').prop('checked', true);
        $('#voltvar').trigger('change');
    }
    if ($('#saborvar').attr('checked') == 'checked') {
        $('#saborvar').prop('checked', true);
        $('#saborvar').trigger('change');
    }
});

function loadAfterFileInputPreview(element) {
    $('div.file-preview-frame.krajee-default.file-sortable', element).addClass('file-drag-handle drag-handle-init');
}