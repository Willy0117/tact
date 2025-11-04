<template>
  <AppLayout>
    <template #header>
      {{ role ? t('edit_role') : t('create_role') }}
    </template>

    <div class="p-6">
      <div class="space-y-4">
        <form @submit.prevent="submitForm">
          <!-- Role Name -->
          <div>
            <label class="block mb-1">{{ t('name') }}</label>
            <input
              v-model="form.name"
              type="text"
              placeholder="Role Name"
              class="border rounded px-3 py-2 w-full"
            />
            <p v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</p>
          </div>

          <!-- Permissions -->
          <div class="mt-4">
            <label class="block mb-1">{{ t('permissions') }}</label>
            <div class="flex flex-wrap gap-2">
              <div
                v-for="perm in permissions"
                :key="perm.id"
                class="flex items-center space-x-1"
              >
                <input
                  type="checkbox"
                  :value="perm.id"
                  v-model="form.permissions"
                  :id="'perm_' + perm.id"
                />
                <label :for="'perm_' + perm.id">{{ perm.name }}</label>
              </div>
            </div>
            <p v-if="errors.permissions" class="text-red-500 text-sm mt-1">{{ errors.permissions }}</p>
          </div>

          <!-- Buttons -->
          <div class="flex space-x-2 mt-6">
            <button
              type="submit"
              class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
            >
              {{ role ? t('update') : t('create') }}
            </button>
            <button
              @click="router.get(route('roles.index', filters), { preserveState: true })"
              type="button"
              class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400"
            >
              {{ t('cancel') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import { reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  role: Object,          // null → 新規作成, オブジェクト → 編集
  permissions: Array,    // 全権限リスト
  filters: Object        // Index 画面の検索条件
})

const { t } = useI18n()

// フォーム初期値
const form = reactive({
  name: props.role ? props.role.name : '',
  permissions: props.role ? props.role.permissions.map(p => p.id) : []
})

// エラー管理
const errors = reactive({
  name: '',
  permissions: ''
})

// 送信処理
const submitForm = () => {
  if (props.role) {
    // 編集
    router.put(route('roles.update', props.role.id), form, {
      preserveState: true,
      onError: (err) => Object.assign(errors, err),
      onSuccess: () => router.get(route('roles.index', props.filters))
    })
  } else {
    // 新規作成
    router.post(route('roles.store'), form, {
      preserveState: true,
      onError: (err) => Object.assign(errors, err),
      onSuccess: () => router.get(route('roles.index', props.filters))
    })
  }
}
</script>
