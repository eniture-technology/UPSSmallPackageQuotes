<?php

namespace Eniture\UPSSmallPackageQuotes\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class TransitDaysRestrictionBy creates Transit Days options for Quotes Settings page
 */
class TransitDaysRestrictionBy implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return  [
                    [
                        'value' => 'BusinessDaysInTransit',
                        'label' => __('Restrict the carriers in transit days metric')
                    ],
                    [
                        'value' => 'CalenderDaysInTransit',
                        'label' => __('Restrict by calendar days in transit')
                    ],
                ];
    }
}
