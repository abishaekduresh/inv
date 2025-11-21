<?php
namespace App\Helper;

class ActionMapperHelper
{
    public static function getActionName(string $method, string $endpoint): string
    {
        $map = [
            // // --- Auth ---
            // 'POST /backend/public/api/auth/users/login'    => 'User Logged In',
            // 'POST /api/users/register' => 'User Registered',

            // // --- Business ---
            // 'GET /backend/public/api/business' => 'Business Retrived',
            // 'PUT /api/business/update' => 'Business Profile Updated',
            // 'POST /api/business' => 'Business Created',
            // 'DELETE /api/business/delete' => 'Business Deleted',

            // // --- Invoices ---
            // 'GET /backend/public/api/invoices' => 'Invoice Retrived',
            // 'POST /backend/public/api/invoices' => 'Invoice Created',
            // 'PUT /backend/public/api/invoices'  => 'Invoice Updated',
            // 'DELETE /backend/public/api/invoices' => 'Invoice Deleted',

            // // --- Fallback example ---
            // 'GET /api/dashboard' => 'Dashboard Viewed',
        ];

        $key = strtoupper($method) . ' ' . $endpoint;

        return $map[$key] ?? 'API Request: ' . $key;
    }
}
