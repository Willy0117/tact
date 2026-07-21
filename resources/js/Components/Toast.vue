<script setup>
import { watchEffect, nextTick } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { toast, Toaster } from 'vue-sonner'
import 'vue-sonner/style.css'

const page = usePage()

let last = ''

watchEffect(async () => {
  const flash = page.props.flash

  const message =
    flash?.success ??
    flash?.error ??
    flash?.warning

  if (!message || message === last) return

  last = message

  await nextTick()

  if (flash?.success) {
    toast.success(flash.success)
  } else if (flash?.error) {
    toast.error(flash.error)
  } else if (flash?.warning) {
    toast.warning(flash.warning)
  }
})
</script>

<template>
  <Toaster
    richColors
    position="top-right"
    :duration="3000"
  />
</template>