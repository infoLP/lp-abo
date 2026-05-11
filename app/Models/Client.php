<?php
namespace App\Models;
use App\Enums\ClientStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
class Client extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['user_id','client_number','external_code','type','company_name','siret','vat_number','company_email','civility','first_name','last_name','email','phone','mobile','notes','custom_fields','is_active','status','deactivation_reason','deactivated_at','archived_at','archived_reason','archived_by','is_payer','payer_client_id'];
    protected function casts(): array { return ['is_active'=>'boolean','is_payer'=>'boolean','custom_fields'=>'array','deactivated_at'=>'datetime','archived_at'=>'datetime','status'=>ClientStatus::class]; }
    public function user(): HasOne { return $this->hasOne(User::class); }
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class,'client_id'); }
    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Order::class, 'client_id');
    }

    public function ordersAsBeneficiary(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Order::class, 'beneficiary_id');
    }

    public function paidSubscriptions(): HasMany { return $this->hasMany(Subscription::class,'payer_client_id'); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function payerAccount(): BelongsTo { return $this->belongsTo(Client::class,'payer_client_id'); }
    public function directBeneficiaries(): HasMany { return $this->hasMany(Client::class,'payer_client_id'); }
    public function activeSubscriptions(): HasMany { return $this->subscriptions()->where('status','active'); }
    public function archivedByUser(): BelongsTo { return $this->belongsTo(User::class,'archived_by'); }
    public function payers(): BelongsToMany { return $this->belongsToMany(Client::class,'payers','client_id','payer_client_id')->withTimestamps(); }
    public function beneficiaries(): BelongsToMany { return $this->belongsToMany(Client::class,'payers','payer_client_id','client_id')->withTimestamps(); }
    public function isActive(): bool { return $this->status === ClientStatus::ACTIVE; }
    public function isInactive(): bool { return $this->status === ClientStatus::INACTIVE; }
    public function isArchived(): bool { return $this->status === ClientStatus::ARCHIVED; }
    public function activate(): void { $this->update(['status'=>ClientStatus::ACTIVE,'is_active'=>true,'deactivation_reason'=>null,'deactivated_at'=>null]); }
    public function deactivate(string $reason = ''): void { $this->update(['status'=>ClientStatus::INACTIVE,'is_active'=>false,'deactivation_reason'=>$reason,'deactivated_at'=>now()]); $this->subscriptions()->where('status','active')->update(['status'=>'suspended']); }
    public function archive(string $reason = '', ?int $userId = null): void { $this->update(['status'=>ClientStatus::ARCHIVED,'is_active'=>false,'archived_at'=>now(),'archived_reason'=>$reason,'archived_by'=>$userId]); $this->subscriptions()->whereIn('status',['active','suspended','pending'])->update(['status'=>'cancelled']); if ($this->user) $this->user->update(['is_active'=>false]); }
    public function restoreFromArchive(): void { $this->update(['status'=>ClientStatus::INACTIVE,'is_active'=>false,'archived_at'=>null,'archived_reason'=>null,'archived_by'=>null,'deactivation_reason'=>'Restaure depuis archive','deactivated_at'=>now()]); }
    public function getFullNameAttribute(): string { $p = $this->civility ? "{$this->civility} " : ''; return ($this->type==='company' && $this->company_name) ? "{$this->company_name} ({$p}{$this->first_name} {$this->last_name})" : "{$p}{$this->first_name} {$this->last_name}"; }
    public function getDisplayNameAttribute(): string { return ($this->type==='company' && $this->company_name) ? $this->company_name : "{$this->first_name} {$this->last_name}"; }
    public function getDefaultAddressNameAttribute(): string { return ($this->type==='company' && $this->company_name) ? $this->company_name : trim(($this->civility??'').' '.($this->last_name??'').' '.($this->first_name??'')); }
    public function getShippingAddressAttribute(): array { return $this->getPostalAddress(); }
    public function getCustomField(string $slug, $default = null) { return $this->custom_fields[$slug] ?? $default; }
    public function canAccessIssue(MagazineIssue $issue): bool {
        if ($this->status !== ClientStatus::ACTIVE) return false;
        return $this->subscriptions()->where('magazine_id',$issue->magazine_id)->where('status','active')->whereIn('support_type',['digital','combined'])->where('start_date','<=',$issue->publication_date)->where(fn($q)=>$q->whereNull('end_date')->orWhere('end_date','>=',$issue->publication_date))->exists();
    }
    protected static function boot(): void {
        parent::boot();
        static::creating(function (Client $c) { if(empty($c->client_number)) $c->client_number = self::generateClientNumber(); if(empty($c->status)) $c->status = ClientStatus::ACTIVE; });
    }
    public static function generateClientNumber(): string { $p='CLI-'; $l=self::withTrashed()->where('client_number','like',"{$p}%")->orderByDesc('client_number')->first(); return $p.str_pad($l?((int)str_replace($p,'',$l->client_number))+1:1, 6, '0', STR_PAD_LEFT); }

    public function addresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Address::class)->orderBy('is_default', 'desc')->orderBy('name');
    }

    public function defaultBillingAddress(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Address::class)
            ->whereIn('usage', ['billing', 'both'])
            ->where('is_default', true);
    }

    public function defaultDeliveryAddress(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Address::class)
            ->whereIn('usage', ['delivery', 'both'])
            ->where('is_default', true);
    }

    /**
     * Adresse de facturation par défaut (depuis table addresses)
     */
    public function getDefaultBillingAddressAttribute(): ?\App\Models\Address
    {
        return $this->addresses()
            ->whereIn('usage', ['billing', 'both'])
            ->where('is_default', true)
            ->first()
            ?? $this->addresses()->whereIn('usage', ['billing', 'both'])->first();
    }

    /**
     * Adresse de livraison par défaut (depuis table addresses)
     */
    public function getDefaultDeliveryAddressAttribute(): ?\App\Models\Address
    {
        return $this->addresses()
            ->whereIn('usage', ['delivery', 'both'])
            ->where('is_default', true)
            ->first()
            ?? $this->addresses()->whereIn('usage', ['delivery', 'both'])->first();
    }

    /**
     * Retourne l'adresse de livraison formatée pour le routage postal
     */
    public function getPostalAddress(): array
    {
        $addr = $this->default_delivery_address ?? $this->default_billing_address;
        if (!$addr) return [];
        return [
            'name'        => $addr->l1 ?? '',
            'line1'       => $addr->l4 ?? '',
            'line2'       => $addr->l5 ?? '',
            'line3'       => '',
            'postal_code' => $addr->l6_postal_code ?? '',
            'city'        => $addr->l6_city ?? '',
            'cedex'       => $addr->l6_cedex ?? '',
            'country'     => $addr->l7_country ?? 'FR',
        ];
    }
}
