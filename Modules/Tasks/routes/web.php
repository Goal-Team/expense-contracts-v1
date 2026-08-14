<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Http\Controllers\TasksController;

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
//     Route::resource('tasks', TasksController::class)->names('tasks');
// });

Route::group(['middleware'=>['xssprotect', 'contractauth'],'prefix'=>'tasks','as'=>'tasks.'], function() {  

    // Route::get('/', [TasksController::class,'index'])->name('index');
    
    Route::get('/', [TasksController::class,'index'])->name('tasks');
    
    Route::get('/data', [TasksController::class,'index_data'])->name('index_data');

    Route::any('/tasks-add', [TasksController::class,'create'])->name('tasks-add');
    
    Route::any('/tasks-create', [TasksController::class,'store'])->name('tasks-create');

    Route::get('/tasks-delete/{id}', [TasksController::class,'destroy'])->name('tasks-delete');

    Route::any('/tasks-edit/{id}', [TasksController::class,'edit'])->name('tasks-edit');
    
    Route::any('/tasks-view/{id}', [TasksController::class,'view'])->name('tasks-view');
    
    Route::any('/tasks-update/{id}', [TasksController::class,'update'])->name('tasks-update');

    // Contract Parties

});
