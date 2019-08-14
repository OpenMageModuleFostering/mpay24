<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Acceptpayment.php 6413 2015-07-14 12:50:34Z anna $
 */
include_once Mage::getBaseDir ( 'code' ) . "/community/Mpay24/Mpay24/Model/Api/MPay24MagentoShop.php";
abstract class Mpay24_Mpay24_Model_Method_Acceptpayment extends Mpay24_Mpay24_Model_Method_Abstract {
  public function initialize($payment, $amount) {
    $ps = false;
    $type = "";
    $brand = "";
    $bic = "";
    $token = "";
    $paymentType = Mage::app ()->getRequest ()->getParam ( 'payment' );
        
    switch ($paymentType ['method']) {
      case 'mpay24_paypal' :
        $type = $brand = "PAYPAL";
        break;
      case 'mpay24_eps' :
        $type = $brand = "EPS";
        foreach(unserialize(Mage::getStoreConfig('mpay24/mpay24/active_payments')) as $key => $value)
          if($value["P_TYPE"] == "EPS")
            $brand = $value["BRAND"];
        
        preg_match('#\((.*?)\)#', Mage::app ()->getRequest ()->getParam ( 'bankname' ), $match);
        $bic = $match[1];
        break;
      case 'mpay24_tokenizer' :
        $type = $brand = "TOKEN";
        $token = Mage::app ()->getRequest ()->getParam ( 'token' );
        break;
      default :
        break;
    }
    
    $this->clearSession ();
    $error = false;
    
    if ($amount > 0) {
      $payment->setAmount ( $amount );
      $payment->setCcType ( "$type => $brand" )->save ();
      $mPay24MagentoShop = MPay24MagentoShop::getMPay24Api ();
      $mPay24MagentoShop->setVariables ( $payment->getOrder (), $ps, $type, $brand, $bic, $token);
      $soapResult = $mPay24MagentoShop->payBackend2Backend ( $type );

      if ($soapResult->getGeneralResponse ()->getStatus () == "OK") {
        $this->getPayment ()->setAuth ( true );
        
        if($soapResult->getGeneralResponse ()->getReturnCode() == "REDIRECT") {
          $this->getSession ()->setUrl ( $soapResult->getLocation () );
          $payment->getOrder ()->addStatusToHistory ( Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper ( 'mpay24' )->__ ( "Redirected to an external page" ) )->save ();
        } 
        else if($soapResult->getGeneralResponse ()->getReturnCode() == "OK") {
          if(Mage::helper('customer')->isLoggedIn())
            $this->getSession ()->setUrl ( Mage::getUrl(MPay24MagentoShop::SUCCESS_URL,array('_secure' => true, '_query' => "TID=" . substr($payment->getOrder ()->getIncrementId(),0,32) )) );
          else
            $this->getSession ()->setUrl ( Mage::getUrl(MPay24MagentoShop::GUEST_SUCCESS_URL,array('_secure' => true, '_query' => "tid=" . substr($payment->getOrder ()->getIncrementId(),0,32) )) );
        }
        else
          Mage::log("Unexpected response from mPAY24: " . $soapResult->getReturnCode());
        
        $payment->setTransactionId ( $payment->getOrder ()->getIncrementId () )->setIsTransactionClosed ( 1 )->save ();
        $transaction = $payment->addTransaction ( Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER )->save ();
        
        $payment->setAdditionalInformation ( 'user_field', MAGENTO_VERSION . substr ( $payment->getOrder ()->getIncrementId (), 0, 100 ) . '_' . date ( 'Y-m-d' ) );
        
        $payment->setAdditionalInformation ( 'confirmed', "" )->save ();
        
      }
      else
        $error = $soapResult->getGeneralResponse ()->getReturnCode ();
    }
    else {
      $error = Mage::helper ( 'mpay24' )->__ ( 'Invalid amount for authorization.' );
    }
    
    if ($error !== false) {
      Mage::log ( $error, 10 );
            
//       if($type == "TOKEN")
        Mage::throwException ( Mage::helper ( 'mpay24' )->__ ( "The payment was not successful! " ) . "\n" . Mage::helper ( 'mpay24' )->__ ( "Please try again or choose a different payment method." ));
//       else
//         Mage::throwException ( Mage::helper ( 'mpay24' )->__ ( 'Please contact the merchant,' ) . "\n" . Mage::helper ( 'mpay24' )->__ ( 'this payment is not possible at the moment!' ) );
    }
    
    return $this;
  }
  
  public function getOrderPlaceRedirectUrl() {
    return ($this->getPayment ()->getAuth ()) ? Mage::getUrl ( 'mpay24/payment/redirect', array (
        '_secure' => true 
    ) ) : false;
  }
}
?>
