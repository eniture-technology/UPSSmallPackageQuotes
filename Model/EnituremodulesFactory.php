<?php

namespace Eniture\UPSSmallPackageQuotes\Model;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class EnituremodulesFactory provides data from Eniture Module DB Table
 */
class EnituremodulesFactory
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
        return $this->objectManager->create('Eniture\UPSSmallPackageQuotes\Model\Enituremodules', $arguments, false);
    }
}
