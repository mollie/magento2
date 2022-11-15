define(['uiClass'], function (uiClass) {
    var dataContainer = null;

    return function (data) {
        if (data) {
            dataContainer = data;
        }

        return dataContainer;
    };
});
