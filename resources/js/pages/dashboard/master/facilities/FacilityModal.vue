<template>
  <div class="modal fade" id="kt_modal_facility" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-550px">
      <div class="modal-content rounded-3 shadow-lg border-0 theme-modal">
        
        <div class="modal-header border-0 pb-0 justify-content-between align-items-center py-4 px-5">
            <h2 class="fw-bold m-0 fs-3 text-gray-900">{{ isEditMode ? 'Edit Fasilitas' : 'Tambah Fasilitas' }}</h2>
            <div class="btn btn-sm btn-icon btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
                <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>

        <div class="modal-body scroll-y px-5 pb-5 pt-2">
          <el-form @submit.prevent="submit" :model="formData" :rules="rules" ref="formRef" label-position="top" class="compact-form">
            
            <div class="mb-5 mt-2 d-flex justify-content-center">
                 <div class="image-upload-circle position-relative rounded-circle border border-dashed border-gray-300 bg-light-subtle d-flex align-items-center justify-content-center overflow-hidden transition-300"
                      :style="{ backgroundImage: `url(${iconPreview})` }">
                    
                    <div v-if="!iconPreview" class="text-center p-3">
                        <i class="ki-duotone ki-picture fs-2x text-gray-400 mb-1"><span class="path1"></span><span class="path2"></span></i>
                        <div class="fs-9 fw-bold text-gray-500">Upload Icon</div>
                    </div>

                    <div class="hover-overlay position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 backdrop-blur gap-2">
                        <label class="btn btn-sm btn-icon btn-orange rounded-circle shadow-sm hover-scale cursor-pointer" title="Ganti Icon">
                            <i class="ki-duotone ki-pencil fs-3 text-white"><span class="path1"></span><span class="path2"></span></i>
                            <input type="file" accept=".svg, .png, .jpg, .jpeg" @change="handleIconChange" class="d-none" />
                        </label>
                        <button v-if="iconPreview" @click="removeIcon" type="button" class="btn btn-sm btn-icon btn-danger rounded-circle shadow-sm hover-scale" title="Hapus Icon">
                            <i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                        </button>
                    </div>
                 </div>
            </div>

            <el-form-item prop="name" class="mb-4">
                <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase ls-1">Nama Fasilitas</span></template>
                <el-input v-model="formData.name" placeholder="Contoh: Wi-Fi Super Cepat" class="metronic-input fw-bold fs-6">
                    <template #prefix><i class="ki-duotone ki-tag fs-3 text-gray-500 me-1"><span class="path1"></span><span class="path2"></span></i></template>
                </el-input>
            </el-form-item>

            <el-form-item prop="description" class="mb-0">
                 <template #label><span class="fw-bold fs-8 text-gray-600 text-uppercase ls-1">Deskripsi</span></template>
                 <el-input v-model="formData.description" type="textarea" :rows="3" resize="none" placeholder="Jelaskan detail fasilitas ini..." class="metronic-input"/>
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

// --- Interfaces ---
interface FacilityData { id: number; name: string; icon: string | null; description: string | null; icon_url: string | null; }
interface FormData { id: number | null; name: string; description: string | null; icon: File | null; }

const props = defineProps<{ facilityData: FacilityData | null }>();
const emit = defineEmits(['facility-updated']);

// --- Refs ---
const formRef = ref<FormInstance>();
const modalRef = ref<null | HTMLElement>(null);
const loading = ref(false);
const iconPreview = ref<string | null>(null);
const isEditMode = computed(() => !!props.facilityData);

const getInitialFormData = (): FormData => ({ id: null, name: "", description: null, icon: null });
const formData = ref<FormData>(getInitialFormData());

const rules = ref<FormRules>({
  name: [{ required: true, message: "Nama fasilitas wajib diisi", trigger: "blur" }],
});

// --- Watchers ---
watch(() => props.facilityData, (newVal) => {
  if (newVal) {
    formData.value = { id: newVal.id, name: newVal.name, description: newVal.description, icon: null };
    iconPreview.value = newVal.icon_url;
  } else {
    formRef.value?.resetFields();
    formData.value = getInitialFormData();
    iconPreview.value = null;
  }
});

// --- Handlers ---
const handleIconChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    if (file.size > 1024 * 1024) { // 1MB Max
        Swal.fire({ text: "Ukuran ikon maksimal 1MB.", icon: "warning" });
        return;
    }
    formData.value.icon = file;
    const reader = new FileReader();
    reader.onload = (e) => { iconPreview.value = e.target?.result as string; };
    reader.readAsDataURL(file);
  }
};

const removeIcon = () => { iconPreview.value = null; formData.value.icon = null; };

const submit = () => {
  if (!formRef.value) return;
  formRef.value.validate(async (valid: boolean) => {
    if (valid) {
      loading.value = true;
      const data = new FormData();
      data.append('name', formData.value.name);
      if (formData.value.description) data.append('description', formData.value.description);
      if (formData.value.icon) data.append('icon', formData.value.icon);

      try {
        if (isEditMode.value && formData.value.id) {
          data.append('_method', 'PUT');
          await axios.post(`/facilities/${formData.value.id}`, data);
        } else {
          await axios.post("/facilities", data);
        }
        
        Swal.fire({ text: `Fasilitas berhasil ${isEditMode.value ? 'diperbarui' : 'ditambahkan'}!`, icon: "success", timer: 1500, showConfirmButton: false })
            .then(() => {
                if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
                emit('facility-updated');
            });

      } catch (error) {
        Swal.fire({ text: "Gagal menyimpan data.", icon: "error" });
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
.btn-orange { background-color: #F68B1E; color: white; border: none; }
.btn-orange:hover { background-color: #d97814; color: white; }

/* ========================
   ICON UPLOAD (CIRCLE)
   ======================== */
.image-upload-circle {
    width: 100px; height: 100px; /* Circular */
    background-size: cover; background-position: center;
    transition: all 0.3s ease;
}
.image-upload-circle:hover { border-color: #F68B1E !important; }

.hover-overlay { opacity: 0; transition: opacity 0.2s ease; }
.image-upload-circle:hover .hover-overlay { opacity: 1; }
.backdrop-blur { backdrop-filter: blur(2px); }

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
.hover-scale:hover { transform: scale(1.1); transition: all 0.2s; }

/* ========================
   DARK MODE
   ======================== */
[data-bs-theme="dark"] .theme-modal { background-color: #1e1e2d; color: #ffffff; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-600 { color: #CDCDDE !important; }
[data-bs-theme="dark"] .bg-light-subtle { background-color: #1b1b29 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .border-gray-300 { border-color: #323248 !important; }

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