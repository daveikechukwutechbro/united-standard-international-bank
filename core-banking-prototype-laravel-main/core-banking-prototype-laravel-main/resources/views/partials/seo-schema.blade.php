{{-- Schema.org JSON-LD — available for pages that need custom schema injection.
     Most pages use SchemaHelper directly; this partial is kept for ad-hoc overrides. --}}
@if(isset($schemas) && is_array($schemas))
    @foreach($schemas as $schema)
        <x-schema :type="$schema['type']" :data="$schema['data'] ?? []" />
    @endforeach
@elseif(isset($schema))
    {!! $schema !!}
@endif