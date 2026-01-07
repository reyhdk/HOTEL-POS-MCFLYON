const ID_TOKEN_KEY = "id_token" as string;

/**
 * @description get token from localStorage
 */
export const getToken = (): string | null => {
    const token = window.localStorage.getItem(ID_TOKEN_KEY);

    // ✅ Log untuk debugging (bisa dimatikan di production)
    if (import.meta.env.DEV) {
        console.log(
            "🔍 getToken:",
            token ? token.substring(0, 20) + "..." : null
        );
    }

    return token;
};

/**
 * @description save token into localStorage
 * @param token: string
 */
export const saveToken = (token: string): void => {
    console.log("💾 Saving token to localStorage...");

    try {
        window.localStorage.setItem(ID_TOKEN_KEY, token);

        // ✅ Verifikasi token tersimpan
        const savedToken = window.localStorage.getItem(ID_TOKEN_KEY);

        if (savedToken === token) {
            console.log(
                "✅ Token saved successfully:",
                token.substring(0, 20) + "..."
            );
        } else {
            console.error("❌ CRITICAL: Token not saved correctly!");
            console.error("Expected:", token.substring(0, 20) + "...");
            console.error(
                "Got:",
                savedToken ? savedToken.substring(0, 20) + "..." : null
            );
        }
    } catch (error) {
        console.error(
            "❌ CRITICAL: Failed to save token to localStorage:",
            error
        );
    }
};

/**
 * @description remove token from localStorage
 */
export const destroyToken = (): void => {
    console.log("🗑️ Removing token from localStorage...");

    window.localStorage.removeItem(ID_TOKEN_KEY);

    // ✅ Verifikasi token terhapus
    const remainingToken = window.localStorage.getItem(ID_TOKEN_KEY);

    if (!remainingToken) {
        console.log("✅ Token removed successfully");
    } else {
        console.error("❌ CRITICAL: Token still exists after removal!");
    }
};

export default { getToken, saveToken, destroyToken };
