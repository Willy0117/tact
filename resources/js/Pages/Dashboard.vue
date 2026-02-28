<template>
  <AppLayout>
    <template #header>{{ t('dashboard') }}</template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- 今日 -->
        <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-lg font-semibold mb-4">
            逸脱データ数（今日）
        </h2>
        <DonutChart :value="props.today.deviation" :total="props.today.total" />
        </div>

        <!-- 月まとめ -->
        <div class="bg-white rounded-xl shadow p-6 text-center">
        <h2 class="text-lg font-semibold mb-4">
            逸脱データ数（今月）
        </h2>
        <DonutChart :value="props.month.deviation" :total="props.month.total" />
        </div>

        <!-- 3つ目（例：累計など） -->
        <div class="bg-white rounded-xl shadow p-6 text-center">
            <h2 class="text-lg font-semibold mb-4">
                最新温度ログ
            </h2>
            <div class="log-card">
            <div
                v-for="log in props.logs"
                :key="log.id"
                class="log-row"
            >
                <!-- 献立名 -->
                <div class="log-menu">
                {{ log.menu?.name }}
                </div>

                <!-- 時分 -->
                <div class="log-time">
                {{ formatTime(log.created_at) }}
                </div>

                <!-- process -->
                <div
                class="log-process"
                :class="{
                    heating: log.process?.name === '加熱',
                    cooling: log.process?.name === '冷却'
                }"
                >
                {{ log.process?.name }}
                </div>

                <!-- 作業者 -->
                <div class="log-operator">
                {{ log.operator?.name }}
                </div>

            </div>

            </div>

        </div>

    </div>

    <div class="mt-6 dashboard">

    <div
        v-for="block in blocks"
        :key="block.key"
        class="meal-card"
        :style="{ backgroundColor: block.color }"
    >
        <h3 class="text-center">今日の献立（{{ block.label }}）</h3>

        <div v-if="groupedMenus[block.key].length === 0">
        メニューなし
        </div>

        <div
        v-for="menu in groupedMenus[block.key]"
        :key="menu.id"
        class="menu-row"
        >
            <div class="menu-name">
                {{ menu.name }}
            </div>

            <div class="counts">
                🔥 {{ menu.heating_count }}
                ❄ {{ menu.cooling_count }}
            </div>
        </div>

    </div>

    </div>

 
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Autocomplete from '@/Components/Autocomplete.vue'
import DonutChart from '@/Components/DonutChart.vue'

import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { router,Link } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import axios from 'axios'
import { PlusIcon, PencilIcon, PrinterIcon, FunnelIcon, MagnifyingGlassIcon, DocumentPlusIcon} from '@heroicons/vue/24/outline'


const props = defineProps({
  today: Object,
  month: Object,  
  logs: Object,
  menus: Object,
  tenants: Array,
  user: Object,
  filters: Object,
  success: String,
})
console.log(props);

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
  { key: 'breakfast', label: '朝食', color: '#FFF3CD' },
  { key: 'snack_morning', label: 'おやつ(10)', color: '#D1ECF1' },
  { key: 'lunch', label: '昼食', color: '#D4EDDA' },
  { key: 'snack_afternoon', label: 'おやつ(15)', color: '#E2D6F3' },
  { key: 'dinner', label: '夕食', color: '#F8D7DA' }
]


const isSuperAdmin = computed(() =>
  props.user?.roles?.some(r => r.name.toLowerCase() === 'super admin')
)
// 検索フォーム・per_page・sort・sort_dirを reactive で管理
const openDrawer = ref(false)

const isUpdated = (log) => {
  return log.updated_at && log.created_at !== log.updated_at
}

// Form
const form = reactive({
  tenant_id: props.filters?.tenant_id,
})
// persistQueryに各検索項目を追加
const persistQuery = () => ({
  tenant_id: props.tenant_id,
})

// 小数点第一まで
const formatTemp = (v) => {
  return v != null ? Number(v).toFixed(1) : '-'
}

let interval = null

onMounted(() => {
  interval = setInterval(() => {
    router.reload({
      only: ['logs'], // コントローラで渡しているprops名
      preserveState: true,
      preserveScroll: true,
    })
  }, 300000) // 5分 = 300000ms
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
<style>
.temp-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 4px 12px; /* 縦 横 */
  list-style: none;
  padding: 0;
  margin: 0;
}
.dashboard {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 16px;
}

.meal-card {
  padding: 16px;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.menu-row {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;
  padding: 6px 0;
  border-bottom: 1px solid rgba(0,0,0,0.05);
}

.menu-name {
  font-weight: 500;
}

.counts {
  font-size: 14px;
}

.log-card {
  padding: 16px;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

.card-header {
  font-weight: 700;
  margin-bottom: 12px;
  font-size: 16px;
}

.log-row {
  display: grid;
  grid-template-columns: 1.6fr 0.8fr 0.8fr 1fr;
  gap: 8px;
  padding: 8px 0;
  border-bottom: 1px solid rgba(0,0,0,0.05);
  font-size: 14px;
  align-items: center;
}

.log-menu {
  font-weight: 600;
}

.log-time {
  color: #777;
  font-size: 13px;
}

.log-operator {
  font-size: 13px;
  color: #444;
}

.heating {
  color: #e67e22;
  font-weight: bold;
}

.cooling {
  color: #3498db;
  font-weight: bold;
}

.time { color: #666; }

</style>




