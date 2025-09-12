<template>
  <div class="modal fade" id="kt_modal_guest" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="fw-bold">{{ isEditMode ? 'Edit Tamu' : 'Tambah Tamu Baru' }}</h2>
          <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
            <i class="ki-duotone ki-cross fs-1"></i>
          </div>
        </div>
        <el-form @submit.prevent="submit" :model="formData" :rules="rules" ref="formRef">
          <div class="modal-body py-10 px-lg-17">
            <div class="fv-row mb-7">
              <label class="required fs-6 fw-semibold mb-2">Nama Lengkap</label>
              <el-form-item prop="name"><el-input v-model="formData.name" /></el-form-item>
            </div>
            <div class="fv-row mb-7">
              <label class="fs-6 fw-semibold mb-2">Email</label>
              <el-form-item prop="email"><el-input v-model="formData.email" /></el-form-item>
            </div>
            <div class="fv-row mb-7">
              <label class="fs-6 fw-semibold mb-2">Nomor Telepon</label>
              <el-form-item prop="phone_number"><el-input v-model="formData.phone_number" /></el-form-item>
            </div>
            <div class="fv-row mb-7">
              <label class="fs-6 fw-semibold mb-2">Alamat</label>
              <el-form-item prop="address"><el-input v-model="formData.address" type="textarea" :rows="3" /></el-form-item>
            </div>
          </div>
          <div class="modal-footer flex-center">
            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
            <button :data-kt-indicator="loading ? 'on' : null" class="btn btn-lg btn-primary" type="submit">
              <span v-if="!loading" class="indicator-label">Simpan</span>
            </button>
          </div>
        </el-form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed, defineProps, defineEmits } from "vue";
import Swal from "sweetalert2";
import axios from "axios";
import { Modal } from "bootstrap";

const props = defineProps<{ guestData: any }>();
const emit = defineEmits(['guest-updated']);

const formRef = ref<any>(null);
const modalRef = ref<null | HTMLElement>(null);
const loading = ref(false);
const isEditMode = computed(() => !!props.guestData);

const formData = ref({
  id: null, name: "", email: "", phone_number: "", address: ""
});

watch(() => props.guestData, (newVal) => {
  if (newVal) {
    formData.value = { ...newVal };
  } else {
    formRef.value?.resetFields();
    formData.value = { id: null, name: "", email: "", phone_number: "", address: "" };
  }
});

const rules = ref({
  name: [{ required: true, message: "Nama wajib diisi.", trigger: "blur" }],
  email: [{ type: 'email', message: 'Format email tidak valid.', trigger: ['blur', 'change'] }]
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
        Swal.fire("Berhasil!", `Data tamu berhasil ${isEditMode.value ? 'diperbarui' : 'ditambahkan'}.`, "success");
        emit("guest-updated");
        if (modalRef.value) Modal.getInstance(modalRef.value)?.hide();
      } catch (error: any) {
        let errorMessages = "Terjadi kesalahan.";
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