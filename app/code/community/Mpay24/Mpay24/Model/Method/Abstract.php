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
 * @version             $Id: Abstract.php 5 2013-10-10 13:08:44Z sapolhei $
 */

abstract class Mpay24_Mpay24_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract {

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