<template>
  <div class="card mb-5 mb-xl-10 shadow-sm animate__animated animate__fadeIn">
    
    <div class="card-header border-0 cursor-pointer py-5 d-flex align-items-center justify-content-between">
      <div class="card-title m-0">
        <h3 class="fw-bold m-0 text-gray-800">
          <i class="bi bi-person-circle fs-2 me-2 text-orange-theme"></i> 
          Profil Saya
        </h3>
      </div>
      
      <router-link 
        :to="backRoute"
        class="btn btn-sm btn-icon btn-light-danger btn-active-danger shadow-sm"
        data-bs-toggle="tooltip" 
        title="Kembali ke Dashboard"
      >
        <i class="bi bi-x-lg fs-4"></i>
      </router-link>
    </div>

    <div id="kt_account_profile_details" class="collapse show">
      <VForm
        id="kt_account_profile_details_form"
        class="form"
        novalidate
        @submit="saveProfile"
        :validation-schema="profileDetailsValidator"
      >
        <div class="card-body border-top p-9">
          
          <div class="row mb-8 align-items-center">
            <label class="col-lg-4 col-form-label fw-bold fs-6">Foto Profil</label>
            <div class="col-lg-8">
              <div class="d-flex align-items-center">
                <div class="symbol symbol-100px symbol-lg-150px symbol-circle me-5 shadow-sm border border-3 border-white">
                    <div 
                      class="symbol-label" 
                      :style="`background-image: url(${photoPreview}); background-size: cover; background-position: center;`"
                    ></div>
                </div>

                <div class="d-flex flex-column">
                    <label
                      class="btn btn-orange-theme btn-sm mb-2 shadow-sm"
                      data-bs-toggle="tooltip"
                      title="Ganti Foto"
                    >
                      <i class="bi bi-pencil-fill fs-7 me-2"></i> Ubah Foto
                      <input
                        type="file"
                        name="avatar"
                        accept=".png, .jpg, .jpeg"
                        @change="onFileChange"
                        style="display: none;"
                      />
                    </label>

                    <button
                        v-if="profileDetails.avatarFile || (store.user && (store.user as any).photo)"
                        type="button" 
                        class="btn btn-light-danger btn-sm shadow-sm"
                        @click="removeImage"
                    >
                      <i class="bi bi-trash fs-7 me-2"></i> Hapus
                    </button>
                    <div class="form-text mt-2">Format: png, jpg, jpeg.</div>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-6">
            <label class="col-lg-4 col-form-label required fw-bold fs-6">Nama Lengkap</label>
            <div class="col-lg-8 fv-row">
              <Field
                type="text"
                name="name"
                class="form-control form-control-lg form-control-solid border-hover-orange"
                placeholder="Nama Lengkap"
                v-model="profileDetails.name"
              />
              <div class="fv-plugins-message-container">
                <div class="fv-help-block"><ErrorMessage name="name" /></div>
              </div>
            </div>
          </div>

          <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-bold fs-6">Email</label>
            <div class="col-lg-8 fv-row">
              <input
                type="text"
                class="form-control form-control-lg form-control-solid bg-light-secondary"
                v-model="profileDetails.email"
                readonly
                disabled
              />
              <div class="fw-semibold fs-7 text-muted mt-2">Email tidak dapat diubah.</div>
            </div>
          </div>

          <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-bold fs-6">Password Baru</label>
            <div class="col-lg-8 fv-row">
              <div class="position-relative mb-3">
                 <Field
                  type="password"
                  name="password"
                  class="form-control form-control-lg form-control-solid border-hover-orange"
                  placeholder="Kosongkan jika tidak ingin mengganti"
                  v-model="profileDetails.password"
                />
                <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2" data-kt-password-meter-control="visibility">
                    <i class="bi bi-eye-slash fs-2"></i>
                </span>
              </div>
            </div>
          </div>

        </div>

        <div class="card-footer d-flex justify-content-end py-6 px-9 bg-light-orange">
          
          <router-link :to="backRoute" class="btn btn-light btn-active-light-primary me-2">
            Batal
          </router-link>

          <button
            type="submit"
            class="btn btn-orange-theme shadow"
            :disabled="loading"
          >
            <span v-if="!loading" class="indicator-label">
                <i class="bi bi-check-circle-fill me-2"></i> Simpan Perubahan
            </span>
            <span v-else class="indicator-progress" style="display:block">
              Menyimpan...
              <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
            </span>
          </button>
        </div>
      </VForm>
    </div>
  </div>

  <div v-if="showCropper" class="cropper-modal-overlay animate__animated animate__fadeIn">
      <div class="cropper-modal-content card shadow-lg">
          <div class="card-header bg-orange-theme text-white">
              <h3 class="card-title text-white mb-0">Sesuaikan Foto</h3>
              <div class="card-toolbar">
                  <button type="button" class="btn btn-sm btn-icon btn-active-color-white" @click="cancelCrop">
                      <i class="bi bi-x-lg text-white"></i>
                  </button>
              </div>
          </div>
          <div class="card-body p-0 d-flex justify-content-center align-items-center bg-dark" style="height: 400px;">
              <cropper
                  ref="cropperRef"
                  :src="imageForCrop"
                  :aspect-ratio="1"
                  :view-mode="1"
                  :auto-crop-area="1"
                  :background="false"
                  :rotatable="true"
                  :scalable="true"
                  :zoomable="true"
                  style="max-height: 400px; max-width: 100%;"
              />
          </div>
          <div class="card-footer d-flex justify-content-between">
              <button type="button" class="btn btn-light" @click="cancelCrop">
                  <i class="bi bi-x-lg me-1"></i> Batal
              </button>
              <button type="button" class="btn btn-orange-theme" @click="cropImage">
                  <i class="bi bi-check-lg me-1"></i> Potong & Pakai
              </button>
          </div>
      </div>
  </div>
</template>

<script setup lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { ref, onMounted, computed } from "vue";
import { ErrorMessage, Field, Form as VForm } from "vee-validate";
import Swal from "sweetalert2";
import * as Yup from "yup";
import { useAuthStore } from "@/stores/auth";
import ApiService from "@/core/services/ApiService";
import VueCropper from "vue-cropperjs";
import "cropperjs/dist/cropper.css";

// Rename component untuk avoid conflict
const Cropper = VueCropper;

const store = useAuthStore();
const loading = ref(false);

// Logic Navigasi Pintar (User vs Admin)
const backRoute = computed(() => {
    // FIX: Gunakan (store.user as any) agar TypeScript tidak protes soal 'roles'
    const userAny = store.user as any;
    
    // Ambil role dari roles[0].name (jika ada) atau fallback ke store.userRole
    const role = userAny?.roles?.[0]?.name || store.userRole || 'user';
    
    // Jika role user adalah 'user', kembalikan ke dashboard user
    if (role === 'user') {
        return { name: 'user-dashboard' };
    }
    // Default ke admin
    return { name: 'admin-dashboard' };
});

// Refs untuk Cropper
const showCropper = ref(false);
const imageForCrop = ref("");
const cropperRef = ref<InstanceType<typeof VueCropper> | null>(null);
const fileInputTemp = ref<File | null>(null);

// Validator
const profileDetailsValidator = Yup.object().shape({
  name: Yup.string().required().label("Nama Lengkap"),
});

// State Data
const profileDetails = ref({
  name: "",
  email: "",
  password: "",
  avatarFile: null as File | null,
});

const photoPreview = ref(getAssetPath("media/avatars/blank.png"));

// 1. Load Data User
onMounted(() => {
  if (store.user) {
    profileDetails.value.name = store.user.name;
    profileDetails.value.email = store.user.email;
    
    // Gunakan 'as any' untuk menghindari redline TypeScript
    const userPhoto = (store.user as any).photo;
    if (userPhoto) {
      photoPreview.value = userPhoto;
    }
  }
});

// 2. Saat File Dipilih -> Buka Modal Crop
const onFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];

  if (file) {
      if (!file.type.includes('image/')) {
          Swal.fire("Error", "Mohon pilih file gambar.", "error");
          return;
      }
      
      fileInputTemp.value = file;
      
      const reader = new FileReader();
      reader.onload = (evt) => {
          imageForCrop.value = evt.target?.result as string;
          showCropper.value = true;
      };
      reader.readAsDataURL(file);
  }
  target.value = "";
};

// 3. Eksekusi Crop
const cropImage = () => {
    if (cropperRef.value) {
        // Akses instance cropperjs di dalam vue-cropperjs
        const cropper = (cropperRef.value as any).cropper;
        
        if (cropper) {
            const canvas = cropper.getCroppedCanvas({
                width: 300,
                height: 300,
                imageSmoothingQuality: 'high'
            });
            
            canvas.toBlob((blob: Blob | null) => {
                if (blob) {
                    const newFile = new File([blob], "avatar_cropped.jpg", { type: "image/jpeg" });
                    profileDetails.value.avatarFile = newFile;
                    photoPreview.value = URL.createObjectURL(blob);
                    showCropper.value = false;
                    
                    Swal.fire({
                        title: "Berhasil!",
                        text: "Foto berhasil dipotong. Jangan lupa simpan perubahan.",
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            }, 'image/jpeg', 0.9);
        }
    }
};

const cancelCrop = () => {
    showCropper.value = false;
    imageForCrop.value = "";
    fileInputTemp.value = null;
};

const removeImage = () => {
  profileDetails.value.avatarFile = null;
  photoPreview.value = getAssetPath("media/avatars/blank.png");
};

// 4. Simpan ke Backend
const saveProfile = async () => {
  if (!store.user) return;
  loading.value = true;

  const formData = new FormData();
  formData.append("name", profileDetails.value.name);
  if (profileDetails.value.password) formData.append("password", profileDetails.value.password);
  if (profileDetails.value.avatarFile) formData.append("photo", profileDetails.value.avatarFile);

  try {
    const response = await ApiService.post("auth/update-profile", formData);
    
    store.user.name = profileDetails.value.name;
    const newPhotoUrl = response.data.data?.photo_url; 
    if (newPhotoUrl) (store.user as any).photo = newPhotoUrl;

    Swal.fire({
      title: "Berhasil!",
      text: "Profil Anda telah diperbarui.",
      icon: "success",
      confirmButtonText: "Mantap",
      customClass: { confirmButton: "btn btn-orange-theme" },
      buttonsStyling: false
    });
    
    profileDetails.value.password = "";
  } catch (error: any) {
    Swal.fire({
      text: error.response?.data?.message || "Gagal update profil.",
      icon: "error",
      confirmButtonText: "Ok",
      buttonsStyling: false,
      customClass: { confirmButton: "btn btn-danger" },
    });
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.text-orange-theme {
    color: #f57c00 !important;
}

.bg-orange-theme {
    background-color: #f57c00 !important;
}

.bg-light-orange {
    background-color: #fff3e0 !important;
}

.btn-orange-theme {
    background-color: #f57c00;
    color: #ffffff;
    border: none;
    transition: all 0.3s ease;
}

.btn-orange-theme:hover {
    background-color: #ef6c00;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 124, 0, 0.3);
}

.border-hover-orange:focus {
    border-color: #f57c00 !important;
    box-shadow: 0 0 0 0.25rem rgba(245, 124, 0, 0.25) !important;
}

.cropper-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.75);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
    backdrop-filter: blur(5px);
}

.cropper-modal-content {
    width: 90%;
    max-width: 650px;
    background: white;
    border-radius: 12px;
    overflow: hidden;
}
</style>