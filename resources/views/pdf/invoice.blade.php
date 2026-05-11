@php
use App\Models\Setting;

$companyName    = Setting::get('company.name',        'LPAbonnements');
$companyAddress1= Setting::get('company.address1',    '');
$companyAddress2= Setting::get('company.address2',    '');
$companyPostal  = Setting::get('company.postal_code', '');
$companyCity    = Setting::get('company.city',        '');
$companySiret   = Setting::get('company.siret',       '');
$companyVat     = Setting::get('company.vat_number',  '');
$companyEmail   = Setting::get('company.email',       '');
$companyPhone   = Setting::get('company.phone',       '');
$companyNaf     = Setting::get('company.naf',         '');
$companyLogo    = Setting::get('company.logo',        '');

$primaryColor   = Setting::get('invoice.primary_color',       '#1a1a1a');
$secondaryColor = Setting::get('invoice.secondary_color',     '#6b7280');
$fontSize       = (int) Setting::get('invoice.font_size',     11);
$marginTop      = (int) Setting::get('invoice.margin_top',    15);
$marginBottom   = (int) Setting::get('invoice.margin_bottom', 15);
$marginLeft     = (int) Setting::get('invoice.margin_left',   15);
$marginRight    = (int) Setting::get('invoice.margin_right',  15);
$showLogo       = (bool) Setting::get('invoice.show_logo',    true);
$showShipping   = (bool) Setting::get('invoice.show_shipping_line', true);
$footer1        = Setting::get('invoice.footer_line1',        '');
$footer2        = Setting::get('invoice.footer_line2',        '');
$footer3        = Setting::get('invoice.footer_line3',        '');
$legalMentions  = Setting::get('invoice.legal_mentions',      '');
$paymentCond    = Setting::get('invoice.payment_conditions',  '');
$bankIban       = Setting::get('invoice.bank_iban',           '');
$bankBic        = Setting::get('invoice.bank_bic',            '');
$bankName       = Setting::get('invoice.bank_name',           '');
$vatRate        = (float) Setting::get('invoice.vat_default_rate', 2.10);

$payer     = $invoice->payer ?? $invoice->client;
$ben       = $invoice->client;
$statusVal = is_string($invoice->status) ? $invoice->status : $invoice->status->value;
$statusLabels = [
    'draft'     => 'Brouillon',
    'sent'      => 'Envoyée',
    'paid'      => 'Payée',
    'overdue'   => 'En retard',
    'cancelled' => 'Annulée',
];

$orderNumbers = $invoice->orders?->pluck('number')->join(', ') ?? '';

$pmMethod = $invoice->payment_method;
$pmLabel  = $pmMethod ? (is_string($pmMethod) ? $pmMethod : $pmMethod->label()) : '';
$pmRef    = $invoice->payment_reference ?? '';
$pmDate   = $invoice->paid_at?->format('d/m/Y') ?? '';
$pmTotal  = number_format((float)$invoice->total, 2, ',', ' ') . ' €';
$isCB     = str_starts_with($pmRef, 'pi_') || str_starts_with($pmRef, 'ch_')
            || strtolower($pmLabel) === 'carte bancaire';

// Chemin absolu du logo pour DomPDF
// DomPDF ne peut pas accéder aux URLs — il faut un chemin système absolu
$logoPath = '';
if ($showLogo && $companyLogo) {
    // Essai 1 : storage/app/public/ (FileUpload Filament disk=public)
    $abs = storage_path('app/public/' . ltrim($companyLogo, '/'));
    if (file_exists($abs)) {
        $logoPath = $abs;
    } else {
        // Essai 2 : public/storage/ (si symlink storage)
        $abs2 = public_path('storage/' . ltrim($companyLogo, '/'));
        if (file_exists($abs2)) {
            $logoPath = $abs2;
        }
    }
}

// Hauteur du footer fixe (mm) — ajuster si contenu plus long
$footerHeight = 35;
if ($legalMentions) $footerHeight += 8;
if ($bankIban)      $footerHeight += 8;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: {{ $fontSize }}px;
    color: #1a1a1a;
}

/* ══ ZONE DE CONTENU (laisse la place pour le footer fixe) ══ */
.page {
    padding: {{ $marginTop }}mm {{ $marginRight }}mm 0 {{ $marginLeft }}mm;
    /* Le padding-bottom est géré par le footer fixe */
}

/* ══ FOOTER FIXE EN BAS DE PAGE ════════════════════════════ */
.page-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 0 {{ $marginRight }}mm 6mm {{ $marginLeft }}mm;
    background: #ffffff;
}
.footer-separator {
    border-top: 2px solid {{ $primaryColor }};
    margin-bottom: 5px;
}

/* ══ EN-TÊTE ════════════════════════════════════════════════ */
.header {
    display: table;
    width: 100%;
    border-bottom: 2px solid {{ $primaryColor }};
    padding-bottom: 10px;
    margin-bottom: 12px;
}
.header-logo {
    display: table-cell;
    width: 38%;
    vertical-align: middle;
}
.header-logo img {
    max-height: 60px;
    max-width: 170px;
}
.company-name-fallback {
    font-size: {{ $fontSize + 6 }}px;
    font-weight: bold;
    color: {{ $primaryColor }};
}
.header-blocks {
    display: table-cell;
    width: 62%;
    vertical-align: middle;
    text-align: right;
}
.header-blocks table {
    display: inline-table;
    border-collapse: collapse;
    margin-left: auto;
}
.header-blocks td {
    padding: 6px 16px;
    border-left: 2px solid {{ $primaryColor }};
    vertical-align: middle;
    text-align: center;
    min-width: 95px;
}
.header-blocks td:first-child { border-left: none; }
.hb-label {
    font-size: {{ $fontSize - 2 }}px;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: {{ $secondaryColor }};
    display: block;
    margin-bottom: 2px;
}
.hb-value {
    font-size: {{ $fontSize + 2 }}px;
    font-weight: bold;
    color: {{ $primaryColor }};
    display: block;
}

/* ══ ADRESSES ═══════════════════════════════════════════════ */
.addresses {
    display: table;
    width: 100%;
    margin-bottom: 12px;
}
.addr-cell {
    display: table-cell;
    width: 50%;
    vertical-align: top;
    padding-right: 8px;
}
.addr-cell:last-child {
    padding-right: 0;
    padding-left: 8px;
}
.addr-block {
    border: 1px solid #d1d5db;
    border-radius: 3px;
    padding: 8px 12px;
    min-height: 78px;
}
.addr-title {
    font-size: {{ $fontSize - 2 }}px;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: {{ $secondaryColor }};
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 3px;
    margin-bottom: 5px;
}
.addr-name { font-weight: bold; font-size: {{ $fontSize + 1 }}px; line-height: 1.6; }
.addr-line { font-size: {{ $fontSize }}px; line-height: 1.7; }

/* ══ META ROW ═══════════════════════════════════════════════ */
.meta-row {
    display: table;
    width: 100%;
    margin-bottom: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 3px;
    background: #f9fafb;
}
.meta-cell {
    display: table-cell;
    padding: 6px 12px;
    vertical-align: middle;
    border-right: 1px solid #e5e7eb;
}
.meta-cell:last-child { border-right: none; }
.meta-label {
    font-size: {{ $fontSize - 2 }}px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: {{ $secondaryColor }};
    display: block;
    margin-bottom: 2px;
}
.meta-value { font-weight: 600; font-size: {{ $fontSize }}px; }
.meta-ref {
    font-size: {{ $fontSize - 2 }}px;
    color: {{ $secondaryColor }};
    font-family: monospace;
    display: block;
    margin-top: 1px;
}

/* ══ TABLEAU LIGNES ════════════════════════════════════════ */
.lines-table { width: 100%; border-collapse: collapse; }
.lines-table thead tr { background: {{ $primaryColor }}; color: #fff; }
.lines-table thead th {
    padding: 7px 8px;
    font-size: {{ $fontSize - 1 }}px;
    font-weight: bold;
    text-align: left;
}
.lines-table thead th.r { text-align: right; }
.lines-table thead th.c { text-align: center; }
.lines-table tbody tr { border-bottom: 1px solid #e5e7eb; }
.lines-table tbody tr:last-child { border-bottom: 2px solid {{ $primaryColor }}; }
.lines-table tbody td {
    padding: 7px 8px;
    vertical-align: middle;
    font-size: {{ $fontSize }}px;
}
.lines-table tbody td.r { text-align: right; }
.lines-table tbody td.c { text-align: center; }
.lines-table tbody tr:nth-child(even) { background: #f9fafb; }
.tva-note {
    font-size: {{ $fontSize - 2 }}px;
    color: {{ $secondaryColor }};
    display: block;
}

/* ══ TOTAUX — alignés à GAUCHE ════════════════════════════ */
.totals-wrap {
    margin-top: 0;
    margin-bottom: 14px;
}
.totals-table {
    border-collapse: collapse;
    font-size: {{ $fontSize }}px;
    min-width: 320px;
}
.totals-table td { padding: 5px 12px; }
.totals-table td:last-child { text-align: right; font-weight: 600; min-width: 90px; }
.t-head td {
    font-size: {{ $fontSize - 2 }}px;
    color: {{ $secondaryColor }};
    font-weight: normal;
    text-align: right !important;
    padding-bottom: 2px;
}
.t-head td:first-child { text-align: left !important; }
.t-sep td { border-top: 1px solid #d1d5db; padding-top: 5px; }
.t-final {
    background: {{ $primaryColor }};
    color: #fff;
    font-weight: bold;
    font-size: {{ $fontSize + 2 }}px;
}
.t-final td { padding: 8px 12px; }

/* ══ BADGE PAYÉE ════════════════════════════════════════════ */
.paid-badge {
    display: inline-block;
    background: #dcfce7;
    color: #15803d;
    font-weight: bold;
    font-size: {{ $fontSize + 1 }}px;
    padding: 5px 16px;
    border: 1px solid #86efac;
    border-radius: 4px;
    margin-top: 8px;
}

/* ══ FOOTER INTERNE ════════════════════════════════════════ */
.footer-legal {
    font-size: {{ $fontSize - 2 }}px;
    color: {{ $secondaryColor }};
    line-height: 1.7;
    margin-bottom: 4px;
}
.footer-bank {
    font-size: {{ $fontSize - 2 }}px;
    color: #0369a1;
    line-height: 1.7;
    margin-bottom: 4px;
    text-align: center;
}
.footer-company {
    text-align: center;
    font-size: {{ $fontSize - 2 }}px;
    color: {{ $secondaryColor }};
    line-height: 1.9;
}
</style>
</head>
<body>

{{-- ══ FOOTER FIXE (affiché en bas de chaque page) ══════════════════════ --}}
<div class="page-footer">
  <div class="footer-separator"></div>

  {{-- 1. Mentions légales --}}
  @if($legalMentions)
  <div class="footer-legal">{{ $legalMentions }}</div>
  @endif

  {{-- 2. Coordonnées bancaires --}}
  @if($bankIban)
  <div class="footer-bank">
    <strong>Virement :</strong>
    @if($bankName){{ $bankName }} — @endif
    IBAN : {{ $bankIban }}
    @if($bankBic) — BIC : {{ $bankBic }}@endif
  </div>
  @endif

  {{-- 3. Coordonnées société --}}
  <div class="footer-company">
    @if($footer1 || $footer2 || $footer3)
      @if($footer1){{ $footer1 }}@if($footer2 || $footer3) &mdash; @endif@endif
      @if($footer2){{ $footer2 }}@if($footer3) &mdash; @endif@endif
      @if($footer3){{ $footer3 }}@endif
    @else
      {{ $companyName }}
      @if($companyAddress1) &mdash; {{ trim($companyAddress1 . ($companyAddress2 ? ', '.$companyAddress2 : '')) }}@endif
      @if($companyPostal || $companyCity) &mdash; {{ trim($companyPostal . ' ' . $companyCity) }}@endif
      @if($companySiret) &mdash; SIRET {{ $companySiret }}@if($companyNaf) ({{ $companyNaf }})@endif@endif
      @if($companyVat) &mdash; {{ $companyVat }}@endif
      @if($companyEmail)<br>{{ $companyEmail }}@if($companyPhone) &mdash; {{ $companyPhone }}@endif@endif
    @endif
    <br>{{ $invoice->invoice_number }} &mdash; Généré le {{ now()->format('d/m/Y à H:i') }}
  </div>
</div>

{{-- ══ CONTENU PRINCIPAL ══════════════════════════════════════════════════ --}}
<div class="page">

  {{-- ── En-tête : Logo | Facture N° | Date | N° Client ──────────────── --}}
  <div class="header">

    <div class="header-logo">
      @if($logoPath)
        <img src="{{ $logoPath }}" alt="{{ $companyName }}">
      @else
        <div class="company-name-fallback">{{ $companyName }}</div>
      @endif
    </div>

    <div class="header-blocks">
      <table>
        <tr>
          <td>
            <span class="hb-label">Facture</span>
            <span class="hb-value">{{ $invoice->invoice_number }}</span>
          </td>
          <td>
            <span class="hb-label">Date</span>
            <span class="hb-value">{{ $invoice->invoice_date->format('d/m/Y') }}</span>
          </td>
          <td>
            <span class="hb-label">Client N°</span>
            <span class="hb-value">{{ $payer?->client_number ?? '—' }}</span>
          </td>
        </tr>
      </table>
    </div>

  </div>

  {{-- ── Adresses : Livraison (gauche) | Facturation (droite) ────────── --}}
  <div class="addresses">

    {{-- Livraison --}}
    <div class="addr-cell">
      <div class="addr-block">
        <div class="addr-title">Adresse de livraison</div>
        @if($ben)
          @php
            $delName    = trim(($ben->civility ? $ben->civility.' ' : '') . $ben->first_name . ' ' . $ben->last_name);
            $delCompany = $ben->company_name ?? null;
            $delAddr1   = $ben->delivery_address_line1 ?? $ben->address_line1 ?? '';
            $delAddr2   = $ben->delivery_address_line2 ?? $ben->address_line2 ?? '';
            $delCp      = $ben->delivery_postal_code   ?? $ben->postal_code   ?? '';
            $delCity    = $ben->delivery_city          ?? $ben->city          ?? '';
            $delCedex   = $ben->delivery_cedex         ?? $ben->cedex         ?? '';
          @endphp
          @if($delCompany)<div class="addr-name">{{ $delCompany }}</div>@endif
          <div class="{{ $delCompany ? 'addr-line' : 'addr-name' }}">{{ $delName }}</div>
          @if($delAddr1)<div class="addr-line">{{ $delAddr1 }}</div>@endif
          @if($delAddr2)<div class="addr-line">{{ $delAddr2 }}</div>@endif
          <div class="addr-line">
            {{ trim($delCp . ' ' . strtoupper($delCity)) }}
            @if($delCedex) CEDEX {{ $delCedex }}@endif
          </div>
        @else
          <div class="addr-line" style="color:{{ $secondaryColor }};">—</div>
        @endif
      </div>
    </div>

    {{-- Facturation (fenêtre enveloppe DL) --}}
    <div class="addr-cell">
      <div class="addr-block">
        <div class="addr-title">Adresse de facturation</div>
        @if($payer)
          @php
            $bilName    = trim(($payer->civility ? $payer->civility.' ' : '') . $payer->first_name . ' ' . $payer->last_name);
            $bilCompany = $payer->company_name ?? null;
          @endphp
          @if($bilCompany)<div class="addr-name">{{ $bilCompany }}</div>@endif
          <div class="{{ $bilCompany ? 'addr-line' : 'addr-name' }}">{{ $bilName }}</div>
          @if($payer->address_line1)<div class="addr-line">{{ $payer->address_line1 }}</div>@endif
          @if($payer->address_line2)<div class="addr-line">{{ $payer->address_line2 }}</div>@endif
          <div class="addr-line">
            {{ trim($payer->postal_code . ' ' . strtoupper($payer->city)) }}
            @if($payer->cedex) CEDEX {{ $payer->cedex }}@endif
          </div>
          @if($payer->siret)
            <div class="addr-line" style="color:{{ $secondaryColor }};">SIRET : {{ $payer->siret }}</div>
          @endif
          @if($payer->vat_number)
            <div class="addr-line" style="color:{{ $secondaryColor }};">TVA : {{ $payer->vat_number }}</div>
          @endif
        @else
          <div class="addr-line" style="color:{{ $secondaryColor }};">—</div>
        @endif
      </div>
    </div>

  </div>

  {{-- ── Meta : Commande | Statut | Paiement ──────────────────────── --}}
  <div class="meta-row">

    @if($orderNumbers)
    <div class="meta-cell" style="width:25%">
      <span class="meta-label">Commande</span>
      <span class="meta-value">{{ $orderNumbers }}</span>
    </div>
    @endif

    <div class="meta-cell" style="width:15%">
      <span class="meta-label">Statut</span>
      <span class="meta-value">{{ $statusLabels[$statusVal] ?? $statusVal }}</span>
    </div>

    @if($pmLabel || $invoice->isPaid())
    <div class="meta-cell">
      <span class="meta-label">Règlement</span>
      <span class="meta-value">
        {{ $pmLabel }}
        @if($pmDate) &mdash; {{ $pmDate }}@endif
        @if($invoice->total) &mdash; <strong>{{ $pmTotal }}</strong>@endif
      </span>
      @if($pmRef)
        <span class="meta-ref">
          @if($isCB)N° transaction : @else Réf. : @endif{{ $pmRef }}
        </span>
      @endif
    </div>
    @endif

  </div>

  {{-- ── Tableau des lignes ────────────────────────────────────────── --}}
  <table class="lines-table">
    <thead>
      <tr>
        <th style="width:38%">Désignation</th>
        <th class="c" style="width:8%">Offre</th>
        <th class="c" style="width:5%">Qté</th>
        <th class="r" style="width:12%">PU HT</th>
        <th class="r" style="width:11%">Remise HT</th>
        <th class="r" style="width:11%">TVA</th>
        <th class="r" style="width:15%">TTC</th>
      </tr>
    </thead>
    <tbody>
      @forelse($invoice->lines as $line)
      @php
        $puHt   = (float) $line->unit_price;
        $qty    = (int)   ($line->quantity ?? 1);
        $totHt  = (float) $line->total;
        $tvaAmt = round($totHt * $vatRate / 100, 2);
        $ttc    = round($totHt + $tvaAmt, 2);
      @endphp
      <tr>
        <td>{{ $line->description }}</td>
        <td class="c">{{ $line->offer_ref ?? '—' }}</td>
        <td class="c">{{ $qty }}</td>
        <td class="r">{{ number_format($puHt, 2, ',', ' ') }} €</td>
        <td class="r">{{ number_format($totHt, 2, ',', ' ') }} €</td>
        <td class="r">
          {{ number_format($tvaAmt, 2, ',', ' ') }} €
          <span class="tva-note">{{ number_format($vatRate, 2, ',', ' ') }} %</span>
        </td>
        <td class="r"><strong>{{ number_format($ttc, 2, ',', ' ') }} €</strong></td>
      </tr>
      @empty
      <tr>
        <td colspan="7" style="text-align:center;color:{{ $secondaryColor }};padding:14px;">
          Aucune ligne
        </td>
      </tr>
      @endforelse

      @if($showShipping)
      <tr>
        <td style="color:{{ $secondaryColor }}">Frais de port : inclus France</td>
        <td class="c">—</td>
        <td class="c">—</td>
        <td class="r">0,00 €</td>
        <td class="r">0,00 €</td>
        <td class="r">0,00 €<span class="tva-note">{{ number_format($vatRate, 2, ',', ' ') }} %</span></td>
        <td class="r"><strong>0,00 €</strong></td>
      </tr>
      @endif
    </tbody>
  </table>

  {{-- ── Totaux — alignés à GAUCHE ────────────────────────────────── --}}
  <div class="totals-wrap">
    <table class="totals-table">
      <tr class="t-head">
        <td> </td>
        <td>Montant HT</td>
        <td>TVA</td>
        <td>Montant TTC</td>
      </tr>
      <tr>
        <td>Articles</td>
        <td style="text-align:right">{{ number_format((float)$invoice->subtotal,   2, ',', ' ') }} €</td>
        <td style="text-align:right">{{ number_format((float)$invoice->tax_amount, 2, ',', ' ') }} €</td>
        <td style="text-align:right">{{ number_format((float)$invoice->total,      2, ',', ' ') }} €</td>
      </tr>
      <tr>
        <td>Transport</td>
        <td style="text-align:right">0,00 €</td>
        <td style="text-align:right"> </td>
        <td style="text-align:right"> </td>
      </tr>
      <tr class="t-sep"><td colspan="4"> </td></tr>
      <tr class="t-final">
        <td colspan="3">Total</td>
        <td>{{ number_format((float)$invoice->total, 2, ',', ' ') }} €</td>
      </tr>
    </table>
  </div>

  {{-- ── Badge payée ──────────────────────────────────────────────── --}}
  @if($invoice->isPaid())
  <div><span class="paid-badge">✓ Facture payée</span></div>
  @endif

  {{-- ── Conditions de règlement ──────────────────────────────────── --}}
  @if($paymentCond)
  <div style="margin-top:10px;font-size:{{ $fontSize - 1 }}px;color:{{ $secondaryColor }};">
    <strong>Conditions de règlement :</strong> {{ $paymentCond }}
  </div>
  @endif

</div>{{-- fin .page --}}

</body>
</html>
