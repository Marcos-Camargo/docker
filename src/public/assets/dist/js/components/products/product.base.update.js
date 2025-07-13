var ProductBaseUpdate = function (args) {
    this.baseUrl = args.baseUrl;
    this.endpoint = args.endpoint;

    this.productList = [];
}

ProductBaseUpdate.prototype = {

    addProduct: function (prod) {
        this.productList.push(prod);
        return this;
    }
}