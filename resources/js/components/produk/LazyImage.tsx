import { cn } from '@/lib/utils';
import { useEffect, useRef, useState } from 'react';

interface LazyImageProps {
    src?: string | null;
    alt: string;
    className?: string;
    placeholderClassName?: string;
}

export default function LazyImage({
    src,
    alt,
    className,
    placeholderClassName,
}: LazyImageProps) {
    const [isVisible, setIsVisible] = useState(false);
    const [isLoaded, setIsLoaded] = useState(false);
    const [hasError, setHasError] = useState(false);
    const containerRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        if (!src) return;

        if (
            typeof window === 'undefined' ||
            !(window as any).IntersectionObserver
        ) {
            setIsVisible(true);
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        setIsVisible(true);
                        observer.disconnect();
                    }
                });
            },
            {
                root: null,
                rootMargin: '50px',
                threshold: 0.1,
            },
        );

        if (containerRef.current) {
            observer.observe(containerRef.current);
        }

        return () => {
            observer.disconnect();
        };
    }, [src]);

    if (!src || hasError) {
        return (
            <div
                ref={containerRef}
                className={cn(
                    'flex items-center justify-center rounded bg-muted text-xs text-muted-foreground',
                    className,
                    placeholderClassName,
                )}
            >
                No Image
            </div>
        );
    }

    return (
        <div
            ref={containerRef}
            className={cn(
                'relative overflow-hidden rounded bg-muted',
                className,
            )}
        >
            {!isLoaded && (
                <div
                    className={cn(
                        'absolute inset-0 animate-pulse bg-muted',
                        placeholderClassName,
                    )}
                />
            )}
            {isVisible && (
                <img
                    src={src}
                    alt={alt}
                    onLoad={() => setIsLoaded(true)}
                    onError={() => setHasError(true)}
                    className={cn(
                        'h-full w-full object-cover transition-opacity duration-300',
                        isLoaded ? 'opacity-100' : 'opacity-0',
                    )}
                />
            )}
        </div>
    );
}

