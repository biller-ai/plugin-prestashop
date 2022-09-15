<?php

namespace Biller\PrestaShop\Repositories;

/**
 * Class NotificationHubRepository. Used for CRUD operations over the notifications hub table.
 *
 * @package Biller\PrestaShop\Repositories
 */
class NotificationHubRepository extends BaseRepository
{
    /**
     * Name of the base entity table in database.
     */
    const TABLE_NAME = 'biller_notifications';
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
