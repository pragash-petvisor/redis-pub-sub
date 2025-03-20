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
            $this->redis->setOption(Redis::OPT_TCP_KEEPALIVE, 1);  // Enable keepalive
            $this->redis->setOption(Redis::OPT_TCP_KEEPALIVE_TIME, 60); // First probe after 60 seconds
            $this->redis->setOption(Redis::OPT_TCP_KEEPALIVE_INTERVAL, 30); // Probe interval every 15 seconds
            $this->redis->setOption(Redis::OPT_TCP_KEEPALIVE_PROBES, 5);  // Retry 5 times before disconnecting            
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
    // public function subscribe()
    // {
    //     if ($this->isSubscribed) {
    //         return;
    //     }

    //     try {
    //         echo "Subscribing to channel: {$this->channel}\n";
    //         $this->redis->subscribe([$this->channel], function ($redis, $channel, $message) {
    //             echo "Message received on channel '{$channel}': {$message}\n";
    //             call_user_func($this->callback, $message);
    //         });

    //         $this->isSubscribed = true;
    //     } catch (RedisException $e) {
    //         echo "Subscription error: " . $e->getMessage() . "\n";
    //         $this->reconnect();
    //     }
    // }

    // Close the Redis connection
    public function close()
    {
        if ($this->redis) {
            $this->redis->close();
            echo "Connection closed\n";
        }
    }

    public function subscribeWithTimeout(int $timeout, callable $onTimeout): bool
    {
        if ($this->isSubscribed) {
            return true;
        }

        // Create shared memory for tracking the last received time
        $shmKey = ftok(__FILE__, 't');
        $shmId = shmop_open($shmKey, "c", 0644, 8);
        shmop_write($shmId, pack("L", time()), 0); // Store current timestamp

        $pid = pcntl_fork();

        if ($pid == -1) {
            // Fork failed
            echo "Failed to fork process\n";
            return false;
        } elseif ($pid) {
            // Parent process: acts as a watchdog to monitor timeout
            while (true) {
                sleep(1);

                // Read last received message time
                $lastReceivedTime = unpack("L", shmop_read($shmId, 0, 8))[1];
                $timeDifference = time() - $lastReceivedTime;

                echo "Time elapsed since last message: $timeDifference seconds\n";

                if ($timeDifference > $timeout) {
                    echo "Subscription timeout reached, executing custom script\n";

                    // Call timeout handler
                    call_user_func($onTimeout);

                    // Kill the subscription process
                    posix_kill($pid, SIGTERM);
                    pcntl_waitpid($pid, $status); // Cleanup zombie process

                    shmop_delete($shmId);
                    // shmop_close($shmId);

                    return false;
                }
            }
        } else {
            // Child process: runs the Redis subscription
            try {
                echo "Subscribing to channel: {$this->channel}\n";

                $this->redis->subscribe([$this->channel], function ($redis, $channel, $message) use ($shmId) {
                    echo "Message received on channel '{$channel}': {$message}\n";
                    call_user_func($this->callback, $message);

                    // Update last received message time in shared memory
                    shmop_write($shmId, pack("L", time()), 0);
                });

                exit(0); // Exit child process when done
            } catch (RedisException $e) {
                echo "Subscription error: " . $e->getMessage() . "\n";
                exit(1);
            }
        }

        return true;
    }

}

