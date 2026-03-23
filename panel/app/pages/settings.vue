<template>
  <div>
    <!-- Loading -->
    <template v-if="state === 'loading'">
      <div class="settings-grid">
        <div class="settings-card" v-for="i in 3" :key="i">
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
        <ZButton variant="outline" size="sm" @click="fetchSettings">Thử lại</ZButton>
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else>
      <div class="settings-header">
        <p class="settings-description">Quản lý cấu hình hệ thống dành cho Panel.</p>
        <ZButton variant="primary" size="sm" :loading="savePending" @click="handleSave">
          Lưu thay đổi
        </ZButton>
      </div>

      <div v-if="!groupedSettings.length" class="settings-empty">
        <ZEmptyState
          title="Không có cài đặt"
          description="Hệ thống chưa có cấu hình nào được định nghĩa."
        />
      </div>

      <div v-else class="settings-grid">
        <div
          v-for="group in groupedSettings"
          :key="group.group"
          class="settings-card"
        >
          <p class="settings-card__title">{{ group.label }}</p>
          <div class="settings-card__fields">
            <div
              v-for="setting in group.items"
              :key="setting.key"
              class="settings-field"
            >
              <label :for="`setting-${setting.key}`" class="settings-field__label">
                {{ setting.label ?? setting.key }}
              </label>
              <component
                :is="setting.type === 'boolean' ? 'div' : 'div'"
                class="settings-field__control"
              >
                <template v-if="setting.type === 'boolean'">
                  <button
                    :id="`setting-${setting.key}`"
                    class="settings-toggle"
                    :class="{ 'settings-toggle--on': editValues[setting.key] === 'true' }"
                    type="button"
                    :aria-checked="editValues[setting.key] === 'true'"
                    role="switch"
                    @click="toggleBoolean(setting.key)"
                  >
                    <span class="settings-toggle__thumb" />
                  </button>
                </template>
                <template v-else-if="setting.type === 'text' || setting.type === 'string'">
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
    const payload = Object.entries(editValues).map(([key, value]) => ({ key, value }));
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

<style scoped>
.settings-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}
.settings-description {
  margin: 0;
  font-size: 0.875rem;
  color: #666;
}

.settings-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}
@media (min-width: 768px) {
  .settings-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (min-width: 1200px) {
  .settings-grid { grid-template-columns: repeat(3, 1fr); }
}

.settings-card {
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.07);
  border-radius: 10px;
  padding: 1.25rem;
}
.settings-card__title {
  margin: 0 0 1.125rem;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: #888;
}
.settings-card__fields {
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
}

.settings-field {}
.settings-field__label {
  display: block;
  margin-bottom: 0.375rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #444;
}
.settings-field__control {}

.settings-empty { margin-top: 1rem; }

/* Toggle switch */
.settings-toggle {
  position: relative;
  display: inline-flex;
  align-items: center;
  width: 2.75rem;
  height: 1.5rem;
  border-radius: 999px;
  border: 0;
  background: #d1d1ce;
  cursor: pointer;
  transition: background 200ms;
  padding: 0;
  flex-shrink: 0;
}
.settings-toggle--on {
  background: #111110;
}
.settings-toggle__thumb {
  position: absolute;
  left: 0.1875rem;
  width: 1.125rem;
  height: 1.125rem;
  border-radius: 50%;
  background: #fff;
  transition: transform 200ms ease;
  box-shadow: 0 1px 3px rgba(0,0,0,0.18);
}
.settings-toggle--on .settings-toggle__thumb {
  transform: translateX(1.25rem);
}

.mb-2 { margin-bottom: 0.5rem; }
.mb-3 { margin-bottom: 0.75rem; }
</style>
