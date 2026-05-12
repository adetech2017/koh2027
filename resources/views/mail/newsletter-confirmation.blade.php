<x-mail::message>
# Confirm Your Email Subscription

Hello{{ $subscriber->name ? ', ' . $subscriber->name : '' }}!

Thank you for signing up for the KOH 2027 Campaign Newsletter. To complete your subscription, please confirm your email address by clicking the button below.

<x-mail::button :url="route('newsletter.confirm', $subscriber->token)">
Confirm Subscription
</x-mail::button>

If you did not sign up for this newsletter, you can simply ignore this email.

**Kadri Obafemi Hamzat for Governor**

Lagos State | 2027

</x-mail::message>
