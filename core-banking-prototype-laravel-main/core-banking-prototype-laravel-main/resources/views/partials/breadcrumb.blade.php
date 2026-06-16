{{-- Visual breadcrumb trail for inner pages --}}
@if(isset($items) && count($items) > 0)
<nav class="breadcrumb mb-6" aria-label="Breadcrumb">
    <a href="{{ url('/') }}">Home</a>
    @foreach($items as $item)
        <span class="breadcrumb-sep"></span>
        @if(!$loop->last)
            <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
        @else
            <span class="text-slate-500">{{ $item['name'] }}</span>
        @endif
    @endforeach
</nav>
@endif
