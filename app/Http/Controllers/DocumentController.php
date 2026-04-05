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
    public function index(Request $request): Response
    {
        $query = Document::with(['entity', 'filiale', 'category', 'subcategory'])
            ->where('is_active', true);

        if ($request->entity_id) {
            $query->where('entity_id', $request->entity_id);
        }
        if ($request->filiale_id) {
            $query->where('filiale_id', $request->filiale_id);
        }
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->subcategory_id) {
            $query->where('subcategory_id', $request->subcategory_id);
        }
        if ($request->year) {
            $query->where('year', $request->year);
        }
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

        return Inertia::render('Library/Index', [
            'documents'  => $documents,
            'entities'   => Entity::where('is_active', true)->get(),
            'filiales'   => Filiale::where('is_active', true)->get(),
            'categories' => Category::with('subcategories')->get(),
            'years'      => Document::distinct()->orderBy('year', 'desc')->pluck('year'),
            'filters'    => $request->only(['entity_id', 'filiale_id', 'category_id', 'subcategory_id', 'year', 'search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'file'           => 'required|file|max:1048576',
            'entity_id'      => 'required|exists:entities,id',
            'filiale_id'     => 'nullable|exists:filiales,id',
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'year'           => 'required|integer|min:2000|max:2099',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $fileType = $this->getFileType($extension);
        $fileName = Str::slug($request->title) . '_' . time() . '.' . $extension;
        $path = $file->storeAs("documents/{$request->entity_id}/{$request->year}", $fileName, 'public');

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
        if ($user->isRmc() && $document->filiale_id !== $user->filiale_id) {
            abort(403);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document supprimé.');
    }

    public function update(Request $request, Document $document)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
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

        if ($request->entity_id)      $query->where('entity_id', $request->entity_id);
        if ($request->filiale_id)     $query->where('filiale_id', $request->filiale_id);
        if ($request->category_id)    $query->where('category_id', $request->category_id);
        if ($request->subcategory_id) $query->where('subcategory_id', $request->subcategory_id);
        if ($request->year)           $query->where('year', $request->year);
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
        ];

        $callback = function () use ($documents) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
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
        $document->increment('download_count');

        return Storage::disk('public')->download(
            $document->file_path,
            $document->title . '.' . $document->file_extension
        );
    }

    public function stream(Document $document)
    {
        $path = Storage::disk('public')->path($document->file_path);

        if (!file_exists($path)) {
            abort(404);
        }

        $size     = filesize($path);
        $mimeType = mime_content_type($path);
        $start    = 0;
        $end      = $size - 1;

        $headers = [
            'Content-Type'   => $mimeType,
            'Accept-Ranges'  => 'bytes',
            'Content-Length' => $size,
        ];

        // Gestion des range requests (seek vidéo/audio)
        if (request()->hasHeader('Range')) {
            $range = request()->header('Range');
            preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
            $start = (int) $matches[1];
            $end   = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : $size - 1;
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
