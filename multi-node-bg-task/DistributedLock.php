<?php

declare(strict_types=1);

namespace Vetstoria\Framework\Application\DistributedLock;

interface DistributedLock
{
    public function acquire(): bool;
    public function extendLock(): bool;    
    public function release(): bool;   
    public function isHeld(): bool;  
}