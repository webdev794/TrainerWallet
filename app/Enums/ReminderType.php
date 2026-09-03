<?php

namespace App\Enums;

enum ReminderType: string
{
    case PreDue = 'pre_due';
    case Overdue = 'overdue';
}
