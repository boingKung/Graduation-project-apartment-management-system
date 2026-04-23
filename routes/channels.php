<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Tenant;

// Private channel for each tenant
Broadcast::channel('tenant.{tenantId}', function ($user, $tenantId) {
    // User is authenticated via tenant guard (thanks to AuthenticateTenantForBroadcast middleware)
    // Check if the authenticated user is the same tenant requesting the channel
    if ($user && $user instanceof Tenant && (string)$user->id === (string)$tenantId) {
        return true;
    }
    return false;
});

