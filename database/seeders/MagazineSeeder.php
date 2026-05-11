<?php
namespace Database\Seeders;
use App\Models\Magazine; use App\Models\SubscriptionPlan; use Illuminate\Database\Seeder;
class MagazineSeeder extends Seeder { public function run(): void {
    $pubs = [
        ['name'=>'La Vie Economique','short_name'=>'VE','slug'=>'la-vie-economique','description'=>'Hebdomadaire economique.','frequency'=>'weekly'],
        ['name'=>'Les Annonces Landaises','short_name'=>'AL','slug'=>'les-annonces-landaises','description'=>'Annonces legales Landes.','frequency'=>'weekly'],
        ['name'=>'Les Echos Judiciaires Girondins','short_name'=>'EJG','slug'=>'les-echos-judiciaires-girondins','description'=>'Annonces legales Gironde.','frequency'=>'weekly'],
        ['name'=>'7 Jours','short_name'=>'7J','slug'=>'7-jours','description'=>'Hebdomadaire d information.','frequency'=>'weekly'],
        ['name'=>'L Informateur Judiciaire','short_name'=>'IJ','slug'=>'l-informateur-judiciaire','description'=>'Publication juridique.','frequency'=>'weekly'],
    ];
    foreach ($pubs as $i=>$d) {
        $d['sort_order']=$i; $d['is_active']=true; $d['type']='publication';
        $mag = Magazine::updateOrCreate(['slug'=>$d['slug']], $d);
        foreach ([
            ['name'=>'Papier 1 an','slug'=>'papier-1an','support_type'=>'paper','mode'=>'duration','duration_months'=>12,'issues_count'=>null,'price'=>89,'is_free'=>false,'sort_order'=>1],
            ['name'=>'Papier 2 ans','slug'=>'papier-2ans','support_type'=>'paper','mode'=>'duration','duration_months'=>24,'issues_count'=>null,'price'=>159,'is_free'=>false,'sort_order'=>2],
            ['name'=>'Numerique 1 an','slug'=>'numerique-1an','support_type'=>'digital','mode'=>'duration','duration_months'=>12,'issues_count'=>null,'price'=>59,'is_free'=>false,'sort_order'=>3],
            ['name'=>'Combine 1 an','slug'=>'combine-1an','support_type'=>'combined','mode'=>'duration','duration_months'=>12,'issues_count'=>null,'price'=>119,'is_free'=>false,'sort_order'=>4],
            ['name'=>'6 numeros papier','slug'=>'6-numeros-papier','support_type'=>'paper','mode'=>'issues','duration_months'=>null,'issues_count'=>6,'price'=>49,'is_free'=>false,'sort_order'=>5],
            ['name'=>'Decouverte','slug'=>'decouverte','support_type'=>'digital','mode'=>'issues','duration_months'=>null,'issues_count'=>1,'price'=>0,'is_free'=>true,'sort_order'=>6],
        ] as $p) { $p['magazine_id']=$mag->id; $p['is_active']=true; SubscriptionPlan::updateOrCreate(['magazine_id'=>$mag->id,'slug'=>$p['slug']], $p); }
    }
}}
