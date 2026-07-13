<template>
  <AppLayout>
    <Head title="Home" />

    <!-- Hero Slider -->
    <HeroSlider :slides="heroSlides" />

    <!-- About Section -->
    <section class="py-16 md:py-24 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
          <div>
            <h2 class="text-3xl md:text-4xl font-bold text-dark mb-6">About Kadri Obafemi Hamzat</h2>
            <p class="text-body leading-relaxed mb-6">
              A visionary leader with a proven track record of delivering results for Lagos State. Kadri brings decades of experience in public service and private sector excellence to the table.
            </p>
            <p class="text-body leading-relaxed mb-8">
              His commitment to inclusive growth, sustainable development, and transparent governance makes him the ideal choice for Lagos State's future.
            </p>
            <ul class="space-y-3">
              <li class="flex items-center space-x-3">
                <svg class="w-6 h-6 text-success" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>30+ Years of Leadership Experience</span>
              </li>
              <li class="flex items-center space-x-3">
                <svg class="w-6 h-6 text-success" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>Proven Track Record of Achievements</span>
              </li>
              <li class="flex items-center space-x-3">
                <svg class="w-6 h-6 text-success" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>Vision for Modern Lagos</span>
              </li>
            </ul>
          </div>
          <div class="h-96 bg-light-gray rounded-lg overflow-hidden flex items-center justify-center text-gray-400">
            <img :src="portraitImage" alt="Kadri Obafemi Hamzat" class="w-full h-full object-cover" />
          </div>
        </div>
      </div>
    </section>

    <!-- Platforms Section -->
    <section class="py-16 md:py-24 bg-light-gray">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl md:text-4xl font-bold text-dark mb-4">Campaign Platforms</h2>
          <p class="text-body max-w-2xl mx-auto">Our vision for a progressive Lagos is built on three pillars of excellence</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <PlatformCard v-for="pillar in pillars" :key="pillar.id" :pillar="pillar" />
        </div>
      </div>
    </section>

    <!-- Achievements Section -->
    <section class="py-16 md:py-24 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl md:text-4xl font-bold text-dark mb-4">Our Achievements</h2>
          <p class="text-body max-w-2xl mx-auto">Tangible results that speak to our commitment to Lagos</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <StatCard v-for="stat in stats" :key="stat.id" :stat="stat" />
        </div>
      </div>
    </section>

    <!-- Events Section -->
    <section class="py-16 md:py-24 bg-light-gray">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-12">
          <div>
            <h2 class="text-3xl md:text-4xl font-bold text-dark mb-2">Upcoming Events</h2>
            <p class="text-body">Join us on the campaign trail</p>
          </div>
          <Link href="/events" class="btn-primary">See All Events</Link>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <EventCard v-for="event in events" :key="event.id" :event="event" />
        </div>
      </div>
    </section>

    <!-- News Section -->
    <section class="py-16 md:py-24 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-12">
          <div>
            <h2 class="text-3xl md:text-4xl font-bold text-dark mb-2">Latest News</h2>
            <p class="text-body">Campaign updates and press releases</p>
          </div>
          <Link href="/news" class="btn-primary">Read All News</Link>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <NewsCard v-for="article in articles" :key="article.id" :article="article" />
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-16 md:py-24 bg-light-gray">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl md:text-4xl font-bold text-dark mb-4">What Lagosians Say</h2>
          <p class="text-body max-w-2xl mx-auto">Hear from people who believe in our vision</p>
        </div>
        <Swiper :options="{ slidesPerView: 1, spaceBetween: 24, breakpoints: { 768: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }, autoplay: { delay: 8000 }, loop: true }" class="testimonials-slider">
          <SwiperSlide v-for="testimonial in testimonials" :key="testimonial.id">
            <TestimonialCard :testimonial="testimonial" />
          </SwiperSlide>
        </Swiper>
      </div>
    </section>

    <!-- Gallery Section -->
    <section class="py-16 md:py-24 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-12">
          <div>
            <h2 class="text-3xl md:text-4xl font-bold text-dark mb-2">Campaign Gallery</h2>
            <p class="text-body">Moments from the campaign trail</p>
          </div>
          <Link href="/gallery" class="btn-primary">View Full Gallery</Link>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div v-for="image in galleryImages" :key="image.id" class="h-48 bg-light-gray rounded-lg overflow-hidden cursor-pointer group">
            <img
              v-if="image.image_url"
              :src="image.image_url"
              :alt="image.alt_text"
              class="w-full h-full object-cover group-hover:scale-110 transition-transform"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
              <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 md:py-24 bg-primary text-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">Join the Movement</h2>
        <p class="text-lg text-gray-100 mb-12 max-w-2xl mx-auto">Be part of building a better Lagos. Subscribe to our newsletter, volunteer, or donate to support the campaign.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
          <a href="https://hamzatforlagos.com/volunteer" target="_blank" rel="noopener noreferrer" class="btn-gold">Volunteer</a>
          <a href="https://hamzatforlagos.com/register-voter" target="_blank" rel="noopener noreferrer" class="btn-secondary bg-white text-primary hover:bg-gray-100">Voter Registration</a>
        </div>
        <form @submit.prevent="subscribeNewsletterHome" class="max-w-md mx-auto">
          <div class="flex gap-2">
            <input
              v-model="newsletterForm.email"
              type="email"
              placeholder="Enter your email"
              class="flex-grow px-4 py-3 rounded bg-white text-dark placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gold"
              required
            />
            <button type="submit" class="btn-gold">Subscribe</button>
          </div>
        </form>
      </div>
    </section>

    <!-- Materials Teaser -->
    <section class="py-16 md:py-24 bg-light-gray">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl md:text-4xl font-bold text-dark mb-4">Manifesto</h2>
          <p class="text-body mb-8">Download brochures, policy documents, and campaign graphics</p>
          <Link href="/materials" class="btn-primary">Access All Materials</Link>
        </div>
      </div>
    </section>

    <!-- Social Media Feeds -->
    <SocialFeedsSection />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import HeroSlider from '@/Components/HeroSlider.vue'
import PlatformCard from '@/Components/PlatformCard.vue'
import StatCard from '@/Components/StatCard.vue'
import EventCard from '@/Components/EventCard.vue'
import NewsCard from '@/Components/NewsCard.vue'
import TestimonialCard from '@/Components/TestimonialCard.vue'
import SocialFeedsSection from '@/Components/SocialFeedsSection.vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay } from 'swiper/modules'

const portraitImage = '/storage/personal/koh-2027-4.jpeg'

defineProps({
  heroSlides: Array,
  pillars: Array,
  stats: Array,
  events: Array,
  articles: Array,
  testimonials: Array,
  galleryImages: Array,
})

const newsletterForm = useForm({
  email: '',
  name: '',
})

const subscribeNewsletterHome = () => {
  newsletterForm.post(route('newsletter.subscribe'), {
    onSuccess: () => {
      newsletterForm.reset()
    },
  })
}
</script>
