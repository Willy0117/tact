<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

import {
  Home, User,  Server, Users, Plus, Building2, Ticket, LogOut, Settings, Box, FlaskConical, Menu, X, ShieldCheck, ChevronRight, Check,
  Utensils, Upload, Blender, SmartphoneNfc, Gauge, CalendarDays, CookingPot, Thermometer,
} from '@lucide/vue'

const page = usePage()

const mobileOpen = ref(false)       // モバイル用の開閉状態

const collapsed = ref(false)
const openSubMenu = ref(null)

const toggleCollapse = () => (collapsed.value = !collapsed.value)
const toggleSubMenu = (menu) => (openSubMenu.value = openSubMenu.value === menu ? null : menu)

const { props } = usePage()
console.log(props)
// Jetstream props
const authUser = props.auth.user
const currentTeam = authUser.current_team
const currentTeamId = authUser.current_team_id
const allTeams = authUser.all_teams
const hasApiFeatures = props.jetstream.hasApiFeatures
const hasTeamFeatures = props.jetstream.hasTeamFeatures
const canCreateTeams = props.jetstream.canCreateTeams

const { t, locale } = useI18n()

// ページURLに応じて初期サブメニューを決定
onMounted(() => {
  if (page.url.startsWith('/temperatures')) {
    openSubMenu.value = 'temperatures'
  }
  if (page.url.startsWith('/menus') || page.url.startsWith('/menus/weekly') || page.url.startsWith('/menus/import')) {
    openSubMenu.value = 'menus'
  }
  if (page.url.startsWith('/tenants') || page.url.startsWith('/roles') || page.url.startsWith('/permissions')) {
    openSubMenu.value = 'access'
  }
  if (page.url.startsWith('/devices') || page.url.startsWith('/operators') || page.url.startsWith('/sensors') || page.url.startsWith('/processes') ) {
    openSubMenu.value = 'masters'
  }
  if (page.url.startsWith('/users')) {
    openSubMenu.value = 'users'
  }
})

// ヘッダー操作
const logout = () => { router.post(route('logout')) }
const switchTeam = (team) => { router.put(route('current-team.update'), { team_id: team.id }) }
const isActive = (name) => route().current(name)

// ---------------------------
// Permission helper (Vue側)
// ---------------------------
const user = usePage().props.auth.user || null

console.log(user)

const can = (permissionName) => {
  if (!user) return false

  // permissions を安全に配列化
  const perms = Array.isArray(user.permissions)
    ? user.permissions
    : (user.permissions?.data ?? [])
  if (perms.length > 0) {
    return perms.some(p => p.name === permissionName)
  }

  // role を配列化
  const roles = Array.isArray(user.roles) ? user.roles : (user.roles?.data ?? [])

  if (roles.length > 0) {
    // Super Admin は全権限
    if (roles.some(r => ['super admin', 'super-admin'].includes(r.name.toLowerCase()))) {
      return true
    }

    // Tenant Admin は一部権限のみ
    if (roles.some(r => r.name.toLowerCase().startsWith('tenant_admin'))) {
      return ['manage roles', 'manage permissions'].includes(permissionName)
    }
  }

  return false
}

// showAccessControl : セクション丸ごと表示判定
const showAccessControl = computed(() => {
  if (!user) return false

  // Super Admin は全て表示
  if (user.roles?.some(r => r.name.toLowerCase() === 'super admin')) {
    return true
  }

  // テナント管理者は role / permission のみ表示
  return can('manage roles') || can('manage permissions')
})
</script>

<template>
  <div class="flex">
    <!-- モバイル用ハンバーガー -->
    <button
      @click="mobileOpen = !mobileOpen"
      class="lg:hidden p-2 rounded-full hover:bg-gray-200"
    >
      <template v-if="mobileOpen">
        <X class="w-5 h-5 text-gray-600" />
      </template>
      <template v-else>
        <Menu class="w-5 h-5 text-gray-600" />
      </template>
    </button>

    <!-- サイドバー -->
    <aside
      :class="[
        'bg-gray-100 h-screen flex flex-col transition-all duration-300 z-50',
        collapsed ? 'w-16' : 'w-64',
        mobileOpen ? 'left-0' : '-left-full',
        'fixed top-0 lg:relative lg:left-0 h-screen'
      ]"
    >
      <!-- PC折りたたみボタン -->
      <div class="flex justify-end p-2 flex-none lg:flex">
        <button
          @click="toggleCollapse"
          class="p-2 rounded-full hover:bg-gray-200"
        >
          <template v-if="collapsed">
            <Menu class="w-5 h-5 text-gray-600" />
          </template>
          <template v-else>
            <X class="w-5 h-5 text-gray-600" />
          </template>
        </button>
      </div>
       <nav class="flex-1 overflow-y-auto px-2 py-4 text-sm">
      <!-- Dashboard -->
      <Link :href="route('dashboard')"
            class="flex items-center py-2 px-2 rounded hover:bg-gray-200 transition-colors"
            :class="isActive('dashboard') ? 'bg-gray-300 font-semibold' : ''">
        <Home class="w-5 h-5"/>
        <span v-if="!collapsed" class="ml-2">{{ t('dashboard') }}</span>
      </Link>
      <!-- 温度計測メニュー -->
      <button @click="toggleSubMenu('temperatures')"
              class="flex items-center justify-between w-full py-2 px-2 rounded hover:bg-gray-200 transition-colors">
        <div class="flex items-center">
          <Gauge class="w-5 h-5"/>
          <span v-if="!collapsed" class="ml-2">{{ t('temperatures') }}</span>
        </div>
        <ChevronRight v-if="!collapsed" :class="{'rotate-90': openSubMenu==='temperatures'}" class="w-4 h-4 transform transition-transform duration-200" />
      </button>
      <transition name="slide-fade">
        <div v-show="openSubMenu==='temperatures' && !collapsed" class="pl-6 mt-1 space-y-1">
          <Link :href="route('temperatures.by-serving-date')"
                class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                :class="isActive('temperatures.by-serving-date') ? 'bg-gray-200 font-semibold' : ''">
            <CalendarDays class="w-4 h-4 mr-1"/>
            {{ t('by_serving_date') }}
          </Link>

          <Link :href="route('temperatures.by-cooking-date')"
                class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                :class="isActive('temperatures.by-cooking-date') ? 'bg-gray-200 font-semibold' : ''">
            <CookingPot class="w-4 h-4 mr-1"/>
            {{ t('by_cooking_date') }}
          </Link>

          <Link :href="route('temperatures.index')"
                class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                :class="isActive('temperatures.index') ? 'bg-gray-200 font-semibold' : ''">
            <Thermometer class="w-4 h-4 mr-1"/>
            {{ t('all_select') }}
          </Link>
        </div>
      </transition>

      <!-- 献立関連メニュー -->
      <button @click="toggleSubMenu('menus')"
              class="flex items-center justify-between w-full py-2 px-2 rounded hover:bg-gray-200 transition-colors mt-2">
        <div class="flex items-center">
          <Utensils class="w-5 h-5"/>
          <span v-if="!collapsed" class="ml-2">{{ t('menus') }}</span>
        </div>
        <ChevronRight v-if="!collapsed" :class="{'rotate-90': openSubMenu==='menus'}" class="w-4 h-4 transform transition-transform duration-200" />
      </button>
      <transition name="slide-fade">
        <div v-show="openSubMenu==='menus' && !collapsed" class="pl-6 mt-1 space-y-1">
          <Link :href="route('menus.index')"
                class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                :class="isActive('menus.index') ? 'bg-gray-200 font-semibold' : ''">
            <Utensils class="w-4 h-4 mr-1"/>
            {{ t('menu_list') }}
          </Link>

          <Link :href="route('menus.weekly')"
                class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                :class="isActive('menus.weekly') ? 'bg-gray-200 font-semibold' : ''">
            <Utensils class="w-4 h-4 mr-1"/>
            {{ t('weekly_menu') }}
          </Link>

          <Link :href="route('menus.import')"
                class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                :class="isActive('menus.import') ? 'bg-gray-200 font-semibold' : ''">
            <Upload class="w-4 h-4 mr-1"/>
            {{ t('excel_menu_import') }}
          </Link>
        </div>
      </transition>       
      <!-- Profile サブメニュー -->
      <div class="mt-2">
        <button @click="toggleSubMenu('profile')"
                class="flex items-center justify-between w-full py-2 px-2 rounded hover:bg-gray-200 transition-colors">
          <div class="flex items-center">
            <User class="w-5 h-5"/>
            <span v-if="!collapsed" class="ml-2">{{ t('profile') }}</span>
          </div>
          <ChevronRight v-if="!collapsed" :class="{'rotate-90': openSubMenu==='profile'}" class="w-4 h-4 transform transition-transform duration-200" />
        </button>
        <transition name="slide-fade">
          <div v-show="openSubMenu==='profile' && !collapsed" class="pl-6 mt-1 space-y-1">
            <Link :href="route('profile.show')"
                  class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                  :class="isActive('profile.show') ? 'bg-gray-200 font-semibold' : ''">
              <User class="w-4 h-4 mr-1"/>
              {{ t('profile setting') }}
            </Link>
            <Link v-if="hasApiFeatures" :href="route('api-tokens.index')"
                  class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                  :class="isActive('api-tokens.index') ? 'bg-gray-200 font-semibold' : ''">
              <Server class="w-4 h-4 mr-1"/>
              API Tokens
            </Link>
            <!--button @click="logout" class="flex items-center w-full text-left py-2 px-2 rounded hover:bg-gray-100">
              <LogOut class="w-4 h-4 mr-1"/>
              Log Out
            </button -->
          </div>
        </transition>
        <!-- Access Control -->
        <template v-if="showAccessControl">
          <button @click="toggleSubMenu('access')"
                  class="flex items-center justify-between w-full py-2 px-2 rounded hover:bg-gray-200 transition-colors mt-2">
            <div class="flex items-center">
              <ShieldCheck class="w-5 h-5"/>
              <span v-if="!collapsed" class="ml-2">{{ t('access_control') }}</span>
            </div>
            <ChevronRight v-if="!collapsed" :class="{'rotate-90': openSubMenu==='access'}" class="w-4 h-4 transform transition-transform duration-200" />
          </button>

          <transition name="slide-fade">
            <div v-show="openSubMenu==='access' && !collapsed" class="pl-6 mt-1 space-y-1">
              <Link
                v-if="can('manage tenants')"
                :href="route('tenants.index')"
                class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                :class="isActive('tenants.index') ? 'bg-gray-200 font-semibold' : ''"
              >
                <Building2 class="w-4 h-4 mr-1"/>
                {{ t('tenants') }}
              </Link>

              <Link
                v-if="can('manage roles')"
                :href="route('roles.index')"
                class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                :class="isActive('roles.index') ? 'bg-gray-200 font-semibold' : ''"
              >
                <Users class="w-4 h-4 mr-1"/>
                {{ t('roles') }}
              </Link>

              <Link
                v-if="can('manage permissions')"
                :href="route('permissions.index')"
                class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                :class="isActive('permissions.index') ? 'bg-gray-200 font-semibold' : ''"
              >
                <Ticket class="w-4 h-4 mr-1"/>
                {{ t('permissions') }}
              </Link>
            </div>
          </transition>
        </template>
        <!-- ここからマスター系メニュー -->

        <button @click="toggleSubMenu('masters')"
                class="flex items-center justify-between w-full py-2 px-2 rounded hover:bg-gray-200 transition-colors mt-2">
          <div class="flex items-center">
            <Settings class="w-5 h-5"/>
            <span v-if="!collapsed" class="ml-2">{{ t('masters') }}</span>
          </div>
          <ChevronRight v-if="!collapsed" :class="{'rotate-90': openSubMenu==='masters'}" class="w-4 h-4 transform transition-transform duration-200" />
        </button>
        <transition name="slide-fade">
          <div v-show="openSubMenu==='masters' && !collapsed" class="pl-6 mt-1 space-y-1">
            <Link :href="route('devices.index')"
                  class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                  :class="isActive('devices.index') ? 'bg-gray-200 font-semibold' : ''">
              <Blender class="w-4 h-4 mr-1"/>
              {{ t('devices') }}
            </Link>

            <Link :href="route('operators.index')"
                  class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                  :class="isActive('operators.index') ? 'bg-gray-200 font-semibold' : ''">
              <Users class="w-4 h-4 mr-1"/>
              {{ t('operators') }}
            </Link>

            <Link :href="route('sensors.index')"
                  class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                  :class="isActive('sensors.index') ? 'bg-gray-200 font-semibold' : ''">
              <SmartphoneNfc class="w-4 h-4 mr-1"/>
              {{ t('sensors') }}
            </Link>
            <Link :href="route('processes.index')"
                  class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                  :class="isActive('processes.index') ? 'bg-gray-200 font-semibold' : ''">
              <FlaskConical class="w-4 h-4 mr-1"/>
              {{ t('process') }}
            </Link>
          </div>
        </transition>          
      </div>
      <!-- ここからユーザーメニュー（マスターとは独立） -->
      <div>
        <button @click="toggleSubMenu('users')"
                class="flex items-center justify-between w-full py-2 px-2 rounded hover:bg-gray-200 transition-colors mt-2">
          <div class="flex items-center">
            <Users class="w-5 h-5"/>
            <span v-if="!collapsed" class="ml-2">{{ t('user') }}</span>
          </div>
          <ChevronRight v-if="!collapsed" :class="{'rotate-90': openSubMenu==='users'}" class="w-4 h-4 transform transition-transform duration-200" />
        </button>

        <transition name="slide-fade">
          <div v-show="openSubMenu==='users' && !collapsed" class="pl-6 mt-1 space-y-1">
            <Link :href="route('users.index')"
                  class="flex items-center py-2 px-2 rounded hover:bg-gray-100"
                  :class="isActive('users.index') ? 'bg-gray-200 font-semibold' : ''">
              <User class="w-4 h-4 mr-1"/>
              {{ t('user') }}
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
          <Users class="w-5 h-5"/>
          <span v-if="!collapsed" class="ml-2">Team Settings</span>
        </Link>
        <Link v-if="canCreateTeams" :href="route('teams.create')"
              class="flex items-center py-2 px-2 rounded hover:bg-gray-200 transition-colors"
              :class="isActive('teams.create') ? 'bg-gray-300 font-semibold' : ''">
          <Plus class="w-5 h-5"/>
          <span v-if="!collapsed" class="ml-2">Create Team</span>
        </Link>
        <div v-for="team in allTeams" :key="team.id">
          <form @submit.prevent="switchTeam(team)">
            <button type="submit" class="flex items-center w-full py-2 px-2 rounded hover:bg-gray-200 transition-colors">
              <Check v-if="team.id===currentTeamId" class="w-4 h-4 mr-2 text-green-400" />
              <span v-if="!collapsed">{{ team.name }}</span>
            </button>
          </form>
        </div>
      </div>

    </nav>
  </aside>
      <!-- モバイルオーバーレイ -->
  <div
    v-if="mobileOpen"
    class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
    @click="mobileOpen = false"
  ></div>
  </div>
  <style>
    .slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.2s ease; }
    .slide-fade-enter-from, .slide-fade-leave-to { opacity: 0; max-height: 0; }
    .slide-fade-enter-to, .slide-fade-leave-from { opacity: 1; max-height: 500px; }
  </style>
</template>