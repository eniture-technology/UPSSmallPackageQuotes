<?php

namespace Eniture\UPSSmallPackageQuotes\Model\Source;

/**
 * Class HandlingFee provides dropdown options on quote settings page
 */
class HandlingFee
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'handlingFeeVal' =>
                ['value' => 'flat', 'label'  => 'Flat Rate'],
                ['value' => '%', 'label'  => 'Percentage ( % )'],
        ];
    }
}
