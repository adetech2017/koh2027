<x-mail::message>
# Welcome to the KOH 2027 Volunteer Team!

Hello {{ $volunteer->first_name }}!

Thank you for volunteering to be part of Kadri Obafemi Hamzat's campaign for Lagos State Governor. We are excited to have you on our team!

## What's Next?

Our campaign team will review your volunteer registration and reach out to you shortly with:

- Available volunteer opportunities in your area ({{ $volunteer->lga }})
- Training and resources you'll need
- Campaign events and activities
- Ways you can make the most impact

## Your Registered Information

- **Location:** {{ $volunteer->lga }}
- **Contact:** {{ $volunteer->email }}
- **Skills:** {{ implode(', ', array_filter($volunteer->skills ?? [])) ?: 'Any' }}

## Stay Connected

Follow us on social media to stay updated:

- Facebook: @KadriObafemiHamzat
- Twitter/X: @KOHforLagos
- Instagram: @kohforlakosstate
- TikTok: @KadriForLagos

Together, we will build a better Lagos!

**Kadri Obafemi Hamzat for Governor**

Lagos State | 2027

</x-mail::message>
