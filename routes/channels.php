<?php

use Illuminate\Support\Facades\Broadcast;

// User-specific private channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Passenger-specific private channel for flight updates
Broadcast::channel('passenger.{passengerId}', function ($user, $passengerId) {
    // Allow if user is staff/admin or if user is the passenger's owner
    if (in_array($user->role ?? 'passenger', ['admin', 'staff'])) {
        return true;
    }
    
    // For passengers, check if they own this passenger record
    return \App\Models\Passenger::where('id', $passengerId)
        ->where('email', $user->email)
        ->exists();
});

// Staff-only channels
Broadcast::channel('staff.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId && 
           in_array($user->role ?? 'passenger', ['admin', 'staff']);
});

// Admin-only channel
Broadcast::channel('admin', function ($user) {
    return ($user->role ?? 'passenger') === 'admin';
});

// Staff presence channel
Broadcast::channel('staff-online', function ($user) {
    if (in_array($user->role ?? 'passenger', ['admin', 'staff'])) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ];
    }
    return false;
});

// Flight-specific staff monitoring
Broadcast::channel('flight.{flightId}.staff', function ($user, $flightId) {
    if (in_array($user->role ?? 'passenger', ['admin', 'staff'])) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ];
    }
    return false;
});
