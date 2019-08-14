<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Collection.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Model_Mysql4_Mpay24_Debug_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {
  protected function _construct() {
    $this->_init('mpay24/mpay24_debug');
  }
}