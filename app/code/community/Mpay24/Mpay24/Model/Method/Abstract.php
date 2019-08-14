<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Abstract.php 6252 2015-03-26 15:57:57Z anna $
 */

abstract class Mpay24_Mpay24_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract {


  /**
   * Capture payment
   *
   * @param             Varien_Object       $orderPayment                 The payment object, used for the capturing
   * @param             int                 $amount                       The amount, multiplied 100 (€ 1.00 => 100)
   * @return            bool|Mpay24_Mpay24_Model_PaymentMethod
   */
  public function capture(Varien_Object $payment, $amount) {
    if($payment->getAdditionalInformation('confirmation') !== true) {
      $this->clearSession();
      $mPay24MagentoShop = MPay24MagentoShop::getMPay24Api();
      $mPAY24Result = $mPay24MagentoShop->clearAmount($payment->getOrder()->getIncrementId(),$amount*100);
  
      if($mPAY24Result->getGeneralResponse()->getStatus() != 'OK') {
        Mage::log('The order was not captured: ' . $mPAY24Result->getGeneralResponse()->getReturnCode(), 10);
        Mage::throwException(Mage::helper('mpay24')->__("The order could not be captured! For mor information see the log files!"));
        return false;
      }
    }
  
    parent::capture($payment, $amount);
  
    if($payment->getOrder()->getIsNotVirtual())
      $s = Mage_Sales_Model_Order::STATE_PROCESSING;
    else
      $s = Mage_Sales_Model_Order::STATE_COMPLETE;
  
    if($payment->getOrder()->getState() != $s)
      $payment->getOrder()->addStatusToHistory($s, Mage::helper('mpay24')->__("Payment ") . Mage::helper('mpay24')->__("BILLED") .' [ ' . $payment->getOrder()->getBaseCurrencyCode() . " " .$payment->getOrder()->formatPriceTxt($amount).' ]', true)->save();
  
    return $this;
  }
  
  /**
   * Refund money
   *
   * @param             Varien_Object       $orderPayment                 The payment object, used for the refunding
   * @param             int                 $amount                       The amount, multiplied 100 (€ 1.00 => 100)
   * @return            bool|Mpay24_Mpay24_Model_PaymentMethod
   */
  public function refund(Varien_Object $payment, $amount) {
    $this->clearSession();
  
    if(!$payment->getAdditionalInformation('MIFCredit') && !$payment->getAdditionalInformation('error')) {
      $mPay24MagentoShop = MPay24MagentoShop::getMPay24Api();
      $mPAY24Result = $mPay24MagentoShop->creditAmount($payment->getOrder()->getIncrementId(),$amount*100);
  
      if($mPAY24Result->getGeneralResponse()->getStatus() != 'OK') {
        Mage::log('The order was not refunded: ' . $mPAY24Result->getGeneralResponse()->getReturnCode(), 10);
        Mage::throwException(Mage::helper('mpay24')->__("The order could not be refunded! For mor information see the log files!"));
        return false;
      }
    }
  
    parent::refund($payment, $amount);
  
    return $this;
  }
  
  /**
   * Void a payment
   *
   * @param             Varien_Object       $orderPayment                 The payment object, used for the voiding
   * @return            bool|Mpay24_Mpay24_Model_PaymentMethod
   */
  public function void(Varien_Object $payment) {
    $this->clearSession();
  
    if(!$payment->getAdditionalInformation('MIFReverse') && !$payment->getAdditionalInformation('error')) {
      $mPay24MagentoShop = MPay24MagentoShop::getMPay24Api();
      $status = $mPay24MagentoShop->updateStatusToCancel($payment->getOrder()->getIncrementId());
  
      if($status != "SUSPENDED" && $status != "PENDING")
        $mPAY24Result = $mPay24MagentoShop->cancelTransaction($payment->getOrder()->getIncrementId());
  
      if($status != "SUSPENDED" && $status != "PENDING" && $mPAY24Result->getGeneralResponse()->getStatus() != 'OK') {
        Mage::log('The order was not canceled: ' . $mPAY24Result->getGeneralResponse()->getReturnCode(), 10);
        Mage::throwException(Mage::helper('mpay24')->__("The order could not be canceled! For mor information see the log files!"));
        return false;
      }
    }
  
    parent::void($payment);
  
    return $this;
  }
  
  /**
   * Cancel a payment
   *
   * @param             Varien_Object       $orderPayment                 The payment object, used for the cancelation
   * @return            bool|Mpay24_Mpay24_Model_PaymentMethod
   */
  public function cancel(Varien_Object $payment) {
    $this->clearSession();
  
    if(!$payment->getAdditionalInformation('cancelButton') && !$payment->getAdditionalInformation('error')) {
      $mPay24MagentoShop = MPay24MagentoShop::getMPay24Api();
      $status = $mPay24MagentoShop->updateStatusToCancel($payment->getOrder()->getIncrementId());
  
      if($status != "SUSPENDED" && $status != "PENDING")
        $mPAY24Result = $mPay24MagentoShop->cancelTransaction($payment->getOrder()->getIncrementId());
  
      if($status != "SUSPENDED" && $status != "PENDING" && $mPAY24Result->getGeneralResponse()->getStatus() != 'OK') {
        Mage::log('The order was not canceled: ' . $mPAY24Result->getGeneralResponse()->getReturnCode(), 10);
        Mage::throwException(Mage::helper('mpay24')->__("The order could not be canceled! For mor information see the log files!"));
        return false;
      }
    }
  
    parent::cancel($payment);
  
    return $this;
  }
  
  /**
   * Get mpay24 session namespace
   *
   * @return            Mpay24_Mpay24_Model_Session
   */
  public function getSession() {
    return Mage::getSingleton('mpay24/session');
  }

  public function clearSession(){
    $this->getSession()->set3DSUrl('');
    $this->getSession()->setAdditionalInformation('mpay_tid', '');
  }

  /**
   * Get payment
   *
   * @return            Mage_Sales_Model_Order
   */
  public function getPayment() {
    if (empty($this->_payment))
      $this->_payment = $this->getCheckout()->getQuote()->getPayment();

    return $this->_payment;
  }

  /**
   * Get quote
   *
   * @return            Mage_Sales_Model_Order
   */
  public function getQuote() {
    if (empty($this->_quote))
      $this->_quote = $this->getCheckout()->getQuote();

    return $this->_quote;
  }

  /**
   * Get checkout
   *
   * @return            Mage_Sales_Model_Order
   */
  public function getCheckout() {
    if (empty($this->_checkout))
            $this->_checkout = Mage::getSingleton('checkout/session');

    return $this->_checkout;
  }

  /**
   * Get order model
   *
   * @return            Mage_Sales_Model_Order
   */
  public function getOrder() {
    if (!$this->_order) {
      $paymentInfo = $this->getInfoInstance();
      $this->_order = Mage::getModel('sales/order')->loadByIncrementId($paymentInfo->getOrder()->getRealOrderId());
    }

    return $this->_order;
  }
}