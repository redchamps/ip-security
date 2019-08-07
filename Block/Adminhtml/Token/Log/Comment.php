<?php
namespace RedChamps\IpSecurity\Block\Adminhtml\Token\Log;

use Magento\Framework\View\Element\Template;
use RedChamps\IpSecurity\Model\ConfgManager as Helper;

class Comment extends Template
{
    protected $helper;

    public function __construct(
        Helper $helper,
        Template\Context $context,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    public function getToken()
    {
        return $this->helper->getToken();
    }

    public function getTokenExpiredTimeMessage()
    {
        return $this->helper->getTokenExpiredTimeMessage();
    }

    public function isEnabledIpSecurityToken()
    {
        return $this->helper->isEnabledIpSecurityToken();
    }
}
