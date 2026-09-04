import { Head, Link, router } from '@inertiajs/react';

import { ConfirmDialog } from '@/components/confirm-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { formatDateTime, formatMoney } from '@/lib/format';
import * as portal from '@/routes/portal';
import * as portalBookings from '@/routes/portal/bookings';

type BookingRow = {
    id: number;
    service_name: string;
    trainer_name: string;
    amount: string;
    currency: string;
    scheduled_at: string | null;
    status: string;
    status_label: string;
    invoice_number: string | null;
    invoice_status: string | null;
    invoice_token: string | null;
};

const variant: Record<string, 'secondary' | 'success' | 'outline'> = {
    confirmed: 'secondary',
    completed: 'success',
    cancelled: 'outline',
};

export default function PortalBookings({
    bookings,
}: {
    bookings: BookingRow[];
}) {
    return (
        <ClientPortalLayout title="Your bookings">
            <Head title="Bookings" />

            <div className="mb-4">
                <Button asChild size="sm">
                    <Link href={portal.book().url}>Book another</Link>
                </Button>
            </div>

            <Card>
                <CardContent className="p-0">
                    {bookings.length === 0 ? (
                        <p className="text-muted-foreground p-10 text-center text-sm">
                            No bookings yet.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Service</TableHead>
                                    <TableHead>Trainer</TableHead>
                                    <TableHead>When</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Invoice</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {bookings.map((booking) => (
                                    <TableRow key={booking.id}>
                                        <TableCell className="font-medium">
                                            {booking.service_name}
                                        </TableCell>
                                        <TableCell>
                                            {booking.trainer_name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {booking.scheduled_at
                                                ? formatDateTime(
                                                      booking.scheduled_at,
                                                  )
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                booking.amount,
                                                booking.currency,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    variant[booking.status] ??
                                                    'secondary'
                                                }
                                            >
                                                {booking.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {booking.invoice_token ? (
                                                <Link
                                                    href={`/i/${booking.invoice_token}`}
                                                    className="text-primary text-sm underline"
                                                >
                                                    {booking.invoice_number}
                                                    {booking.invoice_status
                                                        ? ` (${booking.invoice_status})`
                                                        : ''}
                                                </Link>
                                            ) : (
                                                '—'
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {booking.status === 'confirmed' && (
                                                <ConfirmDialog
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                        >
                                                            Cancel
                                                        </Button>
                                                    }
                                                    title="Cancel this booking?"
                                                    description="Your trainer will be notified. Any unpaid invoice stays open — contact them about a refund if you've paid."
                                                    confirmLabel="Cancel booking"
                                                    onConfirm={() =>
                                                        router.put(
                                                            portalBookings.update(
                                                                booking.id,
                                                            ).url,
                                                            {
                                                                status: 'cancelled',
                                                            },
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                />
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
