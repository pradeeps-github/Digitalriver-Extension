/**
 *
 * @category   Diconium
 * @package    Diconium_DrPay
 */

require(['jquery'],
    function($){
         $(document).ready(function(){
            if (window.location.href.indexOf('#payment') > 0) {
             $('.privacy-links').show();
            }
            else{
                 $('.privacy-links').hide();
            }
       })
});




    
