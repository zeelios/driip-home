<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Staff\StaffController;
use App\Http\Controllers\Api\V1\Staff\SalaryController;
use App\Http\Controllers\Api\V1\Customer\CustomerController;
use App\Http\Controllers\Api\V1\Customer\CustomerInteractionController;
use App\Http\Controllers\Api\V1\Product\BrandController;
use App\Http\Controllers\Api\V1\Product\CategoryController;
use App\Http\Controllers\Api\V1\Product\ProductController;
use App\Http\Controllers\Api\V1\Product\ProductVariantController;
use App\Http\Controllers\Api\V1\Coupon\CouponController;
use App\Http\Controllers\Api\V1\Order\OrderController;
use App\Http\Controllers\Api\V1\Order\OrderClaimController;
use App\Http\Controllers\Api\V1\Order\OrderReturnController;
use App\Http\Controllers\Api\V1\Order\BulkOrderController;
use App\Http\Controllers\Api\V1\Order\DocumentController;
use App\Http\Controllers\Api\V1\Order\OrderPaymentController;
use App\Http\Controllers\Api\V1\Order\OrderActivityController;
use App\Http\Controllers\Api\V1\Order\PaymentReportController;
use App\Http\Controllers\Api\V1\Payment\BankConfigController;
use App\Http\Controllers\Api\V1\Payment\PendingDepositController;
use App\Http\Controllers\Api\V1\CommissionController;
use App\Http\Controllers\Api\V1\Inventory\InventoryController;
use App\Http\Controllers\Api\V1\Inventory\PurchaseOrderController;
use App\Http\Controllers\Api\V1\Inventory\StockTransferController;
use App\Http\Controllers\Api\V1\Inventory\PurchaseRequestController;
use App\Http\Controllers\Api\V1\Inventory\StockCountController;
use App\Http\Controllers\Api\V1\Warehouse\WarehouseController;
use App\Http\Controllers\Api\V1\Shipment\ShipmentController;
use App\Http\Controllers\Api\V1\Shipment\CourierConfigController;
use App\Http\Controllers\Api\V1\Shipment\RemittanceController;
use App\Http\Controllers\Api\V1\Shipment\ShipmentDiscrepancyController;
use App\Http\Controllers\Api\V1\Shipment\GhtkController;
use App\Http\Controllers\Api\V1\Loyalty\LoyaltyTierController;
use App\Http\Controllers\Api\V1\Loyalty\LoyaltyAccountController;
use App\Http\Controllers\Api\V1\Loyalty\LoyaltyCampaignController;
use App\Http\Controllers\Api\V1\Tax\TaxConfigController;
use App\Http\Controllers\Api\V1\Tax\TaxInvoiceController;
use App\Http\Controllers\Api\V1\SaleEvent\SaleEventController;
use App\Http\Controllers\Api\V1\Settings\SettingsController;
use App\Http\Controllers\Api\V1\Dashboard\DashboardController;
use App\Http\Controllers\Api\V1\Fulfillment\FulfillmentController;

Route::prefix('v1/panel')->group(function () {

    // DEBUG: Test endpoint to diagnose controller issues
    Route::get('test', function () {
        return response()->json(['status' => 'ok', 'time' => now()]);
    });

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // Authenticated & authorized routes
    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Staff Management
        Route::apiResource('staff', StaffController::class);
        Route::post('staff/{staff}/salary/pay', [SalaryController::class, 'store']);
        Route::get('staff/{staff}/salary', [SalaryController::class, 'index']);

        // Customer Management
        Route::get('customers/search', [CustomerController::class, 'search']);
        Route::apiResource('customers', CustomerController::class);
        Route::post('customers/{customer}/block', [CustomerController::class, 'block']);
        Route::get('customers/{customer}/orders', [CustomerController::class, 'orders']);
        Route::get('customers/{customer}/loyalty', [CustomerController::class, 'loyalty']);
        Route::apiResource('customers.interactions', CustomerInteractionController::class)->only(['index', 'store']);

        // Products
        Route::apiResource('brands', BrandController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::get('products/search', [ProductController::class, 'search']);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('products.variants', ProductVariantController::class);
        Route::get('products/{product}/variants/{variant}/inventory', [ProductVariantController::class, 'inventory']);

        // Coupons
        Route::post('coupons/validate', [CouponController::class, 'validate']);
        Route::apiResource('coupons', CouponController::class);

        // Bulk order operations (must be before individual order routes to avoid {order} wildcard match)
        Route::prefix('orders/bulk')->group(function () {
            Route::post('confirm', [BulkOrderController::class, 'confirm']);
            Route::post('ship', [BulkOrderController::class, 'ship']);
            Route::post('cancel', [BulkOrderController::class, 'cancel']);
            Route::post('export', [BulkOrderController::class, 'export']);
        });

        // Orders
        Route::apiResource('orders', OrderController::class);
        Route::post('orders/{order}/confirm', [OrderController::class, 'confirm']);
        Route::post('orders/{order}/pack', [OrderController::class, 'pack']);
        Route::post('orders/{order}/ship', [OrderController::class, 'ship']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
        Route::get('orders/{order}/timeline', [OrderController::class, 'timeline']);
        Route::get('orders/{order}/packing-slip', [DocumentController::class, 'packingSlip']);
        Route::post('orders/{order}/tax-invoice', [DocumentController::class, 'generateTaxInvoice']);
        Route::apiResource('orders.claims', OrderClaimController::class)->only(['index', 'store', 'show', 'update']);
        Route::apiResource('orders.returns', OrderReturnController::class)->only(['index', 'store', 'show', 'update']);

        // Order Payments & Activity
        Route::post('orders/{order}/deposit', [OrderPaymentController::class, 'recordDeposit']);
        Route::post('orders/{order}/payment-proof', [OrderPaymentController::class, 'uploadProof']);
        Route::delete('orders/{order}/payment-proof/{index}', [OrderPaymentController::class, 'removeProof']);
        Route::post('orders/{order}/verify-payment', [OrderPaymentController::class, 'verifyPayment']);
        Route::post('orders/{order}/record-payment', [OrderPaymentController::class, 'recordPayment']);
        Route::post('orders/{order}/cod-collection', [OrderPaymentController::class, 'recordCodCollection']);
        Route::get('orders/{order}/activities', [OrderActivityController::class, 'index']);
        Route::get('orders/{order}/activities/{activity}', [OrderActivityController::class, 'show']);

        // Payment Reports
        Route::get('payments/summary', [PaymentReportController::class, 'summary']);
        Route::get('payments/outstanding', [PaymentReportController::class, 'outstanding']);
        Route::get('payments/daily', [PaymentReportController::class, 'daily']);
        Route::get('payments/cod-pending', [PaymentReportController::class, 'codPendingCollection']);
        Route::get('payments/cod-discrepancies', [PaymentReportController::class, 'codDiscrepancies']);

        // Bank Configurations (RPA)
        Route::apiResource('bank-configs', BankConfigController::class);
        Route::post('bank-configs/{bankConfig}/test-connection', [BankConfigController::class, 'testConnection']);
        Route::post('bank-configs/{bankConfig}/trigger-check', [BankConfigController::class, 'triggerCheck']);

        // Pending Deposits
        Route::get('pending-deposits', [PendingDepositController::class, 'index']);
        Route::get('pending-deposits/{pendingDeposit}', [PendingDepositController::class, 'show']);
        Route::post('pending-deposits/{pendingDeposit}/cancel', [PendingDepositController::class, 'cancel']);
        Route::post('pending-deposits/{pendingDeposit}/manual-match', [PendingDepositController::class, 'manualMatch']);
        Route::get('bank-check-logs', [PendingDepositController::class, 'logs']);
        Route::post('orders/{order}/pending-deposit', [PendingDepositController::class, 'store']);

        // Commission Management
        Route::get('commissions/summary', [CommissionController::class, 'summary']);
        Route::get('commissions/orders', [CommissionController::class, 'orders']);
        Route::post('commissions/configs', [CommissionController::class, 'storeConfig']);
        Route::post('commissions/{order}/approve', [CommissionController::class, 'approve']);
        Route::post('commissions/{order}/mark-paid', [CommissionController::class, 'markPaid']);
        Route::post('commissions/{order}/cancel', [CommissionController::class, 'cancel']);

        // Fulfillment
        Route::get('fulfillment/items', [FulfillmentController::class, 'index']);
        Route::get('fulfillment/stats', [FulfillmentController::class, 'stats']);
        Route::post('fulfillment/pick', [FulfillmentController::class, 'pick']);
        Route::post('fulfillment/pack', [FulfillmentController::class, 'pack']);
        Route::post('fulfillment/export', [FulfillmentController::class, 'export']);

        // Inventory
        Route::get('inventory', [InventoryController::class, 'index']);
        Route::post('inventory/adjust', [InventoryController::class, 'adjust']);
        Route::get('inventory/movements', [InventoryController::class, 'movements']);
        Route::get('inventory/export', [InventoryController::class, 'export']);
        Route::get('inventory/{variant}', [InventoryController::class, 'show']);
        Route::apiResource('purchase-orders', PurchaseOrderController::class);
        Route::get('purchase-requests', [PurchaseRequestController::class, 'index']);
        Route::get('purchase-requests/low-stock', [PurchaseRequestController::class, 'lowStock']);
        Route::get('purchase-requests/unfulfillable', [PurchaseRequestController::class, 'unfulfillable']);
        Route::get('purchase-requests/by-supplier', [PurchaseRequestController::class, 'bySupplier']);
        Route::post('purchase-requests/create-po', [PurchaseRequestController::class, 'createPurchaseOrders']);
        Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve']);
        Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
        Route::apiResource('stock-transfers', StockTransferController::class);
        Route::post('stock-transfers/{stockTransfer}/approve', [StockTransferController::class, 'approve']);
        Route::post('stock-transfers/{stockTransfer}/dispatch', [StockTransferController::class, 'dispatch']);
        Route::post('stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'receive']);
        Route::apiResource('stock-counts', StockCountController::class);
        Route::post('stock-counts/{stockCount}/items/{item}/count', [StockCountController::class, 'countItem']);
        Route::post('stock-counts/{stockCount}/complete', [StockCountController::class, 'complete']);
        Route::post('stock-counts/{stockCount}/approve', [StockCountController::class, 'approve']);

        // Warehouses
        Route::apiResource('warehouses', WarehouseController::class);
        Route::get('warehouses/{warehouse}/inventory', [WarehouseController::class, 'inventory']);
        Route::post('warehouses/{warehouse}/staff', [WarehouseController::class, 'assignStaff']);

        // Shipments
        Route::apiResource('shipments', ShipmentController::class)->only(['index', 'show', 'destroy']);
        Route::post('shipments/{shipment}/track', [ShipmentController::class, 'syncTracking']);
        Route::get('shipments/{shipment}/label', [ShipmentController::class, 'label']);
        Route::get('shipments/pending-cod', [ShipmentDiscrepancyController::class, 'pendingCod']);
        Route::get('shipments/discrepancies', [ShipmentDiscrepancyController::class, 'index']);
        Route::get('shipments/discrepancies/summary', [ShipmentDiscrepancyController::class, 'summary']);
        Route::post('shipments/discrepancies/detect', [ShipmentDiscrepancyController::class, 'forceDetect']);
        Route::get('shipments/discrepancies/{discrepancy}', [ShipmentDiscrepancyController::class, 'show']);
        Route::post('shipments/discrepancies/{discrepancy}/resolve', [ShipmentDiscrepancyController::class, 'resolve']);
        Route::post('shipments/discrepancies/{discrepancy}/investigate', [ShipmentDiscrepancyController::class, 'investigate']);
        Route::post('shipments/discrepancies/{discrepancy}/dismiss', [ShipmentDiscrepancyController::class, 'dismiss']);

        // GHTK Specific Endpoints
        Route::prefix('ghtk')->group(function () {
            Route::post('calculate-fee', [GhtkController::class, 'calculateFee']);
            Route::post('submit-order', [GhtkController::class, 'submitOrder']);
            Route::get('orders/{trackingNumber}/status', [GhtkController::class, 'getOrderStatus']);
            Route::post('orders/{trackingNumber}/sync', [GhtkController::class, 'syncStatus']);
            Route::get('orders/{trackingNumber}/label', [GhtkController::class, 'printLabel']);
            Route::get('shipments/{shipment}/label-a7', [GhtkController::class, 'printA7Label']);
            Route::post('orders/{trackingNumber}/cancel', [GhtkController::class, 'cancelOrder']);
            Route::post('shipments/{shipment}/cancel', [GhtkController::class, 'cancelShipment']);
        });

        Route::apiResource('courier-configs', CourierConfigController::class);
        Route::get('courier-remittances', [RemittanceController::class, 'index']);
        Route::get('courier-remittances/{remittance}', [RemittanceController::class, 'show']);
        Route::post('courier-remittances/{remittance}/reconcile', [RemittanceController::class, 'reconcile']);
        Route::post('courier-remittances/{remittance}/confirm', [RemittanceController::class, 'confirm']);

        // Loyalty
        Route::apiResource('loyalty/tiers', LoyaltyTierController::class);
        Route::get('loyalty/accounts/{customer}', [LoyaltyAccountController::class, 'show']);
        Route::post('loyalty/accounts/{customer}/earn', [LoyaltyAccountController::class, 'earn']);
        Route::post('loyalty/accounts/{customer}/redeem', [LoyaltyAccountController::class, 'redeem']);
        Route::apiResource('loyalty/campaigns', LoyaltyCampaignController::class);

        // Tax
        Route::apiResource('tax/configs', TaxConfigController::class);
        Route::apiResource('tax/invoices', TaxInvoiceController::class)->only(['index', 'show']);
        Route::get('tax/invoices/{invoice}', [TaxInvoiceController::class, 'download']);

        // Sale Events
        Route::apiResource('sale-events', SaleEventController::class);
        Route::post('sale-events/{saleEvent}/activate', [SaleEventController::class, 'activate']);
        Route::post('sale-events/{saleEvent}/end', [SaleEventController::class, 'end']);

        // Settings
        Route::get('settings', [SettingsController::class, 'index']);
        Route::patch('settings', [SettingsController::class, 'update']);
    });
});
