<?php
/**
 * Created by RedChamps.
 * User: Rav
 * Date: 28/07/17
 * Time: 3:07 PM
 */
namespace RedChamps\IpSecurity\Observer;

use Magento\Framework\Event\Observer;

class LoadingAdmin extends Base
{
    /**
     * If loading Admin
     *
     * Event: controller_action_predispatch
     * @param $observer Observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $this->getHelper()->log('onLoadingAdmin()');
        $eventName = (string)$observer->getEvent()->getName();
        $this->getHelper()->log('event Name: ' . $eventName);
        $this->_readAdminConfig();
        $this->_readTokenConfig();
        $this->_processIpCheck($observer);
    }
}
