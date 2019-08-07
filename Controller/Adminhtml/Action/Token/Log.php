<?php
namespace RedChamps\IpSecurity\Controller\Adminhtml\Action\Token;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class RedChamps_IpSecurity_Adminhtml_Action_Token_Log
 */
class Log extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Magento_Customer::customer')
            ->_addBreadcrumb(
                __('Customers'),
                __('ET IP Security Token log')
            );

        return $this;
    }

    /**
     * Default Action
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $this->_initAction();
        $resultPage->getConfig()->getTitle()->prepend(__('Security Token Log'));
        return $resultPage;
    }
}
