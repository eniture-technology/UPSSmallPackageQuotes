<?php

namespace Eniture\UPSSmallPackageQuotes\Model\Source;

/**
 * Class DomesticServices creates options on Quotes Settings page
 */
class DomesticServices
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'serviceOptions' =>
                ['value' => '03',  'label'  => 'UPS Ground'],

                ['value' => '02',  'label'  => 'UPS 2nd Day Air'],

                ['value' => '59',  'label'  => 'UPS 2nd Day Air A.M.'],

                ['value' => '13',  'label'  => 'UPS Next Day Air Saver'],

                ['value' => '01',  'label'  => 'UPS Next Day Air'],

                ['value' => '14',  'label'  => 'UPS Next Day Air Early'],

                ['value' => '12',  'label'  => 'UPS 3 Day Select'],
            ];
    }
}
