<?php

namespace Eniture\UPSSmallPackageQuotes\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class HazourdousFeeOptions creates Transit Days options for Quotes Settings page
 */
class HazourdousFeeOptions implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return  [
                    [
                        'value' => 'ShipAsOwnPackage',
                        'label' => __('Quote each item marked as hazmat as shipping as its own package.')
                    ],
                    [
                        'value' => 'CombineQuantities',
                        'label' => __('Combine quantities of the same item marked as hazmat into a single package before applying the hazmat fee.')
                    ],
                ];
    }
}
