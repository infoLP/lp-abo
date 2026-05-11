<?php
namespace App\Filament\Resources\MagazineIssueResource\Pages;
use App\Filament\Resources\MagazineIssueResource; use App\Services\PdfThumbnailService; use Filament\Resources\Pages\CreateRecord; use Illuminate\Support\Facades\Storage;
class CreateMagazineIssue extends CreateRecord { protected static string $resource = MagazineIssueResource::class;
    protected function afterCreate(): void { $r=$this->record; if($r->pdf_file){$s=new PdfThumbnailService();$r->update(['thumbnail_path'=>$s->generateThumbnail($r->pdf_file),'page_count'=>$s->getPageCount($r->pdf_file),'file_size'=>Storage::disk('local')->size($r->pdf_file)]);} }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
