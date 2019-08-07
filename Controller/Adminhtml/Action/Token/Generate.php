<?php
namespace RedChamps\IpSecurity\Controller\Adminhtml\Action\Token;

use RedChamps\IpSecurity\Model\ConfgManager;

/**
 * Class RedChamps_IpSecurity_Adminhtml_Action_Token_Generate
 */
class Generate extends Base
{

    /**
     * action generate token
     */
    public function execute()
    {
        $response = [
            'frontUrl' => '',
            'adminUrl' => '',
            'date' => ''
        ];

        $value = $this->getRequest()->getParam('ip_security_token_name');

        if ($value != '') {
            /** @var ConfgManager $helper */
            $helper = $this->configManager;

            $date = $helper->setLastUpdateToken();

            $value = trim($value);

            $helper->setToken($value);

            $response['frontUrl'] = $helper->getFrontTokenUrl();
            $response['adminUrl'] = $helper->getAdminTokenUrl();
            $this->cleanCache();

            $response['date'] = $date;
        }
        $this->processAndSendResponse($response);
    }
}
