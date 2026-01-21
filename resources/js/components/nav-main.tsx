import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Star } from 'lucide-react';
import { cn, resolveUrl } from '@/lib/utils';
import type { NavGroup, NavItem } from '@/types';
import { SidebarBadge } from './sidebar-badge';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface NavMainProps {
    groups: NavGroup[];
    expandedGroups: string[];
    favorites: string[];
    isCollapsed: boolean;
    onToggleGroup: (groupTitle: string) => void;
    onToggleFavorite: (href: string) => void;
}

export function NavMain({
    groups,
    expandedGroups,
    favorites,
    isCollapsed,
    onToggleGroup,
    onToggleFavorite,
}: NavMainProps) {
    const page = usePage();

    return (
        <div className="px-3 py-2 space-y-4">
            {groups.map((group) => {
                const isExpanded = expandedGroups.includes(group.title);
                const hasActiveItem = group.items.some(item =>
                    page.url.startsWith(resolveUrl(item.href))
                );

                return (
                    <div key={group.title}>
                        {/* Group Header */}
                        <button
                            onClick={() => onToggleGroup(group.title)}
                            className={cn(
                                'w-full flex items-center justify-between px-3 py-2 text-sm font-semibold rounded-md cursor-pointer transition-colors',
                                isExpanded || hasActiveItem
                                    ? 'text-gray-900 bg-gray-50'
                                    : 'text-gray-700 hover:bg-gray-50'
                            )}
                        >
                            {!isCollapsed && (
                                <>
                                    <span className="text-xs uppercase tracking-wider">
                                        {group.title}
                                    </span>
                                    <ChevronDown
                                        className={cn(
                                            'h-4 w-4 transition-transform duration-200',
                                            isExpanded ? 'rotate-180' : ''
                                        )}
                                    />
                                </>
                            )}
                        </button>

                        {/* Group Items */}
                        {(isExpanded || isCollapsed) && (
                            <div
                                className={cn(
                                    'mt-1 space-y-1',
                                    !isCollapsed && 'pl-0'
                                )}
                            >
                                {group.items.map((item) => {
                                    const isActive = page.url.startsWith(resolveUrl(item.href));
                                    const isFavorite = favorites.includes(String(item.href));
                                    const Icon = item.icon;

                                    const menuContent = (
                                        <div className="relative group/item">
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
                                                    <>
                                                        <span className="flex-1 truncate">{item.title}</span>
                                                        {item.badge && (
                                                            <SidebarBadge {...item.badge} />
                                                        )}
                                                    </>
                                                )}
                                            </Link>

                                            {/* Favorite Star Button */}
                                            {!isCollapsed && (
                                                <button
                                                    onClick={(e) => {
                                                        e.preventDefault();
                                                        e.stopPropagation();
                                                        onToggleFavorite(String(item.href));
                                                    }}
                                                    className={cn(
                                                        'absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded opacity-0 group-hover/item:opacity-100 transition-opacity',
                                                        isFavorite && 'opacity-100'
                                                    )}
                                                    title={isFavorite ? 'Remove from favorites' : 'Add to favorites'}
                                                >
                                                    <Star
                                                        className={cn(
                                                            'h-4 w-4',
                                                            isFavorite
                                                                ? 'fill-yellow-400 text-yellow-400'
                                                                : 'text-gray-400 hover:text-yellow-400'
                                                        )}
                                                    />
                                                </button>
                                            )}
                                        </div>
                                    );

                                    if (isCollapsed) {
                                        return (
                                            <TooltipProvider key={String(item.href)} delayDuration={300}>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        {menuContent}
                                                    </TooltipTrigger>
                                                    <TooltipContent side="right" className="flex flex-col gap-1">
                                                        <p className="font-medium">{item.title}</p>
                                                        {item.description && (
                                                            <p className="text-xs text-muted-foreground">
                                                                {item.description}
                                                            </p>
                                                        )}
                                                        {item.badge && (
                                                            <div className="flex items-center gap-2 mt-1">
                                                                <SidebarBadge {...item.badge} />
                                                            </div>
                                                        )}
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        );
                                    }

                                    return <div key={String(item.href)}>{menuContent}</div>;
                                })}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
