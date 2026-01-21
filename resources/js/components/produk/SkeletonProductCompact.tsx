interface SkeletonProductCompactProps {
    count: number;
}

export default function SkeletonProductCompact({
    count,
}: SkeletonProductCompactProps) {
    const items = Array.from({ length: Math.max(count, 1) }, (_, i) => i);

    return (
        <>
            {items.map((i) => (
                <div
                    key={i}
                    className="flex items-center justify-between rounded-lg border bg-card p-3"
                >
                    <div className="flex items-center gap-3">
                        <div className="h-6 w-16 animate-pulse rounded-full bg-muted" />
                        <div>
                            <div className="mb-1 h-4 w-40 animate-pulse rounded bg-muted" />
                            <div className="flex gap-2">
                                <div className="h-3 w-20 animate-pulse rounded bg-muted" />
                                <div className="h-3 w-12 animate-pulse rounded bg-muted" />
                                <div className="h-3 w-24 animate-pulse rounded bg-muted" />
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="h-4 w-20 animate-pulse rounded bg-muted" />
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

