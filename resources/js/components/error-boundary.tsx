import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { RotateCcw, TriangleAlert } from 'lucide-react';
import { Component, type ReactNode } from 'react';

export class ErrorBoundary extends Component<
    { children: ReactNode },
    { hasError: boolean; error: unknown }
> {
    state = { hasError: false, error: null };

    static getDerivedStateFromError(error: unknown) {
        return { hasError: true, error };
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="flex min-h-[60vh] items-center justify-center p-6">
                    <div className="w-full max-w-2xl space-y-4">
                        <Alert variant="destructive">
                            <TriangleAlert />
                            <AlertTitle>Terjadi kesalahan</AlertTitle>
                            <AlertDescription>
                                Halaman gagal ditampilkan. Coba muat ulang
                                halaman.
                            </AlertDescription>
                        </Alert>
                        <div className="flex justify-end">
                            <Button
                                type="button"
                                onClick={() => {
                                    if (typeof window !== 'undefined') {
                                        window.location.reload();
                                    }
                                }}
                            >
                                <RotateCcw />
                                Muat ulang
                            </Button>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

