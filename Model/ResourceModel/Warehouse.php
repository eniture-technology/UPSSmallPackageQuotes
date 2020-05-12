<?php
namespace Eniture\UPSSmallPackageQuotes\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Warehouse initializes AbstractDb
 */
class Warehouse extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('warehouse', 'warehouse_id');
    }
}
