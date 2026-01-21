import { cn } from '@/lib/utils';

interface SidebarBadgeProps {
    label?: string;
    count?: number;
    variant?: 'default' | 'warning' | 'destructive';
    className?: string;
}

export function SidebarBadge({ label, count, variant = 'default', className }: SidebarBadgeProps) {
    const displayText = count !== undefined ? String(count) : label;
    
    if (!displayText) return null;

    return (
        <span
            className={cn(
                'ml-auto px-2 py-0.5 text-xs font-medium rounded-full shrink-0',
                {
                    'bg-gray-100 text-gray-700': variant === 'default',
                    'bg-yellow-100 text-yellow-700': variant === 'warning',
                    'bg-red-100 text-red-700': variant === 'destructive',
                },
                className
            )}
        >
            {displayText}
        </span>
    );
}