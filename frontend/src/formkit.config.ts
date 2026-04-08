import { generateClasses } from '@formkit/themes'

export default {
  config: {
    classes: generateClasses({
      global: {
        label: 'block font-medium mb-1',
        input: 'border rounded px-3 py-2 w-full',
      },
    }),
  },
}