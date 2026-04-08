<script setup lang="ts">
import { ref, computed} from 'vue';

const searchTerm =ref('');

interface Column {
  label: string
  field: string
}

const props = defineProps<{
  columns: Column[]
  rows: any[]
  currentPage: number
  totalPages: number
  search: string
}>()

const emit = defineEmits<{
  (e: 'update:page', page: number): void
  (e: 'update:search', value: string): void
}>()


const changePage = (page: number) => emit('update:page', page);

const filteredRows = computed(() => {
  if (!searchTerm.value) return props.rows

  return props.rows.filter(row =>
    props.columns.some(col =>
      String(row[col.field]).toLowerCase().includes(searchTerm.value.toLowerCase())
    )
  )
})

const onSearchInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:search', target.value)
}

</script>

<template>
<div class="mb-4 flex justify-between items-center">
 
<input 
  :value="search"
  @input="onSearchInput"
  type="text"
  placeholder="Search users..."
  class="border p-2 rounded mb-4"
/>
 </div>

<table class="table-auto border-collapse border w-full">
  <thead>
    <tr class="bg-gray-200">
      <th v-for="col in columns" :key="col.field" class="border p-2">{{ col.label }}</th>
      <th class="border p-2">Actions</th>
    </tr>
  </thead>

  <tbody>
    <tr v-for="row in filteredRows" :key="row.id">
      <td v-for="col in columns" :key="col.field" class="border p-2">{{ row[col.field] }}</td>
      <td class="border p-2">
        <slot name="actions" :row="row"></slot>
      </td>
    </tr>

    <tr v-if="rows.length === 0">
      <td :colspan="columns.length + 1" class="text-center p-2">No data found.</td>
    </tr>
  </tbody>
</table>

<!-- Pagination -->
<div class="mt-4 flex justify-center space-x-2">
  <button
    class="px-2 py-1 border rounded"
    :disabled="currentPage === 1"
    @click="changePage(currentPage-1)"
  >
    Prev
  </button>

  <button
    v-for="p in totalPages"
    :key="p"
    class="px-2 py-1 border rounded"
    :class="{ 'bg-gray-300': p === currentPage }"
    @click="changePage(p)"
  >
    {{ p }}
  </button>

  <button
    class="px-2 py-1 border rounded"
    :disabled="currentPage === totalPages"
    @click="changePage(currentPage+1)"
  >
    Next
  </button>
</div>
</template>