import { Head, Link, router } from '@inertiajs/react';

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
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, formatMoney } from '@/lib/format';
import * as invoices from '@/routes/invoices';
import * as bookings from '@/routes/bookings';

type BookingRow = {
    id: number;
    client_name: string;
    service_name: string;
    amount: string;
    currency: string;
    scheduled_at: string | null;
    status: string;
    status_label: string;
    invoice_id: number | null;
    invoice_number: string | null;
    invoice_status: string | null;
    created_at: string | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

const variant: Record<string, 'secondary' | 'success' | 'outline'> = {
    confirmed: 'secondary',
    completed: 'success',
    cancelled: 'outline',
};

export default function BookingsIndex({
    bookings: page,
    currency,
}: {
    bookings: Paginated<BookingRow>;
    currency: string;
}) {
    return (
        <AppLayout title="Bookings">
            <Head title="Bookings" />

            <Card>
                <CardContent className="p-0">
                    {page.data.length === 0 ? (
                        <p className="text-muted-foreground p-10 text-center text-sm">
                            When clients book a service from your public
                            profile, it shows up here.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Booked</TableHead>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Service</TableHead>
                                    <TableHead>When</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Invoice</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {page.data.map((booking) => (
                                    <TableRow key={booking.id}>
                                        <TableCell className="text-muted-foreground">
                                            {booking.created_at
                                                ? formatDateTime(
                                                      booking.created_at,
                                                  )
                                                : '—'}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {booking.client_name}
                                        </TableCell>
                                        <TableCell>
                                            {booking.service_name}
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
                                                booking.currency || currency,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {booking.invoice_id ? (
                                                <Link
                                                    href={
                                                        invoices.show(
                                                            booking.invoice_id,
                                                        ).url
                                                    }
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
                                        <TableCell className="text-right">
                                            {booking.status === 'confirmed' && (
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            router.put(
                                                                bookings.update(
                                                                    booking.id,
                                                                ).url,
                                                                {
                                                                    status: 'completed',
                                                                },
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Mark done
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            router.put(
                                                                bookings.update(
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
                                                    >
                                                        Cancel
                                                    </Button>
                                                </div>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>

            {page.links.length > 3 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {page.links.map((link, i) => (
                        <Button
                            key={i}
                            variant={link.active ? 'default' : 'outline'}
                            size="sm"
                            disabled={!link.url}
                            onClick={() =>
                                link.url &&
                                router.get(
                                    link.url,
                                    {},
                                    {
                                        preserveState: true,
                                        preserveScroll: true,
                                    },
                                )
                            }
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
