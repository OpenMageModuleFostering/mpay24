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
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: mysql4-install-1.6.3.php 6404 2015-07-08 09:25:24Z anna $
 */
if(class_exists('Mage_Sales_Model_Resource_Setup'))
  $install = new Mage_Sales_Model_Resource_Setup('sales_setup');
else
  $install = $this;
  
$install->startSetup();

$install->run("DELETE FROM {$this->getTable('core_config_data')} WHERE `path` LIKE '%mpay%'");

$install->run("DROP TABLE if exists {$this->getTable('mpay24_debug')};");

$install->run("UPDATE {$this->getTable('sales_flat_quote_payment')}  SET `method` = 'mpay24' WHERE `method` LIKE 'mpay24_%';");

$install->run("UPDATE {$this->getTable('sales_flat_order_payment')}  SET `method` = 'mpay24' WHERE `method` LIKE 'mpay24_%';");

$install->addAttribute('quote_address', 'payment_charge_type', array('type' => 'varchar'));
$install->addAttribute('quote_address', 'payment_charge', array('type' => 'decimal'));
$install->addAttribute('quote_address', 'base_payment_charge', array('type' => 'decimal'));

$install->addAttribute('order', 'payment_charge_type', array('type' => 'varchar'));
$install->addAttribute('order', 'payment_charge', array('type' => 'decimal'));
$install->addAttribute('order', 'base_payment_charge', array('type' => 'decimal'));

$install->addAttribute('invoice', 'payment_charge_type', array('type' => 'varchar'));
$install->addAttribute('invoice', 'payment_charge', array('type' => 'decimal'));
$install->addAttribute('invoice', 'base_payment_charge', array('type' => 'decimal'));

$install->addAttribute('creditmemo', 'payment_charge_type', array('type' => 'varchar'));
$install->addAttribute('creditmemo', 'payment_charge', array('type' => 'decimal'));
$install->addAttribute('creditmemo', 'base_payment_charge', array('type' => 'decimal'));

$install->endSetup();
?>