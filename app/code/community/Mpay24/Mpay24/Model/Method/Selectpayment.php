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
 * @category  Mpay24
 * @package    Mpay24_Mpay24
 * @author    Firedrago Magento
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Selectpayment.php 5 2013-10-10 13:08:44Z sapolhei $
 */

include_once "app/code/community/Mpay24/Mpay24/Model/Api/MPay24MagentoShop.php";

abstract class Mpay24_Mpay24_Model_Method_Selectpayment extends Mpay24_Mpay24_Model_Method_Abstract{

  public function authorize(Varien_Object $payment, $amount) {
    $ps = false;
    $type = "";
    $brand = "";

    $payments = array();
    $paymentsArray = unserialize(Mage::getStoreConfig('mpay24/mpay24/active_payments'));
    $i = 1;
    
    foreach($paymentsArray as $id => $paymentType) {
      if(strlen($id) == 1)
        $id = "00$id";
      elseif(strlen($id) == 2)
        $id = "0$id";

      $payments[$id] = $paymentType;
      $payments[$id]['PS'] = $i;
      
      $i++;
    }

    if(isset($_REQUEST['mpay24_ps']) && $_REQUEST['mpay24_ps'] != 'false') {
      $ps = true;
      $brand = $payments[substr($_REQUEST['mpay24_ps'],0,3)]['BRAND'];
      $type = $payments[substr($_REQUEST['mpay24_ps'],0,3)]['P_TYPE'];
    }

    $this->clearSession();
    $error = false;

    if($amount>0) {
      $payment->setAmount($amount);

      $mPay24MagentoShop = MPay24MagentoShop::getMPay24Api();
      $mPay24MagentoShop->setVariables($payment->getOrder(), $ps, $type, $brand);
      $soapResult = $mPay24MagentoShop->pay();

      $payment->setTransactionId($payment->getOrder()->getIncrementId())->setIsTransactionClosed(0);

      if($soapResult->getGeneralResponse()->getStatus() == "OK") {
        $this->getPayment()->setAuth(true);
        $this->getSession()->setUrl($soapResult->getLocation());
        $payment->setAdditionalInformation('user_field', MPay24MagentoShop::MAGENTO_VERSION.substr($payment->getOrder()->getIncrementId(),0,100).'_'.date('Y-m-d'));
        
        $payment->setIsTransactionPending(true);
        $payment->getOrder()->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper('mpay24')->__("Redirected to mPAY24 pay page"))->save();
      } else
        $error = $soapResult->getGeneralResponse()->getReturnCode();
    } else {
            $error = Mage::helper('mpay24')->__('Invalid amount for authorization.');
    }

    if ($error !== false) {
      Mage::log($error, 10);
      Mage::throwException(Mage::helper('mpay24')->__('Please contact the merchant,')."\n".Mage::helper('mpay24')->__('this payment is not possible at the moment!'));
    }
  }

  public function getOrderPlaceRedirectUrl() {
    return ($this->getPayment()->getAuth()) ? Mage::getUrl('mpay24/payment/redirect',array('_secure' => true)) : false;
  }
}