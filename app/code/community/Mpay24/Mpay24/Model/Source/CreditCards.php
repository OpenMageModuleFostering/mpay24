<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: AllowedIPs.php 6252 2015-03-26 15:57:57Z anna $
 */
class Mpay24_Mpay24_Model_Source_CreditCards {
  public function toOptionArray() {
    $creditCards = array();
    
    $paymentsArray = unserialize(Mage::getStoreConfig('mpay24/mpay24/active_payments'));
    
    foreach($paymentsArray as $key => $value)
      if($value["P_TYPE"] == "CC")
        array_push($creditCards, array('value' => $value["BRAND"]."=>".$value["DESCR"], 'label' => Mage::helper('mpay24')->__($value["DESCR"])));
    
    return $creditCards;
  }
}