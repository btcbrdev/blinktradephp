<?php

require_once("vendor/autoload.php");                // Composer autoloader

$loop = \React\EventLoop\Factory::create();

$logger = new \Zend\Log\Logger();
$writer = new Zend\Log\Writer\Stream("php://output");
$logger->addWriter($writer);

$client = new \Devristo\Phpws\Client\WebSocket("wss://api.blinktrade.com/trade/", $loop, $logger);

$client->on("request", function($headers) use ($logger){
    $logger->notice("Request object created!");
});

$client->on("handshake", function() use ($logger) {
    $logger->notice("Handshake received!");
});

$client->on("connect", function($headers) use ($logger, $client){
    $logger->notice("Connected!");
    $client->send('{"MsgType": "e", "SecurityStatusReqID": 666, "SubscriptionRequestType": "1", "Instruments": ["BTCBRL"]}');
});

$client->on("message", function($message) use ($client, $logger){
    $logger->notice("Got message: ".$message->getData());
    //$client->close();
});

$client->open();
$loop->run();
