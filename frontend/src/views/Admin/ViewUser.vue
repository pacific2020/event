<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import { useAuthStore } from '@/store/auth'
import type { User, LaravelPagination } from '@/types/index'
import axiosInstance from '@/axios'
import DataTable from '@/components/DataTable.vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import { useQuery, useQueryClient, keepPreviousData } from '@tanstack/vue-query'
import { useToast } from 'vue-toastification'

const toast = useToast();

const queryClient = useQueryClient()

const auth = useAuthStore()

/* -----------------------------
Dropdown
----------------------------- */
const openDropdown = ref<number | null>(null)

function toggleDropdown(id: number) {
  openDropdown.value = openDropdown.value === id ? null : id
}

/* -----------------------------
Table Columns
----------------------------- */
const columns = [
  { label: 'ID', field: 'id' },
  { label: 'College', field: 'college_name' },
  { label: 'Position', field: 'position' },
  { label: 'Fullname', field: 'fullname' },
  { label: 'Email', field: 'email' },
  { label: 'Phone', field: 'phone_number' },
  { label: 'Status', field: 'is_active' },
]

/* -----------------------------
Pagination
----------------------------- */
const page = ref(1)

const search = ref('')
/* -----------------------------
Fetch Users
----------------------------- */
const fetchUsers = async (): Promise<LaravelPagination<User>> => {
  const params: Record<string, any> = { page: page.value }

  if (search.value.trim() !== '') {
    params.search = search.value.trim()  // only add search if it has value
  }

  const { data } = await axiosInstance.get('/viewUsers', { params })
  return data.data
}

/* -----------------------------
Vue Query (FIXED)
----------------------------- */
const { data: usersData, isLoading, isFetching } = useQuery({
  queryKey: ['users', page, search], // ✅ include search
  queryFn: fetchUsers,
 placeholderData: keepPreviousData,
})

const changePage = (newPage: number) => {
  page.value = newPage
}

/* -----------------------------
Computed
----------------------------- */

const tableRows = computed(() => {
  return usersData.value?.data.map(user => ({
    ...user,
    college_name: user.college?.short_name ?? 'N/A',
    is_active: user?.is_active ? 'Active' : 'Inative'
  })) ?? []
})

const totalPages = computed(() => usersData.value?.last_page ?? 1)


const deleteUser = async (id: number) => {
  try {
    const response = await axiosInstance.delete(`/delete/user/${id}`)
    toast.success('User deleted successfully')


    // Refetch users after delete
    queryClient.invalidateQueries({ queryKey: ['users'] })
  } catch (error) {
    console.error('Delete failed:', error)
  }
}



/* -----------------------------
Mounted
----------------------------- */
onMounted(() => {
  auth.getUser()
})

watch(search, () => {
  page.value = 1
})
</script>

<template>
  <div class="flex">

    <div class="flex-1 p-4">
      
      <div v-if="auth.loading || isLoading">
        Loading...
      </div>

      <section class="p-4">

   <DataTable
  :columns="columns"
  :rows="tableRows"
  :current-page="page"
  :total-pages="totalPages"
  @update:page="changePage"
  v-model:search="search"
>

  <!-- Actions -->
  <template #actions="{ row }">
    <div class="relative inline-block text-left">
      
      <button 
        @click="toggleDropdown(row.id)" 
        class="px-2 py-1 border rounded"
      >
        Actions
        <ChevronDownIcon class="w-4 h-4 ml-2 inline" />
      </button>

      <div 
        v-if="openDropdown === row.id"
        class="absolute right-0 mt-2 w-40 bg-white shadow rounded z-50"
      >
        <button class="block w-full px-4 py-2 text-left hover:bg-gray-100">
          View
        </button>

        <button class="block w-full px-4 py-2 text-left hover:bg-gray-100">
          Edit
        </button>

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