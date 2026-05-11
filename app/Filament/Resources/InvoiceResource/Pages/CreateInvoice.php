<?php
namespace App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calcul des totaux avant sauvegarde
        $lines    = collect($data['lines'] ?? []);
        $subtotal = $lines->sum(fn($l) => (float)($l['total'] ?? ($l['quantity'] * $l['unit_price'])));
        $taxRate  = (float)($data['tax_rate'] ?? 2.10);
        $taxAmt   = round($subtotal * $taxRate / 100, 2);

        $data['subtotal']   = $subtotal;
        $data['tax_amount'] = $taxAmt;
        $data['total']      = round($subtotal + $taxAmt, 2);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
