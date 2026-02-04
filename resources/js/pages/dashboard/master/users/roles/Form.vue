<template>
  <div class="modal fade" id="kt_modal_role" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-800px">
      <div class="modal-content rounded-4 shadow-lg border-0 theme-modal">
        
        <div class="modal-header border-0 pb-0 justify-content-between align-items-center py-4 px-5">
            <h2 class="fw-bold m-0 fs-3 text-gray-900">{{ isEditMode ? 'Edit Role' : 'Role Baru' }}</h2>
            <div class="btn btn-sm btn-icon btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
                <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>

        <div class="modal-body scroll-y px-5 pb-5 pt-2">
          <el-form @submit.prevent="submit" :model="formData" :rules="rules" ref="formRef" label-position="top" class="modern-form">
            
            <div class="bg-light-subtle rounded-3 p-4 mb-5 border border-dashed border-gray-300">
                <div class="row g-5">
                    <div class="col-md-6">
                        <el-form-item prop="name" class="mb-0">
                            <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase ls-1">Slug Role</span></template>
                            <el-input v-model="formData.name" placeholder="cth: admin-gudang" class="metronic-input fw-bold" :disabled="isEditMode">
                                <template #prefix><i class="ki-duotone ki-code fs-3 text-gray-500 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></template>
                            </el-input>
                            <div class="form-text text-muted fs-9 mt-1" v-if="!isEditMode">Gunakan huruf kecil tanpa spasi.</div>
                        </el-form-item>
                    </div>
                    <div class="col-md-6">
                        <el-form-item prop="full_name" class="mb-0">
                            <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase ls-1">Nama Tampilan</span></template>
                            <el-input v-model="formData.full_name" placeholder="cth: Admin Gudang" class="metronic-input fw-bold fs-6">
                                <template #prefix><i class="ki-duotone ki-text-align-left fs-3 text-gray-500 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></template>
                            </el-input>
                        </el-form-item>
                    </div>
                </div>
            </div>

            <div class="mb-2">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-30px me-2 bg-light-orange rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ki-duotone ki-shield-tick fs-3 text-orange"><span class="path1"></span><span class="path2"></span></i>
                        </div>
                        <label class="fw-bold fs-7 text-gray-800 mb-0">Hak Akses</label>
                        <span class="badge badge-light-orange ms-2 fw-bold">{{ formData.permissions.length }} Dipilih</span>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <div class="position-relative w-200px">
                            <i class="ki-duotone ki-magnifier fs-4 text-gray-500 position-absolute top-50 translate-middle-y ms-3"><span class="path1"></span><span class="path2"></span></i>
                            <input type="text" v-model="searchPermission" class="form-control form-control-sm form-control-solid ps-10" placeholder="Cari izin..." />
                        </div>
                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input border-orange" type="checkbox" id="check_all" @change="toggleAllPermissions" :checked="isAllSelected" />
                            <label class="form-check-label fw-bold fs-8 text-gray-600 cursor-pointer text-nowrap" for="check_all">Semua</label>
                        </div>
                    </div>
                </div>

                <div class="permission-box rounded-3 border border-gray-300 bg-body p-3 scroll-y">
                    
                    <div v-if="loadingPermissions" class="d-flex flex-column align-items-center justify-content-center py-10">
                        <span class="spinner-border spinner-border-sm text-orange align-middle mb-2"></span> 
                        <span class="text-gray-500 fs-7">Memuat daftar izin...</span>
                    </div>
                    
                    <div v-else-if="filteredPermissions.length === 0" class="text-center py-10 text-muted">
                        <i class="ki-duotone ki-search-list fs-1 text-gray-300 mb-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        <div class="fs-7">Tidak ditemukan izin dengan kata kunci "{{ searchPermission }}"</div>
                    </div>

                    <div v-else class="row g-3">
                        <div class="col-md-6 col-lg-4" v-for="perm in filteredPermissions" :key="perm.id">
                            <!-- PERBAIKAN CLASS: Menggunakan includes untuk highlighting yang akurat -->
                            <label class="d-flex align-items-center h-100 p-3 rounded-2 border cursor-pointer transition-200 permission-item" 
                                   :class="formData.permissions.includes(perm.name) ? 'border-orange bg-light-orange active' : 'border-gray-200 hover-border-gray-400 bg-white'">
                                
                                <div class="form-check form-check-custom form-check-solid form-check-sm me-3">
                                    <input class="form-check-input" type="checkbox" :value="perm.name" v-model="formData.permissions" />
                                </div>
                                
                                <div class="d-flex flex-column overflow-hidden">
                                    <span class="fw-bold fs-8 text-truncate" :class="formData.permissions.includes(perm.name) ? 'text-orange' : 'text-gray-800'">
                                        {{ formatPermissionName(perm.name) }}
                                    </span>
                                    <span class="fs-9 text-muted font-monospace text-truncate">{{ perm.name }}</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end align-items-center mt-6 pt-3 border-top border-gray-200">
                 <button type="button" class="btn btn-light me-3 fw-bold text-gray-700 px-5" data-bs-dismiss="modal">Batal</button>
                 <button :disabled="loading" class="btn btn-orange fw-bold px-6 shadow-sm hover-elevate" type="submit">
                    <span v-if="!loading" class="d-flex align-items-center">
                        Simpan <i class="ki-duotone ki-check-circle fs-2 ms-2 text-white"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <span v-if="loading" class="indicator-progress d-flex align-items-center">
                        Menyimpan... <span class="spinner-border spinner-border-sm ms-2"></span>
                    </span>
                 </button>
            </div>

          </el-form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import type { FormInstance, FormRules } from 'element-plus'

interface Permission { id: number; name: string; }
interface RoleData { id: number; name: string; full_name: string; permissions?: any[]; }
interface FormData { name: string; full_name: string; permissions: string[]; }

const props = defineProps<{ roleData: RoleData | null }>();
const emit = defineEmits(['role-updated']);

const formRef = ref<FormInstance>();
const modalRef = ref<null | HTMLElement>(null);
const loading = ref(false);
const loadingPermissions = ref(false);
const allPermissions = ref<Permission[]>([]);
const searchPermission = ref(""); 
const isEditMode = computed(() => !!props.roleData);

const getInitialFormData = (): FormData => ({ name: "", full_name: "", permissions: [] });
const formData = ref<FormData>(getInitialFormData());

const fetchPermissions = async () => {
    try {
        loadingPermissions.value = true;
        const { data } = await ApiService.get("/master/permissions");
        allPermissions.value = Array.isArray(data) ? data : (data.data || []);
    } catch (e) { 
        console.error("Error permissions", e);
        Swal.fire("Error", "Gagal memuat permission.", "error");
    } finally { 
        loadingPermissions.value = false; 
    }
};

const loadRoleDetails = async (roleId: number) => {
    loadingPermissions.value = true;
    try {
        const { data } = await ApiService.get(`/master/roles/${roleId}`);
        const role = data.data || data;

        // --- PERBAIKAN LOGIC SINKRONISASI EDIT ---
        // Backend mengirim permissions sebagai array of strings: ['view', 'edit']
        // Kode lama mencoba .map() seolah-olah object, sehingga hasilnya undefined/error
        
        if (role.permissions && Array.isArray(role.permissions)) {
            // Cek apakah item didalamnya adalah string (langsung nama permission)
            if (role.permissions.length > 0 && typeof role.permissions[0] === 'string') {
                 formData.value.permissions = role.permissions;
            } 
            // Cek jika item didalamnya object (misal controller berubah dikemudian hari)
            else if (role.permissions.length > 0 && typeof role.permissions[0] === 'object') {
                 formData.value.permissions = role.permissions.map((p: any) => p.name);
            }
            else {
                 formData.value.permissions = [];
            }
        } else {
            formData.value.permissions = [];
        }
        
    } catch (e) {
        console.error("Gagal load detail role", e);
        Swal.fire("Error", "Gagal mengambil detail role", "error");
    } finally {
        loadingPermissions.value = false;
    }
};

// --- Computed & Helpers ---
const filteredPermissions = computed(() => {
    if (!searchPermission.value) return allPermissions.value;
    const q = searchPermission.value.toLowerCase();
    return allPermissions.value.filter(p => p.name.toLowerCase().includes(q) || formatPermissionName(p.name).toLowerCase().includes(q));
});

const isAllSelected = computed(() => {
    return filteredPermissions.value.length > 0 && 
           filteredPermissions.value.every(p => formData.value.permissions.includes(p.name));
});

const toggleAllPermissions = (e: Event) => {
    const checked = (e.target as HTMLInputElement).checked;
    const visiblePermissions = filteredPermissions.value.map(p => p.name);
    
    if (checked) {
        const newSet = new Set([...formData.value.permissions, ...visiblePermissions]);
        formData.value.permissions = Array.from(newSet);
    } else {
        formData.value.permissions = formData.value.permissions.filter(p => !visiblePermissions.includes(p));
    }
};

const formatPermissionName = (name: string) => {
    return name.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

watch(() => props.roleData, async (newVal) => {
  if (newVal) {
    // Set data dasar dulu agar UI responsif
    formData.value = { name: newVal.name, full_name: newVal.full_name, permissions: [] };
    // Load permissions detail untuk mengisi checklist
    await loadRoleDetails(newVal.id);
  } else {
    formRef.value?.resetFields();
    formData.value = getInitialFormData();
  }
});

const rules = ref<FormRules>({
  name: [{ required: true, message: "Wajib diisi", trigger: "blur" }],
  full_name: [{ required: true, message: "Wajib diisi", trigger: "blur" }],
});

const submit = () => {
  if (!formRef.value) return;
  formRef.value.validate(async (valid: boolean) => {
    if (valid) {
      loading.value = true;
      try {
        if (isEditMode.value && props.roleData?.id) {
            await ApiService.put(`/master/roles/${props.roleData.id}`, formData.value);
        } else {
            await ApiService.post("/master/roles", formData.value);
        }
        Swal.fire({ text: `Berhasil disimpan!`, icon: "success", timer: 1500, showConfirmButton: false }).then(() => {
            if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
            emit('role-updated');
        });
      } catch (error: any) {
        Swal.fire({ text: error.response?.data?.message || "Gagal menyimpan.", icon: "error" });
      } finally {
        loading.value = false;
      }
    }
  });
};

onMounted(() => { fetchPermissions(); });
</script>

<style scoped>
/* ========================
   THEME COLORS
   ======================== */
.text-orange { color: #F68B1E !important; }
.bg-light-orange { background-color: #FFF8F1 !important; }
.border-orange { border-color: #F68B1E !important; }
.btn-orange { background-color: #F68B1E; color: white; border: none; }
.btn-orange:hover { background-color: #d97814; color: white; }

/* ========================
   PERMISSION CARD GRID
   ======================== */
.permission-box {
    max-height: 350px;
    background-color: #ffffff;
}

.permission-item {
    transition: all 0.2s ease;
}
.permission-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border-color: #F68B1E !important;
}
.permission-item.active {
    background-color: #FFF4E6 !important;
    border-color: #F68B1E !important;
}

/* Custom Checkbox Color */
.form-check.form-check-solid .form-check-input:checked {
    background-color: #F68B1E;
    border-color: #F68B1E;
}

/* ========================
   INPUTS STYLE
   ======================== */
.required:after { content: "*"; color: #f1416c; margin-left: 3px; }

:deep(.metronic-input .el-input__wrapper) {
    background-color: #F9F9F9;
    box-shadow: none !important; 
    border: 1px solid transparent; 
    border-radius: 0.6rem; 
    padding: 8px 12px;
    height: 40px;
    transition: all 0.2s;
}

:deep(.metronic-input .el-input__wrapper.is-focus) {
    background-color: #ffffff;
    border-color: #F68B1E !important;
    box-shadow: 0 0 0 3px rgba(246, 139, 30, 0.1) !important;
}

/* ========================
   DARK MODE
   ======================== */
[data-bs-theme="dark"] .theme-modal { background-color: #1e1e2d; color: #ffffff; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-800 { color: #e1e3ea !important; }
[data-bs-theme="dark"] .text-gray-600 { color: #CDCDDE !important; }
[data-bs-theme="dark"] .text-gray-500 { color: #9A9CAE !important; }
[data-bs-theme="dark"] .bg-light-subtle { background-color: #1b1b29 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .bg-body { background-color: #151521 !important; }
[data-bs-theme="dark"] .border-gray-300, [data-bs-theme="dark"] .border-gray-200 { border-color: #323248 !important; }

/* Permission Dark */
[data-bs-theme="dark"] .permission-box { background-color: #1b1b29; border-color: #323248 !important; }
[data-bs-theme="dark"] .permission-item { background-color: #1e1e2d !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .permission-item:hover { border-color: #F68B1E !important; background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .permission-item.active { background-color: rgba(246, 139, 30, 0.15) !important; border-color: #F68B1E !important; }
[data-bs-theme="dark"] .bg-light-orange { background-color: #2b2b40 !important; }

/* Input Dark */
[data-bs-theme="dark"] :deep(.metronic-input .el-input__wrapper) {
    background-color: #1b1b29 !important; 
    color: #ffffff;
    border-color: #323248 !important;
}
[data-bs-theme="dark"] :deep(.metronic-input .el-input__wrapper.is-focus) {
    border-color: #F68B1E !important;
    background-color: #151521 !important;
}
[data-bs-theme="dark"] :deep(.el-input__inner) { color: #ffffff; }
[data-bs-theme="dark"] .form-control-solid { background-color: #1b1b29 !important; border-color: #323248 !important; color: #ffffff; }
</style>