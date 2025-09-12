<<template>
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
                  width: '150px',
                  height: '150px',
                  backgroundSize: 'cover',
                  borderRadius: '1.25rem'
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
import { ref, computed, watch, onMounted, defineProps, defineEmits } from "vue";
import axios from "@/libs/axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";

// Mendefinisikan tipe data
interface Facility {
  id: number;
  name: string;
}

interface RoomData {
  id: number;
  room_number: string;
  type: string;
  status: string;
  price_per_night: number;
  description: string | null;
  image_url: string | null; // Disesuaikan untuk menerima URL gambar
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
const emit = defineEmits(['room-updated']);

// Variabel Reaktif
const formRef = ref<any>(null);
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
    const response = await axios.get("/facilities");
    allFacilities.value = response.data;
  } catch (error) {
    console.error("Gagal mengambil data fasilitas:", error);
    Swal.fire("Error", "Gagal memuat daftar fasilitas.", "error");
  } finally {
    loadingFacilities.value = false;
  }
};

// Panggil saat komponen dimuat
onMounted(() => {
  getFacilities();
});

// Mengawasi perubahan pada props untuk mengisi form
watch(() => props.roomData, (newVal) => {
  if (newVal) {
    // Mode Edit: Isi form dengan data yang ada
    formData.value = {
      ...newVal,
      image: null,
      facility_ids: newVal.facilities ? newVal.facilities.map(f => f.id) : []
    };
    imagePreview.value = newVal.image_url;
  } else {
    // Mode Tambah: Reset form
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
const rules = ref({
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
      data.append('room_number', formData.value.room_number);
      data.append('type', formData.value.type);
      data.append('status', formData.value.status);
      data.append('price_per_night', String(formData.value.price_per_night));

      if (formData.value.description) data.append('description', formData.value.description);
      if (formData.value.image) data.append('image', formData.value.image);
      if (formData.value.tersedia_mulai) data.append('tersedia_mulai', formData.value.tersedia_mulai);
      if (formData.value.tersedia_sampai) data.append('tersedia_sampai', formData.value.tersedia_sampai);

      if (formData.value.facility_ids.length > 0) {
        formData.value.facility_ids.forEach(id => {
          data.append('facility_ids[]', String(id));
        });
      }

      try {
        if (isEditMode.value) {
          data.append('_method', 'PUT'); // Gunakan PUT untuk update
          await axios.post(`/rooms/${formData.value.id}`, data, { headers: { 'Content-Type': 'multipart/form-data' } });
        } else {
          await axios.post("/rooms", data, { headers: { 'Content-Type': 'multipart/form-data' } });
        }
        Swal.fire({
          text: `Kamar berhasil ${ isEditMode.value ? "diperbarui" : "ditambahkan" }!`,
          icon: "success",
          buttonsStyling: false,
          confirmButtonText: "Ok, mengerti!",
          customClass: { confirmButton: "btn btn-primary" },
        }).then(() => {
          if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
          emit("room-updated");
        });
      } catch (error: any) {
         let errorMessages = "Maaf, terjadi kesalahan.";
        if (error.response && error.response.data && error.response.data.errors) {
          errorMessages = Object.values(error.response.data.errors).flat().join('<br>');
        }
        Swal.fire({
          html: errorMessages,
          icon: "error",
          buttonsStyling: false,
          confirmButtonText: "Ok, mengerti!",
          customClass: { confirmButton: "btn btn-primary" },
        });
      } finally { 
        loading.value = false; 
      }
    }
  });
};
</script>