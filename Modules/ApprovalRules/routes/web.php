<?php

use Illuminate\Support\Facades\Route;
use Modules\ApprovalRules\Http\Controllers\ApprovalRulesController;

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
//     Route::resource('approvalrules', ApprovalRulesController::class)->names('approvalrules');
// });

Route::group(['middleware'=>['xssprotect', 'contractauth'],'prefix'=>'contract-setup','as'=>'contract-setup.'], function() {  

    Route::post('/getState',[ApprovalRulesController::class,'getState'])->name('getState');

    Route::get('/approval-rules', [ApprovalRulesController::class,'financial'])->name('financial');

    Route::get('/financial_old', [ApprovalRulesController::class,'financial_old'])->name('financial_old');

    Route::get('/financial', function () {
          return view('contract-setup.financial.index');
    })->name('financial');
    
    Route::get('/financial/data', [ApprovalRulesController::class,'financial_data'])->name('financial_data');
    
    Route::any('/financial-add', [ApprovalRulesController::class,'financial_add'])->name('financial-add');
    
    Route::post('/check_limit',[ApprovalRulesController::class,'check_limit'])->name('check_limit');
    
    Route::get('/financial-delete/{id}', [ApprovalRulesController::class,'financial_delete'])->name('financial-delete');
    
    Route::any('/financial-edit/{id}', [ApprovalRulesController::class,'financial_edit'])->name('financial-edit');
    
    Route::get('/party-approval-rules', function () { return view('contract-setup::party.index');})->name('party-approval-rules');
    
    Route::any('/party-approval-add', [ApprovalRulesController::class,'party_approval_add'])->name('party-approval-add');
    
    Route::get('/party-approval/data', [ApprovalRulesController::class,'party_approval_data'])->name('party_approval_data');
    
    Route::any('/party-approval-edit/{id}', [ApprovalRulesController::class,'party_approval_edit'])->name('party-approval-edit');
    
    Route::get('/getUsers',[ApprovalRulesController::class,'getUsers'])->name('getUsers');
    
    Route::post('/getApprovers',[ApprovalRulesController::class,'getApprovers'])->name('getApprovers');
    
    Route::get('autocomplete',[ApprovalRulesController::class,'autocomplete'])->name('autocomplete');
    
    Route::get('/financial-add-users/{index}',[ApprovalRulesController::class,'financial_add_users']);
    
    Route::get('/party-approval-add-users/{index}',[ApprovalRulesController::class,'financial_party_add_users']);
    

    // API endpoints to fetch master lists for the rule builder
    Route::get('/api/branches', [ApprovalRulesController::class, 'apiBranches'])->name('api.branches');
    Route::get('/api/departments', [ApprovalRulesController::class, 'apiDepartments'])->name('api.departments');
    Route::get('/api/categories', [ApprovalRulesController::class, 'apiCategories'])->name('api.categories');
    Route::get('/api/contract-types', [ApprovalRulesController::class, 'apiContractTypes'])->name('api.contract-types');
    Route::get('/api/locations', [ApprovalRulesController::class, 'apiLocations'])->name('api.locations');
    Route::get('/api/external-representatives', [ApprovalRulesController::class, 'apiExternalRepresentatives'])->name('api.external-representatives');
});
