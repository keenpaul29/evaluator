@props([
    'type' => 'text',
    'name' => null,
    'label' => null,
    'placeholder' => '',
    'value' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'icon' => null,
    'iconPosition' => 'left',
    'class' => '',
    'attributes' => [],
])

@php
    $inputTypes = ['text', 'email', 'password', 'number', 'tel', 'url', 'search', 'date', 'datetime-local', 'time', 'textarea', 'select'];
    $isTextarea = $type === 'textarea';
    $isSelect = $type === 'select';
    $hasIcon = !is_null($icon);
    $inputId = $name ? 'input-' . str_replace(['[', ']', '.'], '-', $name) : 'input-' . uniqid();

    $baseClasses = 'w-full px-3 py-2.5 border border-gray-200 rounded-button text-sm bg-white placeholder:text-gray-400';
    $focusClasses = 'focus:ring-2 focus:ring-accent/20 focus:border-accent transition-colors';
    $disabledClasses = 'disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed';
    $errorClasses = $error ? 'border-red-300 focus:ring-red-500/20 focus:border-red-500' : '';
    $iconClasses = $hasIcon ? ($iconPosition === 'left' ? 'pl-10' : 'pr-10') : '';
    $iconPositionClass = $iconPosition === 'left' ? 'absolute left-3 top-1/2 -translate-y-1/2' : 'absolute right-3 top-1/2 -translate-y-1/2';
@endphp

<div class="{{ $class }}" {{ $attributes }}>
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-1" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    @if($hasIcon && !$isTextarea && !$isSelect)
        <div class="relative">
    @endif

    @if($isTextarea)
        <textarea id="{{ $inputId }}"
                  name="{{ $name }}"
                  placeholder="{{ $placeholder }}"
                  required="{{ $required ? 'required' : '' }}"
                  disabled="{{ $disabled ? 'disabled' : '' }}"
                  readonly="{{ $readonly ? 'readonly' : '' }}"
                  class="{{ $baseClasses }} {{ $focusClasses }} {{ $disabledClasses }} {{ $errorClasses }} {{ $class }}"
                  rows="4"
                  {{ $attributes }}>{{ $value }}</textarea>
    @elseif($isSelect)
        <select id="{{ $inputId }}"
                name="{{ $name }}"
                required="{{ $required ? 'required' : '' }}"
                disabled="{{ $disabled ? 'disabled' : '' }}"
                class="{{ $baseClasses }} {{ $focusClasses }} {{ $disabledClasses }} {{ $errorClasses }} appearance-none bg-white {{ $class }}"
                {{ $attributes }}>
            @if($placeholder && !$required)
                <option value="">{{ $placeholder }}</option>
            @endif
            @if(isset($attributes['options']) && is_array($attributes['options']))
                @foreach($attributes['options'] as $value => $label)
                    <option value="{{ $value }}" {{ (string)$value === (string)($value) ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            @endif
        </select>
    @else
        <input type="{{ $type }}"
               id="{{ $inputId }}"
               name="{{ $name }}"
               placeholder="{{ $placeholder }}"
               value="{{ $value }}"
               required="{{ $required ? 'required' : '' }}"
               disabled="{{ $disabled ? 'disabled' : '' }}"
               readonly="{{ $readonly ? 'readonly' : '' }}"
               class="{{ $baseClasses }} {{ $focusClasses }} {{ $disabledClasses }} {{ $errorClasses }} {{ $iconClasses }} {{ $class }}"
               {{ $attributes }}>
    @endif

    @if($hasIcon && !$isTextarea && !$isSelect)
        <div class="{{ $iconPositionClass }} text-gray-400 pointer-events-none"
             aria-hidden="true">
            {{ $icon }}
        </div>
        </div>
    @endif

    @if($error)
        <p class="mt-1.5 text-sm text-red-600" role="alert">{{ $error }}</p>
    @endif

    @if($hint && !$error)
        <p class="mt-1.5 text-sm text-gray-400">{{ $hint }}</p>
    @endif
</div>