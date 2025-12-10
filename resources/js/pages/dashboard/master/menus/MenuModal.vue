<template>
  <div class="modal fade" id="kt_modal_menu" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-550px">
      <div class="modal-content rounded-3 shadow-lg border-0 theme-modal">
        
        <div class="modal-header border-0 pb-0 justify-content-between align-items-center py-4 px-5">
            <h2 class="fw-bold m-0 fs-3 text-gray-900">{{ isEditMode ? 'Edit Menu' : 'Tambah Menu' }}</h2>
            <div class="btn btn-sm btn-icon btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
                <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>

        <div class="modal-body scroll-y px-5 pb-5 pt-2">
          <el-form @submit.prevent="submit" :model="formData" :rules="rules" ref="formRef" label-position="top" class="compact-form">
            
            <div class="mb-5 mt-2">
                 <div class="image-upload-box d-flex align-items-center justify-content-center position-relative rounded-3 overflow-hidden border border-dashed border-gray-300 bg-light-subtle transition-300"
                      :class="{ 'has-image': imagePreview }"
                      :style="{ backgroundImage: `url(${imagePreview})` }">
                    
                    <div v-if="!imagePreview" class="text-center p-3">
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                            <i class="ki-duotone ki-picture fs-2 text-gray-400"><span class="path1"></span><span class="path2"></span></i>
                            <span class="fs-7 fw-bold text-gray-600">Foto Menu</span>
                        </div>
                        <div class="fs-9 text-muted">Max 2MB (JPG/PNG)</div>
                    </div>

                    <div class="hover-overlay position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 backdrop-blur gap-2">
                        <label class="btn btn-sm btn-icon btn-orange rounded-circle shadow-sm hover-scale cursor-pointer" title="Ganti Foto">
                            <i class="ki-duotone ki-pencil fs-4 text-white"><span class="path1"></span><span class="path2"></span></i>
                            <input type="file" accept=".png, .jpg, .jpeg" @change="handleImageChange" class="d-none" />
                        </label>
                        <button v-if="imagePreview" @click="removeImage" type="button" class="btn btn-sm btn-icon btn-danger rounded-circle shadow-sm hover-scale" title="Hapus">
                            <i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                        </button>
                    </div>
                 </div>
            </div>

            <div class="row g-4 mb-1">
                <div class="col-12">
                    <el-form-item prop="name" class="mb-3">
                        <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase ls-1">Nama Menu</span></template>
                        <el-input v-model="formData.name" placeholder="Contoh: Nasi Goreng Spesial" class="metronic-input fw-bold fs-6" />
                    </el-form-item>
                </div>
                <div class="col-12">
                    <el-form-item prop="category" class="mb-3">
                        <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase ls-1">Kategori</span></template>
                        <el-select v-model="formData.category" placeholder="Pilih Kategori..." class="w-100 metronic-input">
                            <el-option value="Makanan" label="Makanan" />
                            <el-option value="Minuman" label="Minuman" />
                            <el-option value="Snack" label="Snack" />
                        </el-select>
                    </el-form-item>
                </div>
            </div>

            <div class="row g-4 mb-1">
                <div class="col-6">
                    <el-form-item prop="price" class="mb-3">
                        <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase ls-1">Harga</span></template>
                        <el-input v-model="formData.price" type="number" placeholder="0" class="metronic-input text-end fw-bold">
                            <template #prefix><span class="text-orange fw-bold fs-8 mt-1">Rp</span></template>
                        </el-input>
                    </el-form-item>
                </div>
                <div class="col-6">
                    <el-form-item prop="stock" class="mb-3">
                        <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase ls-1">Stok Awal</span></template>
                        <el-input v-model="formData.stock" type="number" placeholder="0" class="metronic-input text-center fw-bold" />
                    </el-form-item>
                </div>
            </div>

            <div class="d-flex justify-content-end align-items-center mt-5 pt-3 border-top border-gray-200">
                 <button type="button" class="btn btn-sm btn-light me-3 fw-bold text-gray-700 px-5" data-bs-dismiss="modal">Batal</button>
                 <button :disabled="loading" class="btn btn-orange fw-bold px-6 shadow-sm hover-elevate" type="submit">
                    <span v-if="!loading" class="d-flex align-items-center">
                        Simpan <i class="ki-duotone ki-check-circle fs-2 ms-2 text-white"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <span v-if="loading" class="indicator-progress d-flex align-items-center">
                        <span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...
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
import { ref, computed, watch } from "vue";
import axios from "@/libs/axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import type { FormInstance, FormRules } from 'element-plus'

// --- Interfaces ---
interface MenuData { id: number; name: string; category: string; price: number; stock: number; image_url: string | null; }
interface FormData { id: number | null; name: string; category: string; price: number | string; stock: number | string; image: File | null; }

const props = defineProps<{ menuData: MenuData | null }>();
const emit = defineEmits(['menu-updated']);

// --- Refs ---
const formRef = ref<FormInstance>();
const modalRef = ref<null | HTMLElement>(null);
const loading = ref(false);
const imagePreview = ref<string | null>(null);
const isEditMode = computed(() => !!props.menuData);

const getInitialFormData = (): FormData => ({ id: null, name: "", category: "", price: "", stock: "", image: null });
const formData = ref<FormData>(getInitialFormData());

watch(() => props.menuData, (newVal) => {
  if (newVal) {
    formData.value = { ...newVal, price: String(newVal.price), stock: String(newVal.stock), image: null };
    imagePreview.value = newVal.image_url;
  } else {
    formRef.value?.resetFields();
    formData.value = getInitialFormData();
    imagePreview.value = null;
  }
});

const handleImageChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    if (file.size > 2 * 1024 * 1024) { Swal.fire({text: "Ukuran max 2MB", icon: "warning"}); return; }
    formData.value.image = file;
    const reader = new FileReader();
    reader.onload = (e) => { imagePreview.value = e.target?.result as string; };
    reader.readAsDataURL(file);
  }
};

const removeImage = () => { imagePreview.value = null; formData.value.image = null; };

const rules = ref<FormRules>({
  name: [{ required: true, message: "Wajib diisi", trigger: "blur" }],
  category: [{ required: true, message: "Pilih kategori", trigger: "change" }],
  price: [{ required: true, message: "Wajib diisi", trigger: "blur" }],
  stock: [{ required: true, message: "Wajib diisi", trigger: "blur" }],
});

const submit = () => {
  if (!formRef.value) return;
  formRef.value.validate(async (valid: boolean) => {
    if (valid) {
      loading.value = true;
      const data = new FormData();
      data.append('name', formData.value.name);
      data.append('category', formData.value.category);
      data.append('price', String(formData.value.price));
      data.append('stock', String(formData.value.stock));
      if (formData.value.image) data.append('image', formData.value.image);

      try {
        if (isEditMode.value) {
            data.append('_method', 'PUT');
            await axios.post(`/menus/${formData.value.id}`, data);
        } else {
            await axios.post("/menus", data);
        }
        
        Swal.fire({ text: `Menu berhasil ${isEditMode.value ? 'diperbarui' : 'ditambahkan'}!`, icon: "success", timer: 1500, showConfirmButton: false })
            .then(() => {
                if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
                emit('menu-updated');
            });
      } catch (error: any) {
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
   IMAGE UPLOAD
   ======================== */
.image-upload-box {
    height: 140px; 
    background-size: cover; background-position: center;
    transition: all 0.2s ease;
}
.image-upload-box:hover { border-color: #F68B1E !important; }
.image-upload-box.has-image { border-style: solid; }

.hover-overlay { opacity: 0; transition: opacity 0.2s ease; }
.image-upload-box:hover .hover-overlay { opacity: 1; }
.backdrop-blur { backdrop-filter: blur(2px); }

/* ========================
   INPUTS STYLE
   ======================== */
.required:after { content: "*"; color: #f1416c; margin-left: 2px; }

:deep(.metronic-input .el-input__wrapper), 
:deep(.metronic-input .el-textarea__inner) {
    background-color: #F9F9F9;
    box-shadow: none !important; 
    border: 1px solid transparent; 
    border-radius: 0.5rem; 
    padding: 6px 12px;
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
[data-bs-theme="dark"] .bg-light-subtle { background-color: #1b1b29 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .border-gray-200, 
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

/* Popups Dark */
[data-bs-theme="dark"] :deep(.el-select-dropdown__item.hover) { background-color: #2b2b40 !important; color: #F68B1E; }
[data-bs-theme="dark"] :deep(.el-select-dropdown) { background-color: #1e1e2d; border-color: #323248; }
</style>