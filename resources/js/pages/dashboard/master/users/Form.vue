<template>
  <div class="modal fade" id="kt_modal_user" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-550px">
      <div class="modal-content rounded-3 shadow-lg border-0 theme-modal">
        
        <div class="modal-header border-0 pb-0 justify-content-between align-items-center py-4 px-5">
            <h2 class="fw-bold m-0 fs-3 text-gray-900">{{ isEditMode ? 'Edit User' : 'Tambah User' }}</h2>
            <div class="btn btn-sm btn-icon btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
                <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>

        <div class="modal-body scroll-y px-5 pb-5 pt-2">
          <el-form @submit.prevent="submit" :model="formData" :rules="rules" ref="formRef" label-position="top" class="compact-form">
            
            <div class="mb-5 text-center mt-2">
                <div class="symbol symbol-60px symbol-circle bg-light-orange mb-3">
                    <div class="symbol-label text-orange fs-2 fw-bold">
                        {{ getInitials(formData.name) }}
                    </div>
                </div>
                <div class="fs-7 text-muted" v-if="!formData.name">Profil Pengguna</div>
            </div>

            <el-form-item prop="name" class="mb-4">
                <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase ls-1">Nama Lengkap</span></template>
                <el-input v-model="formData.name" placeholder="Nama User" class="metronic-input fw-bold fs-6">
                    <template #prefix><i class="ki-duotone ki-user fs-3 text-gray-500 me-1"><span class="path1"></span><span class="path2"></span></i></template>
                </el-input>
            </el-form-item>

            <div class="row g-4 mb-1">
                <div class="col-6">
                    <el-form-item prop="email" class="mb-3">
                        <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase">Email</span></template>
                        <el-input v-model="formData.email" placeholder="email@contoh.com" class="metronic-input" />
                    </el-form-item>
                </div>
                <div class="col-6">
                    <el-form-item prop="phone" class="mb-3">
                        <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase">No. Telepon</span></template>
                        <el-input v-model="formData.phone" placeholder="08..." class="metronic-input" />
                    </el-form-item>
                </div>
            </div>

            <el-form-item prop="role_name" class="mb-4">
                <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase">Role / Hak Akses</span></template>
                <el-select v-model="formData.role_name" placeholder="Pilih Role..." class="w-100 metronic-input">
                    <el-option v-for="role in allRoles" :key="role.id" :label="role.full_name || role.name" :value="role.name">
                        <div class="d-flex align-items-center">
                            <span class="badge badge-light-primary fw-bold fs-9 me-2">{{ role.name }}</span>
                            <span class="text-gray-600 fs-9">{{ role.full_name }}</span>
                        </div>
                    </el-option>
                </el-select>
            </el-form-item>

            <div class="row g-4 mb-1">
                <div class="col-6">
                    <el-form-item prop="password" class="mb-0">
                        <template #label><span class="fw-bold fs-8 text-gray-600 text-uppercase" :class="{ 'required': !isEditMode }">Password</span></template>
                        <el-input v-model="formData.password" type="password" placeholder="******" class="metronic-input" show-password />
                    </el-form-item>
                </div>
                <div class="col-6">
                    <el-form-item prop="password_confirmation" class="mb-0">
                        <template #label><span class="fw-bold fs-8 text-gray-600 text-uppercase" :class="{ 'required': !isEditMode }">Konfirmasi</span></template>
                        <el-input v-model="formData.password_confirmation" type="password" placeholder="******" class="metronic-input" show-password />
                    </el-form-item>
                </div>
            </div>
            <div class="form-text text-muted fs-9 mt-2" v-if="isEditMode">Kosongkan password jika tidak ingin mengubahnya.</div>

            <div class="d-flex justify-content-end align-items-center mt-8 pt-4 border-top border-gray-200">
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
import { ref, watch, computed, onMounted } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import type { FormInstance, FormRules } from 'element-plus'

// Interfaces
interface Role { id: number; name: string; full_name: string; }
interface UserData { uuid: string; name: string; email: string; phone: string; roles: Role[]; }
interface FormData { name: string; email: string; phone: string; role_name: string; password: string; password_confirmation: string; }

const props = defineProps<{ userData: UserData | null }>();
const emit = defineEmits(['user-updated']);

const formRef = ref<FormInstance>();
const modalRef = ref<null | HTMLElement>(null);
const loading = ref(false);
const allRoles = ref<Role[]>([]);
const isEditMode = computed(() => !!props.userData);

const getInitialFormData = (): FormData => ({ name: "", email: "", phone: "", role_name: "", password: "", password_confirmation: "" });
const formData = ref<FormData>(getInitialFormData());

const getInitials = (name: string) => {
    if (!name) return "?";
    const parts = name.split(" ");
    return parts.length > 1 ? (parts[0][0] + parts[1][0]).toUpperCase() : parts[0].substring(0, 2).toUpperCase();
};

// Fetch Roles
const getRoles = async () => {
    try {
        const { data } = await ApiService.get("/master/roles");
        allRoles.value = data.data || data;
    } catch (e) { console.error(e); }
};
onMounted(getRoles);

// Watcher untuk Edit Mode
watch(() => props.userData, (newVal) => {
  if (newVal) {
    formData.value = {
        name: newVal.name,
        email: newVal.email,
        phone: newVal.phone,
        role_name: newVal.roles?.[0]?.name || "",
        password: "",
        password_confirmation: ""
    };
  } else {
    formRef.value?.resetFields();
    formData.value = getInitialFormData();
  }
});

// Validasi Element Plus
const rules = computed<FormRules>(() => ({
  name: [{ required: true, message: "Wajib diisi", trigger: "blur" }],
  email: [{ required: true, message: "Wajib diisi", trigger: "blur" }, { type: 'email', message: 'Email tidak valid', trigger: ['blur', 'change'] }],
  phone: [{ required: true, message: "Wajib diisi", trigger: "blur" }],
  role_name: [{ required: true, message: "Pilih role", trigger: "change" }],
  // Password wajib hanya jika mode tambah
  password: !isEditMode.value ? [{ required: true, message: "Password wajib", trigger: "blur" }, { min: 8, message: "Min 8 karakter", trigger: "blur" }] : [],
  password_confirmation: !isEditMode.value ? [
      { required: true, message: "Konfirmasi password", trigger: "blur" },
      { validator: (rule: any, value: string, callback: any) => {
          if (value !== formData.value.password) callback(new Error("Password tidak sama"));
          else callback();
        }, trigger: "blur" 
      }
  ] : []
}));

const submit = () => {
  if (!formRef.value) return;
  formRef.value.validate(async (valid: boolean) => {
    if (valid) {
      loading.value = true;
      try {
        if (isEditMode.value && props.userData?.uuid) {
            await ApiService.put(`/master/users/${props.userData.uuid}`, formData.value);
        } else {
            await ApiService.post("/master/users", formData.value);
        }
        
        Swal.fire({ text: `User berhasil ${isEditMode.value ? 'diupdate' : 'ditambahkan'}!`, icon: "success", timer: 1500, showConfirmButton: false })
            .then(() => {
                if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
                emit('user-updated');
            });

      } catch (error: any) {
        let msg = "Gagal menyimpan data.";
        if (error.response?.data?.message) msg = error.response.data.message;
        Swal.fire({ text: msg, icon: "error" });
      } finally {
        loading.value = false;
      }
    }
  });
};
</script>

<style scoped>
/* ========================
   THEME COLORS
   ======================== */
.text-orange { color: #F68B1E !important; }
.bg-light-orange { background-color: #FFF8F1 !important; }
.btn-orange { background-color: #F68B1E; color: white; border: none; }
.btn-orange:hover { background-color: #d97814; color: white; }

/* ========================
   INPUTS STYLE
   ======================== */
.required:after { content: "*"; color: #f1416c; margin-left: 3px; }

:deep(.metronic-input .el-input__wrapper), 
:deep(.metronic-input .el-textarea__inner) {
    background-color: #F9F9F9;
    box-shadow: none !important; 
    border: 1px solid transparent; 
    border-radius: 0.6rem; 
    padding: 8px 12px;
    height: 40px;
    transition: all 0.2s;
}

:deep(.metronic-input .el-input__wrapper.is-focus),
:deep(.metronic-input .el-textarea__inner:focus) {
    background-color: #ffffff;
    border-color: #F68B1E !important;
    box-shadow: 0 0 0 3px rgba(246, 139, 30, 0.1) !important;
}

/* Utils */
.hover-elevate:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

/* ========================
   DARK MODE
   ======================== */
[data-bs-theme="dark"] .theme-modal { background-color: #1e1e2d; color: #ffffff; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-600 { color: #CDCDDE !important; }
[data-bs-theme="dark"] .bg-light-orange { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .border-gray-200 { border-color: #323248 !important; }

/* Input Dark */
[data-bs-theme="dark"] :deep(.metronic-input .el-input__wrapper),
[data-bs-theme="dark"] :deep(.metronic-input .el-textarea__inner) {
    background-color: #1b1b29 !important; 
    color: #ffffff;
    border-color: #323248 !important;
}

[data-bs-theme="dark"] :deep(.metronic-input .el-input__wrapper.is-focus),
[data-bs-theme="dark"] :deep(.metronic-input .el-textarea__inner:focus) {
    border-color: #F68B1E !important;
    background-color: #151521 !important;
}
[data-bs-theme="dark"] :deep(.el-input__inner) { color: #ffffff; }

/* Popups Dark */
[data-bs-theme="dark"] :deep(.el-select-dropdown__item.hover) { background-color: #2b2b40 !important; color: #F68B1E; }
[data-bs-theme="dark"] :deep(.el-select-dropdown) { background-color: #1e1e2d; border-color: #323248; }
</style>