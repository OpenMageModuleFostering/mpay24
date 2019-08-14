<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: IndexController.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_IndexController extends Mage_Core_Controller_Front_Action {
  public function indexAction() {
    $this->_redirect('/');
  }
}