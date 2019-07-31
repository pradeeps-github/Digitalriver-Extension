<?php
/**
 *
 * @category   Diconium
 * @package    Diconium_DigitalRiver
 */
 
namespace Diconium\DigitalRiver\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\App\Config\ScopeConfigInterface;

class TestApi extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Diconium_DigitalRiver::testapi.phtml';

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig, 
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for collect button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        $token = $this->getApiKey();
        if($token) { 
            $token = $this->encryptor->decrypt($token);
            if($this->getIsTestOrder()) { 
                $apiUrl = "https://api-cte-ext.digitalriver.com/v1/site.drivenjson?apiKey=".$token;
            }else{ 
                $apiUrl = "https://api.digitalriver.com/v1/site.drivenjson?apiKey=".$token;
            }
           return $apiUrl;
        }
        return false;
    }
    public function getApiKey() {
        return $this->scopeConfig->getValue('dr_settings/config/dr_api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    public function getIsTestOrder(){
        return $this->scopeConfig->getValue('dr_settings/config/testorder', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Generate collect button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'test_api_button',
                'label' => __('Run Test'),
            ]
        );

        return $button->toHtml();
    }
}
