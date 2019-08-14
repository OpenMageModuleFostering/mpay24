<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Observer.php 6252 2015-03-26 15:57:57Z anna $
 */
include_once Mage::getBaseDir('code')."/community/Mpay24/Mpay24/Model/Api/MPay24MagentoShop.php";

class Mpay24_Mpay24_Model_Observer extends Mage_Core_Model_Config_Data {

  /**
   * Retrieve active system payments
   */
  public function afterSave() {
    if(!Mage::getStoreConfig('mpay24/mpay24as/active'))
      return false;
    
//     Mage::getConfig()->saveConfig("payment/mpay24_tokenizer/active", 0);
//     Mage::getConfig()->saveConfig("payment/mpay24_eps/active", 0);
//     Mage::getConfig()->saveConfig("payment/mpay24_paypal/active", 0);
    Mage::getConfig()->saveConfig("payment/mpay24/active", 0);
    
    Mage::getConfig()->saveConfig("mpay24/mpay24/payments_count", 0);
    Mage::getConfig()->saveConfig("mpay24/mpay24/payments_error", "");
    Mage::getConfig()->saveConfig("mpay24/mpay24/active_payments", "");

    if(Mage::getStoreConfig('mpay24/mpay24as/old_merchantid') == "")
      Mage::getConfig()->saveConfig("mpay24/mpay24as/old_merchantid", Mage::getStoreConfig('mpay24/mpay24as/merchantid'));

    if(Mage::getStoreConfig('mpay24/mpay24as/old_merchantid') != Mage::getStoreConfig('mpay24/mpay24as/merchantid')) {

      for($i=1; $i<=50; $i++) {
        Mage::getConfig()->saveConfig("payment/mpay24_ps_$i/active", 1);
      }
    }

    Mage::getConfig()->reinit();
    Mage::app()->reinitStores();

    $mPay24MagentoShop = MPay24MagentoShop::getMPay24Api();
    $result = $mPay24MagentoShop->getPaymentMethods();
    $payments = array();
    
    if($result->getGeneralResponse()->getStatus() == 'OK') {
      Mage::getConfig()->saveConfig("mpay24/mpay24/payments_count", $result->getAll());
      
      $i=0;
      
      foreach($result->getBrands() as $brand) {
        $payments[$result->getPMethID($i)]['P_TYPE'] = $result->getPType($i);
        $payments[$result->getPMethID($i)]['BRAND'] = $result->getBrand($i);
        $payments[$result->getPMethID($i)]['DESCR'] = $result->getDescription($i);
      
        $i++;
      
        if(Mage::getStoreConfig('mpay24/mpay24as/old_merchantid') != Mage::getStoreConfig('mpay24/mpay24as/merchantid')) {
          Mage::getConfig()->saveConfig("payment/mpay24_ps_$i/active", 1);
        }
      }
      
      if(!Mage::getStoreConfig('mpay24/mpay24/seamless')) {
        Mage::getConfig()->saveConfig("payment/mpay24/active", 1);
      } else {
        Mage::getConfig()->saveConfig("mpay24/mpay24/seamless", 1);
//         Mage::getConfig()->saveConfig("payment/mpay24_tokenizer/active", 1);
//         Mage::getConfig()->saveConfig("payment/mpay24_eps/active", 1);
//         Mage::getConfig()->saveConfig("payment/mpay24_paypal/active", 1);
        Mage::getConfig()->saveConfig("payment/mpay24/active", 0);
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