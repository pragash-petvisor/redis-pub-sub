<?php

if ($argc < 2) {
    die("Usage: php sub_task.php <task_number>\n");
}

$taskNumber = $argv[1];

$redis = new Redis();
$redis->connect('localhost', 6379); // Connect to Redis

echo "Sub-task $taskNumber (PID: " . getmypid() . ") started\n";
$interval = rand(2, 5);

echo "Sleep interval is " . $interval;

sleep($interval);

// Notify main task via Redis
$redis->publish('pms_check_channel', $taskNumber);

echo "Sub-task $taskNumber (PID: " . getmypid() . ") completed\n";
exit(0);
