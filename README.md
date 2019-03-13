## Usage example

```


$driver = new RFDrivers\RabbitMQ\Driver($connection);

$consumer = new TRFConsumer\Consumer($driver);

$consumer->consume(function (TRFConsumer\Interfaces\MQMessage $message) {
            var_dump($message->body());
            
            //if ConsumeException is throw, msg go to retry queue
            throw new ConsumeException("Exception test", UnexpectedValueException::class, $message);
        });
```