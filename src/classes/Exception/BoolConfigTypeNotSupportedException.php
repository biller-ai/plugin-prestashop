<?php

namespace Biller\PrestaShop\Exception;

use Exception;

/**
 * Class BoolConfigTypeNotSupportedException. Storing boolean values is not supported since Configuration::get doesn't
 * take a default value and returns false if value under the given key is not found.
 *
 * @see \Configuration::get()
 *
 * @package Biller\PrestaShop\Utility\Exception
 */
class BoolConfigTypeNotSupportedException extends Exception
{
}
