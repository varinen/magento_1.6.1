<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */

$installer = $this;
$installer->startSetup();
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'kiala_exported', "boolean default '0'");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dspid', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'language', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'dspid', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'language', "varchar(255) null default ''");
$installer->run("
      CREATE TABLE IF NOT EXISTS {$this->getTable('kiala_language')} (
      `language_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `country` varchar(255) NOT NULL DEFAULT '',
      `language` varchar(255) NOT NULL,
      `description`varchar(255) NOT NULL,
      PRIMARY KEY (`language_id`),
      UNIQUE KEY `locale` (`country`,`language`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
    ");
$installer->run("INSERT INTO {$this->getTable('kiala_language')} (`language_id`, `country`, `language`, `description`) VALUES
        (NULL, 'BE', 'fr', 'Français'),
        (NULL, 'BE', 'nl', 'Nederlands'),
        (NULL, 'DE', 'de', 'Deutsch'),
        (NULL, 'ES', 'ca', 'Cataluña'),
        (NULL, 'ES', 'es', 'Español'),
        (NULL, 'FR', 'fr', 'Français'),
        (NULL, 'LU', 'fr', 'Français'),
        (NULL, 'LU', 'nl', 'Nederlands'),
        (NULL, 'NL', 'nl', 'Nederlands'),
        (NULL, 'UK', 'en', 'English');");
$installer->endSetup();
