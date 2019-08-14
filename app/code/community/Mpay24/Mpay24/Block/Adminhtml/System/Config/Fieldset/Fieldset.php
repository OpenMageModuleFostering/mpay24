<?php
/**
 * @category            Mpay24
 * @package             Mpay24_Mpay24
 * @author              Anna Sadriu (mPAY24 GmbH)
 * @license             http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version             $Id: Fieldset.php 6413 2015-07-14 12:50:34Z anna $
 */

include_once Mage::getBaseDir('code')."/community/Mpay24/Mpay24/Model/Api/MPay24MagentoShop.php";

class Mpay24_Mpay24_Block_Adminhtml_System_Config_Fieldset_Fieldset
                                                                      extends Mage_Adminhtml_Block_Abstract
                                                                      implements Varien_Data_Form_Element_Renderer_Interface {
  public $pTypes = array();

  /**
   * Render fieldset html
   *
   * @param             Varien_Data_Form_Element_Abstract                 $element
   * @return            string
   */
  public function render(Varien_Data_Form_Element_Abstract $element) {
    $html = $this->_getHeaderHtml($element);

    if(Mage::getStoreConfig('mpay24/mpay24as/active') && Mage::getStoreConfig('mpay24/mpay24/active_payments') != '') {
      $elementName = $element->getHtmlId();

      $payments = Mage::getStoreConfig('mpay24/mpay24/active_payments');
      $paymentsArray = unserialize($payments);
      $keys = array_keys($paymentsArray);
      $j = 0;

      foreach ($element->getSortedElements() as $field) {
        if(substr($field->getHtmlId(), strrpos($field->getHtmlId(), "_")+1) <= Mage::getStoreConfig('mpay24/mpay24/payments_count') && strpos($field->getHtmlId(), 'mpay24_mpay24_tax_') === false) {
          $first_paymentID = array_shift($keys);
          $payment = array_shift($paymentsArray);

          if(strlen($first_paymentID) == 1)
            $first_paymentID = "00$first_paymentID";
          elseif(strlen($first_paymentID) == 2)
            $first_paymentID = "0$first_paymentID";

          $field->setLabel("<img src='https://www.mpay24.com/web/img/logos/ptypes/$first_paymentID.png' alt='" . $payment['BRAND'] . ":" .  $payment['DESCR'] . "' title='" . $payment['P_TYPE'] . ":" .  $payment['DESCR'] . "'>");
          $html.= $field->toHtml();
        } elseif(substr($field->getHtmlId(), 0, 17) != 'mpay24_mpay24_ps_' && strpos($field->getHtmlId(), 'mpay24_mpay24_tax_') === false )
            $html.= $field->toHtml();
        elseif(strpos($field->getHtmlId(), 'mpay24_mpay24_tax_') !== false && substr($field->getHtmlId(), strrpos($field->getHtmlId(), "_")+1) <= Mage::getStoreConfig('mpay24/mpay24/payments_count'))
            $html.= $field->toHtml();
      }
    } else
      $html .= '<div style="color:red;">' . Mage::getStoreConfig('mpay24/mpay24/payments_error') . "</div>";

    $html .= $this->_getFooterHtml($element);
    $html .= "<h1>Extension Version: " . MAGENTO_VERSION . "</h1>";

    $comment = Mage::helper('mpay24')->__("The payment systems will be synchronized when the settings are saved!");

    $html .= "
      <script type='text/javascript'>
        window.onload = function() {
          document.getElementById('mpay24_mpay24as_title').setAttribute('class', document.getElementById('mpay24_mpay24as_title').getAttribute('class') + ' required-entry');
          var merchantID = document.getElementById('mpay24_mpay24as_merchantid');
          var mpay24Active = document.getElementById('mpay24_mpay24as_active');
          var mpay24System = document.getElementById('mpay24_mpay24as_system');
          var mpay24SOAPPass = document.getElementById('mpay24_mpay24as_soap_pass');
          var mpay24UseProxy = document.getElementById('mpay24_mpay24as_use_proxy');
          var mpay24ProxyPort = document.getElementById('mpay24_mpay24as_proxy_port');
          var mpay24ProxyHost = document.getElementById('mpay24_mpay24as_proxy_host');

          merchantID.onchange = function() {
            if(merchantID.parentElement.childNodes[2].innerHTML.indexOf('$comment') == -1) {
              document.getElementById('mpay24_mpay24').style.display = 'none';
              merchantID.parentElement.childNodes[2].innerHTML = merchantID.parentElement.childNodes[2].innerHTML + '<br/><span style=\'color: red;\'>$comment</span>';
            }
          }

          mpay24Active.onchange = function() {
            if(mpay24Active.parentElement.childNodes[2].innerHTML.indexOf('$comment') == -1) {
              document.getElementById('mpay24_mpay24').style.display = 'none';
              mpay24Active.parentElement.childNodes[2].innerHTML = mpay24Active.parentElement.childNodes[2].innerHTML + '<br/><span style=\'color: red;\'>$comment</span>';
            }
          }

          mpay24System.onchange = function() {
            if(mpay24System.parentElement.childNodes[2].innerHTML.indexOf('$comment') == -1) {
              document.getElementById('mpay24_mpay24').style.display = 'none';
              mpay24System.parentElement.childNodes[2].innerHTML = mpay24System.parentElement.childNodes[2].innerHTML + '<br/><span style=\'color: red;\'>$comment</span>';
            }
          }

          mpay24SOAPPass.onchange = function() {
            if(mpay24SOAPPass.parentElement.childNodes[2].innerHTML.indexOf('$comment') == -1) {
              document.getElementById('mpay24_mpay24').style.display = 'none';
              mpay24SOAPPass.parentElement.childNodes[2].innerHTML = mpay24SOAPPass.parentElement.childNodes[2].innerHTML + '<br/><span style=\'color: red;\'>$comment</span>';
            }
          }

          mpay24UseProxy.onchange = function() {
            if(mpay24UseProxy.parentElement.childNodes[2].innerHTML.indexOf('$comment') == -1) {
              document.getElementById('mpay24_mpay24').style.display = 'none';
              mpay24UseProxy.parentElement.childNodes[2].innerHTML = mpay24UseProxy.parentElement.childNodes[2].innerHTML + '<br/><span style=\'color: red;\'>$comment</span>';
            }
          }

          mpay24ProxyPort.onchange = function() {
            if(mpay24ProxyPort.parentElement.childNodes[2].innerHTML.indexOf('$comment') == -1) {
              document.getElementById('mpay24_mpay24').style.display = 'none';
              mpay24ProxyPort.parentElement.childNodes[2].innerHTML = mpay24ProxyPort.parentElement.childNodes[2].innerHTML + '<br/><span style=\'color: red;\'>$comment</span>';
            }
          }

          mpay24ProxyHost.onchange = function() {
            if(mpay24ProxyHost.parentElement.childNodes[2].innerHTML.indexOf('$comment') == -1) {
              document.getElementById('mpay24_mpay24').style.display = 'none';
              mpay24ProxyHost.parentElement.childNodes[2].innerHTML = mpay24ProxyHost.parentElement.childNodes[2].innerHTML + '<br/><span style=\'color: red;\'>$comment</span>';
            }
          }
      }
    </script>";

    return $html;
  }

  /**
   * Return header html for fieldset
   *
   * @param             Varien_Data_Form_Element_Abstract                 $element
   * @return            string
   */
  protected function _getHeaderHtml($element) {
    $html = '';
    $default = !$this->getRequest()->getParam('website') && !$this->getRequest()->getParam('store');

    $elementName = $element->getHtmlId();

    $html = '<div  class="entry-edit-head collapseable" ><a id="'.$element->getHtmlId().'-head" href="#" onclick="Fieldset.toggleCollapse(\''.$element->getHtmlId().'\', \''.$this->getUrl('*/*/state').'\'); return false;">'.$element->getLegend().'</a></div>';
    $html.= '<input id="'.$element->getHtmlId().'-state" name="config_state['.$element->getId().']" type="hidden" value="'.(int)$this->_getCollapseState($element).'" />';
    $html.= '<fieldset class="'.$this->_getFieldsetCss().'" id="'.$element->getHtmlId().'">';
    $html.= '<legend>'.$element->getLegend().'</legend>';

    if ($element->getComment())
      $html .= '<div class="comment">'.$element->getComment().'</div>';

    // field label column
    $html.= '<table cellspacing="0" class="form-list"><colgroup class="label" /><colgroup class="value" />';
    if (!$default)
      $html.= '<colgroup class="use-default" />';

    $html.= '<colgroup class="scope-label" /><colgroup class="" /><tbody>';

    return $html;
  }

  /**
   * Return full css class name for form fieldset
   *
   * @return            string
   */
  protected function _getFieldsetCss() {
    //         $configCss = (string)$this->getGroup()->fieldset_css;
    //         return 'config collapseable'.($configCss ? ' ' . $configCss : '');
  }

  /**
   * Return footer html for fieldset
   * Add extra tooltip comments to elements
   *
   * @param             Varien_Data_Form_Element_Abstract                 $element
   * @return            string
   */
  protected function _getFooterHtml($element) {
    $tooltipsExist = false;
    $html = '</tbody></table>';
    foreach ($element->getSortedElements() as $field)
      if ($field->getTooltip())
        $tooltipsExist = true;
        $html .= sprintf('<div id="row_%s_comment" class="system-tooltip-box" style="display:none;">%s</div>',
            $field->getId(), $field->getTooltip()
        );
    $html .= '</fieldset>' . $this->_getExtraJs($element, $tooltipsExist);
    return $html;
  }

  /**
   * Return js code for fieldset:
   * - observe fieldset rows;
   * - apply collapse;
   *
   * @param             Varien_Data_Form_Element_Abstract                 $element
   * @param             bool                $tooltipsExist                Init tooltips observer or not
   * @return string
   */
  protected function _getExtraJs($element, $tooltipsExist = false) {
    $id = $element->getHtmlId();
    $js = "Fieldset.applyCollapse('{$id}');";
    if ($tooltipsExist)
      $js.= "$$('#{$id} table')[0].addClassName('system-tooltip-wrap');
             $$('#{$id} table tbody tr').each(function(tr) {
                 Event.observe(tr, 'mouseover', function (event) {
                     var relatedTarget = $(event.relatedTarget || event.fromElement);
                     if(relatedTarget && (relatedTarget == this || relatedTarget.descendantOf(this))) {
                         return;
                     }
                     showTooltip(event);
                 });
                 Event.observe(tr, 'mouseout', function (event) {
                     var relatedTarget = $(event.relatedTarget || event.toElement);
                     if(relatedTarget && (relatedTarget == this || relatedTarget.childOf(this))) {
                         return;
                     }
                     hideTooltip(event);
                 });
             });
             $$('#{$id} table')[0].select('input','select').each(function(field) {
                 Event.observe(field, 'focus', function (event) {
                     showTooltip(event);
                 });
                 Event.observe(field, 'blur', function (event) {
                     hideTooltip(event);
                 });
             });
             function showTooltip(event) {
                 var tableHeight = Event.findElement(event, 'table').getStyle('height');
                 var tr = Event.findElement(event, 'tr');
                 var id = tr.id + '_comment';
                 $$('div.system-tooltip-box').invoke('hide');
                 if ($(id)) {
                     $(id).show().setStyle({height : tableHeight});
                     if(document.viewport.getWidth() < 1200) {
                         $(id).addClassName('system-tooltip-small').setStyle({height : 'auto'});
                     } else {
                         $(id).removeClassName('system-tooltip-small');
                     }
                 }
             };
             function hideTooltip(event) {
                 var tr = Event.findElement(event, 'tr');
                 var id = tr.id + '_comment';
                 if ($(id)) {
                     setTimeout(function() { $(id).hide(); }, 1);
                 }
             };";

      return Mage::helper('adminhtml/js')->getScript($js);
    }

  /**
   * Collapsed or expanded fieldset when page loaded?
   *
   * @param             Varien_Data_Form_Element_Abstract                 $element
   * @return            bool
   */
  protected function _getCollapseState($element) {
    if ($element->getExpanded() !== null)
        return 1;

    $extra = Mage::getSingleton('admin/session')->getUser()->getExtra();

    if (isset($extra['configState'][$element->getId()]))
      return $extra['configState'][$element->getId()];

    return false;
  }
}