<?php

namespace Biller\PrestaShop\Tests\Repositories;

use Biller\Tests\Infrastructure\ORM\AbstractGenericStudentRepositoryTest;

/**
 * PrestashopGenericBaseRepositoryTest class.
 *
 * @package Biller\PrestaShop\Tests\Repositories
 */
class PrestashopGenericBaseRepositoryTest extends AbstractGenericStudentRepositoryTest
{
    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->createTestTable();
    }

    /**
     * @return string
     */
    public function getStudentEntityRepositoryClass()
    {
        return TestRepository::class;
    }

    /**
     * Cleans up all storage services used by repositories.
     */
    public function cleanUpStorage()
    {
        \Db::getInstance()->execute('DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'biller_test');
    }

    /**
     * Creates a table for testing purposes.
     */
    private function createTestTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'biller_test
            (
             `id` INT(64) NOT NULL AUTO_INCREMENT,
             `type` VARCHAR(255) NOT NULL,
             `index_1` VARCHAR(255),
             `index_2` VARCHAR(255),
             `index_3` VARCHAR(255),
             `index_4` VARCHAR(255),
             `index_5` VARCHAR(255),
             `index_6` VARCHAR(255),
             `index_7` VARCHAR(255),
             `index_8` VARCHAR(255),
             `data` MEDIUMTEXT,
              PRIMARY KEY(`id`)
            )
            ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        \Db::getInstance()->execute($sql);
    }
}