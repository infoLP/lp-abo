<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PdfThumbnailService
{
    /**
     * Vérifie que le chemin reste bien dans le disque local autorisé.
     * Protège contre les attaques path traversal (../../etc/passwd).
     */
    private function sanitizePath(string $pdfPath): string
    {
        $realBase = realpath(Storage::disk('local')->path(''));

        if (!$realBase) {
            throw new \RuntimeException('Répertoire de stockage introuvable.');
        }

        $fullPath = Storage::disk('local')->path($pdfPath);
        $realPath = realpath($fullPath);

        if (!$realPath || !str_starts_with($realPath, $realBase . DIRECTORY_SEPARATOR)) {
            Log::warning("PdfThumbnailService: tentative d'accès hors répertoire autorisé.", [
                'path'     => $pdfPath,
                'resolved' => $fullPath,
            ]);
            throw new \InvalidArgumentException('Chemin de fichier non autorisé.');
        }

        // Vérifier que c'est bien un PDF
        if (strtolower(pathinfo($realPath, PATHINFO_EXTENSION)) !== 'pdf') {
            throw new \InvalidArgumentException('Seuls les fichiers PDF sont acceptés.');
        }

        return $realPath;
    }

    public function generateThumbnail(string $pdfPath, string $outputDir = 'thumbnails'): ?string
    {
        try {
            $fullPath = $this->sanitizePath($pdfPath);
        } catch (\InvalidArgumentException $e) {
            Log::error("Thumbnail refusé: " . $e->getMessage());
            return null;
        }

        if (!file_exists($fullPath)) return null;

        $name    = pathinfo($fullPath, PATHINFO_FILENAME) . '_thumb.jpg';
        $relPath = $outputDir . '/' . $name;
        $absPath = Storage::disk('public')->path($relPath);

        Storage::disk('public')->makeDirectory($outputDir);

        try {
            $cmd = sprintf(
                'gs -dFirstPage=1 -dLastPage=1 -sDEVICE=jpeg -dJPEGQ=85 -r150 -dBATCH -dNOPAUSE -dSAFER -sOutputFile=%s %s 2>&1',
                escapeshellarg($absPath),
                escapeshellarg($fullPath)
            );
            exec($cmd, $output, $rc);

            if ($rc === 0 && file_exists($absPath)) return $relPath;

            Log::warning("GS failed: " . implode("\n", $output));
            return null;
        } catch (\Exception $e) {
            Log::error("Thumbnail: " . $e->getMessage());
            return null;
        }
    }

    public function getPageCount(string $pdfPath): int
    {
        try {
            $fullPath = $this->sanitizePath($pdfPath);
        } catch (\InvalidArgumentException $e) {
            Log::error("getPageCount refusé: " . $e->getMessage());
            return 0;
        }

        exec("pdfinfo " . escapeshellarg($fullPath) . " 2>&1", $output);

        foreach ($output as $line) {
            if (preg_match('/^Pages:\s+(\d+)/', $line, $m)) return (int)$m[1];
        }

        return 0;
    }
}
