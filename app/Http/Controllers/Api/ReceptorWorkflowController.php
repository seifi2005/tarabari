<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receptor;
use App\Models\ReceptorWorkflow;
use App\Models\ReceptorWorkflowStep;
use App\Models\ReceptorWorkflowStepAction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceptorWorkflowController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            /** @var User|null $user */
            $user = auth()->user();
            if (!$user || (!$user->isSuperAdmin() && !$user->isOperator())) {
                return response()->json(['message' => trans('messages.unauthorized')], 403);
            }
            return $next($request);
        });
    }

    /**
     * لیست Actions موجود (برای Frontend)
     */
    public function getAvailableActions()
    {
        $actions = [
            [
                'id' => 'notify_receptor',
                'icon' => '🌐',
                'name' => 'اطلاع رسانی به پذیرنده',
            ],
            [
                'id' => 'send_sms_to_customer',
                'icon' => '📱',
                'name' => 'ارسال SMS به کاربر',
            ],
            [
                'id' => 'send_sms_to_admin',
                'icon' => '📱',
                'name' => 'ارسال SMS به ادمین',
            ],
        ];

        return response()->json([
            'actions' => $actions,
        ]);
    }

    /**
     * دریافت Workflow یک پذیرنده
     */
    public function show($receptorId)
    {
        $receptor = Receptor::findOrFail($receptorId);
        $workflow = $receptor->workflow;

        if (!$workflow) {
            return response()->json([
                'workflow' => null,
            ]);
        }

        return response()->json([
            'workflow' => $this->formatWorkflowResponse($workflow),
        ]);
    }

    /**
     * ایجاد یا به‌روزرسانی Workflow
     */
    public function store(Request $request, $receptorId)
    {
        $receptor = Receptor::findOrFail($receptorId);

        $request->validate([
            'is_active' => 'required|boolean',
            'steps' => 'required|array|min:1',
            'steps.*.order' => 'required|integer|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.actions' => 'required|array|min:1',
            'steps.*.actions.*.id' => 'required|string',
            'steps.*.actions.*.config' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // ایجاد یا به‌روزرسانی Workflow
            $workflow = ReceptorWorkflow::updateOrCreate(
                ['receptor_id' => $receptorId],
                ['is_active' => $request->is_active]
            );

            // حذف مراحل قبلی
            $workflow->steps()->delete();

            // ایجاد مراحل جدید
            foreach ($request->steps as $stepData) {
                $step = $workflow->steps()->create([
                    'order' => $stepData['order'],
                    'name' => $stepData['name'],
                ]);

                // ایجاد Actions برای هر مرحله
                foreach ($stepData['actions'] as $index => $actionData) {
                    $step->actions()->create([
                        'action_type' => $actionData['id'],
                        'config' => $actionData['config'] ?? [],
                        'order' => $index + 1,
                    ]);
                }
            }

            DB::commit();

            // بارگذاری مجدد relationships
            $workflow->load('steps.actions');

            return response()->json([
                'message' => trans('messages.workflow_created'),
                'workflow' => $this->formatWorkflowResponse($workflow),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => trans('messages.error_creating_workflow'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * به‌روزرسانی Workflow (همان store)
     */
    public function update(Request $request, $receptorId)
    {
        return $this->store($request, $receptorId);
    }

    /**
     * حذف Workflow
     */
    public function destroy($receptorId)
    {
        $receptor = Receptor::findOrFail($receptorId);
        $workflow = $receptor->workflow;

        if (!$workflow) {
            return response()->json([
                'message' => trans('messages.workflow_not_found'),
            ], 404);
        }

        try {
            $workflow->delete();

            return response()->json([
                'message' => trans('messages.workflow_deleted'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('messages.error_deleting_workflow'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * فرمت کردن Response Workflow برای Frontend
     */
    private function formatWorkflowResponse(ReceptorWorkflow $workflow): array
    {
        return [
            'id' => $workflow->id,
            'receptor_id' => $workflow->receptor_id,
            'is_active' => $workflow->is_active,
            'steps' => $workflow->steps->map(function ($step) {
                return [
                    'id' => $step->id,
                    'order' => $step->order,
                    'name' => $step->name,
                    'actions' => $step->actions->map(function ($action) {
                        // پیدا کردن icon و name از لیست Actions
                        $actionInfo = $this->getActionInfo($action->action_type);
                        
                        return [
                            'id' => $action->id,
                            'step_id' => $action->step_id,
                            'action_type' => $action->action_type,
                            'icon' => $actionInfo['icon'],
                            'name' => $actionInfo['name'],
                            'config' => $action->config ?? [],
                            'order' => $action->order,
                        ];
                    })->values(),
                ];
            })->values(),
            'created_at' => $workflow->created_at,
            'updated_at' => $workflow->updated_at,
        ];
    }

    /**
     * دریافت اطلاعات Action (icon و name)
     */
    private function getActionInfo(string $actionType): array
    {
        $actions = [
            'notify_receptor' => [
                'icon' => '🌐',
                'name' => 'اطلاع رسانی به پذیرنده',
            ],
            'send_sms_to_customer' => [
                'icon' => '📱',
                'name' => 'ارسال SMS به کاربر',
            ],
            'send_sms_to_admin' => [
                'icon' => '📱',
                'name' => 'ارسال SMS به ادمین',
            ],
        ];

        return $actions[$actionType] ?? [
            'icon' => '❓',
            'name' => 'Action ناشناخته',
        ];
    }
}

