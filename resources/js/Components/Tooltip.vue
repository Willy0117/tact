<template>
  <div
    class="absolute left-full ml-2 top-1/2 -translate-y-1/2
           bg-gray-900 text-white text-xs px-2 py-1 rounded
           opacity-0 group-hover:opacity-100 whitespace-nowrap shadow-lg"
  >
    {{ text }}
  </div>
</template>

<script setup>
defineProps({
  text: String
})
</script>
