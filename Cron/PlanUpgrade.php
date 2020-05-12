<?php

namespace Eniture\UPSSmallPackageQuotes\Cron;

use Eniture\UPSSmallPackageQuotes\Helper\EnConstants;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class PlanUpgrade checks plan status periodically
 */
class PlanUpgrade
{
    /**
     * @var StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var Curl
     */
    public $curl;
    /**
     * @var ConfigInterface
     */
    public $resourceConfig;
    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * PlanUpgrade constructor.
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     * @param ConfigInterface $resourceConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Curl $curl,
        ConfigInterface $resourceConfig,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->curl = $curl;
        $this->resourceConfig = $resourceConfig;
        $this->logger = $logger;
    }

    /**
     *
     */
    public function execute()
    {
        $domain = $this->storeManager->getStore()->getUrl();
        $webhookUrl = $domain . 'upssmallpackagequotes';
        $postData = http_build_query([
            'platform' => 'magento2',
            'carrier' => EnConstants::APP_ID,
            'store_url' => $domain,
            'webhook_url' => $webhookUrl,
        ]);

        $url    = EnConstants::PLAN_URL;

        $this->curl->post($url, $postData);
        $output = $this->curl->getBody();
        $result = json_decode($output, true);

        $plan       = $result['pakg_group'] ?? '';
        $expireDay  = $result['pakg_duration'] ?? '';
        $expiryDate = $result['expiry_date'] ?? '';
        $planType   = $result['plan_type'] ?? '';
        $pakgPrice  = $result['pakg_price'] ?? 0;

        if ($pakgPrice == 0) {
            $plan = 0;
        }

        $today = date('F d, Y');
        if (strtotime($today) > strtotime($expiryDate)) {
            $plan = '-1';
        }
        $this->saveConfigurations('eniture/ENUPSSmpkg/plan', "$plan");
        $this->saveConfigurations('eniture/ENUPSSmpkg/expireday', "$expireDay");
        $this->saveConfigurations('eniture/ENUPSSmpkg/expiredate', "$expiryDate");
        $this->saveConfigurations('eniture/ENUPSSmpkg/storetype', "$planType");
        $this->saveConfigurations('eniture/ENUPSSmpkg/pakgprice', "$pakgPrice");
        $this->saveConfigurations('eniture/ENUPSSmpkg/label', "Eniture - UPS Small Package Quotes");
        $this->logger->info($output);
    }


    /**
     * @param $path
     * @param $value
     */
    public function saveConfigurations($path, $value)
    {
        $this->resourceConfig->saveConfig(
            $path,
            $value,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );
    }
}
