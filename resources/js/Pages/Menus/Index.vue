<template>
  <AppLayout>
    <template #header>{{ t('menu_list') }}</template>

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
            <Link :href="route('menus.create', persistQuery())">
              <Plus class="w-3.5 h-3.5 mr-1" />{{ t('add_menu') }}
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
          {{ t('dish_name') }}: {{ form.name }}
          <button @click="form.name = ''; submitSearch()"><X class="w-3 h-3" /></button>
        </Badge>
        <Badge v-if="form.serving_date_from || form.serving_date_to" variant="secondary" class="gap-1">
          {{ t('serving_date') }}: {{ form.serving_date_from }} 〜 {{ form.serving_date_to }}
          <button @click="form.serving_date_from = ''; form.serving_date_to = ''; submitSearch()"><X class="w-3 h-3" /></button>
        </Badge>
        <Badge v-if="form.cooking_date_from || form.cooking_date_to" variant="secondary" class="gap-1">
          {{ t('cooking_date') }}: {{ form.cooking_date_from }} 〜 {{ form.cooking_date_to }}
          <button @click="form.cooking_date_from = ''; form.cooking_date_to = ''; submitSearch()"><X class="w-3 h-3" /></button>
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
              <th v-if="isSuperAdmin" class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('tenant_id')">
                {{ t('tenant') }}
                <SortIcon field="tenant_id" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('name')">
                {{ t('dish_name') }}
                <SortIcon field="name" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('serving_date')">
                {{ t('serving_date') }}
                <SortIcon field="serving_date" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ t('serving_time') }}
              </th>
              <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('cooking_date')">
                {{ t('cooking_date') }}
                <SortIcon field="cooking_date" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ t('materials') }}
              </th>
              <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ t('actions') }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="menus.data.length === 0">
              <td :colspan="isSuperAdmin ? 7 : 6" class="px-3 py-12 text-center text-muted-foreground">
                <Utensils class="w-8 h-8 mx-auto mb-2 opacity-30" />
                {{ t('no_results') }}
              </td>
            </tr>
            <tr
              v-for="menu in menus.data"
              :key="menu.id"
              class="odd:bg-white even:bg-muted/30 hover:bg-muted/50 transition-colors border-b"
            >
              <td class="px-3 py-2.5 text-center">
                <Checkbox
                  :model-value="selectedIds.includes(menu.id)"
                  @update:model-value="(checked) => toggleSelect(menu.id, checked)"
                />
              </td>
              <td v-if="isSuperAdmin" class="px-3 py-2.5 text-sm text-muted-foreground">
                {{ tenants.find(t => t.id === menu.tenant_id)?.name || '-' }}
              </td>
              <td class="px-3 py-2.5 font-medium">{{ menu.name }}</td>
              <td class="px-3 py-2.5 text-center text-sm">
                {{ menu.serving_date ? dayjs(menu.serving_date).format('YYYY/MM/DD') : '' }}
              </td>
              <td class="px-3 py-2.5 text-center text-sm">{{ menu.serving_time }}</td>
              <td class="px-3 py-2.5 text-center text-sm">
                {{ menu.cooking_date ? dayjs(menu.cooking_date).format('YYYY/MM/DD') : '' }}
              </td>
              <td class="px-3 py-2.5 text-sm text-muted-foreground">{{ menu.materials || '-' }}</td>
              <td class="px-3 py-2.5">
                <div class="flex items-center justify-center gap-1">
                  <Button variant="ghost" size="icon" class="h-7 w-7" @click="copyMenu(menu.id)">
                    <Copy class="w-3.5 h-3.5" />
                  </Button>
                  <Button variant="ghost" size="icon" class="h-7 w-7 text-blue-600" as-child>
                    <Link :href="route('menus.edit', { menu: menu.id, ...persistQuery() })">
                      <Pencil class="w-3.5 h-3.5" />
                    </Link>
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 text-destructive hover:text-destructive"
                    @click="deleteMenu(menu.id)"
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
        <span>{{ startItem }}〜{{ endItem }} 件 / 全{{ menus.total }}件</span>
        <Pagination :paginator="menus" :onPageChange="goPage" />
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
              <Select v-model="form.tenant_id">
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
              <Label>{{ t('name') }}</Label>
              <Input v-model="form.name" :placeholder="t('name')" />
            </div>

            <div class="space-y-1.5">
              <Label>{{ t('serving_date') }}</Label>
              <div class="grid grid-cols-2 gap-2">
                <Input v-model="form.serving_date_from" type="date" />
                <Input v-model="form.serving_date_to" type="date" />
              </div>
            </div>

            <div class="space-y-1.5">
              <Label>{{ t('serving_time') }}</Label>
              <Input v-model="form.serving_time" type="time" />
            </div>

            <div class="space-y-1.5">
              <Label>{{ t('cooking_date') }}</Label>
              <div class="grid grid-cols-2 gap-2">
                <Input v-model="form.cooking_date_from" type="date" />
                <Input v-model="form.cooking_date_to" type="date" />
              </div>
            </div>

            <div class="space-y-1.5">
              <Label>{{ t('materials') }}</Label>
              <textarea v-model="form.materials" class="w-full border rounded px-3 py-2" rows="3" />
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
import { Plus, Pencil, Trash2, Copy, Search, Filter, X, Utensils } from '@lucide/vue'

const props = defineProps({
  menus: Object,
  tenants: { type: Array, default: () => [] },
  user: { type: Object, default: null },
  filters: { type: Object, default: () => ({}) },
})

const { t } = useI18n()

const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

const openDrawer = ref(false)

const form = reactive({
  name: props.filters.name || '',
  serving_date_from: props.filters.serving_date_from || '',
  serving_date_to: props.filters.serving_date_to || '',
  serving_time: props.filters.serving_time || '',
  cooking_date_from: props.filters.cooking_date_from || '',
  cooking_date_to: props.filters.cooking_date_to || '',
  materials: props.filters.materials || '',
  per_page: props.filters.per_page ?? 20,
  sort_by: props.filters.sort_by || 'serving_date',
  sort_dir: props.filters.sort_dir ?? 'desc',
  tenant_id: props.filters.tenant_id ? String(props.filters.tenant_id) : 'all',
})

const hasActiveFilters = computed(() =>
  form.name || form.serving_date_from || form.serving_date_to ||
  form.cooking_date_from || form.cooking_date_to || form.materials ||
  (isSuperAdmin.value && form.tenant_id !== 'all')
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
  selectedIds.value = checked ? props.menus.data.map(s => s.id) : []
}

const selectAll = computed(() =>
  props.menus.data.length > 0 && selectedIds.value.length === props.menus.data.length
)

watch(() => props.menus.current_page, () => { selectedIds.value = [] })

const persistQuery = () => ({
  ...form,
  tenant_id: form.tenant_id === 'all' ? '' : form.tenant_id,
  page: props.menus.current_page,
})

const submitSearch = () => {
  router.get(route('menus.index'), { ...persistQuery(), page: 1 }, { preserveState: true })
}

const resetSearch = () => {
  form.name = ''
  form.serving_date_from = ''
  form.serving_date_to = ''
  form.serving_time = ''
  form.cooking_date_from = ''
  form.cooking_date_to = ''
  form.materials = ''
  form.tenant_id = 'all'
  submitSearch()
  openDrawer.value = false
}

const goPage = (page) => {
  router.get(route('menus.index'), { ...persistQuery(), page }, { preserveState: true })
}

const sortBy = (field) => {
  if (form.sort_by === field) form.sort_dir = form.sort_dir === 'asc' ? 'desc' : 'asc'
  else { form.sort_by = field; form.sort_dir = 'desc' }
  submitSearch()
}

const deleteMenu = (menu_id) => {
  if (!confirm(t('confirm_delete'))) return
  router.delete(route('menus.destroy', menu_id), { preserveState: true, onSuccess: () => submitSearch() })
}

const bulkDelete = () => {
  if (!confirm(t('confirm_delete_selected'))) return
  router.post(route('menus.bulkDelete'), { ids: selectedIds.value }, { preserveState: true, onSuccess: () => submitSearch() })
}

const copyMenu = (menu_id) => {
  router.get(route('menus.create', { ...persistQuery(), mode: 'copy', menu_id }))
}

const startItem = computed(() => props.menus.per_page * (props.menus.current_page - 1) + 1)
const endItem = computed(() => Math.min(props.menus.per_page * props.menus.current_page, props.menus.total))
</script>