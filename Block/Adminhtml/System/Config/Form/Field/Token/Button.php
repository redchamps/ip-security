<?php
namespace RedChamps\IpSecurity\Block\Adminhtml\System\Config\Form\Field\Token;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class RedChamps_IpSecurity_Block_Adminhtml_System_Config_Form_Field_Token_Button
 */
class Button extends Field
{

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('RedChamps_IpSecurity::admin_config_generation_button.phtml');
        }
        return $this;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        //1400 fix
        if (!($originalData = $element->getOriginalData())) {
            $originalData = [];
            foreach ($element->getData("field_config") as $key => $value) {
                if (!$value->hasChildren()) {
                    $originalData[$key] = (string)$value;
                }
            }
        }

        $originalData["token_area"] = "front";

        $this->addData(
            [
                'button_label' => __('Generate token'),

                'comment' => __($originalData['comment']),
                'html_id' => $element->getHtmlId(),
                'token_area' => $originalData["token_area"],

                'ajax_url' => $this->_urlBuilder
                    ->getUrl(
                        'ipsecurity/action/token_generate',
                        ["token_area" => $originalData["token_area"]]
                    ),

                'button_label_delete' => __('Delete token'),

                'ajax_url_delete' => $this->_urlBuilder
                    ->getUrl(
                        'ipsecurity/action/token_delete',
                        ["token_area" => $originalData["token_area"]]
                    ),
                'form_key' => $this->getFormKey()
            ]
        );

        return $this->_toHtml();
    }
}
