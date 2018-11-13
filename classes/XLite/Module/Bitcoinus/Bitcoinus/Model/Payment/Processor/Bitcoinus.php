<?php

namespace XLite\Module\Bitcoinus\Bitcoinus\Model\Payment\Processor;

class Bitcoinus extends \XLite\Model\Payment\Base\WebBased
{

  /**
     * Get module settings
   */
  public function getSettingsWidget()
  {
    return 'modules/Bitcoinus/Bitcoinus/config.twig';
  }

  /**
   * @param \XLite\Model\Payment\Method $method Payment method
   *
   * @return string|boolean|null
   */
  public function getAdminIconURL(\XLite\Model\Payment\Method $method)
  {
      return true;
  }

  /**
     * Return mode (test or live)
     * @return sting
   */
  public function isTestMode(\XLite\Model\Payment\Method $method)
  {
    return $method->getSetting('mode') != 'live';
  }

  /**
     * Is configured module settings
   */
  public function isConfigured(\XLite\Model\Payment\Method $method)
  {
    return parent::isConfigured($method)
      && $method->getSetting('projectid')
      && $method->getSetting('secretkey');
  }

  /**
     * Transaction data object 
     * @param  int $pid Bitcoinus Project ID 
     * @param  string $orderid Client side order ID
     * @param  double $amount Payment amount 
     * @param  double $discount Discounts in percents per currency
     * @param  string $currency Payment currency 
     * @param  string $lang Two characters session language code
     * @param  string $name Client's full name 
     * @param  string $email Client's email
     * @param  string $street Client’s street address 
     * @param  string $redirect Relative redirect path
     * @param  string $back Relative path if cancelled by user
     * @param  string $timeout Relative path if payment timeout is reached
     * @param  string $redirect Relative redirect path
     * @param  int $test Test mode (defaults to 0), 1 is on
     * @return array Transaction data object in JSON format  
   */
  public function getDataObject()
  {

    $pid = $this->getSetting('projectid');
    $orderid = $this->getTransactionId();
    $amount = $this->transaction->getOrder()->getTotal();
    $currency = \XLite::getInstance()->getCurrency()->getCode();
    $lang = !empty(\XLite\Core\Request::getInstance()->getLanguageCode()) ? \XLite\Core\Request::getInstance()->getLanguageCode() : 'EN';
    $name = !empty($this->getProfile()->getName()) ? $this->getProfile()->getName() : '';
    $email = !empty($this->getProfile()->getEmail()) ? $this->getProfile()->getEmail() : '';
    $street = !empty($this->getProfile()->getBillingAddress()->getStreet()) ? $this->getProfile()->getBillingAddress()->getStreet() : '';
    $redirect = $this->getReturnURL(null, true);
    $back = $this->getReturnURL(null, true, true);
    $timeout = $this->getReturnURL(null, true);
    $discount = (object)[
      'bits' => intval($this->getSetting('discount')),
    ];

    if($this->getSetting('mode') =='live'){
      $test = 0;
    }
    else{
      $test = 1;
    }

    $data = json_encode((object)[
      'pid' => $pid,
      'orderid' => $orderid,
      'amount' => $amount,
      'discount' =>$discount,
      'currency' => $currency,
      'lang' => $lang,
      'name' => $name,
      'email' => $email,
      'street' => $street,
      'redirect' => $redirect,
      'back' => $back,
      'timeout' => $timeout,
      'test' => $test,
    ]);

    return $data;
  }

  /**
     * Array of objects with information about products
     * @return Array of objects with information about product items being purchased
   */
  public function getItemsArrObject()
  {
    $items = [];

    foreach ($this->getOrder()->getItems() as $item) {
      $items[] = (object)[
        'title' => $item->getName(),
        'qty' => number_format($item->getAmount(),2),
        'price' => $item->getItemPrice(),
      ];
    }

    return $items;
  }

  /**
     * Redirect to Bitcoinus
     * @param  array $data transaction data object in JSON format 
     * @param  array $items information about product items being purchased, in JSON format 
     * @param  string $signature HMAC-SHA-256 hash of data parameter with project secret as a key
     * @return string Redirect URL to Bitcoinus
   */
  protected function getFormURL()
  {
    //print_r(hash_hmac('sha256',$this->getDataObject(),$this->getSetting('secretkey')));
    $data = filter_var(base64_encode($this->getDataObject()));
    $signature = filter_var(hash_hmac('sha256',$this->getDataObject(),$this->getSetting('secretkey')));

    if($this->getSetting('info') == 1){
      $items = filter_var(base64_encode(json_encode($this->getItemsArrObject())));
    }
    else{
      $items ='';
    }

    return 'https://pay.bitcoinus.io/do?' . 'data=' . $data . '&items=' . $items . '&signature=' . $signature;
  }

  /**
     * Get form fields
     * @return array
   */
  protected function getFormFields()
  {
    return array(
      'transactionID' => $this->transaction->getPublicTxnId(),
      'returnURL' => $this->getReturnURL('transactionID'),
    );
  }

  /**
     * Set order status
     * @param  array $result callback data
     * @param  int $cancel cancel status
     * @param  string $signature_local HMAC-SHA-256 hash of data parameter with project secret as a key
     * @param  array $order order data
     * @param  int $callbackStatus order status
     * @param  array $notes status notes
   */
  public function processReturn(\XLite\Model\Payment\Transaction $transaction)
  {
    parent::processReturn($transaction);

    $cancel = \XLite\Core\Request::getInstance()->cancel;
    $result = \XLite\Core\Request::getInstance()->getData();
    $signature_local = hash_hmac('sha256',$this->getDataObject(),$this->getSetting('secretkey'));
    $order = $this->getOrder();

    if($cancel == 1){
      $status = $transaction::STATUS_CANCELED;
      $notes[] = 'Payment Cancelled';
    }
    elseif (!isset(json_decode($result['data'])->status)){
      if (!$order->isNotFinishedOrder()) {
        $status = $transaction::STATUS_PENDING;
        $notes[] = 'Awaiting Payment';
      }
    }

    if (is_object($data = json_decode($result['data']))) {
      if(isset($data->status)){
        $callbackStatus = intval($data->status);

        if(!isset($result['signature']) || $signature_local != $result['signature']){
          $status = $transaction::STATUS_FAILED;
          $notes[] = 'Payment Failed invalid signature';
        }
        else{
          if ($callbackStatus == 1) {
            $status = $transaction::STATUS_SUCCESS;
            $notes[] = 'Paid';
            $order->setPaymentStatus(\XLite\Model\Order\Status\Payment::STATUS_PAID);
          }
          if ($callbackStatus == 2) {
            $status = $transaction::STATUS_FAILED;
            $notes[] = 'Payment Failed';
          }
        }
      }
    }

    $this->transaction->setStatus($status);
    $this->transaction->setNote(implode('. ', $notes));
  }

}