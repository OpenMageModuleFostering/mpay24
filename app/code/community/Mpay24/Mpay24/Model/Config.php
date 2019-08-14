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
 * @version             $Id: Config.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Model_Config extends Mage_Payment_Model_Config {

  /**
   * Retrieve active system payments
   *
   * @param             mixed               $store
   * @return            array
   */
  public function getActiveMethods($store=null) {
    $methods = array();
    $config = Mage::getStoreConfig('mpay24', $store);

    foreach ($config as $code => $methodConfig)
      if (Mage::getStoreConfigFlag('payment/'.$code.'/active', $store))
        $methods[$code] = $this->_getMethod($code, $methodConfig);

    return $methods;
  }

  /**
   * Retrieve all system payments
   *
   * @param             mixed               $store
   * @return            array
   */
  public function getAllMethods($store=null) {
    $methods = array();
    $config = Mage::getStoreConfig('payment', $store);

    foreach ($config as $code => $methodConfig)
      $methods[$code] = $this->_getMethod($code, $methodConfig);

    return $methods;
  }
}