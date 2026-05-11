<?php
namespace App\Models;
use App\Enums\ContactStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\SoftDeletes;
class Contact extends Model { use HasFactory, SoftDeletes;
    protected $fillable = ['first_name','last_name','email','phone','subject','message','status','admin_notes','handled_by','replied_at'];
    protected function casts(): array { return ['status'=>ContactStatus::class,'replied_at'=>'datetime']; }
    public function handler(): BelongsTo { return $this->belongsTo(User::class,'handled_by'); }
    public function getFullNameAttribute(): string { return "{$this->first_name} {$this->last_name}"; }
}
