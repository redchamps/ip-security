<?php
namespace RedChamps\IpSecurity\Block\Adminhtml\Log;

use Magento\Backend\Block\Template\Context;
use RedChamps\IpSecurity\Model\IpsecuritylogFactory;
use Magento\Backend\Block\Widget\Grid\Extended;

/**
 * Class RedChamps_IpSecurity_Block_Adminhtml_Log_Grid
 */
class Grid extends Extended
{

    /**
     * @var IpsecuritylogFactory
     */
    protected $ipSecurityLogFactory;
    /**
     * Constructor
     */
    public function __construct(
        IpsecuritylogFactory $ipSecurityLogFactory,
        Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->ipSecurityLogFactory = $ipSecurityLogFactory;
        $this->setId('ipsecuritylogGrid');
        $this->setDefaultSort('update_time');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Prepare grid collection object
     *
     * @return \RedChamps\IpSecurity\Block\Adminhtml\Log\Grid $this
     */
    protected function _prepareCollection()
    {
        /** @var \RedChamps\IpSecurity\Model\Ipsecuritylog $model */
        $model = $this->ipSecurityLogFactory->create();

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

        $this->addColumn('blocked_ip', [
            'header' => __('Blocked IP'),
            'align' => 'left',
            'width' => '150px',
            'index' => 'blocked_ip',
        ]);

        $this->addColumn('qty', [
            'header' => __('Qty blocked'),
            'align' => 'left',
            'width' => '100px',
            'index' => 'qty',
            'type' => 'number',
        ]);

        $this->addColumn('last_block_rule', [
            'header' => __('Last block rule'),
            'align' => 'left',
            'width' => '300px',
            'index' => 'last_block_rule',
            'renderer' => \RedChamps\IpSecurity\Block\Adminhtml\Log\Renderer\Translaterule::class,
            'filter' => false,
        ]);

        $this->addColumn('create_time', [
            'header' => __('First block'),
            'align' => 'left',
            'width' => '160px',
            'index' => 'create_time',
            'type' => 'datetime',
        ]);

        $this->addColumn('update_time', [
            'header' => __('Last block'),
            'align' => 'left',
            'width' => '160px',
            'index' => 'update_time',
            'type' => 'datetime',
        ]);

        $this->addColumn('blocked_from', [
            'header' => __('Blocked from'),
            'align' => 'left',
            //'width'     => '100px',
            'index' => 'blocked_from',
        ]);

        $this->addExportType('*/*/exportCsv', __('CSV'));
        $this->addExportType('*/*/exportXml', __('Excel XML'));

        return parent::_prepareColumns();
    }
}
