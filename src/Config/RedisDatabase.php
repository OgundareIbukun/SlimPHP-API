<?php
	namespace App\Config;

	use Predis\Client;
	use Predis\Connection\ConnectionException;

	define("server", serialize([ 'host' => '127.0.0.1', 'port' => 6379, 'database' => 15, ]));

	class RedisDatabase
	{
		public function redis_version($info)
		{
		    if (isset($info['Server']['redis_version'])) {
		        return $info['Server']['redis_version'];
		    } elseif (isset($info['redis_version'])) {
		        return $info['redis_version'];
		    } else {
		        return 'unknown version';
		    }
		}

		public function single_client() {

			$single_server = unserialize(server);
			try {
			    $client = $this->single_server();
			    $client->connect();

			    $is_connected = true;
			    
			} catch(ConnectionException $e) {
			    // echo $e->getMessage();
				$is_connected = false;
			}
			return $is_connected; 
		}

		public function single_server() {
			$single_server = unserialize(server);
			$server = new Client($single_server);
			return $server;
		}

		public function multiple_servers() {

			$multiple_servers = [
			    [ 'host' => '127.0.0.1', 'port' => 6379, 'database' => 15, 'alias' => 'first', ],
			    [ 'host' => '127.0.0.1', 'port' => 6380, 'database' => 15, 'alias' => 'second', ],
			];

			return $multiple_servers;
		}

	}