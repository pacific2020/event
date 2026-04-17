<script setup lang="ts">
import DataTable from '@/components/DataTable.vue'
import { useAuthStore } from '@/store/auth'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import axiosInstance from '@/axios'
import { useToast } from 'vue-toastification'
import { ChevronDownIcon } from '@heroicons/vue/24/solid'
import { useRoute } from 'vue-router' 

// -----------------------------
// Initialization
// -----------------------------
const toast = useToast()
const queryClient = useQueryClient()
const auth = useAuthStore()
const route = useRoute()
const backendUrl = import.meta.env.VITE_BACKEND_URL;

// -----------------------------
// UI State
// -----------------------------
const openDropdown = ref<number | null>(null)
const page = ref(1)
const search = ref('')

function toggleDropdown(id: number) {
  openDropdown.value = openDropdown.value === id ? null : id
}

const closeDropdown = (e: MouseEvent) => {
  const target = e.target as HTMLElement;
  if (!target.closest('.dropdown-wrapper')) {
    openDropdown.value = null;
  }
}

// -----------------------------
// Table Columns
// -----------------------------
const columns = [
  { label: 'ID', field: 'id' },
  { label: 'Type', field: 'type' },
  { label: 'Role', field: 'display_identifier' },
  { label: 'Fullname', field: 'fullname' },
  { label: 'ID Number', field: 'graduate_idnumber' },
  { label: 'Phone', field: 'phonenumber' },
  { label: 'Scanned By', field: 'scannedBy' },
  { label: 'Status', field: 'scanned' },
  { label: 'Delivery', field: 'status' },
  { label: 'Scanned At', field: 'date_scanned' },
  { label: 'Created At', field: 'created_at' },
]

// -----------------------------
// Fetching Logic
// -----------------------------
const fetchInvitations = async () => {
  const params: any = { page: page.value }
  if (search.value.trim() !== '') {
    params.search = search.value.trim()
  }
  const { data } = await axiosInstance.get('/view-invitations', { params })
  return data
}

// ✅ 1. Use a unique key ('invitations') to avoid sharing cache with other lists
// ✅ 2. Removed placeholderData: keepPreviousData to stop "ghost data" from showing
const { data: usersData, isLoading, refetch, isFetching } = useQuery({
  queryKey: ['invitations', page, search], 
  queryFn: fetchInvitations,
  staleTime: 0,
  gcTime: 0 // Ensures cache is cleared when component unmounts
})

const refreshData = () => {
  search.value = ''
  page.value = 1
  queryClient.removeQueries({ queryKey: ['invitations'] })
  refetch()
}

// -----------------------------
// Computed Data Mapping
// -----------------------------
const tableRows = computed(() => {
  return usersData.value?.data.map((item: any) => ({
    ...item,
    scannedBy: item.scanner?.fullname ?? 'N/A',
    display_identifier: item.reg_no ?? item.position,
    created_at: item.created_at 
      ? new Date(item.created_at).toLocaleString('en-US', {
          year: 'numeric', month: 'short', day: 'numeric',
          hour: '2-digit', minute: '2-digit', hour12: true
        }) 
      : 'N/A'
  })) ?? []
})

const totalPages = computed(() => usersData.value?.last_page || 1)

// -----------------------------
// Actions
// -----------------------------
const viewPdf = (pdfPath: string | null) => {
  if (!pdfPath) {
    toast.error("No PDF file associated with this record.");
    return;
  }
  const cleanBaseUrl = backendUrl.endsWith('/') ? backendUrl.slice(0, -1) : backendUrl;
  const cleanPath = pdfPath.startsWith('/') ? pdfPath.slice(1) : pdfPath;
  const finalUrl = pdfPath.startsWith('http') ? pdfPath : `${cleanBaseUrl}/storage/invitations/${cleanPath}`;
  
  window.open(finalUrl, '_blank');
  openDropdown.value = null;
};

const scanInvitation = async (id: number) => {
  if (!confirm("Manually mark as scanned?")) return;
  try {
    const payload = {
      scanned: 'scanned',
      entrance_user_id: auth.user?.id,
      date_scanned: new Date().toISOString()
    };
    await axiosInstance.post(`/update-invitation-scan/${id}`, payload);
    toast.success('Invitation updated');
    openDropdown.value = null;
    queryClient.invalidateQueries({ queryKey: ['invitations'] });
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Update failed');
  }
};

// -----------------------------
// Watchers
// -----------------------------
watch(search, () => {
  page.value = 1
})

watch(() => route.path, () => {
  refreshData()
}, { immediate: true })

const changePage = (newPage: number) => {
  page.value = newPage
}

// -----------------------------
// Lifecycle
// -----------------------------
onMounted(() => {
  auth.getUser()
  window.addEventListener('click', closeDropdown)
})

onUnmounted(() => {
  window.removeEventListener('click', closeDropdown)
})
</script>

<template>
  <div class="flex min-h-screen bg-gray-100">
    <div class="flex-1 flex flex-col min-w-0">
      <header class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">View Invitations</h1>
        <button 
          @click="refreshData" 
          class="p-2 hover:bg-gray-100 rounded-full transition-colors"
          :class="{ 'animate-spin': isFetching }"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </header>

      <main class="p-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 relative overflow-hidden">
          
          <div v-if="isLoading || isFetching" class="absolute inset-0 bg-white/60 backdrop-blur-sm z-50 flex flex-col items-center justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-600 mb-4"></div>
            <p class="text-blue-600 font-bold tracking-wide">Load...</p>
          </div>

          <DataTable
            :columns="columns"
            :rows="tableRows"
            :current-page="page"
            :total-pages="totalPages"
            @update:page="changePage"
            @refresh="refreshData"
            v-model:search="search"
            class="w-full"
          >
            <template #actions="{ row }">
              <div class="relative inline-block text-left dropdown-wrapper">
                <button 
                  @click.stop="toggleDropdown(row.id)" 
                  class="flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                >
                  Actions
                  <ChevronDownIcon class="w-4 h-4 ml-2 text-gray-500" />
                </button>

                <div 
                  v-if="openDropdown === row.id"
                  class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-xl z-[999]"
                >
                  <div class="py-1">
                    <button 
                      @click="scanInvitation(row.id)" 
                      :disabled="row.scanned === 'scanned'"
                      class="flex items-center w-full px-4 py-2 text-sm font-medium transition-colors"
                      :class="row.scanned === 'scanned' ? 'text-gray-400 bg-gray-50' : 'text-emerald-600 hover:bg-emerald-50'"
                    >
                      Update Manually
                    </button>
                    <button 
                      @click="viewPdf(row.pdf)" 
                      class="flex items-center w-full px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 font-medium"
                    >
                      View Invitation
                    </button>
                  </div>
                </div>
              </div>
            </template>
          </DataTable>

          <div v-if="!isLoading && tableRows.length === 0" class="text-center py-20 text-gray-400 italic">
             No records found for this query.
          </div>
        </div>
      </main>
    </div>
  </div>
</template>