<?php

namespace Biller\PrestaShop\Utility\Config;

use Biller\Infrastructure\Configuration\Configuration as ConfigurationInterface;
use Biller\PrestaShop\InfrastructureService\ConfigurationService;
use Biller\Infrastructure\ServiceRegister;
use Module;
use Biller;

/**
 * Class BillerPaymentConfiguration. Implementation of BillerPaymentConfiguration interface.
 *
 * @package Biller\PrestaShop\Utility\Config
 */
class BillerPaymentConfiguration implements Contract\BillerPaymentConfiguration
{
    /** @var string File name for translation contextualization */
    const FILE_NAME = 'BillerPaymentConfiguration';

    /** @var ConfigurationService */
    private $configurationService;

    /** @var Biller */
    private $module;

    public function __construct()
    {
        $this->module = Module::getInstanceByName('biller');
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->getConfigurationService()->getConfigValue(
            Config::NAME_KEY,
            $this->module->l('Biller business invoice', self::FILE_NAME)
        );
    }

    /**
     * @inheritDoc
     */
    public function setName($name)
    {
        $this->getConfigurationService()->saveConfigValue(Config::NAME_KEY, $name);
    }

    /**
     * @inheritDoc
     */
    public function getDescription()
    {
        return $this->getConfigurationService()->getConfigValue(
            Config::DESCRIPTION_KEY,
            $this->module->l('The payment solution that advances both sides. We pay out every invoice on time.', self::FILE_NAME)
        );
    }

    /**
     * @inheritDoc
     */
    public function setDescription($description)
    {
        $this->getConfigurationService()->saveConfigValue(Config::DESCRIPTION_KEY, $description);
    }

    /**
     * Gets configuration service instance.
     *
     * @return ConfigurationService
     */
    private function getConfigurationService()
    {
        if (!$this->configurationService) {
            $this->configurationService = ServiceRegister::getService(ConfigurationInterface::CLASS_NAME);
        }

        return $this->configurationService;
    }
}
