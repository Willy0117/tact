<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: [String, Number, null],
  apiUrl: { type: String, required: true },
  placeholder: { type: String, default: '' },
  initialItem: { type: Object, default: null },
  minLength: { type: Number, default: 1 },
  debounce: { type: Number, default: 250 },
  disabled: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'update:name'])

const wrapper = ref(null)
const inputRef = ref(null)
const keyword = ref('')
const items = ref([])
const show = ref(false)
const loading = ref(false)
const selectedIndex = ref(-1)
const justSelected = ref(false)

let timer = null

onMounted(() => {
  if (props.initialItem) {
    keyword.value = props.initialItem.name
  }
  document.addEventListener('click', handleOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutside)
})

function handleOutside(e) {
  if (!wrapper.value?.contains(e.target)) {
    close()
  }
}

function close() {
  show.value = false
  selectedIndex.value = -1
}

async function fetchItems(val = '') {
  loading.value = true
  try {
    const res = await axios.get(props.apiUrl, { params: { q: val } })
    items.value = res.data
    show.value = true
    selectedIndex.value = 0
  } catch (e) {
    items.value = []
  } finally {
    loading.value = false
  }
}

watch(keyword, (val) => {
  if (timer) clearTimeout(timer)
  if (!val || val.length < props.minLength) {
    items.value = []
    return
  }
  timer = setTimeout(() => { fetchItems(val) }, props.debounce)
})

function selectItem(item) {
  justSelected.value = true
  keyword.value = item.name
  emit('update:modelValue', item.id)
  emit('update:name', item.name)
  close()
}

function clear() {
  justSelected.value = true
  keyword.value = ''
  emit('update:modelValue', null)
  emit('update:name', '')
  items.value = []
}

function onKeydown(e) {
  if (!show.value && e.key === 'ArrowDown') {
    show.value = true
    return
  }
  if (!items.value.length) return

  switch (e.key) {
    case 'ArrowDown':
      e.preventDefault()
      selectedIndex.value = (selectedIndex.value + 1) % items.value.length
      scrollIntoView()
      break
    case 'ArrowUp':
      e.preventDefault()
      selectedIndex.value = (selectedIndex.value - 1 + items.value.length) % items.value.length
      scrollIntoView()
      break
    case 'Enter':
      e.preventDefault()
      if (items.value[selectedIndex.value]) {
        selectItem(items.value[selectedIndex.value])
      }
      break
    case 'Escape':
      close()
      break
  }
}

function scrollIntoView() {
  nextTick(() => {
    const list = wrapper.value?.querySelector('.autocomplete-list')
    const active = list?.children[selectedIndex.value]
    active?.scrollIntoView({ block: 'nearest' })
  })
}

watch(
  () => props.modelValue,
  async (val) => {
    if (justSelected.value) {
      justSelected.value = false
      return
    }

    if (!val) {
      keyword.value = ''
      return
    }

    if (items.value.find(i => i.id === val)) return

    if (props.initialItem && props.initialItem.id === val) {
      keyword.value = props.initialItem.name
      emit('update:name', props.initialItem.name)
      return
    }

    try {
      const res = await axios.get(`${props.apiUrl}/${val}`)
      keyword.value = res.data.name
      emit('update:name', res.data.name)
    } catch {
      keyword.value = ''
    }
  },
  { immediate: true }
)
</script>

<template>
  <div ref="wrapper" class="relative w-full">
    <div class="relative">
      <input
        ref="inputRef"
        type="text"
        v-model="keyword"
        :placeholder="placeholder"
        :disabled="disabled"
        class="w-full border border-gray-300 rounded-md px-3 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-300 transition"
        @focus="fetchItems(keyword)"
        @keydown="onKeydown"
      />
      <button
        v-if="keyword && !disabled"
        type="button"
        @click="clear"
        class="absolute right-2 top-2 text-gray-400 hover:text-black"
      >
        ×
      </button>
    </div>

    <div
      v-if="show"
      class="autocomplete-list absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-72 overflow-auto"
    >
      <div v-if="loading" class="px-3 py-2 text-sm text-gray-500">
        <slot name="loading">読み込み中...</slot>
      </div>
      <div v-else-if="!items.length" class="px-3 py-2 text-sm text-gray-400">
        <slot name="no-data">データが見つかりません</slot>
      </div>
      <div
        v-for="(item, index) in items"
        :key="item.id"
        @click="selectItem(item)"
        class="px-3 py-2 cursor-pointer transition text-sm"
        :class="{
          'bg-blue-400 text-white': selectedIndex === index,
          'hover:bg-gray-100': selectedIndex !== index
        }"
      >
        <slot name="item" :item="item">{{ item.label }}</slot>
      </div>
    </div>
  </div>
</template>