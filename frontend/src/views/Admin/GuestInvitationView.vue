<script setup lang="ts">
import DataTable from '@/components/DataTable.vue'
import { useAuthStore } from '@/store/auth'
import { useQuery, useQueryClient, keepPreviousData } from '@tanstack/vue-query'
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute } from 'vue-router' 
import axiosInstance from '@/axios'
import { useToast } from 'vue-toastification'
import { ChevronDownIcon, ArrowPathIcon } from '@heroicons/vue/24/solid' // ✅ Added Refresh Icon

const loading = ref(false)
const loadingReminder = ref(false)
const route = useRoute()
const toast = useToast()
const queryClient = useQueryClient()
const auth = useAuthStore()

// --- Dropdown state ---
const openDropdown = ref<number | null>(null)
function toggleDropdown(id: number) {
  openDropdown.value = openDropdown.value === id ? null : id
}

// --- Table Columns ---
const columns = [
  { label: 'ID', field: 'id' },
  { label: 'Position', field: 'position' },
  { label: 'Fullname', field: 'fullname' },
  { label: 'Email', field: 'email' },
  { label: 'Phone', field: 'phonenumber' },
  { label: 'Status', field: 'is_active' }
]

// --- Pagination & Search ---
const page = ref(1)
const search = ref('')

// --- Fetch Logic ---
const fetchUsers = async () => {
  const params: any = { page: page.value }
  if (search.value.trim() !== '') {
    params.search = search.value.trim()
  }
  const { data } = await axiosInstance.get('/viewGuestInvitation', { params })
  return data
}

const { data, isLoading, refetch, isFetching } = useQuery({
  queryKey: ['guests', page, search],
  queryFn: fetchUsers,
  placeholderData: keepPreviousData,
  staleTime: 0
})

// ✅ NEW: Manual/Route Refresh Logic
const refreshData = async () => {
  search.value = ''
  page.value = 1
  // Clear the cache for this specific key to avoid "flickering" old data
  queryClient.removeQueries({ queryKey: ['guests'] })
  await refetch()
}

// --- Computed Table Data ---
const tableRows = computed(() => data.value?.data || [])
const totalPages = computed(() => data.value?.last_page || 1)

// --- Watchers ---
// ✅ Reset page to 1 on search, but don't refetch manually (useQuery handles it via dependency)
watch(search, () => {
  page.value = 1
})

// ✅ Watch route to clear old data when navigating back to this view
watch(() => route.path, () => {
  refreshData()
})

const changePage = (newPage: number) => {
  page.value = newPage
}

// --- Actions (Delete, Reminder, Invitations) ---
const deleteUser = async (id: number) => {
  if (!confirm('Are you sure you want to delete this guest?')) return
  try {
    await axiosInstance.delete(`/deleteGuest/${id}`)
    toast.success('Deleted successfully')
    queryClient.invalidateQueries({ queryKey: ['guests'] })
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Delete failed')
  }
}

const handleReminder = async () => {
  loadingReminder.value = true
  try {
    const { data } = await axiosInstance.post('/send-reminders') 
    toast.success(data.message || 'Reminders sent successfully')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Error sending reminders')
  } finally {
    loadingReminder.value = false
  }
}

const handleSubmit = async () => {
  if (loading.value) return 
  loading.value = true
  try {    
    const { data } = await axiosInstance.post('/send-invitations')
    toast.success(data.message || 'Invitations sent')
    queryClient.invalidateQueries({ queryKey: ['guests'] })
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Error sending invitations')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  auth.getUser()
})
</script>

<template>
  <div class="flex min-h-screen bg-gray-100">
    <div class="flex-1 p-6">
      <div class="flex justify-between items-center mb-6">
        <div class="flex items-center gap-4">
          <h1 class="text-xl font-bold">Guest Invitations</h1>
          <button 
            @click="refreshData" 
            class="p-2 bg-white rounded-full shadow-sm hover:bg-gray-50 transition-all group"
            :class="{ 'animate-spin': isFetching }"
            title="Refresh Table"
          >
            <ArrowPathIcon class="w-5 h-5 text-gray-600 group-hover:text-blue-600" />
          </button>
        </div>

        <div class="flex gap-2">
          <button 
            @click="handleSubmit"
            :disabled="loading"
            class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800 disabled:bg-gray-400 flex items-center"
          >
            <span v-if="loading" class="mr-2 animate-spin text-lg">↻</span>
            {{ loading ? 'Sending...' : 'Send Invitations' }}
          </button>

          <button 
            @click="handleReminder"
            :disabled="loadingReminder"
            class="bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-800 disabled:bg-blue-400 flex items-center"
          >
            <span v-if="loadingReminder" class="mr-2 animate-spin text-lg">↻</span>
            {{ loadingReminder ? 'Processing...' : 'Send Reminder' }}
          </button>
        </div>
      </div>

      <section class="p-4 bg-white rounded-lg shadow relative">
        <div v-if="isLoading || isFetching" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-10 flex items-center justify-center rounded-lg">
           <div class="flex flex-col items-center">
              <span class="animate-spin text-3xl mb-2 text-blue-600">↻</span>
              <p class="text-sm font-medium text-gray-500">Syncing Guests...</p>
           </div>
        </div>

        <DataTable
          :columns="columns"
          :rows="tableRows"
          :current-page="page"
          :total-pages="totalPages"
          @update:page="changePage"
          @refresh="refreshData"
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
      </section>
    </div>
  </div>
</template>