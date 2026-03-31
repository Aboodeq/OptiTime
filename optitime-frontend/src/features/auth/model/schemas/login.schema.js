import { z } from 'zod'

export function createLoginSchema(t) {
  return z.object({
    email: z
      .string()
      .trim()
      .min(1, t('pages.login.errors.emailRequired'))
      .email(t('pages.login.errors.emailInvalid')),
    password: z.string().min(1, t('pages.login.errors.passwordRequired')),
  })
}
