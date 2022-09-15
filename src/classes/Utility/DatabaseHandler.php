<?php

namespace Biller\PrestaShop\Utility;

class DatabaseHandler
{
    /**
     * @param $name
     * @return bool Result of drop table query.
     */
    public function dropTable($name)
    {
        $script = 'DROP TABLE IF EXISTS ' . bqSQL(_DB_PREFIX_ . "${name}");

        return \Db::getInstance()->execute($script);
    }

    /**
     * @param $name
     * @return bool Result of create table query.
     */
    public function createTable($name)
    {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . bqSQL(_DB_PREFIX_ . "${name}")
            . '(
  	        `id`           INT(64) unsigned NOT NULL AUTO_INCREMENT,
	        `type`         VARCHAR(255),
	        `index_1`      VARCHAR(255),
	        `index_2`      VARCHAR(255),
	        `index_3`      VARCHAR(255),
	        `index_4`      VARCHAR(255),
	        `index_5`      VARCHAR(255),
	        `index_6`      VARCHAR(255),
	        `index_7`      VARCHAR(255),
	        `index_8`      VARCHAR(255),
	        `data`         MEDIUMTEXT,
	         PRIMARY KEY (`id`)
             ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        return \Db::getInstance()->execute($sql);
    }
}
