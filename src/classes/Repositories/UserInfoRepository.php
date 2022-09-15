<?php

namespace Biller\PrestaShop\Repositories;

use Biller\BusinessLogic\Authorization\DTO\UserInfo;
use Biller\BusinessLogic\Integration\Authorization\UserInfoRepository as UserInfoRepositoryInterface;
use Biller\PrestaShop\Utility\Config\Config;
use Biller\PrestaShop\Utility\Encryption\Encryptor;
use PrestaShop\PrestaShop\Adapter\Entity\Module;
use Biller\Domain\Exceptions\InvalidArgumentException;
use Context;

/**
 * Class UserInfoRepository
 * @package Biller\PrestaShop\Repositories
 */
class UserInfoRepository implements UserInfoRepositoryInterface
{
    /** @var string */
    const DEFAULT_MODE = 'sandbox';

    /**
     * @inheritDoc
     */
    public function saveUserInfo(UserInfo $userInfo)
    {
        $encryptor = new Encryptor();
        $userInfo->setPassword($encryptor->encrypt($userInfo->getPassword()));
        $storageItem = $userInfo->toArray();

        if ($userInfo->getMode() === 'live') {
            if (!ConfigurationRepository::updateValue(Config::BILLER_USER_INFO_LIVE_KEY, json_encode($storageItem))) {
                Context::getContext()->controller->errors[] =
                    Module::getInstanceByName('biller')->l('Error while updating user info!');
            }
        } else {
            if (!ConfigurationRepository::updateValue(Config::BILLER_USER_INFO_SANDBOX_KEY, json_encode($storageItem))) {
                Context::getContext()->controller->errors[] =
                    Module::getInstanceByName('biller')->l('Error while updating user info!');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getActiveUserInfo()
    {
        $encryptor = new Encryptor();
        $mode = ConfigurationRepository::getValue(Config::BILLER_MODE_KEY);
        if (!$mode) {
            $this->setDefaultMode();
            $mode = self::DEFAULT_MODE;
        }
        $mode === 'sandbox' ?
            $userInfoJson = ConfigurationRepository::getValue(Config::BILLER_USER_INFO_SANDBOX_KEY) :
            $userInfoJson = ConfigurationRepository::getValue(Config::BILLER_USER_INFO_LIVE_KEY);

        try {
            $userInfo = $userInfoJson ? UserInfo::fromArray(json_decode($userInfoJson, true)) : null;
            if ($userInfo) {
                $userInfo->setPassword($encryptor->decrypt($userInfo->getPassword()));
            }
            return $userInfo;
        } catch (InvalidArgumentException $exception) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function saveMode($mode)
    {
        if (!ConfigurationRepository::updateValue(Config::BILLER_MODE_KEY, $mode)) {
            Context::getContext()->controller->errors[] =
                Module::getInstanceByName('biller')->l('Error while updating mode!');
        }
    }

    /**
     * Sets default Biller mode.
     *
     * @return void
     */
    private function setDefaultMode()
    {
        ConfigurationRepository::updateValue(Config::BILLER_MODE_KEY, self::DEFAULT_MODE);
    }
}
