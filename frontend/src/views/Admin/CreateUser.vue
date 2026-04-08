<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useAuthStore } from '@/store/auth'
import axiosInstance from '@/axios'
import { AxiosError } from 'axios'
import { useToast } from 'vue-toastification'
import type { College } from '@/types/index'

const auth = useAuthStore()
const toast = useToast()
const loading = ref(false)
const colleges = ref<{ label: string; value: number }[]>([])

// ✅ Fetch colleges from API
const getCollege = async () => {
  try {
    const { data } = await axiosInstance.get('/colleges')
    colleges.value = data.data.map((c: College) => ({
      label: c.short_name,
      value: c.id
    }))
  } catch (e) {
    if (e instanceof AxiosError) {
      toast.error(e.response?.data.message || "Failed to load colleges")
    }
  }
}

onMounted(() => {
  auth.getUser()
  getCollege()
})

const handleSubmit = async (formData: any) => {
  loading.value = true

  try {
    // Now you can use formData.position DIRECTLY.
    // No mapping or computed property needed.
    const payload = {
      ...formData,
      position: formData.position, // This will be "Admin", "Scanner", etc.
      is_active: 1
    }

    const response = await axiosInstance.post('/createUsers', payload)
    toast.success(response.data.message)
  } catch (e: any) {
    toast.error('Submission failed')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen bg-gray-100">


    <div class="flex-1 flex items-center justify-center p-6">
      <div class="w-full max-w-lg bg-white shadow-lg rounded-xl p-8 border border-gray-100">
        
        <h2 class="text-2xl font-bold text-center mb-8 text-gray-800">
          Create User
        </h2>

        <FormKit
          type="form"
          :disabled="loading"
          @submit="handleSubmit"
          submit-label="Create Account"
        >
          <FormKit
            type="select"
            name="college_id"
            label="Select College"
            :options="colleges"
            placeholder="Choose a college"
            validation="required"
            outer-class="mb-4"
          />
<FormKit
      type="select"
      name="position"
      label="Select Position"
      placeholder="Choose a position"
      validation="required"
      :options="[
        { value: 'Admin', label: 'Admin' },
        { value: 'Scanner', label: 'Scanner' },
        { value: 'RecordOfficer', label: 'RecordOfficer' },
        { value: 'Protocol', label: 'Protocol' }
      ]"
    />

          <div class="space-y-4">
            <FormKit
              type="text"
              name="fullname"
              label="Fullname"
              placeholder="Enter fullname"
              validation="required"
            />

            <FormKit
              type="email"
              name="email"
              label="Email Address"
              placeholder="Enter email"
              validation="required|email"
            />

            <FormKit
              type="text"
              name="phone"
              label="Phone Number"
              placeholder="Enter phone number"
              validation="required"
            />

            <FormKit
              type="password"
              name="password"
              label="Password"
              placeholder="Enter password"
              validation="required|min:6"
            />
          </div>
        </FormKit>
      </div>
    </div>
  </div>
</template>