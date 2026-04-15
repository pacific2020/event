<script setup lang="ts">
import { useRoute } from 'vue-router'
import { ref, onMounted } from 'vue'
import axiosInstance from '@/axios'
import { useToast } from 'vue-toastification'

const route = useRoute()
const toast = useToast()
const email = ref('')
const isSubmitting = ref(false)

onMounted(() => {
  email.value = route.params.email as string
})

const handleConfirmation = async (formData: any) => {
  isSubmitting.value = true
  try {
    // Merge email from URL with form data
    const payload = { ...formData, email: email.value }
    
    const { data } = await axiosInstance.post('/guest/confirm-attendance', payload)
    toast.success(data.message || 'Confirmation submitted successfully!')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to submit confirmation.')
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
    <div class="max-w-xl w-full bg-white rounded-xl shadow-lg p-8">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Attendance Confirmation</h1>
        <p class="text-gray-500 mt-2">Confirming for: <span class="font-semibold text-blue-600">{{ email }}</span></p>
      </div>

      <FormKit
        type="form"
        :actions="false"
        @submit="handleConfirmation"
        #default="{ value = {} as any }"
      >
        <div class="space-y-4">
          <FormKit
            type="text"
            name="fullname"
            label="Full Name"
            placeholder="Enter your full name"
            validation="required|length:3"
          />

          <FormKit
            type="tel"
            name="phonenumber"
            label="Phone Number"
            placeholder="e.g. 0788000000"
            validation="required"
          />

          <FormKit
            type="text"
            name="organization"
            label="Organization / Company"
            placeholder="Where do you work?"
            validation="required"
          />

          <FormKit
            type="radio"
            name="attendance_type"
            label="Will you attend the event?"
            :options="{
              self: 'I will attend in person',
              delegate: 'I will send a delegate',
              not: 'I will not attend'
            }"
            validation="required"
          />

          <FormKit
            v-if="value.attendance_type !== 'not'"
            type="text"
            name="plate_number"
            label="Vehicle Plate Number"
            placeholder="e.g. RAC 123 A"
            help="Leave blank if you aren't bringing a vehicle."
          />

          <div class="pt-4">
            <FormKit
              type="submit"
              label="Submit Confirmation"
              :disabled="isSubmitting"
              input-class="w-full flex justify-center bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition-colors disabled:bg-gray-400"
            >
               <span v-if="!isSubmitting">Submit Confirmation</span>
               <span v-else>Processing...</span>
            </FormKit>
          </div>
        </div>
      </FormKit>
    </div>
  </div>
</template>