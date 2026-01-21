interface SkeletonProductTableProps {
    count: number;
}

export default function SkeletonProductTable({
    count,
}: SkeletonProductTableProps) {
    const rows = Array.from({ length: Math.max(count, 1) }, (_, i) => i);

    return (
        <>
            {rows.map((i) => (
                <tr key={i} className="border-b last:border-0">
                    <td className="px-4 py-3">
                        <div className="h-3 w-16 animate-pulse rounded bg-muted" />
                    </td>
                    <td className="px-4 py-3">
                        <div className="h-10 w-10 animate-pulse rounded bg-muted" />
                    </td>
                    <td className="px-4 py-3">
                        <div className="mb-1 h-3 w-40 animate-pulse rounded bg-muted" />
                        <div className="h-3 w-24 animate-pulse rounded bg-muted/70" />
                    </td>
                    <td className="px-4 py-3">
                        <div className="h-3 w-24 animate-pulse rounded bg-muted" />
                    </td>
                    <td className="px-4 py-3">
                        <div className="ml-auto h-3 w-20 animate-pulse rounded bg-muted" />
                    </td>
                    <td className="px-4 py-3">
                        <div className="mx-auto h-3 w-16 animate-pulse rounded bg-muted" />
                    </td>
                    <td className="px-4 py-3">
                        <div className="mx-auto h-5 w-20 animate-pulse rounded-full bg-muted" />
                    </td>
                    <td className="px-4 py-3">
                        <div className="mx-auto flex gap-2">
                            <div className="h-3 w-10 animate-pulse rounded bg-muted" />
                            <div className="h-3 w-10 animate-pulse rounded bg-muted" />
                        </div>
                    </td>
                </tr>
            ))}
        </>
    );
}

