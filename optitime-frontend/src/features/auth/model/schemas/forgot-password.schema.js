import { z } from 'zod'

export function createForgotPasswordEmailSchema(t) {
  return z.object({
    email: z
      .string()
      .trim()
      .min(1, t('pages.forgotPassword.errors.emailRequired'))
      .email(t('pages.forgotPassword.errors.emailInvalid')),
  })
}

export function createForgotPasswordCodeSchema(t) {
  return z.object({
    code: z
      .string()
      .trim()
      .min(1, t('pages.forgotPassword.errors.codeRequired'))
      .length(6, t('pages.forgotPassword.errors.codeInvalid')),
  })
}

export function createForgotPasswordResetSchema(t) {
  return z
    .object({
      code: z
        .string()
        .trim()
        .min(1, t('pages.forgotPassword.errors.codeRequired'))
        .length(6, t('pages.forgotPassword.errors.codeInvalid')),
      password: z
        .string()
        .min(1, t('pages.forgotPassword.errors.passwordRequired'))
        .min(8, t('pages.forgotPassword.errors.passwordMin')),
      confirmPassword: z.string().min(1, t('pages.forgotPassword.errors.confirmPasswordRequired')),
    })
    .refine((data) => data.password === data.confirmPassword, {
      path: ['confirmPassword'],
      message: t('pages.forgotPassword.errors.passwordMismatch'),
    })
}
