<?php
namespace RedChamps\IpSecurity\Block\Adminhtml\System\Config\Form\Field\Token\Update;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class RedChamps_IpSecurity_Block_Adminhtml_System_Config_Form_Field_Token_Update_Date
 */
class Date extends Field
{
    /**
     * @inheritdoc
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $value = $element->getValue();
        if ($value) {
            $value = $this->formatDate($value, \IntlDateFormatter::MEDIUM, true);
        } else {
            $value = __(\RedChamps\IpSecurity\Model\ConfgManager::MESSAGE_TOKEN_NOT_UPDATED);
        }
        return "<span id='ip_security_token_last_updated_date'>" . $value . "</span>";
    }
}
