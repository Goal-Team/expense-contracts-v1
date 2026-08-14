<?php

use Illuminate\Support\Facades\Route;
use Modules\Contractsetup\Http\Controllers\ContractsetupController;
use Modules\Contractsetup\Http\Controllers\HealthCheckMasterController;
use Modules\Contractsetup\Http\Controllers\MenuConfigController;
use Modules\Contractsetup\Http\Controllers\LocationWebController;
use Modules\Contractsetup\Http\Controllers\ConsultationWebController;
use Modules\Contractsetup\Http\Controllers\AnnexureMasterController;
use Modules\Contractsetup\Http\Controllers\TestWebController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::group([], function () {
//     Route::resource('contractsetup', ContractsetupController::class)->names('contractsetup');
// });

Route::group(['middleware'=>['contractauth'],'prefix'=>'contract-setup','as'=>'contract-setup.'], function() {  
    
     Route::get('/file-config', [ContractsetupController::class, 'fileConfig'])->name('fileConfig');
    Route::post('/file-config-update', [ContractsetupController::class, 'fileConfigStore'])->name('fileConfigStore');

    Route::get('/contract-custom-fields', [ContractsetupController::class, 'index'])->name('index');
    Route::post('list', [ContractsetupController::class, 'list'])->name('list');
    Route::post('contract', [ContractsetupController::class, 'store'])->name('store');
    Route::post('update', [ContractsetupController::class, 'update'])->name('update');

    Route::get('party', [ContractsetupController::class, 'indexParty'])->name('indexParty');
    Route::get('party-ind', [ContractsetupController::class, 'indexPartyIndividual'])->name('indexPartyIndividual');
    Route::post('party/list', [ContractsetupController::class, 'indexPartyList'])->name('indexPartyList');
    Route::post('party/list-ind', [ContractsetupController::class, 'indexPartyIndividualList'])->name('indexPartyIndividualList');

    Route::post('contract/create/parties', [ContractsetupController::class, 'contractCreateparties'])->name('contractCreateparties');
    Route::post('/store/contract',[ContractsetupController::class,'storeContract'])->name('storeContract');
    
    Route::get('/contract-type', [ContractsetupController::class, 'contractTypeCreate'])->name('contractTypeCreate');
    
    Route::get('/contract-import', [ContractsetupController::class, 'contractImport'])->name('contractImport');
    Route::post('/contract-type-import-store', [ContractsetupController::class, 'contractImportStore'])->name('contractImportStore');

    Route::post('/contract-type-store', [ContractsetupController::class, 'contractTypeStore'])->name('contractTypeStore');
    
    Route::get('/contract-type/list', [ContractsetupController::class, 'contractTypeList'])->name('contractTypeList');
    
    Route::get('/contract-type/listdata',[ContractsetupController::class, 'contractTypeListData'])->name('contractTypeListData');
    
    Route::get('/contract-type-edit/{id}', [ContractsetupController::class, 'contractTypeEdit'])->name('contractTypeEdit');
    
    Route::get('/contract-type-delete/{id}', [ContractsetupController::class, 'contractTypeDelete'])->name('contractTypeDelete');
    
    //Clause Config
    Route::get('clauseconfig', [ContractsetupController::class, 'clauseConfigSetup'])->name('clauseSetup');
    Route::get('clause/titles', [ContractsetupController::class, 'clauseConfigTitleList'])->name('clauseTitleList');
    Route::get('clause/template-add', [ContractsetupController::class, 'clauseConfigTemplateAdd'])->name('clauseTemplateAdd');
    Route::post('clause/template-store', [ContractsetupController::class, 'clauseConfigTemplateStore'])->name('clauseTemplateStore');
    Route::post('clause/list', [ContractsetupController::class, 'clauseConfigList'])->name('clausesList');
    Route::post('clause/add', [ContractsetupController::class, 'clauseConfigStore'])->name('clauseStore');
    Route::post('clause/title-add', [ContractsetupController::class, 'clauseConfigTitleStore'])->name('clauseTitleStore');
    Route::post('clause/modify', [ContractsetupController::class, 'clauseConfigUpdate'])->name('clauseModify');
    Route::post('clause/list/template/{type}', [ContractsetupController::class, 'clauseConfigBaseTemplate'])->name('templateClauses');
    Route::post('category/title-add', [ContractsetupController::class, 'categoryConfigTitleStore'])->name('categoryTitleStore');
    Route::get('customvarconfig', [ContractsetupController::class, 'customVariableConfigSetup'])->name('customVariableSetup');
    Route::get('customvarlist', [ContractsetupController::class, 'customVariableListObject'])->name('customVariableList');
    Route::post('vars/custom-var-add', [ContractsetupController::class, 'customVariableStore'])->name('customVariableStore');
    Route::post('vars/custom-var-edit', [ContractsetupController::class, 'customVariableEdit'])->name('customVariableEdit');
    Route::post('vars/custom-var-update', [ContractsetupController::class, 'customVariableUpdate'])->name('customVariableUpdate');
    Route::get('/custom-fields', [ContractsetupController::class, 'customFieldsGet']);
    // Health Check Master Routes
    Route::prefix('health-checks')->name('health-checks.')->group(function () {
        Route::get('/', [HealthCheckMasterController::class, 'index'])->name('index');
        Route::get('/create', [HealthCheckMasterController::class, 'create'])->name('create');
        Route::post('/', [HealthCheckMasterController::class, 'store'])->name('store');
        Route::get('/{id}', [HealthCheckMasterController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [HealthCheckMasterController::class, 'edit'])->name('edit');
        Route::put('/{id}', [HealthCheckMasterController::class, 'update'])->name('update');
        Route::delete('/{id}', [HealthCheckMasterController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [HealthCheckMasterController::class, 'bulkDestroy'])->name('bulk-destroy');
    });
    // Admin Menu Configs
    Route::get('/admin/menu-configs', [MenuConfigController::class, 'index'])->name('admin.menu-configs.index');
    Route::post('/admin/menu-configs', [MenuConfigController::class, 'store'])->name('admin.menu-configs.store');
    Route::get('/admin/menu-configs/{id}/edit', [MenuConfigController::class, 'edit'])->name('admin.menu-configs.edit');
    Route::put('/admin/menu-configs/{id}', [MenuConfigController::class, 'update'])->name('admin.menu-configs.update');
    Route::delete('/admin/menu-configs/{id}', [MenuConfigController::class, 'destroy'])->name('admin.menu-configs.destroy');
    Route::post('/admin/menu-configs/{id}/toggle', [MenuConfigController::class, 'toggleActive'])->name('admin.menu-configs.toggle');
    
    // Location Master CRUD (web views)
    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/', [LocationWebController::class, 'index'])->name('index');
        Route::get('/create', [LocationWebController::class, 'create'])->name('create');
        Route::post('/', [LocationWebController::class, 'store'])->name('store');
        Route::get('/{id}', [LocationWebController::class, 'edit'])->name('show');
        Route::get('/{id}/edit', [LocationWebController::class, 'edit'])->name('edit');
        Route::put('/{id}', [LocationWebController::class, 'update'])->name('update');
        Route::delete('/{id}', [LocationWebController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [LocationWebController::class, 'bulkDestroy'])->name('bulk-destroy');
    });

    // Consultation Master CRUD (web views)
    Route::prefix('consultations')->name('consultations.')->group(function () {
        Route::get('/', [ConsultationWebController::class, 'index'])->name('index');
        Route::get('/create', [ConsultationWebController::class, 'create'])->name('create');
        Route::post('/', [ConsultationWebController::class, 'store'])->name('store');
        Route::get('/{id}', [ConsultationWebController::class, 'edit'])->name('show');
        Route::get('/{id}/edit', [ConsultationWebController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ConsultationWebController::class, 'update'])->name('update');
        Route::delete('/{id}', [ConsultationWebController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [ConsultationWebController::class, 'bulkDestroy'])->name('bulk-destroy');
    });

    // Annexure Master CRUD (web views)
    Route::prefix('annexures')->name('annexures.')->group(function () {
        Route::get('/', [AnnexureMasterController::class, 'index'])->name('index');
        Route::get('/create', [AnnexureMasterController::class, 'create'])->name('create');
        Route::post('/', [AnnexureMasterController::class, 'store'])->name('store');
        Route::get('/list', [AnnexureMasterController::class, 'getList'])->name('getList');
        Route::get('/{id}/sample', [AnnexureMasterController::class, 'downloadSample'])->name('sample');
        Route::get('/{id}/edit', [AnnexureMasterController::class, 'edit'])->name('edit');
        Route::get('/{id}', [AnnexureMasterController::class, 'edit'])->name('show');
        Route::put('/{id}', [AnnexureMasterController::class, 'update'])->name('update');
        Route::delete('/{id}', [AnnexureMasterController::class, 'destroy'])->name('destroy');
    });

    // Test Master CRUD (web views)
    Route::prefix('tests')->name('tests.')->group(function () {
        Route::get('/', [TestWebController::class, 'index'])->name('index');
        Route::get('/create', [TestWebController::class, 'create'])->name('create');
        Route::post('/', [TestWebController::class, 'store'])->name('store');
        Route::get('/{id}', [TestWebController::class, 'edit'])->name('show');
        Route::get('/{id}/edit', [TestWebController::class, 'edit'])->name('edit');
        Route::put('/{id}', [TestWebController::class, 'update'])->name('update');
        Route::delete('/{id}', [TestWebController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-delete', [TestWebController::class, 'bulkDestroy'])->name('bulk-destroy');
    });    
});

Route::get('/health-checks-api/get-list', [HealthCheckMasterController::class, 'getHealthChecks'])->name('list-api-healthchecks');

// API: Get list of consultations for JS/dropdowns
Route::get('/consultations-api/get-list', [ConsultationWebController::class, 'getList'])->name('list-api-consultations');

// API: Get list of tests for JS/dropdowns
Route::get('/tests-api/get-list', [TestWebController::class, 'getList'])->name('list-api-tests');