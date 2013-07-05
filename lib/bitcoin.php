<?php
define("BITCOIN_ADDRESS_VERSION", "00");// this is a hex byte
class Bitcoin {

  /*
   * Bitcoin utility functions by theymos
   * Via http://www.bitcoin.org/smf/index.php?topic=1844.0
   * hex input must be in uppercase, with no leading 0x
   */
  private static $hexchars = "0123456789ABCDEF";
  private static $base58chars = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

  private function decodeHex($hex) {
    $hex = strtoupper($hex);
    $return = "0";
    for ($i = 0; $i < strlen($hex); $i++) {
      $current = (string) strpos(self::$hexchars, $hex[$i]);
      $return = (string) bcmul($return, "16", 0);
      $return = (string) bcadd($return, $current, 0);
    }
    return $return;
  }

  private function encodeHex($dec) {
    $return = "";
    while (bccomp($dec, 0) == 1) {
      $dv = (string) bcdiv($dec, "16", 0);
      $rem = (integer) bcmod($dec, "16");
      $dec = $dv;
      $return = $return . self::$hexchars[$rem];
    }
    return strrev($return);
  }

  private function decodeBase58($base58) {
    $origbase58 = $base58;

    $return = "0";
    for ($i = 0; $i < strlen($base58); $i++) {
      $current = (string) strpos(Bitcoin::$base58chars, $base58[$i]);
      $return = (string) bcmul($return, "58", 0);
      $return = (string) bcadd($return, $current, 0);
    }

    $return = self::encodeHex($return);

    //leading zeros
    for ($i = 0; $i < strlen($origbase58) && $origbase58[$i] == "1"; $i++) {
      $return = "00" . $return;
    }

    if (strlen($return) % 2 != 0) {
      $return = "0" . $return;
    }

    return $return;
  }

  private function encodeBase58($hex) {
    if (strlen($hex) % 2 != 0) {
      die("encodeBase58: uneven number of hex characters");
    }
    $orighex = $hex;

    $hex = self::decodeHex($hex);
    $return = "";
    while (bccomp($hex, 0) == 1) {
      $dv = (string) bcdiv($hex, "58", 0);
      $rem = (integer) bcmod($hex, "58");
      $hex = $dv;
      $return = $return . self::$base58chars[$rem];
    }
    $return = strrev($return);

    //leading zeros
    for ($i = 0; $i < strlen($orighex) && substr($orighex, $i, 2) == "00"; $i += 2) {
      $return = "1" . $return;
    }

    return $return;
  }

  public static function hash160ToAddress($hash160, $addressversion = BITCOIN_ADDRESS_VERSION) {
    $hash160 = $addressversion . $hash160;
    $check = pack("H*", $hash160);
    $check = hash("sha256", hash("sha256", $check, true));
    $check = substr($check, 0, 8);
    $hash160 = strtoupper($hash160 . $check);
    return self::encodeBase58($hash160);
  }

  public static function addressToHash160($addr) {
    $addr = self::decodeBase58($addr);
    $addr = substr($addr, 2, strlen($addr) - 10);
    return $addr;
  }

  public static function checkAddress($addr, $addressversion = BITCOIN_ADDRESS_VERSION) {
    $addr = self::decodeBase58($addr);
    if (strlen($addr) != 50) {
      return false;
    }
    $version = substr($addr, 0, 2);
    if (hexdec($version) > hexdec($addressversion)) {
      return false;
    }
    $check = substr($addr, 0, strlen($addr) - 8);
    $check = pack("H*", $check);
    $check = strtoupper(hash("sha256", hash("sha256", $check, true)));
    $check = substr($check, 0, 8);
    return $check == substr($addr, strlen($addr) - 8);
  }

  private function hash160($data) {
    $data = pack("H*", $data);
    return strtoupper(hash("ripemd160", hash("sha256", $data, true)));
  }

  public static function pubKeyToAddress($pubkey) {
    return self::hash160ToAddress($this->hash160($pubkey));
  }

  public static function remove0x($string) {
    if (substr($string, 0, 2) == "0x" || substr($string, 0, 2) == "0X") {
      $string = substr($string, 2);
    }
    return $string;
  }
}

class BitcoinClientException extends ErrorException {
  // Redefine the exception so message isn't optional
  public function __construct($message, $code = 0, $severity = E_USER_NOTICE, Exception $previous = null) {
    parent::__construct($message, $code, $severity, $previous);
  }

  public function __toString() {
    return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }
}

require_once(dirname(__FILE__) . "/includes/xmlrpc.inc");
require_once(dirname(__FILE__) . "/includes/jsonrpc.inc");

class BitcoinClient extends jsonrpc_client {

  public function __construct($scheme, $username, $password, $address = "localhost", $port = 8332, $certificate_path = '', $debug_level = 0) {
    $scheme = strtolower($scheme);
    if ($scheme != "http" && $scheme != "https")
      throw new BitcoinClientException("Scheme must be http or https");
    if (empty($username))
      throw new BitcoinClientException("Username must be non-blank");
    if (empty($password))
      throw new BitcoinClientException("Password must be non-blank");
    $port = (string) $port;
    if (!$port || empty($port) || !is_numeric($port) || $port < 1 || $port > 65535 || floatval($port) != intval($port))
      throw new BitcoinClientException("Port must be an integer and between 1 and 65535");
    if (!empty($certificate_path) && !is_readable($certificate_path))
      throw new BitcoinClientException("Certificate file " . $certificate_path . " is not readable");
    $uri = $scheme . "://" . $username . ":" . $password . "@" . $address . ":" . $port . "/";
    parent::__construct($uri);
    $this->setDebug($debug_level);
    $this->setSSLVerifyHost(0);
    if ($scheme == "https")
      if (!empty($certificate_path))
        $this->setCaCertificate($certificate_path);
      else
        $this->setSSLVerifyPeer(false);
  }

  public function can_connect() {
    try {
      $r = $this->getinfo();
    } catch (BitcoinClientException $e) {
      return $e->getMessage();
    }
    return true;
  }

  public function query_arg_to_parameter($argument) {
    $type = "";// "string" is encoded as this default type value in xmlrpc.inc
    if (is_numeric($argument)) {
      if (intval($argument) != floatval($argument)) {
        $argument = floatval($argument);
        $type = "double";
      } else {
        $argument = intval($argument);
        $type = "int";
      }
    }
    if (is_bool($argument))
      $type = "boolean";
    if (is_int($argument))
      $type = "int";
    if (is_float($argument))
      $type = "double";
    if (is_array($argument))
      $type = "array";
    return new jsonrpcval($argument, $type);
  }

  public function query($message) {
    if (!$message || empty($message))
      throw new BitcoinClientException("Bitcoin client query requires a message");
    $msg = new jsonrpcmsg($message);
    if (func_num_args() > 1) {
      for ($i = 1; $i < func_num_args(); $i++) {
        $msg->addParam(self::query_arg_to_parameter(func_get_arg($i)));
      }
    }
    $response = $this->send($msg);
    if ($response->faultCode()) {
      throw new BitcoinClientException($response->faultString());
    }
    return php_xmlrpc_decode($response->value());
  }

  /*
   * The following functions implement the Bitcoin RPC API as documented at https://www.bitcoin.org/wiki/doku.php?id=api
   */

  public function backupwallet($destination) {
    if (!$destination || empty($destination))
      throw new BitcoinClientException("backupwallet requires a destination");
    return $this->query("backupwallet", $destination);
  }

  public function getbalance($account = NULL, $minconf = 1) {
    if (!is_numeric($minconf) || $minconf < 0)
      throw new BitcoinClientException('getbalance requires a numeric minconf >= 0');
    if ($account == NULL)
      return $this->query("getbalance");
    return $this->query("getbalance", $account, $minconf);
  }

  public function getblockcount() {
    return $this->query("getblockcount");
  }

  public function getblocknumber() {
    return $this->query("getblocknumber");
  }

  public function getconnectioncount() {
    return $this->query("getconnectioncount");
  }

  public function getdifficulty() {
    return $this->query("getdifficulty");
  }

  public function getgenerate() {
    return $this->query("getgenerate");
  }

  public function setgenerate($generate = TRUE, $maxproc = -1) {
    if (!is_numeric($maxproc) || $maxproc < -1)
      throw new BitcoinClientException('setgenerate: $maxproc must be numeric and >= -1');
    return $this->query("setgenerate", $generate, $maxproc);
  }

  public function getinfo() {
    return $this->query("getinfo");
  }

  public function getaccount($address) {
    if (!$address || empty($address))
      throw new BitcoinClientException("getaccount requires an address");
    return $this->query("getaccount", $address);
  }

  public function getlabel($address) {
    if (!$address || empty($address))
      throw new BitcoinClientException("getlabel requires an address");
    return $this->query("getlabel", $address);
  }

  public function setaccount($address, $account = "") {
    if (!$address || empty($address))
      throw new BitcoinClientException("setaccount requires an address");
    return $this->query("setaccount", $address, $account);
  }

  public function setlabel($address, $label = "") {
    if (!$address || empty($address))
      throw new BitcoinClientException("setlabel requires an address");
    return $this->query("setlabel", $address, $label);
  }

  public function getnewaddress($account = NULL) {
    if (!$account || empty($account))
      return $this->query("getnewaddress");
    return $this->query("getnewaddress", $account);
  }

  public function getreceivedbyaddress($address, $minconf = 1) {
    if (!is_numeric($minconf) || $minconf < 0)
      throw new BitcoinClientException('getreceivedbyaddress requires a numeric minconf >= 0');
    if (!$address || empty($address))
      throw new BitcoinClientException("getreceivedbyaddress requires an address");
    return $this->query("getreceivedbyaddress", $address, $minconf);
  }

  public function getreceivedbyaccount($account, $minconf = 1) {
    if (!is_numeric($minconf) || $minconf < 0)
      throw new BitcoinClientException('getreceivedbyaccount requires a numeric minconf >= 0');
    if (!$account || empty($account))
      throw new BitcoinClientException("getreceivedbyaccount requires an account");
    return $this->query("getreceivedbyaccount", $account, $minconf);
  }

  public function getreceivedbylabel($label, $minconf = 1) {
    if (!is_numeric($minconf) || $minconf < 0)
      throw new BitcoinClientException('getreceivedbylabel requires a numeric minconf >= 0');
    if (!$label || empty($label))
      throw new BitcoinClientException("getreceivedbylabel requires a label");
    return $this->query("getreceivedbylabel", $label, $minconf);
  }

  public function help($command = NULL) {
    if (!$command || empty($command))
      return $this->query("help");
    return $this->query("help", $command);
  }

  public function listreceivedbyaddress($minconf = 1, $includeempty = FALSE) {
    if (!is_numeric($minconf) || $minconf < 0)
      throw new BitcoinClientException('listreceivedbyaddress requires a numeric minconf >= 0');
    return $this->query("listreceivedbyaddress", $minconf, $includeempty);
  }

  public function listreceivedbyaccount($minconf = 1, $includeempty = FALSE) {
    if (!is_numeric($minconf) || $minconf < 0)
      throw new BitcoinClientException('listreceivedbyaccount requires a numeric minconf >= 0');
    return $this->query("listreceivedbyaccount", $minconf, $includeempty);
  }

  public function listreceivedbylabel($minconf = 1, $includeempty = FALSE) {
    if (!is_numeric($minconf) || $minconf < 0)
      throw new BitcoinClientException('listreceivedbylabel requires a numeric minconf >= 0');
    return $this->query("listreceivedbylabel", $minconf, $includeempty);
  }

  public function sendtoaddress($address, $amount, $comment = NULL, $comment_to = NULL) {
    if (!$address || empty($address))
      throw new BitcoinClientException("sendtoaddress requires a destination address");
    if (!$amount || empty($amount))
      throw new BitcoinClientException("sendtoaddress requires an amount to send");
    if (!is_numeric($amount) || $amount <= 0)
      throw new BitcoinClientException("sendtoaddress requires the amount sent to be a number > 0");
    $amount = floatval($amount);
    if (!$comment && !$comment_to)
      return $this->query("sendtoaddress", $address, $amount);
    if (!$comment_to)
      return $this->query("sendtoaddress", $address, $amount, $comment);
    return $this->query("sendtoaddress", $address, $amount, $comment, $comment_to);
  }

  public function stop() {
    return $this->query("stop");
  }

  public function validateaddress($address) {
    if (!$address || empty($address))
      throw new BitcoinClientException("validateaddress requires a Bitcoin address");
    return $this->query("validateaddress", $address);
  }

  public function gettransaction($txid) {
    if (!$txid || empty($txid) || strlen($txid) != 64 || !preg_match('/^[0-9a-fA-F]+$/', $txid))
      throw new BitcoinClientException("gettransaction requires a valid hexadecimal transaction ID");
    return $this->query("gettransaction", $txid);
  }

  public function move($fromaccount = "", $toaccount, $amount, $minconf = 1, $comment = NULL) {
    if (!$fromaccount)
      $fromaccount = "";
    if (!$toaccount || empty($toaccount) || !$amount || !is_numeric($amount) || $amount <= 0)
      throw new BitcoinClientException("move requires a from account, to account and numeric amount > 0");
    if (!is_numeric($minconf) || $minconf < 0)
      throw new BitcoinClientException('move requires a numeric $minconf >= 0');
    if (!$comment || empty($comment))
      return $this->query("move", $fromaccount, $toaccount, $amount, $minconf);
    return $this->query("move", $fromaccount, $toaccount, $amount, $minconf, $comment);
  }

  public function sendfrom($account, $toaddress, $amount, $minconf = 1, $comment = NULL, $comment_to = NULL) {
    if (!$account || !$toaddress || empty($toaddress) || !$amount || !is_numeric($amount) || $amount <= 0)
      throw new BitcoinClientException("sendfrom requires a from account, to account and numeric amount > 0");
    if (!is_numeric($minconf) || $minconf < 0)
      throw new BitcoinClientException('sendfrom requires a numeric $minconf >= 0');
    if (!$comment && !$comment_to)
      return $this->query("sendfrom", $account, $toaddress, $amount, $minconf);
    if (!$comment_to)
      return $this->query("sendfrom", $account, $toaddress, $amount, $minconf, $comment);
    $this->query("sendfrom", $account, $toaddress, $amount, $minconf, $comment, $comment_to);
  }

  public function getwork($data = NULL) {
    if (!$data)
      return $this->query("getwork");
    return $this->query("getwork", $data);
  }

  public function getaccountaddress($account) {
    if (!$account || empty($account))
      throw new BitcoinClientException("getaccountaddress requires an account");
    return $this->query("getaccountaddress", $account);
  }

  public function gethashespersec() {
    return $this->query("gethashespersec");
  }

  public function getaddressesbyaccount($account) {
    if (!$account || empty($account))
      throw new BitcoinClientException("getaddressesbyaccount requires an account");
    return $this->query("getaddressesbyaccount", $account);
  }

}