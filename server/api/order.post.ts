import { appendGoogleSheetRow } from '../utils/google-sheets'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)

  const {
    firstName, lastName, phone, email,
    province, district, ward, street,
    sku, size, color, coupon, timestamp,
  } = body

  if (!firstName || !lastName || !phone || !province || !street || !sku || !size || !color) {
    throw createError({ statusCode: 400, statusMessage: 'All required fields must be filled' })
  }

  const fullAddress = [street, ward, district, province].filter(Boolean).join(', ')

  await appendGoogleSheetRow('Orders!A:O', [
    timestamp ?? new Date().toISOString(),
    firstName,
    lastName,
    phone,
    email ?? '',
    province,
    district ?? '',
    ward ?? '',
    street,
    fullAddress,
    sku,
    size,
    color,
    coupon ?? 'DRIIP20',
    'order_form',
  ])

  return { ok: true }
})
