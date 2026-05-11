<?php
namespace App\Filament\Resources\VatRateResource\Pages;
use App\Filament\Resources\VatRateResource;
use Filament\Resources\Pages\CreateRecord;
class CreateVatRate extends CreateRecord
{
    protected static string $resource = VatRateResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
