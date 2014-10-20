<?php

class AtoPaymentSystem
{

    private static $_settingsCache = array();

    public static function getDriverParameter(
        $driverId,
        $name = '',
        $default = NULL
    )
    {
        if (!isset(self::$_settingsCache[$driverId])) {
            $oDB = AMI::getSingleton('db');
            $settings =
                $oDB->fetchValue(
                    DB_Query::getSnippet(
                        "SELECT `settings` " .
                        "FROM `cms_pay_drivers` " .
                        "WHERE `name` = %s AND `is_installed` = 1"
                    )
                        ->q($driverId)
                );
            if ($settings) {
                self::$_settingsCache[$driverId] = @unserialize($settings);
            } else {
                self::$_settingsCache[$driverId] = FALSE;
            }
        }
        if (
            isset(self::$_settingsCache[$driverId]) &&
            is_array(self::$_settingsCache[$driverId])
        ) {
            if ($name != '') {
                return
                    isset(self::$_settingsCache[$driverId][$name])
                        ? self::$_settingsCache[$driverId][$name]
                        : $default;
            } else {
                return self::$_settingsCache[$driverId];
            }
        }
        return $default;
    }
}
