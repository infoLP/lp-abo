<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\SoftDeletes;
class Payment extends Model { use HasFactory, SoftDeletes;
    protected $fillable = ['payment_number','client_id','invoice_id','subscription_id','amount','method','status','reference','stripe_payment_id','payment_date','notes'];
    protected function casts(): array { return ['amount'=>'decimal:2','payment_date'=>'date']; }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    protected static function boot(): void { parent::boot(); static::creating(function (Payment $p) { if(empty($p->payment_number)){$px='PAY';$ym=date('Ym');$l=self::withTrashed()->where('payment_number','like',"{$px}{$ym}%")->orderByDesc('payment_number')->first();$p->payment_number=$px.$ym.str_pad($l?((int)substr($l->payment_number,9))+1:1,5,'0',STR_PAD_LEFT);} }); }
}
