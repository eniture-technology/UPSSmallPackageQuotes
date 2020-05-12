<?php

namespace Eniture\UPSSmallPackageQuotes\App;

use Magento\Framework\App\Area as Area;
use Magento\Framework\App\State as ParentState;


class State extends ParentState
{
    /**
     *
     */
    public function validateAreaCode()
    {
        if (!isset($this->_areaCode)) {
            $this->setAreaCode(Area::AREA_GLOBAL);
        }
    }
}
