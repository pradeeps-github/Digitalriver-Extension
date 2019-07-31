<?php
/**
 *
 * @category   Diconium
 * @package    Diconium_DigitalRiver
 */

namespace Diconium\DigitalRiver\Plugin;

class QuotePlugin
{
	protected $drHelper;
	
	public function __construct( 
        \Diconium\DigitalRiver\Helper\Data $drHelper 
    ){
		 $this->drHelper= $drHelper;
	}

    /**
     * Set shipping address
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return $this
     */
    public function afterSetShippingAddress( 
        \Magento\Quote\Model\Quote $subject, 
        $result, 
        $address
    )
    { 
        if(!$subject->isVirtual()){ 
            // Create Shopper and get Full access token
            $this->drHelper->convertTokenToFullAccessToken();
            //Create the cart in DR
            $this->drHelper->createFullCartInDr($subject);
        }
        return $result;
    } 

    /**
     * Set billing address.
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return $this
     */
    public function afterSetBillingAddress( 
        \Magento\Quote\Model\Quote $subject,
        $result, 
        $address
    )
    { 
        if($subject->isVirtual()){        
            // Create Shopper and get Full access token
            $this->drHelper->convertTokenToFullAccessToken();
            //Create the cart in DR
            $this->drHelper->createFullCartInDr($subject);
        } 
        return $result;
    }
}
