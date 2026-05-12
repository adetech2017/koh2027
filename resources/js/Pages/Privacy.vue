<template>
  <AppLayout>
    <Head title="Privacy Policy" />
    <div class="min-h-screen bg-white py-16 md:py-24">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl md:text-5xl font-bold text-dark mb-12">Privacy Policy</h1>
        <div class="prose prose-sm max-w-none space-y-6 text-body">
          <h2 class="text-2xl font-bold text-dark">Introduction</h2>
          <p>Welcome to the KOH 2027 Campaign website. We are committed to protecting your privacy and ensuring you have a positive experience on our site.</p>

          <h2 class="text-2xl font-bold text-dark">Information We Collect</h2>
          <p>We collect information you voluntarily provide through contact forms, newsletter subscriptions, event registrations, and volunteer applications. This may include your name, email address, phone number, and location.</p>

          <h2 class="text-2xl font-bold text-dark">How We Use Your Information</h2>
          <p>Your information is used to:</p>
          <ul class="list-disc list-inside space-y-2">
            <li>Respond to your inquiries</li>
            <li>Send campaign updates and news</li>
            <li>Process event registrations</li>
            <li>Manage volunteer applications</li>
            <li>Improve our website and services</li>
          </ul>

          <h2 class="text-2xl font-bold text-dark">Data Security</h2>
          <p>We implement appropriate security measures to protect your personal information from unauthorized access, alteration, disclosure, or destruction.</p>

          <h2 class="text-2xl font-bold text-dark">Your Rights</h2>
          <p>You have the right to access, update, or delete your personal information at any time. You can also unsubscribe from our mailing list by clicking the unsubscribe link in any email.</p>

          <h2 class="text-2xl font-bold text-dark">Contact Us</h2>
          <p>If you have any questions about this privacy policy or our privacy practices, please contact us at <a href="mailto:campaign@kohforlakosstate.com" class="text-primary hover:underline">campaign@kohforlakosstate.com</a>.</p>

          <p class="text-sm text-gray-500 mt-12">Last updated: January 2026</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
</script>
