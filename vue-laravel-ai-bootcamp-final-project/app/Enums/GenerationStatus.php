<?php

namespace App\Enums;

enum GenerationStatus: string
{
    case Pending = 'pending';
    case Streaming = 'streaming';
    case Completed = 'completed';
    case Failed = 'failed';
    case Stopped = 'stopped';
}
