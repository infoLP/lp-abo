<?php
namespace App\Services;
use App\Models\MagazineIssue;
use App\Models\PostalRouting;
use App\Models\Subscription;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
class PostalRoutingService
{
    public function generateRoutingFile(MagazineIssue $issue): PostalRouting
    {
        $subs = Subscription::with('client')->where('magazine_id', $issue->magazine_id)->where('status','active')
            ->whereIn('support_type', ['paper','combined'])->where('start_date','<=',$issue->publication_date)
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date','>=',$issue->publication_date))->get();
        $filename = sprintf('routage/%s_%s_%s.csv', $issue->magazine->slug, $issue->issue_number, now()->format('Ymd_His'));
        Storage::disk('local')->makeDirectory('routage');
        $csv = Writer::createFromString(''); $csv->setDelimiter(';');
        $csv->insertOne(['N_CLIENT','CIVILITE','NOM','PRENOM','SOCIETE','ADRESSE_1','ADRESSE_2','ADRESSE_3','CODE_POSTAL','VILLE','CEDEX','PAYS','PUBLICATION','NUMERO','DATE_PUBLICATION']);
        foreach ($subs as $sub) {
            $c = $sub->client; $addr = $c->shipping_address;
            $csv->insertOne([$c->client_number,$c->civility??'',$c->last_name,$c->first_name,$c->company_name??'',$addr['line1'] ?? '',$addr['line2']??'',$addr['line3']??'',$addr['postal_code']??'',$addr['city']??'',$addr['cedex']??'',$addr['country']??'FR',$issue->magazine->name,$issue->issue_number,$issue->publication_date->format('d/m/Y')]);
        }
        Storage::disk('local')->put($filename, $csv->toString());
        return PostalRouting::updateOrCreate(['magazine_issue_id'=>$issue->id],['file_path'=>$filename,'total_recipients'=>$subs->count(),'status'=>'generated','generated_at'=>now()]);
    }
}
