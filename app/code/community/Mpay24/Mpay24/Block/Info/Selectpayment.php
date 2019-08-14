<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Selectpayment.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Block_Info_Selectpayment extends Mage_Payment_Block_Info {
  /**
   * Init default template for block
   */
  protected function _construct() {
    parent::_construct();
    $this->setTemplate('mpay24/info/selectpayment.phtml');
  }

  public function getInfo() {
    $info = $this->getData('info');

    if (!($info instanceof Mage_Payment_Model_Info))
      Mage::throwException($this->__('Can not retrieve payment info model object.'));

    return $info;
  }

  public function getMethod() {
    $payment = $this->getInfo()->getMethodInstance();

    return $this->getInfo()->getMethodInstance();
  }

  public function getPMethId() {
    $pTypeBrand = explode(" => ", $this->getInfo()->getCcType());
    $payments = $this->getActiveMethods();
    $p_meth_id = 0;

    foreach($payments as $id => $payment)
      if($payment['P_TYPE'] == $pTypeBrand[0] && $payment['BRAND'] == $pTypeBrand[1])
        $p_meth_id = $id;

    if(strlen($p_meth_id) == 1)
      $p_meth_id = "00$p_meth_id";
    elseif(strlen($p_meth_id) == 2)
      $p_meth_id = "0$p_meth_id";

    return $p_meth_id;
  }

  public function getPType() {
    $pTypeBrand = explode(" => ", $this->getInfo()->getCcType());
    return $pTypeBrand[0];
  }

  public function getBrand() {
    $pTypeBrand = explode(" => ", $this->getInfo()->getCcType());

    if(isset($pTypeBrand[1]))
      return $pTypeBrand[1];
    else
      return "";
  }

  public function toPdf() {
    $this->setTemplate('mpay24/info/pdf/selectpayment.phtml');

    return $this->toHtml();
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