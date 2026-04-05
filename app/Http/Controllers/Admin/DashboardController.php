<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Entity;
use App\Models\Filiale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $totalDocuments = Document::where('is_active', true)->count();
        $totalUsers     = User::count();
        $activeUsers    = User::where('is_active', true)->count();
        $totalEntities  = Entity::where('is_active', true)->count();
        $totalFiliales  = Filiale::where('is_active', true)->count();
        $totalDownloads = Document::sum('download_count');

        $totalSize = Document::where('is_active', true)->sum('file_size');

        $byType = Document::where('is_active', true)
            ->select('file_type', DB::raw('count(*) as count'))
            ->groupBy('file_type')
            ->pluck('count', 'file_type');

        $byYear = Document::where('is_active', true)
            ->select('year', DB::raw('count(*) as count'))
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->limit(5)
            ->get();

        $byEntity = Document::where('is_active', true)
            ->with('entity:id,name')
            ->select('entity_id', DB::raw('count(*) as count'))
            ->groupBy('entity_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name'  => $row->entity?->name ?? '—',
                'count' => $row->count,
            ]);

        $recentDocuments = Document::with(['entity', 'category', 'uploader:id,name'])
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get(['id', 'title', 'file_type', 'file_extension', 'entity_id', 'category_id', 'uploaded_by', 'created_at', 'download_count']);

        $topDownloaded = Document::where('is_active', true)
            ->with('entity:id,name')
            ->orderByDesc('download_count')
            ->limit(5)
            ->get(['id', 'title', 'file_type', 'file_extension', 'entity_id', 'download_count']);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'documents'      => $totalDocuments,
                'users'          => $totalUsers,
                'active_users'   => $activeUsers,
                'entities'       => $totalEntities,
                'filiales'       => $totalFiliales,
                'downloads'      => $totalDownloads,
                'total_size'     => $totalSize,
            ],
            'by_type'          => $byType,
            'by_year'          => $byYear,
            'by_entity'        => $byEntity,
            'recent_documents' => $recentDocuments,
            'top_downloaded'   => $topDownloaded,
        ]);
    }
}
