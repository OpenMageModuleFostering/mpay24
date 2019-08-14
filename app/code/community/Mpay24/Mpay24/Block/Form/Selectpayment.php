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
 * @version             $Id: Selectpayment.php 13 2013-10-31 14:44:29Z sapolhei $
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

    $firstPS = Mage::getStoreConfig('mpay24/mpay24/ps_1');
    $allPS = true;

    for($i=2; $i<=Mage::getStoreConfig('mpay24/mpay24/payments_count'); $i++) {
      if($firstPS != Mage::getStoreConfig('mpay24/mpay24/ps_'.$i)) {
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
        $payment['ACTIVE'] = Mage::getStoreConfig('mpay24/mpay24/ps_'.$i);
        $payments[$id] = $payment;

        $i++;
      }
    }

    return $payments;
  }
}