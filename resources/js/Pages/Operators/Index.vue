<template>
  <AppLayout>
    <template #header>{{ t('operator_list') }}</template>

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

          <Select v-model="form.status" @update:modelValue="submitSearch">
            <SelectTrigger class="w-28 h-9">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">{{ t('all') }}</SelectItem>
              <SelectItem value="enabled">{{ t('enable') }}</SelectItem>
              <SelectItem value="disabled">{{ t('disable') }}</SelectItem>
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
            <Link :href="route('operators.create', persistQuery())">
              <Plus class="w-3.5 h-3.5 mr-1" />{{ t('add_operator') }}
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
        <Badge v-if="form.code" variant="secondary" class="gap-1">
          {{ t('code') }}: {{ form.code }}
          <button @click="form.code = ''; submitSearch()"><X class="w-3 h-3" /></button>
        </Badge>
        <Badge v-if="form.name" variant="secondary" class="gap-1">
          {{ t('name') }}: {{ form.name }}
          <button @click="form.name = ''; submitSearch()"><X class="w-3 h-3" /></button>
        </Badge>
        <Badge v-if="isSuperAdmin && form.tenant !== 'all'" variant="secondary" class="gap-1">
          {{ t('tenant') }}: {{ tenants.find(t => String(t.id) === form.tenant)?.name }}
          <button @click="form.tenant = 'all'; submitSearch()"><X class="w-3 h-3" /></button>
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
              <th v-if="isSuperAdmin" class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('tenant_id')">
                {{ t('tenant') }}
                <SortIcon field="tenant_id" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('code')">
                {{ t('code') }}
                <SortIcon field="code" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('name')">
                {{ t('name') }}
                <SortIcon field="name" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ t('updated_at') }}
              </th>
              <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ t('disabled') }}
              </th>
              <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('display_order')">
                {{ t('display_order') }}
                <SortIcon field="display_order" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ t('actions') }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="operators.data.length === 0">
              <td :colspan="isSuperAdmin ? 7 : 6" class="px-3 py-12 text-center text-muted-foreground">
                <Users class="w-8 h-8 mx-auto mb-2 opacity-30" />
                {{ t('no_results') }}
              </td>
            </tr>
            <tr
              v-for="operator in operators.data"
              :key="operator.id"
              class="odd:bg-white even:bg-muted/30 hover:bg-muted/50 transition-colors border-b"
            >
              <td class="px-3 py-2.5 text-center">
                <Checkbox
                  :model-value="selectedIds.includes(operator.id)"
                  @update:model-value="(checked) => toggleSelect(operator.id, checked)"
                />
              </td>
              <td v-if="isSuperAdmin" class="px-3 py-2.5 text-sm text-muted-foreground">
                {{ tenants.find(t => t.id === operator.tenant_id)?.name || '-' }}
              </td>
              <td class="px-3 py-2.5 text-sm text-muted-foreground">{{ operator.code }}</td>
              <td class="px-3 py-2.5 font-medium">{{ operator.name }}</td>
              <td class="px-3 py-2.5 text-sm text-muted-foreground">
                {{ operator.updated_at ? dayjs(operator.updated_at).format('YYYY/MM/DD HH:mm:ss') : '' }}
              </td>
              <td class="px-3 py-2.5 text-center">
                <Badge :variant="operator.disabled ? 'outline' : 'secondary'">
                  {{ operator.disabled ? t('disable') : t('enable') }}
                </Badge>
              </td>
              <td class="px-3 py-2.5 text-center text-sm text-muted-foreground">{{ operator.display_order }}</td>
              <td class="px-3 py-2.5">
                <div class="flex items-center justify-center gap-1">
                  <Button variant="ghost" size="icon" class="h-7 w-7" @click="copyOperator(operator.id)">
                    <Copy class="w-3.5 h-3.5" />
                  </Button>
                  <Button variant="ghost" size="icon" class="h-7 w-7 text-blue-600" as-child>
                    <Link :href="route('operators.edit', { operator: operator.id, ...persistQuery() })">
                      <Pencil class="w-3.5 h-3.5" />
                    </Link>
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 text-destructive hover:text-destructive"
                    @click="deleteOperator(operator.id)"
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
        <span>{{ startItem }}〜{{ endItem }} 件 / 全{{ operators.total }}件</span>
        <Pagination :paginator="operators" :onPageChange="goPage" />
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
            <div v-if="isSuperAdmin" class="space-y-1.5">
              <Label>{{ t('tenant') }}</Label>
              <Select v-model="form.tenant">
                <SelectTrigger><SelectValue :placeholder="t('select_tenant')" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">{{ t('select_tenant') }}</SelectItem>
                  <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                    {{ tenant.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-1.5">
              <Label>{{ t('code') }}</Label>
              <Input v-model="form.code" :placeholder="t('code')" />
            </div>

            <div class="space-y-1.5">
              <Label>{{ t('name') }}</Label>
              <Input v-model="form.name" :placeholder="t('name')" />
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
import { Plus, Pencil, Trash2, Copy, Search, Filter, X, Users } from '@lucide/vue'

const props = defineProps({
  operators: Object,
  tenants: { type: Array, default: () => [] },
  user: { type: Object, default: null },
  filters: {
    type: Object,
    default: () => ({
      code: '', name: '', tenant: '', status: 'enabled',
      per_page: 20, sort_by: 'id', sort_dir: 'desc', page: 1
    })
  }
})

const { t } = useI18n()

const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

const openDrawer = ref(false)

const form = reactive({
  tenant: props.filters.tenant ? String(props.filters.tenant) : 'all',
  code: props.filters.code || '',
  name: props.filters.name || '',
  status: props.filters.status || 'enabled',
  per_page: props.filters.per_page || 20,
  sort_by: props.filters.sort_by || 'id',
  sort_dir: props.filters.sort_dir || 'desc',
})

const hasActiveFilters = computed(() =>
  form.code || form.name || (isSuperAdmin.value && form.tenant !== 'all')
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
  selectedIds.value = checked ? props.operators.data.map(s => s.id) : []
}

const resetSelectedIds = () => {
  selectedIds.value = []
}

const selectAll = computed(() =>
  props.operators.data.length > 0 && selectedIds.value.length === props.operators.data.length
)

watch(() => props.operators.current_page, () => {
  selectedIds.value = []
})

const persistQuery = () => ({
  tenant: form.tenant === 'all' ? '' : form.tenant,
  code: form.code,
  name: form.name,
  status: form.status,
  per_page: form.per_page,
  sort_by: form.sort_by,
  sort_dir: form.sort_dir,
  page: props.operators.current_page
})

const submitSearch = () => {
  router.get(route('operators.index'), { ...persistQuery(), page: 1 }, {
    preserveState: true,
    replace: true,
    onSuccess: () => resetSelectedIds()
  })
}

const resetSearch = () => {
  form.tenant = 'all'
  form.code = ''
  form.name = ''
  submitSearch()
  openDrawer.value = false
}

const goPage = (page) => {
  router.get(route('operators.index'), { ...persistQuery(), page }, {
    preserveState: true,
    replace: true,
    onSuccess: () => resetSelectedIds()
  })
}

const sortBy = (field) => {
  if (form.sort_by === field) form.sort_dir = form.sort_dir === 'asc' ? 'desc' : 'asc'
  else { form.sort_by = field; form.sort_dir = 'desc' }
  submitSearch()
}

const deleteOperator = (operator_id) => {
  if (!confirm(t('confirm_delete'))) return
  router.delete(route('operators.destroy', operator_id), {
    preserveState: true,
    onSuccess: () => submitSearch()
  })
}

const bulkDelete = () => {
  if (!confirm(t('confirm_delete_selected'))) return
  router.post(
    route('operators.bulkDelete'),
    { ids: selectedIds.value },
    {
      preserveState: true,
      onSuccess: () => submitSearch()
    }
  )
}

const copyOperator = (operator_id) => {
  router.get(
    route('operators.create', { ...persistQuery(), mode: 'copy', operator_id })
  )
}

const startItem = computed(() => props.operators.per_page * (props.operators.current_page - 1) + 1)
const endItem = computed(() => Math.min(props.operators.per_page * props.operators.current_page, props.operators.total))
</script>