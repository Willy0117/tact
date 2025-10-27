<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

// Heroicons
import { HomeIcon, UserIcon, ServerIcon, UsersIcon, PlusIcon, BuildingOfficeIcon, TicketIcon, ArrowRightOnRectangleIcon, Cog6ToothIcon, CubeIcon, BeakerIcon } from '@heroicons/vue/24/outline'

const { props } = usePage()

// Jetstream props
const authUser = props.auth.user
const currentTeam = authUser.current_team
const currentTeamId = authUser.current_team_id
const allTeams = authUser.all_teams
const hasApiFeatures = props.jetstream.hasApiFeatures
const hasTeamFeatures = props.jetstream.hasTeamFeatures
const canCreateTeams = props.jetstream.canCreateTeams

// サイドバー状態
const collapsed = ref(JSON.parse(localStorage.getItem('sidebar-collapsed') ?? 'false'))
const openSubMenu = ref(null)

const { t, locale } = useI18n()

// レスポンシブ判定
const isMobile = ref(false)
const handleResize = () => { isMobile.value = window.innerWidth < 1024 }
onMounted(() => {
  handleResize()
  window.addEventListener('resize', handleResize)
})
onBeforeUnmount(() => window.removeEventListener('resize', handleResize))

// トグル
const toggleCollapse = () => { if (!isMobile.value) collapsed.value = !collapsed.value }
const toggleSubMenu = (name) => { openSubMenu.value = openSubMenu.value === name ? null : name }

// ページ遷移でサブメニュー閉じる
watch(() => router.page, () => { openSubMenu.value = null })

// collapsed 状態保存
watch(collapsed, val => { localStorage.setItem('sidebar-collapsed', JSON.stringify(val)) })

// ヘッダー操作
const logout = () => { router.post(route('logout')) }
const switchTeam = (team) => { router.put(route('current-team.update'), { team_id: team.id }) }
const isActive = (name) => route().current(name)
</script>

<template>
  <div class="flex min-h-screen bg-gray-100">

    <!-- サイドバー -->
    <aside v-if="!isMobile" :class="['bg-gray-50 text-gray-800 shadow-lg h-full flex flex-col transition-all duration-300 z-50 overflow-hidden', collapsed ? 'w-16' : 'w-64']">
      <div class="flex justify-end p-2">
        <button @click="toggleCollapse" class="text-gray-500 hover:text-gray-700">
          <span v-if="collapsed">➡</span>
          <span v-else>⬅</span>
        </button>
      </div>

      <nav class="flex-1 px-2 py-4">
        <!-- Dashboard -->
        <Link :href="route('dashboard')"
              class="flex items-center py-2 px-2 rounded hover:bg-gray-200 transition-colors"
              :class="isActive('dashboard') ? 'bg-gray-300 font-semibold' : ''">
          <HomeIcon class="w-5 h-5"/>
          <span v-if="!collapsed" class="ml-2">{{ t('dashboard') }}</span>
        </Link>
    <!-- SaaS 会社管理メニュー -->
        <Link :href="route('admin.companies.index')"
              class="flex items-center py-2 px-2 rounded hover:bg-gray-200 transition-colors"
              :class="isActive('companies') ? 'bg-gray-300 font-semibold' : ''">
          <BuildingOfficeIcon class="w-5 h-5" />
          <span v-if="!collapsed" class="ml-2">{{ t('companies') }}</span>
        </Link>
        <!-- Profile サブメニュー -->
        <div class="mt-2">
          <button @click="toggleSubMenu('profile')"
                  class="flex items-center justify-between w-full py-2 px-2 rounded hover:bg-gray-200 transition-colors">
            <div class="flex items-center">
              <UserIcon class="w-5 h-5"/>
              <span v-if="!collapsed" class="ml-2">{{ t('profile') }}</span>
            </div>
            <svg v-if="!collapsed" :class="{'rotate-90': openSubMenu==='profile'}" class="w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </button>
          <transition name="slide-fade">
            <div v-show="openSubMenu==='profile' && !collapsed" class="pl-6 mt-1 space-y-1">
              <Link :href="route('profile.show')"
                    class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                    :class="isActive('profile.show') ? 'bg-gray-200 font-semibold' : ''">
                <UserIcon class="w-4 h-4 mr-1"/>
                Profile Settings
              </Link>
              <Link v-if="hasApiFeatures" :href="route('api-tokens.index')"
                    class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                    :class="isActive('api-tokens.index') ? 'bg-gray-200 font-semibold' : ''">
                <ServerIcon class="w-4 h-4 mr-1"/>
                API Tokens
              </Link>
              <!--button @click="logout" class="flex items-center w-full text-left py-2 px-2 rounded hover:bg-gray-100">
                <ArrowRightOnRectangleIcon class="w-4 h-4 mr-1"/>
                Log Out
              </button -->
            </div>
          </transition>
          <!-- ここからマスター系メニュー -->
          <button @click="toggleSubMenu('masters')"
                  class="flex items-center justify-between w-full py-2 px-2 rounded hover:bg-gray-200 transition-colors mt-2">
            <div class="flex items-center">
              <Cog6ToothIcon class="w-5 h-5"/>
              <span v-if="!collapsed" class="ml-2">{{ t('masters') }}</span>
            </div>
            <svg v-if="!collapsed" :class="{'rotate-90': openSubMenu==='masters'}" class="w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </button>
          <transition name="slide-fade">
            <div v-show="openSubMenu==='masters' && !collapsed" class="pl-6 mt-1 space-y-1">
              <Link :href="route('devices.index')"
                    class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                    :class="isActive('devices.index') ? 'bg-gray-200 font-semibold' : ''">
                <CubeIcon class="w-4 h-4 mr-1"/>
                {{ t('devices') }}
              </Link>

              <Link :href="route('operators.index')"
                    class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                    :class="isActive('operators.index') ? 'bg-gray-200 font-semibold' : ''">
                <UsersIcon class="w-4 h-4 mr-1"/>
                {{ t('operators') }}
              </Link>

              <Link :href="route('sensors.index')"
                    class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                    :class="isActive('sensors.index') ? 'bg-gray-200 font-semibold' : ''">
                <BeakerIcon class="w-4 h-4 mr-1"/>
                {{ t('sensors') }}
              </Link>
            </div>
          </transition>          
        </div>

        <!-- Teams -->
        <div v-if="hasTeamFeatures" class="mt-4 border-t border-gray-200 pt-2 text-xs text-gray-400 px-2">Manage Team</div>
        <div v-if="hasTeamFeatures" class="mt-1 space-y-1">
          <Link :href="route('teams.show', currentTeam)"
                class="flex items-center py-2 px-2 rounded hover:bg-gray-200 transition-colors"
                :class="isActive('teams.show') ? 'bg-gray-300 font-semibold' : ''">
            <UsersIcon class="w-5 h-5"/>
            <span v-if="!collapsed" class="ml-2">Team Settings</span>
          </Link>
          <Link v-if="canCreateTeams" :href="route('teams.create')"
                class="flex items-center py-2 px-2 rounded hover:bg-gray-200 transition-colors"
                :class="isActive('teams.create') ? 'bg-gray-300 font-semibold' : ''">
            <PlusIcon class="w-5 h-5"/>
            <span v-if="!collapsed" class="ml-2">Create Team</span>
          </Link>
          <div v-for="team in allTeams" :key="team.id">
            <form @submit.prevent="switchTeam(team)">
              <button type="submit" class="flex items-center w-full py-2 px-2 rounded hover:bg-gray-200 transition-colors">
                <svg v-if="team.id===currentTeamId" class="w-4 h-4 mr-2 text-green-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <span v-if="!collapsed">{{ team.name }}</span>
              </button>
            </form>
          </div>
        </div>

      </nav>
    </aside>

    <!-- メイン -->
    <div class="flex-1 flex flex-col">
      <header class="bg-white shadow flex items-center justify-between px-4 h-16">
        <h1 class="text-lg font-semibold">My App</h1>

        <div class="flex items-center space-x-2">
          <button v-if="isMobile" class="lg:hidden p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          <!-- button @click="logout" class="p-2 text-gray-700 hover:text-gray-900">Logout</button -->
        </div>
      </header>

      <main class="p-4">
        <slot />
      </main>
    </div>
  </div>

  <style>
    .slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.2s ease; }
    .slide-fade-enter-from, .slide-fade-leave-to { opacity: 0; max-height: 0; }
    .slide-fade-enter-to, .slide-fade-leave-from { opacity: 1; max-height: 500px; }
  </style>
</template>



