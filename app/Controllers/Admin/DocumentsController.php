<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Services\DocumentService;

/**
 * Area Documenti dell'amministratore. Stessa libreria vista dai reseller
 * (App\Services\DocumentService + config/documents.php).
 */
class DocumentsController
{
    public function index(Request $request): void
    {
        view('admin/documents/index', [
            'title'       => 'Documenti',
            'activeMenu'  => 'documents',
            'groups'      => DocumentService::grouped(),
            'categories'  => DocumentService::CATEGORIES,
            'previewBase' => 'admin/documents',
        ], 'admin');
    }

    public function preview(Request $request): void
    {
        DocumentService::serve((string)$request->param('key'), 'inline');
    }

    public function download(Request $request): void
    {
        DocumentService::serve((string)$request->param('key'), 'attachment');
    }
}
