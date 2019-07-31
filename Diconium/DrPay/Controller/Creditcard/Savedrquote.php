<?php
/**
 *
 * @category   Diconium
 * @package    Diconium_DrPay
 */
 
namespace Diconium\DrPay\Controller\Creditcard;

use Magento\Framework\Controller\ResultFactory;

class Savedrquote extends \Magento\Framework\App\Action\Action
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
        $quote = $this->_checkoutSession->getQuote();
        $cartResult = $this->helper->createFullCartInDr($quote, 1);

        if($cartResult){
            $responseContent = [
                'success'        => true,
                'content'        => $cartResult
            ];            
        }
		$response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($responseContent);

        return $response;
    }
}
