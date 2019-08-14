<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Selectpayment.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Block_Form_Selectpayment extends Mage_Payment_Block_Form {
  protected function _construct() {
    parent::_construct();
    if(Mage::getStoreConfig('mpay24/mpay24/form_template'))
      $this->setTemplate('mpay24/form/'.Mage::getStoreConfig('mpay24/mpay24/form_template'));
  }

  /**
   * Retrieve payment configuration object
   *
   * @return            Mage_Payment_Model_Config
   */
  protected function _getConfig() {
    return Mage::getSingleton('mpay24/config');
  }

  /**
   * Get the payment systems for the merchant from the core_config_data and explode it into an array
   * @return            array               $method
   */
  public function getActiveMethods() {
    $methods = array();
    $payments = Mage::getStoreConfig('mpay24/mpay24/active_payments');
    $paymentsArray = unserialize($payments);

    $firstPS = Mage::getStoreConfig('payment/mpay24_ps_1/active');
    $allPS = true;

    for($i=2; $i<=Mage::getStoreConfig('mpay24/mpay24/payments_count'); $i++) {
      if($firstPS != Mage::getStoreConfig("payment/mpay24_ps_$i/active")) {
        $allPS = false;
        break;
      }
    }

    if($allPS) {
      if($firstPS == 0) {
        Mage::getConfig()->saveConfig("payment/mpay24/active", 0);
        Mage::getConfig()->reinit();
        Mage::app()->reinitStores();
      }

      foreach($paymentsArray as $key => $value) {
        $value['ACTIVE'] = 1;
        $paymentsArray[$key] = $value;
      }
      
      return $paymentsArray;
    } else {
      $i=1;
      $payments = array();
      foreach($paymentsArray as $id => $payment) {
        $payment['ACTIVE'] = Mage::getStoreConfig("payment/mpay24_ps_$i/active");
        $payments[$id] = $payment;

        $i++;
      }
    }

    return $payments;
  }
}