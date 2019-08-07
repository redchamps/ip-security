<?php
namespace RedChamps\IpSecurity\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use RedChamps\IpSecurity\Model\IpVariableFactory;

/**
 * Class RedChamps_IpSecurity_Block_Adminhtml_GetIpInfo
 */
class GetIpInfo extends Field
{
    /**
     * @var IpVariableFactory
     */
    protected $ipSecurityIpVariableFactory;

    protected $phpRequest;

    public function __construct(
        IpVariableFactory $ipSecurityIpVariableFactory,
        Request $phpRequest,
        Context $context,
        array $data = []
    ) {
        $this->ipSecurityIpVariableFactory = $ipSecurityIpVariableFactory;
        $this->phpRequest = $phpRequest;
        parent::__construct($context, $data);
    }

    /**
     * Shows in admin panel which ip address returns each method
     *
     * @param AbstractElement $element
     * @return string
     *
     * @inheritdoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        /** @var \RedChamps\IpSecurity\Model\IpVariable $model */
        $model = $this->ipSecurityIpVariableFactory->create();

        $result = __('Below is a list of standard variables where the server can '
            . 'store the IP address of the visitor, and what each of these variables contains on your server:<br><br>');

        $getIpMethodArray = $model->getOptionArray();
        foreach ($getIpMethodArray as $key => $value) {
            $ip = (!empty($this->phpRequest->getServer($value))) ? $this->phpRequest->getServer($value) : __('Nothing');
            $result .= ' <b>' . $key . '</b> ' .
                __('returns') .
                '<b> ' . $ip . '</b><br>';
        }
        return $result;
    }
}
