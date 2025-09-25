<script setup lang="ts">
import { onMounted, ref, watch, computed } from "vue";
import * as Yup from "yup";
import { toast } from "vue3-toastify";
import type { User, Role } from "@/types"; // Gunakan tipe data global dari types/index.d.ts
import ApiService from "@/core/services/ApiService";
import { Form as VForm, Field, ErrorMessage } from "vee-validate";
import { block, unblock } from "@/libs/utils";

const props = defineProps<{
    selected: string | null; // ID user (UUID) adalah string
}>();

const emit = defineEmits(["close", "refresh"]);

// --- STATE MANAGEMENT ---
const formData = ref<any>({}); // Gunakan 'any' untuk fleksibilitas form saat reset
const formRef = ref<any>(null);
const loading = ref(false);
const allRoles = ref<Role[]>([]); // State untuk menyimpan daftar semua role

// --- SKEMA VALIDASI ---
const formSchema = Yup.object().shape({
    name: Yup.string().required("Nama harus diisi"),
    email: Yup.string().email("Email harus valid").required("Email harus diisi"),
    password: Yup.string().min(8, "Minimal 8 karakter").nullable(),
    password_confirmation: Yup.string()
        .oneOf([Yup.ref("password")], "Konfirmasi password harus sama")
        .nullable(),
    phone: Yup.string().required("Nomor Telepon harus diisi"),
    role_name: Yup.string().required("Pilih role"),
});

// --- FUNGSI-FUNGSI UTAMA ---
async function fetchData() {
    // Ambil daftar semua role untuk dropdown
    try {
        const { data } = await ApiService.get("/master/all-roles");
        allRoles.value = data;
    } catch (error) {
        toast.error("Gagal memuat daftar role.");
    }

    // Jika dalam mode edit, ambil data user spesifik
    if (props.selected) {
        loading.value = true;
        try {
            const { data } = await ApiService.get(`/master/users/${props.selected}`);
            formData.value = {
                ...data,
                // Siapkan role_name dari relasi roles
                role_name: data.roles?.[0]?.name || null,
            };
        } catch (error: any) {
            toast.error(error.response?.data?.message || "Gagal memuat data user.");
            emit('close');
        } finally {
            loading.value = false;
        }
    }
}

async function submit() {
    if (!formRef.value) return;

    const { valid } = await formRef.value.validate();
    if (!valid) return;

    loading.value = true;
    const formEl = document.getElementById("form-user");
    if (formEl) block(formEl);

    // [DIPERBAIKI] Buat payload tanpa properti yang tidak perlu dikirim
    const payload = { ...formData.value };
    delete payload.roles; // Hapus relasi 'roles' sebelum mengirim
    delete payload.photo_url; // Hapus properti 'photo_url'

    try {
        if (props.selected) {
            await ApiService.put(`/master/users/${props.selected}`, payload);
        } else {
            await ApiService.post("/master/users", payload);
        }
        toast.success("Data berhasil disimpan");
        emit('refresh');
        emit('close');
    } catch (error: any) {
        if (error.response?.data?.errors) {
            formRef.value.setErrors(error.response.data.errors);
        }
        toast.error(error.response?.data?.message || "Terjadi kesalahan.");
    } finally {
        if (formEl) unblock(formEl);
        loading.value = false;
    }
}

// --- LIFECYCLE HOOKS ---
onMounted(fetchData);

watch(() => props.selected, (newVal) => {
    if (newVal) {
        fetchData();
    } else {
        // [DIPERBAIKI] Cara reset yang lebih aman
        formData.value = {};
        formRef.value?.resetForm();
    }
});
</script>

<template>
    <VForm
        class="form card mb-10 shadow-sm"
        @submit="submit"
        :validation-schema="formSchema"
        id="form-user"
        ref="formRef"
    >
        <div class="card-header align-items-center">
            <h2 class="mb-0">{{ selected ? "Edit User" : "Tambah User Baru" }}</h2>
            <button
                type="button"
                class="btn btn-sm btn-light-danger ms-auto"
                @click="emit('close')"
            >
                Batal
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 fv-row mb-7">
                    <label class="form-label fw-bold fs-6 required">Nama</label>
                    <Field class="form-control form-control-solid" type="text" name="name" v-model="formData.name" />
                    <div class="fv-plugins-message-container"><ErrorMessage name="name" /></div>
                </div>
                <div class="col-md-6 fv-row mb-7">
                    <label class="form-label fw-bold fs-6 required">Email</label>
                    <Field class="form-control form-control-solid" type="email" name="email" v-model="formData.email" />
                    <div class="fv-plugins-message-container"><ErrorMessage name="email" /></div>
                </div>
                <div class="col-md-6 fv-row mb-7">
                    <label class="form-label fw-bold fs-6">Password</label>
                    <Field class="form-control form-control-solid" type="password" name="password" v-model="formData.password" placeholder="Isi untuk mengubah" autocomplete="new-password" />
                    <div class="fv-plugins-message-container"><ErrorMessage name="password" /></div>
                </div>
                <div class="col-md-6 fv-row mb-7">
                    <label class="form-label fw-bold fs-6">Konfirmasi Password</label>
                    <Field class="form-control form-control-solid" type="password" name="password_confirmation" v-model="formData.password_confirmation" />
                    <div class="fv-plugins-message-container"><ErrorMessage name="password_confirmation" /></div>
                </div>
                <div class="col-md-6 fv-row mb-7">
                    <label class="form-label fw-bold fs-6 required">Nomor Telepon</label>
                    <Field class="form-control form-control-solid" type="text" name="phone" v-model="formData.phone" />
                    <div class="fv-plugins-message-container"><ErrorMessage name="phone" /></div>
                </div>
                <div class="col-md-6 fv-row mb-7">
                    <label class="form-label fw-bold fs-6 required">Role</label>
                    <Field as="select" name="role_name" class="form-select form-select-solid" v-model="formData.role_name">
                        <option value="" disabled>Pilih role...</option>
                        <option v-for="role in allRoles" :key="role.id" :value="role.name">
                            {{ role.full_name }}
                        </option>
                    </Field>
                    <div class="fv-plugins-message-container"><ErrorMessage name="role_name" /></div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex">
            <button type="submit" class="btn btn-primary btn-sm ms-auto" :disabled="loading">
                <span v-if="!loading">Simpan</span>
                <span v-else>
                    Menyimpan...
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </div>
    </VForm>
</template>
