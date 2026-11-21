@php($bagErrors = isset($bag) ? $errors->getBag($bag) : $errors)
@if($bagErrors->has($field))
    <p id="{{ ($bag ?? 'f') }}-{{ str_replace(['.', '*'], '-', $field) }}-error" class="field-error" role="alert">{{ $bagErrors->first($field) }}</p>
@endif
