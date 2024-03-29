<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Totals.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Block_Adminhtml_Sales_order_Creditmemo_Totals extends Mpay24_Mpay24_Block_Adminhtml_Sales_Totals {
  protected $_creditmemo;

  public function getCreditmemo() {
    if ($this->_creditmemo === null)
      if ($this->hasData('creditmemo'))
        $this->_creditmemo = $this->_getData('creditmemo');
      elseif (Mage::registry('current_creditmemo'))
        $this->_creditmemo = Mage::registry('current_creditmemo');
      elseif ($this->getParentBlock() && $this->getParentBlock()->getCreditmemo())
        $this->_creditmemo = $this->getParentBlock()->getCreditmemo();
    
    return $this->_creditmemo;
  }

  public function getSource() {
    return $this->getCreditmemo();
  }

  /**
   * Initialize creditmemo totals array
   *
   * @return Mage_Sales_Block_Order_Totals
   */
  protected function _initTotals() {
    parent::_initTotals();
    $this->addTotal(new Varien_Object(array(
                                            'code'      => 'adjustment_positive',
                                            'value'     => $this->getSource()->getAdjustmentPositive(),
                                            'base_value'=> $this->getSource()->getBaseAdjustmentPositive(),
                                            'label'     => $this->helper('sales')->__('Adjustment Refund')
                                        )));
    $this->addTotal(new Varien_Object(array(
                                            'code'      => 'adjustment_negative',
                                            'value'     => $this->getSource()->getAdjustmentNegative(),
                                            'base_value'=> $this->getSource()->getBaseAdjustmentNegative(),
                                            'label'     => $this->helper('sales')->__('Adjustment Fee')
                                        )));
    return $this;
  }
}