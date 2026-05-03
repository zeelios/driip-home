<template>
  <div class="page">
    <PPageHeader title="Kho vật lý" eyebrow="Quản lý">
      <template #actions>
        <PBtn @click="createOpen=true">+ Thêm kho</PBtn>
      </template>
    </PPageHeader>

    <div v-if="store.error" class="error-bar">{{ store.error }}</div>

    <div v-if="store.loading" class="skeleton-grid">
      <div v-for="i in 4" :key="i" class="skeleton wh-skeleton" />
    </div>
    <div v-else class="wh-grid">
      <NuxtLink v-for="w in store.list" :key="w.id" :to="`/warehouses/${w.id}`" class="wh-card">
        <div class="wh-card__header">
          <p class="wh-name">{{ w.name }}</p>
          <PBadge :status="w.is_active ? 'active' : 'blocked'" />
        </div>
        <p class="wh-addr">{{ w.address }}</p>
        <p class="wh-id">{{ w.id.slice(0,8) }}</p>
        <PBtn variant="ghost" size="xs" class="wh-edit" @click.prevent="openEdit(w)">Sửa</PBtn>
      </NuxtLink>
      <div v-if="!store.list.length" class="empty">Chưa có kho nào.</div>
    </div>

    <ZModal v-model="createOpen" :title="editingId ? 'Sửa kho' : 'Thêm kho'" size="sm">
      <div class="form-grid">
        <PInput v-model="form.name" label="Tên kho" />
        <PInput v-model="form.address" label="Địa chỉ" type="textarea" :rows="2" />
      </div>
      <div v-if="store.error" class="form-error">{{ store.error }}</div>
      <template #footer>
        <PBtn variant="ghost" @click="createOpen=false;resetForm()">Hủy</PBtn>
        <PBtn :loading="store.actionBusy" @click="save">{{ editingId ? 'Lưu' : 'Tạo' }}</PBtn>
      </template>
    </ZModal>
  </div>
</template>
<script setup lang="ts">
const store = useWarehousesStore()
const createOpen = ref(false)
const editingId = ref('')
const form = reactive({ name:'', address:'' })
onMounted(()=>store.fetchList())
function openEdit(w:any){editingId.value=w.id;Object.assign(form,{name:w.name,address:w.address});createOpen.value=true}
function resetForm(){editingId.value='';Object.assign(form,{name:'',address:''})}
async function save(){
  const ok=editingId.value?await store.update(editingId.value,form):await store.create(form)
  if(ok){createOpen.value=false;resetForm()}
}
</script>
<style scoped>
.page{display:flex;flex-direction:column;gap:1rem}
.error-bar{padding:.5rem .75rem;background:var(--status-danger);color:var(--status-danger-t);border-radius:.375rem;font-size:.8rem}
.skeleton-grid,.wh-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.75rem}
.wh-skeleton{height:120px}
.wh-card{display:flex;flex-direction:column;gap:.375rem;padding:1rem;background:var(--bg-card);border:1px solid var(--border);border-radius:.75rem;text-decoration:none;color:inherit;transition:border-color .15s;position:relative}
.wh-card:hover{border-color:var(--border-hi)}
.wh-card__header{display:flex;align-items:center;justify-content:space-between;gap:.5rem}
.wh-name{font-size:.9rem;font-weight:700;color:var(--text)}
.wh-addr{font-size:.75rem;color:var(--text-sub);line-height:1.4;flex:1}
.wh-id{font-family:monospace;font-size:.65rem;color:var(--text-mute)}
.wh-edit{align-self:flex-end;margin-top:.25rem}
.empty{grid-column:1/-1;padding:2rem;text-align:center;font-size:.875rem;color:var(--text-mute)}
.form-grid{display:flex;flex-direction:column;gap:.875rem}
.form-error{font-size:.75rem;color:#ef4444;padding:.5rem .75rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:.375rem;margin-top:.5rem}
</style>
