import { defineStore } from "pinia";
import { ref } from "vue";
import ApiService from "@/core/services/ApiService";

export const useCheckInStore = defineStore(
    "checkIn",
    () => {
        // ------------------------------------------
        // 1. STATE
        // ------------------------------------------
        const isLoading = ref<boolean>(false);
        const isCheckedIn = ref<boolean>(false);

        const roomNumber = ref<string>("");
        const guestName = ref<string>("");
        const bookingId = ref<number | null>(null);

        // [BARU] Menambahkan variable untuk tanggal Check In & Out
        const checkInDate = ref<string | null>(null);
        const checkOutDate = ref<string | null>(null);

        // ------------------------------------------
        // 2. ACTIONS
        // ------------------------------------------

        function clearState() {
            isCheckedIn.value = false;
            roomNumber.value = "";
            guestName.value = "";
            bookingId.value = null;
            // Reset tanggal saat clear
            checkInDate.value = null;
            checkOutDate.value = null;
        }

        async function fetchStatus() {
            isLoading.value = true;
            try {
                // Panggil API yang sudah kita update tadi
                const response = await ApiService.get("/user/check-in-status");
                const data = response.data;

                if (data.is_active) {
                    isCheckedIn.value = true;
                    bookingId.value = data.booking_id;
                    roomNumber.value = data.room_number;
                    guestName.value = data.guest_name;

                    // [PENTING] Simpan data tanggal dari Backend ke State
                    checkInDate.value = data.check_in_date;
                    checkOutDate.value = data.check_out_date;
                } else {
                    clearState();
                }
            } catch (error) {
                console.error("Gagal cek status check-in:", error);
                clearState();
            } finally {
                isLoading.value = false;
            }
        }

        // ------------------------------------------
        // 3. RETURN
        // ------------------------------------------
        return {
            isLoading,
            isCheckedIn,
            roomNumber,
            guestName,
            bookingId,
            checkInDate, // Jangan lupa di-export
            checkOutDate, // Jangan lupa di-export
            fetchStatus,
            clearState,
        };
    },
    {
        persist: true, // Data tetap ada walau halaman di-refresh
    }
);
