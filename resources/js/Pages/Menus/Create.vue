<template>
  <AppLayout>
    <template #header>{{ t('add_menu') }}</template>

    <div class="p-6">
      <div class="space-y-4">
        <!-- 料理名 -->
        <div>
          <label class="block">{{ t('dish_name') }}</label>
          <textarea
            v-model="form.dish_name"
            class="border rounded px-3 py-2 w-full"
            rows="2"
          ></textarea>
          <div v-if="errors.dish_name" class="text-red-500 text-sm">{{ errors.dish_name }}</div>
        </div>

        <!-- 配膳日 -->
        <div>
          <label class="block">{{ t('serving_date') }}</label>
          <input v-model="form.serving_date" type="date" class="border rounded px-3 py-2 w-full" />
          <div v-if="errors.serving_date" class="text-red-500 text-sm">{{ errors.serving_date }}</div>
        </div>

        <!-- 配膳時間 -->
        <div>
          <label class="block">{{ t('serving_time') }}</label>
          <input v-model="form.serving_time" type="time" class="border rounded px-3 py-2 w-full" />
          <div v-if="errors.serving_time" class="text-red-500 text-sm">{{ errors.serving_time }}</div>
        </div>

        <!-- 調理日 -->
        <div>
          <label class="block">{{ t('cooking_date') }}</label>
          <input v-model="form.cooking_date" type="date" class="border rounded px-3 py-2 w-full" />
          <div v-if="errors.cooking_date" class="text-red-500 text-sm">{{ errors.cooking_date }}</div>
        </div>

        <!-- 材料 -->
        <div>
          <label class="block">{{ t('materials') }}</label>
          <textarea
            v-model="form.materials"
            class="border rounded px-3 py-2 w-full"
            rows="3"
          ></textarea>
          <div v-if="errors.materials" class="text-red-500 text-sm">{{ errors.materials }}</div>
        </div>

        <!-- ボタン -->
        <div class="flex space-x-2">
          <button @click="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            {{ t('save') }}
          </button>
          <button
            @click="router.get(route('menus.index', filters), {}, { preserveState: true })"
            class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400"
          >
            {{ t('cancel') }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { router } from '@inertiajs/vue3'

const { t } = useI18n()

const props = defineProps({
  filters: Object,
  menu: Object // コピー用に渡される場合
})

const form = reactive({
  dish_name: props.menu?.dish_name ?? '',
  serving_date: props.menu?.serving_date ?? '',
  serving_time: props.menu?.serving_time ?? '',
  cooking_date: props.menu?.cooking_date ?? '',
  materials: props.menu?.materials ?? ''
})

const errors = reactive({
  dish_name: '', serving_date: '', serving_time: '', cooking_date: '', materials: ''
})

// コピー用データをセット
onMounted(() => {
  if (props.menu) {
    form.dish_name = props.menu.dish_name
    form.serving_date = props.menu.serving_date
    form.serving_time = props.menu.serving_time
    form.cooking_date = props.menu.cooking_date
    form.materials = props.menu.materials
  }
})

const submit = () => {
  router.post(route('menus.store'), form, {
    preserveState: true,
    onSuccess: () => router.get(route('menus.index', props.filters)),
    onError: (errs) => Object.assign(errors, errs)
  })
}
</script>
