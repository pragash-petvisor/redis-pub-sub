<?php

class RedisSubscriber
{
    private $redis;
    private $channel;
    private $callback;
    private $isSubscribed = false;
    private $maxRetries = 5; // Maximum number of retry attempts
    private $retryInterval = 5; // Wait time (in seconds) before retrying

    // Constructor to initialize Redis, channel, and callback
    public function __construct($channel, callable $callback)
    {
        $this->channel = $channel;
        $this->callback = $callback;
        $this->connect();
    }

    // Connect to Redis
    private function connect()
    {
        try {
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1', 6379);
            echo "Connected to Redis\n";
        } catch (RedisException $e) {
            echo "Failed to connect to Redis: " . $e->getMessage() . "\n";
            $this->reconnect();
        }
    }

    // Handle reconnection attempts
    private function reconnect()
    {
        echo "*********";
        $attempts = 0;

        while ($attempts < $this->maxRetries) {
            echo "Reconnection attempt " . ($attempts + 1) . " of {$this->maxRetries}...\n";
            sleep($this->retryInterval);

            try {
                $this->redis->connect('127.0.0.1', 6379);
                echo "Reconnected to Redis successfully!\n";
                $this->subscribe(); // Resume subscription
                return;
            } catch (RedisException $e) {
                echo "Reconnection failed: " . $e->getMessage() . "\n";
                $attempts++;
            }
        }

        echo "Max reconnection attempts reached. Giving up.\n";
    }

    // Subscribe to Redis channel and trigger the callback when a message is received
    public function subscribe()
    {
        if ($this->isSubscribed) {
            return;
        }

        try {
            echo "Subscribing to channel: {$this->channel}\n";
            $this->redis->subscribe([$this->channel], function ($redis, $channel, $message) {
                echo "Message received on channel '{$channel}': {$message}\n";
                call_user_func($this->callback, $message);
            });

            $this->isSubscribed = true;
        } catch (RedisException $e) {
            echo "Subscription error: " . $e->getMessage() . "\n";
            $this->reconnect();
        }
    }

    // Close the Redis connection
    public function close()
    {
        if ($this->redis) {
            $this->redis->close();
            echo "Connection closed\n";
        }
    }
}

