import { appendGoogleSheetRow, readGoogleSheetValues } from "./google-sheets";

type SheetRow = (string | number)[];

const ORDER_FLUSH_MAX_WAIT_MS = 60_000;
const ORDER_FLUSH_RETRY_MS = 60_000;
const SHEET_RANGE = "Web!A:T";

const pendingRows: SheetRow[] = [];
let flushTimer: ReturnType<typeof setTimeout> | null = null;
let flushPromise: Promise<void> | null = null;
let allocationChain: Promise<void> = Promise.resolve();

const highestSequenceByDate = new Map<string, number>();
const seedPromiseByDate = new Map<string, Promise<number>>();

function getVietnamDateCode(date = new Date()): string {
  const parts = new Intl.DateTimeFormat("en-US", {
    timeZone: "Asia/Ho_Chi_Minh",
    month: "short",
    day: "2-digit",
  }).formatToParts(date);

  const month = parts.find((part) => part.type === "month")?.value ?? "";
  const day = parts.find((part) => part.type === "day")?.value ?? "";

  return `${month}${day}`;
}

async function seedHighestSequence(dateCode: string): Promise<number> {
  const cached = highestSequenceByDate.get(dateCode);
  if (cached !== undefined) return cached;

  const cachedPromise = seedPromiseByDate.get(dateCode);
  if (cachedPromise) return cachedPromise;

  const promise = (async () => {
    const prefix = `${dateCode}-`;
    let highestSequence = 0;

    try {
      // Read from the first row so blank sheets and single-header sheets don't
      // trigger a grid-limits error before we can fall back to the local seed.
      const rows = await readGoogleSheetValues("Web!A:A");

      for (const row of rows) {
        const value = String(row[0] ?? "").trim();
        if (!value.startsWith(prefix)) continue;

        const suffix = value.slice(prefix.length);
        if (!/^\d{2}$/.test(suffix)) continue;

        highestSequence = Math.max(highestSequence, Number(suffix));
      }
    } catch (error) {
      console.warn(
        "[Google Sheets] Could not read existing order IDs, falling back to the local sequence cache.",
        error
      );
    }

    highestSequenceByDate.set(dateCode, highestSequence);
    return highestSequence;
  })().finally(() => {
    seedPromiseByDate.delete(dateCode);
  });

  seedPromiseByDate.set(dateCode, promise);
  return promise;
}

export async function reserveOrderId(date = new Date()): Promise<string> {
  const allocate = async (): Promise<string> => {
    const dateCode = getVietnamDateCode(date);
    const highestSequence = await seedHighestSequence(dateCode);
    const currentHighest =
      highestSequenceByDate.get(dateCode) ?? highestSequence;
    const nextSequence = currentHighest + 1;

    highestSequenceByDate.set(dateCode, nextSequence);

    return `${dateCode}-${String(nextSequence).padStart(2, "0")}`;
  };

  const reserved = allocationChain.then(allocate, allocate);
  allocationChain = reserved.then(
    () => undefined,
    () => undefined
  );

  return reserved;
}

function clearFlushTimer(): void {
  if (!flushTimer) return;

  clearTimeout(flushTimer);
  flushTimer = null;
}

function scheduleFlush(delayMs = 0): void {
  if (flushTimer) return;

  flushTimer = setTimeout(() => {
    flushTimer = null;
    void flushOrderQueue().catch((error) => {
      console.error("[Google Sheets] Failed to flush queued orders.", error);
      scheduleFlush(ORDER_FLUSH_RETRY_MS);
    });
  }, delayMs);
}

export function queueOrderRows(rows: SheetRow[]): void {
  if (!rows.length) return;

  const firstRow = rows[0] ?? [];
  console.info("[Google Sheets] Queueing order row(s)", {
    range: SHEET_RANGE,
    rowCount: rows.length,
    dob: String(firstRow[18] ?? ""),
    gender: String(firstRow[19] ?? ""),
  });

  pendingRows.push(...rows);
  scheduleFlush(ORDER_FLUSH_MAX_WAIT_MS);
}

export async function flushOrderQueue(): Promise<void> {
  if (flushPromise) return flushPromise;
  if (!pendingRows.length) {
    clearFlushTimer();
    return;
  }

  clearFlushTimer();

  const batch = pendingRows.splice(0, pendingRows.length);

  flushPromise = (async () => {
    try {
      await appendGoogleSheetRow(SHEET_RANGE, batch);
    } catch (error) {
      pendingRows.unshift(...batch);
      throw error;
    } finally {
      if (!pendingRows.length) {
        clearFlushTimer();
      }
    }
  })().finally(() => {
    flushPromise = null;

    if (pendingRows.length) {
      scheduleFlush();
    }
  });

  return flushPromise;
}
