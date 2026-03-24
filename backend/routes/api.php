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
use App\Http\Controllers\Api\V1\CommissionController;
use App\Http\Controllers\Api\V1\Inventory\InventoryController;
use App\Http\Controllers\Api\V1\Inventory\PurchaseOrderController;
use App\Http\Controllers\Api\V1\Inventory\StockTransferController;
use App\Http\Controllers\Api\V1\Inventory\StockCountController;
use App\Http\Controllers\Api\V1\Warehouse\WarehouseController;
use App\Http\Controllers\Api\V1\Shipment\ShipmentController;
use App\Http\Controllers\Api\V1\Shipment\CourierConfigController;
use App\Http\Controllers\Api\V1\Shipment\RemittanceController;
use App\Http\Controllers\Api\V1\Loyalty\LoyaltyTierController;
use App\Http\Controllers\Api\V1\Loyalty\LoyaltyAccountController;
use App\Http\Controllers\Api\V1\Loyalty\LoyaltyCampaignController;
use App\Http\Controllers\Api\V1\Tax\TaxConfigController;
use App\Http\Controllers\Api\V1\Tax\TaxInvoiceController;
use App\Http\Controllers\Api\V1\SaleEvent\SaleEventController;
use App\Http\Controllers\Api\V1\Settings\SettingsController;
use App\Http\Controllers\Api\V1\Dashboard\DashboardController;

Route::prefix('v1/panel')->group(function () {

    // Auth (rate limited: 5/min per IP)
    Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
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
        Route::apiResource('customers', CustomerController::class);
        Route::post('customers/{customer}/block', [CustomerController::class, 'block']);
        Route::get('customers/{customer}/orders', [CustomerController::class, 'orders']);
        Route::get('customers/{customer}/loyalty', [CustomerController::class, 'loyalty']);
        Route::apiResource('customers.interactions', CustomerInteractionController::class)->only(['index', 'store']);

        // Products
        Route::apiResource('brands', BrandController::class);
        Route::apiResource('categories', CategoryController::class);
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
        Route::get('orders/{order}/activities', [OrderActivityController::class, 'index']);
        Route::get('orders/{order}/activities/{activity}', [OrderActivityController::class, 'show']);

        // Commission Management
        Route::get('commissions/summary', [CommissionController::class, 'summary']);
        Route::get('commissions/orders', [CommissionController::class, 'orders']);
        Route::post('commissions/configs', [CommissionController::class, 'storeConfig']);
        Route::post('commissions/{order}/approve', [CommissionController::class, 'approve']);
        Route::post('commissions/{order}/mark-paid', [CommissionController::class, 'markPaid']);
        Route::post('commissions/{order}/cancel', [CommissionController::class, 'cancel']);

        // Inventory
        Route::get('inventory', [InventoryController::class, 'index']);
        Route::post('inventory/adjust', [InventoryController::class, 'adjust']);
        Route::get('inventory/movements', [InventoryController::class, 'movements']);
        Route::get('inventory/export', [InventoryController::class, 'export']);
        Route::get('inventory/{variant}', [InventoryController::class, 'show']);
        Route::apiResource('purchase-orders', PurchaseOrderController::class);
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
