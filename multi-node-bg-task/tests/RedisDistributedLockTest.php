<?php

require_once __DIR__ . '/../RedisDistributedLock.php';

use PHPUnit\Framework\TestCase;

class RedisDistributedLockTest extends TestCase
{
    private $redisMock;
    private RedisDistributedLock $lock;
    private string $lockKey = 'test_lock';
    private string $lockValue = 'process_123';
    private int $ttl = 30;

    protected function setUp(): void
    {
        $this->redisMock = $this->createMock(\Redis::class);
        $this->lock = new RedisDistributedLock($this->redisMock, $this->lockKey, $this->lockValue, $this->ttl);
    }

    public function testAcquireLockSuccessfully(): void
    {
        $this->redisMock
            ->expects($this->once())
            ->method('eval')
            ->with($this->anything(), [$this->lockKey, $this->lockValue, $this->ttl], 1)
            ->willReturn(1);

        $this->assertTrue($this->lock->acquire());
    }

    public function testAcquireLockFails(): void
    {
        $this->redisMock
            ->expects($this->once())
            ->method('eval')
            ->with($this->anything(), [$this->lockKey, $this->lockValue, $this->ttl], 1)
            ->willReturn(0);

        $this->assertFalse($this->lock->acquire());
    }

    public function testExtendLockSuccessfully(): void
    {
        $this->redisMock
            ->expects($this->once())
            ->method('eval')
            ->with($this->anything(), [$this->lockKey, $this->lockValue, $this->ttl], 1)
            ->willReturn(1);

        $this->assertTrue($this->lock->extendLock());
    }

    public function testExtendLockFails(): void
    {
        $this->redisMock
            ->expects($this->once())
            ->method('eval')
            ->with($this->anything(), [$this->lockKey, $this->lockValue, $this->ttl], 1)
            ->willReturn(0);

        $this->assertFalse($this->lock->extendLock());
    }

    public function testReleaseLockSuccessfully(): void
    {
        $this->redisMock
            ->expects($this->once())
            ->method('eval')
            ->with($this->anything(), [$this->lockKey, $this->lockValue], 1)
            ->willReturn(1);

        $this->assertTrue($this->lock->release());
    }

    public function testReleaseLockFails(): void
    {
        $this->redisMock
            ->expects($this->once())
            ->method('eval')
            ->with($this->anything(), [$this->lockKey, $this->lockValue], 1)
            ->willReturn(0);

        $this->assertFalse($this->lock->release());
    }

    public function testIsHeldReturnsTrue(): void
    {
        $this->redisMock
            ->expects($this->once())
            ->method('get')
            ->with($this->lockKey)
            ->willReturn($this->lockValue);

        $this->assertTrue($this->lock->isHeld());
    }

    public function testIsHeldReturnsFalse(): void
    {
        $this->redisMock
            ->expects($this->once())
            ->method('get')
            ->with($this->lockKey)
            ->willReturn(null);

        $this->assertFalse($this->lock->isHeld());
    }
}
