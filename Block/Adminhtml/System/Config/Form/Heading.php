<?php

namespace RedChamps\IpSecurity\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;
use RedChamps\Core\Model\Processor;

/*
 * Package: IpSecurity
 * Class: Heading
 * Company: RedChamps
 * */
class Heading extends Field
{
    /**
     * Return heading block html
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = '<div 
                    class="rc-heading" 
                    style="padding:12px;margin:0 0 10px 0;background-color:#fffeed;border: 1px solid #dddddd;"
                    >
                    <span class="row-1" style="display: block">
                    <span class="left">
                    <span class="logo">
                    <a href="https://redchamps.com/" target="_blank">
                    <img 
                        src="https://redchamps.com/pub/media/logo/stores/1/logo.svg"
                        width="200px"
                        >
                    </span>
                    </a>
                    </span>
                    <span class="right" style="float: right; margin-top: 15px" >
                    <a 
                        type="button" 
                        class="action- scalable action-secondary" 
                        data-ui-id="view-extensions-button" 
                        target="_blank" 
                        href="https://redchamps.com/magento-2-extensions.html?utm_source=ip-security-admin-setting"
                        >
                        <span>' . __("View More Extensions") . '</span>
                    </a>
                    <a 
                        type="button" 
                        class="action- scalable action-secondary" 
                        data-ui-id="follow-twitter" 
                        target="_blank" 
                        href="https://twitter.com/intent/follow?screen_name=@_redChamps"
                        style="margin-left: 10px"
                        >
                        <span>' . __("Follow us on Twitter") . '</span>
                    </a>
                    </span>
                    </span>
                    <span class="content row-2" style="display: block;margin: 5px 0 0 18px;">
                    <span style="color: #ef6262; font-weight: bold"> 
                    IP Security
                    </span> 
                    is product of ET Web Solutions available for 
                    <a href="https://shop.etwebsolutions.com/eng/et-ip-security.html" target="_blank">Magento 1</a>.  
                    It is migrated to Magento 2 by  
                    <a href="https://redchamps.com/" target="_blank">RedChamps</a>. 
                    <b>
                    Found a bug? Report it 
                    <a href="https://github.com/magento/magento2/issues" target="_blank">here</a>
                    </b>
                    </span>
            </div>';

        return $html;
    }
}
