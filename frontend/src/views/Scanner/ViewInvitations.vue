<script setup lang="ts">
import DataTable from '@/components/DataTable.vue'
import { useAuthStore } from '@/store/auth'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import axiosInstance from '@/axios'
import { useToast } from 'vue-toastification'
import { ChevronDownIcon } from '@heroicons/vue/24/solid'

// -----------------------------
// Initialization
// -----------------------------
const toast = useToast()
const queryClient = useQueryClient()
const auth = useAuthStore()

// -----------------------------
// Dropdown Logic (Fixed for Outside Click)
// -----------------------------
const openDropdown = ref<number | null>(null)

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
  { label: 'Reg No', field: 'reg_no' },
  { label: 'fullname', field: 'fullname' },
  { label: 'Id number', field: 'graduate_idnumber' },
  { label: 'Phone Number', field: 'phonenumber' },
  { label: 'Scanned By', field: 'scannedBy' },
    { label: 'Status', field: 'scanned' },
  { label: 'DElivery', field: 'status' },
   { label: 'Scanned At', field: 'date_scanned' },
  { label: 'Created At', field: 'created_at' },
]

// -----------------------------
// Pagination & Search
// -----------------------------
const page = ref(1)
const search = ref('')

const fetchInvitations = async () => {
  const params: any = { page: page.value }
  if (search.value.trim() !== '') {
    params.search = search.value.trim()
  }
  const { data } = await axiosInstance.get('/view-invitations', { params })
  return data
}

const { data: usersData, isLoading, refetch } = useQuery({
  queryKey: ['guests', page, search],
  queryFn: fetchInvitations
})

// -----------------------------
// Computed Data Mapping
// -----------------------------
const tableRows = computed(() => {
  return usersData.value?.data.map((item: any) => ({
    ...item,
    scannedBy: item.scanner?.fullname ?? 'N/A',
    created_at: item.created_at 
      ? new Date(item.created_at).toLocaleString('en-US', {
          year: 'numeric', month: 'short', day: 'numeric',
          hour: '2-digit', minute: '2-digit', hour12: true
        }) 
      : 'N/A'
  })) ?? []
})

const totalPages = computed(() => usersData.value?.last_page || 1)




// Define your backend URL from environment variables
const backendUrl = import.meta.env.VITE_BACKEND_URL;

/**
 * Opens the PDF in a new tab.
 * Handles both full URLs and relative storage paths.
 */
const viewPdf = (pdfPath: string | null) => {
  if (!pdfPath) {
    toast.error("No PDF file associated with this record.");
    return;
  }

  let finalUrl = '';

  // Check if the path is already a full URL (starts with http)
  if (pdfPath.startsWith('http')) {
    finalUrl = pdfPath;
  } else {
    // Construct the URL. 
    // Usually, Laravel public files are in 'storage/...' 
    // Ensure there is no double slash between backendUrl and the path
    const cleanBaseUrl = backendUrl.endsWith('/') ? backendUrl.slice(0, -1) : backendUrl;
    const cleanPath = pdfPath.startsWith('/') ? pdfPath.slice(1) : pdfPath;
    
    finalUrl = `${cleanBaseUrl}/storage/invitations/${cleanPath}`;
  }

  // Open in new tab
  window.open(finalUrl, '_blank');
  
  // Close the dropdown menu
  openDropdown.value = null;
};


const scanInvitation = async (id: number) => {
  // Optional: Add a confirmation dialog
  if (!confirm("Are you sure you want to manually mark this invitation as scanned?")) return;

  try {
    const payload = {
      scanned: 'scanned',            // Status update
      entrance_user_id: auth.user?.id, // ID of the logged-in staff/scanner
     date_scanned: new Date().toLocaleString('sv-SE').replace(' ', 'T'), // Current timestamp
    };

    // Replace '/update-invitation-scan' with your actual endpoint
    await axiosInstance.post(`/update-invitation-scan/${id}`, payload);

    toast.success('Invitation updated successfully');
    
    // Close the dropdown menu
    openDropdown.value = null;
    
    // Refresh the table data using TanStack Query
    queryClient.invalidateQueries({ queryKey: ['guests'] });
  } catch (error: any) {
    console.error("Update Error:", error);
    toast.error(error.response?.data?.message || 'Failed to update record');
  }
};

// -----------------------------
// Watchers
// -----------------------------
watch(search, () => {
  page.value = 1
  refetch()
})

const changePage = (newPage: number) => {
  page.value = newPage
}

const returnGown = async (id: number) => {
  try {
    // 1. Prepare the data (Objects use {}, not [])
    const payload = {
      receiver_id: auth.user?.id,
      status: 'Returned',
      returned_date: new Date().toISOString(), // Standard JS Date format
    };

    // 2. Pass the ID in the URL and the payload as the second argument
    await axiosInstance.post(`/returnGown/${id}`, payload);

    toast.success('Gown returned successfully');
    openDropdown.value = null; // Close the menu
    queryClient.invalidateQueries({ queryKey: ['guests'] }); // Refresh the table
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Return failed');
  }
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
      <header class="bg-white border-b border-gray-200 px-6 py-4">
        <h1 class="text-2xl font-bold text-gray-800">View Invitation</h1>
      </header>

      <main class="p-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          
          <DataTable
            :columns="columns"
            :rows="tableRows"
            :current-page="page"
            :total-pages="totalPages"
            @update:page="changePage"
            v-model:search="search"
            class="w-full"
          >
            <template #actions="{ row }">
              <div class="relative inline-block text-left dropdown-wrapper">
                <button 
                  @click.stop="toggleDropdown(row.id)" 
                  class="flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500"
                >
                  Actions
                  <ChevronDownIcon class="w-4 h-4 ml-2 text-gray-500" />
                </button>

                <div 
                  v-if="openDropdown === row.id"
                  class="absolute right-0 mt-2 w-44 bg-white border border-gray-200 divide-y divide-gray-100 rounded-lg shadow-xl z-[999] overflow-visible"
                >
<div class="py-1">
  <button 
    @click="scanInvitation(row.id)" 
    :disabled="row.scanned === 'scanned'"
    class="flex items-center w-full px-4 py-2 text-sm font-medium transition-colors"
    :class="row.scanned === 'scanned' 
      ? 'text-gray-400 bg-gray-50 cursor-not-allowed' 
      : 'text-emerald-600 hover:bg-emerald-50'"
  >
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>
    {{ row.scanned === 'scanned' ? 'Already Scanned' : 'Update Manually' }}
  </button>
</div>

 <div class="py-1">
  <button 
    @click="viewPdf(row.pdf)" 
    class="flex items-center w-full px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 font-medium transition-colors"
  >
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
    </svg>
    View Invitation
  </button>
</div>
                  <div class="py-1">

                  </div>
                </div>
              </div>
            </template>
          </DataTable>

          <div v-if="isLoading" class="flex flex-col items-center justify-center py-20">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-3"></div>
            <p class="text-gray-500 font-medium">Fetching data...</p>
          </div>

          <div v-if="!isLoading && tableRows.length === 0" class="text-center py-20 text-gray-400">
             No records found.
          </div>
        </div>
      </main>
    </div>
  </div>
</template>