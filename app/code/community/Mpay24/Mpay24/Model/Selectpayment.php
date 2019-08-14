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
 * @version             $Id: Selectpayment.php 6413 2015-07-14 12:50:34Z anna $
 */

include_once Mage::getBaseDir('code')."/community/Mpay24/Mpay24/Model/Api/MPay24MagentoShop.php";

class Mpay24_Mpay24_Model_Selectpayment extends Mpay24_Mpay24_Model_Method_Selectpayment {
  protected $_code = 'mpay24';
  protected $_formBlockType = 'mpay24/form_selectpayment';
  protected $_infoBlockType = 'mpay24/info_selectpayment';

  /**
   * Availability options
   */
  protected $_isGateway               = true;
  protected $_canOrder                = true;
  protected $_canAuthorize            = true;
  protected $_canCapture              = true;
  protected $_canCapturePartial       = true;
  protected $_canRefund               = true;
  protected $_canRefundInvoicePartial = true;
  protected $_canVoid                 = true;
  protected $_canUseInternal          = true;
  protected $_canUseCheckout          = true;
  protected $_isInitializeNeeded      = true;

  public function validate() {
    parent::validate();
    return $this;
  }

//   public function authorize(Varien_Object $payment, $amount) {
//     return parent::authorize($payment, $amount);
//   }
  
  /**
   * Instantiate state and set it to state object
   * @param string $paymentAction
   * @param Varien_Object
   */
  public function initialize($paymentAction, $stateObject) {
    switch ($paymentAction) {
      case MPay24MagentoShop::PAYMENT_TYPE_AUTH:
      case MPay24MagentoShop::PAYMENT_TYPE_SALE:
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $payment->setAmountAuthorized($order->getTotalDue());
        $payment->setBaseAmountAuthorized($order->getBaseTotalDue());

        break;
      default:
        break;
    }

    parent:: initialize($payment, $order->getTotalDue());
  }

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
}
?>