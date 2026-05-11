<?php
namespace App\Models;
use App\Enums\PaymentMethod; use App\Enums\SubscriptionMode; use App\Enums\SubscriptionStatus; use App\Enums\SupportType;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany; use Illuminate\Database\Eloquent\SoftDeletes;
class Subscription extends Model { use HasFactory, SoftDeletes;
    protected $fillable = ['subscription_number','client_id','payer_client_id','magazine_id','subscription_plan_id','status','support_type','mode','start_date','end_date','issues_total','issues_delivered','first_issue_number','last_issue_number','amount_paid','payment_method','payment_reference','auto_renew','notes','shipping_address','created_by'];
    protected function casts(): array { return ['status'=>SubscriptionStatus::class,'support_type'=>SupportType::class,'mode'=>SubscriptionMode::class,'payment_method'=>PaymentMethod::class,'start_date'=>'date','end_date'=>'date','amount_paid'=>'decimal:2','auto_renew'=>'boolean','shipping_address'=>'array']; }
    public function client(): BelongsTo { return $this->belongsTo(Client::class,'client_id'); }
    public function payer(): BelongsTo { return $this->belongsTo(Client::class,'payer_client_id'); }
    public function getIsSponsoredAttribute(): bool { return $this->payer_client_id !== null && $this->payer_client_id !== $this->client_id; }
    public function magazine(): BelongsTo { return $this->belongsTo(Magazine::class); }
    public function subscriptionPlan(): BelongsTo { return $this->belongsTo(SubscriptionPlan::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); }
    protected static function boot(): void { parent::boot(); static::creating(function (Subscription $s) { if(empty($s->subscription_number)){$p='AB';$y=date('Y');$l=self::withTrashed()->where('subscription_number','like',"{$p}{$y}%")->orderByDesc('subscription_number')->first();$s->subscription_number=$p.$y.str_pad($l?((int)substr($l->subscription_number,6))+1:1,6,'0',STR_PAD_LEFT);} }); }
}
