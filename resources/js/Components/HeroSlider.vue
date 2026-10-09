<template>
  <section class="relative w-full h-[calc(100svh-4rem)] md:h-[calc(100svh-5rem)] min-h-[28rem] max-h-[56rem] overflow-hidden bg-dark" aria-roledescription="carousel" aria-label="Campaign highlights">
    <!-- Progress Bar -->
    <div class="absolute top-0 left-0 h-1 bg-gold z-10" :style="{ width: progressWidth + '%' }" aria-hidden="true"></div>

    <Swiper
      ref="swiperRef"
      :modules="modules"
      :slides-per-view="1"
      :effect="'fade'"
      :fade-effect="{ crossFade: true }"
      :loop="true"
      :autoplay="{ delay: 6000, disableOnInteraction: false, pauseOnMouseEnter: true }"
      :speed="300"
      :touch-ratio="1"
      :resistance-ratio="0.85"
      @swiper="onSwiper"
      @slide-change="onSlideChange"
      @autoplay-time-left="onTimeLeft"
      class="w-full h-full"
    >
      <SwiperSlide v-for="(slide, idx) in slides" :key="idx" class="relative" role="group" aria-roledescription="slide" :aria-label="`${idx + 1} of ${slides.length}`">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0">
          <img
            :src="slide.image_url || '/placeholder-hero.jpg'"
            :loading="idx === 0 ? 'eager' : 'lazy'"
            :fetchpriority="idx === 0 ? 'high' : 'auto'"
            :alt="slide.image_alt"
            class="w-full h-full object-cover"
          />
          <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/30 to-transparent"></div>
        </div>

        <!-- Content -->
        <div class="relative h-full flex items-center">
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full md:w-1/2">
            <!-- Tagline -->
            <div class="mb-4 animate-fade-in" :style="{ animationDelay: '0.2s' }">
              <span class="inline-block text-gold text-xs md:text-sm font-semibold uppercase tracking-widest border-b-2 border-gold pb-2">
                {{ slide.tagline }}
              </span>
            </div>

            <!-- Headline -->
            <component
              :is="idx === 0 ? 'h1' : 'h2'"
              class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight max-w-2xl animate-slide-up"
              :style="{ animationDelay: '0.5s' }"
            >
              {{ slide.headline }}
            </component>

            <!-- Subtitle -->
            <p
              class="text-lg md:text-xl text-gray-200 mb-8 max-w-xl leading-relaxed animate-fade-in"
              :style="{ animationDelay: '0.9s' }"
            >
              {{ slide.subtitle }}
            </p>

            <!-- CTA Button -->
            <div v-if="slide.cta_text && slide.cta_url" class="animate-fade-in" :style="{ animationDelay: '1.2s' }">
              <a
                :href="slide.cta_url"
                data-track="cta_click"
                :data-track-label="`Hero: ${slide.cta_text}`"
                v-bind="isExternal(slide.cta_url) ? { target: '_blank', rel: 'noopener noreferrer' } : {}"
                :class="[
                  'inline-block px-8 py-4 rounded font-semibold text-lg transition-all hover:scale-105 active:scale-95',
                  slide.cta_style === 'primary'
                    ? 'btn-primary'
                    : 'btn-gold',
                ]"
              >
                {{ slide.cta_text }}
              </a>
            </div>
          </div>
        </div>
      </SwiperSlide>

      <!-- Navigation Arrows -->
      <button
        @click="previousSlide"
        aria-label="Previous slide"
        class="absolute left-6 top-1/2 -translate-y-1/2 z-20 w-12 h-12 bg-white bg-opacity-70 hover:bg-opacity-90 rounded-full flex items-center justify-center text-dark transition-all hidden md:flex"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <button
        @click="nextSlide"
        aria-label="Next slide"
        class="absolute right-6 top-1/2 -translate-y-1/2 z-20 w-12 h-12 bg-white bg-opacity-70 hover:bg-opacity-90 rounded-full flex items-center justify-center text-dark transition-all hidden md:flex"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>

      <!-- Pagination Dots -->
      <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-3 z-20">
        <button
          v-for="(slide, idx) in slides"
          :key="idx"
          @click="goToSlide(idx)"
          :class="[
            'rounded-full transition-all',
            idx === currentSlide
              ? 'w-4 h-4 bg-gold'
              : 'w-2 h-2 bg-white bg-opacity-50 hover:bg-opacity-75',
          ]"
          :aria-label="`Go to slide ${idx + 1}`"
          :aria-current="idx === currentSlide ? 'true' : undefined"
        ></button>
      </div>
    </Swiper>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay, EffectFade } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/effect-fade'

defineProps({
  slides: {
    type: Array,
    required: true,
  },
})

const modules = [Autoplay, EffectFade]

const isExternal = (url) => /^https?:\/\//i.test(url || '')
const swiperRef = ref(null)
const currentSlide = ref(0)
const progressWidth = ref(0)
let swiperInstance = null

const onSwiper = (swiper) => {
  swiperInstance = swiper
}

const onSlideChange = (swiper) => {
  currentSlide.value = swiper.realIndex
}

// Swiper reports the remaining fraction of the current slide's delay; it stops while paused
const onTimeLeft = (swiper, timeLeft, progress) => {
  progressWidth.value = Math.min(100, Math.max(0, (1 - progress) * 100))
}

const previousSlide = () => {
  if (swiperInstance) {
    swiperInstance.slidePrev()
  }
}

const nextSlide = () => {
  if (swiperInstance) {
    swiperInstance.slideNext()
  }
}

const goToSlide = (index) => {
  if (swiperInstance) {
    swiperInstance.slideToLoop(index)
  }
}
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slide-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.8s ease-out forwards;
  opacity: 0;
}

.animate-slide-up {
  animation: slide-up 0.8s ease-out forwards;
  opacity: 0;
}
</style>
