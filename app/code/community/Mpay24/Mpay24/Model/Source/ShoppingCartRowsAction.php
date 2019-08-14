<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: ShoppingCartRowsAction.php 6252 2015-03-26 15:57:57Z anna $
 */
class Mpay24_Mpay24_Model_Source_ShoppingCartRowsAction {
  public function toOptionArray() {
    return array(
                  array('value' => 'Number', 'label' => Mage::helper('mpay24')->__('Number')),
                  array('value' => 'ProductNr', 'label' => Mage::helper('mpay24')->__('ProductNr')),
                  array('value' => 'Description', 'label' => Mage::helper('mpay24')->__('Description')),
                  array('value' => 'Package', 'label' => Mage::helper('mpay24')->__('Package')),
                  array('value' => 'Quantity', 'label' => Mage::helper('mpay24')->__('Quantity')),
                  array('value' => 'ItemPrice', 'label' => Mage::helper('mpay24')->__('ItemPrice')),
                  array('value' => 'Price', 'label' => Mage::helper('mpay24')->__('Price'))
                );
  }
}