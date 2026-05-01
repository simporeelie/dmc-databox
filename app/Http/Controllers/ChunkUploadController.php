<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChunkUploadController extends Controller
{
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp',
        'mp4', 'avi', 'mov', 'wmv', 'mkv', 'webm',
        'mp3', 'wav', 'ogg', 'aac', 'm4a',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    ];

    // Taille maximale totale d'un upload en bytes (500 MB)
    private const MAX_TOTAL_SIZE = 524288000;

    public function upload(Request $request)
    {
        $request->validate([
            'chunk'          => 'required|file',
            'chunk_index'    => 'required|integer|min:0',
            'total_chunks'   => 'required|integer|min:1|max:1000',
            'total_size'     => 'required|integer|min:1|max:' . self::MAX_TOTAL_SIZE,
            'upload_id'      => ['required', 'string', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'filename'       => 'required|string|max:255',
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string|max:5000',
            'entity_id'      => 'required|exists:entities,id',
            'filiale_id'     => 'nullable|exists:filiales,id',
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'year'           => 'required|integer|min:2000|max:2099',
        ]);

        $extension = strtolower(pathinfo($request->filename, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return response()->json(['error' => 'Type de fichier non autorisé.'], 422);
        }

        // Sécurise l'upload_id contre path traversal
        $uploadId   = preg_replace('/[^a-zA-Z0-9_-]/', '', $request->upload_id);
        $chunkIndex = (int) $request->chunk_index;
        $totalChunks = (int) $request->total_chunks;
        $tmpDir     = storage_path("app/chunks/{$uploadId}");

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0750, true);
        }

        $request->file('chunk')->move($tmpDir, "chunk_{$chunkIndex}");

        $receivedChunks = count(glob("{$tmpDir}/chunk_*"));

        if ($receivedChunks < $totalChunks) {
            return response()->json([
                'status'   => 'partial',
                'received' => $receivedChunks,
                'total'    => $totalChunks,
            ]);
        }

        // Assemblage
        $fileName = Str::slug($request->title) . '_' . time() . '.' . $extension;
        $destDir  = "documents/{$request->entity_id}/{$request->year}";

        // Stockage sur disque privé
        $destPath = storage_path("app/private/{$destDir}");

        if (!is_dir($destPath)) {
            mkdir($destPath, 0750, true);
        }

        $finalPath = "{$destPath}/{$fileName}";
        $out = fopen($finalPath, 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFile = "{$tmpDir}/chunk_{$i}";
            if (!file_exists($chunkFile)) {
                fclose($out);
                $this->cleanupChunks($tmpDir);
                return response()->json(['error' => 'Chunk manquant, upload corrompu.'], 422);
            }
            $in = fopen($chunkFile, 'rb');
            while (!feof($in)) {
                fwrite($out, fread($in, 1024 * 1024));
            }
            fclose($in);
            unlink($chunkFile);
        }

        fclose($out);
        rmdir($tmpDir);

        // Validation MIME du fichier assemblé
        $realMime = mime_content_type($finalPath);
        if (!$this->isMimeAllowed($extension, $realMime)) {
            unlink($finalPath);
            return response()->json(['error' => 'Le type MIME du fichier ne correspond pas à son extension.'], 422);
        }

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

    private function cleanupChunks(string $dir): void
    {
        foreach (glob("{$dir}/chunk_*") as $f) {
            unlink($f);
        }
        if (is_dir($dir)) {
            rmdir($dir);
        }
    }

    private function isMimeAllowed(string $extension, string $realMime): bool
    {
        $map = [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'webp' => ['image/webp'],
            'svg'  => ['image/svg+xml', 'text/html', 'text/xml', 'application/xml'],
            'bmp'  => ['image/bmp', 'image/x-bmp'],
            'mp4'  => ['video/mp4'],
            'avi'  => ['video/x-msvideo', 'video/avi'],
            'mov'  => ['video/quicktime'],
            'wmv'  => ['video/x-ms-wmv'],
            'mkv'  => ['video/x-matroska', 'application/octet-stream'],
            'webm' => ['video/webm'],
            'mp3'  => ['audio/mpeg', 'audio/mp3'],
            'wav'  => ['audio/wav', 'audio/x-wav'],
            'ogg'  => ['audio/ogg', 'video/ogg'],
            'aac'  => ['audio/aac', 'audio/x-aac'],
            'm4a'  => ['audio/mp4', 'audio/x-m4a'],
            'pdf'  => ['application/pdf'],
            'doc'  => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'xls'  => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'ppt'  => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        ];

        return isset($map[$extension]) && in_array($realMime, $map[$extension]);
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
