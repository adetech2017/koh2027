import { ref, reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'

export function useFormSubmit(initialData, onSuccess = null) {
  const isSubmitting = ref(false)
  const errors = reactive({})

  const form = useForm(initialData)

  const handleSubmit = async (submitFn) => {
    isSubmitting.value = true
    errors.value = {}

    try {
      await submitFn()
      if (onSuccess) onSuccess()
    } catch (error) {
      if (error.response?.data?.errors) {
        Object.assign(errors, error.response.data.errors)
      } else {
        console.error('Form submission error:', error)
      }
    } finally {
      isSubmitting.value = false
    }
  }

  return {
    form,
    isSubmitting,
    errors,
    handleSubmit,
  }
}
