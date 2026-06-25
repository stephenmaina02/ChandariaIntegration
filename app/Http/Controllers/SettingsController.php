<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    private const SETTINGS_FILE = 'settings.json';

    public function getAllowedWarehouses()
    {
        $settings = $this->readSettings();
        return response()->json([
            'allowed_order_warehouses' => $settings['allowed_order_warehouses'] ?? []
        ]);
    }

    public function updateAllowedWarehouses(Request $request)
    {
        $request->validate([
            'warehouses'   => 'required|array|min:1',
            'warehouses.*' => 'required|string|max:50',
        ]);

        $warehouses = array_values(array_unique(array_map('strtoupper', $request->warehouses)));

        $settings = $this->readSettings();
        $settings['allowed_order_warehouses'] = $warehouses;
        $this->writeSettings($settings);

        return response()->json([
            'message'                  => 'Allowed warehouses updated successfully.',
            'allowed_order_warehouses' => $warehouses,
        ]);
    }

    public static function readSettings(): array
    {
        if (!Storage::exists(self::SETTINGS_FILE)) {
            return [];
        }
        return json_decode(Storage::get(self::SETTINGS_FILE), true) ?? [];
    }

    private function writeSettings(array $settings): void
    {
        Storage::put(self::SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT));
    }
}
