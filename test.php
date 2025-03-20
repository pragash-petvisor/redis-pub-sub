<?php

$reflection = new ReflectionClass('Redis');
print_r($reflection->getConstants());