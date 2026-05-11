<x-mail::message>
# Nouveau message
**De :** {{ $contact->full_name }}
**Email :** {{ $contact->email }}
**Sujet :** {{ $contact->subject }}
---
{{ $contact->message }}
{{ config('app.name') }}
</x-mail::message>
