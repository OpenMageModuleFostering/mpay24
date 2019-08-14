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
 * @author              Firedrago Magento
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: System.php 5 2013-10-10 13:08:44Z sapolhei $
 */
class Mpay24_Mpay24_Model_Source_System {

  /**
   * Options getter
   *
   * @return            array
   */
  public function toOptionArray() {
    return array(
                  array('value' => true, 'label'=>Mage::helper('adminhtml')->__('Test')),
                  array('value' => false, 'label'=>Mage::helper('adminhtml')->__('Live')),
                );
  }
}