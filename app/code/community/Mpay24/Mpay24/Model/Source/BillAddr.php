<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: BillAddr.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Model_Source_BillAddr {

  /**
   * Options getter
   *
   * @return            array
   */
  public function toOptionArray() {
    return array(
                  array('value' => 'ReadWrite', 'label'=>Mage::helper('adminhtml')->__('ReadWrite')),
                  array('value' => 'ReadOnly', 'label'=>Mage::helper('adminhtml')->__('ReadOnly')),
                );
  }
}