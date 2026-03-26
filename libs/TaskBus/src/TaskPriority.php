<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus;

enum TaskPriority: int
{
    case Low = -10;
    case Normal = 0;
    case High = 10;
    case Critical = 20;
}
