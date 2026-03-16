import { google } from 'googleapis'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)

  const {
    firstName, lastName, phone,
    province, district, ward, street,   // Vietnam address fields
    sku, size, color, coupon, timestamp,
  } = body

  if (!firstName || !lastName || !phone || !province || !street || !sku || !size || !color) {
    throw createError({ statusCode: 400, statusMessage: 'All required fields must be filled' })
  }

  // Compose a single address string for the sheet
  const fullAddress = [street, ward, district, province].filter(Boolean).join(', ')

  const config = useRuntimeConfig()

  const auth = new google.auth.GoogleAuth({
    credentials: {
      client_email: config.googleClientEmail as string,
      private_key:  (config.googlePrivateKey as string).replace(/\\n/g, '\n'),
    },
    scopes: ['https://www.googleapis.com/auth/spreadsheets'],
  })

  const sheets = google.sheets({ version: 'v4', auth })

  await sheets.spreadsheets.values.append({
    spreadsheetId:    config.googleSheetId as string,
    range:            'Orders!A:L',
    valueInputOption: 'USER_ENTERED',
    requestBody: {
      values: [[
        timestamp ?? new Date().toISOString(),
        firstName,
        lastName,
        phone,
        province,
        district ?? '',
        ward    ?? '',
        street,
        fullAddress,
        sku,
        size,
        color,
        coupon ?? 'DRIIP20',
        'order_form',
      ]],
    },
  })

  return { ok: true }
})
