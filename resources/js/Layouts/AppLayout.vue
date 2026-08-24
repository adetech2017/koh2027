<template>
  <div class="min-h-screen flex flex-col bg-white">
    <Navigation />
    <FlashMessage />
    <main class="flex-grow">
      <slot />
    </main>
    <Footer />
    <ManifestoChat />
  </div>
</template>

<script setup>
import Navigation from '@/Components/Navigation.vue'
import Footer from '@/Components/Footer.vue'
import FlashMessage from '@/Components/FlashMessage.vue'
import ManifestoChat from '@/Components/ManifestoChat.vue'
</script>
