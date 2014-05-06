<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use PbApp\PBoard;

require dirname(__DIR__).'/vendor/autoload.php';

$port = 8989;

echo 'Start a WampServer on port '.$port."...\n";

$loop = React\EventLoop\Factory::create();
$pusher = new PBoard();

// Listen for the web server to make a ZMQ push
$context = new React\ZMQ\Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
// Binding to 127.0.0.1 means only accept itself's connect
$pull->bind('tcp://127.0.0.1:5555');
$pull->on('message', array($pusher, 'broadcastBoardData'));

// Set up our Websocket server for clients wanting realtime updates
$webSock = new React\Socket\Server($loop);
// Binding to 0.0.0.0 means remotes can connect
$webSock->listen($port, '0.0.0.0');
$webServer = new Ioserver(
	new HttpServer(
		new WsServer(
			new WampServer($pusher)
		)
	),
	$webSock
);

$loop->run();

/*
  We can subscribe a board's updates in client side:
<script src="http://autobahn.s3.amazonaws.com/js/autobahn.min.js"></script>
<script>
	var conn = new ab.Session(
		'ws://domain:port' // The host (our Ratchet WebSocket server) to  connect to
	  , function() {            // Once the connection has been established
			conn.subscribe('kittensCategory', function(topic, data) {
				// This is where you would add the new article to the DOM ( beyond the scope of this tutorial)
				console.log('New article published to category "' + topic +  '" : ' + data.title);
			});
		}
	  , function() {            // When the connection is closed
			console.warn('WebSocket connection closed');
		}
	  , {                       // Additional parameters, we're ignoring  the WAMP sub-protocol for older browsers
			'skipSubprotocolCheck': true
		}
	);
</script>
 */
