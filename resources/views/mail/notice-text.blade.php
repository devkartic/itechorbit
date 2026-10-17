{!! $campaign->subject !!}

Dear {!! trim((string) $recipient->name) ?: 'iTechOrbit member' !!},

{!! html_entity_decode(trim(preg_replace('/<[^>]+>/', '', preg_replace('/<\/(p|div|h[1-6]|li)>|<br\s*\/?\s*>/i', "\n", $campaign->body))), ENT_QUOTES | ENT_HTML5, 'UTF-8') !!}

Kind regards,
The iTechOrbit Team

This notice was sent to you as an iTechOrbit account holder.
