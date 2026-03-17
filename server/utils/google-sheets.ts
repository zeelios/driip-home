import { useRuntimeConfig } from "#imports";
import { google } from "googleapis";

export async function appendGoogleSheetRow(
  range: string,
  values: (string | number)[]
): Promise<void> {
  const config = useRuntimeConfig();

  const auth = new google.auth.GoogleAuth({
    credentials: {
      client_email: config.googleClientEmail as string,
      private_key: (config.googlePrivateKey as string).replace(/\\n/g, "\n"),
    },
    scopes: ["https://www.googleapis.com/auth/spreadsheets"],
  });

  const sheets = google.sheets({ version: "v4", auth });

  await sheets.spreadsheets.values.append({
    spreadsheetId: config.googleSheetId as string,
    range,
    valueInputOption: "USER_ENTERED",
    requestBody: {
      values: [values],
    },
  });
}
