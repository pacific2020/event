<script setup lang="ts">
import { useAuthStore } from '@/store/auth';
import axiosInstance from '@/axios';
import { useQuery } from '@tanstack/vue-query';
import { 
  TicketIcon, 
  CheckBadgeIcon, 
  UserCircleIcon,
  PresentationChartBarIcon,
  UsersIcon
} from '@heroicons/vue/24/outline'

const auth = useAuthStore();

/**
 * Fetcher function
 */
const fetchStats = async () => {
  if (!auth.user?.id) await auth.getUser();
  // Ensure this matches the route you defined in Laravel
  const { data } = await axiosInstance.get(`/scanner-stats-admin/${auth.user?.id}`);
  return data;
};

/**
 * useQuery implementation
 */
const { data: stats, isLoading } = useQuery({
  queryKey: ['admin-scanner-stats', auth.user?.id],
  queryFn: fetchStats,
  refetchInterval: 2000, 
  refetchIntervalInBackground: true,
});
</script>

<template>
  <div class="flex min-h-screen bg-slate-50">

    <div class="flex-1">


      <main class="p-8 max-w-7xl mx-auto">
        <div class="mb-8">
          <h2 class="text-2xl font-black text-slate-900">Live Event Monitoring 👋</h2>
          <p class="text-slate-500">Overview of all scanner activities and invitation statistics.</p>
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
            <p class="text-[10px] text-slate-400 mt-2 italic font-medium">Updating every 2s</p>
          </div>
        </div>

        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden mb-10">
          <div class="p-8 border-b border-slate-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
              <UsersIcon class="w-5 h-5 text-indigo-500" />
              Scanner Performance
            </h3>
            <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-xs font-bold rounded-full">LIVE</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50/50">
                  <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Scanner Name</th>
                  <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-center">Total Scans</th>
                  <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-right">Progress</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="isLoading" class="animate-pulse">
                  <td colspan="3" class="px-8 py-10 text-center text-slate-400">Loading live data...</td>
                </tr>
                <tr v-else-if="!stats?.scanner_performance?.length">
                  <td colspan="3" class="px-8 py-10 text-center text-slate-400">No scans recorded yet.</td>
                </tr>
                <tr v-for="staff in stats?.scanner_performance" :key="staff.entrance_user_id" class="hover:bg-slate-50 transition-colors">
                  <td class="px-8 py-4 border-t border-slate-50">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-[10px] font-bold">
                        {{ staff.scanner?.fullname?.charAt(0) }}
                      </div>
                      <span class="font-bold text-slate-700">{{ staff.scanner?.fullname }}</span>
                    </div>
                  </td>
                  <td class="px-8 py-4 border-t border-slate-50 text-center">
                    <span class="font-black text-indigo-600">{{ staff.total }}</span>
                  </td>
                  <td class="px-8 py-4 border-t border-slate-50 text-right">
                    <div class="w-32 h-2 bg-slate-100 rounded-full inline-block overflow-hidden">
                      <div 
                        class="h-full bg-indigo-500 rounded-full" 
                        :style="{ width: (staff.total / stats.total_invitations * 100) + '%' }"
                      ></div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm">
           <h3 class="font-bold text-slate-800 mb-4">Admin Note</h3>
           <p class="text-slate-500 text-sm leading-relaxed">
             This dashboard displays real-time data. To manage user roles or check individual logs, please visit the <strong>User Management</strong> section. 
             If you are assisting with entry, ensure you use the 
             <router-link target="_blank" to="/Protocol/open" class="text-indigo-600 underline font-semibold">
               protocol scanner tool
             </router-link>.
           </p>
        </div>
      </main>
    </div>
  </div>
</template>