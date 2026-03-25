<template>
  <div>
    <!-- Loading -->
    <template v-if="state === 'loading'">
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-5"
          v-for="i in 3"
          :key="i"
        >
          <ZSkeleton height="0.75rem" width="40%" class="mb-3" />
          <ZSkeleton height="2.5rem" width="100%" class="mb-2" />
          <ZSkeleton height="2.5rem" width="100%" class="mb-2" />
          <ZSkeleton height="2.5rem" width="80%" />
        </div>
      </div>
    </template>

    <!-- Error -->
    <ZEmptyState
      v-else-if="state === 'error'"
      title="Không thể tải cài đặt"
      :description="errorMsg ?? ''"
      :icon="ERROR_ICON"
    >
      <template #action>
        <ZButton variant="outline" size="sm" @click="fetchSettings"
          >Thử lại</ZButton
        >
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else>
      <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
        <p class="m-0 text-sm text-white/50">
          Quản lý cấu hình hệ thống dành cho Panel.
        </p>
        <ZButton
          variant="primary"
          size="sm"
          :loading="savePending"
          @click="handleSave"
        >
          Lưu thay đổi
        </ZButton>
      </div>

      <div v-if="!groupedSettings.length" class="mt-4">
        <ZEmptyState
          title="Không có cài đặt"
          description="Hệ thống chưa có cấu hình nào được định nghĩa."
        />
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
          v-for="group in groupedSettings"
          :key="group.group"
          class="bg-[#111111] border border-white/8 rounded-[10px] p-5"
        >
          <p
            class="m-0 mb-4.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            {{ group.label }}
          </p>
          <div class="flex flex-col gap-3.5">
            <div v-for="setting in group.items" :key="setting.key">
              <label
                :for="`setting-${setting.key}`"
                class="block mb-1.5 text-[0.8125rem] font-medium text-white/70"
              >
                {{ setting.label ?? setting.key }}
              </label>
              <component :is="setting.type === 'boolean' ? 'div' : 'div'">
                <template v-if="setting.type === 'boolean'">
                  <button
                    :id="`setting-${setting.key}`"
                    class="relative inline-flex items-center w-11 h-6 rounded-full border-0 bg-white/15 cursor-pointer transition-colors duration-200 p-0 shrink-0"
                    :class="{
                      'bg-white': editValues[setting.key] === 'true',
                    }"
                    type="button"
                    :aria-checked="editValues[setting.key] === 'true'"
                    role="switch"
                    @click="toggleBoolean(setting.key)"
                  >
                    <span
                      class="absolute left-0.75 w-4.5 h-4.5 rounded-full bg-white/90 transition-transform duration-200 ease shadow-sm"
                      :class="{
                        'translate-x-5': editValues[setting.key] === 'true',
                        'bg-[#0a0a0a]': editValues[setting.key] === 'true',
                      }"
                    />
                  </button>
                </template>
                <template
                  v-else-if="
                    setting.type === 'text' || setting.type === 'string'
                  "
                >
                  <ZInput
                    :id="`setting-${setting.key}`"
                    v-model="editValues[setting.key]"
                    :placeholder="setting.label ?? setting.key"
                  />
                </template>
                <template v-else-if="setting.type === 'number'">
                  <ZInput
                    :id="`setting-${setting.key}`"
                    v-model="editValues[setting.key]"
                    type="number"
                    :placeholder="setting.label ?? setting.key"
                  />
                </template>
                <template v-else>
                  <ZInput
                    :id="`setting-${setting.key}`"
                    v-model="editValues[setting.key]"
                    :placeholder="setting.label ?? setting.key"
                  />
                </template>
              </component>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";
import { getErrorMessage } from "~/utils/format";
import type { SettingModel } from "~~/types/generated/backend-models.generated";

definePageMeta({ layout: "panel" });

type LoadState = "idle" | "loading" | "loaded" | "error";

interface SettingsListResponse {
  data: SettingModel[];
}

interface GroupedSettings {
  group: string;
  label: string;
  items: SettingModel[];
}

const api = useApi();
const toast = useToast();

const state = ref<LoadState>("idle");
const errorMsg = ref<string | null>(null);
const settings = ref<SettingModel[]>([]);
const editValues = reactive<Record<string, string>>({});
const savePending = ref(false);

const ERROR_ICON = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

const GROUP_LABELS: Record<string, string> = {
  general: "Cài đặt chung",
  payment: "Thanh toán",
  shipping: "Vận chuyển",
  notification: "Thông báo",
  loyalty: "Chương trình tích điểm",
  tax: "Thuế",
};

const groupedSettings = computed((): GroupedSettings[] => {
  const groups = new Map<string, SettingModel[]>();
  for (const s of settings.value) {
    if (!groups.has(s.group)) groups.set(s.group, []);
    groups.get(s.group)!.push(s);
  }
  return Array.from(groups.entries()).map(([group, items]) => ({
    group,
    label: GROUP_LABELS[group] ?? group,
    items,
  }));
});

async function fetchSettings(): Promise<void> {
  state.value = "loading";
  errorMsg.value = null;

  try {
    const response = await api.get<SettingsListResponse>("/settings");
    settings.value = response.data ?? [];
    for (const s of settings.value) {
      editValues[s.key] = s.value ?? "";
    }
    state.value = "loaded";
  } catch (error) {
    state.value = "error";
    errorMsg.value = getErrorMessage(error, "Không thể tải cài đặt.");
  }
}

function toggleBoolean(key: string): void {
  editValues[key] = editValues[key] === "true" ? "false" : "true";
}

async function handleSave(): Promise<void> {
  savePending.value = true;
  try {
    const payload = settings.value.map((s) => ({
      group: s.group,
      key: s.key,
      value: editValues[s.key] ?? "",
    }));
    await api.patch("/settings", { settings: payload });
    toast.success("Đã lưu cài đặt", "Cấu hình đã được cập nhật.");
  } catch (error) {
    toast.error("Lưu thất bại", getErrorMessage(error));
  } finally {
    savePending.value = false;
  }
}

onMounted(() => {
  fetchSettings();
});
</script>
