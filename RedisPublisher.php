<?php

class RedisPublisher
{
    private $redis;
    private $maxRetries;
    private $retryDelay;

    public function __construct($maxRetries = 5, $retryDelay = 2)
    {
        // Initialize Redis connection
        $this->redis = new Redis();
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
    }

    // Function to attempt publishing a message
    public function publish($channel, $message)
    {
        $attempts = 0;

        while ($attempts < $this->maxRetries) {
            try {
                if ($this->redis->connect('127.0.0.1', 6379)) {
                    $this->redis->publish($channel, $message);
                    echo "Message published to channel '$channel': $message\n";
                    return true;  // Successfully published
                }
            } catch (RedisException $e) {
                // Handle connection failure
                echo "Failed to connect to Redis: " . $e->getMessage() . "\n";
            }

            // Increment the attempt counter
            $attempts++;

            // If attempts are exhausted, stop retrying
            if ($attempts >= $this->maxRetries) {
                echo "Failed to publish message after {$this->maxRetries} attempts.\n";
                return false;
            }

            // Wait before retrying
            echo "Retrying in {$this->retryDelay} seconds...\n";
            sleep($this->retryDelay);
        }
    }
}

?>

