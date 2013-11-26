<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */
$installer = $this;
$installer->getConnection()->addColumn($installer->getTable('sales/quote_address'), 'kp_id', "varchar(255) null default ''");
$installer->getConnection()->addColumn($installer->getTable('sales/order_address'), 'kp_id', "varchar(255) null default ''");

$installer->run("
      CREATE TABLE IF NOT EXISTS {$this->getTable('kiala_customeraddress')} (
      `customeraddress_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `quote` integer(11) NOT NULL,
      `address` text NOT NULL,
      PRIMARY KEY (`customeraddress_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
    ");
$installer->run("DELETE FROM {$this->getTable('kiala_language')} WHERE `country` = 'ES' AND `language` = 'ca';");

// TODO: Check how new (default) values should be pushed to customer

$installer->endSetup();