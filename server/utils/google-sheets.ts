import { useRuntimeConfig } from "#imports";
import { google } from "googleapis";

function createSheetsClient() {
  const config = useRuntimeConfig();

  const auth = new google.auth.GoogleAuth({
    credentials: {
      client_email: config.googleClientEmail as string,
      private_key: (config.googlePrivateKey as string).replace(/\\n/g, "\n"),
    },
    scopes: ["https://www.googleapis.com/auth/spreadsheets"],
  });

  return google.sheets({ version: "v4", auth });
}

export async function appendGoogleSheetRow(
  range: string,
  values: (string | number)[] | (string | number)[][]
): Promise<void> {
  const config = useRuntimeConfig();
  const sheets = createSheetsClient();
  const isMultiRow = Array.isArray(values[0]);

  await sheets.spreadsheets.values.append({
    spreadsheetId: config.googleSheetId as string,
    range,
    valueInputOption: "USER_ENTERED",
    requestBody: {
      values: isMultiRow ? (values as (string | number)[][]) : [values as (string | number)[]],
    },
  });
}

export async function readGoogleSheetValues(range: string): Promise<(string | number)[][]> {
  const config = useRuntimeConfig();
  const sheets = createSheetsClient();

  const response = await sheets.spreadsheets.values.get({
    spreadsheetId: config.googleSheetId as string,
    range,
  });

  return (response.data.values ?? []) as (string | number)[][];
}
