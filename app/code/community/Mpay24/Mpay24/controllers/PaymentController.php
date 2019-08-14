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
 * @version             $Id: PaymentController.php 6413 2015-07-14 12:50:34Z anna $
 */
include_once Mage::getBaseDir('code')."/community/Mpay24/Mpay24/Model/Api/MPay24MagentoShop.php";

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
    Mage::log('mPAY24 Extension (confirmationAction): called for order '.$this->getRequest()->getParam('TID'));

    Mage::log('mPAY24 Extension (confirmationAction): RESULT from confirmation:');
    Mage::log($this->getRequest()->getParams());
    
    if($this->getRequest()->getParam('OPERATION') == 'CONFIRMATION') {
      Mage::log($this->getTransactionStatus($this->getRequest()->getParam('TID'), true));
      
      //confirmation evaluation
      $order = Mage::getModel('sales/order');
      $order->loadByIncrementId($this->getRequest()->getParam('TID'));

      Mage::log('mPAY24 Extension (confirmationAction): Confirmation processing DONE! Confirmed: '.$order->getPayment()->getAdditionalInformation('confirmed').'');
      $this->getResponse()->setBody("OK: " . MAGENTO_VERSION . " - confirmation received");
    } else {
      Mage::throwException(Mage::helper('core')->__('ERROR (mPAY24 Extension): Confirmation parameters are not as expected!'));
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
    Mage::log('mPAY24 Extension (successAction): called for order '.$this->getRequest()->getParam('TID'));
    $order = $this->getOrder($this->getRequest()->getParam('TID'));
    
    if(!$this->getRequest()->getParam('TIME'))
      $this->getRequest()->setParam("TIME", 0);
    
    if($order->getPayment()->getAdditionalInformation('confirmed') == "" && $this->getRequest()->getParam('TIME') <= 4) {
      Mage::log('mPAY24 Extension (successAction): Confirmation is processing - wait for (max) 20 seconds...');
      sleep(5);
      $this->_redirectUrl(Mage::getUrl(MPay24MagentoShop::SUCCESS_URL,array('_secure' => true, '_query' => "TID=" . $this->getRequest()->getParam('TID') . "&TIME=" . $this->getRequest()->getParam('TIME')+1)));
    }
    
    $order = $this->getOrder($this->getRequest()->getParam('TID'));
    if($order->getPayment()->getAdditionalInformation('confirmed') == "")
      $this->getTransactionStatus($this->getRequest()->getParam('TID'));
    
    $order->getPayment()->setAdditionalInformation('success', true)->save();
    
    $this->_redirect('sales/order/view/order_id/'.$order->getId().'/');
  }

  /**
  * Is called from mPAY24 as Success-URL for not registered customers. Send TRANSACTION-STATUS request to mPAY24
  */
  public function guestsuccessAction() {
    Mage::log('mPAY24 Extension: guest success action called for order '.$this->getRequest()->getParam('TID'));
    $order = $this->getOrder($this->getRequest()->getParam('TID'));
    
    if(!$this->getRequest()->getParam('TIME'))
      $this->getRequest()->setParam("TIME", 0);
    
    if($order->getPayment()->getAdditionalInformation('confirmed') == "" && $this->getRequest()->getParam('TIME') <= 4) {
      Mage::log('mPAY24 Extension (guestsuccessAction): Confirmation is processing - wait for (max) 20 seconds...');
      sleep(5);
      $this->_redirectUrl(Mage::getUrl(MPay24MagentoShop::GUEST_SUCCESS_URL,array('_secure' => true, '_query' => "TID=" . $this->getRequest()->getParam('TID') . "&TIME=" . $this->getRequest()->getParam('TIME')+1)));
    }
    
    $order = $this->getOrder($this->getRequest()->getParam('TID'));
    if($order->getPayment()->getAdditionalInformation('confirmed') == "")
      $this->getTransactionStatus($this->getRequest()->getParam('TID'));
    
    $order->getPayment()->setAdditionalInformation('success', true)->save();
    
    $this->_redirect('checkout/onepage/success/');
  }

  /**
  * Is called from mPAY24 as Error-URL. Send TRANSACTION-STATUS request to mPAY24
  */
  public function errorAction() {
    Mage::log('mPAY24 Extension (): error action called for order '.$this->getRequest()->getParam('TID'));
    $order = $this->getOrder($this->getRequest()->getParam('TID'));
    
    $order->getPayment()->setAdditionalInformation('error_text', utf8_encode($this->getRequest()->getParam('ERROR')))->save();
    $order->getPayment()->setAdditionalInformation('error', true)->save();
    
    if($order->canCancel() && $order->getState() != Mage_Sales_Model_Order::STATE_CANCELED && $order->getData('status') != Mage_Sales_Model_Order::STATE_CANCELED)
      $order->cancel($order->getPayment())->save();

    if(!$this->getRequest()->getParam('TIME'))
      $this->getRequest()->setParam("TIME", 0);
    
    if($order->getPayment()->getAdditionalInformation('confirmed') == "" && $this->getRequest()->getParam('TIME') <= 4) {
      Mage::log('mPAY24 Extension (errorAction): Confirmation is processing - wait for (max) 20 seconds...');
      sleep(5);
      $this->_redirectUrl(Mage::getUrl(MPay24MagentoShop::ERROR_URL,array('_secure' => true, '_query' => "TID=" . $this->getRequest()->getParam('TID') . "&TIME=" . ($this->getRequest()->getParam('TIME')+1) . ",ERROR=" . utf8_encode($this->getRequest()->getParam('ERROR')))));
    }
    
    $order = $this->getOrder($this->getRequest()->getParam('TID'));
    if($order->getPayment()->getAdditionalInformation('confirmed') == "")
      $this->getTransactionStatus($this->getRequest()->getParam('TID'));
        
    Mage::getSingleton('mpay24/session')->setParentRedirectUrl('checkout/'.Mage::getStoreConfig('mpay24/mpay24/checkout_template').'/');
    $this->getResponse()->setBody($this->getLayout()->createBlock("mpay24/parentRedirect")->toHtml());
  }

  /**
  * Is called from mPAY24 as Cancel-URL. Send TRANSACTION-STATUS request to mPAY24
  */
  public function cancelAction() {
    Mage::log('mPAY24 Extension: cancel action called for order '.$this->getRequest()->getParam('TID'));
    $order = $this->getOrder($this->getRequest()->getParam('TID'));
    
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

    if(!$this->getRequest()->getParam('TIME'))
      $this->getRequest()->setParam("TIME", 0);
    
    if($order->getPayment()->getAdditionalInformation('confirmed') == "" && $this->getRequest()->getParam('TIME') <= 4) {
      Mage::log('mPAY24 Extension (errorAction): Confirmation is processing - wait for (max) 20 seconds...');
      sleep(5);
      $this->_redirectUrl(Mage::getUrl(MPay24MagentoShop::ERROR_URL,array('_secure' => true, '_query' => "TID=" . $this->getRequest()->getParam('TID') . "&TIME=" . ($this->getRequest()->getParam('TIME')+1))));
    }
    
    $order = $this->getOrder($this->getRequest()->getParam('TID'));
    if($order->getPayment()->getAdditionalInformation('confirmed') == "")
      $this->getTransactionStatus($this->getRequest()->getParam('TID'));
    
    Mage::getSingleton('mpay24/session')->setParentRedirectUrl('checkout/'.Mage::getStoreConfig('mpay24/mpay24/checkout_template').'/');
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
    $arrBacktrace = debug_backtrace();
    Mage::log('mPAY24 Extension ('.$arrBacktrace[2]['function'].'): get order called');
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
    $arrBacktrace = debug_backtrace();
    Mage::log('mPAY24 Extension ('.$arrBacktrace[1]['function'].'): getTransactionStatus called for order '.$tid);
    $orderHistoryText = "";
    $order = $this->getOrder($tid);

    $paymentHistoryText = "Payment ";

    if($confirmation)
      $paymentHistoryText = "Confirmation URL called: Payment ";

    if($order->getPayment()) {
      if(!$confirmation || !in_array($this->getRequest()->getClientIp(), explode(',', Mage::getStoreConfig('mpay24/mpay24as/allowed_ips')))) {
        if(!in_array($this->getRequest()->getClientIp(), explode(',', Mage::getStoreConfig('mpay24/mpay24as/allowed_ips'))))
          Mage::log("mPAY24 Extension: The IP '".$this->getRequest()->getClientIp()."', which is calling the confirmation URL is not white-listed! The allowed IPs are " . Mage::getStoreConfig('mpay24/mpay24as/allowed_ips'));
        
        $mPay24MagentoShop = MPay24MagentoShop::getMPay24Api();
        $mPAY24Result = $mPay24MagentoShop->updateTransactionStatus($tid);
        $res = $mPAY24Result->getGeneralResponse()->getStatus();

        if($res != 'OK' || $mPAY24Result->getParam('APPR_CODE') == '')
           $apprCode = 'N/A';
        else
           $apprCode = $mPAY24Result->getParam('APPR_CODE');
      } else {
        $mPAY24Result = new TransactionStatusResponse("");
        $res = "OK";

        foreach($this->getRequest()->getParams() as $key => $value) {
          if($key == 'STATUS')
            $mPAY24Result->setParam('TSTATUS', $value);
          else
            $mPAY24Result->setParam($key, $value);
        }

        $apprCode = $mPAY24Result->getParam('APPR_CODE');
      }
      
      $order->getPayment()->setAdditionalInformation('error', false)->save();
      
      //set APPR_CODE and MPAYTID
      $order->getPayment()->setAdditionalInformation('mpay_tid', $mPAY24Result->getParam('MPAYTID'))->save();
      $order->getPayment()->setAdditionalInformation('appr_code', $apprCode)->save();
      
      $status = $mPAY24Result->getParam('TSTATUS');

      $order->getPayment()->setAdditionalInformation('confirmed', $status)->save();

      if($mPAY24Result->getParam('BRAND') == 'AMEX')
        $this->setAmexData($order, $mPAY24Result);
        
      switch ($res) {
        case "OK":
          if($order->getPayment()->getAdditionalInformation('user_field') == $mPAY24Result->getParam('USER_FIELD')) {
            $orderHistoryText = "The transaction status was successfully updated!";

            $order->getPayment()->setAdditionalData($mPAY24Result->getParam("P_TYPE"))->save();
            $order->getPayment()->setCcType($mPAY24Result->getParam("P_TYPE") . " => " . $mPAY24Result->getParam('BRAND'))->save();
            $order->getPayment()->setAdditionalInformation('status', true)->save();
            $orderHistoryText .= "\n\t\t\tActual status: " . $mPAY24Result->getParam('TSTATUS');
            $orderHistoryText .= "\n\t\t\tAmount: " . $mPAY24Result->getParam('CURRENCY') . " " . number_format($mPAY24Result->getParam('PRICE')/100, 2, '.', '');
            $orderHistoryText .= "\n\t\t\tMPAYTID: " . $mPAY24Result->getParam('MPAYTID');
            $orderHistoryText .= "\n\t\t\tAppr_code: " . $apprCode;

            if($order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
              $order->sendNewOrderEmail()->save();
              Mage::log("mPAY24 Extension: New order mail sent!!!");
            }

            if($status != 'ERROR' && $order->getState() == Mage_Sales_Model_Order::STATE_CANCELED && Mage::getStoreConfig('mpay24/mpay24/notifyForFalseNOK')) {
              $orderHistoryText .= $this->sendEmailForNOKTransactions($orderHistoryText);
            }
            
            $this->transactionStatusHandler($order, $mPAY24Result, $paymentHistoryText);

            if(in_array($mPAY24Result->getParam('TSTATUS'), array("RESERVED", "BILLED", "CREDITED")) && Mage::getStoreConfig('mpay24/mpay24/billingAddressMode') == "ReadWrite")
              if(!$mPAY24Result->getParam('BILLING_ADDR') || $mPAY24Result->getParam('BILLING_ADDR') == '')
                $this->sendEmailForBillingAddr();
              else
                $this->setNewBillingAddr($order, $mPAY24Result);
          } else {
            if($order->canCancel() && $order->getState() != Mage_Sales_Model_Order::STATE_CANCELED && $order->getData('status') != Mage_Sales_Model_Order::STATE_CANCELED)
              $order->cancel($order->getPayment())->save();

            $order->addStatusToHistory($order->getStatus(), Mage::helper('mpay24')->__("The transaction was canceled by the customer"), true)->save();
            $order->getPayment()->setAdditionalInformation('status', true)->save();
            $order->getPayment()->setAdditionalInformation('mpay_tid', 'N/A')->save();
          }
          break;
        case "ERROR":
          if($mPAY24Result->getGeneralResponse()->getReturnCode() == 'NOT_FOUND' && $order->getPayment()->getAdditionalInformation('cancelButton'))
            $orderHistoryText = 'The order was canceled by the customer';
          else
            $orderHistoryText = 'The transaction was not found!';

          $order->addStatusToHistory($order->getStatus(), Mage::helper('mpay24')->__($orderHistoryText), true)->save();
          $order->getPayment()->setAdditionalInformation('status', true)->save();
          $order->getPayment()->setAdditionalInformation('mpay_tid', 'N/A')->save();
          break;
        default:
          break;
      }
    } else
      Mage::throwException(Mage::helper('core')->__('ERROR (mPAY24 Extension): There is no payment for the order ' . $tid));
    
    return $orderHistoryText;
  }

  public function _createInvoice($order) {
    $arrBacktrace = debug_backtrace();
    Mage::log('mPAY24 Extension ('.$arrBacktrace[2]['function'].'): create invoice called');
    if ($order->canInvoice()) {
      $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

      if (!$invoice->getTotalQty())
        Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));

      $order->getPayment()->setAdditionalInformation('confirmation', true);
      $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
      $order->getPayment()->setAdditionalInformation('confirmation', false);
      
      $invoice->register();

      $arrBacktrace = debug_backtrace();
      Mage::log("mPAY24 Extension (".$arrBacktrace[2]['function']."): Invoice registered!");

      Mage::getModel('core/resource_transaction')
                      ->addObject($invoice)
                      ->addObject($invoice->getOrder())
                      ->save();

      $order->save();

      foreach ($order->getInvoiceCollection() as $orderInvoice)
        if ($order->getTotalPaid() == 0.00) {
          $orderInvoice->pay();
          $orderInvoice->save();

          $order->getPayment()->capture($orderInvoice)->save();
        }

      $invoice->sendEmail(true, '');
      return $invoice;
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
  
  private function setAmexData($order, $mPAY24Result) {
    $addr_ver = Mage::helper('mpay24')->__("The 'AMEX_ADDR_VER' parameter was not returned!");
    
    if($mPAY24Result->getParam('AMEX_ADDR_VER')) {
      switch ($mPAY24Result->getParam('AMEX_ADDR_VER')) {
        case "Y":
          $addr_ver = Mage::helper('mpay24')->__("Yes, Customer Address and Postal Code are both correct.");
          break;
        case "N":
          $addr_ver = Mage::helper('mpay24')->__("No, Customer Address and Postal Code are both incorrect.");
          break;
        case "A":
          $addr_ver = Mage::helper('mpay24')->__("Customer Address only correct.");
          break;
        case "Z":
          $addr_ver = Mage::helper('mpay24')->__("Customer Postal Code only correct.");
          break;
        case "U":
          $addr_ver = Mage::helper('mpay24')->__("Information unavailable.");
          break;
        case "S":
          $addr_ver = Mage::helper('mpay24')->__("SE not allowed AAV function.");
          break;
        case "R":
          $addr_ver = Mage::helper('mpay24')->__("System unavailable; retry.");
          break;
        case "L":
          $addr_ver = Mage::helper('mpay24')->__("Customer Name and Postal Code match.");
          break;
        case "M":
          $addr_ver = Mage::helper('mpay24')->__("Customer Name, Address and Postal Code match.");
          break;
        case "O":
          $addr_ver = Mage::helper('mpay24')->__("Customer Name and Address match.");
          break;
        case "K":
          $addr_ver = Mage::helper('mpay24')->__("Customer Name matches.");
          break;
        case "D":
          $addr_ver = Mage::helper('mpay24')->__("Customer Name incorrect, Postal Code matches.");
          break;
        case "E":
          $addr_ver = Mage::helper('mpay24')->__("Customer Name incorrect, Address and Postal Code match.");
          break;
        case "F":
          $addr_ver = Mage::helper('mpay24')->__("Customer Name incorrect, Address matches.");
          break;
        case "W":
          $addr_ver = Mage::helper('mpay24')->__("No, Customer Name, Address and Postal Code are all incorrect.");
          break;
        default:
          $addr_ver = Mage::helper('mpay24')->__("Unknown returned value:") . " '" . $mPAY24Result->getParam('AMEX_ADDR_VER') . "'";
          break;
      }
    
    }
    $order->getPayment()->setAdditionalInformation('amex_addr_ver', $addr_ver)->save();
    
    $cid_ver = Mage::helper('mpay24')->__("The 'AMEX_CVC_VER' parameter was not returned!");
    
    if($mPAY24Result->getParam('AMEX_CVC_VER')) {
      switch ($mPAY24Result->getParam('AMEX_CVC_VER')) {
        case "Y":
          $cid_ver = Mage::helper('mpay24')->__("CID/4DBC/4CSC matched.");
          break;
        case "N":
          $cid_ver = Mage::helper('mpay24')->__("CID/4DBC/4CSC did not match.");
          break;
        case "U":
          $cid_ver = Mage::helper('mpay24')->__("CID/4DBC/4CSC was not checked.");
          break;
        default:
          $cid_ver = Mage::helper('mpay24')->__("Unknown returned value:") . " '" . $mPAY24Result->getParam('AMEX_CVC_VER') . "'";
          break;
      }
    }
    
    $order->getPayment()->setAdditionalInformation('amex_cid_ver', $cid_ver)->save();
    
    
    if($mPAY24Result->getParam('AMEX_CVC_VER'))
      $order->getPayment()->setAdditionalInformation('amex_cid_ver', $mPAY24Result->getParam('AMEX_CVC_VER'))->save();
  }
  
  private function setBillpayData($order, $mPAY24Result) {
    if($mPAY24Result->getParam('REFERENCE'))
      $order->getPayment()->setAdditionalInformation('reference', $mPAY24Result->getParam('REFERENCE'))->save();
    
    if($mPAY24Result->getParam('ACCT_HOLDER'))
      $order->getPayment()->setAdditionalInformation('acct_holder', $mPAY24Result->getParam('ACCT_HOLDER'))->save();
     
    if($mPAY24Result->getParam('ACCT_NUMBER'))
      $order->getPayment()->setAdditionalInformation('acct_number', $mPAY24Result->getParam('ACCT_NUMBER'))->save();
     
    if($mPAY24Result->getParam('BANK_CODE'))
      $order->getPayment()->setAdditionalInformation('bank_code', $mPAY24Result->getParam('BANK_CODE'))->save();
     
    if($mPAY24Result->getParam('BANK_NAME'))
      $order->getPayment()->setAdditionalInformation('bank_name', $mPAY24Result->getParam('BANK_NAME'))->save();
     
    if($mPAY24Result->getParam('REFERENCE'))
      $order->getPayment()->setAdditionalInformation('reference', $mPAY24Result->getParam('REFERENCE'))->save();
  	;
  }
  
  private function setNewBillingAddr($order, $mPAY24Result) {
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
  
  private function sendEmailForNOKTransactions($orderHistoryText) {
    $template = $this->_initTemplate('id');
    
    $template->setTemplateSubject(Mage::helper('mpay24')->__("ATTENTION!"))
    ->setTemplateCode('FALSE_NOK')
    ->setTemplateText('<table>
                               <thead>
                               <tr>
                               <th>'.Mage::helper('mpay24')->__("A SUCCESSFUL confirmation occured for an already canceled order!").'</th>
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
    
    if($this->getRequest()->getParam('_change_type_flag')) {
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
        'reason' => Mage::helper('mpay24')->__("ATTENTION! - It is possible that the payment for the order ID '") .
        $order->getIncrementId() . Mage::helper('mpay24')->__("' was SUCCESSFUL, although the order is set as 'Canceled'! Please check in the mPAY24 Merchant Interface (https://www.mpay24.com) whether the amount was BILLED!"));
    
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
    
    return $orderHistoryText . "\nThe order could not be billed!";
  }
  
  private function sendEmailForBillingAddr() {
    $billingAddressMode = new Mage_Core_Model_Config();
    $billingAddressMode->saveConfig('mpay24/mpay24/billingAddressMode', 'ReadOnly', 'default', "");
    
    $request = $this->getRequest();
    
    $template = $this->_initTemplate('id');
    
    $template->setTemplateSubject(Mage::helper('mpay24')->__("ATTENTION!"))
    ->setTemplateCode('ADDR_MODE')
    ->setTemplateText('<table>
                               <thead>
                               <tr>
                               <th>'.Mage::helper('mpay24')->__("The billing address was not returned by mPAY24!").'</th>
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
        $order->getIncrementId() . Mage::helper('mpay24')->__("' was changed by the customer, but not in your shop! The billing address mode was set back to 'ReadOnly'! If you want to use the mode 'ReadWrite', the variable 'BILLING_ADDR' has to be activated for the 'TRANSACTIONSTATUS' request by mPAY24. Please contact (including your merchant ID '") . Mage::getStoreConfig('mpay24/mpay24as/merchantid')
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
  }

  private function transactionStatusHandler($order, $mPAY24Result, $paymentHistoryText) {
    switch($mPAY24Result->getParam('TSTATUS')) {
      case 'RESERVED':
        $this->setBillpayData($order, $mPAY24Result);
    
        $order->getPayment()->authorize(false, $mPAY24Result->getParam('PRICE')/100)->save();
        $order->sendOrderUpdateEmail(true, Mage::helper('mpay24')->__("$paymentHistoryText") . Mage::helper('mpay24')->__("RESERVED") . ' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]')->save();
        break;
      case 'BILLED':
        $this->setBillpayData($order, $mPAY24Result);
    
        if (!$order->getEmailSent())
          $order->sendNewOrderEmail()
          ->setIsCustomerNotified(true)
          ->save();
    
        if($order->getInvoiceCollection()->count() == 0)
          $invoice = $this->_createInvoice($order);
    
        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, 'processing', Mage::helper('mpay24')->__("$paymentHistoryText") . Mage::helper('mpay24')->__("BILLED") .' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]' . " (" . $this->getRequest()->getClientIp() . ")", true);
    
        $order->save();
        break;
      case 'CREDITED':
        $this->setBillpayData($order, $mPAY24Result);
    
        if ($order->getTotalOnlineRefunded() == 0.00) {
          $creditmemo = Mage::getModel('sales/service_order', $order)
          ->prepareCreditmemo()
          ->setPaymentRefundDisallowed(true)
          ->setAutomaticallyCreated(true)
          ->register();
    
          $creditmemo->addComment(Mage::helper('mpay24')->__("Credit memo has been created automatically through of MI/F crediting!"));
          $creditmemo->save();
    
          $order->getPayment()->refund($creditmemo)->save();
        }
    
    
        $order->addStatusHistoryComment(Mage::helper('mpay24')->__("$paymentHistoryText") . Mage::helper('mpay24')->__("CREDITED") .' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]' . " (" . $this->getRequest()->getClientIp() . ")")->save();
        $order->sendOrderUpdateEmail(true, Mage::helper('mpay24')->__("$paymentHistoryText") . Mage::helper('mpay24')->__("CREDITED") .' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]')->save();
        $order->save();
        break;
      case 'SUSPENDED':
        $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper('mpay24')->__("$paymentHistoryText") . Mage::helper('mpay24')->__("SUSPENDED") . ' [ '.$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]' . " (" . $this->getRequest()->getClientIp() . ")");
        $order->save();
        break;
      case 'REVERSED':
        if($order->getState() != Mage_Sales_Model_Order::STATE_CANCELED)
          foreach ($order->getInvoiceCollection() as $orderInvoice) {
            $order->getPayment()->setAdditionalInformation('MIFReverse', true)->save();
            $order->getPayment()->void($orderInvoice)->save();
          }
    
        $order->addStatusToHistory($order->getState(), Mage::helper('mpay24')->__("$paymentHistoryText") . Mage::helper('mpay24')->__("REVERSED") .' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]' . " (" . $this->getRequest()->getClientIp() . ")", true)->save();
        $order->sendOrderUpdateEmail(true, Mage::helper('mpay24')->__("$paymentHistoryText") . Mage::helper('mpay24')->__("REVERSED") .' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]')->save();
        $order->save();
        break;
      case 'ERROR':
        $this->setBillpayData($order, $mPAY24Result);
    
        $order->getPayment()->setAdditionalInformation('error', true)->save();
    
        $order->addStatusToHistory($order->getStatus(), Mage::helper('mpay24')->__("$paymentHistoryText") . Mage::helper('mpay24')->__("ERROR") . " " . $order->getPayment()->getAdditionalInformation('error_text') . " (" . $this->getRequest()->getClientIp() . ")");
        $order->sendOrderUpdateEmail(true, Mage::helper('mpay24')->__("$paymentHistoryText") . Mage::helper('mpay24')->__("ERROR") . " " . $order->getPayment()->getAdditionalInformation('error_text'))->save();
        $order->save();
        break;
      default:
        break;
    }
  }
}