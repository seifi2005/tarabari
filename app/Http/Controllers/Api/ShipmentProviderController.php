<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Shipment;
use App\Services\Providers\ProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShipmentProviderController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isSuperAdmin() && !auth()->user()->isOperator()) {
                return response()->json(['message' => trans('messages.unauthorized')], 403);
            }
            return $next($request);
        });
    }

    /**
     * ارسال محموله به provider
     */
    public function send(Request $request, $shipmentId)
    {
        $request->validate([
            'provider_id' => 'required|exists:providers,id',
        ]);

        try {
            $shipment = Shipment::with(['receptor', 'orderItems.pricing'])->findOrFail($shipmentId);
            $provider = Provider::findOrFail($request->provider_id);

            // بررسی اینکه receptor مجاز به استفاده از این provider است
            if (!$shipment->receptor->providers->contains($provider->id)) {
                return response()->json([
                    'message' => 'Receptor is not authorized to use this provider',
                ], 403);
            }

            // بررسی اینکه provider تنظیمات دارد
            if (!$provider->isConfigured()) {
                return response()->json([
                    'message' => 'Provider is not configured',
                ], 400);
            }

            // ارسال به provider
            $providerService = new ProviderService();
            $result = $providerService->sendShipment($shipment, $provider);

            // ذخیره اطلاعات در shipment
            $shipment->update([
                'provider_id' => $provider->id,
                'provider_tracking_number' => $result['tracking_number'],
                'provider_order_id' => $result['reference_number'] ?? null,
                'sent_to_provider_at' => now(),
                'provider_response' => $result['response'] ?? null,
            ]);

            return response()->json([
                'message' => 'Shipment sent to provider successfully',
                'data' => [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $result['tracking_number'],
                    'reference_number' => $result['reference_number'] ?? null,
                    'amount' => $result['amount'] ?? null,
                    'tax' => $result['tax'] ?? null,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error sending shipment to provider', [
                'shipment_id' => $shipmentId,
                'provider_id' => $request->provider_id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to send shipment to provider',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * رهگیری محموله از provider
     */
    public function track($shipmentId)
    {
        try {
            $shipment = Shipment::with('provider')->findOrFail($shipmentId);

            if (!$shipment->provider_id || !$shipment->provider_tracking_number) {
                return response()->json([
                    'message' => 'Shipment is not sent to any provider',
                ], 400);
            }

            if (!$shipment->provider) {
                return response()->json([
                    'message' => 'Provider not found',
                ], 404);
            }

            $providerService = new ProviderService();
            $trackingData = $providerService->trackShipment(
                $shipment->provider_tracking_number,
                $shipment->provider
            );

            return response()->json([
                'success' => true,
                'data' => $trackingData,
            ]);

        } catch (\Exception $e) {
            Log::error('Error tracking shipment', [
                'shipment_id' => $shipmentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to track shipment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ابطال محموله در provider
     */
    public function cancel(Request $request, $shipmentId)
    {
        $request->validate([
            'reason_id' => 'required|integer',
        ]);

        try {
            $shipment = Shipment::with('provider')->findOrFail($shipmentId);

            if (!$shipment->provider_id || !$shipment->provider_tracking_number) {
                return response()->json([
                    'message' => 'Shipment is not sent to any provider',
                ], 400);
            }

            if (!$shipment->provider) {
                return response()->json([
                    'message' => 'Provider not found',
                ], 404);
            }

            $providerService = new ProviderService();
            $result = $providerService->cancelShipment(
                $shipment->provider_tracking_number,
                $shipment->provider,
                $request->reason_id
            );

            if ($result) {
                // آپدیت وضعیت shipment
                $shipment->update(['status' => 'cancelled']);

                return response()->json([
                    'message' => 'Shipment cancelled successfully',
                ]);
            } else {
                return response()->json([
                    'message' => 'Failed to cancel shipment',
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error cancelling shipment', [
                'shipment_id' => $shipmentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to cancel shipment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

