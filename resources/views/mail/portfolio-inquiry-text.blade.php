@php($personal = $personal ?? true)
{!! $personal ? $owner->name.' · Personal portfolio' : 'iTechOrbit · Business inquiry' !!}

Hello {!! $owner->name !!},

{!! $inquiry->name !!} {!! $personal ? 'sent a message through your portfolio.' : 'sent an inquiry through the iTechOrbit website.' !!}

From: {!! $inquiry->name !!}
Email: {!! $inquiry->email !!}
Subject: {!! $inquiry->subject !!}

Message:
{!! $inquiry->message !!}

Reply directly to this email to respond to {!! $inquiry->name !!}.

{!! $personal ? 'View in your personal inbox:' : 'View in the business inbox:' !!} {!! url('/contacts/'.$inquiry->id) !!}

{!! $personal ? 'This message was addressed to you through your personal portfolio.' : 'An iTechOrbit website inquiry. Reply directly to continue the conversation.' !!}
