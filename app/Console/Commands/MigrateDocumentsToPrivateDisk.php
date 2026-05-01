<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateDocumentsToPrivateDisk extends Command
{
    protected $signature   = 'documents:migrate-to-private {--dry-run : Simuler sans déplacer les fichiers}';
    protected $description = 'Migre les documents du disque public vers le disque privé (local)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Mode simulation — aucun fichier ne sera déplacé.');
        }

        $documents = Document::whereNotNull('file_path')->get();
        $total     = $documents->count();
        $migrated  = 0;
        $missing   = 0;
        $skipped   = 0;

        $this->info("Traitement de {$total} documents...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($documents as $doc) {
            $publicPath = Storage::disk('public')->path($doc->file_path);
            $bar->advance();

            // Déjà sur le disque privé
            if (Storage::disk('local')->exists($doc->file_path)) {
                $skipped++;
                continue;
            }

            // Fichier source absent du disque public
            if (!file_exists($publicPath)) {
                $this->newLine();
                $this->warn("  Fichier introuvable : {$doc->file_path} (doc #{$doc->id})");
                $missing++;
                continue;
            }

            if (!$dryRun) {
                $destDir = storage_path('app/private/' . dirname($doc->file_path));
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0750, true);
                }

                copy($publicPath, storage_path('app/private/' . $doc->file_path));
                unlink($publicPath);

                if ($doc->thumbnail_path && $doc->thumbnail_path !== $doc->file_path) {
                    $thumbPublic = Storage::disk('public')->path($doc->thumbnail_path);
                    if (file_exists($thumbPublic) && !Storage::disk('local')->exists($doc->thumbnail_path)) {
                        $thumbDestDir = storage_path('app/private/' . dirname($doc->thumbnail_path));
                        if (!is_dir($thumbDestDir)) {
                            mkdir($thumbDestDir, 0750, true);
                        }
                        copy($thumbPublic, storage_path('app/private/' . $doc->thumbnail_path));
                        unlink($thumbPublic);
                    }
                }
            }

            $migrated++;
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Résultat :');
        $this->line("  ✓ Migrés   : {$migrated}");
        $this->line("  - Ignorés  : {$skipped} (déjà sur disque privé)");
        $this->warn("  ✗ Manquants: {$missing}");

        if ($dryRun && $migrated > 0) {
            $this->newLine();
            $this->info('Relancez sans --dry-run pour effectuer la migration réelle.');
        }

        return Command::SUCCESS;
    }
}
