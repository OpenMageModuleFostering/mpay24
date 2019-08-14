<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Acceptpayment.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Block_Info_Acceptpayment extends Mage_Payment_Block_Info {
  /**
   * Init default template for block
   */
  protected function _construct() {
    parent::_construct();
    $this->setTemplate('mpay24/info/acceptpayment.phtml');
  }

  public function getInfo() {
    $info = $this->getData('info');

    if (!($info instanceof Mage_Payment_Model_Info))
      Mage::throwException($this->__('Can not retrieve payment info model object.'));

    return $info;
  }

  public function getMethod() {
    $payment = $this->getInfo()->getMethodInstance();
    return $this->getInfo()->getMethodInstance();
  }

  public function toPdf() {
    $this->setTemplate('mpay24/info/pdf/acceptpayment.phtml');

    return $this->toHtml();
  }
  
  public function getPType() {
    $pTypeBrand = explode(" => ", $this->getInfo()->getCcType());
    
    if($pTypeBrand[0] == "CC")
      return $pTypeBrand[1];
    else 
      return $pTypeBrand[0];
  }
}