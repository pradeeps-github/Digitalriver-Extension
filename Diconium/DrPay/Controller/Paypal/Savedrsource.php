<?php
/**
 *
 * @category   Diconium
 * @package    Diconium_DrPay
 */

namespace Diconium\DrPay\Controller\Paypal;

use Magento\Framework\Controller\ResultFactory;

class Savedrsource extends \Magento\Framework\App\Action\Action
{

	/**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Diconium\DigitalRiver\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Diconium\DigitalRiver\Helper\Data $helper
    ) {
		$this->helper =  $helper;
		$this->_checkoutSession = $checkoutSession;
		parent::__construct($context);
    }

    public function execute()
    {
        $responseContent = [
            'success'        => false
        ];      
        if($this->getRequest()->getParam('source_id')){
            $source_id = $this->getRequest()->getParam('source_id');
            $paymentResult = $this->helper->applyQuotePayment($source_id);
            if($paymentResult){
                $responseContent = [
                    'success'        => true,
                    'content'        => $paymentResult
                ];            
            }
        } 
		$response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($responseContent);

        return $response;
    }
}
