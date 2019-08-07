<?php
namespace RedChamps\IpSecurity\Model\System\Config\Source\Token;

/**
 * Class RedChamps_IpSecurity_Model_System_Config_Source_Token_Expire
 */
class Expire
{
    const TOKEN_DISABLED_AFTER_3_DAYS = 3;
    const TOKEN_DISABLED_AFTER_7_DAYS = 7;
    const TOKEN_DISABLED_AFTER_10_DAYS = 10;
    const TOKEN_DISABLED_AFTER_14_DAYS = 14;

    public function toOptionArray()
    {
        $option = [];

        $option[] = [
            'label' => self::TOKEN_DISABLED_AFTER_3_DAYS . " " . __('days'),
            'value' => self::TOKEN_DISABLED_AFTER_3_DAYS
        ];

        $option[] = [
            'label' => self::TOKEN_DISABLED_AFTER_7_DAYS . " " . __('days'),
            'value' => self::TOKEN_DISABLED_AFTER_7_DAYS
        ];

        $option[] = [
            'label' => self::TOKEN_DISABLED_AFTER_10_DAYS . " " . __('days'),
            'value' => self::TOKEN_DISABLED_AFTER_10_DAYS
        ];

        $option[] = [
            'label' => self::TOKEN_DISABLED_AFTER_14_DAYS . " " . __('days'),
            'value' => self::TOKEN_DISABLED_AFTER_14_DAYS
        ];

        return $option;
    }
}
