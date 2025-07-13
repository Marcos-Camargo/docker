var ProductMoveTrash = function (args) {
    ProductBaseUpdate.apply(this, [args]);
};

ProductMoveTrash.prototype = $.extend(Object.create(ProductBaseUpdate.prototype), {

    send: function () {
        var url = this.baseUrl.concat(this.endpoint).concat('/moveToTrash');
        return $.post(url, {data: JSON.stringify({products: this.productList})});
    }

});