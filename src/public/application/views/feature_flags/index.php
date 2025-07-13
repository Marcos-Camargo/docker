<div class="content-wrapper">
    <?php
    $data['pageinfo'] = "feature_flags_management";
    $this->load->view('templates/content_header', $data);
    ?>

    <!-- Main content -->
    <section class="content">
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

                <div class="box box-primary mt-2">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-flag" title="Feature Flags"></i>
                            Gerenciamento de Feature Flags
                        </h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" id="refresh_data_button" title="Atualizar Dados">
                                <i class="fa fa-sync-alt"></i> Atualizar
                            </button>
                        </div>
                    </div>

                    <div class="box-body">
                        <form id="feature-flags-filters" enctype="text/plain">
                            <div class="row">
                                <div class="form-group col-md-4 col-sm-6 col-xs-12">
                                    <label for="filter_status">
                                        <i class="fa fa-toggle-on"></i> Status
                                    </label>
                                    <select class="form-control" id="filter_status" name="filter_status">
                                        <option value="all">Todos os Status</option>
                                        <option value="enabled">Ativado</option>
                                        <option value="disabled">Desativado</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-4 col-sm-6 col-xs-12">
                                    <label for="filter_expiration">
                                        <i class="fa fa-clock"></i> Expiração
                                    </label>
                                    <select class="form-control" id="filter_expiration" name="filter_expiration">
                                        <option value="all">Todas as Expirações</option>
                                        <option value="expiring">Expirando</option>
                                        <option value="persistent">Permanente</option>
                                        <option value="expired">Expirado</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary" id="filter_button">
                                            <i class="fa fa-filter"></i> Aplicar Filtros
                                        </button>
                                        <button type="button" class="btn btn-default" id="reset_filter_button">
                                            <i class="fa fa-undo"></i> Redefinir Filtros
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn-danger pull-right" id="clear_all_features_button">
                                        <i class="fa fa-trash"></i> Forçar Expiração de Todos
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="box box-primary mt-2">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-list"></i> Lista de Feature Flags</h3>
                        <div class="box-tools pull-right">
                            <span class="badge bg-blue" id="feature-count">
                                <?php echo (isset($features) && is_array($features)) ? count($features) : 0; ?> Features
                            </span>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table id="featureFlagsTable" class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th width="30%"><i class="fa fa-tag"></i> Nome da Feature</th>
                                        <th width="15%"><i class="fa fa-toggle-on"></i> Status</th>
                                        <th width="25%"><i class="fa fa-clock"></i> Tempo para Expirar</th>
                                        <th width="30%"><i class="fa fa-hourglass-half"></i> Contagem Regressiva</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($features)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">
                                                <div class="empty-state">
                                                    <i class="fa fa-flag fa-3x text-muted"></i>
                                                    <p class="text-muted">Nenhuma feature flag encontrada</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($features as $feature): ?>
                                            <tr class="feature-row <?php echo $feature['enabled'] ? 'enabled-feature' : 'disabled-feature'; ?> <?php echo ($feature['ttl'] === -1) ? 'persistent-feature' : (($feature['ttl'] > 0) ? 'expiring-feature' : 'expired-feature'); ?>">
                                                <td>
                                                    <strong class="feature-name"><?php echo $feature['name']; ?></strong>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($feature['enabled']): ?>
                                                        <span class="label label-success status-badge"><i class="fa fa-check-circle"></i> Ativado</span>
                                                    <?php else: ?>
                                                        <span class="label label-danger status-badge"><i class="fa fa-times-circle"></i> Desativado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($feature['ttl'] !== null && $feature['ttl'] > 0): ?>
                                                        <span class="ttl-value" data-ttl="<?php echo $feature['ttl']; ?>">
                                                            <i class="fa fa-hourglass-start"></i> <?php echo $feature['ttl']; ?> segundos
                                                        </span>
                                                    <?php elseif ($feature['ttl'] === -1): ?>
                                                        <span class="label label-primary"><i class="fa fa-infinity"></i> Sem expiração (permanente)</span>
                                                    <?php else: ?>
                                                        <span class="label label-warning"><i class="fa fa-exclamation-triangle"></i> Expirado ou não definido</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($feature['ttl'] !== null && $feature['ttl'] > 0): ?>
                                                        <div class="progress">
                                                            <div class="progress-bar progress-bar-striped active countdown-bar" role="progressbar" 
                                                                aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;" 
                                                                data-ttl="<?php echo $feature['ttl']; ?>" 
                                                                data-original-ttl="<?php echo $feature['ttl']; ?>">
                                                                <span class="countdown-text"><?php echo $feature['ttl']; ?> segundos</span>
                                                            </div>
                                                        </div>
                                                    <?php elseif ($feature['ttl'] === -1): ?>
                                                        <span class="label label-primary"><i class="fa fa-infinity"></i> Permanente</span>
                                                    <?php else: ?>
                                                        <span class="label label-warning"><i class="fa fa-exclamation-triangle"></i> Expirado</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="box-footer">
                        <div class="alert alert-info">
                            <h4><i class="fa fa-info-circle"></i> Informações</h4>
                            <p><strong>Feature Flags:</strong> Esta página mostra todas as feature flags disponíveis no sistema e seus status atuais.</p>
                            <p><strong>Tempo para Expirar:</strong> Mostra quanto tempo resta antes que o cache da feature flag expire no Redis.</p>
                            <p><strong>Contagem Regressiva:</strong> Fornece uma representação visual do tempo restante antes da expiração.</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- col-md-12 -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script type="text/javascript">
    $(document).ready(function() {
        // Initialize DataTable with improved settings
        var featureFlagsTable = $('#featureFlagsTable').DataTable({
            "language": {
                "url": "<?php echo base_url(); ?>assets/bower_components/datatables.net/i18n/<?=ucfirst($this->input->cookie('swlanguage'))?>.lang",
                "searchPlaceholder": "Buscar feature flags",
                "emptyTable": "Nenhuma feature flag disponível",
                "zeroRecords": "Nenhuma feature flag correspondente encontrada"
            },
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "pageLength": 10,
            "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
            "order": [[0, "asc"]],
            "columnDefs": [
                { "orderable": false, "targets": 3 } // Disable sorting on countdown column
            ],
            "responsive": true,
            "dom": '<"top"lf>rt<"bottom"ip><"clear">'
        });

        // Start countdown timers
        startCountdowns();

        // Filter functionality with improved UX
        $('#filter_button').on('click', function() {
            applyFilters();
            // Show feedback to user
            showMessage('info', 'Filtros aplicados com sucesso');
        });

        // Reset filters with improved UX
        $('#reset_filter_button').on('click', function() {
            $('#filter_status').val('all');
            $('#filter_expiration').val('all');
            applyFilters();
            // Show feedback to user
            showMessage('info', 'Filtros foram redefinidos');
        });

        // Clear all features button with improved confirmation
        $('#clear_all_features_button').on('click', function() {
            // Show confirmation dialog with more details
            if (confirm('AVISO: Você está prestes a forçar a expiração de TODAS as feature flags.\n\nIsso removerá todas as feature flags do cache e elas precisarão ser buscadas novamente do servidor.\n\nEsta ação não pode ser desfeita. Tem certeza de que deseja continuar?')) {
                clearAllFeatures();
            }
        });

        // Add refresh button functionality
        $('#refresh_data_button').on('click', function() {
            // Show loading indicator
            showMessage('info', '<i class="fa fa-spinner fa-spin"></i> Atualizando dados das feature flags...');

            // Reload the page to get fresh data
            setTimeout(function() {
                location.reload();
            }, 1000);
        });

        // Add keyboard shortcuts for better accessibility
        $(document).keydown(function(e) {
            // Alt+F for filter
            if (e.altKey && e.keyCode === 70) {
                e.preventDefault();
                $('#filter_button').click();
            }
            // Alt+R for reset
            if (e.altKey && e.keyCode === 82) {
                e.preventDefault();
                $('#reset_filter_button').click();
            }
            // Alt+C for clear all
            if (e.altKey && e.keyCode === 67) {
                e.preventDefault();
                $('#clear_all_features_button').click();
            }
        });

        // Function to apply filters with improved logic
        function applyFilters() {
            var statusFilter = $('#filter_status').val();
            var expirationFilter = $('#filter_expiration').val();

            // Clear any existing search/filter
            featureFlagsTable.search('').columns().search('').draw();

            // Apply custom filtering with improved logic
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var $row = $(featureFlagsTable.row(dataIndex).node());

                    // Status filtering
                    var statusMatch = statusFilter === 'all' || 
                                    (statusFilter === 'enabled' && $row.hasClass('enabled-feature')) || 
                                    (statusFilter === 'disabled' && $row.hasClass('disabled-feature'));

                    // Expiration filtering
                    var expirationMatch = expirationFilter === 'all' || 
                                        (expirationFilter === 'expiring' && $row.hasClass('expiring-feature')) || 
                                        (expirationFilter === 'persistent' && $row.hasClass('persistent-feature')) || 
                                        (expirationFilter === 'expired' && $row.hasClass('expired-feature'));

                    return statusMatch && expirationMatch;
                }
            );

            // Apply the filter
            featureFlagsTable.draw();

            // Remove the custom filter after drawing
            $.fn.dataTable.ext.search.pop();

            // Update the feature count to show filtered count
            updateFeatureCount(featureFlagsTable.page.info().recordsDisplay);
        }

        // Function to update the feature count badge
        function updateFeatureCount(count) {
            $('#feature-count').text(count + ' Features');
        }

        // Function to show messages with auto-dismiss
        function showMessage(type, message) {
            // Clear previous messages
            $('#messages').empty();

            // Create alert based on type
            var alertClass = 'alert-info';
            if (type === 'success') alertClass = 'alert-success';
            if (type === 'error') alertClass = 'alert-danger';
            if (type === 'warning') alertClass = 'alert-warning';

            // Show the message
            $('#messages').html('<div class="alert ' + alertClass + ' alert-dismissible fade in" role="alert">' +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span></button>' +
                message + '</div>');

            // Auto-dismiss after 5 seconds unless it's an error
            if (type !== 'error') {
                setTimeout(function() {
                    $('#messages .alert').alert('close');
                }, 5000);
            }
        }
    });

    // Improved countdown function with better performance
    function startCountdowns() {
        // Start a countdown for each feature with a TTL
        $('.countdown-bar').each(function() {
            var $this = $(this);
            var ttl = parseInt($this.data('ttl'));
            var originalTtl = parseInt($this.data('original-ttl'));
            var $ttlValue = $this.closest('tr').find('.ttl-value');

            if (ttl > 0) {
                var countdownInterval = setInterval(function() {
                    ttl--;

                    if (ttl <= 0) {
                        // Handle expiration
                        clearInterval(countdownInterval);
                        $this.removeClass('progress-bar-striped active progress-bar-success progress-bar-warning')
                             .addClass('progress-bar-danger')
                             .css('width', '100%');
                        $this.find('.countdown-text').html('<i class="fa fa-exclamation-triangle"></i> Expirado');

                        // Update the TTL value display
                        $ttlValue.html('<i class="fa fa-exclamation-triangle"></i> Expirado');

                        // Add expired class to the row
                        $this.closest('tr').removeClass('expiring-feature').addClass('expired-feature');
                    } else {
                        // Update countdown
                        var percentage = (ttl / originalTtl) * 100;
                        $this.css('width', percentage + '%');

                        // Format the time nicely
                        var formattedTime = formatTime(ttl);
                        $this.find('.countdown-text').text(formattedTime);

                        // Update the TTL value display with icon
                        $ttlValue.html('<i class="fa fa-hourglass-half"></i> ' + formattedTime);

                        // Change color based on remaining time with smoother transitions
                        if (percentage < 25) {
                            $this.removeClass('progress-bar-success progress-bar-warning')
                                 .addClass('progress-bar-danger');
                        } else if (percentage < 50) {
                            $this.removeClass('progress-bar-success progress-bar-danger')
                                 .addClass('progress-bar-warning');
                        } else {
                            $this.removeClass('progress-bar-warning progress-bar-danger')
                                 .addClass('progress-bar-success');
                        }
                    }
                }, 1000);

                // Store the interval ID on the element for potential cleanup
                $this.data('interval-id', countdownInterval);
            }
        });
    }

    // Improved time formatting function
    function formatTime(seconds) {
        if (seconds < 60) {
            return seconds + ' segundos';
        } else if (seconds < 3600) {
            var minutes = Math.floor(seconds / 60);
            var remainingSeconds = seconds % 60;
            return minutes + ' min ' + (remainingSeconds > 0 ? remainingSeconds + ' seg' : '');
        } else {
            var hours = Math.floor(seconds / 3600);
            var remainingMinutes = Math.floor((seconds % 3600) / 60);
            var remainingSeconds = seconds % 60;

            var result = hours + ' h';
            if (remainingMinutes > 0) result += ' ' + remainingMinutes + ' min';
            if (remainingSeconds > 0) result += ' ' + remainingSeconds + ' seg';

            return result;
        }
    }

    // Improved function to clear all features
    function clearAllFeatures() {
        // Show loading indicator with spinner
        $('#messages').html('<div class="alert alert-info alert-dismissible" role="alert">' +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span></button>' +
            '<i class="fa fa-spinner fa-spin"></i> Limpando todas as feature flags. Por favor, aguarde...</div>');

        // Disable the clear button to prevent multiple clicks
        $('#clear_all_features_button').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processando...');

        // Make AJAX request to clear all features
        $.ajax({
            url: '<?php echo base_url('featureFlags/clearAllFeatures'); ?>',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                // Clear previous messages
                $('#messages').empty();

                if (response.success) {
                    // Show success message
                    $('#messages').html('<div class="alert alert-success alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                        '<span aria-hidden="true">&times;</span></button>' +
                        '<i class="fa fa-check-circle"></i> ' + response.message + '</div>');

                    // Reload the page after a short delay to show updated data
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    // Show error message
                    $('#messages').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                        '<span aria-hidden="true">&times;</span></button>' +
                        '<i class="fa fa-times-circle"></i> ' + response.message + '</div>');

                    // Re-enable the clear button
                    $('#clear_all_features_button').prop('disabled', false).html('<i class="fa fa-trash"></i> Forçar Expiração de Todos');
                }
            },
            error: function(xhr, status, error) {
                // Clear previous messages
                $('#messages').empty();

                // Show error message with details
                $('#messages').html('<div class="alert alert-danger alert-dismissible" role="alert">' +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span></button>' +
                    '<i class="fa fa-times-circle"></i> Erro: Falha ao limpar feature flags. Por favor, tente novamente.</div>');

                console.error('AJAX Error:', error);

                // Re-enable the clear button
                $('#clear_all_features_button').prop('disabled', false).html('<i class="fa fa-trash"></i> Forçar Expiração de Todos');
            }
        });
    }
</script>

<style>
    /* General Spacing and Layout */
    .mt-2 {
        margin-top: 20px;
    }
    .mb-2 {
        margin-bottom: 20px;
    }
    .p-2 {
        padding: 20px;
    }

    /* Feature Flags Table Styling */
    #featureFlagsTable {
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    #featureFlagsTable th {
        background-color: #3c8dbc;
        color: white;
        font-weight: 600;
        border-bottom: 2px solid #367fa9;
        padding: 12px 8px;
    }

    #featureFlagsTable th i {
        margin-right: 5px;
    }

    #featureFlagsTable td {
        padding: 12px 8px;
        vertical-align: middle;
    }

    /* Feature Row Styling */
    .feature-row {
        transition: all 0.3s ease;
    }

    .feature-row:hover {
        background-color: #f9f9f9;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .enabled-feature {
        border-left: 4px solid #00a65a;
    }

    .disabled-feature {
        border-left: 4px solid #dd4b39;
    }

    .persistent-feature {
        background-color: #f9faff;
    }

    .expired-feature {
        background-color: #fff9f9;
    }

    /* Feature Name Styling */
    .feature-name {
        font-size: 14px;
        color: #333;
        display: block;
        padding: 2px 0;
    }

    /* Status Badge Styling */
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
        min-width: 80px;
        text-align: center;
    }

    .label i {
        margin-right: 3px;
    }

    /* Countdown Styling */
    .countdown-text {
        display: block;
        text-align: center;
        color: #fff;
        font-weight: bold;
        text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
        font-size: 13px;
    }

    .progress {
        margin-bottom: 0;
        height: 25px;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
    }

    .progress-bar {
        line-height: 25px;
        transition: width 0.5s ease, background-color 0.5s ease;
    }

    /* Box and Header Styling */
    .box {
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-top: 3px solid #3c8dbc;
    }

    .box-primary {
        border-top-color: #3c8dbc;
    }

    .box-header {
        padding: 15px;
        border-bottom: 1px solid #f4f4f4;
    }

    .box-title {
        font-size: 18px;
        font-weight: 600;
    }

    .box-title .fa {
        margin-right: 10px;
        color: #3c8dbc;
    }

    .box-body {
        padding: 20px;
    }

    /* Filter Section Styling */
    #feature-flags-filters label {
        font-weight: 600;
        color: #555;
        margin-bottom: 8px;
    }

    #feature-flags-filters .form-control {
        height: 38px;
        border-radius: 4px;
        box-shadow: none;
        border: 1px solid #ddd;
    }

    #feature-flags-filters .form-control:focus {
        border-color: #3c8dbc;
        box-shadow: 0 0 5px rgba(60,141,188,0.2);
    }

    /* Button Styling */
    .btn {
        border-radius: 4px;
        font-weight: 600;
        padding: 8px 15px;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background-color: #3c8dbc;
        border-color: #367fa9;
    }

    .btn-primary:hover, .btn-primary:focus {
        background-color: #367fa9;
        border-color: #2e6da4;
    }

    .btn-danger {
        background-color: #dd4b39;
        border-color: #d73925;
    }

    .btn-danger:hover, .btn-danger:focus {
        background-color: #d73925;
        border-color: #c23321;
    }

    .btn i {
        margin-right: 5px;
    }

    /* Badge Styling */
    .badge {
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 10px;
    }

    .bg-blue {
        background-color: #3c8dbc !important;
    }

    /* Empty State Styling */
    .empty-state {
        padding: 30px;
        text-align: center;
    }

    .empty-state i {
        margin-bottom: 15px;
        color: #ddd;
    }

    .empty-state p {
        font-size: 16px;
        color: #999;
    }

    /* Alert Styling */
    .alert {
        border-radius: 4px;
        padding: 15px;
    }

    .alert h4 {
        margin-top: 0;
        margin-bottom: 10px;
        font-weight: 600;
    }

    .alert-info {
        background-color: #d9edf7;
        border-color: #bce8f1;
        color: #31708f;
    }

    /* Responsive Adjustments */
    @media (max-width: 767px) {
        .box-title {
            font-size: 16px;
        }

        .btn {
            padding: 6px 12px;
            font-size: 13px;
            margin-bottom: 5px;
        }

        #feature-flags-filters .form-group {
            margin-bottom: 15px;
        }

        .status-badge {
            min-width: 70px;
            padding: 4px 8px;
        }
    }
</style>
