// formkit.config.ts
import { defaultConfig } from '@formkit/vue'
import { en } from '@formkit/i18n'

export default defaultConfig({
  locales: { en },
  locale: 'en',
  messages: {
    en: {
      submit: {
        invalid: 'Please fix the errors before submitting'
      }
    }
  }
})