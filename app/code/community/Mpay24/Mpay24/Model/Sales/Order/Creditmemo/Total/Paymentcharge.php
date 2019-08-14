<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Paymentcharge.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Model_Sales_Order_Creditmemo_Total_Paymentcharge extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract {
  public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo) {
    $creditmemo->setPaymentCharge(0);
    $creditmemo->setBasePaymentCharge(0);
    
    $amount = $creditmemo->getOrder()->getBasePaymentCharge();
    $creditmemo->setBasePaymentCharge($amount);

    $type = $creditmemo->getOrder()->getPaymentChargeType();
    $creditmemo->setPaymentChargeType($type);
    
    $amount = $creditmemo->getOrder()->getPaymentCharge();
    if($type == 'percent')
      $creditmemo->setPaymentCharge($creditmemo->getSubtotal()*$creditmemo->getBasePaymentCharge()/100);
    else
      $creditmemo->setPaymentCharge($amount);
    
    if($type == 'percent') {
      $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $creditmemo->getPaymentCharge());
      $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $creditmemo->getSubtotal()*$creditmemo->getBasePaymentCharge()/100);
    } else {
      $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $creditmemo->getPaymentCharge());
      $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $creditmemo->getBasePaymentCharge());
    }

    return $this;
  }
}