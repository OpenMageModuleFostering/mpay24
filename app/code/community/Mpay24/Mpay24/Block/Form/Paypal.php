<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Paypal.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Block_Form_Paypal extends Mage_Payment_Block_Form {
  protected function _construct() {
    $titleSetting = Mage::getStoreConfig('payment/mpay24_paypal/title');
    
    if($titleSetting == 'logo') {
      $logo = Mage::getConfig()->getBlockClassName('core/template');
      $logo = new $logo;
      $logo->setTemplate('mpay24/form/logos.phtml')
          ->setPaymentLogoSrc1(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN)."frontend/base/default/images/Mpay24/Mpay24/logos/PAYPAL.svg")
          ->setAlt1("PayPal");
      $this->setTemplate('mpay24/form/paypal.phtml')
      ->setRedirectMessage(
          Mage::helper('mpay24')->__('You will be redirected to the PayPal website.')
      )
      ->setMethodTitle('')
      ->setMethodLabelAfterHtml($logo->toHtml());
    } else {
      $this->setTemplate('mpay24/form/paypal.phtml')
      ->setRedirectMessage(
          Mage::helper('mpay24')->__('You will be redirected to the PayPal website.')
      )
      ->setMethodTitle('PayPal');
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
 
}