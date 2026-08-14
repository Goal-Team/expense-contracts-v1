<?php

namespace Modules\Contractsetup\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MenuConfig;

class MenuConfigController extends Controller
{
    public function __construct()
    {
        // // Simple role check using session; restrict access to Admin and Super Admin
        // $this->middleware(function ($request, $next) {
        //     $role = session('contractSessionUserRole');
        //     if (!in_array($role, ['Admin', 'Super Admin'])) {
        //         abort(403, 'Unauthorized.');
        //     }
        //     return $next($request);
        // });
    }

    public function index()
    {
        $configs = MenuConfig::orderBy('menu_type')->orderBy('role')->get();
        return view('contract-setup::admin.menus.index', compact('configs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'menu_type' => 'required|in:Vertical,Horizontal',
            'role' => 'required|string|max:255',
            'menu_json' => 'required|string',
            'active' => 'sometimes|boolean',
        ]);

        $data['active'] = isset($data['active']) ? (bool)$data['active'] : true;

        $config = MenuConfig::updateOrCreate(
            ['menu_type' => $data['menu_type'], 'role' => $data['role']],
            ['menu_json' => $data['menu_json'], 'active' => $data['active']]
        );

        return response()->json(['success' => true, 'config' => $config]);
    }

    public function edit($id)
    {
        $config = MenuConfig::findOrFail($id);
        return response()->json(['config' => $config]);
    }

    public function update(Request $request, $id)
    {
        $config = MenuConfig::findOrFail($id);

        $data = $request->validate([
            'menu_type' => 'required|in:Vertical,Horizontal',
            'role' => 'required|string|max:255',
            'menu_json' => 'required|string',
            'active' => 'sometimes|boolean',
        ]);

        $config->update([
            'menu_type' => $data['menu_type'],
            'role' => $data['role'],
            'menu_json' => $data['menu_json'],
            'active' => isset($data['active']) ? (bool)$data['active'] : true,
        ]);

        return response()->json(['success' => true, 'config' => $config]);
    }

    public function destroy($id)
    {
        $config = MenuConfig::findOrFail($id);
        $config->delete();
        return response()->json(['success' => true]);
    }

    public function toggleActive($id)
    {
        $config = MenuConfig::findOrFail($id);
        $config->active = !$config->active;
        $config->save();
        return response()->json(['success' => true, 'active' => $config->active]);
    }
}
