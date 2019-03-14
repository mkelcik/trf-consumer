## Usage example

```
$connection = new PhpAmqpLib\Connection\AMQPStreamConnection('192.168.0.115', 5672, 'test', 'test', 'testing');

$driver = new \TRFDrivers\RabbitMQ\Driver($connection);

$consumer = new TRFConsumer\Consumer($driver, 'my-consumer-tag', 5);

// consume 'Testing' queue
$consumer->consume("Testing", function (TRFConsumer\Interfaces\MQMessage $message) {

    // print msg content
    var_dump($message->body());

    //process msg ...

    //Exception happened, message will be send to Testing-retry queue to be processed later, after 5 unsuccessful attempts will be send to Testing-fail queue
    throw new \Exception("Process error, retry processing later");
});
```