<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;

class UnsubscribeController
{
    public function show(Request $request): void
    {
        $token = $request->param('token');
        $db = Database::getInstance();

        // Look up token
        $stmt = $db->prepare('SELECT u.tenant_id, u.email, t.name AS tenant_name
                              FROM email_unsubscribes u
                              JOIN tenants t ON u.tenant_id = t.id
                              WHERE u.token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $record = $stmt->fetch();

        if (!$record) {
            $tenantName = '';
            $success = false;
        } else {
            $tenantName = $record['tenant_name'];
            $success = true;

            // Mark customer as unsubscribed
            $db->prepare(
                'UPDATE customers SET unsubscribed = 1, unsubscribed_at = NOW()
                 WHERE tenant_id = :tid AND email = :email AND unsubscribed = 0'
            )->execute(['tid' => $record['tenant_id'], 'email' => $record['email']]);
        }

        // Render standalone page (no layout)
        view('email/unsubscribe', [
            'tenantName' => $tenantName,
            'success'    => $success,
        ]);
    }
}
