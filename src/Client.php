<?php
namespace Ratchet\SocketIO;
use React\EventLoop\LoopInterface;
use Ratchet\RFC6455\Messaging;
use Ratchet\Client\Connector;
use React\EventLoop\Factory as ReactFactory;
use React\EventLoop\Timer\Timer;

/**
*  Client connection
*/
class Client extends Connector {
  const IO_EVENT   =  4;
  const IO_PING    =  2;
  const IO_PONG    =  3;

  static private $__connection;
  static public $LOOP;

  static public $HOST = 'localhost';
  static public $PORT = '1337';
  static public $QUERY = [
    '__sails_io_sdk_version'  => '1.2.1',
    '__sails_io_sdk_platform' => 'node',
    '__sails_io_sdk_language' => 'javascript',
    'EIO' => '3',
    'transport' => 'websocket'
  ];

  static public $PROTOCOLS = [];
  static public $HEADERS = [];

  private $__MESSAGE_MAP = [];
  private $__MESSAGE_ID  = 0;


  public function __construct(LoopInterface $loop = null) {
      // init loop
      parent::__construct(static::LoopFactory($loop));
  }

  /**
  * Return URL patch
  * @return string
  */
  static public function url() {
    return 'ws://' . static::$HOST .':' . static::$PORT . '/socket.io/?' . http_build_query(static::$QUERY,'','&');
  }

  /**
  * Connecntion Factory
  * @return \React\Promise\PromiseInterface<\Ratchet\SocketIO\Client>
  */
  static public function Factory ($host = null, $port = null) {
    if (static::$__connection) return static::$__connection;

    $host = $host || static::$HOST;
    $port = $port || static::$PORT;
    static::$__connection = (new static())(static::url(),static::$PROTOCOLS, static::$HEADERS);
    return static::$__connection;
  }

  /**
  * Event Loop Factory
  * @return Object<\React\EventLoop\LoopInterface>
  */
  static public function LoopFactory (LoopInterface $loop = null) {
    if ($loop) return $loop;
    if (static::$LOOP instanceof LoopInterface) return static::$LOOP;

    // initialize new event loop
    if (!$loop) $loop = ReactFactory::create();

    static::$LOOP = $loop;

    $runHasBeenCalled = false;

    $loop->addTimer(Timer::MIN_INTERVAL, function () use (&$runHasBeenCalled) {
        $runHasBeenCalled = true;
    });

    register_shutdown_function(function() use ($loop, &$runHasBeenCalled) {
        if (!$runHasBeenCalled) {
            $loop->run();
        }
    });

    return static::$LOOP;

  }

  /**
  * Initialize connection
  * @return PromiseInterface<\Ratchet\SocketIO\Client>
  */
  static public function connect() {
    if(static::$__connection) return static::$__connection;
    return static::Factory();
  }

  private function encode(aray $data){
    return strval($this->IO_EVENT)
      . strval($this->IO_PING)
      . strval($this->__MESSAGE_ID++)
      . json_encode($data);
  }

  /**
  * Emitting message by WebSocket
  * @param string     $event      Event name
  * @param array      $data       Payload data
  * @param callable   $cb         Payload data
  * @return PromiseInterface<\Ratchet\SocketIO\Client>
  */
  function emit ($event, array $data = [], callable $cb = null) {
    return $this->send($this->encode([$event, $data]))
      ->then(function($data){
        $cb($res);
        return $res;
      });
  }


}
