<script setup lang="ts">
import DataTable from '@/components/DataTable.vue'
import { useAuthStore } from '@/store/auth'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import { ref, onMounted, watch, computed } from 'vue'
import axiosInstance from '@/axios'
import { useToast } from 'vue-toastification'
import { ChevronDownIcon } from '@heroicons/vue/24/solid'
import { formatDate } from '@/utils/dateFormatter'

const loading = ref(false)
const loadingReminder = ref(false)

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
  { label: 'category', field: 'category' },
  { label: 'event_name', field: 'event_name' },
  { label: 'Invitation', field: 'expected_invitation' },
  { label: 'starting_date', field: 'starting_date' },
  { label: 'ending_date', field: 'ending_date' },
  { label: 'ending_date', field: 'ending_date' },
  { label: 'generated_at', field: 'generated_at' },
   { label: 'created_at', field: 'created_at' },
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

  const { data } = await axiosInstance.get('/viewEvent', { params })
  return data
}

const { data, isLoading, refetch } = useQuery({
  queryKey: ['guests', page, search],
  queryFn: fetchUsers
})

// -----------------------------
// Computed Table Data
// -----------------------------
const tableRows = computed(() => {
  const rows = data.value?.data || []
  
  return rows.map((row: any) => ({
    ...row,
    // Apply the helper to every date field
    starting_date: formatDate(row.starting_date),
    ending_date: formatDate(row.ending_date),
    generated_at: formatDate(row.generated_at),
    created_at: formatDate(row.created_at),
  }))
})


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
    await axiosInstance.delete(`/deleteEvent/${id}`)
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

    <div class="flex-1 p-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-xl font-bold">View Event</h1>

   
      </div>

      <section class="p-4 bg-white rounded-lg shadow">
        <DataTable
          :columns="columns"
          :rows="tableRows"
          :current-page="page"
          :total-pages="totalPages"
          @update:page="changePage"
          v-model:search="search"
        >
          <template #actions="{ row }">
            <div class="relative inline-block text-left">
              <button 
                @click="toggleDropdown(row.id)" 
                class="px-2 py-1 border rounded hover:bg-gray-50 flex items-center"
              >
                Actions
                <ChevronDownIcon class="w-4 h-4 ml-2" />
              </button>

              <div 
                v-if="openDropdown === row.id"
                class="absolute right-0 mt-2 w-40 bg-white shadow-lg border rounded z-50 py-1"
              >
                <button 
                  @click="deleteUser(row.id)" 
                  class="block w-full px-4 py-2 text-left text-red-600 hover:bg-gray-100"
                >
                  Delete
                </button>
              </div>
            </div>
          </template>
        </DataTable>

        <div v-if="isLoading" class="text-center py-10 text-gray-500">
          <p>Loading guests...</p>
        </div>
      </section>
    </div>
  </div>
</template>