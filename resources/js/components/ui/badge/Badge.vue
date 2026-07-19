<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import type { BadgeVariants } from '.'
import { reactiveOmit } from '@vueuse/core'
import { Primitive } from 'reka-ui'
import { cn } from '@/lib/utils'
import { badgeVariants } from '.'

interface Props {
  variant?: BadgeVariants['variant']
  class?: HTMLAttributes['class']
  as?: string
  asChild?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  as: 'span',
})

const delegatedProps = reactiveOmit(props, 'class')
</script>

<template>
  <Primitive
    data-slot="badge"
    :data-variant="variant"
    :class="cn(badgeVariants({ variant }), props.class)"
    v-bind="delegatedProps"
  >
    <slot />
  </Primitive>
</template>