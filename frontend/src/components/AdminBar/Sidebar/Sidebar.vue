<script setup>
import { onMounted, ref } from 'vue'
import { useAuthStore } from '@/store/auth'

// UI State
const isCollapsed = ref(false)
const openMenus = ref({
  user: false,
  event: false,
  invitation: false,
  gown: false
})

const auth = useAuthStore()

const toggleMenu = (menu) => {
  openMenus.value[menu] = !openMenus.value[menu]
}

onMounted(async () => {
  await auth.getUser()
})
</script>

<template>
  <aside 
    v-if="auth.user"
    :class="[isCollapsed ? 'w-20' : 'w-64']"
    class="h-screen bg-gray-900 text-gray-100 transition-all duration-300 flex flex-col fixed left-0 top-0"
  >
    <div class="p-4 border-b border-gray-800 flex justify-between items-center">
      <h2 v-if="!isCollapsed" class="text-xl font-bold truncate">
        {{ auth.user.position || 'Dashboard' }}
      </h2>
      <button @click="isCollapsed = !isCollapsed" class="p-1 hover:bg-gray-800 rounded">
        {{ isCollapsed ? '→' : '←' }}
      </button>
    </div>

    <nav class="flex-1 overflow-y-auto p-4 space-y-2">
      
      <template v-if="auth.user.position === 'Admin'">
         <router-link to="/Admin/dashboard" class="menu-btn">Dashboard</router-link>
        <div>
          <button @click="toggleMenu('user')" class="menu-btn">
            <span>👤 User</span>
            <span v-if="!isCollapsed">{{ openMenus.user ? '−' : '+' }}</span>
          </button>
          <div v-if="openMenus.user && !isCollapsed" class="pl-6 mt-1 space-y-1">
            <router-link to="/Admin/user/create" class="sub-link">Create User</router-link>
            <router-link to="/Admin/user/view" class="sub-link">View Users</router-link>
          </div>
        </div>

        <div class="mt-2">
          <button @click="toggleMenu('event')" class="menu-btn">
            <span>📅 Event</span>
            <span v-if="!isCollapsed">{{ openMenus.event ? '−' : '+' }}</span>
          </button>
          <div v-if="openMenus.event && !isCollapsed" class="pl-6 mt-1 space-y-1">
            <router-link to="/Admin/event/create" class="sub-link">Create Event</router-link>
            <router-link to="/Admin/event/view" class="sub-link">View Events</router-link>
          </div>
        </div>

        <div class="mt-2">
          <button @click="toggleMenu('invitation')" class="menu-btn">
            <span>✉️ Invitation</span>
            <span v-if="!isCollapsed">{{ openMenus.invitation ? '−' : '+' }}</span>
          </button>
          <div v-if="openMenus.invitation && !isCollapsed" class="pl-6 mt-1 space-y-1">
            <router-link to="/Admin/invitation/guest" class="sub-link">Guest Invite</router-link>
            <router-link to="/Admin/invitation/view" class="sub-link">All Invites</router-link>
            <router-link to="/Admin/graduation/list" class="sub-link">Graduation List</router-link>
          </div>
        </div>
      </template>










      <template v-else-if="auth.user.position === 'Scanner'">
        <router-link to="/Scanner/dashboard" class="menu-btn">📊 Dashboard</router-link>
        <button @click="toggleMenu('event')" class="menu-btn mt-2">
          <span>🔍 Scan Invites</span>
          <span v-if="!isCollapsed">{{ openMenus.event ? '−' : '+' }}</span>
        </button>
        <div v-if="openMenus.event && !isCollapsed" class="pl-6 mt-1">
          <router-link to="/Scanner/invitation/view" class="sub-link">View All</router-link>
        </div>
      </template>

      <template v-else-if="auth.user.position === 'RecordOfficer'">
         <router-link to="/RecordOfficer/dashboard" class="menu-btn">Dashboard</router-link>
        <button @click="toggleMenu('gown')" class="menu-btn">
          <span>🎓 Gown</span>
          <span v-if="!isCollapsed">{{ openMenus.gown ? '−' : '+' }}</span>
        </button>
        <div v-if="openMenus.gown && !isCollapsed" class="pl-6 mt-1 space-y-1">
          <router-link to="/RecordOfficer/gown/create" class="sub-link">Issue Gown</router-link>
          <router-link to="/RecordOfficer/gown/view" class="sub-link">Gown Logs</router-link>
        </div>
          <router-link to="/RecordOfficer/pickup/view" class="sub-link">Pickup</router-link>

      </template>

      <template v-else-if="auth.user.position === 'Protocol'">
        <router-link to="/Protocol/dashboard" class="menu-btn">🏠 Main Dashboard</router-link>
         <button @click="toggleMenu('event')" class="menu-btn mt-2">
          <span>🔍 Scan Invites</span>
          <span v-if="!isCollapsed">{{ openMenus.event ? '−' : '+' }}</span>
        </button>
        <div v-if="openMenus.event && !isCollapsed" class="pl-6 mt-1">
          <router-link to="/Protocol/invitation/view" class="sub-link">View All</router-link>
        </div>
      </template>

    </nav>

    <div class="p-4 border-t border-gray-800">
      <button 
        @click="auth.logout" 
        class="w-full flex items-center p-2 text-red-400 hover:bg-red-900/20 rounded transition-colors"
      >
        <span>🚪</span>
        <span v-if="!isCollapsed" class="ml-2">Logout</span>
      </button>
    </div>
  </aside>

  <div :class="[isCollapsed ? 'ml-20' : 'ml-64']" class="transition-all duration-300"></div>
</template>

<style scoped>
.menu-btn {
  @apply w-full flex justify-between items-center p-3 hover:bg-gray-800 rounded transition-colors text-left;
}
.sub-link {
  @apply block p-2 text-sm text-gray-400 hover:text-white hover:bg-gray-800 rounded transition-colors;
}
/* Active router link styling */
.router-link-active {
  @apply bg-blue-600 text-white font-semibold;
}
</style>