<script setup lang="ts">
import { computed, watch } from 'vue';
import { useRoute } from 'vue-router';

interface Column {
  label: string
  field: string
}

const props = defineProps<{
  columns: Column[]
  rows: any[]
  currentPage: number
  totalPages: number
  search: string // The "Source of Truth" from parent
}>()

const emit = defineEmits<{
  (e: 'update:page', page: number): void
  (e: 'update:search', value: string): void
  (e: 'refresh'): void 
}>()

const route = useRoute();

// ✅ 1. Use a watcher to reset state when moving between routes
watch(() => route.path, () => {
  emit('update:search', ''); // Clear parent search state
  emit('update:page', 1);    // Reset to first page
  emit('refresh');           // Trigger new data fetch
}, { immediate: true });

// ✅ 2. Logic to change page
const changePage = (page: number) => {
  if (page >= 1 && page <= props.totalPages) {
    emit('update:page', page);
  }
};

// ✅ 3. Filtered Rows: Now using props.search correctly
const filteredRows = computed(() => {
  if (!props.search) return props.rows

  const query = props.search.toLowerCase();
  return props.rows.filter(row =>
    props.columns.some(col =>
      String(row[col.field] || '').toLowerCase().includes(query)
    )
  )
})

// ✅ 4. Handle Search Input
const onSearchInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:search', target.value)
}
</script>

<template>
  <div class="mb-4 flex justify-between items-center">
    <div class="flex items-center gap-2">
      <input 
        :value="search"
        @input="onSearchInput"
        type="text"
        placeholder="Search..."
        class="border p-2 rounded focus:ring-2 focus:ring-blue-500 outline-none"
      />
      <button 
        @click="$emit('refresh')" 
        type="button"
        class="p-2 bg-gray-100 hover:bg-gray-200 rounded transition-colors"
        title="Refresh Data"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
      </button>
    </div>
  </div>

  <div class="overflow-x-auto border rounded-lg">
    <table class="table-auto border-collapse w-full text-left text-sm">
      <thead>
        <tr class="bg-gray-50 border-b">
          <th v-for="col in columns" :key="col.field" class="p-3 font-semibold text-gray-700">
            {{ col.label }}
          </th>
          <th class="p-3 font-semibold text-gray-700">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <tr v-for="(row, index) in filteredRows" :key="row.id || index" class="hover:bg-gray-50 transition-colors">
          <td v-for="col in columns" :key="col.field" class="p-3 text-gray-600">
            {{ row[col.field] }}
          </td>
          <td class="p-3 text-gray-600">
            <slot name="actions" :row="row"></slot>
          </td>
        </tr>
        <tr v-if="filteredRows.length === 0">
          <td :colspan="columns.length + 1" class="p-8 text-center text-gray-400 italic">
            No matching data found.
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div v-if="totalPages > 1" class="mt-4 flex justify-center items-center gap-2">
    <button
      @click="changePage(currentPage - 1)"
      :disabled="currentPage === 1"
      class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-gray-50"
    >
      Prev
    </button>
    
    <span class="text-sm text-gray-600">
      Page {{ currentPage }} of {{ totalPages }}
    </span>

    <button
      @click="changePage(currentPage + 1)"
      :disabled="currentPage === totalPages"
      class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-gray-50"
    >
      Next
    </button>
  </div>
</template>