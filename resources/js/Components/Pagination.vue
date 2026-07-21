<template>
  <div class="flex justify-between items-center mt-4">
    <!-- 左端: Previous + ページ番号 + Next -->
    <div class="flex space-x-1 items-center">
      <button
        :disabled="paginator.current_page === 1"
        @click="changePage(paginator.current_page - 1)"
        class="w-8 h-8 flex items-center justify-center rounded-full border hover:bg-gray-100 disabled:opacity-40 disabled:hover:bg-transparent transition-colors"
      >
        «
      </button>

      <template v-for="page in displayPages" :key="page.key">
        <span
          v-if="page.type === 'page'"
          @click="changePage(page.number)"
          :class="[
            'w-8 h-8 flex items-center justify-center rounded-full cursor-pointer text-sm transition-colors',
            page.number === paginator.current_page
              ? 'bg-slate-800 text-white font-bold'
              : 'border hover:bg-gray-100'
          ]"
        >
          {{ page.number }}
        </span>
        <span v-else class="w-8 h-8 flex items-center justify-center text-gray-400">…</span>
      </template>

      <button
        :disabled="paginator.current_page === paginator.last_page"
        @click="changePage(paginator.current_page + 1)"
        class="w-8 h-8 flex items-center justify-center rounded-full border hover:bg-gray-100 disabled:opacity-40 disabled:hover:bg-transparent transition-colors"
      >
        »
      </button>
    </div>

    <!-- 右端: 表示件数 -->
    <div class="text-gray-600 text-sm">
      {{ startItem }}-{{ endItem }} / {{ paginator.total }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  paginator: Object,
  onPageChange: Function
})

const startItem = computed(() => props.paginator.per_page * (props.paginator.current_page - 1) + 1)
const endItem = computed(() => Math.min(props.paginator.per_page * props.paginator.current_page, props.paginator.total))

const displayPages = computed(() => {
  const total = props.paginator.last_page
  const current = props.paginator.current_page
  const pages = []

  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push({ type: 'page', number: i, key: i })
  } else {
    pages.push({ type: 'page', number: 1, key: 1 })

    if (current > 4) pages.push({ type: 'ellipsis', key: 'start-ellipsis' })

    for (let i = Math.max(2, current - 2); i <= Math.min(total - 1, current + 2); i++) {
      pages.push({ type: 'page', number: i, key: i })
    }

    if (current < total - 3) pages.push({ type: 'ellipsis', key: 'end-ellipsis' })

    pages.push({ type: 'page', number: total, key: total })
  }

  return pages
})

function changePage(page) {
  props.onPageChange(page)
}
</script>