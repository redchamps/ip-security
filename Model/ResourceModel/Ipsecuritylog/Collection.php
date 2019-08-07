<?php
namespace RedChamps\IpSecurity\Model\ResourceModel\Ipsecuritylog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class RedChamps_IpSecurity_Model_ResourceModel_Ipsecuritylog_Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Internal constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \RedChamps\IpSecurity\Model\Ipsecuritylog::class,
            \RedChamps\IpSecurity\Model\ResourceModel\Ipsecuritylog::class
        );
    }
}
