<template>
  <AppLayout>
    <template #header>{{ t('dashboard') }}</template>

    <!-- 凡例（1箇所のみ） -->
    <div class="flex items-center justify-end gap-4 mb-3 text-xs text-muted-foreground">
      <span class="flex items-center gap-1">
        <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: #22c55e;"></span>
        正常データ
      </span>
      <span class="flex items-center gap-1">
        <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: #ff8080;"></span>
        逸脱データ
      </span>
    </div>

    <!-- 上段: 今日+昨日 / 今月+先月 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

      <Card>
        <CardHeader>
          <CardTitle>今日の登録データ数</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-end justify-center gap-6">
            <div class="text-center">
              <DonutChart :value="props.today.success" :total="props.today.total" />
              <div class="flex items-center justify-center gap-2 mt-3">
                <Badge variant="secondary" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">
                  正常 {{ props.today.success }}
                </Badge>
                <Badge variant="destructive">
                  逸脱 {{ props.today.deviation }}
                </Badge>
              </div>
            </div>
            <div class="text-center">
              <p class="text-xs text-muted-foreground mb-2">昨日</p>
              <DonutChart :value="props.yesterday.success" :total="props.yesterday.total" :size="180" />
              <div class="flex items-center justify-center gap-2 mt-3">
                <Badge variant="secondary" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">
                  正常 {{ props.yesterday.success }}
                </Badge>
                <Badge variant="destructive">
                  逸脱 {{ props.yesterday.deviation }}
                </Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>今月の登録データ数</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-end justify-center gap-6">
            <div class="text-center">
              <DonutChart :value="props.month.success" :total="props.month.total" />
              <div class="flex items-center justify-center gap-2 mt-3">
                <Badge variant="secondary" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">
                  正常 {{ props.month.success }}
                </Badge>
                <Badge variant="destructive">
                  逸脱 {{ props.month.deviation }}
                </Badge>
              </div>
            </div>
            <div class="text-center">
              <p class="text-xs text-muted-foreground mb-2">先月</p>
              <DonutChart :value="props.lastMonth.success" :total="props.lastMonth.total" :size="180" />
              <div class="flex items-center justify-center gap-2 mt-3">
                <Badge variant="secondary" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">
                  正常 {{ props.lastMonth.success }}
                </Badge>
                <Badge variant="destructive">
                  逸脱 {{ props.lastMonth.deviation }}
                </Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

    </div>

    <!-- 下段: 今日の献立 / 最新の登録データ -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">

      <!-- 今日の献立（5区分を縦に積む） -->
      <div class="space-y-3">
        <Card v-for="block in blocks" :key="block.key" size="sm">
          <CardHeader>
            <CardTitle class="text-center text-sm">
              今日の献立（{{ block.label }}）
            </CardTitle>
          </CardHeader>
          <CardContent>
            <template v-if="groupedMenus[block.key] && groupedMenus[block.key].length !== 0">
              <div
                v-for="menu in groupedMenus[block.key]"
                :key="menu.id"
                class="flex items-center justify-between py-1.5 border-b last:border-b-0 text-xs"
              >
                <div class="font-medium truncate mr-2">{{ menu.name }}</div>
                <div class="flex gap-1.5 shrink-0">
                  <span class="tooltip-wrapper">
                    🔥 {{ menu.heating_count }}
                    <span class="tooltip-text">加熱</span>
                  </span>
                  <span class="tooltip-wrapper">
                    ❄ {{ menu.cooling_count }}
                    <span class="tooltip-text">冷却</span>
                  </span>
                </div>
              </div>
            </template>
            <div v-else class="text-center text-xs text-muted-foreground py-3">
              該当する献立はありません
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- 最新の登録データ -->
      <Card>
        <CardHeader>
          <CardTitle>最新の登録データ（5件）</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="props.logs.length === 0" class="text-center text-sm text-muted-foreground py-8">
            登録データがありません
          </div>
          <div v-else class="space-y-1">
            <div
              v-for="log in props.logs"
              :key="log.id"
              class="grid grid-cols-[1.6fr_0.8fr_0.8fr_1fr] gap-2 items-center py-2 border-b last:border-b-0 text-sm"
            >
              <div class="font-medium truncate">{{ log.menu?.name }}</div>
              <div class="text-muted-foreground text-xs">{{ formatTime(log.created_at) }}</div>
              <Badge
                v-if="log.process?.name"
                :class="log.process.name === '加熱' ? 'bg-orange-100 text-orange-700 hover:bg-orange-100' : 'bg-blue-100 text-blue-700 hover:bg-blue-100'"
              >
                {{ log.process.name }}
              </Badge>
              <span v-else></span>
              <div class="text-muted-foreground text-xs truncate">{{ log.operator?.name }}</div>
            </div>
          </div>
        </CardContent>
      </Card>

    </div>

  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DonutChart from '@/Components/DonutChart.vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  today: Object,
  yesterday: Object,
  month: Object,
  lastMonth: Object,
  logs: Object,
  menus: Object,
  tenants: Array,
  user: Object,
  filters: Object,
  success: String,
})

const { t } = useI18n()

const categorize = (time) => {
  if (time < '10:00') return 'breakfast'
  if (time < '12:00') return 'snack_morning'
  if (time < '15:00') return 'lunch'
  if (time < '17:00') return 'snack_afternoon'
  return 'dinner'
}

const groupedMenus = computed(() => {
  const groups = {
    breakfast: [],
    snack_morning: [],
    lunch: [],
    snack_afternoon: [],
    dinner: []
  }

  props.menus.forEach(menu => {
    const key = categorize(menu.serving_time)
    groups[key].push(menu)
  })

  return groups
})

const blocks = [
  { key: 'breakfast', label: '朝食' },
  { key: 'snack_morning', label: 'おやつ(10)' },
  { key: 'lunch', label: '昼食' },
  { key: 'snack_afternoon', label: 'おやつ(15)' },
  { key: 'dinner', label: '夕食' },
]

const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)

const form = reactive({
  tenant_id: props.filters?.tenant_id,
})

const persistQuery = () => ({
  tenant_id: props.tenant_id,
})

let interval = null

onMounted(() => {
  interval = setInterval(() => {
    router.reload({
      only: ['logs'],
      preserveState: true,
      preserveScroll: true,
    })
  }, 300000)
})

onUnmounted(() => {
  clearInterval(interval)
})

const formatTime = (datetime) => {
  if (!datetime) return ''
  return new Date(datetime).toLocaleTimeString('ja-JP', {
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
.tooltip-wrapper {
  position: relative;
  cursor: default;
  display: inline-flex;
  align-items: center;
}

.tooltip-text {
  visibility: hidden;
  opacity: 0;
  position: absolute;
  bottom: 125%;
  left: 50%;
  transform: translateX(-50%);
  background: #333;
  color: #fff;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  white-space: nowrap;
  transition: opacity 0.2s;
  z-index: 10;
  pointer-events: none;
}

.tooltip-wrapper:hover .tooltip-text {
  visibility: visible;
  opacity: 1;
}
</style>