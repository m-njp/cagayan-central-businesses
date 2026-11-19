<?php

namespace App\Http\Controllers;

use App\Queries\FinanceEvidenceQuery;
use Inertia\Inertia;
use Inertia\Response;

final class PublicSourcesController extends Controller
{
    public function __invoke(FinanceEvidenceQuery $evidence): Response
    {
        return Inertia::render('public/sources/index', [
            'citations' => $evidence->publicCatalog(),
        ]);
    }
}
