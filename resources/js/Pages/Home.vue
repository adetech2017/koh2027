<template>
  <AppLayout>
    <Head title="Kadri Obafemi Hamzat for Lagos" />

    <!-- Hero Slider -->
    <HeroSlider v-if="heroSlides.length" :slides="heroSlides" />

    <!-- Key actions, right under the hero -->
    <section aria-label="Get involved" class="relative z-10 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 md:-mt-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-6 md:py-0">
          <a
            :href="campaign.volunteerUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="group flex items-center gap-4 bg-white rounded-lg shadow-card hover:shadow-card-hover p-5 border-t-4 border-gold transition-all"
          >
            <span class="w-12 h-12 rounded-full bg-gold/15 text-gold-dark flex items-center justify-center flex-shrink-0" aria-hidden="true">
              <UserGroupIcon class="w-6 h-6" />
            </span>
            <span>
              <span class="block font-bold text-dark group-hover:text-primary">Volunteer</span>
              <span class="block text-sm text-body">Give an hour, a skill or a ride.</span>
            </span>
          </a>
          <a
            :href="campaign.voterRegistrationUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="group flex items-center gap-4 bg-white rounded-lg shadow-card hover:shadow-card-hover p-5 border-t-4 border-primary transition-all"
          >
            <span class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center flex-shrink-0" aria-hidden="true">
              <CheckBadgeIcon class="w-6 h-6" />
            </span>
            <span>
              <span class="block font-bold text-dark group-hover:text-primary">Register to vote</span>
              <span class="block text-sm text-body">Make sure your voice counts in 2027.</span>
            </span>
          </a>
          <Link
            href="/materials"
            class="group flex items-center gap-4 bg-white rounded-lg shadow-card hover:shadow-card-hover p-5 border-t-4 border-success transition-all"
          >
            <span class="w-12 h-12 rounded-full bg-success/10 text-success flex items-center justify-center flex-shrink-0" aria-hidden="true">
              <DocumentTextIcon class="w-6 h-6" />
            </span>
            <span>
              <span class="block font-bold text-dark group-hover:text-primary">Read the manifesto</span>
              <span class="block text-sm text-body">The Lagos Promise, pillar by pillar.</span>
            </span>
          </Link>
        </div>
      </div>
    </section>

    <!-- About Section -->
    <section class="py-16 md:py-24 bg-white" aria-labelledby="about-heading">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
          <div>
            <h2 id="about-heading" class="text-3xl md:text-4xl font-bold text-dark mb-6">About Kadri Obafemi Hamzat</h2>
            <p class="text-body leading-relaxed mb-6">
              A visionary leader with a proven track record of delivering results for Lagos State. Kadri brings decades of experience in public service and private sector excellence to the table.
            </p>
            <p class="text-body leading-relaxed mb-8">
              His commitment to inclusive growth, sustainable development, and transparent governance makes him the ideal choice for Lagos State's future.
            </p>
            <ul class="space-y-3 mb-8">
              <li v-for="point in aboutPoints" :key="point" class="flex items-center gap-3">
                <CheckCircleIcon class="w-6 h-6 text-success flex-shrink-0" aria-hidden="true" />
                <span>{{ point }}</span>
              </li>
            </ul>
            <Link href="/about" class="inline-flex items-center gap-2 font-semibold text-primary hover:gap-3 transition-all">
              Read his story <ArrowRightIcon class="w-4 h-4" aria-hidden="true" />
            </Link>
          </div>
          <div class="h-96 bg-light-gray rounded-lg overflow-hidden">
            <img :src="portraitImage" alt="Kadri Obafemi Hamzat" class="w-full h-full object-cover" loading="lazy" />
          </div>
        </div>
      </div>
    </section>

    <!-- Platforms Section -->
    <section v-if="pillars.length" class="py-16 md:py-24 bg-light-gray" aria-labelledby="pillars-heading">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 id="pillars-heading" class="text-3xl md:text-4xl font-bold text-dark mb-4">The Lagos Promise</h2>
          <p class="text-body max-w-2xl mx-auto">
            Our plan for a progressive Lagos rests on {{ pillarCountWord }} pillars.
            <template v-if="pillarCount > pillars.length">Here are the first {{ pillars.length }}.</template>
          </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <PlatformCard v-for="pillar in pillars" :key="pillar.id" :pillar="pillar" />
        </div>
        <div v-if="pillarCount > pillars.length" class="text-center mt-10">
          <Link href="/platforms" class="btn-secondary">See all {{ pillarCount }} pillars</Link>
        </div>
      </div>
    </section>

    <!-- Achievements Section -->
    <section v-if="stats.length" class="py-16 md:py-24 bg-white" aria-labelledby="stats-heading">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 id="stats-heading" class="text-3xl md:text-4xl font-bold text-dark mb-4">Our Achievements</h2>
          <p class="text-body max-w-2xl mx-auto">Tangible results that speak to our commitment to Lagos</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
          <StatCard v-for="stat in stats" :key="stat.id" :stat="stat" />
        </div>
      </div>
    </section>

    <!-- Events Section -->
    <section class="py-16 md:py-24 bg-light-gray" aria-labelledby="events-heading">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10">
          <div>
            <h2 id="events-heading" class="text-3xl md:text-4xl font-bold text-dark mb-2">Upcoming Events</h2>
            <p class="text-body">Join us on the campaign trail</p>
          </div>
          <Link v-if="events.length" href="/events" class="btn-primary self-start sm:self-auto">See all events</Link>
        </div>
        <div v-if="events.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <EventCard v-for="event in events" :key="event.id" :event="event" />
        </div>
        <div v-else class="bg-white rounded-lg shadow-card p-8 md:p-10 grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
          <div>
            <CalendarDaysIcon class="w-10 h-10 text-primary mb-3" aria-hidden="true" />
            <p class="text-xl font-bold text-dark mb-2">New events are being planned</p>
            <p class="text-body">
              Town halls, rallies and community meetings are announced here first. Subscribe and we'll tell you when one is coming to your area.
            </p>
            <Link v-if="pastEventCount" href="/events?when=past" class="inline-block mt-4 text-sm font-semibold text-primary hover:underline">
              See past events →
            </Link>
          </div>
          <NewsletterForm id="events-newsletter-email" />
        </div>
      </div>
    </section>

    <!-- News Section -->
    <section v-if="articles.length" class="py-16 md:py-24 bg-white" aria-labelledby="news-heading">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10">
          <div>
            <h2 id="news-heading" class="text-3xl md:text-4xl font-bold text-dark mb-2">Latest News</h2>
            <p class="text-body">Campaign updates and press releases</p>
          </div>
          <Link href="/news" class="btn-primary self-start sm:self-auto">Read all news</Link>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <NewsCard v-for="article in articles" :key="article.id" :article="article" />
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section v-if="testimonials.length" class="py-16 md:py-24 bg-light-gray" aria-labelledby="testimonials-heading">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 id="testimonials-heading" class="text-3xl md:text-4xl font-bold text-dark mb-4">What Lagosians Say</h2>
          <p class="text-body max-w-2xl mx-auto">Hear from people who believe in our vision</p>
        </div>
        <!-- Swiper's Vue component takes each setting as its own prop (an `options` object is ignored) -->
        <Swiper
          :modules="testimonialModules"
          :slides-per-view="1"
          :space-between="24"
          :breakpoints="{ 768: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }"
          :autoplay="{ delay: 8000, disableOnInteraction: false, pauseOnMouseEnter: true }"
          :pagination="{ clickable: true }"
          :a11y="{ enabled: true }"
          :loop="testimonials.length > 3"
          :auto-height="false"
          class="testimonials-slider !pb-12"
        >
          <SwiperSlide v-for="testimonial in testimonials" :key="testimonial.id" class="!h-auto">
            <TestimonialCard :testimonial="testimonial" />
          </SwiperSlide>
        </Swiper>
      </div>
    </section>

    <!-- Gallery Section -->
    <section v-if="galleryImages.length" class="py-16 md:py-24 bg-white" aria-labelledby="gallery-heading">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10">
          <div>
            <h2 id="gallery-heading" class="text-3xl md:text-4xl font-bold text-dark mb-2">Campaign Gallery</h2>
            <p class="text-body">Moments from the campaign trail</p>
          </div>
          <Link href="/gallery" class="btn-primary self-start sm:self-auto">View full gallery</Link>
        </div>
        <!-- One row of cards: four across on larger screens, a swipeable row on phones -->
        <ul class="flex md:grid md:grid-cols-4 gap-4 overflow-x-auto md:overflow-visible snap-x snap-mandatory -mx-4 px-4 md:mx-0 md:px-0 pb-2 md:pb-0 scrollbar-none">
          <li
            v-for="(image, i) in galleryRow"
            :key="image.id"
            class="snap-start flex-shrink-0 w-[75%] sm:w-[45%] md:w-auto"
          >
            <button
              type="button"
              class="group block w-full text-left bg-white rounded-lg overflow-hidden shadow-card hover:shadow-card-hover transition-shadow focus:outline-none focus-visible:ring-4 focus-visible:ring-gold"
              :aria-label="isMoreTile(i) ? `View all ${galleryImages.length} photos` : `View photo${image.alt_text ? ': ' + image.alt_text : ''}`"
              @click="lightbox.open(i)"
            >
              <span class="relative block aspect-[4/3] bg-light-gray overflow-hidden">
                <img
                  :src="image.image_url"
                  :alt="image.alt_text || ''"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                  loading="lazy"
                />
                <span
                  v-if="isMoreTile(i)"
                  class="absolute inset-0 bg-dark/60 flex flex-col items-center justify-center text-white"
                  aria-hidden="true"
                >
                  <span class="text-3xl font-bold">+{{ galleryImages.length - galleryRow.length }}</span>
                  <span class="text-sm">more photos</span>
                </span>
              </span>
              <span v-if="caption(image)" class="block px-4 py-3 text-sm text-body truncate">{{ caption(image) }}</span>
            </button>
          </li>
        </ul>
        <Lightbox ref="lightbox" :images="galleryImages" />
      </div>
    </section>

    <!-- Manifesto -->
    <section class="py-16 md:py-24 bg-light-gray" aria-labelledby="manifesto-heading">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-card p-8 md:p-12 grid grid-cols-1 md:grid-cols-5 gap-8 items-center">
          <div class="md:col-span-3">
            <p class="text-sm font-semibold uppercase tracking-wider text-gold-dark mb-2">The Lagos Promise</p>
            <h2 id="manifesto-heading" class="text-3xl md:text-4xl font-bold text-dark mb-4">Read the manifesto</h2>
            <p class="text-body leading-relaxed">
              The full manifesto and a short document for each of the {{ pillarCountWord }} pillars, free to download and share.
              Got a question? Ask the Manifesto Assistant and get an answer from the documents themselves.
            </p>
          </div>
          <div class="md:col-span-2 flex flex-col gap-3">
            <Link href="/materials" class="btn-primary text-center">Download the manifesto</Link>
            <button type="button" class="btn-secondary" @click="openAssistant">Ask the Manifesto Assistant</button>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 md:py-24 bg-primary text-white" aria-labelledby="join-heading">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 id="join-heading" class="text-3xl md:text-4xl font-bold mb-6">Join the Movement</h2>
        <p class="text-lg text-gray-100 mb-10 max-w-2xl mx-auto">Be part of building a better Lagos. Volunteer, make sure you're registered to vote, and get campaign news in your inbox.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-10">
          <a :href="campaign.volunteerUrl" target="_blank" rel="noopener noreferrer" class="btn-gold">Volunteer</a>
          <a :href="campaign.voterRegistrationUrl" target="_blank" rel="noopener noreferrer" class="btn-secondary bg-white text-primary hover:bg-gray-100">Voter Registration</a>
        </div>
        <div class="max-w-md mx-auto">
          <NewsletterForm id="home-newsletter-email" />
        </div>
      </div>
    </section>

    <!-- Social Media Feeds -->
    <SocialFeedsSection />
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay, Pagination, A11y } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/pagination'
import {
  ArrowRightIcon,
  CalendarDaysIcon,
  CheckBadgeIcon,
  CheckCircleIcon,
  DocumentTextIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import AppLayout from '@/Layouts/AppLayout.vue'
import HeroSlider from '@/Components/HeroSlider.vue'
import PlatformCard from '@/Components/PlatformCard.vue'
import StatCard from '@/Components/StatCard.vue'
import EventCard from '@/Components/EventCard.vue'
import NewsCard from '@/Components/NewsCard.vue'
import TestimonialCard from '@/Components/TestimonialCard.vue'
import SocialFeedsSection from '@/Components/SocialFeedsSection.vue'
import NewsletterForm from '@/Components/NewsletterForm.vue'
import Lightbox from '@/Components/Lightbox.vue'

const props = defineProps({
  heroSlides: { type: Array, default: () => [] },
  pillars: { type: Array, default: () => [] },
  pillarCount: { type: Number, default: 0 },
  stats: { type: Array, default: () => [] },
  events: { type: Array, default: () => [] },
  pastEventCount: { type: Number, default: 0 },
  articles: { type: Array, default: () => [] },
  testimonials: { type: Array, default: () => [] },
  galleryImages: { type: Array, default: () => [] },
})

const page = usePage()
const campaign = computed(() => page.props.campaign || {})

const portraitImage = '/storage/personal/koh-2027-4.jpeg'
const aboutPoints = ['30+ Years of Leadership Experience', 'Proven Track Record of Achievements', 'Vision for Modern Lagos']

const testimonialModules = [Autoplay, Pagination, A11y]

// The gallery shows a single row of four; the viewer still steps through every featured photo
const GALLERY_ROW = 4
const galleryRow = computed(() => props.galleryImages.slice(0, GALLERY_ROW))
const isMoreTile = (i) => i === GALLERY_ROW - 1 && props.galleryImages.length > GALLERY_ROW
// Older uploads stored the filename as the title; never show that as a caption
const isFilename = (text) => /\.(jpe?g|png|gif|webp)$/i.test(text || '')
const caption = (image) => [isFilename(image.title) ? null : image.title, image.event_label].filter(Boolean).join(' · ')
const lightbox = ref(null)

const NUMBER_WORDS = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten']
const pillarCountWord = computed(() => NUMBER_WORDS[props.pillarCount] || String(props.pillarCount))

const openAssistant = () => window.dispatchEvent(new CustomEvent('open-manifesto-chat'))
</script>

<style scoped>
/* Hide the scrollbar on the swipeable gallery row (still scrollable by touch, wheel and keyboard) */
.scrollbar-none {
  scrollbar-width: none;
}
.scrollbar-none::-webkit-scrollbar {
  display: none;
}

/* Campaign-coloured pagination dots for the testimonials slider */
.testimonials-slider :deep(.swiper-pagination-bullet) {
  background: var(--color-primary);
  opacity: 0.3;
  width: 10px;
  height: 10px;
}
.testimonials-slider :deep(.swiper-pagination-bullet-active) {
  opacity: 1;
}
</style>
