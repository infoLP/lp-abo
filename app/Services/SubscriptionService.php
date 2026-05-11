<?php
namespace App\Services;
use App\Models\Client;
use App\Models\Magazine;
use App\Models\MagazineIssue;
use Illuminate\Database\Eloquent\Collection;
class SubscriptionService
{
    public function getAccessiblePublications(Client $client): Collection
    {
        $ids = $client->subscriptions()->where('status','active')->whereIn('support_type',['digital','combined'])->pluck('magazine_id')->unique();
        return Magazine::whereIn('id', $ids)->where('is_active', true)->orderBy('sort_order')->get();
    }
    public function getAccessibleIssues(Client $client, Magazine $magazine): Collection
    {
        $subs = $client->subscriptions()->where('magazine_id', $magazine->id)->where('status','active')->whereIn('support_type',['digital','combined'])->get();
        if ($subs->isEmpty()) return new Collection();
        return MagazineIssue::where('magazine_id', $magazine->id)->where('is_published', true)
            ->where(function ($q) use ($subs) {
                foreach ($subs as $sub) { $q->orWhere(function ($q2) use ($sub) { $q2->where('publication_date', '>=', $sub->start_date); if ($sub->end_date) $q2->where('publication_date', '<=', $sub->end_date); }); }
            })->orderByDesc('publication_date')->get();
    }
    public function getAllAccessibleIssues(Client $client): Collection
    {
        $all = new Collection();
        foreach ($this->getAccessiblePublications($client) as $pub) { $all = $all->merge($this->getAccessibleIssues($client, $pub)); }
        return $all->sortByDesc('publication_date');
    }
}
