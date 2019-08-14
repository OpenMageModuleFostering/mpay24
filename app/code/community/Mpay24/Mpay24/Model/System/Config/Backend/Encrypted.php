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
 * @version             $Id: Encrypted.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Model_System_Config_Backend_Encrypted extends Mage_Core_Model_Config_Data {

  /**
   * Decrypt value after loading
   */
  protected function _afterLoad() {
    $value = (string)$this->getValue();

    if (!empty($value) && ($decrypted = Mage::helper('core')->decrypt($value)))
      $this->setValue($decrypted);
  }

  protected function _beforeSave() {
    $value = (string)$this->getValue();

    // don't change value, if an obscured value came
    if (preg_match('/^\*+$/', $this->getValue()))
      $value = $this->getOldValue();

    if (!empty($value) && ($encrypted = Mage::helper('core')->encrypt($value)))
      $this->setValue($encrypted);
  }

  /**
   * Get & decrypt old value from configuration
   *
   * @return            string
   */
  public function getOldValue() {
    return Mage::helper('core')->decrypt(parent::getOldValue());
  }
}