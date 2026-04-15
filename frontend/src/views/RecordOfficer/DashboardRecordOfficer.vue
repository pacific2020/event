<script setup lang="ts">
import { useAuthStore } from '@/store/auth';
import axiosInstance from '@/axios';
import { useQuery } from '@tanstack/vue-query';
import { 
  ShoppingBagIcon, // Changed to a gown-related icon
  CheckBadgeIcon, 
  ArrowPathIcon,   // Icon for "Returned"
} from '@heroicons/vue/24/outline'

const auth = useAuthStore();

/**
 * Fetcher function
 */
const fetchStats = async () => {
  // Wait for user to be loaded to get the ID
  if (!auth.user?.id) await auth.getUser();
  
  // Matches your PHP route: /gown-stats/{user_id}
  const { data } = await axiosInstance.get(`/gown-stats/${auth.user?.id}`);
  return data;
};

/**
 * useQuery implementation
 */
const { data: stats, isLoading } = useQuery({
  queryKey: ['gown-stats', auth.user?.id], // Updated key name
  queryFn: fetchStats,
  refetchInterval: 2000, 
  enabled: !!auth.user?.id || !!localStorage.getItem('token'), // Only run if we have a user/token
});
</script>


<template>
  <div class="flex min-h-screen bg-slate-50">
    <div class="flex-1">
  
      <main class="p-8 max-w-7xl mx-auto">
        <div class="mb-8">
          <h2 class="text-2xl font-black text-slate-900">Gown Management 👋</h2>
          <p class="text-slate-500">Real-time tracking for issued and returned academic gowns.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
          
          <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
            <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center mb-4 text-blue-600">
              <ShoppingBagIcon class="w-7 h-7" />
            </div>
            <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">Total Gowns</p>
            <h3 class="text-3xl font-black text-slate-900 mt-1">
              {{ isLoading ? '...' : stats?.total_invitations ?? 0 }}
            </h3>
          </div>

          <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
            <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center mb-4 text-emerald-600">
              <CheckBadgeIcon class="w-7 h-7" />
            </div>
            <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">My Issued Gowns</p>
            <h3 class="text-3xl font-black text-slate-900 mt-1">
              {{ isLoading ? '...' : stats?.totalIssued ?? 0 }}
            </h3>
          </div>

          <div class="bg-white p-6 rounded-3xl border border-indigo-100 shadow-md ring-2 ring-indigo-500/5">
            <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center mb-4 text-white">
              <ArrowPathIcon class="w-7 h-7" />
            </div>
            <p class="text-sm font-bold text-indigo-600 uppercase tracking-wider">My Returned Gowns</p>
            <h3 class="text-3xl font-black text-slate-900 mt-1">
              {{ isLoading ? '...' : stats?.totalReturned ?? 0 }}
            </h3>
            <p class="text-[10px] text-slate-400 mt-2 italic font-medium">Auto-refreshing every 2s</p>
          </div>

        </div>
      </main>
    </div>
  </div>
</template>