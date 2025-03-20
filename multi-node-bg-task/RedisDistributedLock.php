<?php

declare(strict_types=1);

// namespace Vetstoria\Framework\Application\DistributedLock;

// use Vetstoria\Framework\Application\WebApplication;

class RedisDistributedLock
{
    private string $lockKey;
    private string $lockValue;
    private ?int $ttl;
    private \Redis $redis;

    private $lockAcquireLockScript = <<<LUA
    local key = KEYS[1]
    local lockValue = ARGV[1]
    local ttl = tonumber(ARGV[2])

    local currentLock = redis.call("GET", key)

    if not currentLock then
        if ttl and ttl > 0 then
            redis.call("SET", key, lockValue, "NX", "EX", ttl)
        else
            redis.call("SET", key, lockValue, "NX")
        end
        return 1
    elseif currentLock == lockValue then
        return 1
    else
        return 0
    end
    LUA;

    private $lockExtensionScript = <<<LUA
    local key = KEYS[1]
    local lockValue = ARGV[1]
    local ttl = ARGV[2]
    
    local currentLock = redis.call("GET", key)
    
    if currentLock == lockValue then
        if ttl then
            redis.call("EXPIRE", key, ttl)
        end
        return 1
    else
        return 0
    end
    LUA;

    private $lockReleaseScript = <<<LUA
    local key = KEYS[1]
    local lockValue = ARGV[1]
    
    if redis.call("GET", key) == lockValue then
        redis.call("DEL", key)
        return 1
    else
        return 0
    end
    LUA;

    public function __construct(\Redis $redis, string $lockKey, string $lockValue, ?int $ttl = null)
    {
        $this->redis = $redis;
        $this->lockKey = $lockKey;
        $this->lockValue = $lockValue;
        $this->ttl = $ttl;
    }

    public function acquire(): bool
    {
        $result = $this->redis->eval($this->lockAcquireLockScript, [$this->lockKey, $this->lockValue, $this->ttl], 1);

        if ($result == 1) {
            echo "Lock acquired successfully.\n";
            return true;
        } else {
            echo "Lock already held by another process.\n";
            return false;
        }
    }

    public function extendLock(): bool
    {
        if ($this->ttl === null || $this->ttl <= 0) {
            echo "Invalid TTL value $this->ttl.\n";
            return false;
        }

        return (bool) $this->redis->eval($this->lockExtensionScript, [$this->lockKey, $this->lockValue, $this->ttl], 1);
    }

    public function release(): bool
    {
        return (bool) $this->redis->eval($this->lockReleaseScript, [$this->lockKey, $this->lockValue], 1);
    }

    public function isHeld(): bool
    {
        return $this->redis->get($this->lockKey) === $this->lockValue;
    }
}