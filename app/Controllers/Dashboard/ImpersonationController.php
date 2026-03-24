<?php

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuditLog;

class ImpersonationController
{
    public function stop(Request $request): void
    {
        if (!Auth::isImpersonating()) {
            Response::redirect(url('dashboard'));
            return;
        }

        $impersonatedName = Auth::user()['name'] ?? '';
        $impersonatedId = Auth::id();
        $originalAdminId = Auth::originalAdminId();

        AuditLog::log(
            AuditLog::IMPERSONATION_END,
            "Admin ID:{$originalAdminId} ha terminato impersonation di {$impersonatedName} (ID:{$impersonatedId})",
            $originalAdminId
        );

        Auth::stopImpersonation();

        Response::redirect(url('admin/users'));
    }
}
