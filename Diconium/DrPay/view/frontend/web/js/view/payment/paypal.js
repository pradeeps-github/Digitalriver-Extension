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
            drPaypal = 'drpay_paypal';
        if (config[drPaypal].is_active) {
            rendererList.push(
                {
                    type: drPaypal,
                    component: 'Diconium_DrPay/js/view/payment/method-renderer/paypal'
                }
            );
        }

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
