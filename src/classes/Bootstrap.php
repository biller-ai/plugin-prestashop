<?php

namespace Biller\PrestaShop;

use Biller\BusinessLogic\Authorization\AuthorizationService;
use Biller\BusinessLogic\BootstrapComponent;
use Biller\BusinessLogic\Integration\Authorization\UserInfoRepository as UserInfoRepositoryInterface;
use Biller\BusinessLogic\Integration\Order\OrderStatusTransitionService as OrderStatusTransitionServiceInterface;
use Biller\BusinessLogic\Integration\Refund\OrderRefundService as OrderRefundServiceInterface;
use Biller\BusinessLogic\Notifications\DefaultNotificationChannel;
use Biller\BusinessLogic\Notifications\Interfaces\DefaultNotificationChannelAdapter;
use Biller\BusinessLogic\Notifications\Model\Notification;
use Biller\BusinessLogic\Order\OrderReference\Entities\OrderReference;
use Biller\Infrastructure\Configuration\ConfigEntity;
use Biller\Infrastructure\Configuration\Configuration;
use Biller\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Biller\Infrastructure\ORM\RepositoryRegistry;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\BusinessService\OrderRefundService;
use Biller\PrestaShop\BusinessService\OrderStatusTransitionService;
use Biller\PrestaShop\InfrastructureService\ConfigurationService;
use Biller\PrestaShop\InfrastructureService\LoggerService;
use Biller\PrestaShop\Repositories\BaseRepository;
use Biller\PrestaShop\Repositories\NotificationHubRepository;
use Biller\PrestaShop\Repositories\OrderReferenceRepository;
use Biller\PrestaShop\Repositories\UserInfoRepository;
use Biller\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Biller\BusinessLogic\Authorization\Contracts\AuthorizationService as AuthroizationServiceInterface;
use Biller\PrestaShop\Utility\Config\BillerOrderStatusMapping;
use Biller\PrestaShop\Utility\Config\BillerPaymentConfiguration;
use Biller\PrestaShop\Utility\Config\Contract\BillerOrderStatusMapping as BillerOrderStatusMappingInterface;
use Biller\PrestaShop\Utility\Config\Contract\BillerPaymentConfiguration as BillerPaymentConfigurationInterface;


/**
 * Class Bootstrap
 * @package Biller\PrestaShop
 */
class Bootstrap extends BootstrapComponent
{
    /**
     * Initializes infrastructure services and utilities.
     */
    protected static function initServices()
    {
        parent::initServices();

        ServiceRegister::registerService(
            ShopLoggerAdapter::CLASS_NAME,
            function () {
                return LoggerService::getInstance();
            }
        );

        ServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () {
                return ConfigurationService::getInstance();
            }
        );

        ServiceRegister::registerService(
            UserInfoRepositoryInterface::class,
            function () {
                return new UserInfoRepository();
            }
        );

        ServiceRegister::registerService(
            OrderStatusTransitionServiceInterface::class,
            function () {
                return new OrderStatusTransitionService();
            }
        );

        ServiceRegister::registerService(
            OrderRefundServiceInterface::class,
            function () {
                return new OrderRefundService();
            }
        );

        ServiceRegister::registerService(
            AuthroizationServiceInterface::class,
            function () {
                return AuthorizationService::getInstance();
            }
        );

        ServiceRegister::registerService(
            BillerPaymentConfigurationInterface::class,
            function () {
                return new BillerPaymentConfiguration();
            }
        );

        ServiceRegister::registerService(
            BillerOrderStatusMappingInterface::class,
            function () {
                return new BillerOrderStatusMapping();
            }
        );

        ServiceRegister::registerService(
            DefaultNotificationChannelAdapter::CLASS_NAME,
            function() {
                return new DefaultNotificationChannel();
            }
        );
    }

    /**
     * Initializes repositories.
     * @throws RepositoryClassException
     */
    protected static function initRepositories()
    {
        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(OrderReference::getClassName(), OrderReferenceRepository::getClassName());
        RepositoryRegistry::registerRepository(Notification::getClassName(), NotificationHubRepository::getClassName());
    }
}
