<?php
namespace RedChamps\IpSecurity\Block\Adminhtml\Token;

/**
 * Class RedChamps_IpSecurity_Block_Adminhtml_Token_Log
 */
class Log extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_token_log';
        $this->_blockGroup = 'RedChamps_IpSecurity';
        $this->_headerText = __('IP Security Access Token log');

        parent::_construct();
        $this->removeButton('add');
    }
}
