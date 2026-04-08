<script setup lang="ts">
import DataTable from '@/components/DataTable.vue'
import { useAuthStore } from '@/store/auth'
import { useQuery, useQueryClient } from '@tanstack/vue-query'
import { ref, onMounted, watch, computed } from 'vue'
import axiosInstance from '@/axios'
import { useToast } from 'vue-toastification'
import { ChevronDownIcon } from '@heroicons/vue/24/solid'

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
  { label: 'Position', field: 'position' },
  { label: 'Fullname', field: 'fullname' },
  { label: 'Email', field: 'email' },
  { label: 'Phone', field: 'phonenumber' },
  { label: 'Status', field: 'is_active' }
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

  const { data } = await axiosInstance.get('/viewGuestInvitation', { params })
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
    // Replace with your actual reminder endpoint
    const { data } = await axiosInstance.post('/send-reminders') 
    toast.success(data.message || 'Reminders sent successfully')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Error sending reminders')
  } finally {
    loadingReminder.value = false
  }
}

// Updated handleSubmit for Invitations
const handleSubmit = async () => {
  if (loading.value) return // Prevent double triggers
  
  loading.value = true
  try {    
    const { data } = await axiosInstance.post('/send-invitations')
    
    if (data.status === 'success') {
      toast.success(data.message || 'Invitations sent')
    } else {
      toast.error(data.message || 'Invitations not sent')     
    }

    // Refresh the table data
    queryClient.invalidateQueries({ queryKey: ['guests'] })
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Error sending invitations')
  } finally {
    loading.value = false
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
        <h1 class="text-xl font-bold">Guest Invitations</h1>

        <div class="flex gap-2">
          <button 
            @click="handleSubmit"
            :disabled="loading"
            class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800 disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center"
          >
            <span v-if="loading" class="mr-2 animate-spin">⏳</span>
            {{ loading ? 'Sending...' : 'Send Invitations' }}
          </button>

          <button 
            @click="handleReminder"
            :disabled="loadingReminder"
            class="bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-800 disabled:bg-blue-400 disabled:cursor-not-allowed flex items-center"
          >
            <span v-if="loadingReminder" class="mr-2 animate-spin">⏳</span>
            {{ loadingReminder ? 'Processing...' : 'Send Reminder' }}
          </button>
        </div>
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