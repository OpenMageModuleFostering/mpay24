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
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Firedrago Magento
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Paymentcharge.php 5 2013-10-10 13:08:44Z sapolhei $
 */

class Mpay24_Mpay24_Model_Sales_Quote_Address_Total_Paymentcharge extends Mage_Sales_Model_Quote_Address_Total_Abstract {
  public function __construct() {
    $this->setCode('payment_charge');
  }

  public function collect(Mage_Sales_Model_Quote_Address $address) {
    if(isset($_REQUEST['mpay24_ps'])) {
      $address->setPaymentCharge(0);
      $address->setBasePaymentCharge(0);
      $address->setPaymentChargeType("absolute");
  
      $items = $address->getAllItems();
  
      if (!count($items))
        return $this;
  
      $paymentMethod = $address->getQuote()->getPayment()->getMethod();

      if ($paymentMethod) {
        $amount = Mage::helper('mpay24')->getPaymentCharge($paymentMethod, $address->getQuote());
        
        if(isset($amount[substr($_REQUEST['mpay24_ps'],4)])) {
          $address->setPaymentChargeType($amount[substr($_REQUEST['mpay24_ps'],4)]['type']);
          $address->setPaymentCharge($amount[substr($_REQUEST['mpay24_ps'],4)]['value']);
          $address->setBasePaymentCharge($amount[substr($_REQUEST['mpay24_ps'],4)]['value']);
        }
      }

      $charge = $address->getPaymentCharge();
      $baseCharge = $address->getBasePaymentCharge();

      if($address->getPaymentChargeType() == "percent") {
        $address->setGrandTotal($address->getGrandTotal() + $address->getSubtotal()*$charge/100);
        $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getSubtotal()*$baseCharge/100);
      } else {
        $address->setGrandTotal($address->getGrandTotal() + $charge);
        $address->setBaseGrandTotal($address->getBaseGrandTotal() + $baseCharge);
      }
    }
    
    return $this;
  }

  public function fetch(Mage_Sales_Model_Quote_Address $address) {
    $charge = $address->getPaymentCharge();
    
    if($address->getPaymentChargeType() == "percent") {
      $label = Mage::helper('mpay24')->__("Payment charge") . " (" . number_format($charge,2,'.','') . "%)";
      $charge = $address->getSubtotal()*$charge/100;
    } else
      $label = Mage::helper('mpay24')->__("Payment charge") . " (" . Mage::helper('mpay24')->__("Absolute value") . ")";
    
    if ($charge && $charge!=0 && $charge != "")
      $address->addTotal(array(
                                'code' => $this->getCode(),
                                'title' => $label,
                                'full_info' => array(),
                                'value' => $charge
                            ));
    
    return $this;
  }
}