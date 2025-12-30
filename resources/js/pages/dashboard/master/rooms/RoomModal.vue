<template>
  <div class="modal fade" id="kt_modal_room" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-550px">
      <div class="modal-content rounded-3 shadow-lg border-0 theme-modal">
        
        <div class="modal-header border-0 pb-0 justify-content-between align-items-center py-3 px-4">
            <h2 class="fw-bold m-0 fs-4 text-gray-900">{{ isEditMode ? 'Edit Kamar' : 'Tambah Kamar' }}</h2>
            <div class="btn btn-sm btn-icon btn-active-light-primary rounded-circle" data-bs-dismiss="modal">
                <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>

        <div class="modal-body scroll-y px-4 pb-4 pt-0">
          <el-form @submit.prevent="submit" :model="formData" :rules="rules" ref="formRef" label-position="top" class="compact-form">
            
            <div class="mb-4 mt-2">
                 <div class="image-upload-box d-flex align-items-center justify-content-center position-relative rounded-2 overflow-hidden border border-dashed border-gray-300 bg-light-subtle transition-300"
                      :class="{ 'has-image': imagePreview }"
                      :style="{ backgroundImage: imagePreview ? `url(${imagePreview})` : 'none' }">
                    
                    <div v-if="!imagePreview" class="text-center p-3">
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                            <i class="ki-duotone ki-picture fs-3 text-primary"><span class="path1"></span><span class="path2"></span></i>
                            <span class="fs-7 fw-bold text-gray-600">Upload Foto</span>
                        </div>
                        <div class="fs-9 text-muted">Max 4MB (JPG/PNG)</div>
                    </div>

                    <div class="hover-overlay position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 backdrop-blur">
                        <label class="btn btn-sm btn-icon btn-orange rounded-circle shadow-sm me-2 hover-scale cursor-pointer" title="Ganti Foto">
                            <i class="ki-duotone ki-pencil fs-4 text-white"><span class="path1"></span><span class="path2"></span></i>
                            <input type="file" accept=".png, .jpg, .jpeg" @change="handleImageChange" class="d-none" />
                        </label>
                        <button v-if="imagePreview" @click="removeImage" type="button" class="btn btn-sm btn-icon btn-danger rounded-circle shadow-sm hover-scale" title="Hapus Foto">
                            <i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                        </button>
                    </div>
                 </div>
            </div>

            <div class="row g-3 mb-1">
                <div class="col-6">
                    <el-form-item prop="room_number" class="mb-3">
                        <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase">Nomor Kamar</span></template>
                        <el-input v-model="formData.room_number" placeholder="Cth: 101" class="metronic-input fw-bold" />
                    </el-form-item>
                </div>
                <div class="col-6">
                    <el-form-item prop="price_per_night" class="mb-3">
                        <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase">Harga / Malam</span></template>
                        <el-input v-model="formData.price_per_night" type="number" placeholder="0" class="metronic-input text-end fw-bold">
                            <template #prefix><span class="text-orange fw-bold fs-8 mt-1">Rp</span></template>
                        </el-input>
                    </el-form-item>
                </div>
            </div>

            <div class="row g-3 mb-1">
                <div class="col-6">
                     <el-form-item prop="type" class="mb-3">
                        <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase">Tipe</span></template>
                        <el-select v-model="formData.type" placeholder="Pilih..." class="w-100 metronic-input">
                            <el-option value="Standard" label="Standard Room" />
                            <el-option value="Deluxe" label="Deluxe Room" />
                            <el-option value="Suite" label="Executive Suite" />
                        </el-select>
                    </el-form-item>
                </div>
                <div class="col-6">
                    <el-form-item prop="status" class="mb-3">
                        <template #label><span class="required fw-bold fs-8 text-gray-600 text-uppercase">Status</span></template>
                        <el-select v-model="formData.status" placeholder="Pilih..." class="w-100 metronic-input">
                             <el-option value="available" label="Tersedia" />
                             <el-option value="occupied" label="Terisi" />
                             <el-option value="maintenance" label="Perbaikan" />
                             <el-option value="dirty" label="Kotor" />
                        </el-select>
                    </el-form-item>
                </div>
            </div>

            <div class="bg-light-subtle rounded-2 p-3 mb-3 border border-dashed border-gray-300">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="fw-bold fs-8 text-gray-700 text-uppercase mb-0">Periode Ketersediaan</label>
                    <span class="badge badge-light-warning fs-9">Opsional</span>
                </div>
                <div class="d-flex gap-2">
                    <el-form-item prop="tersedia_mulai" class="mb-0 w-50">
                        <el-date-picker v-model="formData.tersedia_mulai" type="date" placeholder="Mulai" class="w-100 metronic-date" value-format="YYYY-MM-DD" size="small" />
                    </el-form-item>
                    <span class="align-self-center text-gray-400 fw-bold fs-9">-</span>
                    <el-form-item prop="tersedia_sampai" class="mb-0 w-50">
                        <el-date-picker v-model="formData.tersedia_sampai" type="date" placeholder="Selesai" class="w-100 metronic-date" value-format="YYYY-MM-DD" size="small" />
                    </el-form-item>
                </div>
                <div class="form-text fs-9 mt-1 text-muted fst-italic">*Kosongkan jika tersedia selamanya.</div>
            </div>

            <el-form-item prop="facility_ids" class="mb-3">
                <template #label><span class="fw-bold fs-8 text-gray-600 text-uppercase">Fasilitas</span></template>
                <el-select v-model="formData.facility_ids" multiple collapse-tags collapse-tags-tooltip placeholder="Pilih fasilitas..." class="w-100 metronic-input" :loading="loadingFacilities">
                    <el-option v-for="facility in allFacilities" :key="facility.id" :label="facility.name" :value="facility.id" />
                </el-select>
            </el-form-item>

            <el-form-item prop="description" class="mb-0">
                 <template #label><span class="fw-bold fs-8 text-gray-600 text-uppercase">Deskripsi</span></template>
                 <el-input v-model="formData.description" type="textarea" :rows="2" resize="none" placeholder="Catatan singkat..." class="metronic-input"/>
            </el-form-item>

            <div class="d-flex justify-content-end align-items-center mt-5 pt-3 border-top border-gray-200">
                 <button type="button" class="btn btn-sm btn-light me-2 fw-bold text-gray-700 px-4" data-bs-dismiss="modal">Batal</button>
                 <button :disabled="loading" class="btn btn-sm btn-orange fw-bold px-5 shadow-sm hover-elevate" type="submit">
                    <span v-if="!loading">Simpan</span>
                    <span v-if="loading" class="indicator-progress d-flex align-items-center">
                        <span class="spinner-border spinner-border-sm me-2"></span> Simpan...
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

// --- Interfaces ---
interface Facility { id: number; name: string; }
// Sesuaikan dengan data yang dikembalikan API
interface RoomData { 
    id: number; 
    room_number: string; 
    type: string; 
    status: string; 
    price_per_night: number; 
    description: string | null; 
    image: string | null; // API biasanya mengembalikan field 'image' berisi path
    image_url?: string | null; // Optional jika pakai accessor
    tersedia_mulai: string | null; 
    tersedia_sampai: string | null; 
    facilities: Facility[]; 
}
interface FormData { 
    id: number | null; 
    room_number: string; 
    type: string; 
    status: string; 
    price_per_night: number | string; 
    description: string | null; 
    image: File | null; 
    tersedia_mulai: string | null; 
    tersedia_sampai: string | null; 
    facility_ids: number[]; 
}

const props = defineProps<{ roomData: RoomData | null }>();
const emit = defineEmits(['room-updated', 'close-modal']);

// --- Refs ---
const formRef = ref<FormInstance>();
const modalRef = ref<null | HTMLElement>(null);
const loading = ref(false);
const imagePreview = ref<string | null>(null);
const allFacilities = ref<Facility[]>([]);
const loadingFacilities = ref(false);

const isEditMode = computed(() => !!props.roomData);

const getInitialFormData = (): FormData => ({ 
    id: null, 
    room_number: "", 
    type: "", 
    status: "available", 
    price_per_night: "", 
    description: null, 
    image: null, 
    tersedia_mulai: null, 
    tersedia_sampai: null, 
    facility_ids: [], 
});

const formData = ref<FormData>(getInitialFormData());

// --- Helper: Get Full Image URL ---
const getStorageUrl = (path: string | null) => {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    // Asumsi folder storage sudah dilink ke public/storage
    return `/storage/${path}`;
};

// --- API Facilities ---
const getFacilities = async () => { 
    try { 
        loadingFacilities.value = true; 
        const { data } = await ApiService.get("/facilities"); 
        allFacilities.value = data; 
    } catch (e) { 
        console.error("Gagal load fasilitas", e); 
    } finally { 
        loadingFacilities.value = false; 
    } 
};
onMounted(getFacilities);

const formatDate = (dateString: string | null) => dateString ? new Date(dateString).toISOString().split('T')[0] : null;

// --- Watcher untuk Mode Edit/Tambah ---
watch(() => props.roomData, (newVal) => {
  if (newVal) {
    // Mode Edit
    formData.value = { 
        ...getInitialFormData(), // Reset structure first
        id: newVal.id,
        room_number: newVal.room_number,
        type: newVal.type,
        status: newVal.status,
        price_per_night: newVal.price_per_night,
        description: newVal.description,
        image: null, // File upload tetap null saat init
        facility_ids: newVal.facilities ? newVal.facilities.map(f => f.id) : [], 
        tersedia_mulai: formatDate(newVal.tersedia_mulai), 
        tersedia_sampai: formatDate(newVal.tersedia_sampai) 
    };
    
    // Logic pintar untuk preview gambar dari backend
    const imagePath = newVal.image_url || newVal.image;
    imagePreview.value = getStorageUrl(imagePath);

  } else {
    // Mode Tambah (Reset)
    formRef.value?.resetFields(); 
    formData.value = getInitialFormData(); 
    imagePreview.value = null;
  }
});

const handleImageChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    // Validasi size di frontend (4MB)
    if (file.size > 4 * 1024 * 1024) {
        Swal.fire({ text: "Ukuran file terlalu besar! Max 4MB.", icon: "warning" });
        return;
    }
    formData.value.image = file;
    const reader = new FileReader(); 
    reader.onload = (e) => { imagePreview.value = e.target?.result as string; }; 
    reader.readAsDataURL(file);
  }
};

const removeImage = () => { 
    imagePreview.value = null; 
    formData.value.image = null; 
    // Jika perlu, bisa tambahkan flag untuk menghapus gambar di backend (opsional)
};

const rules = ref<FormRules>({
  room_number: [{ required: true, message: "Nomor kamar wajib diisi", trigger: "blur" }],
  type: [{ required: true, message: "Tipe kamar wajib dipilih", trigger: "change" }],
  status: [{ required: true, message: "Status wajib dipilih", trigger: "change" }],
  price_per_night: [{ required: true, message: "Harga wajib diisi", trigger: "blur" }],
});

const submit = () => {
  if (!formRef.value) return;
  
  formRef.value.validate(async (valid: boolean) => {
    if (valid) {
      loading.value = true;
      
      const data = new FormData();
      
      // Append Data
      data.append('room_number', formData.value.room_number);
      data.append('type', formData.value.type);
      data.append('status', formData.value.status);
      data.append('price_per_night', String(formData.value.price_per_night));
      
      if (formData.value.description) data.append('description', formData.value.description);
      if (formData.value.tersedia_mulai) data.append('tersedia_mulai', formData.value.tersedia_mulai);
      if (formData.value.tersedia_sampai) data.append('tersedia_sampai', formData.value.tersedia_sampai);
      
      // Handle Fasilitas (Array)
      if (formData.value.facility_ids && formData.value.facility_ids.length > 0) {
          formData.value.facility_ids.forEach(id => data.append('facility_ids[]', String(id)));
      }

      // Handle Image
      if (formData.value.image) {
          data.append('image', formData.value.image);
      }

      try {
        if (isEditMode.value) { 
            // Laravel Method Spoofing untuk PUT dengan File
            data.append('_method', 'PUT'); 
            await ApiService.post(`/rooms/${formData.value.id}`, data); 
        } else { 
            await ApiService.post("/rooms", data); 
        }
        
        Swal.fire({ text: "Data kamar berhasil disimpan!", icon: "success", timer: 1500, showConfirmButton: false }).then(() => { 
            if (modalRef.value) Modal.getInstance(modalRef.value)?.hide(); 
            emit("room-updated"); 
        });
      } catch (error: any) { 
        console.error("Error submitting room", error);
        Swal.fire({ 
            text: error.response?.data?.message || "Terjadi kesalahan saat menyimpan data.", 
            icon: "error" 
        }); 
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
   COMPACT IMAGE UPLOAD
   ======================== */
.image-upload-box {
    height: 130px; /* Reduced Height */
    background-size: cover; background-position: center;
    transition: all 0.2s ease;
}
.image-upload-box:hover { border-color: #F68B1E !important; }
.image-upload-box.has-image { border-style: solid; }

.hover-overlay { opacity: 0; transition: opacity 0.2s ease; }
.image-upload-box:hover .hover-overlay { opacity: 1; }
.backdrop-blur { backdrop-filter: blur(2px); }

/* ========================
   COMPACT INPUTS
   ======================== */
.required:after { content: "*"; color: #f1416c; margin-left: 2px; }

/* Menimpa style element plus agar lebih ramping */
:deep(.metronic-input .el-input__wrapper), 
:deep(.metronic-input .el-textarea__inner),
:deep(.metronic-date .el-input__wrapper) {
    background-color: #F9F9F9; /* Match Index */
    box-shadow: none !important; 
    border: 1px solid transparent; 
    border-radius: 0.45rem; 
    padding: 6px 10px; /* Reduced Padding */
    height: 38px; /* Slightly shorter */
    transition: all 0.2s;
}

:deep(.metronic-input .el-textarea__inner) { height: auto; }

/* Focus State */
:deep(.metronic-input .el-input__wrapper.is-focus),
:deep(.metronic-input .el-textarea__inner:focus),
:deep(.metronic-date .el-input__wrapper.is-focus) {
    background-color: #ffffff;
    border-color: #F68B1E !important;
    box-shadow: 0 0 0 2px rgba(246, 139, 30, 0.1) !important;
}

/* Utils */
.hover-elevate:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
.hover-scale:hover { transform: scale(1.05); transition: all 0.2s; }

/* ========================
   DARK MODE
   ======================== */
[data-bs-theme="dark"] .theme-modal { background-color: #1e1e2d; color: #ffffff; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-700, 
[data-bs-theme="dark"] .text-gray-600 { color: #CDCDDE !important; }
[data-bs-theme="dark"] .bg-light-subtle { background-color: #1b1b29 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .border-gray-200, 
[data-bs-theme="dark"] .border-gray-300 { border-color: #323248 !important; }

/* Input Dark */
[data-bs-theme="dark"] :deep(.metronic-input .el-input__wrapper),
[data-bs-theme="dark"] :deep(.metronic-date .el-input__wrapper),
[data-bs-theme="dark"] :deep(.metronic-input .el-textarea__inner) {
    background-color: #1b1b29 !important; 
    color: #ffffff;
    border-color: #323248 !important;
}

[data-bs-theme="dark"] :deep(.metronic-input .el-input__wrapper.is-focus),
[data-bs-theme="dark"] :deep(.metronic-date .el-input__wrapper.is-focus),
[data-bs-theme="dark"] :deep(.metronic-input .el-textarea__inner:focus) {
    border-color: #F68B1E !important;
    background-color: #151521 !important;
}

[data-bs-theme="dark"] :deep(.el-input__inner) { color: #ffffff; }

/* Popups Dark */
[data-bs-theme="dark"] :deep(.el-picker-panel), 
[data-bs-theme="dark"] :deep(.el-select-dropdown) {
    background: #1e1e2d !important; border-color: #323248 !important;
}
[data-bs-theme="dark"] :deep(.el-select-dropdown__item.hover) { 
    background-color: #2b2b40 !important; color: #F68B1E; 
}
[data-bs-theme="dark"] :deep(.el-date-table th) { color: #CDCDDE; }
[data-bs-theme="dark"] :deep(.el-date-table td.available .el-date-table-cell__text) { color: #ffffff; }
</style>