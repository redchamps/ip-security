<?php
namespace RedChamps\IpSecurity\Block\Adminhtml\System\Config\Form\Field\Token;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use RedChamps\IpSecurity\Model\ConfgManager;

/**
 * Class RedChamps_IpSecurity_Block_Adminhtml_System_Config_Form_Field_Link
 */
class Link extends Field
{

    /**
     * @param AbstractElement|\Magento\Framework\Data\Form\Element\Text $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $value = $element->getValue();
        if ($value == '') {
            $value = __(ConfgManager::MESSAGE_TOKEN_NOT_CREATED);
        }

        $html = '<div id="' . $element->getHtmlId() . '" style="width:30em;height:40px;overflow-x: auto; white-space: nowrap;">';
        $html .= '<span style="font-weight: bold;" id="ip_security_token_link">' . $value . '</span>';
        $html .= '</div>';
        return $html;
    }
}
