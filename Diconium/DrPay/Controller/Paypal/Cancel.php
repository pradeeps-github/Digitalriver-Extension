<?php
/**
 *
 * @category   Diconium
 * @package    Diconium_DrPay
 */
namespace Diconium\DrPay\Controller\Paypal;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\Context;

class Cancel extends \Magento\Framework\App\Action\Action
{
	/**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var Order
     */
    protected $order;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     */

    public function __construct(
		Context $context, 
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Sales\Model\Order $order,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Diconium\DigitalRiver\Helper\Data $helper
    ){
        $this->customerSession = $customerSession;
        $this->order = $order;
        $this->helper =  $helper;
        $this->checkoutSession = $checkoutSession;
        return parent::__construct($context);
    }
    
    /**
     * Canceled paypal transaction
     *
     * @return void
     */
    public function execute()
    {
        $fulldir        = explode('app/code',dirname(__FILE__));
        $logfilename    = $fulldir[0] . 'var/log/drpay-paypal.log';       
        $orderId = $this->checkoutSession->getLastOrderId();    
        $order = $this->order->load($orderId);
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $this->messageManager->addError(__('Unable to Place Order!! Payment has been failed'));
        file_put_contents($logfilename, "Paypal Order canceled "." OrderId ".$order->getId(). "\r\n", FILE_APPEND);
        if($order && $order->getId()){
            $order->cancel()->save();
            /* @var $cart \Magento\Checkout\Model\Cart */
            $cart = $this->_objectManager->get(\Magento\Checkout\Model\Cart::class);
            $items = $order->getItemsCollection();
            foreach ($items as $item) {
                try {
                    $cart->addOrderItem($item);
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    if ($this->_objectManager->get(\Magento\Checkout\Model\Session::class)->getUseNotice(true)) {
                        $this->messageManager->addNoticeMessage($e->getMessage());
                    } else {
                        $this->messageManager->addErrorMessage($e->getMessage());
                    }
                    return $resultRedirect->setPath('checkout/cart');
                } catch (\Exception $e) {
                    $this->messageManager->addExceptionMessage(
                        $e,
                        __('We can\'t add this item to your shopping cart right now.')
                    );
                    return $resultRedirect->setPath('checkout/cart');
                }
            }
            $cart->save();            
        }
        return $resultRedirect->setPath('checkout/cart');
    }
}
