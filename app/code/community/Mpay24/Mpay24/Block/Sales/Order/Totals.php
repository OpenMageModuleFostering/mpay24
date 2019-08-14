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
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Totals.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Block_Sales_Order_Totals extends Mage_Sales_Block_Order_Totals {
  /**
   * Initialize order totals array
   *
   * @return Mage_Sales_Block_Order_Totals
   */
  protected function _initTotals() {
    parent::_initTotals();

    $source = $this->getSource();

    /**
     * Add store rewards
     */
    $totals = $this->_totals;
    $newTotals = array();

    if (count($totals)>0) {
      foreach ($totals as $index=>$arr) {
        if ($index == "grand_total")
          if (((float)$source->getPaymentCharge()) != 0) {
            if($source->getBasePaymentCharge() > 0)
              $lab = Mage::helper('mpay24')->__("Payment charge");
            else
              $lab = Mage::helper('mpay24')->__("Payment discount");
            
            if($source->getPaymentChargeType() == "percent") {
              $label =  "$lab (" . number_format($source->getBasePaymentCharge(),2,'.','') . "%)";
              $amount = $source->getSubtotal()*$source->getBasePaymentCharge()/100;
            } else {
              $label = "$lab (" . Mage::helper('mpay24')->__("Absolute value") . ")";
              $amount = $source->getPaymentCharge();
            }
            
            if($source->getBasePaymentCharge() > 0)
              $newTotals['payment_charge'] = new Varien_Object(array(
                                                                  'code'  => 'payment_charge',
                                                                  'field' => 'payment_charge',
                                                                  'value' => $amount,
                                                                  'label' => $label
                                                              ));
            elseif($source->getBasePaymentCharge() < 0)
              $newTotals['payment_discount'] = new Varien_Object(array(
                                                                  'code'  => 'payment_discount',
                                                                  'field' => 'payment_discount',
                                                                  'value' => $amount,
                                                                  'label' => $label
                                                              ));
          }
        $newTotals[$index] = $arr;
      }
      $this->_totals = $newTotals;
    }
    
    return $this;
  }
}