import { InertiaLinkProps } from '@inertiajs/react';
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function isSameUrl(
    url1: NonNullable<InertiaLinkProps['href']>,
    url2: NonNullable<InertiaLinkProps['href']>,
) {
    return resolveUrl(url1) === resolveUrl(url2);
}

export function resolveUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * Generate full image URL from image_path.
 * Handles both absolute URLs and relative paths.
 *
 * @param imagePath - The storage path (e.g., 'foto-products/image.jpg')
 * @param storageBaseUrl - Optional custom storage base URL
 * @returns Full URL to the image or null if no path provided
 */
export function getImageUrl(
    imagePath: string | null | undefined,
    storageBaseUrl?: string | null
): string | null {
    if (!imagePath) return null;

    // If it's already a full URL (starts with http/https), return as-is
    if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
        return imagePath;
    }

    // Remove leading slash if present
    const cleanPath = imagePath.replace(/^\//, '');

    // Use provided base URL or construct from window location
    if (storageBaseUrl) {
        return `${storageBaseUrl.replace(/\/$/, '')}/${cleanPath}`;
    }

    // Fallback: use /storage/ path (assumes symlink exists)
    return `/storage/${cleanPath}`;
}

/**
 * Check if image file exists by trying to load it.
 * Returns a promise that resolves to boolean.
 */
export function checkImageExists(url: string | null): Promise<boolean> {
    if (!url) return Promise.resolve(false);

    return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => resolve(true);
        img.onerror = () => resolve(false);
        img.src = url;
    });
}
