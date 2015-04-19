<?php

namespace Goose\Utils;

/**
 * Debug logger
 *
 * @todo Re-factor to use psr/log
 *
 * @package Goose\Utils
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
class Debug {
    /** @var bool */
    private static $enableTrace = false;

    /**
     * @param bool $enableTrace
     */
    public static function init($enableTrace) {
        self::$enableTrace = $enableTrace;
    }

    /**
     * @param string $logPrefix
     * @param string $message
     */
    public static function trace($logPrefix, $message) {
        if (self::$enableTrace) {
            if (empty($logPrefix)) {
                $logPrefix = 'Default';
            }

            echo '<p><strong>' . $logPrefix . ':</strong> ' . $message . '</p>';
        }
    }
}