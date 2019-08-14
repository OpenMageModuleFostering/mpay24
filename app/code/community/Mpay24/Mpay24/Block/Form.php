<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Form.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Block_Form extends Mage_Core_Block_Template {
  /**
   * Retrieve payment method model
   *
   * @return            Mage_Payment_Model_Method_Abstract
   */
  public function getMethod() {
    $method = $this->getData('method');

    if (!($method instanceof Mage_Payment_Model_Method_Abstract))
      Mage::throwException($this->__('Can not retrieve payment method model object.'));

    return $method;
  }

  /**
   * Retrieve payment method code
   *
   * @return            string
   */
  public function getMethodCode() {
    return $this->getMethod()->getCode();
  }

  /**
   * Retrieve field value data from payment info object
   *
   * @param             string              $field
   * @return            mixed
   */
  public function getInfoData($field) {
    return $this->htmlEscape($this->getMethod()->getInfoInstance()->getData($field));
  }
}