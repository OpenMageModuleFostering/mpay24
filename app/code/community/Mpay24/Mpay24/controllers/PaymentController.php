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
 * @version             $Id: PaymentController.php 25 2014-06-24 15:33:56Z sapolhei $
 */
include_once "app/code/community/Mpay24/Mpay24/Model/Api/MPay24MagentoShop.php";

class Mpay24_Mpay24_PaymentController extends Mage_Core_Controller_Front_Action {

  protected function _expireAjax() {
    Mage::log('mPAY24 Extension: expireAjax called');
    if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
      $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
      exit;
    }
  }

  /**
  * Redirect to mPAY24 pay page
  */
  public function redirectAction() {
    Mage::log('mPAY24 Extension: redirect action called: redirected to ' . urldecode(Mage::getSingleton('mpay24/session')->getUrl()));
    $this->_redirectUrl(urldecode(Mage::getSingleton('mpay24/session')->getUrl()));
  }

  /**
   * Is called from mPAY24 as Confirmation-URL. Send TRANSACTION-STATUS request to mPAY24
   */
  public function confirmationAction() {
    Mage::log('mPAY24 Extension: confirmation action called for order '.$_REQUEST['TID']);

    $this->getResponse()->setBody("OK: " . MPay24MagentoShop::MAGENTO_VERSION . " - confirmation received");

    $result = $this->getRequest()->getParams();

    $order = Mage::getModel('sales/order');
    $order->loadByIncrementId($result['TID']);

    Mage::log('mPAY24 Extension: RESULT from confirmation action:'."\n");
    Mage::log($result);

    switch($result['OPERATION']) {
      case 'CONFIRMATION':
        $this->getTransactionStatus($result['TID'], true);
        break;
       default:
        break;
    }
  }

  public function indexAction() {
    Mage::log('mPAY24 Extension: index action called');
    $this->loadLayout();
    $this->renderLayout();
  }

  public function paymentAction() {
    Mage::log('mPAY24 Extension: payment action called');
    $this->loadLayout();
    $this->renderLayout();
  }

  /**
  * Is called from mPAY24 as Success-URL. Send TRANSACTION-STATUS request to mPAY24
  */
  public function successAction() {
    Mage::log('mPAY24 Extension: success action called for order '.$_REQUEST['TID']);
    $order = $this->getOrder($_REQUEST['TID']);
    $order->getPayment()->setAdditionalInformation('success', true)->save();
    $this->getTransactionStatus($_REQUEST['TID']);
    $this->_redirect('sales/order/view/order_id/'.$order->getId().'/');
  }

  /**
  * Is called from mPAY24 as Success-URL for not registered customers. Send TRANSACTION-STATUS request to mPAY24
  */
  public function guestsuccessAction() {
    Mage::log('mPAY24 Extension: guest success action called for order '.$_REQUEST['TID']);
    $order = $this->getOrder($_REQUEST['TID']);
    $order->getPayment()->setAdditionalInformation('success', true)->save();
    $this->getTransactionStatus($_REQUEST['TID']);
    $this->_redirect('checkout/onepage/success/');
  }

  /**
  * Is called from mPAY24 as Error-URL. Send TRANSACTION-STATUS request to mPAY24
  */
  public function errorAction() {
    Mage::log('mPAY24 Extension: error action called for order '.$_REQUEST['TID']);
    $order = $this->getOrder($_REQUEST['TID']);
    $order->getPayment()->setAdditionalInformation('error_text', utf8_encode($_REQUEST['ERROR']))->save();
    $order->getPayment()->setAdditionalInformation('error', true)->save();
    
    if($order->canCancel() && $order->getState() != Mage_Sales_Model_Order::STATE_CANCELED && $order->getData('status') != Mage_Sales_Model_Order::STATE_CANCELED)
      $order->cancel($order->getPayment())->save();

    $this->getTransactionStatus($_REQUEST['TID']);

    Mage::getSingleton('mpay24/session')->setParentRedirectUrl('checkout/onepage/');
    $this->getResponse()->setBody($this->getLayout()->createBlock("mpay24/parentRedirect")->toHtml());
  }

  /**
  * Is called from mPAY24 as Cancel-URL. Send TRANSACTION-STATUS request to mPAY24
  */
  public function cancelAction() {
    Mage::log('mPAY24 Extension: cancel action called for order '.$_REQUEST['TID']);
    $order = $this->getOrder($_REQUEST['TID']);
    $order->getPayment()->setAdditionalInformation('cancelButton', true)->save();

    $session = Mage::getSingleton('checkout/session');
    $cart = Mage::getSingleton('checkout/cart');

    $items = $order->getItemsCollection();
    foreach ($items as $item) {
      try {
          $cart->addOrderItem($item,$item->getQty());
      }
      catch (Mage_Core_Exception $e){
        if (Mage::getSingleton('checkout/session')->getUseNotice(true)) {
            Mage::getSingleton('checkout/session')->addNotice($e->getMessage());
        }
        else {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
        }
      }
      catch (Exception $e) {
        Mage::getSingleton('checkout/session')->addException($e,
            Mage::helper('checkout')->__('Cannot add the item to shopping cart.')
        );
      }
    }

    $cart->save();
    
    if($order->canCancel() && $order->getState() != Mage_Sales_Model_Order::STATE_CANCELED && $order->getData('status') != Mage_Sales_Model_Order::STATE_CANCELED)
      $order->cancel($order->getPayment())->save();

    $this->getTransactionStatus($_REQUEST['TID']);

    Mage::getSingleton('mpay24/session')->setParentRedirectUrl('checkout/onepage/');
    $this->getResponse()->setBody($this->getLayout()->createBlock("mpay24/parentRedirect")->toHtml());
  }

  public function getCheckout() {
    Mage::log('mPAY24 Extension: get checkout called');
    return Mage::getSingleton('checkout/session');
  }

  /**
  * Get the order for a tid
  * @param              string              $tid                          The Order ID in the shop
  * @return             Mage_Sales_Model_Order
  */
  public function getOrder($tid=null) {
    Mage::log('mPAY24 Extension: get order called');
    $order = Mage::getSingleton('sales/order');

    if($tid == null)
      return $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
    else
      return $order->loadByIncrementId($tid);
  }

  /**
  * Synchronize the transaction status with mPAY24
  * @param              string              $tid                          The Order ID in the shop
  * @param              bool                $confirmation                 TRUE if the function is called as a result of a mPAY24 confirmation call
  * @return             string
  */
  public function getTransactionStatus($tid=null, $confirmation=false) {
    Mage::log('mPAY24 Extension: getTransactionStatus called for order '.$tid);
    $auth_refund = array("CC", "CB", "MAESTRO", "PAYPAL", "ELV", "PB", "MPASS", "INVOICE", "HP", "SAFETYPAY");
    $updateResult = "";
    $order = $this->getOrder($tid);

    $confirmationCalled = "Payment ";

    if($confirmation)
      $confirmationCalled = "Confirmation URL called: Payment ";


    $incrementId = $order->getData('increment_id');
    $tid = $order->getId();

    if($order->getPayment()) {
      if(!$confirmation || !in_array($this->getRequest()->getClientIp(), explode(',', Mage::getStoreConfig('mpay24/mpay24as/allowed_ips')))) {
        $mPay24MagentoShop = MPay24MagentoShop::getMPay24Api();
        $mPAY24Result = $mPay24MagentoShop->updateTransactionStatus($incrementId);
        $res = $mPAY24Result->getGeneralResponse()->getStatus();
        if($mPAY24Result->getParam('P_TYPE') == 'SOFORT')
          $status = Mage::getStoreConfig('mpay24/mpay24as/sofort_state');
        else
          $status = $mPAY24Result->getParam('TSTATUS');

        if($mPAY24Result->getGeneralResponse()->getStatus() != 'OK' || $mPAY24Result->getParam('APPR_CODE') == '')
           $apprCode = 'N/A';
        else
           $apprCode = $mPAY24Result->getParam('APPR_CODE');
      } else {
        $mPAY24Result = new TransactionStatusResponse("");
        $res = "OK";

        foreach($this->getRequest()->getParams() as $key => $value)
          $mPAY24Result->setParam($key, $value);

        if($mPAY24Result->getParam('P_TYPE') == 'SOFORT')
          $status = Mage::getStoreConfig('mpay24/mpay24as/sofort_state');
        else
          $status = $mPAY24Result->getParam('STATUS');
        $apprCode = $mPAY24Result->getParam('APPR_CODE');
      }

       switch ($res) {
        case "OK":
          if($order->getPayment()->getAdditionalInformation('user_field') == $mPAY24Result->getParam('USER_FIELD')) {
            $updateResult = "The transaction status was succesfully updated!";

            $order->getPayment()->setAdditionalData($mPAY24Result->getParam("P_TYPE"))->save();
            $order->getPayment()->setCcType($mPAY24Result->getParam("P_TYPE") . " => " . $mPAY24Result->getParam('BRAND'))->save();
            $order->getPayment()->setAdditionalInformation('status', true)->save();
            $updateResult .= "\n\nActual status: " . $mPAY24Result->getParam('STATUS ');
            $updateResult .= "\nAmount: " . number_format($mPAY24Result->getParam('PRICE')/100, 2, '.', '') . " " . $mPAY24Result->getParam('CURRENCY');
            $updateResult .= "\nMPAYTID: " . $mPAY24Result->getParam('MPAYTID');
            $updateResult .= "\nAppr_code: " . $apprCode;

            if($order->getIsNotVirtual())
              $s = Mage::getStoreConfig('payment/mpay24/paid_order_status');
            else
              $s = Mage_Sales_Model_Order::STATE_COMPLETE;

            switch($status) {
               case 'RESERVED':
                if($order->getId()){
                   $order->getPayment()->setAdditionalInformation('mpay_tid', $mPAY24Result->getParam('MPAYTID'))->save();
                   $order->getPayment()->setAdditionalInformation('appr_code', $apprCode)->save();
                   $order->getPayment()->setAmountAuthorized($mPAY24Result->getParam('PRICE')/100)->save();
                   $order->getPayment()->setAdditionalInformation('error', false)->save();
                   $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PROCESSING, Mage::helper('mpay24')->__("$confirmationCalled") . Mage::helper('mpay24')->__("RESERVED") . ' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]' . " (" . $this->getRequest()->getClientIp() . ")");
                   $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING)->save();
                   $order->sendNewOrderEmail();

                   if($order->getInvoiceCollection()->count() == 0)
                     if(Mage::getStoreConfig('payment/mpay24/paid_payment_action') == MPay24MagentoShop::PAYMENT_TYPE_SALE)
                       $this->_createInvoice($order, true);
                    else
                       $this->_createInvoice($order);
                }

                $order->save();
                 break;
              case 'BILLED':
                $order->getPayment()->setAdditionalInformation('mpay_tid', $mPAY24Result->getParam('MPAYTID'))->save();
                $order->getPayment()->setAdditionalInformation('appr_code', $apprCode)->save();

                if($order->getInvoiceCollection()->count() == 0) {
                  $order->sendNewOrderEmail();
                   if(in_array($mPAY24Result->getParam('P_TYPE'), MPay24MagentoShop::getAllowedAuth())) {
                     $onlineCapture = false;
                     $mif = true;
                   } else {
                     $onlineCapture = true;
                     $mif = false;
                   }

                  $order->getPayment()->setAmountCharged($mPAY24Result->getParam('PRICE')/100)->save();
                  $order->getPayment()->setAdditionalInformation('error', false)->save();
                  $order->sendOrderUpdateEmail();

                  $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING)->save();

                  if($order->getInvoiceCollection()->count() == 0) {
                    if($confirmation)
                      $this->_createInvoice($order, true, true, $onlineCapture);
                    else
                      $this->_createInvoice($order, true, $mif, $onlineCapture);
                    $order->save();
                  }
                }

                $order->addStatusToHistory($s, Mage::helper('mpay24')->__("$confirmationCalled") . Mage::helper('mpay24')->__("BILLED") .' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]' . " (" . $this->getRequest()->getClientIp() . ")", true)->save();

                $order->save();
                 break;
              case 'CREDITED':
                 if ($order->getTotalOnlineRefunded() == 0.00) {
                   $creditmemo = Mage::getModel('sales/service_order', $order)
                                               ->prepareCreditmemo()
                                               ->setPaymentRefundDisallowed(true)
                                               ->setAutomaticallyCreated(true)
                                               ->register();

                  $creditmemo->addComment(Mage::helper('mpay24')->__("Credit memo has been created automatically through of MI/F crediting!"));
                   $creditmemo->save();

                   $order->getPayment()->refund($creditmemo)->save();
                   $order->sendOrderUpdateEmail();
                }

                 $order->getPayment()->setAdditionalInformation('mpay_tid', $mPAY24Result->getParam('MPAYTID'))->save();
                 $order->getPayment()->setAdditionalInformation('appr_code', $apprCode)->save();

                 $this->_addChildTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND,
                                             Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);

                $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_COMPLETE, Mage::helper('mpay24')->__("$confirmationCalled") . Mage::helper('mpay24')->__("CREDITED") . ' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]' . " (" . $this->getRequest()->getClientIp() . ")")->save();
                 $order->save();

                 break;
               case 'SUSPENDED':
                 if($order->getId()) {
                  $order->getPayment()->setAdditionalInformation('mpay_tid', $mPAY24Result->getParam('MPAYTID'))->save();
                   $order->getPayment()->setAdditionalInformation('appr_code', $apprCode)->save();
                   $order->getPayment()->setAdditionalInformation('error', false)->save();
                   $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper('mpay24')->__("$confirmationCalled") . Mage::helper('mpay24')->__("SUSPENDED") . ' [ '.$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]' . " (" . $this->getRequest()->getClientIp() . ")");
                 }
                 $order->save();
                 break;
              case 'REVERSED':
                if($order->getState() != Mage_Sales_Model_Order::STATE_CANCELED)
                  foreach ($order->getInvoiceCollection() as $orderInvoice) {
                    $order->getPayment()->setAdditionalInformation('MIFReverse', true)->save();
                    $order->getPayment()->void($orderInvoice)->save();
                  }

                 $order->getPayment()->setAdditionalInformation('mpay_tid', $mPAY24Result->getParam('MPAYTID'))->save();
                 $order->getPayment()->setAdditionalInformation('appr_code', $apprCode)->save();
                 $order->sendOrderUpdateEmail();
                $order->addStatusToHistory($order->getState(), Mage::helper('mpay24')->__("$confirmationCalled") . Mage::helper('mpay24')->__("REVERSED") .' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]' . " (" . $this->getRequest()->getClientIp() . ")", true)->save();
                $order->save();
                break;
              case 'ERROR':
                $order->getPayment()->setAdditionalInformation('mpay_tid', $mPAY24Result->getParam('MPAYTID'))->save();
                $order->getPayment()->setAdditionalInformation('appr_code', $apprCode)->save();
                $order->getPayment()->setAdditionalInformation('error', true)->save();

                $order->addStatusToHistory($order->getStatus(), Mage::helper('mpay24')->__("$confirmationCalled") . Mage::helper('mpay24')->__("ERROR") . " " . $order->getPayment()->getAdditionalInformation('error_text') . " (" . $this->getRequest()->getClientIp() . ")");
                $order->save();

                break;
              default:
                break;
            }

            if(in_array($mPAY24Result->getParam('TSTATUS'), array("RESERVED", "BILLED", "CREDITED")) && Mage::getStoreConfig('mpay24/mpay24/billingAddressMode') == "ReadWrite") {
              if(!$mPAY24Result->getParam('BILLING_ADDR') || $mPAY24Result->getParam('BILLING_ADDR') == '') {
                $billingAddressMode = new Mage_Core_Model_Config();
                $billingAddressMode->saveConfig('mpay24/mpay24/billingAddressMode', 'ReadOnly', 'default', "");

                $request = $this->getRequest();

                $template = $this->_initTemplate('id');

                $template->setTemplateSubject(Mage::helper('mpay24')->__("ATTENTION!"))
                           ->setTemplateCode('ADDR_MODE')
                           ->setTemplateText('<table>
                               <thead>
                               <tr>
                               <th>'.Mage::helper('mpay24')->__("The billing address las not returned by mPAY24!").'</th>
                               </tr>
                               </thead>
                               <tbody>
                               <tr>
                               <td>
                               <p>
                               {{var reason}}
                               </p>
                               </td>
                               </tr>
                               </tbody>
                               </table>');

                // The Id you just marked...
                if (!$template->getId())
                  $template->setTemplateType(Mage_Core_Model_Email_Template::TYPE_HTML);

                if($request->getParam('_change_type_flag')) {
                  $template->setTemplateType(Mage_Core_Model_Email_Template::TYPE_TEXT);
                  $template->setTemplateStyles('');
                }

                $template->save();


                // Define the sender, here we query Magento default email (in the configuration)
                // For customer support email, use : 'trans_email/ident_support/...'
                $sender = Array('name' => Mage::getStoreConfig('trans_email/ident_general/name'),
                                 'email' => Mage::getStoreConfig('trans_email/ident_general/email'));

                // Set you store
                // This information may be taken from the current logged in user
                $store = Mage::app()->getStore();

                // In this array, you set the variables you use in your template
                $vars = Array(
                         'reason' => Mage::helper('mpay24')->__("ATTENTION! - It is possible that the billing address for the order ID '") .
                         $order->getIncrementId() . Mage::helper('mpay24')->__("was changed by the customer, but not in your shop! The billing address mode was set back to 'ReadOnly'! If you want to use the mode 'ReadWrite', the variable 'BILLING_ADDR' has to be activated for the 'TRANSACTIONSTATUS' request by mPAY24. Please contact (including your merchant ID '") . Mage::getStoreConfig('mpay24/mpay24as/merchantid')
                         . Mage::helper('mpay24')->__("') mPAY24 (support@mpay24.com)!"));

                // You don't care about this...
                $translate  = Mage::getSingleton('core/translate');

                // Send your email
                Mage::getModel('core/email_template')->sendTransactional($template->getId(),
                                                                         $sender,
                                                                         Mage::getStoreConfig('trans_email/ident_general/email'),
                                                                         Mage::getStoreConfig('trans_email/ident_general/name'),
                                                                         $vars,
                                                                         $store->getId());

                // You don't care as well
                $translate->setTranslateInline(true);

                $template->delete();
              } else {
                $billingAddress = new DOMDocument();
                $billingAddress->loadXML(trim($mPAY24Result->getParam('BILLING_ADDR')));
                $billingAddress->saveXML();

                $name = $billingAddress->getElementsByTagName("Name")->item(0)->nodeValue;
                $street = $billingAddress->getElementsByTagName("Street")->item(0)->nodeValue;
                $street2 = $billingAddress->getElementsByTagName("Street2")->item(0)->nodeValue;
                $zip = $billingAddress->getElementsByTagName("Zip")->item(0)->nodeValue;
                $city = $billingAddress->getElementsByTagName("City")->item(0)->nodeValue;
                $countryCode = $billingAddress->getElementsByTagName("Country")->item(0)->getAttribute("code");

                //Build billing address for customer, for checkout
                if(strpos($name, " "))
                  $_billing_address = array (
                                             'firstname' => substr($name, 0, strpos($name, " ")),
                                             'lastname' => substr($name, strpos($name, " ")+1),
                                             'street' => array (
                                                 '0' => $street,
                                                 '1' => $street2,
                                             ),

                                       'city' => $city,
                                       'postcode' => $zip,
                                       'country_id' => $countryCode,
                                       'prefix' => '',
                                       'middlename' => '',
                                       'suffix' => '',
                                       'company' => '',
                                       'region' => '',
                                       'region_id' => '',
                                       'telephone' => '',
                                       'fax' => ''
                                   );
                else
                  $_billing_address = array (
                                             'firstname' => $name,
                                             'lastname' => '',
                                             'street' => array (
                                                 '0' => $street,
                                                 '1' => $street2,
                                             ),

                                       'city' => $city,
                                       'postcode' => $zip,
                                       'country_id' => $countryCode,
                                       'prefix' => '',
                                       'middlename' => '',
                                       'suffix' => '',
                                       'company' => '',
                                       'region' => '',
                                       'region_id' => '',
                                       'telephone' => '',
                                       'fax' => ''
                                   );

                if(Mage::helper('customer')->isLoggedIn()) {
                  $addressAlreadySaved = false;

                  foreach(Mage::getSingleton('customer/session')->getCustomer()->getAddressesCollection() as $a)
                    if($a->getFirstname() == substr($name, 0, strpos($name, " ")) && $a->getLastname() == substr($name, strpos($name, " ")+1)
                                                       && $a->getStreet1() == $street && $a->getStreet2() == $street2 && $a->getCity() == $city
                                                       && $a->getPostcode() == $zip && $a->getCountry() == $countryCode)
                      $addressAlreadySaved = true;

                  if(!$addressAlreadySaved) {
                    $billAddress = Mage::getModel('customer/address');
                    $billAddress->setData($_billing_address)
                                 ->setCustomerId(Mage::getSingleton('customer/session')->getCustomer()->getId())
                                 ->setIsDefaultBilling('0')
                                 ->setIsDefaultShipping('0')
                                 ->setSaveInAddressBook('1');

                    $billAddress->save();
                  }
                }

                // set Billing Address
                $addressId  = $order->getBillingAddress()->getId();
                $address    = Mage::getModel('sales/order_address')->load($addressId);
                $address->addData($_billing_address);
                $address->implodeStreetAddress()->save();

                $order->setBillingAddress($address);
                $order->save();
              }
            }
          } else {
            if($order->canCancel() && $order->getState() != Mage_Sales_Model_Order::STATE_CANCELED && $order->getData('status') != Mage_Sales_Model_Order::STATE_CANCELED)
              $order->cancel($order->getPayment())->save();

            $order->addStatusToHistory($order->getStatus(), Mage::helper('mpay24')->__("The transaction was canceled by the customer"), true)->save();
            $order->getPayment()->setAdditionalInformation('status', true)->save();
            $order->getPayment()->setAdditionalInformation('mpay_tid', 'N/A')->save();
            $order->getPayment()->setAdditionalInformation('appr_code', 'N/A')->save();
          }
          break;
        case "ERROR":
          if($mPAY24Result->getGeneralResponse()->getReturnCode() == 'NOT_FOUND' && $order->getPayment()->getAdditionalInformation('cancelButton'))
            $updateResult = 'The order was canceled by the customer';
          else
            $updateResult = 'The transaction was not found!';

//           if($order->canCancel() && $order->getState() != Mage_Sales_Model_Order::STATE_CANCELED && $order->getData('status') != Mage_Sales_Model_Order::STATE_CANCELED)
//             $order->cancel($order->getPayment())->save();

          $order->addStatusToHistory($order->getStatus(), Mage::helper('mpay24')->__($updateResult), true)->save();
          $order->getPayment()->setAdditionalInformation('status', true)->save();
          $order->getPayment()->setAdditionalInformation('mpay_tid', 'N/A')->save();
          $order->getPayment()->setAdditionalInformation('appr_code', 'N/A')->save();
          break;
        default:
          break;
      }
    }
    return $updateResult;
  }

  protected function _initTemplate($idFieldName = 'template_id') {
    Mage::log('mPAY24 Extension: initTemplate called');
    $this->_title($this->__('System'))->_title($this->__('Transactional Emails'));

    $id = (int)$this->getRequest()->getParam($idFieldName);
    $model = Mage::getModel('adminhtml/email_template');

    if ($id)
      $model->load($id);

    if (!Mage::registry('email_template'))
      Mage::register('email_template', $model);

    if (!Mage::registry('current_email_template'))
      Mage::register('current_email_template', $model);

    return $model;
  }

  public function _createInvoice($order, $capture=false, $mif=false, $onlineCapture=true) {
    Mage::log('mPAY24 Extension: create invoice called');
    if ($order->canInvoice()) {
      $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

      if (!$invoice->getTotalQty())
        Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));

      if($capture && $onlineCapture)
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);

      $invoice->register();
      Mage::log("mPAY24 Extension: Invoice registered!");

      Mage::getModel('core/resource_transaction')
                      ->addObject($invoice)
                      ->addObject($invoice->getOrder())
                      ->save();

      $order->save();

      if($capture)
        foreach ($order->getInvoiceCollection() as $orderInvoice)
          if ($order->getTotalPaid() == 0.00) {
            $orderInvoice->pay();
            $orderInvoice->save();

            if($mif)
              $order->getPayment()->setAdditionalInformation('MIFClear', true)->save();

            $order->getPayment()->capture($orderInvoice)->save();
          }

      $invoice->sendEmail(true, '');
      return true;
    } else
      Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
  }

  /**
  * Add transaction to payment with defined type
  *
  * @param              string              $typeTarget                   The type of the current transaction ('Authorization', 'Capture', 'Refund', etc)
  * @param              string              $typeParent                   The type of the parent transaction ('Authorization', 'Capture', 'Refund', etc)
  */
  protected function _addChildTransaction($typeTarget, $typeParent = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH) {
    Mage::log('mPAY24 Extension: addChildTransaction called');
    $payment                = $this->getOrder()->getPayment();
    $parentTransactionId    = $this->getOrder()->getIncrementId();

    if ($typeParent != Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH)
      $parentTransactionId .= '-' . $typeParent;
    else
      $payment->setIsTransactionClosed(false);

    $parentTransaction = $payment->getTransaction($parentTransactionId);

    if ($parentTransaction) {
      $payment->setParentTransactionId($parentTransactionId)
              ->setTransactionId($parentTransactionId . '-' . $typeTarget)
              ->addTransaction($typeTarget);

      if ($this->getOrder()->getTotalDue() < .0001)
        $parentTransaction->setIsClosed(true)->save();
    }
  }
}