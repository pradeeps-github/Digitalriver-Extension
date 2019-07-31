/**
 *
 * @category   Diconium
 * @package    Diconium_DrPay
 */
/*browser:true*/
/*global define*/

define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        var config = window.checkoutConfig.payment,
            drCreditcard = 'drpay_creditcard';
        if (config[drCreditcard].is_active) {
            rendererList.push(
                {
                    type: drCreditcard,
                    component: 'Diconium_DrPay/js/view/payment/method-renderer/creditcard'
                }
            );
        }

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
