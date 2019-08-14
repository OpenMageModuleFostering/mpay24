<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Paymentcharge.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Model_Sales_Order_Invoice_Total_Paymentcharge extends Mage_Sales_Model_Order_Invoice_Total_Abstract {
  public function collect(Mage_Sales_Model_Order_Invoice $invoice) {
    $invoice->setPaymentCharge(0);
    $invoice->setBasePaymentCharge(0);
    
    $amount = $invoice->getOrder()->getBasePaymentCharge();
    $invoice->setBasePaymentCharge($amount);
    
    $type = $invoice->getOrder()->getPaymentChargeType();
    $invoice->setPaymentChargeType($type);
    
    $amount = $invoice->getOrder()->getPaymentCharge();
    if($type == 'percent')
      $invoice->setPaymentCharge($invoice->getSubtotal()*$invoice->getBasePaymentCharge()/100);
    else
      $invoice->setPaymentCharge($amount);
    
    if($type == 'percent') {
      $invoice->setGrandTotal($invoice->getGrandTotal() + $invoice->getPaymentCharge());
      $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $invoice->getSubtotal()*$invoice->getBasePaymentCharge()/100);
    } else {
      $invoice->setGrandTotal($invoice->getGrandTotal() + $invoice->getPaymentCharge());
      $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $invoice->getBasePaymentCharge());
    }

    return $this;
  }
}