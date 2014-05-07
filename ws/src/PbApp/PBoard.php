<?php
namespace PbApp;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class PBoard implements WampServerInterface
{
	protected $subscribedTopics = array();
	protected $subscribedCount = array();

	public function onSubscribe(ConnectionInterface $conn, $topic) {
		if (!array_key_exists($topic->getId(), $this->subscribedTopics)) {
			$this->subscribedTopics[$topic->getId() ] = $topic;
			$this->subscribedCount[$topic->getId()] = 0;
		}
		$this->subscribedCount[$topic->getId()]++;
		$this->log('[subscrib]User '.$conn->WAMP->sessionId.' subscribed topic '.$topic->getId());
	}

	/**
	 * broadcastBoardData
	 * Send the data to all the clients subscribed to that board
	 * @param  string $entry JSON'ified string receive from ZMQ
	 */
	public function broadcastBoardData($entry) {
		$this->log('[broadcast]Webserver broadcast: '.$entry);
		$entryData = json_decode($entry);
		$tid = $entryData->topic;

		if(!array_key_exists($tid, $this->subscribedTopics)){
			$this->log('[broadcast]No subscrib on topic "'.$tid.'"');
			return;
		}

		$topic = $this->subscribedTopics[$tid];
		// send the data to all the clients subscribed to that board
		$topic->broadcast($entryData);
	}

	public function onUnSubscribe(ConnectionInterface $conn, $topic) {
		if (!array_key_exists($topic->getId(), $this->subscribedTopics)) {
			return;
		}

		$tid = $topic->getId();
		$this->log('[unsubscribe]User '.$conn->WAMP->sessionId.' unsubscribed topic '.$tid);
		if($this->subscribedCount[$tid] <= 0){
			unset($this->subscribedTopics[$tid], $this->subscribedCount[$tid]);
		}
	}

	public function onOpen(ConnectionInterface $conn) {
	}

	public function onClose(ConnectionInterface $conn) {
	}

	public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {

		// In this application if clients send data it's because the user hacked around in console
		$conn->callError($id, $topic, 'You are not allowed to make calls')->close();
	}

	public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {

		// In this application if clients send data it's because the user hacked around in console
		$conn->close();
	}

	public function onError(ConnectionInterface $conn, \Exception $e) {
	}

	public function log($msg) {
		echo 'PbApp: ' . print_r($msg, true) . PHP_EOL;
	}
}
