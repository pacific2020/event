<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { useGraduandStore } from '@/store/graduand';
import axiosInstance from '@/axios';

// ✅ Added missing Icon imports from Heroicons
import { 
  Bars3Icon, 
  HomeIcon, 
  EnvelopeIcon, 
  AcademicCapIcon, 
  ArrowRightOnRectangleIcon 
} from '@heroicons/vue/24/outline';

const auth = useGraduandStore();
const gown = ref<any>(null);
const loading = ref(true);
const isCollapsed = ref(false); // ✅ Defined isCollapsed
let pollInterval: any = null;

// --- Gown Polling Logic ---
const getGownStatus = async () => {
  if (!auth.user?.reg_no) return;
  
  try {
    const { data } = await axiosInstance.get(`/view-gown/${auth.user.reg_no}`);
    // Handle both array and object responses
    gown.value = Array.isArray(data) ? data[0] : data.data;

    if (gown.value && gown.value.status !== 'Pending') {
      stopPolling();
    }
  } catch (e) {
    console.error("Polling failed");
  } finally {
    loading.value = false;
  }
};

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval);
    pollInterval = null;
  }
};

onMounted(async () => {
  if (!auth.user) await auth.getUser();
  await getGownStatus();

  if (!gown.value || gown.value.status === 'Pending') {
    pollInterval = setInterval(getGownStatus, 2000);
  }
});

onUnmounted(() => {
  stopPolling();
});
</script>

<template>
  <aside 
    v-if="auth.user"
    :class="[isCollapsed ? 'w-20' : 'w-64']"
    class="h-screen bg-gray-900 text-gray-100 transition-all duration-300 flex flex-col fixed left-0 top-0 z-50 shadow-xl"
  >
    <div class="p-4 border-b border-gray-800 flex items-center justify-between overflow-hidden">
      <h2 v-if="!isCollapsed" class="text-xl font-bold truncate">
        ID: <span class="text-blue-400">{{ auth.user.reg_no }}</span>
      </h2>
      <button @click="isCollapsed = !isCollapsed" class="p-2 hover:bg-gray-800 rounded-lg transition-colors">
        <Bars3Icon class="w-6 h-6 text-gray-400" />
      </button>
    </div>

    <nav class="flex-1 overflow-y-auto p-4 space-y-2">
      <router-link to="/Graduand/dashboard" class="flex items-center p-3 hover:bg-gray-800 rounded-lg transition-colors group">
        <HomeIcon class="w-6 h-6 text-gray-400 group-hover:text-white" />
        <span v-if="!isCollapsed" class="ml-4 font-medium">Dashboard</span>
      </router-link>

      <router-link to="/Graduand/generate/create" class="flex items-center p-3 hover:bg-gray-800 rounded-lg transition-colors group">
        <EnvelopeIcon class="w-6 h-6 text-gray-400 group-hover:text-white" />
        <span v-if="!isCollapsed" class="ml-4 font-medium">Invitations</span>
      </router-link>

      <router-link to="/Graduand/gown/status" class="flex items-center p-3 hover:bg-gray-800 rounded-lg transition-colors group">
        <AcademicCapIcon class="w-6 h-6 text-gray-400 group-hover:text-white" />
        <span v-if="!isCollapsed" class="ml-4 font-medium">Gown Service</span>
      </router-link>
    </nav>

    <div class="p-4 border-t border-gray-800">
      <button 
        @click="auth.logout"
        class="w-full flex items-center p-3 text-red-400 hover:bg-red-900/20 rounded-lg transition-colors group"
      >
        <ArrowRightOnRectangleIcon class="w-6 h-6" />
        <span v-if="!isCollapsed" class="ml-4 font-bold">Logout</span>
      </button>
    </div>
  </aside>

  <div :class="[isCollapsed ? 'ml-20' : 'ml-64']" class="transition-all duration-300"></div>
</template>

<style scoped>
.router-link-active {
  background-color: #1e3a8a;
  color: #ffffff;
}
.router-link-active svg {
  color: #3b82f6;
}
</style>