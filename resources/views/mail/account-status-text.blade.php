{!! $title !!}

Hello {!! $recipientName !!},

Your {!! $kind !!} has been {!! $enabled ? 'enabled' : 'disabled' !!}.
{!! $description !!}

{!! $actionLabel !!}: {!! $actionUrl !!}
Contact our team: {!! url('/contact/info') !!}

Kind regards,
The iTechOrbit Team
