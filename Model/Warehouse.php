<?php
namespace Eniture\UPSSmallPackageQuotes\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Warehouse creates Warehouse Abstract Model
 */
class Warehouse extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Eniture\UPSSmallPackageQuotes\Model\ResourceModel\Warehouse');
    }
}
