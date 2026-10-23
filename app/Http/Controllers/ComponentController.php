<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ComponentController extends Controller
{
    public function index(Request $request)
    {
        $components = [
            [
                'name' => 'Badge',
                'description' => 'Status and label badges with dot indicator',
                'variants' => ['default', 'primary', 'success', 'warning', 'danger', 'info', 'status-*', 'verdict-*'],
                'sizes' => ['xs', 'sm', 'md', 'lg'],
                'example' => '<x-badge label="Active" variant="success" size="sm" />',
            ],
            [
                'name' => 'Card',
                'description' => 'Container component with elevation variants',
                'variants' => ['default', 'elevated', 'bordered', 'ghost', 'surface'],
                'paddings' => ['none', 'xs', 'sm', 'md', 'lg', 'xl'],
                'example' => '<x-card variant="elevated" padding="md" header="Title">Content</x-card>',
            ],
            [
                'name' => 'Form Input',
                'description' => 'Form input with label, error handling, and icons',
                'types' => ['text', 'email', 'password', 'number', 'url', 'textarea', 'select', 'date'],
                'example' => '<x-form-input type="email" name="email" label="Email" required :error="$errors->first(\'email\')" />',
            ],
            [
                'name' => 'Table',
                'description' => 'Data table with sorting, empty states, and custom cells',
                'features' => ['striped', 'hover', 'custom cell rendering', 'alignment', 'empty state'],
                'example' => '<x-table :headers="[...]" :rows="[...]" />',
            ],
        ];

        return view('components.index', compact('components'));
    }
}