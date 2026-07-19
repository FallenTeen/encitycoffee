import { usePage } from '@inertiajs/react';
import { getImageUrl as getImageUrlUtil } from '@/lib/utils';

/**
 * Hook untuk mengakses konfigurasi storage.
 * Mengambil base URL dari server-side configuration.
 */
export function useStorage() {
    const { props } = usePage();

    // Ambil storage config dari props (diset oleh HandleInertiaRequests)
    const storageConfig = (props as any).storage;
    const storageBaseUrl = storageConfig?.base_url || '/storage';

    /**
     * Generate full URL untuk image dari image_path.
     * Compatible dengan API response yang sudah ada.
     *
     * @param imagePath - Path gambar dari database (e.g., 'foto-products/image.jpg')
     * @returns Full URL untuk gambar
     */
    const getImageUrl = (imagePath?: string | null): string | null => {
        return getImageUrlUtil(imagePath, storageBaseUrl);
    };

    /**
     * Generate full URL untuk image dari API response yang mungkin sudah contain image_url.
     * Jika sudah ada full URL, return as-is.
     *
     * @param imageUrlOrPath - URL lengkap atau path dari API
     * @returns Full URL
     */
    const resolveImageUrl = (imageUrlOrPath?: string | null): string | null => {
        if (!imageUrlOrPath) return null;

        // Jika sudah full URL (http/https), return as-is
        if (imageUrlOrPath.startsWith('http://') || imageUrlOrPath.startsWith('https://')) {
            return imageUrlOrPath;
        }

        // Jika path relatif, construct URL
        return getImageUrl(imageUrlOrPath);
    };

    return {
        storageBaseUrl,
        getImageUrl,
        resolveImageUrl,
        config: storageConfig,
    };
}

/**
 * Helper function untuk generate image URL.
 * Bisa digunakan di luar React components.
 *
 * @param imagePath - Path gambar
 * @param baseUrl - Base URL (optional, akan diambil dari window jika tidak disediakan)
 */
export function getStorageImageUrl(
    imagePath: string | null | undefined,
    baseUrl?: string
): string | null {
    return getImageUrlUtil(imagePath, baseUrl);
}
