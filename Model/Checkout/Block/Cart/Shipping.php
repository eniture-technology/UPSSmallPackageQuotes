<?php

namespace Eniture\UPSSmallPackageQuotes\Model\Checkout\Block\Cart;

use Magento\Checkout\Block\Cart\LayoutProcessor;
use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Directory\Model\ResourceModel\Country\Collection as CountryCollection;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Shipping to show city field on Cart page
 */
class Shipping extends LayoutProcessor
{

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;


    /**
     * Shipping constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param AttributeMerger $merger
     * @param CountryCollection $countryCollection
     * @param RegionCollection $regionCollection
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AttributeMerger $merger,
        CountryCollection $countryCollection,
        RegionCollection $regionCollection
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($merger, $countryCollection, $regionCollection);
    }


    /**
     * @return bool
     */
    public function isCityActive()
    {
        if ($this->scopeConfig->getValue('carriers/ENUPSSmpkg/active')) {
            return true;
        }

    }
}
