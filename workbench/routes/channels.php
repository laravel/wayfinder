<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('orders.{orderId}', function ($user, $orderId) {
    return true;
});

Broadcast::channel('user.{userId}.notifications', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('chat.{roomId}.messages', function ($user, $roomId) {
    return true;
});

Broadcast::channel('public-announcements', function ($user) {
    return true;
});
