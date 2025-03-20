<?php

declare(ticks = 1);

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$lockKey = "process_lock";
$lockTTL = 60; // 1 minute
$pid = pcntl_fork();

if ($pid === -1) {
    die("Failed to fork process\n");
} elseif ($pid === 0) {
    // 🔹 Child Process: Manages lock in Redis
    $myPid = getmypid();

    // Lua script to acquire or update the lock
    $luaScript = <<<LUA
    if redis.call("GET", KEYS[1]) == ARGV[1] then
        return redis.call("EXPIRE", KEYS[1], ARGV[2])
    elseif redis.call("SETNX", KEYS[1], ARGV[1]) == 1 then
        return redis.call("EXPIRE", KEYS[1], ARGV[2])
    else
        return 0
    end
    LUA;

    function sig_handler($signo) {
        if ($signo == SIGTERM) {
            echo "Child received SIGTERM! from parent exiting...\n";
            exit(0);
        }
    }

    pcntl_signal(SIGTERM, "sig_handler");

    while (true) {
        $result = $redis->eval($luaScript, [$lockKey, $myPid], 1);

        if ($result) {
            echo "Lock acquired/TTL updated by process (PID: $myPid)\n";
        } else {
            echo "Lock is held by another process. Exiting child process.\n";
            break;
        }

        sleep(60); // Wait 1 minute
    }

    exit(0);
} else {
    $counter = 0;
    // 🔹 Parent Process: Runs main logic
    while (true) {
        echo "Main process running...\n";
        sleep(30); // Simulate work
        $counter++;

        if ($counter >= 5) {
            posix_kill($pid, SIGTERM);
            exit(0);
        }

    }
}
