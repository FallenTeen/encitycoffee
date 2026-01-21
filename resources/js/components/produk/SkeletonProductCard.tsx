interface SkeletonProductCardProps {
    count: number;
}

export default function SkeletonProductCard({
    count,
}: SkeletonProductCardProps) {
    const items = Array.from({ length: Math.max(count, 1) }, (_, i) => i);

    return (
        <>
            {items.map((i) => (
                <div
                    key={i}
                    className="group rounded-lg border bg-card p-4 shadow-sm"
                >
                    <div className="flex gap-3">
                        <div className="h-20 w-20 animate-pulse rounded bg-muted" />
                        <div className="flex-1 space-y-2">
                            <div className="h-4 w-40 animate-pulse rounded bg-muted" />
                            <div className="flex gap-2">
                                <div className="h-4 w-16 animate-pulse rounded bg-muted" />
                                <div className="h-4 w-16 animate-pulse rounded bg-muted" />
                            </div>
                            <div className="h-5 w-24 animate-pulse rounded bg-muted" />
                        </div>
                    </div>
                    <div className="mt-3 flex items-center justify-between border-t pt-3">
                        <div className="h-3 w-20 animate-pulse rounded bg-muted" />
                        <div className="flex gap-2">
                            <div className="h-8 w-16 animate-pulse rounded bg-muted" />
                            <div className="h-8 w-16 animate-pulse rounded bg-muted" />
                        </div>
                    </div>
                </div>
            ))}
        </>
    );
}

