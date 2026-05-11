<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class InvoicePreviewController extends Controller
{
    public function preview()
    {
        // ── Fausse facture pour l'aperçu ──────────────────────────────────
        $invoice = $this->makeFakeInvoice();

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice])
            ->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'apercu-facture-' . now()->format('Ymd-His') . '.pdf'
        );
    }

    private function makeFakeInvoice(): object
    {
        // Fausses lignes
        $lines = collect([
            (object)[
                'description' => 'Abonnement à partir du 01/01/2026 pour l\'offre "Abonnement 1 an papier + web (7 Jours)"',
                'quantity'    => 1,
                'unit_price'  => 67.58,
                'total'       => 67.58,
                'notes'       => null,
                'offer_ref'   => null,
            ],
        ]);

        // Faux client payeur
        $payer = (object)[
            'client_number'          => '123456',
            'company_name'           => null,
            'civility'               => 'Mme',
            'first_name'             => 'Marie',
            'last_name'              => 'DUPONT',
            'address_line1'          => '12 rue des Fleurs',
            'address_line2'          => null,
            'postal_code'            => '33000',
            'city'                   => 'Bordeaux',
            'cedex'                  => null,
            'country'                => 'FR',
            'siret'                  => null,
            'vat_number'             => null,
            'delivery_address_line1' => null,
            'delivery_postal_code'   => null,
            'delivery_city'          => null,
        ];

        $vatRate  = (float) Setting::get('invoice.vat_default_rate', 2.10);
        $subtotal = $lines->sum('total');
        $taxAmt   = round($subtotal * $vatRate / 100, 2);
        $total    = round($subtotal + $taxAmt, 2);

        // Fausse facture (stdClass qui simule le modèle Invoice)
        $invoice = new class(
            $lines, $payer, $subtotal, $taxAmt, $total, $vatRate
        ) {
            public string  $invoice_number;
            public Carbon  $invoice_date;
            public Carbon  $due_date;
            public ?Carbon $paid_at        = null;
            public string  $status_value   = 'sent';
            public         $status;
            public float   $subtotal;
            public float   $tax_rate;
            public float   $tax_amount;
            public float   $total;
            public         $lines;
            public         $client;
            public         $payer;
            public ?string $payment_method = null;
            public ?string $payment_reference = null;
            public ?string $notes          = null;
            public int     $client_id      = 1;
            public ?int    $payer_client_id = 1;
            public         $orders;

            public function __construct($lines, $payer, $subtotal, $taxAmt, $total, $vatRate)
            {
                $this->invoice_number = 'APERÇU-' . date('Ymd');
                $this->invoice_date   = Carbon::now();
                $this->due_date       = Carbon::now()->addDays(30);
                $this->lines          = $lines;
                $this->client         = $payer;
                $this->payer          = $payer;
                $this->subtotal       = $subtotal;
                $this->tax_rate       = $vatRate;
                $this->tax_amount     = $taxAmt;
                $this->total          = $total;
                $this->orders         = collect();
                // Simuler l'enum InvoiceStatus
                $this->status = new class {
                    public string $value = 'sent';
                    public function label(): string { return 'Envoyée'; }
                    public function color(): string { return 'info'; }
                };
            }

            public function isDraft(): bool    { return false; }
            public function isSent(): bool     { return true; }
            public function isPaid(): bool     { return false; }
            public function isOverdue(): bool  { return false; }
            public function isCancelled(): bool{ return false; }
        };

        return $invoice;
    }
}
