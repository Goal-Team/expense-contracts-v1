<?php

namespace App\Models;
use App\Models\Scopes\EntityScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityMain extends Model
{
    use HasFactory;

    protected $table = 'entity';
    
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new EntityScope());  
    }
    
    
        /**
     * Get the party Details that was External.
     */
    public function addressDetailsIn(): BelongsTo
    {
        return $this->belongsTo(Companyprofile::class, 'id', 'entityid');
    }
}
