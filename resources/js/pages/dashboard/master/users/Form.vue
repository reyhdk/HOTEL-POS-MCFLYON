<template>
  <div class="modal fade" id="kt_modal_user" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-550px">
      <div class="modal-content rounded shadow-lg border-0">
        
        <!-- Modal Header -->
        <div class="modal-header border-0 pb-0 pt-5 px-7">
          <h2 class="fw-bolder m-0 text-gray-900">
            <i class="ki-duotone ki-user-edit fs-2 text-orange me-2">
              <span class="path1"></span>
              <span class="path2"></span>
              <span class="path3"></span>
            </i>
            {{ isEditMode ? 'Edit User' : 'Tambah User Baru' }}
          </h2>
          <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
            <i class="ki-duotone ki-cross fs-1">
              <span class="path1"></span>
              <span class="path2"></span>
            </i>
          </div>
        </div>

        <!-- Modal Body -->
        <div class="modal-body scroll-y px-7 py-5">
          <el-form 
            @submit.prevent="submit" 
            :model="formData" 
            :rules="rules" 
            ref="formRef" 
            label-position="top"
            size="default"
          >
            
            <!-- Photo Upload - Compact -->
            <div class="text-center mb-6">
              <div class="image-input image-input-outline image-input-circle" data-kt-image-input="true">
                <div 
                  class="image-input-wrapper w-80px h-80px shadow-sm border border-2 border-gray-300"
                  :style="`background-image: url(${photoPreview})`"
                ></div>

                <label 
                  class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-20px h-20px bg-body shadow-sm"
                  data-kt-image-input-action="change"
                  data-bs-toggle="tooltip"
                  title="Ubah foto"
                >
                  <i class="ki-duotone ki-pencil fs-6">
                    <span class="path1"></span>
                    <span class="path2"></span>
                  </i>
                  <input type="file" accept=".png, .jpg, .jpeg" @change="onFileChange" />
                </label>

                <span 
                  v-if="photoPreview && !photoPreview.includes('blank')"
                  class="btn btn-icon btn-circle btn-color-muted btn-active-color-danger w-20px h-20px bg-body shadow-sm"
                  data-kt-image-input-action="remove"
                  data-bs-toggle="tooltip"
                  title="Hapus foto"
                  @click="removePhoto"
                >
                  <i class="ki-duotone ki-cross fs-7">
                    <span class="path1"></span>
                    <span class="path2"></span>
                  </i>
                </span>
              </div>
              <div class="form-text fs-8 text-muted mt-2">Format: PNG, JPG, JPEG (Max 2MB)</div>
            </div>

            <!-- Form Fields -->
            <el-form-item prop="name" label="Nama Lengkap" class="mb-4">
              <el-input 
                v-model="formData.name" 
                placeholder="Contoh: John Doe" 
                class="metronic-input-orange"
                clearable
              >
                <template #prefix>
                  <i class="ki-duotone ki-profile-circle fs-3 text-gray-500">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                  </i>
                </template>
              </el-input>
            </el-form-item>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <el-form-item prop="email" label="Email" class="mb-0">
                  <el-input 
                    v-model="formData.email" 
                    placeholder="email@example.com" 
                    class="metronic-input-orange"
                    clearable
                  >
                    <template #prefix>
                      <i class="ki-duotone ki-sms fs-3 text-gray-500">
                        <span class="path1"></span>
                        <span class="path2"></span>
                      </i>
                    </template>
                  </el-input>
                </el-form-item>
              </div>
              <div class="col-md-6">
                <el-form-item prop="phone" label="No. Telepon" class="mb-0">
                  <el-input 
                    v-model="formData.phone" 
                    placeholder="08123456789" 
                    class="metronic-input-orange"
                    clearable
                  >
                    <template #prefix>
                      <i class="ki-duotone ki-phone fs-3 text-gray-500">
                        <span class="path1"></span>
                        <span class="path2"></span>
                      </i>
                    </template>
                  </el-input>
                </el-form-item>
              </div>
            </div>

            <el-form-item prop="role_name" label="Role / Jabatan" class="mb-4">
              <el-select 
                v-model="formData.role_name" 
                placeholder="Pilih role pengguna" 
                class="w-100 metronic-select-orange"
                clearable
              >
                <template #prefix>
                  <i class="ki-duotone ki-security-user fs-3 text-gray-500">
                    <span class="path1"></span>
                    <span class="path2"></span>
                  </i>
                </template>
                <el-option 
                  v-for="role in roles" 
                  :key="role.id" 
                  :label="role.name.toUpperCase()" 
                  :value="role.name"
                >
                  <span class="badge badge-light-orange fw-bold">{{ role.name.toUpperCase() }}</span>
                </el-option>
              </el-select>
            </el-form-item>

            <!-- Password Section -->
            <div class="separator separator-dashed my-5"></div>
            
            <div class="d-flex align-items-center mb-3">
              <i class="ki-duotone ki-lock fs-2 text-orange me-2">
                <span class="path1"></span>
                <span class="path2"></span>
              </i>
              <h5 class="fw-bold text-gray-800 mb-0">Keamanan Akun</h5>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <el-form-item prop="password" label="Password" class="mb-0">
                  <el-input 
                    v-model="formData.password" 
                    type="password" 
                    placeholder="Minimal 8 karakter" 
                    show-password 
                    class="metronic-input-orange"
                    clearable
                  >
                    <template #prefix>
                      <i class="ki-duotone ki-lock-2 fs-3 text-gray-500">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                      </i>
                    </template>
                  </el-input>
                  <div v-if="isEditMode" class="form-text fs-8 text-muted mt-1">
                    <i class="bi bi-info-circle me-1"></i>Kosongkan jika tidak ingin mengubah
                  </div>
                </el-form-item>
              </div>
              <div class="col-md-6">
                <el-form-item prop="password_confirmation" label="Konfirmasi Password" class="mb-0">
                  <el-input 
                    v-model="formData.password_confirmation" 
                    type="password" 
                    placeholder="Ulangi password" 
                    show-password 
                    class="metronic-input-orange"
                    clearable
                  >
                    <template #prefix>
                      <i class="ki-duotone ki-shield-tick fs-3 text-gray-500">
                        <span class="path1"></span>
                        <span class="path2"></span>
                      </i>
                    </template>
                  </el-input>
                </el-form-item>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-end gap-2 mt-6 pt-3 border-top">
              <button type="button" class="btn btn-light btn-sm fw-bold" data-bs-dismiss="modal">
                <i class="ki-duotone ki-cross fs-3 me-1">
                  <span class="path1"></span>
                  <span class="path2"></span>
                </i>
                Batal
              </button>
              <button type="button" class="btn btn-orange btn-sm fw-bold" @click="submit" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="ki-duotone ki-check fs-3 me-1">
                  <span class="path1"></span>
                  <span class="path2"></span>
                </i>
                {{ isEditMode ? 'Simpan Perubahan' : 'Tambah User' }}
              </button>
            </div>

          </el-form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { Modal } from 'bootstrap';
import ApiService from "@/core/services/ApiService";
import Swal from 'sweetalert2';
import { getAssetPath } from "@/core/helpers/assets";

// Types
interface Role {
  id: number;
  name: string;
}

interface User {
  id: number;
  name: string;
  email: string;
  phone?: string;
  photo?: string;
  roles: Role[];
}

// Emits
const emit = defineEmits(['saved']);

// Refs
const modalRef = ref<HTMLElement | null>(null);
const formRef = ref<any>(null);
const modalInstance = ref<any>(null);
const loading = ref(false);
const isEditMode = ref(false);
const editId = ref<number | null>(null);
const roles = ref<Role[]>([]);

// Photo Setup
const defaultPhoto = getAssetPath("media/avatars/blank.png");
const photoPreview = ref(defaultPhoto);
const photoFile = ref<File | null>(null);

// Form Data
const formData = reactive({
  name: '',
  email: '',
  phone: '',
  role_name: '',
  password: '',
  password_confirmation: ''
});

// Validation Rules
const rules = reactive({
  name: [{ required: true, message: 'Nama wajib diisi', trigger: 'blur' }],
  email: [
    { required: true, message: 'Email wajib diisi', trigger: 'blur' },
    { type: 'email', message: 'Format email tidak valid', trigger: 'blur' }
  ],
  role_name: [{ required: true, message: 'Role wajib dipilih', trigger: 'change' }],
  password: [{ 
    required: false, 
    validator: (rule: any, value: any, callback: any) => {
      if (!isEditMode.value && !value) {
        callback(new Error('Password wajib diisi untuk user baru'));
      } else if (value && value.length < 8) {
        callback(new Error('Password minimal 8 karakter'));
      } else {
        callback();
      }
    }, 
    trigger: 'blur' 
  }],
  password_confirmation: [{
    required: false,
    validator: (rule: any, value: any, callback: any) => {
      if (formData.password && value !== formData.password) {
        callback(new Error('Konfirmasi password tidak cocok'));
      } else {
        callback();
      }
    },
    trigger: 'blur'
  }]
});

onMounted(() => {
  if (modalRef.value) {
    modalInstance.value = new Modal(modalRef.value);
  }
  fetchRoles();
});

const fetchRoles = async () => {
  try {
    const response = await ApiService.get("master/all-roles");
    roles.value = response.data;
  } catch (error) {
    console.error("Gagal memuat roles", error);
  }
};

// Photo Handlers
const onFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    
    // Validasi ukuran file (2MB)
    if (file.size > 2 * 1024 * 1024) {
      Swal.fire({
        text: "Ukuran file maksimal 2MB",
        icon: "warning",
        buttonsStyling: false,
        confirmButtonText: "Ok",
        customClass: { confirmButton: "btn btn-orange" }
      });
      return;
    }
    
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
  }
};

const removePhoto = () => {
  photoFile.value = null;
  photoPreview.value = defaultPhoto;
};

// Open Modal
const open = (user: User | null = null) => {
  resetForm();
  
  if (user) {
    isEditMode.value = true;
    editId.value = user.id;
    
    formData.name = user.name;
    formData.email = user.email;
    formData.phone = user.phone || '';
    formData.role_name = user.roles && user.roles.length > 0 ? user.roles[0].name : '';
    
    photoPreview.value = user.photo || defaultPhoto;
  } else {
    isEditMode.value = false;
    editId.value = null;
    photoPreview.value = defaultPhoto;
  }
  
  if (modalInstance.value) {
    modalInstance.value.show();
  }
};

const resetForm = () => {
  if (formRef.value) {
    formRef.value.resetFields();
  }
  Object.assign(formData, {
    name: '',
    email: '',
    phone: '',
    role_name: '',
    password: '',
    password_confirmation: ''
  });
  photoFile.value = null;
  photoPreview.value = defaultPhoto;
};

const submit = () => {
  if (!formRef.value) return;

  formRef.value.validate(async (valid: boolean) => {
    if (valid) {
      loading.value = true;
      try {
        const data = new FormData();
        data.append('name', formData.name);
        data.append('email', formData.email);
        if (formData.phone) data.append('phone', formData.phone);
        data.append('role_name', formData.role_name);

        if (formData.password) {
          data.append('password', formData.password);
          data.append('password_confirmation', formData.password_confirmation);
        }

        if (photoFile.value) {
          data.append('photo', photoFile.value);
        }
        
        if (isEditMode.value) {
          data.append('_method', 'PUT');
          await ApiService.post(`master/users/${editId.value}`, data);
        } else {
          await ApiService.post("master/users", data);
        }

        Swal.fire({
          text: `User berhasil ${isEditMode.value ? 'diperbarui' : 'ditambahkan'}!`,
          icon: "success",
          buttonsStyling: false,
          confirmButtonText: "Ok",
          customClass: { confirmButton: "btn btn-orange" }
        });

        if (modalInstance.value) {
          modalInstance.value.hide();
        }
        emit('saved');
      } catch (error: any) {
        Swal.fire({
          text: error.response?.data?.message || "Terjadi kesalahan sistem.",
          icon: "error",
          buttonsStyling: false,
          confirmButtonText: "Ok",
          customClass: { confirmButton: "btn btn-danger" }
        });
      } finally {
        loading.value = false;
      }
    }
  });
};

defineExpose({ open });
</script>

<style scoped>
/* ========================
   ORANGE THEME
   ======================== */
.text-orange { 
  color: #F68B1E !important; 
}

.bg-orange { 
  background-color: #F68B1E !important; 
}

.bg-light-orange { 
  background-color: rgba(246, 139, 30, 0.1) !important; 
}

.btn-orange {
  background-color: #F68B1E;
  border-color: #F68B1E;
  color: #fff;
}

.btn-orange:hover:not(:disabled) {
  background-color: #e57b0e;
  border-color: #e57b0e;
  color: #fff;
}

.btn-orange:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.badge-light-orange {
  background-color: rgba(246, 139, 30, 0.1);
  color: #F68B1E;
}

/* ========================
   INPUT STYLING - ORANGE
   ======================== */
.metronic-input-orange :deep(.el-input__wrapper) {
  background-color: var(--bs-body-bg);
  border: 1px solid #e4e6ef;
  box-shadow: none;
  transition: all 0.3s;
}

.metronic-input-orange :deep(.el-input__wrapper:hover) {
  border-color: #F68B1E;
}

.metronic-input-orange :deep(.el-input__wrapper.is-focus) {
  border-color: #F68B1E;
  box-shadow: 0 0 0 0.15rem rgba(246, 139, 30, 0.15);
}

.metronic-select-orange :deep(.el-input__wrapper) {
  background-color: var(--bs-body-bg);
  border: 1px solid #e4e6ef;
  box-shadow: none;
  transition: all 0.3s;
}

.metronic-select-orange :deep(.el-input__wrapper:hover) {
  border-color: #F68B1E;
}

.metronic-select-orange :deep(.el-input__wrapper.is-focus) {
  border-color: #F68B1E;
  box-shadow: 0 0 0 0.15rem rgba(246, 139, 30, 0.15);
}

/* Dark Mode */
[data-bs-theme="dark"] .metronic-input-orange :deep(.el-input__wrapper),
[data-bs-theme="dark"] .metronic-select-orange :deep(.el-input__wrapper) {
  background-color: #1b1b29;
  border-color: #323248;
}

[data-bs-theme="dark"] .metronic-input-orange :deep(.el-input__inner),
[data-bs-theme="dark"] .metronic-select-orange :deep(.el-input__inner) {
  color: #ffffff;
}

/* ========================
   IMAGE INPUT
   ======================== */
.image-input {
  position: relative;
  display: inline-block;
}

.image-input-wrapper {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background-repeat: no-repeat;
  background-size: cover;
  background-position: center;
}

.image-input .btn-icon {
  position: absolute;
  bottom: 0;
  right: 0;
  z-index: 1;
}

.image-input [data-kt-image-input-action="change"] {
  right: 0;
}

.image-input [data-kt-image-input-action="remove"] {
  right: -5px;
  bottom: 25px;
}

.image-input input[type="file"] {
  display: none;
}

/* ========================
   FORM LABEL
   ======================== */
:deep(.el-form-item__label) {
  font-weight: 600;
  color: var(--bs-gray-700);
  font-size: 0.925rem;
  margin-bottom: 0.5rem;
}

[data-bs-theme="dark"] :deep(.el-form-item__label) {
  color: var(--bs-gray-400);
}

/* ========================
   MODAL ENHANCEMENTS
   ======================== */
.modal-content {
  border-radius: 0.75rem;
}

.scroll-y {
  max-height: calc(100vh - 200px);
  overflow-y: auto;
}

/* Custom Scrollbar */
.scroll-y::-webkit-scrollbar {
  width: 6px;
}

.scroll-y::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.scroll-y::-webkit-scrollbar-thumb {
  background: #F68B1E;
  border-radius: 10px;
}

.scroll-y::-webkit-scrollbar-thumb:hover {
  background: #e57b0e;
}

[data-bs-theme="dark"] .scroll-y::-webkit-scrollbar-track {
  background: #1b1b29;
}
</style>