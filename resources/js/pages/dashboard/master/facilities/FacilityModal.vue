<template>
  <div class="modal fade" id="kt_modal_facility" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="fw-bold">{{ isEditMode ? 'Edit Fasilitas' : 'Tambah Fasilitas Baru' }}</h2>
          <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
            <i class="ki-duotone ki-cross fs-1"></i>
          </div>
        </div>
        <form @submit.prevent="submit">
          <div class="modal-body py-10 px-lg-17">
            <div class="fv-row mb-7">
              <label class="required fs-6 fw-semibold mb-2">Nama Fasilitas</label>
              <input v-model="formData.name" type="text" class="form-control" placeholder="Contoh: Wi-Fi Cepat"/>
            </div>

            <div class="fv-row mb-7">
              <label class="fs-6 fw-semibold mb-2">Ikon (Opsional)</label>
              
              <div class="mb-3 text-center" v-if="iconPreview">
                <img :src="iconPreview" alt="Pratinjau Ikon" style="max-height: 60px; border-radius: 6px;" />
              </div>

              <input 
                type="file" 
                class="form-control" 
                @change="handleIconChange"
                accept="image/svg+xml, image/png, image/jpeg"
              />
              <div class="form-text">Pilih file SVG, PNG, atau JPG (Maks. 1MB).</div>
            </div>
            <div class="fv-row mb-7">
              <label class="fs-6 fw-semibold mb-2">Deskripsi (Opsional)</label>
              <textarea v-model="formData.description" class="form-control" rows="3" placeholder="Deskripsi singkat..."></textarea>
            </div>
          </div>
          <div class="modal-footer flex-center">
            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
            <button :disabled="loading" class="btn btn-lg btn-primary" type="submit">
              <span v-if="!loading" class="indicator-label">Simpan</span>
              <span v-if="loading" class="indicator-progress">
                Mohon tunggu... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import axios from "@/libs/axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";

interface FacilityData {
  id: number;
  name: string;
  icon: string | null;
  description: string | null;
  icon_url: string | null; // <-- Tambahkan ini untuk menerima URL dari accessor
}

const props = defineProps<{ facilityData: FacilityData | null }>();
const emit = defineEmits(['facility-updated']);

const modalRef = ref<null | HTMLElement>(null);
const loading = ref(false);
const isEditMode = computed(() => !!props.facilityData);

const iconPreview = ref<string | null>(null);
const iconFile = ref<File | null>(null);

const getInitialFormData = () => ({
  id: null as number | null,
  name: "",
  description: null as string | null,
});

const formData = ref(getInitialFormData());

// Fungsi untuk menangani saat user memilih file
const handleIconChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    const file = target.files[0];
    iconFile.value = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      iconPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
};

// Mengawasi perubahan data untuk mengisi form & pratinjau
watch(() => props.facilityData, (newVal) => {
  formData.value = getInitialFormData();
  iconFile.value = null;
  
  if (newVal) {
    formData.value = { id: newVal.id, name: newVal.name, description: newVal.description };
    iconPreview.value = newVal.icon_url; // Tampilkan gambar yang sudah ada
  } else {
    iconPreview.value = null; // Kosongkan pratinjau saat menambah baru
  }
});

// Mengirim data menggunakan FormData
const submit = async () => {
  if (!formData.value.name) {
    Swal.fire("Oops!", "Nama fasilitas wajib diisi.", "warning");
    return;
  }
  
  loading.value = true;
  
  const data = new FormData();
  data.append('name', formData.value.name);
  if (formData.value.description) {
    data.append('description', formData.value.description);
  }
  if (iconFile.value) {
    data.append('icon', iconFile.value);
  }

  try {
    if (isEditMode.value && formData.value.id) {
      data.append('_method', 'PUT'); // Trik untuk request PUT dengan FormData
      await axios.post(`/facilities/${formData.value.id}`, data);
    } else {
      await axios.post("/facilities", data);
    }
    
    Swal.fire("Berhasil!", `Fasilitas berhasil ${isEditMode.value ? 'diperbarui' : 'ditambahkan'}.`, "success");
    emit('facility-updated');
    if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();

  } catch (error) {
    Swal.fire("Error!", "Terjadi kesalahan.", "error");
  } finally {
    loading.value = false;
  }
};
</script>