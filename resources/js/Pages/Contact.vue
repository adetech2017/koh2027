<template>
  <AppLayout>
    <Head title="Contact" />
    <div class="min-h-screen bg-white py-16 md:py-24">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl md:text-5xl font-bold text-dark mb-12 text-center">Get in Touch</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
          <div>
            <h2 class="text-2xl font-bold text-dark mb-6">Contact Information</h2>
            <div class="space-y-6">
              <div>
                <h3 class="font-semibold text-dark mb-2">Address</h3>
                <p class="text-body">Lagos State, Nigeria</p>
              </div>
              <div>
                <h3 class="font-semibold text-dark mb-2">Email</h3>
                <a href="mailto:campaign@kohforlakosstate.com" class="text-primary hover:underline">campaign@kohforlakosstate.com</a>
              </div>
              <div>
                <h3 class="font-semibold text-dark mb-2">Phone</h3>
                <p class="text-body">+234 (0) XXX XXX XXXX</p>
              </div>
              <div>
                <h3 class="font-semibold text-dark mb-4">Follow Us</h3>
                <div class="flex gap-4">
                  <a href="https://facebook.com" target="_blank" class="text-primary hover:text-primary-dark">Facebook</a>
                  <a href="https://twitter.com" target="_blank" class="text-primary hover:text-primary-dark">Twitter</a>
                  <a href="https://instagram.com" target="_blank" class="text-primary hover:text-primary-dark">Instagram</a>
                </div>
              </div>
            </div>
          </div>
          <div>
            <form @submit.prevent="submitForm" class="space-y-6">
              <div>
                <label class="block text-dark font-medium mb-2">Name</label>
                <input v-model="form.name" type="text" placeholder="Your name" required class="w-full px-4 py-3 bg-white text-dark border-2 border-gray-300 rounded placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
              </div>
              <div>
                <label class="block text-dark font-medium mb-2">Email</label>
                <input v-model="form.email" type="email" placeholder="your@email.com" required class="w-full px-4 py-3 bg-white text-dark border-2 border-gray-300 rounded placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
              </div>
              <div>
                <label class="block text-dark font-medium mb-2">Subject</label>
                <input v-model="form.subject" type="text" placeholder="Message subject" required class="w-full px-4 py-3 bg-white text-dark border-2 border-gray-300 rounded placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
              </div>
              <div>
                <label class="block text-dark font-medium mb-2">Message</label>
                <textarea v-model="form.message" placeholder="Your message..." required rows="6" class="w-full px-4 py-3 bg-white text-dark border-2 border-gray-300 rounded placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"></textarea>
              </div>
              <button type="submit" class="btn-primary w-full">Send Message</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'

const form = useForm({
  name: '',
  email: '',
  subject: '',
  message: '',
})

const submitForm = () => {
  form.post(route('contact.store'))
}
</script>
