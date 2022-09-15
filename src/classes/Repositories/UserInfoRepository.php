<?php

namespace Biller\PrestaShop\Repositories;

use Biller\BusinessLogic\Authorization\DTO\UserInfo;
use Biller\BusinessLogic\Integration\Authorization\UserInfoRepository as UserInfoRepositoryInterface;
use Biller\Infrastructure\Configuration\Configuration as ConfigurationInterface;
use Biller\Infrastructure\Logger\Logger;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Bootstrap;
use Biller\PrestaShop\InfrastructureService\ConfigurationService;
use Biller\PrestaShop\Utility\Config\Config;
use Biller\PrestaShop\Utility\Version\Encryptor\Contract\EncryptorVersionInterface;
use Module;
use Biller;
use Context;

/**
 * Class UserInfoRepository. Used for getting and setting user info data from database.
 *
 * @package Biller\PrestaShop\Repositories
 */
class UserInfoRepository implements UserInfoRepositoryInterface
{
    /** @var string File name for translation contextualization */
    const FILE_NAME = 'UserInfoRepository';

    /** @var string */
    const DEFAULT_MODE = 'sandbox';

    /** @var Biller */
    private $module;

    public function __construct()
    {
        $this->module = Module::getInstanceByName('biller');
    }

    /**
     * @inheritDoc
     */
    public function saveUserInfo(UserInfo $userInfo)
    {
        $userInfo->setPassword($this->getEncryptor()->encrypt($userInfo->getPassword()));
        $storageItem = $userInfo->toArray();

        /** @var ConfigurationService $configurationService */
        $configurationService = ServiceRegister::getService(ConfigurationInterface::CLASS_NAME);

        try {
            if ($userInfo->getMode() === 'live') {
                $configurationService->saveConfigValue(Config::USER_INFO_LIVE_KEY, json_encode($storageItem));
            } else {
                $configurationService->saveConfigValue(Config::USER_INFO_SANDBOX_KEY, json_encode($storageItem));
            }
        } catch (\Exception $exception) {
            Logger::logError($exception->getMessage());
            Context::getContext()->controller->errors[] =
                $this->module->l('Error while updating user info!', self::FILE_NAME);
        }
    }

    /**
     * @inheritDoc
     */
    public function getActiveUserInfo()
    {
        /** @var ConfigurationService $configurationService */
        $configurationService = ServiceRegister::getService(ConfigurationInterface::CLASS_NAME);

        $mode = $configurationService->getConfigValue(Config::MODE_KEY);
        if (!$mode) {
            $mode = self::DEFAULT_MODE;
            $configurationService->saveConfigValue(Config::MODE_KEY, $mode);
        }
        $mode === 'sandbox' ?
            $userInfoJson = $configurationService->getConfigValue(Config::USER_INFO_SANDBOX_KEY) :
            $userInfoJson = $configurationService->getConfigValue(Config::USER_INFO_LIVE_KEY);

        try {
            $userInfo = $userInfoJson ? UserInfo::fromArray(json_decode($userInfoJson, true)) : null;
            if ($userInfo) {
                $userInfo->setPassword($this->getEncryptor()->decrypt($userInfo->getPassword()));
            }

            return $userInfo;
        } catch (\Exception $exception) {
            Logger::logError($exception->getMessage());
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function saveMode($mode)
    {
        /** @var ConfigurationService $configurationService */
        $configurationService = ServiceRegister::getService(ConfigurationInterface::CLASS_NAME);

        try {
            $configurationService->saveConfigValue(Config::MODE_KEY, $mode);
        } catch (\Exception $exception) {
            Logger::logError($exception->getMessage());
            Context::getContext()->controller->errors[] =
                $this->module->l('Error while updating mode!', self::FILE_NAME);
        }
    }

    /**
     * Returns Encryptor depending on used PrestaShop version.
     * For version from 1.6.0.14 to 1.7.0.0 EncryptorVersion16 is returned.
     * For version 1.7.0.0+ EncryptorVersionVersion17 is returned.
     *
     * @return EncryptorVersionInterface
     */
    private function getEncryptor()
    {
        Bootstrap::init();

        return ServiceRegister::getService(EncryptorVersionInterface::class);
    }
}
