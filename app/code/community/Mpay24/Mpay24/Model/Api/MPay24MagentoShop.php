<?php
/**
 * @author              support@mpay24.com
 * @version             $Id: MPay24MagentoShop.php 6413 2015-07-14 12:50:34Z anna $
 * @filesource          test.php
 * @license             http://ec.europa.eu/idabc/eupl.html EUPL, Version 1.1
 */

include_once(Mage::getBaseDir('code')."/community/Mpay24/Mpay24/Model/Api/MPay24Shop.php");
define("MAGENTO_VERSION", "Magento v " . Mage::getVersion() . " Module v 2.0.0 ");

class MPay24MagentoShop extends MPay24Shop {

  const PAYMENT_TYPE_AUTH = 'authorize';
  const PAYMENT_TYPE_SALE = 'authorize_capture';

  const SUCCESS_URL = 'mpay24/payment/success';
  const GUEST_SUCCESS_URL = 'mpay24/payment/guestsuccess';
  const ERROR_URL = 'mpay24/payment/error';
  const CONFIRMATION_URL = 'mpay24/payment/confirmation';

  const CANCEL_URL = 'mpay24/payment/cancel';
  
  var $tid;
  var $price;
  var $order;
  var $ps;
  var $type;
  var $brand;
  var $bic;
  var $token;

  public static function getAllowedAuth() {
    return array("CB", "CC", "ELV", "BILLPAY", "PAYPAL", "PB", "PSC", "SAFETYPAY");
  }

  private function getOrder($tid=null) {
    $order = Mage::getSingleton('sales/order');

    if($tid == null)
      return $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
    else
      return $order->loadByIncrementId($tid);
  }

  function updateTransaction($tid, $args, $shippingConfirmed) {}

  function updateStatusToCancel($tid) {
    $order = $this->getOrder($tid);
    Mage::log('mPAY24 Extension: updateStatusToCancel called for order '.$order->getData('increment_id'));
    $confirmationCalled = "Payment ";

    $status = "";

    $incrementId = $order->getData('increment_id');
    $tid = $order->getId();

    if($order->getPayment()) {
      $mPay24MagentoShop = $this->getMPay24Api();
      $mPAY24Result = $mPay24MagentoShop->updateTransactionStatus($incrementId);

      if($mPAY24Result->getGeneralResponse()->getStatus() != 'OK' || $mPAY24Result->getParam('APPR_CODE') == '')
        $apprCode = 'N/A';
      else
        $apprCode = $mPAY24Result->getParam('APPR_CODE');

      switch ($mPAY24Result->getGeneralResponse()->getStatus()) {
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

            $status = $mPAY24Result->getParam('TSTATUS');

            switch($status) {
              case 'RESERVED':
                if($order->getId()) {
                  $order->getPayment()->setAdditionalInformation('mpay_tid', $mPAY24Result->getParam('MPAYTID'))->save();
                  $order->getPayment()->setAdditionalInformation('appr_code', $apprCode)->save();
                  $order->getPayment()->setAmountAuthorized($mPAY24Result->getParam('PRICE')/100)->save();
                  $order->getPayment()->setAdditionalInformation('error', false)->save();
                  $order->addStatusToHistory($order->getStatus(), Mage::helper('mpay24')->__("$confirmationCalled") . Mage::helper('mpay24')->__("RESERVED") . ' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]');
                  $order->sendNewOrderEmail();

                  if($order->getInvoiceCollection()->count() == 0)
//                     if(Mage::getStoreConfig('payment/mpay24/paid_payment_action') == MPay24MagentoShop::PAYMENT_TYPE_SALE) {
//                       $order->getPayment()->setAdditionalInformation('mpay24AutoClearing', true)->save();
//                       $this->_createInvoice($order, true);
//                     } else
                      $this->_createInvoice($order);
                }

                $order->save();
                break;
              case 'BILLED':
                if($order->getInvoiceCollection()->count() == 0) {
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

                  if($confirmation) {
                    $order->getPayment()->setAdditionalInformation('mpay24AutoClearing', false)->save();
                    $this->_createInvoice($order, true, true, $onlineCapture);
                  } else
                    $this->_createInvoice($order, true, $mif, $onlineCapture);

                  $order->save();
                }

                $order->getPayment()->setAdditionalInformation('mpay_tid', $mPAY24Result->getParam('MPAYTID'))->save();
                $order->getPayment()->setAdditionalInformation('appr_code', $apprCode)->save();

                if($order->getIsNotVirtual())
                  $s = Mage_Sales_Model_Order::STATE_PROCESSING;
                else
                  $s = Mage_Sales_Model_Order::STATE_COMPLETE;

                $order->addStatusToHistory($s, Mage::helper('mpay24')->__("$confirmationCalled") . Mage::helper('mpay24')->__("BILLED") .' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]', true)->save();
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

                $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_COMPLETE, Mage::helper('mpay24')->__("$confirmationCalled") . Mage::helper('mpay24')->__("CREDITED") . ' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]')->save();
                $order->save();

                break;
              case 'SUSPENDED':
                if($order->getId()) {
                  $order->getPayment()->setAdditionalInformation('mpay_tid', $mPAY24Result->getParam('MPAYTID'))->save();
                  $order->getPayment()->setAdditionalInformation('appr_code', $apprCode)->save();
                  $order->getPayment()->setAdditionalInformation('error', false)->save();
                  $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper('mpay24')->__("$confirmationCalled") . Mage::helper('mpay24')->__("SUSPENDED") . ' [ '.$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]');
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
                $order->addStatusToHistory($order->getState(), Mage::helper('mpay24')->__("$confirmationCalled") . Mage::helper('mpay24')->__("REVERSED") .' [ ' . $mPAY24Result->getParam('CURRENCY') . " " .$order->formatPriceTxt($mPAY24Result->getParam('PRICE')/100).' ]', true)->save();
                $order->save();
                break;
              case 'ERROR':
                $order->getPayment()->setAdditionalInformation('mpay_tid', $mPAY24Result->getParam('MPAYTID'))->save();
                $order->getPayment()->setAdditionalInformation('appr_code', $apprCode)->save();
                $order->getPayment()->setAdditionalInformation('error', true)->save();

                if($order->canCancel() && $order->getState() != Mage_Sales_Model_Order::STATE_CANCELED &&
                    $order->getData('status') != Mage_Sales_Model_Order::STATE_CANCELED)
                  $order->cancel($order->getPayment())->save();

                $order->addStatusToHistory($order->getStatus(), Mage::helper('mpay24')->__("$confirmationCalled") . Mage::helper('mpay24')->__("ERROR") . " " . $order->getPayment()->getAdditionalInformation('error_text'));
                $order->save();
                break;
              default:
                break;
            }
          } else
          $status = "SUSPENDED";
          break;
        case "ERROR":
          if($mPAY24Result->getReturnCode() == 'NOT_FOUND' && $order->getPayment()->getAdditionalInformation('cancelButton'))
            $updateResult = 'The order was canceled by the customer';
          else {
            $status = 'SUSPENDED';
            $updateResult = Mage::helper('mpay24')->__("The transaction was not found!");
          }

          if($order->canCancel() && $order->getState() != Mage_Sales_Model_Order::STATE_CANCELED &&
              $order->getData('status') != Mage_Sales_Model_Order::STATE_CANCELED && $status != 'SUSPENDED')
            $order->cancel($order->getPayment())->save();

          $order->addStatusToHistory($order->getStatus(), Mage::helper('mpay24')->__($updateResult), true)->save();
          $order->getPayment()->setAdditionalInformation('status', true)->save();
          $order->getPayment()->setAdditionalInformation('mpay_tid', 'N/A')->save();
          $order->getPayment()->setAdditionalInformation('appr_code', 'N/A')->save();
          break;
        default:
          $status = 'SUSPENDED';
          break;
      }
    }
    return $status;
  }

  function getTransaction($tid) {
    $transaction = new Transaction($tid);
    $order = Mage::getSingleton('sales/order');
    $order->loadByIncrementId($tid);
    $transaction->PRICE = number_format(floatval($order->getPayment()->getAmountAuthorized()), 2, '.', '');
    $transaction->CURRENCY = $order->getBaseCurrencyCode();
    $transaction->MPAYTID = $order->getPayment()->getAdditionalInformation('mpay_tid');
    return $transaction;
  }

  function createProfileOrder($tid) {}
  
  function createBackend2BackendOrder($transaction, $paymentType) {
    $b2bOrder = new ORDER();
    
    $b2bOrder->AcceptPayment->tid = $transaction->TID;
    $b2bOrder->AcceptPayment->merchantID = Mage::getStoreConfig('mpay24/mpay24as/merchantid');
    $b2bOrder->AcceptPayment->pType = $paymentType;
     
    $b2bOrder->AcceptPayment->payment->amount = $transaction->PRICE*100;
    $b2bOrder->AcceptPayment->payment->currency = $this->order->getOrderCurrencyCode();

    if($paymentType == "EPS") {
      $b2bOrder->AcceptPayment->payment->brand = $this->brand;
      $b2bOrder->AcceptPayment->payment->bic = $this->bic;
    } else if($paymentType == "TOKEN")
      $b2bOrder->AcceptPayment->payment->token = $this->token;

    $b2bOrder->AcceptPayment->order->description = Mage::getStoreConfig('mpay24/mpay24/description');
    $b2bOrder->AcceptPayment->order->userField = MAGENTO_VERSION.$transaction->TID.'_'.date('Y-m-d');

    if(number_format($this->order->getData('discount_amount'),2,'.','') !== '0.00')
      $b2bOrder->AcceptPayment->order->shoppingCart->discount = number_format($this->order->getData('discount_amount'), 2, '.', '')*100;
    if(number_format($this->order->getData('shipping_amount'),2,'.','') !== '0.00')
      if(Mage::getStoreConfig('tax/cart_display/shipping') == 2 || Mage::getStoreConfig('tax/cart_display/shipping') == 3)
        $b2bOrder->AcceptPayment->order->shoppingCart->shippingCosts = number_format($this->order->getShippingInclTax(), 2, '.', '')*100;
      else
        $b2bOrder->AcceptPayment->order->shoppingCart->shippingCosts = number_format($this->order->getData('shipping_amount'), 2, '.', '')*100;

    $total_tax = 0;
    if(number_format($this->order->getData('tax_amount'),2,'.','') !== '0.00') {
      $array = $this->order->getFullTaxInfo();
      $taxInfo = array();
      
      foreach($array as $a){
        $taxArray = $a;
        $total_tax += number_format($taxArray['amount'],2,'.','');
      }
    }
    
    if($total_tax !== 0)
      $b2bOrder->AcceptPayment->order->shoppingCart->tax = $total_tax*100;

    $linecount = 0;
    foreach($this->order->getAllItems() as $_item) {
      if(Mage::getStoreConfig('mpay24/mpay24/show_free_products') == 1 ||
          (Mage::getStoreConfig('mpay24/mpay24/show_free_products') == 0 && number_format(($_item->getData('price')*1),2,'.','') != '0.00')) {
        $linecount++;
        
        $b2bOrder->AcceptPayment->order->shoppingCart->item($linecount)->number = $linecount;
        $b2bOrder->AcceptPayment->order->shoppingCart->item($linecount)->productNr = $this->xmlentities($_item->getData('sku'));
        $b2bOrder->AcceptPayment->order->shoppingCart->item($linecount)->description = $this->xmlentities($_item->getData('name'));
        $b2bOrder->AcceptPayment->order->shoppingCart->item($linecount)->package = "";
        $b2bOrder->AcceptPayment->order->shoppingCart->item($linecount)->quantity = (int)$_item->getQtyOrdered();
        if(Mage::getStoreConfig('tax/cart_display/price') == 2 || Mage::getStoreConfig('tax/cart_display/price') == 3)
          $b2bOrder->AcceptPayment->order->shoppingCart->item($linecount)->amount = number_format($_item->getPriceInclTax()*1,2,'.','')*100;
        else
          $b2bOrder->AcceptPayment->order->shoppingCart->item($linecount)->amount = number_format(($_item->getData('price')*1),2,'.','')*100;
      }
    }

    $billingCountry = "";
    $billingCountryCode = "";
    $shippingCountry = "";
    $shippingCountryCode = "";
    
    foreach(Mage::app()->getLocale()->getOptionCountries() as $c)
      if ($c['value'] == $this->order->getBillingAddress()->getCountry()) {
        $billingCountry = $c['label'];
        $billingCountryCode = $c['value'];
        break;
      }
    
    if($this->order->getShippingAddress())
      foreach(Mage::app()->getLocale()->getOptionCountries() as $c)
        if ($c['value'] == $this->order->getShippingAddress()->getCountry()) {
          $shippingCountry = $c['label'];
          $shippingCountryCode = $c['value'];
          break;
        }

      $b2bOrder->AcceptPayment->order->billing->mode = "READONLY";
    $b2bOrder->AcceptPayment->order->billing->name = $this->xmlentities(substr($this->order->getBillingAddress()->getName(),0,50));
    
    $billingAdress = $this->xmlentities($this->order->getBillingAddress()->getStreetFull());
    
    $billingStreet = $this->splitAdress($billingAdress);
    
    if(isset($billingStreet[0]) && is_array($billingStreet[0])) {
      $b2bOrder->AcceptPayment->order->billing->street = $billingStreet[0]['Street'];
      $b2bOrder->AcceptPayment->order->billing->street2 = $billingStreet[1]['Street2'];
    } else {
      $b2bOrder->AcceptPayment->order->billing->street = $billingStreet['Street'];
    
      if(isset($billingStreet['Street2']))
        $b2bOrder->AcceptPayment->order->billing->street2 = $billingStreet['Street2'];
    }
    
    $b2bOrder->AcceptPayment->order->billing->zip = substr($this->xmlentities($this->order->getBillingAddress()->getPostcode()),0,50);
    $b2bOrder->AcceptPayment->order->billing->city = substr($this->xmlentities($this->order->getBillingAddress()->getCity()),0,50);
    $b2bOrder->AcceptPayment->order->billing->countryCode = $this->xmlentities($billingCountryCode);
    $b2bOrder->AcceptPayment->order->billing->email = substr($this->xmlentities($this->order->getBillingAddress()->getEmail()),0,50);

    if($this->order->getShippingAddress()) {
      $b2bOrder->AcceptPayment->order->shipping->mode = "READONLY";
      $b2bOrder->AcceptPayment->order->shipping->name = $this->xmlentities(substr($this->order->getBillingAddress()->getName(),0,50));
      
      $shippingAdress = $this->xmlentities($this->order->getShippingAddress()->getStreetFull());
      
      $shippingStreet = $this->splitAdress($shippingAdress);
      
      if(isset($shippingStreet[0]) && is_array($shippingStreet[0])) {
        $b2bOrder->AcceptPayment->order->shipping->street = $shippingStreet[0]['Street'];
        $b2bOrder->AcceptPayment->order->shipping->street2 = $shippingStreet[1]['Street2'];
      } else {
        $b2bOrder->AcceptPayment->order->shipping->street = $shippingStreet['Street'];
      
        if(isset($shippingStreet['Street2']))
          $b2bOrder->AcceptPayment->order->shipping->street2 = $shippingStreet['Street2'];
      }
      
      $b2bOrder->AcceptPayment->order->shipping->zip = substr($this->xmlentities($this->order->getShippingAddress()->getPostcode()),0,50);
      $b2bOrder->AcceptPayment->order->shipping->city = substr($this->xmlentities($this->order->getShippingAddress()->getCity()),0,50);
      $b2bOrder->AcceptPayment->order->shipping->countryCode = $this->xmlentities($shippingCountryCode);
      $b2bOrder->AcceptPayment->order->shipping->email = substr($this->xmlentities($this->order->getShippingAddress()->getEmail()),0,50);
    }

    $lang = explode('_', Mage::getStoreConfig('general/locale/code'));
    
    if(in_array(strtoupper($lang[0]), array("BG", "CS", "DE", "EN", "FR", "HR", "IT")))
      $b2bOrder->AcceptPayment->language = strtoupper($lang[0]);
    else
      $b2bOrder->AcceptPayment->language = "EN";
  
    if(Mage::helper('customer')->isLoggedIn())
      $b2bOrder->AcceptPayment->successURL = Mage::getUrl(MPay24MagentoShop::SUCCESS_URL,array('_secure' => true, '_query' => "TID=" . substr($this->order->getIncrementId(),0,32) ));
    else
      $b2bOrder->AcceptPayment->successURL = Mage::getUrl(MPay24MagentoShop::GUEST_SUCCESS_URL,array('_secure' => true, '_query' => "tid=" . substr($this->order->getIncrementId(),0,32) ));
    
    $b2bOrder->AcceptPayment->errorURL = Mage::getUrl(MPay24MagentoShop::ERROR_URL,array('_secure' => true, '_query' => "TID=" . substr($this->order->getIncrementId(),0,32) ));
    $b2bOrder->AcceptPayment->confirmationURL = Mage::getUrl(MPay24MagentoShop::CONFIRMATION_URL,array('_secure' => true));

    if(Mage::getStoreConfig('mpay24/mpay24as/debug') == 1)
      Mage::log($b2bOrder->toXML(), null, "mpay24/xmls/".$transaction->TID."_b2b.xml",true);
    
    return $b2bOrder;
  }
  
  function createFinishExpressCheckoutOrder($tid, $s, $a, $c) {}

  function write_log($operation, $info_to_log) {
    Mage::log("$operation : $info_to_log", null, "mpay24/mPAY24.log",true);
  }

  function createSecret($tid, $amount, $currency, $timeStamp) {}
  function getSecret($tid) {}

  function createTransaction() {
    $transaction = new Transaction($this->tid);
    $transaction->PRICE = $this->price;
    return $transaction;
  }

  function createMDXI($transaction) {
    $mdxi = new ORDER();

    $mdxi->Order->setStyle(Mage::getStoreConfig('mpay24/mpay24/style'));
    $mdxi->Order->setLogoStyle(Mage::getStoreConfig('mpay24/mpay24/logostyle'));
    $mdxi->Order->setPageHeaderStyle(Mage::getStoreConfig('mpay24/mpay24/pageheaderstyle'));
    $mdxi->Order->setPageCaptionStyle(Mage::getStoreConfig('mpay24/mpay24/pagecaptionstyle'));
    $mdxi->Order->setPageStyle(Mage::getStoreConfig('mpay24/mpay24/pagestyle'));
    $mdxi->Order->setInputFieldsStyle(Mage::getStoreConfig('mpay24/mpay24/inputfieldsstyle'));
    $mdxi->Order->setDropDownListsStyle(Mage::getStoreConfig('mpay24/mpay24/dropdownlistsstyle'));
    $mdxi->Order->setButtonsStyle(Mage::getStoreConfig('mpay24/mpay24/buttonsstyle'));
    $mdxi->Order->setErrorsStyle(Mage::getStoreConfig('mpay24/mpay24/errorsstyle'));
    $mdxi->Order->setSuccessTitleStyle(Mage::getStoreConfig('mpay24/mpay24/successtitlestyle'));
    $mdxi->Order->setErrorTitleStyle(Mage::getStoreConfig('mpay24/mpay24/errortitlestyle'));
    $mdxi->Order->setFooterStyle(Mage::getStoreConfig('mpay24/mpay24/footerstyle'));

    $this->order->getPayment()->setAdditionalInformation('user_field', MAGENTO_VERSION.$transaction->TID.'_'.date('Y-m-d'))->save();
    
    //validates IPv4
    $validIPv4 = filter_var($this->order->getRemoteIp(), FILTER_VALIDATE_IP,FILTER_FLAG_IPV4);
    
    //validates IPv6
    $validIPv6 = filter_var($this->order->getRemoteIp(), FILTER_VALIDATE_IP,FILTER_FLAG_IPV6);
        
    if($validIPv4)
      $mdxi->Order->ClientIP = $this->order->getRemoteIp();
    
    $mdxi->Order->UserField = MAGENTO_VERSION.$transaction->TID.'_'.date('Y-m-d');
    $mdxi->Order->Tid = $transaction->TID;

    $lang = explode('_', Mage::getStoreConfig('general/locale/code'));

    if(in_array(strtoupper($lang[0]), array("BG", "CS", "DE", "EN", "FR", "HR", "IT")))
      $mdxi->Order->TemplateSet->setLanguage(strtoupper($lang[0]));
    else
      $mdxi->Order->TemplateSet->setLanguage("EN");
    
    $mdxi->Order->TemplateSet->setCSSName("MODERN");

    if($this->ps) {
      $this->order->getPayment()->setCcType($this->type . " => " . $this->brand)->save();
      $mdxi->Order->PaymentTypes->setEnable('true');

      $mdxi->Order->PaymentTypes->Payment(1)->setType($this->type);

      if($this->type != $this->brand)
        $mdxi->Order->PaymentTypes->Payment(1)->setBrand($this->brand);
    } else {
      $firstPS = Mage::getStoreConfig('mpay24/mpay24/ps_1');
      $allPS = true;

      for($i=2; $i<=Mage::getStoreConfig('mpay24/mpay24/payments_count'); $i++)
        if($firstPS != Mage::getStoreConfig('mpay24/mpay24/ps_'.$i)) {
          $allPS = false;
          break;
        }

      if(!$allPS && Mage::getStoreConfig('mpay24/mpay24/payments_active') != 'false') {
        $mdxi->Order->PaymentTypes->setEnable('true');

        $allPaymentsArray = unserialize(Mage::getStoreConfig('mpay24/mpay24/active_payments'));
        $c = 1;
        $k = 1;
        foreach($allPaymentsArray as $id => $payment) {
          if(Mage::getStoreConfig("mpay24/mpay24/ps_$k") == '1') {
            $mdxi->Order->PaymentTypes->Payment($c)->setType($payment['P_TYPE']);

            if($payment['P_TYPE'] != $payment['BRAND'])
              $mdxi->Order->PaymentTypes->Payment($c)->setBrand($payment['BRAND']);

            $c++;
          }

          $k++;
        }
      }
    }

    $conf = explode(',',Mage::getStoreConfig('mpay24/mpay24/sc_row'));

    $mdxi->Order->ShoppingCart->setStyle(Mage::getStoreConfig('mpay24/mpay24/sc_style'));
    $mdxi->Order->ShoppingCart->setHeader(Mage::getStoreConfig('mpay24/mpay24/sc_header'));
    $mdxi->Order->ShoppingCart->setHeaderStyle(Mage::getStoreConfig('mpay24/mpay24/sc_headerstyle'));
    $mdxi->Order->ShoppingCart->setCaptionStyle(Mage::getStoreConfig('mpay24/mpay24/sc_captionstyle'));

    if(in_array('Number',$conf)) {
      $mdxi->Order->ShoppingCart->setNumberHeader(Mage::getStoreConfig('mpay24/mpay24/sc_numberheader'));
      $mdxi->Order->ShoppingCart->setNumberStyle(Mage::getStoreConfig('mpay24/mpay24/sc_numberstyle'));
    }
    if(in_array('ProductNr',$conf)) {
      $mdxi->Order->ShoppingCart->setProductNrHeader(Mage::getStoreConfig('mpay24/mpay24/sc_productnrheader'));
      $mdxi->Order->ShoppingCart->setProductNrStyle(Mage::getStoreConfig('mpay24/mpay24/sc_productnrstyle'));
    }
    if(in_array('Description',$conf)) {
      $mdxi->Order->ShoppingCart->setDescriptionHeader(Mage::getStoreConfig('mpay24/mpay24/sc_descriptionheader'));
      $mdxi->Order->ShoppingCart->setDescriptionStyle(Mage::getStoreConfig('mpay24/mpay24/sc_descriptionstyle'));
    }
    if(in_array('Package',$conf)) {
      $mdxi->Order->ShoppingCart->setPackageHeader(Mage::getStoreConfig('mpay24/mpay24/sc_packageheader'));
      $mdxi->Order->ShoppingCart->setPackageStyle(Mage::getStoreConfig('mpay24/mpay24/sc_packagestyle'));
    }
    if(in_array('Quantity',$conf)) {
      $mdxi->Order->ShoppingCart->setQuantityHeader(Mage::getStoreConfig('mpay24/mpay24/sc_quantityheader'));
      $mdxi->Order->ShoppingCart->setQuantityStyle(Mage::getStoreConfig('mpay24/mpay24/sc_quantitystyle'));
    }
    if(in_array('ItemPrice',$conf)) {
      $mdxi->Order->ShoppingCart->setItemPriceHeader(Mage::getStoreConfig('mpay24/mpay24/sc_itempriceheader'));
      $mdxi->Order->ShoppingCart->setItemPriceStyle(Mage::getStoreConfig('mpay24/mpay24/sc_itempricestyle'));
    }
    if(in_array('Price',$conf)) {
      $mdxi->Order->ShoppingCart->setPriceHeader(Mage::getStoreConfig('mpay24/mpay24/sc_priceheader'));
      $mdxi->Order->ShoppingCart->setPriceStyle(Mage::getStoreConfig('mpay24/mpay24/sc_pricestyle'));
    }

    $mdxi->Order->ShoppingCart->Description = Mage::getStoreConfig('mpay24/mpay24/description');

    $style1 = Mage::getStoreConfig('mpay24/mpay24/item_style1');
    $style2 = Mage::getStoreConfig('mpay24/mpay24/item_style2');
    $ret = "";
    $linecount = 0;

    foreach($this->order->getAllItems() as $_item) {
      if(Mage::getStoreConfig('mpay24/mpay24/show_free_products') == 1 ||
        (Mage::getStoreConfig('mpay24/mpay24/show_free_products') == 0 && number_format(($_item->getData('price')*1),2,'.','') != '0.00')) {

        $linecount++;
        $style = ($linecount % 2== 1) ? $style1 : $style2;

        if(in_array('Number',$conf)) {
          $mdxi->Order->ShoppingCart->Item($linecount)->Number = $linecount;
          $mdxi->Order->ShoppingCart->Item($linecount)->Number->setStyle($style);
        }
        if(in_array('ProductNr',$conf)) {
          $mdxi->Order->ShoppingCart->Item($linecount)->ProductNr = $this->xmlentities($_item->getData('sku'));
          $mdxi->Order->ShoppingCart->Item($linecount)->ProductNr->setStyle($style);
        }
        if(in_array('Description',$conf)) {
          $mdxi->Order->ShoppingCart->Item($linecount)->Description = $this->xmlentities($_item->getData('name'));
          $mdxi->Order->ShoppingCart->Item($linecount)->Description->setStyle($style);
        }
        if(in_array('Package',$conf)) {
          $mdxi->Order->ShoppingCart->Item($linecount)->Package = "";
          $mdxi->Order->ShoppingCart->Item($linecount)->Package->setStyle($style);
        }
        if(in_array('Quantity',$conf)) {
          $mdxi->Order->ShoppingCart->Item($linecount)->Quantity = (int)$_item->getQtyOrdered();
          $mdxi->Order->ShoppingCart->Item($linecount)->Quantity->setStyle($style);
        }

        if(Mage::getStoreConfig('tax/cart_display/price') == 2 || Mage::getStoreConfig('tax/cart_display/price') == 3) {
          if(in_array('ItemPrice',$conf)) {
            $mdxi->Order->ShoppingCart->Item($linecount)->ItemPrice = number_format($_item->getPriceInclTax()*1,2,'.','');
            $mdxi->Order->ShoppingCart->Item($linecount)->ItemPrice->setStyle($style);
            $mdxi->Order->ShoppingCart->Item($linecount)->ItemPrice->setTax(number_format(number_format(($_item->getData('price')*1),2,'.','') * number_format($_item->getTaxPercent(),2,'.','')/100,2,'.',''));
          }
        } else {
          if(in_array('ItemPrice',$conf)) {
            $mdxi->Order->ShoppingCart->Item($linecount)->ItemPrice = number_format(($_item->getData('price')*1),2,'.','');
            $mdxi->Order->ShoppingCart->Item($linecount)->ItemPrice->setStyle($style);
          }
        }
        
        if(Mage::getStoreConfig('tax/cart_display/price') == 2 || Mage::getStoreConfig('tax/cart_display/price') == 3) {
          if(in_array('Price',$conf)) {
            $mdxi->Order->ShoppingCart->Item($linecount)->Price = number_format(($_item->getPriceInclTax()*1),2,'.','') * (int)$_item->getQtyOrdered();
            $mdxi->Order->ShoppingCart->Item($linecount)->Price->setStyle($style);
          }
        } else {
          if(in_array('Price',$conf)) {
            $mdxi->Order->ShoppingCart->Item($linecount)->Price = number_format(($_item->getData('price')*1),2,'.','') * (int)$_item->getQtyOrdered();
            $mdxi->Order->ShoppingCart->Item($linecount)->Price->setStyle($style);
          }
        }
      }
    }

    $mdxi->Order->ShoppingCart->SubTotal->setHeader(Mage::getStoreConfig('mpay24/mpay24/subtotal_header'));
    $mdxi->Order->ShoppingCart->SubTotal->setHeaderStyle(Mage::getStoreConfig('mpay24/mpay24/subtotal_headerstyle'));
    $mdxi->Order->ShoppingCart->SubTotal->setStyle(Mage::getStoreConfig('mpay24/mpay24/subtotal_style'));

    if(Mage::getStoreConfig('tax/cart_display/subtotal') == 2 || Mage::getStoreConfig('tax/cart_display/subtotal') == 3)
      $mdxi->Order->ShoppingCart->SubTotal = number_format($this->order->getSubtotalInclTax(),2,'.','');
    else
      $mdxi->Order->ShoppingCart->SubTotal = number_format($this->order->getData('subtotal'),2,'.','');

    if(number_format($this->order->getData('discount_amount'),2,'.','') !== '0.00') {
      $mdxi->Order->ShoppingCart->Discount->setHeader($this->order->getData('discount_description'));
      $mdxi->Order->ShoppingCart->Discount->setHeaderStyle(Mage::getStoreConfig('mpay24/mpay24/discount_headerstyle'));
      $mdxi->Order->ShoppingCart->Discount->setStyle(Mage::getStoreConfig('mpay24/mpay24/discount_style'));
      $mdxi->Order->ShoppingCart->Discount = number_format($this->order->getData('discount_amount'), 2, '.', '');
    }

    if(number_format($this->order->getData('shipping_amount'),2,'.','') !== '0.00') {
      if(Mage::getStoreConfig('tax/cart_display/shipping') == 2 || Mage::getStoreConfig('tax/cart_display/shipping') == 3)
        $mdxi->Order->ShoppingCart->ShippingCosts(1, number_format($this->order->getShippingInclTax(), 2, '.', ''));
      else
        $mdxi->Order->ShoppingCart->ShippingCosts(1, number_format($this->order->getData('shipping_amount'), 2, '.', ''));
      
      $mdxi->Order->ShoppingCart->ShippingCosts(1)->setHeader($this->order->getData('shipping_description'));
      $mdxi->Order->ShoppingCart->ShippingCosts(1)->setHeaderStyle(Mage::getStoreConfig('mpay24/mpay24/shipping_costs_headerstyle'));
      $mdxi->Order->ShoppingCart->ShippingCosts(1)->setStyle(Mage::getStoreConfig('mpay24/mpay24/shipping_costs_style'));
    }
    
    $t=1;
    if($this->order->getPaymentCharge() > 0) {
      if($this->order->getPaymentChargeType() == "percent") {
        $mdxi->Order->ShoppingCart->Tax(1, number_format($this->order->getData('subtotal')*$this->order->getBasePaymentCharge()/100,2,'.',''));
        $mdxi->Order->ShoppingCart->Tax(1)->setHeader(Mage::helper('mpay24')->__("Payment charge") . " (" . number_format($this->order->getBasePaymentCharge(),2,'.','') . "%)");
      } else {
        $mdxi->Order->ShoppingCart->Tax(1, number_format($this->order->getPaymentCharge(),2,'.',''));
        $mdxi->Order->ShoppingCart->Tax(1)->setHeader(Mage::helper('mpay24')->__("Payment charge") . " (" . Mage::helper('mpay24')->__("Absolute value") . ")");
      }
    
      $mdxi->Order->ShoppingCart->Tax(1)->setHeaderStyle(Mage::getStoreConfig('mpay24/mpay24/tax_headerstyle'));
      $mdxi->Order->ShoppingCart->Tax(1)->setStyle(Mage::getStoreConfig('mpay24/mpay24/tax_style'));
      $t=2;
    } elseif($this->order->getPaymentCharge() < 0) {
      if($this->order->getPaymentChargeType() == "percent") {
        $mdxi->Order->ShoppingCart->Discount(1, number_format($this->order->getData('subtotal')*$this->order->getBasePaymentCharge()/100,2,'.',''));
        $mdxi->Order->ShoppingCart->Discount(1)->setHeader(Mage::helper('mpay24')->__("Payment dicount") . " (" . number_format($this->order->getBasePaymentCharge(),2,'.','') . "%)");
      } else {
        $mdxi->Order->ShoppingCart->Discount(1, number_format($this->order->getPaymentCharge(),2,'.',''));
        $mdxi->Order->ShoppingCart->Discount(1)->setHeader(Mage::helper('mpay24')->__("Payment discount") . " (" . Mage::helper('mpay24')->__("Absolute value") . ")");
      }
      
      $mdxi->Order->ShoppingCart->Discount(1)->setHeaderStyle(Mage::getStoreConfig('mpay24/mpay24/discount_headerstyle'));
      $mdxi->Order->ShoppingCart->Discount(1)->setStyle(Mage::getStoreConfig('mpay24/mpay24/discount_style'));
    }

    if(number_format($this->order->getData('tax_amount'),2,'.','') !== '0.00') {
      $array = $this->order->getFullTaxInfo();
      $taxInfo = array();

      foreach($array as $a){
        $taxArray = $a;

        foreach(array_keys($taxArray) as $taxKey)
          $taxInfo[$taxKey] = $taxArray[$taxKey];

        if(Mage::getStoreConfig('tax/cart_display/price') == 2 || Mage::getStoreConfig('tax/cart_display/price') == 3) {
          if(substr(Mage::getStoreConfig('general/locale/code'), 0, 2) == 'de')
            $inklText = "inkl. ";
           else
             $inklText = "incl. ";
         } else
            $inklText = "";

        $mdxi->Order->ShoppingCart->Tax($t, number_format($taxArray['amount'],2,'.',''));
            
        $mdxi->Order->ShoppingCart->Tax($t)->setHeader($inklText . $taxArray['id']);
        $mdxi->Order->ShoppingCart->Tax($t)->setHeaderStyle(Mage::getStoreConfig('mpay24/mpay24/tax_headerstyle'));
        $mdxi->Order->ShoppingCart->Tax($t)->setStyle(Mage::getStoreConfig('mpay24/mpay24/tax_style'));
        $mdxi->Order->ShoppingCart->Tax($t)->setPercent(number_format($taxArray['rates'][0]['percent'],0,'.',''));
        
        $t++;
      }
    }

    $billingCountry = "";
    $billingCountryCode = "";
    $shippingCountry = "";
    $shippingCountryCode = "";

    foreach(Mage::app()->getLocale()->getOptionCountries() as $c)
      if ($c['value'] == $this->order->getBillingAddress()->getCountry()) {
        $billingCountry = $c['label'];
        $billingCountryCode = $c['value'];
        break;
      }

    if($this->order->getShippingAddress())
      foreach(Mage::app()->getLocale()->getOptionCountries() as $c)
        if ($c['value'] == $this->order->getShippingAddress()->getCountry()) {
          $shippingCountry = $c['label'];
          $shippingCountryCode = $c['value'];
          break;
         }

    $mdxi->Order->Price = $transaction->PRICE;
      
    $mdxi->Order->Price->setHeader(Mage::getStoreConfig('mpay24/mpay24/price_header'));
    $mdxi->Order->Price->setHeaderStyle(Mage::getStoreConfig('mpay24/mpay24/price_headerstyle'));
    $mdxi->Order->Price->setStyle(Mage::getStoreConfig('mpay24/mpay24/price_style'));

    $mdxi->Order->Currency = $this->xmlentities($this->order->getOrderCurrencyCode());
    $mdxi->Order->Customer = $this->xmlentities(substr($this->order->getCustomerName(),0,50));
    $mdxi->Order->Customer->setId($this->order->getCustomerId());

    $mdxi->Order->BillingAddr->setMode(Mage::getStoreConfig('mpay24/mpay24/billingAddressMode'));

    $mdxi->Order->BillingAddr->Name = $this->xmlentities(substr($this->order->getBillingAddress()->getName(),0,50));

    if($this->xmlentities(substr($this->order->getBillingAddress()->getName(),0,50)) == $this->xmlentities(substr($this->order->getCustomerName(),0,50))) {
      $mdxi->Order->BillingAddr->Name->setGender(substr(Mage::getResourceSingleton('customer/customer')->getAttribute('gender')->getSource()->getOptionText($this->order->getCustomer()->getGender()), 0, 1));
      $mdxi->Order->BillingAddr->Name->setBirthday(date('Y-m-d', strtotime($this->order->getCustomerDob())));
    }

    $billingAdress = $this->xmlentities($this->order->getBillingAddress()->getStreetFull());

    $billingStreet = $this->splitAdress($billingAdress);

    if(isset($billingStreet[0]) && is_array($billingStreet[0])) {
      $mdxi->Order->BillingAddr->Street = $billingStreet[0]['Street'];
      $mdxi->Order->BillingAddr->Street2 = $billingStreet[1]['Street2'];
    } else {
      $mdxi->Order->BillingAddr->Street = $billingStreet['Street'];

      if(isset($billingStreet['Street2']))
        $mdxi->Order->BillingAddr->Street2 = $billingStreet['Street2'];
    }

    $mdxi->Order->BillingAddr->Zip = substr($this->xmlentities($this->order->getBillingAddress()->getPostcode()),0,50);
    $mdxi->Order->BillingAddr->City = substr($this->xmlentities($this->order->getBillingAddress()->getCity()),0,50);
    $mdxi->Order->BillingAddr->State = substr($this->xmlentities($this->order->getBillingAddress()->getRegion()),0,50);
    $mdxi->Order->BillingAddr->Country->setCode($this->xmlentities($billingCountryCode));
    $mdxi->Order->BillingAddr->Email = substr($this->xmlentities($this->order->getBillingAddress()->getEmail()),0,50);
    $mdxi->Order->BillingAddr->Phone = substr($this->xmlentities($this->order->getBillingAddress()->getTelephone()),0,20);

    if($this->order->getShippingAddress()) {
      $mdxi->Order->ShippingAddr->setMode("ReadOnly");

      $mdxi->Order->ShippingAddr->Name = substr($this->order->getShippingAddress()->getName(),0,50);

      if($this->xmlentities(substr($this->order->getShippingAddress()->getName(),0,50)) == $this->xmlentities(substr($this->order->getCustomerName(),0,50))) {
        $mdxi->Order->ShippingAddr->Name->setGender(substr(Mage::getResourceSingleton('customer/customer')->getAttribute('gender')->getSource()->getOptionText($this->order->getCustomer()->getGender()), 0, 1));
        $mdxi->Order->ShippingAddr->Name->setBirthday(date('Y-m-d', strtotime($this->order->getCustomerDob())));
      }

      $shippingAdress = $this->xmlentities($this->order->getShippingAddress()->getStreetFull());

      $shippingStreet = $this->splitAdress($shippingAdress);

      if(isset($shippingStreet[0]) && is_array($shippingStreet[0])) {
        $mdxi->Order->ShippingAddr->Street = $shippingStreet[0]['Street'];
        $mdxi->Order->ShippingAddr->Street2 = $shippingStreet[1]['Street2'];
      } else {
        $mdxi->Order->ShippingAddr->Street = $shippingStreet['Street'];

        if(isset($shippingStreet['Street2']))
          $mdxi->Order->ShippingAddr->Street2 = $shippingStreet['Street2'];
      }

      $mdxi->Order->ShippingAddr->Zip = substr($this->xmlentities($this->order->getShippingAddress()->getPostcode()),0,50);
      $mdxi->Order->ShippingAddr->City = substr($this->xmlentities($this->order->getShippingAddress()->getCity()),0,50);
      $mdxi->Order->ShippingAddr->State = substr($this->xmlentities($this->order->getShippingAddress()->getRegion()),0,50);
      $mdxi->Order->ShippingAddr->Country->setCode($this->xmlentities($shippingCountryCode));
      $mdxi->Order->ShippingAddr->Email = substr($this->xmlentities($this->order->getShippingAddress()->getEmail()),0,50);
      $mdxi->Order->ShippingAddr->Phone = substr($this->xmlentities($this->order->getShippingAddress()->getTelephone()),0,20);
    }
    
    if(Mage::helper('customer')->isLoggedIn())
      $mdxi->Order->URL->Success = Mage::getUrl(MPay24MagentoShop::SUCCESS_URL,array('_secure' => true, '_query' => "TID=" . substr($this->order->getIncrementId(),0,32) ));
    else
      $mdxi->Order->URL->Success = Mage::getUrl(MPay24MagentoShop::GUEST_SUCCESS_URL,array('_secure' => true, '_query' => "tid=" . substr($this->order->getIncrementId(),0,32) ));

    $mdxi->Order->URL->Error = Mage::getUrl(MPay24MagentoShop::ERROR_URL,array('_secure' => true, '_query' => "TID=" . substr($this->order->getIncrementId(),0,32) ));
    $mdxi->Order->URL->Confirmation = Mage::getUrl(MPay24MagentoShop::CONFIRMATION_URL,array('_secure' => true));
    $mdxi->Order->URL->Cancel = Mage::getUrl(MPay24MagentoShop::CANCEL_URL,array('_secure' => true, '_query' => "TID=" . substr($this->order->getIncrementId(),0,32) ));

    if(Mage::getStoreConfig('mpay24/mpay24as/debug') == 1)
      Mage::log($mdxi->toXML(), null, "mpay24/xmls/".$transaction->TID.".xml",true);

    return $mdxi;
  }

  function setVariables($order, $ps, $type, $brand, $bic = "", $token = "") {
    $this->tid = $order->getIncrementId();
    $this->order = $order;
    $this->price = number_format($order->getData('grand_total'),2,'.','');
    $this->ps = $ps;
    $this->type = $type;
    $this->brand = $brand;
    $this->bic = $bic;
    $this->token = $token;

    $m= new Mage;
    $version=$m->getVersion();
    $this->mPay24Api->shop = "Magento";
    $this->mPay24Api->shopVersion = $version;
    $this->mPay24Api->moduleVersion = MAGENTO_VERSION;
  }

  private function xmlentities($string) {
    static $trans;

    if (!isset($trans)) {
      $trans = get_html_translation_table(HTML_SPECIALCHARS);

      foreach ($trans as $key => $value)
        $trans[$key] = '&#'.ord($key).';';

      // dont translate the '&' in case it is part of &xxx;
      $trans[chr(38)] = '&';
    }

    //after the initial translation, _do_ map standalone '&' into '&#38;'
    return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/","&#38;" , strtr($string, $trans));
  }

  private function splitAdress($adress, $str2=null) {
    $posNewLine = strpos($adress, "\n");
    $strArray = array();

    if($posNewLine === false) {
      if(strlen($adress) <= 50)
        if($str2 !== true)
          $strArray['Street'] = $adress;
        else
          $strArray['Street2'] = $adress;
      else {
        $street1before = substr($adress, 0, 50);
        $posLastInterval1 = strrpos($street1before, " ");

        $street1 = substr($street1before, 0, $posLastInterval1);

        $street2before = substr(substr($street1before, $posLastInterval1).substr($adress, 50), 1, 50);
        $posLastInterval2 = strrpos($street2before, " ");
        $street2 = substr($street2before, 0, $posLastInterval2);

        if($str2 === null) {
          $strArray['Street'] = $street1;
          $strArray['Street2'] = $street2;
        } elseif($str2 === false)
            if($posLastInterval1)
              $strArray['Street'] = $street1;
            else
              $strArray['Street'] = $street1before;
        else
          if($posLastInterval1)
            $strArray['Street2'] = $street1;
          else
            $strArray['Street2'] = $street1before;
        }
    } else {
      $array = explode("\n", $adress);
      $adress1 = $array[0];
      array_push($strArray, $this->splitAdress($adress1, false));

      $adress2 = $array[1];
      array_push($strArray, $this->splitAdress($adress2, true));
    }
    return $strArray;
  }

  public static function getMPay24Api() {
    if(Mage::getStoreConfig('mpay24/mpay24as/system') == 1)
      $test = TRUE;
    else
      $test = FALSE;

    if(Mage::getStoreConfig('mpay24/mpay24as/debug') == 1)
      $debug = TRUE;
    else
      $debug = FALSE;
    
    if(Mage::getStoreConfig('mpay24/mpay24as/verify_peer') == 1)
      $verify_peer = TRUE;
    else
      $verify_peer = FALSE;

    if(Mage::getStoreConfig('mpay24/mpay24as/use_proxy') == 1) {
      $proxy_host = Mage::getStoreConfig('mpay24/mpay24as/proxy_host');
      $proxy_port = Mage::getStoreConfig('mpay24/mpay24as/proxy_port');
      if(Mage::getStoreConfig('mpay24/mpay24as/use_proxy_auth') == 1) {
        $proxy_user = Mage::getStoreConfig('mpay24/mpay24as/proxy_user');
        $proxy_pass = Mage::getStoreConfig('mpay24/mpay24as/proxy_pass');
      } else {
        $proxy_user = null;
        $proxy_pass = null;
      }
    } else {
      $proxy_host = null;
      $proxy_port = null;
      $proxy_user = null;
      $proxy_pass = null;
    }

    $mPay24MagentoShop = new MPay24MagentoShop(Mage::getStoreConfig('mpay24/mpay24as/merchantid'), Mage::getStoreConfig('mpay24/mpay24as/soap_pass'), $test, $debug, $proxy_host, $proxy_port, $proxy_user, $proxy_pass, $verify_peer);
    return   $mPay24MagentoShop;
  }
}
?>
