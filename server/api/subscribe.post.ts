import { google } from 'googleapis'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)

  const { name, email, phone, coupon, timestamp } = body

  if (!name || !email) {
    throw createError({ statusCode: 400, statusMessage: 'Name and email are required' })
  }

  const config = useRuntimeConfig()

  // Build Google Auth from service account credentials stored in env
  const auth = new google.auth.GoogleAuth({
    credentials: {
      client_email: config.googleClientEmail,
      private_key: (config.googlePrivateKey as string).replace(/\\n/g, '\n'),
    },
    scopes: ['https://www.googleapis.com/auth/spreadsheets'],
  })

  const sheets = google.sheets({ version: 'v4', auth })

  await sheets.spreadsheets.values.append({
    spreadsheetId: config.googleSheetId as string,
    range: 'Sheet1!A:F',
    valueInputOption: 'USER_ENTERED',
    requestBody: {
      values: [[
        timestamp,
        name,
        email,
        phone || '',
        coupon || '',
        'landing_page',
      ]],
    },
  })

  return { ok: true }
})
