<?php

namespace Biller\PrestaShop\Utility;

/**
 * TranslationUtility class.
 *
 * @package Biller\PrestaShop\Utility
 */
class TranslationUtility
{
    /**
     * Biller module instance.
     *
     * @var \Module
     */
    private static $moduleInstance;

    /**
     * Method that wraps PrestaShop translation function.
     *
     * @param string $string String that needs to be translated.
     * @param array $args Translation arguments (one or more values to be injected into translated string).
     *
     * @return string Translated string.
     */
    public static function __($string, array $args = array())
    {
        $result = self::getModuleInstance()->l($string);

        if (!empty($args)) {
            $result = vsprintf($result, $args);
        }

        return $result;
    }

    /**
     * Creates a field with the given value for all languages.
     *
     * @param string $value
     *
     * @return array
     */
    public static function createMultiLanguageField($value)
    {
        $result = [];

        $languageIds = \Language::getIDs(false);

        foreach ($languageIds as $languageId) {
            $result[$languageId] = $value;
        }

        return $result;
    }

    /**
     * Returns module instance.
     *
     * @return \Module
     */
    private static function getModuleInstance()
    {
        if (self::$moduleInstance === null) {
            self::$moduleInstance = \Module::getInstanceByName('biller');
        }

        return self::$moduleInstance;
    }
}
