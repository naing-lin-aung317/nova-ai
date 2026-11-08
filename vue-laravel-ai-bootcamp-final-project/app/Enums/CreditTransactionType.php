<?php

namespace App\Enums;

enum CreditTransactionType: string
{
    case SignupBonus = 'signup_bonus';
    case Purchase = 'purchase';
    case Reserve = 'reserve';
    case Capture = 'capture';
    case Release = 'release';
    case Refund = 'refund';
    case AdminAdjustment = 'admin_adjustment';
}
