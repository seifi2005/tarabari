<?php

namespace App\Jobs;

use App\Models\Receptor;
use App\Models\Shipment;
use App\Services\WorkflowExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteReceptorWorkflowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $receptorId;
    public int $shipmentId;
    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(int $receptorId, int $shipmentId)
    {
        $this->receptorId = $receptorId;
        $this->shipmentId = $shipmentId;
    }

    public function handle(WorkflowExecutionService $workflowService): void
    {
        try {
            $receptor = Receptor::findOrFail($this->receptorId);
            $shipment = Shipment::findOrFail($this->shipmentId);

            // اجرای Workflow
            $workflowService->execute($receptor, $shipment);

        } catch (\Exception $e) {
            Log::error('Error executing receptor workflow', [
                'receptor_id' => $this->receptorId,
                'shipment_id' => $this->shipmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}

