## Usage example

```
$connection = new PhpAmqpLib\Connection\AMQPStreamConnection('192.168.0.1', 5672, 'test', 'test', 'testing');

$driver = new \TRFDrivers\RabbitMQ\Driver($connection);

$consumer = new TRFConsumer\Consumer($driver);

$consumer->consume("TestQueue", function (TRFConsumer\Interfaces\MQMessage $message) {
    //print msg body
    var_dump($message->body());

    //if ConsumeException is throw, msg go to retry queue
    throw new ConsumeException("Exception test", UnexpectedValueException::class, $message);
});
```