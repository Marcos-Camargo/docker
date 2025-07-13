var RadioTwoOptionsComponent = function (args) {
    this.ACTIVE = '1';
    this.INACTIVE = '0';

    this.component = args.target;
    this.refElement = eval($(this.component).data('refElement'));
    this.currentState = null;

    this.input = null;
};

RadioTwoOptionsComponent.prototype = {

    build: function () {
        var div = $('<div>', {class: 'input-group product-actions'});
        this.input = $('<input>', {
            type: 'checkbox',
            value: this.getInputValue(),
        });
        $(this.input).attr('data-toggle', 'toggle').attr('data-on', this.getActiveDescription())
            .attr('data-off', this.getInactiveDescription())
            .attr('data-onstyle', this.getActiveButtonStyle())
            .attr('data-offstyle', this.getInactiveButtonStyle()).attr('data-width', 100)
            .attr('data-height', 20).attr('data-line-height', 20);
        $(div).append(this.input);
        $(this.component).append(div);

        if (this.isChecked()) {
            $(this.input).prop('checked', true);
        }

        if (!this.isEnabled()) {
            this.disableComponent();
        } else {
            $('input[data-toggle="toggle"]', $(this.component)).bootstrapToggle();
            $('div[data-toggle="toggle"]', $(this.component))
                .off('click')
                .on('click', function (e) {
                    var reverseState = this.isChecked()
                        ? ($(this.component).data('inactiveOption')['value'] ?? '0') : ($(this.component).data('activeOption')['value'] ?? '1');
                    this.currentState = parseInt(reverseState) == 0 ? $(this.component).data('inactiveOption') : $(this.component).data('activeOption');
                    var input = $('input[data-toggle="toggle"]', $(e.currentTarget));
                    $(input).prop('checked', this.isChecked());
                    $(input).trigger('change');
                    $(input).val(this.currentState['value']);
                    this.refElement.val(this.currentState['value']);
                    this.refElement.trigger('change')
                    return true;
                }.bind(this));
        }
        return this;
    },
    getInputValue: function () {
        return this.refElement.val();
    },
    getActiveDescription: function () {
        var currentState = $(this.component).data('activeOption');
        return currentState['description'] ?? '';
    },
    getInactiveDescription: function () {
        var currentState = $(this.component).data('inactiveOption');
        return currentState['description'] ?? '';
    },
    getActiveButtonStyle: function () {
        var currentState = $(this.component).data('activeOption');
        return currentState['color'] ?? '';
    },
    getInactiveButtonStyle: function () {
        var currentState = $(this.component).data('inactiveOption');
        return currentState['color'] ?? '';
    },

    isChecked: function () {
        return $(this.component).data('activeValue') == this.getInputValue();
    },
    isEnabled: function () {
        return $(this.component).data('enabled') == '1';
    },
    disableComponent: function () {
        $(this.input).prop('disabled', true);
        $('input[data-toggle="toggle"]', $(this.component)).bootstrapToggle();
    }
}


$(document).ready(function () {
    var e = (new RadioTwoOptionsComponent({
        target: $('#RadioTwoOptionsComponent')
    })).build();
});