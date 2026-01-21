import { Link, usePage } from '@inertiajs/react';
import { Star } from 'lucide-react';
import { cn, resolveUrl } from '@/lib/utils';
import type { NavItem } from '@/types';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface NavFavoritesProps {
    items: NavItem[];
    favorites: string[];
    isCollapsed: boolean;
}

export function NavFavorites({ items, favorites, isCollapsed }: NavFavoritesProps) {
    const page = usePage();

    if (favorites.length === 0) return null;

    const favoriteItems = items.filter(item =>
        favorites.includes(String(item.href))
    );

    if (favoriteItems.length === 0) return null;

    return (
        <div className="px-3 py-2">
            {!isCollapsed && (
                <div className="mb-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    ⭐ Favorites
                </div>
            )}
            <div className="space-y-1">
                {favoriteItems.map((item) => {
                    const isActive = page.url.startsWith(resolveUrl(item.href));
                    const Icon = item.icon;

                    const content = (
                        <Link
                            href={item.href}
                            className={cn(
                                'flex items-center gap-3 px-3 py-2.5 text-sm rounded-md transition-all',
                                isActive
                                    ? 'bg-primary-50 text-primary-600 font-medium'
                                    : 'text-gray-600 hover:ring-1 hover:ring-primary-200 hover:bg-gray-50'
                            )}
                        >
                            {Icon && (
                                <Icon
                                    className={cn(
                                        'h-5 w-5 shrink-0',
                                        isActive ? 'text-primary-600' : 'text-gray-500'
                                    )}
                                    strokeWidth={isActive ? 2.5 : 2}
                                />
                            )}
                            {!isCollapsed && (
                                <span className="truncate">{item.title}</span>
                            )}
                        </Link>
                    );

                    if (isCollapsed) {
                        return (
                            <TooltipProvider key={String(item.href)} delayDuration={300}>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        {content}
                                    </TooltipTrigger>
                                    <TooltipContent side="right">
                                        <p>{item.title}</p>
                                        {item.description && (
                                            <p className="text-xs text-muted-foreground mt-1">
                                                {item.description}
                                            </p>
                                        )}
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        );
                    }

                    return <div key={String(item.href)}>{content}</div>;
                })}
            </div>
        </div>
    );
}
