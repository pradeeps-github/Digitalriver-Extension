<?php
/**
 *
 * @category   Diconium
 * @package    Diconium_DrPay
 */
 
namespace Diconium\DrPay\Model\PayPal;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;

class ConfigProvider implements ConfigProviderInterface
{

    const PAYMENT_METHOD_CREDITCARD_CODE = 'drpay_paypal';
    /**
     * @var string[]
     */
    protected $_methodCode = self::PAYMENT_METHOD_CREDITCARD_CODE;

    /**
     * $_method.
     *
     * @var Magento\Payment\Helper\Data
     */
    protected $_method;
    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * __construct constructor.
     *
     * @param PaymentHelper                              $paymentHelper
     * @param \Magento\Framework\UrlInterface            $urlBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Escaper                                    $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper
    ) {
        $this->_method = $paymentHelper->getMethodInstance($this->_methodCode);
        $this->escaper = $escaper;
    }

    /**
     * getConfig function to return cofig data to payment renderer.
     *
     * @return []
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                'drpay_paypal' => [
                    'js_url' => $this->_method->getJsUrl(),
                    'public_key' => $this->_method->getPublicKey(),
                    'is_active' => $this->_method->isAvailable(),
                    'title' => $this->_method->getTitle(),
                ],
            ],
        ];
		if ($this->_method->isAvailable()) {
			$config['payment']['instructions'][$this->_methodCode] = $this->getInstructions($this->_methodCode);
		}

        return $config;
    }
	/**
     * Get instructions text from config
     *
     * @param string $code
     * @return string
     */
    protected function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->_method->getInstructions()));
    }
}
