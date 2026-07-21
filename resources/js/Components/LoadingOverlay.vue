<template>
  <Teleport to="body">
    <div
      v-if="isLoading"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/40 backdrop-blur-[1px]"
    >
      <div class="w-10 h-10 border-4 border-slate-300 border-t-slate-800 rounded-full animate-spin"></div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const isLoading = ref(false)

router.on('start', () => {
  isLoading.value = true
})
router.on('finish', () => {
  isLoading.value = false
})
</script>