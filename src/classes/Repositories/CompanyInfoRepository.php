<?php

namespace Biller\PrestaShop\Repositories;

/**
 * Class CompanyInfoRepository. Used for CRUD operations over the company info table.
 *
 * @package Biller\PrestaShop\Repositories
 */
class CompanyInfoRepository extends BaseRepository
{
    /**
     * Name of the base entity table in database.
     */
    const TABLE_NAME = 'biller_company_info';
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;

    /**
     * Returns full class name.
     *
     * @return string Full class name
     */
    public static function getClassName()
    {
        return static::THIS_CLASS_NAME;
    }
}
