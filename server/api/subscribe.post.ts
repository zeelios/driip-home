import { appendGoogleSheetRow } from '../utils/google-sheets'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)

  const { name, email, phone, coupon, timestamp } = body

  if (!name || !email) {
    throw createError({ statusCode: 400, statusMessage: 'Name and email are required' })
  }

  await appendGoogleSheetRow('Sheet1!A:F', [
    timestamp,
    name,
    email,
    phone || '',
    coupon || '',
    'landing_page',
  ])

  return { ok: true }
})
