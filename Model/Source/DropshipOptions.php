<?php

namespace Eniture\UPSSmallPackageQuotes\Model\Source;

use Eniture\UPSSmallPackageQuotes\Helper\Data;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Class DropshipOptions shows DS options
 */
class DropshipOptions extends AbstractSource
{
    /**
     * @var Data
     */
    public $dataHelper;
    /**
     * @var array
     */
    public $options = [];
    
    /**
     * @param Data $dataHelper
     */
    public function __construct(
        Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }
    /**
     * Abstract method of source class
     * @return type
     */
    public function getAllOptions()
    {
        $get_dropship = $this->dataHelper->fetchWarehouseSecData('dropship');
        
        if (isset($get_dropship) && !empty($get_dropship)) {
            foreach ($get_dropship as $manufacturer) {
                (isset($manufacturer['nickname']) && $manufacturer['nickname'] == '') ?
                $nickname = '' : $nickname = html_entity_decode($manufacturer['nickname'], ENT_QUOTES).' - ';
                $city       = $manufacturer['city'];
                $state      = $manufacturer['state'];
                $zip        = $manufacturer['zip'];
                $dropship   = $nickname.$city.', '.$state.', '.$zip;
                $this->options[] = [
                        'label' => __($dropship),
                        'value' => $manufacturer['warehouse_id'],
                    ];
            }
        }
        return $this->options;
    }
    /**
     * Abstract method of source class that returns data
     * @param $value
     * @return boolean
     */
    public function getOptionText($value)
    {
        $options = $this->getAllOptions(false);

        foreach ($options as $item) {
            if ($item['value'] == $value) {
                return $item['label'];
            }
        }
        return false;
    }
}
