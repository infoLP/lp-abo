<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PostalRouting extends Model { protected $fillable = ['magazine_issue_id','file_path','total_recipients','status','generated_at','sent_at','notes']; protected function casts(): array { return ['generated_at'=>'datetime','sent_at'=>'datetime']; } public function magazineIssue(): BelongsTo { return $this->belongsTo(MagazineIssue::class); } }
