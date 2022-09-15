<?php

namespace Biller\PrestaShop\Utility\Services;

use Biller;
use Biller\BusinessLogic\Authorization\AuthorizationService as CoreAuthorizationService;
use Biller\BusinessLogic\Authorization\Contracts\AuthorizationService as AuthorizationServiceInterface;
use Biller\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException;
use Biller\BusinessLogic\Authorization\Exceptions\UnauthorizedException;
use Biller\BusinessLogic\Integration\Authorization\UserInfoRepository;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Utility\Config\Config;
use Context;
use Module;
use Tools;

/**
 * Class AuthorizationService
 *
 * @package Biller\PrestaShop\Utility\Services
 */
class AuthorizationService
{
    /** @var string File name for translation contextualization */
    const FILE_NAME = 'AuthorizationService';

    /** @var Biller */
    private $module;

    public function __construct()
    {
        $this->module = Module::getInstanceByName('biller');
    }

    /**
     * @return array
     */
    public function authorize()
    {
        $errors = array();

        $username = Tools::getValue(Config::USERNAME_KEY);
        $password = Tools::getValue(Config::PASSWORD_KEY);
        $webshopuid = Tools::getValue(Config::WEBSHOP_UID_KEY);
        $mode = Tools::getValue(Config::MODE_KEY);

        if (!$username || !$password || !$webshopuid) {
            Context::getContext()->controller->errors[] =
            $errors[] = $this->module->l('Username, password and webshop uid are required!', self::FILE_NAME);

            return $errors;
        }

        if ($this->authorizationNeeded($username, $password, $webshopuid, $mode)) {
            try {
                /** @var CoreAuthorizationService $authorizationService */
                $authorizationService = ServiceRegister::getInstance()->getService(
                    AuthorizationServiceInterface::class
                );
                $authorizationService->authorize($username, $password, $webshopuid, $mode);
            } catch (UnauthorizedException $e) {
                $errors[] =
                    $this->module->l(
                        'Invalid credentials: Unable to establish connection with Biller API.',
                        self::FILE_NAME
                    );
            }
        }

        return $errors;
    }

    /**
     * Checks if there are credentials saved in configuration.
     *
     * @return bool Logged in status
     */
    public function loggedIn()
    {
        /** @var CoreAuthorizationService $authorizationService */
        $authorizationService = ServiceRegister::getInstance()->getService(
            AuthorizationServiceInterface::class
        );

        try {
            $authorizationService->getUserInfo();

            return true;
        } catch (FailedToRetrieveAuthInfoException $e) {
            return false;
        }
    }

    /**
     * Checks if submitted credentials match the ones saved in configuration.
     *
     * @param string $username Username
     * @param string $password Password
     * @param string $webshopuid Current web-shop's UUID
     * @param string $mode Biller mode
     *
     * @return bool True if no credentials are saved in database or if they don't match the ones submitted.
     */
    private function authorizationNeeded(
        $username,
        $password,
        $webshopuid,
        $mode
    ) {
        /** @var UserInfoRepository $userInfoRepository */
        $userInfoRepository = ServiceRegister::getService(UserInfoRepository::class);
        $userInfo = $userInfoRepository->getActiveUserInfo();

        return !$userInfo ||
            !(
                $username === $userInfo->getUsername() &&
                $password === $userInfo->getPassword() &&
                $webshopuid === $userInfo->getWebShopUID() &&
                $mode === $userInfo->getMode()
            );
    }
}
