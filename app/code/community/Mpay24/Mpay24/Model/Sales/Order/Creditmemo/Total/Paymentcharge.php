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
 * @version             $Id: Paymentcharge.php 10 2013-10-31 14:23:20Z sapolhei $
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