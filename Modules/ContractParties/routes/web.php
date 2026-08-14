<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\ContractParties\Http\Controllers\ContractPartiesController;

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


Route::group(['middleware'=>['xssprotect', 'contractauth'],'prefix'=>'parties','as'=>'parties.'], function() { 
    
    Route::get('/', [ContractPartiesController::class,'dashboard'])->name('parties');

    Route::get('/individual', [ContractPartiesController::class,'dashboard_individual'])->name('parties-individual');
    
    Route::get('/data', [ContractPartiesController::class,'contract_parties'])->name('contract_parties');
    
    Route::get('/parties-search', [ContractPartiesController::class,'parties_json'])->name('contract_parties_json');
    
    Route::get('/parties-get-entity-types', [ContractPartiesController::class,'get_party_entity_types'])->name('contract_parties_entity_types_json');

    Route::any('/contract-parties-org-add', [ContractPartiesController::class,'contract_parties_add_org'])->name('contract-parties-add-organization');
    
    Route::any('/contract-parties-add-form', [ContractPartiesController::class,'contract_parties_add_form'])->name('contract-parties-add-form');
    
    Route::any('/contract-parties-ind-add', [ContractPartiesController::class,'contract_parties_add_ind'])->name('contract-parties-add-individual');

    Route::get('/contract-parties-delete/{id}', [ContractPartiesController::class,'contract_parties_delete'])->name('contract-parties-delete');

    Route::any('/contract-parties-org-edit/{id}', [ContractPartiesController::class,'contract_parties_edit_org'])->name('contract-parties-edit-organization');
    
    Route::any('/contract-parties-ind-edit/{id}', [ContractPartiesController::class,'contract_parties_edit_ind'])->name('contract-parties-edit-individual');
    
    Route::any('/contract-parties-org-view/{id}', [ContractPartiesController::class,'contract_parties_view_org'])->name('contract-parties-view-organization');
    
    Route::any('/contract-parties-ind-view/{id}', [ContractPartiesController::class,'contract_parties_view_ind'])->name('contract-parties-view-individual');
    
    Route::any('/contract-parties-org-bulk-import', [ContractPartiesController::class,'contract_parties_bulk_import_org'])->name('contract-parties-bulk-import-organization');
    
    Route::any('/contract-parties-ind-bulk-import', [ContractPartiesController::class,'contract_parties_bulk_import_ind'])->name('contract-parties-bulk-import-individual');
    
    Route::any('/contract-parties-bulk-check', [ContractPartiesController::class,'contract_parties_bulk_check_view'])->name('contract-parties-bulk-check-view');
    
    Route::any('/template-download-org-parties', [ContractPartiesController::class,'contract_parties_template_download_org'])->name('contract-parties-template-download-organization');
    
    Route::any('/template-download-ind-parties', [ContractPartiesController::class,'contract_parties_template_download_ind'])->name('contract-parties-template-download-individual');
    
    Route::post('builk-org-import/upload', [ContractPartiesController::class, 'contract_parties_upload_file_org'])->name('upload-organization');
    
    Route::post('builk-ind-import/upload', [ContractPartiesController::class, 'contract_parties_upload_file_ind'])->name('upload-individual');

    Route::post('builk-org-import/store', [ContractPartiesController::class, 'contract_parties_store_file_org'])->name('file_store-organization');
    
    Route::post('builk-ind-import/store', [ContractPartiesController::class, 'contract_parties_store_file_ind'])->name('file_store-individual');
    
    Route::post('builk-parties/check', [ContractPartiesController::class, 'contract_parties_bulk_check'])->name('file_check-all');
    
    Route::post('/partyApprovalFlow', [ContractPartiesController::class,'party_approval_flow'])->name('party-approval-flow');

    // Vendor Import & Validation
    Route::any('/vendor-import',              [ContractPartiesController::class, 'vendor_import_view'])->name('vendor_import_view');
    Route::post('/vendor-import/upload',      [ContractPartiesController::class, 'vendor_import_upload'])->name('vendor_import_upload');
    Route::post('/vendor-import/process-chunk', [ContractPartiesController::class, 'vendor_import_process_chunk'])->name('vendor_import_process_chunk');
    Route::post('/vendor-import/validate',    [ContractPartiesController::class, 'vendor_import_validate'])->name('vendor_import_validate');
    Route::post('/vendor-import/export',      [ContractPartiesController::class, 'vendor_import_export_unmatched'])->name('vendor_import_export');

    // Contract Parties
    Route::get('/representative-section/{index}', function (Request $request) {

	   $index = $request->index;
	   return view('parties::contract_parties.representative',compact('index'));
    });
    
    Route::post('/getState',[ContractPartiesController::class,'getState'])->name('getState');

    // Party Update Import via Excel
    Route::get('/parties-update-import', [ContractPartiesController::class, 'parties_update_import_view'])->name('parties_update_import_view');
    Route::post('/parties-update-import/upload', [ContractPartiesController::class, 'parties_update_import_upload'])->name('parties_update_import_upload');
    Route::post('/parties-update-import/preview', [ContractPartiesController::class, 'parties_update_import_preview'])->name('parties_update_import_preview');
    Route::post('/parties-update-import/execute', [ContractPartiesController::class, 'parties_update_import_execute'])->name('parties_update_import_execute');

});
