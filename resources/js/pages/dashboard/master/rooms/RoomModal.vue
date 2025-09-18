<template>
  <div class="modal fade" id="kt_modal_room" ref="modalRef" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-650px">
      <div class="modal-content rounded">
        <div class="modal-header pb-0 border-0 justify-content-end">
          <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
            <i class="ki-duotone ki-cross fs-1"></i>
          </div>
        </div>

        <div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
          <el-form @submit.prevent="submit" :model="formData" :rules="rules" ref="formRef" class="form">
            <div class="mb-13 text-center">
              <h1 class="mb-3">{{ isEditMode ? 'Edit Kamar' : 'Tambah Kamar Baru' }}</h1>
              <div class="text-muted fw-semibold fs-5">
                Isi detail kamar di bawah ini.
              </div>
            </div>

            <div class="mb-10 text-center">
              <div class="position-relative d-inline-block">
                <div class="image-input-wrapper shadow-sm" :style="{
                  backgroundImage: `url(${imagePreview || '/media/svg/avatars/blank.svg'})`,
                  width: '150px', height: '150px', backgroundSize: 'cover', borderRadius: '1.25rem'
                }">
                </div>
                <label class="btn btn-icon btn-circle btn-color-muted btn-active-color-primary w-35px h-35px bg-body shadow-sm position-absolute top-100 start-100 translate-middle" title="Ganti gambar">
                  <i class="ki-duotone ki-pencil fs-4"></i>
                  <input type="file" ref="imageInputRef" accept=".png, .jpg, .jpeg" @change="handleImageChange" class="d-none" />
                </label>
              </div>
            </div>

            <div class="row g-9">
              <div class="col-12">
                <label class="required fs-6 fw-semibold mb-2">Nomor Kamar</label>
                <el-form-item prop="room_number">
                  <el-input v-model="formData.room_number" placeholder="Contoh: 101 atau V-01"/>
                </el-form-item>
              </div>
              <div class="col-md-6">
                <label class="required fs-6 fw-semibold mb-2">Tipe Kamar</label>
                <el-form-item prop="type">
                  <el-select v-model="formData.type" placeholder="Pilih tipe kamar" class="w-100">
                    <el-option value="Standard" label="Standard" />
                    <el-option value="Deluxe" label="Deluxe" />
                    <el-option value="Suite" label="Suite" />
                  </el-select>
                </el-form-item>
              </div>
              <div class="col-md-6">
                <label class="required fs-6 fw-semibold mb-2">Status Kamar</label>
                <el-form-item prop="status">
                  <el-select v-model="formData.status" placeholder="Pilih status kamar" class="w-100">
                    <el-option value="available" label="Available" />
                    <el-option value="occupied" label="Occupied" />
                    <el-option value="maintenance" label="Maintenance" />
                  </el-select>
                </el-form-item>
              </div>
              <div class="col-12">
                <label class="required fs-6 fw-semibold mb-2">Harga per Malam</label>
                <el-form-item prop="price_per_night">
                  <el-input v-model="formData.price_per_night" type="number" placeholder="Contoh: 500000">
                    <template #prepend>Rp</template>
                  </el-input>
                </el-form-item>
              </div>

              <div class="col-md-6">
                <label class="fs-6 fw-semibold mb-2">Tersedia Mulai</label>
                <el-form-item prop="tersedia_mulai">
                    <el-date-picker v-model="formData.tersedia_mulai" type="date" placeholder="Pilih tanggal mulai" class="w-100" format="YYYY-MM-DD" value-format="YYYY-MM-DD"/>
                </el-form-item>
              </div>
              <div class="col-md-6">
                <label class="fs-6 fw-semibold mb-2">Tersedia Sampai</label>
                <el-form-item prop="tersedia_sampai">
                    <el-date-picker v-model="formData.tersedia_sampai" type="date" placeholder="Pilih tanggal selesai" class="w-100" format="YYYY-MM-DD" value-format="YYYY-MM-DD"/>
                </el-form-item>
              </div>

              <div class="col-12">
                <label class="fs-6 fw-semibold mb-2">Fasilitas Kamar</label>
                <el-form-item prop="facility_ids">
                  <el-select v-model="formData.facility_ids" multiple filterable placeholder="Pilih satu atau lebih fasilitas" class="w-100" :loading="loadingFacilities">
                    <el-option v-for="facility in allFacilities" :key="facility.id" :label="facility.name" :value="facility.id" />
                  </el-select>
                </el-form-item>
              </div>
              <div class="col-12">
                <label class="fs-6 fw-semibold mb-2">Deskripsi</label>
                <el-form-item prop="description">
                    <el-input v-model="formData.description" type="textarea" :rows="3" placeholder="Deskripsi singkat fasilitas kamar..." />
                </el-form-item>
              </div>
            </div>

            <div class="text-center pt-10">
              <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
              <button :data-kt-indicator="loading ? 'on' : null" class="btn btn-primary" type="submit">
                <span v-if="!loading" class="indicator-label">Simpan</span>
                <span v-if="loading" class="indicator-progress">
                  Harap tunggu... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
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
// [DIBENARKAN] ApiService lebih baik digunakan daripada axios langsung jika sudah ada
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import type { FormInstance, FormRules } from 'element-plus'

// Mendefinisikan tipe data
interface Facility { id: number; name: string; }
interface RoomData {
  id: number;
  room_number: string;
  type: string;
  status: string;
  price_per_night: number;
  description: string | null;
  image_url: string | null;
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

// Variabel Reaktif
const formRef = ref<FormInstance>();
const modalRef = ref<null | HTMLElement>(null);
const imageInputRef = ref<null | HTMLInputElement>(null);
const loading = ref(false);
const imagePreview = ref<string | null>(null);
const isEditMode = computed(() => !!props.roomData);
const allFacilities = ref<Facility[]>([]);
const loadingFacilities = ref(true);

// Data form awal
const getInitialFormData = (): FormData => ({
  id: null, room_number: "", type: "", status: "available", price_per_night: "", description: null, image: null,
  tersedia_mulai: null,
  tersedia_sampai: null,
  facility_ids: [],
});

const formData = ref<FormData>(getInitialFormData());

// Mengambil daftar fasilitas dari API
const getFacilities = async () => {
  try {
    loadingFacilities.value = true;
    const { data } = await ApiService.get("/facilities");
    allFacilities.value = data;
  } catch (error) {
    Swal.fire("Error", "Gagal memuat daftar fasilitas.", "error");
  } finally {
    loadingFacilities.value = false;
  }
};

onMounted(getFacilities);

// Fungsi untuk format tanggal YYYY-MM-DD
const formatDate = (dateString: string | null): string | null => {
    if (!dateString) return null;
    const date = new Date(dateString);
    // [DIBENARKAN] Mengatasi masalah timezone offset
    const offset = date.getTimezoneOffset();
    const correctedDate = new Date(date.getTime() - (offset*60*1000));
    return correctedDate.toISOString().split('T')[0];
};

// Mengawasi perubahan pada props untuk mengisi form
watch(() => props.roomData, (newVal) => {
  if (newVal) {
    // Mode Edit
    formData.value = {
      ...newVal,
      image: null,
      facility_ids: newVal.facilities ? newVal.facilities.map(f => f.id) : [],
      // [DIBENARKAN] Format tanggal saat mengisi form
      tersedia_mulai: formatDate(newVal.tersedia_mulai),
      tersedia_sampai: formatDate(newVal.tersedia_sampai),
    };
    imagePreview.value = newVal.image_url;
  } else {
    // Mode Tambah
    formRef.value?.resetFields();
    formData.value = getInitialFormData();
    imagePreview.value = null;
    if(imageInputRef.value) imageInputRef.value.value = "";
  }
});

// Menangani perubahan gambar
const handleImageChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    formData.value.image = file;
    const reader = new FileReader();
    reader.onload = (e) => { imagePreview.value = e.target?.result as string; };
    reader.readAsDataURL(file);
  }
};

// Aturan Validasi
const rules = ref<FormRules>({
  room_number: [{ required: true, message: "Nomor kamar wajib diisi.", trigger: "blur" }],
  type: [{ required: true, message: "Tipe kamar wajib dipilih.", trigger: "change" }],
  status: [{ required: true, message: "Status kamar wajib dipilih.", trigger: "change" }],
  price_per_night: [{ required: true, message: "Harga wajib diisi.", trigger: "blur" }],
});

// Fungsi Submit
const submit = () => {
  if (!formRef.value) return;
  formRef.value.validate(async (valid: boolean) => {
    if (valid) {
      loading.value = true;

      const data = new FormData();
      // [DIBENARKAN] Mengirim semua data termasuk yang nullable
      Object.entries(formData.value).forEach(([key, value]) => {
          if (key === 'facility_ids') {
              (value as number[]).forEach(id => data.append('facility_ids[]', String(id)));
          } else if (value !== null && value !== '') {
              data.append(key, value as any);
          }
      });

      try {
        if (isEditMode.value) {
          // [DIBENARKAN] Laravel hanya menerima POST untuk FormData, _method digunakan untuk meniru PUT
          data.append('_method', 'PUT');
          await ApiService.post(`/rooms/${formData.value.id}`, data);
        } else {
          await ApiService.post("/rooms", data);
        }
        Swal.fire({
          text: `Kamar berhasil ${ isEditMode.value ? "diperbarui" : "ditambahkan" }!`,
          icon: "success",
        }).then(() => {
          if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
          emit("room-updated");
        });
      } catch (error: any) {
        let errorMessages = "Maaf, terjadi kesalahan.";
        if (error.response?.data?.errors) {
         errorMessages = Object.values(error.response.data.errors).flat().join('<br>');
        }
        Swal.fire({ html: errorMessages, icon: "error" });
      } finally {
        loading.value = false;
      }
    }
  });
};
</script>

