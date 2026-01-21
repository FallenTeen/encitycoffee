import { useState, useEffect, useCallback } from 'react';

interface SidebarState {
    expandedGroups: string[];
    favorites: string[];
    recentItems: Array<{ href: string; title: string; icon?: string }>;
    isCollapsed: boolean;
}

export function useSidebarState(userId?: number) {
    const storageKey = (key: string) => userId ? `${key}_${userId}` : key;

    // Initialize state from localStorage
    const [expandedGroups, setExpandedGroups] = useState<string[]>(() => {
        if (typeof window === 'undefined') return [];
        const saved = localStorage.getItem(storageKey('sidebar_expanded_groups'));
        return saved ? JSON.parse(saved) : [];
    });

    const [favorites, setFavorites] = useState<string[]>(() => {
        if (typeof window === 'undefined') return [];
        const saved = localStorage.getItem(storageKey('sidebar_favorites'));
        return saved ? JSON.parse(saved) : [];
    });

    const [recentItems, setRecentItems] = useState<Array<{ href: string; title: string; icon?: string }>>(() => {
        if (typeof window === 'undefined') return [];
        const saved = localStorage.getItem(storageKey('sidebar_recent'));
        return saved ? JSON.parse(saved) : [];
    });

    const [isCollapsed, setIsCollapsed] = useState(() => {
        if (typeof window === 'undefined') return false;
        const saved = localStorage.getItem('sidebar_collapsed');
        return saved === 'true';
    });

    // Persist to localStorage
    useEffect(() => {
        localStorage.setItem(storageKey('sidebar_expanded_groups'), JSON.stringify(expandedGroups));
    }, [expandedGroups, userId]);

    useEffect(() => {
        localStorage.setItem(storageKey('sidebar_favorites'), JSON.stringify(favorites));
    }, [favorites, userId]);

    useEffect(() => {
        localStorage.setItem(storageKey('sidebar_recent'), JSON.stringify(recentItems));
    }, [recentItems, userId]);

    useEffect(() => {
        localStorage.setItem('sidebar_collapsed', String(isCollapsed));
    }, [isCollapsed]);

    // Toggle group (accordion mode - only one open at a time)
    const toggleGroup = useCallback((groupTitle: string) => {
        setExpandedGroups((prev) => {
            if (prev.includes(groupTitle)) {
                return prev.filter(g => g !== groupTitle);
            }
            return [groupTitle]; // Only keep the new one expanded
        });
    }, []);

    // Expand specific group
    const expandGroup = useCallback((groupTitle: string) => {
        setExpandedGroups([groupTitle]);
    }, []);

    // Toggle favorite
    const toggleFavorite = useCallback((href: string) => {
        setFavorites((prev) => {
            if (prev.includes(href)) {
                return prev.filter(f => f !== href);
            }
            if (prev.length >= 5) {
                return prev; // Max 5 favorites
            }
            return [...prev, href];
        });
    }, []);

    // Add to recent items
    const addToRecent = useCallback((item: { href: string; title: string; icon?: string }) => {
        setRecentItems((prev) => {
            // Remove if already exists
            const filtered = prev.filter(i => i.href !== item.href);
            // Add to beginning, keep max 3
            return [item, ...filtered].slice(0, 3);
        });
    }, []);

    // Toggle sidebar collapsed
    const toggleCollapsed = useCallback(() => {
        setIsCollapsed(prev => !prev);
    }, []);

    return {
        expandedGroups,
        favorites,
        recentItems,
        isCollapsed,
        toggleGroup,
        expandGroup,
        toggleFavorite,
        addToRecent,
        toggleCollapsed,
    };
}