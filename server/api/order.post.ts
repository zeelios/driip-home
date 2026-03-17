import { appendGoogleSheetRow } from '../utils/google-sheets'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)

  const {
    firstName, lastName, phone, email,
    province, fullAddress, street,
    boxes, compareTotal, tierTotal, finalTotal,
    sku, size, color, coupon, timestamp,
  } = body

  if (!firstName || !lastName || !phone || !province || !(fullAddress || street) || !sku || !size || !color) {
    throw createError({ statusCode: 400, statusMessage: 'All required fields must be filled' })
  }

  const resolvedAddress = [fullAddress || street, province].filter(Boolean).join(', ')

  await appendGoogleSheetRow('Orders!A:O', [
    timestamp ?? new Date().toISOString(),
    firstName,
    lastName,
    phone,
    email ?? '',
    province,
    boxes ?? 1,
    finalTotal ?? '',
    fullAddress || street,
    `${resolvedAddress}${tierTotal ? ` | compare:${compareTotal ?? ''} | tier:${tierTotal} | final:${finalTotal ?? ''}` : ''}`,
    sku,
    size,
    color,
    coupon ?? 'DRIIP20',
    'order_form',
  ])

  return { ok: true }
})
