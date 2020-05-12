<?php
namespace Eniture\UPSSmallPackageQuotes\Model\Carrier;

/**
 * Class for set carriers globally
 */
class UPSSmallSetCarriersGlobaly
{
    /**
     * @var
     */
    public $dataHelper;
    /**
     * @var
     */
    public $registry;

    /**
     * constructor of class
     */
    public function _init($dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }
    
    /**
     * function for manage carriers globally
     * @param $UPSArr
     * @return boolean
     */
    public function manageCarriersGlobaly($UPSArr, $registry)
    {
        $this->registry = $registry;
        $enitureCarriers = $this->registry->registry('enitureCarriers');
        if ($enitureCarriers === null) {
            $enitureCarriersArray = [];
            $enitureCarriersArray['upsSmall'] = $UPSArr;
            $this->registry->register('enitureCarriers', $enitureCarriersArray);
        } else {
            $carriersArr = $enitureCarriers;
            $carriersArr['upsSmall'] = $UPSArr;
            $this->registry->unregister('enitureCarriers');
            $this->registry->register('enitureCarriers', $carriersArr);
        }
        
        $activeEnitureModulesCount = $this->getActiveEnitureModulesCount();

        if (count($this->registry->registry('enitureCarriers')) < $activeEnitureModulesCount) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * function that return count of active eniture modules
     * @return int
     */
    public function getActiveEnitureModulesCount()
    {
        $activeModules = array_keys($this->dataHelper->getActiveCarriersForENCount());
        $activeEnitureModulesArr = array_filter($activeModules, function ($moduleName) {
            if (substr($moduleName, 0, 2) == 'EN') {
                return true;
            }
                return false;
        });
            
        return count($activeEnitureModulesArr);
    }
}
