<?php

namespace App\Controllers\Reseller;

use App\Core\Request;
use App\Services\DocumentService;

/**
 * Area Documenti del reseller. Stessa libreria vista dall'admin
 * (App\Services\DocumentService + config/documents.php).
 */
class DocumentsController
{
    public function index(Request $request): void
    {
        view('reseller/documents/index', [
            'title'       => 'Documenti',
            'activeMenu'  => 'reseller-documents',
            'groups'      => DocumentService::grouped(),
            'categories'  => DocumentService::CATEGORIES,
            'previewBase' => 'reseller/documents',
        ], 'reseller');
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
