<?php

require 'RedisPublisher.php';

if ($argc < 2) {
    die("Usage: php sub_task.php <task_number>\n");
}

$taskNumber = $argv[1];

echo "Sub-task $taskNumber (PID: " . getmypid() . ") started\n";
$interval = rand(2, 5);

echo "Sleep interval is " . $interval;

sleep($interval);

$publisher = new RedisPublisher();
$publisher->publish('pms_check_channel', $taskNumber);

echo "Sub-task $taskNumber (PID: " . getmypid() . ") completed\n";
exit(0);
