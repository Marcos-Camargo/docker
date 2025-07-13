var ProductUpdateStatus = function (args) {
    ProductBaseUpdate.apply(this, [args]);
};

ProductUpdateStatus.prototype = $.extend(Object.create(ProductBaseUpdate.prototype), {

    send: function () {
        var url = this.baseUrl.concat(this.endpoint).concat('/updateStatus');
        return $.post(url, {data: JSON.stringify({products: this.productList})});
    }
});