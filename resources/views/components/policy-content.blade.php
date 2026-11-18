@props(['policy'])

@if ($policy->content_format === \App\PolicyContentFormat::Html)
    <div {{ $attributes->merge(['class' => 'policy-content']) }}>{!! $policy->content !!}</div>
@else
    <div {{ $attributes->merge(['class' => 'whitespace-pre-wrap text-sm leading-7 text-slate-800']) }}>{{ $policy->content }}</div>
@endif
