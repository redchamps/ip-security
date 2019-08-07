<?php
namespace RedChamps\IpSecurity\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class RedChamps_IpSecurity_Model_ResourceModel_Iptokenlog
 */
class Iptokenlog extends AbstractDb
{
    /**
     * Internal constructor
     */
    public function _construct()
    {
        // Note that the logid refers to the key field in your database table.
        $this->_init('ipsecurity_token_log', 'logid');
    }
}
