<?php
namespace Eniture\UPSSmallPackageQuotes\Model\Carrier;

use Magento\Store\Model\ScopeInterface;

/**
 * class that generated request data
 */
class UPSSmpkgGenerateRequestData
{
    /**
     * @var
     */
    public $registry;
    /**
     * @var
     */
    public $moduleManager;
    /**
     * @var
     */
    public $scopeConfig;
    /**
     * @var
     */
    public $dataHelper;
    /**
     * @var
     */
    public $request;
    /**
     * @var string
     */
    public $FedexOneRatePricing = '0';
    /**
     * @var string
     */
    public $oneRatePricing = '0';
    /**
     * @var string
     */
    public $airServicesPricing = '0';
    /**
     * @var string
     */
    public $homeGroundPricing = '0';
    /**
     * This variable stores service type e.g domestic, international, both
     * @var string
     */
    public $serviceType = [];

    public $allSelectedServices;
    /**
     * @param type $scopeConfig
     * @param type $registry
     * @param type $moduleManager
     * @param type $dataHelper
     */
    public function _init(
        $scopeConfig,
        $registry,
        $moduleManager,
        $dataHelper,
        $request
    ) {
        $this->registry            = $registry;
        $this->scopeConfig         = $scopeConfig;
        $this->moduleManager       = $moduleManager;
        $this->dataHelper          = $dataHelper;
        $this->request             = $request;
        $this->allSelectedServices = $this->getSelectedServices();
    }

    /**
     * function that generates UPS array
     * @return array
     */
    public function generateUPSSmpkgArray($request, $origin)
    {
        $getDistance = 0;
        $upsSmpkgArr = [
            'licenseKey'    => $this->getConfigData('licnsKey'),
            'serverName'    => $this->request->getServer('SERVER_NAME'),
            'carrierMode'   => 'pro',
            'quotestType'   => 'small',
            'version'       => '1.0.0',
            'api'           => $this->getApiInfoArr($request->getDestCountryId(), $origin),
            'getDistance'   => $getDistance,
        ];
        return  $upsSmpkgArr;
    }

    /**
     * Function that generates request array
     * @param $request
     * @param $upsSmpkgArr
     * @param $itemsArr
     * @param $cart
     * @return array
     */
    public function generateRequestArray($request, $upsSmpkgArr, $itemsArr, $cart)
    {
        if (count($upsSmpkgArr['originAddress']) > 1) {
            foreach ($upsSmpkgArr['originAddress'] as $wh) {
                $whIDs[] = $wh['locationId'];
            }
            if (count(array_unique($whIDs)) > 1) {
                foreach ($upsSmpkgArr['originAddress'] as $id => $wh) {
                    if (isset($wh['InstorPickupLocalDelivery'])) {
                        $upsSmpkgArr['originAddress'][$id]['InstorPickupLocalDelivery'] = [];
                    }
                }
            }
        }
        $smartPost = $this->getConfigData('FedExSmartPost');
        if ($this->registry->registry('fedexSmartPost') === null) {
            $this->registry->register('fedexSmartPost', $smartPost);
        }
        $carriers = $this->registry->registry('enitureCarriers');
        $carriers['upsSmall'] = $upsSmpkgArr;
        $receiverAddress = $this->getReceiverData($request);

        $requestArr = [
            'apiVersion'                    => '2.0',
            'platform'                      => 'magento2',
            'binPackagingMultiCarrier'      => $this->binPackSuspend(),
            'autoResidentials'              => $this->autoResidentialDelivery(),
            'liftGateWithAutoResidentials'  => $this->registry->registry('radForLiftgate'),
            'FedexOneRatePricing'           => $this->FedexOneRatePricing,
            'FedexSmartPostPricing'         => $smartPost,
            'requestKey'                    => $cart->getQuote()->getId(),
            'carriers'                      => $carriers,
            'receiverAddress'               => $receiverAddress,
            'commdityDetails'               => $itemsArr
        ];

        $binsData = $this->getSavedBins();
        $requestArr = array_merge($requestArr, $binsData);

        return  $requestArr;
    }

    /**
     * @return string
     */
    public function binPackSuspend()
    {
        $return = "0";
        if ($this->moduleManager->isEnabled('Eniture_StandardBoxSizes')) {
            $return = $this->scopeConfig->getValue("binPackaging/suspend/value", ScopeInterface::SCOPE_STORE) == "no" ? "1" : "0";
        }
        return $return;
    }

    /**
     * @return int
     */
    public function autoResidentialDelivery()
    {
        $autoDetectResidential = 0;
        if ($this->moduleManager->isEnabled('Eniture_ResidentialAddressDetection')) {
            $suspndPath = "resaddressdetection/suspend/value";
            $autoResidential = $this->scopeConfig->getValue($suspndPath, ScopeInterface::SCOPE_STORE);
            if ($autoResidential != null && $autoResidential == 'no') {
                $autoDetectResidential = 1;
            }
        }
        if ($this->registry->registry('autoDetectResidential') === null) {
            $this->registry->register('autoDetectResidential', $autoDetectResidential);
        }

        return $autoDetectResidential ;
    }

    public function setValuesInRequest()
    {
        $domesticServices               = explode(',', $this->getConfigData('UPSDomesticServices'));
        $oneRateChecked                 = $this->getOneRateServices();
        $internationalServicesLength    = $this->getServiceOptionsLength('UPSInternationalServices');
        $oneRateServicesLength          = $this->getServiceOptionsLength('UPSOneRateServices');
        $boxSizeChecked                 = $this->getSavedBins();

        if ($oneRateServicesLength || $oneRateChecked) {
            $this->FedexOneRatePricing = '1' ;
            if ($oneRateServicesLength) {
                //set one rate pricing = 1
                $this->oneRatePricing = '1' ;
            }

            foreach ($domesticServices as $key => $data) {
                if ($data == 'GROUND_HOME_DELIVERY' || $data == 'FEDEX_GROUND') {
                    // set home ground pricing = 1
                    $this->homeGroundPricing = '1';
                }

                if (($data == 'GROUND_HOME_DELIVERY') || ($data == 'FEDEX_GROUND')) {
                    unset($domesticServices[$key]);
                }
            }

            if (($internationalServicesLength || (!empty($domesticServices)))) {
                $this->airServicesPricing = '1' ;
            }
        }

        if ($this->registry->registry('FedexOneRatePricing') === null) {
            $this->registry->register('FedexOneRatePricing', $this->FedexOneRatePricing);
        }
    }

    /**
     * @param type $services
     * @return string
     */
    public function getServiceOptionsLength($services)
    {
        return strlen($this->getConfigData($services));
    }

    /**
     * @return array
     */
    public function getOneRateServices()
    {
        $checked = [];
        if ($this->moduleManager->isEnabled('Eniture_StandardBoxSizes')) {
            $boxsizeHelper = $this->dataHelper->getBoxHelper('helper');
            $checked = $boxsizeHelper->getEnabledOneRateServices();
        }

        return $checked;
    }


    public function getSavedBins()
    {
        $savedBins = [];
        if ($this->moduleManager->isEnabled('Eniture_StandardBoxSizes')) {
            $boxSizeHelper = $this->dataHelper->getBoxHelper('helper');
            $savedBins = $boxSizeHelper->fillBoxingData();
        }
        return $savedBins;
    }

    /**
     * This function returns carriers array if have not empty origin address
     * @return array
     */
    public function getCarriersArray()
    {
        $carriersArr = $this->registry->registry('enitureCarriers');
        $newCarriersArr = [];
        foreach ($carriersArr as $carrkey => $carrArr) {
            $notHaveEmptyOrigin = true;
            foreach ($carrArr['originAddress'] as $key => $value) {
                if (empty($value['senderZip'])) {
                    $notHaveEmptyOrigin = false;
                }
            }
            if ($notHaveEmptyOrigin) {
                $newCarriersArr[$carrkey] = $carrArr;
            }
        }

        return $newCarriersArr;
    }

    /**
     * function that returns API array
     * @return array
     */
    public function getApiInfoArr($country, $origin)
    {
        $this->setValuesInRequest();

        if ($this->autoResidentialDelivery()) {
            $residential = 'no';
        } else {
            $residential = ($this->getConfigData('residentialDlvry'))?'yes':'no';
        }
        $this->serviceType = $this->getServiceType($country, $origin);

        if ($this->registry->registry('upsServiceType') === null) {
            $this->registry->register('upsServiceType', $this->serviceType);
        }

        $smartPostData = ($this->getConfigData('UPSSmartPost')) ?
            ['hubId' => $this->getConfigData('hubId'), 'indicia' => 'PARCEL_SELECT'] : [];

        $apiArray = [
            'ups_small_pkg_username'            => $this->getConfigData('username'),
            'ups_small_pkg_password'            => $this->getConfigData('password'),
            'ups_small_pkg_authentication_key'  => $this->getConfigData('upsLicenseKey'),
            'ups_small_pkg_account_number'      => $this->getConfigData('accountNumber'),
            'ups_small_pkg_resid_delivery'      => $residential,
            'services'                          => $this->getServices(),
            'prefferedCurrency'                 => $this->registry->registry('baseCurrency'),
            'includeDeclaredValue'              => $this->registry->registry('en_insurance'),
        ];

        return  $apiArray;
    }

    /**
     * This function returns Services Array
     * @return array
     */
    public function getServices()
    {
        $domesticArr = $international = $surePost = [];
        if ($this->serviceType == 'domestic' || $this->serviceType == 'Cdomestic' || $this->serviceType == 'both') {
            $hazourdous = $this->dataHelper->checkHazardousShipment();
            if (count($hazourdous) > 0 && $this->getConfigData('groundHazardous')) {
                $domesticArr = [
                    'ups_small_pkg_Ground'  => $this->isServiceActive('03'),
                ];
            } else {
                $domesticArr = [
                    // Domestic Services //
                    'ups_small_pkg_3_Day_Select'            => $this->isServiceActive('12'),
                    'ups_small_pkg_Ground'                  => $this->isServiceActive('03'),
                    'ups_small_pkg_2nd_Day_Air'             => $this->isServiceActive('02'),
                    'ups_small_pkg_2nd_Day_Air_AM'          => $this->isServiceActive('59'),
                    'ups_small_pkg_Next_Day_Air'            => $this->isServiceActive('01'),
                    'ups_small_pkg_Next_Day_Air_Saver'      => $this->isServiceActive('13'),
                    'ups_small_pkg_Next_Day_Air_Early_AM'   => $this->isServiceActive('14')
                ];
            }
            if ($this->serviceType == 'Cdomestic') {
                $domesticArr['ups_small_pkg_Standard'] = $this->isServiceActive('11');
            }
        }

        if ($this->serviceType == 'international' || $this->serviceType == 'both') {
            $international = [
                //International Services //
                'ups_small_pkg_3_Day_Select'            => $this->isServiceActive('12'),
                'ups_small_pkg_Standard'                => $this->isServiceActive('11'),
                'ups_small_pkg_Worldwide_Express'       => $this->isServiceActive('07'),
                'ups_small_pkg_Worldwide_Express_Plus'  => $this->isServiceActive('54'),
                'ups_small_pkg_Worldwide_Expedited'     => $this->isServiceActive('08'),
                'ups_small_pkg_Saver'                   => $this->isServiceActive('65'),
            ];
        }

        $planInfo = $this->dataHelper->planName();
        if ($planInfo['planNumber'] != 0 || $planInfo['planNumber'] != 1) {
            $surePost = [
                'ups_small_surepost_less_than_1LB'  => $this->isServiceActive('92'),
                'ups_small_surepost_1LB_or_greater' => $this->isServiceActive('93'),
                'ups_small_surepost_bpm'            => $this->isServiceActive('94'),
                'ups_small_surepost_media_mail'     => $this->isServiceActive('95'),
            ];
        }

        $servicesArr = array_merge($domesticArr, $international, $surePost);

        if ((int) $this->getConfigData('UPSGndwithFreight')) {
            $servicesArr['ups_small_pkg_Ground_Freight_Pricing'] = 'yes';
        }

        $servicesArr['ups_small_pkg_aditional_handling'] = 'N';


        return $servicesArr;
    }


    public function getSelectedServices()
    {

        $domesticServices = $internationalServices = $surePostServices = [];
        $domesticServices       = explode(',', $this->getConfigData('UPSDomesticServices'));
        $internationalServices  = explode(',', $this->getConfigData('UPSInternationalServices'));
        $surePostServices       = explode(',', $this->getConfigData('UPSSurePost'));
        return array_merge($domesticServices, $internationalServices, $surePostServices);
    }
    /**
     * Function that returns weather this service is active or not
     * @param string $serviceId
     * @return string
     */
    public function isServiceActive($serviceId)
    {
        if (in_array($serviceId, $this->allSelectedServices)) {
            return 'yes';
        } else {
            return 'N';
        }
    }

    /**
     * function return service data
     * @param $fieldId
     * @return string
     */
    public function getConfigData($fieldId)
    {
        $secThreeIds = [
            'residentialDlvry',
            'UPSDomesticServices',
            'UPSInternationalServices',
            'UPSSurePost',
            'UPSGndwithFreight'
        ];
        if (in_array($fieldId, $secThreeIds)) {
            $sectionId = 'upsQuoteSetting';
            $groupId = 'third';
        } else {
            $sectionId = 'upsconnsettings';
            $groupId = 'first';
        }
        $confPath = "$sectionId/$groupId/$fieldId";
        return $this->scopeConfig->getValue($confPath, ScopeInterface::SCOPE_STORE);
    }

    /**
     * This function returns Receiver Data Array
     * @param $request
     * @return array
     */
    public function getReceiverData($request)
    {
        $addressType = $this->scopeConfig->getValue("resaddressdetection/addressType/value", ScopeInterface::SCOPE_STORE);
        $receiverDataArr = [
            'addressLine'           => $request->getDestStreet(),
            'receiverCity'          => $request->getDestCity(),
            'receiverState'         => $request->getDestRegionCode(),
            'receiverZip'           => preg_replace('/\s+/', '', $request->getDestPostcode()),
            'receiverCountryCode'   => $request->getDestCountryId(),
            'defaultRADAddressType' => $addressType ?? 'residential', //get value from RAD
        ];

        return  $receiverDataArr;
    }

    /**
     * @param type $destinationCountry
     * @param type $originArr
     * @return string
     */
    public function getServiceType($destinationCountry, $originArr)
    {

        $serviceType = 'both';
        $originCountryCode = '';
        $destinationCountry = strtoupper($destinationCountry);

        foreach ($originArr as $key => $value) {
            //In case of Multishipment
            if (!empty($originCountryCode) && $originCountryCode != $value['senderCountryCode']) {
                $serviceType = 'both';
                break;
            }

            if ($destinationCountry == 'US') {
                if ($value['senderCountryCode'] == $destinationCountry) {
                    $serviceType = 'domestic';
                } elseif ($value['senderCountryCode'] != $destinationCountry) {
                    $serviceType = 'international';
                }
            } elseif ($destinationCountry == 'CA') {
                if ($value['senderCountryCode'] == $destinationCountry) {
                    $serviceType = 'Cdomestic';
                } else {
                    $serviceType = 'international';
                }
            }

            $originCountryCode = $value['senderCountryCode'];
        }
        return $serviceType;
    }
}
