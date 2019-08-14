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
 * @version             $Id: Observer.php 5 2013-10-10 13:08:44Z sapolhei $
 */
include_once "app/code/community/Mpay24/Mpay24/Model/Api/MPay24MagentoShop.php";

class Mpay24_Mpay24_Model_Observer extends Mage_Core_Model_Config_Data {

  /**
   * Retrieve active system payments
   */
  public function afterSave() {
    Mage::getConfig()->saveConfig("mpay24/mpay24/payments_count", 0);
    Mage::getConfig()->saveConfig("mpay24/mpay24/payments_error", "");
    Mage::getConfig()->saveConfig("mpay24/mpay24/active_payments", "");

    if(Mage::getStoreConfig('mpay24/mpay24as/old_merchantid') == "")
      Mage::getConfig()->saveConfig("mpay24/mpay24as/old_merchantid", Mage::getStoreConfig('mpay24/mpay24as/merchantid'));

    if(Mage::getStoreConfig('mpay24/mpay24as/old_merchantid') != Mage::getStoreConfig('mpay24/mpay24as/merchantid')) {

      for($i=1; $i<=50; $i++)
        Mage::getConfig()->saveConfig("mpay24/mpay24/ps_".$i, 1);
    }

    Mage::getConfig()->reinit();
    Mage::app()->reinitStores();

    $mPay24MagentoShop = MPay24MagentoShop::getMPay24Api();
    $result = $mPay24MagentoShop->getPaymentMethods();

    if($result->getGeneralResponse()->getStatus() == 'OK') {
      Mage::getConfig()->saveConfig("mpay24/mpay24/payments_count", $result->getAll());

      $payments = array();
      $i=0;

      foreach($result->getBrands() as $brand) {
        $payments[$result->getPMethID($i)]['P_TYPE'] = $result->getPType($i);
        $payments[$result->getPMethID($i)]['BRAND'] = $result->getBrand($i);
        $payments[$result->getPMethID($i)]['DESCR'] = $result->getDescription($i);

        $i++;

        if(Mage::getStoreConfig('mpay24/mpay24as/old_merchantid') != Mage::getStoreConfig('mpay24/mpay24as/merchantid'))
          Mage::getConfig()->saveConfig("mpay24/mpay24/ps_".$i, 1);
      }

      Mage::getConfig()->saveConfig("mpay24/mpay24/active_payments", serialize($payments));
      Mage::getConfig()->reinit();
      Mage::app()->reinitStores();
    } else {
      Mage::getConfig()->saveConfig("mpay24/mpay24/payments_error", $result->getGeneralResponse()->getReturnCode());
      Mage::getConfig()->reinit();
      Mage::app()->reinitStores();
    }

    if(Mage::getStoreConfig('mpay24/mpay24as/old_merchantid') != Mage::getStoreConfig('mpay24/mpay24as/merchantid'))
      Mage::getConfig()->saveConfig("mpay24/mpay24as/old_merchantid", Mage::getStoreConfig('mpay24/mpay24as/merchantid'));
  }
}