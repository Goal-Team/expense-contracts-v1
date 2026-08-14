<?php

namespace Modules\Contract\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\AdminSettings;

class AdminConfigSettingsController extends Controller
{
    public function index()
    {
        $settings = AdminSettings::latest()->paginate(10);
        return view('contract::admin_settings.index', compact('settings'));
    }

    public function create()
    {
        return view('contract::admin_settings.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'admin_key' => 'required|string|unique:admin_settings,admin_key',
            'admin_value' => 'nullable',
            'active' => 'boolean',
        ]);

        $validated['admin_value'] = $this->parseValue($validated['admin_value']);

        AdminSettings::create($validated);

        return redirect('/admin-settings')
            ->with('success', 'Setting created successfully.');
    }

    public function edit(AdminSettings $admin_setting)
    {
        return view('contract::admin_settings.edit', compact('admin_setting'));
    }

    public function update(Request $request, AdminSettings $admin_setting)
    {
        $validated = $request->validate([
            'admin_value' => 'nullable',
            'active' => 'boolean',
        ]);

        $validated['admin_value'] = $this->parseValue($validated['admin_value']);
        $admin_setting->update($validated);

        return redirect('/admin-settings')->with('success', 'Setting updated successfully.');
    }

    private function parseValue($value)
    {
        if (is_null($value) || $value === '') return null;
        $lower = strtolower($value);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if ($this->isJson($value)) return json_decode($value, true);
        return $value;
    }

    private function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

?>
