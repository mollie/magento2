define(
    [
        'ko',
        'Magento_Ui/js/model/messages'
    ],
    function (ko, Messages) {
        'use strict';

        /**
         * `Magento_Ui/js/model/messages` only knows errors and successes, so a notice added by Mollie would end up
         * in a red box. This adds the third list, rendered by `Mollie_Payment/messages`.
         */
        return Messages.extend(
            {
                initObservable: function () {
                    this._super();

                    this.noticeMessages = ko.observableArray([]);

                    return this;
                },
                addNoticeMessage: function (message) {
                    return this.add(message, this.noticeMessages);
                },
                getNoticeMessages: function () {
                    return this.noticeMessages;
                },
                hasMessages: function () {
                    return this._super() || this.noticeMessages().length > 0;
                },
                clear: function () {
                    this._super();

                    this.noticeMessages.removeAll();
                }
            }
        );
    }
);
