<?php
namespace RedChamps\IpSecurity\Model;

/**
 * Class RedChamps_IpSecurity_Model_Iptokenlog
 */
class Iptokenlog extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Internal constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(ResourceModel\Iptokenlog::class);
    }
}
