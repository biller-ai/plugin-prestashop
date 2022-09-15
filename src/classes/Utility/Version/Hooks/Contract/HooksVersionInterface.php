<?php

namespace Biller\PrestaShop\Utility\Version\Hooks\Contract;

/**
 * Contains methods for hooks that vary by PrestaShop versions.
 */
interface HooksVersionInterface
{
    /**
     * Get all hooks needed for installation.
     *
     * @return array
     */
    public function getHooks();
}

