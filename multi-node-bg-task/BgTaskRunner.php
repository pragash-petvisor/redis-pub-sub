<?php

require './RedisDistributedLock.php';

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$taskName = "pmscheck";
$lockKey = "bg_task_lock_$taskName";
// $lockValue = bin2hex(random_bytes(16)) . '_' . $taskName;
// lockWithoutTTL($redis, $lockKey, $lockValue);

$lockValue = bin2hex(random_bytes(16)) . '_' . $taskName;
lockWithTTL($redis, $lockKey, $lockValue);

function lockWithoutTTL($redis, $lockKey, $lockValue) {
    $distributedLock = new RedisDistributedLock($redis, $lockKey, $lockValue);

    if ($distributedLock->acquire()) {
        echo "Lock acquired with key: $lockKey and value: $lockValue\n";
        
        if ($distributedLock->isHeld()) {
            echo "Lock held already.\n";
        }
        else {
            echo "No lock held.\n";
        }
    
        sleep(10);
        echo "removing lock.\n";
        $distributedLock->release();
    } else {
        echo "Lock is already held by another process.\n";
    }
}


function lockWithTTL($redis, $lockKey, $lockValue) {
    $distributedLock = new RedisDistributedLock($redis, $lockKey, $lockValue, 10);

    if ($distributedLock->acquire()) {
        echo "Lock acquired with key: $lockKey and value: $lockValue\n";
        
        if ($distributedLock->isHeld()) {
            echo "Lock held already\n";
        }
        else {
            echo "No lock held\n";
        }
    
        sleep(10);
        $distributedLock->extendLock();

        echo "removing lock\n";
        sleep(60);
        $distributedLock->release();
    } else {
        echo "Lock is already held by another process.\n";
    }
}



