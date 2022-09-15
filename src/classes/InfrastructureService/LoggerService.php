<?php

namespace Biller\PrestaShop\InfrastructureService;

use Biller\Infrastructure\Configuration\Configuration;
use Biller\Infrastructure\Logger\LogData;
use Biller\Infrastructure\Logger\Logger;
use Biller\Infrastructure\ServiceRegister;
use Biller\Infrastructure\Singleton;
use Biller\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use PrestaShopLogger;

/**
 * Class LoggerService
 *
 * @package Biller\PrestaShop\InfrastructureService
 */
class LoggerService extends Singleton implements ShopLoggerAdapter
{
    /**
     * PrestaShop log severity level codes
     */
    const PRESTASHOP_INFO = 1;
    const PRESTASHOP_WARNING = 2;
    const PRESTASHOP_ERROR = 3;

    /** @var Singleton instance of this class */
    protected static $instance;

    /** @var string[] Log level names for corresponding log level codes */
    private static $logLevelName = array(
        Logger::ERROR => 'ERROR',
        Logger::WARNING => 'WARNING',
        Logger::INFO => 'INFO',
        Logger::DEBUG => 'DEBUG',
    );

    /** @var int[] Mappings of Biller log severity levels to Prestashop log severity levels */
    private static $logMapping = array(
        Logger::ERROR => self::PRESTASHOP_ERROR,
        Logger::WARNING => self::PRESTASHOP_WARNING,
        Logger::INFO => self::PRESTASHOP_INFO,
        Logger::DEBUG => self::PRESTASHOP_INFO,
    );

    /**
     * Log message in system.
     *
     * @param LogData $data Data to be logged
     */
    public function logMessage(LogData $data)
    {
        try {
            $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
            $minLogLevel = $configService->getMinLogLevel();
            $logLevel = $data->getLogLevel();

            if (($logLevel > (int)$minLogLevel) && !$configService->isDebugModeEnabled()) {
                return;
            }
        } catch (\Exception $e) {
            // if we cannot access configuration, log any error directly.
            $logLevel = Logger::ERROR;
        }

        $message = 'BILLER LOG:' . ' | '
            . 'Date: ' . date('d/m/Y') . ' | '
            . 'Time: ' . date('H:i:s') . ' | '
            . 'Log level: ' . self::$logLevelName[$logLevel] . ' | '
            . 'Message: ' . $data->getMessage();
        $context = $data->getContext();
        if (!empty($context)) {
            $contextData = array();
            foreach ($context as $item) {
                $contextData[$item->getName()] = print_r($item->getValue(), true);
            }

            $message .= ' | ' . 'Context data: [' . json_encode($contextData) . ']';
        }

        PrestaShopLogger::addLog($message, self::$logMapping[$logLevel]);
    }
}
