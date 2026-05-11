<?php
namespace App\Services;
use App\Models\Client;
use App\Models\Magazine;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
class ClientImportService
{
    private array $stats = [];
    public function parseFile(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($ext, ['xlsx','xls']) ? $this->parseExcel($filePath) : $this->parseCsv($filePath);
    }
    private function fixEncoding(string $v): string
    {
        if (empty($v)) return '';
        if (mb_check_encoding($v, 'UTF-8')) return $v;
        $detected = mb_detect_encoding($v, ['Windows-1252','ISO-8859-1','ISO-8859-15','CP1252'], true);
        if ($detected && $detected !== 'UTF-8') { $c = mb_convert_encoding($v, 'UTF-8', $detected); if ($c !== false) return $c; }
        $c = @iconv('Windows-1252', 'UTF-8//IGNORE', $v);
        return $c !== false ? $c : $v;
    }
    private function parseCsv(string $filePath): array
    {
        $raw = file_get_contents($filePath);
        if (substr($raw,0,3) === "\xEF\xBB\xBF") $raw = substr($raw, 3);
        if (!mb_check_encoding($raw, 'UTF-8')) {
            $det = mb_detect_encoding($raw, ['Windows-1252','ISO-8859-1','ISO-8859-15'], true);
            $raw = $det ? mb_convert_encoding($raw, 'UTF-8', $det) : mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
        }
        $tmp = tempnam(sys_get_temp_dir(), 'csv_');
        $headers = [];
        $rows = [];
        // ── Correction : finally garantit le nettoyage même en cas d'exception ──
        try {
            file_put_contents($tmp, $raw);
            $sep = substr_count(strtok($raw, "\n"), ';') >= substr_count(strtok($raw, "\n"), ',') ? ';' : ',';
            $handle = fopen($tmp, 'r');
            $n = 0;
            while (($row = fgetcsv($handle, 0, $sep)) !== false) {
                $n++; $row = array_map(fn($c) => $this->fixEncoding((string)($c ?? '')), $row);
                if ($n === 1) { $headers = array_map(fn($h) => trim(mb_strtolower($h)), $row); continue; }
                if (count($row) === 1 && empty($row[0])) continue;
                $rows[] = $row;
            }
            fclose($handle);
        } finally {
            if (file_exists($tmp)) @unlink($tmp);
        }
        return ['headers' => $headers, 'rows' => $rows];
    }
    private function parseExcel(string $filePath): array
    {
        $reader = new \OpenSpout\Reader\XLSX\Reader(); $reader->open($filePath);
        $headers = []; $rows = []; $n = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $n++; $cells = array_map(function ($c) {
                    if ($c instanceof \DateTimeInterface) return $c->format('d/m/Y');
                    return $this->fixEncoding((string)($c ?? ''));
                }, $row->toArray());
                if ($n === 1) { $headers = array_map(fn($h) => trim(mb_strtolower($h)), $cells); continue; }
                if (empty(array_filter($cells, fn($c) => $c !== ''))) continue;
                $rows[] = $cells;
            }
            break;
        }
        $reader->close();
        return ['headers' => $headers, 'rows' => $rows];
    }
    public function suggestMapping(array $fileHeaders): array
    {
        $aliases = [
            'external_code'=>['code_externe','ref_externe','code_ext','external_code','ancien_code','reference','ref','code_logiciel','id_externe'],
            'client_number'=>['n_client','num_client','code_client','client_number','numero_client','code'],
            'type'=>['type','type_client'],
            'company_name'=>['societe','raison_sociale','entreprise','company','company_name','nom_societe','denomination'],
            'siret'=>['siret','siren','n_siret'],
            'vat_number'=>['tva','n_tva','vat','vat_number','tva_intra'],
            'company_email'=>['email_entreprise','email_societe','company_email','email_pro','mail_societe'],
            'civility'=>['civilite','civ','civility','titre'],
            'last_name'=>['nom','last_name','nom_famille','nom_contact'],
            'first_name'=>['prenom','first_name','prenom_contact'],
            'email'=>['email','mail','e_mail','courriel','email_contact','email_personnel'],
            'phone'=>['telephone','tel','phone','tel_fixe','tel_principal','telephone_principal','tel1'],
            'mobile'=>['mobile','portable','tel_mobile','gsm','tel_secondaire','telephone_secondaire','tel2'],
            'address_name'=>['nom_adresse','destinataire','address_name','nom_destinataire','a_l_attention_de'],
            'address_line1'=>['adresse','adresse1','address','address_line1','rue','voie','adr1','adresse_facturation'],
            'address_line2'=>['adresse2','complement','address_line2','adr2','complement_adresse'],
            'address_line3'=>['adresse3','address_line3','bp','lieu_dit','adr3'],
            'postal_code'=>['cp','code_postal','postal_code','codepostal'],
            'city'=>['ville','city','commune','localite'],
            'cedex'=>['cedex'],
            'country'=>['pays','country','code_pays'],
            'delivery_address_name'=>['nom_livraison','destinataire_livraison','delivery_name'],
            'delivery_address_line1'=>['adresse_livraison','delivery_address','adr_livraison','adresse_expedition'],
            'delivery_address_line2'=>['complement_livraison','delivery_complement'],
            'delivery_postal_code'=>['cp_livraison','code_postal_livraison','delivery_cp'],
            'delivery_city'=>['ville_livraison','delivery_city','commune_livraison'],
            'delivery_cedex'=>['cedex_livraison','delivery_cedex'],
            'publication'=>['publication','magazine','revue','abonnement'],
            'support_type'=>['support','type_support','format','support_type'],
            'plan'=>['formule','plan','offre','type_abonnement'],
            'start_date'=>['date_debut','debut','start_date','date_abonnement'],
            'end_date'=>['date_fin','fin','end_date','echeance'],
            'amount'=>['montant','prix','amount','tarif'],
            'payment_method'=>['paiement','mode_paiement','reglement','payment_method'],
            'payer_code'=>['code_payeur','n_payeur','payeur','payer_code'],
            'notes'=>['notes','commentaire','remarques','observation'],
        ];
        $mapping = [];
        foreach ($fileHeaders as $i => $h) {
            $clean = trim(mb_strtolower(str_replace([' ','-','.','\''], '_', $h)));
            $matched = false;
            foreach ($aliases as $field => $fa) { if (in_array($clean, $fa)) { $mapping[$i] = $field; $matched = true; break; } }
            if (!$matched) $mapping[$i] = '_skip';
        }
        return $mapping;
    }
    public function analyzeContent(array $rows, array $mapping, array $headers): array
    {
        $suggestions = [];
        foreach ($mapping as $ci => $field) {
            if ($field !== '_skip') continue;
            $samples = array_filter(array_slice(array_column($rows, $ci), 0, 20), fn($s) => !empty(trim((string)$s)));
            if (empty($samples)) continue;
            $cnt = count($samples);
            if (count(array_filter($samples, fn($s) => filter_var($s, FILTER_VALIDATE_EMAIL))) > $cnt * 0.5) { $suggestions[$ci] = ['field' => in_array('email', array_values($mapping)) ? 'company_email' : 'email', 'confidence' => 'high', 'reason' => 'Emails']; continue; }
            if (count(array_filter($samples, fn($s) => preg_match('/^\d{5}$/', trim($s)))) > $cnt * 0.5) { $suggestions[$ci] = ['field' => in_array('postal_code', array_values($mapping)) ? 'delivery_postal_code' : 'postal_code', 'confidence' => 'medium', 'reason' => 'Codes postaux']; continue; }
            if (count(array_filter($samples, fn($s) => preg_match('/^\d{9,14}$/', preg_replace('/\s/', '', $s)))) > $cnt * 0.3) { $suggestions[$ci] = ['field' => 'siret', 'confidence' => 'high', 'reason' => 'SIREN/SIRET']; continue; }
            if (count(array_filter($samples, fn($s) => preg_match('/^[\d\s\.\-\+]{10,}$/', trim($s)))) > $cnt * 0.5) { $suggestions[$ci] = ['field' => in_array('phone', array_values($mapping)) ? 'mobile' : 'phone', 'confidence' => 'medium', 'reason' => 'Telephones']; }
        }
        return $suggestions;
    }
    public function detectDuplicates(array $rows, array $mapping): array
    {
        $results = [];
        foreach ($rows as $ri => $row) {
            $mapped = $this->mapRow($row, $mapping);
            $dups = $this->findDups($mapped);
            $results[$ri] = ['data'=>$mapped,'raw'=>$row,'duplicates'=>$dups,'has_conflict'=>!empty($dups),'action'=>empty($dups)?'create':'skip','has_delivery_address'=>!empty($mapped['delivery_address_line1'])||!empty($mapped['delivery_postal_code'])];
        }
        return $results;
    }
    private function mapRow(array $row, array $mapping): array { $m = []; foreach ($mapping as $ci => $f) { if ($f !== '_skip' && isset($row[$ci])) $m[$f] = trim((string)($row[$ci]??'')); } return $m; }
    private function findDups(array $data): array
    {
        $dups = []; $ids = [];
        foreach (['external_code'=>'external_code','email'=>'email','company_email'=>'company_email','siret'=>'siret','client_number'=>'client_number'] as $key => $col) {
            if (!empty($data[$key])) { $v = $key === 'siret' ? preg_replace('/\s+/','',$data[$key]) : $data[$key]; $f = Client::where($col, $v)->whereNotIn('id', $ids)->first(); if ($f) { $dups[] = ['client'=>$f,'match_type'=>$key,'match_value'=>$v]; $ids[] = $f->id; } }
        }
        if (!empty($data['last_name']) && !empty($data['first_name']) && !empty($data['postal_code'])) {
            // ── Correction : échapper les caractères spéciaux LIKE ──────────
            $lastName  = str_replace(['%','_','\\'], ['\\%','\\_','\\\\'], $data['last_name']);
            $firstName = str_replace(['%','_','\\'], ['\\%','\\_','\\\\'], $data['first_name']);

            $f = Client::where('last_name', 'like', $lastName)
                ->where('first_name', 'like', $firstName)
                ->whereHas('addresses', fn($q) => $q->where('l6_postal_code', $data['postal_code']))
                ->whereNotIn('id', $ids)->first();
            if ($f) $dups[] = ['client'=>$f,'match_type'=>'nom_prenom_cp','match_value'=>"{$data['last_name']} {$data['first_name']} ({$data['postal_code']})"];
        }
        return $dups;
    }
    public function executeImport(array $rows, array $mapping, array $actions, ?int $payerClientId = null, ?int $userId = null, string $mode = 'import'): array
    {
        $this->stats = ['created'=>0,'updated'=>0,'skipped'=>0,'subscriptions_created'=>0,'errors'=>[]];
        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $action = $actions[$i] ?? ($mode === 'update' ? 'update' : 'create');
                $mapped = $this->mapRow($row, $mapping);
                if ($action === 'skip') { $this->stats['skipped']++; continue; }
                try {
                    $client = null;
                    if ($action === 'update') {
                        $uid = $actions["update_id_{$i}"] ?? null;
                        if ($uid) { $client = $this->updateClient((int)$uid, $mapped, $mode === 'update'); $this->stats['updated']++; }
                        else { $client = $this->findForUpdate($mapped); if ($client) { $this->updateClient($client->id, $mapped, true); $this->stats['updated']++; } else { $this->stats['skipped']++; } continue; }
                    } else { $client = $this->createClient($mapped, $payerClientId); $this->stats['created']++; }
                    if ($client && (!empty($mapped['delivery_address_line1'])||!empty($mapped['delivery_postal_code']))) {
                        $client->addresses()->create([
                            'name'            => 'Adresse de livraison',
                            'address_type'    => $client->type === 'company' ? 'entreprise' : 'particulier',
                            'usage'           => 'delivery',
                            'is_default'      => true,
                            'l1'              => $mapped['delivery_address_name'] ?? $client->company_name ?? trim($client->first_name.' '.$client->last_name),
                            'l4'              => $mapped['delivery_address_line1'] ?? '',
                            'l5'              => $mapped['delivery_address_line2'] ?? null,
                            'l6_postal_code'  => $mapped['delivery_postal_code'] ?? '',
                            'l6_city'         => strtoupper($mapped['delivery_city'] ?? ''),
                            'l6_cedex'        => $mapped['delivery_cedex'] ?? null,
                            'l7_country'      => 'FR',
                        ]);
                    }
                    if ($client && (!empty($mapped['publication'])||!empty($mapped['plan'])) && $mode !== 'update') { $this->createSub($client, $mapped, $payerClientId, $userId); $this->stats['subscriptions_created']++; }
                } catch (\Exception $e) { $this->stats['errors'][] = "Ligne ".($i+2).": ".$e->getMessage(); }
            }
            DB::commit();
        } catch (\Exception $e) { DB::rollBack(); $this->stats['errors'][] = "Erreur: ".$e->getMessage(); }
        return $this->stats;
    }
    private function findForUpdate(array $data): ?Client
    {
        foreach (['external_code','client_number','email','siret'] as $f) {
            if (!empty($data[$f])) { $v = $f === 'siret' ? preg_replace('/\s+/','',$data[$f]) : $data[$f]; $c = Client::where($f, $v)->first(); if ($c) return $c; }
        }
        return null;
    }
    private function createClient(array $data, ?int $payerClientId = null): Client
    {
        $type = (!empty($data['company_name'])||!empty($data['siret'])) ? 'company' : (in_array(strtolower($data['type']??''),['entreprise','company','societe','pro']) ? 'company' : 'individual');
        $cd = ['type'=>$type,'external_code'=>$data['external_code']??null,'company_name'=>$data['company_name']??null,'siret'=>!empty($data['siret'])?preg_replace('/\s+/','',$data['siret']):null,'vat_number'=>$data['vat_number']??null,'company_email'=>$data['company_email']??null,
            'civility'=>$this->civ($data['civility']??''),'first_name'=>$data['first_name']??'N/A','last_name'=>$data['last_name']??'N/A',
            'email'=>!empty($data['email'])?$data['email']:'import_'.Str::random(8).'@placeholder.local',
            'phone'=>$data['phone']??null,'mobile'=>$data['mobile']??null,
            'notes'=>$data['notes']??null,'is_active'=>true,'payer_client_id'=>$payerClientId];
        if (empty($cd['address_name'])) $cd['address_name'] = ($type==='company' && !empty($cd['company_name'])) ? $cd['company_name'] : trim(($cd['civility']??'').' '.($cd['last_name']??'').' '.($cd['first_name']??''));
        return Client::create($cd);
    }
    private function updateClient(int $id, array $data, bool $enrich = false): Client
    {
        $c = Client::findOrFail($id); $u = [];
        foreach (['external_code','company_name','siret','vat_number','company_email','civility','first_name','last_name','email','phone','mobile','notes'] as $f) {
            $v = $data[$f] ?? null; if ($v === null || $v === '') continue;
            if ($f === 'civility') $v = $this->civ($v); if ($f === 'siret') $v = preg_replace('/\s+/','',$v);
            if ($enrich && !empty($c->$f) && $f !== 'notes') continue;
            $u[$f] = $v;
        }
        if ($enrich && !empty($data['notes']) && !empty($c->notes)) $u['notes'] = $c->notes."\n---\n".$data['notes'];
        if (!empty($u)) $c->update($u);
        return $c;
    }
    private function createSub(Client $client, array $data, ?int $payerClientId, ?int $userId): void
    {
        if (empty($data['publication'])) return;
        $mag = Magazine::where('name','like','%'.$data['publication'].'%')->orWhere('short_name','like','%'.$data['publication'].'%')->first();
        if (!$mag) return;
        $st = match(true) { in_array(strtolower($data['support_type']??''),['numerique','digital','num','pdf'])=>'digital', in_array(strtolower($data['support_type']??''),['combine','combined','papier+num'])=>'combined', default=>'paper' };
        $plan = SubscriptionPlan::where('magazine_id',$mag->id)->where('support_type',$st)->where('is_active',true)->orderBy('sort_order')->first();
        if (!$plan) return;
        $start = $this->parseDate($data['start_date']??'') ?? now(); $end = $this->parseDate($data['end_date']??'');
        if (!$end && $plan->duration_months) $end = $start->copy()->addMonths($plan->duration_months);
        Subscription::create(['client_id'=>$client->id,'payer_client_id'=>$payerClientId,'magazine_id'=>$mag->id,'subscription_plan_id'=>$plan->id,'status'=>'active','support_type'=>$st,'mode'=>$plan->mode->value??'duration','start_date'=>$start,'end_date'=>$end,'issues_total'=>$plan->issues_count,'amount_paid'=>(!empty($data['amount']) && (float)str_replace(',','.',$data['amount']) > 0) ? (float)str_replace(',','.',$data['amount']) : $plan->price,'created_by'=>$userId]);
    }
    private function civ(string $v): ?string { $v=strtolower(trim($v)); return match(true) { in_array($v,['m','m.','mr','monsieur'])=>'M', in_array($v,['mme','mme.','madame'])=>'Mme', in_array($v,['dr','dr.'])=>'Dr', in_array($v,['pr','pr.'])=>'Pr', default=>null }; }
    private function parseDate(string $v): ?\Carbon\Carbon { if(empty($v))return null; foreach(['d/m/Y','Y-m-d','d-m-Y','d.m.Y'] as $f){try{return \Carbon\Carbon::createFromFormat($f,trim($v));}catch(\Exception $e){}} try{return \Carbon\Carbon::parse($v);}catch(\Exception $e){return null;} }
    public static function getAvailableFields(): array
    {
        return ['_skip'=>'-- Ignorer --','external_code'=>'Code externe','client_number'=>'N Client LPA','type'=>'Type','company_name'=>'Raison sociale','siret'=>'SIREN/SIRET','vat_number'=>'N TVA','company_email'=>'Email entreprise','civility'=>'Civilite','last_name'=>'Nom','first_name'=>'Prenom','email'=>'Email contact','phone'=>'Tel principal','mobile'=>'Tel secondaire','address_name'=>'Destinataire (fact.)','address_line1'=>'Adresse fact. L1','address_line2'=>'Adresse fact. L2','address_line3'=>'Adresse fact. L3','postal_code'=>'CP fact.','city'=>'Ville fact.','cedex'=>'CEDEX','country'=>'Pays','delivery_address_name'=>'Destinataire (livr.)','delivery_address_line1'=>'Adresse livr. L1','delivery_address_line2'=>'Adresse livr. L2','delivery_postal_code'=>'CP livr.','delivery_city'=>'Ville livr.','delivery_cedex'=>'CEDEX livr.','publication'=>'Publication','support_type'=>'Support','plan'=>'Formule','start_date'=>'Date debut','end_date'=>'Date fin','amount'=>'Montant','payment_method'=>'Paiement','payer_code'=>'Code payeur','notes'=>'Notes'];
    }
}
