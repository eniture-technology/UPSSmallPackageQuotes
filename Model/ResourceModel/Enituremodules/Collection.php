<?php

namespace Eniture\UPSSmallPackageQuotes\Model\ResourceModel\Enituremodules;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection creates Eniture Module DB table collection
 */
class Collection extends AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Eniture\UPSSmallPackageQuotes\Model\Enituremodules', 'Eniture\UPSSmallPackageQuotes\Model\ResourceModel\Enituremodules');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }
}
