<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceptorWorkflowStepAction extends Model
{
    use HasFactory;

    protected $connection = 'core_db';

    protected $fillable = [
        'step_id',
        'action_type',
        'config',
        'order',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    // Relationships
    public function step()
    {
        return $this->belongsTo(ReceptorWorkflowStep::class, 'step_id');
    }
}

