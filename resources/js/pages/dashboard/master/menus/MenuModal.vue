<template>
  <div class="modal fade" id="kt_modal_menu" ref="modalRef" tabindex="-1">
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
              <h1 class="mb-3">{{ isEditMode ? 'Edit Menu' : 'Tambah Menu Baru' }}</h1>
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
                <label class="required fs-6 fw-semibold mb-2">Nama Menu</label>
                <el-form-item prop="name"><el-input v-model="formData.name" placeholder="Contoh: Nasi Goreng Spesial"/></el-form-item>
              </div>
              <div class="col-12">
                <label class="required fs-6 fw-semibold mb-2">Kategori</label>
                <el-form-item prop="category">
                  <el-select v-model="formData.category" placeholder="Pilih kategori" class="w-100">
                    <el-option value="Makanan" label="Makanan"/>
                    <el-option value="Minuman" label="Minuman"/>
                    <el-option value="Snack" label="Snack"/>
                  </el-select>
                </el-form-item>
              </div>
              <div class="col-md-6">
                <label class="required fs-6 fw-semibold mb-2">Harga</label>
                <el-form-item prop="price"><el-input v-model="formData.price" type="number"><template #prepend>Rp</template></el-input></el-form-item>
              </div>
              <div class="col-md-6">
                <label class="required fs-6 fw-semibold mb-2">Stok Awal</label>
                <el-form-item prop="stock"><el-input v-model="formData.stock" type="number"/></el-form-item>
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
import { ref, watch, computed } from "vue";
import Swal from "sweetalert2";
import axios from "@/libs/axios";
import { Modal } from "bootstrap";

// Tipe Data
interface MenuData {
  id: number;
  name: string;
  category: string;
  price: number;
  stock: number;
  image_url: string | null; // Menerima image_url dari backend
}
interface FormData {
  id: number | null;
  name: string;
  category: string;
  price: number | string;
  stock: number | string;
  image: File | null;
}

// Props & Emits
const props = defineProps<{ menuData: MenuData | null }>();
const emit = defineEmits(["menu-updated"]);

// Variabel Reaktif
const formRef = ref<any>(null);
const modalRef = ref<null | HTMLElement>(null);
const imageInputRef = ref<null | HTMLInputElement>(null);
const loading = ref(false);
const imagePreview = ref<string | null>(null);

const isEditMode = computed(() => !!props.menuData);

const getInitialFormData = (): FormData => ({
  id: null, name: "", category: "", price: "", stock: 0, image: null,
});

const formData = ref<FormData>(getInitialFormData());

// Mengawasi perubahan props untuk mengisi form
watch(() => props.menuData, (newVal) => {
  if (newVal) {
    // Mode Edit
    formData.value = { ...newVal, price: String(newVal.price), stock: String(newVal.stock), image: null };
    imagePreview.value = newVal.image_url; // Gunakan image_url untuk pratinjau
  } else {
    // Mode Tambah: Reset form
    formRef.value?.resetFields();
    formData.value = getInitialFormData();
    imagePreview.value = null;
    if (imageInputRef.value) imageInputRef.value.value = "";
  }
});

// Fungsi menangani perubahan gambar
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

// Aturan validasi
const rules = ref({
  name: [{ required: true, message: "Nama menu wajib diisi.", trigger: "blur" }],
  category: [{ required: true, message: "Kategori wajib dipilih.", trigger: "change" }],
  price: [{ required: true, message: "Harga wajib diisi.", trigger: "blur" }],
  stock: [{ required: true, message: "Stok wajib diisi.", trigger: "blur" }],
});

// Fungsi Submit
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
      if (formData.value.image) {
        data.append('image', formData.value.image);
      }

      try {
        if (isEditMode.value) {
          // [DIUBAH] Gunakan method _method: 'PUT' untuk update dengan FormData
          data.append('_method', 'PUT');
          await axios.post(`/menus/${formData.value.id}`, data);
        } else {
          await axios.post("/menus", data);
        }
        
        Swal.fire({
          text: `Menu berhasil ${ isEditMode.value ? 'diperbarui' : 'ditambahkan' }!`,
          icon: "success",
          buttonsStyling: false,
          confirmButtonText: "Ok, mengerti!",
          customClass: { confirmButton: "btn btn-primary" },
        });

        const modal = Modal.getInstance(modalRef.value as HTMLElement);
        modal?.hide();
        emit("menu-updated");
        
      } catch (error: any) {
        const message = error.response?.data?.message || "Gagal menyimpan data.";
        Swal.fire({
          text: message,
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