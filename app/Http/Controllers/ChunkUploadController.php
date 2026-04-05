<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkUploadController extends Controller
{
    /**
     * Reçoit un chunk et l'assemble.
     * Quand tous les chunks sont reçus, crée le Document.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'chunk'          => 'required|file',
            'chunk_index'    => 'required|integer|min:0',
            'total_chunks'   => 'required|integer|min:1',
            'upload_id'      => 'required|string',
            'filename'       => 'required|string',
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'entity_id'      => 'required|exists:entities,id',
            'filiale_id'     => 'nullable|exists:filiales,id',
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'year'           => 'required|integer|min:2000|max:2099',
        ]);

        $uploadId    = $request->upload_id;
        $chunkIndex  = (int) $request->chunk_index;
        $totalChunks = (int) $request->total_chunks;
        $tmpDir      = storage_path("app/chunks/{$uploadId}");

        // Crée le dossier temporaire si nécessaire
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        // Sauvegarde le chunk
        $request->file('chunk')->move($tmpDir, "chunk_{$chunkIndex}");

        // Vérifie si tous les chunks sont arrivés
        $receivedChunks = count(glob("{$tmpDir}/chunk_*"));

        if ($receivedChunks < $totalChunks) {
            return response()->json([
                'status'   => 'partial',
                'received' => $receivedChunks,
                'total'    => $totalChunks,
            ]);
        }

        // Tous les chunks reçus — on assemble
        $originalName = $request->filename;
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $fileName     = Str::slug($request->title) . '_' . time() . '.' . $extension;
        $destDir      = "documents/{$request->entity_id}/{$request->year}";
        $destPath     = storage_path("app/public/{$destDir}");

        if (!is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $finalPath = "{$destPath}/{$fileName}";
        $out = fopen($finalPath, 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFile = "{$tmpDir}/chunk_{$i}";
            $in = fopen($chunkFile, 'rb');
            while (!feof($in)) {
                fwrite($out, fread($in, 1024 * 1024));
            }
            fclose($in);
            unlink($chunkFile);
        }

        fclose($out);
        rmdir($tmpDir);

        $fileType    = $this->getFileType($extension);
        $storagePath = "{$destDir}/{$fileName}";
        $fileSize    = filesize($finalPath);
        $thumbnailPath = $fileType === 'image' ? $storagePath : null;

        Document::create([
            'title'          => $request->title,
            'description'    => $request->description,
            'file_path'      => $storagePath,
            'file_type'      => $fileType,
            'file_extension' => $extension,
            'file_size'      => $fileSize,
            'thumbnail_path' => $thumbnailPath,
            'entity_id'      => $request->entity_id,
            'filiale_id'     => $request->filiale_id ?: null,
            'category_id'    => $request->category_id,
            'subcategory_id' => $request->subcategory_id ?: null,
            'year'           => $request->year,
            'uploaded_by'    => Auth::id(),
        ]);

        return response()->json(['status' => 'done']);
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
            if (in_array($extension, $extensions)) return $type;
        }
        return 'other';
    }
}
