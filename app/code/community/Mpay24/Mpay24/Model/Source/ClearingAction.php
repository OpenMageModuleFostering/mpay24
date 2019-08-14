<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: ClearingAction.php 6252 2015-03-26 15:57:57Z anna $
 */

include_once Mage::getBaseDir('code')."/community/Mpay24/Mpay24/Model/Api/MPay24MagentoShop.php";

class Mpay24_Mpay24_Model_Source_ClearingAction {
  public function toOptionArray() {
    return array(
                  array('value' => MPay24MagentoShop::PAYMENT_TYPE_AUTH, 'label' => Mage::helper('mpay24')->__('Authorization')),
                  array('value' => MPay24MagentoShop::PAYMENT_TYPE_SALE, 'label' => Mage::helper('mpay24')->__('Billing')),
                );
  }
}