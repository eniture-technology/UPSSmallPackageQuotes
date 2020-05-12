<?php

namespace Eniture\UPSSmallPackageQuotes\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class FedexRates creates Rates options for Quotes Settings page
 */
class UpsRates implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return  [
                    [
                        'value' => 'publish',
                        'label' => __('Use retail (list) rates.')
                    ],
                    [
                        'value' => 'negotiate',
                        'label' => __('Use my negotiated rates.')
                    ],
                ];
    }
}
