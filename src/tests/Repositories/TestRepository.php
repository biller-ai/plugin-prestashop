<?php

namespace Biller\PrestaShop\Tests\Repositories;

use Biller\PrestaShop\Repositories\BaseRepository;

/**
 * TestRepository class.
 *
 * @package Biller\PrestaShop\Tests\Repositories
 */
class TestRepository extends BaseRepository
{
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;
    /**
     * Name of the base entity table in database.
     */
    const TABLE_NAME = 'biller_test';
}