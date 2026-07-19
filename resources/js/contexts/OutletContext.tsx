import { usePage } from '@inertiajs/react';
import { useState, useEffect, useCallback } from 'react';

export interface Outlet {
    id: number;
    nama: string;
    kode?: string;
}

interface OutletContextType {
    selectedOutlet: Outlet | null;
    outlets: Outlet[];
    isLoading: boolean;
    setSelectedOutlet: (outlet: Outlet | null) => void;
    switchOutlet: (outletId: number) => void;
    clearSelectedOutlet: () => void;
}

const STORAGE_KEY = 'selected_outlet_id';

// Simple hook that manages outlet state
function useOutletState(outlets: Outlet[]): {
    selectedOutlet: Outlet | null;
    setSelectedOutlet: (outlet: Outlet | null) => void;
} {
    // Initialize from localStorage
    const [selectedOutlet, setSelectedOutletState] = useState<Outlet | null>(() => {
        if (typeof window === 'undefined') return outlets.length > 0 ? outlets[0] : null;
        
        try {
            const storedId = localStorage.getItem(STORAGE_KEY);
            if (storedId) {
                const parsedId = parseInt(storedId, 10);
                const found = outlets.find(o => o.id === parsedId);
                if (found) return found;
            }
        } catch {
            // Ignore localStorage errors
        }
        
        return outlets.length > 0 ? outlets[0] : null;
    });
    
    // Update when outlets change
    useEffect(() => {
        if (outlets.length > 0 && !selectedOutlet) {
            setSelectedOutletState(outlets[0]);
        }
    }, [outlets, selectedOutlet]);
    
    // Persist to localStorage
    const setSelectedOutlet = useCallback((outlet: Outlet | null) => {
        setSelectedOutletState(outlet);
        
        if (typeof window !== 'undefined') {
            try {
                if (outlet) {
                    localStorage.setItem(STORAGE_KEY, String(outlet.id));
                } else {
                    localStorage.removeItem(STORAGE_KEY);
                }
            } catch {
                // Ignore localStorage errors
            }
        }
    }, []);
    
    return { selectedOutlet, setSelectedOutlet };
}

// Main hook - call this inside components wrapped by Inertia
export function useOutlet(): OutletContextType {
    const { props } = usePage<any>();
    
    // Get outlets from page props (set by backend HandleInertiaRequests)
    const userOutlets = (props?.auth?.user?.cabang as Outlet[] | undefined) || [];
    const { selectedOutlet, setSelectedOutlet } = useOutletState(userOutlets);
    
    const switchOutlet = useCallback((outletId: number) => {
        const outlet = userOutlets.find(o => o.id === outletId);
        if (outlet) {
            setSelectedOutlet(outlet);
        }
    }, [userOutlets, setSelectedOutlet]);
    
    const clearSelectedOutlet = useCallback(() => {
        setSelectedOutlet(null);
    }, [setSelectedOutlet]);
    
    return {
        selectedOutlet,
        outlets: userOutlets,
        isLoading: false,
        setSelectedOutlet,
        switchOutlet,
        clearSelectedOutlet,
    };
}

// Hook for pages that need the selected outlet ID
export function useOutletId(): number | null {
    const { selectedOutlet } = useOutlet();
    return selectedOutlet?.id ?? null;
}

// Hook to force refresh when outlet changes
export function useOutletRefresh(): () => void {
    const { selectedOutlet } = useOutlet();
    
    // Access selectedOutlet to trigger re-renders when it changes
    void selectedOutlet;
    
    return () => {
        if (typeof window !== 'undefined') {
            window.location.reload();
        }
    };
}
