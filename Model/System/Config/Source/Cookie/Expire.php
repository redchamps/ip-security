<?php
namespace RedChamps\IpSecurity\Model\System\Config\Source\Cookie;

/**
 * Class RedChamps_IpSecurity_Model_System_Config_Source_Cookie_Expire
 */
class Expire
{
    const COOKIE_DISABLED_AFTER_1_HOUR = 1;
    const COOKIE_DISABLED_AFTER_24_HOUR = 24;

    public function toOptionArray()
    {
        $option = [];

        $option[] = [
            'label' => self::COOKIE_DISABLED_AFTER_1_HOUR . " " . __('hour'),
            'value' => self::COOKIE_DISABLED_AFTER_1_HOUR
        ];

        $option[] = [
            'label' => self::COOKIE_DISABLED_AFTER_24_HOUR . " " . __('hour'),
            'value' => self::COOKIE_DISABLED_AFTER_24_HOUR
        ];

        return $option;
    }
}
