<?php
/**
 * Sends responses to Facebook API from the RabbitMQ
 */

include __DIR__ . "/vendor/autoload.php";
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
const TOKEN = 'EAAEg1NXI6wABAGOpnSPZAU9ltfwKZBDj38eu2Sj5aPl7pDWcZCXkPluoK4lshJnHiWKUxBZC4F5kPJPfR2q1DZAw2H9Xr8ODR44g0wVgrd70cCl3WsFo3eE62CiqYTixsmGMvFIutNzeqqEeRNXjLMmjTTZCY8EvOOaScgd7gmuAZDZD';

$logger = new Logger('my_logger');
$logger->pushHandler(new StreamHandler('/tmp/http-rabbitmq-responser.log', Logger::DEBUG));
$logger->addInfo('Responser activated');

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
/** @var \PhpAmqpLib\Channel\AMQPChannel $channel */

$channel = $connection->channel();
$topic = 'callback.received';
$queue = 'queue.vrann.magebot.api.messageSenderInterface.sendMessage';

/**
 * @param $msg
 */
$callback = function($msg) use ($logger) {
    /**
     * @var Logger $logger
     */
    $message = json_decode($msg->body, true)['message'];
    $logger->addDebug(' [x] ' . $msg->delivery_info['routing_key'] . ':' . $message . "\n");
    $transport = new \Vrann\FbChatBot\Transport\Http(TOKEN, $logger);
    $transport->send($message);
};

$channel->basic_consume($queue, '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();
$logger->addInfo('Response is sent');