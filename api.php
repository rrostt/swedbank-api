<?php 
require 'vendor/autoload.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'])['path'];
$action = explode('/', $path)[1];

$methodCall = strtolower($method) . ucfirst($action);
(new Api())->$methodCall($_REQUEST);

class Api {
  const AUTH_TIMEOUT = 30;

  private function authUsingBankID($bankApp, $username) {
    session_start();

    $auth = new SwedbankJson\Auth\MobileBankID($bankApp, $username);
    $auth->initAuth();
    $tries = 0;
    while(!$auth->verify() && ++$tries<self::AUTH_TIMEOUT) {
      sleep(1); // waiting one second
    }

    if ($tries<self::AUTH_TIMEOUT) {
      return $auth;
    }

    return false;
  }

  private function errorResponse($msg) {
    $error = [
      'error' => true,
      'msg' => $msg
    ];
    header("Content-Type: application/json");
    echo json_encode($error);
  }

  public function getAccounts($args) {
    $bankApp  = 'swedbank';
    $username = $args['username'];

    if (empty(username)) {
      $this->errorResponse("error missing username");
      return;
    }

    $auth = $this->authUsingBankID($bankApp, $username);
    if (!$auth) {
      $this->errorResponse("BankID timeout");
      return;
    }

    $bankConn = new SwedbankJson\SwedbankJson($auth);

    $accounts = $bankConn->accountList();
    $bankConn->terminate();

    header("Content-Type: application/json");
    echo json_encode($accounts);
  }

  public function postTransfer($args) {
    $bankApp  = 'swedbank';
    $username = $args['username'];
    $from = $args['from'];
    $to = $args['to'];
    $fromMsg = $args['fromMsg'] ?: '';
    $toMsg = $args['toMsg'] ?: $fromMsg;
    $amount = $args['amount'];

    if (empty($username)) {
      $this->errorResponse("error missing auth parameters");
      return;
    }

    if (empty($from) || empty($to) || empty($amount)) {
      $this->errorResponse("error missing transfer parameters");
      return;
    }

    $auth = $this->authUsingBankID($bankApp, $username);
    if (!$auth) {
      $this->errorResponse("BankID timeout");
      return;
    }
    $bankConn = new SwedbankJson\SwedbankJson($auth);

    $baseInfo = $bankConn->baseInfo();

    $fromAccountsLists = array_map(function (&$account) { return $account->accounts; }, $baseInfo->fromAccountGroup);
    $fromAccounts = [];
    $fromAccounts = array_reduce($fromAccountsLists, function (&$result, &$list) { return array_merge($result, $list); }, $fromAccounts);
    $toAccountsLists = array_map(function (&$account) { return $account->accounts; }, $baseInfo->recipientAccountGroup);
    $toAccounts = [];
    $toAccounts = array_reduce($toAccountsLists, function (&$result, &$list) { return array_merge($result, $list); }, $toAccounts);

    foreach ($fromAccounts as &$account) {
      if ($account->fullyFormattedNumber == $from) {
        $fromId = $account->id;
      }
    }
    foreach($toAccounts as &$account) {
      if ($account->fullyFormattedNumber == $to) {
        $toId = $account->id;
      }
    }

    if (empty($fromId)) {
      $this->errorResponse("unable to find from account");
      return;
    }

    if (empty($toId)) {
      $this->errorResponse("unable to find to account");
      return;
    }

    $bankConn->registerTransfer($amount, $fromId, $toId, $fromMsg, $toMsg);
    $bankConn->confirmTransfer();
    $bankConn->terminate();

    header("Content-Type: application/json");
    echo json_encode(['success' => true]);
  }
}
