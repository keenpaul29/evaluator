<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DesignTokenController extends Controller
{
    public function index(Request $request)
    {
        $tokens = [
            'colors' => [
                'surface' => [
                    'primary' => ['value' => '#ffffff', 'description' => 'Primary surface background'],
                    'secondary' => ['value' => '#f9fafb', 'description' => 'Secondary surface background (gray-50)'],
                    'elevated' => ['value' => '#ffffff', 'description' => 'Elevated surface with shadow'],
                ],
                'border' => [
                    'default' => ['value' => '#f3f4f6', 'description' => 'Default border (gray-100)'],
                    'subtle' => ['value' => '#e5e7eb', 'description' => 'Subtle border (gray-200)'],
                ],
                'text' => [
                    'primary' => ['value' => '#111827', 'description' => 'Primary text (gray-900)'],
                    'secondary' => ['value' => '#6b7280', 'description' => 'Secondary text (gray-500)'],
                    'tertiary' => ['value' => '#9ca3af', 'description' => 'Tertiary text (gray-400)'],
                ],
                'accent' => [
                    'default' => ['value' => '#2563eb', 'description' => 'Primary accent (blue-600)'],
                ],
                'status' => [
                    'submitted' => ['value' => '#f59e0b', 'description' => 'Submitted status (amber-500)'],
                    'analyzing' => ['value' => '#3b82f6', 'description' => 'Analyzing status (blue-500)'],
                    'evaluated' => ['value' => '#8b5cf6', 'description' => 'Evaluated status (violet-500)'],
                    'shortlisted' => ['value' => '#10b981', 'description' => 'Shortlisted status (emerald-500)'],
                    'rejected' => ['value' => '#ef4444', 'description' => 'Rejected status (red-500)'],
                    'pending' => ['value' => '#6b7280', 'description' => 'Pending status (gray-500)'],
                    'processing' => ['value' => '#3b82f6', 'description' => 'Processing status (blue-500)'],
                    'complete' => ['value' => '#10b981', 'description' => 'Complete status (emerald-500)'],
                    'partial_failure' => ['value' => '#f59e0b', 'description' => 'Partial failure status (amber-500)'],
                ],
                'verdict' => [
                    'strong_hire' => ['value' => '#16a34a', 'description' => 'Strong hire (green-600)'],
                    'hire' => ['value' => '#10b981', 'description' => 'Hire (emerald-500)'],
                    'maybe' => ['value' => '#f59e0b', 'description' => 'Maybe (amber-500)'],
                    'no_hire' => ['value' => '#f97316', 'description' => 'No hire (orange-500)'],
                    'strong_no_hire' => ['value' => '#ef4444', 'description' => 'Strong no hire (red-500)'],
                    'insufficient_data' => ['value' => '#9ca3af', 'description' => 'Insufficient data (gray-400)'],
                ],
            ],
            'spacing' => [
                '0' => ['value' => '0px', 'description' => 'No spacing'],
                '1' => ['value' => '4px', 'description' => 'Base unit'],
                '2' => ['value' => '8px', 'description' => '2x base'],
                '3' => ['value' => '12px', 'description' => '3x base'],
                '4' => ['value' => '16px', 'description' => '4x base'],
                '5' => ['value' => '20px', 'description' => '5x base'],
                '6' => ['value' => '24px', 'description' => '6x base'],
                '8' => ['value' => '32px', 'description' => '8x base'],
                '10' => ['value' => '40px', 'description' => '10x base'],
                '12' => ['value' => '48px', 'description' => '12x base'],
            ],
            'borderRadius' => [
                'card' => ['value' => '12px', 'description' => 'Card radius'],
                'badge' => ['value' => '6px', 'description' => 'Badge radius'],
                'button' => ['value' => '8px', 'description' => 'Button radius'],
            ],
            'boxShadow' => [
                'card' => ['value' => '0 1px 3px 0 rgb(0 0 0 / 0.04), 0 1px 2px -1px rgb(0 0 0 / 0.04)', 'description' => 'Card shadow'],
                'elevated' => ['value' => '0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05)', 'description' => 'Elevated shadow'],
            ],
            'typography' => [
                'fontFamily' => [
                    'sans' => ['value' => 'Inter, system-ui, -apple-system, sans-serif', 'description' => 'Primary font'],
                    'mono' => ['value' => 'JetBrains Mono, Fira Code, monospace', 'description' => 'Monospace font'],
                ],
            ],
            'transitions' => [
                'fast' => ['value' => '150ms', 'description' => 'Fast transition'],
                'normal' => ['value' => '200ms', 'description' => 'Normal transition'],
                'slow' => ['value' => '300ms', 'description' => 'Slow transition'],
            ],
        ];

        $jsonTokens = $this->generateJsonTokens($tokens);

        return view('design-tokens.index', compact('tokens', 'jsonTokens'));
    }

    private function generateJsonTokens(array $tokens): array
    {
        $result = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'version' => '1.0.0',
            'name' => 'ColoredCow Design Tokens',
            'description' => 'Design tokens for ColoredCow Technical Candidate Evaluator',
            'tokens' => [],
        ];

        foreach ($tokens as $category => $groups) {
            $result['tokens'][$category] = [];
            foreach ($groups as $group => $tokens) {
                $result['tokens'][$category][$group] = [];
                foreach ($tokens as $name => $data) {
                    $result['tokens'][$category][$group][$name] = [
                        'value' => $data['value'],
                        'description' => $data['description'],
                        'type' => $this->inferType($data['value']),
                    ];
                }
            }
        }

        return $result;
    }

    private function inferType(string $value): string
    {
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return 'color';
        }
        if (preg_match('/^\d+px$/', $value)) {
            return 'dimension';
        }
        if (preg_match('/^\d+ms$/', $value)) {
            return 'duration';
        }
        if (str_contains($value, 'rgb') || str_contains($value, 'rgba')) {
            return 'color';
        }
        return 'other';
    }
}