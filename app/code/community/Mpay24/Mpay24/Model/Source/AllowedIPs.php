<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: AllowedIPs.php 6252 2015-03-26 15:57:57Z anna $
 */
class Mpay24_Mpay24_Model_Source_AllowedIPs {
  public function toOptionArray() {
    return array(
                  array('value' => '213.164.23.169', 'label' => Mage::helper('mpay24')->__('213.164.23.169')),
                  array('value' => '213.164.25.245', 'label' => Mage::helper('mpay24')->__('213.164.25.245')),
                  array('value' => '80.110.33.70', 'label' => Mage::helper('mpay24')->__('80.110.33.70')),
                  array('value' => '127.0.0.1', 'label' => Mage::helper('mpay24')->__('127.0.0.1'))
                );
  }
}