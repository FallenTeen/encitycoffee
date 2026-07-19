import { usePage } from '@inertiajs/react';
import { useOutlet } from '@/contexts/OutletContext';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Building2, Check } from 'lucide-react';
import { cn } from '@/lib/utils';

export function OutletSwitcher() {
    const { props } = usePage<any>();
    const { selectedOutlet, outlets, switchOutlet } = useOutlet();
    
    // Only show if user has multiple outlets
    if (outlets.length <= 1) {
        return null;
    }
    
    const handleValueChange = (value: string) => {
        const outletId = parseInt(value, 10);
        if (!isNaN(outletId)) {
            switchOutlet(outletId);
        }
    };
    
    return (
        <div className="flex items-center gap-2">
            <Building2 className="h-4 w-4 text-muted-foreground" />
            <Select
                value={selectedOutlet?.id?.toString() ?? ''}
                onValueChange={handleValueChange}
            >
                <SelectTrigger className="w-[180px] h-8 text-sm">
                    <SelectValue placeholder="Pilih Outlet" />
                </SelectTrigger>
                <SelectContent>
                    {outlets.map((outlet) => (
                        <SelectItem
                            key={outlet.id}
                            value={outlet.id.toString()}
                            className="text-sm"
                        >
                            <div className="flex items-center justify-between w-full">
                                <span className="truncate">
                                    {outlet.kode ? `${outlet.kode} - ` : ''}
                                    {outlet.nama}
                                </span>
                                {selectedOutlet?.id === outlet.id && (
                                    <Check className="h-4 w-4 ml-2 text-green-600" />
                                )}
                            </div>
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

// Compact version for inline use
export function OutletSwitcherCompact() {
    const { selectedOutlet, outlets, switchOutlet } = useOutlet();
    
    if (outlets.length <= 1) {
        return null;
    }
    
    return (
        <div className="flex items-center gap-2">
            <Building2 className="h-4 w-4 text-muted-foreground" />
            <select
                value={selectedOutlet?.id?.toString() ?? ''}
                onChange={(e) => {
                    const outletId = parseInt(e.target.value, 10);
                    if (!isNaN(outletId)) {
                        switchOutlet(outletId);
                    }
                }}
                className="h-8 px-3 pr-8 text-sm border rounded-md bg-white focus:outline-none focus:ring-2 focus:ring-primary"
            >
                {outlets.map((outlet) => (
                    <option key={outlet.id} value={outlet.id.toString()}>
                        {outlet.kode ? `${outlet.kode} - ` : ''}
                        {outlet.nama}
                    </option>
                ))}
            </select>
        </div>
    );
}
