<?php
/** * Magento * * NOTICE OF LICENSE * * This source file is subject to the Open Software License (OSL 3.0) * that is bundled with this package in the file LICENSE.txt. * It is also available through the world-wide-web at this URL: * http://opensource.org/licenses/osl-3.0.php * If you did not receive a copy of the license and are unable to * obtain it through the world-wide-web, please send an email * to license@magentocommerce.com so we can send you a copy immediately. * * @category            Mpay24 * @package             Mpay24_Mpay24 * @author              Anna Sadriu (mPAY24 GmbH) * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0) * @version             $Id: Paymentcharge.php 6252 2015-03-26 15:57:57Z anna $ */
class Mpay24_Mpay24_Model_Sales_Quote_Address_Total_Paymentcharge extends Mage_Sales_Model_Quote_Address_Total_Abstract {

  public function __construct() {
    $this->setCode ( 'payment_charge' );
  }

  public function collect(Mage_Sales_Model_Quote_Address $address) {
    $payment_method = "";
    if(Mage::app()->getRequest()->getParam('payment_method') != null)
      $payment_method = Mage::app()->getRequest()->getParam('payment_method');
    elseif(Mage::app()->getRequest()->getParam('mpay24_ps') != null)
      $payment_method = Mage::app()->getRequest()->getParam('mpay24_ps');
    
    if (Mage::getStoreConfig('mpay24/mpay24/payments_active') == 'true' && Mage::getStoreConfig("mpay24/mpay24/forced_preselection") == 1 && $payment_method != "" ) {
      $address->setPaymentCharge ( 0 );
      $address->setBasePaymentCharge ( 0 );
      $address->setPaymentChargeType ( "absolute" );
      $items = $address->getAllItems ();
      if (! count ( $items ))
        return $this;
      
      $paymentMethod = $address->getQuote ()->getPayment ()->getMethod ();
      
      if ($paymentMethod) {
        $amount = Mage::helper ( 'mpay24' )->getPaymentCharge ( $paymentMethod, $address->getQuote () );
        
        if (isset ( $amount [substr ( $payment_method, 11 )] )) {
          $address->setPaymentChargeType ( $amount [substr ( $payment_method, 11 )] ['type'] );
          $address->setPaymentCharge ( $amount [substr ( $payment_method, 11 )] ['value'] );
          $address->setBasePaymentCharge ( $amount [substr ( $payment_method, 11 )] ['value'] );
        }
      }
      
      $charge = $address->getPaymentCharge ();
      $baseCharge = $address->getBasePaymentCharge ();

      if ($address->getPaymentChargeType () == "percent") {
        $address->setGrandTotal ( $address->getGrandTotal () + $address->getSubtotal () * $charge / 100 );
        $address->setBaseGrandTotal ( $address->getBaseGrandTotal () + $address->getSubtotal () * $baseCharge / 100 );
      } else {
        $address->setGrandTotal ( $address->getGrandTotal () + $charge );
        $address->setBaseGrandTotal ( $address->getBaseGrandTotal () + $baseCharge );
      }
    }
    
    return $this;
  }

  public function fetch(Mage_Sales_Model_Quote_Address $address) {
    $charge = $address->getPaymentCharge ();
    
    if($charge > 0)
      $lab = Mage::helper ( 'mpay24' )->__ ( "Payment charge" );
    else 
      $lab = Mage::helper ( 'mpay24' )->__ ( "Payment discount" );
    
    if ($address->getPaymentChargeType () == "percent") {
      $label = "$lab (" . number_format ( $charge, 2, '.', '' ) . "%)";
      $charge = $address->getSubtotal () * $charge / 100;
    } else
      $label = "$lab (" . Mage::helper ( 'mpay24' )->__ ( "Absolute value" ) . ")";
    
    if ($charge && $charge != 0 && $charge != "")
      $address->addTotal ( array (
          'code' => $this->getCode (),
          'title' => $label,
          'full_info' => array (),
          'value' => $charge 
      )
       );

    return $this;
  }
}