<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceptorWorkflow extends Model
{
    use HasFactory;

    protected $connection = 'core_db';

    protected $fillable = [
        'receptor_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function receptor()
    {
        return $this->belongsTo(Receptor::class);
    }

    public function steps()
    {
        return $this->hasMany(ReceptorWorkflowStep::class, 'workflow_id')->orderBy('order');
    }
}

