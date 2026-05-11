<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasOne; use Illuminate\Database\Eloquent\SoftDeletes; use Illuminate\Support\Facades\Storage;
class MagazineIssue extends Model { use HasFactory, SoftDeletes;
    protected $fillable = ['magazine_id','issue_number','title','description','publication_date','month_label','cover_image','thumbnail_path','pdf_file','file_size','page_count','is_published'];
    protected function casts(): array { return ['publication_date'=>'date','is_published'=>'boolean','file_size'=>'integer','page_count'=>'integer']; }
    public function magazine(): BelongsTo { return $this->belongsTo(Magazine::class); }
    public function postalRouting(): HasOne { return $this->hasOne(PostalRouting::class); }
    public function getThumbnailUrlAttribute(): ?string { return ($this->thumbnail_path && Storage::disk('public')->exists($this->thumbnail_path)) ? Storage::disk('public')->url($this->thumbnail_path) : null; }
    public function getFileSizeHumanAttribute(): string { if(!$this->file_size)return''; $u=['o','Ko','Mo','Go'];$s=$this->file_size;$i=0; while($s>=1024&&$i<3){$s/=1024;$i++;} return round($s,1).' '.$u[$i]; }
}
