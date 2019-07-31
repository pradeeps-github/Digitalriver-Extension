<?php
/**
 *
 * @category   Diconium
 * @package    Diconium_DrPay
 */

namespace Diconium\DrPay\Controller\Paypal;

use Magento\Framework\Controller\ResultFactory;

class Savedrquote extends \Magento\Framework\App\Action\Action
{
    protected $regionModel;
	/**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
		\Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Directory\Model\Region $regionModel,
		\Diconium\DigitalRiver\Helper\Data $helper
    ) {
		$this->helper =  $helper;
		$this->_checkoutSession = $checkoutSession;
        $this->regionModel = $regionModel;
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
            $payload = array();
            $returnurl = $this->_url->getUrl('drpay/paypal/success');
            $cancelurl = $this->_url->getUrl('drpay/paypal/cancel');  
            $itemsArr = array();
            $shipping = array();
            $itemPrice = 0;
            $taxAmnt = 0;
            $shipAmnt = 0;
            foreach($quote->getAllItems() as $item){
                if($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
                    $itemPrice = $item->getPrice();
                    continue;
                }
                $itemsArr = array(
                    'name' => $item->getName(),
                    'quantity' => $item->getQty(),
                    'unitAmount' => ($itemPrice>0)?$itemPrice:$item->getPrice(),
                );
            }
            $address = $quote->getShippingAddress();
            if($quote->isVirtual()){
                $address = $quote->getBillingAddress();
            }
            if($address && $address->getId()){
				$shipAmnt = $address->getShippingAmount();
				$taxAmnt = $address->getTaxAmount();
                $shipping =  array();
                $street = $address->getStreet();
                if(isset($street[0])){
                    $street1 = $street[0];
                }else{
                    $street1 = "";
                }
                if(isset($street[1])){
                    $street2 = $street[1];
                }else{
                    $street2 = "";
                }
                $state = 'na';
                $regionName = $address->getRegion();
                if($regionName){
                    $countryId = $address->getCountryId();
                    $region = $this->regionModel->loadByName($regionName, $countryId);
                    $state = $region->getCode();
                }

                $shipping = array (
                        'recipient' => $address->getFirstname()." ".$address->getLastname(),
                        'phoneNumber' => $address->getTelephone(),
                        'address' => array (
                            'line1' => $street1,
                            'line2' => $street2,
                            'city' => (null !== $address->getCity())?$address->getCity():'na',
                            'state' => $state,
                            'country' => $address->getCountryId(),
                            'postalCode' => $address->getPostcode(),
                      ),
                    );
            }


        
            //Prepare the payload and return in response for DRJS paypal payload
            $payload['payload'] = array(
                'type' => 'payPal',
                'amount' => (int)round($quote->getGrandTotal()),
                'currency' => $quote->getQuoteCurrencyCode(),
                'payPal' => array (
                    'returnUrl' => $returnurl,
                    'cancelUrl' => $cancelurl,
                    'items' => array($itemsArr),
                    'taxAmount' => $taxAmnt,
                    'shippingAmount' => $shipAmnt,
                    'amountsEstimated' => true,
                    'shipping' => $shipping,                    
                ),
            );
            $responseContent = [
                'success'        => true,
                'content'        => $payload
            ];            
        }
		$response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($responseContent);

        return $response;
    }
}
