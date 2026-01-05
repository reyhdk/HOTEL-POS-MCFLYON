export interface Setting {
    id?: number;
    uuid?: string;
    app: string;
    description: string;
    check_in_time: string;
    check_out_time: string;
    bg_auth: string | null;
    bg_landing: string | null;
    telepon?: string;
    alamat?: string;
    dinas?: string;
    pemerintah?: string;
    email?: string;
    logo: File | string | null;
    banner?: File | string | null;
}
