<?php
namespace Eniture\UPSSmallPackageQuotes\Helper;

use Eniture\UPSSmallPackageQuotes\Model\EnituremodulesFactory;
use Eniture\UPSSmallPackageQuotes\Model\WarehouseFactory;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Shipping\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data provides all helper functionality and quotes processing
 */
class Data extends AbstractHelper
{
    /**
     * @var Config
     */
    public $shippingConfig;
    /**
     * @var StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var CurrencyFactory
     */
    public $currencyFactory;
    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;
    /**
     * @var Registry
     */
    public $registry;
    /**
     * @var SessionManagerInterface
     */
    public $coreSession;
    /**
     * @var
     */
    public $originZip;
    /**
     * @var
     */
    public $origins;
    /**
     * @var
     */
    public $residentialDelivery;
    /**
     * @var Curl
     */
    public $curl;
    /**
     * @var int
     */
    public $canAddWh = 1;
    /**
     * @var Manager
     */
    public $cacheManager;
    /**
     * @var WarehouseFactory
     */
    public $warehouseFactory;
    /**
     * @var EnituremodulesFactory
     */
    private $enituremodulesFactory;
    /**
     * @var ResourceConnection
     */
    private $resource;
    /**
     * @var Currency
     */
    private $currenciesModel;
    /**
     * @var \Magento\Directory\Helper\Data
     */
    private $directoryHelper;
    /**
     * @var Context
     */
    private $context;

    public $objectManager;

    public $gndHzrdousFee;
    /**
     * @var String
     */
    public $UPSDomesticServices;
    /**
     * @var String
     */
    public $UPSInternationalServices;
    /**
     * @var String
     */
    public $UPSSurePost;
    /**
     * @var String
     */
    public $residentialDlvry;
    /**
     * @var String
     */
    public $onlyGndService;
    /**
     * @var String
     */
    public $airHzrdousFee;
    /**
     * @var String
     */
    public $upsRates;

    /**
     * Data constructor.
     * @param Context $context
     * @param ResourceConnection $resource
     * @param Config $shippingConfig
     * @param StoreManagerInterface $storeManager
     * @param CurrencyFactory $currencyFactory
     * @param Currency $currencyModel
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param Registry $registry
     * @param SessionManagerInterface $coreSession
     * @param WarehouseFactory $warehouseFactory
     * @param EnituremodulesFactory $enituremodulesFactory
     * @param Curl $curl
     * @param Manager $cacheManager
     */
    public function __construct(
        Context $context,
        ResourceConnection $resource,
        Config $shippingConfig,
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory,
        Currency $currencyModel,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Directory\Helper\Data $directoryHelper,
        Registry $registry,
        SessionManagerInterface $coreSession,
        WarehouseFactory $warehouseFactory,
        EnituremodulesFactory $enituremodulesFactory,
        Curl $curl,
        Manager $cacheManager,
        ObjectManagerInterface $objectmanager
    ) {
        $this->resource            = $resource;
        $this->shippingConfig      = $shippingConfig;
        $this->storeManager        = $storeManager;
        $this->currencyFactory     = $currencyFactory;
        $this->currenciesModel      = $currencyModel;
        $this->priceCurrency       = $priceCurrency;
        $this->directoryHelper      = $directoryHelper;
        $this->registry            = $registry;
        $this->coreSession         = $coreSession;
        $this->warehouseFactory    = $warehouseFactory;
        $this->enituremodulesFactory    = $enituremodulesFactory;
        $this->context       = $context;
        $this->curl = $curl;
        $this->cacheManager = $cacheManager;
        $this->objectManager = $objectmanager;
        parent::__construct($context);
    }

    /**
     * function to return the Store Base Currency
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getBaseCurrencyCode()
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode();
    }

    /**
     * @param $location
     * @return array
     */
    public function fetchWarehouseSecData($location)
    {
        $whCollection       = $this->warehouseFactory->create()
            ->getCollection()->addFilter('location', ['eq' => $location]);
        return $this->purifyCollectionData($whCollection);
    }

    /**
     * @param $whCollection
     * @return array
     */
    public function purifyCollectionData($whCollection)
    {
        $warehouseSecData = [];
        foreach ($whCollection as $wh) {
            $warehouseSecData[] = $wh->getData();
        }
        return $warehouseSecData;
    }

    /**
     * @param $dropshipId
     * @return array
     */
    public function fetchDropshipWithID($dropshipId)
    {
        $whFactory = $this->warehouseFactory->create();
        $dsCollection  = $whFactory->getCollection()
            ->addFilter('location', ['eq' => 'dropship'])
            ->addFilter('warehouse_id', ['eq' => $dropshipId]);

        return $this->purifyCollectionData($dsCollection);
    }

    /**
     * @param type $data
     * @param type $whereClause
     * @return type
     */
    public function updateWarehousData($data, $whereClause)
    {
        $defualtConn = ResourceConnection::DEFAULT_CONNECTION;
        $whTableName = $this->resource->getTableName('warehouse');
        $this->resource->getConnection($defualtConn)->update("$whTableName", $data, "$whereClause");
        return 1;
    }

    /**
     * @param type $data
     * @param type $id
     * @return type
     */
    public function insertWarehouseData($data, $id)
    {
        $defualtConn    = ResourceConnection::DEFAULT_CONNECTION;
        $connection     =  $this->resource->getConnection($defualtConn);
        $whTableName    = $this->resource->getTableName('warehouse');
        $insertQry = $connection->insert("$whTableName", $data);
        if ($insertQry == 0) {
            $lastid = $id;
        } else {
            $lastid = $connection->lastInsertId();
        }
        return ['insertId' => $insertQry, 'lastId' => $lastid];
    }

    /**
     * @param type $data
     * @return type
     */
    public function deleteWarehouseSecData($data)
    {
        $defualtConn    = ResourceConnection::DEFAULT_CONNECTION;
        $whTableName    = $this->resource->getTableName('warehouse');
        return $this->resource->getConnection($defualtConn)->delete("$whTableName", $data);
    }

    /**
     * Data Array
     * @param $inputData
     * @return array
     */

    public function originArray($inputData)
    {
        $dataArr = [
            'city'       => $inputData['city'],
            'state'      => $inputData['state'],
            'zip'        => $inputData['zip'],
            'country'    => $inputData['country'],
            'location'   => $inputData['location'],
            'nickname'   => $inputData['nickname'] ?? '',
            'markup'     => $inputData['markup'] ?? 0
        ];
        $pickupDelvryArr = [
            'enable_store_pickup'           => ($inputData['instore-enable'] === 'on') ? 1 : 0,
            'miles_store_pickup'            => $inputData['is-within-miles'],
            'match_postal_store_pickup'     => $inputData['is-postcode-match'],
            'checkout_desc_store_pickup'    => $inputData['is-checkout-descp'],
            'suppress_other'                => ($inputData['ld-sup-rates'] === 'on') ? 1 : 0,
        ];
        $dataArr['in_store'] = json_encode($pickupDelvryArr);

        $localDelvryArr = [
            'enable_local_delivery'         => ($inputData['ld-enable']=== 'on') ? 1 : 0,
            'miles_local_delivery'          => $inputData['ld-within-miles'],
            'match_postal_local_delivery'   => $inputData['ld-postcode-match'],
            'checkout_desc_local_delivery'  => $inputData['ld-checkout-descp'],
            'fee_local_delivery'            => empty($inputData['ld-fee']) ? 0 : $inputData['ld-fee'],
            'suppress_other'                => ($inputData['ld-sup-rates'] === 'on')?1:0,
        ];
        $dataArr['local_delivery'] = json_encode($localDelvryArr);

        return $dataArr;
    }

    /**
     * @param type $scopeConfig
     */
    public function quoteSettingsData($scopeConfig)
    {
        $this->UPSDomesticServices = $this->adminConfigData('UPSDomesticServices', $scopeConfig);
        $this->UPSInternationalServices = $this->adminConfigData('UPSInternationalServices', $scopeConfig);
        $this->UPSSurePost = $this->adminConfigData('UPSSurePost', $scopeConfig);
        $this->residentialDlvry = $this->adminConfigData('residentialDlvry', $scopeConfig);
        $this->onlyGndService = $this->adminConfigData('onlyGndService', $scopeConfig);
        $this->gndHzrdousFee = $this->adminConfigData('gndHzrdousFee', $scopeConfig);
        $this->airHzrdousFee = $this->adminConfigData('airHzrdousFee', $scopeConfig);
        $this->upsRates = $this->adminConfigData('upsRates', $scopeConfig);

        // Get origin zipcode array for onerate settings
        $this->getOriginZipCodeArr();
    }

    /**
     * getOriginZipCodeArr
     */
    public function getOriginZipCodeArr()
    {
        if ($this->registry->registry('shipmentOrigin') !== null) {
            $originArr = $this->registry->registry('shipmentOrigin');
        }

        foreach ($originArr as $key => $origin) {
            $this->originZip[$key] = $origin['senderZip'];
            if(!empty($origin['senderZip'])){
                $this->origins[$origin['senderZip']] = $origin;
            }
        }
    }

    /**
     * validate Input Post
     * @param $sPostData
     * @return mixed
     */
    public function validatedPostData($sPostData)
    {
        $dataArray = ['city', 'state', 'zip', 'country'];
        foreach ($sPostData as $key => $tag) {
            $preg = '/[#$%@^&_*!()+=\-\[\]\';,.\/{}|":<>?~\\\\]/';
            $check_characters = (in_array($key, $dataArray)) ? preg_match($preg, $tag) : '';

            if ($check_characters != 1) {
                if ($key === 'city' || $key === 'nickname' || $key === 'in_store' || $key === 'local_delivery' || $key == 'markup') {
                    $data[$key] = $tag;
                } else {
                    $data[$key] = preg_replace('/\s+/', '', $tag);
                }
            } else {
                $data[$key] = 'Error';
            }
        }

        return $data;
    }

    /**
     *
     * @param array $getWarehouse
     * @param array $validateData
     * @return string
     */
    public function checkUpdateInstrorePickupDelivery($getWarehouse, $validateData)
    {
        $update = 'no';
        $newData = $oldData = [];
        if (empty($getWarehouse)) {
            return $update;
        }
        $getWarehouse = reset($getWarehouse);
        unset($getWarehouse['warehouse_id']);
        unset($getWarehouse['nickname']);
        unset($validateData['nickname']);
        unset($validateData['markup']);
        foreach ($getWarehouse as $key => $value) {
            if (empty($value) || $value === null) {
                $newData[$key] = 'empty';
            } else {
                $oldData[$key] = trim($value);
            }
        }
        $whData = array_merge($newData, $oldData);
        $diff1 = array_diff($whData, $validateData);
        $diff2 = array_diff($validateData, $whData);
        if ((is_array($diff1) && !empty($diff1)) || (is_array($diff2) && !empty($diff2))) {
            $update = 'yes';
        }
        return $update;
    }

    /**
     * This function send request and return responce
     * $isAssocArray Paramiter When TRUE, then returned objects will
     * be converted into associative arrays, otherwise its an object
     * @param $url
     * @param $postData
     * @param $isAssocArray
     * @return
     */
    public function upsSmpkgSendCurlRequest($url, $postData, $isAssocArray = false)
    {
        $fieldString = http_build_query($postData);
        $this->curl->post($url, $fieldString);
        $output = $this->curl->getBody();
        if(!empty($output) && is_string($output)){
            $result = json_decode($output, $isAssocArray);
        }else{
            $result = ($isAssocArray) ? [] : '';
        }
        return $result;
    }

    /**
     * @param type $key
     * @return string|empty
     */
    public function getZipcode($key)
    {
        $key = empty($key) ? [] : explode("_", $key);
        return (isset($key[0])) ? $key[0] : "";
    }

    /**
     * @param type $quotes
     * @param type $isMultishipmentQuantity
     * @param type $scopeConfig
     * @return type
     */
    public function getQuotesResults($quotes, $getMinimum, $isMultishipmentQuantity, $scopeConfig)
    {
        $this->quoteSettingsData($scopeConfig);
        $allConfigServices = $this->getAllConfigServicesArray($scopeConfig);

        if ($isMultishipmentQuantity) {
            return $this->getOriginsMinimumQuotes($quotes, $allConfigServices, $scopeConfig);
        }

        $filteredQuotes = [];
        $multiShipment = (count($quotes) > 1 ? true : false);

        foreach ($quotes as $key => $quote) {
            if (isset($quote->severity) && $quote->severity == 'ERROR') {
                return [];
            }
            $binPackaging = $this->setBinPackagingData($quote, $key);

            $binPackagingArr[] = $binPackaging;
            $filteredQuotes[$key] = $this->parseUpsSmallOutput($quote, $allConfigServices, $scopeConfig, $binPackaging[$key], $key);
        }

        $this->coreSession->start();
        $this->coreSession->setUpsBinPackaging($binPackagingArr);

        if (!$multiShipment) {
            $this->setOrderDetailWidgetData([], $scopeConfig);
            return $getMinimum ? $filteredQuotes : reset($filteredQuotes);
        } else {
            $multiShipQuotes = $this->getMultishipmentQuotes($filteredQuotes);
            if (!empty($multiShipQuotes)) {
                $this->setOrderDetailWidgetData($multiShipQuotes['orderWidgetQ'], $scopeConfig);
                return $multiShipQuotes['multiShipQ'];
            }
            return [];
        }
    }

    /**
     * @param type $filteredQuotes
     * @return array
     */
    public function getMultishipmentQuotes($filteredQuotes)
    {
        $totalRate = 0;
        $response = [];
        foreach ($filteredQuotes as $key => $multiQuotes) {
            if (isset($multiQuotes[0])) {
                $totalRate += $multiQuotes[0]['rate'];
                $multiship[$key]['quotes'] = $multiQuotes[0];
            }
        }

        if ($totalRate) {
            $response['multiShipQ']['upsSmall'] = $this->getFinalQuoteArray(
                $totalRate,
                'UPSSPMS',
                'Shipping ' . $this->residentialDelivery
            );
            $response['orderWidgetQ'] = $multiship;
        }

        return $response;
    }

    /**
     * @param type $quote
     * @param type $key
     * @return array
     */
    public function setBinPackagingData($quote, $key)
    {
        $binPackaging = [];
        $binPackaging[$key]['upsServices'] = [];
        if (isset($quote->binPackagingData)) {
            $binPackaging[$key]['upsServices'] = $quote->binPackagingData;
            $binPackaging[$key]['upsServices']->boxesFee = isset($quote->binPackagingData->response) ?
                $this->calculateBoxesFee($quote->binPackagingData->response)
                : 0;
        }

        return $binPackaging;
    }

    public function getBoxHelper($objectName)
    {
        if ($objectName == 'helper') {
            return $this->objectManager->get("Eniture\StandardBoxSizes\Helper\Data");
        }
        if ($objectName == 'boxFactory') {
            $boxHelper =  $this->objectManager->get("Eniture\StandardBoxSizes\Helper\Data");
            return $boxHelper->getBoxFactory();
        }
    }

    /**
     *
     * @param type $response
     * @return type
     */
    public function calculateBoxesFee($response)
    {
        $totalBoxesFee = 0;
        $boxesFee = $boxIDs = [];
        foreach ($response->bins_packed as $binDetails) {
            if (isset($binDetails->bin_data->type) && $binDetails->bin_data->type="item") { // If user boxes are not used
                $boxIDs = null;
            } else {
                $boxIDs[] = $binDetails->bin_data->id;
            }
        }
        if ($boxIDs !== null && count($boxIDs) > 0) {
            $boxFactory = $this->getBoxHelper('boxFactory');
            foreach ($boxIDs as $boxID) {
                if (!array_key_exists($boxID, $boxesFee)) {
                    $boxCollection = $boxFactory->getCollection()->addFilter('box_id', ['eq' => $boxID])->addFieldToSelect('boxfee');
                    foreach ($boxCollection as $box) {
                        $boxFee = $box->getData();
                    }
                    $boxesFee[$boxID]= !empty($boxFee['boxfee']) ? $boxFee['boxfee'] : 0;
                }

                $totalBoxesFee +=$boxesFee[$boxID];
            }
        }

        return $totalBoxesFee;
    }

    /**
     * Get Shipping Array For Single Shipment
     * @param $quote
     * @param $serviceType
     * @return array
     */
    public function parseUpsSmallOutput($quote, $allConfigServices, $scopeConfig, $binPackaging, $shipmentKey)
    {
        $getFilteredQuotes = $this->filterQuotes($quote, $scopeConfig, $allConfigServices, $binPackaging, $shipmentKey);

        if (isset($quote->InstorPickupLocalDelivery) && !empty($quote->InstorPickupLocalDelivery)) {
            $getFilteredQuotes = $this->instoreLocalDeliveryQuotes(
                $getFilteredQuotes,
                $quote->InstorPickupLocalDelivery
            );
        }

        return $getFilteredQuotes;
    }

    /**
     *
     * @param type $quotes
     * @param type $scopeConfig
     * @param type $quote
     * @return type
     */
    public function filterQuotes($quotes, $scopeConfig, $allConfigServices, $binPackaging, $shipmentKey)
    {
        $filteredArr = [];
        if (!isset($quotes->q)) {
            return $filteredArr;
        }

        $isRAD = $quotes->autoResidentialsStatus ?? '';
        $quotes = isset($quotes->tnt) ? $this->transitTimeRestriction($quotes) : $quotes;

        foreach ($quotes->q as $servkey => $availableServ) {
            if (isset($availableServ->serviceType) && in_array($availableServ->serviceType, $allConfigServices)) {
                $totalAmount = $this->getQuoteAmount($availableServ, 'upsServices', $binPackaging);
                $totalAmount = $this->addProductLevelMarkup($totalAmount, $shipmentKey);
                $totalAmount = $this->addOriginLevelMarkup($totalAmount, $shipmentKey);
                $addedHandling = $this->calculateHandlingFee($totalAmount, $scopeConfig);
                $addedHazardous = $this->calculateHazardousFee($availableServ->serviceType, $addedHandling, $shipmentKey, $binPackaging);
                $serviceTitle = $this->getServiceTitle($availableServ->serviceType, $quotes);
                $autoResTitle = $this->getAutoResidentialTitle($isRAD);
                if ((isset($serviceTitle) && !empty($serviceTitle)) && $addedHazardous > 0) {
                    $filteredArr[] = [
                        'code' => $availableServ->serviceType . 'UPS',
                        'GuaranteedDaysToDelivery' => $availableServ->GuaranteedDaysToDelivery,
                        'rate' => $addedHazardous,
                        'title' => $serviceTitle . ' ' . $autoResTitle,
                    ];
                }
            }
        }
        return $this->sortAscOrderArr($filteredArr, 'rate');
    }
    /**
     *
     * @param type $rate
     * @param type $index
     * @return type
     */
    public function sortAscOrderArr($rate, $index)
    {
        $priceSortedKey = [];
        foreach ($rate as $key => $costCarrier) {
            $priceSortedKey[$key] = (isset($costCarrier[$index])) ? $costCarrier[$index] : 0;
        }
        array_multisort($priceSortedKey, SORT_ASC, $rate);

        return $rate;
    }

    /**
     * @param $response
     */
    public function transitTimeRestriction($response)
    {
        if (!isset($response->tnt->TransitResponse->ServiceSummary)) {
            return $response;
        }

        $daysToRestrict = $this->getConfigData('upsQuoteSetting/third/transitDaysNumber');
        $transitDayType = $this->getConfigData('upsQuoteSetting/third/transitDaysRestrictionBy');

        $plan = $this->planName();
        $ServiceSummary = $response->tnt->TransitResponse->ServiceSummary;
        if ($plan['planNumber'] == 3 && !empty($daysToRestrict) && strlen($daysToRestrict) > 0 && !empty($transitDayType) && strlen($transitDayType) > 0) {
            foreach ($ServiceSummary as $key => $service) {
                $serviceCode = $service->Service->Code;
                if ($serviceCode === 'GND') { // To check if it is UPS Ground
                    $estimatedArrivalDays = $service->EstimatedArrival->$transitDayType ?? $this->estimatedArrivalDays($service->EstimatedArrival);
                    if ($estimatedArrivalDays >= $daysToRestrict) {
                        $ups_services = array_flip((array)$response->ups_services);
                        $index = (isset($ups_services['UPS Ground'])) ? $ups_services['UPS Ground'] : "";
                        unset($response->q->$index);
                    }
                }
            }
        }
        return $response;
    }

    public function estimatedArrivalDays($estimatedArrival)
    {
        $arrival    = $estimatedArrival->Arrival->Date ?? "";
        $pickup     = $estimatedArrival->Pickup->Date ?? "";
        $date1      =date_create($arrival);
        $date2      =date_create($pickup);
        $diff       =date_diff($date1, $date2);
        return $diff->format("%a");
    }
    /**
     * @param type $service
     * @return string
     */
    public function getAutoResidentialTitle($service)
    {
        $append = '';
        $moduleManager = $this->context->getModuleManager();

        if ($moduleManager->isEnabled('Eniture_ResidentialAddressDetection')) {
            $isRadSuspend = $this->getConfigData('resaddressdetection/suspend/value');
            if ($this->residentialDlvry == "1") {
                $this->residentialDlvry = $isRadSuspend == "no" ? null : $isRadSuspend;
            } else {
                $this->residentialDlvry = $isRadSuspend == "no" ? null : $this->residentialDlvry;
            }

            if ($this->residentialDlvry == null
                || $this->residentialDlvry == '0') {
                if ($service == 'r') {
                    $append = ' with residential delivery';
                }
            }
            $this->residentialDelivery = $append;
        }

        return $append;
    }

    /**
     * @param $serviceType
     * @param $addedHandling
     * @return int
     */
    public function calculateHazardousFee($serviceType, $addedHandling, $shipmentKey, $binPackaging = [])
    {
        $hazourdous = $this->checkHazardousPerShipment($shipmentKey, $binPackaging);
        if ($hazourdous['isHazmat']) {
            $ground = ($serviceType == '03') ? true : false;
            $addedHazardous = 0;
            if ($this->onlyGndService == '1') {
                if ($ground) {
                    $addedHazardous = ($this->gndHzrdousFee * $hazourdous['hazmatQty']) + $addedHandling;
                } elseif (!$ground && $this->airHzrdousFee !== '') {
                    $addedHazardous = 0;
                }
            } else {
                if ($ground && $this->gndHzrdousFee !== '') {
                    $addedHazardous = ($this->gndHzrdousFee * $hazourdous['hazmatQty']) + $addedHandling;
                } elseif (!$ground && $this->airHzrdousFee !== '') {
                    $addedHazardous = ($this->airHzrdousFee * $hazourdous['hazmatQty']) + $addedHandling;
                } else {
                    $addedHazardous = $addedHandling;
                }
            }
        } else {
            $addedHazardous = $addedHandling;
        }
        return $addedHazardous;
    }

    /**
     * @return array
     */
    public function checkHazardousShipment()
    {
        $hazourdous = [];
        $checkHazordous = $this->registry->registry('hazardousShipment');
        if (isset($checkHazordous)) {
            foreach ($checkHazordous as $key => $data) {
                foreach ($data as $k => $d) {
                    if ($d['isHazordous'] == '1') {
                        $hazourdous[] =  $k;
                    }
                }
            }
        }
        return $hazourdous;
    }

    public function checkHazardousPerShipment($shipmentKey, $binPackaging = [])
    {
        $hazourdous = [];
        $hazourdous['isHazmat'] = false;
        $hazourdous['hazmatQty'] = 0;
        $checkHazordous = $this->registry->registry('hazardousShipment');
        $hazmatItemsArr = [];
        $hazourdousFeeOption = $this->getConfigData('upsQuoteSetting/third/hazourdousFeeOptions');
        if (isset($checkHazordous)) {
            foreach ($checkHazordous as $key => $data) {
                if(isset($data[$shipmentKey]) && is_array($data[$shipmentKey])){
                    if ($data[$shipmentKey]['isHazordous'] == '1') {
                        $hazourdous['isHazmat'] = true;
                        $hazmatItemsArr[] = $data[$shipmentKey]['lineItemId'];
                        if(!empty($hazourdousFeeOption) && $hazourdousFeeOption == 'CombineQuantities'){
                            $hazourdous['hazmatQty'] += 1;
                        }else{
                            $qty = ($data[$shipmentKey]['itemQty']) ? $data[$shipmentKey]['itemQty'] : 0;
                            $hazourdous['hazmatQty'] += $qty;
                        }
                    }
                }
            }
        }

        if($hazourdous['isHazmat'] && !empty($binPackaging) && isset($binPackaging['upsServices']->response->bins_packed) && is_array($binPackaging['upsServices']->response->bins_packed)){
            $hazourdous['hazmatQty'] = 0;
            foreach($binPackaging['upsServices']->response->bins_packed as $key => $box){
                if(isset($box->items) && is_array($box->items)){
                    foreach($box->items as $kry => $item){
                        $id = ($item->id) ? $item->id : 0;
                        if(in_array($id, $hazmatItemsArr)){
                            $hazourdous['hazmatQty'] += 1;
                            break;
                        }
                    }
                }
            }
        }

        return $hazourdous;
    }

    /**
     * @param $availableServ
     * @param string $serviceName
     * @param array $binPack
     * @return int
     */
    public function getQuoteAmount($availableServ, $serviceName = '', $binPack = [])
    {
        $boxFee = 0;
        if (!empty($binPack) && isset($binPack[$serviceName])) {
            $boxFee = $binPack[$serviceName]->boxesFee ?? 0;
        }

        $upsRateSource = $this->upsRates;
        if ($upsRateSource == 'negotiate') {
            if ($availableServ->NegotiatedRates->Amount) {
                $quoteAmmount = $availableServ->NegotiatedRates->Amount + $boxFee;
            } else {
                $quoteAmmount = $availableServ->totalNetCharge->Amount + $boxFee;
            }
        } else {
            $quoteAmmount = $availableServ->totalNetCharge->Amount + $boxFee;
        }

        return $quoteAmmount;
    }

    /**
     * @param type $serviceType
     * @param type $serviceTitle
     * @param type $minInQ
     * @return type
     */
    public function multishipSetOrderData($serviceType, $serviceTitle, $minInQ)
    {
        $servicesArr['quotes'] = $this->getFinalQuoteArray($minInQ, $serviceType, $serviceTitle);
        return $servicesArr;
    }

    /**
     * @param type $servicesArr
     * @param type $QCount
     */
    public function setOrderDetailWidgetData(array $servicesArr, $scopeConfig)
    {
        $orderDetail['residentialDelivery'] = ($this->residentialDelivery != '' || $this->residentialDlvry == '1' || $this->residentialDlvry == 'yes') ? 'Residential Delivery' : '';
        $setPkgForOrderDetailReg = null !== $this->registry->registry('setPackageDataForOrderDetail') ?
            $this->registry->registry('setPackageDataForOrderDetail') : [];
        $orderDetail['shipmentData'] = array_replace_recursive($setPkgForOrderDetailReg, $servicesArr);

        // set order detail widget data
        $this->coreSession->start();
        $this->coreSession->setUpsOrderDetailSession($orderDetail);
    }

    /**
     * @param array $quotes
     * @param array $allConfigServices
     * @param $scopeConfig
     * @return array
     */
    public function getOriginsMinimumQuotes($quotes, $allConfigServices, $scopeConfig)
    {
        $minIndexArr = [];
        $resiArr = ['residential' => false, 'label' => ''];

        foreach ($quotes as $key => $quote) {
            $minInQ = [];
            $counter = 0;

            $binPackaging = $this->setBinPackagingData($quote, $key);

            $binPackagingArr[] = $binPackaging;

            $isRad = $quote->autoResidentialsStatus ?? '';
            $resi = $this->getAutoResidentialTitle($isRad);

            if ($this->residentialDlvry == "yes" || $resi != '') {
                $resiArr = ['residential' => true, 'label' => $resi];
            }

            if (isset($quote->q)) {
                foreach ($quote->q as $servkey => $availableServ) {
                    if (isset($availableServ->serviceType)
                        && in_array($availableServ->serviceType, $allConfigServices)) {
                        $totalAmount = $this->getQuoteAmount($availableServ, 'upsServices', $binPackaging[$key]);
                        $totalAmount = $this->addOriginLevelMarkup($totalAmount, $key);
                        $addedHandling = $this->calculateHandlingFee($totalAmount, $scopeConfig);
                        $addedHazardous = $this->calculateHazardousFee($availableServ->serviceType, $addedHandling, $binPackaging[$key]);
                        $currentService = $this->getServiceTitle($availableServ->serviceType, $quote);
                        if ((isset($currentService) && !empty($currentService)) && $addedHazardous > 0) {
                            $currentArray = ['code' => $availableServ->serviceType . 'UPS',
                                'rate' => $addedHazardous,
                                'title' => $currentService . ' ' . $resi,
                                'resi' => $resiArr];
                            if ($counter == 0) {
                                $minInQ = $currentArray;
                            } else {
                                $minInQ = ($currentArray['rate'] < $minInQ['rate'] ? $currentArray : $minInQ);
                            }
                        }
                        $counter ++;
                    }
                }
                if ($minInQ['rate'] > 0) {
                    $minIndexArr[$key] = $minInQ;
                }
            }
        }

        $this->coreSession->start();
        $this->coreSession->setSemiBinPackaging($binPackagingArr);
        return $minIndexArr;
    }

    /**
     * This Function returns all active services array from configurations
     * @return array
     */
    public function getAllConfigServicesArray($scopeConfig)
    {
        $domesticServices       = isset($this->UPSDomesticServices) ?
            explode(',', $this->UPSDomesticServices) : [];
        $internationalServices  = isset($this->UPSInternationalServices) ?
            explode(',', $this->UPSInternationalServices) : [];
        $surePostServices  = isset($this->UPSSurePost) ?
            explode(',', $this->UPSSurePost) : [];

        $groundFreight = (!empty($surePostServices) && in_array('GFP',$surePostServices)) ? ['GFP'] : [];

        return array_merge($domesticServices, $internationalServices, $surePostServices, $groundFreight);
    }

    /**
     * Final quotes array
     * @param $grandTotal
     * @param $code
     * @param $title
     * @return array
     */
    public function getFinalQuoteArray($grandTotal, $code, $title)
    {
        $allowed = [];
        if ($grandTotal > 0) {
            $allowed = [
                'code'  => $code . 'UPS',// or carrier name
                'title' => $title,
                'rate'  => $grandTotal
            ];
        }

        return $allowed;
    }

    /**
     * Calculate Handling Fee
     * @param $totalPrice
     * @param $scopeConfig
     * @return int
     */
    public function calculateHandlingFee($totalPrice, $scopeConfig)
    {
        $grpSec = 'upsQuoteSetting/third';
        $hndlngFeeMarkup = $scopeConfig->getValue(
            $grpSec . '/hndlngFee',
            ScopeInterface::SCOPE_STORE
        );
        $symbolicHndlngFee = $scopeConfig->getValue(
            $grpSec . '/symbolicHndlngFee',
            ScopeInterface::SCOPE_STORE
        );

        if ($hndlngFeeMarkup !== '') {
            if ($symbolicHndlngFee == '%') {
                $prcntVal = $hndlngFeeMarkup / 100 * $totalPrice;
                $grandTotal = $prcntVal + $totalPrice;
            } else {
                $grandTotal = $hndlngFeeMarkup + $totalPrice;
            }
        } else {
            $grandTotal = $totalPrice;
        }
        return $grandTotal;
    }

    public function addOriginLevelMarkup($totalAmount, $key) {
        if(isset($this->origins[$key]) && !empty($this->origins[$key]['markup'])){
            $markup = $this->origins[$key]['markup'];
        }else{
            return $totalAmount;
        }

        if (strpos($markup, '%') !== false) {
            $percentage = (float) rtrim($markup, '%');
            $totalAmount += ($totalAmount * $percentage / 100);
        } else {
            $totalAmount += (float) $markup;
        }
        
        return $totalAmount;
    }

    public function addProductLevelMarkup($totalAmount, $key) {

        $setPkgForOrderDetailReg = (null !== $this->registry->registry('setPackageDataForOrderDetail')) ?
            $this->registry->registry('setPackageDataForOrderDetail') : [];
        $totalMarkup = 0;
        if(is_array($setPkgForOrderDetailReg) && isset($setPkgForOrderDetailReg[$key]) && !empty($setPkgForOrderDetailReg[$key]['item'])){
            foreach($setPkgForOrderDetailReg[$key]['item'] as $key => $value){
                if(!empty($value['product_markup']) && is_numeric($value['product_markup'])){
                    if(is_numeric($value['piecesOfLineItem']) && $value['piecesOfLineItem'] > 1){
                        $productMarkup = (float) ($value['product_markup'] * $value['piecesOfLineItem']);
                    }else{
                        $productMarkup = (float) $value['product_markup'];
                    }
                }else{
                    $productMarkup = 0;
                };

                $totalMarkup += $productMarkup;
            }
        }
        $totalAmount += $totalMarkup;
        return $totalAmount;
    }

    /**
     * @param $fieldId
     * @param $scopeConfig
     * @return type
     */
    public function adminConfigData($fieldId, $scopeConfig)
    {
        return $scopeConfig->getValue(
            "upsQuoteSetting/third/$fieldId",
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return \Magento\Shipping\Model\Carrier\AbstractCarrierInterface[]
     */
    public function getActiveCarriersForENCount()
    {
        return $this->shippingConfig->getActiveCarriers();
    }

    /**
     * This Function returns service title
     * @param $serviceId
     * @param $quotesArr
     * @return array
     */
    public function getServiceTitle($serviceId, $quotesArr)
    {
        $servArr = (array)$quotesArr->ups_services;
        $newServicesArr = [];

        foreach ($servArr as $key => $value) {
            $newServicesArr[$key] = $value;
        }
        return isset($newServicesArr[$serviceId]) ? $newServicesArr[$serviceId] : [];
    }

    /**
     * ups carrier codes with title
     * @return array
     */
    public function upsCarriersWithTitle()
    {
        $domestic = $this->upsDomesticCarriersWithTitle();
        $international = $this->upsInternationalCarriersWithTitle();
        $surePost = $this->upsSurePostCarriersWithTitle();

        return array_merge($domestic, $international, $surePost);
    }

    public function upsDomesticCarriersWithTitle()
    {
        return [
            '03' => __('UPS Ground'),
            '02' => __('UPS 2nd Day Air'),
            '59' => __('UPS 2nd Day Air A.M.'),
            '13' => __('UPS Next Day Air Saver'),
            '01' => __('UPS Next Day Air'),
            '14' => __('UPS Next Day Air Early'),
            '12' => __('UPS 3 Day Select'),
        ];
    }

    /**
     * @return array
     */
    public function upsInternationalCarriersWithTitle()
    {
        return [
            //international services
            '11' => __('UPS Standard'),
            '08' => __('UPS Worldwide Expedited'),
            '65' => __('UPS Worldwide Saver'),
            '07' => __('UPS Worldwide Express'),
            '54' => __('UPS Worldwide Express Plus'),
        ];
    }

    /**
     * @return array
     */
    public function upsSurePostCarriersWithTitle()
    {
        return [
            //SurePost services
            '92' => __('UPS SurePost Less than 1LB'),
            '93' => __('UPS SurePost 1LB or greater'),
            '94' => __('UPS SurePost Bound Printed Matter'),
            '95' => __('UPS SurePost Media Mail'),
        ];
    }

    /**
     * @return array
     */
    public function quoteSettingFieldsToRestrict()
    {
        $restriction = [];
        $currentPlanArr = $this->planName();
        $transitFields = [
            'transitDaysNumber','transitDaysRestrictionByTransitTimeInDays','transitDaysRestrictionByCalenderDaysInTransit'
        ];
        $hazAndSurePostFields = [
            'onlyGndService','gndHzrdousFee','airHzrdousFee', 'UPSSurePost'
        ];
        switch ($currentPlanArr['planNumber']) {
            case 2:
                $restriction = [
                    'advance' => $transitFields
                ];
                break;
            case 3:
                break;
            default:
                $restriction = [
                    'advance' => $transitFields,
                    'standard' => $hazAndSurePostFields
                ];
                break;
        }
        return $restriction;
    }

    /**
     * @return string
     */
    public function upsSmallSetPlanNotice($planRefreshUrl = '')
    {
        $planMsg = '';
        $planPackage = $this->planName();
        if ($planPackage['storeType'] === null) {
            $planPackage = [];
        }
        $planMsg = $this->diplayPlanMessages($planPackage, $planRefreshUrl);
        return $planMsg;
    }

    /**
     * @param type $planPackage
     * @return type
     */
    public function diplayPlanMessages($planPackage, $planRefreshUrl = '')
    {
        $planRefreshLink = '';
        if (!empty($planRefreshUrl)) {
            $planRefreshLink = ' <a href="javascript:void(0)" id="plan-refresh-link" planRefAjaxUrl = '.$planRefreshUrl.' onclick="upsSmpkgPlanRefresh(this)" >Click here</a> to refresh the plan (please sign-in again after this action).';
            $planMsg = __('The subscription to the UPS Small Package Quotes module is inactive. If you believe the subscription should be active and you recently changed plans (e.g. upgraded your plan), your firewall may be blocking confirmation from our licensing system. To resolve the situation, <a href="javascript:void(0)" id="plan-refresh-link" planRefAjaxUrl = '.$planRefreshUrl.' onclick="upsSmpkgPlanRefresh(this)" >click this link</a> and then sign in again. If this does not resolve the issue, log in to eniture.com and verify the license status.');
        }else{
            $planMsg = __('The subscription to the UPS Small Package Quotes module is inactive. Please log into eniture.com and update your license.');
        }

        if (isset($planPackage) && !empty($planPackage)) {
            if ($planPackage['planNumber'] !== null && $planPackage['planNumber'] != '-1') {
                $planMsg = __('The UPS Small Package Quotes from Eniture Technology is currently on the '.$planPackage['planName'].' and will renew on '.$planPackage['expiryDate'].'. If this does not reflect changes made to the subscription plan'.$planRefreshLink.'.');
            }
        }

        return $planMsg;
    }
    /**
     * Get UPS Small Plan
     * @return array
     */
    public function planName()
    {
        //ENUPSSmpkg
        $appData = $this->getConfigData("eniture/ENUPSSmpkg");

        $plan       = $appData["plan"] ?? '';
        $storeType  = $appData["storetype"] ?? '';
        $expireDays = $appData["expireday"] ?? '';
        $expiryDate = $appData["expiredate"] ?? '';
        $planName = "";

        switch ($plan) {
            case 3:
                $planName = "Advanced Plan";
                break;
            case 2:
                $planName = "Standard Plan";
                break;
            case 1:
                $planName = "Basic Plan";
                break;
            case 0:
                $planName = "Trial Plan";
                break;
        }
        return [
            'planNumber' => $plan,
            'planName' => $planName,
            'expireDays' => $expireDays,
            'expiryDate' => $expiryDate,
            'storeType' => $storeType
        ];
    }

    /**
     * @param $confPath
     * @return mixed
     */
    public function getConfigData($confPath)
    {
        $scopeConfig = $this->context->getScopeConfig();
        return $scopeConfig->getValue($confPath, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return int
     */
    public function whPlanRestriction()
    {
        $planArr = $this->planName();
        $warehouses = $this->fetchWarehouseSecData('warehouse');
        $planNumber = isset($planArr['planNumber']) ? $planArr['planNumber'] : '';

        if ($planNumber < 2 && count($warehouses) >= 1) {
            $this->canAddWh = 0;
        }
        return $this->canAddWh;
    }

    /**
     * @return int
     */
    public function checkAdvancePlan()
    {
        $advncPlan = 1;
        $planArr = $this->planName();
        $planNumber = isset($planArr['planNumber']) ? $planArr['planNumber'] : '';

        if ($planNumber != 3) {
            $advncPlan = 0;
        }
        return $advncPlan;
    }

    /**
     * @param type $quotesarray
     * @param type $instoreLd
     * @return type
     */
    public function instoreLocalDeliveryQuotes($quotesarray, $instoreLd)
    {
        $data = $this->registry->registry('shipmentOrigin');
        if (count($data) > 1) {
            return $quotesarray;
        }

        foreach ($data as $array) {
            $warehouseData = $this->getWarehouseData($array);

            /* Quotes array only to be made empty if Suppress other rates is ON and Instore Pickup or Local Delivery also carries some quotes. Else if Instore Pickup or Local Delivery does not have any quotes i.e Postal code or within miles does not match then the Quotes Array should be returned as it is. */
            if ($warehouseData['suppress_other']) {
                if ((isset($instoreLd->inStorePickup->status) && $instoreLd->inStorePickup->status == 1)
                    || (isset($instoreLd->localDelivery->status) && $instoreLd->localDelivery->status == 1)) {
                    $quotesarray=[];
                }
            }
            if (isset($instoreLd->inStorePickup->status) && $instoreLd->inStorePickup->status == 1) {
                $quotesarray[] = [
                    'serviceType' => 'IN_STORE_PICKUP',
                    'code' => 'INSP',
                    'rate' => 0,
                    'transitTime' => '',
                    'title' => $warehouseData['inStoreTitle'],
                    'serviceName' => 'upsServices'
                ];
            }

            if (isset($instoreLd->localDelivery->status) && $instoreLd->localDelivery->status == 1) {
                $quotesarray[] = [
                    'serviceType' => 'LOCAL_DELIVERY',
                    'code' => 'LOCDEL',
                    'rate' => $warehouseData['fee_local_delivery'],
                    'transitTime' => '',
                    'title' => $warehouseData['locDelTitle'],
                    'serviceName' => 'upsServices'
                ];
            }
        }
        return $quotesarray;
    }

    /**
     *
     */
    public function clearCache()
    {
        $this->cacheManager->flush($this->cacheManager->getAvailableTypes());

        // or this
        $this->cacheManager->clean($this->cacheManager->getAvailableTypes());
    }

    /**
     * @param $data
     * @return array
     */
    public function getWarehouseData($data)
    {
        $return = [];
        $whCollection = $this->warehouseFactory->create()->getCollection()
            ->addFilter('location', ['eq' => $data['location']])
            ->addFilter('warehouse_id', ['eq' => $data['locationId']]);

        $whCollection = $this->purifyCollectionData($whCollection);
        if(!empty($whCollection[0]['in_store']) && is_string($whCollection[0]['in_store'])){
            $instore = json_decode($whCollection[0]['in_store'], true);
        }else{
            $instore = [];
        }
        
        if(!empty($whCollection[0]['local_delivery']) && is_string($whCollection[0]['local_delivery'])){
            $locDel = json_decode($whCollection[0]['local_delivery'], true);
        }else{
            $locDel = [];
        }

        if ($instore) {
            $inStoreTitle = $instore['checkout_desc_store_pickup'];
            if (empty($inStoreTitle)) {
                $inStoreTitle = "Instore pick up";
            }
            $return['inStoreTitle'] = $inStoreTitle;
            $return['suppress_other'] = $instore['suppress_other']=='1' ? true : false;
        }

        if ($locDel) {
            $locDelTitle = $locDel['checkout_desc_local_delivery'];
            if (empty($locDelTitle)) {
                $locDelTitle = "Local delivery";
            }
            $return['locDelTitle'] = $locDelTitle;
            $return['fee_local_delivery'] = empty($locDel['fee_local_delivery']) ? 0 : $locDel['fee_local_delivery'];
            $return['suppress_other'] = $locDel['suppress_other']=='1' ? true : false;
        }
        return $return;
    }

    /**
     * @param $warehouseId
     * @return array
     */
    public function fetchWarehouseWithID($location, $warehouseId)
    {
        $whFactory = $this->warehouseFactory->create();
        $dsCollection  = $whFactory->getCollection()
            ->addFilter('location', ['eq' => $location])
            ->addFilter('warehouse_id', ['eq' => $warehouseId]);

        return $this->purifyCollectionData($dsCollection);
    }
}
