<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Selectpayment.php 6252 2015-03-26 15:57:57Z anna $
 */
include_once Mage::getBaseDir('code')."/community/Mpay24/Mpay24/Model/Api/MPay24MagentoShop.php";

class Mpay24_Mpay24_Block_Form_Tokenizer extends Mage_Payment_Block_Form {
  var $soapResult;
  var $cc = array("AMEX" => false, "DINERS" => false, "JCB" => false, "MASTERCARD" => false, "VISA" => false);
  
  protected function _construct() {
    $titleSetting = Mage::getStoreConfig('payment/mpay24_tokenizer/title');
    $creditCards = explode(",", Mage::getStoreConfig('mpay24/mpay24/credit_cards'));

    if($titleSetting == 'logo') {
      $logo = Mage::getConfig()->getBlockClassName('core/template');
      $logo = new $logo;
      $logo->setTemplate('mpay24/form/logos.phtml');
      
      $i=0;
      foreach($creditCards as $key => $value) {
        $i++;
        $paymentLogoSrc = "setPaymentLogoSrc$i";
        $alt = "setAlt$i";
        
        $brandDescr = explode("=>", $value);
        $brand = $brandDescr[0];
        $descr = $brandDescr[1];
        $logo
          ->$paymentLogoSrc(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN)."frontend/base/default/images/Mpay24/Mpay24/logos/$brand.svg")
          ->$alt($descr);
        
        $this->cc[$brand] = true;
      }
      
      $this->setTemplate('mpay24/form/tokenizer.phtml')
      ->setMethodTitle('')
      ->setMethodLabelAfterHtml($logo->toHtml());
    } else {
      $this->setTemplate('mpay24/form/tokenizer.phtml')
      ->setMethodTitle(Mage::helper('mpay24')->__("Credit cards"));
    }
  }

  /**
   * Retrieve payment configuration object
   *
   * @return            Mage_Payment_Model_Config
   */
  protected function _getConfig() {
    return Mage::getSingleton('mpay24/config');
  }
  
  protected function createToken() {
    $mPay24MagentoShop = MPay24MagentoShop::getMPay24Api ();
    $this->soapResult = $mPay24MagentoShop->payWithToken("CC");
  }
  
  protected function getTokenResponse() {
    $error = false;
    
    if($this->soapResult->getPaymentResponse()->generalResponse->getStatus() == "OK") {
      return $this->soapResult;
    } else
      $error = $this->soapResult->getPaymentResponse()->generalResponse->getReturnCode();
    
    if ($error !== false) {
      Mage::log ( $error, 10 );
      Mage::throwException ( Mage::helper ( 'mpay24' )->__ ( 'Please contact the merchant,' ) . "\n" . Mage::helper ( 'mpay24' )->__ ( 'this payment is not possible at the moment!' ) );
      return $this;
    }
  }
  
  protected function isCCActive($brand) {
    return $this->cc[$brand];
  }
}
