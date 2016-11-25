<?php

class EPay {

	public $API_URL = 'https://api.epay.info/?wsdl';

	private $ERROR_MESSAGES = array(
		'-2' => 'Wrong API key',
		'-3' => 'Insufficient funds',
		'-4' => 'One of the mandatory parameters is missing',
		'-5' => 'Payment is sooner than the calculated time out',
		'-6' => 'ACL is active and server IP address is not authorized',
		'-7' =>	'User IP address is blocked',
		'-8' => 'User country is blocked',
		'-10' => 'Daily budget reached',
		'-11' => 'Time-frame limit reached'
	);

	public function __construct($apikey, $timeout=15, $verifyPeer=true) {
		$this->apikey = $apikey;
		$this->timeout = $timeout;
		$this->verifyPeer = $verifyPeer;
	}
	public function setTimeout($timeout) {
		$this->timeout = $timeout;
	}
	public function setVerifyPeer($verifyPeer) {
		$this->verifyPeer = $verifyPeer;
	}
	private function client() {
		$streamopts = array(
			'ssl' => array(
				'verify_peer' => $this->verifyPeer,
				'verify_peer_name' => $this->verifyPeer
			)
		);
		$options = array(
			//'encoding' => 'UTF-8',
			//'soap_version' => SOAP_1_2, 'trace' => 1, 'exceptions' => 1,
			'verifypeer' => $this->verifyPeer,
			'verifyhost' => $this->verifyPeer,
			'stream_context' => stream_context_create($streamopts),
			'connection_timeout' => (int)($this->timeout / 2),
		);
 		ini_set('default_socket_timeout', $this->timeout);
		return new SoapClient($this->API_URL, $options);
	}

	public function getBalance() {
		try {
			$client = $this->client();
			$response = $client->f_balance($this->apikey, 1);
		} catch (SoapFault $e) {
			return array(
				'success' => false,
				'error' => true,
				'message' => 'API Error: '.$e->faultcode.' '.$e->faultstring,
			);
		}
		if ($response < 0) {
			return array(
				'success' => false,
				'error' => true,
				'message' => $this->ERROR_MESSAGES[$response],
			);
		}
		return array(
			'success' => true,
			'error' => false,
			'balance' => $response,//satoshi
			'balance_bitcoin' =>  $response / 100000000,//fullcoin
		);
	}

	public function sendSatoshi($wallet, $amount, $ref=false, $note='') {
		try {
			$client = $this->client();
			$response = $client->send($this->apikey, $wallet, $amount, $ref ? 2 : 1, $note);
		} catch (SoapFault $e) {
			return array(
				'success' => false,
				'error' => true,
				'message' => 'API Error: '.$e->faultcode.' '.$e->faultstring,
			);
		}
		if ($response['status'] < 0) {
			return array(
				'success' => false,
				'error' => true,
				'message' => $this->ERROR_MESSAGES[$response['status']],
			);
		}
		return array(
			'success' => true,
			'error' => false,
			'message' => '',
		);
	}

}
/* Usage:
	$epay = new EPay($faucet_apikey);
	$b = $epay->getBalance();
	if ($b['success'] == true) {
		echo $b['balance'];//satoshi
		//echo $b['balance_full'];//full coin
	}

	$epay = new EPay($faucet_apikey);
	$s = $epay->send($address, $satoshi, $ref_flag, $note);
	if ($s['success'] == true) {
		echo $s['message'];//success message
	} else {
		echo $s['message'];//error message
	}
*/
?>