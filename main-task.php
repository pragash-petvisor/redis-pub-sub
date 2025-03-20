<?php

require 'RedisSubscriber.php';

$maxSubTasks = 10;  // Max concurrent sub-tasks
$totalTasks = 30;   // Total tasks to process
$runningTasks = [];
$taskCounter = 0;

function runSubTask($taskNumber)
{
    // Run the sub-task in the background and pass the task number
    $cmd = "php sub_task.php $taskNumber > /dev/null 2>&1 & echo $!";
    return trim(shell_exec($cmd));
}

// Start initial batch of sub-tasks
while ($taskCounter < $maxSubTasks && $taskCounter < $totalTasks) {
    $taskNumber = ++$taskCounter;
    $pid = runSubTask($taskNumber);
    if ($pid) {
        $runningTasks[$taskNumber] = $pid;
        echo "Started sub-task $taskNumber (PID: $pid)\n";
    }
}

$alertCallback = function ($message) {
    echo "Alert received: {$message}\n";
};

$subscriber = new RedisSubscriber('pms_check_channel', $alertCallback);
$subscriber->subscribeWithTimeout(300, function() {
    echo "Timeout reached";
});
// $subscriber->subscribe();
