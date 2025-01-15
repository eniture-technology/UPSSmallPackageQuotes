<?php

namespace Eniture\UPSSmallPackageQuotes\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class TransitDaysRestrictionBy creates Transit Days options for Quotes Settings page
 */
class PackagingMethod implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return  [
                    [
                        'value' => 'ship_alone',
                        'label' => __('Quote each item as shipping as its own package')
                    ],
                    [
                        'value' => 'ship_combine_and_alone',
                        'label' => __('Combine the weight of all items without dimensions and quote them as one package while quoting each item with dimensions as shipping as its own package')
                    ],
                    [
                        'value' => 'ship_as_one',
                        'label' => __('Quote shipping as if all items ship as one package up to 70 LB each')
                    ],
                    [
                        'value' => 'ship_as_one_150',
                        'label' => __('Quote shipping as if all items ship as one package up to 150 LB each')
                    ],
                ];
    }
}
