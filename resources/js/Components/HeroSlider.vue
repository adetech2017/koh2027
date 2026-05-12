<template>
  <div class="relative w-full h-screen overflow-hidden bg-dark">
    <!-- Progress Bar -->
    <div class="absolute top-0 left-0 h-1 bg-gold transition-all duration-100 z-10" :style="{ width: progressWidth + '%' }"></div>

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
      class="w-full h-full"
    >
      <SwiperSlide v-for="(slide, idx) in slides" :key="idx" class="relative">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0">
          <img
            :src="slide.image_url || '/placeholder-hero.jpg'"
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
            <h1
              class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight max-w-2xl animate-slide-up"
              :style="{ animationDelay: '0.5s' }"
            >
              {{ slide.headline }}
            </h1>

            <!-- Subtitle -->
            <p
              class="text-lg md:text-xl text-gray-200 mb-8 max-w-xl leading-relaxed animate-fade-in"
              :style="{ animationDelay: '0.9s' }"
            >
              {{ slide.subtitle }}
            </p>

            <!-- CTA Button -->
            <div class="animate-fade-in" :style="{ animationDelay: '1.2s' }">
              <button
                :class="[
                  'inline-block px-8 py-4 rounded font-semibold text-lg transition-all hover:scale-105 active:scale-95',
                  slide.cta_style === 'primary'
                    ? 'btn-primary'
                    : 'btn-gold',
                ]"
              >
                {{ slide.cta_text }}
              </button>
            </div>
          </div>
        </div>
      </SwiperSlide>

      <!-- Navigation Arrows -->
      <button
        @click="previousSlide"
        class="absolute left-6 top-1/2 -translate-y-1/2 z-20 w-12 h-12 bg-white bg-opacity-70 hover:bg-opacity-90 rounded-full flex items-center justify-center text-dark transition-all hidden md:flex"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <button
        @click="nextSlide"
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
        ></button>
      </div>
    </Swiper>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
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
const swiperRef = ref(null)
const currentSlide = ref(0)
const progressWidth = ref(0)
let progressInterval = null
let swiperInstance = null

const onSwiper = (swiper) => {
  swiperInstance = swiper
  startProgress()
}

const onSlideChange = (swiper) => {
  currentSlide.value = swiper.realIndex
  progressWidth.value = 0
  clearInterval(progressInterval)
  startProgress()
}

const startProgress = () => {
  let elapsed = 0
  const duration = 6000
  const interval = 50

  progressInterval = setInterval(() => {
    elapsed += interval
    progressWidth.value = (elapsed / duration) * 100
    if (elapsed >= duration) {
      clearInterval(progressInterval)
    }
  }, interval)
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

onMounted(() => {
  startProgress()
})
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
