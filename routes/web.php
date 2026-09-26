<?php

use App\Http\Controllers\Web\AdminAuditWebController;
use App\Http\Controllers\Web\AdminUserWebController;
use App\Http\Controllers\Web\AllocationWebController;
use App\Http\Controllers\Web\AttendanceWebController;
use App\Http\Controllers\Web\BoardWebController;
use App\Http\Controllers\Web\BomWebController;
use App\Http\Controllers\Web\CommercialReportWebController;
use App\Http\Controllers\Web\CommercialTaskWebController;
use App\Http\Controllers\Web\CustomerWebController;
use App\Http\Controllers\Web\DealWebController;
use App\Http\Controllers\Web\DeliveryWebController;
use App\Http\Controllers\Web\DesignWebController;
use App\Http\Controllers\Web\EmployeeWebController;
use App\Http\Controllers\Web\FundingWebController;
use App\Http\Controllers\Web\InventoryItemWebController;
use App\Http\Controllers\Web\InventoryMovementWebController;
use App\Http\Controllers\Web\InventoryStockWebController;
use App\Http\Controllers\Web\InvoiceWebController;
use App\Http\Controllers\Web\LeadWebController;
use App\Http\Controllers\Web\LeaveWebController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\MachineryWebController;
use App\Http\Controllers\Web\MaterialRequestWebController;
use App\Http\Controllers\Web\OperationsWebController;
use App\Http\Controllers\Web\OrgChartWebController;
use App\Http\Controllers\Web\OutboundWebController;
use App\Http\Controllers\Web\PaymentWebController;
use App\Http\Controllers\Web\PayrollWebController;
use App\Http\Controllers\Web\ProcurementWebController;
use App\Http\Controllers\Web\ProductionOrderWebController;
use App\Http\Controllers\Web\ProductWebController;
use App\Http\Controllers\Web\ProfileWebController;
use App\Http\Controllers\Web\ProjectWebController;
use App\Http\Controllers\Web\ReportWebController;
use App\Http\Controllers\Web\SalesDashboardWebController;
use App\Http\Controllers\Web\SalesQuotaWebController;
use App\Http\Controllers\Web\TaskWebController;
use App\Http\Middleware\EnsureWebInvoiceAccess;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return redirect()->route(auth()->user()->preferredHomeRouteName());
});

Route::get('/workspace', function () {
    return redirect()->route(auth()->user()->preferredHomeRouteName());
})->middleware('auth')->name('workspace');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/logout/idle', [LoginController::class, 'idleLogout'])
    ->middleware('auth')
    ->name('logout.idle');

Route::middleware(['auth', EnsureWebInvoiceAccess::class])->group(function () {
    Route::get('/profile', [ProfileWebController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileWebController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileWebController::class, 'updatePassword'])->name('profile.password');

    Route::get('/orders', [InvoiceWebController::class, 'index'])->name('invoices.index');
    Route::get('/orders/create', [InvoiceWebController::class, 'create'])->name('invoices.create');
    Route::get('/orders/new', [InvoiceWebController::class, 'create'])->name('invoices.new');
    Route::post('/orders', [InvoiceWebController::class, 'store'])->name('invoices.store');
    Route::post('/orders/autosave', [InvoiceWebController::class, 'autosave'])->name('invoices.autosave');
    Route::post('/orders/compose-pdf', [InvoiceWebController::class, 'composePdf'])->name('invoices.compose-pdf');
    Route::get('/orders/{invoice}', [InvoiceWebController::class, 'show'])->name('invoices.show');
    Route::get('/orders/{invoice}/edit', [InvoiceWebController::class, 'edit'])->name('invoices.edit');
    Route::put('/orders/{invoice}', [InvoiceWebController::class, 'update'])->name('invoices.update');
    Route::post('/orders/{invoice}/autosave', [InvoiceWebController::class, 'autosave'])->name('invoices.autosave.existing');
    Route::delete('/orders/{invoice}', [InvoiceWebController::class, 'destroy'])->name('invoices.destroy');
    Route::patch('/orders/{invoice}/status', [InvoiceWebController::class, 'updateStatus'])->name('invoices.status');
    Route::post('/orders/{invoice}/approve', [InvoiceWebController::class, 'approve'])->name('invoices.approve');
    Route::get('/orders/{invoice}/document', [InvoiceWebController::class, 'document'])->name('invoices.document');

    Route::get('/finance/payments', [PaymentWebController::class, 'index'])->name('payments.index');
    Route::post('/finance/payments', [PaymentWebController::class, 'store'])->name('payments.store');

    Route::get('/finance/allocations', [AllocationWebController::class, 'index'])->name('finance.allocations');

    Route::get('/funding', [FundingWebController::class, 'index'])->name('funding.index');
    Route::post('/funding', [FundingWebController::class, 'store'])->name('funding.store');
    Route::patch('/funding/{funding}/status', [FundingWebController::class, 'updateStatus'])->name('funding.status');

    Route::get('/inventory/items', [InventoryItemWebController::class, 'index'])->name('inventory.items');
    Route::post('/inventory/items', [InventoryItemWebController::class, 'store'])->name('inventory.items.store');
    Route::get('/inventory/stock', [InventoryStockWebController::class, 'index'])->name('inventory.stock');
    Route::get('/inventory/low-stock', [InventoryStockWebController::class, 'lowStock'])->name('inventory.low-stock');
    Route::get('/inventory/movements', [InventoryMovementWebController::class, 'index'])->name('inventory.movements');
    Route::post('/inventory/movements', [InventoryMovementWebController::class, 'store'])->name('inventory.movements.store');
    Route::get('/inventory/outbound', [OutboundWebController::class, 'index'])->name('inventory.outbound');
    Route::get('/inventory/material-requests', fn () => redirect()->route('material-requests.index'))->name('inventory.material-requests');

    Route::get('/material-requests', [MaterialRequestWebController::class, 'index'])->name('material-requests.index');
    Route::post('/material-requests', [MaterialRequestWebController::class, 'store'])->name('material-requests.store');
    Route::patch('/material-requests/{materialRequest}', [MaterialRequestWebController::class, 'update'])->name('material-requests.update');

    Route::get('/production', [ProductionOrderWebController::class, 'index'])->name('production.index');
    Route::post('/production', [ProductionOrderWebController::class, 'store'])->name('production.store');
    Route::patch('/production/{order}/status', [ProductionOrderWebController::class, 'updateStatus'])->name('production.status');
    Route::get('/manufacturing/production-orders', fn () => redirect()->route('production.index'))->name('manufacturing.production-orders');

    Route::get('/manufacturing/boms', [BomWebController::class, 'index'])->name('manufacturing.boms');
    Route::post('/manufacturing/boms', [BomWebController::class, 'store'])->name('manufacturing.boms.store');
    Route::patch('/manufacturing/boms/{bom}', [BomWebController::class, 'update'])->name('manufacturing.boms.update');
    Route::delete('/manufacturing/boms/{bom}', [BomWebController::class, 'destroy'])->name('manufacturing.boms.destroy');
    Route::post('/manufacturing/boms/{bom}/lines', [BomWebController::class, 'storeLine'])->name('manufacturing.boms.lines.store');
    Route::patch('/manufacturing/boms/{bom}/lines/{line}', [BomWebController::class, 'updateLine'])->name('manufacturing.boms.lines.update');
    Route::delete('/manufacturing/boms/{bom}/lines/{line}', [BomWebController::class, 'destroyLine'])->name('manufacturing.boms.lines.destroy');

    Route::get('/production/deliveries', [DeliveryWebController::class, 'index'])->name('production.deliveries');
    Route::post('/production/deliveries', [DeliveryWebController::class, 'store'])->name('production.deliveries.store');
    Route::patch('/production/deliveries/{delivery}/status', [DeliveryWebController::class, 'updateStatus'])->name('production.deliveries.status');

    Route::get('/designs', [DesignWebController::class, 'index'])->name('designs.index');
    Route::post('/designs', [DesignWebController::class, 'store'])->name('designs.store');
    Route::patch('/designs/{id}', [DesignWebController::class, 'update'])->name('designs.update')->whereNumber('id');

    Route::get('/machinery', [MachineryWebController::class, 'index'])->name('machinery.index');
    Route::post('/machinery', [MachineryWebController::class, 'store'])->name('machinery.store');
    Route::patch('/machinery/{machinery}/status', [MachineryWebController::class, 'updateStatus'])->name('machinery.status');

    Route::get('/procurement', [ProcurementWebController::class, 'index'])->name('procurement.index');
    Route::post('/procurement/research', [ProcurementWebController::class, 'storeResearch'])->name('procurement.research.store');
    Route::patch('/procurement/research/{id}', [ProcurementWebController::class, 'updateResearch'])->name('procurement.research.update')->whereNumber('id');
    Route::post('/procurement/laborers', [ProcurementWebController::class, 'storeLaborer'])->name('procurement.laborers.store');
    Route::patch('/procurement/laborers/{id}', [ProcurementWebController::class, 'updateLaborer'])->name('procurement.laborers.update')->whereNumber('id');
    Route::post('/procurement/installations', [ProcurementWebController::class, 'storeInstallation'])->name('procurement.installations.store');
    Route::patch('/procurement/installations/{id}', [ProcurementWebController::class, 'updateInstallation'])->name('procurement.installations.update')->whereNumber('id');

    Route::get('/projects', [ProjectWebController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectWebController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectWebController::class, 'show'])->name('projects.show');
    Route::post('/projects/{project}/tasks', [ProjectWebController::class, 'storeTask'])->name('projects.tasks.store');
    Route::patch('/projects/{project}/tasks/{task}', [ProjectWebController::class, 'updateTask'])->name('projects.tasks.update');

    Route::get('/tasks', [TaskWebController::class, 'index'])->name('tasks.index');
    Route::patch('/tasks/{task}', [TaskWebController::class, 'update'])->name('tasks.update');

    Route::get('/reports', [ReportWebController::class, 'index'])->name('reports.index');
    Route::post('/reports', [ReportWebController::class, 'store'])->name('reports.store');
    Route::get('/reports/library', [ReportWebController::class, 'library'])->name('reports.library');

    Route::get('/boards', [BoardWebController::class, 'index'])->name('boards.index');
    Route::post('/boards/workspaces', [BoardWebController::class, 'storeWorkspace'])->name('boards.workspaces.store');
    Route::post('/boards', [BoardWebController::class, 'storeBoard'])->name('boards.store');
    Route::get('/boards/{board}', [BoardWebController::class, 'show'])->name('boards.show');
    Route::post('/boards/{board}/items', [BoardWebController::class, 'storeItem'])->name('boards.items.store');
    Route::patch('/boards/items/{item}/values', [BoardWebController::class, 'updateItemValue'])->name('boards.items.values');

    Route::get('/sales/customers', [CustomerWebController::class, 'index'])->name('sales.customers');
    Route::post('/sales/customers', [CustomerWebController::class, 'store'])->name('sales.customers.store');
    Route::patch('/sales/customers/{party}/approve', [CustomerWebController::class, 'approve'])->name('sales.customers.approve');

    Route::get('/sales/dashboard', [SalesDashboardWebController::class, 'index'])->name('sales.dashboard');
    Route::get('/sales/dashboard/reps/{user}', [SalesDashboardWebController::class, 'repReport'])->name('sales.dashboard.rep')->whereNumber('user');

    Route::get('/sales/quota', [SalesQuotaWebController::class, 'show'])->name('sales.quota');
    Route::get('/sales/quota/manage', [SalesQuotaWebController::class, 'manage'])->name('sales.quota.manage');
    Route::post('/sales/quota', [SalesQuotaWebController::class, 'store'])->name('sales.quota.store');

    Route::get('/commercial/reports', [CommercialReportWebController::class, 'index'])->name('commercial.reports.index');
    Route::post('/commercial/reports/generate/{cadence}', [CommercialReportWebController::class, 'generate'])->name('commercial.reports.generate');
    Route::get('/commercial/tasks', [CommercialTaskWebController::class, 'index'])->name('commercial.tasks.index');
    Route::post('/commercial/tasks', [CommercialTaskWebController::class, 'store'])->name('commercial.tasks.store');
    Route::patch('/commercial/tasks/{task}', [CommercialTaskWebController::class, 'update'])->name('commercial.tasks.update');

    Route::get('/products', [ProductWebController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductWebController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductWebController::class, 'update'])->name('products.update');

    Route::get('/leads', [LeadWebController::class, 'index'])->name('leads.index');
    Route::post('/leads', [LeadWebController::class, 'store'])->name('leads.store');
    Route::patch('/leads/{id}', [LeadWebController::class, 'update'])->name('leads.update')->whereNumber('id');
    Route::post('/leads/{id}/convert', [LeadWebController::class, 'convert'])->name('leads.convert')->whereNumber('id');

    Route::get('/deals', [DealWebController::class, 'index'])->name('deals.index');
    Route::post('/deals', [DealWebController::class, 'store'])->name('deals.store');
    Route::patch('/deals/{id}', [DealWebController::class, 'update'])->name('deals.update')->whereNumber('id');
    Route::post('/deals/{id}/sales-review', [DealWebController::class, 'salesReview'])->name('deals.sales-review')->whereNumber('id');
    Route::post('/deals/{id}/manager-review', [DealWebController::class, 'managerReview'])->name('deals.manager-review')->whereNumber('id');

    Route::get('/hr/employees', [EmployeeWebController::class, 'index'])->name('hr.employees');
    Route::post('/hr/employees', [EmployeeWebController::class, 'store'])->name('hr.employees.store');
    Route::get('/hr/employees/{id}', [EmployeeWebController::class, 'show'])->name('hr.employees.show')->whereNumber('id');
    Route::get('/hr/attendance', [AttendanceWebController::class, 'index'])->name('hr.attendance');
    Route::post('/hr/attendance', [AttendanceWebController::class, 'mark'])->name('hr.attendance.mark');
    Route::post('/hr/attendance/holiday-all', [AttendanceWebController::class, 'holidayAll'])->name('hr.attendance.holiday');
    Route::post('/hr/attendance/compile', [AttendanceWebController::class, 'compile'])->name('hr.attendance.compile');
    Route::patch('/hr/attendance/submissions/{id}', [AttendanceWebController::class, 'review'])->name('hr.attendance.review')->whereNumber('id');
    Route::get('/hr/org-chart', [OrgChartWebController::class, 'show'])->name('hr.org-chart');
    Route::get('/leave', [LeaveWebController::class, 'index'])->name('leave.index');
    Route::post('/leave', [LeaveWebController::class, 'store'])->name('leave.store');
    Route::patch('/leave/{id}', [LeaveWebController::class, 'update'])->name('leave.update')->whereNumber('id');

    Route::get('/finance/payroll', [PayrollWebController::class, 'index'])->name('finance.payroll');
    Route::post('/finance/payroll', [PayrollWebController::class, 'generate'])->name('finance.payroll.generate');
    Route::get('/finance/payroll/{id}', [PayrollWebController::class, 'show'])->name('finance.payroll.show')->whereNumber('id');
    Route::get('/finance/payroll/{id}/csv', [PayrollWebController::class, 'csv'])->name('finance.payroll.csv')->whereNumber('id');
    Route::patch('/finance/payroll/{id}/status', [PayrollWebController::class, 'updateStatus'])->name('finance.payroll.status')->whereNumber('id');
    Route::patch('/finance/payroll/{id}/lines/{lineId}', [PayrollWebController::class, 'updateLine'])->name('finance.payroll.line')->whereNumber(['id', 'lineId']);

    Route::get('/admin/users', [AdminUserWebController::class, 'index'])->name('admin.users');
    Route::post('/admin/users', [AdminUserWebController::class, 'store'])->name('admin.users.store');
    Route::patch('/admin/users/{user}', [AdminUserWebController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [AdminUserWebController::class, 'destroy'])->name('admin.users.destroy');
    Route::get('/admin/audit-log', [AdminAuditWebController::class, 'index'])->name('admin.audit');

    Route::prefix('operations')->name('operations.')->group(function () {
        Route::get('/oms', [OperationsWebController::class, 'omsDashboard'])->name('oms.dashboard');
        Route::get('/oms/check-invoice', [OperationsWebController::class, 'omsCheckInvoice'])->name('oms.check-invoice');
        Route::get('/oms/schedule', [OperationsWebController::class, 'omsSchedule'])->name('oms.schedule');
        Route::get('/oms/pipeline', [OperationsWebController::class, 'omsPipeline'])->name('oms.pipeline');
        Route::get('/oms/pipeline.json', [OperationsWebController::class, 'omsPipelineJson'])->name('oms.pipeline.json');
        Route::get('/omf', [OperationsWebController::class, 'omfDashboard'])->name('omf.dashboard');
        Route::get('/manager', [OperationsWebController::class, 'managerDashboard'])->name('manager.dashboard');
        Route::get('/designer', [OperationsWebController::class, 'designerDashboard'])->name('designer.dashboard');
        Route::get('/product-manager', [OperationsWebController::class, 'productManagerDashboard'])->name('product-manager.dashboard');
        Route::get('/procurement', [OperationsWebController::class, 'procurementDashboard'])->name('procurement.dashboard');
        Route::get('/orders/{intake}', [OperationsWebController::class, 'show'])->name('orders.show');
        Route::get('/orders/{intake}/invoice-document', [OperationsWebController::class, 'invoiceDocument'])->name('orders.invoice-document');

        Route::post('/orders/{intake}/start-review', [OperationsWebController::class, 'startReview'])->name('orders.start-review');
        Route::post('/orders/{intake}/accept', [OperationsWebController::class, 'accept'])->name('orders.accept');
        Route::post('/orders/{intake}/send-to-cm', [OperationsWebController::class, 'sendToCompanyManager'])->name('orders.send-to-cm');
        Route::post('/orders/{intake}/cm-approve', [OperationsWebController::class, 'cmApprove'])->name('orders.cm-approve');
        Route::post('/orders/{intake}/cm-reject', [OperationsWebController::class, 'cmReject'])->name('orders.cm-reject');
        Route::post('/orders/{intake}/reject', [OperationsWebController::class, 'reject'])->name('orders.reject');
        Route::post('/orders/{intake}/resubmit', [OperationsWebController::class, 'resubmit'])->name('orders.resubmit');

        Route::patch('/orders/{intake}/phases/{phase}/deadline', [OperationsWebController::class, 'updateDeadline'])->name('orders.deadlines.update');
        Route::patch('/orders/{intake}/phases/{phase}/label', [OperationsWebController::class, 'updatePhaseLabel'])->name('orders.phases.label');
        Route::post('/orders/{intake}/phases', [OperationsWebController::class, 'addPhase'])->name('orders.phases.add');
        Route::post('/orders/{intake}/phases/{phase}/complete', [OperationsWebController::class, 'completePhase'])->name('orders.phases.complete');
        Route::post('/orders/{intake}/assign-designer', [OperationsWebController::class, 'assignDesigner'])->name('orders.assign-designer');
        Route::post('/orders/{intake}/assign-product-manager', [OperationsWebController::class, 'assignProductManager'])->name('orders.assign-product-manager');
        Route::post('/orders/{intake}/assign-assembler', [OperationsWebController::class, 'assignAssembler'])->name('orders.assign-assembler');
        Route::patch('/orders/{intake}/checkpoints/{checkpoint}', [OperationsWebController::class, 'toggleCheckpoint'])->name('orders.checkpoints.toggle');

        Route::post('/orders/{intake}/materials', [OperationsWebController::class, 'submitMaterials'])->name('orders.materials.submit');
        Route::post('/orders/{intake}/materials/{line}/verify', [OperationsWebController::class, 'verifyStock'])->name('orders.materials.verify');
        Route::post('/orders/{intake}/materials/{line}/procure', [OperationsWebController::class, 'procureMaterial'])->name('orders.materials.procure');
        Route::post('/orders/{intake}/materials/release', [OperationsWebController::class, 'releaseMaterials'])->name('orders.materials.release');

        Route::post('/orders/{intake}/procurement/{procurement}/quotes', [OperationsWebController::class, 'addQuote'])->name('orders.quotes.add');
        Route::post('/orders/{intake}/procurement/{procurement}/quotes/{quote}/recommend', [OperationsWebController::class, 'recommendQuote'])->name('orders.quotes.recommend');
        Route::post('/orders/{intake}/procurement/{procurement}/decide', [OperationsWebController::class, 'decideProcurement'])->name('orders.procurement.decide');

        Route::post('/orders/{intake}/assembly/complete', [OperationsWebController::class, 'completeAssembly'])->name('orders.assembly.complete');
        Route::post('/orders/{intake}/delivery/complete', [OperationsWebController::class, 'completeDelivery'])->name('orders.delivery.complete');
        Route::post('/orders/{intake}/delivery/schedule', [OperationsWebController::class, 'scheduleDelivery'])->name('orders.delivery.schedule');
        Route::post('/orders/{intake}/messages', [OperationsWebController::class, 'sendMessage'])->name('orders.messages.send');
    });
});
