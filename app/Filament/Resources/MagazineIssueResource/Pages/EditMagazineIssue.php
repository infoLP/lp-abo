<?php
namespace App\Filament\Resources\MagazineIssueResource\Pages;
use App\Filament\Resources\MagazineIssueResource;
use App\Services\PdfThumbnailService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditMagazineIssue extends EditRecord
{
    protected static string $resource = MagazineIssueResource::class;

    protected function afterSave(): void
    {
        $r = $this->record;
        if ($r->pdf_file && !$r->thumbnail_path) {
            $s = new PdfThumbnailService();
            $r->update([
                'thumbnail_path' => $s->generateThumbnail($r->pdf_file),
                'page_count'     => $s->getPageCount($r->pdf_file),
                'file_size'      => Storage::disk('local')->size($r->pdf_file),
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Ouvrir la liseuse')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn () => route('admin.preview.reader', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => (bool) $this->record->pdf_file),
            Actions\DeleteAction::make(),
        ];
    }
}
