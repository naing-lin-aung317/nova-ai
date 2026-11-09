<?php

namespace App\Enums;

enum CreditReservationStatus: string
{
    case Pending = 'pending';
    case Captured = 'captured';
    case Released = 'released';
}
