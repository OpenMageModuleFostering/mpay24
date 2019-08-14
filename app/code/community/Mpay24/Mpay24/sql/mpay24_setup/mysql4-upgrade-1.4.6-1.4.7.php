<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Firedrago Magento
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: mysql4-upgrade-1.4.6-1.4.7.php 5 2013-10-10 13:08:44Z sapolhei $
 */
$this->startSetup();

$this->run("DELETE FROM {$this->getTable('core_config_data')} WHERE `path` LIKE '%mpay%'");

$this->run("DROP TABLE if exists {$this->getTable('mpay24_debug')};");

$this->run("UPDATE {$this->getTable('sales_flat_quote_payment')}  SET `method` = 'mpay24' WHERE `method` LIKE 'mpay24_%';");

$this->run("UPDATE {$this->getTable('sales_flat_order_payment')}  SET `method` = 'mpay24' WHERE `method` LIKE 'mpay24_%';");

$this->addAttribute('quote_address', 'payment_charge_type', array('type' => 'varchar'));
$this->addAttribute('quote_address', 'payment_charge', array('type' => 'decimal'));
$this->addAttribute('quote_address', 'base_payment_charge', array('type' => 'decimal'));

$this->addAttribute('order', 'payment_charge_type', array('type' => 'varchar'));
$this->addAttribute('order', 'payment_charge', array('type' => 'decimal'));
$this->addAttribute('order', 'base_payment_charge', array('type' => 'decimal'));

$this->addAttribute('invoice', 'payment_charge_type', array('type' => 'varchar'));
$this->addAttribute('invoice', 'payment_charge', array('type' => 'decimal'));
$this->addAttribute('invoice', 'base_payment_charge', array('type' => 'decimal'));

$this->addAttribute('creditmemo', 'payment_charge_type', array('type' => 'varchar'));
$this->addAttribute('creditmemo', 'payment_charge', array('type' => 'decimal'));
$this->addAttribute('creditmemo', 'base_payment_charge', array('type' => 'decimal'));

$this->endSetup();
?>