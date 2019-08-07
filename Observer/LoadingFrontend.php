<?php
/**
 * Created by RedChamps.
 * User: Rav
 * Date: 28/07/17
 * Time: 3:07 PM
 */
namespace RedChamps\IpSecurity\Observer;

use Magento\Framework\Event\Observer;

class LoadingFrontend extends Base
{
    public function execute(Observer $observer)
    {
        $this->_readFrontendConfig();
        $this->_readTokenConfig();
        $this->_processIpCheck($observer);
    }

    /**
     * Reading configuration for Frontend
     */
    protected function _readFrontendConfig()
    {
        $this->_redirectPage = $this->trimTrailingSlashes(
            $this->configManager->readConfig('front/redirect_page')
        );
        $this->_redirectBlank = $this->configManager->readConfig('front/redirect_blank');
        $this->_rawAllowIpData = $this->configManager->readConfig('front/allow');
        $this->_rawBlockIpData = $this->configManager->readConfig('front/block');
        $this->_eventEmail = $this->configManager->readConfig('front/email_event');
        $this->_emailTemplate = $this->configManager->readConfig('front/email_template');
        $this->_emailIdentity = $this->configManager->readConfig('front/email_identity');
        $this->_alwaysNotify = $this->configManager->readConfig('front/email_always');
        $this->_rawExceptIpData = $this->configManager->readConfig('maintenance/except');

        $this->_storeType = __("Frontend");
        $this->_isFrontend = true;
    }
}
