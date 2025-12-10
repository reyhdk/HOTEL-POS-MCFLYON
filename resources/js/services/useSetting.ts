import { useQuery } from "@tanstack/vue-query";
import axios from "@/libs/axios";

export function useSetting(options = {}) {
    return useQuery({
        queryKey: ["app", "setting"],
        // ✅ PERBAIKAN: Ganti dari /setting menjadi /settings
        queryFn: () => axios.get("/settings").then((res) => res.data),
        ...options,
    });
}
