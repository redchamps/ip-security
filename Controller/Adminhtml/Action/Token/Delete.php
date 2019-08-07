<?php
namespace RedChamps\IpSecurity\Controller\Adminhtml\Action\Token;

use RedChamps\IpSecurity\Model\ConfgManager;

/**
 * Class RedChamps_IpSecurity_Adminhtml_Action_Token_Delete
 */
class Delete extends Base
{
    /**
     * Action Delete Token
     */
    public function execute()
    {
        /** @var ConfgManager $helper */
        $helper  = $this->configManager;
        $response = [
            'frontUrl' => __(ConfgManager::MESSAGE_TOKEN_NOT_CREATED),
            'adminUrl' => __(ConfgManager::MESSAGE_TOKEN_NOT_CREATED),
            'date' => __(ConfgManager::MESSAGE_TOKEN_NOT_UPDATED)
        ];

        $helper->resetTokenLinks();
        $helper->resetLastUpdateTokenTime();

        $this->cleanCache();
        $this->processAndSendResponse($response);
    }
}
