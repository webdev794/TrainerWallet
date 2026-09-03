import { Head } from '@inertiajs/react';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import ClientPortalLayout from '@/layouts/client-portal-layout';
import { formatDateTime } from '@/lib/format';

type SessionRow = {
    id: number;
    scheduled_at: string;
    duration_minutes: number;
    status: string;
    status_label: string;
    trainer_name: string;
    invoiced: boolean;
};

const statusVariant: Record<
    string,
    'secondary' | 'success' | 'warning' | 'outline'
> = {
    scheduled: 'secondary',
    completed: 'success',
    postponed: 'warning',
    cancelled: 'outline',
    no_show: 'warning',
};

export default function PortalSessions({
    sessions,
}: {
    sessions: SessionRow[];
}) {
    return (
        <ClientPortalLayout title="Your sessions">
            <Head title="Sessions" />

            <Card>
                <CardContent className="p-0">
                    {sessions.length === 0 ? (
                        <p className="text-muted-foreground p-10 text-center text-sm">
                            No sessions on record yet.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>When</TableHead>
                                    <TableHead>Trainer</TableHead>
                                    <TableHead>Duration</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Billing</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sessions.map((row) => (
                                    <TableRow key={row.id}>
                                        <TableCell>
                                            {formatDateTime(row.scheduled_at)}
                                        </TableCell>
                                        <TableCell>
                                            {row.trainer_name}
                                        </TableCell>
                                        <TableCell>
                                            {row.duration_minutes} min
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[row.status] ??
                                                    'secondary'
                                                }
                                            >
                                                {row.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {row.status === 'completed' ? (
                                                <Badge
                                                    variant={
                                                        row.invoiced
                                                            ? 'outline'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {row.invoiced
                                                        ? 'Invoiced'
                                                        : 'Not yet invoiced'}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground text-xs">
                                                    —
                                                </span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </ClientPortalLayout>
    );
}
