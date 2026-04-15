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
  { label: 'Reg No', field: 'reg_no' },
  { label: 'college Name', field: 'college_name' },
  { label: 'Created At', field: 'created_at' }
]

// -----------------------------
// Pagination & Search
// -----------------------------
const page = ref(1)
const search = ref('')

const fetchGowns = async () => {
  // 1. Ensure we have a college_id before making the request
  const collegeId = auth.user?.college_id;
  
  if (!collegeId) {
    console.warn("No college ID found for current user");
    return { data: [], last_page: 1 };
  }

  // 2. Set up query parameters (for pagination and search)
  const params: any = { page: page.value };
  if (search.value.trim() !== '') {
    params.search = search.value.trim();
  }

  // 3. Make the request using backticks and ${}
  // The URL matches: /view-gown-pickup-per-college/5 (if ID is 5)
  const { data } = await axiosInstance.get(`/view-gown-pickup-per-college/${collegeId}`, { params });
  
  return data;
}

const { data: usersData, isLoading, refetch } = useQuery({
  queryKey: ['guests', page, search],
  queryFn: fetchGowns
})

// -----------------------------
// Computed Data Mapping
// -----------------------------
const tableRows = computed(() => {
  return usersData.value?.data.map((item: any) => ({
    ...item,
    college_name: item.college?.short_name ?? 'N/A', // Match field: 'college_name'
    // ... other mappings
  })) ?? []
})

const totalPages = computed(() => usersData.value?.last_page || 1)

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

const deleteUser = async (id: number) => {
  if(!confirm('Are you sure you want to delete this record?')) return;
  try {
    await axiosInstance.delete(`/deletePickUp/${id}`)
    toast.success('Deleted successfully')
    openDropdown.value = null
    queryClient.invalidateQueries({ queryKey: ['guests'] })
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Delete failed')
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
        <h1 class="text-2xl font-bold text-gray-800">Pickuo List</h1>
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
  @click="deleteUser(row.id)" 
  :disabled="row.status === 'Returned'"
  class="flex items-center w-full px-4 py-2 text-sm font-medium transition-colors"
  :class="row.status === 'Returned' 
    ? 'text-gray-400 cursor-not-allowed bg-gray-50' 
    : 'text-red-600 hover:bg-red-50'"
>
  Delete Record
</button>
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