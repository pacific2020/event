import { defineStore } from "pinia"
import { ref } from "vue"
import type { User } from "@/types"
import router from "@/router"
import axiosInstance from "@/axios"
import {useToast} from 'vue-toastification';
import { reactive } from 'vue'
import { AxiosError } from "axios"

const toast = useToast();

export const useAuthStore = defineStore("auth", () => {

    const user = ref<User | null>(null)
    const isLoggedIn = ref(false)
    const loading = ref(false)

    const setUser = (userData: User) => {
        user.value = userData
        isLoggedIn.value = true
    }

const getUser = async () => {

  loading .value= true
  try {

    const { data } = await axiosInstance.get('/user')

    setUser(data)

  } catch (error) {

    router.push('/staff/login')

  } finally {

    loading.value = false

  }

}

const cleanState = () => {
  user.value = null;
  isLoggedIn.value = false;
  localStorage.removeItem('token');

}


    const logout = async () => {
    
      try {
    
        await axiosInstance.post('/logout')
    
        user.value = null
        isLoggedIn.value = false
        localStorage.removeItem('token')
        localStorage.removeItem('user_role')
    
        router.push('/staff/login')
    
      } catch (error) {
    
        console.error("Logout Error:", error)
    
      }
    
    }


    
const form = reactive({
  email: '',
  password: ''
})

const errors = reactive({
  email: [] as string[],
  password: [] as string[]
})

const login = async () => {

  try {

    const response = await axiosInstance.post('/login', form)

    localStorage.setItem("token", response.data.access_token)
    localStorage.setItem("user_role", response.data.role)

    toast.success("Welcome back!")

  router.push(`/${response.data.role}/dashboard`)

  } catch (e) {

    if (e instanceof AxiosError && e.response?.status === 422) {

      const responseErrors = e.response.data.errors

      errors.email = responseErrors.email || []
      errors.password = responseErrors.password || []

    } else if (e instanceof AxiosError) {

      toast.error(e.response?.data.message)

    } else {

      toast.error("Login failed")

    }

  }

}



    return {
        user,
        isLoggedIn,
        loading,
        setUser,
        logout,
        getUser,
        login,
        form,
        errors,
        cleanState

    }

})