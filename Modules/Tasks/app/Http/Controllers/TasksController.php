<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use App\Models\Tasks;
use App\Models\Branch;
use App\Models\BranchUser;
use App\Models\Contract;
use App\Models\ContractPartyData;
use App\Models\AddUsers;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Helpers;

class TasksController extends Controller
{

    public function __construct()
    {
      if(Controller::checkCurrentAuth("Contracts") != 1){
         return abort('404');
      }
    }
    
    public function index(){
        return view('tasks::tasks.index');
    }
    
    public function index_data(Request $request)
    {
        
        try
        {
            
            $available_branches = BranchUser::pluck('id');
            
            $userId = $_COOKIE['myFilterTasks'] ?? 0;
            $taskData = [];
            $count = ['completed' => 0, 'pending' => 0, 'inprogress' => 0];
            if (isset($_GET['status'])) {
                $tasksQuery = Tasks::select('id', 'name_of_task', 'status', 'start_date', 'end_date' ,'description') // Removed DB::raw as it is not necessary here
                ->whereIn('branch', $available_branches)
                ->orderBy('id', 'desc');
                
                if($userId > 0){
                 $tasksQuery->where('task_owner', $userId);   
                }
                
                $tasks = $tasksQuery->get();
              
              $tasks->map(function ($task) use (&$count, &$taskData) { 
                  $task->name_of_task = decryptString($task->name_of_task, 'name_of_task');
                  $task->start_date = decryptString($task->start_date, 'start_date');
                  $task->end_date = decryptString($task->end_date, 'end_date');
                  $task->description = decryptString($task->description, 'description');
                  $count[$task->status]++;
                  if($_GET['status'] != 'all' && strtolower($task->status) === $_GET['status']){
                    $taskData[] = $task;
                  }                
                  return $task;
              })
              ->toArray();
                
            } else {
                $tasksQuery = Tasks::select('id', 'name_of_task', 'status', 'start_date', 'end_date','description') // Removed DB::raw as it is not necessary here
              ->whereIn('branch', $available_branches)
                ->whereIn('branch', $available_branches)
                ->orderBy('id', 'desc');
                
                if($userId > 0){
                 $tasksQuery->where('task_owner', $userId);   
                }
                $taskData = $tasksQuery->get()
              ->map(function ($task) use (&$count) { 
                  $task->name_of_task = decryptString($task->name_of_task, 'name_of_task');
                  $task->start_date = decryptString($task->start_date, 'start_date');
                  $task->end_date = decryptString($task->end_date, 'end_date');
                  $task->description = decryptString($task->description, 'description');
                  $count[$task->status]++;
                  return $task;
              })
              ->toArray();
            }
    
            return response()->json([
                    'data' => $taskData,
                    'count_data' => $count,
                    'draw' => $request->input('draw') ?? 1,
                    'recordsTotal' => count($tasks),
                    'recordsFiltered' => count($tasks),
                ]);
                
            return response()->json($taskData, 200);
            
        }catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();      
            return $message;
         }
        

    }
    
  
    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $branch = BranchUser::select('id', decrypt_data('LegalName', 'branch'))->get();
        $add_users = AddUsers::select("id",decrypt_data('FirstName','AddUsers'), decrypt_data('LastName','AddUsers'))->get();
        $contracts_list_all = Contract::select('contract_name', 'id')->where('status', 1)->get();
        $contracts_list = $this->availableContracts($contracts_list_all, true);
        return view('tasks::tasks.create' ,  compact('branch', 'add_users', 'contracts_list'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        
        $validator =  Validator::make($request->all(),[
            'name_of_task' => 'required',
            'priority' => 'required',
            'task_owner' => 'required',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required',
            'task_reviewer' => 'required',
            'start_date' => 'required',
            'branch' => 'required',
            'contract_id' => 'required'
        ]);
        
        
       
        if($validator->fails()) {
            $errors = $validator->errors();
            return redirect('tasks/tasks-add')->withErrors($validator)->withInput();
        }
        

        Tasks::create([
            'name_of_task' => encryptString($request->input('name_of_task'), 'name_of_task'),
            'priority' => encryptString($request->input('priority'), 'priority'),
            'task_owner' => $request->input('task_owner'),
            'branch' => $request->input('branch'),
            'start_date' => encryptString($request->input('start_date'), 'start_date'),
            'task_reviewer' => $request->input('task_reviewer'),
            'status' => $request->input('status'),
            'end_date' => encryptString($request->input('end_date'), 'end_date'),
            'description' => encryptString($request->input('task_desc'), 'description'),
            'contract_id' => $request->input('contract_id'),
            ]);

        return redirect('tasks')->with('success','Tasks created Successfully.');



    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('tasks::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $tasks = Tasks::find($id);
        $branch = BranchUser::select('id', decrypt_data('LegalName', 'branch'))->get();
        $add_users = AddUsers::select("id",decrypt_data('FirstName','AddUsers'), decrypt_data('LastName','AddUsers'))->get();
        $contracts_list_all = Contract::select('contract_name', 'id')->where('status', 1)->get();
        $contracts_list = $this->availableContracts($contracts_list_all, true);     
        return view('tasks::tasks.edit' , compact('tasks','branch','add_users','contracts_list'));
    }
    
    public function view($id)
    {
    
        $tasks = Tasks::find($id);
        // $tasks = Tasks::where('task_id', '=', $id)->first();
        return view('tasks::tasks.view' , compact('tasks'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {

        $validator =  Validator::make($request->all(),[
            'name_of_task' => 'required',
            'priority' => 'required',
            'task_owner' => 'required',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required',
            'task_reviewer' => 'required',
            'start_date' => 'required',
            'branch' => 'required',
            'contract_id' => 'required',
            'task_desc' => 'nullable'
        ]);
        
        
       
        if($validator->fails()) {
            $errors = $validator->errors();
            return redirect('tasks/tasks-edit/'.$id)->withErrors($validator)->withInput();
        }

        $tasks = Tasks::find($id);
        $tasks->name_of_task = encryptString($request->input('name_of_task'), 'name_of_task');
        $tasks->branch = $request->input('branch');
        $tasks->status = $request->input('status');
        $tasks->start_date = encryptString($request->input('start_date'), 'start_date');
        $tasks->end_date = encryptString($request->input('end_date'), 'end_date');
        $tasks->priority = encryptString($request->input('priority'), 'priority');
        $tasks->task_owner = $request->input('task_owner');
        $tasks->task_reviewer = $request->input('task_reviewer');   
        $tasks->description = encryptString($request->input('task_desc'), 'description');
        $tasks->contract_id = $request->input('contract_id');
        
        $tasks->update();

        return redirect('tasks')->with('success','Tasks Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $task=Tasks::find($id);  
        $task->delete();
        return redirect('tasks')->with('success','Tasks Delete Successfully');

    }
}
