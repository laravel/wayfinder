<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Mail = 'mail';
    case Database = 'database';
}
