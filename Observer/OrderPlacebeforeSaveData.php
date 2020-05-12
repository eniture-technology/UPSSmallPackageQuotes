<?php

namespace Eniture\UPSSmallPackageQuotes\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Class OrderPlacebeforeSaveData setup ODW data
 */
class OrderPlacebeforeSaveData implements ObserverInterface
{
    /**
     * @var SessionManagerInterface
     */
    private $coreSession;

    /**
     * @param SessionManagerInterface $coreSession
     */
    public function __construct(
        SessionManagerInterface $coreSession
    ) {
        $this->coreSession = $coreSession;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {

        try {
            $isMulti = '0';
            $multiShip = false;
            $order = $observer->getEvent()->getOrder();
            $quote = $order->getQuote();

            if (isset($quote)) {
                $isMulti = $quote->getIsMultiShipping();
            }
            $method = $order->getShippingMethod();
            if (strpos($method, 'ENUPSSmpkg') !== false) {
                $orderDetailData = $this->coreSession->getUpsOrderDetailSession();
                $semiOrderDetailData = $this->coreSession->getSemiOrderDetailSession();

                if ($orderDetailData && $semiOrderDetailData == null) {
                    if (count($orderDetailData['shipmentData']) == 1) {
                        $orderDetailData['shipmentData'] = $this->setQuotesIfSingleShipment($orderDetailData, $order);
                    }
                } elseif ($semiOrderDetailData) {
                    $orderDetailData = $semiOrderDetailData;
                    $this->coreSession->unsSemiOrderDetailSession();
                }
                $order->setData('order_detail_data', json_encode($orderDetailData));
                $order->save();
                if (!$isMulti) {
                    $this->coreSession->unsUpsLTLOrderDetailSession();
                }
            }
        } catch (\Exception $e) {
            $e->getMessage();
        }
    }

    /**
     * @param type $orderDetailData
     * @param type $order
     * @return type
     */
    private function setQuotesIfSingleShipment($orderDetailData, $order)
    {
        $shippingMethod = explode('_', $order->getShippingMethod());
        $titlePart = "UPS Small Package Quotes - ";
        $newData = [];
        foreach ($orderDetailData['shipmentData'] as $key => $data) {
            $newData[$key]['quotes'] = [
                'code'  => $shippingMethod[1],
                'title' => str_replace($titlePart, "", $order->getShippingDescription()),
                'rate'  => number_format((float)$order->getShippingAmount(), 2, '.', '')
            ];
        }

        $mergedNewData = array_replace_recursive($orderDetailData['shipmentData'], $newData);
        return $mergedNewData;
    }
}
