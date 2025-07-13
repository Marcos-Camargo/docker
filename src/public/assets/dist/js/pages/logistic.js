const getFieldCredentials = async (baseUrl, integration, formBtnSave, cardTabSeller = false, new_format = false) => {
    let cardCredentials = '';
    let nameField = '';
    let response;
    const idEnd = cardTabSeller ? '_card_seller' : '';

    cardCredentials += formBtnSave === '' ? '' : (new_format ? '<div>' : `<div id="${integration}${idEnd}" class="panel-collapse collapse col-md-12">`);

    response = await $.ajax({
        url: `${baseUrl}/logistics/getFieldsForm/${integration}`,
        async: true,
        type: 'GET'
    });

    for await (let [key, value] of Object.entries(response)) {

        nameField = await $.ajax({
            url: `${baseUrl}/Api/Language/${value.name}`,
            async: true,
            type: 'GET',
        });

        nameField = nameField.text

        switch (value.type) {
            case 'text':
            case 'password':
            case 'number':
            case 'url':
                cardCredentials += `
                                <div class="form-group col-md-12 no-padding">
                                    <label for="${key}_${integration}">${nameField}</label>
                                    <input type="${value.type}" class="form-control" name="${key}" id="${key}_${integration}" required autocomplete="one-time-code">
                                </div>
                            `;

                break;
            case 'radio':
                cardCredentials += `<div class="form-group col-md-12 no-padding"><label>${nameField}</label><div class="d-flex justify-content-around">`;

                for (let [app_lang, field_name] of Object.entries(value.values)) {

                    nameField = await $.ajax({
                        url: `${baseUrl}/Api/Language/${app_lang}`,
                        async: true,
                        type: 'GET',
                    });
                    nameField = nameField.text;

                    cardCredentials += `<label><input type="radio" class="form-control icheck_integration_selected${idEnd}" id="${key}_${integration}_${field_name}${idEnd}" name="${key}" value="${field_name}" required> ${nameField}</label>`;
                }
                cardCredentials += `</div></div>`;
                break;
            case 'checkbox':
                cardCredentials += `<div class="form-group col-md-12 no-padding"><label>${nameField}</label><div class="d-flex justify-content-around">`;

                for (let [app_lang, field_name] of Object.entries(value.values)) {

                    nameField = await $.ajax({
                        url: `${baseUrl}/Api/Language/${app_lang}`,
                        async: true,
                        type: 'GET',
                    });
                    nameField = nameField.text;

                    cardCredentials += `<label><input type="checkbox" class="form-control icheck_integration_selected${idEnd}" id="${key}_${integration}_${field_name}${idEnd}" name="${key}" value="${field_name}"> ${nameField}</label>`;
                }
                cardCredentials += `</div></div>`;
                break;
        }
    }

    cardCredentials += formBtnSave === '' ? '' : `${formBtnSave}</div>`;

    return cardCredentials;
}