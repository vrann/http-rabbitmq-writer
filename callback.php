<?php
/**
 * Entry point to Chat Bot
 */

include __DIR__ . "/vendor/autoload.php";
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('my_logger');
$logger->pushHandler(new StreamHandler('/tmp/http-rabbitmq-writer.log', Logger::DEBUG));
$logger->addInfo('Callback activated');

$jsonString = file_get_contents('php://input');
if (empty($jsonString)) {
    $logger->addCritical("No request, no input");
    die();
}
$logger->addDebug($jsonString);

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$topic = 'callback.received';
$channel->queue_declare($topic, false, false, false, false);
$msg = new AMQPMessage($jsonString, ['message_id' => md5(uniqid($topic))]);
$channel->basic_publish($msg, '', $topic);
$channel->close();
$connection->close();

$logger->addInfo('Request is written');