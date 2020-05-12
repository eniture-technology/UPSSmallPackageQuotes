<?php

namespace Eniture\UPSSmallPackageQuotes\Model;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class WarehouseFactory provides data from Warehouse DB table
 */
class WarehouseFactory
{
    /**
     * @var ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create new country model
     *
     * @param array $arguments
     * @return mixed
     */
    public function create(array $arguments = [])
    {
        return $this->objectManager->create('Eniture\UPSSmallPackageQuotes\Model\Warehouse', $arguments, false);
    }
}
