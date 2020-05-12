<?php

namespace Eniture\UPSSmallPackageQuotes\Block\System\Config;

use \Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use \Magento\Framework\Module\Manager;

/**
 * Class BoxSizes to show SBS message on quotes settings page
 */
class BoxSizes extends Field
{
    /**
     *
     */
    const BOXSIZES_TEMPLATE = 'system/config/boxsizes.phtml';

    /**
     * @var Manager
     */
    private $moduleManager;
    /**
     * @var string
     */
    public $enable = 'no';

    /**
     * @param Context $context
     * @param Manager $moduleManager
     * @param array $data
     */
    public function __construct(Context $context, Manager $moduleManager, array $data = [])
    {
        $this->moduleManager   = $moduleManager;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        $this->checkBinPackagingModule();
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::BOXSIZES_TEMPLATE);
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
     * checkBinPackagingModule
     */
    public function checkBinPackagingModule()
    {
        if ($this->moduleManager->isEnabled('Eniture_StandardBoxSizes')) {
            $this->enable = 'yes';
        }
    }
}
