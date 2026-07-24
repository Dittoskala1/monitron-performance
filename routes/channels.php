<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ← tambahkan ini di bawah
Broadcast::channel('notifikasi', function ($user) {
    return $user !== null;
});