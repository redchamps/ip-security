<?php
namespace RedChamps\IpSecurity\Model;

/**
 * Class RedChamps_IpSecurity_Model_IpVariable
 */
class IpVariable
{

    protected $_options = null;

    /**
     * Option getter
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->_options === null) {
            $this->_options = [
                [
                    'label' => 'REMOTE_ADDR',
                    'value' => 'REMOTE_ADDR'
                ],
                [
                    'label' => 'HTTP_X_REAL_IP',
                    'value' => 'HTTP_X_REAL_IP'
                ],
                [
                    'label' => 'HTTP_CLIENT_IP',
                    'value' => 'HTTP_CLIENT_IP'
                ],
                [
                    'label' => 'HTTP_X_FORWARDED_FOR',
                    'value' => 'HTTP_X_FORWARDED_FOR'
                ],
                [
                    'label' => 'HTTP_X_CLUSTER_CLIENT_IP',
                    'value' => 'HTTP_X_CLUSTER_CLIENT_IP'
                ],
            ];
        }
        return $this->_options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function getOptionArray()
    {
        $_options = [];
        foreach ($this->toOptionArray() as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getOptionArray();
    }
}
