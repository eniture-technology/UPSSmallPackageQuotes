<?php

namespace Eniture\UPSSmallPackageQuotes\Model\Source;

/**
 * Class RateSource creates Rate Source options for Quotes Settings page
 */
class RateSource implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return  [
                    [
                        'value' => 'negotiate',
                        'label' => __('Use my negotiated rates.')
                    ],
                    [
                        'value' => 'retail',
                        'label' => __('Use retail (list) rates.')
                    ],
                ];
    }
}
