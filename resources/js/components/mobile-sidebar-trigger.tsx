import { Menu } from 'lucide-react';
import { Button } from './ui/button';

interface MobileSidebarTriggerProps {
    onClick: () => void;
}

export function MobileSidebarTrigger({ onClick }: MobileSidebarTriggerProps) {
    return (
        <Button
            variant="ghost"
            size="icon"
            className="md:hidden"
            onClick={onClick}
            aria-label="Toggle menu"
        >
            <Menu className="h-5 w-5" />
        </Button>
    );
}