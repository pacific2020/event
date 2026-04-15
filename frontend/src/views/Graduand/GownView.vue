<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query';
import { useGraduandStore } from '@/store/graduand';
import { 
  AcademicCapIcon, 
  CalendarIcon, 
  CheckCircleIcon,
  ExclamationCircleIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline';
import axiosInstance from '@/axios';

const auth = useGraduandStore();

// ✅ Define the fetcher function
const fetchGownStatus = async () => {
  if (!auth.user?.reg_no) throw new Error('Registration number missing');
  
  const { data } = await axiosInstance.get(`/view-gown/${auth.user.reg_no}`);
  
  // Handle the array structure [{...}]
  return Array.isArray(data) && data.length > 0 ? data[0] : data;
};

// ✅ useQuery handles everything: loading, error, polling, and data
const { 
  data: gown, 
  isLoading, 
  isError 
} = useQuery({
  queryKey: ['gown-status', auth.user?.reg_no],
  queryFn: fetchGownStatus,
  // Polling logic: Refetch every 2 seconds ONLY IF the status isn't "Issued"
  refetchInterval: (query) => {
    return query.state.data?.status === 'Issued' ? false : 2000;
  },
  // Extra safety
  enabled: !!auth.user?.reg_no,
});

const getStatusClass = (status: string) => {
  switch (status?.toLowerCase()) {
    case 'issued': return 'bg-green-100 text-green-700 border-green-200';
    case 'returned': return 'bg-blue-100 text-blue-700 border-blue-200';
    default: return 'bg-gray-100 text-gray-700 border-gray-200';
  }
}

const formatDate = (dateString: string) => {
  if (!dateString) return 'To be announced';
  return new Date(dateString).toLocaleDateString('en-GB', { 
    day: 'numeric', month: 'long', year: 'numeric' 
  });
}
</script>

<template>
  <div class="p-6 max-w-4xl mx-auto">
    <div class="mb-8 text-center md:text-left">
      <h1 class="text-2xl font-bold text-gray-800">Gown Academic Service</h1>
      <p class="text-gray-500">Track your graduation attire issuance and return schedule.</p>
    </div>

    <div v-if="isLoading" class="flex flex-col items-center justify-center p-12">
      <ArrowPathIcon class="w-10 h-10 text-blue-500 animate-spin" />
      <p class="mt-4 text-gray-500 font-medium">Checking record status...</p>
    </div>

    <div v-else-if="isError" class="p-6 bg-red-50 border border-red-200 rounded-2xl text-center">
      <ExclamationCircleIcon class="w-12 h-12 text-red-500 mx-auto mb-2" />
      <p class="text-red-700 font-bold">Failed to connect to the server.</p>
      <button @click="() => {}" class="mt-4 text-sm text-red-600 underline">Try again</button>
    </div>

    <div v-else-if="gown" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-500">
      <div class="bg-gray-900 p-6 flex flex-col md:flex-row items-center justify-between text-white gap-4">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-gray-800 rounded-xl">
            <AcademicCapIcon class="w-8 h-8 text-blue-400" />
          </div>
          <div>
            <p class="text-sm text-gray-400 uppercase tracking-tight">Gown Tracking ID</p>
            <p class="font-mono font-bold text-lg">#{{ gown.id || 'N/A' }}</p>
          </div>
        </div>
        <span :class="getStatusClass(gown.status)" class="px-6 py-2 rounded-full text-xs font-black uppercase border tracking-widest shadow-sm">
          {{ gown.status }}
        </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-8">
        <div class="flex items-start gap-4 p-5 rounded-2xl bg-gray-50 border border-gray-100">
          <CheckCircleIcon class="w-7 h-7 text-green-500" />
          <div>
            <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Date Issued</p>
            <p class="text-gray-800 font-bold text-lg">{{ formatDate(gown.created_at) }}</p>
            <p class="text-xs text-gray-500">Official collection timestamp</p>
          </div>
        </div>

        <div class="flex items-start gap-4 p-5 rounded-2xl bg-red-50 border border-red-100">
          <CalendarIcon class="w-7 h-7 text-red-500" />
          <div>
            <p class="text-xs font-black text-red-400 uppercase tracking-widest mb-1">Return Deadline</p>
            <p class="text-red-800 font-bold text-lg">{{ formatDate(gown.expected_returning_date) }}</p>
            <p class="text-xs text-red-500">Late returns may incur penalties</p>
          </div>
        </div>
      </div>

      <div class="bg-blue-50 p-5 border-t border-blue-100 flex items-center gap-4">
        <div class="p-2 bg-blue-100 rounded-lg">
          <ExclamationCircleIcon class="w-5 h-5 text-blue-600" />
        </div>
        <p class="text-sm text-blue-800 font-semibold italic">Please ensure the gown is clean and folded when returning to the Academic Office.</p>
      </div>
    </div>

    <div v-else class="text-center p-16 bg-white rounded-3xl border-2 border-dashed border-gray-200">
      <div class="relative inline-block mb-6">
        <AcademicCapIcon class="w-20 h-20 text-gray-200 mx-auto" />
        <ArrowPathIcon class="w-8 h-8 text-blue-400 absolute -bottom-2 -right-2 animate-spin-slow" />
      </div>
      <h3 class="text-xl font-bold text-gray-900">Waiting for Gown Issuance...</h3>
      <p class="text-gray-500 max-w-sm mx-auto mt-2">Your record will automatically appear here once the gown is issued at the storage facility.</p>
    </div>
  </div>
</template>

<style scoped>
.animate-spin-slow {
  animation: spin 3s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>