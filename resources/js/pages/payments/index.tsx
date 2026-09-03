import { Head, router } from '@inertiajs/react';

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

type PaymentRow = {
    id: number;
    invoice_id: number;
    invoice_number: string;
    client_name: string;
    gateway_label: string;
    amount: string;
    net_amount: string;
    currency: string;
    status: string;
    status_label: string;
    reference: string | null;
    paid_at: string | null;
    created_at: string | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

const variant: Record<string, 'secondary' | 'success' | 'destructive'> = {
    pending: 'secondary',
    succeeded: 'success',
    failed: 'destructive',
    refunded: 'destructive',
};

export default function PaymentsIndex({
    payments,
    currency,
}: {
    payments: Paginated<PaymentRow>;
    currency: string;
}) {
    return (
        <AppLayout title="Payments">
            <Head title="Payments" />

            <Card>
                <CardContent className="p-0">
                    {payments.data.length === 0 ? (
                        <p className="text-muted-foreground p-10 text-center text-sm">
                            Payments will show here as your invoices get paid.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>When</TableHead>
                                    <TableHead>Invoice</TableHead>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Method</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Net</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {payments.data.map((payment) => (
                                    <TableRow
                                        key={payment.id}
                                        className="cursor-pointer"
                                        onClick={() =>
                                            router.get(
                                                invoices.show(
                                                    payment.invoice_id,
                                                ).url,
                                            )
                                        }
                                    >
                                        <TableCell className="text-muted-foreground">
                                            {payment.paid_at
                                                ? formatDateTime(
                                                      payment.paid_at,
                                                  )
                                                : payment.created_at
                                                  ? formatDateTime(
                                                        payment.created_at,
                                                    )
                                                  : '—'}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {payment.invoice_number}
                                        </TableCell>
                                        <TableCell>
                                            {payment.client_name}
                                        </TableCell>
                                        <TableCell>
                                            {payment.gateway_label}
                                            {payment.reference
                                                ? ` · ${payment.reference}`
                                                : ''}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                payment.amount,
                                                payment.currency || currency,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                payment.net_amount,
                                                payment.currency || currency,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    variant[payment.status] ??
                                                    'secondary'
                                                }
                                            >
                                                {payment.status_label}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>

            {payments.links.length > 3 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {payments.links.map((link, index) => (
                        <Button
                            key={index}
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
