<?php

namespace App\Dto;

class StatsDto
{
    public function __construct(
        public readonly string $label,
        public readonly float|int $total
    ) {}
}
