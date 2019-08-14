<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: ParentRedirect.php 6252 2015-03-26 15:57:57Z anna $
 */

class Mpay24_Mpay24_Block_ParentRedirect extends Mage_Core_Block_Abstract {
  protected function _toHtml() {
    $html = '<html><body>';
    $html.= '<script type="text/javascript">self.parent.location.href="'.(Mage::getUrl(Mage::getSingleton('mpay24/session')->getParentRedirectUrl(), array('_secure'=>true))).'";</script>';
    $html.= '</body></html>';

    return $html;
  }
}