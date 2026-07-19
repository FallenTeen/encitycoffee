import { cn } from '@/lib/utils';
import { useState, useMemo } from 'react';
import { ImageOff } from 'lucide-react';
import { useStorage } from '@/hooks/useStorage';

interface SafeImageProps {
    src?: string | null;
    alt: string;
    className?: string;
    fallbackClassName?: string;
    fallbackText?: string;
    showIcon?: boolean;
    onErrorFallback?: (src: string) => void;
    /**
     * Jika true, akan resolve src menggunakan storage base URL.
     * Jika false, gunakan src langsung (untuk URL lengkap dari API).
     * Default: false
     */
    resolveUrl?: boolean;
}

/**
 * Image component dengan error handling dan fallback.
 * Menampilkan placeholder dengan ikon dan teks jika gambar gagal dimuat.
 *
 * Mendukung auto-resolution URL menggunakan konfigurasi storage dari server.
 */
export default function SafeImage({
    src,
    alt,
    className,
    fallbackClassName,
    fallbackText,
    showIcon = true,
    onErrorFallback,
    resolveUrl = false,
}: SafeImageProps) {
    const [hasError, setHasError] = useState(false);
    const { resolveImageUrl } = useStorage();

    // Resolve URL jika diperlukan
    const resolvedSrc = useMemo(() => {
        if (!src) return null;
        if (resolveUrl) {
            return resolveImageUrl(src);
        }
        // Check if it's already a full URL
        if (src.startsWith('http://') || src.startsWith('https://')) {
            return src;
        }
        // For relative paths, prefix with storage base
        return resolveImageUrl(src);
    }, [src, resolveUrl, resolveImageUrl]);

    const handleError = () => {
        if (!hasError) {
            setHasError(true);
            if (onErrorFallback && resolvedSrc) {
                onErrorFallback(resolvedSrc);
            }
        }
    };

    // Jika src tidak ada atau terjadi error, tampilkan fallback
    if (!resolvedSrc || hasError) {
        return (
            <div
                className={cn(
                    'flex flex-col items-center justify-center rounded-md bg-muted/50 text-muted-foreground',
                    fallbackClassName
                )}
                role="img"
                aria-label={fallbackText || `Gambar tidak tersedia: ${alt}`}
            >
                {showIcon && (
                    <ImageOff className="h-6 w-6 opacity-50" />
                )}
                {(fallbackText || alt) && (
                    <span className="mt-1 text-xs">
                        {fallbackText || (alt ? alt.substring(0, 20) + (alt.length > 20 ? '...' : '') : 'No image')}
                    </span>
                )}
            </div>
        );
    }

    return (
        <img
            src={resolvedSrc}
            alt={alt}
            className={className}
            onError={handleError}
            loading="lazy"
        />
    );
}

/**
 * Hook untuk track broken images
 */
export function useBrokenImageTracker() {
    const [brokenImages, setBrokenImages] = useState<Set<string>>(new Set());

    const trackBrokenImage = (src: string) => {
        setBrokenImages(prev => {
            const next = new Set(prev);
            next.add(src);
            return next;
        });
    };

    const isImageBroken = (src: string) => brokenImages.has(src);

    const clearBrokenImages = () => setBrokenImages(new Set());

    return {
        brokenImages,
        trackBrokenImage,
        isImageBroken,
        clearBrokenImages,
    };
}
