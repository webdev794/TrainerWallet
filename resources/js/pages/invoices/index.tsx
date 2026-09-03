import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatMoney } from '@/lib/format';
import * as invoices from '@/routes/invoices';

type InvoiceRow = {
    id: number;
    number: string;
    client_name: string;
    status: string;
    status_label: string;
    total: string;
    outstanding: number;
    currency: string;
    due_date: string | null;
    issued_at: string | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

const statusVariant: Record<
    string,
    'secondary' | 'success' | 'warning' | 'outline' | 'destructive'
> = {
    draft: 'outline',
    sent: 'secondary',
    viewed: 'secondary',
    partially_paid: 'warning',
    paid: 'success',
    overdue: 'destructive',
    void: 'outline',
};

export default function InvoicesIndex({
    invoices: page,
    filters,
    currency,
}: {
    invoices: Paginated<InvoiceRow>;
    filters: { status: string };
    currency: string;
}) {
    const [status, setStatus] = useState(filters.status);

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                invoices.index().url,
                { status },
                { preserveState: true, replace: true, preserveScroll: true },
            );
        }, 200);
        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [status]);

    return (
        <AppLayout
            title="Invoices"
            actions={
                <Button asChild size="sm">
                    <Link href={invoices.create().url}>
                        <Plus className="size-4" /> New invoice
                    </Link>
                </Button>
            }
        >
            <Head title="Invoices" />

            <div className="mb-4">
                <Select
                    value={status}
                    onChange={(e) => setStatus(e.target.value)}
                    className="max-w-[12rem]"
                >
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="viewed">Viewed</option>
                    <option value="partially_paid">Partially paid</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                    <option value="void">Void</option>
                </Select>
            </div>

            <Card>
                <CardContent className="p-0">
                    {page.data.length === 0 ? (
                        <p className="text-muted-foreground p-10 text-center text-sm">
                            No invoices yet. Create one from a client or logged
                            sessions.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Number</TableHead>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Issued</TableHead>
                                    <TableHead>Due</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead>Balance</TableHead>
                                    <TableHead>Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {page.data.map((invoice) => (
                                    <TableRow
                                        key={invoice.id}
                                        className="cursor-pointer"
                                        onClick={() =>
                                            router.get(
                                                invoices.show(invoice.id).url,
                                            )
                                        }
                                    >
                                        <TableCell className="font-medium">
                                            {invoice.number}
                                        </TableCell>
                                        <TableCell>
                                            {invoice.client_name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {invoice.issued_at
                                                ? formatDate(invoice.issued_at)
                                                : '—'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {invoice.due_date
                                                ? formatDate(invoice.due_date)
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                invoice.total,
                                                invoice.currency || currency,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {formatMoney(
                                                invoice.outstanding,
                                                invoice.currency || currency,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    statusVariant[
                                                        invoice.status
                                                    ] ?? 'secondary'
                                                }
                                            >
                                                {invoice.status_label}
                                            </Badge>
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
                    {page.links.map((link, index) => (
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
