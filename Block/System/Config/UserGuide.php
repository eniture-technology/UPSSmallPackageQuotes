<?php
namespace Eniture\UPSSmallPackageQuotes\Block\System\Config;

use Eniture\UPSSmallPackageQuotes\Helper\Data;
use Eniture\UPSSmallPackageQuotes\Helper\EnConstants;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class UserGuide page
 */
class UserGuide extends Field
{
    /**
     *
     */
    const GUIDE_TEMPLATE = 'system/config/userguide.phtml';

    /**
     * @var Data
     */
    private $dataHelper;

    public $docUrl = EnConstants::EN_URL.'#documentation';

    /**
     * UserGuide constructor.
     * @param Context $context
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper      = $dataHelper;
        parent::__construct($context, $data);
    }
    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::GUIDE_TEMPLATE);
        }
        return $this;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Show UPS Small Plan Notice
     * @return string
     */
    public function upsSmallPlanNotice()
    {
        return $this->dataHelper->upsSmallSetPlanNotice();
    }
}
