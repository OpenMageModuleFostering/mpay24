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

class Mpay24_Mpay24_Block_Sales_order_Invoice_Totals extends Mpay24_Mpay24_Block_Sales_Order_Totals {
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

  public function setInvoice($invoice) {
    $this->_invoice = $invoice;
    return $this;
  }

  /**
   * Get totals source object
   *
   * @return Mage_Sales_Model_Order
   */
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
    $this->removeTotal('base_grandtotal');
    return $this;
  }
}