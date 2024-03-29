<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Data.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Helper_Data extends Mage_Core_Helper_Abstract {
  /**
   * Get payment charge
   * @param string $code
   * @param Mage_Sales_Model_Quote $quote
   * @return float
   */
  public function getPaymentCharge($code, $quote=null) {
    if (is_null($quote))
      $quote = Mage::getSingleton('checkout/session')->getQuote();
    
    $amount = array();
    $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
 
    $charges = array();
    if ($code == 'mpay24') {
      $paymentsCount = Mage::getStoreConfig("mpay24/mpay24/payments_count");
      for($i=1; $i<=$paymentsCount; $i++) {
        $charges[$i]["type"] = Mage::getStoreConfig("mpay24/mpay24/tax_type_$i");
        $charges[$i]["value"] = Mage::getStoreConfig("mpay24/mpay24/tax_$i");
        
        if ($charges[$i]["value"]) {
          $amount[$i]["type"] = $charges[$i]["type"];
          $amount[$i]["value"] = $charges[$i]["value"];

          if ($charges[$i]["type"] =="percent") {
            $subTotal = $address->getSubtotal();
            $tax = $address->getBaseTaxAmount();
            $amount[$i]["endValue"] = ($subTotal + $tax) * floatval($charges[$i]["value"]) / 100;
          } else
            $amount[$i]["endValue"] = floatval($charges[$i]["value"]);
        }
      }
    }
    
    return $amount;
  }
}