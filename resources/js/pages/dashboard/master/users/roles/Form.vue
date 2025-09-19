<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import * as Yup from "yup";
import { toast } from "vue3-toastify";
import ApiService from "@/core/services/ApiService";
import type { MenuItem } from "@/layouts/default-layout/config/types";
import MainMenuConfig from "@/layouts/default-layout/config/MainMenuConfig";
import { Form as VForm, Field, ErrorMessage } from "vee-validate";
import { block, unblock } from "@/libs/utils";

// [DIBENARKAN] Definisikan tipe data secara lokal di sini
interface Role {
  id: number | null;
  name: string;
  full_name: string;
  permissions: string[];
}
interface Item {
    text: string;
    value: string;
    children?: Item[];
}
// ----------------------------------------------------

const props = defineProps<{
    selected: number | null;
}>();

const emit = defineEmits(["close", "refresh"]);

// --- FUNGSI HELPER ---
const extractNames = (menuConfig: MenuItem[]): Item[] => {
    const names: Item[] = [];
    for (const item of menuConfig) {
        if ("name" in item && item.name) {
            const nameObject: Item = {
                text: item.heading || item.sectionTitle!,
                value: item.name,
            };
            const children = item.pages || ("sub" in item ? item.sub : undefined);
            if (children) {
                nameObject.children = extractNames(children);
            }
            names.push(nameObject);
        } else {
            const children = item.pages || ("sub" in item ? item.sub : undefined);
            if (children) {
                names.push(...extractNames(children));
            }
        }
    }
    return names;
};

const permissions: Item[] = extractNames(MainMenuConfig);

const findChildren = (menuConfig: Item[]): string[] => {
    const names: string[] = [];
    for (const item of menuConfig) {
        if (item.children?.length) {
            names.push(...findChildren(item.children));
        }
        names.push(item.value);
    }
    return names;
};

const findAllParents = (tree: Item[], targetValue: string, parents: string[] = []): string[] => {
    for (const item of tree) {
        if (item.value === targetValue) {
            return parents;
        }
        if (item.children?.length) {
            const foundParents = findAllParents(item.children, targetValue, [...parents, item.value]);
            if (foundParents.length > 0 || item.children.some(c => c.value === targetValue)) {
                return foundParents;
            }
        }
    }
    return [];
};

// --- STATE MANAGEMENT ---
const formData = ref<Role>({ id: null, name: '', full_name: '', permissions: [] });
const formRef = ref<any>(null);
const loading = ref(false);

const formSchema = Yup.object().shape({
    name: Yup.string().required("Nama harus diisi"),
    full_name: Yup.string().required("Nama Lengkap harus diisi"),
    permissions: Yup.array().of(Yup.string()),
});

// --- FUNGSI-FUNGSI UTAMA ---
function handleCheck(item: Item, isChecked: boolean) {
    let currentPermissions = new Set(formData.value.permissions);
    const itemAndChildren = [item.value, ...findChildren(item.children || [])];
    if (isChecked) {
        itemAndChildren.forEach(p => currentPermissions.add(p));
        const parents = findAllParents(permissions, item.value);
        parents.forEach(p => currentPermissions.add(p));
    } else {
        itemAndChildren.forEach(p => currentPermissions.delete(p));
    }
    formData.value.permissions = Array.from(currentPermissions);
}

async function getEdit() {
    if (!props.selected) return;
    loading.value = true;
    const formEl = document.getElementById("form-role");
    if(formEl) block(formEl);
    try {
        const { data } = await ApiService.get(`/master/roles/${props.selected}`);
        if (!data.permissions) {
            data.permissions = [];
        }
        formData.value = data;
    } catch (error: any) {
        toast.error(error.response?.data?.message || "Gagal memuat data role.");
    } finally {
        if(formEl) unblock(formEl);
        loading.value = false;
    }
}

async function submit() {
    if (!formRef.value) return;
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    loading.value = true;
    const formEl = document.getElementById("form-role");
    if(formEl) block(formEl);
    try {
        if (props.selected) {
            await ApiService.put(`/master/roles/${props.selected}`, formData.value);
        } else {
            // URL untuk store disesuaikan, biasanya tidak ada /store di apiResource
            await ApiService.post("/master/roles", formData.value);
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
        if(formEl) unblock(formEl);
        loading.value = false;
    }
}

onMounted(() => {
    if (props.selected) getEdit();
});

watch(() => props.selected, (newVal) => {
    if (newVal) {
        getEdit();
    } else {
        formData.value = { id: null, name: '', full_name: '', permissions: [] };
        formRef.value?.resetForm();
    }
});
</script>

<template>
    <VForm class="form card mb-10" @submit="submit" :validation-schema="formSchema" id="form-role" ref="formRef">
        <div class="card-header align-items-center">
            <h2 class="mb-0">{{ selected ? "Edit" : "Tambah" }} Role</h2>
            <button type="button" class="btn btn-sm btn-light-danger ms-auto" @click="emit('close')">
                Batal
                <i class="la la-times-circle p-0"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required">Nama</label>
                        <Field class="form-control form-control-lg form-control-solid" type="text" name="name" autocomplete="off" v-model="formData.name" placeholder="Masukkan Nama" />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block"><ErrorMessage name="name" /></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required">Nama Lengkap</label>
                        <Field class="form-control form-control-lg form-control-solid" name="full_name" autocomplete="off" v-model="formData.full_name" placeholder="Masukkan Nama Lengkap" />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block"><ErrorMessage name="full_name" /></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <template v-for="(permission) in permissions" :key="permission.value">
                        <div class="form-check mb-5">
                            <input class="form-check-input" type="checkbox" :value="permission.value" :id="permission.value" :checked="formData.permissions.includes(permission.value)" @change="handleCheck(permission, ($event.target as HTMLInputElement).checked)" />
                            <label class="form-check-label" :for="permission.value">{{ permission.text }}</label>
                        </div>

                        <template v-if="permission.children" v-for="(child) in permission.children" :key="child.value">
                            <div class="form-check mb-5" style="margin-left: 3rem">
                                <input class="form-check-input" type="checkbox" :value="child.value" :id="child.value" :checked="formData.permissions.includes(child.value)" @change="handleCheck(child, ($event.target as HTMLInputElement).checked)" />
                                <label class="form-check-label" :for="child.value">{{ child.text }}</label>
                            </div>

                            <template v-if="child.children" v-for="(grandChild) in child.children" :key="grandChild.value">
                                <div class="form-check mb-5" style="margin-left: 6rem">
                                    <input class="form-check-input" type="checkbox" :value="grandChild.value" :id="grandChild.value" :checked="formData.permissions.includes(grandChild.value)" @change="handleCheck(grandChild, ($event.target as HTMLInputElement).checked)" />
                                    <label class="form-check-label" :for="grandChild.value">{{ grandChild.text }}</label>
                                </div>

                                <template v-if="grandChild.children" v-for="(grandSon) in grandChild.children" :key="grandSon.value">
                                    <div class="form-check mb-5" style="margin-left: 9rem">
                                        <input class="form-check-input" type="checkbox" :value="grandSon.value" :id="grandSon.value" :checked="formData.permissions.includes(grandSon.value)" @change="handleCheck(grandSon, ($event.target as HTMLInputElement).checked)" />
                                        <label class="form-check-label" :for="grandSon.value">{{ grandSon.text }}</label>
                                    </div>
                                </template>
                            </template>
                        </template>
                    </template>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex">
            <button type="submit" class="btn btn-primary btn-sm ms-auto">
                Simpan
            </button>
        </div>
    </VForm>
</template>
