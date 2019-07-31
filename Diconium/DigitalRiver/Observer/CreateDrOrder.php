<?php
/**
 *
 * @category   Diconium
 * @package    Diconium_DigitalRiver
 */
 
namespace Diconium\DigitalRiver\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class CreateDrOrder implements ObserverInterface
{
	public function __construct(
        \Diconium\DigitalRiver\Helper\Data $helper,
		\Magento\Checkout\Model\Session $session,
		\Magento\Store\Model\StoreManagerInterface $storeManager
    ){
        $this->helper =  $helper;
		$this->session = $session;
		$this->_storeManager = $storeManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer['order'];
        $quote = $observer['quote'];
		$fulldir		= explode('app/code',dirname(__FILE__));
		$logfilename	= $fulldir[0] . 'var/log/dr-flow.log';
		if($quote->getPayment()->getMethod() == \Diconium\DrPay\Model\CreditCard::PAYMENT_METHOD_CREDITCARD_CODE){
			$result = $this->helper->createFullCartInDr($quote, true);
			$accessToken = $this->session->getDrAccessToken();
			if($this->session->getDrQuoteError()){
				file_put_contents($logfilename, "error ".$accessToken.'----'.json_encode($result["errors"]) . "\r\n", FILE_APPEND);
				throw new CouldNotSaveException(__('Unable to Place Order'));
			}else{
				$totals = $result["cart"]["pricing"]["orderTotal"];
				$drCurrency = $totals["currency"];
				$dr_grand_total = (int)round($totals["value"]);
				$currency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
				$grand_total = (int)round($quote->getGrandTotal());
				//if($currency == $drCurrency && $grand_total == $dr_grand_total){ 
					//$result = $this->helper->submitPaymentInDr($accessToken);
					$result = $this->helper->createOrderInDr($accessToken);
					file_put_contents($logfilename, " ".$accessToken.'FULL ORDER ----'.json_encode($result) . "\r\n", FILE_APPEND);					
					/*if(!isset($result)){
							$orderId = $this->session->getDrQuoteId();
							$order->setDrOrderId($orderId);
							$amount = $quote->getDrTax();
							$order->setDrTax($amount);
					}*/
					if($result && isset($result["errors"])){
						file_put_contents($logfilename, "error ".$accessToken.'----'.json_encode($result["errors"]) . "\r\n", FILE_APPEND);
						//print_r($result);
						throw new CouldNotSaveException(__('Unable to Place Order'));
					}else{
						if(isset($result["submitCart"]["order"]["id"])){
							$order->setState("processing");
							$order->setStatus("processing");
							//Store the drOrderid in database
							$orderId = $result["submitCart"]["order"]["id"];
							$order->setDrOrderId($orderId);
							/*$amount = $quote->getDrTax();
							$order->setDrTax($amount);*/
						}
					}
			}
		}
    }
}
