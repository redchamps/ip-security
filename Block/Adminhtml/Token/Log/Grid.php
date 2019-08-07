<?php
namespace RedChamps\IpSecurity\Block\Adminhtml\Token\Log;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use RedChamps\IpSecurity\Model\IptokenlogFactory;

/**
 * Class RedChamps_IpSecurity_Block_Adminhtml_Token_Log_Grid
 */
class Grid extends Extended
{

    /**
     * @var IptokenlogFactory
     */
    protected $ipSecurityTokenLogFactory;

    /**
     * Constructor
     * @param IptokenlogFactory $ipSecurityTokenLogFactory
     * @param Context $context
     * @param Data $backendHelper
     * @param array $data
     */
    public function __construct(
        IptokenlogFactory $ipSecurityTokenLogFactory,
        Context $context,
        Data $backendHelper,
        array $data = []
    ) {
        $this->ipSecurityTokenLogFactory = $ipSecurityTokenLogFactory;
        $this->setId('ipSecurityTokenLogGrid');
        $this->setDefaultSort('create_time');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Prepare grid collection object
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareCollection()
    {
        /** @var \RedChamps\IpSecurity\Model\Iptokenlog $model */
        $model = $this->ipSecurityTokenLogFactory->create();

        $collection = $model->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @return $this
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('create_time', [
            'header' => __('Date'),
            'align' => 'left',
            'width' => '160px',
            'index' => 'create_time',
            'type' => 'datetime',
        ]);

        $this->addColumn('last_block_rule', [
            'header' => __('Event'),
            'align' => 'left',
            'width' => '300px',
            'index' => 'last_block_rule',
            'renderer' => \RedChamps\IpSecurity\Block\Adminhtml\Log\Renderer\Translaterule::class,
            'filter' => false,
        ]);

        $this->addColumn('blocked_ip', [
            'header' => __('IP'),
            'align' => 'left',
            'width' => '150px',
            'index' => 'blocked_ip',
        ]);

        $this->addColumn('blocked_from', [
            'header' => __('Url'),
            'align' => 'left',
            //'width'     => '100px',
            'index' => 'blocked_from',
        ]);

        return parent::_prepareColumns();
    }
}
