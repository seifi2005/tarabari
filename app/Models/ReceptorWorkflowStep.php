<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceptorWorkflowStep extends Model
{
    use HasFactory;

    protected $connection = 'core_db';

    protected $fillable = [
        'workflow_id',
        'order',
        'name',
    ];

    // Relationships
    public function workflow()
    {
        return $this->belongsTo(ReceptorWorkflow::class, 'workflow_id');
    }

    public function actions()
    {
        return $this->hasMany(ReceptorWorkflowStepAction::class, 'step_id')->orderBy('order');
    }
}

