<?php

namespace App\Services;

use App\Models\Receptor;
use App\Models\ReceptorWorkflowStepAction;
use App\Models\Shipment;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WorkflowExecutionService
{
    private SmsService $smsService;
    private ReceptorLogService $logService;
    private TemplateService $templateService;

    public function __construct(
        SmsService $smsService,
        ReceptorLogService $logService,
        TemplateService $templateService
    ) {
        $this->smsService = $smsService;
        $this->logService = $logService;
        $this->templateService = $templateService;
    }

    /**
     * اجرای Workflow برای یک Shipment
     * 
     * @param Receptor $receptor پذیرنده
     * @param Shipment $shipment محموله
     */
    public function execute(Receptor $receptor, Shipment $shipment): void
    {
        // بررسی وجود Workflow فعال
        $workflow = $receptor->workflow;
        
        if (!$workflow || !$workflow->is_active) {
            $this->logService->info($receptor->id, 'Workflow not found or inactive', [
                'shipment_id' => $shipment->id,
            ]);
            return;
        }

        $this->logService->info($receptor->id, 'Workflow execution started', [
            'workflow_id' => $workflow->id,
            'shipment_id' => $shipment->id,
            'system_order_id' => $shipment->system_order_id,
        ]);

        try {
            // اجرای مراحل به ترتیب
            foreach ($workflow->steps as $step) {
                $this->executeStep($step, $receptor, $shipment);
            }

            $this->logService->info($receptor->id, 'Workflow execution completed', [
                'workflow_id' => $workflow->id,
                'shipment_id' => $shipment->id,
            ]);

        } catch (\Exception $e) {
            $this->logService->error($receptor->id, 'Workflow execution failed', [
                'workflow_id' => $workflow->id,
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * اجرای یک Step
     */
    private function executeStep($step, Receptor $receptor, Shipment $shipment): void
    {
        $this->logService->info($receptor->id, 'Executing step', [
            'step_id' => $step->id,
            'step_name' => $step->name,
            'step_order' => $step->order,
            'shipment_id' => $shipment->id,
        ]);

        // اجرای Actions به ترتیب
        foreach ($step->actions as $action) {
            try {
                $this->executeAction($action, $receptor, $shipment);
            } catch (\Exception $e) {
                // لاگ خطا اما ادامه اجرای بقیه Actions
                $this->logService->error($receptor->id, 'Action execution failed', [
                    'action_id' => $action->id,
                    'action_type' => $action->action_type,
                    'step_id' => $step->id,
                    'shipment_id' => $shipment->id,
                    'error' => $e->getMessage(),
                ]);
                // ادامه می‌دهیم تا بقیه Actions اجرا شوند
            }
        }
    }

    /**
     * اجرای یک Action
     */
    private function executeAction(ReceptorWorkflowStepAction $action, Receptor $receptor, Shipment $shipment): void
    {
        $this->logService->info($receptor->id, 'Executing action', [
            'action_id' => $action->id,
            'action_type' => $action->action_type,
            'shipment_id' => $shipment->id,
        ]);

        switch ($action->action_type) {
            case 'notify_receptor':
                $this->notifyReceptor($action, $receptor, $shipment);
                break;

            case 'send_sms_to_customer':
                $this->sendSmsToCustomer($action, $receptor, $shipment);
                break;

            case 'send_sms_to_admin':
                $this->sendSmsToAdmin($action, $receptor, $shipment);
                break;

            default:
                $this->logService->warning($receptor->id, 'Unknown action type', [
                    'action_id' => $action->id,
                    'action_type' => $action->action_type,
                ]);
        }
    }

    /**
     * اطلاع‌رسانی به پذیرنده (POST/PUT به API پذیرنده)
     */
    private function notifyReceptor(ReceptorWorkflowStepAction $action, Receptor $receptor, Shipment $shipment): void
    {
        $config = $action->config ?? [];
        $note = $config['note'] ?? 'سفارش در سامانه ترابری ثبت شد';

        // بررسی تنظیمات API پذیرنده
        if (!$receptor->hasOrdersApiConfigured()) {
            $this->logService->error($receptor->id, 'Receptor API not configured', [
                'action_id' => $action->id,
                'shipment_id' => $shipment->id,
                'orders_base_url' => $receptor->orders_base_url,
                'has_auth_token' => !empty($receptor->orders_auth_token),
            ]);
            return;
        }

        $callbackUrl = rtrim($receptor->orders_base_url, '/') . '/orders/' . $shipment->source_order_id . '/status';
        
        $this->logService->info($receptor->id, 'Preparing receptor notification', [
            'action_id' => $action->id,
            'shipment_id' => $shipment->id,
            'source_order_id' => $shipment->source_order_id,
            'system_order_id' => $shipment->system_order_id,
            'note' => $note,
            'callback_url' => $callbackUrl,
            'payload' => [
                'status' => 'tarabar-process',
                'note' => $note,
            ],
        ]);

        try {
            $orderService = new OrderService($receptor);
            $success = $orderService->sendCallback(
                (int) $shipment->source_order_id,
                $shipment->system_order_id,
                $shipment->id,
                $note
            );

            if ($success) {
                $this->logService->info($receptor->id, 'Receptor notified successfully', [
                    'action_id' => $action->id,
                    'shipment_id' => $shipment->id,
                    'source_order_id' => $shipment->source_order_id,
                    'callback_url' => $callbackUrl,
                ]);
            } else {
                $this->logService->warning($receptor->id, 'Receptor notification failed - Check laravel.log for details', [
                    'action_id' => $action->id,
                    'shipment_id' => $shipment->id,
                    'source_order_id' => $shipment->source_order_id,
                    'callback_url' => $callbackUrl,
                    'note' => 'Check storage/logs/laravel.log for HTTP response details',
                ]);
            }
        } catch (\Exception $e) {
            $this->logService->error($receptor->id, 'Exception while notifying receptor', [
                'action_id' => $action->id,
                'shipment_id' => $shipment->id,
                'source_order_id' => $shipment->source_order_id,
                'callback_url' => $callbackUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * ارسال SMS به خریدار
     */
    private function sendSmsToCustomer(ReceptorWorkflowStepAction $action, Receptor $receptor, Shipment $shipment): void
    {
        $config = $action->config ?? [];
        $customTemplate = $config['template'] ?? null;

        // بررسی شماره موبایل
        if (empty($shipment->mobile)) {
            $this->logService->error($receptor->id, 'Mobile number is empty', [
                'action_id' => $action->id,
                'shipment_id' => $shipment->id,
                'system_order_id' => $shipment->system_order_id,
            ]);
            return;
        }

        // بررسی API Key
        $apiKey = config('services.kavenegar.api_key');
        if (empty($apiKey)) {
            $this->logService->error($receptor->id, 'Kavenegar API key not configured', [
                'action_id' => $action->id,
                'shipment_id' => $shipment->id,
            ]);
            return;
        }

        // آماده‌سازی متغیرها
        $variables = $this->prepareVariables($shipment, $receptor);
        $template = $customTemplate ?? config('services.kavenegar.sms_template_customer');
        $message = $this->templateService->replaceVariables($template, $variables);

        // لاگ متغیرها برای بررسی
        $this->logService->info($receptor->id, 'Preparing SMS variables', [
            'action_id' => $action->id,
            'shipment_id' => $shipment->id,
            'mobile' => $shipment->mobile,
            'variables' => $variables,
            'message_preview' => substr($message, 0, 100),
            'custom_template' => $customTemplate !== null,
        ]);

        // ارسال SMS
        try {
            $success = $this->smsService->sendToCustomer(
                $shipment->mobile,
                $variables,
                $customTemplate
            );

            if ($success) {
                $this->logService->info($receptor->id, 'SMS sent to customer successfully', [
                    'action_id' => $action->id,
                    'shipment_id' => $shipment->id,
                    'mobile' => $shipment->mobile,
                ]);
            } else {
                // بررسی لاگ‌های دقیق از laravel.log
                $this->logService->error($receptor->id, 'Failed to send SMS to customer - Check laravel.log for details', [
                    'action_id' => $action->id,
                    'shipment_id' => $shipment->id,
                    'mobile' => $shipment->mobile,
                    'mobile_format_valid' => preg_match('/^09\d{9}$/', $shipment->mobile) ? true : false,
                    'api_key_configured' => !empty($apiKey),
                    'message_length' => strlen($message),
                    'note' => 'Check storage/logs/laravel.log for Kavenegar API error details',
                ]);
            }
        } catch (\Exception $e) {
            $this->logService->error($receptor->id, 'Exception while sending SMS', [
                'action_id' => $action->id,
                'shipment_id' => $shipment->id,
                'mobile' => $shipment->mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * ارسال SMS به ادمین
     */
    private function sendSmsToAdmin(ReceptorWorkflowStepAction $action, Receptor $receptor, Shipment $shipment): void
    {
        $config = $action->config ?? [];
        $adminMobile = $config['mobile'] ?? $receptor->mobile; // اگر شماره ادمین نداد، از شماره پذیرنده استفاده می‌شود
        $customTemplate = $config['template'] ?? null;

        // آماده‌سازی متغیرها
        $variables = $this->prepareVariables($shipment, $receptor);

        // ارسال SMS
        $success = $this->smsService->sendToAdmin(
            $adminMobile,
            $variables,
            $customTemplate
        );

        if ($success) {
            $this->logService->info($receptor->id, 'SMS sent to admin', [
                'action_id' => $action->id,
                'shipment_id' => $shipment->id,
                'mobile' => $adminMobile,
            ]);
        } else {
            $this->logService->error($receptor->id, 'Failed to send SMS to admin', [
                'action_id' => $action->id,
                'shipment_id' => $shipment->id,
                'mobile' => $adminMobile,
            ]);
        }
    }

    /**
     * آماده‌سازی متغیرها برای قالب
     */
    private function prepareVariables(Shipment $shipment, Receptor $receptor): array
    {
        return [
            'customer_name' => $shipment->customer_first_name . ' ' . $shipment->customer_last_name,
            'order_id' => $shipment->system_order_id,
            'order_register_date' => Carbon::parse($shipment->created_at)->format('Y/m/d'),
            'total_price' => number_format($shipment->total_price) . ' تومان',
            'receptor_name' => $receptor->company_name,
        ];
    }
}

