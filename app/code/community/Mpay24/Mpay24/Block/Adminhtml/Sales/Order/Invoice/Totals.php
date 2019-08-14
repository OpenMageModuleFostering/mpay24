<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Totals.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Block_Adminhtml_Sales_order_Invoice_Totals extends Mpay24_Mpay24_Block_Adminhtml_Sales_Totals {
  protected $_invoice = null;

  public function getInvoice() {
    if ($this->_invoice === null)
      if ($this->hasData('invoice'))
        $this->_invoice = $this->_getData('invoice');
      elseif (Mage::registry('current_invoice'))
        $this->_invoice = Mage::registry('current_invoice');
      elseif ($this->getParentBlock()->getInvoice())
        $this->_invoice = $this->getParentBlock()->getInvoice();
    
    return $this->_invoice;
  }

  public function getSource() {
    return $this->getInvoice();
  }

  /**
   * Initialize order totals array
   *
   * @return Mage_Sales_Block_Order_Totals
   */
  protected function _initTotals() {
    parent::_initTotals();
    return $this;
  }
}