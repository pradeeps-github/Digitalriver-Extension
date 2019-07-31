<?php
/**
 *
 * @category   Diconium
 * @package    Diconium_DigitalRiver
 */
 
namespace Diconium\DigitalRiver\Helper;

use Magento\Framework\App\Helper\Context;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $session;
	protected $_storeManager;
	protected $regionModel;
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var Session
     */
    private $customerSession;

    public function __construct(Context $context, 
		\Magento\Checkout\Model\Session $session,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
		\Magento\Quote\Api\CartManagementInterface $cartManagement,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Checkout\Helper\Data $checkoutHelper, 
		\Magento\Framework\Encryption\EncryptorInterface $enc,
		\Magento\Directory\Model\Region $regionModel
	) {
        $this->session = $session;
		$this->_storeManager = $storeManager;
 		$this->productRepository = $productRepository;		
		$this->cartManagement = $cartManagement;
		$this->customerSession = $customerSession;
		$this->checkoutHelper = $checkoutHelper;
 		$this->regionModel = $regionModel;		
 		$this->_enc = $enc;
        parent::__construct($context);
    }

/*    public function generateSessionToken()
    {		
		$url = $this->getDrStoreUrl()."SessionToken";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

		$result = curl_exec($ch);
		curl_close($ch);
		$result = json_decode($result, true);
		$token = '';
		if(isset($result["session_token"])){
			$token = $result["session_token"];
		}
		return $token;
    }

    public function generateAccessToken()
    {
		$sessionToken = $this->generateSessionToken();
		$token = '';
		if($sessionToken){	
			$url = $this->getDrBaseUrl()."oauth20/token";
			$data = array("dr_session_token" => $sessionToken, "grant_type" => "password", "format" => "json");
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERPWD, $this->getDrAuthUsername() . ":" . $this->getDrAuthPassword());
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
			$result = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($result, true);
			if(isset($result["access_token"])){
				$token = $result["access_token"];
			}
			return $token;
		}
		return $token;
    }
*/	

    public function convertTokenToFullAccessToken()
    {		
		$quote = $this->session->getQuote();
		$external_reference_id = $quote->getId();
		$address = $quote->getShippingAddress();
		$external_reference_id = $address->getEmail().$external_reference_id;
		$this->createShopperInDr($quote,$external_reference_id);		
		if($external_reference_id){
			$fillAccessToken = '';
			$url = $this->getDrBaseUrl()."oauth20/token";
			// $data = array("dr_external_reference_id" => $external_reference_id, "grant_type" => "client_credentials", "dr_limited_token" => $accessToken, "format" => "json");
			$data = array("grant_type" => "client_credentials", "dr_external_reference_id" => $external_reference_id, "format" => "json");
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERPWD, $this->getDrAuthUsername() . ":" . $this->getDrAuthPassword());
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
			$result = curl_exec($ch);
			$fulldir        = explode('app/code',dirname(__FILE__));
	        $logfilename    = $fulldir[0] . 'var/log/digitalriver.log';
	        file_put_contents($logfilename, "Full Access Token ".json_encode($result)."\r\n", FILE_APPEND);
			curl_close($ch);
			$result = json_decode($result, true);//print_r($result);die;
			if(isset($result["access_token"])){
				$fillAccessToken = $result["access_token"];
			}
			if($fillAccessToken){
				$this->session->setDrAccessToken($fillAccessToken);
			}
			return $fillAccessToken;
		}
    }

    public function createShopperInDr($quote,$external_reference_id)
    {
		if($external_reference_id){
			$address = $quote->getBillingAddress();
			$firstname = $address->getFirstname();
			$lastname = $address->getLastname();
			$email = $address->getEmail();
			$username = $external_reference_id;
			$currency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
			$apikey = $this->getDrApiKey();
			$locale = $this->getLocale();
			$url = $this->getDrBaseUrl()."v1/shoppers?apiKey=".$apikey."&format=json";
			$data = "<shopper><firstName>".$firstname."</firstName><lastName>".$lastname."</lastName><externalReferenceId>".$username."</externalReferenceId><username>".$username."</username><emailAddress>".$email."</emailAddress><locale>".$locale."</locale><currency>".$currency."</currency></shopper>";
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
			$result = curl_exec($ch);
			$fulldir        = explode('app/code',dirname(__FILE__));
	        $logfilename    = $fulldir[0] . 'var/log/digitalriver.log';
	        file_put_contents($logfilename, "Create Shopper Request ".json_encode($data)."\r\n", FILE_APPEND); 
	        file_put_contents($logfilename, "Create Shopper Result ".json_encode($result)."\r\n", FILE_APPEND); 
			curl_close($ch);
		}
		return;
    }

/*    public function updateAccessTokenCurrency($accessToken, $currentCurrency)
    {
		if($accessToken){	
			$locale = $this->getLocale();
			$url = $this->getDrBaseUrl()."v1/shoppers/me?locale=".$locale."&currency=".$currentCurrency."&format=json";
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $accessToken));
			 
			$result = curl_exec($ch);
			curl_close($ch);
		}
		return;
    }

	public function checkDrAccessTokenValidation($token){
		if($token){			
			$url = $this->getDrBaseUrl()."oauth20/access-tokens?token=".$token."&format=json";
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			 
			$result = curl_exec($ch);
			curl_close($ch);
			
			$result = json_decode($result, true);
			if($result["authenticated"] == "false"){
				$this->convertTokenToFullAccessToken();
			}
			$currency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
			if($result["currency"] != $currency){
				$this->updateAccessTokenCurrency($token, $currency);
			}
			if(isset($result["expiresIn"]) && $result["expiresIn"] > 1000){
				return true;
			}
		}
		return false;
	}
*/
    public function createFullCartInDr($quote, $return = null)
    {
		if($this->session->getDrAccessToken()){
			$accessToken = $this->session->getDrAccessToken();
		}else{
			$accessToken = $this->convertTokenToFullAccessToken();
			$this->session->setDrAccessToken($accessToken);
		}
		$token = '';
		if($accessToken){	
			$this->deleteDrCartItems($accessToken);
			$testorder = $this->getIsTestOrder();
			if($testorder){
				$url = $this->getDrBaseUrl()."v1/shoppers/me/carts/active?format=json&skipOfferArbitration=true&testOrder=true";
			}else{				
				$url = $this->getDrBaseUrl()."v1/shoppers/me/carts/active?format=json&skipOfferArbitration=true";
			}
			$data = array();
			$orderLevelExtendedAttribute = array('name' => 'OrderLevelExtendedAttribute1', 'value' => 'test01');

			$data["cart"]["customAttributes"]["attribute"] = $orderLevelExtendedAttribute;
			$lineItems = array();
			$currency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
			$baseCurrencyCode = $this->_storeManager->getStore()->getBaseCurrencyCode();
			foreach($quote->getAllItems() as $item){
				if($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
					continue;
				}

				$item = ($item->getParentItemId())?$item->getParentItem():$item;
				$lineItem =  array();
				$lineItem["quantity"] = $item->getQty();
				$price = $item->getPriceInclTax();
				if($item->getDiscountAmount() > 0){
					$price = $price - ($item->getDiscountAmount()/$item->getQty());
				}
				if($price <= 0){
					$price = 0;
				}
				$lineItem["product"] = array('id' => $item->getSku());
				$lineItem["product"] = array('id' => '5321623900');	
				$lineItem["pricing"]["salePrice"] = array('currency' => $currency, 'value' => round($price,2));
				$lineItemLevelExtendedAttribute = array('name' => 'LineItemLevelExtendedAttribute1', 'value' => 'litest01');
				$lineItem["customAttributes"]["attribute"] = $lineItemLevelExtendedAttribute;
				$lineItems["lineItem"][] = $lineItem;
			}
			$data["cart"]["lineItems"] = $lineItems;		
			// print_r($data);die;
			$address = $quote->getBillingAddress();
			if($address && $address->getId() && $address->getCity()){
				$billingAddress =  array();
				$billingAddress["id"] = "billingAddress";
				$billingAddress["firstName"] = $address->getFirstname();
				$billingAddress["lastName"] = $address->getLastname();
				$street = $address->getStreet();
				if(isset($street[0])){
					$billingAddress["line1"] = $street[0];
				}else{
					$billingAddress["line1"] = "";
				}
				if(isset($street[1])){
					$billingAddress["line2"] = $street[1];
				}else{
					$billingAddress["line2"] = "";
				}
				$billingAddress["line3"] = "";
				$billingAddress["city"] = $address->getCity();
				$billingAddress["countrySubdivision"] = '';
				$regionName = $address->getRegion();
				if($regionName){
					$countryId = $address->getCountryId();
					$region = $this->regionModel->loadByName($regionName, $countryId);
					$billingAddress["countrySubdivision"] = $region->getCode();
				}
				$billingAddress["postalCode"] = $address->getPostcode();
				$billingAddress["country"] = $address->getCountryId();
				$billingAddress["countryName"] = $address->getCountryId();
				$billingAddress["phoneNumber"] = $address->getTelephone();
				$billingAddress["emailAddress"] = $address->getEmail();
				$billingAddress["companyName"] = $address->getCompany();

				$data["cart"]["billingAddress"] = $billingAddress;
				if($quote->getIsVirtual()){
					$billingAddress["id"] = "shippingAddress";
					$data["cart"]["shippingAddress"] = $billingAddress;
				}else{
					$address = $quote->getShippingAddress();
					$shippingAddress =  array();
					$shippingAddress["id"] = "shippingAddress";
					$shippingAddress["firstName"] = $address->getFirstname();
					$shippingAddress["lastName"] = $address->getLastname();
					$street = $address->getStreet();
					if(isset($street[0])){
						$shippingAddress["line1"] = $street[0];
					}else{
						$shippingAddress["line1"] = "";
					}
					if(isset($street[1])){
						$shippingAddress["line2"] = $street[1];
					}else{
						$shippingAddress["line2"] = "";
					}
					$shippingAddress["line3"] = "";
					$shippingAddress["city"] = $address->getCity();
					$shippingAddress["countrySubdivision"] = '';
					$regionName = $address->getRegion();
					if($regionName){
						$countryId = $address->getCountryId();
						$region = $this->regionModel->loadByName($regionName, $countryId);
						$shippingAddress["countrySubdivision"] = $region->getCode();
					}
					$shippingAddress["postalCode"] = $address->getPostcode();
					$shippingAddress["country"] = $address->getCountryId();
					$shippingAddress["countryName"] = $address->getCountryId();
					$shippingAddress["phoneNumber"] = $address->getTelephone();
					$shippingAddress["emailAddress"] = $address->getEmail();
					$shippingAddress["companyName"] = $address->getCompany();

					$data["cart"]["shippingAddress"] = $shippingAddress;					
				}
			}
			if($quote->getIsVirtual()){
				$shippingAmount = 0;
				$shippingTitle = "Shipping Price";
			}else{
				$shippingAmount = $quote->getShippingAddress()->getShippingAmount();
				$shippingTitle = $quote->getShippingAddress()->getShippingDescription();
			}
			if($shippingAmount > 0){
				$shippingDetails =  array();
				$shippingDetails["shippingOffer"]["offerId"] = "62926862701";
				$shippingDetails["shippingOffer"]["customDescription"] = $shippingTitle;
				$shippingDetails["shippingOffer"]["overrideDiscount"]["discount"] = round($shippingAmount,2);
				$shippingDetails["shippingOffer"]["overrideDiscount"]["discountType"] = "amount";
				$data["cart"]["appliedOrderOffers"] = $shippingDetails;
			}
			//print_r(json_encode($data));die;
			$data = $this->encryptRequest(json_encode($data));
			//$data = json_encode($data);
			//print_r($data);die;
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', "Authorization: Bearer " . $accessToken));
			 
			$result = curl_exec($ch);
			$fulldir        = explode('app/code',dirname(__FILE__));
	        $logfilename    = $fulldir[0] . 'var/log/digitalriver.log';
	        file_put_contents($logfilename, "Create Cart Result ".json_encode($result)."\r\n", FILE_APPEND); 

			$result = json_decode($result, true);
			//print_r($result);die;
			if(isset($result["errors"])){
				$this->session->setDrQuoteError(true);
				if($return){
					return $result;
				}else{
					return;
				}
			}
			$this->session->setDrQuoteError(false);
			$drquoteId = $result["cart"]["id"];
			$this->session->setDrQuoteId($drquoteId);
			if($return){
				return $result;
			}else{
				return;
			}
		}
		$this->session->setDrQuoteError(true);
		return;
    }	
	//Apply the sourceId to cart which recieved after subit creditcard in checkout
	public function applyQuotePayment($sourceId = null){
		$result = "";
		if($this->session->getDrAccessToken() && $sourceId!=null){
			$accessToken = $this->session->getDrAccessToken();
			$url = $this->getDrBaseUrl()."v1/shoppers/me/carts/active/apply-payment-method?format=json";
			$data["paymentMethod"]["sourceId"] = $sourceId;
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', "Authorization: Bearer " . $accessToken));	
			$result = curl_exec($ch);
			curl_close($ch);
			//print_r($result);die;
			$result = json_decode($result, true);	
		}
		return $result;			
	}
	
	public function encryptRequest($data){
		$key = $this->getEncryptionKey();
		$method = 'AES-128-CBC';
		$encrypt = trim(openssl_encrypt($data, $method, $key, 0,$key));
		return $encrypt;
	}

    public function deleteDrCartItems($accessToken)
    {
		if($accessToken){	
			$url = $this->getDrBaseUrl()."v1/shoppers/me/carts/active/line-items?format=json";
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $accessToken));
			 
			$result = curl_exec($ch);
			curl_close($ch);
		}
		return;
    }

    public function applyShopperToCart($accessToken)
    {
		if($accessToken){	
			$url = $this->getDrBaseUrl()."v1/shoppers/me/carts/active/apply-shopper?format=json";
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', "Authorization: Bearer " . $accessToken));
			$result = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($result, true);
			return $result;
		}
		return;
	}

    public function createOrderInDr($accessToken)
    {
		if($accessToken){	
			$url = $this->getDrBaseUrl()."v1/shoppers/me/carts/active/submit-cart?expand=all&format=json";
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 40); //timeout in seconds
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $accessToken));
			 
			$result = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($result, true);
			return $result;
		}
		return;
    }
	
	/**
     * Execute operation
     *
     * @param Quote $quote
     * @param array $agreement
     * @return void
     * @throws LocalizedException
     */
    public function createOrderInMagento($quote)
    {
        if ($this->getCheckoutMethod($quote) === \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST) {
            $this->prepareGuestQuote($quote);
        }

        $quote->collectTotals();
        $orderId = $this->cartManagement->placeOrder($quote->getId());
        return $orderId;
    }

    /**
     * Get checkout method
     *
     * @param Quote $quote
     * @return string
     */
    private function getCheckoutMethod($quote)
    {
        if ($this->customerSession->isLoggedIn()) {
            return \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER;
        }
        if (!$quote->getCheckoutMethod()) {
            if ($this->checkoutHelper->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
            } else {
                $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
            }
        }
        return $quote->getCheckoutMethod();
    }

    /**
     * Prepare quote for guest checkout order submit
     *
     * @param Quote $quote
     * @return void
     */
    private function prepareGuestQuote($quote)
    {
        $quote->setCustomerId(null)
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
    }

	public function getIsEnabled(){
		return $this->scopeConfig->getValue('dr_settings/config/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getDrStoreUrl(){
		return $this->scopeConfig->getValue('dr_settings/config/session_token_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getDrBaseUrl(){
		return $this->scopeConfig->getValue('dr_settings/config/dr_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getDrApiKey(){
		return $this->_enc->decrypt($this->scopeConfig->getValue('dr_settings/config/dr_api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
	}

	public function getDrAuthUsername(){
		return $this->scopeConfig->getValue('dr_settings/config/dr_auth_username', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getDrAuthPassword(){
		return $this->_enc->decrypt($this->scopeConfig->getValue('dr_settings/config/dr_auth_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
	}

	public function getIsTestMode(){
		return $this->scopeConfig->getValue('dr_settings/config/testmode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getIsTestOrder(){
		return $this->scopeConfig->getValue('dr_settings/config/testorder', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getEncryptionKey(){
		return $this->scopeConfig->getValue('dr_settings/config/encryption_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getLocale(){
		return $this->scopeConfig->getValue('dr_settings/config/locale', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}

	public function getIframeUrl(){
		$testorder = $this->getIsTestMode();
		if($testorder){
			$url = $this->getDrStoreUrl().'DisplayCreateAddressPaymentInfoPage?authenticated=false&address=false&env=base&api=api-cte-ext';
		}else{				
			$url = $this->getDrStoreUrl().'DisplayCreateAddressPaymentInfoPage?authenticated=false&address=false&env=design&api=api';
		}
		return $url;
	}
}
