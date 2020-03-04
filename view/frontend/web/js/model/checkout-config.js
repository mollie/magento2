define(['uiClass'], function (uiClass) {
    var dataContainer = null;

    return function (data) {
        if (data) {
            dataContainer = data;
        }

        return dataContainer;
    };

    return uiClass.extend({
        initialize: function (data) {
            if (data) {
                dataContainer = data;
            }

            this._super(data);
        },

        doSomething: function () {
            return dataContainer;
        }
    });

    var data = null;
    return function (Config) {
        this.data = Config;
    };
});