<?php

namespace Eniture\UPSSmallPackageQuotes\Model\Source;

/**
 * Class InternationalServices provides Select of services on quote settings page.
 */
class InternationalServices
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            'serviceOptions' =>
                ['value' => '11',  'label'  => 'UPS Standard'],

                ['value' => '08',  'label'  => 'UPS  Expedited | UPS Worldwide Expedited'],

                ['value' => '65',  'label'  => 'UPS Express Saver | UPS Worldwide Saver'],

                ['value' => '07',  'label'  => 'UPS Express | UPS Worldwide Express'],

                ['value' => '54', 'label' => 'UPS Express Plus | UPS Worldwide Express Plus'],
            ];
    }
}
