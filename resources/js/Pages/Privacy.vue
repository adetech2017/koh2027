<template>
  <AppLayout>
    <Head title="Privacy Policy" />
    <div class="min-h-screen bg-white py-12 md:py-16">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl md:text-5xl font-bold text-dark mb-3">Privacy Policy</h1>
        <p class="text-sm text-gray-500 mb-10">Last updated: {{ LAST_UPDATED }}</p>

        <div class="policy space-y-6 text-body leading-relaxed">
          <section>
            <h2>Who we are</h2>
            <p>
              This website is run by the KOH 2027 Campaign, the campaign of Kadri Obafemi Hamzat for Governor of Lagos State
              ("we", "us"). We are responsible for the personal information collected through this site, and we handle it in line
              with the Nigeria Data Protection Act 2023.
            </p>
          </section>

          <section>
            <h2>Information you give us</h2>
            <p>When you use a form on this site, we collect what you enter:</p>
            <ul>
              <li><strong>Contact form:</strong> your name, email, optional phone number, and your message.</li>
              <li><strong>Newsletter:</strong> your email address (and name, if you give it).</li>
              <li><strong>Event registration:</strong> your name, email, and optionally your phone number and Local Government Area.</li>
              <li><strong>Volunteering:</strong> the details you provide on the volunteer form, such as your contact details, LGA and skills.</li>
            </ul>
          </section>

          <section>
            <h2>Website analytics</h2>
            <p>
              To understand which pages are useful, we count visits and clicks using our own analytics, which run on our server.
              We do <strong>not</strong> use cookies for this and we do not use Google Analytics or advertising trackers.
            </p>
            <ul>
              <li>We record the page visited, the website that referred you (for example, Facebook or Google), your device type (mobile, desktop or tablet), your browser name, and clicks on outside links, shares and downloads.</li>
              <li>To count unique visitors, we create an anonymous code from your IP address and browser, mixed with a secret key and that day's date. The code changes every day, cannot be turned back into your IP address, and cannot be used to follow you from one day to the next. Your IP address itself is not stored.</li>
              <li>If your browser sends a "Do Not Track" or "Global Privacy Control" signal, we don't record your visit at all.</li>
              <li>Analytics records are deleted after about 13 months.</li>
            </ul>
          </section>

          <section>
            <h2>How we use your information</h2>
            <ul>
              <li>To reply to your messages.</li>
              <li>To send campaign news, if you subscribed. Every email has an unsubscribe link.</li>
              <li>To manage event registrations and volunteer applications.</li>
              <li>To understand and improve how the website is used.</li>
            </ul>
            <p>We do not sell your personal information.</p>
          </section>

          <section>
            <h2>Who can see it</h2>
            <p>
              Your information is available only to authorised campaign staff. We use service providers to host this website and
              send email, and they process data only on our instructions.
            </p>
          </section>

          <section>
            <h2>How long we keep it</h2>
            <p>
              We keep your information for as long as it's needed for the purpose you gave it, and no longer than the end of the
              2027 election cycle, unless the law requires us to keep it longer. Newsletter subscribers can unsubscribe at any time.
            </p>
          </section>

          <section>
            <h2>Keeping it safe</h2>
            <p>
              We use appropriate technical and organisational measures to protect your information from unauthorised access,
              alteration, disclosure or destruction.
            </p>
          </section>

          <section>
            <h2>Your rights</h2>
            <p>
              You can ask to see the personal information we hold about you, correct it, have it deleted, or object to how we use it.
              You can also withdraw your consent at any time. You have the right to complain to the Nigeria Data Protection Commission.
            </p>
          </section>

          <section>
            <h2>Contact us</h2>
            <p>
              For any privacy question or request, email
              <a v-if="campaign.email" :href="`mailto:${campaign.email}`">{{ campaign.email }}</a>
              <template v-else>us through the <Link href="/contact">contact page</Link></template>.
            </p>
          </section>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

// Update this whenever the policy text changes
const LAST_UPDATED = '9 October 2026'

const page = usePage()
const campaign = computed(() => page.props.campaign || {})
</script>

<style scoped>
.policy h2 { font-size: 1.5rem; font-weight: 700; color: var(--color-dark); margin-bottom: 0.6rem; }
.policy ul { list-style: disc; padding-left: 1.5rem; margin: 0.6rem 0; }
.policy li { margin-bottom: 0.4rem; }
.policy p + p { margin-top: 0.6rem; }
.policy a { color: var(--color-primary); text-decoration: underline; word-break: break-all; }
.policy strong { color: var(--color-dark); }
</style>
