<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Mpay24.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Block_Mpay24 extends Mage_Core_Block_Template {
  public function _prepareLayout() {
    return parent::_prepareLayout();
  }

  public function getMpay24() {
    if (!$this->hasData('mpay24'))
      $this->setData('mpay24', Mage::registry('mpay24'));

    return $this->getData('mpay24');
  }
}