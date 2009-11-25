<?php
require_once dirname(__FILE__) . "/XMPPHP/XMPP.php";

class Superfeedr extends XMPPHP_XMPP {

	public function __construct($jid, $password, $server=null) {
		$jidarr = split('@', $jid, 2);
		$server = $jidarr[1];
		$user = $jidarr[0];
		$host = 'xmpp.superfeedr.com';
		$this->addXPathHandler('{jabber:client}message/{http://jabber.org/protocol/pubsub#event}event/{http://superfeedr.com/xmpp-pubsub-ext}status', 'handle_superfeedr_msg');
		XMPPHP_XMPP::__construct($host, 5222, $user, $password, 'superfeedr', $server, false, XMPPHP_Log::LEVEL_INFO);
		print $jid;
        print $server;
        $this->useEncryption(false);
		$this->connect();
		$payloads = $this->processUntil(array('session_start', 'end_stream'));
		foreach($payloads as $event) {
			$pl = $event[1];
			switch($event[0]) {
				case 'session_start':
				break;
				case 'end_stream':
				break;
			}
		}
	}

	private function handle_superfeedr_msg($xml) {
		$event = array();
		$event['xml'] = $xml;
		$event['feed'] = $xml->sub('event')->sub('status')->attrs['feed'];
		$httpx = $xml->sub('event')->sub('status')->sub('http');
		$event['http'] = array($httpx->attrs['code'], $httpx->data);
		$event['next_fetch'] = $xml->sub('event')->sub('status')->sub('next_fetch')->data;
		$event['entries'] = array();
		foreach($xml->sub('event')->sub('items')->subs as $item) {
			$newy = array();
			$new['title'] =  $item->sub('entry')->sub('title')->data;
			$new['summary'] = $item->sub('entry')->sub('summary')->data;
			$linkx = $item->sub('entry')->sub('link');
			$new['link'] = $linkx->attrs;
			$new['id'] = $item->sub('entry')->sub('id')->data;
			$new['published'] = $item->sub('entry')->sub('published')->data;
			$event['entries'][] = $new;
		}
		$this->event('superfeedr_msg', $event);
	}

	public function subscribe($feed) {
		$id = $this->getID();
		$this->addIdHandler($id, 'handle_sf_subscribe', $this);
		$iq = "<iq type='set'  to='firehoser.superfeedr.com' id='$id'>
<pubsub xmlns='http://jabber.org/protocol/pubsub'>
<subscribe node='$feed' jid='{$this->basejid}'/>
</pubsub>
</iq>";
		$this->send($iq);
		$events = $this->processUntil(array('superfeedr_subscribe_result'));
		foreach($events as $event) {
			if($event[0] == 'superfeedr_subscribe_result') return $event[1];
		}
	}

	public function unsubscribe($feed) {
		$id = $this->getID();
		$this->addIdHandler($id, 'handle_sf_unsubscribe', $this);
		$iq = "<iq type='set' to='firehoser.superfeedr.com' id='$id'>
<pubsub xmlns='http://jabber.org/protocol/pubsub'>
<unsubscribe node='$feed' jid='{$this->basejid}'/>
</pubsub>
</iq>";
		$this->send($iq);
		$events = $this->processUntil(array('superfeedr_unsubscribe_result'));
		foreach($events as $event) {
			if($event[0] == 'superfeedr_unsubscribe_result') return $event[1];
		}
	}

	public function listfeeds($page=1) {
		$id = $this->getID();
		$this->addIdHandler($id, 'handle_sf_list', $this);
		$iq = "<iq type='get' to='firehoser.superfeedr.com' id='$id'>
<pubsub xmlns='http://jabber.org/protocol/pubsub' xmlns:superfeedr='http://superfeedr.com/xmpp-pubsub-ext'>
<subscriptions jid='{$this->basejid}' superfeedr:page='$page'/>
</pubsub>
</iq>";
		$this->send($iq);
		$events = $this->processUntil(array('superfeedr_list_result'));
		foreach($events as $event) {
			if($event[0] == 'superfeedr_list_result') return $event[1];
		}
	}

	public function on_notification($handler, $obj=null) {
		$this->addEventHandler('superfeedr_msg', $handler, $obj);
	}

	public function handle_sf_list($xml) {
		$subs = array();
		foreach($xml->sub('pubsub')->sub('subscriptions')->subs as $sub) {
			$subs[] = $sub->attrs['node'];
		}
		$this->event('superfeedr_list_result', $subs);
	}

	public function handle_sf_subscribe($xml) {
		if($xml->attrs['type'] == 'result') {
			$r = true;
		} else {
			$r = false;
		}
		$this->event('superfeedr_subscribe_result', $r);
	}
	
	public function handle_sf_unsubscribe($xml) {
		if($xml->attrs['type'] == 'result') {
			$r = true;
		} else {
			$r = false;
		}
		$this->event('superfeedr_unsubscribe_result', $r);
	}

}

?>
