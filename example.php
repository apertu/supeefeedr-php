<?php
require_once('superfeedr.php');

function on_notification($event) {
	print_r($event);
}

$sf = new Superfeedr('username@superfeedr.com', 'password');
$sf->on_notification('on_notification', null);

print "Subscribing to http://feeds.digg.com/digg/popular.rss: ";
print $sf->subscribe('http://feeds.digg.com/digg/popular.rss');
print "\n";

print "Listing feeds: ";
print_r($sf->listfeeds());
print "\n";

print "Unsubscribing: ";
print $sf->unsubscribe('http://feeds.digg.com/digg/popular.rss');
print "\n";

print "Listing feeds: ";
print_r($sf->listfeeds());
print "\n";

$sf->process();

?>
