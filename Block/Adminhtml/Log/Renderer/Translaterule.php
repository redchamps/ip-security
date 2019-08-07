<?php
namespace RedChamps\IpSecurity\Block\Adminhtml\Log\Renderer;

/**
 * Class RedChamps_IpSecurity_Block_Adminhtml_Log_Renderer_Translaterule
 */
class Translaterule extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Renders grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return mixed
     */
    public function _getValue(\Magento\Framework\DataObject $row)
    {
        $data = (string)parent::_getValue($row);
        return __($data);
    }
}
