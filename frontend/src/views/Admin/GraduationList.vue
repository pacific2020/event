<script setup lang="ts">
import DataTable from '@/components/DataTable.vue'
import { useAuthStore } from '@/store/auth'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import { ref, onMounted, watch, computed } from 'vue'
import axiosInstance from '@/axios'
import { useToast } from 'vue-toastification'
import { ChevronDownIcon } from '@heroicons/vue/24/solid'



// -----------------------------
// Toast
// -----------------------------
const toast = useToast()

// -----------------------------
// Query Client
// -----------------------------
const queryClient = useQueryClient()

// -----------------------------
// Auth
// -----------------------------
const auth = useAuthStore()

// -----------------------------
// Dropdown state
// -----------------------------
const openDropdown = ref<number | null>(null)

function toggleDropdown(id: number) {
  openDropdown.value = openDropdown.value === id ? null : id
}

// -----------------------------
// Table Columns
// -----------------------------
const columns = [
  { label: 'ID', field: 'id' },
  { label: 'College Name', field: 'college_name' },
  { label: 'Last Name', field: 'last_name' },
  { label: 'First Name', field: 'first_name' },
  { label: 'Degree', field: 'degree' },
  { label: 'Scanned Number', field: 'scanned_number' }
]

// -----------------------------
// Pagination & Search
// -----------------------------
const page = ref(1)
const search = ref('')

// -----------------------------
// Fetch Guest Invitations
// -----------------------------
const fetchUsers = async () => {
  const params: any = { page: page.value }

  if (search.value.trim() !== '') {
    params.search = search.value.trim()
  }

  const { data } = await axiosInstance.get('/graduation-list', { params })
  return data
}

const { data, isLoading, refetch } = useQuery({
  queryKey: ['guests', page, search],
  queryFn: fetchUsers
})

// -----------------------------
// Computed Table Data
// -----------------------------
const tableRows = computed(() => data.value?.data || [])
const totalPages = computed(() => data.value?.last_page || 1)

// -----------------------------
// Watchers for Search & Page
// -----------------------------
watch(search, () => {
  page.value = 1
  refetch() // Refetch when search changes
})

watch(page, () => {
  refetch() // Refetch when page changes
})

// -----------------------------
// Pagination Change
// -----------------------------
const changePage = (newPage: number) => {
  page.value = newPage
}

// -----------------------------
// Delete Guest
// -----------------------------
const deleteUser = async (id: number) => {
  try {
    await axiosInstance.delete(`/deleteGraduant/${id}`)
    toast.success('Deleted successfully')

    queryClient.invalidateQueries({ queryKey: ['guests'] })
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Delete failed')
  }
}



// -----------------------------
// Mounted
// -----------------------------
onMounted(() => {
  auth.getUser()
})
</script>

<template>
  <div class="flex min-h-screen bg-gray-100">

    <div class="flex-1 flex flex-col min-w-0">
      
      <header class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex justify-between items-center">
          <h1 class="text-2xl font-bold text-gray-800">Graduation List</h1>
          <div class="flex items-center gap-3">
             </div>
        </div>
      </header>

      <main class="p-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          
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
              <div class="relative inline-block text-left">
                <button 
                  @click.stop="toggleDropdown(row.id)" 
                  class="flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  Actions
                  <ChevronDownIcon class="w-4 h-4 ml-2 text-gray-500" />
                </button>

                <div 
                  v-if="openDropdown === row.id"
                  class="absolute right-0 mt-2 w-44 bg-white border border-gray-200 divide-y divide-gray-100 rounded-lg shadow-xl z-50 overflow-hidden"
                >
                 
                  <div class="py-1">
                    <button 
                      @click="deleteUser(row.id)" 
                      class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium"
                    >
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            </template>
          </DataTable>

          <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 bg-white">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-3"></div>
            <p class="text-gray-500 font-medium">Loading graduation list...</p>
          </div>

          <div v-if="!isLoading && tableRows.length === 0" class="text-center py-20 text-gray-400">
             No guests found matching your criteria.
          </div>

        </div>
      </main>
    </div>
  </div>
</template>