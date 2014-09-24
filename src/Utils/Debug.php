<?php

namespace Goose\Utils;

class Debug {
    private static $enableTrace = false;

    public static function init($enableTrace) {
        self::$enableTrace = $enableTrace;
    }

    public static function trace($logPrefix, $message) {
        if (self::$enableTrace) {
            if (empty($logPrefix)) {
                $logPrefix = 'Default';
            }

            echo '<p><strong>' . $logPrefix . ':</strong> ' . $message . '</p>';
        }
    }
}