<div class="modal fade" tabindex="-1" role="dialog" id="changeProductStatus">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <h3 class="modal-info text-center"></h3>
                <div class="modal-info-items"></div>
            </div> <!-- modal-body -->
            <div class="modal-footer">
                <button type="button"
                        class="btn btn-default btn-cancel"><?= $this->lang->line('application_cancel'); ?></button>
                <button type="button"
                        class="btn btn-primary btn-confirm"><?= $this->lang->line('application_confirm'); ?></button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script>

    if(!lang) {
        var lang = {};
    }

    $.extend(lang, {
        'move_to_trash': '<?= $this->lang->line('application_move_to_trash') ?>'
    });

    var descriptions = {
        status: {
            product: {
                title: '<?= $this->lang->line('application_change_product_status_title'); ?>',
                info: '<?= $this->lang->line('application_change_product_status_info'); ?>',
                items: {}
            },
            list: {
                title: '<?= $this->lang->line('application_change_product_status_title'); ?>',
                info: '<?= $this->lang->line('application_change_product_status_info_list'); ?>',
                items: {}
            }
        },
        trash: {
            product: {
                title: '<?= $this->lang->line('application_move_product_to_trash_title'); ?>',
                info: '<?= $this->lang->line('application_move_product_to_trash_info'); ?>',
                items: JSON.parse('<?= json_encode($this->lang->line('application_move_product_to_trash_desc')) ?>')
            },
            list: {
                title: '<?= $this->lang->line('application_move_product_to_trash_title'); ?>',
                info: ' <?= $this->lang->line('application_move_product_to_trash_info_list'); ?>',
                items: JSON.parse('<?= json_encode($this->lang->line('application_move_product_to_trash_desc'))?>')
            },
        },
        delete: {
            product: {
                title: '<?= $this->lang->line('application_delete_permanently_product_title'); ?>',
                info: '<?= $this->lang->line('application_delete_permanently_product_info'); ?>',
                items: JSON.parse('<?= json_encode($this->lang->line('application_delete_permanently_product_desc')) ?>')
            }
        },
        csv: {
            list: {
                title: '<?= $this->lang->line('application_move_product_to_trash_title'); ?>',
                info: ' <?= $this->lang->line('application_move_product_to_trash_info_csv'); ?>',
                items: JSON.parse('<?= json_encode($this->lang->line('application_move_product_to_trash_desc'))?>'),
            },
        }
    },

    ChangeProductStatusModal = function (args) {
        args = args ?? {};
        this.view = args.view ?? 'product';
        this.type = args.type ?? 'status';
        this.component = $('#changeProductStatus');
        this.dataStatus = {};
        this.deferred = $.Deferred();

        this.labels = descriptions[this.type] ?? {};
        this.labels = this.labels[this.view] ?? {};

        this.count = 0;
    };
    ChangeProductStatusModal.prototype = {

        init: function () {
            $('.modal-title').text(this.labels['title']);
            $('.modal-info').html(
                this.labels['info']
                    .replace('{count}',
                        $('<b>', {class: 'count-products'}).append(this.count).prop('outerHTML')
                    )
                    .replace('{status}',
                        $('<b>', {class: 'status-desc'}).append(this.dataStatus['description'] ?? '').prop('outerHTML')
                    )
            );

            $('.modal-info-items').html('');
            if (this.labels['items'].length > 0) {
                var ul = $('<ul>');
                $(this.labels['items']).each(function (i, v) {
                    $(ul).append($('<li>').text(v));
                });
                $('.modal-info-items').append(ul);
            }

            $(this.component).modal({
                show: true,
                backdrop: 'static',
                keyboard: false
            });
            this.registerEvents();
            return this.deferred.promise();
        },

        setDeferredForm: function (def) {
            def.then(function (data) {
                $('.btn-confirm', $(this.component)).prop('disabled', false);
            }.bind(this)).fail(function (data) {
                $('.btn-confirm', $(this.component)).prop('disabled', true);
            }.bind(this));
        },
        setForm: function (form) {
            $(form).insertAfter('.modal-info-items');
            $('.btn-confirm', $(this.component)).prop('disabled', true);
            return this;
        },

        registerEvents: function () {
            $('.btn-confirm', $(this.component)).off('click').on('click', function () {
                this.confirm();
            }.bind(this));
            $('.btn-cancel', $(this.component)).off('click').on('click', function () {
                this.cancel();
            }.bind(this));
            $('.close', $(this.component)).off('click').on('click', function () {
                this.cancel();
            }.bind(this));
            return this;
        },

        confirm: function () {
            this.deferred.resolve({type: this.type, status: this.dataStatus});
            $(this.component).modal('toggle');
            return this;
        },
        cancel: function () {
            this.deferred.reject();
            $(this.component).modal('toggle');
            return this;
        },

        setType: function (type) {
            this.type = type;
            return this;
        },

        setStatus: function (status) {
            this.dataStatus = status;
            return this;
        },

        setCount: function (count) {
            this.count = count;
            return this;
        },
    };
</script>