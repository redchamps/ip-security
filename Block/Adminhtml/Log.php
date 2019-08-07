<?php
namespace RedChamps\IpSecurity\Block\Adminhtml;

use Magento\Backend\Block\Widget\Context;

/**
 * Class RedChamps_IpSecurity_Block_Adminhtml_Log
 */
class Log extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     */
    public function __construct(Context $context)
    {
        $this->_controller = 'adminhtml_log';
        $this->_blockGroup = 'RedChamps_IpSecurity';
        $this->_headerText = __('RedChamps IP Security log table');

        parent::__construct($context);
        $this->removeButton('add');
    }
}
