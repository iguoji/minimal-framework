<?php
declare(strict_types=1);

namespace Minimal\Contracts;

interface Annotation
{
    public function handle(array $context) : mixed;
    public function getTargets() : array;
    public function getPriority() : int;
}