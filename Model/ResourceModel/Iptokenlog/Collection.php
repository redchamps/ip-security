<?php
namespace RedChamps\IpSecurity\Model\ResourceModel\Iptokenlog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class RedChamps_IpSecurity_Model_ResourceModel_Iptokenlog_Collection
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
            \RedChamps\IpSecurity\Model\Iptokenlog::class,
            \RedChamps\IpSecurity\Model\ResourceModel\Iptokenlog::class
        );
    }
}
