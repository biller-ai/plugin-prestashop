<?php

namespace Biller\PrestaShop\Utility\Services;

use Biller;
use Biller\BusinessLogic\Authorization\AuthorizationService as CoreAuthorizationService;
use Biller\BusinessLogic\Authorization\Contracts\AuthorizationService as AuthorizationServiceInterface;
use Biller\BusinessLogic\Integration\Authorization\UserInfoRepository;
use Biller\Infrastructure\ServiceRegister;
use Biller\PrestaShop\Utility\Config\Config;
use Context;
use Tools;

/**
 * AuthorizationService class.
 *
 * @package Biller\PrestaShop\BusinessService
 */
class AuthorizationService
{
    /**
     * @var Biller
     */
    private $module;

    /**
     * @param Biller $module
     */
    public function __construct(Biller $module)
    {
        $this->module = $module;
    }

    /**
     * @return array
     */
    public function authorize()
    {
        $errors = array();

        $username = Tools::getValue(Config::BILLER_USERNAME_KEY);
        $password = Tools::getValue(Config::BILLER_PASSWORD_KEY);
        $webshopuid = Tools::getValue(Config::BILLER_WEBSHOP_UID_KEY);
        $mode = Tools::getValue(Config::BILLER_MODE_KEY);

        if (!$username || !$password || !$webshopuid) {
            Context::getContext()->controller->errors[] =
                $errors[] = $this->module->l('Username, password and webshop uid are required!');

            return $errors;
        }

        if ($this->authorizationNeeded($username, $password, $webshopuid, $mode)) {
            try {
                /** @var CoreAuthorizationService $authorizationService */
                $authorizationService = ServiceRegister::getInstance()->getService(
                    AuthorizationServiceInterface::class
                );
                $authorizationService->authorize($username, $password, $webshopuid, $mode);
            } catch (Biller\BusinessLogic\Authorization\Exceptions\UnauthorizedException $e) {
                $errors[] =
                    $this->module->l('Invalid credentials: Unable to establish connection with Biller API.');
            }
        }

        return $errors;
    }

    /**
     * Checks if there are credentials saved in configuration.
     *
     * @return bool
     */
    public function loggedIn()
    {
        /** @var CoreAuthorizationService $authorizationService */
        $authorizationService = Biller\Infrastructure\ServiceRegister::getInstance()->getService(
            AuthorizationServiceInterface::class
        );
        try {
            $authorizationService->getUserInfo();

            return true;
        } catch (Biller\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException $e) {
            return false;
        }
    }

    /**
     * Checks if submitted credentials match the ones saved in configuration.
     *
     * @param string $username
     * @param string $password
     * @param string $webshopuid
     * @param string $mode
     *
     * @return bool True if no credentials are saved in database or if they don't match the ones submitted.
     */
    private function authorizationNeeded(
        $username,
        $password,
        $webshopuid,
        $mode
    )
    {
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
