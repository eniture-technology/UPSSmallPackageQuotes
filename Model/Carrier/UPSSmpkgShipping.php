<?php
namespace Eniture\UPSSmallPackageQuotes\Model\Carrier;

use Eniture\UPSSmallPackageQuotes\Helper\Data;
use Eniture\UPSSmallPackageQuotes\Helper\EnConstants;
use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

/**
 * Class UPSSmpkgShipping create and processes quotes request
 */
class UPSSmpkgShipping extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    public $_code = EnConstants::APP_CODE;

    /**
     * @var bool
     */
    public $isFixed = true;

    /**
     * @var ResultFactory
     */
    public $rateResultFactory;

    /**
     * @var MethodFactory
     */
    public $rateMethodFactory;

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var Data
     */
    public $dataHelper;

    /**
     * @var Registry
     */
    public $registry;

    /**
     * @var Manager
     */
    public $moduleManager;

    /**
     * @var
     */
    public $qty;

    /**
     * @var SessionManagerInterface
     */
    public $session;

    /**
     * @var ProductFactory
     */
    public $productLoader;

    /**
     * @var
     */
    public $mageVersion;

    /**
     * @var ObjectManagerInterface
     */
    public $objectManager;
    /**
     * @var Cart
     */
    public $cart;
    /**
     * @var UrlInterface
     */
    public $urlInterface;
    /**
     * @var UPSSmpkgAdminConfiguration
     */
    public $UPSAdminConfig;
    /**
     * @var UPSSmpkgShipmentPackage
     */
    public $UPSShipPkg;
    /**
     * @var UPSSmpkgGenerateRequestData
     */
    public $UPSReqData;
    /**
     * @var UPSSmallSetCarriersGlobaly
     */
    public $UPSSetGlobalCarrier;
    /**
     * @var UPSSmpkgManageAllQuotes
     */
    public $upsMangQuotes;
    /**
     * @var RequestInterface
     */
    public $httpRequest;


    /**
     * UPSSmpkgShipping constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param Cart $cart
     * @param Data $dataHelper
     * @param Registry $registry
     * @param Manager $moduleManager
     * @param UrlInterface $urlInterface
     * @param SessionManagerInterface $session
     * @param ProductFactory $productloader
     * @param ProductMetadataInterface $productMetadata
     * @param ObjectManagerInterface $objectmanager
     * @param UPSSmpkgAdminConfiguration $UPSAdminConfig
     * @param UPSSmpkgShipmentPackage $UPSShipPkg
     * @param UPSSmpkgGenerateRequestData $UPSReqData
     * @param UPSSmallSetCarriersGlobaly $UPSSetGlobalCarrier
     * @param UPSSmpkgManageAllQuotes $upsMangQuotes
     * @param RequestInterface $httpRequest
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        Cart $cart,
        Data $dataHelper,
        Registry $registry,
        Manager $moduleManager,
        UrlInterface $urlInterface,
        SessionManagerInterface $session,
        ProductFactory $productloader,
        ProductMetadataInterface $productMetadata,
        ObjectManagerInterface $objectmanager,
        UPSSmpkgAdminConfiguration $UPSAdminConfig,
        UPSSmpkgShipmentPackage $UPSShipPkg,
        UPSSmpkgGenerateRequestData $UPSReqData,
        UPSSmallSetCarriersGlobaly $UPSSetGlobalCarrier,
        UPSSmpkgManageAllQuotes $upsMangQuotes,
        RequestInterface $httpRequest,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->scopeConfig = $scopeConfig;
        $this->cart = $cart;
        $this->dataHelper = $dataHelper;
        $this->registry = $registry;
        $this->moduleManager = $moduleManager;
        $this->urlInterface = $urlInterface;
        $this->session = $session;
        $this->productLoader = $productloader;
        $this->mageVersion = $productMetadata->getVersion();
        $this->objectManager = $objectmanager;
        $this->UPSAdminConfig = $UPSAdminConfig;
        $this->UPSShipPkg = $UPSShipPkg;
        $this->UPSReqData = $UPSReqData;
        $this->UPSSetGlobalCarrier = $UPSSetGlobalCarrier;
        $this->upsMangQuotes = $upsMangQuotes;
        $this->httpRequest = $httpRequest;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }


    /**
     * @param RateRequest $request
     * @return mixed
     */
    public function collectRates(RateRequest $request)
    {

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        if (empty($request->getDestPostcode()) || empty($request->getDestCountryId())
            || empty($request->getDestCity()) || empty($request->getDestRegionId())) {
            return false;
        }
        // set base currency
        if ($this->registry->registry('baseCurrency') === null) {
            $this->registry->register('baseCurrency', $this->dataHelper->getBaseCurrencyCode());
        }
        // Admin Configuration Class call
        $this->UPSAdminConfig->_init($this->scopeConfig, $this->registry);

        $ItemsList = $request->getAllItems();
        $receiverZipCode = $request->getDestPostcode();

        $package = $this->getUPSSmpkgShipmentPackage($ItemsList, $receiverZipCode, $request);

        //Generate Request Data Class Initialization
        $this->UPSReqData->_init(
            $this->scopeConfig,
            $this->registry,
            $this->moduleManager,
            $this->dataHelper,
            $this->httpRequest
        );
        $upsSmpkgArr = $this->UPSReqData->generateUPSSmpkgArray(
            $request,
            $package['origin']
        );

        $upsSmpkgArr['originAddress'] = $package['origin'];

        $this->UPSSetGlobalCarrier->_init($this->dataHelper);
        $resp = $this->UPSSetGlobalCarrier->manageCarriersGlobaly($upsSmpkgArr, $this->registry);

        $getQuotesFromSession = $this->quotesFromSession();
        if (null !== $getQuotesFromSession) {
            return $getQuotesFromSession;
        }

        if (!$resp) {
            return false;
        }

        $requestArr = $this->UPSReqData->generateRequestArray(
            $request,
            $upsSmpkgArr,
            $package['items'],
            $this->cart
        );

        if (empty($requestArr)) {
            return false;
        }

        $url    = EnConstants::QUOTES_URL;
        $quotes = $this->dataHelper->upsSmpkgSendCurlRequest($url, $requestArr);

        // Debug point will print data if en_print_query=1
        if ($this->printQuery()) {
            $printData = ['url' => $url,
                'buildQuery' => http_build_query($requestArr),
                'request' => $requestArr,
                'quotes' => $quotes];
            print_r('<pre>');
            print_r($printData);
            print_r('</pre>');
            return;
        }
        $this->upsMangQuotes->_init(
            $quotes,
            $this->dataHelper,
            $this->scopeConfig,
            $this->registry,
            $this->session,
            $this->moduleManager,
            $this->objectManager
        );
        $quotesResult = $this->upsMangQuotes->getQuotesResultArr($request);
        $this->session->setEnShippingQuotes($quotesResult);

        $upsSmpkgQuotes = (!empty($quotesResult)) ? $this->setCarrierRates($quotesResult) : '';
        return $upsSmpkgQuotes;
    }

    /**
     * @return type
     */
    public function quotesFromSession()
    {
        $currentAction = $this->urlInterface->getCurrentUrl();
        $currentAction = strtolower($currentAction);
        if (strpos($currentAction, 'shipping-information') !== false
            || strpos($currentAction, 'payment-information') !== false) {
            $availableSessionQuotes = $this->session->getEnShippingQuotes();
            $availableQuotes = (!empty($availableSessionQuotes)) ?
                $this->setCarrierRates($availableSessionQuotes) : null;
        } else {
            $availableQuotes = null;
        }
        return $availableQuotes;
    }

    /**
     * @return type
     */
    public function getAllowedMethods()
    {
        $allowedList = $this->getConfigData('allowed_methods');
        $allowed = empty($allowedList) ? [] : explode(',', $allowedList);
        $arr = [];
        foreach ($allowed as $k) {
            $arr[$k] = $this->getCode('method', $k);
        }

        return $arr;
    }

    /**
     * Get configuration data of carrier
     * @param string $type
     * @param string $code
     * @return array|false
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getCode($type, $code = '')
    {
        $codes = [
            'method' => $this->dataHelper->upsCarriersWithTitle(),
        ];

        if (!isset($codes[$type])) {
            return false;
        } elseif ('' === $code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }

    /**
     * This function returns package array
     * @param $items
     * @param $receiverZipCode
     * @param $request
     * @return array
     */
    public function getUPSSmpkgShipmentPackage($items, $receiverZipCode, $request)
    {
        $this->UPSShipPkg->_init(
            $request,
            $this->scopeConfig,
            $this->dataHelper,
            $this->productLoader,
            $this->httpRequest
        );

        $freightClass = '';

        $weightConfigExeedOpt = $this->scopeConfig->getValue(
            'upsQuoteSetting/third/weightExeeds',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );



        foreach ($items as $key => $item) {

            $_product = $this->productLoader->create()->load($item->getProductId());
            $productType = $item->getRealProductType() ?? $_product->getTypeId();

            $locationId = 0;

            if ($productType == 'simple' || $productType == 'configurable') {
                $productQty = $item->getQty();

                $isEnableLtl = $_product->getData('en_ltl_check');

                $lineItemClass = $_product->getData('en_freight_class');

                if (($isEnableLtl) || ($_product->getWeight() > 150 && $weightConfigExeedOpt)) {
                    $freightClass = 'ltl';
                } else {
                    $freightClass = '';
                }


                //Checking if plan is at least Standard
                $plan = $this->dataHelper->planName();
                if ($plan['planNumber'] < 2) {
                    $insurance = 0;
                    $hazmat = 'N';
                } else {
                    $hazmat = ($_product->getData('en_hazmat')) ? 'Y' : 'N';
                    $insurance = $_product->getData('en_insurance');
                    if ($insurance && $this->registry->registry('en_insurance') === null) {
                        $this->registry->register('en_insurance', $insurance);
                    }
                }
                switch ($lineItemClass) {
                    case 77:
                        $lineItemClass = 77.5;
                        break;
                    case 92:
                        $lineItemClass = 92.5;
                        break;
                    default:
                        break;
                }

                $originAddress = $this->UPSShipPkg->upsSmpkgOriginAddress($_product, $receiverZipCode);

                $hazordousData[][$originAddress['senderZip']] = $this->setHazmatArray($_product, $hazmat);

                $package['origin'][$_product->getId()] = $originAddress;

                $orderWidget[$originAddress['senderZip']]['origin'] = $originAddress;

                $length = ($_product->getData('en_length') != null) ?
                    $_product->getData('en_length') : $_product->getData('ts_dimensions_length');
                $width = ( $_product->getData('en_width') != null) ?
                    $_product->getData('en_width') : $_product->getData('ts_dimensions_width');
                $height = ( $_product->getData('en_height') != null) ?
                    $_product->getData('en_height') : $_product->getData('ts_dimensions_height');

                $lineItems = [
                    'lineItemClass' => ($lineItemClass == 'No Freight Class'
                        || $lineItemClass == 'No') ?
                        0 : $lineItemClass,
                    'freightClass' => $freightClass,
                    'lineItemId' => $_product->getId(),
                    'lineItemName' => $_product->getName(),
                    'piecesOfLineItem' => $productQty,
                    'lineItemPrice' => $_product->getPrice(),
                    'lineItemWeight' => number_format((float)$_product->getWeight(), 2, '.', ''),
                    'lineItemLength' => number_format((float)$length, 2, '.', ''),
                    'lineItemWidth' => number_format((float)$width, 2, '.', ''),
                    'lineItemHeight' => number_format((float)$height, 2, '.', ''),
                    'isHazmatLineItem' => $hazmat,
                    'product_insurance_active' => $insurance,
                    'shipBinAlone' => $_product->getData('en_own_package'),
                    'vertical_rotation' => $_product->getData('en_vertical_rotation'),
                ];

                $package['items'][$_product->getId()] = array_merge($lineItems);
                $orderWidget[$originAddress['senderZip']]['item'][] = $package['items'][$_product->getId()];
            }
        }
        foreach ($orderWidget as $data) {
            $uniqueOrigins [] = $data['origin'];
        }
        $this->setDataInRegistry($uniqueOrigins, $hazordousData, $orderWidget);

        return $package;
    }

    /**
     * @param object $_product
     * @param string $hazmat
     * @return array
     */
    public function setHazmatArray($_product, $hazmat)
    {
        return [
            'lineItemId' => $_product->getId(),
            'isHazordous' => $hazmat == 'Y' ? '1' : '0',
        ];
    }

    /**
     * @param type $origin
     * @param type $hazordousData
     * @param type $setPackageDataForOrderDetail
     */
    public function setDataInRegistry($origin, $hazordousData, $orderWidget)
    {
        // set order detail widget data
        if ($this->registry->registry('setPackageDataForOrderDetail') === null) {
            $this->registry->register('setPackageDataForOrderDetail', $orderWidget);
        }

        // set hazardous data globally
        if ($this->registry->registry('hazardousShipment') === null) {
            $this->registry->register('hazardousShipment', $hazordousData);
        }
        // set shipment origin globally for instore pickup and local delivery
        if ($this->registry->registry('shipmentOrigin') === null) {
            $this->registry->register('shipmentOrigin', $origin);
        }
    }

    /**
     * @param type $quotes
     * @return type
     */
    public function setCarrierRates($quotes)
    {
        $carrersArray = $this->registry->registry('enitureCarrierCodes');
        $carrersTitle = $this->registry->registry('enitureCarrierTitle');
        $result = $this->rateResultFactory->create();

        foreach ($quotes as $carrierkey => $quote) {
            foreach ($quote as $key => $carreir) {
                $method = $this->rateMethodFactory->create();
                $carrierCode = (isset($carrersTitle[$carrierkey])) ? $carrersTitle[$carrierkey] : $this->_code;
                $carrierTitle = (isset($carrersArray[$carrierkey])) ?
                $carrersArray[$carrierkey] : $this->getConfigData('title');
                $method->setCarrierTitle($carrierCode);
                $method->setCarrier($carrierTitle);
                $method->setMethod($carreir['code']);
                $method->setMethodTitle($carreir['title']);
                $method->setPrice($carreir['rate']);

                $result->append($method);
            }
        }

        return $result;
    }

    public function printQuery()
    {
        $printQuery = 0;
        $query = '';
        if(!empty($this->httpRequest->getServer('HTTP_REFERER')) && !empty(parse_url($this->httpRequest->getServer('HTTP_REFERER'), PHP_URL_QUERY))){
            parse_str(parse_url($this->httpRequest->getServer('HTTP_REFERER'), PHP_URL_QUERY), $query);
        }
        
        if (!empty($query)) {
            $printQuery = ($query['en_print_query']) ?? 0;
        }
        return $printQuery;
    }
}
