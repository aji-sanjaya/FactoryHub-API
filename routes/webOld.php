<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\AuthController;

// authentication pages (PUBLIC)
Route::get('/signin', [AuthController::class, 'showLoginForm'])->name('signin');
Route::post('/signin', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/auth/roles', [AuthController::class, 'showRoleSelection'])->name('auth.roles');
Route::post('/auth/roles', [AuthController::class, 'selectRole'])->name('auth.roles.store');
Route::get('/auth/roles-partial', [AuthController::class, 'showRoleSelectionPartial'])->name('auth.roles.partial');
Route::get('/auth/api/roles', [AuthController::class, 'getRoles'])->name('auth.api.roles');
Route::get('/auth/api/orgs', [AuthController::class, 'getOrgs'])->name('auth.api.orgs');
Route::get('/auth/api/warehouses', [AuthController::class, 'getWarehouses'])->name('auth.api.warehouses');

Route::get('/signup', function () {
    return view('pages.auth.signup', ['title' => 'Sign Up']);
})->name('signup');

// PROTECTED ROUTES
Route::middleware(['auth'])->group(function () {
    // dashboard pages
    Route::get('/', [\App\Http\Controllers\ProcurementDashboardController::class, 'index'])->name('dashboard');
    Route::get('/petty-cash-dashboard', [\App\Http\Controllers\PettyCashDashboardController::class, 'index'])->name('petty-cash-dashboard');
    Route::get('/petty-cash-dashboard/export', [\App\Http\Controllers\PettyCashDashboardController::class, 'export'])->name('petty-cash-dashboard.export');
    Route::get('/sales-dashboard', [\App\Http\Controllers\SalesDashboardController::class, 'index'])->name('sales-dashboard');
    Route::get('/delivery-dashboard', [\App\Http\Controllers\DeliveryDashboardController::class, 'index'])->name('delivery-dashboard');
    Route::get('/warehouse-dashboard', [\App\Http\Controllers\WarehouseDashboardController::class, 'index'])->name('warehouse-dashboard');

    // Procurement / Requisition
    Route::get('/create-pr', [\App\Http\Controllers\RequisitionController::class, 'index'])->name('requisition.index');
    Route::get('/create-pr/new', [\App\Http\Controllers\RequisitionController::class, 'create'])->name('requisition.create');
    Route::post('/create-pr/store', [\App\Http\Controllers\RequisitionController::class, 'store'])->name('requisition.store');
    Route::put('/create-pr/update/{id}', [\App\Http\Controllers\RequisitionController::class, 'updateHeader'])->name('requisition.update');
    Route::get('/create-pr/api/warehouses', [\App\Http\Controllers\RequisitionController::class, 'getWarehouses'])->name('requisition.api.warehouses');
    Route::get('/create-pr/api/products', [\App\Http\Controllers\RequisitionController::class, 'getProducts'])->name('requisition.api.products'); // API for Products
    Route::get('/create-pr/api/product-price', [\App\Http\Controllers\RequisitionController::class, 'getProductPrice'])->name('requisition.api.product-price'); // API for Product Price

    // Requisition Lines
    Route::get('/create-pr/line/create', [\App\Http\Controllers\RequisitionLineController::class, 'create'])->name('requisition.line.create');
    Route::post('/create-pr/line/store', [\App\Http\Controllers\RequisitionLineController::class, 'store'])->name('requisition.line.store');
    Route::put('/create-pr/line/update', [\App\Http\Controllers\RequisitionLineController::class, 'update'])->name('requisition.line.update');
    Route::delete('/create-pr/line/delete', [\App\Http\Controllers\RequisitionLineController::class, 'delete'])->name('requisition.line.delete');

    // Requisition Process & Delete
    Route::post('/create-pr/process', [\App\Http\Controllers\RequisitionController::class, 'process'])->name('requisition.process');
    Route::delete('/create-pr/delete', [\App\Http\Controllers\RequisitionController::class, 'destroy'])->name('requisition.delete');

    // Print Requisition
    Route::get('/create-pr/print/{id}', [\App\Http\Controllers\RequisitionController::class, 'print'])->name('requisition.print');

    // Sales Order
    Route::get('/sales-order', [\App\Http\Controllers\SalesOrderController::class, 'index'])->name('sales-order.index');
    Route::get('/sales-order/new', [\App\Http\Controllers\SalesOrderController::class, 'create'])->name('sales-order.create');
    Route::post('/sales-order/store', [\App\Http\Controllers\SalesOrderController::class, 'store'])->name('sales-order.store');
    Route::put('/sales-order/update/{id}', [\App\Http\Controllers\SalesOrderController::class, 'updateHeader'])->name('sales-order.update');
    Route::get('/sales-order/api/warehouses', [\App\Http\Controllers\SalesOrderController::class, 'getWarehouses'])->name('sales-order.api.warehouses');
    Route::get('/sales-order/api/products', [\App\Http\Controllers\SalesOrderController::class, 'getProducts'])->name('sales-order.api.products');
    Route::get('/sales-order/api/product-price', [\App\Http\Controllers\SalesOrderController::class, 'getProductPrice'])->name('sales-order.api.product-price');
    Route::get('/sales-order-api/bpartner-locations', [\App\Http\Controllers\SalesOrderController::class, 'getBPartnerLocations'])->name('sales-order.api.bpartner-locations');

    // Sales Order Lines
    Route::get('/sales-order/line/create', [\App\Http\Controllers\SalesOrderLineController::class, 'create'])->name('sales-order.line.create');
    Route::post('/sales-order/line/store', [\App\Http\Controllers\SalesOrderLineController::class, 'store'])->name('sales-order.line.store');
    Route::put('/sales-order/line/update', [\App\Http\Controllers\SalesOrderLineController::class, 'update'])->name('sales-order.line.update');
    Route::delete('/sales-order/line/delete', [\App\Http\Controllers\SalesOrderLineController::class, 'delete'])->name('sales-order.line.delete');
    Route::get('/sales-order/line/template', [\App\Http\Controllers\SalesOrderLineController::class, 'downloadTemplate'])->name('sales-order.line.template');
    Route::post('/sales-order/line/import', [\App\Http\Controllers\SalesOrderLineController::class, 'import'])->name('sales-order.line.import');
    Route::get('/sales-order/line/download-errors/{filename}', [\App\Http\Controllers\SalesOrderLineController::class, 'downloadErrors'])->name('sales-order.line.download-errors');

    // Sales Order Process & Delete
    Route::post('/sales-order/process', [\App\Http\Controllers\SalesOrderController::class, 'process'])->name('sales-order.process');
    Route::delete('/sales-order/delete', [\App\Http\Controllers\SalesOrderController::class, 'destroy'])->name('sales-order.delete');

    // Print Sales Order
    Route::get('/sales-order/print/{id}', [\App\Http\Controllers\SalesOrderController::class, 'print'])->name('sales-order.print');

    // Attachments Sales Order
    Route::post('/sales-order/attachment/upload', [\App\Http\Controllers\SalesOrderController::class, 'uploadAttachment'])->name('sales-order.attachment.upload');
    Route::delete('/sales-order/attachment/delete', [\App\Http\Controllers\SalesOrderController::class, 'deleteAttachment'])->name('sales-order.attachment.delete');
    Route::get('/sales-order/attachment/view/{document_id}/{file_name}', [\App\Http\Controllers\SalesOrderController::class, 'viewAttachment'])->name('sales-order.attachment.view');

    // Delivery Schedule
    Route::get('/delivery-schedule', [\App\Http\Controllers\DeliveryScheduleController::class, 'index'])->name('delivery-schedule.index');
    Route::get('/delivery-schedule/new', [\App\Http\Controllers\DeliveryScheduleController::class, 'create'])->name('delivery-schedule.create');
    Route::post('/delivery-schedule/store', [\App\Http\Controllers\DeliveryScheduleController::class, 'store'])->name('delivery-schedule.store');
    Route::put('/delivery-schedule/update/{id}', [\App\Http\Controllers\DeliveryScheduleController::class, 'updateHeader'])->name('delivery-schedule.update');
    Route::get('/delivery-schedule/api/warehouses', [\App\Http\Controllers\DeliveryScheduleController::class, 'getWarehouses'])->name('delivery-schedule.api.warehouses');
    Route::get('/delivery-schedule/api/products', [\App\Http\Controllers\DeliveryScheduleController::class, 'getProducts'])->name('delivery-schedule.api.products');
    Route::get('/delivery-schedule/api/product-price', [\App\Http\Controllers\DeliveryScheduleController::class, 'getProductPrice'])->name('delivery-schedule.api.product-price');
    Route::get('/delivery-schedule-api/bpartner-locations', [\App\Http\Controllers\DeliveryScheduleController::class, 'getBPartnerLocations'])->name('delivery-schedule.api.bpartner-locations');
    Route::get('/delivery-schedule-api/sales-orders', [\App\Http\Controllers\DeliveryScheduleController::class, 'getSalesOrders'])->name('delivery-schedule.api.sales-orders');

    // Delivery Schedule Lines
    Route::get('/delivery-schedule/line/create', [\App\Http\Controllers\DeliveryScheduleLineController::class, 'create'])->name('delivery-schedule.line.create');
    Route::post('/delivery-schedule/line/store', [\App\Http\Controllers\DeliveryScheduleLineController::class, 'store'])->name('delivery-schedule.line.store');
    Route::put('/delivery-schedule/line/update', [\App\Http\Controllers\DeliveryScheduleLineController::class, 'update'])->name('delivery-schedule.line.update');
    Route::delete('/delivery-schedule/line/delete', [\App\Http\Controllers\DeliveryScheduleLineController::class, 'delete'])->name('delivery-schedule.line.delete');
    Route::get('/delivery-schedule/line/template', [\App\Http\Controllers\DeliveryScheduleLineController::class, 'downloadTemplate'])->name('delivery-schedule.line.template');
    Route::post('/delivery-schedule/line/import', [\App\Http\Controllers\DeliveryScheduleLineController::class, 'import'])->name('delivery-schedule.line.import');
    Route::get('/delivery-schedule/line/download-errors/{filename}', [\App\Http\Controllers\DeliveryScheduleLineController::class, 'downloadErrors'])->name('delivery-schedule.line.download-errors');

    // From SO routes
    Route::get('/delivery-schedule/api/so-lines', [\App\Http\Controllers\DeliveryScheduleController::class, 'getSOLines'])->name('delivery-schedule.api.so-lines');
    Route::post('/delivery-schedule/api/store-so-lines', [\App\Http\Controllers\DeliveryScheduleLineController::class, 'storeFromSO'])->name('delivery-schedule.api.store-so-lines');

    // Delivery Schedule Processing
    Route::post('/delivery-schedule/process', [\App\Http\Controllers\DeliveryScheduleController::class, 'process'])->name('delivery-schedule.process');
    Route::delete('/delivery-schedule/delete', [\App\Http\Controllers\DeliveryScheduleController::class, 'destroy'])->name('delivery-schedule.delete');

    // Print
    Route::get('/delivery-schedule/print/{id}', [\App\Http\Controllers\DeliveryScheduleController::class, 'print'])->name('delivery-schedule.print');

    // Delivery Schedule Attachments
    Route::post('/delivery-schedule/attachment/upload', [\App\Http\Controllers\DeliveryScheduleController::class, 'uploadAttachment'])->name('delivery-schedule.attachment.upload');
    Route::delete('/delivery-schedule/attachment/delete', [\App\Http\Controllers\DeliveryScheduleController::class, 'deleteAttachment'])->name('delivery-schedule.attachment.delete');
    Route::get('/delivery-schedule/attachment/view/{document_id}/{file_name}', [\App\Http\Controllers\DeliveryScheduleController::class, 'viewAttachment'])->name('delivery-schedule.attachment.view');
    // Customer Shipment
    Route::get('/customer-shipment', [\App\Http\Controllers\CustomerShipmentController::class, 'index'])->name('customer-shipment.index');
    Route::get('/customer-shipment/new', [\App\Http\Controllers\CustomerShipmentController::class, 'create'])->name('customer-shipment.create');
    Route::post('/customer-shipment/store', [\App\Http\Controllers\CustomerShipmentController::class, 'store'])->name('customer-shipment.store');
    Route::put('/customer-shipment/update/{id}', [\App\Http\Controllers\CustomerShipmentController::class, 'updateHeader'])->name('customer-shipment.update');
    Route::get('/customer-shipment/api/warehouses', [\App\Http\Controllers\CustomerShipmentController::class, 'getWarehouses'])->name('customer-shipment.api.warehouses');
    Route::get('/customer-shipment/api/products', [\App\Http\Controllers\CustomerShipmentController::class, 'getProducts'])->name('customer-shipment.api.products');
    Route::get('/customer-shipment/api/bpartner-locations', [\App\Http\Controllers\CustomerShipmentController::class, 'getBPartnerLocations'])->name('customer-shipment.api.bpartner-locations');
    Route::get('/customer-shipment/api/so-lines', [\App\Http\Controllers\CustomerShipmentController::class, 'getSOLines'])->name('customer-shipment.api.so-lines');

    // Customer Shipment Lines
    Route::get('/customer-shipment/line/create', [\App\Http\Controllers\CustomerShipmentLineController::class, 'create'])->name('customer-shipment.line.create');
    Route::post('/customer-shipment/line/store', [\App\Http\Controllers\CustomerShipmentLineController::class, 'store'])->name('customer-shipment.line.store');
    Route::post('/customer-shipment/line/store-from-so', [\App\Http\Controllers\CustomerShipmentLineController::class, 'storeFromSO'])->name('customer-shipment.line.store-from-so');
    Route::put('/customer-shipment/line/update', [\App\Http\Controllers\CustomerShipmentLineController::class, 'update'])->name('customer-shipment.line.update');
    Route::delete('/customer-shipment/line/delete', [\App\Http\Controllers\CustomerShipmentLineController::class, 'delete'])->name('customer-shipment.line.delete');
    Route::get('/customer-shipment/line/template', [\App\Http\Controllers\CustomerShipmentLineController::class, 'downloadTemplate'])->name('customer-shipment.line.template');
    Route::post('/customer-shipment/line/import', [\App\Http\Controllers\CustomerShipmentLineController::class, 'import'])->name('customer-shipment.line.import');
    Route::get('/customer-shipment/line/download-errors/{filename}', [\App\Http\Controllers\CustomerShipmentLineController::class, 'downloadErrors'])->name('customer-shipment.line.download-errors');

    // Customer Shipment Process & Delete
    Route::post('/customer-shipment/process', [\App\Http\Controllers\CustomerShipmentController::class, 'process'])->name('customer-shipment.process');
    Route::delete('/customer-shipment/delete', [\App\Http\Controllers\CustomerShipmentController::class, 'destroy'])->name('customer-shipment.delete');

    // Print Customer Shipment
    Route::get('/customer-shipment/print/{id}', [\App\Http\Controllers\CustomerShipmentController::class, 'print'])->name('customer-shipment.print');

    // Attachments Customer Shipment
    Route::post('/customer-shipment/attachment/upload', [\App\Http\Controllers\CustomerShipmentController::class, 'uploadAttachment'])->name('customer-shipment.attachment.upload');
    Route::delete('/customer-shipment/attachment/delete', [\App\Http\Controllers\CustomerShipmentController::class, 'deleteAttachment'])->name('customer-shipment.attachment.delete');
    Route::get('/customer-shipment/attachment/view/{document_id}/{file_name}', [\App\Http\Controllers\CustomerShipmentController::class, 'viewAttachment'])->name('customer-shipment.attachment.view');

    // Customer Shipment Post Logic (Journal & Repost)
    Route::post('/customer-shipment/repost/{id}', [\App\Http\Controllers\CustomerShipmentController::class, 'repost'])->name('customer-shipment.repost');
    Route::get('/customer-shipment/export-journals/{id}', [\App\Http\Controllers\CustomerShipmentController::class, 'exportJournals'])->name('customer-shipment.export-journals');
    Route::put('/customer-shipment/toggle-tracking/{id}', [\App\Http\Controllers\CustomerShipmentController::class, 'toggleTracking'])->name('customer-shipment.toggle-tracking');

    // Approval PR
    Route::get('/approval-pr', [\App\Http\Controllers\ApprovalPrController::class, 'index'])->name('approval-pr.index');
    Route::get('/approval-pr/suppliers', [\App\Http\Controllers\ApprovalPrController::class, 'getSuppliers'])->name('approval-pr.suppliers');
    Route::get('/approval-pr/cost-centers', [\App\Http\Controllers\ApprovalPrController::class, 'getCostCenters'])->name('approval-pr.cost-centers');
    Route::get('/approval-pr/{id}', [\App\Http\Controllers\ApprovalPrController::class, 'show'])->name('approval-pr.show');
    Route::post('/approval-pr/{id}/process', [\App\Http\Controllers\ApprovalPrController::class, 'process'])->name('approval-pr.process');

    // Approval PO
    Route::get('/approval-po', [\App\Http\Controllers\ApprovalPoController::class, 'index'])->name('approval-po.index');
    Route::get('/approval-po/suppliers', [\App\Http\Controllers\ApprovalPoController::class, 'getSuppliers'])->name('approval-po.suppliers');
    // Route::get('/approval-po/cost-centers', ...) - PO usually doesn't filter by cost center on header like PR? keeping it simple for now
    Route::get('/approval-po/{id}', [\App\Http\Controllers\ApprovalPoController::class, 'show'])->name('approval-po.show');
    Route::post('/approval-po/{id}/process', [\App\Http\Controllers\ApprovalPoController::class, 'process'])->name('approval-po.process');
    Route::get('/approval-po/attachment/view/{document_id}/{file_name}', [\App\Http\Controllers\ApprovalPoController::class, 'viewAttachment'])->name('approval-po.attachment.view');

    // Attachments
    Route::post('/create-pr/attachment/upload', [\App\Http\Controllers\RequisitionController::class, 'uploadAttachment'])->name('requisition.attachment.upload');
    Route::delete('/create-pr/attachment/delete', [\App\Http\Controllers\RequisitionController::class, 'deleteAttachment'])->name('requisition.attachment.delete');

    // Purchase Order
    Route::get('/create-po', [\App\Http\Controllers\PurchaseOrderController::class, 'index'])->name('purchase-order.index');
    Route::get('/create-po/new', [\App\Http\Controllers\PurchaseOrderController::class, 'create'])->name('purchase-order.create');
    Route::post('/create-po/store', [\App\Http\Controllers\PurchaseOrderController::class, 'store'])->name('purchase-order.store');
    Route::get('/create-po/print/{id}', [\App\Http\Controllers\PurchaseOrderController::class, 'print'])->name('purchase-order.print');
    Route::put('/create-po/update/{id}', [\App\Http\Controllers\PurchaseOrderController::class, 'update'])->name('purchase-order.update');

    // PO API Routes
    Route::get('/create-po/api/products', [\App\Http\Controllers\PurchaseOrderController::class, 'getProducts'])->name('purchase-order.api.products');
    Route::get('/create-po/api/product-price', [\App\Http\Controllers\PurchaseOrderController::class, 'getProductPrice'])->name('purchase-order.api.product-price');
    Route::get('/create-po/api/warehouses', [\App\Http\Controllers\PurchaseOrderController::class, 'getWarehouses'])->name('purchase-order.api.warehouses');
    Route::get('/create-po/api/requisition-lines', [\App\Http\Controllers\PurchaseOrderController::class, 'getRequisitionLines'])->name('purchase-order.api.requisition-lines');
    Route::get('/create-po/api/withholding-types', [\App\Http\Controllers\PurchaseOrderController::class, 'getWithholdingTypes'])->name('purchase-order.api.withholding-types');

    // PO Lines
    Route::post('/create-po/line/store', [\App\Http\Controllers\PurchaseOrderController::class, 'storeLine'])->name('purchase-order.line.store');
    Route::put('/create-po/line/update', [\App\Http\Controllers\PurchaseOrderController::class, 'storeLine'])->name('purchase-order.line.update'); // Reusing storeLine logic or separate
    Route::delete('/create-po/line/delete', [\App\Http\Controllers\PurchaseOrderController::class, 'destroyLine'])->name('purchase-order.line.delete');

    // Header Actions
    Route::post('/create-po/process', [\App\Http\Controllers\PurchaseOrderController::class, 'process'])->name('purchase-order.process');
    Route::delete('/create-po/delete', [\App\Http\Controllers\PurchaseOrderController::class, 'destroy'])->name('purchase-order.delete');

    // Attachments
    Route::post('/create-po/attachment/upload', [\App\Http\Controllers\PurchaseOrderController::class, 'uploadAttachment'])->name('purchase-order.attachment.upload');
    Route::delete('/create-po/attachment/delete', [\App\Http\Controllers\PurchaseOrderController::class, 'deleteAttachment'])->name('purchase-order.attachment.delete');

    Route::get('/create-pr/attachment/view/{document_id}/{file_name}', [\App\Http\Controllers\RequisitionController::class, 'viewAttachment'])->name('requisition.attachment.view');
    Route::get('/create-po/attachment/view/{document_id}/{file_name}', [\App\Http\Controllers\PurchaseOrderController::class, 'viewAttachment'])->name('purchase-order.attachment.view');

    // Material Receipt (GR)
    Route::get('/create-gr', [\App\Http\Controllers\MaterialReceiptController::class, 'index'])->name('material-receipt.index');
    Route::get('/create-gr/new', [\App\Http\Controllers\MaterialReceiptController::class, 'create'])->name('material-receipt.create');
    Route::post('/create-gr/store', [\App\Http\Controllers\MaterialReceiptController::class, 'store'])->name('material-receipt.store');
    Route::put('/create-gr/update/{id}', [\App\Http\Controllers\MaterialReceiptController::class, 'update'])->name('material-receipt.update');
    Route::get('/create-gr/api/warehouses', [\App\Http\Controllers\MaterialReceiptController::class, 'getWarehouses'])->name('material-receipt.api.warehouses');
    Route::get('/create-gr/api/products', [\App\Http\Controllers\MaterialReceiptController::class, 'getProducts'])->name('material-receipt.api.products');
    Route::get('/create-gr/api/po-lines', [\App\Http\Controllers\MaterialReceiptController::class, 'getPoLines'])->name('material-receipt.api.po-lines');
    Route::post('/create-gr/line/store', [\App\Http\Controllers\MaterialReceiptController::class, 'storeLine'])->name('material-receipt.line.store');
    Route::put('/create-gr/line/update', [\App\Http\Controllers\MaterialReceiptController::class, 'storeLine'])->name('material-receipt.line.update');
    Route::delete('/create-gr/line/delete', [\App\Http\Controllers\MaterialReceiptController::class, 'destroyLine'])->name('material-receipt.line.delete');
    Route::post('/create-gr/process', [\App\Http\Controllers\MaterialReceiptController::class, 'process'])->name('material-receipt.process');
    Route::delete('/create-gr/delete', [\App\Http\Controllers\MaterialReceiptController::class, 'destroy'])->name('material-receipt.delete');
    Route::post('/create-gr/attachment/upload', [\App\Http\Controllers\MaterialReceiptController::class, 'uploadAttachment'])->name('material-receipt.attachment.upload');
    Route::delete('/create-gr/attachment/delete', [\App\Http\Controllers\MaterialReceiptController::class, 'deleteAttachment'])->name('material-receipt.attachment.delete');
    Route::get('/create-gr/attachment/view/{document_id}/{file_name}', [\App\Http\Controllers\MaterialReceiptController::class, 'viewAttachment'])->name('material-receipt.attachment.view');
    Route::get('/create-gr/print', [\App\Http\Controllers\MaterialReceiptController::class, 'printDocument'])->name('material-receipt.print');
    Route::post('/create-gr/repost/{id}', [\App\Http\Controllers\MaterialReceiptController::class, 'repost'])->name('material-receipt.repost');
    Route::get('/create-gr/export-journals/{id}', [\App\Http\Controllers\MaterialReceiptController::class, 'exportJournals'])->name('material-receipt.export-journals');

    // AP Invoice
    Route::get('/ap-invoice', [\App\Http\Controllers\ApInvoiceController::class, 'index'])->name('ap-invoice.index');
    Route::get('/ap-invoice/new', [\App\Http\Controllers\ApInvoiceController::class, 'create'])->name('ap-invoice.create');
    Route::post('/ap-invoice/store', [\App\Http\Controllers\ApInvoiceController::class, 'store'])->name('ap-invoice.store');
    Route::get('/ap-invoice/print/{id}', [\App\Http\Controllers\ApInvoiceController::class, 'print'])->name('ap-invoice.print');
    Route::put('/ap-invoice/update/{id}', [\App\Http\Controllers\ApInvoiceController::class, 'update'])->name('ap-invoice.update');
    Route::get('/ap-invoice/api/products', [\App\Http\Controllers\ApInvoiceController::class, 'getProducts'])->name('ap-invoice.api.products');
    Route::get('/ap-invoice/api/gr-lines', [\App\Http\Controllers\ApInvoiceController::class, 'getGrLines'])->name('ap-invoice.api.gr-lines');
    Route::get('/ap-invoice/api/receipt-lines', [\App\Http\Controllers\ApInvoiceController::class, 'getReceiptLines'])->name('ap-invoice.api.receipt-lines');
    Route::get('/ap-invoice/api/vendor-contacts', [\App\Http\Controllers\ApInvoiceController::class, 'getVendorContacts'])->name('ap-invoice.api.vendor-contacts');
    Route::post('/ap-invoice/line/store', [\App\Http\Controllers\ApInvoiceController::class, 'storeLine'])->name('ap-invoice.line.store');
    Route::put('/ap-invoice/line/update', [\App\Http\Controllers\ApInvoiceController::class, 'storeLine'])->name('ap-invoice.line.update');
    Route::delete('/ap-invoice/line/delete', [\App\Http\Controllers\ApInvoiceController::class, 'destroyLine'])->name('ap-invoice.line.delete');
    Route::post('/ap-invoice/process', [\App\Http\Controllers\ApInvoiceController::class, 'process'])->name('ap-invoice.process');
    Route::post('/ap-invoice/repost/{id}', [\App\Http\Controllers\ApInvoiceController::class, 'repost'])->name('ap-invoice.repost');
    Route::get('/ap-invoice/export-journals/{id}', [\App\Http\Controllers\ApInvoiceController::class, 'exportJournals'])->name('ap-invoice.export-journals');
    Route::delete('/ap-invoice/delete', [\App\Http\Controllers\ApInvoiceController::class, 'destroy'])->name('ap-invoice.delete');
    Route::post('/ap-invoice/attachment/upload', [\App\Http\Controllers\ApInvoiceController::class, 'uploadAttachment'])->name('ap-invoice.attachment.upload');
    Route::delete('/ap-invoice/attachment/delete', [\App\Http\Controllers\ApInvoiceController::class, 'deleteAttachment'])->name('ap-invoice.attachment.delete');
    Route::get('/ap-invoice/attachment/view/{document_id}/{file_name}', [\App\Http\Controllers\ApInvoiceController::class, 'viewAttachment'])->name('ap-invoice.attachment.view');

    // AR Invoice
    Route::get('/ar-invoice', [\App\Http\Controllers\ArInvoiceController::class, 'index'])->name('ar-invoice.index');
    Route::get('/ar-invoice/new', [\App\Http\Controllers\ArInvoiceController::class, 'create'])->name('ar-invoice.create');
    Route::post('/ar-invoice/store', [\App\Http\Controllers\ArInvoiceController::class, 'store'])->name('ar-invoice.store');
    Route::get('/ar-invoice/print/{id}', [\App\Http\Controllers\ArInvoiceController::class, 'print'])->name('ar-invoice.print');
    Route::put('/ar-invoice/update/{id}', [\App\Http\Controllers\ArInvoiceController::class, 'update'])->name('ar-invoice.update');
    Route::get('/ar-invoice/api/products', [\App\Http\Controllers\ArInvoiceController::class, 'getProducts'])->name('ar-invoice.api.products');
    Route::get('/ar-invoice/api/shipment-lines', [\App\Http\Controllers\ArInvoiceController::class, 'getShipmentLines'])->name('ar-invoice.api.shipment-lines');
    Route::get('/ar-invoice/api/shipment-lines-link', [\App\Http\Controllers\ArInvoiceController::class, 'getShipmentLinesLink'])->name('ar-invoice.api.shipment-lines-link');
    Route::get('/ar-invoice/api/customer-contacts', [\App\Http\Controllers\ArInvoiceController::class, 'getCustomerContacts'])->name('ar-invoice.api.customer-contacts');
    Route::post('/ar-invoice/line/store', [\App\Http\Controllers\ArInvoiceController::class, 'storeLine'])->name('ar-invoice.line.store');
    Route::put('/ar-invoice/line/update', [\App\Http\Controllers\ArInvoiceController::class, 'storeLine'])->name('ar-invoice.line.update');
    Route::delete('/ar-invoice/line/delete', [\App\Http\Controllers\ArInvoiceController::class, 'destroyLine'])->name('ar-invoice.line.delete');
    Route::post('/ar-invoice/process', [\App\Http\Controllers\ArInvoiceController::class, 'process'])->name('ar-invoice.process');
    Route::post('/ar-invoice/repost/{id}', [\App\Http\Controllers\ArInvoiceController::class, 'repost'])->name('ar-invoice.repost');
    Route::get('/ar-invoice/export-journals/{id}', [\App\Http\Controllers\ArInvoiceController::class, 'exportJournals'])->name('ar-invoice.export-journals');
    Route::delete('/ar-invoice/delete', [\App\Http\Controllers\ArInvoiceController::class, 'destroy'])->name('ar-invoice.delete');
    Route::post('/ar-invoice/attachment/upload', [\App\Http\Controllers\ArInvoiceController::class, 'uploadAttachment'])->name('ar-invoice.attachment.upload');
    Route::delete('/ar-invoice/attachment/delete', [\App\Http\Controllers\ArInvoiceController::class, 'deleteAttachment'])->name('ar-invoice.attachment.delete');
    Route::get('/ar-invoice/attachment/view/{document_id}/{file_name}', [\App\Http\Controllers\ArInvoiceController::class, 'viewAttachment'])->name('ar-invoice.attachment.view');

    // AP Payment
    Route::get('/ap-payment', [\App\Http\Controllers\ApPaymentController::class, 'index'])->name('ap-payment.index');
    Route::get('/ap-payment/new', [\App\Http\Controllers\ApPaymentController::class, 'create'])->name('ap-payment.create');
    Route::post('/ap-payment/store', [\App\Http\Controllers\ApPaymentController::class, 'store'])->name('ap-payment.store');
    Route::put('/ap-payment/update/{id}', [\App\Http\Controllers\ApPaymentController::class, 'update'])->name('ap-payment.update');
    Route::post('/ap-payment/process', [\App\Http\Controllers\ApPaymentController::class, 'process'])->name('ap-payment.process');
    Route::delete('/ap-payment/delete', [\App\Http\Controllers\ApPaymentController::class, 'destroy'])->name('ap-payment.delete');
    Route::get('/ap-payment/api/open-invoices', [\App\Http\Controllers\ApPaymentController::class, 'getOpenInvoices'])->name('ap-payment.api.open-invoices');
    Route::post('/ap-payment/allocate/store', [\App\Http\Controllers\ApPaymentController::class, 'storeAllocation'])->name('ap-payment.allocate.store');
    Route::put('/ap-payment/allocate/{id}', [\App\Http\Controllers\ApPaymentController::class, 'updateAllocation'])->name('ap-payment.allocate.update');
    Route::delete('/ap-payment/allocate/delete', [\App\Http\Controllers\ApPaymentController::class, 'destroyAllocation'])->name('ap-payment.allocate.delete');
    Route::post('/ap-payment/attachment/upload', [\App\Http\Controllers\ApPaymentController::class, 'uploadAttachment'])->name('ap-payment.attachment.upload');
    Route::delete('/ap-payment/attachment/delete', [\App\Http\Controllers\ApPaymentController::class, 'deleteAttachment'])->name('ap-payment.attachment.delete');
    Route::get('/ap-payment/attachment/view/{document_id}/{file_name}', [\App\Http\Controllers\ApPaymentController::class, 'viewAttachment'])->name('ap-payment.attachment.view');
    Route::get('/ap-payment/api/vendor-contacts', [\App\Http\Controllers\ApPaymentController::class, 'getVendorContacts'])->name('ap-payment.api.vendor-contacts');

    // AR Receipt
    Route::get('/ar-receipt', [\App\Http\Controllers\ArReceiptController::class, 'index'])->name('ar-receipt.index');
    Route::get('/ar-receipt/new', [\App\Http\Controllers\ArReceiptController::class, 'create'])->name('ar-receipt.create');
    Route::post('/ar-receipt/store', [\App\Http\Controllers\ArReceiptController::class, 'store'])->name('ar-receipt.store');
    Route::put('/ar-receipt/update/{id}', [\App\Http\Controllers\ArReceiptController::class, 'update'])->name('ar-receipt.update');
    Route::post('/ar-receipt/process', [\App\Http\Controllers\ArReceiptController::class, 'process'])->name('ar-receipt.process');
    Route::delete('/ar-receipt/delete', [\App\Http\Controllers\ArReceiptController::class, 'destroy'])->name('ar-receipt.delete');
    Route::get('/ar-receipt/api/open-invoices', [\App\Http\Controllers\ArReceiptController::class, 'getOpenInvoices'])->name('ar-receipt.api.open-invoices');
    Route::post('/ar-receipt/allocate/store', [\App\Http\Controllers\ArReceiptController::class, 'storeAllocation'])->name('ar-receipt.allocate.store');
    Route::put('/ar-receipt/allocate/{id}', [\App\Http\Controllers\ArReceiptController::class, 'updateAllocation'])->name('ar-receipt.allocate.update');
    Route::delete('/ar-receipt/allocate/delete', [\App\Http\Controllers\ArReceiptController::class, 'destroyAllocation'])->name('ar-receipt.allocate.delete');
    Route::post('/ar-receipt/attachment/upload', [\App\Http\Controllers\ArReceiptController::class, 'uploadAttachment'])->name('ar-receipt.attachment.upload');
    Route::delete('/ar-receipt/attachment/delete', [\App\Http\Controllers\ArReceiptController::class, 'deleteAttachment'])->name('ar-receipt.attachment.delete');
    Route::get('/ar-receipt/attachment/view/{document_id}/{file_name}', [\App\Http\Controllers\ArReceiptController::class, 'viewAttachment'])->name('ar-receipt.attachment.view');
    Route::get('/ar-receipt/api/customer-contacts', [\App\Http\Controllers\ArReceiptController::class, 'getCustomerContacts'])->name('ar-receipt.api.customer-contacts');

    // Petty Cash Request
    Route::get('/petty-cash-request', [\App\Http\Controllers\PettyCashRequestController::class, 'index'])->name('petty-cash-request.index');
    Route::get('/petty-cash-request/new', [\App\Http\Controllers\PettyCashRequestController::class, 'create'])->name('petty-cash-request.create');
    Route::post('/petty-cash-request/store', [\App\Http\Controllers\PettyCashRequestController::class, 'store'])->name('petty-cash-request.store');
    Route::put('/petty-cash-request/update/{id}', [\App\Http\Controllers\PettyCashRequestController::class, 'update'])->name('petty-cash-request.update');
    Route::post('/petty-cash-request/line/store', [\App\Http\Controllers\PettyCashRequestController::class, 'storeLine'])->name('petty-cash-request.line.store');
    Route::put('/petty-cash-request/line/update', [\App\Http\Controllers\PettyCashRequestController::class, 'storeLine'])->name('petty-cash-request.line.update');
    Route::delete('/petty-cash-request/line/delete', [\App\Http\Controllers\PettyCashRequestController::class, 'destroyLine'])->name('petty-cash-request.line.delete');
    Route::post('/petty-cash-request/line/bulk-delete', [\App\Http\Controllers\PettyCashRequestController::class, 'destroyLine'])->name('petty-cash-request.line.bulkDelete');
    Route::post('/petty-cash-request/process', [\App\Http\Controllers\PettyCashRequestController::class, 'process'])->name('petty-cash-request.process');
    Route::delete('/petty-cash-request/delete', [\App\Http\Controllers\PettyCashRequestController::class, 'destroy'])->name('petty-cash-request.delete');
    Route::post('/petty-cash-request/attachment/upload', [\App\Http\Controllers\PettyCashRequestController::class, 'uploadAttachment'])->name('petty-cash-request.attachment.upload');
    Route::delete('/petty-cash-request/attachment/delete', [\App\Http\Controllers\PettyCashRequestController::class, 'deleteAttachment'])->name('petty-cash-request.attachment.delete');
    Route::get('/petty-cash-request/attachment/view/{document_id}/{file_name}', [\App\Http\Controllers\PettyCashRequestController::class, 'viewAttachment'])->name('petty-cash-request.attachment.view');

    // Petty Cash Closing
    Route::get('/petty-cash-closing', [\App\Http\Controllers\PettyCashClosingController::class, 'index'])->name('petty-cash-closing.index');
    Route::get('/petty-cash-closing/new', [\App\Http\Controllers\PettyCashClosingController::class, 'create'])->name('petty-cash-closing.create');
    Route::post('/petty-cash-closing/store', [\App\Http\Controllers\PettyCashClosingController::class, 'store'])->name('petty-cash-closing.store');
    Route::put('/petty-cash-closing/update/{id}', [\App\Http\Controllers\PettyCashClosingController::class, 'update'])->name('petty-cash-closing.update');
    Route::post('/petty-cash-closing/line/store', [\App\Http\Controllers\PettyCashClosingController::class, 'storeLine'])->name('petty-cash-closing.line.store');
    Route::post('/petty-cash-closing/line/bulk-store', [\App\Http\Controllers\PettyCashClosingController::class, 'storeBulkRequestLines'])->name('petty-cash-closing.line.bulkStore');
    Route::put('/petty-cash-closing/line/update', [\App\Http\Controllers\PettyCashClosingController::class, 'storeLine'])->name('petty-cash-closing.line.update');
    Route::delete('/petty-cash-closing/line/delete', [\App\Http\Controllers\PettyCashClosingController::class, 'destroyLine'])->name('petty-cash-closing.line.delete');
    Route::post('/petty-cash-closing/line/bulk-delete', [\App\Http\Controllers\PettyCashClosingController::class, 'destroyLine'])->name('petty-cash-closing.line.bulkDelete');
    Route::post('/petty-cash-closing/process', [\App\Http\Controllers\PettyCashClosingController::class, 'process'])->name('petty-cash-closing.process');
    Route::delete('/petty-cash-closing/delete', [\App\Http\Controllers\PettyCashClosingController::class, 'destroy'])->name('petty-cash-closing.delete');
    Route::post('/petty-cash-closing/attachment/upload', [\App\Http\Controllers\PettyCashClosingController::class, 'uploadAttachment'])->name('petty-cash-closing.attachment.upload');
    Route::delete('/petty-cash-closing/attachment/delete', [\App\Http\Controllers\PettyCashClosingController::class, 'deleteAttachment'])->name('petty-cash-closing.attachment.delete');
    Route::get('/petty-cash-closing/attachment/view/{document_id}/{file_name}', [\App\Http\Controllers\PettyCashClosingController::class, 'viewAttachment'])->name('petty-cash-closing.attachment.view');
    Route::get('/petty-cash-closing/api/request-lines', [\App\Http\Controllers\PettyCashClosingController::class, 'getRequestLines'])->name('petty-cash-closing.api.request-lines');
    Route::get('/petty-cash-closing/api/request-info/{id}', [\App\Http\Controllers\PettyCashClosingController::class, 'getRequestInfo'])->name('petty-cash-closing.api.request-info');

    // Aging AP Invoice Report
    Route::get('/aging-ap-invoice-report', [\App\Http\Controllers\AgingApInvoiceController::class, 'index'])->name('aging-ap-invoice.index');
    Route::get('/aging-ap-invoice-report/suppliers', [\App\Http\Controllers\AgingApInvoiceController::class, 'getSuppliers'])->name('aging-ap-invoice.suppliers');

    // Aging AR Invoice
    Route::get('/aging-ar-invoice-report', [\App\Http\Controllers\AgingArInvoiceController::class, 'index'])->name('aging-ar-invoice.index');
    Route::get('/aging-ar-invoice-report/customers', [\App\Http\Controllers\AgingArInvoiceController::class, 'getCustomers'])->name('aging-ar-invoice.customers');

    // Procurement Report
    Route::get('/procurement-report', [\App\Http\Controllers\ProcurementReportController::class, 'index'])->name('procurement-report.index');
    Route::get('/procurement-report/data', [\App\Http\Controllers\ProcurementReportController::class, 'getData'])->name('procurement-report.data');
    Route::get('/procurement-report/export', [\App\Http\Controllers\ProcurementReportController::class, 'export'])->name('procurement-report.export');

    // Purchase Order Report
    Route::get('/purchase-order-report', [\App\Http\Controllers\PurchaseOrderReportController::class, 'index'])->name('po-report.index');
    Route::get('/purchase-order-report/data', [\App\Http\Controllers\PurchaseOrderReportController::class, 'getData'])->name('po-report.data');
    Route::get('/purchase-order-report/export', [\App\Http\Controllers\PurchaseOrderReportController::class, 'export'])->name('po-report.export');


    // calender pages
    Route::get('/calendar', function () {
        return view('pages.calender', ['title' => 'Calendar']);
    })->name('calendar');

    // profile pages
    Route::get('/profile', function () {
        return view('pages.profile', ['title' => 'Profile']);
    })->name('profile');

    // form pages
    Route::get('/form-elements', function () {
        return view('pages.form.form-elements', ['title' => 'Form Elements']);
    })->name('form-elements');

    // tables pages
    Route::get('/basic-tables', function () {
        return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
    })->name('basic-tables');

    // pages
    Route::get('/blank', function () {
        return view('pages.blank', ['title' => 'Blank']);
    })->name('blank');

    // error pages
    Route::get('/error-404', function () {
        return view('pages.errors.error-404', ['title' => 'Error 404']);
    })->name('error-404');

    // chart pages
    Route::get('/line-chart', function () {
        return view('pages.chart.line-chart', ['title' => 'Line Chart']);
    })->name('line-chart');

    Route::get('/bar-chart', function () {
        return view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
    })->name('bar-chart');

    // ui elements pages
    Route::get('/alerts', function () {
        return view('pages.ui-elements.alerts', ['title' => 'Alerts']);
    })->name('alerts');

    Route::get('/avatars', function () {
        return view('pages.ui-elements.avatars', ['title' => 'Avatars']);
    })->name('avatars');

    Route::get('/badge', function () {
        return view('pages.ui-elements.badges', ['title' => 'Badges']);
    })->name('badges');

    Route::get('/buttons', function () {
        return view('pages.ui-elements.buttons', ['title' => 'Buttons']);
    })->name('buttons');

    Route::get('/image', function () {
        return view('pages.ui-elements.images', ['title' => 'Images']);
    })->name('images');

    Route::get('/videos', function () {
        return view('pages.ui-elements.videos', ['title' => 'Videos']);
    })->name('videos');

    // iDempiere Image Display
    Route::get('/idempiere/image/{imageId}', [\App\Http\Controllers\IdempiereImageController::class, 'show'])->name('idempiere.image.show');
});
