<?php

namespace Eniture\UPSSmallPackageQuotes\Model\ResourceModel\Warehouse;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection create warehouse collection from Warehouses table
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
        $this->_init('Eniture\UPSSmallPackageQuotes\Model\Warehouse', 'Eniture\UPSSmallPackageQuotes\Model\ResourceModel\Warehouse');

        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }
}
