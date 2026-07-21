<template>
  <AppLayout>
    <template #header>{{ t('user_list') }}</template>

    <div class="p-6 space-y-4">

      <!-- ツールバー -->
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <Select :model-value="String(form.per_page)" @update:modelValue="v => { form.per_page = Number(v); submitSearch() }">
            <SelectTrigger class="w-20 h-9">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="n in [10,20,30,50]" :key="n" :value="String(n)">{{ n }}</SelectItem>
            </SelectContent>
          </Select>

          <template v-if="selectedIds.length > 0">
            <Button variant="destructive" size="sm" @click="bulkDelete">
              <Trash2 class="w-3.5 h-3.5 mr-1" />
              {{ selectedIds.length }}{{ t('delete_selected') }}
            </Button>
          </template>
        </div>

        <div class="flex items-center gap-2">
          <Button size="sm" as-child>
            <Link :href="route('users.create', persistQuery())">
              <Plus class="w-3.5 h-3.5 mr-1" />{{ t('add_user') }}
            </Link>
          </Button>
          <Button variant="outline" size="sm" @click="openDrawer = true">
            <Filter class="w-3.5 h-3.5 mr-1" />{{ t('search') }}
          </Button>
        </div>
      </div>

      <!-- 検索中バッジ -->
      <div v-if="hasActiveFilters" class="flex items-center gap-2 flex-wrap">
        <span class="text-xs text-muted-foreground">検索条件:</span>
        <Badge v-if="form.name" variant="secondary" class="gap-1">
          {{ t('name') }}: {{ form.name }}
          <button @click="form.name = ''; submitSearch()"><X class="w-3 h-3" /></button>
        </Badge>
        <Badge v-if="form.email" variant="secondary" class="gap-1">
          {{ t('email') }}: {{ form.email }}
          <button @click="form.email = ''; submitSearch()"><X class="w-3 h-3" /></button>
        </Badge>
        <Badge v-if="isSuperAdmin && form.tenant_id !== 'all'" variant="secondary" class="gap-1">
          {{ t('tenant') }}: {{ tenants.find(t => String(t.id) === form.tenant_id)?.name }}
          <button @click="form.tenant_id = 'all'; submitSearch()"><X class="w-3 h-3" /></button>
        </Badge>
      </div>

      <!-- テーブル -->
      <div class="border rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-muted border-b-2 border-border">
            <tr>
              <th class="px-3 py-2.5 w-8 text-center">
                <Checkbox :model-value="selectAll" @update:model-value="checked => toggleSelectAll(checked)" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('name')">
                {{ t('name') }}
                <SortIcon field="name" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('email')">
                {{ t('email') }}
                <SortIcon field="email" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('tenant_id')">
                {{ t('tenant_id') }}
                <SortIcon field="tenant_id" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('role')">
                {{ t('role') }}
                <SortIcon field="role" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ t('updated_at') }}
              </th>
              <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ t('actions') }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="users.data.length === 0">
              <td colspan="6" class="px-3 py-12 text-center text-muted-foreground">
                <User class="w-8 h-8 mx-auto mb-2 opacity-30" />
                {{ t('no_results') }}
              </td>
            </tr>
            <tr
              v-for="user in users.data"
              :key="user.id"
              class="odd:bg-white even:bg-muted/30 hover:bg-muted/50 transition-colors border-b"
            >
              <td class="px-3 py-2.5 text-center">
                <Checkbox
                  :model-value="selectedIds.includes(user.id)"
                  @update:model-value="(checked) => toggleSelect(user.id, checked)"
                />
              </td>
              <td class="px-3 py-2.5 font-medium">{{ user.name }}</td>
              <td class="px-3 py-2.5 text-sm text-muted-foreground">{{ user.email }}</td>
              <td class="px-3 py-2.5 text-sm text-muted-foreground">{{ user.tenant?.name || '-' }}</td>
              <td class="px-3 py-2.5">
                <Badge v-if="user.roles?.[0]?.name" variant="secondary">{{ user.roles[0].name }}</Badge>
                <span v-else class="text-sm text-muted-foreground">-</span>
              </td>
              <td class="px-3 py-2.5 text-sm text-muted-foreground">
                {{ user.updated_at ? dayjs(user.updated_at).format('YYYY/MM/DD HH:mm:ss') : '' }}
              </td>
              <td class="px-3 py-2.5">
                <div class="flex items-center justify-center gap-1">
                  <Button variant="ghost" size="icon" class="h-7 w-7" @click="copyUser(user.id)">
                    <Copy class="w-3.5 h-3.5" />
                  </Button>
                  <Button variant="ghost" size="icon" class="h-7 w-7 text-blue-600" as-child>
                    <Link :href="route('users.edit', { user: user.id, ...persistQuery() })">
                      <Pencil class="w-3.5 h-3.5" />
                    </Link>
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 text-destructive hover:text-destructive"
                    @click="deleteUser(user.id)"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ページネーション -->
      <div class="flex items-center justify-between text-sm text-muted-foreground">
        <span>{{ startItem }}〜{{ endItem }} 件 / 全{{ users.total }}件</span>
        <Pagination :paginator="users" :onPageChange="goPage" />
      </div>
    </div>

    <!-- ========== 検索 Drawer ========== -->
    <Teleport to="body">
      <div v-if="openDrawer" class="fixed inset-0 z-40">
        <div class="absolute inset-0 bg-black/30" @click="openDrawer = false" />
        <aside class="absolute top-0 right-0 h-full w-80 bg-white shadow-xl z-50 flex flex-col">
          <div class="flex items-center justify-between px-5 py-4 border-b">
            <h2 class="font-bold">{{ t('search') }}</h2>
            <Button variant="ghost" size="icon" @click="openDrawer = false">
              <X class="w-4 h-4" />
            </Button>
          </div>
          <div class="flex-1 overflow-y-auto p-5 space-y-4">
            <div class="space-y-1.5">
              <Label>{{ t('name') }}</Label>
              <Input v-model="form.name" :placeholder="t('name')" />
            </div>

            <div class="space-y-1.5">
              <Label>{{ t('email') }}</Label>
              <Input v-model="form.email" :placeholder="t('email')" />
            </div>

            <div v-if="isSuperAdmin" class="space-y-1.5">
              <Label>{{ t('tenant') }}</Label>
              <Select v-model="form.tenant_id">
                <SelectTrigger><SelectValue :placeholder="t('select')" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">{{ t('select') }}</SelectItem>
                  <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                    {{ tenant.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div class="px-5 py-4 border-t flex gap-2">
            <Button class="flex-1" @click="submitSearch(); openDrawer = false">
              <Search class="w-3.5 h-3.5 mr-1" />{{ t('search') }}
            </Button>
            <Button variant="outline" @click="resetSearch">{{ t('reset') }}</Button>
          </div>
        </aside>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import SortIcon from '@/Components/SortIcon.vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

import { Link, router } from '@inertiajs/vue3'
import { ref, reactive, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import dayjs from 'dayjs'
import { Plus, Pencil, Trash2, Copy, Search, Filter, X, User } from '@lucide/vue'

const props = defineProps({
  users: Object,
  filters: { type: Object, default: () => ({}) },
  tenants: { type: Array, default: () => [] },
})

const { t } = useI18n()

const isSuperAdmin = computed(() => props.tenants && props.tenants.length > 0)

const openDrawer = ref(false)

const form = reactive({
  name: props.filters.name || '',
  email: props.filters.email || '',
  tenant_id: props.filters.tenant_id ? String(props.filters.tenant_id) : 'all',
  per_page: props.filters.per_page || 20,
  sort_by: props.filters.sort_by || 'id',
  sort_dir: props.filters.sort_dir || 'asc',
})

const hasActiveFilters = computed(() =>
  form.name || form.email || (isSuperAdmin.value && form.tenant_id !== 'all')
)

const selectedIds = ref([])

const toggleSelect = (id, checked) => {
  if (checked) {
    if (!selectedIds.value.includes(id)) selectedIds.value.push(id)
  } else {
    selectedIds.value = selectedIds.value.filter(i => i !== id)
  }
}

const toggleSelectAll = (checked) => {
  selectedIds.value = checked ? props.users.data.map(u => u.id) : []
}

const resetSelectedIds = () => { selectedIds.value = [] }

const selectAll = computed(() =>
  props.users.data.length > 0 && selectedIds.value.length === props.users.data.length
)

watch(() => props.users.current_page, () => resetSelectedIds())

const persistQuery = () => ({
  name: form.name,
  email: form.email,
  tenant_id: form.tenant_id === 'all' ? '' : form.tenant_id,
  per_page: form.per_page,
  sort_by: form.sort_by,
  sort_dir: form.sort_dir,
  page: props.users.current_page
})

const submitSearch = () => {
  router.get(route('users.index'), { ...persistQuery(), page: 1 }, { preserveState: true, replace: true, onSuccess: resetSelectedIds })
}

const resetSearch = () => {
  form.name = ''
  form.email = ''
  form.tenant_id = 'all'
  submitSearch()
  openDrawer.value = false
}

const goPage = (page) => {
  router.get(route('users.index'), { ...persistQuery(), page }, { preserveState: true, replace: true, onSuccess: resetSelectedIds })
}

const sortBy = (field) => {
  if (form.sort_by === field) form.sort_dir = form.sort_dir === 'asc' ? 'desc' : 'asc'
  else { form.sort_by = field; form.sort_dir = 'asc' }
  submitSearch()
}

const deleteUser = (user_id) => {
  if (!confirm(t('confirm_delete'))) return
  router.delete(route('users.destroy', user_id), { preserveState: true, onSuccess: () => submitSearch() })
}

const bulkDelete = () => {
  if (!confirm(t('confirm_delete_selected'))) return
  router.post(route('users.bulkDelete'), { ids: selectedIds.value }, { preserveState: true, onSuccess: () => submitSearch() })
}

const copyUser = (user_id) => {
  router.get(route('users.create', { ...persistQuery(), mode: 'copy', user_id }))
}

const startItem = computed(() => props.users.per_page * (props.users.current_page - 1) + 1)
const endItem = computed(() => Math.min(props.users.per_page * props.users.current_page, props.users.total))
</script>