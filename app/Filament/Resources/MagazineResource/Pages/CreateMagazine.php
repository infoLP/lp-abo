<?php
namespace App\Filament\Resources\MagazineResource\Pages;
use App\Filament\Resources\MagazineResource; use Filament\Resources\Pages\CreateRecord;
class CreateMagazine extends CreateRecord { protected static string $resource = MagazineResource::class; protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); } }
