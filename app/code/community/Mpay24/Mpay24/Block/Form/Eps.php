<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Eps.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Block_Form_Eps extends Mage_Payment_Block_Form {
  protected function _construct() {
    $titleSetting = Mage::getStoreConfig('payment/mpay24_eps/title');
    
    $brand = "";
    
    $paymentsArray = unserialize(Mage::getStoreConfig('mpay24/mpay24/active_payments'));
    
    foreach($paymentsArray as $key => $value)
      if($value["P_TYPE"] == "EPS")
        $brand = $value["BRAND"];
    
    if($titleSetting == 'logo') {
      $logo = Mage::getConfig()->getBlockClassName('core/template');
      $logo = new $logo;
          
      if($brand == "EPS")
        $logo->setTemplate('mpay24/form/logos.phtml')
            ->setPaymentLogoSrc1(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN)."frontend/base/default/images/Mpay24/Mpay24/logos/EPS.svg")
            ->setAlt1("eps Online-Überweisung");
      elseif($brand == "INTERNATIONAL") {
        $logo->setTemplate('mpay24/form/logos.phtml')
            ->setPaymentLogoSrc1(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN)."frontend/base/default/images/Mpay24/Mpay24/logos/EPS.svg")
            ->setAlt1("eps Online-Überweisung")
            ->setPaymentLogoSrc2(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN)."frontend/base/default/images/Mpay24/Mpay24/logos/GIROPAY.svg")
            ->setAlt2("giropay");
      }
        
      $this->setTemplate('mpay24/form/eps.phtml')
      ->setRedirectMessage(
          Mage::helper('mpay24')->__('You will be redirected to the Stuzza website.')
      )
      ->setMethodTitle('')
      ->setMethodLabelAfterHtml($logo->toHtml());
    } else {
      $this->setTemplate('mpay24/form/eps.phtml')
      ->setRedirectMessage(
          Mage::helper('mpay24')->__('You will be redirected to the Stuzza website.')
      );
      
      if($brand == "EPS")
        $this->setMethodTitle('eps Online-Überweisung');
      elseif($brand == "INTERNATIONAL")
        $this->setMethodTitle('eps & giropay Online-Überweisung');
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
  
  protected function isInternational() {
    $int = false;
    
    foreach(unserialize(Mage::getStoreConfig('mpay24/mpay24/active_payments')) as $key => $value)
      if($value["P_TYPE"] == "EPS")
        if($value["BRAND"] == "INTERNATIONAL")
          $int = true;
        
    return $int;
  }
 
}
