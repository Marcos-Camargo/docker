<div class="modal fade" tabindex="-1" role="dialog" id="addAttributeMap">
    <form>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="filter_categories"><?= $this->lang->line('application_categories') ?></label>
                            <select class="form-control selectpicker show-tick"
                                    id="filter_categories"
                                    name="categories"
                                    data-live-search="true"
                                    data-style="btn-blue"
                                    title="<?= $this->lang->line('application_search_for_categories'); ?>"
                            ></select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="marketplaces_to_attribute_map"><?= $this->lang->line('application_marketplace') ?></label>
                            <select class="form-control selectpicker show-tick"
                                    id="marketplaces_to_attribute_map"
                                    name="attributes_marketplace"
                                    data-actions-box="true"
                                    multiple="multiple"
                                    data-live-search="true"
                                    data-style="btn-blue"
                                    data-selected-text-format="count > 1"
                                    title="<?= $this->lang->line('application_search_for_marketplaces'); ?>"
                            ></select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label for="attributes_marketplace"><?= $this->lang->line('application_attributes') ?></label>
                            <select class="form-control selectpicker show-tick"
                                    id="attributes_marketplace"
                                    name="attributes_marketplace"
                                    data-actions-box="true"
                                    multiple="multiple"
                                    data-live-search="true"
                                    data-style="btn-blue"
                                    data-selected-text-format="count > 1"
                                    title="<?= $this->lang->line('application_search_for_attributes'); ?>"
                            ></select>
                        </div>

                        <div class="form-group col-md-12">
                            <label for="attrMapValue"><?= lang('application_attribute_map_value') ?></label>
                            <input type="text" class="form-control" name="value" id="attrMapValue" value="" required>
                        </div>
                    </div>
                </div> <!-- modal-body -->
                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-default btn-cancel"><?= $this->lang->line('application_cancel'); ?></button>
                    <button type="button"
                            class="btn btn-primary btn-confirm"
                            disabled><?= $this->lang->line('application_save'); ?></button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </form>
</div><!-- /.modal -->
<script>

    if (!lang) {
        var lang = {};
    }

    AddUpdateAttributeMapModal = function (args) {
        args = args ?? {};
        this.title = args.popupTitle ?? '';
        this.component = $('#addAttributeMap');
        this.deferred = $.Deferred();
        this.data = args.data ?? {};
    };
    AddUpdateAttributeMapModal.prototype = {

        init: function () {
            $('.modal-title').text(this.title);
            $(this.component).modal({
                show: true,
                backdrop: 'static',
                keyboard: false
            });
            $('.btn-confirm', $(this.component)).prop('disabled', true);
            $('input.form-control', $(this.component)).val('');
            this.registerEvents();
            $('input.form-control', $(this.component)).each(function (k, v) {
                var name = $(v).attr('name');
                if (this.data.hasOwnProperty(name)) {
                    $(v).val(this.data[name]);
                    $(v).trigger('keydown');
                }
            }.bind(this));
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
            $('input.form-control[required]', $(this.component)).on('keydown keyup keypress', function (e) {
                if ($(e.currentTarget).val().trim().length > 0) {
                    this.setValidField($(e.currentTarget));
                } else {
                    this.setErrorField($(e.currentTarget));
                }
            }.bind(this));
            $('.form-control', $(this.component)).on('focus blur keypress keydown keyup paste', function (e) {
                if ($('.form-control[required]', this.component).length == $('.form-control[required][valid="true"]', this.component).length) {
                    $('.btn-confirm', $(this.component)).prop('disabled', false);
                    if ($('.form-control[valid="false"]', $(this.component)).length > 0) {
                        $('.btn-confirm', $(this.component)).prop('disabled', true);
                    }
                } else if ($('.form-control[required]', $(this.component)).length != $('.form-control[required][valid="true"]', $(this.component)).length) {
                    $('.btn-confirm', $(this.component)).prop('disabled', true);
                }
            }.bind(this));
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

        setErrorField: function (el) {
            $(el).attr('valid', false).parent().parent().parent().addClass('form-error');
        },
        setValidField: function (el) {
            $(el).attr('valid', true).parent().parent().parent().removeClass('form-error');
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
        getFormValues: function () {
            var values = [];
            $.each($($(this.component).children('form')[0]).serializeArray(), function (i, field) {
                if (field.value.length > 0)
                    values.push(field.value);
            });

            return {
                values: values
            }
        }
    };
</script>