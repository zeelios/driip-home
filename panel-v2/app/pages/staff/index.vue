<template>
  <div class="page">
    <PPageHeader title="Nhân viên" eyebrow="Quản lý">
      <template #actions>
        <PBtn v-if="auth.isAdmin" @click="createOpen=true">+ Thêm nhân viên</PBtn>
      </template>
    </PPageHeader>
    <div v-if="store.error" class="error-bar">{{ store.error }}</div>
    <div class="table-card">
      <ZTable :cols="cols" :rows="store.list" :loading="store.loading" row-key="id">
        <template #cell-name="{ row }">
          <div class="staff-name">
            <div class="avatar">{{ row.name?.[0]?.toUpperCase() }}</div>
            <div><p class="name-t">{{ row.name }}</p><p class="email-t">{{ row.email }}</p></div>
          </div>
        </template>
        <template #cell-role="{ value }"><PBadge :status="value==='admin'?'danger':value==='manager'?'pending':'muted'" :label="value" /></template>
        <template #cell-is_active="{ value }"><PBadge :status="value?'active':'blocked'" /></template>
        <template #cell-actions="{ row }">
          <div class="row-actions" v-if="auth.isAdmin">
            <PBtn variant="ghost" size="xs" @click.stop="openEdit(row)">Sửa</PBtn>
            <PBtn variant="ghost" size="xs" @click.stop="openPwd(row)">Mật khẩu</PBtn>
            <PBtn v-if="row.id !== auth.staff?.id" variant="danger" size="xs" :loading="store.actionBusy && delId===row.id" @click.stop="confirmDelete(row.id)">Xóa</PBtn>
          </div>
        </template>
        <template #mobile-card="{ row }">
          <div class="mc-row">
            <div class="avatar">{{ row.name?.[0]?.toUpperCase() }}</div>
            <div class="mc-info"><p class="name-t">{{ row.name }}</p><p class="email-t">{{ row.email }}</p></div>
          </div>
          <div class="mc-right">
            <PBadge :status="row.role==='admin'?'danger':row.role==='manager'?'pending':'muted'" :label="row.role" />
          </div>
        </template>
      </ZTable>
    </div>
    <PPagination :page="store.filters.page" :total="store.totalPages" @change="changePage" />

    <!-- Create/Edit modal -->
    <ZModal v-model="createOpen" :title="editingId?'Sửa nhân viên':'Thêm nhân viên'">
      <div class="form-grid">
        <PInput v-model="form.name" label="Họ tên" />
        <PInput v-model="form.email" label="Email" type="email" />
        <PInput v-if="!editingId" v-model="form.password" label="Mật khẩu" type="password" />
        <PInput v-model="form.role" label="Vai trò" type="select">
          <option value="admin">Admin</option>
          <option value="manager">Manager</option>
          <option value="staff">Staff</option>
          <option value="readonly">Read-only</option>
        </PInput>
      </div>
      <div v-if="store.error" class="form-error">{{ store.error }}</div>
      <template #footer>
        <PBtn variant="ghost" @click="createOpen=false;resetForm()">Hủy</PBtn>
        <PBtn :loading="store.actionBusy" @click="save">{{ editingId?'Lưu':'Tạo' }}</PBtn>
      </template>
    </ZModal>

    <!-- Change password modal -->
    <ZModal v-model="pwdOpen" title="Đổi mật khẩu" size="sm">
      <PInput v-model="newPwd" label="Mật khẩu mới" type="password" />
      <div v-if="store.error" class="form-error">{{ store.error }}</div>
      <template #footer>
        <PBtn variant="ghost" @click="pwdOpen=false">Hủy</PBtn>
        <PBtn :loading="store.actionBusy" @click="savePwd">Lưu</PBtn>
      </template>
    </ZModal>
  </div>
</template>
<script setup lang="ts">
import type { ZCol } from '~/components/ZTable.vue'
const store = useStaffStore()
const auth = useAuthStore()
const createOpen = ref(false)
const pwdOpen = ref(false)
const editingId = ref('')
const delId = ref('')
const newPwd = ref('')
const pwdTargetId = ref('')
const form = reactive({ name:'', email:'', password:'', role:'staff' })
const cols: ZCol[] = [
  { key:'name', label:'Nhân viên', width:'minmax(150px,2fr)' },
  { key:'role', label:'Vai trò', width:'110px' },
  { key:'is_active', label:'Trạng thái', width:'100px', hide:'sm' },
  { key:'actions', label:'', width:'180px', align:'right', hide:'sm' },
]
onMounted(()=>store.fetchList())
function changePage(p:number){store.filters.page=p;store.fetchList()}
function openEdit(r:any){editingId.value=r.id;Object.assign(form,{name:r.name,email:r.email,password:'',role:r.role});createOpen.value=true}
function openPwd(r:any){pwdTargetId.value=r.id;newPwd.value='';store.error=null;pwdOpen.value=true}
function resetForm(){editingId.value='';Object.assign(form,{name:'',email:'',password:'',role:'staff'})}
async function save(){
  const ok=editingId.value
    ?await store.update(editingId.value,{name:form.name,email:form.email,role:form.role})
    :await store.create(form)
  if(ok){createOpen.value=false;resetForm()}
}
async function savePwd(){
  if(!newPwd.value)return
  const ok=await store.changePassword(pwdTargetId.value,newPwd.value)
  if(ok)pwdOpen.value=false
}
async function confirmDelete(id:string){
  if(!confirm('Xóa nhân viên này?'))return
  delId.value=id;await store.remove(id);delId.value=''
}
</script>
<style scoped>
.page{display:flex;flex-direction:column;gap:1rem}
.error-bar{padding:.5rem .75rem;background:var(--status-danger);color:var(--status-danger-t);border-radius:.375rem;font-size:.8rem}
.table-card{background:var(--bg-card);border:1px solid var(--border);border-radius:.75rem;overflow:hidden}
.staff-name,.mc-row{display:flex;align-items:center;gap:.625rem}
.mc-row{flex:1;min-width:0}
.mc-info,.mc-right{min-width:0}
.mc-right{flex-shrink:0}
.avatar{width:1.75rem;height:1.75rem;border-radius:.35rem;background:var(--bg-hover);border:1px solid var(--border-hi);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:var(--text);flex-shrink:0}
.name-t{font-size:.8rem;font-weight:600;color:var(--text)}
.email-t{font-size:.68rem;color:var(--text-mute)}
.row-actions{display:flex;gap:.375rem;justify-content:flex-end}
.form-grid{display:flex;flex-direction:column;gap:.875rem}
.form-error{font-size:.75rem;color:#ef4444;padding:.5rem .75rem;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:.375rem;margin-top:.5rem}
</style>
