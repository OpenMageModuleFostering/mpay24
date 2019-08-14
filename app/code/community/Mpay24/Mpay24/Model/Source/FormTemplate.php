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
 * @version             $Id: FormTemplate.php 27 2014-08-27 13:59:46Z sapolhei $
 */
class Mpay24_Mpay24_Model_Source_FormTemplate {

  /**
   * Options getter
   *
   * @return            array
   */
  public function toOptionArray() {
    return array(
                  array('value' => 'dropDown.phtml', 'label'=>'Drop-Down'),
                  array('value' => 'area.phtml', 'label'=>'Icons'),
                  array('value' => 'radio.phtml', 'label'=>'Radio-Button'),
    );
  }
}
?>