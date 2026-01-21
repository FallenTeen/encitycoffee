import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Clock, ChevronDown } from 'lucide-react';
import { cn, resolveUrl } from '@/lib/utils';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface RecentItem {
    href: string;
    title: string;
    icon?: string;
}

interface NavRecentProps {
    items: RecentItem[];
    isCollapsed: boolean;
}

export function NavRecent({ items, isCollapsed }: NavRecentProps) {
    const page = usePage();
    const [isExpanded, setIsExpanded] = useState(false);

    if (items.length === 0) return null;

    return (
        <div className="px-3 py-2 border-t">
            {/* Collapsible Header */}
            <button
                onClick={() => setIsExpanded(!isExpanded)}
                className={cn(
                    'w-full flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-md cursor-pointer transition-colors uppercase tracking-wider',
                    isExpanded
                        ? 'text-gray-900 bg-gray-50'
                        : 'text-gray-500 hover:bg-gray-50'
                )}
            >
                {!isCollapsed && (
                    <>
                        <span className="flex items-center gap-2">
                            <Clock className="h-4 w-4" />
                            Recent
                        </span>
                        <ChevronDown
                            className={cn(
                                'h-4 w-4 transition-transform duration-200',
                                isExpanded ? 'rotate-180' : ''
                            )}
                        />
                    </>
                )}
                {isCollapsed && (
                    <Clock className="h-4 w-4 mx-auto" />
                )}
            </button>

            {/* Collapsible Content */}
            {isExpanded && (
                <div className="mt-1 space-y-1">
                    {items.map((item) => {
                        const isActive = page.url.startsWith(resolveUrl(item.href));

                        const content = (
                            <Link
                                href={item.href}
                                className={cn(
                                    'flex items-center gap-3 px-3 py-2 text-sm rounded-md transition-all',
                                    isActive
                                        ? 'bg-primary-50 text-primary-600 font-medium'
                                        : 'text-gray-600 hover:ring-1 hover:ring-primary-200 hover:bg-gray-50'
                                )}
                            >
                                <Clock
                                    className={cn(
                                        'h-4 w-4 shrink-0',
                                        isActive ? 'text-primary-600' : 'text-gray-400'
                                    )}
                                    strokeWidth={isActive ? 2.5 : 2}
                                />
                                {!isCollapsed && (
                                    <span className="truncate text-xs">{item.title}</span>
                                )}
                            </Link>
                        );

                        if (isCollapsed) {
                            return (
                                <TooltipProvider key={item.href} delayDuration={300}>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            {content}
                                        </TooltipTrigger>
                                        <TooltipContent side="right">
                                            <p>{item.title}</p>
                                            <p className="text-xs text-muted-foreground mt-1">
                                                Recently viewed
                                            </p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            );
                        }

                        return <div key={item.href}>{content}</div>;
                    })}
                </div>
            )}
        </div>
    );
}