<?php
namespace RedChamps\IpSecurity\Model;

/**
 * Class RedChamps_IpSecurity_Model_Ipsecuritylog
 */
class Ipsecuritylog extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Internal constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(ResourceModel\Ipsecuritylog::class);
    }
}
