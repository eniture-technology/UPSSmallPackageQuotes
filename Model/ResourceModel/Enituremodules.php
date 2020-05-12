<?php
namespace Eniture\UPSSmallPackageQuotes\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Enituremodules initiates DB object for Eniture Modules Db table
 */
class Enituremodules extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('enituremodules', 'module_id');
    }
}
