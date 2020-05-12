<?php
namespace Eniture\UPSSmallPackageQuotes\Controller\Test;

use Eniture\UPSSmallPackageQuotes\Helper\Data;
use Eniture\UPSSmallPackageQuotes\Helper\EnConstants;
use \Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

/**
 * Class TestConnection collects, sends and receive data for test connection
 */
class TestConnection extends Action
{
    /**
     * @var Data
     */
    public $dataHelper;
    /**
     * @var
     */
    public $request;

    /**
     * TestConnection constructor.
     * @param Context $context
     * @param Data $dataHelper
     */
    public function __construct(
        Context $context,
        Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
        $this->request = $context->getRequest();
        parent::__construct($context);
    }


    /**
     *
     */
    public function execute()
    {
        foreach ($this->getRequest()->getPostValue() as $key => $data) {
            $credentials[$key] = filter_var($data, FILTER_SANITIZE_STRING);
        }
        $postData = [
            'platform'              => 'magento2',
            'ups_username'          => $credentials['userName'],
            'ups_password'          => $credentials['password'],
            'ups_license_key'       => $credentials['upsLcnsKey'],
            'ups_account_number'    => $credentials['accountNumber'],
            'ups_domain_name'       => $this->request->getServer('SERVER_NAME'),
            'plugin_licence_key'    => $credentials['pluginLicenceKey'],
        ];

        $response = $this->dataHelper->upsSmpkgSendCurlRequest(EnConstants::TEST_CONN_URL, $postData);
        $result = $this->upsSmpkgLtlTestConnResponse($response);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($result);
    }


    /**
     * @param $response
     * @return false|string
     */
    public function upsSmpkgLtlTestConnResponse($response)
    {
        $response1 = [];
        $successMsg = 'The test resulted in a successful connection.';
        $erMsg = 'The credentials entered did not result in a successful test. Confirm your credentials and try again.';

        if (isset($response->error) && $response->error == 1) {
            $response1['Error'] =  $erMsg;
        } elseif ((isset($response->error) && isset($response->success)) && $response->error == 1) {
            $response1['Error'] =  $erMsg;
        } elseif (isset($response->error) && !is_int($response->error)) {
            $response1['Error'] =  $response->error;
        } else {
            $response1['Success'] =  $successMsg;
        }
        return json_encode($response1);
    }
}
