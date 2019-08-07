<?php

namespace RedChamps\IpSecurity\Controller\Adminhtml\Action\Token;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Serialize\Serializer\Json;
use RedChamps\IpSecurity\Model\ConfgManager;

/**
 * Class RedChamps_IpSecurity_Adminhtml_Action_Token_Base
 */
abstract class Base extends Action
{
    protected $configManager;

    protected $cacheList;

    protected $jsonHandler;

    public function __construct(
        ConfgManager $configManager,
        Context $context,
        TypeListInterface $cacheList,
        Json $jsonHandler
    ) {
        $this->configManager = $configManager;
        $this->cacheList = $cacheList;
        $this->jsonHandler = $jsonHandler;
        parent::__construct($context);
    }

    /**
     * Action Delete Token
     */
    protected function cleanCache()
    {
        $this->cacheList->cleanType('config');
    }

    protected function processAndSendResponse($response)
    {
        $body = $this->jsonHandler->serialize($response);
        $this->getResponse()->setBody($body);
    }
}
