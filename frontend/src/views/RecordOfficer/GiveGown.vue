<script setup lang="ts">
import axiosInstance from '@/axios';
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/store/auth'
import { onMounted, ref, reactive } from 'vue'
import VueSelect from "vue3-select-component";

const auth = useAuthStore()
const toast = useToast()
const loading = ref(false)

// Initial state for resetting
const initialState = {
  reg_no: '',
  expected_returning_date: '',
  notes: ''
}

const formData = reactive({ ...initialState })
const graduandOptions = ref<{ label: string; value: string }[]>([])

const getStudent = async () => {
  try {
    const { data } = await axiosInstance.get('/get-graduand')
    if (data.graduand) {
      graduandOptions.value = data.graduand.map((c: any) => ({
        label: `${c.reg_no} - ${c.first_name} ${c.last_name}`,
        value: c.reg_no
      }))
    }
  } catch (e) {
    toast.error("Failed to load students")
  }
}

onMounted(() => {
  auth.getUser()
  getStudent()
})

const handleSubmit = async () => {
  if (!formData.reg_no) {
    toast.error("Please select a student.");
    return;
  }

  loading.value = true;
  try {
    const payload = {
      user_id: auth.user?.id,
      reg_no: formData.reg_no,
      expected_returning_date: formData.expected_returning_date,
      status: 'Issued',
      notes: formData.notes
    };

    const { data } = await axiosInstance.post('/issue-gown', payload);
    toast.success(data.message || "Success!");

    // ✅ FORCE RESET: Manually clear each property to ensure the UI updates
    formData.reg_no = '';
    formData.expected_returning_date = '';
    formData.notes = '';
    
  } catch (e: any) {
    toast.error(e.response?.data?.message || "Server Error");
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="flex min-h-screen bg-gray-50">

    <div class="flex-1 p-8 flex justify-center items-start pt-12">
      <div class="w-full max-w-lg bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        
        <div class="mb-6">
          <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Issue Graduation Gown</h2>
          <p class="text-gray-500 text-sm">Assign a gown to a student.</p>
        </div>

        <FormKit 
          type="form" 
          @submit="handleSubmit" 
          :actions="false"
        >
          <div class="mb-5">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Search Student</label>
           <VueSelect 
  v-model="formData.reg_no"
  :options="graduandOptions"
  placeholder="Select Reg No..."
  :is-searchable="true"
  :styles="{
    // ✅ Fix: Tell TypeScript 'base' is an object
    control: (base: Record<string, any>) => ({
      ...base,
      padding: '4px',
      borderRadius: '0.5rem',
      borderColor: '#e5e7eb',
    })
  }"
/>
            <p v-if="!formData.reg_no && loading" class="text-red-500 text-xs mt-1">Student selection is required</p>
          </div>

          <FormKit
            type="date"
            name="expected_returning_date" 
            label="Expected Return Date"
            v-model="formData.expected_returning_date"
            validation="required|date_after:2025-12-31" 
            validation-visibility="live"
            outer-class="mb-4"
          />

          <FormKit
            type="textarea"
            name="notes"
            v-model="formData.notes"
            label="Additional Notes (Optional)"
            rows="3"
            outer-class="mb-6"
          />

          <button
            type="submit"
            :disabled="loading"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-md active:transform active:scale-[0.98]"
          >
            <span v-if="loading" class="flex items-center justify-center gap-2">
              <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              Processing...
            </span>
            <span v-else>Confirm Issuance</span>
          </button>
        </FormKit>
      </div>
    </div>
  </div>
</template>