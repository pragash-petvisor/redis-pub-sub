<?php

require './RedisDistributedLock.php';

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$taskName = "pmscheck";
$lockKey = "bg_task_lock_$taskName";
$lockValue = bin2hex(random_bytes(16)) . '_' . $taskName;

$distributedLock = new RedisDistributedLock($redis, $lockKey, $lockValue);

if ($distributedLock->acquire()) {
    echo "Lock acquired with key: $lockKey and value: $lockValue\n";
    executeTask($lockKey, $lockValue);
} else {
    echo "Lock is already held by another process.\n";
}



function executeTask(string $lockKey, string $lockValue) {
    exec("php PmsCheckTask.php $lockKey $lockValue");
}
