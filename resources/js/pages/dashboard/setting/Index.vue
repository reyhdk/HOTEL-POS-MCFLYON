<template>
    <VForm class="card mb-10" @submit="submit" :validation-schema="formSchema">
        
        <div class="card-header align-items-center">
            <h2 class="mb-0">Konfigurasi Website</h2>
        </div>

        <div class="card-body">
            
            <div class="fv-row mb-8">
                <label class="form-label fw-bold fs-6 required">Nama Aplikasi</label>
                <Field class="form-control form-control-lg form-control-solid" 
                    type="text" name="app" autocomplete="off" 
                    v-model="formData.app" placeholder="Masukkan nama aplikasi" />
                <div class="fv-plugins-message-container">
                    <ErrorMessage name="app" class="text-danger" />
                </div>
            </div>

            <div class="fv-row mb-8">
                <label class="form-label fw-bold fs-6 required">Deskripsi</label>
                <Field as="textarea" rows="4" class="form-control form-control-lg form-control-solid" 
                    name="description" v-model="formData.description" 
                    placeholder="Deskripsi singkat aplikasi" />
                <div class="fv-plugins-message-container">
                    <ErrorMessage name="description" class="text-danger" />
                </div>
            </div>

            <div class="fv-row mb-8">
                <label class="form-label fw-bold fs-6">Logo Aplikasi</label>
                <file-upload 
                    :files="files.logo" 
                    :accepted-file-types="fileTypes"
                    @updatefiles="(items) => files.logo = items"
                ></file-upload>
                <div class="text-muted fs-7 mt-2">Format: PNG, JPG. Max 2MB.</div>
            </div>

            <div class="fv-row mb-8">
                <label class="form-label fw-bold fs-6">Background Login / Register</label>
                <file-upload 
                    :files="files.bgAuth" 
                    :accepted-file-types="fileTypes"
                    @updatefiles="(items) => files.bgAuth = items"
                ></file-upload>
                <div class="text-muted fs-7 mt-2">
                    Background untuk halaman login dan register. Disarankan resolusi 1920x1080.
                </div>
            </div>

            <div class="fv-row mb-8">
                <label class="form-label fw-bold fs-6">Background Landing Page</label>
                <file-upload 
                    :files="files.bgLanding" 
                    :accepted-file-types="fileTypes"
                    @updatefiles="(items) => files.bgLanding = items"
                ></file-upload>
                <div class="text-muted fs-7 mt-2">
                    Background untuk halaman utama/landing page. Disarankan resolusi 1920x1080.
                </div>
            </div>

        </div>

        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="submit" class="btn btn-primary">
                Simpan Perubahan
            </button>
        </div>

    </VForm>
</template>

<script lang="ts">
import { defineComponent, ref, reactive } from "vue";
import { ErrorMessage, Field, Form as VForm } from "vee-validate";
import { useSetting } from "@/services";
import * as Yup from "yup";
import { block, unblock } from "@/libs/utils";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";

export default defineComponent({
    components: {
        ErrorMessage,
        Field,
        VForm,
    },
    setup() {
        const setting = useSetting()
        
        const fileTypes = ref(["image/jpeg", "image/png", "image/jpg"])

        const formData = ref({
            app: '',
            description: '',
        })

        // Variable reactive untuk menampung file
        const files = reactive<{ logo: any[], bgAuth: any[], bgLanding: any[] }>({
            logo: [],
            bgAuth: [],
            bgLanding: []
        })

        const formSchema = Yup.object().shape({
            app: Yup.string().required('Nama aplikasi wajib diisi'),
            description: Yup.string().required('Deskripsi wajib diisi'),
        })

        return {
            setting,
            formData,
            formSchema,
            fileTypes,
            files
        }
    },
    methods: {
        submit() {
            // DEBUG: Cek apakah data file sudah masuk ke variable
            console.log("=== DEBUG SUBMIT ===");
            console.log("Logo Files:", this.files.logo);
            
            const data = new FormData()

            data.append('app', this.formData.app)
            data.append('description', this.formData.description)

            // --- FUNGSI HELPER: Ekstrak file dari object FilePond ---
            const getFile = (fileItem: any) => {
                if (!fileItem) return null;
                
                // 1. Jika URL string (file lama), abaikan
                if (typeof fileItem === 'string') return null;

                // 2. Jika FilePond Wrapper (biasanya ada di properti .file)
                if (fileItem.file) return fileItem.file;

                // 3. Jika File Object Native
                if (fileItem instanceof File) return fileItem;

                return null;
            }

            // --- PROSES LOGO ---
            if (this.files.logo && this.files.logo.length > 0) {
                // Ambil item terakhir yang diupload user
                const item = this.files.logo[0]; 
                const file = getFile(item);
                
                if (file) {
                    console.log("File Logo Ditemukan:", file.name); // Debug
                    data.append('logo', file);
                } else {
                    console.log("File Logo TIDAK valid atau String URL lama");
                }
            }

            // --- PROSES BG AUTH ---
            if (this.files.bgAuth && this.files.bgAuth.length > 0) {
                const file = getFile(this.files.bgAuth[0]);
                if (file) data.append('bg_auth', file);
            }

            // --- PROSES BG LANDING ---
            if (this.files.bgLanding && this.files.bgLanding.length > 0) {
                const file = getFile(this.files.bgLanding[0]);
                if (file) data.append('bg_landing', file);
            }

            block(this.$el)
            
            axios.post("/settings", data, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then((res) => {
                toast.success(res.data.message)
                
                this.setting.refetch()
                
                // Force reload gambar preview
                setTimeout(() => {
                    const timestamp = new Date().getTime();
                    if (res.data.data.logo) {
                        this.files.logo = [res.data.data.logo + '?v=' + timestamp]
                    }
                    if (res.data.data.bg_auth) {
                        this.files.bgAuth = [res.data.data.bg_auth + '?v=' + timestamp]
                    }
                    if (res.data.data.bg_landing) {
                        this.files.bgLanding = [res.data.data.bg_landing + '?v=' + timestamp]
                    }
                }, 500)
            })
            .catch(err => {
                console.error('Error detail:', err.response)
                toast.error(err.response?.data?.message || 'Terjadi kesalahan')
            })
            .finally(() => {
                unblock(this.$el)
            })
        }
    },
    watch: {
        setting: {
            handler(setting) {
                if (setting.data && setting.data.value) {
                    const val = setting.data.value
                    
                    this.formData = {
                        app: val.app,
                        description: val.description
                    }

                    this.files.logo = val.logo ? [val.logo] : []
                    this.files.bgAuth = val.bg_auth ? [val.bg_auth] : []
                    this.files.bgLanding = val.bg_landing ? [val.bg_landing] : []
                }
            },
            deep: true,
            immediate: true
        }
    }
})
</script>