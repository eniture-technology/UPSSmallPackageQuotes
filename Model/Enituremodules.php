<?php
namespace Eniture\UPSSmallPackageQuotes\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Enituremodules initializes DB Abstract
 */
class Enituremodules extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Eniture\UPSSmallPackageQuotes\Model\ResourceModel\Enituremodules');
    }
}
