<?php
namespace RedChamps\IpSecurity\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class RedChamps_IpSecurity_Adminhtml_Action_Log
 */
class Log extends Action
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
    /**
     * Init action
     * @return \RedChamps\IpSecurity\Controller\Adminhtml\Action\Log $this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_addBreadcrumb(
            __('Customers'),
            __('ET IP Security log')
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
        $this->_setActiveMenu('Magento_Customer::customer');
        $resultPage->getConfig()->getTitle()->prepend(__('Security Log'));
        return $resultPage;
    }
}
