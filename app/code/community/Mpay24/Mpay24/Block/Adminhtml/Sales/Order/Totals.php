<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Totals.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Block_Adminhtml_Sales_order_Totals extends Mpay24_Mpay24_Block_Adminhtml_Sales_Totals {
  /**
   * Initialize order totals array
   *
   * @return Mage_Sales_Block_Order_Totals
   */
  protected function _initTotals() {
    parent::_initTotals();
    $this->_totals['paid'] = new Varien_Object(array(
                                                    'code'      => 'paid',
                                                    'strong'    => true,
                                                    'value'     => $this->getSource()->getTotalPaid(),
                                                    'base_value'=> $this->getSource()->getBaseTotalPaid(),
                                                    'label'     => $this->helper('sales')->__('Total Paid'),
                                                    'area'      => 'footer'
                                                  ));
    $this->_totals['refunded'] = new Varien_Object(array(
                                                    'code'      => 'refunded',
                                                    'strong'    => true,
                                                    'value'     => $this->getSource()->getTotalRefunded(),
                                                    'base_value'=> $this->getSource()->getBaseTotalRefunded(),
                                                    'label'     => $this->helper('sales')->__('Total Refunded'),
                                                    'area'      => 'footer'
                                                  ));
    $this->_totals['due'] = new Varien_Object(array(
                                                    'code'      => 'due',
                                                    'strong'    => true,
                                                    'value'     => $this->getSource()->getTotalDue(),
                                                    'base_value'=> $this->getSource()->getBaseTotalDue(),
                                                    'label'     => $this->helper('sales')->__('Total Due'),
                                                    'area'      => 'footer'
                                                  ));
    return $this;
  }
}