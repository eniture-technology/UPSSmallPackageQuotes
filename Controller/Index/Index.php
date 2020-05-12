<?php

namespace Eniture\UPSSmallPackageQuotes\Controller\Index;

use Eniture\UPSSmallPackageQuotes\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;

/**
 * Class Index receives outer HTTP requests like Plan status
 */
class Index extends Action
{
    /**
     * @var
     */
    private $request;
    /**
     * @var ConfigInterface
     */
    private $resourceConfig;
    /**
     * @var Data
     */
    private $helper;

    /**
     * Index constructor.
     * @param Context $context
     * @param ConfigInterface $resourceConfig
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        ConfigInterface $resourceConfig,
        Data $helper
    ) {
        $this->request = $context->getRequest();
        $this->resourceConfig = $resourceConfig;
        $this->helper= $helper;
        parent::__construct($context);
    }

    /**
     *
     */
    public function execute()
    {
        $params = $this->request->getParams();

        if (!empty($params)) {
            $plan       = $params['pakg_group'] ?? '';
            $expireDay  = $params['pakg_duration'] ?? '';
            $expiryDate = $params['expiry_date'] ?? '';
            $planType   = $params['plan_type'] ?? '';
            $pakgPrice  = $params['pakg_price'] ?? '0';
            if ($pakgPrice == '0') {
                $plan = '0';
            }

            $today =  date('F d, Y');
            if (strtotime($today) > strtotime($expiryDate)) {
                $plan ='-1';
            }

            $this->saveConfigurations('plan', $plan);
            $this->saveConfigurations('expireday', $expireDay);
            $this->saveConfigurations('expiredate', $expiryDate);
            $this->saveConfigurations('storetype', $planType);
            $this->saveConfigurations('pakgprice', $pakgPrice);
            $this->helper->clearCache();
        }
    }

        /**
         * @param string $path
         * @param string $value
         */
    public function saveConfigurations($path, $value)
    {
        $this->resourceConfig->saveConfig(
            'eniture/ENUPSSmpkg/'.$path,
            $value,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
    }
}
