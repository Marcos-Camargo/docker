var ProductStatusComponent = function (args) {
    this.ACTIVE = 'active';
    this.INACTIVE = 'inactive';
    this.DELETED = 'deleted';
    this.BLOCKED = 'blocked';

    this.component = args.target;

    this.origin = $(this.component).data('origin');
    this.productId = $(this.component).data('product-id');
    this.productStatus = $(this.component).data('product-status');
    this.productStatus = this.productStatus == 0 ? 2 : this.productStatus;
    this.dataStatus = $(this.component).data('status');
    this.refElement = eval($(this.component).data('refElement'));
    this.baseUrl = $(this.component).data('base-url');
    this.endpoint = $(this.component).data('endpoint');

    this.enableDelete = $(this.component).data('enable-delete') ?? false;

    this.currentStatusData = null;

    this.modal = null;
};

ProductStatusComponent.prototype = {

    build: function () {
        var div = $('<div>', {class: 'input-group product-actions'});
        var input = $('<input>', {
            id: 'product_status_' + this.productId,
            type: 'checkbox',
            value: this.getInputValue(),
        });
        $(input).attr('data-toggle', 'toggle').attr('data-on', this.getActiveDescription())
            .attr('data-off', this.getInactiveDescription())
            .attr('data-onstyle', this.getButtonStyle())
            .attr('data-offstyle', 'default').attr('data-width', 100)
            .attr('data-height', 20).attr('data-line-height', 20);
        $(div).append(input);
        $(this.component).append(div);

        if (this.isChecked()) {
            $(input).prop('checked', true);
        }

        if (!this.isEnabled()) {
            $(input).prop('disabled', true);
            $('input[data-toggle="toggle"]', $(this.component)).bootstrapToggle();
        } else {
            $('input[data-toggle="toggle"]', $(this.component)).bootstrapToggle();
            $('div[data-toggle="toggle"]', $(this.component))
                .off('click')
                .on('click', function (e) {
                    var currentStatus = this.getCurrentStatusData();
                    if (currentStatus['alias'] == this.ACTIVE || currentStatus['alias'] == this.INACTIVE) {
                        var reverseStatus = currentStatus['alias'] == this.ACTIVE ? this.INACTIVE : this.ACTIVE;
                        this.currentStatusData = $(this.dataStatus).filter(function (i, s) {
                            return s['alias'] == reverseStatus;
                        }.bind(this))[0];
                        if (parseInt(this.productId) <= 0) {
                            this.refElement.val(this.currentStatusData['code']);
                            return true;
                        }
                        (new ChangeProductStatusModal()).setStatus(this.currentStatusData)
                            .init().then(function () {
                            this.refElement.val(this.currentStatusData['code']);
                            if (this.productId > 0) {
                                return this.updateStatus(this.currentStatusData['code']).then(function (response) {
                                    var res = JSON.parse(response);
                                    Toast.fire({
                                        icon: 'success',
                                        title: res['message']
                                    });
                                }).fail(function (e) {
                                    var msg = e.responseText.length > 0 ? JSON.parse(e.responseText)['errors'] : [e.statusText];
                                    msg = msg[0] ?? '';
                                    Toast.fire({
                                        icon: 'error',
                                        title: msg
                                    });
                                    return $.Deferred().reject(e);
                                });
                            }
                        }.bind(this)).fail(function (e) {
                            var input = $('input[data-toggle="toggle"]', $(e.currentTarget));
                            $(input).val(currentStatus['alias'] == this.INACTIVE ? '0' : '1');
                            this.refElement.val(currentStatus['code']);
                            $(input).prop('checked', $(input).val() > 0 ? true : false);
                            $(input).trigger('change');
                            this.currentStatusData = currentStatus;
                        }.bind(this, e, currentStatus));
                    }
                }.bind(this));
        }

        if (this.enableDeleted() && this.productId > 0) {
            var deleteBtn = $('<button>', {
                type: 'button', class: 'btn btn-danger', title: lang && lang['move_to_trash'] ? lang['move_to_trash'] : ''
            })
                .data({
                    toggle: 'tooltip'
                })
                .append($('<i>', {class: 'fa fa-trash'}));
            $(div).append(deleteBtn);
            $(deleteBtn).tooltip();
            $(deleteBtn).off('click').on('click', function (e) {
                e.preventDefault();
                (new ChangeProductStatusModal({
                    type: 'trash'
                })).setStatus({}).init().then(function () {
                    this.moveToTrash();
                }.bind(this));
            }.bind(this));
        }
        return this;
    },

    getCurrentStatusData: function () {
        if (this.currentStatusData == null) {
            this.currentStatusData = $(this.dataStatus).filter(function (i, s) {
                return s['code'] == this.productStatus;
            }.bind(this))[0];
        }
        return this.currentStatusData;
    },

    enableDeleted: function () {
        var currentStatus = this.getCurrentStatusData();
        if (currentStatus['alias'] == this.DELETED) {
            return false;
        }
        return this.enableDelete;
    },

    isVisible: function () {
        var currentStatus = this.getCurrentStatusData();
        return currentStatus['alias'] != this.DELETED;
    },

    isChecked: function () {
        var currentStatus = this.getCurrentStatusData();
        return currentStatus['alias'] == this.INACTIVE ? false : true;
    },

    isEnabled: function () {
        var currentStatus = this.getCurrentStatusData();
        if (currentStatus.hasOwnProperty('enabled') && currentStatus['enabled'] == false) {
            return false;
        }
        return currentStatus['alias'] != this.BLOCKED
            && currentStatus['alias'] != this.DELETED;
    },

    getInputValue: function () {
        var currentStatus = this.getCurrentStatusData();
        return currentStatus['code'];
    },

    getActiveDescription: function () {
        var currentStatus = this.getCurrentStatusData();
        if (currentStatus['alias'] == this.INACTIVE) {
            currentStatus = $(this.dataStatus).filter(function (i, s) {
                return s['alias'] == this.ACTIVE;
            }.bind(this))[0];
        }
        return currentStatus['description'];
    },
    getInactiveDescription: function () {
        var currentStatus = $(this.dataStatus).filter(function (i, s) {
            return s['alias'] == this.INACTIVE;
        }.bind(this))[0];
        return currentStatus['description'];
    },
    getButtonStyle: function () {
        var currentStatus = this.getCurrentStatusData();
        switch (currentStatus['alias']) {
            case this.ACTIVE:
            case this.INACTIVE:
                return 'success';
            case this.BLOCKED:
                return 'warning';
            case this.DELETED:
                return 'danger';
            default:
                return 'default';
        }
    },

    updateStatus: function (status) {
        if (parseInt(this.productId) == 0) return;
        var products = [];
        products.push({
            id: this.productId,
            status: status
        });
        var url = this.baseUrl.concat(this.endpoint).concat('/updateStatus');
        return $.post(url, {data: JSON.stringify({products: products})});
    },
    moveToTrash: function () {
        if (parseInt(this.productId) == 0) return;
        var products = [];
        products.push({
            id: this.productId
        });
        var url = this.baseUrl.concat(this.endpoint).concat('/moveToTrash');
        $.post(url, {data: JSON.stringify({products: products})})
            .then(function (response) {
                response = JSON.parse(response);
                if (response['redirect']) {
                    location.href = response['redirect'];
                }
            }.bind(this)).fail(function (e) {
                var errs = e.responseText.length > 0 ? JSON.parse(e.responseText)['errors'] : [e.statusText];
                var msg = errs[0] ?? '';
                Toast.fire({
                    icon: 'error',
                    title: msg
                });
        });
    }
}


$(document).ready(function () {
    var e = (new ProductStatusComponent({
        target: $('#ProductStatusComponent')
    })).build();
});