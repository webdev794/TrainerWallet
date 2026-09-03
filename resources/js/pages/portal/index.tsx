import { Head, Link } from '@inertiajs/react';

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
import { formatDate, formatMoney } from '@/lib/format';
import * as portalInvoices from '@/routes/portal/invoices';

type InvoiceRow = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    total: string;
    outstanding: number;
    currency: string;
    due_date: string | null;
};

const variant: Record<
    string,
    'secondary' | 'success' | 'warning' | 'outline' | 'destructive'
> = {
    sent: 'secondary',
    viewed: 'secondary',
    partially_paid: 'warning',
    paid: 'success',
    overdue: 'destructive',
    void: 'outline',
};

export default function PortalIndex({ invoices }: { invoices: InvoiceRow[] }) {
    return (
        <ClientPortalLayout title="Your invoices">
            <Head title="Invoices" />

            <Card>
                <CardContent className="p-0">
                    {invoices.length === 0 ? (
                        <p className="text-muted-foreground p-10 text-center text-sm">
                            You have no invoices yet.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Invoice</TableHead>
                                    <TableHead>Due</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead>Balance</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoices.map((invoice) => (
                                    <TableRow key={invoice.id}>
                                        <TableCell className="font-medium">
                                            {invoice.number}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {invoice.due_date
                                                ? formatDate(invoice.due_date)
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                invoice.total,
                                                invoice.currency,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                invoice.outstanding,
                                                invoice.currency,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    variant[invoice.status] ??
                                                    'secondary'
                                                }
                                            >
                                                {invoice.status_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                asChild
                                                size="sm"
                                                variant={
                                                    invoice.outstanding > 0
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                            >
                                                <Link
                                                    href={
                                                        portalInvoices.show(
                                                            invoice.id,
                                                        ).url
                                                    }
                                                >
                                                    {invoice.outstanding > 0
                                                        ? 'Pay'
                                                        : 'View'}
                                                </Link>
                                            </Button>
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
