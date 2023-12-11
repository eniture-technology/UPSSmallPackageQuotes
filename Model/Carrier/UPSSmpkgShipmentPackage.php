<?php
namespace Eniture\UPSSmallPackageQuotes\Model\Carrier;

use Eniture\UPSSmallPackageQuotes\Helper\EnConstants;

/**
 * Class UPSSmpkgShipmentPackage creates quotes request
 */
class UPSSmpkgShipmentPackage
{
    /**
     * @var type $scopeConfig
     */
    public $scopeConfig;
    /**
     * @var type $request
     */
    public $request;
    /**
     * @var type $productloader
     */
    public $productloader;
    /**
     * @var type $dataHelper
     */
    public $dataHelper;
    /**
     * @var type $httpRequest
     */
    public $httpRequest;

    /**
     * @param type $request
     * @param type $scopeConfig
     * @param type $dataHelper
     * @param type $productloader
     */
    public function _init(
        $request,
        $scopeConfig,
        $dataHelper,
        $productloader,
        $httpRequest
    ) {
        $this->request          = $request;
        $this->scopeConfig      = $scopeConfig;
        $this->dataHelper       = $dataHelper;
        $this->productloader    = $productloader;
        $this->httpRequest      = $httpRequest;
    }

    /**
     * fuction that returns address array
     * @param $_product
     * @param $receiverZipCode
     * @return array
     */
    public function upsSmpkgOriginAddress($_product, $receiverZipCode)
    {
        $whQuery        = $this->dataHelper->fetchWarehouseSecData('warehouse');
        $enableDropship = $_product->getData('en_dropship');

        if ($enableDropship) {
            $dropshipID = $_product->getData('en_dropship_location');
            $originList = $this->dataHelper->fetchDropshipWithID($dropshipID);

            if (!$originList) {
                $product = $this->productloader->create()->load($_product->getEntityId());
                $product->setData('en_dropship', 0)->getResource()->saveAttribute($product, 'en_dropship');
                $origin    = $whQuery;
            } else {
                $origin = $originList;
            }
        } else {
            $origin    = $whQuery;
        }

        if (!empty($origin)) {
            return $this->multiWarehouse($origin, $receiverZipCode);
        }
    }

    /**
     * This function returns the closest warehouse if multiple warehouse exists otherwise
     * return single.
     * @param $warehousList
     * @param $receiverZipCode
     * @return array
     */
    public function multiWarehouse($warehousList, $receiverZipCode)
    {
        $packageArray = $this->dataHelper->planName();
        $planNumber = $packageArray['planNumber'];
        if (!empty($warehousList)) {
            $distance_array = [];
            if (count($warehousList) == 1) {
                $warehousList = reset($warehousList);
                return $this->upsSmpkgOriginArray($warehousList, $receiverZipCode, $planNumber);
            } elseif (count($warehousList) > 1 && ($planNumber == 0 || $planNumber == 1)) {
                return $this->upsSmpkgOriginArray($warehousList[0], $receiverZipCode, $planNumber);
            }

            $response  = $this->upsSmpkgAddress($warehousList);
            if (!empty($response)) {
                $originWithMinDist = (isset($response->origin_with_min_dist)
                        && !empty($response->origin_with_min_dist)) ?
                        (array)$response->origin_with_min_dist: [];
                return $this->upsSmpkgOriginArray($originWithMinDist, $receiverZipCode, $planNumber);
            }
        }
    }

    /**
     * function that returns shortest origin managed array
     * @param array $shortOrigin
     * @return array
     */
    public function upsSmpkgOriginArray($shortOrigin, $receiverZipCode, $planNumber)
    {
        if (isset($shortOrigin) && count($shortOrigin) > 1) {
            $origin = isset($shortOrigin['origin']) ? $shortOrigin['origin'] : $shortOrigin;
            $zip = isset($origin['zipcode']) ? $origin['zipcode'] : $origin['zip'];
            $city = $origin['city'];
            $state = $origin['state'];
            $country = ($origin['country'] == "CN") ? "CA" : $origin['country'];
            $location = isset($origin['location']) ? $origin['location'] : 'warehouse';
            $locationId = isset($shortOrigin['id']) ? $shortOrigin['id'] : $shortOrigin['warehouse_id'];
            return [
                    'location' => $location,
                    'locationId' => $locationId,
                    'senderZip' => $zip,
                    'senderCity' => $city,
                    'senderState' => $state,
                    'senderCountryCode' => $country,
                    'InstorPickupLocalDelivery' => $planNumber==3 ? $this->instorePickupLdData($shortOrigin, $receiverZipCode) : '',
                ];
        }
    }

    /**
     * This function returns response from google api
     * @param $originAddress
     * @return array
     */
    public function upsSmpkgAddress($originAddress)
    {
        $originAddress = $this->changeWarehouseIdKey($originAddress);
        $post = [
            'acessLevel'        => 'MultiDistance',
            'address'           => $originAddress,
            'originAddresses'   => (isset($originAddress)) ? $originAddress : "",
            'destinationAddress'=> [
                'city'      => $this->request->getDestCity(),
                'state'     => $this->request->getDestRegionCode(),
                'zip'       => $this->request->getDestPostcode(),
                'country'   => $this->request->getDestCountryId(),
            ],
            'ServerName'        => $this->httpRequest->getServer('SERVER_NAME'),
            'eniureLicenceKey'  => $this->scopeConfig->getValue(
                'upsconnsettings/first/licnsKey',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
        ];
        $url = EnConstants::GOOGLE_URL;
        $curlRes = $this->dataHelper->upsSmpkgSendCurlRequest($url, $post);
        if (!isset($curlRes->error)) {
            $response = $curlRes;
        } else {
            $response = [];
        }
        return $response;
    }
    /**
     * @param type $origins
     * @return type
     */
    public function changeWarehouseIdKey($origins)
    {
        foreach ($origins as $key => $origin) {
            if ($origin['warehouse_id']) {
                $origin['id'] = $origin['warehouse_id'];
                unset($origin['warehouse_id']);
            }
            $result[$key] = $origin;
        }

        return $result;
    }

    /**
     * @param type $shortOrigin
     * @param type $receiverZipCode
     * @return type
     */
    public function instorePickupLdData($shortOrigin, $receiverZipCode)
    {
        $array = [];
        if (!empty($shortOrigin['in_store']) && $shortOrigin['in_store'] != 'null') {
            $instore = json_decode($shortOrigin['in_store']);
            if ($instore->enable_store_pickup == 1) {
                $array['inStorePickup'] = [
                    'addressWithInMiles' =>$instore->miles_store_pickup ,
                    'postalCodeMatch'    =>$this->checkPostalCodeMatch($receiverZipCode, $instore->match_postal_store_pickup),
                ];
            }
        }

        if (!empty($shortOrigin['local_delivery']) && $shortOrigin['local_delivery'] != 'null') {
            $locDel = json_decode($shortOrigin['local_delivery']);
            if ($locDel->enable_local_delivery == 1) {
                $array['localDelivery'] = [
                    'addressWithInMiles' => $locDel->miles_local_delivery,
                    'postalCodeMatch'    =>$this->checkPostalCodeMatch($receiverZipCode, $locDel->match_postal_local_delivery),
                    'suppressOtherRates' =>$locDel->suppress_other,
                ];
            }
        }
        return $array;
    }


    /**
     * @param $receiverZipCode
     * @param $originZipCodes
     * @return int
     */
    public function checkPostalCodeMatch($receiverZipCode, $originZipCodes)
    {
        $receiverZipCode = preg_replace('/\s+/', '', $receiverZipCode);
        $originZipCodes = preg_replace('/\s+/', '', $originZipCodes);
        $originZipCodesArr = empty($originZipCodes) ? [] : explode(',', $originZipCodes);
        return in_array($receiverZipCode, $originZipCodesArr) ? 1 : 0;
    }
}
