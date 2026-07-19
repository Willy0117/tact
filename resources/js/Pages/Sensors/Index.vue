<template>
  <AppLayout>
    <template #header>{{ t('sensor_list') }}</template>

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
            <Link :href="route('sensors.create', persistQuery())">
              <Plus class="w-3.5 h-3.5 mr-1" />{{ t('add_sensor') }}
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
        <Badge v-if="form.model" variant="secondary" class="gap-1">
          {{ t('model') }}: {{ form.model }}
          <button @click="form.model = ''; submitSearch()"><X class="w-3 h-3" /></button>
        </Badge>
        <Badge v-if="form.serial_number" variant="secondary" class="gap-1">
          {{ t('serial_number') }}: {{ form.serial_number }}
          <button @click="form.serial_number = ''; submitSearch()"><X class="w-3 h-3" /></button>
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
                {{ t('name') }}
                <SortIcon field="name" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('model')">
                {{ t('model') }}
                <SortIcon field="model" :current="form.sort_by" :dir="form.sort_dir" />
              </th>
              <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground cursor-pointer" @click="sortBy('serial_number')">
                {{ t('serial_number') }}
                <SortIcon field="serial_number" :current="form.sort_by" :dir="form.sort_dir" />
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
            <tr v-if="sensors.data.length === 0">
              <td :colspan="isSuperAdmin ? 8 : 7" class="px-3 py-12 text-center text-muted-foreground">
                <SmartphoneNfc class="w-8 h-8 mx-auto mb-2 opacity-30" />
                {{ t('no_results') }}
              </td>
            </tr>
            <tr
              v-for="sensor in sensors.data"
              :key="sensor.id"
              class="odd:bg-white even:bg-muted/30 hover:bg-muted/50 transition-colors border-b"
            >
              <td class="px-3 py-2.5 text-center">
                <Checkbox
                  :model-value="selectedIds.includes(sensor.id)"
                  @update:model-value="(checked) => toggleSelect(sensor.id, checked)"
                />
              </td>
              <td v-if="isSuperAdmin" class="px-3 py-2.5 text-sm text-muted-foreground">
                {{ tenants.find(t => t.id === sensor.tenant_id)?.name || '-' }}
              </td>
              <td class="px-3 py-2.5 font-medium">{{ sensor.name }}</td>
              <td class="px-3 py-2.5 text-sm text-muted-foreground">{{ sensor.model }}</td>
              <td class="px-3 py-2.5 text-sm text-muted-foreground">{{ sensor.serial_number }}</td>
              <td class="px-3 py-2.5 text-sm text-muted-foreground">
                {{ sensor.updated_at ? dayjs(sensor.updated_at).format('YYYY/MM/DD HH:mm:ss') : '' }}
              </td>
              <td class="px-3 py-2.5 text-center">
                <Badge :variant="sensor.disabled ? 'outline' : 'secondary'">
                  {{ sensor.disabled ? t('disable') : t('enable') }}
                </Badge>
              </td>
              <td class="px-3 py-2.5 text-center text-sm text-muted-foreground">{{ sensor.display_order }}</td>
              <td class="px-3 py-2.5">
                <div class="flex items-center justify-center gap-1">
                  <Button variant="ghost" size="icon" class="h-7 w-7" @click="copySensor(sensor.id)">
                    <Copy class="w-3.5 h-3.5" />
                  </Button>
                  <Button variant="ghost" size="icon" class="h-7 w-7 text-blue-600" as-child>
                    <Link :href="route('sensors.edit', { sensor: sensor.id, ...persistQuery() })">
                      <Pencil class="w-3.5 h-3.5" />
                    </Link>
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 text-destructive hover:text-destructive"
                    @click="deleteSensor(sensor.id)"
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
        <span>{{ startItem }}〜{{ endItem }} 件 / 全{{ sensors.total }}件</span>
        <Pagination :paginator="sensors" :onPageChange="goPage" />
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
                <SelectTrigger><SelectValue :placeholder="t('please_select')" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">{{ t('please_select') }}</SelectItem>
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
              <Label>{{ t('model') }}</Label>
              <Input v-model="form.model" :placeholder="t('model')" />
            </div>

            <div class="space-y-1.5">
              <Label>{{ t('serial_number') }}</Label>
              <Input v-model="form.serial_number" :placeholder="t('serial number')" />
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
import { Plus, Pencil, Trash2, Copy, Search, Filter, X, SmartphoneNfc } from '@lucide/vue'

const props = defineProps({
  tenants: { type: Array, default: () => [] },
  user: { type: Object, default: null },
  sensors: Object,
  filters: {
    type: Object,
    default: () => ({
      name: '', model: '', serial_number: '', tenant_id: '',
      per_page: 20, sort_by: 'display_order', sort_dir: 'asc', page: 1
    })
  }
})

const { t } = useI18n()
const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

const openDrawer = ref(false)

const form = reactive({
  tenant_id: props.filters.tenant_id ? String(props.filters.tenant_id) : 'all',
  name: props.filters.name || '',
  model: props.filters.model || '',
  serial_number: props.filters.serial_number || '',
  per_page: props.filters.per_page ?? 20,
  sort_by: props.filters.sort_by ?? 'display_order',
  sort_dir: props.filters.sort_dir ?? 'asc',
  status: props.filters.status || 'enabled',
})

const hasActiveFilters = computed(() =>
  form.name || form.model || form.serial_number || (isSuperAdmin.value && form.tenant_id !== 'all')
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
  selectedIds.value = checked ? props.sensors.data.map(s => s.id) : []
}

const resetSelectedIds = () => {
  selectedIds.value = []
}

const selectAll = computed(() =>
  props.sensors.data.length > 0 && selectedIds.value.length === props.sensors.data.length
)

watch(() => props.sensors.current_page, () => {
  selectedIds.value = []
})

const persistQuery = () => ({
  tenant_id: form.tenant_id === 'all' ? '' : form.tenant_id,
  name: form.name,
  model: form.model,
  serial_number: form.serial_number,
  per_page: form.per_page,
  sort_by: form.sort_by,
  sort_dir: form.sort_dir,
  page: props.sensors.current_page,
  status: form.status,
})

const submitSearch = () => {
  router.get(route('sensors.index'), { ...persistQuery(), page: 1 }, {
    preserveState: true,
    replace: true,
    onSuccess: () => resetSelectedIds()
  })
}

const resetSearch = () => {
  form.tenant_id = 'all'
  form.name = ''
  form.model = ''
  form.serial_number = ''
  form.status = 'enabled',
  submitSearch()
  openDrawer.value = false
}

const goPage = (page) => {
  router.get(route('sensors.index'), { ...persistQuery(), page }, {
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

const deleteSensor = (sensor_id) => {
  if (!confirm(t('confirm_delete'))) return
  router.delete(route('sensors.destroy', sensor_id), {
    preserveState: true,
    onSuccess: () => submitSearch()
  })
}

const bulkDelete = () => {
  if (!confirm(t('confirm_delete_selected'))) return
  router.post(
    route('sensors.bulkDelete'),
    { ids: selectedIds.value },
    {
      preserveState: true,
      onSuccess: () => submitSearch()
    }
  )
}

const copySensor = (sensor_id) => {
  router.get(
    route('sensors.create', { ...persistQuery(), mode: 'copy', sensor_id })
  )
}

const startItem = computed(() => props.sensors.per_page * (props.sensors.current_page - 1) + 1)
const endItem = computed(() => Math.min(props.sensors.per_page * props.sensors.current_page, props.sensors.total))
</script>