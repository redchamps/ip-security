<?php
namespace RedChamps\IpSecurity\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class RedChamps_IpSecurity_Model_Ipsecuritylog
 */
class Ipsecuritylog extends AbstractModel
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
