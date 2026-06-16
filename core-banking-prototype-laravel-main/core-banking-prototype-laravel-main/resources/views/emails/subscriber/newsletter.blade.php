<x-mail::message>
{!! Str::markdown($content) !!}

<x-mail::button :url="config('app.url')">
Visit {{ company_name() }}
</x-mail::button>

Best regards,<br>
{{ team_signature() }}

<x-mail::subcopy>
You're receiving this email because you subscribed to updates from {{ company_name() }}. If you no longer wish to receive these emails, you can <a href="{{ $unsubscribeUrl }}">unsubscribe here</a>.
</x-mail::subcopy>
</x-mail::message>