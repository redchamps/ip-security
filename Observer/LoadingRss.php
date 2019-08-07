<?php
/**
 * Created by RedChamps.
 * User: Rav
 * Date: 28/07/17
 * Time: 3:07 PM
 */
namespace RedChamps\IpSecurity\Observer;

use Magento\Framework\Event\Observer;

class LoadingRss extends Base
{
    /**
     * Rss with admin authentication
     * @var array
     */
    protected $_requestPathList = [
        '/rss/feed/index/type/new_order',
        '/rss/feed/index/type/notifystock',
        '/rss/feed/index/type/review'
    ];

    /**
     * If loading Frontend and router is "rss"
     *
     * Event: controller_action_predispatch
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        foreach ($this->_requestPathList as $pattern) {
            if (strpos($this->request->getPathInfo(), $pattern) !== false) {
                $this->getHelper()->log('onLoadingRss()');

                $eventName = (string)$observer->getEvent()->getName();
                $this->getHelper()->log('event Name: ' . $eventName);

                $this->_readAdminConfig();
                $this->_readTokenConfig();
                $this->_processIpCheck($observer);
                break;
            }
        }
    }
}
