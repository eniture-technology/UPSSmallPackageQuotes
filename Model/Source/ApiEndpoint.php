<?php
namespace Eniture\UPSSmallPackageQuotes\Model\Source;

class ApiEndpoint
{
    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
        return  [
            [
                'value' => 'legacy',
                'label' => __('Legacy API')
            ],
            [
                'value' => 'new',
                'label' => __('New API')
            ],
        ];
    }
}
