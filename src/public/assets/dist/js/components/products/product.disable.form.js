var ProductDisableForm = function (args) {
    this.form = args.form;

    this.disableElmConfigs = {
        'input': {props: {disabled: true, readonly: true}},
        'textarea': {props: {disabled: true, readonly: true}},
        'select': {props: {disabled: true, readonly: true}},
        'button': {props: {disabled: true}},
        '.alert': {
            action: function (el) {
                return $(el).hide();
            }
        }
    }
};

ProductDisableForm.prototype = {

    disableForm: function () {
        $.each(this.disableElmConfigs, function (el, config) {
            if (config['props']) {
                $($(el), this.form).prop(config['props']);
            } else if (config['action']) {
                config['action']($(el, this.form));
            }
        }.bind(this));
    }
};
