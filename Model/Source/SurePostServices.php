<?php
namespace Eniture\UPSSmallPackageQuotes\Model\Source;

/**
 * Class OneRateServices
 */
class SurePostServices
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'serviceOptions' =>
                ['value' => '92', 'label' => 'UPS SurePost Less than 1LB'],

                ['value' => '93', 'label' => 'UPS SurePost 1LB or greater'],

                ['value' => '94', 'label' => 'UPS SurePost Bound Printed Matter'],

                ['value' => '95', 'label' => 'UPS SurePost Media Mail'],
            ];
    }
}
