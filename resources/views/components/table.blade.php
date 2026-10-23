@props([
    'headers' => [],
    'rows' => [],
    'striped' => true,
    'hover' => true,
    'emptyMessage' => 'No data available.',
    'emptyIcon' => null,
    'emptyAction' => null,
    'class' => '',
    'attributes' => [],
])

@php
    $hasHeaders = !empty($headers);
    $hasRows = !empty($rows);
    $emptyIconDefault = '<svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
    $emptyIcon = $emptyIcon ?? $emptyIconDefault;
@endphp

<div class="{{ $class }}" {{ $attributes }}>
    @if($hasHeaders && $hasRows)
        <div class="overflow-x-auto">
            <table class="w-full text-sm" {{ $attributes }}>
                <thead>
                    <tr class="border-b border-gray-100">
                        @foreach($headers as $header)
                            @php
                                $headerClass = 'px-5 py-3 font-medium text-gray-400 text-xs uppercase tracking-wider';
                                $alignClass = $header['align'] ?? 'left';
                                $widthClass = $header['width'] ?? '';
                            @endphp
                            <th class="{{ $headerClass }} text-{{ $alignClass }} {{ $widthClass }}"
                                {{ $header['attributes'] ?? '' }}>
                                {{ $header['label'] ?? $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="{{ $striped ? 'divide-y divide-gray-50' : '' }}">
                    @foreach($rows as $row)
                        <tr class="{{ $hover ? 'hover:bg-gray-50/50 transition-colors' : '' }} {{ $row['class'] ?? '' }}">
                            @foreach($headers as $index => $header)
                                @php
                                    $alignClass = $header['align'] ?? 'left';
                                    $cellClass = 'px-5 py-3.5 text-{{ $alignClass }}';
                                    $rowKey = $header['key'] ?? $index;
                                    $cellData = $row[$rowKey] ?? '';
                                @endphp
                                <td class="{{ $cellClass }}" {{ $header['cellAttributes'] ?? '' }}>
                                    @if(is_callable($cellData))
                                        {{ $cellData($row) }}
                                    @else
                                        {{ $cellData }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif(!$hasRows)
        <div class="p-16 text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                {!! $emptyIcon !!}
            </div>
            <p class="text-gray-500 text-sm">{{ $emptyMessage }}</p>
            @if($emptyAction)
                <div class="mt-3">
                    @if(is_string($emptyAction))
                        {{ $emptyAction }}
                    @else
                        {{ $emptyAction }}
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>