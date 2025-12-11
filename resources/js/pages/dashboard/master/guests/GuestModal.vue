<template>
  <div class="modal fade" id="kt_modal_guest" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-550px">
      <div class="modal-content rounded-3 shadow-lg border-0 theme-modal">
        
        <div class="modal-header border-0 pb-0 justify-content-between align-items-center py-4 px-5">
            <h2 class="fw-bold m-0 fs-3 text-gray-900">{{ isEditMode ? 'Edit Data Tamu' : 'Registrasi Tamu' }}</h2>
            <div class="btn btn-sm btn-icon btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
                <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>

        <div class="modal-body scroll-y px-5 pb-5 pt-2">
          <el-form @submit.prevent="submit" :model="formData" :rules="rules" ref="formRef" label-position="top" class="compact-form">
            
            <div class="mb-4 mt-2 text-center">
                <div class="symbol symbol-60px symbol-circle bg-light-orange mb-3">
                    <div class="symbol-label text-orange fs-2 fw-bold">
                        {{ getInitials(formData.name) }}
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <el-form-item label="Nama Lengkap" prop="name">
                        <el-input v-model="formData.name" placeholder="Nama sesuai KTP" class="metronic-input" />
                    </el-form-item>
                </div>
                <div class="col-6">
                    <el-form-item label="No. HP / WhatsApp" prop="phone_number">
                        <el-input v-model="formData.phone_number" placeholder="08..." class="metronic-input" />
                    </el-form-item>
                </div>
                <div class="col-6">
                    <el-form-item label="Email (Opsional)" prop="email">
                        <el-input v-model="formData.email" placeholder="nama@email.com" class="metronic-input" />
                    </el-form-item>
                </div>
                <div class="col-12">
                    <el-form-item label="Alamat" prop="address">
                        <el-input v-model="formData.address" type="textarea" rows="2" placeholder="Alamat domisili..." class="metronic-input" />
                    </el-form-item>
                </div>
            </div>

            <div class="separator separator-dashed my-4"></div>

            <div class="bg-light-danger rounded border border-danger border-dashed p-4 mb-4">
                <div class="d-flex flex-stack">
                    <div class="d-flex align-items-center me-2">
                        <i class="ki-duotone ki-shield-cross fs-2x text-danger me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        <div>
                            <span class="fw-bold text-gray-800 fs-6 d-block">Blacklist Tamu Ini?</span>
                            <span class="text-gray-500 fs-8">Tamu tidak akan bisa Check-in jika diaktifkan.</span>
                        </div>
                    </div>
                    <div class="form-check form-switch form-check-custom form-check-solid form-check-danger">
                        <input class="form-check-input h-25px w-45px" type="checkbox" v-model="formData.is_blacklisted" :true-value="true" :false-value="false" />
                    </div>
                </div>

                <div v-if="formData.is_blacklisted" class="mt-3 animate__animated animate__fadeIn">
                    <label class="required fs-8 fw-bold text-danger mb-1">Alasan Blacklist</label>
                    <el-input 
                        v-model="formData.blacklist_reason" 
                        type="textarea" 
                        rows="2" 
                        placeholder="Contoh: Merusak TV kamar 101, Kabur tanpa bayar..." 
                        class="metronic-input border-danger"
                    />
                </div>
            </div>

            <div class="d-flex gap-3 pt-3 border-top border-gray-100">
                <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-orange w-100" :disabled="loading">
                    <span v-if="!loading">{{ isEditMode ? 'Simpan Perubahan' : 'Registrasi' }}</span>
                    <span v-else>Menyimpan...</span>
                </button>
            </div>

          </el-form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, nextTick } from 'vue';
import { Modal } from 'bootstrap';
import ApiService from '@/core/services/ApiService';
import Swal from 'sweetalert2';
import type { FormInstance, FormRules } from 'element-plus';

const props = defineProps<{
    isEditMode: boolean,
    guestData: any
}>();

const emit = defineEmits(['saved']);

// Refs
const modalRef = ref<HTMLElement | null>(null);
const formRef = ref<FormInstance>();
const modalInstance = ref<Modal | null>(null);
const loading = ref(false);

// Form Data dengan tambahan Blacklist
const formData = reactive({
    id: null,
    name: '',
    email: '',
    phone_number: '',
    address: '',
    is_blacklisted: false,
    blacklist_reason: ''
});

// Rules
const rules = reactive<FormRules>({
    name: [{ required: true, message: 'Nama wajib diisi', trigger: 'blur' }],
    phone_number: [{ required: true, message: 'No HP wajib diisi', trigger: 'blur' }],
    // Validasi conditional untuk blacklist reason
    blacklist_reason: [
        { 
            validator: (rule, value, callback) => {
                if (formData.is_blacklisted && !value) {
                    callback(new Error('Alasan blacklist wajib diisi'));
                } else {
                    callback();
                }
            }, 
            trigger: 'blur' 
        }
    ]
});

// Helper Initials
const getInitials = (name: string) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

// === FUNGSI UTAMA YANG MEMPERBAIKI MASALAH TOMBOL ===
const open = () => {
    if (modalRef.value) {
        modalInstance.value = new Modal(modalRef.value);
        modalInstance.value.show();
    }
};

const hide = () => {
    if (modalInstance.value) {
        modalInstance.value.hide();
    }
};

// PENTING: Expose fungsi open dan hide agar bisa dipanggil dari Index.vue
defineExpose({
    open,
    hide
});
// ====================================================

// Watcher untuk mengisi data saat Edit Mode
watch(() => props.guestData, (newVal) => {
    if (props.isEditMode && newVal) {
        Object.assign(formData, {
            id: newVal.id,
            name: newVal.name,
            email: newVal.email,
            phone_number: newVal.phone_number,
            address: newVal.address,
            // Mapping data blacklist (pastikan backend mengirim field ini)
            is_blacklisted: Boolean(newVal.is_blacklisted),
            blacklist_reason: newVal.blacklist_reason || ''
        });
    } else {
        // Reset form untuk mode tambah baru
        Object.assign(formData, {
            id: null, name: '', email: '', phone_number: '', address: '', 
            is_blacklisted: false, blacklist_reason: ''
        });
        if(formRef.value) formRef.value.resetFields();
    }
});

const submit = async () => {
    if (!formRef.value) return;
    await formRef.value.validate(async (valid) => {
        if (valid) {
            loading.value = true;
            try {
                if (props.isEditMode && formData.id) {
                    await ApiService.put(`/guests/${formData.id}`, formData);
                } else {
                    await ApiService.post('/guests', formData);
                }
                
                Swal.fire({
                    text: "Data berhasil disimpan!",
                    icon: "success",
                    buttonsStyling: false,
                    confirmButtonText: "Ok, Siap!",
                    customClass: { confirmButton: "btn btn-primary" }
                });
                
                emit('saved');
                hide();
            } catch (error: any) {
                Swal.fire({
                    text: error.response?.data?.message || "Terjadi kesalahan.",
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
</script>

<style scoped>
.text-orange { color: #F68B1E !important; }
.bg-light-orange { background-color: #FFF8F1 !important; }
.btn-orange { background-color: #F68B1E; color: white; border: none; }
.btn-orange:hover { background-color: #d97814; }

/* Input Styles */
:deep(.metronic-input .el-input__wrapper),
:deep(.metronic-input .el-textarea__inner) {
    background-color: #F9F9F9;
    box-shadow: none !important;
    border: 1px solid transparent;
    border-radius: 0.6rem;
    padding: 8px 12px;
}

:deep(.metronic-input .el-input__wrapper.is-focus),
:deep(.metronic-input .el-textarea__inner:focus) {
    background-color: #ffffff;
    border-color: #F68B1E !important;
    box-shadow: 0 0 0 3px rgba(246, 139, 30, 0.1) !important;
}

/* Red Border untuk alasan blacklist */
:deep(.metronic-input.border-danger .el-textarea__inner) {
    border-color: #f1416c !important;
    background-color: #fff5f8 !important;
}
:deep(.metronic-input.border-danger .el-textarea__inner:focus) {
    box-shadow: 0 0 0 3px rgba(241, 65, 108, 0.1) !important;
}

/* Dark Mode */
[data-bs-theme="dark"] .theme-modal { background-color: #1e1e2d; color: white; }
[data-bs-theme="dark"] :deep(.metronic-input .el-input__wrapper),
[data-bs-theme="dark"] :deep(.metronic-input .el-textarea__inner) {
    background-color: #1b1b29; color: white; border-color: #323248;
}
</style>