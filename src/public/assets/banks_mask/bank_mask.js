function applyBankMask(bank_name){
    $.each(banks, function(i,bank){
        if(banks[i].name == bank_name) {
            var pattern = /[a-zA-Z0-9]/ig;
            mask_account = banks[i].mask_account.replaceAll(pattern, "#")
            mask_agency = banks[i].mask_agency.replace(pattern, "#")
            $('#agency').mask(mask_agency);
            $('#agency').attr("placeholder", banks[i].mask_agency);
            $('#agency').attr("maxlength", mask_agency.length);
            $('#agency').attr("minlength", mask_agency.length);
            $('#account').mask(mask_account);
            $('#account').attr("placeholder", banks[i].mask_account);
            $('#account').attr("maxlength", mask_account.length);
            $('#account').attr("minlength", mask_account.length);
        }
    });
}