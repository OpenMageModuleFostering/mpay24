<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Paypal.php 6413 2015-07-14 12:50:34Z anna $
 */

include_once Mage::getBaseDir('code')."/community/Mpay24/Mpay24/Model/Api/MPay24MagentoShop.php";

class Mpay24_Mpay24_Model_Paypal extends Mpay24_Mpay24_Model_Method_AcceptPayment {
  protected $_code = 'mpay24_paypal';
  protected $_formBlockType = 'mpay24/form_paypal';
  protected $_infoBlockType = 'mpay24/info_acceptpayment';

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
}
?>