### Setup redis in docker
 docker pull redis:latest
 docker run -d -p 6379:6379 redis

### Run main task

 php main-task.php

### Run sub task

 php sub-task.php ANY_RANDOM_NUMBER

### Does application reconnect if redis is down

The main task will retry 5 times to connect and subscribe. If it fails it will exit the process.
