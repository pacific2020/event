<script setup lang="ts">
import { useAuthStore } from '@/store/auth';
import axiosInstance from '@/axios';
import { useQuery } from '@tanstack/vue-query';
import { 
  TicketIcon, 
  CheckBadgeIcon, 
  UserCircleIcon,
  PresentationChartBarIcon 
} from '@heroicons/vue/24/outline'

const auth = useAuthStore();

/**
 * Fetcher function
 */
const fetchStats = async () => {
  // Ensure we wait for the user to be available if auth.getUser() is needed
  if (!auth.user?.id) await auth.getUser();
  
  const { data } = await axiosInstance.get(`/scanner-stats/${auth.user?.id}`);
  return data;
};

/**
 * useQuery implementation
 * @refetchInterval 2000 - Polls the API every 2 seconds
 */
const { data: stats, isLoading } = useQuery({
  queryKey: ['scanner-stats', auth.user?.id],
  queryFn: fetchStats,
  refetchInterval: 2000, // Check after every 2 seconds
  refetchIntervalInBackground: true, // Keep checking even if tab is unfocused (optional)
});
</script>

<template>
  <div class="flex min-h-screen bg-slate-50">
    <div class="flex-1">
      <header class="bg-white border-b border-slate-200 px-8 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
          <PresentationChartBarIcon class="w-6 h-6 text-indigo-600" />
          Scanner Report
        </h1>
        <div class="flex items-center gap-3">
          <span class="text-sm font-medium text-slate-600">{{ auth.user?.fullname }}</span>
          <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold">
            {{ auth.user?.fullname?.charAt(0) }}
          </div>
        </div>
      </header>

      <main class="p-8 max-w-7xl mx-auto">
        <div class="mb-8">
          <h2 class="text-2xl font-black text-slate-900">Welcome Back! 👋</h2>
          <p class="text-slate-500">Here is the real-time invitation tracking report.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
          
          <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
            <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center mb-4 text-blue-600">
              <TicketIcon class="w-7 h-7" />
            </div>
            <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Total Invitations</p>
            <h3 class="text-3xl font-black text-slate-900 mt-1">
              {{ isLoading ? '...' : stats?.total_invitations ?? 0 }}
            </h3>
          </div>

          <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center mb-4 text-emerald-600">
              <CheckBadgeIcon class="w-7 h-7" />
            </div>
            <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Global Scanned</p>
            <h3 class="text-3xl font-black text-slate-900 mt-1">
              {{ isLoading ? '...' : stats?.scanned_total ?? 0 }}
            </h3>
          </div>

          <div class="bg-white p-6 rounded-3xl border border-indigo-100 shadow-md ring-2 ring-indigo-500/5">
            <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center mb-4 text-white">
              <UserCircleIcon class="w-7 h-7" />
            </div>
            <p class="text-sm font-bold text-indigo-600 uppercase tracking-wider">Your Personal Scans</p>
            <h3 class="text-3xl font-black text-slate-900 mt-1">
              {{ isLoading ? '...' : stats?.my_scans ?? 0 }}
            </h3>
            <p class="text-[10px] text-slate-400 mt-2 italic font-medium">Auto-refreshing every 2s</p>
          </div>

        </div>

        <div class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm">
           <h3 class="font-bold text-slate-800 mb-4">Quick Tip</h3>
           <p class="text-slate-500 text-sm leading-relaxed">
             To scan a new invitation, go to the <strong>Issuance List</strong> and use the "Update Manually" action or use the mobile 
             <router-link target="_blank" to="/Scanner/open" class="text-indigo-600 underline font-semibold">
               scanner app
             </router-link>. All records updated by you are reflected in "Your Personal Scans" above.
           </p>
        </div>
      </main>
    </div>
  </div>
</template>