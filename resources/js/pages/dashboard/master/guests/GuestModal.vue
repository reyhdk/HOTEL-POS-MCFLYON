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
            
            <div class="mb-3 mt-2 text-center">
                <div class="symbol symbol-60px symbol-circle bg-light-orange mb-3">
                    <div class="symbol-label text-orange fs-2 fw-bold">
                        {{ getInitials(formData.name) }}
                    </div>
                </div>
                <div class="fs-7 text-muted" v-if="!formData.name">Nama tamu akan muncul di sini</div>
            </div>

            <el-form-item prop="name" class="mb-4">
                <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase ls-1">Nama Lengkap</span></template>
                <el-input v-model="formData.name" placeholder="Nama sesuai KTP..." class="metronic-input fw-bold fs-6">
                    <template #prefix><i class="ki-duotone ki-user fs-3 text-gray-500 me-1"><span class="path1"></span><span class="path2"></span></i></template>
                </el-input>
            </el-form-item>

            <div class="row g-4 mb-1">
                <div class="col-6">
                    <el-form-item prop="email" class="mb-3">
                        <template #label><span class="fw-bold fs-8 text-gray-600 text-uppercase">Email</span></template>
                        <el-input v-model="formData.email" placeholder="contoh@email.com" class="metronic-input" />
                    </el-form-item>
                </div>
                <div class="col-6">
                    <el-form-item prop="phone_number" class="mb-3">
                        <template #label><span class="fw-bold fs-8 text-gray-600 text-uppercase">No. Telepon</span></template>
                        <el-input v-model="formData.phone_number" placeholder="0812..." class="metronic-input" />
                    </el-form-item>
                </div>
            </div>

            <el-form-item prop="address" class="mb-0">
                 <template #label><span class="fw-bold fs-8 text-gray-600 text-uppercase">Alamat</span></template>
                 <el-input v-model="formData.address" type="textarea" :rows="3" resize="none" placeholder="Alamat lengkap..." class="metronic-input"/>
            </el-form-item>

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
import { ref, watch, computed } from "vue";
import axios from "@/libs/axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import type { FormInstance, FormRules } from 'element-plus'

const props = defineProps<{ guestData: any }>();
const emit = defineEmits(['guest-updated']);

const formRef = ref<FormInstance>();
const modalRef = ref<null | HTMLElement>(null);
const loading = ref(false);
const isEditMode = computed(() => !!props.guestData);

const getInitialFormData = () => ({ id: null, name: "", email: "", phone_number: "", address: "" });
const formData = ref(getInitialFormData());

const getInitials = (name: string) => {
    if (!name) return "?";
    const parts = name.split(" ");
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
};

watch(() => props.guestData, (newVal) => {
  if (newVal) {
    formData.value = { ...newVal };
  } else {
    formRef.value?.resetFields();
    formData.value = getInitialFormData();
  }
});

const rules = ref<FormRules>({
  name: [{ required: true, message: "Nama wajib diisi", trigger: "blur" }],
  email: [{ type: 'email', message: 'Format email tidak valid', trigger: ['blur', 'change'] }]
});

const submit = () => {
  if (!formRef.value) return;
  formRef.value.validate(async (valid: boolean) => {
    if (valid) {
      loading.value = true;
      try {
        if (isEditMode.value) {
          await axios.put(`/guests/${formData.value.id}`, formData.value);
        } else {
          await axios.post("/guests", formData.value);
        }
        
        Swal.fire({ text: `Data tamu berhasil ${isEditMode.value ? 'diperbarui' : 'ditambahkan'}!`, icon: "success", timer: 1500, showConfirmButton: false })
            .then(() => {
                if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
                emit('guest-updated');
            });

      } catch (error: any) {
        let msg = "Terjadi kesalahan.";
        if (error.response?.data?.errors) msg = Object.values(error.response.data.errors).flat().join('<br>');
        Swal.fire({ html: msg, icon: "error" });
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
   INPUTS STYLE (MATCHING)
   ======================== */
.required:after { content: "*"; color: #f1416c; margin-left: 3px; }

:deep(.metronic-input .el-input__wrapper), 
:deep(.metronic-input .el-textarea__inner) {
    background-color: #F9F9F9;
    box-shadow: none !important; 
    border: 1px solid transparent; 
    border-radius: 0.6rem; 
    padding: 8px 12px;
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
</style>