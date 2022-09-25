<?php

namespace Biller\PrestaShop;

use Biller\BusinessLogic\Authorization\AuthorizationService;
use Biller\BusinessLogic\BootstrapComponent;
use Biller\BusinessLogic\Integration\Authorization\UserInfoRepository as UserInfoRepositoryInterface;
use Biller\BusinessLogic\Integration\Order\OrderStatusTransitionService as OrderStatusTransitionServiceInterface;
use Biller\BusinessLogic\Integration\Refund\OrderRefundService as OrderRefundServiceInterface;
use Biller\BusinessLogic\Notifications\Collections\ShopNotificationChannelCollection;
use Biller\BusinessLogic\Notifications\DefaultNotificationChannel;
use Biller\BusinessLogic\Notifications\Interfaces\DefaultNotificationChannelAdapter;
use Biller\BusinessLogic\Notifications\Interfaces\ShopNotificationChannelAdapter;
use Biller\BusinessLogic\Notifications\Model\Notification;
use Biller\BusinessLogic\Notifications\NotificationHub;
use Biller\BusinessLogic\Order\OrderReference\Entities\OrderReference;
use Biller\BusinessLogic\Order\OrderReference\Repository\OrderReferenceRepository as OrderReferenceRepositoryCore;
use Biller\Infrastructure\Configuration\ConfigEntity;
use Biller\Infrastructure\Configuration\Configuration;
use Biller\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Biller\Infrastructure\ORM\RepositoryRegistry;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\BusinessService\OrderRefundService;
use Biller\PrestaShop\BusinessService\OrderStatusTransitionService;
use Biller\PrestaShop\BusinessService\RefundAmountRequestService;
use Biller\PrestaShop\Entity\CompanyInfo;
use Biller\PrestaShop\InfrastructureService\ConfigurationService;
use Biller\PrestaShop\InfrastructureService\LoggerService;
use Biller\PrestaShop\Repositories\BaseRepository;
use Biller\PrestaShop\Repositories\CompanyInfoRepository;
use Biller\PrestaShop\Repositories\NotificationHubRepository;
use Biller\PrestaShop\Repositories\OrderReferenceRepository;
use Biller\PrestaShop\Repositories\UserInfoRepository;
use Biller\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Biller\BusinessLogic\Authorization\Contracts\AuthorizationService as AuthroizationServiceInterface;
use Biller\PrestaShop\Utility\Config\BillerOrderStatusMapping;
use Biller\PrestaShop\Utility\Config\BillerPaymentConfiguration;
use Biller\PrestaShop\Utility\Config\Contract\BillerOrderStatusMapping as BillerOrderStatusMappingInterface;
use Biller\PrestaShop\Utility\Config\Contract\BillerPaymentConfiguration as BillerPaymentConfigurationInterface;
use Biller\BusinessLogic\Integration\Refund\RefundAmountRequestService as RefundAmountRequestServiceInterface;
use Biller\BusinessLogic\Integration\Cancellation\CancellationService as CancellationServiceInterface;
use Biller\BusinessLogic\Integration\Shipment\ShipmentService as ShipmentServiceInterface;
use Biller\PrestaShop\BusinessService\ShipmentService;
use Biller\PrestaShop\BusinessService\CancellationService;
use Biller\PrestaShop\Utility\Services\CompanyInfoService;
use Biller\PrestaShop\Utility\Services\OrderService;
use Biller\PrestaShop\Utility\Version\Configuration\ConfigurationVersion16;
use Biller\PrestaShop\Utility\Version\Configuration\ConfigurationVersion17;
use Biller\PrestaShop\Utility\Version\Configuration\Contract\ConfigurationVersionInterface;
use Biller\PrestaShop\Utility\Version\Hooks\Contract\HooksVersionInterface;
use Biller\PrestaShop\Utility\Version\Hooks\HooksVersion16;
use Biller\PrestaShop\Utility\Version\Hooks\HooksVersion17;
use Biller\PrestaShop\Utility\Version\Hooks\HooksVersion177;
use Biller\PrestaShop\Utility\Version\OrderRefundMapper\Contract\OrderRefundMapperVersion;
use Biller\PrestaShop\Utility\Version\OrderRefundMapper\OrderRefundMapperVersion16;
use Biller\PrestaShop\Utility\Version\OrderRefundMapper\OrderRefundMapperVersion17;
use Biller\PrestaShop\Utility\Version\OrderRefundMapper\OrderRefundMapperVersion177;
use Biller\PrestaShop\Utility\Version\TemplateAndJs\TemplateAndJsVersion16;
use Biller\PrestaShop\Utility\Version\TemplateAndJs\TemplateAndJsVersion17;
use Biller\PrestaShop\Utility\Version\TemplateAndJs\TemplateAndJsVersion177;
use Biller\PrestaShop\Utility\TranslationUtility;
use Biller\PrestaShop\Utility\Services\AuthorizationService as UtilityAuthorizationService;
use Biller\PrestaShop\Utility\Version\Encryptor\Contract\EncryptorVersionInterface;
use Biller\PrestaShop\Utility\Version\Encryptor\EncryptorVersionVersion16;
use Biller\PrestaShop\Utility\Version\Encryptor\EncryptorVersionVersion17;
use Biller\PrestaShop\Utility\Version\Redirection\Contract\RedirectionVersionInterface;
use Biller\PrestaShop\Utility\Version\Redirection\RedirectionVersion16;
use Biller\PrestaShop\Utility\Version\Redirection\RedirectionVersion17;
use Biller\PrestaShop\Utility\Version\Redirection\RedirectionVersion177;
use Biller\PrestaShop\Utility\Version\TemplateAndJs\Contract\TemplateAndJSVersionInterface;

/**
 * Class Bootstrap. Used for services and repositories registration.
 *
 * @package Biller\PrestaShop
 */
class Bootstrap extends BootstrapComponent
{
    /**
     * Initializes infrastructure services and utilities.
     *
     * @return void
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
            function () {
                return new DefaultNotificationChannel();
            }
        );

        ServiceRegister::registerService(
            ShopNotificationChannelAdapter::CLASS_NAME,
            function () {
                return new ShopNotificationChannelCollection();
            }
        );

        ServiceRegister::registerService(
            NotificationHub::class,
            function () {
                return NotificationHub::getInstance();
            }
        );

        ServiceRegister::registerService(
            RefundAmountRequestServiceInterface::class,
            function () {
                return RefundAmountRequestService::getInstance();
            }
        );

        ServiceRegister::registerService(
            CancellationServiceInterface::class,
            function () {
                return new CancellationService();
            }
        );

        ServiceRegister::registerService(
            ShipmentServiceInterface::class,
            function () {
                return new ShipmentService();
            }
        );

        ServiceRegister::registerService(
            CompanyInfoService::class,
            function () {
                return new CompanyInfoService();
            }
        );

        ServiceRegister::registerService(
            OrderService::class,
            function () {
                return new OrderService();
            }
        );

        ServiceRegister::registerService(
            OrderReferenceRepositoryCore::class,
            function () {
                return new OrderReferenceRepositoryCore();
            }
        );

        ServiceRegister::registerService(
            TranslationUtility::class,
            function () {
                return new TranslationUtility();
            }
        );

        ServiceRegister::registerService(
            UtilityAuthorizationService::class,
            function () {
                return new UtilityAuthorizationService();
            }
        );

        ServiceRegister::registerService(
            EncryptorVersionInterface::class,
            function () {
                if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
                    return new EncryptorVersionVersion16();
                }

                return new EncryptorVersionVersion17();
            }
        );

        ServiceRegister::registerService(
            ConfigurationVersionInterface::class,
            function () {
                if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
                    return new ConfigurationVersion16();
                }

                return new ConfigurationVersion17();
            }
        );

        ServiceRegister::registerService(
            OrderRefundMapperVersion::class,
            function () {
                if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
                    return new OrderRefundMapperVersion16();
                }

                if (version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
                    return new OrderRefundMapperVersion17();
                }

                return new OrderRefundMapperVersion177();
            }
        );

        ServiceRegister::registerService(
            RedirectionVersionInterface::class,
            function () {
                if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
                    return new RedirectionVersion16();
                }

                if (version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
                    return new RedirectionVersion17();
                }

                return new RedirectionVersion177();
            }
        );

        ServiceRegister::registerService(
            HooksVersionInterface::class,
            function () {
                if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
                    return new HooksVersion16();
                }

                if (version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
                    return new HooksVersion17();
                }

                return new HooksVersion177();
            }
        );

        ServiceRegister::registerService(
            TemplateAndJSVersionInterface::class,
            function () {
                if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
                    return new TemplateAndJsVersion16();
                }

                if (version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
                    return new TemplateAndJsVersion17();
                }

                return new TemplateAndJsVersion177();
            }
        );
    }

    /**
     * Initializes repositories.
     *
     * @return void
     * @throws RepositoryClassException
     */
    protected static function initRepositories()
    {
        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(
            OrderReference::getClassName(),
            OrderReferenceRepository::getClassName()
        );
        RepositoryRegistry::registerRepository(Notification::getClassName(), NotificationHubRepository::getClassName());
        RepositoryRegistry::registerRepository(CompanyInfo::getClassName(), CompanyInfoRepository::getClassName());
    }
}
