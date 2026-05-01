<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use App\Models\Entity;
use App\Models\Filiale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    // Extensions autorisées par type avec leur MIME réel
    private const ALLOWED_MIMES = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'bmp'  => 'image/bmp',
        'mp4'  => 'video/mp4',
        'avi'  => 'video/x-msvideo',
        'mov'  => 'video/quicktime',
        'wmv'  => 'video/x-ms-wmv',
        'mkv'  => 'video/x-matroska',
        'webm' => 'video/webm',
        'mp3'  => 'audio/mpeg',
        'wav'  => 'audio/wav',
        'ogg'  => 'audio/ogg',
        'aac'  => 'audio/aac',
        'm4a'  => 'audio/mp4',
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    public function index(Request $request): Response
    {
        $query = Document::with(['entity', 'filiale', 'category', 'subcategory'])
            ->where('is_active', true);

        if ($request->entity_id)      $query->whereIn('entity_id', (array) $request->entity_id);
        if ($request->filiale_id)     $query->whereIn('filiale_id', (array) $request->filiale_id);
        if ($request->category_id)    $query->whereIn('category_id', (array) $request->category_id);
        if ($request->subcategory_id) $query->whereIn('subcategory_id', (array) $request->subcategory_id);
        if ($request->year)           $query->whereIn('year', (array) $request->year);
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $user = Auth::user();
        if ($user->isRmc() && $user->filiale_id) {
            $query->where('filiale_id', $user->filiale_id);
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(24)->withQueryString();

        $baseQuery = Document::where('is_active', true);
        if ($user->isRmc() && $user->filiale_id) {
            $baseQuery->where('filiale_id', $user->filiale_id);
        }

        $counts = [
            'entity'   => (clone $baseQuery)->whereNotNull('entity_id')->groupBy('entity_id')
                            ->selectRaw('entity_id as id, count(*) as total')->pluck('total', 'id'),
            'filiale'  => (clone $baseQuery)->whereNotNull('filiale_id')->groupBy('filiale_id')
                            ->selectRaw('filiale_id as id, count(*) as total')->pluck('total', 'id'),
            'category' => (clone $baseQuery)->whereNotNull('category_id')->groupBy('category_id')
                            ->selectRaw('category_id as id, count(*) as total')->pluck('total', 'id'),
            'year'     => (clone $baseQuery)->whereNotNull('year')->groupBy('year')
                            ->selectRaw('year as id, count(*) as total')->pluck('total', 'id'),
        ];

        return Inertia::render('Library/Index', [
            'documents'  => $documents,
            'entities'   => Entity::where('is_active', true)->get(),
            'filiales'   => Filiale::where('is_active', true)->get(),
            'categories' => Category::with('subcategories')->get(),
            'years'      => Document::where('is_active', true)->distinct()->orderBy('year', 'desc')->pluck('year'),
            'filters'    => $request->only(['entity_id', 'filiale_id', 'category_id', 'subcategory_id', 'year', 'search']),
            'counts'     => $counts,
        ]);
    }

    public function store(Request $request)
    {
        $allowedExtensions = implode(',', array_keys(self::ALLOWED_MIMES));
        $allowedMimes      = implode(',', array_unique(array_values(self::ALLOWED_MIMES)));

        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string|max:5000',
            'file'           => "required|file|max:1048576|mimes:{$allowedExtensions}|mimetypes:{$allowedMimes}",
            'entity_id'      => 'required|exists:entities,id',
            'filiale_id'     => 'nullable|exists:filiales,id',
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'year'           => 'required|integer|min:2000|max:2099',
        ]);

        $file      = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $fileType  = $this->getFileType($extension);
        $fileName  = Str::slug($request->title) . '_' . time() . '.' . $extension;
        $path      = $file->storeAs("documents/{$request->entity_id}/{$request->year}", $fileName, 'local');

        $thumbnailPath = $fileType === 'image' ? $path : null;

        Document::create([
            'title'          => $request->title,
            'description'    => $request->description,
            'file_path'      => $path,
            'file_type'      => $fileType,
            'file_extension' => $extension,
            'file_size'      => $file->getSize(),
            'thumbnail_path' => $thumbnailPath,
            'entity_id'      => $request->entity_id,
            'filiale_id'     => $request->filiale_id,
            'category_id'    => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'year'           => $request->year,
            'uploaded_by'    => Auth::id(),
        ]);

        return back()->with('success', 'Document ajouté avec succès.');
    }

    public function destroy(Document $document)
    {
        $user = Auth::user();

        if (!$user->canDelete()) {
            abort(403);
        }

        if ($user->isRmc() && $document->filiale_id !== $user->filiale_id) {
            abort(403);
        }

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document supprimé.');
    }

    public function update(Request $request, Document $document)
    {
        $user = Auth::user();

        // RMC ne peut modifier que les documents de sa filiale
        if ($user->isRmc() && $document->filiale_id !== $user->filiale_id) {
            abort(403);
        }

        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string|max:5000',
            'entity_id'      => 'required|exists:entities,id',
            'filiale_id'     => 'nullable|exists:filiales,id',
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'year'           => 'required|integer|min:2000|max:2099',
        ]);

        $document->update($request->only([
            'title', 'description', 'entity_id', 'filiale_id',
            'category_id', 'subcategory_id', 'year',
        ]));

        return back()->with('success', 'Document mis à jour.');
    }

    public function export(Request $request)
    {
        $query = Document::with(['entity', 'filiale', 'category', 'subcategory', 'uploader'])
            ->where('is_active', true);

        if ($request->entity_id)      $query->whereIn('entity_id', (array) $request->entity_id);
        if ($request->filiale_id)     $query->whereIn('filiale_id', (array) $request->filiale_id);
        if ($request->category_id)    $query->whereIn('category_id', (array) $request->category_id);
        if ($request->subcategory_id) $query->whereIn('subcategory_id', (array) $request->subcategory_id);
        if ($request->year)           $query->whereIn('year', (array) $request->year);
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $user = Auth::user();
        if ($user->isRmc() && $user->filiale_id) {
            $query->where('filiale_id', $user->filiale_id);
        }

        $documents = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="documents_' . now()->format('Ymd_His') . '.csv"',
            'X-Content-Type-Options' => 'nosniff',
        ];

        $callback = function () use ($documents) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Titre', 'Description', 'Type', 'Extension', 'Taille', 'Entité', 'Filiale', 'Catégorie', 'Sous-catégorie', 'Année', 'Téléchargements', 'Ajouté par', 'Date d\'ajout'], ';');
            foreach ($documents as $doc) {
                fputcsv($handle, [
                    $doc->title,
                    $doc->description ?? '',
                    $doc->file_type,
                    $doc->file_extension,
                    $doc->file_size_formatted,
                    $doc->entity?->name ?? '',
                    $doc->filiale?->name ?? '',
                    $doc->category?->name ?? '',
                    $doc->subcategory?->name ?? '',
                    $doc->year,
                    $doc->download_count,
                    $doc->uploader?->name ?? '',
                    $doc->created_at->format('d/m/Y H:i'),
                ], ';');
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function download(Document $document)
    {
        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404);
        }

        $document->increment('download_count');

        return Storage::disk('local')->download(
            $document->file_path,
            $document->title . '.' . $document->file_extension
        );
    }

    public function downloadZip(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1|max:50',
            'ids.*' => 'integer|exists:documents,id',
        ]);

        $documents = Document::whereIn('id', $request->ids)
            ->where('is_active', true)
            ->get();

        if ($documents->isEmpty()) {
            abort(404);
        }

        $user    = Auth::user();
        $tmpPath = sys_get_temp_dir() . '/dmc_zip_' . uniqid() . '.zip';
        $zip     = new \ZipArchive();
        $zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($documents as $doc) {
            if ($user->isRmc() && $doc->filiale_id !== $user->filiale_id) {
                continue;
            }

            $filePath = Storage::disk('local')->path($doc->file_path);
            if (file_exists($filePath)) {
                $doc->increment('download_count');
                $zip->addFile($filePath, $doc->title . '.' . $doc->file_extension);
            }
        }

        $zip->close();

        return response()->download($tmpPath, 'documents_' . now()->format('Ymd_His') . '.zip', [
            'Content-Type'           => 'application/zip',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    public function thumbnail(Document $document)
    {
        if (!$document->thumbnail_path || !Storage::disk('local')->exists($document->thumbnail_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($document->thumbnail_path);
    }

    public function stream(Document $document)
    {
        $user = Auth::user();

        // RMC ne peut streamer que sa filiale
        if ($user->isRmc() && $document->filiale_id && $document->filiale_id !== $user->filiale_id) {
            abort(403);
        }

        $path = Storage::disk('local')->path($document->file_path);

        if (!file_exists($path)) {
            abort(404);
        }

        $size     = filesize($path);
        $mimeType = mime_content_type($path);
        $start    = 0;
        $end      = $size - 1;

        $headers = [
            'Content-Type'           => $mimeType,
            'Accept-Ranges'          => 'bytes',
            'Content-Length'         => $size,
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (request()->hasHeader('Range')) {
            $range = request()->header('Range');
            preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
            $start  = (int) $matches[1];
            $end    = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : $size - 1;
            $length = $end - $start + 1;

            $headers['Content-Range']  = "bytes {$start}-{$end}/{$size}";
            $headers['Content-Length'] = $length;

            return response()->stream(function () use ($path, $start, $length) {
                $fp = fopen($path, 'rb');
                fseek($fp, $start);
                $remaining = $length;
                while (!feof($fp) && $remaining > 0) {
                    $chunk = min(8192, $remaining);
                    echo fread($fp, $chunk);
                    $remaining -= $chunk;
                    flush();
                }
                fclose($fp);
            }, 206, $headers);
        }

        return response()->stream(function () use ($path) {
            $fp = fopen($path, 'rb');
            while (!feof($fp)) {
                echo fread($fp, 8192);
                flush();
            }
            fclose($fp);
        }, 200, $headers);
    }

    private function getFileType(string $extension): string
    {
        $types = [
            'image'    => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'],
            'video'    => ['mp4', 'avi', 'mov', 'wmv', 'mkv', 'webm'],
            'audio'    => ['mp3', 'wav', 'ogg', 'aac', 'm4a'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
        ];

        foreach ($types as $type => $extensions) {
            if (in_array(strtolower($extension), $extensions)) {
                return $type;
            }
        }
        return 'other';
    }
}
